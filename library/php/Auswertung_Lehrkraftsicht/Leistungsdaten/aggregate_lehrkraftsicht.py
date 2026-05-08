#!/usr/bin/env python3
"""
FRZK-Aggregation leistungsspezifischer Daten aus datenm_values_sem_dichte_lehrer_type_3.

Das Skript liest Daten aus MySQL/MariaDB, aggregiert sie nach definierbaren Ebenen
(Woche, Gruppe, Lehrkraft, Teilnehmer) und exportiert die Ergebnisse als JSON.

Beispiel:
python frzk_aggregate_lehrkraftsicht.py \
  --host localhost --user root --password "" --database icas \
  --output frzk_leistung_aggregiert.json \
  --group-by gruppe_id kw \
  --exclude-kw 202544
"""

from __future__ import annotations

import argparse
import json
import math
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Iterable

import pandas as pd

try:
    import mysql.connector  # type: ignore
except Exception as exc:  # pragma: no cover
    raise SystemExit(
        "mysql-connector-python wird benötigt. Installation: pip install mysql-connector-python"
    ) from exc

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DIMENSION_NAMES = [d.replace("x_", "") for d in DIMENSIONS]


@dataclass
class QueryConfig:
    host: str
    user: str
    password: str
    database: str
    port: int = 3306


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Aggregiert FRZK-Leistungsdaten und exportiert JSON.")
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="icas")
    parser.add_argument("--output", default="frzk_leistung_aggregiert.json")
    parser.add_argument(
        "--group-by",
        nargs="+",
        default=["gruppe_id", "kw"],
        help="Aggregationsschlüssel, z. B. gruppe_id kw oder lehrkraft_id kw.",
    )
    parser.add_argument("--date-from", default=None)
    parser.add_argument("--date-to", default=None)
    parser.add_argument("--gruppe-id", type=int, default=None)
    parser.add_argument("--lehrkraft-id", type=int, default=None)
    parser.add_argument("--teilnehmer-id", type=int, default=None)
    parser.add_argument(
        "--exclude-kw",
        nargs="*",
        default=[],
        help="Kalenderwochen im Format YYYYWW, die ausgeschlossen werden sollen, z. B. 202544.",
    )
    return parser.parse_args()


def build_query(args: argparse.Namespace) -> tuple[str, list[Any]]:
    sql = """
        SELECT
            id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            YEARWEEK(datum, 1) AS kw,
            thema,
            bemerkung,
            lehrkraft_id,
            id_mtr_rueckkopplung_datenmaske,
            x_kognition,
            x_sozial,
            x_affektiv,
            x_motivation,
            x_methodik,
            x_performanz,
            x_regulation,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        FROM datenm_values_sem_dichte_lehrer_type_3
        WHERE 1 = 1 and datum>='2025-09-01' and (fach='MAT' or fach='PHY') and lehrkraft_id<>1
    """
    params: list[Any] = []

    if args.date_from:
        sql += " AND datum >= %s"
        params.append(args.date_from)
    if args.date_to:
        sql += " AND datum <= %s"
        params.append(args.date_to)
    if args.gruppe_id is not None:
        sql += " AND gruppe_id = %s"
        params.append(args.gruppe_id)
    if args.lehrkraft_id is not None:
        sql += " AND lehrkraft_id = %s"
        params.append(args.lehrkraft_id)
    if args.teilnehmer_id is not None:
        sql += " AND teilnehmer_id = %s"
        params.append(args.teilnehmer_id)
    if args.exclude_kw:
        placeholders = ",".join(["%s"] * len(args.exclude_kw))
        sql += f" AND YEARWEEK(datum, 1) NOT IN ({placeholders})"
        params.extend(args.exclude_kw)

    sql += " ORDER BY datum ASC, id ASC"
    return sql, params


def load_dataframe(cfg: QueryConfig, sql: str, params: Iterable[Any]) -> pd.DataFrame:
    conn = mysql.connector.connect(
        host=cfg.host,
        port=cfg.port,
        user=cfg.user,
        password=cfg.password,
        database=cfg.database,
    )
    try:
        df = pd.read_sql(sql, conn, params=list(params))
    finally:
        conn.close()

    if df.empty:
        raise SystemExit("Die Abfrage lieferte keine Datensätze.")

    df["datum"] = pd.to_datetime(df["datum"])
    for col in DIMENSIONS + ["dominante_dimension_wert", "d_semantisch", "polaritaet_gesamt"]:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df["vektor_norm"] = (df[DIMENSIONS] ** 2).sum(axis=1).pow(0.5)
    df["polaritaet_label"] = df["polaritaet_gesamt"].map({1: "positiv", 0: "neutral", -1: "negativ"}).fillna("unbekannt")
    return df


def safe_float(value: Any) -> float | None:
    if value is None:
        return None
    try:
        if pd.isna(value):
            return None
    except Exception:
        pass
    return float(value)


