#!/usr/bin/env python3
"""Exportiert die Korrelationsstruktur aus datenm_values_sem_dichte_lehrer_type_3 nach JSON.

Beispiel:
python frzk_korrelations_export.py \
  --host localhost --user root --password "" --database icas \
  --output frzk_korrelationsstruktur.json

Optional filterbar nach gruppe_id, lehrkraft_id, fach und Zeitraum.
"""
from __future__ import annotations

import argparse
import json
import math
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Iterable

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def get_connection(args: argparse.Namespace):
    """Versucht mysql.connector, dann pymysql."""
    try:
        import mysql.connector  # type: ignore

        return mysql.connector.connect(
            host="localhost",
            port=3306,
            user="root",
            password="",
            database="icas",
            charset="utf8mb4",
        )
    except Exception as first_error:
        try:
            import pymysql  # type: ignore

            return pymysql.connect(
                host="localhost",
                port=3306,
                user="root",
                password="",
                database="icas",
                charset="utf8mb4",
                cursorclass=pymysql.cursors.DictCursor,
            )
        except Exception as second_error:
            raise RuntimeError(
                "Weder mysql.connector noch pymysql konnte verwendet werden. "
                f"mysql.connector-Fehler: {first_error}; pymysql-Fehler: {second_error}"
            )


def build_query(args: argparse.Namespace) -> tuple[str, list[Any]]:
    select_cols = [
        "id",
        "gruppe_id",
        "teilnehmer_id",
        "fach",
        "datum",
        "thema",
        "lehrkraft_id",
        "id_mtr_rueckkopplung_datenmaske",
        *DIMENSIONS,
        "dominante_dimension",
        "dominante_dimension_wert",
        "polaritaet_gesamt",
        "d_semantisch",
    ]
    sql = [
        f"SELECT {', '.join(select_cols)}",
        "FROM datenm_values_sem_dichte_lehrer_type_3",
        "WHERE 1=1 and (fach='MAT' or fach='PHY') and datum>='2025-09-01' and weekofyear(datum)<>44 and lehrkraft_id<>1 "
    ]
    params: list[Any] = []

    if args.gruppe_id is not None:
        sql.append("AND gruppe_id = %s")
        params.append(args.gruppe_id)
    if args.lehrkraft_id is not None:
        sql.append("AND lehrkraft_id = %s")
        params.append(args.lehrkraft_id)
    if args.fach:
        sql.append("AND fach = %s")
        params.append(args.fach)
    if args.date_from:
        sql.append("AND datum >= %s")
        params.append(args.date_from)
    if args.date_to:
        sql.append("AND datum <= %s")
        params.append(args.date_to)

    sql.append("ORDER BY datum ASC, id ASC")
    return "\n".join(sql), params


def fetch_rows(args: argparse.Namespace) -> list[dict[str, Any]]:
    conn = get_connection(args)
    try:
        cur = conn.cursor()
        query, params = build_query(args)
        cur.execute(query, params)
        rows = cur.fetchall()

        # mysql.connector liefert Tupel, pymysql Dicts
        if rows and not isinstance(rows[0], dict):
            columns = [desc[0] for desc in cur.description]
            rows = [dict(zip(columns, row)) for row in rows]

        cleaned: list[dict[str, Any]] = []
        for row in rows:
            entry = dict(row)
            for dim in DIMENSIONS:
                entry[dim] = float(entry[dim]) if entry[dim] is not None else math.nan
            cleaned.append(entry)
        return cleaned
    finally:
        conn.close()


def mean(values: Iterable[float]) -> float:
    vals = [v for v in values if not math.isnan(v)]
    return sum(vals) / len(vals) if vals else math.nan


def std_sample(values: Iterable[float]) -> float:
    vals = [v for v in values if not math.isnan(v)]
    n = len(vals)
    if n < 2:
        return math.nan
    m = mean(vals)
    return math.sqrt(sum((v - m) ** 2 for v in vals) / (n - 1))


def covariance(xs: list[float], ys: list[float]) -> float:
    pairs = [(x, y) for x, y in zip(xs, ys) if not math.isnan(x) and not math.isnan(y)]
    n = len(pairs)
    if n < 2:
        return math.nan
    mx = sum(x for x, _ in pairs) / n
    my = sum(y for _, y in pairs) / n
    return sum((x - mx) * (y - my) for x, y in pairs) / (n - 1)


