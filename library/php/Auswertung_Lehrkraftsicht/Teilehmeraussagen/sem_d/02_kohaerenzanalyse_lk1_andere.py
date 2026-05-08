# 02_kohaerenzanalyse_lk1_andere.py
# Kohärenzmaß C = ||Summe(S_i)|| / Summe(||S_i||)
# für Lehrkraft 1 vs. andere.
#
# Interpretation:
# C nahe 1  -> hohe Richtungsbündelung / kohärenter Vektorraum
# C nahe 0  -> starke Streuung / widersprüchlicher Vektorraum

import json
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd


INPUT_FILE = "sem_d_lehrer_vektorraum_lk1_andere.json"
OUTPUT_DIR = Path("plots_kohaerenz")

DIMS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def load_df():
    data = json.loads(Path(INPUT_FILE).read_text(encoding="utf-8"))
    rows = []

    for r in data["records"]:
        row = {
            "gruppe": r["gruppe"],
            "datum": r.get("datum"),
        }
        row.update(r["vector"])
        rows.append(row)

    df = pd.DataFrame(rows)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    return df.dropna(subset=["datum"])


def coherence(matrix):
    if len(matrix) == 0:
        return 0.0

    sum_vec = matrix.sum(axis=0)
    numerator = np.linalg.norm(sum_vec)
    denominator = np.linalg.norm(matrix, axis=1).sum()

    if denominator == 0:
        return 0.0

    return numerator / denominator


def group_coherence(df):
    rows = []

    for gruppe, g in df.groupby("gruppe"):
        X = g[DIMS].to_numpy(dtype=float)
        rows.append({
            "gruppe": gruppe,
            "n": len(g),
            "coherence_C": coherence(X),
        })

    return pd.DataFrame(rows)


def rolling_coherence(df, window=30):
    out = []

    for gruppe, g in df.sort_values("datum").groupby("gruppe"):
        g = g.sort_values("datum").reset_index(drop=True)

        for i in range(len(g)):
            start = max(0, i - window + 1)
            part = g.iloc[start:i + 1]
            X = part[DIMS].to_numpy(dtype=float)

            out.append({
                "gruppe": gruppe,
                "datum": g.loc[i, "datum"],
                "coherence_C": coherence(X),
                "window_n": len(part),
            })

    return pd.DataFrame(out)


def plot_group_coherence(summary):
    labels = ["Lehrkraft 1", "Andere"]
    values = [
        float(summary.loc[summary["gruppe"] == "lehrkraft_1", "coherence_C"].iloc[0])
        if (summary["gruppe"] == "lehrkraft_1").any() else 0,
        float(summary.loc[summary["gruppe"] == "andere", "coherence_C"].iloc[0])
        if (summary["gruppe"] == "andere").any() else 0,
    ]

    plt.figure(figsize=(7, 5))
    plt.bar(labels, values)
    plt.ylim(0, 1)
    plt.title("FRZK-Kohärenzmaß C")
    plt.ylabel("C = ||ΣSᵢ|| / Σ||Sᵢ||")
    plt.grid(axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "01_kohaerenz_C_vergleich.png", dpi=300)
    plt.close()


def plot_rolling(roll):
    plt.figure(figsize=(12, 6))

    for gruppe, label in [("lehrkraft_1", "Lehrkraft 1"), ("andere", "Andere")]:
        part = roll[roll["gruppe"] == gruppe]
        plt.plot(part["datum"], part["coherence_C"], linewidth=1.8, label=label)

    plt.title("Gleitende FRZK-Kohärenz")
    plt.xlabel("Datum")
    plt.ylabel("C")
    plt.ylim(0, 1)
    plt.grid(alpha=0.3)
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "02_kohaerenz_C_zeitverlauf.png", dpi=300)
    plt.close()


def main():
    OUTPUT_DIR.mkdir(exist_ok=True)

    df = load_df()
    summary = group_coherence(df)
    roll = rolling_coherence(df, window=30)

    summary.to_csv(OUTPUT_DIR / "kohaerenz_summary.csv", index=False, encoding="utf-8-sig")
    roll.to_csv(OUTPUT_DIR / "kohaerenz_rolling.csv", index=False, encoding="utf-8-sig")

    plot_group_coherence(summary)
    plot_rolling(roll)

    print("Kohärenzanalyse abgeschlossen.")
    print(summary)
    print("Ausgabeordner:", OUTPUT_DIR.resolve())


if __name__ == "__main__":
    main()
