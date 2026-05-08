
#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
FRZK-Clusteranalyse mit dimensionsspezifischer Gewichtung (1..10), Grid-Search
und automatischer Auswahl des besten Settings.

Ziel:
- JSON aus datenm_values_sem_dichte_lehrer_type_3 einlesen
- FRZK-Dimensionen gewichten
- für mehrere K und Gewichtungssettings Clustering testen
- bestes Setting nach Silhouette / Calinski-Harabasz / Davies-Bouldin wählen
- Ergebnisdateien und Abbildungen speichern

Erwartetes JSON-Format:
[
  {
    "id": 1,
    "gruppe_id": 4,
    "teilnehmer_id": 123,
    "fach": "MAT",
    "datum": "2026-01-14",
    "lehrkraft_id": 1,
    "x_kognition": 0.61,
    "x_sozial": 0.07,
    "x_affektiv": 0.21,
    "x_motivation": 0.33,
    "x_methodik": 0.42,
    "x_performanz": 0.51,
    "x_regulation": 0.28
  },
  ...
]

Alternativ wird auch ein Objekt mit Schlüssel "records" unterstützt:
{"records": [ ... ]}
"""

import argparse
import itertools
import json
import math
import os
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA
from sklearn.metrics import calinski_harabasz_score, davies_bouldin_score, silhouette_score
from sklearn.preprocessing import StandardScaler

FRZK_DIMS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

FRZK_GROUPS = {
    "struktur": ["x_kognition", "x_methodik", "x_performanz"],
    "sozio_affektiv": ["x_sozial", "x_affektiv"],
    "selbststeuerung": ["x_motivation", "x_regulation"],
}


def parse_args():
    p = argparse.ArgumentParser(description="FRZK Grid-Search für gewichtetes K-Means-Clustering")
    p.add_argument("--input", required=True, help="Pfad zur JSON-Datei")
    p.add_argument("--output-dir", required=True, help="Ausgabeverzeichnis")
    p.add_argument("--k-min", type=int, default=3, help="Minimale Clusteranzahl")
    p.add_argument("--k-max", type=int, default=8, help="Maximale Clusteranzahl")
    p.add_argument("--weight-min", type=int, default=1, help="Minimaler Gewichtungsfaktor")
    p.add_argument("--weight-max", type=int, default=10, help="Maximaler Gewichtungsfaktor")
    p.add_argument(
        "--mode",
        choices=["grouped", "full"],
        default="grouped",
        help=(
            "grouped = 3 interpretierbare Gruppenparameter "
            "(struktur, sozio_affektiv, selbststeuerung); "
            "full = 7 Einzelfaktoren für alle Dimensionen"
        ),
    )
    p.add_argument(
        "--step",
        type=int,
        default=1,
        help="Schrittweite der Faktoren, z. B. 1 für 1..10, 2 für 1,3,5,...",
    )
    p.add_argument(
        "--normalize",
        choices=["zscore", "none"],
        default="zscore",
        help="Vorverarbeitung vor der Gewichtung",
    )
    p.add_argument(
        "--max-combinations",
        type=int,
        default=5000,
        help="Sicherheitsgrenze für die Anzahl getesteter Gewichtungskombinationen",
    )
    p.add_argument(
        "--random-state",
        type=int,
        default=42,
        help="Random State für Reproduzierbarkeit",
    )
    p.add_argument(
        "--n-init",
        type=int,
        default=20,
        help="n_init für KMeans",
    )
    p.add_argument(
        "--top-n",
        type=int,
        default=25,
        help="Anzahl der besten Settings, die zusätzlich gespeichert werden",
    )
    return p.parse_args()


def load_records(path: str) -> pd.DataFrame:
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    if isinstance(data, dict) and "records" in data:
        records = data["records"]
    elif isinstance(data, list):
        records = data
    else:
        raise ValueError("JSON muss entweder eine Liste oder ein Objekt mit Schlüssel 'records' sein.")

    df = pd.DataFrame(records)
    missing = [c for c in FRZK_DIMS if c not in df.columns]
    if missing:
        raise ValueError(f"Folgende FRZK-Dimensionen fehlen im JSON: {missing}")

    # numerisch erzwingen
    for c in FRZK_DIMS:
        df[c] = pd.to_numeric(df[c], errors="coerce")

    df = df.dropna(subset=FRZK_DIMS).copy()

    if "id" not in df.columns:
        df["id"] = np.arange(1, len(df) + 1)

    return df


def preprocess(df: pd.DataFrame, normalize: str) -> np.ndarray:
    X = df[FRZK_DIMS].to_numpy(dtype=float)

    if normalize == "zscore":
        scaler = StandardScaler()
        X = scaler.fit_transform(X)

    return X


def grouped_weight_vectors(weight_values):
    combos = list(itertools.product(weight_values, repeat=3))
    for struktur, sozio_affektiv, selbststeuerung in combos:
        weights = {
            "x_kognition": struktur,
            "x_methodik": struktur,
            "x_performanz": struktur,
            "x_sozial": sozio_affektiv,
            "x_affektiv": sozio_affektiv,
            "x_motivation": selbststeuerung,
            "x_regulation": selbststeuerung,
        }
        yield {
            "mode": "grouped",
            "struktur": struktur,
            "sozio_affektiv": sozio_affektiv,
            "selbststeuerung": selbststeuerung,
            "weights": weights,
        }


def full_weight_vectors(weight_values):
    combos = itertools.product(weight_values, repeat=len(FRZK_DIMS))
    for combo in combos:
        weights = dict(zip(FRZK_DIMS, combo))
        row = {"mode": "full", **weights, "weights": weights}
        yield row


def count_combinations(mode: str, n_values: int) -> int:
    if mode == "grouped":
        return n_values ** 3
    return n_values ** len(FRZK_DIMS)


def apply_weights(X: np.ndarray, weights: dict) -> np.ndarray:
    w = np.array([weights[c] for c in FRZK_DIMS], dtype=float)
    return X * w


def evaluate_setting(Xw: np.ndarray, k: int, random_state: int, n_init: int) -> dict:
    model = KMeans(n_clusters=k, random_state=random_state, n_init=n_init)
    labels = model.fit_predict(Xw)

    # Schutz gegen degenerierte Lösungen
    n_labels = len(np.unique(labels))
    if n_labels < 2:
        return {
            "labels": labels,
            "model": model,
            "silhouette": float("-inf"),
            "calinski_harabasz": float("-inf"),
            "davies_bouldin": float("inf"),
            "score": float("-inf"),
        }

    sil = silhouette_score(Xw, labels)
    ch = calinski_harabasz_score(Xw, labels)
    db = davies_bouldin_score(Xw, labels)

    # kombinierter Score: hoch = gut
    # Silhouette positiv, CH positiv, DB niedrig.
    score = sil + math.log1p(max(ch, 0.0)) - db

    return {
        "labels": labels,
        "model": model,
        "silhouette": sil,
        "calinski_harabasz": ch,
        "davies_bouldin": db,
        "score": score,
    }


def cluster_profiles(df: pd.DataFrame, labels: np.ndarray) -> pd.DataFrame:
    tmp = df.copy()
    tmp["cluster"] = labels

    rows = []
    for cluster_id, g in tmp.groupby("cluster"):
        means = g[FRZK_DIMS].mean().to_dict()
        dominant_dim = max(FRZK_DIMS, key=lambda c: abs(means[c]))
        dominant_val = means[dominant_dim]
        rows.append({
            "cluster": int(cluster_id),
            "n": int(len(g)),
            "dominante_dimension": dominant_dim,
            "dominante_dimension_wert": float(dominant_val),
            **{f"mean_{c}": float(means[c]) for c in FRZK_DIMS},
        })

    return pd.DataFrame(rows).sort_values("cluster").reset_index(drop=True)


def save_pca_plot(Xw: np.ndarray, ids: np.ndarray, labels: np.ndarray, output_path: Path, title: str):
    pca = PCA(n_components=2, random_state=42)
    coords = pca.fit_transform(Xw)

    plt.figure(figsize=(14, 9))
    for cluster_id in sorted(np.unique(labels)):
        mask = labels == cluster_id
        plt.scatter(coords[mask, 0], coords[mask, 1], s=70, alpha=0.8, label=f"Cluster {cluster_id}")
        for x, y, rec_id in zip(coords[mask, 0], coords[mask, 1], ids[mask]):
            plt.text(x, y, str(rec_id), fontsize=8, alpha=0.7)

    plt.title(title)
    plt.xlabel("PCA 1")
    plt.ylabel("PCA 2")
    plt.legend()
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(output_path, dpi=180)
    plt.close()

    return {
        "explained_variance_ratio": pca.explained_variance_ratio_.tolist()
    }


def main():
    args = parse_args()
    outdir = Path(args.output_dir)
    outdir.mkdir(parents=True, exist_ok=True)

    df = load_records(args.input)
    X = preprocess(df, args.normalize)
    ids = df["id"].to_numpy()

    weight_values = list(range(args.weight_min, args.weight_max + 1, args.step))
    total_combinations = count_combinations(args.mode, len(weight_values))
    if total_combinations > args.max_combinations:
        raise ValueError(
            f"Zu viele Gewichtungskombinationen: {total_combinations}. "
            f"Erhöhe --step, nutze mode=grouped oder setze --max-combinations höher."
        )

    if args.mode == "grouped":
        generator = grouped_weight_vectors(weight_values)
    else:
        generator = full_weight_vectors(weight_values)

    results = []
    best = None
    best_payload = None

    tested = 0
    for weight_payload in generator:
        weights = weight_payload["weights"]
        Xw = apply_weights(X, weights)

        for k in range(args.k_min, args.k_max + 1):
            ev = evaluate_setting(
                Xw=Xw,
                k=k,
                random_state=args.random_state,
                n_init=args.n_init,
            )

            row = {
                "k": k,
                "silhouette": float(ev["silhouette"]),
                "calinski_harabasz": float(ev["calinski_harabasz"]),
                "davies_bouldin": float(ev["davies_bouldin"]),
                "score": float(ev["score"]),
            }

            for dim in FRZK_DIMS:
                row[dim] = weight_payload["weights"][dim]

            if args.mode == "grouped":
                row["struktur"] = weight_payload["struktur"]
                row["sozio_affektiv"] = weight_payload["sozio_affektiv"]
                row["selbststeuerung"] = weight_payload["selbststeuerung"]

            results.append(row)

            if best is None or row["score"] > best["score"]:
                best = row
                best_payload = {
                    "weights": weights,
                    "labels": ev["labels"],
                    "model": ev["model"],
                    "Xw": Xw.copy(),
                    "k": k,
                }

        tested += 1

    results_df = pd.DataFrame(results).sort_values(
        ["score", "silhouette", "calinski_harabasz"],
        ascending=[False, False, False]
    ).reset_index(drop=True)

    results_df.to_csv(outdir / "gridsearch_ergebnisse.csv", index=False)

    top_df = results_df.head(args.top_n).copy()
    top_df.to_csv(outdir / "gridsearch_top_settings.csv", index=False)

    # Bestes Setting detailliert sichern
    best_labels = best_payload["labels"]
    df_best = df.copy()
    df_best["cluster"] = best_labels

    profile_df = cluster_profiles(df, best_labels)
    profile_df.to_csv(outdir / "bestes_setting_clusterprofile.csv", index=False)
    df_best.to_csv(outdir / "bestes_setting_clusterzuordnung.csv", index=False)

    pca_meta = save_pca_plot(
        Xw=best_payload["Xw"],
        ids=ids,
        labels=best_labels,
        output_path=outdir / "bestes_setting_cluster_pca.png",
        title=f"Bestes Setting im PCA-Raum (K={best_payload['k']})"
    )

    # Zentren im gewichteten Raum
    centers_df = pd.DataFrame(best_payload["model"].cluster_centers_, columns=FRZK_DIMS)
    centers_df.insert(0, "cluster", range(len(centers_df)))
    centers_df.to_csv(outdir / "bestes_setting_clusterzentren_gewichteter_raum.csv", index=False)

    summary = {
        "input": args.input,
        "output_dir": str(outdir),
        "normalize": args.normalize,
        "mode": args.mode,
        "k_min": args.k_min,
        "k_max": args.k_max,
        "weight_min": args.weight_min,
        "weight_max": args.weight_max,
        "step": args.step,
        "anzahl_datensaetze": int(len(df)),
        "anzahl_getesteter_gewichtungsvektoren": int(tested),
        "anzahl_getesteter_modelle": int(len(results_df)),
        "bestes_setting": {
            "k": int(best_payload["k"]),
            "score": float(best["score"]),
            "silhouette": float(best["silhouette"]),
            "calinski_harabasz": float(best["calinski_harabasz"]),
            "davies_bouldin": float(best["davies_bouldin"]),
            "weights": best_payload["weights"],
            "pca_explained_variance_ratio": pca_meta["explained_variance_ratio"],
        },
        "dateien": [
            "gridsearch_ergebnisse.csv",
            "gridsearch_top_settings.csv",
            "bestes_setting_clusterprofile.csv",
            "bestes_setting_clusterzuordnung.csv",
            "bestes_setting_clusterzentren_gewichteter_raum.csv",
            "bestes_setting_cluster_pca.png",
            "summary.json",
        ],
    }

    with open(outdir / "summary.json", "w", encoding="utf-8") as f:
        json.dump(summary, f, ensure_ascii=False, indent=2)

    print("Fertig.")
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
