#!/usr/bin/env python3
"""
Exportiert die Korrelationsstruktur des Views `datenm_values_sem_dichte_lehrer_type_3`
als JSON-Datei für Kapitel 6.x.6.

Berechnet Pearson-Korrelationen gemäß
    rho_jk = Cov(x_j, x_k) / (sigma_j * sigma_k)
über die sieben FRZK-Dimensionen.

Optional können Filter auf Gruppe, Lehrkraft, Fach und Datumsbereich gesetzt werden.
"""

from __future__ import annotations

import argparse
import json
import math
from datetime import date, datetime
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Sequence, Tuple

import pymysql

DIMENSIONS: List[str] = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

SHORT_NAMES: Dict[str, str] = {
    "x_kognition": "kognition",
    "x_sozial": "sozial",
    "x_affektiv": "affektiv",
    "x_motivation": "motivation",
    "x_methodik": "methodik",
    "x_performanz": "performanz",
    "x_regulation": "regulation",
}


@dataclass
class DbConfig:
    host: str
    port: int
    user: str
    password: str
    database: str
    charset: str = "utf8mb4"


@dataclass
class FilterConfig:
    gruppe_id: Optional[int] = None
    lehrkraft_id: Optional[int] = None
    fach: Optional[str] = None
    datum_von: Optional[str] = None
    datum_bis: Optional[str] = None
    polaritaet: Optional[int] = None


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Exportiert Korrelationsdaten aus datenm_values_sem_dichte_lehrer_type_3 als JSON."
    )
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="icas")
    parser.add_argument(
        "--output",
        default="korrelationsstruktur_lehrkraftsicht.json",
        help="Pfad der auszugebenden JSON-Datei",
    )
    parser.add_argument("--gruppe-id", type=int)
    parser.add_argument("--lehrkraft-id", type=int)
    parser.add_argument("--fach")
    parser.add_argument("--datum-von", help="Format YYYY-MM-DD")
    parser.add_argument("--datum-bis", help="Format YYYY-MM-DD")
    parser.add_argument("--polaritaet", type=int, choices=[-1, 0, 1])
    return parser.parse_args()


def build_query(filters: FilterConfig) -> Tuple[str, List[Any]]:
    sql = f"""
        SELECT
            id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            lehrkraft_id,
            id_mtr_rueckkopplung_datenmaske,
            {', '.join(DIMENSIONS)}
        FROM datenm_values_sem_dichte_lehrer_type_3
        WHERE datum>'2025-09-01' and lehrkraft_id<>1 and 1=1
    """
    params: List[Any] = []

    if filters.gruppe_id is not None:
        sql += " AND gruppe_id = %s"
        params.append(filters.gruppe_id)
    if filters.lehrkraft_id is not None:
        sql += " AND lehrkraft_id = %s"
        params.append(filters.lehrkraft_id)
    if filters.fach:
        sql += " AND fach = %s"
        params.append(filters.fach)
    if filters.datum_von:
        sql += " AND datum >= %s"
        params.append(filters.datum_von)
    if filters.datum_bis:
        sql += " AND datum <= %s"
        params.append(filters.datum_bis)
    if filters.polaritaet is not None:
        sql += " AND polaritaet_gesamt = %s"
        params.append(filters.polaritaet)

    sql += " ORDER BY datum ASC, id ASC"
    return sql, params


def fetch_rows(db: DbConfig, filters: FilterConfig) -> List[Dict[str, Any]]:
    sql, params = build_query(filters)
    connection = pymysql.connect(
        host=db.host,
        port=db.port,
        user=db.user,
        password=db.password,
        database=db.database,
        charset=db.charset,
        cursorclass=pymysql.cursors.DictCursor,
    )
    try:
        with connection.cursor() as cursor:
            cursor.execute(sql, params)
            rows = cursor.fetchall()
    finally:
        connection.close()
    return rows


def mean(values: Sequence[float]) -> float:
    return sum(values) / len(values)


def sample_variance(values: Sequence[float], mu: float) -> float:
    if len(values) < 2:
        return 0.0
    return sum((x - mu) ** 2 for x in values) / (len(values) - 1)


def sample_covariance(xs: Sequence[float], ys: Sequence[float], mean_x: float, mean_y: float) -> float:
    if len(xs) < 2:
        return 0.0
    return sum((x - mean_x) * (y - mean_y) for x, y in zip(xs, ys)) / (len(xs) - 1)