def pearson(xs: list[float], ys: list[float]) -> float:
    cov = covariance(xs, ys)
    sx = std_sample(xs)
    sy = std_sample(ys)
    if math.isnan(cov) or math.isnan(sx) or math.isnan(sy) or sx == 0 or sy == 0:
        return math.nan
    return cov / (sx * sy)


@dataclass
class PairResult:
    dim_a: str
    dim_b: str
    covariance: float
    correlation: float


def build_statistics(rows: list[dict[str, Any]]) -> dict[str, Any]:
    series = {dim: [row[dim] for row in rows] for dim in DIMENSIONS}

    descriptive = {}
    for dim in DIMENSIONS:
        descriptive[dim] = {
            "mean": mean(series[dim]),
            "std": std_sample(series[dim]),
            "min": min(v for v in series[dim] if not math.isnan(v)) if rows else math.nan,
            "max": max(v for v in series[dim] if not math.isnan(v)) if rows else math.nan,
        }

    corr_matrix: dict[str, dict[str, float]] = {}
    cov_matrix: dict[str, dict[str, float]] = {}
    pair_results: list[PairResult] = []

    for dim_a in DIMENSIONS:
        corr_matrix[dim_a] = {}
        cov_matrix[dim_a] = {}
        for dim_b in DIMENSIONS:
            cov = covariance(series[dim_a], series[dim_b])
            corr = pearson(series[dim_a], series[dim_b])
            cov_matrix[dim_a][dim_b] = cov
            corr_matrix[dim_a][dim_b] = corr
            if dim_a < dim_b:
                pair_results.append(PairResult(dim_a, dim_b, cov, corr))

    strongest_positive = sorted(
        [p for p in pair_results if not math.isnan(p.correlation)],
        key=lambda p: p.correlation,
        reverse=True,
    )[:10]
    strongest_negative = sorted(
        [p for p in pair_results if not math.isnan(p.correlation)],
        key=lambda p: p.correlation,
    )[:10]

    return {
        "dimensions": DIMENSIONS,
        "n": len(rows),
        "deskriptiv": descriptive,
        "kovarianzmatrix": cov_matrix,
        "korrelationsmatrix": corr_matrix,
        "staerkste_positive_kopplungen": [p.__dict__ for p in strongest_positive],
        "staerkste_negative_kopplungen": [p.__dict__ for p in strongest_negative],
    }


def sanitize_rows(rows: list[dict[str, Any]], include_rows: bool, max_rows: int | None) -> list[dict[str, Any]]:
    if not include_rows:
        return []
    if max_rows is not None:
        rows = rows[:max_rows]

    out = []
    for row in rows:
        item = {}
        for key, value in row.items():
            if hasattr(value, "isoformat"):
                item[key] = value.isoformat()
            elif isinstance(value, float) and math.isnan(value):
                item[key] = None
            else:
                item[key] = value
        out.append(item)
    return out


def main() -> None:
    parser = argparse.ArgumentParser(description="FRZK-Korrelationsstruktur aus datenm_values_sem_dichte_lehrer_type_3 exportieren")
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="icas")
    parser.add_argument("--gruppe-id", dest="gruppe_id", type=int)
    parser.add_argument("--lehrkraft-id", dest="lehrkraft_id", type=int)
    parser.add_argument("--fach")
    parser.add_argument("--date-from")
    parser.add_argument("--date-to")
    parser.add_argument("--output", default="frzk_korrelationsstruktur.json")
    parser.add_argument("--include-rows", action="store_true", help="Einzelzeilen zusätzlich im JSON speichern")
    parser.add_argument("--max-rows", type=int, default=None, help="Begrenzt die Anzahl gespeicherter Einzelzeilen")
    args = parser.parse_args()

    rows = fetch_rows(args)
    stats = build_statistics(rows)

    payload = {
        "quelle": "datenm_values_sem_dichte_lehrer_type_3",
        "filter": {
            "gruppe_id": args.gruppe_id,
            "lehrkraft_id": args.lehrkraft_id,
            "fach": args.fach,
            "date_from": args.date_from,
            "date_to": args.date_to,
        },
        "formel": {
            "kovarianz": "Cov(x_j, x_k)",
            "korrelation": "rho_jk = Cov(x_j, x_k) / (sigma_j * sigma_k)",
        },
        "statistik": stats,
        "daten": sanitize_rows(rows, include_rows=args.include_rows, max_rows=args.max_rows),
    }

    output_path = Path(args.output)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"JSON exportiert nach: {output_path.resolve()}")
    print(f"Anzahl Datensätze: {stats['n']}")


if __name__ == "__main__":
    main()
