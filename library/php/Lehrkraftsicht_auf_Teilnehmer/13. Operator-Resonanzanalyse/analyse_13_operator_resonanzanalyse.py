# -*- coding: utf-8 -*-
"""
13. Operator-Resonanzanalyse – Analyse- und Visualisierungsskript

Liest auswertung_13_operator_resonanzanalyse.json und erzeugt:
- Konsolenauswertung je Scope
- CSV-Zusammenfassungen
- Grafiken als PNG

Voraussetzung:
pip install pandas matplotlib numpy
"""

from __future__ import annotations

import json
import math
from pathlib import Path
from typing import Any, Dict, List, Optional

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

INPUT_JSON = Path("auswertung_13_operator_resonanzanalyse.json")
OUTPUT_DIR = Path("charts_13_operator_resonanzanalyse")
DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]


def ensure_dir(path: Path) -> None:
    path.mkdir(parents=True, exist_ok=True)


def safe(v: Any) -> Optional[float]:
    if v is None:
        return None
    try:
        f = float(v)
        if math.isnan(f):
            return None
        return f
    except (TypeError, ValueError):
        return None


def load_payload() -> Dict[str, Any]:
    if not INPUT_JSON.exists():
        raise FileNotFoundError(f"JSON nicht gefunden: {INPUT_JSON.resolve()}")
    return json.loads(INPUT_JSON.read_text(encoding="utf-8"))


def records_to_frame(records: List[Dict[str, Any]]) -> pd.DataFrame:
    df = pd.DataFrame(records)
    if df.empty:
        return df
    for col in [
        "delta_norm",
        "delta_d_semantisch",
        "cosine_prev_current",
        "resonance_change",
        "d_semantisch",
        "operator_count",
        "modulator_count",
        "token_anzahl",
        "funktionsklassen_anzahl_gesamt",
    ]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    if "datum" in df.columns:
        df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    return df


def expand_operator_rows(df: pd.DataFrame) -> pd.DataFrame:
    if df.empty or "operator_list" not in df.columns:
        return pd.DataFrame()
    rows = []
    for _, row in df.iterrows():
        ops = row.get("operator_list")
        if isinstance(ops, list) and ops:
            for op in ops:
                new_row = row.to_dict()
                new_row["operator"] = op
                rows.append(new_row)
    return pd.DataFrame(rows)


def save_text_report(scope_name: str, lines: List[str]) -> None:
    (OUTPUT_DIR / f"report_{scope_name}.txt").write_text("\n".join(lines), encoding="utf-8")


def print_and_collect(lines: List[str], text: str = "") -> None:
    print(text)
    lines.append(text)


def plot_operator_vs_free(df: pd.DataFrame, scope_name: str) -> None:
    trans = df[df["has_previous_state"] == 1].copy()
    if trans.empty:
        return
    trans["Operatorstatus"] = np.where(trans["has_operator"] == 1, "operatorhaltig", "operatorfrei")
    metrics = [
        ("delta_norm", "ΔV-Norm"),
        ("resonance_change", "Resonanzänderung (1 - cos)"),
        ("delta_d_semantisch", "Δ semantische Dichte"),
    ]
    for metric, label in metrics:
        if metric not in trans.columns or trans[metric].dropna().empty:
            continue
        data = [
            trans.loc[trans["Operatorstatus"] == "operatorhaltig", metric].dropna(),
            trans.loc[trans["Operatorstatus"] == "operatorfrei", metric].dropna(),
        ]
        if all(len(x) == 0 for x in data):
            continue
        plt.figure(figsize=(7, 5))
        plt.boxplot(data, labels=["operatorhaltig", "operatorfrei"], showmeans=True)
        plt.title(f"{scope_name}: {label} – operatorhaltig vs. operatorfrei")
        plt.ylabel(label)
        plt.tight_layout()
        plt.savefig(OUTPUT_DIR / f"{scope_name}_{metric}_operator_vs_frei.png", dpi=180)
        plt.close()


def plot_top_operator_delta(op_df: pd.DataFrame, scope_name: str) -> None:
    if op_df.empty or "delta_norm" not in op_df.columns:
        return
    grouped = (
        op_df.dropna(subset=["delta_norm"])
        .groupby("operator")
        .agg(n=("delta_norm", "size"), mean_delta_norm=("delta_norm", "mean"))
        .reset_index()
    )
    grouped = grouped[grouped["n"] >= 2].sort_values("mean_delta_norm", ascending=False).head(15)
    if grouped.empty:
        return
    grouped.to_csv(OUTPUT_DIR / f"{scope_name}_operator_delta_ranking.csv", index=False, encoding="utf-8-sig")
    plt.figure(figsize=(10, 6))
    plt.barh(grouped["operator"][::-1], grouped["mean_delta_norm"][::-1])
    plt.title(f"{scope_name}: stärkste mittlere Zustandsänderung nach Operator")
    plt.xlabel("mittlere ΔV-Norm")
    plt.ylabel("Operator")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_top_operator_delta.png", dpi=180)
    plt.close()


