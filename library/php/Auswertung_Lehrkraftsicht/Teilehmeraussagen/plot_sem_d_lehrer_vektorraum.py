# plot_sem_d_lehrer_vektorraum.py
# Erstellt Grafiken aus sem_d_lehrer_vektorraum_lk1_andere.json.

import json
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from sklearn.decomposition import PCA


INPUT_FILE = "sem_d_lehrer_vektorraum_lk1_andere.json"
OUTPUT_DIR = Path("plots_sem_d_lehrer_vektorraum")

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def load_dataframe():
    data = json.loads(Path(INPUT_FILE).read_text(encoding="utf-8"))
    rows = []

    for r in data["records"]:
        row = {
            "gruppe": r["gruppe"],
            "datum": r.get("datum"),
            "lehrkraft_id": r.get("lehrkraft_id"),
            "d_semantisch": r.get("d_semantisch", 0.0),
            "norm_x": r.get("norm_x", 0.0),
            "dominante_dimension": r.get("dominante_dimension"),
            "polaritaet_gesamt": r.get("polaritaet_gesamt"),
        }
        row.update(r["vector"])
        rows.append(row)

    df = pd.DataFrame(rows)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    return df


def plot_mean_vectors(df):
    means = df.groupby("gruppe")[DIMENSIONS].mean().T

    ax = means.plot(kind="bar", figsize=(12, 6))
    ax.set_title("FRZK-Vektorenraum: mittlere Dimensionen Lehrkraft 1 vs. andere")
    ax.set_xlabel("FRZK-Dimension")
    ax.set_ylabel("mittlerer x-Wert")
    ax.grid(axis="y", alpha=0.3)
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "01_mittelwerte_dimensionen_lk1_andere.png", dpi=300)
    plt.close()


def plot_density_boxplot(df):
    groups = ["lehrkraft_1", "andere"]
    values = [df.loc[df["gruppe"] == g, "d_semantisch"].dropna().to_numpy() for g in groups]

    plt.figure(figsize=(8, 6))
    plt.boxplot(values, labels=["Lehrkraft 1", "Andere"])
    plt.title("Semantische Dichte: Lehrkraft 1 vs. andere")
    plt.ylabel("d_semantisch")
    plt.grid(axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "02_boxplot_semantische_dichte.png", dpi=300)
    plt.close()


def plot_pca_space(df):
    clean = df.dropna(subset=DIMENSIONS).copy()

    if len(clean) < 3:
        print("Zu wenige Datensätze für PCA.")
        return

    X = clean[DIMENSIONS].to_numpy(dtype=float)
    pca = PCA(n_components=2)
    coords = pca.fit_transform(X)

    clean["PC1"] = coords[:, 0]
    clean["PC2"] = coords[:, 1]

    plt.figure(figsize=(9, 7))

    for group, marker, label in [
        ("lehrkraft_1", "o", "Lehrkraft 1"),
        ("andere", "x", "Andere"),
    ]:
        part = clean[clean["gruppe"] == group]
        plt.scatter(part["PC1"], part["PC2"], marker=marker, alpha=0.7, label=label)

    plt.title("2D-Projektion des 7D-FRZK-Vektorenraums (PCA)")
    plt.xlabel(f"PC1 ({pca.explained_variance_ratio_[0] * 100:.1f}% Varianz)")
    plt.ylabel(f"PC2 ({pca.explained_variance_ratio_[1] * 100:.1f}% Varianz)")
    plt.legend()
    plt.grid(alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "03_pca_vektorenraum_lk1_andere.png", dpi=300)
    plt.close()


def plot_temporal_density(df):
    tmp = df.dropna(subset=["datum"]).copy()

    if tmp.empty:
        print("Keine Datumswerte für Zeitverlauf vorhanden.")
        return

    daily = (
        tmp.groupby(["gruppe", pd.Grouper(key="datum", freq="D")])["d_semantisch"]
        .mean()
        .reset_index()
        .sort_values("datum")
    )

    plt.figure(figsize=(12, 6))

    for group, label in [
        ("lehrkraft_1", "Lehrkraft 1"),
        ("andere", "Andere"),
    ]:
        part = daily[daily["gruppe"] == group]
        plt.plot(part["datum"], part["d_semantisch"], marker="o", linewidth=1.5, label=label)

    plt.title("Zeitlicher Verlauf der semantischen Dichte")
    plt.xlabel("Datum")
    plt.ylabel("mittlere d_semantisch")
    plt.legend()
    plt.grid(alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "04_zeitverlauf_semantische_dichte.png", dpi=300)
    plt.close()


def plot_dominant_dimensions(df):
    counts = (
        df.groupby(["gruppe", "dominante_dimension"])
        .size()
        .unstack(fill_value=0)
        .T
    )

    ax = counts.plot(kind="bar", figsize=(12, 6))
    ax.set_title("Dominante Dimensionen: Häufigkeit nach Gruppe")
    ax.set_xlabel("dominante Dimension")
    ax.set_ylabel("Anzahl")
    ax.grid(axis="y", alpha=0.3)
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "05_dominante_dimensionen_counts.png", dpi=300)
    plt.close()


def write_summary(df):
    summary = df.groupby("gruppe")[DIMENSIONS + ["d_semantisch", "norm_x"]].agg(["count", "mean", "std"])
    summary.to_csv(OUTPUT_DIR / "summary_lk1_andere.csv", encoding="utf-8-sig")


def main():
    OUTPUT_DIR.mkdir(exist_ok=True)
    df = load_dataframe()

    plot_mean_vectors(df)
    plot_density_boxplot(df)
    plot_pca_space(df)
    plot_temporal_density(df)
    plot_dominant_dimensions(df)
    write_summary(df)

    print(f"Grafiken gespeichert in: {OUTPUT_DIR.resolve()}")
    print("Erzeugte Kernplots:")
    print("01_mittelwerte_dimensionen_lk1_andere.png")
    print("02_boxplot_semantische_dichte.png")
    print("03_pca_vektorenraum_lk1_andere.png")
    print("04_zeitverlauf_semantische_dichte.png")
    print("05_dominante_dimensionen_counts.png")
    print("summary_lk1_andere.csv")


if __name__ == "__main__":
    main()