def compute_group_record(key_values: tuple[Any, ...], group_keys: list[str], g: pd.DataFrame) -> dict[str, Any]:
    record: dict[str, Any] = {k: v.item() if hasattr(v, "item") else v for k, v in zip(group_keys, key_values)}

    means = g[DIMENSIONS].mean()
    stds = g[DIMENSIONS].std(ddof=0).fillna(0.0)

    record["n"] = int(len(g))
    record["zeitraum_von"] = g["datum"].min().strftime("%Y-%m-%d")
    record["zeitraum_bis"] = g["datum"].max().strftime("%Y-%m-%d")
    record["mittelwerte"] = {dim.replace("x_", ""): safe_float(means[dim]) for dim in DIMENSIONS}
    record["streuungen"] = {dim.replace("x_", ""): safe_float(stds[dim]) for dim in DIMENSIONS}

    record["d_semantisch_mittel"] = safe_float(g["d_semantisch"].mean())
    record["d_semantisch_std"] = safe_float(g["d_semantisch"].std(ddof=0))
    record["vektor_norm_mittel"] = safe_float(g["vektor_norm"].mean())

    polarity_counts = g["polaritaet_label"].value_counts(normalize=True).to_dict()
    record["polaritaet_anteile"] = {
        "positiv": safe_float(polarity_counts.get("positiv", 0.0)),
        "neutral": safe_float(polarity_counts.get("neutral", 0.0)),
        "negativ": safe_float(polarity_counts.get("negativ", 0.0)),
    }

    dominance_counts = g["dominante_dimension"].value_counts().to_dict()
    record["dominanz_haeufigkeit"] = {str(k): int(v) for k, v in dominance_counts.items()}
    record["dominante_dimension_modal"] = g["dominante_dimension"].mode().iloc[0] if not g["dominante_dimension"].mode().empty else None

    abs_means = means.abs()
    dominant_mean_dim = abs_means.idxmax().replace("x_", "")
    record["dominante_dimension_mittelvektor"] = dominant_mean_dim
    record["dominante_dimension_mittelwert"] = safe_float(means[f"x_{dominant_mean_dim}"])

    # Polarisierung: mittlere Standardabweichung über die 7 Dimensionen
    record["polarisierungsindex"] = safe_float(stds.mean())

    # Kohärenz/Stabilität auf Aggregationsebene: Norm des Mittelvektors vs. mittlere Norm
    mean_vec_norm = math.sqrt(float((means ** 2).sum()))
    mean_row_norm = float(g["vektor_norm"].mean()) if len(g) else 0.0
    record["kohärenzindex"] = safe_float(mean_vec_norm / mean_row_norm) if mean_row_norm else None

    # Einfache Stabilität als inverse mittlere Distanz zum Gruppenmittelwert
    if len(g) > 1:
        centered = g[DIMENSIONS].sub(means, axis=1)
        distances = (centered ** 2).sum(axis=1).pow(0.5)
        record["stabilitaetsindex"] = safe_float(1.0 / (1.0 + float(distances.mean())))
    else:
        record["stabilitaetsindex"] = 1.0

    return record


def build_summary_table(records: list[dict[str, Any]], group_keys: list[str]) -> list[dict[str, Any]]:
    table: list[dict[str, Any]] = []
    for rec in records:
        row = {k: rec.get(k) for k in group_keys}
        row.update(
            {
                "n": rec["n"],
                "d_semantisch_mittel": rec["d_semantisch_mittel"],
                "kohärenzindex": rec["kohärenzindex"],
                "stabilitaetsindex": rec["stabilitaetsindex"],
                "polarisierungsindex": rec["polarisierungsindex"],
                "dominante_dimension": rec["dominante_dimension_mittelvektor"],
                "polaritaet_positiv": rec["polaritaet_anteile"]["positiv"],
                "polaritaet_neutral": rec["polaritaet_anteile"]["neutral"],
                "polaritaet_negativ": rec["polaritaet_anteile"]["negativ"],
            }
        )
        for dim in DIMENSION_NAMES:
            row[dim] = rec["mittelwerte"][dim]
        table.append(row)
    return table


def main() -> None:
    args = parse_args()
    cfg = QueryConfig(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
    )

    sql, params = build_query(args)
    df = load_dataframe(cfg, sql, params)

    invalid_keys = [k for k in args.group_by if k not in df.columns]
    if invalid_keys:
        raise SystemExit(f"Unbekannte group-by-Spalten: {', '.join(invalid_keys)}")

    grouped = df.groupby(args.group_by, dropna=False, sort=True)
    records: list[dict[str, Any]] = []
    for key_values, group_df in grouped:
        if not isinstance(key_values, tuple):
            key_values = (key_values,)
        records.append(compute_group_record(key_values, args.group_by, group_df.copy()))

    payload = {
        "meta": {
            "quelle": "datenm_values_sem_dichte_lehrer_type_3",
            "datenbank": args.database,
            "group_by": args.group_by,
            "date_from": args.date_from,
            "date_to": args.date_to,
            "gruppe_id": args.gruppe_id,
            "lehrkraft_id": args.lehrkraft_id,
            "teilnehmer_id": args.teilnehmer_id,
            "exclude_kw": args.exclude_kw,
            "dimensions": DIMENSION_NAMES,
            "n_raw": int(len(df)),
        },
        "summary_table": build_summary_table(records, args.group_by),
        "records": records,
    }

    output_path = Path(args.output)
    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"JSON exportiert: {output_path.resolve()}")


if __name__ == "__main__":
    main()
