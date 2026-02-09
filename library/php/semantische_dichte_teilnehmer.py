#!/usr/bin/env python3
# frzk_3d_schueler_semantische_dichte_emotionen.py
# FRZK-3D-Darstellung (Schülersicht) inkl. Emotionen

import json
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D  # noqa
import numpy as np

# -------------------------------------------------
# Dateien
# -------------------------------------------------
SEM_FILE = "frzk_semantische_dichte.json"
EMO_FILE = "_mtr_emotionen.json"

# -------------------------------------------------
# JSON laden
# -------------------------------------------------
with open(SEM_FILE, "r", encoding="utf-8") as f:
    sem_data = json.load(f)

with open(EMO_FILE, "r", encoding="utf-8") as f:
    emo_raw = json.load(f)

# -------------------------------------------------
# Emotionen: Mapping id -> Eigenschaften
# -------------------------------------------------
emotion_map = {
    int(e["id"]): {
        "emotion": e["emotion"],
        "valenz": float(e["valenz"]),
        "aktivierung": float(e["aktivierung"]),
        "type": e["type_name"]
    }
    for e in emo_raw
}

# -------------------------------------------------
# Hilfsfunktion: mittlere Valenz berechnen
# -------------------------------------------------
def mean_valence(emotion_string):
    if not emotion_string:
        return 0.0
    ids = [int(e.strip()) for e in emotion_string.split(",")]
    vals = [emotion_map[i]["valenz"] for i in ids if i in emotion_map]
    return np.mean(vals) if vals else 0.0

# -------------------------------------------------
# Daten extrahieren
# -------------------------------------------------
x = [d["x_kognition"] for d in sem_data]
y = [d["y_sozial"] for d in sem_data]
z = [d["z_affektiv"] for d in sem_data]

# semantische Dichte -> Punktgröße
sizes = [d["h_bedeutung"] * 140 for d in sem_data]

# emotionale Valenz -> Farbe
valences = [mean_valence(d.get("emotions", "")) for d in sem_data]

# -------------------------------------------------
# Plot
# -------------------------------------------------
fig = plt.figure(figsize=(11, 9))
ax = fig.add_subplot(111, projection="3d")

sc = ax.scatter(
    x, y, z,
    c=valences,
    s=sizes,
    cmap="RdYlGn",     # rot = negativ, grün = positiv
    alpha=0.75
)

# -------------------------------------------------
# Achsen & Layout
# -------------------------------------------------
ax.set_xlabel("kognitiv")
ax.set_ylabel("sozial")
ax.set_zlabel("affektiv")

ax.set_title(
    "FRZK – Semantische Dichte & Emotionen (Schülersicht)",
    fontsize=13
)

ax.set_xlim(0, 3)
ax.set_ylim(0, 3)
ax.set_zlim(0, 3)

# Farblegende
cbar = plt.colorbar(sc, shrink=0.6, pad=0.1)
cbar.set_label("emotionale Valenz (− negativ / + positiv)")

plt.tight_layout()
plt.show()
