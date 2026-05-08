#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Visualisiert ein bereits exportiertes JSON aus FRZK 6.x.8.
Nützlich, wenn du den DB-Export getrennt vom Plotten fahren willst.
"""

from __future__ import annotations

import json
import argparse
from pathlib import Path

import numpy as np
import matplotlib.pyplot as plt

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

def parse_args():
    p = argparse.ArgumentParser()
    p.add_argument("json_file")
    p.add_argument("--output-dir", default="frzk_6x8_plots_from_json")
    return p.parse_args()

def main():
    args = parse_args()
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    with open(args.json_file, "r", encoding="utf-8") as f:
        data = json.load(f)

    ts = data["zeitreihe"]
    x = np.arange(len(ts))

    fig, ax = plt.subplots(figsize=(14, 7))
    for dim in DIMENSIONS:
        y = [row["S_t"][dim] for row in ts]
        ax.plot(x, y, marker="o", label=dim)
    ax.set_title("FRZK-Zustandsbahnen S(t)")
    ax.set_xlabel("Zeitindex t")
    ax.set_ylabel("Dimensionswert")
    ax.legend()
    ax.grid(True, alpha=0.3)
    fig.tight_layout()
    fig.savefig(output_dir / "zustandsbahnen.png", dpi=200, bbox_inches="tight")
    plt.close(fig)

    fig, ax = plt.subplots(figsize=(12, 5))
    y = [row["d_semantisch"] for row in ts]
    ax.plot(x, y, marker="o")
    ax.set_title("Semantische Dichte d(S_t)")
    ax.set_xlabel("Zeitindex t")
    ax.set_ylabel("d_semantisch")
    ax.grid(True, alpha=0.3)
    fig.tight_layout()
    fig.savefig(output_dir / "semantische_dichte.png", dpi=200, bbox_inches="tight")
    plt.close(fig)

    P = np.array(data["uebergangsmatrix"]["probabilities"], dtype=float)
    states = data["uebergangsmatrix"]["states"]

    fig, ax = plt.subplots(figsize=(8, 7))
    im = ax.imshow(P)
    ax.set_title("Übergangsmatrix P_ab")
    ax.set_xticks(range(len(states)))
    ax.set_yticks(range(len(states)))
    ax.set_xticklabels(states, rotation=45, ha="right")
    ax.set_yticklabels(states)
    for i in range(P.shape[0]):
        for j in range(P.shape[1]):
            ax.text(j, i, f"{P[i, j]:.2f}", ha="center", va="center")
    fig.colorbar(im, ax=ax)
    fig.tight_layout()
    fig.savefig(output_dir / "uebergangsmatrix.png", dpi=200, bbox_inches="tight")
    plt.close(fig)

    print(f"Plots gespeichert in: {output_dir.resolve()}")

if __name__ == "__main__":
    main()
