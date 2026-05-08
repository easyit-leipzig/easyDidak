import argparse
import json
from pathlib import Path
from typing import Dict, List

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from sklearn.cluster import KMeans
from sklearn.decomposition import PCA
from sklearn.preprocessing import StandardScaler

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DIMENSION_LABELS = {
    "x_kognition": "Kognition",
    "x_sozial": "Sozial",
    "x_affektiv": "Affektiv",
    "x_motivation": "Motivation",
    "x_methodik": "Methodik",
    "x_performanz": "Performanz",
    "x_regulation": "Regulation",
}



def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Cluster- und Typenbildung für exportierte FRZK-Daten aus datenm_values_sem_dichte_lehrer_type_3"
    )
    parser.add_argument(
        "--input",
        required=True,
        help="Pfad zur Export-JSON-Datei aus export_datenm_values_sem_dichte_lehrer_type_3.py",
    )
    parser.add_argument("--k", type=int, default=3, help="Anzahl der Cluster")
    parser.add_argument(
        "--output-dir",
        default="cluster_output",
        help="Zielordner für JSON, CSV und Abbildungen",
    )
    parser.add_argument(
        "--random-state", type=int, default=42, help="Seed für reproduzierbares KMeans"
    )
    return parser.parse_args()



def load_dataframe(path: Path) -> pd.DataFrame:
    payload = json.loads(path.read_text(encoding="utf-8"))
    if "daten" not in payload:
        raise ValueError("Die JSON-Datei enthält keinen Schlüssel 'daten'.")
    df = pd.DataFrame(payload["daten"])
    missing = [col for col in DIMENSIONS if col not in df.columns]
    if missing:
        raise ValueError(f"Fehlende Dimensionsspalten: {missing}")
    for col in DIMENSIONS:
        df[col] = pd.to_numeric(df[col], errors="coerce")
    df = df.dropna(subset=DIMENSIONS).reset_index(drop=True)
    return df



def build_typology(center: np.ndarray) -> Dict[str, str]:
    dominant_idx = int(np.argmax(np.abs(center)))
    dominant_dim = DIMENSIONS[dominant_idx]
    dominant_val = float(center[dominant_idx])

    if dominant_dim == "x_kognition":
        typname = "kognitiv dominant"
    elif dominant_dim == "x_sozial":
        typname = "sozial stabil"
    elif dominant_dim == "x_affektiv" and dominant_val < 0:
        typname = "affektiv negativ"
    elif dominant_dim == "x_affektiv":
        typname = "affektiv positiv"
    elif dominant_dim == "x_methodik":
        typname = "methodisch fokussiert"
    elif dominant_dim == "x_performanz":
        typname = "performanzorientiert"
    elif dominant_dim == "x_regulation":
        typname = "regulativ stabil"
    elif dominant_dim == "x_motivation":
        typname = "motivational getragen"
    else:
        typname = dominant_dim.replace("x_", "")

    return {
        "dominante_dimension": dominant_dim,
        "dominante_dimension_label": DIMENSION_LABELS[dominant_dim],
        "typname": typname,
    }



def create_cluster_profile(df: pd.DataFrame, centers_original: np.ndarray) -> List[Dict]:
    profiles: List[Dict] = []
    for cluster_id in sorted(df["cluster_id"].unique()):
        subset = df[df["cluster_id"] == cluster_id]
        center = centers_original[cluster_id]
        typology = build_typology(center)
        profile = {
            "cluster_id": int(cluster_id),
            "groesse": int(len(subset)),
            "anteil": round(len(subset) / len(df), 6),
            **typology,
            "zentrum": {dim: round(float(center[idx]), 6) for idx, dim in enumerate(DIMENSIONS)},
            "mittelwerte": {
                dim: round(float(subset[dim].mean()), 6) for dim in DIMENSIONS
            },
            "d_semantisch_mittel": round(float(subset["d_semantisch"].mean()), 6)
            if "d_semantisch" in subset.columns
            else None,
        }
        profiles.append(profile)
    return profiles



