import json
import math
from pathlib import Path
from datetime import date, datetime

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

OUTFILE = Path("auswertung_07_dynamische_drift.json")

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def json_default(obj):
    if isinstance(obj, (date, datetime)):
        return obj.isoformat()
    return str(obj)


def fetch_rows(where_sql="", params=None):
    params = params or []

    sql = f"""
        SELECT
            id,
            gruppe_id,
            teilnehmer_id,
            lehrkraft_id,
            datum,
            fach,
            thema,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
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
        FROM analyze_lehrkraftdaten
        WHERE teilnehmer_id IS NOT NULL
        {where_sql}
        ORDER BY teilnehmer_id, datum, id
    """

    cnx = mysql.connector.connect(**DB_CONFIG)
    cur = cnx.cursor(dictionary=True)
    cur.execute(sql, params)
    rows = cur.fetchall()
    cur.close()
    cnx.close()
    return rows


def vector(row):
    return [float(row[d] or 0.0) for d in DIMENSIONS]


def euclidean_delta(v1, v2):
    return math.sqrt(sum((b - a) ** 2 for a, b in zip(v1, v2)))


def cosine_similarity(v1, v2):
    dot = sum(a * b for a, b in zip(v1, v2))
    n1 = math.sqrt(sum(a * a for a in v1))
    n2 = math.sqrt(sum(b * b for b in v2))
    if n1 == 0 or n2 == 0:
        return None
    return dot / (n1 * n2)


def build_drift(rows, scope_name):
    by_teilnehmer = {}
    for row in rows:
        by_teilnehmer.setdefault(row["teilnehmer_id"], []).append(row)

    transitions = []

    for teilnehmer_id, items in by_teilnehmer.items():
        items = sorted(items, key=lambda r: (r["datum"], r["id"]))

        for a, b in zip(items[:-1], items[1:]):
            va = vector(a)
            vb = vector(b)
            delta_vector = {
                dim.replace("x_", "delta_"): vb[i] - va[i]
                for i, dim in enumerate(DIMENSIONS)
            }

            transitions.append({
                "scope": scope_name,
                "teilnehmer_id": teilnehmer_id,
                "lehrkraft_id_a": a["lehrkraft_id"],
                "lehrkraft_id_b": b["lehrkraft_id"],
                "id_a": a["id"],
                "id_b": b["id"],
                "datum_a": a["datum"],
                "datum_b": b["datum"],
                "gruppe_id_a": a["gruppe_id"],
                "gruppe_id_b": b["gruppe_id"],
                "fach_a": a["fach"],
                "fach_b": b["fach"],
                "thema_a": a["thema"],
                "thema_b": b["thema"],
                "delta_bewegung": euclidean_delta(va, vb),
                "cosine_similarity": cosine_similarity(va, vb),
                "d_semantisch_a": float(a["d_semantisch"] or 0.0),
                "d_semantisch_b": float(b["d_semantisch"] or 0.0),
                "delta_d_semantisch": float(b["d_semantisch"] or 0.0) - float(a["d_semantisch"] or 0.0),
                "dominante_dimension_a": a["dominante_dimension"],
                "dominante_dimension_b": b["dominante_dimension"],
                "dominanzwechsel": a["dominante_dimension"] != b["dominante_dimension"],
                "polaritaet_a": a["polaritaet_gesamt"],
                "polaritaet_b": b["polaritaet_gesamt"],
                "polaritaetswechsel": a["polaritaet_gesamt"] != b["polaritaet_gesamt"],
                **delta_vector
            })

    deltas = [t["delta_bewegung"] for t in transitions]

    summary = {
        "scope": scope_name,
        "datensaetze": len(rows),
        "teilnehmer": len(by_teilnehmer),
        "transitionen": len(transitions),
        "delta_min": min(deltas) if deltas else None,
        "delta_max": max(deltas) if deltas else None,
        "delta_mittelwert": sum(deltas) / len(deltas) if deltas else None,
        "dominanzwechsel_anzahl": sum(1 for t in transitions if t["dominanzwechsel"]),
        "polaritaetswechsel_anzahl": sum(1 for t in transitions if t["polaritaetswechsel"]),
    }

    return {
        "summary": summary,
        "transitions": transitions
    }


def main():
    scopes = {
        "alle_lehrkraefte": fetch_rows(),
        "lehrkraft_1": fetch_rows("AND lehrkraft_id = %s", [1]),
        "ohne_lehrkraft_1": fetch_rows("AND lehrkraft_id <> %s", [1]),
    }

    result = {
        "auswertungspunkt": "7. Dynamische Drift",
        "frzk_vorhersage": "Teilnehmer bewegen sich kontinuierlich durch den Zustandsraum.",
        "formel": "ΔS(t)=S(t+1)-S(t)",
        "dimensionen": DIMENSIONS,
        "scopes": {
            name: build_drift(rows, name)
            for name, rows in scopes.items()
        }
    }

    OUTFILE.write_text(
        json.dumps(result, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8"
    )

    print(f"JSON erzeugt: {OUTFILE.resolve()}")


if __name__ == "__main__":
    main()