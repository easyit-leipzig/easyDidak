#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
11. Grundmatching Lehrkraftsicht ↔ Teilnehmersicht
Analyse-/Visualisierungsskript: liest die JSON-Datei des Export-Skripts und erzeugt
textuelle sowie grafische FRZK-Auswertungen.

Erzeugte Ausgaben:
  - TXT-Zusammenfassung
  - CSV mit allen Match-Kennwerten
  - Diagramme: Cosine-Verteilung, Distanz-Verteilung, Dimensionendeltas,
               Zeitverlauf, Polaritätsmatrix, Emotion-Kopplung
"""
from __future__ import annotations

import json
import math
from pathlib import Path
from typing import Any, Dict, List

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

INPUT_JSON = Path("auswertung_11_grundmatching_lehrkraft_teilnehmer.json")
OUTPUT_DIR = Path("auswertung_11_grundmatching_output")
DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def vector_from(node: Dict[str, Any], side: str) -> np.ndarray:
    return np.array([float(node[side]["vector_x"].get(d, 0.0) or 0.0) for d in DIMENSIONS], dtype=float)


def cosine(a: np.ndarray, b: np.ndarray) -> float:
    na = float(np.linalg.norm(a))
    nb = float(np.linalg.norm(b))
    if na == 0 or nb == 0:
        return 0.0
    return float(np.dot(a, b) / (na * nb))


def slope(values: List[float]) -> float:
    if len(values) < 2:
        return 0.0
    x = np.arange(len(values), dtype=float)
    y = np.array(values, dtype=float)
    return float(np.polyfit(x, y, 1)[0])


def corr_safe(a: pd.Series, b: pd.Series) -> float:
    df = pd.concat([a, b], axis=1).dropna()
    if len(df) < 3:
        return float("nan")
    if df.iloc[:, 0].std() == 0 or df.iloc[:, 1].std() == 0:
        return float("nan")
    return float(df.iloc[:, 0].corr(df.iloc[:, 1]))


def load_rows() -> pd.DataFrame:
    data = json.loads(INPUT_JSON.read_text(encoding="utf-8"))
    rows: List[Dict[str, Any]] = []
    for scope, records in data.get("scopes", {}).items():
        for r in records:
            lv = vector_from(r, "lehrkraft")
            tv = vector_from(r, "teilnehmer")
            delta = lv - tv
            row = {
                "scope": scope,
                "match_id": r["match_id"],
                "datum": pd.to_datetime(r["datum"], errors="coerce"),
                "gruppe_id": r.get("gruppe_id"),
                "teilnehmer_id": r.get("teilnehmer_id"),
                "lehrkraft_id": r.get("lehrkraft_id"),
                "cosine_lt": cosine(lv, tv),
                "distanz_lt": float(np.linalg.norm(delta)),
                "density_lehrkraft": float(r["lehrkraft"].get("d_semantisch", 0.0) or 0.0),
                "density_teilnehmer": float(r["teilnehmer"].get("d_semantisch", 0.0) or 0.0),
                "density_delta": float(r["lehrkraft"].get("d_semantisch", 0.0) or 0.0) - float(r["teilnehmer"].get("d_semantisch", 0.0) or 0.0),
                "dominanz_gleich": int(r["lehrkraft"].get("dominante_dimension") == r["teilnehmer"].get("dominante_dimension")),
                "dominanz_lehrkraft": r["lehrkraft"].get("dominante_dimension"),
                "dominanz_teilnehmer": r["teilnehmer"].get("dominante_dimension"),
                "polaritaet_lehrkraft": r["lehrkraft"].get("polaritaet_gesamt"),
                "polaritaet_teilnehmer": r["teilnehmer"].get("polaritaet_gesamt"),
                "emotion_valenz": r["teilnehmer"].get("emotion_valenz"),
                "emotion_aktivierung": r["teilnehmer"].get("emotion_aktivierung"),
                "emotion_anzahl": r["teilnehmer"].get("emotion_anzahl"),
            }
            for i, d in enumerate(DIMENSIONS):
                row[f"l_{d}"] = float(lv[i])
                row[f"t_{d}"] = float(tv[i])
                row[f"delta_{d}"] = float(delta[i])
                row[f"abs_delta_{d}"] = abs(float(delta[i]))
            rows.append(row)
    return pd.DataFrame(rows)


def scope_summary(df: pd.DataFrame) -> pd.DataFrame:
    rows = []
    for scope, g in df.groupby("scope", sort=False):
        rows.append({
            "scope": scope,
            "n": len(g),
            "cosine_mittel": g["cosine_lt"].mean(),
            "cosine_std": g["cosine_lt"].std(ddof=0),
            "cosine_min": g["cosine_lt"].min(),
            "cosine_max": g["cosine_lt"].max(),
            "distanz_mittel": g["distanz_lt"].mean(),
            "distanz_std": g["distanz_lt"].std(ddof=0),
            "dominanz_gleich_anteil": g["dominanz_gleich"].mean(),
            "polaritaet_gleich_anteil": (g["polaritaet_lehrkraft"] == g["polaritaet_teilnehmer"]).mean(),
            "dichte_delta_mittel": g["density_delta"].mean(),
            "corr_l_affektiv_emotion_valenz": corr_safe(g["l_affektiv"], pd.to_numeric(g["emotion_valenz"], errors="coerce")),
            "corr_t_affektiv_emotion_valenz": corr_safe(g["t_affektiv"], pd.to_numeric(g["emotion_valenz"], errors="coerce")),
            "corr_l_motivation_emotion_aktivierung": corr_safe(g["l_motivation"], pd.to_numeric(g["emotion_aktivierung"], errors="coerce")),
            "corr_t_motivation_emotion_aktivierung": corr_safe(g["t_motivation"], pd.to_numeric(g["emotion_aktivierung"], errors="coerce")),
        })
    return pd.DataFrame(rows)


def lag_summary(df: pd.DataFrame) -> pd.DataFrame:
    rows = []
    base = df[df["scope"] == "alle_lehrkraefte"].copy()
    base = base.sort_values(["teilnehmer_id", "datum"])
    for (lk, tn), g in base.groupby(["lehrkraft_id", "teilnehmer_id"]):
        if len(g) < 2:
            continue
        distances = g["distanz_lt"].tolist()
        cosines = g["cosine_lt"].tolist()
        rows.append({
            "lehrkraft_id": lk,
            "teilnehmer_id": tn,
            "n": len(g),
            "cosine_slope": slope(cosines),
            "distanz_slope": slope(distances),
            "drift_delta_mittel": float(np.mean(np.diff(distances))),
        })
    return pd.DataFrame(rows)


def save_plots(df: pd.DataFrame) -> None:
    OUTPUT_DIR.mkdir(exist_ok=True)
    all_df = df[df["scope"] == "alle_lehrkraefte"].copy()

    plt.figure(figsize=(10, 6))
    all_df["cosine_lt"].dropna().hist(bins=30)
    plt.title("Abb. 6.x.11.1: Verteilung der perspektivischen Vektorkohärenz")
    plt.xlabel("Cosine Similarity(L_t, T_t)")
    plt.ylabel("Anzahl Matches")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_6x11_1_cosine_verteilung.png", dpi=200)
    plt.close()

    plt.figure(figsize=(10, 6))
    all_df["distanz_lt"].dropna().hist(bins=30)
    plt.title("Abb. 6.x.11.2: Semantische Distanz zwischen Lehrkraft- und Teilnehmersicht")
    plt.xlabel("||L_t - T_t||")
    plt.ylabel("Anzahl Matches")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_6x11_2_distanz_verteilung.png", dpi=200)
    plt.close()

    mean_abs_delta = all_df[[f"abs_delta_{d}" for d in DIMENSIONS]].mean()
    plt.figure(figsize=(11, 6))
    plt.bar(DIMENSIONS, mean_abs_delta.values)
    plt.title("Abb. 6.x.11.3: Mittlere absolute Dimensionsabweichung")
    plt.xlabel("FRZK-Dimension")
    plt.ylabel("mittleres |L - T|")
    plt.xticks(rotation=30, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_6x11_3_dimensionsabweichung.png", dpi=200)
    plt.close()

    plt.figure(figsize=(10, 6))
    plt.scatter(all_df["distanz_lt"], all_df["cosine_lt"], alpha=0.65)
    plt.title("Abb. 6.x.11.4: Kopplungsraum Distanz vs. Cosine")
    plt.xlabel("semantische Distanz ||L_t - T_t||")
    plt.ylabel("Cosine Similarity")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_6x11_4_distanz_vs_cosine.png", dpi=200)
    plt.close()

    time_df = all_df.groupby("datum", as_index=False)["cosine_lt"].mean().dropna()
    plt.figure(figsize=(11, 6))
    plt.plot(time_df["datum"], time_df["cosine_lt"], marker="o")
    plt.title("Abb. 6.x.11.5: Zeitverlauf der mittleren Vektorkohärenz")
    plt.xlabel("Datum")
    plt.ylabel("mittlere Cosine Similarity")
    plt.xticks(rotation=30, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_6x11_5_cosine_zeitverlauf.png", dpi=200)
    plt.close()

    polar = pd.crosstab(all_df["polaritaet_lehrkraft"], all_df["polaritaet_teilnehmer"])
    plt.figure(figsize=(7, 6))
    plt.imshow(polar.values, aspect="auto")
    plt.title("Abb. 6.x.11.6: Polaritätskopplung Lehrkraft ↔ Teilnehmer")
    plt.xlabel("Teilnehmerpolarität")
    plt.ylabel("Lehrkraftpolarität")
    plt.xticks(range(len(polar.columns)), polar.columns)
    plt.yticks(range(len(polar.index)), polar.index)
    for i in range(polar.shape[0]):
        for j in range(polar.shape[1]):
            plt.text(j, i, str(polar.values[i, j]), ha="center", va="center")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_6x11_6_polaritaetsmatrix.png", dpi=200)
    plt.close()

    valid = all_df.dropna(subset=["emotion_valenz"])
    if len(valid) >= 3:
        plt.figure(figsize=(10, 6))
        plt.scatter(valid["emotion_valenz"], valid["cosine_lt"], alpha=0.65)
        plt.title("Abb. 6.x.11.7: Emotionale Valenz und perspektivische Kohärenz")
        plt.xlabel("emotion_valenz")
        plt.ylabel("Cosine Similarity(L_t, T_t)")
        plt.tight_layout()
        plt.savefig(OUTPUT_DIR / "abb_6x11_7_emotion_valenz_vs_cosine.png", dpi=200)
        plt.close()


def write_report(df: pd.DataFrame, summary: pd.DataFrame, lag: pd.DataFrame) -> None:
    OUTPUT_DIR.mkdir(exist_ok=True)
    lines: List[str] = []
    lines.append("11. Grundmatching Lehrkraftsicht ↔ Teilnehmersicht")
    lines.append("=" * 72)
    lines.append("")
    lines.append("FRZK-Kernlogik: L_t und T_t werden als zwei Beobachtervektoren desselben Lernzustands behandelt.")
    lines.append("Die zentrale Kopplungsgröße ist die Richtungskohärenz über Cosine Similarity; ergänzt wird sie durch euklidische Distanz, Dimensionsdeltas, Dominanz-, Polaritäts- und Emotionskopplung.")
    lines.append("")
    lines.append("Scope-Zusammenfassung")
    lines.append(summary.to_string(index=False))
    lines.append("")

    all_df = df[df["scope"] == "alle_lehrkraefte"].copy()
    if not all_df.empty:
        strongest = all_df.sort_values("cosine_lt", ascending=False).head(10)
        weakest = all_df.sort_values("cosine_lt", ascending=True).head(10)
        lines.append("Stärkste Kopplungen nach Cosine Similarity")
        lines.append(strongest[["match_id", "datum", "lehrkraft_id", "gruppe_id", "teilnehmer_id", "cosine_lt", "distanz_lt"]].to_string(index=False))
        lines.append("")
        lines.append("Schwächste / brüchigste Kopplungen nach Cosine Similarity")
        lines.append(weakest[["match_id", "datum", "lehrkraft_id", "gruppe_id", "teilnehmer_id", "cosine_lt", "distanz_lt"]].to_string(index=False))
        lines.append("")
        lines.append("Mittlere absolute Dimensionsabweichungen")
        for d in DIMENSIONS:
            lines.append(f"- {d}: {all_df[f'abs_delta_{d}'].mean():.6f}")
        lines.append("")

    if not lag.empty:
        lines.append("Lag-/Drift-Hinweise pro Lehrkraft-Teilnehmer-Zeitreihe")
        lines.append(lag.sort_values("distanz_slope").head(20).to_string(index=False))
        lines.append("")

    lines.append("Erzeugte Abbildungen")
    lines.append("- abb_6x11_1_cosine_verteilung.png: Verteilung der Richtungskohärenz zwischen L_t und T_t.")
    lines.append("- abb_6x11_2_distanz_verteilung.png: Verteilung der semantischen Differenznorm.")
    lines.append("- abb_6x11_3_dimensionsabweichung.png: Dimensionen, in denen Fremd- und Selbstwahrnehmung auseinanderfallen.")
    lines.append("- abb_6x11_4_distanz_vs_cosine.png: Kopplungsraum aus Distanz und Richtungsgleichheit.")
    lines.append("- abb_6x11_5_cosine_zeitverlauf.png: mittlere Kopplung über die Zeit.")
    lines.append("- abb_6x11_6_polaritaetsmatrix.png: Vorzeichenkopplung zwischen Lehrkraft- und Teilnehmersicht.")
    lines.append("- abb_6x11_7_emotion_valenz_vs_cosine.png: Zusammenhang subjektiver Valenz und perspektivischer Kohärenz, falls genügend Emotionsdaten vorliegen.")

    (OUTPUT_DIR / "auswertung_11_grundmatching_report.txt").write_text("\n".join(lines), encoding="utf-8")


def main() -> None:
    if not INPUT_JSON.exists():
        raise FileNotFoundError(f"JSON nicht gefunden: {INPUT_JSON.resolve()} - zuerst Export-Skript ausführen.")
    OUTPUT_DIR.mkdir(exist_ok=True)
    df = load_rows()
    if df.empty:
        raise RuntimeError("Keine Match-Daten in der JSON-Datei gefunden.")
    df.to_csv(OUTPUT_DIR / "auswertung_11_match_metrics.csv", index=False, encoding="utf-8-sig")
    summary = scope_summary(df)
    summary.to_csv(OUTPUT_DIR / "auswertung_11_scope_summary.csv", index=False, encoding="utf-8-sig")
    lag = lag_summary(df)
    lag.to_csv(OUTPUT_DIR / "auswertung_11_lag_drift_summary.csv", index=False, encoding="utf-8-sig")
    save_plots(df)
    write_report(df, summary, lag)
    print(f"Analyse abgeschlossen. Ausgaben in: {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()
