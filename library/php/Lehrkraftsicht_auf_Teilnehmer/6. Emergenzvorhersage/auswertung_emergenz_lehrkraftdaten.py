import json
from pathlib import Path
import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans, DBSCAN
from sklearn.metrics import silhouette_score
from sklearn.decomposition import PCA

INFILE = Path("auswertung_06_emergenzvorhersage.json")
OUTDIR = Path("auswertung_06_emergenzvorhersage_outputs")
OUTDIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
    "x_methodik", "x_performanz", "x_regulation"
]


def load_scope(payload, scope_name):
    rows = payload["scopes"][scope_name]["rows"]
    df = pd.DataFrame(rows)

    if df.empty:
        return df

    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    df = df.sort_values(["datum", "id_mtr_rueckkopplung_datenmaske", "id"]).reset_index(drop=True)

    for d in DIMENSIONS:
        df[d] = pd.to_numeric(df[d], errors="coerce").fillna(0.0)

    df["d_semantisch"] = pd.to_numeric(df["d_semantisch"], errors="coerce").fillna(0.0)
    df["polaritaet_gesamt"] = pd.to_numeric(df["polaritaet_gesamt"], errors="coerce").fillna(0)

    return df


def best_kmeans(X_scaled, k_min=2, k_max=8):
    n = len(X_scaled)
    if n < 4:
        return None, None, None

    max_k = min(k_max, n - 1)
    results = []

    for k in range(k_min, max_k + 1):
        model = KMeans(n_clusters=k, random_state=42, n_init=20)
        labels = model.fit_predict(X_scaled)

        if len(set(labels)) > 1:
            sil = silhouette_score(X_scaled, labels)
        else:
            sil = np.nan

        results.append((k, sil, model, labels))

    results = [r for r in results if not np.isnan(r[1])]
    if not results:
        return None, None, None

    best = max(results, key=lambda r: r[1])
    return best[2], best[3], pd.DataFrame(
        [{"k": r[0], "silhouette": r[1]} for r in results]
    )


def transition_matrix(labels):
    labels = list(labels)
    unique = sorted(set(labels))
    index = {c: i for i, c in enumerate(unique)}
    mat = np.zeros((len(unique), len(unique)), dtype=int)

    for a, b in zip(labels[:-1], labels[1:]):
        mat[index[a], index[b]] += 1

    return pd.DataFrame(mat, index=unique, columns=unique)


def attractor_table(df, label_col):
    grouped = df.groupby(label_col).agg(
        n=("id", "count"),
        d_semantisch_mean=("d_semantisch", "mean"),
        d_semantisch_std=("d_semantisch", "std"),
        dominante_dimension_mode=("dominante_dimension", lambda x: x.mode().iloc[0] if not x.mode().empty else None),
        polaritaet_mean=("polaritaet_gesamt", "mean")
    ).reset_index()

    grouped["anteil"] = grouped["n"] / grouped["n"].sum()
    grouped = grouped.sort_values(["n", "d_semantisch_mean"], ascending=False)
    return grouped


def plot_pca(df, labels, scope_name, method_name):
    X = df[DIMENSIONS].to_numpy()
    if len(df) < 3:
        return None

    X_scaled = StandardScaler().fit_transform(X)
    pca = PCA(n_components=2, random_state=42)
    coords = pca.fit_transform(X_scaled)

    plt.figure(figsize=(10, 7))
    plt.scatter(coords[:, 0], coords[:, 1], c=labels, s=35)
    plt.title(f"6.x.1 Clusterbildung im FRZK-Raum – {scope_name} ({method_name})")
    plt.xlabel("PCA 1")
    plt.ylabel("PCA 2")
    plt.grid(True)

    path = OUTDIR / f"abb_06_1_cluster_pca_{scope_name}_{method_name}.png"
    plt.tight_layout()
    plt.savefig(path, dpi=300)
    plt.close()
    return path


def plot_transition_matrix(mat, scope_name, method_name):
    plt.figure(figsize=(8, 7))
    plt.imshow(mat.values)
    plt.title(f"6.x.2 Transition-Matrix emergenter Zustandsräume – {scope_name}")
    plt.xlabel("Folgezustand / Cluster")
    plt.ylabel("Ausgangszustand / Cluster")
    plt.xticks(range(len(mat.columns)), mat.columns)
    plt.yticks(range(len(mat.index)), mat.index)
    plt.colorbar(label="Übergänge")

    path = OUTDIR / f"abb_06_2_transition_matrix_{scope_name}_{method_name}.png"
    plt.tight_layout()
    plt.savefig(path, dpi=300)
    plt.close()
    return path


def plot_density_by_cluster(df, label_col, scope_name, method_name):
    plt.figure(figsize=(10, 6))
    df.boxplot(column="d_semantisch", by=label_col)
    plt.title(f"6.x.3 Semantische Dichte nach Zustandsraum – {scope_name}")
    plt.suptitle("")
    plt.xlabel("Cluster / Zustandsraum")
    plt.ylabel("d_semantisch")

    path = OUTDIR / f"abb_06_3_dichte_cluster_{scope_name}_{method_name}.png"
    plt.tight_layout()
    plt.savefig(path, dpi=300)
    plt.close()
    return path


