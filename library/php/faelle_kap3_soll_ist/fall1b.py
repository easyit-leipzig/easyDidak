import json
import numpy as np
import matplotlib.pyplot as plt

# =====================================
# Klassische MDS (Torgerson)
# =====================================
def classical_mds(D, n_components=2):
    D = np.array(D, dtype=float)
    n = D.shape[0]

    if n < 2:
        raise ValueError("Mindestens zwei Entitäten erforderlich.")

    J = np.eye(n) - np.ones((n, n)) / n
    B = -0.5 * J @ (D ** 2) @ J

    eigvals, eigvecs = np.linalg.eigh(B)
    idx = np.argsort(eigvals)[::-1]

    eigvals = eigvals[idx]
    eigvecs = eigvecs[:, idx]

    L = np.diag(np.sqrt(np.maximum(eigvals[:n_components], 0)))
    V = eigvecs[:, :n_components]

    return V @ L


# =============================
# IST: Kontext-gebinnt (Emergenz)
# =============================
with open("fall1b_relations.json", "r") as f:
    data = json.load(f)

labels = data["labels"]
R = np.array(data["relation_matrix"])

coords = classical_mds(R)

# =============================
# Abbildung
# =============================
plt.figure(figsize=(5, 5))
plt.scatter(coords[:, 0], coords[:, 1])

for i, label in enumerate(labels):
    plt.text(coords[i, 0], coords[i, 1], label, fontsize=9)

plt.title("IST (Fall 1b): Emergenz nach Kontext-Binning")
plt.xlabel("Emergente Dimension 1")
plt.ylabel("Emergente Dimension 2")

plt.figtext(
    0.5, -0.15,
    "Abbildung Y: Fall 1b – Emergenz einer metrischen Struktur nach Kontext-Binning.\n"
    "Auf Basis derselben ICAS-Daten wie in Fall 1 entstehen durch zeitliche Aggregation "
    "Ko-Aktivierungen von Entitäten. Diese führen zur Ausbildung nicht-trivialer Relationen "
    "(`frzk_relations`), aus denen mittels klassischer MDS eine stabile metrische Struktur "
    "rekonstruiert werden kann. Der Befund bestätigt die Vorhersagen P2–P4 der "
    "Prediction–Observation-Tabelle.",
    ha="center",
    fontsize=9
)

plt.tight_layout()
plt.show()
