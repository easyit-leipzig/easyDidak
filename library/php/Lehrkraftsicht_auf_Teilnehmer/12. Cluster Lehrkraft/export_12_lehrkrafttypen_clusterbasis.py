#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Export 12: Clusterbasis Lehrkrafttypen aus analyze_lehrkraftdaten / sql_semantische_dichte_lehrer_type_1

Ziel:
- Exportiert eine JSON-Basisdatei für die spätere Clusteranalyse.
- Enthält drei Sichten:
  1. alle_lehrkraefte
  2. lehrkraft_1
  3. ohne_lehrkraft_1
- Zusätzlich werden pro lehrkraft_id aggregierte Merkmale erzeugt.

Standard-DB-Konfiguration nach Projektvorgabe.
"""

from __future__ import annotations

import json
import math
import statistics
from collections import Counter, defaultdict
from datetime import date, datetime
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Sequence, Tuple

import mysql.connector
from mysql.connector import Error

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "connection_timeout": 5,
    "use_pure": True,
}

OUTPUT_JSON = Path("lehrkrafttypen_clusterbasis.json")

# Bevorzugte Quelle. Falls diese View nicht existiert, wird automatisch auf sql_semantische_dichte_lehrer_type_1 gewechselt.
PREFERRED_SOURCE = "analyze_lehrkraftdaten"
FALLBACK_SOURCE = "sql_semantische_dichte_lehrer_type_1"

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

X_COLUMNS = [f"x_{d}" for d in DIMENSIONS]
SUM_COLUMNS = [f"sum_{d}" for d in DIMENSIONS]
OPTIONAL_COLUMNS = [
    "d_semantisch",
    "token_anzahl",
    "funktionsklassen_anzahl_gesamt",
    "operator_count",
    "modulator_count",
    "has_operator",
    "polaritaet_gesamt",
    "dominante_dimension",
    "dominante_dimension_wert",
    "gruppe_id",
    "teilnehmer_id",
    "ue_id",
    "datum",
    "zeitpunkt",
    "fach",
    "thema",
]


def json_default(value: Any) -> Any:
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    return str(value)


def connect():
    return mysql.connector.connect(**DB_CONFIG)


def table_or_view_exists(cursor, name: str) -> bool:
    cursor.execute(
        """
        SELECT COUNT(*) AS n
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = %s
        """,
        (name,),
    )
    row = cursor.fetchone()
    return bool(row and row["n"] > 0)


def get_columns(cursor, source: str) -> List[str]:
    cursor.execute(
        """
        SELECT column_name
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = %s
        ORDER BY ordinal_position
        """,
        (source,),
    )
    return [r["column_name"] for r in cursor.fetchall()]


def choose_source(cursor) -> Tuple[str, List[str]]:
    for source in (PREFERRED_SOURCE, FALLBACK_SOURCE):
        if table_or_view_exists(cursor, source):
            columns = get_columns(cursor, source)
            required = {"lehrkraft_id", *X_COLUMNS}
            missing = required.difference(columns)
            if missing:
                raise RuntimeError(
                    f"Quelle '{source}' existiert, aber Pflichtspalten fehlen: {sorted(missing)}"
                )
            return source, columns
    raise RuntimeError(
        f"Weder '{PREFERRED_SOURCE}' noch '{FALLBACK_SOURCE}' wurde in der Datenbank gefunden."
    )


def safe_float(value: Any) -> Optional[float]:
    if value is None:
        return None
    try:
        v = float(value)
        if math.isnan(v) or math.isinf(v):
            return None
        return v
    except (TypeError, ValueError):
        return None


def mean(values: Iterable[Optional[float]]) -> Optional[float]:
    clean = [v for v in values if v is not None]
    return float(statistics.fmean(clean)) if clean else None


def std(values: Iterable[Optional[float]]) -> Optional[float]:
    clean = [v for v in values if v is not None]
    return float(statistics.pstdev(clean)) if len(clean) > 1 else 0.0 if len(clean) == 1 else None


def minimum(values: Iterable[Optional[float]]) -> Optional[float]:
    clean = [v for v in values if v is not None]
    return float(min(clean)) if clean else None


def maximum(values: Iterable[Optional[float]]) -> Optional[float]:
    clean = [v for v in values if v is not None]
    return float(max(clean)) if clean else None


def dominant_distribution(rows: Sequence[Dict[str, Any]]) -> Dict[str, int]:
    c = Counter(str(r.get("dominante_dimension")) for r in rows if r.get("dominante_dimension") not in (None, ""))
    return dict(c.most_common())


def polarity_distribution(rows: Sequence[Dict[str, Any]]) -> Dict[str, int]:
    c = Counter(str(r.get("polaritaet_gesamt")) for r in rows if r.get("polaritaet_gesamt") is not None)
    return dict(c.most_common())


def vector_norm(row: Dict[str, Any], prefix: str = "x_") -> Optional[float]:
    vals = [safe_float(row.get(f"{prefix}{d}")) for d in DIMENSIONS]
    if any(v is None for v in vals):
        return None
    return math.sqrt(sum(float(v) ** 2 for v in vals))


def aggregate_teacher(lehrkraft_id: int, rows: Sequence[Dict[str, Any]]) -> Dict[str, Any]:
    agg: Dict[str, Any] = {
        "lehrkraft_id": lehrkraft_id,
        "n_saetze": len(rows),
        "dominante_dimension_verteilung": dominant_distribution(rows),
        "polaritaet_verteilung": polarity_distribution(rows),
    }

    for col in X_COLUMNS:
        vals = [safe_float(r.get(col)) for r in rows]
        key = col.replace("x_", "")
        agg[f"avg_{key}"] = mean(vals)
        agg[f"std_{key}"] = std(vals)
        agg[f"min_{key}"] = minimum(vals)
        agg[f"max_{key}"] = maximum(vals)

    for col in SUM_COLUMNS:
        if col in rows[0]:
            vals = [safe_float(r.get(col)) for r in rows]
            key = col.replace("sum_", "")
            agg[f"avg_sum_{key}"] = mean(vals)
            agg[f"std_sum_{key}"] = std(vals)

    for col in [
        "d_semantisch",
        "token_anzahl",
        "funktionsklassen_anzahl_gesamt",
        "operator_count",
        "modulator_count",
        "dominante_dimension_wert",
    ]:
        if col in rows[0]:
            vals = [safe_float(r.get(col)) for r in rows]
            agg[f"avg_{col}"] = mean(vals)
            agg[f"std_{col}"] = std(vals)
            agg[f"min_{col}"] = minimum(vals)
            agg[f"max_{col}"] = maximum(vals)

    norms = [vector_norm(r, "x_") for r in rows]
    agg["avg_x_norm"] = mean(norms)
    agg["std_x_norm"] = std(norms)

    # Balance: je kleiner die Standardabweichung der mittleren Dimensionen, desto balancierter der Lehrkraftzustandsraum.
    avg_vec = [agg.get(f"avg_{d}") for d in DIMENSIONS]
    clean_avg_vec = [v for v in avg_vec if v is not None]
    agg["dimensionale_balance"] = 1.0 / (1.0 + statistics.pstdev(clean_avg_vec)) if len(clean_avg_vec) > 1 else None

    # Dominanzachse aus mittleren Beträgen.
    if len(clean_avg_vec) == 7:
        pairs = list(zip(DIMENSIONS, clean_avg_vec))
        dom_dim, dom_val = max(pairs, key=lambda p: abs(p[1]))
        agg["aggregierte_dominante_dimension"] = dom_dim
        agg["aggregierte_dominante_dimension_wert"] = dom_val
    else:
        agg["aggregierte_dominante_dimension"] = None
        agg["aggregierte_dominante_dimension_wert"] = None

    return agg


def make_scope(name: str, rows: Sequence[Dict[str, Any]]) -> Dict[str, Any]:
    grouped: Dict[int, List[Dict[str, Any]]] = defaultdict(list)
    for r in rows:
        lid = r.get("lehrkraft_id")
        if lid is not None:
            grouped[int(lid)].append(r)

    teachers = [aggregate_teacher(lid, teacher_rows) for lid, teacher_rows in sorted(grouped.items())]

    return {
        "scope": name,
        "n_rows": len(rows),
        "n_lehrkraefte": len(teachers),
        "lehrkraft_ids": sorted(grouped.keys()),
        "teacher_aggregates": teachers,
    }


def fetch_rows(cursor, source: str, available_columns: Sequence[str]) -> List[Dict[str, Any]]:
    selected = ["lehrkraft_id"]
    for col in [*X_COLUMNS, *SUM_COLUMNS, *OPTIONAL_COLUMNS]:
        if col in available_columns and col not in selected:
            selected.append(col)

    sql = f"SELECT {', '.join(f'`{c}`' for c in selected)} FROM `{source}` WHERE lehrkraft_id IS NOT NULL"
    cursor.execute(sql)
    rows = cursor.fetchall()

    # numerische Konvertierung für bekannte Zahlenfelder
    numeric_prefixes = ("x_", "sum_", "avg_", "std_", "min_", "max_")
    numeric_cols = {
        "d_semantisch",
        "token_anzahl",
        "funktionsklassen_anzahl_gesamt",
        "operator_count",
        "modulator_count",
        "has_operator",
        "polaritaet_gesamt",
        "dominante_dimension_wert",
        "gruppe_id",
        "teilnehmer_id",
        "ue_id",
    }
    for r in rows:
        for k, v in list(r.items()):
            if k.startswith(numeric_prefixes) or k in numeric_cols:
                fv = safe_float(v)
                r[k] = fv if fv is not None else v
    return rows


def main() -> None:
    try:
        conn = connect()
        cursor = conn.cursor(dictionary=True)
        source, columns = choose_source(cursor)
        rows = fetch_rows(cursor, source, columns)

        scopes = {
            "alle_lehrkraefte": make_scope("alle_lehrkraefte", rows),
            "lehrkraft_1": make_scope("lehrkraft_1", [r for r in rows if int(r["lehrkraft_id"]) == 1]),
            "ohne_lehrkraft_1": make_scope("ohne_lehrkraft_1", [r for r in rows if int(r["lehrkraft_id"]) != 1]),
        }

        payload = {
            "metadata": {
                "created_at": datetime.now().isoformat(timespec="seconds"),
                "database": DB_CONFIG["database"],
                "source": source,
                "preferred_source": PREFERRED_SOURCE,
                "fallback_source": FALLBACK_SOURCE,
                "dimensionen": DIMENSIONS,
                "beschreibung": "Clusterbasis zur Extraktion von Lehrkrafttypen aus FRZK-Lehrkraftdaten.",
            },
            "scopes": scopes,
        }

        OUTPUT_JSON.write_text(json.dumps(payload, ensure_ascii=False, indent=2, default=json_default), encoding="utf-8")
        print(f"Export abgeschlossen: {OUTPUT_JSON.resolve()}")
        print(f"Quelle: {source}")
        print(f"Datensätze: {len(rows)}")
        print(f"Lehrkräfte: {scopes['alle_lehrkraefte']['n_lehrkraefte']}")

    except Error as exc:
        raise SystemExit(f"MySQL-Fehler: {exc}") from exc
    finally:
        try:
            cursor.close()  # type: ignore[name-defined]
            conn.close()    # type: ignore[name-defined]
        except Exception:
            pass


if __name__ == "__main__":
    main()
