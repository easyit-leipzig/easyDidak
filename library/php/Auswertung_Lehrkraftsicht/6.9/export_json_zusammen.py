import pymysql
import json
import math
from collections import defaultdict
from datetime import datetime

# ---------------------------------
# DB Verbindung
# ---------------------------------
conn = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="icas",
    charset="utf8mb4",
    cursorclass=pymysql.cursors.DictCursor
)

cursor = conn.cursor()

# ---------------------------------
# Daten laden
# ---------------------------------
sql = """
SELECT 
    datum,
    lehrkraft_id,
    x_kognition,
    x_sozial,
    x_affektiv,
    x_motivation,
    x_methodik,
    x_performanz,
    x_regulation
FROM sem_dichte_lehrer_werte_datenmaske where datum <'2025-09-01' 
ORDER BY datum ASC
"""

cursor.execute(sql)
rows = cursor.fetchall()

cursor.close()
conn.close()

# ---------------------------------
# Hilfsfunktionen
# ---------------------------------
DIM = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation"
]

def vec(row):
    return [float(row[d]) for d in DIM]

def mean_vec(vs):
    n = len(vs)
    return [sum(v[i] for v in vs)/n for i in range(7)]

def norm(v):
    return math.sqrt(sum(x*x for x in v))

def delta(v1, v0):
    return [v1[i]-v0[i] for i in range(7)]

def variance_all(vs):
    flat = [x for v in vs for x in v]
    m = sum(flat)/len(flat)
    return sum((x-m)**2 for x in flat)/len(flat)

# ---------------------------------
# Gruppierung
# ---------------------------------
def build_group(data, name):

    grouped = defaultdict(list)

    for r in data:
        d = r["datum"]
        if not isinstance(d, str):
            d = d.isoformat()
        grouped[d].append(vec(r))

    dates = sorted(grouped.keys())

    states = []
    prev = None
    deltas = []
    all_vecs = []

    for i, d in enumerate(dates):
        vs = grouped[d]
        s = mean_vec(vs)
        all_vecs.append(s)

        if prev is None:
            dvec = [0]*7
            dnorm = 0
        else:
            dvec = delta(s, prev)
            dnorm = norm(dvec)
            deltas.append(dnorm)

        var_dim = variance_all([s])  # lokale Dim-Varianz minimal
        coh_local = (1/(1+var_dim))*(1/(1+dnorm))

        states.append({
            "t": i,
            "datum": d,
            "state": s,
            "norm": norm(s),
            "delta_norm": dnorm,
            "kohärenz_lokal": coh_local
        })

        prev = s

    # globale Kennzahlen
    var = variance_all(all_vecs)
    d_mean = sum(deltas)/len(deltas) if deltas else 0

    stab = 1 - d_mean
    coh = (1/(1+var))*(1/(1+d_mean))

    return {
        "gruppe": name,
        "stabilität": stab,
        "kohärenz": coh,
        "varianz": var,
        "delta_mittel": d_mean,
        "zustände": states
    }

# ---------------------------------
# Datensplits
# ---------------------------------
all_data = rows
lk1 = [r for r in rows if r["lehrkraft_id"] == 1]
nlk = [r for r in rows if r["lehrkraft_id"] != 1]

# ---------------------------------
# Berechnung
# ---------------------------------
result = {
    "alle": build_group(all_data, "alle"),
    "lehrkraft_1": build_group(lk1, "lehrkraft_1"),
    "nicht_lehrkraft_1": build_group(nlk, "nicht_lehrkraft_1")
}

# ---------------------------------
# JSON speichern
# ---------------------------------
with open("frzk_6x9_zustaende.json", "w", encoding="utf-8") as f:
    json.dump(result, f, indent=2, ensure_ascii=False)

print("✔ JSON erstellt: frzk_6x9_zustaende.json")

# ---------------------------------
# Direkt-Ausgabe (wichtig für dich)
# ---------------------------------
for k in result:
    print("\n", k)
    print("Stabilität:", round(result[k]["stabilität"], 4))
    print("Kohärenz:", round(result[k]["kohärenz"], 4))
    print("Varianz:", round(result[k]["varianz"], 4))