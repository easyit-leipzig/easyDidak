#!/usr/bin/env python3
"""
Liest die exportierte Korrelations-JSON für Kapitel 6.x.6 ein und erzeugt
zwei Grafiken:
1. eine Heatmap der Korrelationsmatrix
2. ein Netzwerkdiagramm funktionaler Kopplungen
"""

from __future__ import annotations

import argparse
import json
import math
from pathlib import Path
from typing import Any, Dict, List, Tuple

import matplotlib.pyplot as plt
import networkx as nx
import numpy as np


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Visualisiert die Korrelationsstruktur aus einer JSON-Datei."
    )
    parser.add_argument(
        "--input",
        default="korrelationsstruktur_lehrkraftsicht.json",
        help="Pfad zur JSON-Datei aus dem Exportskript",
    )
    parser.add_argument(
        "--heatmap-output",
        default="korrelationsmatrix_heatmap.png",
        help="Ausgabepfad der Heatmap",
    )
    parser.add_argument(
        "--netzwerk-output",
        default="korrelationsnetzwerk.png",
        help="Ausgabepfad des Netzwerkdiagramms",
    )
    parser.add_argument(
        "--schwelle",
        type=float,
        default=0.30,
        help="Minimale absolute Korrelation für eine Kante im Netzwerk",
    )
    parser.add_argument(
        "--top-n",
        type=int,
        default=10,
        help="Zusätzlich auszugebende Zahl der stärksten Kopplungen",
    )
    return parser.parse_args()


def load_json(path: Path) -> Dict[str, Any]:
    with path.open("r", encoding="utf-8") as f:
        return json.load(f)


def create_heatmap(dimensions: List[str], matrix: List[List[float]], output_path: Path) -> None:
    arr = np.array(matrix, dtype=float)
    fig, ax = plt.subplots(figsize=(9, 7))
    im = ax.imshow(arr, vmin=-1, vmax=1)

    ax.set_xticks(np.arange(len(dimensions)))
    ax.set_yticks(np.arange(len(dimensions)))
    ax.set_xticklabels(dimensions, rotation=45, ha="right")
    ax.set_yticklabels(dimensions)
    ax.set_title("Korrelationsmatrix des FRZK-Systems")

    for i in range(arr.shape[0]):
        for j in range(arr.shape[1]):
            value = arr[i, j]
            label = "n/a" if np.isnan(value) else f"{value:.2f}"
            ax.text(j, i, label, ha="center", va="center", fontsize=9)

    fig.colorbar(im, ax=ax, fraction=0.046, pad=0.04, label="ρ")
    fig.tight_layout()
    output_path.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(output_path, dpi=300, bbox_inches="tight")
    plt.close(fig)


def create_network(pairwise: List[Dict[str, Any]], threshold: float, output_path: Path) -> None:
    graph = nx.Graph()

    dimensions = set()
    for item in pairwise:
        d1 = item["dimension_1"]
        d2 = item["dimension_2"]
        dimensions.add(d1)
        dimensions.add(d2)

    for dim in sorted(dimensions):
        graph.add_node(dim)

    for item in pairwise:
        d1 = item["dimension_1"]
        d2 = item["dimension_2"]
        rho = item.get("rho")
        if d1 == d2 or rho is None:
            continue
        if abs(rho) >= threshold:
            graph.add_edge(d1, d2, weight=abs(rho), rho=rho)

    fig, ax = plt.subplots(figsize=(10, 8))

    if graph.number_of_edges() == 0:
        ax.text(
            0.5,
            0.5,
            f"Keine Korrelationen mit |ρ| ≥ {threshold:.2f} gefunden.",
            ha="center",
            va="center",
            fontsize=12,
        )
        ax.axis("off")
    else:
        pos = nx.spring_layout(graph, seed=42, weight="weight")
        edge_widths = [1.0 + 6.0 * graph[u][v]["weight"] for u, v in graph.edges()]
        edge_labels = {(u, v): f"{graph[u][v]['rho']:.2f}" for u, v in graph.edges()}

        nx.draw_networkx_nodes(graph, pos, ax=ax, node_size=2200)
        nx.draw_networkx_labels(graph, pos, ax=ax, font_size=10)
        nx.draw_networkx_edges(graph, pos, ax=ax, width=edge_widths)
        nx.draw_networkx_edge_labels(graph, pos, edge_labels=edge_labels, ax=ax, font_size=9)
        ax.set_title(f"Funktionale Kopplungen mit |ρ| ≥ {threshold:.2f}")
        ax.axis("off")

    fig.tight_layout()
    output_path.parent.mkdir(parents=True, exist_ok=True)
    fig.savefig(output_path, dpi=300, bbox_inches="tight")
    plt.close(fig)


def print_top_couplings(couplings: List[Dict[str, Any]], top_n: int) -> None:
    print("Stärkste funktionale Kopplungen:")
    for item in couplings[:top_n]:
        rho = item.get("rho")
        rho_text = "n/a" if rho is None else f"{rho:.4f}"
        print(f"- {item['dimension_1']} ↔ {item['dimension_2']}: rho = {rho_text}")


def main() -> None:
    args = parse_args()
    data = load_json(Path(args.input))

    matrix_info = data["auswertung"]["korrelationsmatrix"]
    pairwise = data["auswertung"]["stärkste_kopplungen_absteigend"]

    dimensions: List[str] = matrix_info["dimensionen"]
    matrix: List[List[float]] = matrix_info["werte"]

    create_heatmap(dimensions, matrix, Path(args.heatmap_output))
    create_network(data["auswertung"]["paarweise_korrelationen"], args.schwelle, Path(args.netzwerk_output))
    print_top_couplings(pairwise, args.top_n)

    print(f"Heatmap gespeichert unter: {Path(args.heatmap_output).resolve()}")
    print(f"Netzwerk gespeichert unter: {Path(args.netzwerk_output).resolve()}")


if __name__ == "__main__":
    main()
