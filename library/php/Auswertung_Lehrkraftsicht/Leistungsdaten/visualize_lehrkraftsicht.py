#!/usr/bin/env python3
"""
Importiert das vom Aggregationsskript erzeugte JSON und erzeugt Tabellen/Abbildungen.

Beispiel:
python frzk_visualize_lehrkraftsicht.py \
  --input frzk_leistung_aggregiert.json \
  --output-dir out_frzk
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

import matplotlib.pyplot as plt
import pandas as pd

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Visualisiert aggregierte FRZK-Leistungsdaten aus JSON.")
    parser.add_argument("--input", required=True, help="Pfad zur JSON-Datei aus dem Aggregationsskript.")
    parser.add_argument("--output-dir", default="frzk_output", help="Zielordner für CSV und PNG.")
    return parser.parse_args()


def load_payload(path: Path) -> dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def to_dataframe(payload: dict[str, Any]) -> pd.DataFrame:
    df = pd.DataFrame(payload.get("summary_table", []))
    if df.empty:
        raise SystemExit("Die JSON-Datei enthält keine summary_table.")
    return df


def choose_x_axis(df: pd.DataFrame) -> str:
    for candidate in ["kw", "datum", "gruppe_id", "lehrkraft_id", "teilnehmer_id"]:
        if candidate in df.columns:
            return candidate
    return df.columns[0]


def choose_series_key(df: pd.DataFrame, x_axis: str) -> str | None:
    for candidate in ["gruppe_id", "lehrkraft_id", "teilnehmer_id"]:
        if candidate in df.columns and candidate != x_axis:
            return candidate
    return None


def export_table(df: pd.DataFrame, output_dir: Path) -> Path:
    csv_path = output_dir / "frzk_auswertungstabelle.csv"
    df.to_csv(csv_path, index=False, encoding="utf-8-sig")
    return csv_path


def plot_dimensions(df: pd.DataFrame, x_axis: str, series_key: str | None, output_dir: Path) -> Path:
    plt.figure(figsize=(12, 7))

    if series_key:
        first_value = sorted(df[series_key].dropna().unique().tolist())[0]
        plot_df = df[df[series_key] == first_value].sort_values(x_axis)
        title_suffix = f" ({series_key}={first_value})"
    else:
        plot_df = df.sort_values(x_axis)
        title_suffix = ""

    for dim in DIMENSIONS:
        if dim in plot_df.columns:
            plt.plot(plot_df[x_axis], plot_df[dim], marker="o", label=dim)

    plt.title(f"FRZK-Dimensionsverlauf{title_suffix}")
    plt.xlabel(x_axis)
    plt.ylabel("Mittelwert")
    plt.legend()
    plt.xticks(rotation=45)
    plt.tight_layout()
    path = output_dir / "frzk_dimensionsverlauf.png"
    plt.savefig(path, dpi=200)
    plt.close()
    return path


def plot_indices(df: pd.DataFrame, x_axis: str, series_key: str | None, output_dir: Path) -> Path:
    plt.figure(figsize=(12, 7))

    if series_key:
        first_value = sorted(df[series_key].dropna().unique().tolist())[0]
        plot_df = df[df[series_key] == first_value].sort_values(x_axis)
        title_suffix = f" ({series_key}={first_value})"
    else:
        plot_df = df.sort_values(x_axis)
        title_suffix = ""

    for col in ["d_semantisch_mittel", "kohärenzindex", "stabilitaetsindex", "polarisierungsindex"]:
        if col in plot_df.columns:
            plt.plot(plot_df[x_axis], plot_df[col], marker="o", label=col)

    plt.title(f"Kohärenz-, Stabilitäts- und Polarisierungsverlauf{title_suffix}")
    plt.xlabel(x_axis)
    plt.ylabel("Index")
    plt.legend()
    plt.xticks(rotation=45)
    plt.tight_layout()
    path = output_dir / "frzk_indizes.png"
    plt.savefig(path, dpi=200)
    plt.close()
    return path


def plot_polarity(df: pd.DataFrame, x_axis: str, series_key: str | None, output_dir: Path) -> Path:
    plt.figure(figsize=(12, 7))

    if series_key:
        first_value = sorted(df[series_key].dropna().unique().tolist())[0]
        plot_df = df[df[series_key] == first_value].sort_values(x_axis)
        title_suffix = f" ({series_key}={first_value})"
    else:
        plot_df = df.sort_values(x_axis)
        title_suffix = ""

    for col in ["polaritaet_positiv", "polaritaet_neutral", "polaritaet_negativ"]:
        if col in plot_df.columns:
            plt.plot(plot_df[x_axis], plot_df[col], marker="o", label=col)

    plt.title(f"Polaritätsanteile{title_suffix}")
    plt.xlabel(x_axis)
    plt.ylabel("Anteil")
    plt.legend()
    plt.xticks(rotation=45)
    plt.tight_layout()
    path = output_dir / "frzk_polaritaet.png"
    plt.savefig(path, dpi=200)
    plt.close()
    return path


def main() -> None:
    args = parse_args()
    input_path = Path(args.input)
    output_dir = Path(args.output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    payload = load_payload(input_path)
    df = to_dataframe(payload)

    x_axis = choose_x_axis(df)
    series_key = choose_series_key(df, x_axis)

    table_path = export_table(df, output_dir)
    dim_plot = plot_dimensions(df, x_axis, series_key, output_dir)
    idx_plot = plot_indices(df, x_axis, series_key, output_dir)
    pol_plot = plot_polarity(df, x_axis, series_key, output_dir)

    print(f"Tabelle exportiert: {table_path.resolve()}")
    print(f"Abbildung exportiert: {dim_plot.resolve()}")
    print(f"Abbildung exportiert: {idx_plot.resolve()}")
    print(f"Abbildung exportiert: {pol_plot.resolve()}")


if __name__ == "__main__":
    main()
