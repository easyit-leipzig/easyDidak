import pandas as pd
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler

df = pd.read_csv("sql_semantische_dichte_lehrer_type_1_export.csv")

# DEBUG: Spalten anzeigen
print(df.columns)

# FALLBACK: Gruppe erzeugen, falls nicht vorhanden
if "gruppe" not in df.columns:
    df["gruppe"] = df["lehrkraft_id"].apply(
        lambda x: "LK_1" if x == 1 else "LK_other"
    )

dims = [
    "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
    "x_methodik", "x_performanz", "x_regulation"
]

X = df[dims].astype(float)

scaler = StandardScaler()
X_scaled = scaler.fit_transform(X)

kmeans = KMeans(n_clusters=3, random_state=42, n_init=50)
df["cluster"] = kmeans.fit_predict(X_scaled) + 1

# WICHTIG: nur numerische Spalten mitteln
cluster_profile = df.groupby(["gruppe", "cluster"])[dims + ["polaritaet_gesamt"]].mean()

cluster_counts = df.groupby(["gruppe", "cluster"]).size().reset_index(name="n")

print(cluster_counts)
print(cluster_profile)

df.to_csv("frzk_cluster_type_1.csv", index=False)
cluster_profile.to_csv("frzk_cluster_profile_type_1.csv")