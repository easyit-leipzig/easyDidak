# -*- coding: utf-8 -*-
"""
18. Zeitliche Unterrichtsphasen-Erkennung – Analyse und Visualisierung

Liest auswertung_18_zeitliche_unterrichtsphasen.json und erzeugt:
- textuelle Auswertung je Scope
- Phasenzuordnung je Beobachtung
- Changepoint-Erkennung über Drift, Dichte, Polaritäts- und Dominanzwechsel
- Rolling Cohesion
- Driftfenster
- CSV-Tabellen und PNG-Charts

Optional: HMM/ GaussianHMM wird genutzt, wenn hmmlearn installiert ist.
Ohne hmmlearn läuft eine deterministische, dissertationsstabile Heuristik.
"""

from __future__ import annotations

import json
import math
from collections import Counter, defaultdict
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

INPUT_FILE = Path("auswertung_18_zeitliche_unterrichtsphasen.json")
OUTPUT_DIR = Path("auswertung_18_zeitliche_unterrichtsphasen_output")
ROLLING_WINDOW = 5
CHANGEPOINT_Z = 1.25

PHASES = [
    "Einstieg",
    "Aktivierung",
    "Destabilisierung",
    "Rekonstruktion",
    "Konsolidierung",
    "Ermüdung",
    "Abschlussstabilisierung",
]

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]


def safe_float(value: Any, default: float = 0.0) -> float:
    try:
        if value is None:
            return default
        value = float(value)
        if math.isnan(value) or math.isinf(value):
            return default
        return value
    except Exception:
        return default


def load_json(path: Path) -> Dict[str, Any]:
    if not path.exists():
        raise FileNotFoundError(f"JSON-Datei nicht gefunden: {path}")
    return json.loads(path.read_text(encoding="utf-8"))


def items_to_dataframe(items: List[Dict[str, Any]]) -> pd.DataFrame:
    rows = []
    for item in items:
        vec = item.get("vector") or [0] * 7
        temporal = item.get("temporal") or {}
        row = {
            "datum": item.get("datum"),
            "path_key": item.get("path_key"),
            "lehrkraft_id": item.get("lehrkraft_id"),
            "gruppe_id": item.get("gruppe_id"),
            "teilnehmer_id": item.get("teilnehmer_id"),
            "fach": item.get("fach"),
            "thema": item.get("thema"),
            "dominante_dimension": item.get("dominante_dimension"),
            "dominante_dimension_wert": safe_float(item.get("dominante_dimension_wert")),
            "polaritaet_gesamt": int(item.get("polaritaet_gesamt") or 0),
            "d_semantisch": safe_float(item.get("d_semantisch")),
            "token_anzahl": int(item.get("token_anzahl") or 0),
            "funktionsklassen_anzahl_gesamt": int(item.get("funktionsklassen_anzahl_gesamt") or 0),
            "operator_count": int(item.get("operator_count") or 0),
            "modulator_count": int(item.get("modulator_count") or 0),
            "has_operator": int(item.get("has_operator") or 0),
            "drift_norm": safe_float(temporal.get("drift_norm")),
            "cosine_prev": temporal.get("cosine_prev"),
            "delta_dichte": safe_float(temporal.get("delta_dichte")),
            "polaritaetswechsel": int(temporal.get("polaritaetswechsel") or 0),
            "dominanzwechsel": int(temporal.get("dominanzwechsel") or 0),
        }
        for i, dim in enumerate(DIMENSIONS):
            row[f"x_{dim}"] = safe_float(vec[i] if i < len(vec) else 0)
        rows.append(row)

    df = pd.DataFrame(rows)
    if not df.empty:
        df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
        df = df.sort_values(["path_key", "datum"]).reset_index(drop=True)
        df["cosine_prev"] = pd.to_numeric(df["cosine_prev"], errors="coerce")
    return df


def add_rolling_metrics(df: pd.DataFrame) -> pd.DataFrame:
    if df.empty:
        return df
    df = df.copy()
    df["rolling_dichte"] = df.groupby("path_key")["d_semantisch"].transform(
        lambda s: s.rolling(ROLLING_WINDOW, min_periods=1).mean()
    )
    df["rolling_drift"] = df.groupby("path_key")["drift_norm"].transform(
        lambda s: s.rolling(ROLLING_WINDOW, min_periods=1).mean()
    )
    df["rolling_cohesion"] = df.groupby("path_key")["cosine_prev"].transform(
        lambda s: s.fillna(1.0).rolling(ROLLING_WINDOW, min_periods=1).mean()
    )
    df["drift_window"] = df.groupby("path_key")["drift_norm"].transform(
        lambda s: s.rolling(ROLLING_WINDOW, min_periods=1).sum()
    )
    return df


