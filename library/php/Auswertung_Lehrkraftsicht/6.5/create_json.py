# -*- coding: utf-8 -*-
"""
generate_semantische_dichte_json.py

Erzeugt eine JSON-Datei für Kapitel 6.x.5
auf Basis des Views datenm_values_sem_dichte_lehrer_type_3.

Inhalt:
- Rohdaten pro Datensatz
- Wochenaggregation
- Gruppenaggregation
- Lehrkraftaggregation
- Fachaggregation
- Basiskennwerte zur semantischen Dichte

Voraussetzungen:
pip install pandas sqlalchemy pymysql
"""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import numpy as np
import pandas as pd
from sqlalchemy import create_engine, text


# =========================
# KONFIGURATION
# =========================

DB_HOST = "localhost"
DB_PORT = 3306
DB_NAME = "icas"
DB_USER = "root"
DB_PASS = ""

OUTPUT_FILE = "semantische_dichte_auswertung_lehrkraftsicht.json"

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


# =========================
# HILFSFUNKTIONEN
# =========================

def to_builtin(value: Any) -> Any:
    """Konvertiert NumPy/Pandas-Typen in JSON-kompatible Python-Typen."""
    if pd.isna(value):
        return None
    if isinstance(value, (np.integer,)):
        return int(value)
    if isinstance(value, (np.floating,)):
        return float(value)
    if isinstance(value, (np.bool_,)):
        return bool(value)
    if isinstance(value, (pd.Timestamp,)):
        return value.strftime("%Y-%m-%d")
    return value


def round_float(value: Any, digits: int = 6) -> Any:
    """Rundet float-Werte für saubere JSON-Ausgabe."""
    if value is None or pd.isna(value):
        return None
    try:
        return round(float(value), digits)
    except Exception:
        return value


def df_to_records(df: pd.DataFrame) -> list[dict[str, Any]]:
    """Konvertiert DataFrame-Zeilen in JSON-Records."""
    records: list[dict[str, Any]] = []
    for _, row in df.iterrows():
        record: dict[str, Any] = {}
        for col in df.columns:
            val = to_builtin(row[col])
            if isinstance(val, float):
                val = round_float(val)
            record[col] = val
        records.append(record)
    return records


def safe_mode(series: pd.Series) -> Any:
    """Robuster Modus, falls mehrere Werte gleich häufig auftreten."""
    series = series.dropna()
    if series.empty:
        return None
    modes = series.mode()
    if modes.empty:
        return None
    return to_builtin(modes.iloc[0])


# =========================
# DATEN LADEN
# =========================

def load_data() -> pd.DataFrame:
    engine = create_engine(
        f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}:{DB_PORT}/{DB_NAME}?charset=utf8mb4"
    )

    query = text("""
        SELECT
            id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            thema,
            bemerkung,
            wochentag,
            day_number,
            lehrkraft_id,
            id_mtr_rueckkopplung_datenmaske,
            type,
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
        FROM datenm_values_sem_dichte_lehrer_type_3 where datum > '2025-09-01' 
        ORDER BY datum ASC, id ASC
    """)

    with engine.connect() as conn:
        df = pd.read_sql(query, conn)

    if df.empty:
        raise ValueError("Keine Daten in datenm_values_sem_dichte_lehrer_type_3 gefunden.")

    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    df["jahr"] = df["datum"].dt.isocalendar().year.astype("Int64")
    df["kw"] = df["datum"].dt.isocalendar().week.astype("Int64")
    df["jahr_kw"] = df["jahr"].astype(str) + "-" + df["kw"].astype(str).str.zfill(2)

    # Rechenkontrolle: Dichte aus den sieben Dimensionen erneut berechnen
    df["d_semantisch_recalc"] = np.sqrt((df[DIMENSIONS].fillna(0.0) ** 2).sum(axis=1))
    df["d_semantisch_diff"] = (df["d_semantisch"].fillna(0.0) - df["d_semantisch_recalc"]).abs()

    return df


# =========================
# AGGREGATIONEN
# =========================

def build_summary(df: pd.DataFrame) -> dict[str, Any]:
    summary = {
        "anzahl_datensaetze": int(len(df)),
        "zeitraum_von": to_builtin(df["datum"].min()),
        "zeitraum_bis": to_builtin(df["datum"].max()),
        "anzahl_gruppen": int(df["gruppe_id"].nunique(dropna=True)),
        "anzahl_teilnehmer": int(df["teilnehmer_id"].nunique(dropna=True)),
        "anzahl_lehrkraefte": int(df["lehrkraft_id"].nunique(dropna=True)),
        "faecher": sorted([str(x) for x in df["fach"].dropna().unique().tolist()]),
        "d_semantisch_mittelwert": round_float(df["d_semantisch"].mean()),
        "d_semantisch_median": round_float(df["d_semantisch"].median()),
        "d_semantisch_std": round_float(df["d_semantisch"].std()),
        "d_semantisch_min": round_float(df["d_semantisch"].min()),
        "d_semantisch_max": round_float(df["d_semantisch"].max()),
        "rechenkontrolle_max_abweichung": round_float(df["d_semantisch_diff"].max(), 10),
        "rechenkontrolle_mittel_abweichung": round_float(df["d_semantisch_diff"].mean(), 10),
    }
    return summary


