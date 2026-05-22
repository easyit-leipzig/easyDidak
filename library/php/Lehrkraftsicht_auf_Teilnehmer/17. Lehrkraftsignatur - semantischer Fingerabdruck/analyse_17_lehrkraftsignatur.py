import json
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt

from sklearn.preprocessing import StandardScaler
from sklearn.decomposition import PCA
from sklearn.manifold import TSNE
from sklearn.cluster import KMeans


INPUT_FILE = Path("auswertung_17_lehrkraftsignatur.json")
OUTPUT_DIR = Path("charts_17_lehrkraftsignatur")
OUTPUT_DIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
    "x_methodik", "x_performanz", "x_regulation"
]


def load_profiles(scope_name):
    data = json.loads(INPUT_FILE.read_text(encoding="utf-8"))
    return data["scopes"][scope_name]["teacher_profiles"]


def profiles_to_dataframe(profiles):
    rows = []

    for p in profiles:
        row = {
            "lehrkraft_id": p["lehrkraft_id"],
            "n_records": p["n_records"],
            "mean_semantic_density": p["mean_semantic_density"],
            "std_semantic_density": p["std_semantic_density"],
            "mean_polarity": p["mean_polarity"],
            "mean_operator_count": p["mean_operator_count"],
            "mean_modulator_count": p["mean_modulator_count"],
            "mean_drift": p["mean_drift"],
            "std_drift": p["std_drift"],
            "dominance_change_rate": p["dominance_change_rate"],
            "primary_dominant_dimension": p["primary_dominant_dimension"]
        }

        for d in DIMENSIONS:
            row[f"mean_{d}"] = p["mean_vector"].get(d, 0.0)
            row[f"std_{d}"] = p["std_vector"].get(d, 0.0)

        rows.append(row)

    return pd.DataFrame(rows)


def classify_teacher(row):
    dim_values = {
        "kognitiv verdichtend": row["mean_x_kognition"],
        "sozial integrierend": row["mean_x_sozial"],
        "motivational aktivierend": row["mean_x_motivation"],
        "performanzzentriert": row["mean_x_performanz"],
        "regulatorisch stabilisierend": row["mean_x_regulation"]
    }

    main_type = max(dim_values, key=dim_values.get)

    if row["mean_drift"] < 0.15 and row["dominance_change_rate"] < 0.25:
        return "regulatorisch stabilisierend"

    return main_type


def run_pca(df, scope_name):
    feature_cols = [c for c in df.columns if c.startswith("mean_x_")] + [
        "mean_semantic_density",
        "mean_polarity",
        "mean_operator_count",
        "mean_modulator_count",
        "mean_drift",
        "dominance_change_rate"
    ]

    X = df[feature_cols].fillna(0.0)

    if len(df) < 2:
        return df

    X_scaled = StandardScaler().fit_transform(X)

    pca = PCA(n_components=2)
    coords = pca.fit_transform(X_scaled)

    df["pca_1"] = coords[:, 0]
    df["pca_2"] = coords[:, 1]

    plt.figure(figsize=(10, 7))
    plt.scatter(df["pca_1"], df["pca_2"])

    for _, row in df.iterrows():
        plt.text(row["pca_1"], row["pca_2"], str(int(row["lehrkraft_id"])))

    plt.title(f"PCA der Lehrkraftsignaturen – {scope_name}")
    plt.xlabel("PCA 1")
    plt.ylabel("PCA 2")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_pca_lehrkraftsignatur.png", dpi=300)
    plt.close()

    return df


def run_tsne(df, scope_name):
    feature_cols = [c for c in df.columns if c.startswith("mean_x_")] + [
        "mean_semantic_density",
        "mean_polarity",
        "mean_operator_count",
        "mean_modulator_count",
        "mean_drift",
        "dominance_change_rate"
    ]

    if len(df) < 4:
        return df

    X = df[feature_cols].fillna(0.0)
    X_scaled = StandardScaler().fit_transform(X)

    perplexity = max(2, min(5, len(df) - 1))

    tsne = TSNE(
        n_components=2,
        perplexity=perplexity,
        random_state=42,
        init="pca",
        learning_rate="auto"
    )

    coords = tsne.fit_transform(X_scaled)

    df["tsne_1"] = coords[:, 0]
    df["tsne_2"] = coords[:, 1]

    plt.figure(figsize=(10, 7))
    plt.scatter(df["tsne_1"], df["tsne_2"])

    for _, row in df.iterrows():
        plt.text(row["tsne_1"], row["tsne_2"], str(int(row["lehrkraft_id"])))

    plt.title(f"t-SNE der Lehrkraftsignaturen – {scope_name}")
    plt.xlabel("t-SNE 1")
    plt.ylabel("t-SNE 2")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_tsne_lehrkraftsignatur.png", dpi=300)
    plt.close()

    return df


