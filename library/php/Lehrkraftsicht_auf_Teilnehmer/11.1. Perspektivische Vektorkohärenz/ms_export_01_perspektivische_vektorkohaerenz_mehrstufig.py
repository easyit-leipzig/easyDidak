# -*- coding: utf-8 -*-
"""
Auswertungspunkt 1: Perspektivische Vektorkohärenz – mehrstufiges FRZK-Matching

Erzeugt ein JSON mit direkter, zeitfensterbasierter, rekursiv-lagbasierter
und semantischer Nachbarschaftskopplung zwischen Lehrkraftsicht lehrkraft_id=1
und Teilnehmer-7D-Sicht.

Matching-Ebenen:
1) exact_sync: gleicher teilnehmer_id, gruppe_id, Datum
2) temporal_window: gleicher teilnehmer_id, gruppe_id, Datum innerhalb +/- WINDOW_DAYS
3) recursive_lag: Teilnehmerreaktion in den nächsten 1..MAX_SESSION_LAG Teilnehmerterminen
4) semantic_neighborhood: semantisch ähnliche Zustände gleicher Teilnehmer/Gruppe unabhängig vom exakten Datum,
   begrenzt durch MAX_SEMANTIC_DAY_DISTANCE und COSINE_THRESHOLD_SEMANTIC

Ausgabe:
- alle Kandidatenbeziehungen mit Strategie-Flags
- beste Kandidaten pro Lehrkrafttermin
- konservative und erweiterte Zusammenfassungen
"""

import json
import math
from pathlib import Path
from datetime import datetime, date
from collections import defaultdict, Counter

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

OUTFILE = Path("auswertung_01_perspektivische_vektorkohaerenz_mehrstufig.json")

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]

WINDOW_DAYS = 3
MAX_SESSION_LAG = 3
MAX_SEMANTIC_DAY_DISTANCE = 45
COSINE_THRESHOLD_SEMANTIC = 0.75
MIN_MATCH_SCORE = 0.55
EPS = 1e-12


def as_date(value):
    if isinstance(value, datetime):
        return value.date()
    if isinstance(value, date):
        return value
    return datetime.fromisoformat(str(value)[:10]).date()


def vec_from_row(row, prefix="x_"):
    return [float(row.get(prefix + d) or 0.0) for d in DIMENSIONS]


def norm(v):
    return math.sqrt(sum(x * x for x in v))


def cosine(a, b):
    na, nb = norm(a), norm(b)
    if na <= EPS or nb <= EPS:
        return None
    return sum(x * y for x, y in zip(a, b)) / (na * nb)


def mean(values):
    values = [float(v) for v in values if v is not None]
    return sum(values) / len(values) if values else None


def mean_vec(rows):
    if not rows:
        return [0.0] * 7
    acc = [0.0] * 7
    for r in rows:
        v = vec_from_row(r)
        for i in range(7):
            acc[i] += v[i]
    return [x / len(rows) for x in acc]


def mode(values):
    vals = [v for v in values if v is not None]
    return Counter(vals).most_common(1)[0][0] if vals else None


def polarity_from_rows(rows):
    vals = [int(v) for v in (r.get("polaritaet_gesamt") for r in rows) if v is not None]
    return mode(vals) if vals else None


def dominant_from_vector(v):
    idx = max(range(len(v)), key=lambda i: abs(v[i]))
    return DIMENSIONS[idx]


def fetch_dicts(cur, sql, params=None):
    cur.execute(sql, params or ())
    return cur.fetchall()


def score_candidate(cos_val, day_delta, dominance_match, polarity_match, ue_match):
    # Cosine wird von [-1,1] auf [0,1] normiert.
    cos_norm = 0.0 if cos_val is None else (cos_val + 1.0) / 2.0
    time_weight = math.exp(-abs(day_delta) / max(WINDOW_DAYS, 1))
    return (
        0.55 * cos_norm
        + 0.20 * time_weight
        + 0.10 * (1.0 if dominance_match else 0.0)
        + 0.10 * (1.0 if polarity_match else 0.0)
        + 0.05 * (1.0 if ue_match else 0.0)
    )


def classify_cosine(c):
    if c is None:
        return "nicht_berechenbar"
    if c >= 0.75:
        return "hoch"
    if c >= 0.35:
        return "mittel"
    if c >= 0.00:
        return "niedrig"
    return "negativ_wahrnehmungsbruch"


