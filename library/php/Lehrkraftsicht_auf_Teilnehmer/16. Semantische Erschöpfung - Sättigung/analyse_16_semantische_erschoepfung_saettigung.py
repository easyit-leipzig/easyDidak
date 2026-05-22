# -*- coding: utf-8 -*-
"""
16. Semantische Erschöpfung / Sättigung – Analyse- und Visualisierungsskript

Liest die JSON-Datei aus export_16_semantische_erschoepfung_saettigung.py
und erzeugt textuelle Auswertung plus Grafiken.
"""

import json
from pathlib import Path
from typing import Dict, Any, List

import matplotlib.pyplot as plt
import pandas as pd

INPUT_JSON = Path("auswertung_16_semantische_erschoepfung_saettigung.json")
OUTPUT_DIR = Path("charts_16_semantische_erschoepfung_saettigung")
OUTPUT_TXT = OUTPUT_DIR / "auswertung_16_semantische_erschoepfung_saettigung_report.txt"


def load_data() -> Dict[str, Any]:
    if not INPUT_JSON.exists():
        raise FileNotFoundError(f"JSON-Datei nicht gefunden: {INPUT_JSON.resolve()}")
    return json.loads(INPUT_JSON.read_text(encoding="utf-8"))


def rows_to_df(rows: List[Dict[str, Any]]) -> pd.DataFrame:
    df = pd.DataFrame(rows)
    if df.empty:
        return df
    numeric_cols = [
        "d_semantisch", "token_anzahl", "drift_zum_vorwert", "drift_pro_token",
        "damping_beta_0_15", "aktivierung_pro_token", "dichte_delta_zum_vorwert",
        "dominante_dimension_wert", "polaritaet_gesamt",
    ]
    for col in numeric_cols:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    if "datum" in df.columns:
        df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    return df


def save_scatter(df: pd.DataFrame, scope: str) -> None:
    plot_df = df.dropna(subset=["d_semantisch", "drift_zum_vorwert", "token_anzahl"])
    if plot_df.empty:
        return
    plt.figure(figsize=(9, 6))
    sizes = (plot_df["token_anzahl"].clip(lower=1) * 12).to_numpy()
    plt.scatter(plot_df["d_semantisch"], plot_df["drift_zum_vorwert"], s=sizes, alpha=0.65)
    plt.xlabel("Semantische Dichte d_semantisch")
    plt.ylabel("Drift zum Vorwert ||S_t - S_{t-1}||")
    plt.title(f"Auswertung 16 – Dichte vs. Drift ({scope})")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_16_1_dichte_vs_drift_{scope}.png", dpi=200)
    plt.close()


def save_density_bin_curve(df: pd.DataFrame, scope: str) -> None:
    plot_df = df.dropna(subset=["d_semantisch", "drift_zum_vorwert"])
    if len(plot_df) < 5:
        return
    plot_df = plot_df.copy()
    plot_df["dichte_quartil"] = pd.qcut(plot_df["d_semantisch"], q=4, duplicates="drop")
    grouped = plot_df.groupby("dichte_quartil", observed=True)["drift_zum_vorwert"].mean().reset_index()
    grouped["quartil"] = range(1, len(grouped) + 1)

    plt.figure(figsize=(8, 5))
    plt.plot(grouped["quartil"], grouped["drift_zum_vorwert"], marker="o")
    plt.xlabel("Semantische Dichte – Quartil")
    plt.ylabel("Mittlere Drift")
    plt.title(f"Auswertung 16 – Sättigungskurve ({scope})")
    plt.xticks(grouped["quartil"])
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_16_2_saettigungskurve_{scope}.png", dpi=200)
    plt.close()


def save_damping_curve(df: pd.DataFrame, scope: str) -> None:
    plot_df = df.dropna(subset=["d_semantisch", "damping_beta_0_15"])
    if plot_df.empty:
        return
    plot_df = plot_df.sort_values("d_semantisch")
    plt.figure(figsize=(8, 5))
    plt.plot(plot_df["d_semantisch"], plot_df["damping_beta_0_15"], marker=".", linestyle="none", alpha=0.6)
    plt.xlabel("Semantische Dichte ||V||")
    plt.ylabel("Dämpfung exp(-0.15 · ||V||)")
    plt.title(f"Auswertung 16 – Rekursive Dämpfung ({scope})")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_16_3_damping_{scope}.png", dpi=200)
    plt.close()


