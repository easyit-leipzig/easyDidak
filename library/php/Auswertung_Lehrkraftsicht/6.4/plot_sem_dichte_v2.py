#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
plot_sem_dichte.py

Grafische Darstellung für JSON-Dateien aus aggregate_sem_dichte.py

Erzeugt:
- 01_dimensionsverlauf.png
- 02_semantische_dichte.png
- 03_polaritaet.png
- 04_dominanz.png
- 00_readme.txt

Beispiel:
python plot_sem_dichte.py --input sem_dichte_aggregation.json --x kw --outdir plots_sem_dichte
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

import matplotlib.pyplot as plt


DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DOM_DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Erzeugt Diagramme aus aggregierten FRZK-JSON-Daten.")
    parser.add_argument("--input", required=True, help="JSON-Datei aus aggregate_sem_dichte.py")
    parser.add_argument("--x", default=None, help="Feld für die x-Achse, z. B. kw, datum oder gruppe_id")
    parser.add_argument("--outdir", default="plots_sem_dichte", help="Ausgabeordner")
    parser.add_argument(
        "--title-prefix",
        default="",
        help="Optionaler Präfix für Diagrammtitel",
    )
    return parser.parse_args()


def load_json(path: str) -> dict[str, Any]:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def choose_x_field(payload: dict[str, Any], explicit_x: str | None) -> str:
    if explicit_x:
        return explicit_x

    group_by = payload.get("meta", {}).get("group_by", [])
    if not group_by:
        raise ValueError("Kein x-Feld angegeben und keine group_by-Information im JSON gefunden.")
    return group_by[0]


def sort_rows(rows: list[dict[str, Any]], x_field: str) -> list[dict[str, Any]]:
    def sort_key(row: dict[str, Any]) -> Any:
        value = row.get(x_field)

        if value is None:
            return (2, "")

        try:
            return (0, float(value))
        except (TypeError, ValueError):
            return (1, str(value))

    return sorted(rows, key=sort_key)


def get_x_values(rows: list[dict[str, Any]], x_field: str) -> list[Any]:
    return [row.get(x_field) for row in rows]


def title_with_prefix(prefix: str, title: str) -> str:
    prefix = prefix.strip()
    return f"{prefix} – {title}" if prefix else title


def save_readme(payload: dict[str, Any], outdir: Path) -> Path:
    meta = payload.get("meta", {})
    lines = [
        "Auswertung datenm_values_sem_dichte_lehrer_type_3",
        "==============================================",
        f"Quelle: {meta.get('source_table')}",
        f"Gruppierung: {meta.get('group_by')}",
        f"Filter: {meta.get('filters')}",
        f"Zeilenzahl: {meta.get('row_count')}",
        "",
        "Diagramme:",
        "01_dimensionsverlauf.png  -> Mittelwerte der sieben Dimensionen",
        "02_semantische_dichte.png -> mittlere semantische Dichte",
        "03_polaritaet.png         -> positive / negative / neutrale Polarität",
        "04_dominanz.png           -> Häufigkeit dominanter Dimensionen",
    ]
    outpath = outdir / "00_readme.txt"
    outpath.write_text("\n".join(lines), encoding="utf-8")
    return outpath


def save_dimension_plot(rows: list[dict[str, Any]], x_field: str, outdir: Path, title_prefix: str) -> Path:
    x = get_x_values(rows, x_field)

    plt.figure(figsize=(13, 7))
    for dim in DIMENSIONS:
        y = [row.get(f"avg_{dim}") for row in rows]
        plt.plot(x, y, marker="o", label=dim.replace("x_", ""))

    plt.xlabel(x_field)
    plt.ylabel("Mittelwert")
    plt.title(title_with_prefix(title_prefix, "Mittlere Dimensionswerte"))
    plt.xticks(rotation=45, ha="right")
    plt.legend()
    plt.tight_layout()

    outpath = outdir / "01_dimensionsverlauf.png"
    plt.savefig(outpath, dpi=200, bbox_inches="tight")
    plt.close()
    return outpath


def save_density_plot(rows: list[dict[str, Any]], x_field: str, outdir: Path, title_prefix: str) -> Path:
    x = get_x_values(rows, x_field)
    y = [row.get("avg_d_semantisch") for row in rows]

    plt.figure(figsize=(13, 7))
    plt.plot(x, y, marker="o")
    plt.xlabel(x_field)
    plt.ylabel("Ø d_semantisch")
    plt.title(title_with_prefix(title_prefix, "Mittlere semantische Dichte"))
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()

    outpath = outdir / "02_semantische_dichte.png"
    plt.savefig(outpath, dpi=200, bbox_inches="tight")
    plt.close()
    return outpath


def save_polarity_plot(rows: list[dict[str, Any]], x_field: str, outdir: Path, title_prefix: str) -> Path:
    x = [str(v) for v in get_x_values(rows, x_field)]
    pos = [row.get("n_pos", 0) for row in rows]
    neg = [row.get("n_neg", 0) for row in rows]
    neutral = [row.get("n_neutral", 0) for row in rows]

    plt.figure(figsize=(13, 7))
    plt.plot(x, pos, marker="o", label="positiv")
    plt.plot(x, neg, marker="o", label="negativ")
    plt.plot(x, neutral, marker="o", label="neutral")
    plt.xlabel(x_field)
    plt.ylabel("Anzahl")
    plt.title(title_with_prefix(title_prefix, "Polaritätsverteilung"))
    plt.xticks(rotation=45, ha="right")
    plt.legend()
    plt.tight_layout()

    outpath = outdir / "03_polaritaet.png"
    plt.savefig(outpath, dpi=200, bbox_inches="tight")
    plt.close()
    return outpath


def save_dominance_plot(rows: list[dict[str, Any]], x_field: str, outdir: Path, title_prefix: str) -> Path:
    x = [str(v) for v in get_x_values(rows, x_field)]

    plt.figure(figsize=(13, 7))
    for dom in DOM_DIMENSIONS:
        y = [row.get(f"dom_{dom}", 0) for row in rows]
        plt.plot(x, y, marker="o", label=dom)

    plt.xlabel(x_field)
    plt.ylabel("Anzahl")
    plt.title(title_with_prefix(title_prefix, "Dominante Dimensionen"))
    plt.xticks(rotation=45, ha="right")
    plt.legend()
    plt.tight_layout()

    outpath = outdir / "04_dominanz.png"
    plt.savefig(outpath, dpi=200, bbox_inches="tight")
    plt.close()
    return outpath


def main() -> None:
    args = parse_args()

    payload = load_json(args.input)
    rows = payload.get("data", [])
    if not rows:
        raise ValueError("Die JSON-Datei enthält keine Daten in 'data'.")

    x_field = choose_x_field(payload, args.x)
    rows = sort_rows(rows, x_field)

    outdir = Path(args.outdir)
    outdir.mkdir(parents=True, exist_ok=True)

    created = [
        save_readme(payload, outdir),
        save_dimension_plot(rows, x_field, outdir, args.title_prefix),
        save_density_plot(rows, x_field, outdir, args.title_prefix),
        save_polarity_plot(rows, x_field, outdir, args.title_prefix),
        save_dominance_plot(rows, x_field, outdir, args.title_prefix),
    ]

    print("Erzeugte Dateien:")
    for path in created:
        print(path)


if __name__ == "__main__":
    main()
