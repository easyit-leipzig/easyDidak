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

    # ΔS(t) gegen ΔP(t+1)
    if len(delta_s) > 3:
        x = delta_s[:-1]
        y = delta_p[1:]

        r, p = spearmanr(x, y)

        print(key, "lagged spearman:", r, p)
        
out = pd.DataFrame(results)
print(out)
out.to_csv("deltaS_vs_performanz.csv", index=False)