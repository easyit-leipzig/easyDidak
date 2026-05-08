# export_semantische_dichte_json.py

import json
import math
from pathlib import Path

import pymysql


OUTPUT_FILE = "datenm_values_sem_dichte_lehrer_type_3.json"


def to_float(value):
    if value is None:
        return None
    return float(value)


def compute_norm(row):
    dims = [
        to_float(row["x_kognition"]) or 0.0,
        to_float(row["x_sozial"]) or 0.0,
        to_float(row["x_affektiv"]) or 0.0,
        to_float(row["x_motivation"]) or 0.0,
        to_float(row["x_methodik"]) or 0.0,
        to_float(row["x_performanz"]) or 0.0,
        to_float(row["x_regulation"]) or 0.0,
    ]
    return math.sqrt(sum(x * x for x in dims))


def main():
    conn = pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="icas",
        charset="utf8mb4",
        cursorclass=pymysql.cursors.DictCursor
    )

    sql = """
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
        FROM datenm_values_sem_dichte_lehrer_type_3 where datum>='2025-09-01' and lehrkraft_id<>1 
        ORDER BY datum ASC, id ASC
    """

    with conn.cursor() as cur:
        cur.execute(sql)
        rows = cur.fetchall()

    conn.close()

    export_rows = []
    for row in rows:
        item = {
            "id": row["id"],
            "gruppe_id": row["gruppe_id"],
            "teilnehmer_id": row["teilnehmer_id"],
            "fach": row["fach"],
            "datum": row["datum"].isoformat() if row["datum"] else None,
            "thema": row["thema"],
            "bemerkung": row["bemerkung"],
            "wochentag": row["wochentag"],
            "day_number": row["day_number"],
            "lehrkraft_id": row["lehrkraft_id"],
            "id_mtr_rueckkopplung_datenmaske": row["id_mtr_rueckkopplung_datenmaske"],
            "type": row["type"],
            "vektor_normiert": {
                "kognition": to_float(row["x_kognition"]),
                "sozial": to_float(row["x_sozial"]),
                "affektiv": to_float(row["x_affektiv"]),
                "motivation": to_float(row["x_motivation"]),
                "methodik": to_float(row["x_methodik"]),
                "performanz": to_float(row["x_performanz"]),
                "regulation": to_float(row["x_regulation"]),
            },
            "dominante_dimension": row["dominante_dimension"],
            "dominante_dimension_wert": to_float(row["dominante_dimension_wert"]),
            "polaritaet_gesamt": row["polaritaet_gesamt"],
            "d_semantisch": to_float(row["d_semantisch"]),
            "d_semantisch_aus_x_normiert_berechnet": compute_norm(row)
        }
        export_rows.append(item)

    payload = {
        "quelle": "datenm_values_sem_dichte_lehrer_type_3",
        "anzahl_datensaetze": len(export_rows),
        "dimensionen": [
            "kognition",
            "sozial",
            "affektiv",
            "motivation",
            "methodik",
            "performanz",
            "regulation"
        ],
        "daten": export_rows
    }

    Path(OUTPUT_FILE).write_text(
        json.dumps(payload, ensure_ascii=False, indent=2),
        encoding="utf-8"
    )

    print(f"JSON exportiert: {OUTPUT_FILE}")
    print(f"Datensätze: {len(export_rows)}")


if __name__ == "__main__":
    main()