import pandas as pd
import numpy as np
from scipy.stats import pearsonr, spearmanr

df = pd.read_csv("auswertung_datensatz.csv")

dims = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]

df["datum"] = pd.to_datetime(df["datum"])
df = df.sort_values(["gruppe", "lehrkraft_id", "datum", "id"])

results = []

for key, g in df.groupby(["gruppe", "lehrkraft_id"]):
    g = g.sort_values("datum").copy()

    vectors = g[dims].to_numpy()

    delta_s = np.linalg.norm(np.diff(vectors, axis=0), axis=1)
    delta_p = np.diff(g["performanz"].to_numpy())

    if len(delta_s) > 2:
        r_p, p_p = pearsonr(delta_s, delta_p)
        r_s, p_s = spearmanr(delta_s, delta_p)

        results.append({
            "gruppe": key[0],
            "lehrkraft_id": key[1],
            "n_transitions": len(delta_s),
            "mean_delta_S": delta_s.mean(),
            "mean_delta_performanz": delta_p.mean(),
            "pearson_r": r_p,
            "pearson_p": p_p,
            "spearman_r": r_s,
            "spearman_p": p_s
        })

out = pd.DataFrame(results)
print(out)
out.to_csv("deltaS_vs_performanz.csv", index=False)