def run_clustering(df, scope_name):
    feature_cols = [c for c in df.columns if c.startswith("mean_x_")] + [
        "mean_semantic_density",
        "mean_polarity",
        "mean_operator_count",
        "mean_modulator_count",
        "mean_drift",
        "dominance_change_rate"
    ]

    if len(df) < 3:
        df["cluster"] = 0
        return df

    X = df[feature_cols].fillna(0.0)
    X_scaled = StandardScaler().fit_transform(X)

    k = min(5, len(df))
    model = KMeans(n_clusters=k, random_state=42, n_init=20)
    df["cluster"] = model.fit_predict(X_scaled)

    return df


def plot_dimension_heatmap(df, scope_name):
    dim_cols = [f"mean_{d}" for d in DIMENSIONS]

    if df.empty:
        return

    matrix = df.set_index("lehrkraft_id")[dim_cols]

    plt.figure(figsize=(12, max(5, len(df) * 0.4)))
    plt.imshow(matrix, aspect="auto")
    plt.colorbar(label="mittlerer Dimensionswert")
    plt.yticks(range(len(matrix.index)), matrix.index)
    plt.xticks(range(len(dim_cols)), [c.replace("mean_x_", "") for c in dim_cols], rotation=45)
    plt.title(f"Semantischer Fingerabdruck je Lehrkraft – {scope_name}")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_heatmap_semantischer_fingerabdruck.png", dpi=300)
    plt.close()


def write_report(df, scope_name):
    report_file = OUTPUT_DIR / f"{scope_name}_bericht_lehrkraftsignatur.txt"

    lines = []
    lines.append(f"Auswertung 17: Lehrkraftsignatur / semantischer Fingerabdruck – {scope_name}")
    lines.append("=" * 90)
    lines.append("")

    lines.append(f"Anzahl Lehrkräfte: {len(df)}")
    lines.append("")

    for _, row in df.sort_values("lehrkraft_id").iterrows():
        lines.append(f"Lehrkraft {int(row['lehrkraft_id'])}")
        lines.append(f"  Datensätze: {int(row['n_records'])}")
        lines.append(f"  Typisierung: {row['lehrkrafttyp']}")
        lines.append(f"  Cluster: {row.get('cluster', '-')}")
        lines.append(f"  mittlere semantische Dichte: {row['mean_semantic_density']:.4f}")
        lines.append(f"  mittlere Polarität: {row['mean_polarity']:.4f}")
        lines.append(f"  mittlere Drift: {row['mean_drift']:.4f}")
        lines.append(f"  Dominanzwechselrate: {row['dominance_change_rate']:.4f}")
        lines.append(f"  Primäre dominante Dimension: {row['primary_dominant_dimension']}")
        lines.append("")

    report_file.write_text("\n".join(lines), encoding="utf-8")


def analyze_scope(scope_name):
    profiles = load_profiles(scope_name)
    df = profiles_to_dataframe(profiles)

    if df.empty:
        return

    df["lehrkrafttyp"] = df.apply(classify_teacher, axis=1)

    df = run_clustering(df, scope_name)
    df = run_pca(df, scope_name)
    df = run_tsne(df, scope_name)

    plot_dimension_heatmap(df, scope_name)

    df.to_csv(OUTPUT_DIR / f"{scope_name}_lehrkraftsignaturen.csv", index=False, encoding="utf-8-sig")
    write_report(df, scope_name)


def main():
    for scope in ["alle_lehrkraefte", "lehrkraft_1", "ohne_lehrkraft_1"]:
        analyze_scope(scope)

    print(f"Analyse abgeschlossen. Ergebnisse in: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()