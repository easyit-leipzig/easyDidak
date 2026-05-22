# -*- coding: utf-8 -*-
"""
Auswertungspunkt 23: Didaktische Interventionssensitivitaet
Analyse- und Visualisierungsskript

Liest die JSON-Datei aus export_23_didaktische_interventionssensitivitaet.py
und erzeugt:
- CSV-Ranking pro Scope
- CSV-Ereignisliste pro Scope
- Balkendiagramm Effektstaerke
- Balkendiagramm Dichteveraenderung
- Heatmap Dimensionseffekte
- Textreport

Voraussetzung:
    pip install pandas matplotlib numpy
"""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any, Dict, List

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

INPUT_JSON = Path("auswertung_23_didaktische_interventionssensitivitaet.json")
OUTPUT_DIR = Path("auswertung_23_didaktische_interventionssensitivitaet_output")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]


def load_data() -> Dict[str, Any]:
    if not INPUT_JSON.exists():
        raise FileNotFoundError(
            f"{INPUT_JSON} nicht gefunden. Bitte zuerst das Export-Skript ausführen."
        )
    return json.loads(INPUT_JSON.read_text(encoding="utf-8"))


def ensure_dir(path: Path) -> None:
    path.mkdir(parents=True, exist_ok=True)


def ranking_dataframe(scope_data: Dict[str, Any]) -> pd.DataFrame:
    rows = []
    for item in scope_data.get("ranking_by_effect_strength", []):
        row = {
            "intervention": item.get("intervention"),
            "n": item.get("n", 0),
            "mean_delta_norm": item.get("mean_delta_norm", 0),
            "mean_abs_delta_dichte": item.get("mean_abs_delta_dichte", 0),
            "mean_delta_dichte": item.get("mean_delta_dichte", 0),
            "mean_cosine_shift": item.get("mean_cosine_shift"),
            "dominanzwechsel_rate": item.get("dominanzwechsel_rate", 0),
            "polaritaetswechsel_rate": item.get("polaritaetswechsel_rate", 0),
        }
        for d in DIMENSIONS:
            row[f"delta_{d}"] = item.get("mean_dimension_delta", {}).get(d, 0)
        rows.append(row)
    return pd.DataFrame(rows)


def events_dataframe(scope_data: Dict[str, Any]) -> pd.DataFrame:
    rows = []
    for event in scope_data.get("events", []):
        base = {
            "datum": event.get("datum"),
            "lehrkraft_id": event.get("lehrkraft_id"),
            "gruppe_id": event.get("gruppe_id"),
            "teilnehmer_id": event.get("teilnehmer_id"),
            "id_mtr_rueckkopplung_datenmaske": event.get("id_mtr_rueckkopplung_datenmaske"),
            "mtr_rueckkopplung_datenmaske_values_id": event.get("mtr_rueckkopplung_datenmaske_values_id"),
            "thema": event.get("thema"),
            "interventions": ", ".join(event.get("interventions", [])),
            "delta_norm": event.get("delta_norm"),
            "delta_dichte": event.get("delta_dichte"),
            "cosine_shift": event.get("cosine_shift"),
            "dominanzwechsel": event.get("dominanzwechsel"),
            "polaritaetswechsel": event.get("polaritaetswechsel"),
            "dominante_dimension_vorher": event.get("dominante_dimension_vorher"),
            "dominante_dimension_nachher": event.get("dominante_dimension_nachher"),
            "bemerkung": event.get("bemerkung"),
        }
        for d in DIMENSIONS:
            base[f"delta_{d}"] = event.get("dimension_delta", {}).get(d, 0)
        rows.append(base)
    return pd.DataFrame(rows)


def plot_effect_strength(df: pd.DataFrame, scope: str, outdir: Path) -> None:
    if df.empty:
        return
    plot_df = df.sort_values("mean_delta_norm", ascending=True)
    plt.figure(figsize=(10, 6))
    plt.barh(plot_df["intervention"], plot_df["mean_delta_norm"])
    plt.xlabel("mittlere Zustandsänderung ||ΔV||")
    plt.ylabel("Intervention")
    plt.title(f"Auswertung 23 – Effektstärke pro Intervention ({scope})")
    plt.tight_layout()
    plt.savefig(outdir / f"23_{scope}_effektstaerke_delta_norm.png", dpi=200)
    plt.close()


