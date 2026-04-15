# ============================================================
# export_cluster_frzk.py
# Exportiert Daten aus MySQL -> berechnet Cluster -> speichert JSON
# ============================================================

import json
import pymysql
import numpy as np
from sklearn.cluster import KMeans
from datetime import datetime

# -------------------------------
# DB CONFIG
# -------------------------------
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "icas",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor
}

# -------------------------------
# PARAMETER
# -------------------------------
K = 3  # Anzahl Cluster (anpassbar)
OUTPUT_FILE = "frzk_cluster_output.json"

# -------------------------------
# DATEN LADEN
# -------------------------------
def load_data():
    connection = pymysql.connect(**DB_CONFIG)

    query = """
    SELECT 
        id,
        datum,
        gruppe_id,
        lehrkraft_id,
        x_kognition,
        x_sozial,
        x_affektiv,
        x_motivation,
        x_methodik,
        x_performanz,
        x_regulation
    FROM datenm_values_sem_dichte_lehrer_type_3
    WHERE datum>'2025-09-01' and x_kognition IS NOT NULL
    ORDER BY datum ASC
    """

    with connection.cursor() as cursor:
        cursor.execute(query)
        rows = cursor.fetchall()

    connection.close()
    return rows


# -------------------------------
# VEKTOREN ERSTELLEN
# -------------------------------
def build_vectors(rows):
    vectors = []
    for r in rows:
        vec = [
            float(r["x_kognition"]),
            float(r["x_sozial"]),
            float(r["x_affektiv"]),
            float(r["x_motivation"]),
            float(r["x_methodik"]),
            float(r["x_performanz"]),
            float(r["x_regulation"])
        ]
        vectors.append(vec)
    return np.array(vectors)


# -------------------------------
# CLUSTER BERECHNUNG
# -------------------------------
def compute_clusters(X):
    kmeans = KMeans(n_clusters=K, random_state=42, n_init=10)
    labels = kmeans.fit_predict(X)
    centers = kmeans.cluster_centers_
    return labels, centers


# -------------------------------
# SEMANTISCHE DICHTE
# -------------------------------
def semantic_density(vec):
    return float(np.linalg.norm(vec))


# -------------------------------
# DOMINANTE DIMENSION
# -------------------------------
DIM_NAMES = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation"
]

def dominant_dimension(vec):
    idx = int(np.argmax(np.abs(vec)))
    return DIM_NAMES[idx], float(vec[idx])


# -------------------------------
# JSON AUFBAU
# -------------------------------
def build_json(rows, X, labels, centers):
    output = {
        "meta": {
            "generated_at": datetime.now().isoformat(),
            "clusters": K,
            "dimensions": DIM_NAMES
        },
        "cluster_centers": [],
        "data": []
    }

    # Clusterzentren
    for i, c in enumerate(centers):
        dim, val = dominant_dimension(c)
        output["cluster_centers"].append({
            "cluster": i,
            "center": c.tolist(),
            "semantic_density": semantic_density(c),
            "dominant_dimension": dim,
            "dominant_value": val
        })

    # Einzelpunkte
    for i, r in enumerate(rows):
        vec = X[i]
        dim, val = dominant_dimension(vec)

        output["data"].append({
            "id": r["id"],
            "datum": str(r["datum"]),
            "gruppe_id": r["gruppe_id"],
            "lehrkraft_id": r["lehrkraft_id"],
            "vector": vec.tolist(),
            "cluster": int(labels[i]),
            "semantic_density": semantic_density(vec),
            "dominant_dimension": dim,
            "dominant_value": val
        })

    return output


# -------------------------------
# MAIN
# -------------------------------
def main():
    print("Lade Daten...")
    rows = load_data()

    print("Erzeuge Vektoren...")
    X = build_vectors(rows)

    print("Berechne Cluster...")
    labels, centers = compute_clusters(X)

    print("Erzeuge JSON...")
    output = build_json(rows, X, labels, centers)

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(output, f, ensure_ascii=False, indent=2)

    print("Fertig:", OUTPUT_FILE)


if __name__ == "__main__":
    main()