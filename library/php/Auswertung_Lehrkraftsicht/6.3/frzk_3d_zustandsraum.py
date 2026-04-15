import json
from pathlib import Path
from typing import Dict, List

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd
from mpl_toolkits.mplot3d import Axes3D  # noqa: F401


DIMS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def load_df(path: str | Path, transformed: bool = False) -> pd.DataFrame:
    path = Path(path)
    with path.open("r", encoding="utf-8") as f:
        payload = json.load(f)

    key = "rohdaten_transformiert" if transformed else "rohdaten"
    if key not in payload:
        raise ValueError(f"Schlüssel '{key}' nicht gefunden in {path}")
    return pd.DataFrame(payload[key]).copy()


def add_pca_projection(df: pd.DataFrame, dims: List[str] = DIMS) -> pd.DataFrame:
    out = df.copy()
    X = out[dims].to_numpy(float)
    X_centered = X - X.mean(axis=0, keepdims=True)

    U, S, Vt = np.linalg.svd(X_centered, full_matrices=False)
    scores = X_centered @ Vt.T[:, :3]

    out["PC1"] = scores[:, 0]
    out["PC2"] = scores[:, 1]
    out["PC3"] = scores[:, 2] if scores.shape[1] > 2 else 0.0
    return out


def plot_3d_state_space(
    datasets: Dict[str, pd.DataFrame],
    title: str,
    output_path: str | Path,
) -> None:
    fig = plt.figure(figsize=(10, 8))
    ax = fig.add_subplot(111, projection="3d")

    for label, df in datasets.items():
        p = add_pca_projection(df)
        ax.scatter(
            p["PC1"], p["PC2"], p["PC3"],
            label=label,
            s=18,
            alpha=0.65,
        )

        centroid = p[["PC1", "PC2", "PC3"]].mean()
        ax.scatter(
            [centroid["PC1"]], [centroid["PC2"]], [centroid["PC3"]],
            s=120,
            marker="X",
            label=f"Zentrum {label}",
        )

    ax.set_title(title)
    ax.set_xlabel("PC1")
    ax.set_ylabel("PC2")
    ax.set_zlabel("PC3")
    ax.legend()
    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()


def plot_3d_single_with_centroid_lines(
    df: pd.DataFrame,
    title: str,
    output_path: str | Path,
    sample_n: int = 120,
) -> None:
    p = add_pca_projection(df)

    if len(p) > sample_n:
        p = p.sample(sample_n, random_state=42)

    centroid = p[["PC1", "PC2", "PC3"]].mean()

    fig = plt.figure(figsize=(10, 8))
    ax = fig.add_subplot(111, projection="3d")

    ax.scatter(p["PC1"], p["PC2"], p["PC3"], s=20, alpha=0.7)
    ax.scatter(
        [centroid["PC1"]], [centroid["PC2"]], [centroid["PC3"]],
        s=150, marker="X"
    )

    for _, row in p.iterrows():
        ax.plot(
            [centroid["PC1"], row["PC1"]],
            [centroid["PC2"], row["PC2"]],
            [centroid["PC3"], row["PC3"]],
            alpha=0.2,
            linewidth=0.8,
        )

    ax.set_title(title)
    ax.set_xlabel("PC1")
    ax.set_ylabel("PC2")
    ax.set_zlabel("PC3")
    plt.tight_layout()
    plt.savefig(output_path, dpi=300)
    plt.close()


if __name__ == "__main__":
    alle = load_df("sem_dichte_auswertung.json", transformed=False)
    nicht1 = load_df("sem_dichte_auswertung - nicht 1.json", transformed=False)
    lehrkraft1 = load_df("sem_dichte_auswertung - 1.json", transformed=False)

    plot_3d_state_space(
        {
            "Alle": alle,
            "Nicht Lehrkraft 1": nicht1,
            "Lehrkraft 1": lehrkraft1,
        },
        title="FRZK-Zustandsraum (Originaldaten)",
        output_path="zustandsraum_3d_original.png",
    )

    alle_a = load_df("alle_alpha10.json", transformed=True)
    nicht1_a = load_df("nicht1_alpha10.json", transformed=True)
    lehrkraft1_a = load_df("lehrkraft1_alpha10.json", transformed=True)

    plot_3d_state_space(
        {
            "Alle α=10": alle_a,
            "Nicht Lehrkraft 1 α=10": nicht1_a,
            "Lehrkraft 1 α=10": lehrkraft1_a,
        },
        title="FRZK-Zustandsraum nach metrischer Streckung (α = 10)",
        output_path="zustandsraum_3d_alpha10.png",
    )

    plot_3d_single_with_centroid_lines(
        lehrkraft1_a,
        title="Lehrkraft 1: 3D-Zustandsraum mit Schwerpunktvektoren (α = 10)",
        output_path="zustandsraum_3d_lehrkraft1_alpha10.png",
    )

    print("3D-Visualisierungen wurden erzeugt.")
