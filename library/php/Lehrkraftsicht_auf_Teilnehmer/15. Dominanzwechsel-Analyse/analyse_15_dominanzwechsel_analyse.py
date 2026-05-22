# -*- coding: utf-8 -*-
"""
15. Dominanzwechsel-Analyse – Analyse-/Visualisierungsskript

Liest auswertung_15_dominanzwechsel.json und erzeugt:
- Konsolenauswertung
- CSV-Tabellen
- Markov-Heatmaps
- Balkendiagramme der dominanten Dimensionen
- Balkendiagramme der häufigsten Dominanzwechsel
- Zeitreihenplots der dominanten Dimension je Scope
"""

from __future__ import annotations

import csv
import json
from pathlib import Path
from typing import Any, Dict, List

import matplotlib.pyplot as plt
import numpy as np

INPUT_JSON = Path("auswertung_15_dominanzwechsel.json")
OUTPUT_DIR = Path("auswertung_15_dominanzwechsel_output")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

DIM_TO_NUM = {dim: i for i, dim in enumerate(DIMENSIONS)}


def load_data(path: Path) -> Dict[str, Any]:
    if not path.exists():
        raise FileNotFoundError(f"JSON-Datei nicht gefunden: {path.resolve()}")
    return json.loads(path.read_text(encoding="utf-8"))


def ensure_output_dir() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)


def save_csv(path: Path, rows: List[Dict[str, Any]], fieldnames: List[str]) -> None:
    with path.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, extrasaction="ignore", delimiter=";")
        writer.writeheader()
        writer.writerows(rows)


def plot_markov_heatmap(scope_name: str, matrix: Dict[str, Dict[str, float]]) -> None:
    arr = np.array([[matrix.get(fr, {}).get(to, 0.0) for to in DIMENSIONS] for fr in DIMENSIONS])
    fig, ax = plt.subplots(figsize=(9, 7))
    im = ax.imshow(arr, aspect="auto")
    ax.set_xticks(range(len(DIMENSIONS)))
    ax.set_yticks(range(len(DIMENSIONS)))
    ax.set_xticklabels(DIMENSIONS, rotation=45, ha="right")
    ax.set_yticklabels(DIMENSIONS)
    ax.set_xlabel("D(t+1)")
    ax.set_ylabel("D(t)")
    ax.set_title(f"Markov-Übergangsmatrix der dominanten Dimension – {scope_name}")
    for i in range(arr.shape[0]):
        for j in range(arr.shape[1]):
            ax.text(j, i, f"{arr[i, j]:.2f}", ha="center", va="center", fontsize=8)
    fig.colorbar(im, ax=ax, label="P(D(t+1) | D(t))")
    fig.tight_layout()
    fig.savefig(OUTPUT_DIR / f"15_markov_matrix_{scope_name}.png", dpi=200)
    plt.close(fig)


def plot_dimension_counts(scope_name: str, counts: Dict[str, int]) -> None:
    values = [counts.get(dim, 0) for dim in DIMENSIONS]
    fig, ax = plt.subplots(figsize=(9, 5))
    ax.bar(DIMENSIONS, values)
    ax.set_title(f"Häufigkeit dominanter Dimensionen – {scope_name}")
    ax.set_ylabel("Anzahl Datensätze")
    ax.tick_params(axis="x", rotation=45)
    fig.tight_layout()
    fig.savefig(OUTPUT_DIR / f"15_dimension_counts_{scope_name}.png", dpi=200)
    plt.close(fig)


def plot_top_changes(scope_name: str, top_changes: List[List[Any]]) -> None:
    if not top_changes:
        return
    labels = [item[0] for item in top_changes[:15]]
    values = [item[1] for item in top_changes[:15]]
    fig, ax = plt.subplots(figsize=(10, 6))
    y = np.arange(len(labels))
    ax.barh(y, values)
    ax.set_yticks(y)
    ax.set_yticklabels(labels)
    ax.invert_yaxis()
    ax.set_xlabel("Anzahl")
    ax.set_title(f"Häufigste echte Dominanzwechsel – {scope_name}")
    fig.tight_layout()
    fig.savefig(OUTPUT_DIR / f"15_top_dominanzwechsel_{scope_name}.png", dpi=200)
    plt.close(fig)


