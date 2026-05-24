#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Auswertungspunkt 24: Gruppendynamik – Analyse- und Visualisierungsskript

Liest auswertung_24_gruppendynamik_export.json und erzeugt:
- textuelle Zusammenfassung als Markdown
- CSV-Tabellen
- Diagramme zur Fokusgruppe 4, Phasenlogik und Lehrkraftperspektive

Voraussetzung:
    pip install pandas matplotlib
"""

from __future__ import annotations

import json
from pathlib import Path
from typing import Dict, List

import matplotlib.pyplot as plt
import pandas as pd

INPUT_FILE = Path("auswertung_24_gruppendynamik_export.json")
OUTPUT_DIR = Path("auswertung_24_gruppendynamik_output")
OUTPUT_DIR.mkdir(exist_ok=True)

ABSENCE_START = pd.Timestamp("2025-12-12")
RETURN_ASSUMPTION = pd.Timestamp("2026-01-12")
INCIDENT_DATE = pd.Timestamp("2026-01-08")
FOCUS_GROUP_ID = 4
DIMENSIONS = ["kognition", "sozial", "affektiv", "motivation", "methodik", "performanz", "regulation"]
SUM_DIMS = [f"sum_{d}" for d in DIMENSIONS]


def load_payload() -> Dict:
    if not INPUT_FILE.exists():
        raise FileNotFoundError(f"JSON-Datei nicht gefunden: {INPUT_FILE}")
    return json.loads(INPUT_FILE.read_text(encoding="utf-8"))


def make_teacher_frame(payload: Dict) -> pd.DataFrame:
    frames: List[pd.DataFrame] = []
    for key in ["alle_lehrkraefte", "lehrkraft_1_thiele", "ohne_lehrkraft_1"]:
        rows = payload["daten"].get(key, [])
        if rows:
            frames.append(pd.DataFrame(rows))
    if not frames:
        return pd.DataFrame()
    df = pd.concat(frames, ignore_index=True)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    for col in ["gruppe_id", "lehrkraft_id", "polaritaet_gesamt"]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    for col in ["d_semantisch", "d_semantisch_mean", "d_semantisch_max", "streuung_gesamt", "kohaerenz_index", "konflikt_index", "dominante_dimension_wert", "polaritaet_spannung"] + SUM_DIMS:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    return df


def make_emotion_frame(payload: Dict) -> pd.DataFrame:
    rows = payload["daten"].get("gruppenemotion", [])
    if not rows:
        return pd.DataFrame()
    df = pd.DataFrame(rows)
    df["zeitpunkt"] = pd.to_datetime(df["zeitpunkt"], errors="coerce")
    for col in ["gruppe_id", "z_affektiv", "kohaerenz", "stabilitaet", "dynamik"]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    return df


def phase_summary(df: pd.DataFrame) -> pd.DataFrame:
    metrics = [c for c in ["d_semantisch", "d_semantisch_mean", "streuung_gesamt", "kohaerenz_index", "konflikt_index", "polaritaet_spannung"] if c in df.columns]
    if not metrics:
        return pd.DataFrame()
    summary = (
        df.groupby(["perspektive", "gruppe_id", "zeitphase"], dropna=False)[metrics]
        .agg(["count", "mean", "std", "min", "max"])
        .reset_index()
    )
    summary.columns = ["_".join([str(x) for x in col if x]) for col in summary.columns.to_flat_index()]
    return summary


def dominance_summary(df: pd.DataFrame) -> pd.DataFrame:
    if "dominante_dimension" not in df.columns:
        return pd.DataFrame()
    return (
        df.groupby(["perspektive", "gruppe_id", "zeitphase", "dominante_dimension"], dropna=False)
        .size()
        .reset_index(name="n")
        .sort_values(["perspektive", "gruppe_id", "zeitphase", "n"], ascending=[True, True, True, False])
    )


def polarity_summary(df: pd.DataFrame) -> pd.DataFrame:
    if "polaritaet_gesamt" not in df.columns:
        return pd.DataFrame()
    return (
        df.groupby(["perspektive", "gruppe_id", "zeitphase", "polaritaet_gesamt"], dropna=False)
        .size()
        .reset_index(name="n")
    )


def plot_focus_group_density(df: pd.DataFrame) -> None:
    sub = df[(df["perspektive"] == "alle_lehrkraefte") & (df["gruppe_id"] == FOCUS_GROUP_ID)].copy()
    if sub.empty or "d_semantisch" not in sub.columns:
        return
    daily = sub.groupby("datum", as_index=False)["d_semantisch"].mean().sort_values("datum")
    plt.figure(figsize=(11, 5))
    plt.plot(daily["datum"], daily["d_semantisch"], marker="o")
    plt.axvline(ABSENCE_START, linestyle="--")
    plt.axvline(INCIDENT_DATE, linestyle=":")
    plt.axvline(RETURN_ASSUMPTION, linestyle="--")
    plt.title("Auswertung 24 – Gruppe 4: semantische Dichte im Ereignisverlauf")
    plt.xlabel("Datum")
    plt.ylabel("mittlere semantische Dichte")
    plt.xticks(rotation=45, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_24_1_gruppe4_dichte_zeitverlauf.png", dpi=200)
    plt.close()


def plot_group_comparison(df: pd.DataFrame) -> None:
    sub = df[df["perspektive"] == "alle_lehrkraefte"].copy()
    if sub.empty or "d_semantisch" not in sub.columns:
        return
    comp = sub.groupby(["gruppe_id", "zeitphase"], as_index=False)["d_semantisch"].mean()
    pivot = comp.pivot(index="gruppe_id", columns="zeitphase", values="d_semantisch").sort_index()
    if pivot.empty:
        return
    pivot.plot(kind="bar", figsize=(12, 5))
    plt.title("Auswertung 24 – Gruppenvergleich nach Zeitphase")
    plt.xlabel("Gruppe")
    plt.ylabel("mittlere semantische Dichte")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_24_2_gruppenvergleich_phasen.png", dpi=200)
    plt.close()


def plot_focus_group_dimensions(df: pd.DataFrame) -> None:
    sub = df[(df["perspektive"] == "alle_lehrkraefte") & (df["gruppe_id"] == FOCUS_GROUP_ID)].copy()
    available = [c for c in SUM_DIMS if c in sub.columns]
    if sub.empty or not available:
        return
    dim = sub.groupby("zeitphase")[available].mean()
    if dim.empty:
        return
    dim.columns = [c.replace("sum_", "") for c in dim.columns]
    dim.plot(kind="bar", figsize=(12, 5))
    plt.title("Auswertung 24 – Gruppe 4: Dimensionsprofil nach Zeitphase")
    plt.xlabel("Zeitphase")
    plt.ylabel("mittlerer Dimensionswert")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_24_3_gruppe4_dimensionsprofil.png", dpi=200)
    plt.close()


def plot_emotion_focus_group(emotion: pd.DataFrame) -> None:
    if emotion.empty:
        return
    sub = emotion[emotion["gruppe_id"] == FOCUS_GROUP_ID].copy()
    if sub.empty:
        return
    for metric in ["kohaerenz", "stabilitaet", "dynamik", "z_affektiv"]:
        if metric not in sub.columns:
            continue
        plt.figure(figsize=(11, 5))
        plt.plot(sub["zeitpunkt"], sub[metric], marker="o")
        plt.axvline(ABSENCE_START, linestyle="--")
        plt.axvline(INCIDENT_DATE, linestyle=":")
        plt.axvline(RETURN_ASSUMPTION, linestyle="--")
        plt.title(f"Auswertung 24 – Gruppe 4: {metric} im Ereignisverlauf")
        plt.xlabel("Zeitpunkt")
        plt.ylabel(metric)
        plt.xticks(rotation=45, ha="right")
        plt.tight_layout()
        plt.savefig(OUTPUT_DIR / f"abb_24_4_gruppe4_emotion_{metric}.png", dpi=200)
        plt.close()


def write_markdown_report(payload: Dict, df: pd.DataFrame, emotion: pd.DataFrame, summaries: Dict[str, pd.DataFrame]) -> None:
    lines: List[str] = []
    lines.append("# Auswertung 24 – Gruppendynamik im FRZK-Raum")
    lines.append("")
    er = payload.get("ereignisrahmen", {})
    lines.append("## Ereignisrahmen")
    lines.append(f"- Fokusgruppe: {er.get('fokusgruppe_id', FOCUS_GROUP_ID)}")
    lines.append(f"- Fokuslehrkraft: lehrkraft_id={er.get('fokuslehrkraft_id', 1)} ({er.get('fokuslehrkraft_name', 'Herr Thiele')})")
    lines.append(f"- Abwesenheit ab: {er.get('abwesenheit_ab', '2025-12-12')}")
    lines.append(f"- Ereignisdatum: {er.get('ereignisdatum', '2026-01-08')}")
    lines.append(f"- Notiz: {er.get('ereignisnotiz', '')}")
    lines.append("")

    lines.append("## Datengrundlage")
    if df.empty:
        lines.append("Keine Lehrkraftdaten im JSON gefunden.")
    else:
        lines.append(f"- Lehrkraftdatensätze: {len(df)}")
        lines.append(f"- Gruppen: {', '.join(map(str, sorted(df['gruppe_id'].dropna().astype(int).unique())))}")
        lines.append(f"- Zeitraum: {df['datum'].min().date()} bis {df['datum'].max().date()}")
        focus = df[(df["perspektive"] == "alle_lehrkraefte") & (df["gruppe_id"] == FOCUS_GROUP_ID)]
        lines.append(f"- Datensätze Fokusgruppe 4: {len(focus)}")
    if not emotion.empty:
        lines.append(f"- Gruppenemotion-Datensätze: {len(emotion)}")
    lines.append("")

    if not df.empty:
        focus = df[(df["perspektive"] == "alle_lehrkraefte") & (df["gruppe_id"] == FOCUS_GROUP_ID)]
        if not focus.empty and "d_semantisch" in focus.columns:
            lines.append("## Kernauswertung Gruppe 4")
            by_phase = focus.groupby("zeitphase")["d_semantisch"].agg(["count", "mean", "std", "min", "max"]).reset_index()
            lines.append(by_phase.to_markdown(index=False))
            lines.append("")
        if "dominante_dimension" in focus.columns and not focus.empty:
            lines.append("## Dominante Dimensionen Gruppe 4")
            dom = focus.groupby(["zeitphase", "dominante_dimension"]).size().reset_index(name="n")
            lines.append(dom.sort_values(["zeitphase", "n"], ascending=[True, False]).to_markdown(index=False))
            lines.append("")

    lines.append("## Erzeugte Dateien")
    for p in sorted(OUTPUT_DIR.glob("*")):
        if p.name != "bericht_24_gruppendynamik.md":
            lines.append(f"- {p.name}")
    lines.append("")

    (OUTPUT_DIR / "bericht_24_gruppendynamik.md").write_text("\n".join(lines), encoding="utf-8")


def main() -> None:
    payload = load_payload()
    df = make_teacher_frame(payload)
    emotion = make_emotion_frame(payload)

    summaries = {
        "phase_summary": phase_summary(df) if not df.empty else pd.DataFrame(),
        "dominance_summary": dominance_summary(df) if not df.empty else pd.DataFrame(),
        "polarity_summary": polarity_summary(df) if not df.empty else pd.DataFrame(),
    }

    if not df.empty:
        df.to_csv(OUTPUT_DIR / "daten_24_lehrkraftdaten_langformat.csv", index=False, encoding="utf-8-sig")
    if not emotion.empty:
        emotion.to_csv(OUTPUT_DIR / "daten_24_gruppenemotion.csv", index=False, encoding="utf-8-sig")
    for name, table in summaries.items():
        if not table.empty:
            table.to_csv(OUTPUT_DIR / f"{name}.csv", index=False, encoding="utf-8-sig")

    plot_focus_group_density(df)
    plot_group_comparison(df)
    plot_focus_group_dimensions(df)
    plot_emotion_focus_group(emotion)
    write_markdown_report(payload, df, emotion, summaries)

    print(f"Analyse abgeschlossen. Ergebnisse in: {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()
