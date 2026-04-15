# ============================================================
# visualize_clusters_frzk.py
# Visualisiert Cluster + Kohärenz
# ============================================================

import json
import numpy as np
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D

INPUT_FILE = "frzk_cluster_output.json"

# -------------------------------
# DATEN LADEN
# -------------------------------
with open(INPUT_FILE, "r", encoding="utf-8") as f:
    data = json.load(f)

points = data["data"]
centers = data["cluster_centers"]

# -------------------------------
# ARRAY BUILD
# -------------------------------
X = np.array([p["vector"] for p in points])
labels = np.array([p["cluster"] for p in points])

# -------------------------------
# DIMENSIONEN AUSWÄHLEN (3D)
# -------------------------------
# kognition, sozial, affektiv
dims = [0, 1, 2]

# -------------------------------
# PLOT
# -------------------------------
fig = plt.figure()
ax = fig.add_subplot(111, projection='3d')

scatter = ax.scatter(
    X[:, dims[0]],
    X[:, dims[1]],
    X[:, dims[2]],
    c=labels
)

# Clusterzentren
for c in centers:
    center = np.array(c["center"])
    ax.scatter(
        center[dims[0]],
        center[dims[1]],
        center[dims[2]],
        s=200,
        marker='x'
    )

ax.set_xlabel("Kognition")
ax.set_ylabel("Sozial")
ax.set_zlabel("Affektiv")

plt.title("FRZK Clusterstruktur")
plt.show()


# -------------------------------
# KOHÄRENZVERLAUF
# -------------------------------
# Coh = exp(-||x - mu||^2)
import math

coh_values = []

for p in points:
    vec = np.array(p["vector"])
    center = np.array(centers[p["cluster"]]["center"])
    dist = np.linalg.norm(vec - center)
    coh = math.exp(-dist**2)
    coh_values.append(coh)

plt.figure()
plt.plot(coh_values)
plt.title("FRZK Kohärenzverlauf")
plt.xlabel("Index")
plt.ylabel("Coh(S)")
plt.show()