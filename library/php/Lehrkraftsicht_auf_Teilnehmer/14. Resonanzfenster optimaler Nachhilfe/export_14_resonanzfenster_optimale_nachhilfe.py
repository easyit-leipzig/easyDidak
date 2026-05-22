import json
import math
from datetime import date, datetime
from pathlib import Path

import mysql.connector


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

OUTPUT_FILE = "auswertung_14_resonanzfenster_optimale_nachhilfe.json"

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation"
]


def json_serializer(obj):
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def fetch_scope(cursor, where_clause, params):
    sql = f"""
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
            mtr_rueckkopplung_datenmaske_values_id,

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
            operator_count,
            modulator_count,
            has_operator,
            operator_names,
            modulator_names
        FROM analyze_lehrkraftdaten
        WHERE {where_clause}
        ORDER BY
            lehrkraft_id ASC,
            gruppe_id ASC,
            teilnehmer_id ASC,
            datum ASC,
            id ASC
    """
    cursor.execute(sql, params)
    return cursor.fetchall()


def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    scopes = {
        "alle_lehrkraefte": ("1=1", []),
        "lehrkraft_1": ("lehrkraft_id = %s", [1]),
        "ohne_lehrkraft_1": ("lehrkraft_id <> %s", [1]),
    }

    result = {
        "auswertung": "14. Resonanzfenster optimaler Nachhilfe",
        "beschreibung": (
            "Ableitung funktionaler Resonanzfenster aus mittlerer Drift, "
            "hoher Kohärenz, positiver Polarität, hoher Motivation und "
            "moderater Instabilität."
        ),
        "dimensionen": DIMENSIONS,
        "scopes": {}
    }

    for scope_name, (where_clause, params) in scopes.items():
        rows = fetch_scope(cursor, where_clause, params)
        result["scopes"][scope_name] = {
            "anzahl_datensaetze": len(rows),
            "daten": rows
        }

    cursor.close()
    conn.close()

    Path(OUTPUT_FILE).write_text(
        json.dumps(result, ensure_ascii=False, indent=2, default=json_serializer),
        encoding="utf-8"
    )

    print(f"JSON exportiert: {OUTPUT_FILE}")


if __name__ == "__main__":
    main()