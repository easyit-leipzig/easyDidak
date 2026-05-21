# -*- coding: utf-8 -*-
"""
Export 01 – Stabilisierungsvorhersage des FRZK

Erzeugt eine JSON-Datei mit drei Scopes:
1. alle_lehrkraefte
2. lehrkraft_1
3. ohne_lehrkraft_1
"""

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

OUTPUT_FILE = Path("auswertung_01_stabilisierungsvorhersage.json")

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
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return obj


def fetch_rows(where_clause: str = "", params=None):
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
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch,
            token_anzahl,
            funktionsklassen_anzahl_gesamt,
            operator_count,
            modulator_count,
            has_operator,
            operator_names,
            modulator_names
        FROM analyze_lehrkraftdaten
        {where_clause}
        ORDER BY teilnehmer_id, datum, id
    """

    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(sql, params)
        return cur.fetchall()
    finally:
        conn.close()


def mean(values):
    values = [v for v in values if v is not None]
    return sum(values) / len(values) if values else None


def stddev(values):
    values = [v for v in values if v is not None]
    if len(values) < 2:
        return None
    m = mean(values)
    return math.sqrt(sum((v - m) ** 2 for v in values) / (len(values) - 1))


def euclidean_delta(row_a, row_b):
    total = 0.0
    for dim in DIMENSIONS:
        a = row_a.get(dim)
        b = row_b.get(dim)
        if a is None or b is None:
            continue
        total += (float(b) - float(a)) ** 2
    return math.sqrt(total)


def compute_scope_metrics(rows):
    by_participant = {}

    for row in rows:
        tid = row["teilnehmer_id"]
        by_participant.setdefault(tid, []).append(row)

    participant_metrics = []

    for tid, items in by_participant.items():
        items = sorted(items, key=lambda r: (r["datum"], r["id"]))

        d_values = [float(r["d_semantisch"]) for r in items if r["d_semantisch"] is not None]

        deltas = []
        dominance_changes = 0
        dominance_pairs = 0

        for prev, curr in zip(items, items[1:]):
            deltas.append(euclidean_delta(prev, curr))

            if prev.get("dominante_dimension") and curr.get("dominante_dimension"):
                dominance_pairs += 1
                if prev["dominante_dimension"] != curr["dominante_dimension"]:
                    dominance_changes += 1

        dimension_std = {
            dim.replace("x_", "") + "_varianz": stddev(
                [float(r[dim]) for r in items if r.get(dim) is not None]
            )
            for dim in DIMENSIONS
        }

        stabilitaet = stddev(d_values)
        mittlere_delta_bewegung = mean(deltas)
        delta_std = stddev(deltas)

        dominanzwechsel_rate = (
            dominance_changes / dominance_pairs if dominance_pairs > 0 else None
        )

        dominanzstabilitaet = (
            1 - dominanzwechsel_rate if dominanzwechsel_rate is not None else None
        )

        kohaerenz_index = (
            1 / (1 + stabilitaet) if stabilitaet is not None else None
        )

        participant_metrics.append({
            "teilnehmer_id": tid,
            "n": len(items),
            "stabilitaet_std_d_semantisch": stabilitaet,
            "mittlere_delta_bewegung": mittlere_delta_bewegung,
            "delta_std": delta_std,
            "dominanzwechsel": dominance_changes,
            "dominanzwechsel_rate": dominanzwechsel_rate,
            "dominanzstabilitaet": dominanzstabilitaet,
            "kohaerenz_index": kohaerenz_index,
            **dimension_std
        })

    participant_metrics = sorted(
        participant_metrics,
        key=lambda x: (
            x["stabilitaet_std_d_semantisch"] is None,
            x["stabilitaet_std_d_semantisch"] or 999999
        )
    )

    return {
        "anzahl_datensaetze": len(rows),
        "anzahl_teilnehmer": len(by_participant),
        "teilnehmer_metrics": participant_metrics,
        "scope_summary": {
            "mittlere_stabilitaet": mean([
                x["stabilitaet_std_d_semantisch"]
                for x in participant_metrics
            ]),
            "mittlere_delta_bewegung": mean([
                x["mittlere_delta_bewegung"]
                for x in participant_metrics
            ]),
            "mittlere_dominanzstabilitaet": mean([
                x["dominanzstabilitaet"]
                for x in participant_metrics
            ]),
            "mittlerer_kohaerenz_index": mean([
                x["kohaerenz_index"]
                for x in participant_metrics
            ])
        }
    }


def build_scope(name, where_clause="", params=None):
    rows = fetch_rows(where_clause, params)
    return {
        "name": name,
        "beschreibung": name,
        "rows": rows,
        "metrics": compute_scope_metrics(rows)
    }


def main():
    data = {
        "auswertung": "01_stabilisierungsvorhersage_frzk",
        "frzk_vorhersage": (
            "Wiederholte kohärente Interaktion erzeugt stabile Zustandsräume."
        ),
        "messlogik": {
            "sinkende_varianz": "STDDEV(d_semantisch) und Dimensionsvarianzen",
            "sinkende_delta_bewegung": "euklidische Distanz aufeinanderfolgender Zustände",
            "steigende_dominanzstabilitaet": "1 - Rate der Wechsel dominanter Dimensionen",
            "steigende_kohaerenz": "1 / (1 + STDDEV(d_semantisch))"
        },
        "scopes": {
            "alle_lehrkraefte": build_scope("alle_lehrkraefte"),
            "lehrkraft_1": build_scope(
                "lehrkraft_1",
                "WHERE lehrkraft_id = %s",
                [1]
            ),
            "ohne_lehrkraft_1": build_scope(
                "ohne_lehrkraft_1",
                "WHERE lehrkraft_id <> %s OR lehrkraft_id IS NULL",
                [1]
            )
        }
    }

    with OUTPUT_FILE.open("w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2, default=json_default)

    print(f"JSON erzeugt: {OUTPUT_FILE.resolve()}")


if __name__ == "__main__":
    main()