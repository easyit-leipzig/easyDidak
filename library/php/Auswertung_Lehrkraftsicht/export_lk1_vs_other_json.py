#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
export_lk1_vs_other_json.py

Exportiert FRZK-Vektordaten aus MySQL/MariaDB als JSON für die Analyse:
- Gruppe LK_1: lehrkraft_id = 1
- Gruppe LK_other: alle anderen lehrkraft_id != 1

Ergebnisdatei:
    frzk_lk1_vs_other_export.json

Voraussetzungen:
    pip install pymysql
"""

import json
import math
from datetime import date, datetime
from pathlib import Path

import pymysql


# ============================================================
# KONFIGURATION
# ============================================================

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
}

TABLE_NAME = "sql_semantische_dichte_lehrer_type_1"

OUTPUT_FILE = Path("frzk_lk1_vs_other_export.json")

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


# ============================================================
# HILFSFUNKTIONEN
# ============================================================

def to_float(value):
    if value is None:
        return None
    try:
        return float(value)
    except (TypeError, ValueError):
        return None


def to_int(value):
    if value is None:
        return None
    try:
        return int(value)
    except (TypeError, ValueError):
        return None


def json_default(obj):
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def compute_norm(vector):
    vals = [v for v in vector if v is not None]
    if not vals:
        return None
    return math.sqrt(sum(v * v for v in vals))


def fetch_rows():
    sql = f"""
        SELECT
            datum,
            lehrkraft_id,
            wochentag,
            gruppe_id,
            id,
            ue_id,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
            teilnehmer_id,

            x_kognition,
            x_sozial,
            x_affektiv,
            x_motivation,
            x_methodik,
            x_performanz,
            x_regulation,

            sum_kognition,
            sum_sozial,
            sum_affektiv,
            sum_motivation,
            sum_methodik,
            sum_performanz,
            sum_regulation,

            token_anzahl,
            funktionsklassen_anzahl_gesamt,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch,
            created_at

        FROM {TABLE_NAME}
        WHERE lehrkraft_id IS NOT NULL and datum>='2025-09-01' and gruppe_id<>0 
        ORDER BY datum ASC, lehrkraft_id ASC, id ASC
    """

    connection = pymysql.connect(**DB_CONFIG)
    try:
        with connection.cursor() as cursor:
            cursor.execute(sql)
            return cursor.fetchall()
    finally:
        connection.close()


def normalize_row(row):
    lehrkraft_id = to_int(row.get("lehrkraft_id"))
    gruppe = "LK_1" if lehrkraft_id == 1 else "LK_other"

    x_vector = [to_float(row.get(col)) for col in X_COLUMNS]
    sum_vector = [to_float(row.get(col)) for col in SUM_COLUMNS]

    d_semantisch = to_float(row.get("d_semantisch"))
    if d_semantisch is None:
        d_semantisch = compute_norm(sum_vector)

    normalized = {
        "gruppe": gruppe,
        "datum": json_default(row.get("datum")),
        "lehrkraft_id": lehrkraft_id,
        "wochentag": to_int(row.get("wochentag")),
        "gruppe_id": to_int(row.get("gruppe_id")),
        "id": to_int(row.get("id")),
        "ue_id": to_int(row.get("ue_id")),
        "id_mtr_rueckkopplung_datenmaske": to_int(row.get("id_mtr_rueckkopplung_datenmaske")),
        "mtr_rueckkopplung_datenmaske_values_id": to_int(row.get("mtr_rueckkopplung_datenmaske_values_id")),
        "teilnehmer_id": to_int(row.get("teilnehmer_id")),
        "token_anzahl": to_int(row.get("token_anzahl")),
        "funktionsklassen_anzahl_gesamt": to_int(row.get("funktionsklassen_anzahl_gesamt")),
        "dominante_dimension": row.get("dominante_dimension"),
        "dominante_dimension_wert": to_float(row.get("dominante_dimension_wert")),
        "polaritaet_gesamt": to_int(row.get("polaritaet_gesamt")),
        "d_semantisch": d_semantisch,
        "created_at": json_default(row.get("created_at")),
        "x": dict(zip(DIMENSIONS, x_vector)),
        "sum": dict(zip(DIMENSIONS, sum_vector)),
        "vector_x": x_vector,
        "vector_sum": sum_vector,
    }

    return normalized


def summarize(records):
    result = {}
    for group in ["LK_1", "LK_other"]:
        group_records = [r for r in records if r["gruppe"] == group]
        result[group] = {
            "n": len(group_records),
            "lehrkraft_ids": sorted(set(r["lehrkraft_id"] for r in group_records if r["lehrkraft_id"] is not None)),
            "datum_min": min((r["datum"] for r in group_records if r["datum"]), default=None),
            "datum_max": max((r["datum"] for r in group_records if r["datum"]), default=None),
        }
    return result


def main():
    raw_rows = fetch_rows()
    records = [normalize_row(row) for row in raw_rows]

    output = {
        "metadata": {
            "quelle": TABLE_NAME,
            "beschreibung": "FRZK-Vektordaten gruppiert nach lehrkraft_id=1 versus andere Lehrkräfte",
            "gruppenlogik": {
                "LK_1": "lehrkraft_id == 1",
                "LK_other": "lehrkraft_id != 1",
            },
            "dimensionen": DIMENSIONS,
            "x_columns": X_COLUMNS,
            "sum_columns": SUM_COLUMNS,
            "exportiert_am": datetime.now().isoformat(timespec="seconds"),
        },
        "summary": summarize(records),
        "records": records,
    }

    OUTPUT_FILE.write_text(
        json.dumps(output, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8",
    )

    print(f"Export abgeschlossen: {OUTPUT_FILE.resolve()}")
    print(json.dumps(output["summary"], ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