def analyze_scope(scope_name, df):
    if df.empty or len(df) < 4:
        return {
            "scope": scope_name,
            "n": len(df),
            "hinweis": "Zu wenige Datensätze für Clusteranalyse."
        }

    X = df[DIMENSIONS].to_numpy()
    X_scaled = StandardScaler().fit_transform(X)

    kmeans_model, kmeans_labels, k_table = best_kmeans(X_scaled)
    if kmeans_model is None:
        return {
            "scope": scope_name,
            "n": len(df),
            "hinweis": "KMeans konnte keinen stabilen Clusterraum bilden."
        }

    df["cluster_kmeans"] = kmeans_labels

    dbscan = DBSCAN(eps=1.25, min_samples=5)
    db_labels = dbscan.fit_predict(X_scaled)
    df["cluster_dbscan"] = db_labels

    k_attractors = attractor_table(df, "cluster_kmeans")
    k_transitions = transition_matrix(df["cluster_kmeans"])

    pca_path = plot_pca(df, kmeans_labels, scope_name, "kmeans")
    trans_path = plot_transition_matrix(k_transitions, scope_name, "kmeans")
    density_path = plot_density_by_cluster(df, "cluster_kmeans", scope_name, "kmeans")

    csv_clustered = OUTDIR / f"daten_06_clustered_{scope_name}.csv"
    df.to_csv(csv_clustered, index=False, encoding="utf-8-sig")

    csv_attr = OUTDIR / f"tabelle_06_attraktoren_{scope_name}.csv"
    k_attractors.to_csv(csv_attr, index=False, encoding="utf-8-sig")

    csv_trans = OUTDIR / f"tabelle_06_transition_matrix_{scope_name}.csv"
    k_transitions.to_csv(csv_trans, encoding="utf-8-sig")

    dominant_cluster = k_attractors.iloc[0].to_dict()

    interpretation = (
        f"Im Vergleichsraum '{scope_name}' wurden {len(df)} Zustände analysiert. "
        f"Die KMeans-Lösung erzeugt {df['cluster_kmeans'].nunique()} wiederkehrende Zustandsräume. "
        f"Der stärkste Attraktor ist Cluster {dominant_cluster['cluster_kmeans']} mit "
        f"{int(dominant_cluster['n'])} Beobachtungen und einer mittleren semantischen Dichte von "
        f"{dominant_cluster['d_semantisch_mean']:.3f}. "
        f"Die dominante Dimension dieses Attraktors ist "
        f"{dominant_cluster['dominante_dimension_mode']}. "
        f"FRZK-konform spricht dies für emergente Stabilisierung, wenn Cluster nicht nur punktuell auftreten, "
        f"sondern über Transitionen wiederholt erreicht werden."
    )

    return {
        "scope": scope_name,
        "n": len(df),
        "kmeans_silhouette": None if k_table is None else float(k_table["silhouette"].max()),
        "kmeans_kandidaten": None if k_table is None else k_table.to_dict(orient="records"),
        "attraktoren": k_attractors.to_dict(orient="records"),
        "transition_matrix": k_transitions.to_dict(),
        "abbildungen": {
            "cluster_pca": str(pca_path),
            "transition_matrix": str(trans_path),
            "dichte_cluster": str(density_path)
        },
        "tabellen": {
            "clustered_data": str(csv_clustered),
            "attraktoren": str(csv_attr),
            "transition_matrix": str(csv_trans)
        },
        "interpretation": interpretation
    }


def main():
    payload = json.loads(INFILE.read_text(encoding="utf-8"))
    results = {
        "auswertungspunkt": payload["auswertungspunkt"],
        "frzk_vorhersage": payload["frzk_vorhersage"],
        "analysen": {}
    }

    for scope_name in payload["scopes"].keys():
        df = load_scope(payload, scope_name)
        results["analysen"][scope_name] = analyze_scope(scope_name, df)

    out_json = OUTDIR / "analyse_06_emergenzvorhersage_ergebnisse.json"
    out_json.write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")

    out_txt = OUTDIR / "analyse_06_emergenzvorhersage_textauswertung.txt"
    with out_txt.open("w", encoding="utf-8") as f:
        f.write("6. Emergenzvorhersage – textuelle Auswertung\n\n")
        for scope, res in results["analysen"].items():
            f.write(f"{scope}\n")
            f.write("-" * len(scope) + "\n")
            f.write(res.get("interpretation", res.get("hinweis", "")))
            f.write("\n\n")

    print(f"Analyse abgeschlossen: {out_json.resolve()}")
    print(f"Textauswertung: {out_txt.resolve()}")


if __name__ == "__main__":
    main()