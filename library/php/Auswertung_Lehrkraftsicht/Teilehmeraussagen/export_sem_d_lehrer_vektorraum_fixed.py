# export_sem_d_lehrer_vektorraum.py
# Exportiert den FRZK-Vektorenraum aus der View sem_d_lehrer_datenmaske
# getrennt nach lehrkraft_id = 1 und lehrkraft_id <> 1.
#
# Fix:
# - Aggregation liest x_*-Werte korrekt aus record["vector"].
# - Fallback für fehlende Spalten/NULL-Werte robuster gemacht.
# - JSON enthält records mit verschachteltem "vector" und summary mit Mittelwertvektoren.

import json
import math
from datetime import date, datetime
from pathlib import Path

import pymysql


DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "icas",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
}

OUTPUT_FILE = "sem_d_lehrer_vektorraum_lk1_andere.json"

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def to_float(value):
    if value is None:
        return 0.0
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


def to_int_or_none(value):
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


def norm(values):
    return math.sqrt(sum(v * v for v in values))


def get_dim_value(row, dimension):
    """
    Unterstützt beide Strukturen:
    1. Rohdatenbankzeile: row["x_kognition"]
    2. Exportdatensatz:   row["vector"]["x_kognition"]
    """
    if "vector" in row and isinstance(row["vector"], dict):
        return to_float(row["vector"].get(dimension))
    return to_float(row.get(dimension))


def mean_vector(rows):
    if not rows:
        return {d: 0.0 for d in DIMENSIONS}

    return {
        d: sum(get_dim_value(r, d) for r in rows) / len(rows)
        for d in DIMENSIONS
    }


def aggregate_group(rows):
    mv = mean_vector(rows)

    d_values = [to_float(r.get("d_semantisch")) for r in rows]
    norm_values = [to_float(r.get("norm_x")) for r in rows]

    polaritaet = [
        int(r["polaritaet_gesamt"])
        for r in rows
        if r.get("polaritaet_gesamt") is not None
    ]

    dominant_counts = {}
    for r in rows:
        dim = r.get("dominante_dimension")
        if dim:
            dominant_counts[dim] = dominant_counts.get(dim, 0) + 1

    return {
        "n": len(rows),
        "mean_vector": mv,
        "mean_norm_from_mean_vector": norm(list(mv.values())),
        "mean_norm_x": sum(norm_values) / len(norm_values) if norm_values else 0.0,
        "mean_d_semantisch": sum(d_values) / len(d_values) if d_values else 0.0,
        "polaritaet_mean": sum(polaritaet) / len(polaritaet) if polaritaet else 0.0,
        "dominante_dimension_counts": dominant_counts,
    }


def main():
    sql = f"""
        SELECT
            id,
            ue_id,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
            teilnehmer_id,
            lehrkraft_id,
            datum,
            {", ".join(DIMENSIONS)},
            d_semantisch,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            token_anzahl,
            funktionsklassen_anzahl_gesamt,
            created_at
        FROM sem_d_lehrer_datenmaske
        WHERE lehrkraft_id IS NOT NULL
        ORDER BY datum ASC, id_mtr_rueckkopplung_datenmaske ASC, id ASC
    """

    connection = pymysql.connect(**DB_CONFIG)

    try:
        with connection.cursor() as cursor:
            cursor.execute(sql)
            rows = cursor.fetchall()
    finally:
        connection.close()

    records = []

    for r in rows:
        lehrkraft_id = to_int_or_none(r.get("lehrkraft_id"))
        if lehrkraft_id is None:
            continue

        vector = [to_float(r.get(d)) for d in DIMENSIONS]
        group = "lehrkraft_1" if lehrkraft_id == 1 else "andere"

        item = {
            "gruppe": group,
            "id": r.get("id"),
            "ue_id": r.get("ue_id"),
            "id_mtr_rueckkopplung_datenmaske": r.get("id_mtr_rueckkopplung_datenmaske"),
            "mtr_rueckkopplung_datenmaske_values_id": r.get("mtr_rueckkopplung_datenmaske_values_id"),
            "teilnehmer_id": r.get("teilnehmer_id"),
            "lehrkraft_id": lehrkraft_id,
            "datum": r.get("datum"),
            "vector": dict(zip(DIMENSIONS, vector)),
            "norm_x": norm(vector),
            "d_semantisch": to_float(r.get("d_semantisch")),
            "dominante_dimension": r.get("dominante_dimension"),
            "dominante_dimension_wert": to_float(r.get("dominante_dimension_wert")),
            "polaritaet_gesamt": to_int_or_none(r.get("polaritaet_gesamt")),
            "token_anzahl": to_int_or_none(r.get("token_anzahl")),
            "funktionsklassen_anzahl_gesamt": to_int_or_none(r.get("funktionsklassen_anzahl_gesamt")),
            "created_at": r.get("created_at"),
        }

        records.append(item)

    lk1 = [r for r in records if r["gruppe"] == "lehrkraft_1"]
    andere = [r for r in records if r["gruppe"] == "andere"]

    output = {
        "quelle": {
            "datenbank": DB_CONFIG["database"],
            "view": "sem_d_lehrer_datenmaske",
            "gruppenlogik": "lehrkraft_id = 1 vs. lehrkraft_id <> 1",
            "dimensionen": DIMENSIONS,
            "exportiert_am": datetime.now().isoformat(timespec="seconds"),
        },
        "summary": {
            "gesamt": aggregate_group(records),
            "lehrkraft_1": aggregate_group(lk1),
            "andere": aggregate_group(andere),
        },
        "records": records,
    }

    Path(OUTPUT_FILE).write_text(
        json.dumps(output, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8"
    )

    print(f"Export abgeschlossen: {OUTPUT_FILE}")
    print(f"Datensätze gesamt: {len(records)}")
    print(f"Lehrkraft 1: {len(lk1)}")
    print(f"Andere: {len(andere)}")


if __name__ == "__main__":
    main()
