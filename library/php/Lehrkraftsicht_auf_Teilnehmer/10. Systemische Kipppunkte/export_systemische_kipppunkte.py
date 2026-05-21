import json
import mysql.connector
from datetime import date, datetime
from pathlib import Path

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

OUTPUT_FILE = "auswertung_10_systemische_kipppunkte.json"
THRESHOLD_D_SEMANTISCH = 2.5

BASE_SQL = """
SELECT
    id,
    gruppe_id,
    teilnehmer_id,
    datum,
    thema,
    lehrkraft_id,
    dominante_dimension,
    dominante_dimension_wert,
    polaritaet_gesamt,
    d_semantisch,
    x_kognition,
    x_sozial,
    x_affektiv,
    x_motivation,
    x_methodik,
    x_performanz,
    x_regulation
FROM analyze_lehrkraftdaten
WHERE teilnehmer_id IS NOT NULL
ORDER BY teilnehmer_id, datum, id
"""

SCOPES = {
    "alle_lehrkraefte": "",
    "lehrkraft_id_1": "WHERE lehrkraft_id = 1",
    "ohne_lehrkraft_id_1": "WHERE lehrkraft_id <> 1 OR lehrkraft_id IS NULL"
}


def json_default(obj):
    if isinstance(obj, (date, datetime)):
        return obj.isoformat()
    return str(obj)


def fetch_scope(cursor, scope_filter):
    sql = BASE_SQL.replace(
        "WHERE teilnehmer_id IS NOT NULL",
        f"WHERE teilnehmer_id IS NOT NULL AND ({scope_filter[6:]})"
        if scope_filter.startswith("WHERE")
        else "WHERE teilnehmer_id IS NOT NULL"
    )
    cursor.execute(sql)
    return cursor.fetchall()


def add_transition_metrics(rows):
    last_by_participant = {}

    for row in rows:
        tid = row["teilnehmer_id"]
        prev = last_by_participant.get(tid)

        if prev is None:
            row["delta_d_semantisch"] = None
            row["polaritaetswechsel"] = False
            row["dominanzwechsel"] = False
            row["kipppunkt_kandidat"] = row["d_semantisch"] is not None and row["d_semantisch"] > THRESHOLD_D_SEMANTISCH
        else:
            d_now = float(row["d_semantisch"] or 0)
            d_prev = float(prev["d_semantisch"] or 0)

            row["delta_d_semantisch"] = d_now - d_prev
            row["polaritaetswechsel"] = row["polaritaet_gesamt"] != prev["polaritaet_gesamt"]
            row["dominanzwechsel"] = row["dominante_dimension"] != prev["dominante_dimension"]
            row["kipppunkt_kandidat"] = (
                d_now > THRESHOLD_D_SEMANTISCH
                or abs(row["delta_d_semantisch"]) > THRESHOLD_D_SEMANTISCH
                or row["polaritaetswechsel"]
                or row["dominanzwechsel"]
            )

        last_by_participant[tid] = row

    return rows


def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    export = {
        "auswertungspunkt": "10. Systemische Kipppunkte",
        "frzk_vorhersage": "Ab bestimmten Schwellen entstehen qualitative Zustandswechsel.",
        "threshold_d_semantisch": THRESHOLD_D_SEMANTISCH,
        "scopes": {}
    }

    for scope_name, scope_filter in SCOPES.items():
        rows = fetch_scope(cursor, scope_filter)
        rows = add_transition_metrics(rows)

        export["scopes"][scope_name] = {
            "anzahl_datensaetze": len(rows),
            "daten": rows
        }

    cursor.close()
    conn.close()

    Path(OUTPUT_FILE).write_text(
        json.dumps(export, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8"
    )

    print(f"Export abgeschlossen: {OUTPUT_FILE}")


if __name__ == "__main__":
    main()