def plot_operator_dimension_heatmap(op_df: pd.DataFrame, scope_name: str) -> None:
    if op_df.empty or "delta_vector" not in op_df.columns:
        return
    rows = []
    for _, row in op_df.iterrows():
        delta = row.get("delta_vector")
        if not isinstance(delta, list) or len(delta) != len(DIMENSIONS):
            continue
        entry = {"operator": row.get("operator")}
        for dim, val in zip(DIMENSIONS, delta):
            entry[dim] = safe(val)
        rows.append(entry)
    if not rows:
        return
    ddf = pd.DataFrame(rows)
    counts = ddf.groupby("operator").size().rename("n")
    means = ddf.groupby("operator")[DIMENSIONS].mean()
    means = means.join(counts).query("n >= 2").drop(columns=["n"])
    if means.empty:
        return
    # Top 12 nach absoluter mittlerer Dimensionsbewegung
    means["_score"] = means.abs().sum(axis=1)
    means = means.sort_values("_score", ascending=False).drop(columns=["_score"]).head(12)
    means.to_csv(OUTPUT_DIR / f"{scope_name}_operator_dimension_delta_matrix.csv", encoding="utf-8-sig")

    plt.figure(figsize=(10, max(4, 0.45 * len(means))))
    plt.imshow(means.values, aspect="auto")
    plt.colorbar(label="mittleres Δ je Dimension")
    plt.xticks(range(len(DIMENSIONS)), DIMENSIONS, rotation=45, ha="right")
    plt.yticks(range(len(means.index)), means.index)
    plt.title(f"{scope_name}: operatorische Dimensionsverschiebung")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_operator_dimension_heatmap.png", dpi=180)
    plt.close()


def plot_polarity_dominance_rates(op_df: pd.DataFrame, scope_name: str) -> None:
    if op_df.empty:
        return
    needed = {"operator", "polaritaet_change", "dominanz_change"}
    if not needed.issubset(set(op_df.columns)):
        return
    grouped = (
        op_df.groupby("operator")
        .agg(
            n=("operator", "size"),
            polaritaet_change_rate=("polaritaet_change", "mean"),
            dominanz_change_rate=("dominanz_change", "mean"),
        )
        .reset_index()
    )
    grouped = grouped[grouped["n"] >= 2]
    if grouped.empty:
        return
    grouped["score"] = grouped["polaritaet_change_rate"] + grouped["dominanz_change_rate"]
    grouped = grouped.sort_values("score", ascending=False).head(12)
    grouped.to_csv(OUTPUT_DIR / f"{scope_name}_operator_polaritaet_dominanz.csv", index=False, encoding="utf-8-sig")

    x = np.arange(len(grouped))
    width = 0.35
    plt.figure(figsize=(11, 6))
    plt.bar(x - width / 2, grouped["polaritaet_change_rate"], width, label="Polaritätswechsel")
    plt.bar(x + width / 2, grouped["dominanz_change_rate"], width, label="Dominanzwechsel")
    plt.xticks(x, grouped["operator"], rotation=45, ha="right")
    plt.ylabel("Rate")
    plt.title(f"{scope_name}: Polaritäts- und Dominanzwechsel nach Operator")
    plt.legend()
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_operator_polaritaet_dominanz.png", dpi=180)
    plt.close()


def plot_time_series(df: pd.DataFrame, scope_name: str) -> None:
    if df.empty or "datum" not in df.columns or "delta_norm" not in df.columns:
        return
    trans = df[(df["has_previous_state"] == 1) & df["datum"].notna()].copy()
    if trans.empty:
        return
    daily = trans.groupby("datum").agg(
        mean_delta_norm=("delta_norm", "mean"),
        mean_resonance_change=("resonance_change", "mean"),
        operator_rate=("has_operator", "mean"),
    ).reset_index()
    if daily.empty:
        return
    daily.to_csv(OUTPUT_DIR / f"{scope_name}_zeitreihe_operator_resonanz.csv", index=False, encoding="utf-8-sig")

    plt.figure(figsize=(10, 5))
    plt.plot(daily["datum"], daily["mean_delta_norm"], marker="o", label="mittlere ΔV-Norm")
    plt.plot(daily["datum"], daily["mean_resonance_change"], marker="o", label="mittlere Resonanzänderung")
    plt.title(f"{scope_name}: zeitlicher Verlauf operatorischer Zustandsdynamik")
    plt.xlabel("Datum")
    plt.ylabel("Wert")
    plt.legend()
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_zeitreihe_delta_resonanz.png", dpi=180)
    plt.close()


