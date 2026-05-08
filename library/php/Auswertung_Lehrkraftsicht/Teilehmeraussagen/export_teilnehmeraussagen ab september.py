#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
FRZK-Export aus datenm_values_sem_dichte_lehrer_type_3

Erzeugt ein JSON mit drei Auswertungsgruppen:
1. alle
2. lehrkraft_1  -> lehrkraft_id = 1
3. andere       -> lehrkraft_id <> 1

Voraussetzung:
    pip install mysql-connector-python
"""

import json
import math
import statistics
from datetime import date, datetime
from collections import Counter, defaultdict

import mysql.connector


DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "icas",
    "charset": "utf8mb4",
}

TABLE = "datenm_values_sem_dichte_lehrer_type_3"
OUTPUT_FILE = "frzk_auswertung_teilnehmeraussagen_alle_lk1_andere.json"
COUNT_NULL_AS_ANDERE = True

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DIMENSION_LABELS = {
    "x_kognition": "kognition",
    "x_sozial": "sozial",
    "x_affektiv": "affektiv",
    "x_motivation": "motivation",
    "x_methodik": "methodik",
    "x_performanz": "performanz",
    "x_regulation": "regulation",
}


def as_float(value, default=0.0):
    if value is None:
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def json_default(obj):
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def fetch_rows():
    conn = mysql.connector.connect(**DB_CONFIG)
    cur = conn.cursor(dictionary=True)

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
            type,
            {", ".join(DIMENSIONS)},
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        FROM {TABLE} where datum>='2025_09_01' 
        ORDER BY datum, id
    """

    cur.execute(sql)
    rows = cur.fetchall()
    cur.close()
    conn.close()
    return rows


def filter_group(rows, group_name):
    if group_name == "alle":
        return rows
    if group_name == "lehrkraft_1":
        return [r for r in rows if str(r.get("lehrkraft_id")) == "1"]
    if group_name == "andere":
        if COUNT_NULL_AS_ANDERE:
            return [r for r in rows if str(r.get("lehrkraft_id")) != "1"]
        return [r for r in rows if r.get("lehrkraft_id") is not None and str(r.get("lehrkraft_id")) != "1"]
    raise ValueError(f"Unbekannte Gruppe: {group_name}")


def describe(values):
    vals = [as_float(v) for v in values if v is not None]
    if not vals:
        return {"n": 0, "mean": None, "std": None, "min": None, "max": None, "sum": None}
    return {
        "n": len(vals),
        "mean": sum(vals) / len(vals),
        "std": statistics.stdev(vals) if len(vals) > 1 else 0.0,
        "min": min(vals),
        "max": max(vals),
        "sum": sum(vals),
    }


def vector_norm(row):
    return math.sqrt(sum(as_float(row.get(d)) ** 2 for d in DIMENSIONS))


def week_key(dt):
    if not dt:
        return "ohne_datum"
    if isinstance(dt, str):
        dt = datetime.fromisoformat(dt).date()
    iso = dt.isocalendar()
    return f"{iso.year}-KW{iso.week:02d}"