def aggregate_weekly(df: pd.DataFrame) -> list[dict[str, Any]]:
    agg = (
        df.groupby(["jahr", "kw", "jahr_kw"], dropna=False)
        .agg(
            n=("id", "count"),
            datum_min=("datum", "min"),
            datum_max=("datum", "max"),
            d_semantisch_mean=("d_semantisch", "mean"),
            d_semantisch_median=("d_semantisch", "median"),
            d_semantisch_std=("d_semantisch", "std"),
            polaritaet_mean=("polaritaet_gesamt", "mean"),
            dominante_dimension_modus=("dominante_dimension", safe_mode),
            x_kognition_mean=("x_kognition", "mean"),
            x_sozial_mean=("x_sozial", "mean"),
            x_affektiv_mean=("x_affektiv", "mean"),
            x_motivation_mean=("x_motivation", "mean"),
            x_methodik_mean=("x_methodik", "mean"),
            x_performanz_mean=("x_performanz", "mean"),
            x_regulation_mean=("x_regulation", "mean"),
        )
        .reset_index()
        .sort_values(["jahr", "kw"])
    )
    return df_to_records(agg)


def aggregate_by_group_and_week(df: pd.DataFrame) -> list[dict[str, Any]]:
    agg = (
        df.groupby(["gruppe_id", "jahr", "kw", "jahr_kw"], dropna=False)
        .agg(
            n=("id", "count"),
            d_semantisch_mean=("d_semantisch", "mean"),
            d_semantisch_std=("d_semantisch", "std"),
            polaritaet_mean=("polaritaet_gesamt", "mean"),
            dominante_dimension_modus=("dominante_dimension", safe_mode),
            x_kognition_mean=("x_kognition", "mean"),
            x_sozial_mean=("x_sozial", "mean"),
            x_affektiv_mean=("x_affektiv", "mean"),
            x_motivation_mean=("x_motivation", "mean"),
            x_methodik_mean=("x_methodik", "mean"),
            x_performanz_mean=("x_performanz", "mean"),
            x_regulation_mean=("x_regulation", "mean"),
        )
        .reset_index()
        .sort_values(["gruppe_id", "jahr", "kw"])
    )
    return df_to_records(agg)


def aggregate_by_teacher(df: pd.DataFrame) -> list[dict[str, Any]]:
    agg = (
        df.groupby(["lehrkraft_id"], dropna=False)
        .agg(
            n=("id", "count"),
            d_semantisch_mean=("d_semantisch", "mean"),
            d_semantisch_median=("d_semantisch", "median"),
            d_semantisch_std=("d_semantisch", "std"),
            polaritaet_mean=("polaritaet_gesamt", "mean"),
            dominante_dimension_modus=("dominante_dimension", safe_mode),
        )
        .reset_index()
        .sort_values("lehrkraft_id")
    )
    return df_to_records(agg)


def aggregate_by_subject(df: pd.DataFrame) -> list[dict[str, Any]]:
    agg = (
        df.groupby(["fach"], dropna=False)
        .agg(
            n=("id", "count"),
            d_semantisch_mean=("d_semantisch", "mean"),
            d_semantisch_median=("d_semantisch", "median"),
            d_semantisch_std=("d_semantisch", "std"),
            polaritaet_mean=("polaritaet_gesamt", "mean"),
            dominante_dimension_modus=("dominante_dimension", safe_mode),
        )
        .reset_index()
        .sort_values("fach")
    )
    return df_to_records(agg)


def build_raw_records(df: pd.DataFrame) -> list[dict[str, Any]]:
    cols = [
        "id",
        "gruppe_id",
        "teilnehmer_id",
        "fach",
        "datum",
        "jahr",
        "kw",
        "jahr_kw",
        "thema",
        "bemerkung",
        "wochentag",
        "day_number",
        "lehrkraft_id",
        "id_mtr_rueckkopplung_datenmaske",
        "type",
        "x_kognition",
        "x_sozial",
        "x_affektiv",
        "x_motivation",
        "x_methodik",
        "x_performanz",
        "x_regulation",
        "dominante_dimension",
        "dominante_dimension_wert",
        "polaritaet_gesamt",
        "d_semantisch",
        "d_semantisch_recalc",
        "d_semantisch_diff",
    ]
    out = df[cols].copy()
    return df_to_records(out)


# =========================
# MAIN
# =========================

def main() -> None:
    df = load_data()

    payload = {
        "metadaten": {
            "quelle": "datenm_values_sem_dichte_lehrer_type_3",
            "beschreibung": (
                "Semantische Dichte aus Lehrkraftsicht. "
                "d_semantisch ist die Norm des 7D-Zustandsvektors."
            ),
            "dimensionen": DIMENSIONS,
            "formel": "d_semantisch = sqrt(sum(x_i^2))",
        },
        "zusammenfassung": build_summary(df),
        "wochenaggregation": aggregate_weekly(df),
        "gruppen_wochenaggregation": aggregate_by_group_and_week(df),
        "lehrkraftaggregation": aggregate_by_teacher(df),
        "fachaggregation": aggregate_by_subject(df),
        "rohdaten": build_raw_records(df),
    }

    output_path = Path(OUTPUT_FILE)
    output_path.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2),
        encoding="utf-8"
    )

    print(f"JSON erfolgreich erzeugt: {output_path.resolve()}")


if __name__ == "__main__":
    main()