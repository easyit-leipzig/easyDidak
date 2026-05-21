import json
from pathlib import Path
from datetime import date, datetime

import numpy as np
import pandas as pd
import mysql.connector


OUTFILE = Path("auswertung_05_kohaerenzbildung.json")

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "connection_timeout": 5,
    "use_pure": True
}

SCOPES = {
    "alle_lehrkraefte": "",
    "lehrkraft_1": "WHERE lehrkraft_id = 1",
    "ohne_lehrkraft_1": "WHERE lehrkraft_id <> 1 OR lehrkraft_id IS NULL",
}

BASE_SQL = """
SELECT
    id,
    teilnehmer_id,
    lehrkraft_id,
    datum,
    x_kognition,
    x_motivation,
    x_methodik,
    x_regulation,
    polaritaet_gesamt,
    ABS(x_kognition - x_motivation) AS kohaerenz_1,
    ABS(x_methodik - x_regulation) AS kohaerenz_2,
    (
        ABS(x_kognition - x_motivation)
        + ABS(x_methodik - x_regulation)
    ) / 2 AS kohaerenz_index
FROM analyze_lehrkraftdaten
{where_clause}
ORDER BY teilnehmer_id, datum, id
"""


def json_default(obj):
    if isinstance(obj, (date, datetime, pd.Timestamp)):
        return obj.isoformat()
    if isinstance(obj, np.integer):
        return int(obj)
    if isinstance(obj, np.floating):
        return float(obj)
    if pd.isna(obj):
        return None
    return str(obj)


def polaritaetswechsel(gruppe: pd.DataFrame) -> int:
    p = gruppe["polaritaet_gesamt"].dropna().astype(int).tolist()
    return sum(1 for a, b in zip(p, p[1:]) if a != b)


def clean_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()

    if "datum" in df.columns:
        df["datum"] = pd.to_datetime(df["datum"], errors="coerce").dt.date

    numeric_cols = [
        "x_kognition",
        "x_motivation",
        "x_methodik",
        "x_regulation",
        "polaritaet_gesamt",
        "kohaerenz_1",
        "kohaerenz_2",
        "kohaerenz_index",
    ]

    for col in numeric_cols:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")

    return df


def build_scope(df: pd.DataFrame) -> dict:
    df = clean_dataframe(df)

    if df.empty:
        return {
            "records": [],
            "summary": {},
            "by_teilnehmer": [],
        }

    by_t = (
        df.groupby("teilnehmer_id", dropna=False)
        .agg(
            n=("id", "count"),
            kohaerenz_1_mean=("kohaerenz_1", "mean"),
            kohaerenz_2_mean=("kohaerenz_2", "mean"),
            kohaerenz_index_mean=("kohaerenz_index", "mean"),
            kohaerenz_index_std=("kohaerenz_index", "std"),
            erste_sitzung=("datum", "min"),
            letzte_sitzung=("datum", "max"),
        )
        .reset_index()
    )

    wechsel = (
        df.groupby("teilnehmer_id", dropna=False)
        .apply(polaritaetswechsel, include_groups=False)
        .reset_index(name="polaritaetswechsel")
    )

    by_t = by_t.merge(wechsel, on="teilnehmer_id", how="left")

    summary = {
        "n_records": int(len(df)),
        "n_teilnehmer": int(df["teilnehmer_id"].nunique(dropna=True)),
        "kohaerenz_1_mean": float(df["kohaerenz_1"].mean()),
        "kohaerenz_2_mean": float(df["kohaerenz_2"].mean()),
        "kohaerenz_index_mean": float(df["kohaerenz_index"].mean()),
        "kohaerenz_index_std": float(df["kohaerenz_index"].std()),
        "polaritaetswechsel_sum": int(by_t["polaritaetswechsel"].sum()),
    }

    return {
        "summary": summary,
        "by_teilnehmer": by_t.replace({np.nan: None}).to_dict(orient="records"),
        "records": df.replace({np.nan: None}).to_dict(orient="records"),
    }


def main():
    conn = mysql.connector.connect(**DB_CONFIG)

    result = {
        "auswertungspunkt": "5. Kohärenzbildung",
        "frzk_vorhersage": (
            "Langfristige Lehrkraft-Teilnehmer-Interaktion erzeugt "
            "kohärente semantische Räume."
        ),
        "messlogik": {
            "kohaerenz_1": "AVG(ABS(x_kognition - x_motivation))",
            "kohaerenz_2": "AVG(ABS(x_methodik - x_regulation))",
            "kohaerenz_index": "(kohaerenz_1 + kohaerenz_2) / 2",
            "interpretation": "Niedrige Werte bedeuten hohe interne Kohärenz.",
        },
        "scopes": {},
    }

    try:
        for scope, where_clause in SCOPES.items():
            sql = BASE_SQL.format(where_clause=where_clause)
            df = pd.read_sql(sql, conn)
            result["scopes"][scope] = build_scope(df)

    finally:
        conn.close()

    OUTFILE.write_text(
        json.dumps(result, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8",
    )

    print(f"JSON erzeugt: {OUTFILE.resolve()}")


if __name__ == "__main__":
    main()