def load_data():
    conn = mysql.connector.connect(**DB_CONFIG)
    cur = conn.cursor(dictionary=True)

    lehrer_rows = fetch_dicts(cur, """
        SELECT
            id,
            ue_id,
            datum,
            dat_ges,
            lehrkraft_id,
            gruppe_id,
            teilnehmer_id,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
            x_kognition, x_sozial, x_affektiv, x_motivation,
            x_methodik, x_performanz, x_regulation,
            d_semantisch,
            dominante_dimension,
            polaritaet_gesamt
        FROM sql_semantische_dichte_lehrer_type_1
        WHERE lehrkraft_id = 1
          AND teilnehmer_id IS NOT NULL
          AND gruppe_id IS NOT NULL
          AND datum IS NOT NULL
        ORDER BY datum, gruppe_id, teilnehmer_id, id
    """)

    teilnehmer_rows = fetch_dicts(cur, """
        SELECT
            id,
            rueckkopplung_teilnehmer_id,
            ue_id,
            ue_zuweisung_teilnehmer_id,
            teilnehmer_id,
            gruppe_id,
            zeitpunkt,
            DATE(zeitpunkt) AS datum,
            x_kognition, x_sozial, x_affektiv, x_motivation,
            x_methodik, x_performanz, x_regulation,
            d_semantisch,
            dominante_dimension,
            polaritaet_gesamt,
            emotion_ids,
            emotion_valenz,
            emotion_aktivierung,
            emotion_anzahl
        FROM frzk_semantische_dichte_teilnehmer_7d
        WHERE teilnehmer_id IS NOT NULL
          AND gruppe_id IS NOT NULL
          AND zeitpunkt IS NOT NULL
        ORDER BY zeitpunkt, gruppe_id, teilnehmer_id, id
    """)

    cur.close()
    conn.close()
    return lehrer_rows, teilnehmer_rows


def aggregate_lehrer(lehrer_rows):
    grouped = defaultdict(list)
    for r in lehrer_rows:
        key = (int(r["teilnehmer_id"]), int(r["gruppe_id"]), str(as_date(r["datum"])))
        grouped[key].append(r)

    events = []
    for idx, (key, rows) in enumerate(sorted(grouped.items(), key=lambda x: (x[0][2], x[0][1], x[0][0]))):
        v = mean_vec(rows)
        ue_ids = sorted({int(r["ue_id"] or 0) for r in rows})
        events.append({
            "lk_event_id": f"LK{idx+1:05d}",
            "teilnehmer_id": key[0],
            "gruppe_id": key[1],
            "datum": key[2],
            "datum_obj": as_date(key[2]),
            "lehrkraft_id": 1,
            "satz_anzahl": len(rows),
            "ids": [int(r["id"]) for r in rows],
            "ue_ids": ue_ids,
            "vektor": v,
            "vektor_7d": dict(zip(DIMENSIONS, v)),
            "d_semantisch_mean": mean([r.get("d_semantisch") for r in rows]),
            "dominante_dimension": mode([r.get("dominante_dimension") for r in rows]) or dominant_from_vector(v),
            "dominante_dimensionen": [r.get("dominante_dimension") for r in rows],
            "polaritaet_gesamt": polarity_from_rows(rows),
            "polaritaeten": [r.get("polaritaet_gesamt") for r in rows],
        })
    return events


def normalize_teilnehmer(teilnehmer_rows):
    events = []
    for r in teilnehmer_rows:
        v = vec_from_row(r)
        events.append({
            "tn_event_id": f"TN{int(r['id']):05d}",
            "id": int(r["id"]),
            "rueckkopplung_teilnehmer_id": int(r.get("rueckkopplung_teilnehmer_id") or 0),
            "ue_id": int(r.get("ue_id") or 0),
            "ue_zuweisung_teilnehmer_id": int(r.get("ue_zuweisung_teilnehmer_id") or 0),
            "teilnehmer_id": int(r["teilnehmer_id"]),
            "gruppe_id": int(r["gruppe_id"]),
            "zeitpunkt": str(r["zeitpunkt"]),
            "datum": str(as_date(r["datum"])),
            "datum_obj": as_date(r["datum"]),
            "vektor": v,
            "vektor_7d": dict(zip(DIMENSIONS, v)),
            "d_semantisch": float(r.get("d_semantisch") or 0.0),
            "dominante_dimension": r.get("dominante_dimension") or dominant_from_vector(v),
            "polaritaet_gesamt": r.get("polaritaet_gesamt"),
            "emotion_ids": r.get("emotion_ids"),
            "emotion_valenz": None if r.get("emotion_valenz") is None else float(r.get("emotion_valenz")),
            "emotion_aktivierung": None if r.get("emotion_aktivierung") is None else float(r.get("emotion_aktivierung")),
            "emotion_anzahl": r.get("emotion_anzahl"),
        })
    return events


