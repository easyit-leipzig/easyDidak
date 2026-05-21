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

OUTPUT_JSON = "auswertung_09_lehrkraft_zustandsoperator.json"

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation"
]


def json_default(obj):
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def vector_from_row(row):
    return [
        float(row.get(f"x_{dim}") or 0.0)
        for dim in DIMENSIONS
    ]


def norm(v):
    return math.sqrt(sum(x * x for x in v))


def cosine(a, b):
    na = norm(a)
    nb = norm(b)
    if na == 0 or nb == 0:
        return 0.0
    return sum(x * y for x, y in zip(a, b)) / (na * nb)


def delta_vector(a, b):
    return [b_i - a_i for a_i, b_i in zip(a, b)]


def load_rows(scope_where="", params=None):
    params = params or []

    sql = f"""
        SELECT
            id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            thema,
            bemerkung,
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
        WHERE datum IS NOT NULL
          AND teilnehmer_id IS NOT NULL
          {scope_where}
        ORDER BY teilnehmer_id, datum, id
    """

    con = mysql.connector.connect(**DB_CONFIG)
    cur = con.cursor(dictionary=True)
    cur.execute(sql, params)
    rows = cur.fetchall()
    cur.close()
    con.close()
    return rows


def build_transitions(rows):
    by_participant = {}

    for row in rows:
        tid = row["teilnehmer_id"]
        by_participant.setdefault(tid, []).append(row)

    transitions = []

    for teilnehmer_id, items in by_participant.items():
        items = sorted(items, key=lambda r: (r["datum"], r["id"]))

        for before, after in zip(items[:-1], items[1:]):
            v_before = vector_from_row(before)
            v_after = vector_from_row(after)
            dv = delta_vector(v_before, v_after)

            drift = norm(dv)
            resonance = cosine(v_before, v_after)

            d_before = float(before.get("d_semantisch") or norm(v_before))
            d_after = float(after.get("d_semantisch") or norm(v_after))

            transition = {
                "teilnehmer_id": teilnehmer_id,
                "gruppe_id_before": before.get("gruppe_id"),
                "gruppe_id_after": after.get("gruppe_id"),
                "lehrkraft_id_before": before.get("lehrkraft_id"),
                "lehrkraft_id_after": after.get("lehrkraft_id"),
                "datum_before": before.get("datum"),
                "datum_after": after.get("datum"),
                "id_before": before.get("id"),
                "id_after": after.get("id"),
                "thema_before": before.get("thema"),
                "thema_after": after.get("thema"),

                "vector_before": dict(zip(DIMENSIONS, v_before)),
                "vector_after": dict(zip(DIMENSIONS, v_after)),
                "delta_vector": dict(zip(DIMENSIONS, dv)),

                "d_semantisch_before": d_before,
                "d_semantisch_after": d_after,
                "delta_d_semantisch": d_after - d_before,

                "driftgeschwindigkeit": drift,
                "stabilisierung": 1.0 / (1.0 + drift),
                "resonanz_cosine": resonance,
                "resonanzbildung": max(0.0, resonance),

                "dominante_dimension_before": before.get("dominante_dimension"),
                "dominante_dimension_after": after.get("dominante_dimension"),
                "polaritaet_before": before.get("polaritaet_gesamt"),
                "polaritaet_after": after.get("polaritaet_gesamt"),

                "operator_count_before": before.get("operator_count"),
                "operator_count_after": after.get("operator_count"),
                "modulator_count_before": before.get("modulator_count"),
                "modulator_count_after": after.get("modulator_count"),
                "operator_names_before": before.get("operator_names"),
                "operator_names_after": after.get("operator_names"),
            }

            transitions.append(transition)

    return transitions


def summarize_scope(rows, transitions):
    if not rows:
        return {
            "records": 0,
            "transitions": 0
        }

    def mean(values):
        values = [v for v in values if v is not None]
        return sum(values) / len(values) if values else None

    return {
        "records": len(rows),
        "transitions": len(transitions),
        "unique_teilnehmer": len(set(r["teilnehmer_id"] for r in rows)),
        "unique_lehrkraefte": len(set(r["lehrkraft_id"] for r in rows if r["lehrkraft_id"] is not None)),
        "mean_d_semantisch": mean([float(r.get("d_semantisch") or 0.0) for r in rows]),
        "mean_driftgeschwindigkeit": mean([t["driftgeschwindigkeit"] for t in transitions]),
        "mean_stabilisierung": mean([t["stabilisierung"] for t in transitions]),
        "mean_resonanz_cosine": mean([t["resonanz_cosine"] for t in transitions]),
        "mean_delta_d_semantisch": mean([t["delta_d_semantisch"] for t in transitions]),
    }


def main():
    scopes = {
        "alle_lehrkraefte": {
            "where": "",
            "params": []
        },
        "lehrkraft_id_1": {
            "where": "AND lehrkraft_id = %s",
            "params": [1]
        },
        "alle_ausser_lehrkraft_id_1": {
            "where": "AND lehrkraft_id <> %s",
            "params": [1]
        }
    }

    output = {
        "auswertung": "09_lehrkraft_als_zustandsoperator",
        "modellannahme": "Lehrkraft wirkt als Transformationsoperator im FRZK-Zustandsraum.",
        "dimensions": DIMENSIONS,
        "scopes": {}
    }

    for scope_name, cfg in scopes.items():
        rows = load_rows(cfg["where"], cfg["params"])
        transitions = build_transitions(rows)

        output["scopes"][scope_name] = {
            "summary": summarize_scope(rows, transitions),
            "records": rows,
            "transitions": transitions
        }

    Path(OUTPUT_JSON).write_text(
        json.dumps(output, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8"
    )

    print(f"Export abgeschlossen: {OUTPUT_JSON}")


if __name__ == "__main__":
    main()