def save_candidate_share_by_dimension(df: pd.DataFrame, scope: str) -> None:
    if df.empty or "dominante_dimension" not in df.columns:
        return
    if "semantische_erschoepfung_kandidat" not in df.columns:
        return
    grouped = df.groupby("dominante_dimension")["semantische_erschoepfung_kandidat"].mean().sort_values(ascending=False)
    if grouped.empty:
        return
    plt.figure(figsize=(9, 5))
    grouped.plot(kind="bar")
    plt.xlabel("Dominante Dimension")
    plt.ylabel("Anteil Sättigungskandidaten")
    plt.title(f"Auswertung 16 – Sättigung nach dominanter Dimension ({scope})")
    plt.xticks(rotation=35, ha="right")
    plt.grid(True, axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_16_4_kandidaten_nach_dimension_{scope}.png", dpi=200)
    plt.close()


def save_time_series(df: pd.DataFrame, scope: str) -> None:
    plot_df = df.dropna(subset=["datum", "d_semantisch"])
    if plot_df.empty:
        return
    grouped = plot_df.groupby("datum").agg(
        mean_d_semantisch=("d_semantisch", "mean"),
        mean_drift=("drift_zum_vorwert", "mean"),
        mean_tokens=("token_anzahl", "mean"),
    ).reset_index()
    if len(grouped) < 2:
        return
    plt.figure(figsize=(10, 5))
    plt.plot(grouped["datum"], grouped["mean_d_semantisch"], marker="o", label="mittlere Dichte")
    plt.plot(grouped["datum"], grouped["mean_drift"], marker="o", label="mittlere Drift")
    plt.xlabel("Datum")
    plt.ylabel("Wert")
    plt.title(f"Auswertung 16 – Zeitverlauf Dichte und Drift ({scope})")
    plt.legend()
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_16_5_zeitverlauf_dichte_drift_{scope}.png", dpi=200)
    plt.close()


def interpret_scope(scope: str, df: pd.DataFrame, metadata: Dict[str, Any]) -> str:
    if df.empty:
        return f"\nSCOPE: {scope}\nKeine Datensätze vorhanden.\n"

    summary = metadata.get("summary", {})
    thresholds = metadata.get("classification", {}).get("thresholds", {})
    candidate_share = summary.get("semantische_erschoepfung_anteil", 0) or 0
    candidate_count = summary.get("semantische_erschoepfung_kandidaten", 0) or 0

    text = []
    text.append(f"\nSCOPE: {scope}")
    text.append("=" * (7 + len(scope)))
    text.append(f"Datensätze: {int(summary.get('n', len(df)))}")
    text.append(f"Mittlere semantische Dichte: {summary.get('mean_d_semantisch'):.6f}")
    text.append(f"Mittlere Tokenzahl: {summary.get('mean_token_anzahl'):.6f}")
    if summary.get("mean_drift") is not None:
        text.append(f"Mittlere Drift: {summary.get('mean_drift'):.6f}")
    text.append(f"Mittlere Dämpfung beta=0.15: {summary.get('mean_damping_beta_0_15'):.6f}")
    text.append(f"Sättigungs-/Erschöpfungskandidaten: {candidate_count} ({candidate_share:.2%})")
    text.append("")
    text.append("Schwellenwerte:")
    for key, value in thresholds.items():
        text.append(f"- {key}: {value}")

    if candidate_share >= 0.15:
        interpretation = (
            "Interpretation: Es liegt ein deutlicher Sättigungsbereich vor. "
            "Hohe semantische Aktivierung erzeugt in mehreren Fällen keine entsprechende Zustandsbewegung mehr. "
            "FRZK-konform spricht dies für semantische Erschöpfung: Der Unterricht enthält viel Input, "
            "aber die zusätzliche Resonanzfähigkeit nimmt ab."
        )
    elif candidate_share > 0:
        interpretation = (
            "Interpretation: Es existieren einzelne Sättigungspunkte. "
            "Die semantische Aktivierung erreicht lokal hohe Werte, ohne durchgehend in Drift überzugehen. "
            "Didaktisch sind dies mögliche Hinweise auf punktuelle Überforderung oder Inputverdichtung."
        )
    else:
        interpretation = (
            "Interpretation: In diesem Scope zeigt sich kein starker Sättigungsbefund nach der gewählten Schwellenlogik. "
            "Hohe Dichte geht hier nicht systematisch mit geringer Zustandsänderung zusammen."
        )
    text.append("")
    text.append(interpretation)

    if "dominante_dimension" in df.columns:
        cand = df[df.get("semantische_erschoepfung_kandidat") == True]
        if not cand.empty:
            dim_counts = cand["dominante_dimension"].value_counts().to_dict()
            text.append("")
            text.append("Dominante Dimensionen innerhalb der Sättigungskandidaten:")
            for dim, count in dim_counts.items():
                text.append(f"- {dim}: {count}")

    return "\n".join(text) + "\n"


def main() -> None:
    OUTPUT_DIR.mkdir(exist_ok=True)
    data = load_data()
    report_parts = [
        "16. Semantische Erschöpfung / Sättigung",
        "=======================================",
        "Operationalisierung: hohe semantische Dichte + viele Tokens + geringe Drift bzw. geringe Drift pro Token.",
        "Dämpfung: damping = exp(-0.15 * ||V||).",
        "",
    ]

    for scope, scope_data in data.get("scopes", {}).items():
        df = rows_to_df(scope_data.get("rows", []))
        report_parts.append(interpret_scope(scope, df, scope_data))
        if not df.empty:
            save_scatter(df, scope)
            save_density_bin_curve(df, scope)
            save_damping_curve(df, scope)
            save_candidate_share_by_dimension(df, scope)
            save_time_series(df, scope)

    report = "\n".join(report_parts)
    OUTPUT_TXT.write_text(report, encoding="utf-8")
    print(report)
    print(f"\nReport gespeichert: {OUTPUT_TXT.resolve()}")
    print(f"Grafiken gespeichert in: {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()