def aggregate(rows):
    n = len(rows)

    dim_stats = {
        DIMENSION_LABELS[d]: describe([r.get(d) for r in rows])
        for d in DIMENSIONS
    }

    mean_vector = {
        DIMENSION_LABELS[d]: dim_stats[DIMENSION_LABELS[d]]["mean"]
        for d in DIMENSIONS
    }

    d_values = [
        as_float(r.get("d_semantisch"), vector_norm(r)) if r.get("d_semantisch") is not None else vector_norm(r)
        for r in rows
    ]

    polarity_counts = Counter(str(r.get("polaritaet_gesamt")) for r in rows)
    dominant_counts = Counter(str(r.get("dominante_dimension")) for r in rows if r.get("dominante_dimension"))

    mean_vals = [v for v in mean_vector.values() if v is not None]
    if mean_vals:
        mean_level = sum(mean_vals) / len(mean_vals)
        dispersion = statistics.pstdev(mean_vals) if len(mean_vals) > 1 else 0.0
        coherence_index = mean_level / (1.0 + dispersion)
    else:
        mean_level = None
        dispersion = None
        coherence_index = None

    positive = polarity_counts.get("1", 0)
    negative = polarity_counts.get("-1", 0)
    neutral = polarity_counts.get("0", 0)
    polarity_balance = (positive - negative) / n if n > 0 else None

    weekly_rows = defaultdict(list)
    for r in rows:
        weekly_rows[week_key(r.get("datum"))].append(r)

    weekly = {}
    for kw, kw_rows in sorted(weekly_rows.items()):
        weekly[kw] = {
            "n": len(kw_rows),
            "mean_vector": {
                DIMENSION_LABELS[d]: describe([r.get(d) for r in kw_rows])["mean"]
                for d in DIMENSIONS
            },
            "d_semantisch_mean": describe([
                r.get("d_semantisch") if r.get("d_semantisch") is not None else vector_norm(r)
                for r in kw_rows
            ])["mean"],
            "polaritaet": dict(Counter(str(r.get("polaritaet_gesamt")) for r in kw_rows)),
            "dominante_dimension": dict(Counter(str(r.get("dominante_dimension")) for r in kw_rows if r.get("dominante_dimension"))),
        }

    evidence_rows = []
    for r in rows:
        evidence_rows.append({
            "id": r.get("id"),
            "datum": r.get("datum"),
            "gruppe_id": r.get("gruppe_id"),
            "teilnehmer_id": r.get("teilnehmer_id"),
            "lehrkraft_id": r.get("lehrkraft_id"),
            "fach": r.get("fach"),
            "thema": r.get("thema"),
            "bemerkung": r.get("bemerkung"),
            "vector": {DIMENSION_LABELS[d]: as_float(r.get(d)) for d in DIMENSIONS},
            "d_semantisch": as_float(r.get("d_semantisch"), vector_norm(r)),
            "dominante_dimension": r.get("dominante_dimension"),
            "dominante_dimension_wert": as_float(r.get("dominante_dimension_wert")),
            "polaritaet_gesamt": r.get("polaritaet_gesamt"),
        })

    return {
        "n": n,
        "dimensionen": dim_stats,
        "mean_vector": mean_vector,
        "d_semantisch": describe(d_values),
        "dominante_dimensionen": dict(dominant_counts),
        "polaritaet": {
            "counts": dict(polarity_counts),
            "positive": positive,
            "negative": negative,
            "neutral": neutral,
            "balance": polarity_balance,
        },
        "frzk_indizes": {
            "mean_level": mean_level,
            "dimension_dispersion": dispersion,
            "coherence_index_heuristisch": coherence_index,
            "hinweis": "Heuristischer Kohärenzindex: Mittelwert der Dimensionslage geteilt durch 1 + Streuung der Dimensionsmittelwerte. Für Signifikanz zusätzliche Tests verwenden.",
        },
        "zeitverlauf_kw": weekly,
        "belege": evidence_rows,
    }


def compare_groups(payload):
    lk1 = payload["gruppen"]["lehrkraft_1"]["mean_vector"]
    andere = payload["gruppen"]["andere"]["mean_vector"]

    diff = {}
    for dim in DIMENSION_LABELS.values():
        a = lk1.get(dim)
        b = andere.get(dim)
        diff[dim] = None if a is None or b is None else a - b

    return {
        "delta_lehrkraft_1_minus_andere": diff,
        "interpretation": {
            "positives_delta": "Lehrkraft 1 weist in dieser Dimension höhere mittlere FRZK-Ausprägung auf.",
            "negatives_delta": "Andere Lehrkräfte weisen in dieser Dimension höhere mittlere FRZK-Ausprägung auf.",
            "vorsicht": "Die Differenz ist deskriptiv. Für Signifikanz t-Test, Mann-Whitney, Bootstrap oder Mixed-Effects-Modell prüfen.",
        },
    }


def main():
    rows = fetch_rows()

    payload = {
        "metadata": {
            "quelle": TABLE,
            "gruppenlogik": {
                "alle": "alle Datensätze",
                "lehrkraft_1": "lehrkraft_id = 1",
                "andere": "lehrkraft_id <> 1" + (" inkl. NULL" if COUNT_NULL_AS_ANDERE else " exkl. NULL"),
            },
            "dimensionen": [DIMENSION_LABELS[d] for d in DIMENSIONS],
            "erstellt_am": datetime.now().isoformat(timespec="seconds"),
        },
        "gruppen": {},
    }

    for group_name in ["alle", "lehrkraft_1", "andere"]:
        group_rows = filter_group(rows, group_name)
        payload["gruppen"][group_name] = aggregate(group_rows)

    payload["vergleich"] = compare_groups(payload)

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2, default=json_default)

    print(f"JSON erzeugt: {OUTPUT_FILE}")
    print(f"Datensätze gesamt: {len(rows)}")
    for group_name in ["alle", "lehrkraft_1", "andere"]:
        print(f"{group_name}: {payload['gruppen'][group_name]['n']} Datensätze")


if __name__ == "__main__":
    main()
