import pandas as pd
import matplotlib.pyplot as plt
from sklearn.decomposition import PCA
from sklearn.preprocessing import StandardScaler

# -----------------------------
# CSV laden
# -----------------------------
df_points = pd.read_csv("frzk_cluster_type_1.csv")
df_centers = pd.read_csv("frzk_cluster_profile_type_1.csv")

# -----------------------------
# Feature-Spalten
# -----------------------------
features = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation"
]

# -----------------------------
# Standardisierung
# -----------------------------
scaler = StandardScaler()
X_scaled = scaler.fit_transform(df_points[features])

# -----------------------------
# PCA
# -----------------------------
pca = PCA(n_components=2)
X_pca = pca.fit_transform(X_scaled)

# Clusterzentren transformieren
centers_scaled = scaler.transform(df_centers[features])
centers_pca = pca.transform(centers_scaled)

# -----------------------------
# Plot
# -----------------------------
plt.figure(figsize=(10, 7))

# Farben definieren (stabil!)
colors = {
    1: "tab:blue",
    2: "tab:green",
    3: "tab:red"
}

labels = {
    1: "C1 – kognitiv-performativ",
    2: "C2 – sozial-affektiv",
    3: "C3 – negativ"
}

# Punkte clusterweise plotten
for cluster_id in sorted(df_points["cluster"].unique()):
    mask = df_points["cluster"] == cluster_id
    plt.scatter(
        X_pca[mask, 0],
        X_pca[mask, 1],
        color=colors[cluster_id],
        alpha=0.7,
        label=labels[cluster_id]
    )

# Zentren plotten
plt.scatter(
    centers_pca[:, 0],
    centers_pca[:, 1],
    color="black",
    marker='X',
    s=250,
    label="Clusterzentren"
)

# -----------------------------
# Layout
# -----------------------------
plt.title("FRZK Clusterstruktur (Type 1)")
plt.xlabel("Hauptkomponente 1")
plt.ylabel("Hauptkomponente 2")

plt.legend()
plt.grid(alpha=0.2)

plt.tight_layout()
plt.show()