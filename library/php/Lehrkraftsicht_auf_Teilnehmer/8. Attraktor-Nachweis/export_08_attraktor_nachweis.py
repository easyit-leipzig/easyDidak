import json
import math
from pathlib import Path

import mysql.connector
import numpy as np

# ============================================================
# DB CONFIG
# ============================================================

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

# ============================================================
# OUTPUT
# ============================================================

OUTPUT_JSON = "auswertung_08_attraktor_nachweis.json"

# ============================================================
# VEKTOREN
# ============================================================

VECTOR_COLUMNS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation"
]

# ============================================================
# NULL-BEDINGUNG
# ============================================================

vector_not_null_condition = """
f.x_kognition IS NOT NULL
AND f.x_sozial IS NOT NULL
AND f.x_affektiv IS NOT NULL
AND f.x_motivation IS NOT NULL
AND f.x_methodik IS NOT NULL
AND f.x_performanz IS NOT NULL
AND f.x_regulation IS NOT NULL
"""

# ============================================================
# SCOPES
# ============================================================

SCOPES = {
    "alle_lehrkraefte": "1=1",
    "nur_lehrkraft_1": "m.lehrkraft_id = 1",
    "ohne_lehrkraft_1": "m.lehrkraft_id <> 1"
}

# ============================================================
# COSINE SIMILARITY
# ============================================================

def cosine_similarity(v1, v2):

    v1 = np.array(v1, dtype=float)
    v2 = np.array(v2, dtype=float)

    norm1 = np.linalg.norm(v1)
    norm2 = np.linalg.norm(v2)

    if norm1 == 0 or norm2 == 0:
        return 0.0

    return float(np.dot(v1, v2) / (norm1 * norm2))

# ============================================================
# DB CONNECT
# ============================================================

conn = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor(dictionary=True)

# ============================================================
# RESULT
# ============================================================

result = {}

# ============================================================
# ITERATION
# ============================================================

for scope_name, scope_condition in SCOPES.items():

    print(f"\n===== {scope_name} =====")

    sql = f"""
    SELECT
        m.id,
        m.gruppe_id,
        m.teilnehmer_id,
        m.lehrkraft_id,
        m.datum,
        m.thema,

        f.x_kognition,
        f.x_sozial,
        f.x_affektiv,
        f.x_motivation,
        f.x_methodik,
        f.x_performanz,
        f.x_regulation,

        f.d_semantisch,
        f.dominante_dimension,
        f.polaritaet_gesamt

    FROM frzk_semantische_dichte_lehrer f
    JOIN mtr_rueckkopplung_datenmaske m
        ON m.id = f.id_mtr_rueckkopplung_datenmaske

    WHERE
        {scope_condition}
        AND {vector_not_null_condition}

    ORDER BY
        m.teilnehmer_id,
        m.datum ASC,
        f.id ASC
    """

    cursor.execute(sql)
    rows = cursor.fetchall()

    participant_map = {}

    for row in rows:

        teilnehmer_id = row["teilnehmer_id"]

        vector = [
            float(row["x_kognition"]),
            float(row["x_sozial"]),
            float(row["x_affektiv"]),
            float(row["x_motivation"]),
            float(row["x_methodik"]),
            float(row["x_performanz"]),
            float(row["x_regulation"])
        ]

        if teilnehmer_id not in participant_map:
            participant_map[teilnehmer_id] = []

        participant_map[teilnehmer_id].append({
            "datum": str(row["datum"]),
            "gruppe_id": row["gruppe_id"],
            "lehrkraft_id": row["lehrkraft_id"],
            "thema": row["thema"],
            "vector": vector,
            "d_semantisch": float(row["d_semantisch"]),
            "dominante_dimension": row["dominante_dimension"],
            "polaritaet_gesamt": row["polaritaet_gesamt"]
        })

    participant_results = []

    all_similarities = []
    all_drifts = []

    # ========================================================
    # ATTRAKTORANALYSE
    # ========================================================

    for teilnehmer_id, states in participant_map.items():

        similarities = []
        drifts = []

        if len(states) < 2:
            continue

        for i in range(len(states) - 1):

            v1 = states[i]["vector"]
            v2 = states[i + 1]["vector"]

            cosine = cosine_similarity(v1, v2)

            drift = float(np.linalg.norm(
                np.array(v2) - np.array(v1)
            ))

            similarities.append(cosine)
            drifts.append(drift)

            all_similarities.append(cosine)
            all_drifts.append(drift)

        mean_similarity = float(np.mean(similarities))
        mean_drift = float(np.mean(drifts))

        attractor_strength = mean_similarity / (1 + mean_drift)

        participant_results.append({
            "teilnehmer_id": teilnehmer_id,
            "anzahl_zustaende": len(states),
            "mean_cosine_similarity": mean_similarity,
            "mean_drift": mean_drift,
            "attractor_strength": attractor_strength,
            "states": states
        })

    # ========================================================
    # GESAMTWERTE
    # ========================================================

    if all_similarities:
        global_similarity = float(np.mean(all_similarities))
    else:
        global_similarity = 0.0

    if all_drifts:
        global_drift = float(np.mean(all_drifts))
    else:
        global_drift = 0.0

    global_attractor = global_similarity / (1 + global_drift)

    result[scope_name] = {
        "n_datensaetze": len(rows),
        "n_teilnehmer": len(participant_results),

        "global_similarity": global_similarity,
        "global_drift": global_drift,
        "global_attractor_strength": global_attractor,

        "participants": participant_results
    }

# ============================================================
# JSON EXPORT
# ============================================================

with open(OUTPUT_JSON, "w", encoding="utf-8") as f:
    json.dump(result, f, ensure_ascii=False, indent=4)

print("\nJSON gespeichert:", OUTPUT_JSON)

cursor.close()
conn.close()

print("\nFERTIG.")