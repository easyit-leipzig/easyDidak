# 03_varianz_stabilitaet_lk1_andere.py
# Varianz- und Stabilitätsanalyse für Lehrkraft 1 vs. andere.
#
# Stabilität wird hier operationalisiert als:
# Stabilität = 1 / (1 + mittlere Varianz der 7 Dimensionen)
#
# Hohe Varianz  -> heterogener / instabilerer Bedeutungsraum
# Niedrige Varianz -> stabilerer Bedeutungsraum

import json
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd


INPUT_FILE = "sem_d_lehrer_vektorraum_lk1_andere.json"
OUTPUT_DIR = Path("plots_varianz_stabilitaet")

DIMS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

LABELS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
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
    return df


def variance_summary(df):
    rows = []

    for gruppe, g in df.groupby("gruppe"):
        variances = g[DIMS].var(ddof=1)
        mean_var = variances.mean()
        stability = 1 / (1 + mean_var)

        rows.append({
            "gruppe": gruppe,
            "n": len(g),
            "mean_variance": mean_var,
            "stability_index": stability,
            **{f"var_{d}": variances[d] for d in DIMS},
        })

    return pd.DataFrame(rows)


def plot_variance_by_dimension(summary):
    plot_df = summary.set_index("gruppe")[[f"var_{d}" for d in DIMS]].T
    plot_df.index = LABELS

    ax = plot_df.plot(kind="bar", figsize=(12, 6))
    ax.set_title("Varianz der FRZK-Dimensionen")
    ax.set_xlabel("Dimension")
    ax.set_ylabel("Varianz")
    ax.grid(axis="y", alpha=0.3)
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "01_varianz_dimensionen.png", dpi=300)
    plt.close()


def plot_stability(summary):
    labels = []
    vals = []

    for gruppe, label in [("lehrkraft_1", "Lehrkraft 1"), ("andere", "Andere")]:
        if (summary["gruppe"] == gruppe).any():
            labels.append(label)
            vals.append(float(summary.loc[summary["gruppe"] == gruppe, "stability_index"].iloc[0]))

    plt.figure(figsize=(7, 5))
    plt.bar(labels, vals)
    plt.ylim(0, 1)
    plt.title("Stabilitätsindex der Zustandsräume")
    plt.ylabel("1 / (1 + mittlere Varianz)")
    plt.grid(axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "02_stabilitaetsindex.png", dpi=300)
    plt.close()


def main():
    OUTPUT_DIR.mkdir(exist_ok=True)

    df = load_df()
    summary = variance_summary(df)

    summary.to_csv(OUTPUT_DIR / "varianz_stabilitaet_summary.csv", index=False, encoding="utf-8-sig")

    plot_variance_by_dimension(summary)
    plot_stability(summary)

    print("Varianz-/Stabilitätsanalyse abgeschlossen.")
    print(summary)
    print("Ausgabeordner:", OUTPUT_DIR.resolve())


if __name__ == "__main__":
    main()
