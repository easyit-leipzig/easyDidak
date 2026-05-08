#!/usr/bin/env python3
"""Stellt ein exportiertes FRZK-Korrelations-JSON als Heatmap dar.

Beispiel:
python frzk_korrelations_plot.py \
  --input frzk_korrelationsstruktur.json \
  --output frzk_korrelations_heatmap.png
"""
from __future__ import annotations

import argparse
import json
import math
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np


def main() -> None:
    parser = argparse.ArgumentParser(description="FRZK-Korrelationsmatrix aus JSON visualisieren")
    parser.add_argument("--input", default="frzk_korrelationsstruktur.json")
    parser.add_argument("--output", default="frzk_korrelations_heatmap.png")
    parser.add_argument("--title", default="FRZK – Korrelationsstruktur des Systems")
    args = parser.parse_args()

    payload = json.loads(Path(args.input).read_text(encoding="utf-8"))
    stats = payload["statistik"]
    dims = stats["dimensions"]
    corr = stats["korrelationsmatrix"]

    matrix = np.array([[corr[row][col] for col in dims] for row in dims], dtype=float)

    fig, ax = plt.subplots(figsize=(10, 8))
    im = ax.imshow(matrix, vmin=-1, vmax=1)

    ax.set_xticks(np.arange(len(dims)))
    ax.set_yticks(np.arange(len(dims)))
    ax.set_xticklabels([d.replace("x_", "") for d in dims], rotation=45, ha="right")
    ax.set_yticklabels([d.replace("x_", "") for d in dims])
    ax.set_title(args.title)

    for i in range(len(dims)):
        for j in range(len(dims)):
            value = matrix[i, j]
            label = "nan" if math.isnan(value) else f"{value:.2f}"
            ax.text(j, i, label, ha="center", va="center", fontsize=9)

    cbar = fig.colorbar(im, ax=ax)
    cbar.set_label("Pearson-Korrelation ρ")
    fig.tight_layout()
    fig.savefig(args.output, dpi=300, bbox_inches="tight")

    positives = stats.get("staerkste_positive_kopplungen", [])[:5]
    negatives = stats.get("staerkste_negative_kopplungen", [])[:5]

    print(f"Heatmap gespeichert unter: {Path(args.output).resolve()}")
    print("\nStärkste positive Kopplungen:")
    for item in positives:
        print(f"- {item['dim_a']} ↔ {item['dim_b']}: {item['correlation']:.4f}")

    print("\nStärkste negative Kopplungen:")
    for item in negatives:
        print(f"- {item['dim_a']} ↔ {item['dim_b']}: {item['correlation']:.4f}")


if __name__ == "__main__":
    main()