def build_candidates(lk_events, tn_events):
    tn_by_person_group = defaultdict(list)
    for tn in tn_events:
        tn_by_person_group[(tn["teilnehmer_id"], tn["gruppe_id"])].append(tn)
    for k in tn_by_person_group:
        tn_by_person_group[k].sort(key=lambda x: (x["datum_obj"], x["id"]))

    candidate_map = {}

    for lk in lk_events:
        key = (lk["teilnehmer_id"], lk["gruppe_id"])
        tn_list = tn_by_person_group.get(key, [])
        if not tn_list:
            continue

        # nächstfolgende Teilnehmertermine für rekursive Lags
        future_or_same = [tn for tn in tn_list if (tn["datum_obj"] - lk["datum_obj"]).days >= 0]
        lag_rank = {tn["tn_event_id"]: i + 1 for i, tn in enumerate(future_or_same[:MAX_SESSION_LAG])}

        for tn in tn_list:
            day_delta = (tn["datum_obj"] - lk["datum_obj"]).days
            abs_day_delta = abs(day_delta)
            cos_val = cosine(lk["vektor"], tn["vektor"])
            dominance_match = lk["dominante_dimension"] == tn["dominante_dimension"]
            polarity_match = (
                lk.get("polaritaet_gesamt") is not None
                and tn.get("polaritaet_gesamt") is not None
                and int(lk["polaritaet_gesamt"]) == int(tn["polaritaet_gesamt"])
            )
            ue_match = tn["ue_id"] != 0 and tn["ue_id"] in lk.get("ue_ids", [])

            strategies = []
            if day_delta == 0:
                strategies.append("exact_sync")
            if abs_day_delta <= WINDOW_DAYS:
                strategies.append("temporal_window")
            if tn["tn_event_id"] in lag_rank:
                strategies.append(f"recursive_lag_{lag_rank[tn['tn_event_id']]}")
            if (
                cos_val is not None
                and cos_val >= COSINE_THRESHOLD_SEMANTIC
                and abs_day_delta <= MAX_SEMANTIC_DAY_DISTANCE
            ):
                strategies.append("semantic_neighborhood")

            if not strategies:
                continue

            match_score = score_candidate(cos_val, day_delta, dominance_match, polarity_match, ue_match)
            if match_score < MIN_MATCH_SCORE and "exact_sync" not in strategies:
                continue

            pair_id = f"{lk['lk_event_id']}__{tn['tn_event_id']}"
            candidate_map[pair_id] = {
                "pair_id": pair_id,
                "strategies": strategies,
                "matching": {
                    "teilnehmer_id": lk["teilnehmer_id"],
                    "gruppe_id": lk["gruppe_id"],
                    "lehrer_datum": lk["datum"],
                    "teilnehmer_datum": tn["datum"],
                    "day_delta_tn_minus_lk": day_delta,
                    "abs_day_delta": abs_day_delta,
                    "ue_match": ue_match,
                    "dominance_match": dominance_match,
                    "polarity_match": polarity_match,
                    "recursive_lag_rank": lag_rank.get(tn["tn_event_id"]),
                },
                "metrics": {
                    "cosine_similarity": cos_val,
                    "cosine_klasse": classify_cosine(cos_val),
                    "match_score": match_score,
                    "lehrer_dichte": lk["d_semantisch_mean"],
                    "teilnehmer_dichte": tn["d_semantisch"],
                    "dichte_delta_lk_minus_tn": None if lk["d_semantisch_mean"] is None else lk["d_semantisch_mean"] - tn["d_semantisch"],
                },
                "lehrkraft": {k: v for k, v in lk.items() if k != "datum_obj" and k != "vektor"},
                "teilnehmer_7d": {k: v for k, v in tn.items() if k != "datum_obj" and k != "vektor"},
                "dimension_delta_lk_minus_tn": {
                    d: lk["vektor_7d"][d] - tn["vektor_7d"][d] for d in DIMENSIONS
                }
            }

    candidates = list(candidate_map.values())
    candidates.sort(key=lambda c: (
        c["matching"]["teilnehmer_id"],
        c["matching"]["gruppe_id"],
        c["matching"]["lehrer_datum"],
        -c["metrics"]["match_score"]
    ))
    return candidates