def plot_sequence_timeline(scope_name: str, records: List[Dict[str, Any]]) -> None:
    if not records:
        return

    # Aggregierte Zeitreihe: Pro Datum wird die häufigste dominante Dimension eingetragen.
    per_date: Dict[str, Dict[str, int]] = {}
    for r in records:
        d = str(r.get("datum") or "")[:10]
        dim = r.get("dominante_dimension")
        if not d or dim not in DIM_TO_NUM:
            continue
        per_date.setdefault(d, {x: 0 for x in DIMENSIONS})[dim] += 1

    if not per_date:
        return

    dates = sorted(per_date.keys())
    dominant_by_date = []
    for d in dates:
        counts = per_date[d]
        dominant = max(DIMENSIONS, key=lambda x: counts.get(x, 0))
        dominant_by_date.append(DIM_TO_NUM[dominant])

    fig, ax = plt.subplots(figsize=(12, 5))
    ax.plot(range(len(dates)), dominant_by_date, marker="o")
    ax.set_yticks(range(len(DIMENSIONS)))
    ax.set_yticklabels(DIMENSIONS)
    ax.set_xticks(range(len(dates)))
    ax.set_xticklabels(dates, rotation=60, ha="right")
    ax.set_title(f"Zeitlicher Verlauf der dominanten Tagesdimension – {scope_name}")
    ax.set_xlabel("Datum")
    ax.set_ylabel("Dominante Dimension")
    fig.tight_layout()
    fig.savefig(OUTPUT_DIR / f"15_dominanz_timeline_{scope_name}.png", dpi=200)
    plt.close(fig)


def write_scope_tables(scope_name: str, scope: Dict[str, Any]) -> None:
    summary = scope["summary"]

    transitions = scope.get("transitions", [])
    save_csv(
        OUTPUT_DIR / f"15_transitions_{scope_name}.csv",
        transitions,
        [
            "from",
            "to",
            "is_change",
            "from_date",
            "to_date",
            "from_record_id",
            "to_record_id",
            "from_sentence_id",
            "to_sentence_id",
            "from_value",
            "to_value",
            "delta_dominance_value",
            "from_polarity",
            "to_polarity",
            "polarity_change",
            "from_d_semantisch",
            "to_d_semantisch",
            "delta_d_semantisch",
            "from_operator_count",
            "to_operator_count",
            "from_has_operator",
            "to_has_operator",
        ],
    )

    matrix_rows = []
    for fr in DIMENSIONS:
        row = {"from": fr}
        row.update(summary["markov_probabilities"].get(fr, {}))
        matrix_rows.append(row)
    save_csv(OUTPUT_DIR / f"15_markov_probabilities_{scope_name}.csv", matrix_rows, ["from", *DIMENSIONS])

    count_rows = []
    for fr in DIMENSIONS:
        row = {"from": fr}
        row.update(summary["markov_counts"].get(fr, {}))
        count_rows.append(row)
    save_csv(OUTPUT_DIR / f"15_markov_counts_{scope_name}.csv", count_rows, ["from", *DIMENSIONS])


def print_interpretation(scope_name: str, summary: Dict[str, Any]) -> None:
    print("\n" + "=" * 80)
    print(f"SCOPE: {scope_name}")
    print("=" * 80)
    print(f"Datensätze: {summary['n_records']}")
    print(f"Sequenzen: {summary['n_sequences']}")
    print(f"Übergänge: {summary['n_transitions']}")
    print(f"Echte Dominanzwechsel: {summary['n_changes']}")
    print(f"Stabile Übergänge: {summary['n_stays']}")
    print(f"Wechselquote: {summary['change_rate']:.3f}")
    print(f"Stabilitätsquote: {summary['stability_rate']:.3f}")

    print("\nDominante Dimensionen:")
    for dim, count in sorted(summary["dimension_counts"].items(), key=lambda x: x[1], reverse=True):
        print(f"  {dim:12s} {count:5d}")

    print("\nHäufigste echte Dominanzwechsel:")
    for trans, count in summary.get("top_changes", [])[:10]:
        print(f"  {trans:28s} {count:5d}")

    if summary["change_rate"] >= 0.60:
        interpretation = "hohe semantische Beweglichkeit / starke adaptive Steuerung"
    elif summary["change_rate"] >= 0.35:
        interpretation = "mittlere Beweglichkeit / ausgewogenes Verhältnis aus Stabilität und Wechsel"
    else:
        interpretation = "hohe Stabilität / geringe Dominanzverschiebung"
    print(f"\nFRZK-Deutung: {interpretation}")


def main() -> None:
    ensure_output_dir()
    data = load_data(INPUT_JSON)

    print(f"Analyse: {data.get('title', 'Dominanzwechsel-Analyse')}")
    print(f"Quelle: {data.get('source_view')} | Datenbank: {data.get('database')}")

    for scope_name, scope in data.get("scopes", {}).items():
        summary = scope["summary"]
        print_interpretation(scope_name, summary)
        write_scope_tables(scope_name, scope)
        plot_markov_heatmap(scope_name, summary["markov_probabilities"])
        plot_dimension_counts(scope_name, summary["dimension_counts"])
        plot_top_changes(scope_name, summary.get("top_changes", []))
        plot_sequence_timeline(scope_name, scope.get("records", []))

    print("\nAusgabeordner:", OUTPUT_DIR.resolve())
    print("Erzeugte Kerngrafiken je Scope:")
    print("- 15_markov_matrix_<scope>.png")
    print("- 15_dimension_counts_<scope>.png")
    print("- 15_top_dominanzwechsel_<scope>.png")
    print("- 15_dominanz_timeline_<scope>.png")


if __name__ == "__main__":
    main()
