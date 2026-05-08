#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
analyse_lk1_vs_other.py

Liest frzk_lk1_vs_other_export.json und erstellt:
- deskriptive Kennzahlen
- Mittelwerte je Dimension
- Dominanzverteilung
- Polaritätsverteilung
- semantische Dichte nach Gruppe
- interne Cosine Similarity je Gruppe
- Distanz zwischen Gruppenzentroiden
- Visualisierungen als PNG
- Ergebnisbericht als JSON

Voraussetzungen:
    pip install pandas numpy matplotlib scikit-learn scipy
"""

import json
import math
from pathlib import Path

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

from sklearn.metrics.pairwise import cosine_similarity
from sklearn.decomposition import PCA
from scipy.stats import mannwhitneyu, ttest_ind, chi2_contingency


# ============================================================
# KONFIGURATION
# ============================================================

INPUT_FILE = Path("frzk_lk1_vs_other_export.json")
OUTPUT_DIR = Path("frzk_lk1_vs_other_auswertung")
OUTPUT_DIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

VECTOR_MODE = "sum"  # "sum" für Intensität; "x" für normierte Richtung


# ============================================================
# DATEN LADEN
# ============================================================

def load_data():
    data = json.loads(INPUT_FILE.read_text(encoding="utf-8"))
    rows = []

    for r in data["records"]:
        row = {
            "gruppe": r["gruppe"],
            "datum": r.get("datum"),
            "lehrkraft_id": r.get("lehrkraft_id"),
            "id": r.get("id"),
            "mtr_rueckkopplung_datenmaske_values_id": r.get("mtr_rueckkopplung_datenmaske_values_id"),
            "token_anzahl": r.get("token_anzahl"),
            "funktionsklassen_anzahl_gesamt": r.get("funktionsklassen_anzahl_gesamt"),
            "dominante_dimension": r.get("dominante_dimension"),
            "dominante_dimension_wert": r.get("dominante_dimension_wert"),
            "polaritaet_gesamt": r.get("polaritaet_gesamt"),
            "d_semantisch": r.get("d_semantisch"),
        }

        source = r[VECTOR_MODE]
        for d in DIMENSIONS:
            row[d] = source.get(d)

        rows.append(row)

    df = pd.DataFrame(rows)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")

    for col in DIMENSIONS + ["d_semantisch", "dominante_dimension_wert", "token_anzahl", "funktionsklassen_anzahl_gesamt"]:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df = df.dropna(subset=DIMENSIONS)
    return df


# ============================================================
# STATISTIK
# ============================================================

def cohens_d(a, b):
    a = np.asarray(a, dtype=float)
    b = np.asarray(b, dtype=float)
    a = a[~np.isnan(a)]
    b = b[~np.isnan(b)]

    if len(a) < 2 or len(b) < 2:
        return None

    pooled = math.sqrt(((len(a) - 1) * np.var(a, ddof=1) + (len(b) - 1) * np.var(b, ddof=1)) / (len(a) + len(b) - 2))
    if pooled == 0:
        return 0.0
    return float((np.mean(a) - np.mean(b)) / pooled)


def descriptive_by_group(df):
    metrics = DIMENSIONS + ["d_semantisch", "dominante_dimension_wert", "token_anzahl", "funktionsklassen_anzahl_gesamt"]
    desc = {}

    for group, gdf in df.groupby("gruppe"):
        desc[group] = {
            "n": int(len(gdf)),
            "means": gdf[metrics].mean(numeric_only=True).to_dict(),
            "std": gdf[metrics].std(numeric_only=True).to_dict(),
            "median": gdf[metrics].median(numeric_only=True).to_dict(),
        }

    return desc


def group_tests(df):
    tests = {}
    df1 = df[df["gruppe"] == "LK_1"]
    df2 = df[df["gruppe"] == "LK_other"]

    for col in DIMENSIONS + ["d_semantisch", "dominante_dimension_wert", "token_anzahl", "funktionsklassen_anzahl_gesamt"]:
        a = df1[col].dropna()
        b = df2[col].dropna()

        result = {
            "mean_LK_1": float(a.mean()) if len(a) else None,
            "mean_LK_other": float(b.mean()) if len(b) else None,
            "difference_LK_1_minus_other": float(a.mean() - b.mean()) if len(a) and len(b) else None,
            "cohens_d": cohens_d(a, b),
        }

        if len(a) > 1 and len(b) > 1:
            try:
                result["t_test_p"] = float(ttest_ind(a, b, equal_var=False, nan_policy="omit").pvalue)
            except Exception:
                result["t_test_p"] = None
            try:
                result["mann_whitney_p"] = float(mannwhitneyu(a, b, alternative="two-sided").pvalue)
            except Exception:
                result["mann_whitney_p"] = None

        tests[col] = result

    return tests


def categorical_tests(df):
    result = {}

    for col in ["dominante_dimension", "polaritaet_gesamt"]:
        table = pd.crosstab(df["gruppe"], df[col])
        result[col] = {
            "table": table.to_dict(),
        }

        if table.shape[0] >= 2 and table.shape[1] >= 2:
            chi2, p, dof, expected = chi2_contingency(table)
            result[col]["chi2"] = float(chi2)
            result[col]["p"] = float(p)
            result[col]["dof"] = int(dof)

    return result


def cosine_analysis(df):
    vectors_1 = df[df["gruppe"] == "LK_1"][DIMENSIONS].to_numpy(dtype=float)
    vectors_o = df[df["gruppe"] == "LK_other"][DIMENSIONS].to_numpy(dtype=float)

    result = {}

    def mean_pairwise_cosine(vectors):
        if len(vectors) < 2:
            return None
        sim = cosine_similarity(vectors)
        mask = ~np.eye(sim.shape[0], dtype=bool)
        return float(sim[mask].mean())

    result["mean_internal_similarity_LK_1"] = mean_pairwise_cosine(vectors_1)
    result["mean_internal_similarity_LK_other"] = mean_pairwise_cosine(vectors_o)

    if len(vectors_1) and len(vectors_o):
        between = cosine_similarity(vectors_1, vectors_o)
        result["mean_between_similarity"] = float(between.mean())

        centroid_1 = vectors_1.mean(axis=0)
        centroid_o = vectors_o.mean(axis=0)
        result["centroid_LK_1"] = dict(zip(DIMENSIONS, centroid_1.tolist()))
        result["centroid_LK_other"] = dict(zip(DIMENSIONS, centroid_o.tolist()))
        result["centroid_difference_LK_1_minus_other"] = dict(zip(DIMENSIONS, (centroid_1 - centroid_o).tolist()))
        result["centroid_cosine_similarity"] = float(cosine_similarity([centroid_1], [centroid_o])[0, 0])
        result["centroid_euclidean_distance"] = float(np.linalg.norm(centroid_1 - centroid_o))

    return result


# ============================================================
# VISUALISIERUNG
# ============================================================

def plot_dimension_means(df):
    means = df.groupby("gruppe")[DIMENSIONS].mean().T
    ax = means.plot(kind="bar", figsize=(12, 6))
    ax.set_title("FRZK-Dimensionsmittelwerte: LK_1 vs. LK_other")
    ax.set_xlabel("Dimension")
    ax.set_ylabel(f"Mittelwert ({VECTOR_MODE}-Vektor)")
    ax.legend(title="Gruppe")
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "01_dimensionsmittelwerte_lk1_vs_other.png", dpi=300)
    plt.close()


def plot_density_boxplot(df):
    data = [
        df[df["gruppe"] == "LK_1"]["d_semantisch"].dropna(),
        df[df["gruppe"] == "LK_other"]["d_semantisch"].dropna(),
    ]

    plt.figure(figsize=(8, 6))
    plt.boxplot(data, labels=["LK_1", "LK_other"], showmeans=True)
    plt.title("Semantische Dichte d_semantisch: LK_1 vs. LK_other")
    plt.ylabel("d_semantisch")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "02_semantische_dichte_boxplot.png", dpi=300)
    plt.close()


def plot_dominance_distribution(df):
    table = pd.crosstab(df["dominante_dimension"], df["gruppe"], normalize="columns") * 100
    ax = table.plot(kind="bar", figsize=(12, 6))
    ax.set_title("Dominante Dimensionen: prozentuale Verteilung")
    ax.set_xlabel("Dominante Dimension")
    ax.set_ylabel("Anteil in %")
    ax.legend(title="Gruppe")
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "03_dominante_dimension_verteilung.png", dpi=300)
    plt.close()


def plot_polarity_distribution(df):
    table = pd.crosstab(df["polaritaet_gesamt"], df["gruppe"], normalize="columns") * 100
    ax = table.plot(kind="bar", figsize=(8, 6))
    ax.set_title("Polaritätsverteilung: LK_1 vs. LK_other")
    ax.set_xlabel("Polarität")
    ax.set_ylabel("Anteil in %")
    ax.legend(title="Gruppe")
    plt.xticks(rotation=0)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "04_polaritaet_verteilung.png", dpi=300)
    plt.close()


def plot_pca(df):
    X = df[DIMENSIONS].to_numpy(dtype=float)

    if len(df) < 3:
        return

    pca = PCA(n_components=2)
    coords = pca.fit_transform(X)

    plot_df = pd.DataFrame({
        "PC1": coords[:, 0],
        "PC2": coords[:, 1],
        "gruppe": df["gruppe"].values,
    })

    plt.figure(figsize=(9, 7))
    for group, gdf in plot_df.groupby("gruppe"):
        plt.scatter(gdf["PC1"], gdf["PC2"], label=group, alpha=0.65)

    plt.title("PCA des FRZK-Vektorraums: LK_1 vs. LK_other")
    plt.xlabel(f"PC1 ({pca.explained_variance_ratio_[0] * 100:.1f}% Varianz)")
    plt.ylabel(f"PC2 ({pca.explained_variance_ratio_[1] * 100:.1f}% Varianz)")
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "05_pca_vektorraum_lk1_vs_other.png", dpi=300)
    plt.close()


def plot_similarity_heatmap(df):
    sample = df.copy()

    # Begrenzung für Lesbarkeit
    max_per_group = 120
    sample = pd.concat([
        sample[sample["gruppe"] == "LK_1"].head(max_per_group),
        sample[sample["gruppe"] == "LK_other"].head(max_per_group),
    ])

    if len(sample) < 2:
        return

    X = sample[DIMENSIONS].to_numpy(dtype=float)
    sim = cosine_similarity(X)

    plt.figure(figsize=(8, 7))
    plt.imshow(sim, aspect="auto")
    plt.colorbar(label="Cosine Similarity")
    plt.title("Cosine-Similarity-Matrix der FRZK-Vektoren")
    plt.xlabel("Datensätze")
    plt.ylabel("Datensätze")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "06_cosine_similarity_matrix.png", dpi=300)
    plt.close()


def create_plots(df):
    plot_dimension_means(df)
    plot_density_boxplot(df)
    plot_dominance_distribution(df)
    plot_polarity_distribution(df)
    plot_pca(df)
    plot_similarity_heatmap(df)


# ============================================================
# BERICHT
# ============================================================

def make_markdown_report(results):
    lines = []
    lines.append("# FRZK-Auswertung: LK_1 vs. LK_other\n")
    lines.append(f"Vektormodus: `{VECTOR_MODE}`\n")

    desc = results["descriptive"]
    for group in ["LK_1", "LK_other"]:
        if group in desc:
            lines.append(f"## {group}")
            lines.append(f"n = {desc[group]['n']}\n")
            lines.append("### Mittelwerte")
            for k, v in desc[group]["means"].items():
                lines.append(f"- {k}: {v:.6f}" if isinstance(v, float) else f"- {k}: {v}")
            lines.append("")

    lines.append("## Gruppendifferenzen")
    for k, v in results["group_tests"].items():
        diff = v.get("difference_LK_1_minus_other")
        d = v.get("cohens_d")
        p = v.get("mann_whitney_p")
        lines.append(
            f"- {k}: Δ={diff:.6f} | Cohen's d={d:.4f} | Mann-Whitney-p={p:.6g}"
            if diff is not None and d is not None and p is not None
            else f"- {k}: nicht berechenbar"
        )

    lines.append("\n## Cosine Similarity")
    for k, v in results["cosine_analysis"].items():
        if isinstance(v, (int, float)):
            lines.append(f"- {k}: {v:.6f}")

    lines.append("\n## Plot-Dateien")
    for p in sorted(OUTPUT_DIR.glob("*.png")):
        lines.append(f"- {p.name}")

    return "\n".join(lines)


def main():
    df = load_data()

    results = {
        "metadata": {
            "input_file": str(INPUT_FILE),
            "vector_mode": VECTOR_MODE,
            "dimensionen": DIMENSIONS,
            "n_total": int(len(df)),
            "n_by_group": df["gruppe"].value_counts().to_dict(),
        },
        "descriptive": descriptive_by_group(df),
        "group_tests": group_tests(df),
        "categorical_tests": categorical_tests(df),
        "cosine_analysis": cosine_analysis(df),
    }

    create_plots(df)

    (OUTPUT_DIR / "auswertung_lk1_vs_other.json").write_text(
        json.dumps(results, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )

    (OUTPUT_DIR / "auswertung_lk1_vs_other.md").write_text(
        make_markdown_report(results),
        encoding="utf-8",
    )

    df.to_csv(OUTPUT_DIR / "auswertung_datensatz.csv", index=False, encoding="utf-8-sig")

    print(f"Auswertung abgeschlossen: {OUTPUT_DIR.resolve()}")
    print("Erzeugt:")
    for p in sorted(OUTPUT_DIR.iterdir()):
        print(" -", p.name)


if __name__ == "__main__":
    main()
