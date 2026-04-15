#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
aggregate_sem_dichte.py

Vollständige Aggregation und JSON-Export aus:
datenm_values_sem_dichte_lehrer_type_3

Funktionen:
- MySQL/MariaDB-Verbindung via pymysql
- robuste JSON-Serialisierung (inkl. Decimal, date, datetime)
- Kollationskonflikte bei dominante_dimension werden vermieden
- flexible Filter
- flexible Gruppierung
- Export aggregierter Mittelwerte, Min/Max, Std-Abweichungen
- Export von Polaritäts- und Dominanzverteilungen

Beispiel:
python aggregate_sem_dichte.py ^
  --host localhost ^
  --port 3306 ^
  --db icas ^
  --user root ^
  --password "" ^
  --group-by kw gruppe_id lehrkraft_id ^
  --date-from 2025-09-01 ^
  --fach MAT PHY ^
  --output sem_dichte_aggregation.json
"""

from __future__ import annotations

import argparse
import json
from dataclasses import dataclass
from datetime import date, datetime
from decimal import Decimal
from typing import Any, Iterable

import pymysql


DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DOM_DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

ALLOWED_GROUP_FIELDS = {
    "id",
    "gruppe_id",
    "teilnehmer_id",
    "fach",
    "datum",
    "thema",
    "wochentag",
    "day_number",
    "lehrkraft_id",
    "id_mtr_rueckkopplung_datenmaske",
    "type",
    "kw",
    "dominante_dimension",
    "polaritaet_gesamt",
}


@dataclass
class DBConfig:
    host: str
    port: int
    db: str
    user: str
    password: str
    charset: str = "utf8mb4"
    collation: str = "utf8mb4_general_ci"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Aggregiert datenm_values_sem_dichte_lehrer_type_3 und exportiert JSON."
    )

    parser.add_argument("--host", default="localhost", help="DB-Host")
    parser.add_argument("--port", type=int, default=3306, help="DB-Port")
    parser.add_argument("--db", default="icas", help="Datenbankname")
    parser.add_argument("--user", default="root", help="DB-Benutzer")
    parser.add_argument("--password", default="", help="DB-Passwort")
    parser.add_argument(
        "--table",
        default="datenm_values_sem_dichte_lehrer_type_3",
        help="Quelltabelle oder View",
    )
    parser.add_argument(
        "--group-by",
        nargs="+",
        default=["kw"],
        help=f"Gruppierungsfelder. Erlaubt: {', '.join(sorted(ALLOWED_GROUP_FIELDS))}",
    )

    parser.add_argument("--date-from", default=None, help="Startdatum, z. B. 2025-09-01")
    parser.add_argument("--date-to", default=None, help="Enddatum, z. B. 2025-12-31")
    parser.add_argument("--gruppe-id", type=int, default=None, help="Filter: gruppe_id")
    parser.add_argument("--lehrkraft-id", type=int, default=None, help="Filter: lehrkraft_id")
    parser.add_argument("--teilnehmer-id", type=int, default=None, help="Filter: teilnehmer_id")
    parser.add_argument("--type", type=int, default=None, help="Filter: type")
    parser.add_argument("--fach", nargs="*", default=None, help="Filter: Fachliste, z. B. MAT PHY")
    parser.add_argument(
        "--kw-from",
        type=int,
        default=None,
        help="Filter: kw >= Wert",
    )
    parser.add_argument(
        "--kw-to",
        type=int,
        default=None,
        help="Filter: kw <= Wert",
    )
    parser.add_argument(
        "--output",
        default="sem_dichte_aggregation.json",
        help="Zieldatei für JSON",
    )

    return parser.parse_args()


def validate_group_fields(fields: Iterable[str]) -> list[str]:
    group_fields = list(fields)
    invalid = [field for field in group_fields if field not in ALLOWED_GROUP_FIELDS]
    if invalid:
        raise ValueError(
            f"Ungültige group-by-Felder: {invalid}. Erlaubt sind: {sorted(ALLOWED_GROUP_FIELDS)}"
        )
    return group_fields


def build_query(table: str, group_fields: list[str], args: argparse.Namespace) -> tuple[str, list[Any]]:
    select_parts: list[str] = list(group_fields)

    for dim in DIMENSIONS:
        select_parts.append(f"AVG({dim}) AS avg_{dim}")
        select_parts.append(f"MIN({dim}) AS min_{dim}")
        select_parts.append(f"MAX({dim}) AS max_{dim}")
        select_parts.append(f"STDDEV_POP({dim}) AS std_{dim}")

    select_parts.extend(
        [
            "COUNT(*) AS n",
            "AVG(d_semantisch) AS avg_d_semantisch",
            "MIN(d_semantisch) AS min_d_semantisch",
            "MAX(d_semantisch) AS max_d_semantisch",
            "STDDEV_POP(d_semantisch) AS std_d_semantisch",
            "AVG(dominante_dimension_wert) AS avg_dominante_dimension_wert",
            "MIN(dominante_dimension_wert) AS min_dominante_dimension_wert",
            "MAX(dominante_dimension_wert) AS max_dominante_dimension_wert",
            "STDDEV_POP(dominante_dimension_wert) AS std_dominante_dimension_wert",
            "SUM(CASE WHEN polaritaet_gesamt > 0 THEN 1 ELSE 0 END) AS n_pos",
            "SUM(CASE WHEN polaritaet_gesamt < 0 THEN 1 ELSE 0 END) AS n_neg",
            "SUM(CASE WHEN polaritaet_gesamt = 0 THEN 1 ELSE 0 END) AS n_neutral",
            "AVG(CASE WHEN polaritaet_gesamt IS NOT NULL THEN polaritaet_gesamt END) AS avg_polaritaet",
        ]
    )

    # BINARY-Vergleich vermeidet Kollationskonflikte
    for dom in DOM_DIMENSIONS:
        select_parts.append(
            f"SUM(CASE WHEN BINARY dominante_dimension = BINARY '{dom}' THEN 1 ELSE 0 END) AS dom_{dom}"
        )

    where_clauses: list[str] = []
    params: list[Any] = []

    if args.date_from:
        where_clauses.append("datum >= %s")
        params.append(args.date_from)

    if args.date_to:
        where_clauses.append("datum <= %s")
        params.append(args.date_to)

    if args.gruppe_id is not None:
        where_clauses.append("gruppe_id = %s")
        params.append(args.gruppe_id)

    if args.lehrkraft_id is not None:
        where_clauses.append("lehrkraft_id = %s")
        params.append(args.lehrkraft_id)

    if args.teilnehmer_id is not None:
        where_clauses.append("teilnehmer_id = %s")
        params.append(args.teilnehmer_id)

    if args.type is not None:
        where_clauses.append("type = %s")
        params.append(args.type)

    if args.kw_from is not None:
        where_clauses.append("kw >= %s")
        params.append(args.kw_from)

    if args.kw_to is not None:
        where_clauses.append("kw <= %s")
        params.append(args.kw_to)

    if args.fach:
        placeholders = ", ".join(["%s"] * len(args.fach))
        where_clauses.append(f"fach IN ({placeholders})")
        params.extend(args.fach)

    sql = "SELECT\n  " + ",\n  ".join(select_parts) + f"\nFROM {table}"

    if where_clauses:
        sql += "\nWHERE " + " AND ".join(where_clauses) + " and lehrkraft_id<>1 "

    if group_fields:
        sql += "\nGROUP BY " + ", ".join(group_fields)
        sql += "\nORDER BY " + ", ".join(group_fields)

    return sql, params


def normalize_value(value: Any) -> Any:
    if value is None:
        return None

    if isinstance(value, Decimal):
        if value == value.to_integral_value():
            return int(value)
        return round(float(value), 6)

    if isinstance(value, float):
        return round(value, 6)

    if isinstance(value, (datetime, date)):
        return value.isoformat()

    if hasattr(value, "isoformat"):
        try:
            return value.isoformat()
        except Exception:
            pass

    return value


def row_to_dict(row: dict[str, Any]) -> dict[str, Any]:
    return {key: normalize_value(value) for key, value in row.items()}


def test_connection(config: DBConfig) -> None:
    conn = pymysql.connect(
        host=config.host,
        port=config.port,
        user=config.user,
        password=config.password,
        database=config.db,
        charset=config.charset,
        collation=config.collation,
        cursorclass=pymysql.cursors.DictCursor,
    )
    try:
        with conn.cursor() as cursor:
            cursor.execute("SELECT 1 AS ok")
            cursor.fetchone()
    finally:
        conn.close()


def fetch_rows(config: DBConfig, sql: str, params: list[Any]) -> list[dict[str, Any]]:
    conn = pymysql.connect(
        host=config.host,
        port=config.port,
        user=config.user,
        password=config.password,
        database=config.db,
        charset=config.charset,
        collation=config.collation,
        cursorclass=pymysql.cursors.DictCursor,
    )

    try:
        with conn.cursor() as cursor:
            cursor.execute(sql, params)
            rows = cursor.fetchall()
            return rows
    finally:
        conn.close()


def main() -> None:
    args = parse_args()
    group_fields = validate_group_fields(args.group_by)

    config = DBConfig(
        host=args.host,
        port=args.port,
        db=args.db,
        user=args.user,
        password=args.password,
    )

    test_connection(config)

    sql, params = build_query(args.table, group_fields, args)
    rows = fetch_rows(config, sql, params)

    payload = {
        "meta": {
            "source_table": args.table,
            "group_by": group_fields,
            "filters": {
                "date_from": args.date_from,
                "date_to": args.date_to,
                "gruppe_id": args.gruppe_id,
                "lehrkraft_id": args.lehrkraft_id,
                "teilnehmer_id": args.teilnehmer_id,
                "type": args.type,
                "fach": args.fach,
                "kw_from": args.kw_from,
                "kw_to": args.kw_to,
            },
            "dimensions": DIMENSIONS,
            "dominanz_dimensionen": DOM_DIMENSIONS,
            "row_count": len(rows),
            "sql": sql,
            "params": [normalize_value(x) for x in params],
        },
        "data": [row_to_dict(row) for row in rows],
    }

    with open(args.output, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)

    print(f"JSON exportiert: {args.output}")
    print(f"Gruppierung: {group_fields}")
    print(f"Zeilen: {len(rows)}")


if __name__ == "__main__":
    main()
