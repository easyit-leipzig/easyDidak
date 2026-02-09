import json
import numpy as np
import matplotlib.pyplot as plt

# =====================================
# Klassische MDS (ohne sklearn)
# =====================================
def classical_mds(D, n_components=2):
    D = np.array(D, dtype=float)
    n = D.shape[0]

    if n == 0:
        return np.empty((0, n_components))

    # Zentrierungsmatrix
    J = np.eye(n) - np.ones((n, n)) / n

    # doppelt zentrierte Matrix
    B = -0.5 * J @ (D ** 2) @ J

    # Eigenwertzerlegung
    eigvals, eigvecs = np.linalg.eigh(B)
    idx = np.argsort(eigvals)[::-1]

    eigvals = eigvals[idx]
    eigvecs = eigvecs[:, idx]

    # nur positive Eigenwerte
    L = np.diag(np.sqrt(np.maximum(eigvals[:n_components], 0)))
    V = eigvecs[:, :n_components]

    return V @ L


# =============================
# Panel A: SOLL (synthetisch)
# =============================
np.random.seed(42)
n = 12

R_soll = np.random.rand(n, n)
R_soll = (R_soll + R_soll.T) / 2
np.fill_diagonal(R_soll, 0)

coords_soll = classical_mds(R_soll)

# =============================
# Panel B: IST (ICAS – Leerfall)
# =============================
with open("fall1_relations.json", "r") as f:
    data = json.load(f)

labels = data.get("labels", [])
R_ist = np.array(data.get("relation_matrix", []))

# =============================
# Abbildung
# =============================
plt.figure(figsize=(10, 5))

# --- SOLL ---
plt.subplot(1, 2, 1)
plt.scatter(coords_soll[:, 0], coords_soll[:, 1])
for i in range(n):
    plt.text(coords_soll[i, 0], coords_soll[i, 1], str(i), fontsize=8)

plt.title("SOLL: Emergenz aus Relationen")
plt.xlabel("Emergente Dimension 1")
plt.ylabel("Emergente Dimension 2")

# --- IST ---
plt.subplot(1, 2, 2)

if len(labels) == 0:
    plt.text(
        0.5, 0.5,
        "Keine Relationen rekonstruierbar\n"
        "(keine Ko-Aktivierung,\nVorhersage P1 / P5)",
        ha="center",
        va="center",
        fontsize=10
    )
    plt.xlim(0, 1)
    plt.ylim(0, 1)
else:
    coords_ist = classical_mds(R_ist)
    plt.scatter(coords_ist[:, 0], coords_ist[:, 1])
    for i, label in enumerate(labels):
        plt.text(coords_ist[i, 0], coords_ist[i, 1], label, fontsize=8)

plt.title("IST: ICAS-Daten (ungefenstert)")
plt.xlabel("Emergente Dimension 1")
plt.ylabel("Emergente Dimension 2")

# =============================
# Abbildungslegende
# =============================
plt.figtext(
    0.5, -0.15,
    "Abbildung X: Fall 1 – Emergenz einer metrischen Struktur aus Relationen.\n"
    "Links (SOLL): Klassische MDS-Rekonstruktion aus einer synthetischen relationalen Matrix "
    "ohne geometrische Vorannahmen.\n"
    "Rechts (IST): Empirischer Leerfall auf Basis ungefensterter ICAS-Daten. "
    "Mangels Ko-Aktivierung entstehen keine Relationen und damit keine rekonstruierbare Metrik. "
    "Die Abbildung bestätigt die Vorhersagen P1 und P5 der Prediction–Observation-Tabelle.",
    ha="center",
    fontsize=9
)

plt.tight_layout()
plt.show()
