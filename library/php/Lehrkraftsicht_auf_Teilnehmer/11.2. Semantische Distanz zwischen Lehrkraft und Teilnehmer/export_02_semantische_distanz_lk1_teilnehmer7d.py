import json
import math
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

OUTFILE = Path("auswertung_02_semantische_distanz_lk1_teilnehmer7d.json")

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def euclidean_distance(l, t):
    return math.sqrt(sum((l[f"x_{d}"] - t[f"x_{d}"]) ** 2 for d in DIMENSIONS))


def cosine_similarity(l, t):
    dot = sum(l[f"x_{d}"] * t[f"x_{d}"] for d in DIMENSIONS)
    nl = math.sqrt(sum(l[f"x_{d}"] ** 2 for d in DIMENSIONS))
    nt = math.sqrt(sum(t[f"x_{d}"] ** 2 for d in DIMENSIONS))
    if nl == 0 or nt == 0:
        return None
    return dot / (nl * nt)


def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    cur = conn.cursor(dictionary=True)

    sql = """
        SELECT
            l.id AS lehrer_id,
            t.id AS teilnehmer7d_id,

            l.lehrkraft_id,
            l.ue_id AS l_ue_id,
            t.ue_id AS t_ue_id,

            l.gruppe_id,
            l.teilnehmer_id,

            l.datum AS lehrer_datum,
            DATE(t.zeitpunkt) AS teilnehmer_datum,
            t.zeitpunkt AS teilnehmer_zeitpunkt,

            l.x_kognition AS l_x_kognition,
            l.x_sozial AS l_x_sozial,
            l.x_affektiv AS l_x_affektiv,
            l.x_motivation AS l_x_motivation,
            l.x_methodik AS l_x_methodik,
            l.x_performanz AS l_x_performanz,
            l.x_regulation AS l_x_regulation,

            t.x_kognition AS t_x_kognition,
            t.x_sozial AS t_x_sozial,
            t.x_affektiv AS t_x_affektiv,
            t.x_motivation AS t_x_motivation,
            t.x_methodik AS t_x_methodik,
            t.x_performanz AS t_x_performanz,
            t.x_regulation AS t_x_regulation,

            l.dominante_dimension AS l_dominante_dimension,
            t.dominante_dimension AS t_dominante_dimension,
            l.polaritaet_gesamt AS l_polaritaet,
            t.polaritaet_gesamt AS t_polaritaet,
            l.d_semantisch AS l_d_semantisch,
            t.d_semantisch AS t_d_semantisch,

            t.emotion_ids,
            t.emotion_valenz,
            t.emotion_aktivierung,
            t.emotion_anzahl
        FROM sql_semantische_dichte_lehrer_type_1 l
        INNER JOIN frzk_semantische_dichte_teilnehmer_7d t
            ON l.gruppe_id = t.gruppe_id
           AND l.teilnehmer_id = t.teilnehmer_id
           AND DATE(l.datum) = DATE(t.zeitpunkt)
        WHERE l.lehrkraft_id = 1
        ORDER BY l.datum, l.gruppe_id, l.teilnehmer_id, l.id, t.id
    """

    cur.execute(sql)
    rows = cur.fetchall()

    records = []
    for r in rows:
        l_vec = {f"x_{d}": float(r[f"l_x_{d}"] or 0) for d in DIMENSIONS}
        t_vec = {f"x_{d}": float(r[f"t_x_{d}"] or 0) for d in DIMENSIONS}

        dist = euclidean_distance(l_vec, t_vec)
        cos = cosine_similarity(l_vec, t_vec)

        delta = {
            d: float(l_vec[f"x_{d}"] - t_vec[f"x_{d}"])
            for d in DIMENSIONS
        }

        records.append({
            "lehrer_id": r["lehrer_id"],
            "teilnehmer7d_id": r["teilnehmer7d_id"],
            "lehrkraft_id": r["lehrkraft_id"],
            "gruppe_id": r["gruppe_id"],
            "teilnehmer_id": r["teilnehmer_id"],
            "lehrer_datum": str(r["lehrer_datum"]),
            "teilnehmer_zeitpunkt": str(r["teilnehmer_zeitpunkt"]),
            "l_ue_id": r["l_ue_id"],
            "t_ue_id": r["t_ue_id"],
            "lehrkraft_vector": l_vec,
            "teilnehmer_vector": t_vec,
            "delta_vector_L_minus_T": delta,
            "D_LT": dist,
            "cosine_LT": cos,
            "dominanz_match": r["l_dominante_dimension"] == r["t_dominante_dimension"],
            "polaritaet_match": r["l_polaritaet"] == r["t_polaritaet"],
            "l_dominante_dimension": r["l_dominante_dimension"],
            "t_dominante_dimension": r["t_dominante_dimension"],
            "l_polaritaet": r["l_polaritaet"],
            "t_polaritaet": r["t_polaritaet"],
            "l_d_semantisch": float(r["l_d_semantisch"] or 0),
            "t_d_semantisch": float(r["t_d_semantisch"] or 0),
            "emotion_ids": r["emotion_ids"],
            "emotion_valenz": float(r["emotion_valenz"] or 0),
            "emotion_aktivierung": float(r["emotion_aktivierung"] or 0),
            "emotion_anzahl": int(r["emotion_anzahl"] or 0)
        })

    result = {
        "auswertungspunkt": "02_semantische_distanz_lehrkraft_teilnehmer7d",
        "beschreibung": "D_LT = || L_t - T_t || für lehrkraft_id=1 gegen Teilnehmer-7D-Sicht",
        "matching": "gruppe_id + teilnehmer_id + gleiches Datum",
        "anzahl_matches": len(records),
        "dimensions": DIMENSIONS,
        "records": records
    }

    OUTFILE.write_text(json.dumps(result, ensure_ascii=False, indent=2), encoding="utf-8")

    cur.close()
    conn.close()

    print(f"Export abgeschlossen: {OUTFILE}")
    print(f"Matches: {len(records)}")


if __name__ == "__main__":
    main()