def zscore(series: pd.Series) -> pd.Series:
    std = series.std(ddof=0)
    if std == 0 or pd.isna(std):
        return pd.Series(np.zeros(len(series)), index=series.index)
    return (series - series.mean()) / std


def add_changepoints(df: pd.DataFrame) -> pd.DataFrame:
    if df.empty:
        return df
    df = df.copy()
    df["z_drift"] = zscore(df["drift_norm"].fillna(0))
    df["z_delta_dichte"] = zscore(df["delta_dichte"].abs().fillna(0))
    df["changepoint_score"] = (
        df["z_drift"].clip(lower=0)
        + df["z_delta_dichte"].clip(lower=0)
        + df["polaritaetswechsel"] * 1.5
        + df["dominanzwechsel"] * 1.0
    )
    df["is_changepoint"] = (df["changepoint_score"] >= CHANGEPOINT_Z).astype(int)
    return df


def classify_phase(row: pd.Series, index_in_path: int, path_len: int) -> str:
    """Deterministische Phasenlogik auf Basis FRZK-Zustandsfolgen."""
    drift = safe_float(row.get("drift_norm"))
    dichte = safe_float(row.get("d_semantisch"))
    cohesion = safe_float(row.get("rolling_cohesion"), 1.0)
    delta = safe_float(row.get("delta_dichte"))
    polarity_change = int(row.get("polaritaetswechsel") or 0)
    dominance_change = int(row.get("dominanzwechsel") or 0)
    polarity = int(row.get("polaritaet_gesamt") or 0)
    dominant = str(row.get("dominante_dimension") or "")

    if index_in_path == 0:
        return "Einstieg"

    if path_len > 2 and index_in_path >= path_len - 1 and cohesion >= 0.80 and drift <= 0.35:
        return "Abschlussstabilisierung"

    if polarity_change or (dominance_change and drift >= 0.45) or cohesion < 0.45:
        return "Destabilisierung"

    if drift >= 0.35 and delta > 0 and dominant in {"kognition", "methodik", "regulation"}:
        return "Rekonstruktion"

    if cohesion >= 0.82 and drift <= 0.25 and dichte >= 0.7:
        return "Konsolidierung"

    if polarity < 0 or dominant in {"affektiv", "motivation"} and delta < 0:
        return "Ermüdung"

    return "Aktivierung"


def add_phases(df: pd.DataFrame) -> pd.DataFrame:
    if df.empty:
        return df
    df = df.copy()
    phase_values = []
    for _, group in df.groupby("path_key", sort=False):
        path_len = len(group)
        for pos, (_, row) in enumerate(group.iterrows()):
            phase_values.append((row.name, classify_phase(row, pos, path_len)))
    for idx, phase in phase_values:
        df.loc[idx, "phase"] = phase
    return df


def try_hmm_phases(df: pd.DataFrame) -> Optional[pd.Series]:
    """Optionale HMM-Variante. Läuft nur, wenn hmmlearn verfügbar ist und genug Daten vorhanden sind."""
    try:
        from hmmlearn.hmm import GaussianHMM  # type: ignore
    except Exception:
        return None

    if len(df) < len(PHASES) * 3:
        return None

    features = df[["d_semantisch", "drift_norm", "rolling_cohesion", "delta_dichte"]].fillna(0).to_numpy()
    try:
        model = GaussianHMM(n_components=len(PHASES), covariance_type="diag", n_iter=300, random_state=42)
        hidden = model.fit(features).predict(features)
    except Exception:
        return None

    means = pd.DataFrame(model.means_, columns=["d_semantisch", "drift_norm", "rolling_cohesion", "delta_dichte"])
    # Mapping der HMM-Zustände auf didaktische Phasen anhand der Profilmittelwerte.
    order_by_position = means.sort_values(["drift_norm", "rolling_cohesion", "d_semantisch"]).index.tolist()
    mapping = {}
    ordered_phases = ["Konsolidierung", "Abschlussstabilisierung", "Einstieg", "Aktivierung", "Rekonstruktion", "Ermüdung", "Destabilisierung"]
    for state, phase in zip(order_by_position, ordered_phases):
        mapping[state] = phase
    return pd.Series([mapping.get(s, "Aktivierung") for s in hidden], index=df.index)


