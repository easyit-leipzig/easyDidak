import json
from pathlib import Path
from datetime import datetime, date
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

OUTFILE = Path("auswertung_06_emergenzvorhersage.json")

DIMENSIONS = [
    "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
    "x_methodik", "x_performanz", "x_regulation"
]

BASE_SQL = """
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
ORDER BY datum ASC, id_mtr_rueckkopplung_datenmaske ASC, id ASC
"""

SCOPES = {
    "alle_lehrkraefte": "1=1",
    "lehrkraft_1": "lehrkraft_id = 1",
    "ohne_lehrkraft_1": "lehrkraft_id <> 1"
}


def json_default(obj):
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return obj


def fetch_scope(cursor, where_clause: str):
    cursor.execute(BASE_SQL.format(where_clause=where_clause))
    rows = cursor.fetchall()

    cleaned = []
    for r in rows:
        item = dict(r)
        for d in DIMENSIONS:
            item[d] = float(item[d]) if item[d] is not None else 0.0
        item["d_semantisch"] = float(item["d_semantisch"]) if item["d_semantisch"] is not None else 0.0
        item["dominante_dimension_wert"] = float(item["dominante_dimension_wert"]) if item["dominante_dimension_wert"] is not None else 0.0
        cleaned.append(item)

    return cleaned


def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    payload = {
        "auswertungspunkt": "6. Emergenzvorhersage",
        "frzk_vorhersage": "Bestimmte Kombinationen erzeugen neue stabile Gesamtzustände.",
        "messbar_ueber": [
            "Clusterbildung",
            "Attraktoren",
            "wiederkehrende Zustandsräume",
            "Transition-Matrizen"
        ],
        "dimensionen": DIMENSIONS,
        "generated_at": datetime.now().isoformat(),
        "scopes": {}
    }

    for scope_name, where_clause in SCOPES.items():
        rows = fetch_scope(cursor, where_clause)
        payload["scopes"][scope_name] = {
            "n": len(rows),
            "where": where_clause,
            "rows": rows
        }

    cursor.close()
    conn.close()

    OUTFILE.write_text(
        json.dumps(payload, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8"
    )

    print(f"JSON erzeugt: {OUTFILE.resolve()}")
    for k, v in payload["scopes"].items():
        print(f"{k}: {v['n']} Datensätze")


if __name__ == "__main__":
    main()