def analyze_scope(scope_name: str, scope: Dict[str, Any]) -> None:
    df = records_to_frame(scope.get("records", []))
    summary = scope.get("summary", {})
    lines: List[str] = []

    print_and_collect(lines, "=" * 80)
    print_and_collect(lines, f"SCOPE: {scope_name}")
    print_and_collect(lines, "=" * 80)
    print_and_collect(lines, f"Datensätze: {summary.get('n_records')}")
    print_and_collect(lines, f"Transitionen: {summary.get('n_transitions')}")
    print_and_collect(lines, f"Operatorhaltige Datensätze: {summary.get('n_operator_records')}")
    print_and_collect(lines, f"Operatorhaltige Transitionen: {summary.get('n_operator_transitions')}")
    print_and_collect(lines, f"Operatorfreie Transitionen: {summary.get('n_operatorfreie_transitions')}")
    print_and_collect(lines, "")
    print_and_collect(lines, "Kernvergleich operatorhaltig vs. operatorfrei:")
    print_and_collect(lines, f"  mittlere ΔV-Norm operatorhaltig: {summary.get('mean_delta_norm_operatorhaltig')}")
    print_and_collect(lines, f"  mittlere ΔV-Norm operatorfrei:   {summary.get('mean_delta_norm_operatorfrei')}")
    print_and_collect(lines, f"  mittlere Resonanzänderung operatorhaltig: {summary.get('mean_resonance_change_operatorhaltig')}")
    print_and_collect(lines, f"  mittlere Resonanzänderung operatorfrei:   {summary.get('mean_resonance_change_operatorfrei')}")
    print_and_collect(lines, f"  Δ semantische Dichte operatorhaltig: {summary.get('mean_delta_d_semantisch_operatorhaltig')}")
    print_and_collect(lines, f"  Δ semantische Dichte operatorfrei:   {summary.get('mean_delta_d_semantisch_operatorfrei')}")
    print_and_collect(lines, f"  Polaritätswechselrate operatorhaltig: {summary.get('polaritaet_change_rate_operatorhaltig')}")
    print_and_collect(lines, f"  Polaritätswechselrate operatorfrei:   {summary.get('polaritaet_change_rate_operatorfrei')}")
    print_and_collect(lines, f"  Dominanzwechselrate operatorhaltig: {summary.get('dominanz_change_rate_operatorhaltig')}")
    print_and_collect(lines, f"  Dominanzwechselrate operatorfrei:   {summary.get('dominanz_change_rate_operatorfrei')}")

    by_operator = pd.DataFrame(summary.get("by_operator", []))
    if not by_operator.empty:
        by_operator.to_csv(OUTPUT_DIR / f"{scope_name}_summary_by_operator.csv", index=False, encoding="utf-8-sig")
        top = by_operator.head(10)
        print_and_collect(lines, "")
        print_and_collect(lines, "Top-Operatoren nach mittlerer ΔV-Norm:")
        for _, row in top.iterrows():
            print_and_collect(
                lines,
                f"  {row['operator']}: n={row['n_transitions']}, mean_delta={row['mean_delta_norm']}, "
                f"res_change={row['mean_resonance_change']}, Δd={row['mean_delta_d_semantisch']}",
            )

    if not df.empty:
        df.to_csv(OUTPUT_DIR / f"{scope_name}_records_enriched.csv", index=False, encoding="utf-8-sig")
        op_df = expand_operator_rows(df[df.get("has_previous_state", 0) == 1])
        if not op_df.empty:
            op_df.to_csv(OUTPUT_DIR / f"{scope_name}_operator_transitions_long.csv", index=False, encoding="utf-8-sig")
        plot_operator_vs_free(df, scope_name)
        plot_top_operator_delta(op_df, scope_name)
        plot_operator_dimension_heatmap(op_df, scope_name)
        plot_polarity_dominance_rates(op_df, scope_name)
        plot_time_series(df, scope_name)

    save_text_report(scope_name, lines)


def main() -> None:
    ensure_dir(OUTPUT_DIR)
    payload = load_payload()
    for scope_name, scope in payload.get("scopes", {}).items():
        analyze_scope(scope_name, scope)
    print(f"\nAnalyse abgeschlossen. Ausgaben in: {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()
