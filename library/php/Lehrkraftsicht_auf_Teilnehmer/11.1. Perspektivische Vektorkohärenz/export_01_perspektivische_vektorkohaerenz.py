import json
import math
from pathlib import Path
from datetime import datetime
from collections import defaultdict

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

OUTFILE = Path("auswertung_01_perspektivische_vektorkohaerenz.json")

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def vec(row, prefix="x_"):
    return [float(row.get(prefix + d) or 0.0) for d in DIMENSIONS]


def norm(v):
    return math.sqrt(sum(x * x for x in v))


def mean_vec(rows):
    if not rows:
        return [0.0] * 7
    s = [0.0] * 7
    for r in rows:
        v = vec(r)
        for i in range(7):
            s[i] += v[i]
    return [x / len(rows) for x in s]


def fetch_dicts(cur, sql, params=None):
    cur.execute(sql, params or ())
    return cur.fetchall()


def main():
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

    lk_groups = defaultdict(list)
    for r in lehrer_rows:
        key = (
            int(r["teilnehmer_id"]),
            int(r["gruppe_id"]),
            str(r["datum"])
        )
        lk_groups[key].append(r)

    tn_by_key = defaultdict(list)
    for r in teilnehmer_rows:
        key = (
            int(r["teilnehmer_id"]),
            int(r["gruppe_id"]),
            str(r["datum"])
        )
        tn_by_key[key].append(r)

    matches = []
    unmatched_lehrer = []

    for key, lk_items in lk_groups.items():
        tn_items = tn_by_key.get(key, [])

        if not tn_items:
            unmatched_lehrer.append({
                "teilnehmer_id": key[0],
                "gruppe_id": key[1],
                "datum": key[2],
                "lehrer_satz_anzahl": len(lk_items)
            })
            continue

        lk_vector = mean_vec(lk_items)

        for tn in tn_items:
            matches.append({
                "match_key": {
                    "teilnehmer_id": key[0],
                    "gruppe_id": key[1],
                    "datum": key[2],
                    "matching_typ": "teilnehmer_id + gruppe_id + datum"
                },
                "lehrkraft": {
                    "lehrkraft_id": 1,
                    "satz_anzahl": len(lk_items),
                    "ids": [int(x["id"]) for x in lk_items],
                    "vektor_7d": dict(zip(DIMENSIONS, lk_vector)),
                    "d_semantisch_mean": sum(float(x.get("d_semantisch") or 0.0) for x in lk_items) / len(lk_items),
                    "dominante_dimensionen": [x.get("dominante_dimension") for x in lk_items],
                    "polaritaeten": [x.get("polaritaet_gesamt") for x in lk_items]
                },
                "teilnehmer_7d": {
                    "id": int(tn["id"]),
                    "rueckkopplung_teilnehmer_id": int(tn["rueckkopplung_teilnehmer_id"]),
                    "ue_id": int(tn["ue_id"] or 0),
                    "zeitpunkt": str(tn["zeitpunkt"]),
                    "vektor_7d": dict(zip(DIMENSIONS, vec(tn))),
                    "d_semantisch": float(tn.get("d_semantisch") or 0.0),
                    "dominante_dimension": tn.get("dominante_dimension"),
                    "polaritaet_gesamt": tn.get("polaritaet_gesamt"),
                    "emotion_ids": tn.get("emotion_ids"),
                    "emotion_valenz": tn.get("emotion_valenz"),
                    "emotion_aktivierung": tn.get("emotion_aktivierung"),
                    "emotion_anzahl": tn.get("emotion_anzahl")
                }
            })

    payload = {
        "auswertungspunkt": "1. Perspektivische Vektorkohärenz",
        "beschreibung": "Vergleich der 7D-Richtung von Lehrkraftvektor und Teilnehmer-7D-Vektor über Cosine Similarity.",
        "filter": {
            "lehrkraft_id": 1,
            "matching": "teilnehmer_id + gruppe_id + datum",
            "lehrkraft_quelle": "sql_semantische_dichte_lehrer_type_1",
            "teilnehmer_quelle": "frzk_semantische_dichte_teilnehmer_7d"
        },
        "dimensionen": DIMENSIONS,
        "summary": {
            "lehrer_rohzeilen": len(lehrer_rows),
            "teilnehmer_rohzeilen": len(teilnehmer_rows),
            "lehrer_aggregierte_termine": len(lk_groups),
            "matches": len(matches),
            "unmatched_lehrer_termine": len(unmatched_lehrer)
        },
        "matches": matches,
        "unmatched_lehrer": unmatched_lehrer,
        "created_at": datetime.now().isoformat(timespec="seconds")
    }

    OUTFILE.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"JSON erzeugt: {OUTFILE.resolve()}")
    print(json.dumps(payload["summary"], ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()