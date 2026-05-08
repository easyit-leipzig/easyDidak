# 04_trajectory_systempfade_lk1_andere.py
# Trajectory-/Systempfad-Plot im reduzierten FRZK-Zustandsraum.
#
# Projektion:
# X = Kognition + Methodik + Performanz
# Y = Sozial + Affektiv + Motivation + Regulation
#
# Auf Tagesmittel aggregiert, damit zeitliche Bewegungen sichtbar werden.

import json
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd


INPUT_FILE = "sem_d_lehrer_vektorraum_lk1_andere.json"
OUTPUT_DIR = Path("plots_trajectory")

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


def project(df):
    out = df.copy()
    out["X_kmp"] = out["x_kognition"] + out["x_methodik"] + out["x_performanz"]
    out["Y_samr"] = out["x_sozial"] + out["x_affektiv"] + out["x_motivation"] + out["x_regulation"]
    return out


def daily_projected(df):
    df = project(df)

    daily = (
        df.groupby(["gruppe", pd.Grouper(key="datum", freq="D")])[["X_kmp", "Y_samr"]]
        .mean()
        .reset_index()
        .dropna()
        .sort_values(["gruppe", "datum"])
    )

    return daily


def plot_trajectory(daily):
    plt.figure(figsize=(9, 7))

    for gruppe, marker, label in [
        ("lehrkraft_1", "o", "Lehrkraft 1"),
        ("andere", "x", "Andere"),
    ]:
        part = daily[daily["gruppe"] == gruppe].sort_values("datum")

        plt.plot(part["X_kmp"], part["Y_samr"], marker=marker, linewidth=1.8, label=label)

        if len(part) > 0:
            plt.scatter(part["X_kmp"].iloc[0], part["Y_samr"].iloc[0], s=90, marker="s")
            plt.scatter(part["X_kmp"].iloc[-1], part["Y_samr"].iloc[-1], s=120, marker="*")

    plt.title("FRZK-Systempfade: Lehrkraft 1 vs. andere")
    plt.xlabel("Kognition + Methodik + Performanz")
    plt.ylabel("Sozial + Affektiv + Motivation + Regulation")
    plt.grid(alpha=0.3)
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "01_trajectory_systempfade.png", dpi=300)
    plt.close()


def plot_scatter_density(daily):
    plt.figure(figsize=(9, 7))

    for gruppe, marker, label in [
        ("lehrkraft_1", "o", "Lehrkraft 1"),
        ("andere", "x", "Andere"),
    ]:
        part = daily[daily["gruppe"] == gruppe].sort_values("datum")
        plt.scatter(part["X_kmp"], part["Y_samr"], marker=marker, alpha=0.75, label=label)

    plt.title("FRZK-Zustandsraum: Tagesmittelpunkte")
    plt.xlabel("Kognition + Methodik + Performanz")
    plt.ylabel("Sozial + Affektiv + Motivation + Regulation")
    plt.grid(alpha=0.3)
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "02_zustandsraum_tagesmittelpunkte.png", dpi=300)
    plt.close()


def main():
    OUTPUT_DIR.mkdir(exist_ok=True)

    df = load_df()
    daily = daily_projected(df)

    daily.to_csv(OUTPUT_DIR / "trajectory_daily_projected.csv", index=False, encoding="utf-8-sig")

    plot_trajectory(daily)
    plot_scatter_density(daily)

    print("Trajectory-/Systempfad-Analyse abgeschlossen.")
    print("Ausgabeordner:", OUTPUT_DIR.resolve())


if __name__ == "__main__":
    main()
