# 01_deltaS_analyse_lk1_andere.py
# Analyse der Zustandsänderungen ΔS(t) = S(t+1) - S(t)
# für Lehrkraft 1 vs. andere aus sem_d_lehrer_vektorraum_lk1_andere.json.

import json
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd


INPUT_FILE = "sem_d_lehrer_vektorraum_lk1_andere.json"
OUTPUT_DIR = Path("plots_deltaS")

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
            "lehrkraft_id": r.get("lehrkraft_id"),
            "d_semantisch": r.get("d_semantisch", 0.0),
            "norm_x": r.get("norm_x", 0.0),
        }
        row.update(r["vector"])
        rows.append(row)

    df = pd.DataFrame(rows)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    return df.dropna(subset=["datum"]).sort_values(["gruppe", "datum"])


def daily_mean_states(df):
    return (
        df.groupby(["gruppe", pd.Grouper(key="datum", freq="D")])[DIMS]
        .mean()
        .reset_index()
        .dropna()
        .sort_values(["gruppe", "datum"])
    )


def compute_delta(df_daily):
    parts = []

    for gruppe, g in df_daily.groupby("gruppe"):
        g = g.sort_values("datum").copy()
        delta = g[DIMS].diff()
        delta.columns = [f"d_{c}" for c in DIMS]

        g_delta = pd.concat([g[["gruppe", "datum"]], delta], axis=1)
        g_delta["delta_norm"] = np.sqrt((delta ** 2).sum(axis=1))
        parts.append(g_delta.iloc[1:])

    return pd.concat(parts, ignore_index=True)


def plot_delta_norm(delta_df):
    plt.figure(figsize=(12, 6))

    for gruppe, label in [("lehrkraft_1", "Lehrkraft 1"), ("andere", "Andere")]:
        part = delta_df[delta_df["gruppe"] == gruppe]
        plt.plot(part["datum"], part["delta_norm"], marker="o", linewidth=1.8, label=label)

    plt.title("ΔS-Analyse: Stärke der Zustandsänderung")
    plt.xlabel("Datum")
    plt.ylabel("||ΔS(t)||")
    plt.grid(alpha=0.3)
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "01_deltaS_norm_zeitverlauf.png", dpi=300)
    plt.close()


def plot_delta_mean_bar(delta_df):
    means = delta_df.groupby("gruppe")["delta_norm"].mean()

    plt.figure(figsize=(7, 5))
    plt.bar(["Lehrkraft 1", "Andere"], [
        means.get("lehrkraft_1", 0),
        means.get("andere", 0),
    ])
    plt.title("Mittlere Zustandsänderung ||ΔS||")
    plt.ylabel("mittlere ΔS-Norm")
    plt.grid(axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "02_deltaS_mittelwert_vergleich.png", dpi=300)
    plt.close()


def main():
    OUTPUT_DIR.mkdir(exist_ok=True)

    df = load_df()
    daily = daily_mean_states(df)
    delta = compute_delta(daily)

    delta.to_csv(OUTPUT_DIR / "deltaS_lk1_andere.csv", index=False, encoding="utf-8-sig")

    plot_delta_norm(delta)
    plot_delta_mean_bar(delta)

    print("ΔS-Analyse abgeschlossen.")
    print("Ausgabeordner:", OUTPUT_DIR.resolve())


if __name__ == "__main__":
    main()