def analyze_scope(scope_name: str, df: pd.DataFrame) -> Tuple[pd.DataFrame, str]:
    df = add_rolling_metrics(df)
    df = add_changepoints(df)
    df = add_phases(df)

    hmm_phase = try_hmm_phases(df)
    if hmm_phase is not None:
        df["phase_hmm_optional"] = hmm_phase

    phase_counts = Counter(df["phase"]) if not df.empty else Counter()
    dominant_counts = Counter(df["dominante_dimension"]) if not df.empty else Counter()

    lines = []
    lines.append(f"Scope: {scope_name}")
    lines.append(f"Datensätze: {len(df)}")
    if not df.empty:
        lines.append(f"Zeitraum: {df['datum'].min().date()} bis {df['datum'].max().date()}")
        lines.append(f"Mittlere semantische Dichte: {df['d_semantisch'].mean():.4f}")
        lines.append(f"Mittlere Drift: {df['drift_norm'].mean():.4f}")
        lines.append(f"Mittlere Rolling Cohesion: {df['rolling_cohesion'].mean():.4f}")
        lines.append(f"Changepoints: {int(df['is_changepoint'].sum())}")
        lines.append("Phasenverteilung:")
        for phase in PHASES:
            lines.append(f"  - {phase}: {phase_counts.get(phase, 0)}")
        lines.append("Dominante Dimensionen:")
        for dim, count in dominant_counts.most_common():
            lines.append(f"  - {dim}: {count}")
    lines.append("")
    return df, "\n".join(lines)


def plot_phase_distribution(df: pd.DataFrame, scope_name: str) -> None:
    counts = df["phase"].value_counts().reindex(PHASES, fill_value=0)
    plt.figure(figsize=(11, 6))
    counts.plot(kind="bar")
    plt.title(f"18 Zeitliche Unterrichtsphasen – Phasenverteilung ({scope_name})")
    plt.xlabel("Phase")
    plt.ylabel("Anzahl")
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_01_phasenverteilung.png", dpi=180)
    plt.close()


def plot_temporal_metrics(df: pd.DataFrame, scope_name: str) -> None:
    if df.empty:
        return
    daily = df.groupby("datum", as_index=False).agg(
        d_semantisch=("d_semantisch", "mean"),
        drift_norm=("drift_norm", "mean"),
        rolling_cohesion=("rolling_cohesion", "mean"),
        changepoint_score=("changepoint_score", "mean"),
    )
    plt.figure(figsize=(12, 6))
    plt.plot(daily["datum"], daily["d_semantisch"], marker="o", label="semantische Dichte")
    plt.plot(daily["datum"], daily["drift_norm"], marker="o", label="Drift")
    plt.plot(daily["datum"], daily["rolling_cohesion"], marker="o", label="Rolling Cohesion")
    plt.title(f"18 Zeitliche Unterrichtsphasen – Zeitverlauf ({scope_name})")
    plt.xlabel("Datum")
    plt.ylabel("Wert")
    plt.legend()
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_02_zeitverlauf_dichte_drift_cohesion.png", dpi=180)
    plt.close()


def plot_changepoints(df: pd.DataFrame, scope_name: str) -> None:
    if df.empty:
        return
    daily = df.groupby("datum", as_index=False).agg(
        changepoint_score=("changepoint_score", "mean"),
        changepoints=("is_changepoint", "sum"),
    )
    plt.figure(figsize=(12, 6))
    plt.plot(daily["datum"], daily["changepoint_score"], marker="o", label="Changepoint-Score")
    cp = daily[daily["changepoints"] > 0]
    if not cp.empty:
        plt.scatter(cp["datum"], cp["changepoint_score"], s=80, label="erkannte Changepoints")
    plt.title(f"18 Zeitliche Unterrichtsphasen – Changepoint Detection ({scope_name})")
    plt.xlabel("Datum")
    plt.ylabel("Score")
    plt.legend()
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_03_changepoints.png", dpi=180)
    plt.close()