def choose_best_per_lk(candidates):
    by_lk = defaultdict(list)
    for c in candidates:
        by_lk[c["lehrkraft"]["lk_event_id"]].append(c)
    best = []
    for _, items in by_lk.items():
        items.sort(key=lambda c: (
            -c["metrics"]["match_score"],
            -(-abs(c["matching"]["day_delta_tn_minus_lk"])),
            -(c["metrics"]["cosine_similarity"] or -2)
        ))
        best.append(items[0])
    best.sort(key=lambda c: (c["matching"]["lehrer_datum"], c["matching"]["gruppe_id"], c["matching"]["teilnehmer_id"]))
    return best


def summarize(candidates, lk_events, tn_events):
    by_strategy = Counter()
    for c in candidates:
        for s in c["strategies"]:
            by_strategy[s] += 1

    exact = [c for c in candidates if "exact_sync" in c["strategies"]]
    high = [c for c in candidates if c["metrics"]["cosine_similarity"] is not None and c["metrics"]["cosine_similarity"] >= 0.75]
    negative = [c for c in candidates if c["metrics"]["cosine_similarity"] is not None and c["metrics"]["cosine_similarity"] < 0]

    return {
        "lehrer_events": len(lk_events),
        "teilnehmer_events": len(tn_events),
        "candidate_pairs_total": len(candidates),
        "exact_sync_pairs": len(exact),
        "high_cosine_pairs": len(high),
        "negative_pairs": len(negative),
        "by_strategy": dict(by_strategy),
        "cosine_mean_all_candidates": mean([c["metrics"]["cosine_similarity"] for c in candidates]),
        "match_score_mean_all_candidates": mean([c["metrics"]["match_score"] for c in candidates]),
    }


def main():
    lehrer_rows, teilnehmer_rows = load_data()
    lk_events = aggregate_lehrer(lehrer_rows)
    tn_events = normalize_teilnehmer(teilnehmer_rows)
    candidates = build_candidates(lk_events, tn_events)
    best = choose_best_per_lk(candidates)

    payload = {
        "auswertungspunkt": "1. Perspektivische Vektorkohärenz – mehrstufiges Matching",
        "beschreibung": "Direktes, zeitversetztes und semantisch-nachbarschaftliches Matching zwischen Lehrkraftsicht lehrkraft_id=1 und Teilnehmer-7D-Sicht.",
        "matching_parameter": {
            "exact_sync": "teilnehmer_id + gruppe_id + Datum identisch",
            "temporal_window_days": WINDOW_DAYS,
            "recursive_lag_sessions": MAX_SESSION_LAG,
            "semantic_cosine_threshold": COSINE_THRESHOLD_SEMANTIC,
            "semantic_max_day_distance": MAX_SEMANTIC_DAY_DISTANCE,
            "min_match_score_non_exact": MIN_MATCH_SCORE,
            "score_formula": "0.55*cos_norm + 0.20*time_weight + 0.10*dominance_match + 0.10*polarity_match + 0.05*ue_match"
        },
        "dimensionen": DIMENSIONS,
        "summary": summarize(candidates, lk_events, tn_events),
        "best_matches_per_lehrer_event": best,
        "candidate_matches": candidates,
        "unmatched_lehrer_events": [
            {k: v for k, v in lk.items() if k not in ("datum_obj", "vektor")}
            for lk in lk_events
            if not any(c["lehrkraft"]["lk_event_id"] == lk["lk_event_id"] for c in candidates)
        ],
        "created_at": datetime.now().isoformat(timespec="seconds")
    }

    OUTFILE.write_text(json.dumps(payload, ensure_ascii=False, indent=2, default=str), encoding="utf-8")
    print(f"JSON erzeugt: {OUTFILE.resolve()}")
    print(json.dumps(payload["summary"], ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