def plot_density_change(df: pd.DataFrame, scope: str, outdir: Path) -> None:
    if df.empty:
        return
    plot_df = df.sort_values("mean_abs_delta_dichte", ascending=True)
    plt.figure(figsize=(10, 6))
    plt.barh(plot_df["intervention"], plot_df["mean_abs_delta_dichte"])
    plt.xlabel("mittlere absolute Dichteänderung |Δd|")
    plt.ylabel("Intervention")
    plt.title(f"Auswertung 23 – Dichtesensitivität pro Intervention ({scope})")
    plt.tight_layout()
    plt.savefig(outdir / f"23_{scope}_dichtesensitivitaet.png", dpi=200)
    plt.close()


def plot_dimension_heatmap(df: pd.DataFrame, scope: str, outdir: Path) -> None:
    if df.empty:
        return
    matrix = df[[f"delta_{d}" for d in DIMENSIONS]].to_numpy(dtype=float)
    labels = df["intervention"].tolist()

    plt.figure(figsize=(11, max(4, 0.55 * len(labels))))
    plt.imshow(matrix, aspect="auto")
    plt.yticks(np.arange(len(labels)), labels)
    plt.xticks(np.arange(len(DIMENSIONS)), DIMENSIONS, rotation=45, ha="right")
    plt.colorbar(label="mittlere Dimensionsänderung Δx")
    plt.title(f"Auswertung 23 – Dimensionale Interventionswirkung ({scope})")
    plt.tight_layout()
    plt.savefig(outdir / f"23_{scope}_dimensionseffekte_heatmap.png", dpi=200)
    plt.close()


def write_report(data: Dict[str, Any], report_parts: List[str], outdir: Path) -> None:
    text = "\n\n".join(report_parts)
    (outdir / "bericht_23_didaktische_interventionssensitivitaet.txt").write_text(text, encoding="utf-8")


def main() -> None:
    data = load_data()
    ensure_dir(OUTPUT_DIR)
    report_parts: List[str] = []

    report_parts.append(
        "Auswertungspunkt 23 – Didaktische Interventionssensitivität\n"
        "Welche semantischen Interventionselemente verändern Zustände am stärksten?\n"
        f"Quelle: {INPUT_JSON}\n"
    )

    for scope, scope_data in data.get("scopes", {}).items():
        scope_dir = OUTPUT_DIR / scope
        ensure_dir(scope_dir)

        df_rank = ranking_dataframe(scope_data)
        df_events = events_dataframe(scope_data)

        df_rank.to_csv(scope_dir / f"23_{scope}_ranking_interventionen.csv", index=False, encoding="utf-8-sig")
        df_events.to_csv(scope_dir / f"23_{scope}_ereignisse.csv", index=False, encoding="utf-8-sig")

        plot_effect_strength(df_rank, scope, scope_dir)
        plot_density_change(df_rank, scope, scope_dir)
        plot_dimension_heatmap(df_rank, scope, scope_dir)

        if df_rank.empty:
            report_parts.append(f"Scope {scope}: keine auswertbaren Übergänge.")
            continue

        top = df_rank.sort_values("mean_delta_norm", ascending=False).head(5)
        lines = [
            f"Scope: {scope}",
            f"Datensätze: {scope_data.get('row_count', 0)}",
            f"Übergänge: {scope_data.get('transition_count', 0)}",
            "Top-Interventionen nach mittlerer Zustandsänderung ||ΔV||:",
        ]
        for _, row in top.iterrows():
            lines.append(
                f"- {row['intervention']}: n={int(row['n'])}, "
                f"||ΔV||={row['mean_delta_norm']:.4f}, "
                f"|Δd|={row['mean_abs_delta_dichte']:.4f}, "
                f"Dominanzwechsel={row['dominanzwechsel_rate']:.2%}, "
                f"Polaritätswechsel={row['polaritaetswechsel_rate']:.2%}"
            )

        # stärkste Dimension je Intervention
        lines.append("Dimensionale Hauptwirkung je Intervention:")
        for _, row in df_rank.iterrows():
            values = {d: abs(float(row.get(f"delta_{d}", 0))) for d in DIMENSIONS}
            max_dim = max(values, key=values.get)
            lines.append(f"- {row['intervention']}: stärkste mittlere Veränderung in {max_dim}")

        report_parts.append("\n".join(lines))

    write_report(data, report_parts, OUTPUT_DIR)
    print(f"OK: Auswertung erzeugt in {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()