def plot_drift_window(df: pd.DataFrame, scope_name: str) -> None:
    if df.empty:
        return
    daily = df.groupby("datum", as_index=False).agg(drift_window=("drift_window", "mean"))
    plt.figure(figsize=(12, 6))
    plt.plot(daily["datum"], daily["drift_window"], marker="o")
    plt.title(f"18 Zeitliche Unterrichtsphasen – Driftfenster ({scope_name})")
    plt.xlabel("Datum")
    plt.ylabel(f"Driftsumme im Fenster n={ROLLING_WINDOW}")
    plt.xticks(rotation=35, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_04_driftfenster.png", dpi=180)
    plt.close()


def plot_dimension_phase_heatmap(df: pd.DataFrame, scope_name: str) -> None:
    if df.empty:
        return
    pivot = pd.crosstab(df["phase"], df["dominante_dimension"]).reindex(PHASES, fill_value=0)
    plt.figure(figsize=(11, 6))
    plt.imshow(pivot.values, aspect="auto")
    plt.title(f"18 Zeitliche Unterrichtsphasen – Phase × dominante Dimension ({scope_name})")
    plt.xlabel("Dominante Dimension")
    plt.ylabel("Phase")
    plt.xticks(range(len(pivot.columns)), pivot.columns, rotation=35, ha="right")
    plt.yticks(range(len(pivot.index)), pivot.index)
    plt.colorbar(label="Anzahl")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_05_phase_dimension_heatmap.png", dpi=180)
    plt.close()


def write_interpretation(all_text: List[str], all_frames: Dict[str, pd.DataFrame]) -> None:
    lines = []
    lines.append("18. Zeitliche Unterrichtsphasen-Erkennung – textuelle Gesamtauswertung")
    lines.append("=" * 78)
    lines.append("")
    lines.extend(all_text)
    lines.append("Didaktische Lesart")
    lines.append("- Einstieg: erste Beobachtung einer Trajektorie ohne vorherigen Vergleichszustand.")
    lines.append("- Aktivierung: moderate Bewegung ohne Bruch, häufig mit kognitiver, methodischer oder motivationaler Öffnung.")
    lines.append("- Destabilisierung: Drift, Dominanzwechsel, Polaritätswechsel oder niedrige Kohäsion markieren Irritation.")
    lines.append("- Rekonstruktion: erhöhte Drift bei gleichzeitigem Dichtezuwachs; das System ordnet sich neu.")
    lines.append("- Konsolidierung: hohe Kohäsion, geringe Drift und tragfähige semantische Dichte.")
    lines.append("- Ermüdung: negative Polarität oder affektiv-motivationaler Abfall.")
    lines.append("- Abschlussstabilisierung: am Ende einer Trajektorie geringe Drift und hohe Kohäsion.")
    lines.append("")
    lines.append("Methodische Einschränkung")
    lines.append("Die Phasen sind keine extern beobachteten Unterrichtsabschnitte, sondern aus FRZK-Zustandsfolgen rekonstruierte semantische Phasen. Sie zeigen daher Unterrichtsphasen aus Lehrkraftsicht, nicht aus Teilnehmersicht.")
    (OUTPUT_DIR / "00_textuelle_gesamtauswertung.txt").write_text("\n".join(lines), encoding="utf-8")


def main() -> None:
    OUTPUT_DIR.mkdir(exist_ok=True)
    data = load_json(INPUT_FILE)

    text_blocks: List[str] = []
    all_frames: Dict[str, pd.DataFrame] = {}

    for scope_name, scope_data in data.get("scopes", {}).items():
        df = items_to_dataframe(scope_data.get("items", []))
        df, text = analyze_scope(scope_name, df)
        text_blocks.append(text)
        all_frames[scope_name] = df

        df.to_csv(OUTPUT_DIR / f"{scope_name}_phasentabelle.csv", index=False, encoding="utf-8-sig")
        plot_phase_distribution(df, scope_name)
        plot_temporal_metrics(df, scope_name)
        plot_changepoints(df, scope_name)
        plot_drift_window(df, scope_name)
        plot_dimension_phase_heatmap(df, scope_name)

    write_interpretation(text_blocks, all_frames)
    print(f"Analyse abgeschlossen. Ergebnisse in: {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()
