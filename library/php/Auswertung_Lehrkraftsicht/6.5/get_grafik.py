# -*- coding: utf-8 -*-
"""
plot_semantische_dichte.py

Liest die von generate_semantische_dichte_json.py erzeugte JSON-Datei
und erstellt Grafiken für Kapitel 6.x.5.

Voraussetzungen:
pip install pandas matplotlib
"""

from __future__ import annotations

import json
from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd

INPUT_FILE = "semantische_dichte_auswertung_lehrkraftsicht.json"
OUTPUT_DIR = "plots_semantische_dichte"


def ensure_output_dir() -> Path:
    out = Path(OUTPUT_DIR)
    out.mkdir(parents=True, exist_ok=True)
    return out


def load_json() -> dict:
    path = Path(INPUT_FILE)
    if not path.exists():
        raise FileNotFoundError(
            f"Datei nicht gefunden: {path.resolve()}\n"
            "Bitte zuerst generate_semantische_dichte_json.py ausführen."
        )
    return json.loads(path.read_text(encoding="utf-8"))


def save_fig(path: Path) -> None:
    plt.tight_layout()
    plt.savefig(path, dpi=300, bbox_inches="tight")
    plt.close()


def plot_overall_density_over_time(df_raw: pd.DataFrame, outdir: Path) -> None:
    df = df_raw.copy()
    df["datum"] = pd.to_datetime(df["datum"])
    df = df.sort_values("datum")

    plt.figure(figsize=(12, 5))
    plt.plot(df["datum"], df["d_semantisch"], marker="o")
    plt.title("Semantische Dichte über die Zeit")
    plt.xlabel("Datum")
    plt.ylabel("d_semantisch")
    plt.grid(True, alpha=0.3)
    save_fig(outdir / "01_semantische_dichte_zeitverlauf.png")


def plot_weekly_mean_density(df_weekly: pd.DataFrame, outdir: Path) -> None:
    df = df_weekly.copy()
    df = df.sort_values(["jahr", "kw"])

    plt.figure(figsize=(12, 5))
    plt.plot(df["jahr_kw"], df["d_semantisch_mean"], marker="o")
    plt.title("Wöchentlicher Mittelwert der semantischen Dichte")
    plt.xlabel("Jahr-KW")
    plt.ylabel("mittlere d_semantisch")
    plt.xticks(rotation=45, ha="right")
    plt.grid(True, alpha=0.3)
    save_fig(outdir / "02_woechentlicher_mittelwert.png")


def plot_density_histogram(df_raw: pd.DataFrame, outdir: Path) -> None:
    plt.figure(figsize=(8, 5))
    plt.hist(df_raw["d_semantisch"].dropna(), bins=20)
    plt.title("Verteilung der semantischen Dichte")
    plt.xlabel("d_semantisch")
    plt.ylabel("Häufigkeit")
    plt.grid(True, alpha=0.3)
    save_fig(outdir / "03_histogramm_dichte.png")


def plot_group_weekly_lines(df_group_week: pd.DataFrame, outdir: Path) -> None:
    df = df_group_week.copy()
    df = df.sort_values(["gruppe_id", "jahr", "kw"])

    plt.figure(figsize=(13, 6))
    for gruppe_id, sub in df.groupby("gruppe_id"):
        plt.plot(sub["jahr_kw"], sub["d_semantisch_mean"], marker="o", label=f"Gruppe {gruppe_id}")

    plt.title("Wöchentliche semantische Dichte nach Gruppen")
    plt.xlabel("Jahr-KW")
    plt.ylabel("mittlere d_semantisch")
    plt.xticks(rotation=45, ha="right")
    plt.grid(True, alpha=0.3)
    plt.legend(ncol=2, fontsize=8)
    save_fig(outdir / "04_gruppenverlaeufe_wochen.png")


def plot_dimension_means(df_weekly: pd.DataFrame, outdir: Path) -> None:
    df = df_weekly.copy()
    df = df.sort_values(["jahr", "kw"])

    dims = [
        "x_kognition_mean",
        "x_sozial_mean",
        "x_affektiv_mean",
        "x_motivation_mean",
        "x_methodik_mean",
        "x_performanz_mean",
        "x_regulation_mean",
    ]

    plt.figure(figsize=(13, 6))
    for dim in dims:
        plt.plot(df["jahr_kw"], df[dim], marker="o", label=dim.replace("_mean", ""))

    plt.title("Wöchentliche Mittelwerte der sieben FRZK-Dimensionen")
    plt.xlabel("Jahr-KW")
    plt.ylabel("mittlerer Dimensionswert")
    plt.xticks(rotation=45, ha="right")
    plt.grid(True, alpha=0.3)
    plt.legend(ncol=2, fontsize=8)
    save_fig(outdir / "05_dimensionen_zeitverlauf.png")


def plot_teacher_bar(df_teacher: pd.DataFrame, outdir: Path) -> None:
    df = df_teacher.copy().sort_values("lehrkraft_id")

    plt.figure(figsize=(10, 5))
    plt.bar(df["lehrkraft_id"].astype(str), df["d_semantisch_mean"])
    plt.title("Mittlere semantische Dichte nach Lehrkraft")
    plt.xlabel("Lehrkraft-ID")
    plt.ylabel("mittlere d_semantisch")
    plt.grid(True, axis="y", alpha=0.3)
    save_fig(outdir / "06_lehrkraftvergleich.png")


def plot_subject_bar(df_subject: pd.DataFrame, outdir: Path) -> None:
    df = df_subject.copy().sort_values("fach")

    plt.figure(figsize=(8, 5))
    plt.bar(df["fach"].astype(str), df["d_semantisch_mean"])
    plt.title("Mittlere semantische Dichte nach Fach")
    plt.xlabel("Fach")
    plt.ylabel("mittlere d_semantisch")
    plt.grid(True, axis="y", alpha=0.3)
    save_fig(outdir / "07_fachvergleich.png")


def plot_density_vs_polarity(df_raw: pd.DataFrame, outdir: Path) -> None:
    df = df_raw.copy()

    plt.figure(figsize=(8, 5))
    plt.scatter(df["polaritaet_gesamt"], df["d_semantisch"])
    plt.title("Semantische Dichte in Relation zur Polarität")
    plt.xlabel("polaritaet_gesamt")
    plt.ylabel("d_semantisch")
    plt.grid(True, alpha=0.3)
    save_fig(outdir / "08_dichte_vs_polaritaet.png")


def main() -> None:
    payload = load_json()
    outdir = ensure_output_dir()

    df_raw = pd.DataFrame(payload["rohdaten"])
    df_weekly = pd.DataFrame(payload["wochenaggregation"])
    df_group_week = pd.DataFrame(payload["gruppen_wochenaggregation"])
    df_teacher = pd.DataFrame(payload["lehrkraftaggregation"])
    df_subject = pd.DataFrame(payload["fachaggregation"])

    plot_overall_density_over_time(df_raw, outdir)
    plot_weekly_mean_density(df_weekly, outdir)
    plot_density_histogram(df_raw, outdir)
    plot_group_weekly_lines(df_group_week, outdir)
    plot_dimension_means(df_weekly, outdir)
    plot_teacher_bar(df_teacher, outdir)
    plot_subject_bar(df_subject, outdir)
    plot_density_vs_polarity(df_raw, outdir)

    print(f"Grafiken erfolgreich gespeichert unter: {outdir.resolve()}")


if __name__ == "__main__":
    main()