def save_assignment_json(df: pd.DataFrame, profiles: List[Dict], output_dir: Path) -> Path:
    rows = []
    for row in df.to_dict(orient="records"):
        row_out = {}
        for key, value in row.items():
            if isinstance(value, (np.integer,)):
                row_out[key] = int(value)
            elif isinstance(value, (np.floating, float)):
                row_out[key] = round(float(value), 6)
            else:
                row_out[key] = value
        rows.append(row_out)

    payload = {
        "clusterfunktion": "C: S -> {1, ..., K}",
        "k": len(profiles),
        "dimensionen": DIMENSIONS,
        "clusterprofile": profiles,
        "zuordnungen": rows,
    }
    out_path = output_dir / "cluster_typenbildung.json"
    out_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    return out_path



def plot_pca_clusters(df: pd.DataFrame, output_dir: Path) -> Path:
    fig, ax = plt.subplots(figsize=(10, 7))
    for cluster_id, subset in df.groupby("cluster_id"):
        ax.scatter(subset["pca_1"], subset["pca_2"], label=f"Cluster {cluster_id}", alpha=0.75)

    for _, row in df.iterrows():
        if "id" in row:
            ax.annotate(str(row["id"]), (row["pca_1"], row["pca_2"]), fontsize=7, alpha=0.7)

    ax.set_title("Cluster im PCA-Raum")
    ax.set_xlabel("PCA 1")
    ax.set_ylabel("PCA 2")
    ax.legend()
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    out_path = output_dir / "cluster_pca.png"
    fig.savefig(out_path, dpi=200)
    plt.close(fig)
    return out_path



def plot_cluster_centers(profiles: List[Dict], output_dir: Path) -> Path:
    fig, ax = plt.subplots(figsize=(11, 6))
    x = np.arange(len(DIMENSIONS))

    for profile in profiles:
        y = [profile["zentrum"][dim] for dim in DIMENSIONS]
        ax.plot(x, y, marker="o", label=f"Cluster {profile['cluster_id']} – {profile['typname']}")

    ax.set_xticks(x)
    ax.set_xticklabels([DIMENSION_LABELS[d] for d in DIMENSIONS], rotation=20)
    ax.set_ylabel("Clusterzentrum")
    ax.set_title("Clusterzentren im FRZK-Raum")
    ax.axhline(0, linewidth=1)
    ax.legend()
    ax.grid(True, alpha=0.3)
    fig.tight_layout()

    out_path = output_dir / "cluster_zentren.png"
    fig.savefig(out_path, dpi=200)
    plt.close(fig)
    return out_path



def main() -> None:
    args = parse_args()
    input_path = Path(args.input)
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    df = load_dataframe(input_path)
    if len(df) < args.k:
        raise ValueError("Anzahl der Datensätze ist kleiner als k.")

    X = df[DIMENSIONS].to_numpy(dtype=float)
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    model = KMeans(n_clusters=args.k, random_state=args.random_state, n_init=20)
    cluster_ids = model.fit_predict(X_scaled)
    centers_original = scaler.inverse_transform(model.cluster_centers_)

    df["cluster_id"] = cluster_ids

    pca = PCA(n_components=2, random_state=args.random_state)
    X_pca = pca.fit_transform(X_scaled)
    df["pca_1"] = X_pca[:, 0]
    df["pca_2"] = X_pca[:, 1]

    profiles = create_cluster_profile(df, centers_original)

    df.to_csv(output_dir / "cluster_zuordnungen.csv", index=False, encoding="utf-8-sig")
    json_path = save_assignment_json(df, profiles, output_dir)
    pca_path = plot_pca_clusters(df, output_dir)
    center_path = plot_cluster_centers(profiles, output_dir)

    print(f"Clusteranalyse abgeschlossen. K = {args.k}")
    print(f"JSON: {json_path.resolve()}")
    print(f"CSV: {(output_dir / 'cluster_zuordnungen.csv').resolve()}")
    print(f"Plot PCA: {pca_path.resolve()}")
    print(f"Plot Zentren: {center_path.resolve()}")

    print("\nClusterprofile:")
    for profile in profiles:
        print(
            f"- Cluster {profile['cluster_id']}: {profile['typname']} "
            f"(n={profile['groesse']}, dominante Dimension={profile['dominante_dimension_label']})"
        )


if __name__ == "__main__":
    main()
