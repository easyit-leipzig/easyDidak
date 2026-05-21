#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Analyse 12: Lehrkrafttypen durch Clusterbildung aus JSON-Basis

Liest ausschließlich lehrkrafttypen_clusterbasis.json und erzeugt:
- CSV mit Lehrkraft-ID -> Cluster/Typ
- JSON mit Clusterzentren und Typinterpretationen
- Diagramme: PCA-Scatter, Dendrogramm, Heatmap der Clusterzentren, Silhouette-Kurve

Benötigte Pakete:
    pip install pandas numpy matplotlib scikit-learn scipy
"""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Dict, List, Tuple

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from scipy.cluster.hierarchy import dendrogram, linkage
from sklearn.cluster import AgglomerativeClustering, DBSCAN, KMeans
from sklearn.decomposition import PCA
from sklearn.metrics import silhouette_score
from sklearn.preprocessing import StandardScaler

INPUT_JSON = Path("lehrkrafttypen_clusterbasis.json")
OUTPUT_DIR = Path("lehrkrafttypen_clusteranalyse_output")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

FEATURE_COLUMNS = [
    *[f"avg_{d}" for d in DIMENSIONS],
    *[f"std_{d}" for d in DIMENSIONS],
    "avg_d_semantisch",
    "std_d_semantisch",
    "avg_token_anzahl",
    "avg_funktionsklassen_anzahl_gesamt",
    "avg_operator_count",
    "avg_modulator_count",
    "dimensionale_balance",
    "avg_x_norm",
]


def load_scope(scope: str = "alle_lehrkraefte") -> Tuple[pd.DataFrame, Dict[str, Any]]:
    if not INPUT_JSON.exists():
        raise FileNotFoundError(f"JSON-Datei nicht gefunden: {INPUT_JSON.resolve()}")

    payload = json.loads(INPUT_JSON.read_text(encoding="utf-8"))
    scope_data = payload["scopes"][scope]
    df = pd.DataFrame(scope_data["teacher_aggregates"])
    if df.empty:
        raise ValueError(f"Scope '{scope}' enthält keine Lehrkraftaggregate.")
    return df, payload


def prepare_features(df: pd.DataFrame) -> Tuple[pd.DataFrame, np.ndarray, StandardScaler, List[str]]:
    existing = [c for c in FEATURE_COLUMNS if c in df.columns]
    if len(existing) < 7:
        raise ValueError("Zu wenige numerische Merkmale für eine sinnvolle Clusteranalyse.")

    X_df = df[existing].copy()
    for col in existing:
        X_df[col] = pd.to_numeric(X_df[col], errors="coerce")

    # Fehlende Werte robust ersetzen: zuerst Spaltenmedian, falls komplett leer dann 0.
    X_df = X_df.fillna(X_df.median(numeric_only=True)).fillna(0.0)

    scaler = StandardScaler()
    X = scaler.fit_transform(X_df.values)
    return X_df, X, scaler, existing


def choose_k(X: np.ndarray, max_k: int = 8) -> Tuple[int, pd.DataFrame]:
    n = X.shape[0]
    rows = []
    if n < 3:
        return 1, pd.DataFrame(rows)

    upper = min(max_k, n - 1)
    best_k = 2
    best_score = -1.0

    for k in range(2, upper + 1):
        labels = KMeans(n_clusters=k, random_state=42, n_init=50).fit_predict(X)
        score = silhouette_score(X, labels) if len(set(labels)) > 1 else -1.0
        rows.append({"k": k, "silhouette": float(score)})
        if score > best_score:
            best_score = score
            best_k = k

    return best_k, pd.DataFrame(rows)


def interpret_type(row: pd.Series) -> str:
    vals = {d: float(row.get(f"avg_{d}", 0.0) or 0.0) for d in DIMENSIONS}
    dominant = max(vals.items(), key=lambda x: abs(x[1]))[0]
    dichte = float(row.get("avg_d_semantisch", 0.0) or 0.0)
    balance = float(row.get("dimensionale_balance", 0.0) or 0.0)

    if vals["affektiv"] < -0.15 or vals["motivation"] < -0.15:
        return "defizit-/krisenfokussierter Lehrkrafttyp"
    if vals["kognition"] >= vals["performanz"] and vals["kognition"] >= vals["methodik"]:
        if vals["methodik"] > 0.2 or vals["regulation"] > 0.2:
            return "kognitiv-strukturierender Lehrkrafttyp"
        return "kognitiv-diagnostischer Lehrkrafttyp"
    if vals["performanz"] >= vals["kognition"] and vals["performanz"] >= vals["motivation"]:
        return "performanzorientierter Lehrkrafttyp"
    if vals["sozial"] > 0.2 and vals["affektiv"] > 0.1:
        return "sozial-resonanter Lehrkrafttyp"
    if vals["regulation"] > 0.2 and vals["methodik"] > 0.2:
        return "regulations-/methodikorientierter Lehrkrafttyp"
    if balance > 0.85 and dichte > 0.5:
        return "balancierter Resonanztyp"
    return f"{dominant}-dominanter Übergangstyp"


def cluster_center_table(df: pd.DataFrame, label_col: str) -> pd.DataFrame:
    dim_cols = [f"avg_{d}" for d in DIMENSIONS if f"avg_{d}" in df.columns]
    extra = [c for c in ["avg_d_semantisch", "dimensionale_balance", "avg_x_norm", "n_saetze"] if c in df.columns]
    centers = df.groupby(label_col)[dim_cols + extra].mean(numeric_only=True).reset_index()
    centers["typ_interpretation"] = centers.apply(interpret_type, axis=1)
    return centers


def save_pca_plot(df: pd.DataFrame, X: np.ndarray, labels: np.ndarray, label_name: str, filename: str) -> None:
    if X.shape[0] < 2:
        return
    pca = PCA(n_components=2, random_state=42)
    pts = pca.fit_transform(X)

    plt.figure(figsize=(10, 7))
    scatter = plt.scatter(pts[:, 0], pts[:, 1], c=labels)
    for i, row in df.reset_index(drop=True).iterrows():
        plt.annotate(str(int(row["lehrkraft_id"])), (pts[i, 0], pts[i, 1]), fontsize=9, xytext=(4, 4), textcoords="offset points")
    plt.xlabel(f"PCA 1 ({pca.explained_variance_ratio_[0]:.1%})")
    plt.ylabel(f"PCA 2 ({pca.explained_variance_ratio_[1]:.1%})")
    plt.title(f"Lehrkrafttypen im FRZK-Raum – {label_name}")
    plt.colorbar(scatter, label="Cluster")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / filename, dpi=180)
    plt.close()


def save_silhouette_plot(scores: pd.DataFrame) -> None:
    if scores.empty:
        return
    plt.figure(figsize=(8, 5))
    plt.plot(scores["k"], scores["silhouette"], marker="o")
    plt.xlabel("Clusterzahl k")
    plt.ylabel("Silhouette Score")
    plt.title("Silhouette-Analyse zur Wahl der Clusterzahl")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_12_1_silhouette_clusterzahl.png", dpi=180)
    plt.close()


def save_center_heatmap(centers: pd.DataFrame, filename: str) -> None:
    dim_cols = [f"avg_{d}" for d in DIMENSIONS if f"avg_{d}" in centers.columns]
    if not dim_cols or centers.empty:
        return
    matrix = centers[dim_cols].values
    plt.figure(figsize=(10, max(4, len(centers) * 0.8)))
    plt.imshow(matrix, aspect="auto")
    plt.xticks(range(len(dim_cols)), [c.replace("avg_", "") for c in dim_cols], rotation=45, ha="right")
    plt.yticks(range(len(centers)), [f"Cluster {c}" for c in centers.iloc[:, 0]])
    plt.colorbar(label="mittlerer FRZK-Dimensionswert")
    plt.title("Clusterzentren der Lehrkrafttypen")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / filename, dpi=180)
    plt.close()


def save_dendrogram(X: np.ndarray, ids: List[int]) -> None:
    if X.shape[0] < 2:
        return
    Z = linkage(X, method="ward")
    plt.figure(figsize=(12, 6))
    dendrogram(Z, labels=[str(i) for i in ids])
    plt.title("Hierarchische Nähe der Lehrkrafttypen")
    plt.xlabel("lehrkraft_id")
    plt.ylabel("Ward-Distanz")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_12_4_dendrogramm_lehrkrafttypen.png", dpi=180)
    plt.close()


def run_analysis(scope: str = "alle_lehrkraefte") -> None:
    OUTPUT_DIR.mkdir(exist_ok=True)
    df, payload = load_scope(scope)
    X_df, X, scaler, feature_cols = prepare_features(df)

    best_k, sil_scores = choose_k(X)
    save_silhouette_plot(sil_scores)

    if best_k <= 1:
        kmeans_labels = np.zeros(len(df), dtype=int)
    else:
        kmeans_labels = KMeans(n_clusters=best_k, random_state=42, n_init=50).fit_predict(X)

    df["cluster_kmeans"] = kmeans_labels

    # Hierarchisches Clustering mit gleicher k-Zahl.
    if len(df) >= 2 and best_k > 1:
        df["cluster_hierarchisch"] = AgglomerativeClustering(n_clusters=best_k, linkage="ward").fit_predict(X)
    else:
        df["cluster_hierarchisch"] = 0

    # DBSCAN als Attraktor-/Ausreißerprüfung. eps ist bewusst moderat, weil Daten standardisiert sind.
    if len(df) >= 3:
        df["cluster_dbscan"] = DBSCAN(eps=1.8, min_samples=2).fit_predict(X)
    else:
        df["cluster_dbscan"] = 0

    centers = cluster_center_table(df, "cluster_kmeans")
    type_map = dict(zip(centers["cluster_kmeans"], centers["typ_interpretation"]))
    df["lehrkrafttyp"] = df["cluster_kmeans"].map(type_map)

    # Export Tabellen
    cols_first = [
        "lehrkraft_id",
        "n_saetze",
        "cluster_kmeans",
        "lehrkrafttyp",
        "cluster_hierarchisch",
        "cluster_dbscan",
        "aggregierte_dominante_dimension",
        "aggregierte_dominante_dimension_wert",
        "avg_d_semantisch",
        "dimensionale_balance",
    ]
    export_cols = [c for c in cols_first if c in df.columns] + [c for c in [f"avg_{d}" for d in DIMENSIONS] if c in df.columns]
    result_df = df[export_cols].sort_values(["cluster_kmeans", "lehrkraft_id"])
    result_df.to_csv(OUTPUT_DIR / "lehrkraft_id_zu_lehrkrafttyp.csv", index=False, encoding="utf-8-sig")
    centers.to_csv(OUTPUT_DIR / "clusterzentren_lehrkrafttypen.csv", index=False, encoding="utf-8-sig")

    # Diagramme
    save_pca_plot(df, X, df["cluster_kmeans"].values, "KMeans", "abb_12_2_pca_kmeans_lehrkrafttypen.png")
    save_pca_plot(df, X, df["cluster_dbscan"].values, "DBSCAN", "abb_12_3_pca_dbscan_attraktoren.png")
    save_center_heatmap(centers, "abb_12_5_heatmap_clusterzentren.png")
    save_dendrogram(X, [int(x) for x in df["lehrkraft_id"].tolist()])

    summary = {
        "metadata": payload.get("metadata", {}),
        "scope": scope,
        "n_lehrkraefte": int(len(df)),
        "feature_columns": feature_cols,
        "best_k_kmeans": int(best_k),
        "silhouette_scores": sil_scores.to_dict(orient="records"),
        "clusterzentren": centers.to_dict(orient="records"),
        "lehrkraft_zu_typ": result_df.to_dict(orient="records"),
        "interpretation": {
            "hinweis": "Die Typbezeichnungen sind FRZK-heuristische Interpretationen der Clusterzentren. Die ID-Zuordnung steht in lehrkraft_id_zu_lehrkrafttyp.csv.",
            "dbscan_label_minus_1": "DBSCAN-Label -1 bedeutet Randfall/Ausreißer im Lehrkraftzustandsraum.",
        },
    }
    (OUTPUT_DIR / "analyse_lehrkrafttypen_cluster.json").write_text(
        json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8"
    )

    print("Analyse abgeschlossen.")
    print(f"Output-Verzeichnis: {OUTPUT_DIR.resolve()}")
    print(f"Beste KMeans-Clusterzahl: {best_k}")
    print("ID-Zuordnung: lehrkraft_id_zu_lehrkrafttyp.csv")


if __name__ == "__main__":
    run_analysis("alle_lehrkraefte")