def compute_statistics(rows: List[Dict[str, Any]]) -> Dict[str, Any]:
    n = len(rows)
    if n < 2:
        raise ValueError("Es werden mindestens 2 Datensätze für die Korrelationsberechnung benötigt.")

    vectors: Dict[str, List[float]] = {
        dim: [float(row[dim]) for row in rows if row[dim] is not None] for dim in DIMENSIONS
    }

    means: Dict[str, float] = {}
    stddevs: Dict[str, float] = {}
    variances: Dict[str, float] = {}

    for dim in DIMENSIONS:
        values = vectors[dim]
        if len(values) != n:
            raise ValueError(f"Dimension {dim} enthält NULL-Werte und kann daher nicht vollständig korreliert werden.")
        mu = mean(values)
        var = sample_variance(values, mu)
        means[dim] = mu
        variances[dim] = var
        stddevs[dim] = math.sqrt(var)

    correlation_matrix: List[List[Optional[float]]] = []
    covariance_matrix: List[List[float]] = []
    pairwise: List[Dict[str, Any]] = []

    for dim_i in DIMENSIONS:
        row_corr: List[Optional[float]] = []
        row_cov: List[float] = []
        xs = vectors[dim_i]
        for dim_j in DIMENSIONS:
            ys = vectors[dim_j]
            cov = sample_covariance(xs, ys, means[dim_i], means[dim_j])
            row_cov.append(cov)
            sigma_i = stddevs[dim_i]
            sigma_j = stddevs[dim_j]
            if sigma_i == 0 or sigma_j == 0:
                rho: Optional[float] = None
            else:
                rho = cov / (sigma_i * sigma_j)
            row_corr.append(rho)
            pairwise.append(
                {
                    "dimension_1": SHORT_NAMES[dim_i],
                    "dimension_2": SHORT_NAMES[dim_j],
                    "covarianz": cov,
                    "sigma_1": sigma_i,
                    "sigma_2": sigma_j,
                    "rho": rho,
                }
            )
        covariance_matrix.append(row_cov)
        correlation_matrix.append(row_corr)

    upper_triangle = []
    for i, dim_i in enumerate(DIMENSIONS):
        for j in range(i + 1, len(DIMENSIONS)):
            dim_j = DIMENSIONS[j]
            upper_triangle.append(
                {
                    "dimension_1": SHORT_NAMES[dim_i],
                    "dimension_2": SHORT_NAMES[dim_j],
                    "rho": correlation_matrix[i][j],
                    "covarianz": covariance_matrix[i][j],
                }
            )

    upper_triangle_sorted = sorted(
        upper_triangle,
        key=lambda item: -1.0 if item["rho"] is None else abs(item["rho"]),
        reverse=True,
    )

    return {
        "stichprobe": {
            "anzahl_datensaetze": n,
            "dimensionen": [SHORT_NAMES[d] for d in DIMENSIONS],
        },
        "deskriptiv": {
            SHORT_NAMES[dim]: {
                "mittelwert": means[dim],
                "varianz": variances[dim],
                "standardabweichung": stddevs[dim],
            }
            for dim in DIMENSIONS
        },
        "korrelationsoperator": "rho_jk = Cov(x_j, x_k) / (sigma_j * sigma_k)",
        "korrelationsmatrix": {
            "dimensionen": [SHORT_NAMES[d] for d in DIMENSIONS],
            "werte": correlation_matrix,
        },
        "kovarianzmatrix": {
            "dimensionen": [SHORT_NAMES[d] for d in DIMENSIONS],
            "werte": covariance_matrix,
        },
        "paarweise_korrelationen": pairwise,
        "stärkste_kopplungen_absteigend": upper_triangle_sorted,
    }


def build_metadata(args: argparse.Namespace) -> Dict[str, Any]:
    return {
        "quelle": "datenm_values_sem_dichte_lehrer_type_3",
        "beschreibung": "Korrelationsstruktur des Systems nach Kapitel 6.x.6",
        "filter": {
            "gruppe_id": args.gruppe_id,
            "lehrkraft_id": args.lehrkraft_id,
            "fach": args.fach,
            "datum_von": args.datum_von,
            "datum_bis": args.datum_bis,
            "polaritaet": args.polaritaet,
        },
    }


def json_default_serializer(obj: Any) -> Any:
    if isinstance(obj, (date, datetime)):
        return obj.isoformat()
    raise TypeError(f"Object of type {obj.__class__.__name__} is not JSON serializable")


def save_json(payload: Dict[str, Any], output_path: Path) -> None:
    output_path.parent.mkdir(parents=True, exist_ok=True)
    with output_path.open("w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2, default=json_default_serializer)


def main() -> None:
    args = parse_args()
    db = DbConfig(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
    )
    filters = FilterConfig(
        gruppe_id=args.gruppe_id,
        lehrkraft_id=args.lehrkraft_id,
        fach=args.fach,
        datum_von=args.datum_von,
        datum_bis=args.datum_bis,
        polaritaet=args.polaritaet,
    )

    rows = fetch_rows(db, filters)
    stats = compute_statistics(rows)

    payload = {
        "metadaten": build_metadata(args),
        "datenbasis": rows,
        "auswertung": stats,
    }

    output_path = Path(args.output)
    save_json(payload, output_path)
    print(f"JSON erfolgreich exportiert: {output_path.resolve()}")
    print(f"Verarbeitete Datensätze: {len(rows)}")


if __name__ == "__main__":
    main()
