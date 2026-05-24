#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Auswertungspunkt 24 – Gruppendynamik
Analyseskript: neutrales methodisch-didaktisches Gruppenprofil auf Basis der Export-JSON.

Dieses Skript liest ausschließlich die vom Exportskript erzeugte JSON-Datei.
Es führt keine neue Datenbankabfrage aus.

Analyselogik:
1. Vollzeitraum-Profil jeder Gruppe.
2. Methodisch-didaktische Deutung entlang der sieben FRZK-Dimensionen.
3. Zeitliche Dynamik: Stabilität, Drift, Polaritätslast, dominante Dimensionen.
4. Vergleich der Perspektiven:
   - alle Lehrkräfte
   - lehrkraft_id=1
   - alle außer lehrkraft_id=1
5. Neutrale Eskalationsprüfung erst nach Profilbildung.
   Das Skript spricht nicht von Unausweichlichkeit als Tatsachenbehauptung, sondern berechnet,
   ob die Datenlage eine stark vorgezeichnete Eskalationsdisposition nahelegt.
"""

from __future__ import annotations

import json
import math
import statistics
from collections import Counter
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple

import matplotlib.pyplot as plt

INPUT_FILE = Path("auswertung_24_gruppendynamik_neutral_basis.json")
OUTPUT_DIR = Path("auswertung_24_gruppendynamik_neutral_output")
REPORT_FILE = OUTPUT_DIR / "bericht_24_gruppendynamik_neutral.md"
SUMMARY_JSON = OUTPUT_DIR / "analyse_24_gruppendynamik_neutral_summary.json"

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

DIDAKTISCHE_DIMENSIONEN = {
    "kognition": "fachlich-kognitive Verarbeitung und begriffliche Durchdringung",
    "sozial": "soziale Koordination, Gruppenbezug, Störungs- oder Kooperationsstruktur",
    "affektiv": "emotionale Tönung, Belastung, Sicherheit oder Abwehr",
    "motivation": "Antrieb, Beteiligungsbereitschaft und Zielbindung",
    "methodik": "Arbeitsform, Strukturierung und Strategienutzung",
    "performanz": "sichtbare Leistungs- und Umsetzungsebene",
    "regulation": "Selbststeuerung, Aufmerksamkeits- und Verhaltenskontrolle",
}


def load_data() -> Dict[str, Any]:
    if not INPUT_FILE.exists():
        raise FileNotFoundError(
            f"JSON-Datei nicht gefunden: {INPUT_FILE}. Bitte zuerst export_24_gruppendynamik_neutral.py ausführen."
        )
    return json.loads(INPUT_FILE.read_text(encoding="utf-8"))


def safe_float(v: Any) -> Optional[float]:
    if v is None:
        return None
    try:
        f = float(v)
        if math.isnan(f) or math.isinf(f):
            return None
        return f
    except Exception:
        return None


def mean(vals: List[Optional[float]]) -> Optional[float]:
    xs = [safe_float(v) for v in vals]
    xs = [x for x in xs if x is not None]
    return statistics.fmean(xs) if xs else None


def stdev(vals: List[Optional[float]]) -> Optional[float]:
    xs = [safe_float(v) for v in vals]
    xs = [x for x in xs if x is not None]
    return statistics.stdev(xs) if len(xs) >= 2 else None


def slope(values: List[Optional[float]]) -> Optional[float]:
    ys = [safe_float(v) for v in values]
    if len([y for y in ys if y is not None]) < 2:
        return None
    clean = [(i, y) for i, y in enumerate(ys) if y is not None]
    xs = [i for i, _ in clean]
    ys2 = [y for _, y in clean]
    mx = statistics.fmean(xs)
    my = statistics.fmean(ys2)
    denom = sum((x - mx) ** 2 for x in xs)
    if denom == 0:
        return None
    return sum((x - mx) * (y - my) for x, y in zip(xs, ys2)) / denom


def abs_mean(values: List[Optional[float]]) -> Optional[float]:
    xs = [abs(x) for x in (safe_float(v) for v in values) if x is not None]
    return statistics.fmean(xs) if xs else None


def get_group(scope: Dict[str, Any], gid: str) -> Optional[Dict[str, Any]]:
    return scope.get("groups", {}).get(str(gid))


def dominant_dimension(group: Dict[str, Any]) -> Optional[Tuple[str, float]]:
    means = group.get("dimension_means", {})
    vals = [(dim, safe_float(means.get(dim))) for dim in DIMENSIONS]
    vals = [(d, v) for d, v in vals if v is not None]
    if not vals:
        return None
    return max(vals, key=lambda x: abs(x[1]))


def instability_metrics(group: Dict[str, Any]) -> Dict[str, Any]:
    timeline = group.get("timeline", [])
    if not timeline:
        return {}

    polar = [safe_float(t.get("polaritaet_index")) for t in timeline]
    density = [safe_float(t.get("d_semantisch_mean")) for t in timeline]
    norm = [safe_float(t.get("semantische_breite_mean")) for t in timeline]

    dim_slopes = {}
    dim_volatility = {}
    dim_means_over_time = {}
    for dim in DIMENSIONS:
        vals = [safe_float(t.get("dimension_means", {}).get(dim)) for t in timeline]
        dim_slopes[dim] = slope(vals)
        dim_volatility[dim] = stdev(vals)
        dim_means_over_time[dim] = mean(vals)

    negative_ratio = None
    polar_clean = [p for p in polar if p is not None]
    if polar_clean:
        negative_ratio = len([p for p in polar_clean if p < 0]) / len(polar_clean)

    # Dominanzwechsel: wie oft sich die stärkste Dimension in der Zeitreihe ändert.
    dom_seq = []
    for t in timeline:
        dm = t.get("dimension_means", {})
        vals = [(dim, safe_float(dm.get(dim))) for dim in DIMENSIONS]
        vals = [(d, v) for d, v in vals if v is not None]
        if vals:
            dom_seq.append(max(vals, key=lambda x: abs(x[1]))[0])
    switches = sum(1 for a, b in zip(dom_seq, dom_seq[1:]) if a != b)
    switch_rate = switches / max(1, len(dom_seq) - 1) if dom_seq else None

    return {
        "n_termine": len(timeline),
        "polaritaet_index_mean": mean(polar),
        "negative_polaritaet_ratio": negative_ratio,
        "d_semantisch_mean": mean(density),
        "d_semantisch_slope": slope(density),
        "d_semantisch_volatility": stdev(density),
        "semantische_breite_mean": mean(norm),
        "semantische_breite_slope": slope(norm),
        "dominanzwechsel_rate": switch_rate,
        "dimension_slopes": dim_slopes,
        "dimension_volatility": dim_volatility,
        "dimension_means_over_time": dim_means_over_time,
        "dominanzsequenz": dom_seq,
    }


def emotion_metrics(data: Dict[str, Any], gid: str) -> Dict[str, Any]:
    emo = data.get("group_emotion_profiles", {}).get(str(gid), {})
    if not emo:
        return {}
    return {
        "z_affektiv_mean": safe_float(emo.get("z_affektiv_mean")),
        "kohaerenz_mean": safe_float(emo.get("kohaerenz_mean")),
        "stabilitaet_mean": safe_float(emo.get("stabilitaet_mean")),
        "dynamik_mean": safe_float(emo.get("dynamik_mean")),
        "dynamik_max": safe_float(emo.get("dynamik_max")),
        "emotionaler_status_verteilung": emo.get("emotionaler_status_verteilung", {}),
        "emotionaler_modus_verteilung": emo.get("emotionaler_modus_verteilung", {}),
    }


def normalized_score(value: Optional[float], low: float, high: float, invert: bool = False) -> float:
    if value is None:
        return 0.0
    if high == low:
        return 0.0
    s = (value - low) / (high - low)
    s = max(0.0, min(1.0, s))
    return 1.0 - s if invert else s


def build_escalation_disposition(metrics: Dict[str, Any], emo: Dict[str, Any]) -> Dict[str, Any]:
    """Neutraler Index: keine Ereignisannahme, sondern Risikoprofil aus Zeitreihenmerkmalen."""
    # Soziale, affektive und regulatorische Negativlast
    dim_means = metrics.get("dimension_means_over_time", {})
    sozial = safe_float(dim_means.get("sozial"))
    affektiv = safe_float(dim_means.get("affektiv"))
    regulation = safe_float(dim_means.get("regulation"))
    motivation = safe_float(dim_means.get("motivation"))

    negative_social_load = abs(min(0.0, sozial if sozial is not None else 0.0))
    negative_affective_load = abs(min(0.0, affektiv if affektiv is not None else 0.0))
    negative_regulation_load = abs(min(0.0, regulation if regulation is not None else 0.0))
    negative_motivation_load = abs(min(0.0, motivation if motivation is not None else 0.0))

    volatility = mean([
        metrics.get("d_semantisch_volatility"),
        metrics.get("dominanzwechsel_rate"),
        metrics.get("negative_polaritaet_ratio"),
    ]) or 0.0

    # Gruppendynamik aus frzk_group_emotion: hohe Dynamik + niedrigere Kohärenz/Stabilität erhöhen Disposition.
    dynamik = emo.get("dynamik_mean")
    koh = emo.get("kohaerenz_mean")
    stab = emo.get("stabilitaet_mean")

    components = {
        "negative_soziallast": normalized_score(negative_social_load, 0, 1),
        "negative_affektlast": normalized_score(negative_affective_load, 0, 1),
        "negative_regulationslast": normalized_score(negative_regulation_load, 0, 1),
        "negative_motivationslast": normalized_score(negative_motivation_load, 0, 1),
        "zeitliche_volatilitaet": normalized_score(volatility, 0, 1),
        "gruppendynamik": normalized_score(dynamik, 0, 1) if dynamik is not None else 0.0,
        "kohaerenzdefizit": normalized_score(koh, 0.75, 1.0, invert=True) if koh is not None else 0.0,
        "stabilitaetsdefizit": normalized_score(stab, 0.75, 1.0, invert=True) if stab is not None else 0.0,
    }

    weights = {
        "negative_soziallast": 0.20,
        "negative_affektlast": 0.15,
        "negative_regulationslast": 0.20,
        "negative_motivationslast": 0.10,
        "zeitliche_volatilitaet": 0.15,
        "gruppendynamik": 0.10,
        "kohaerenzdefizit": 0.05,
        "stabilitaetsdefizit": 0.05,
    }
    score = sum(components[k] * weights[k] for k in weights)

    if score >= 0.70:
        level = "sehr hohe Eskalationsdisposition"
    elif score >= 0.50:
        level = "erhöhte Eskalationsdisposition"
    elif score >= 0.30:
        level = "moderate Eskalationsdisposition"
    else:
        level = "geringe Eskalationsdisposition"

    return {
        "score": score,
        "level": level,
        "components": components,
        "interpretation": (
            "Der Index beschreibt keine Kausalbehauptung und keine rückwirkende Zwangsläufigkeit. "
            "Er zeigt, ob die gruppenbezogenen Daten bereits vor einer konkreten Ereignisdeutung "
            "eine instabile soziale, affektive oder regulatorische Struktur erkennen lassen."
        ),
    }


def compare_scopes(data: Dict[str, Any], gid: str) -> Dict[str, Any]:
    scopes = data.get("scopes", {})
    result = {}
    for name in ["alle_lehrkraefte", "lehrkraft_1", "ohne_lehrkraft_1"]:
        group = get_group(scopes.get(name, {}), gid)
        if not group:
            result[name] = None
            continue
        dom = dominant_dimension(group)
        result[name] = {
            "satzanzahl_sum": group.get("satzanzahl_sum"),
            "n_termine": group.get("n_termine"),
            "n_teilnehmer_beobachtet": group.get("n_teilnehmer_beobachtet"),
            "d_semantisch_mean": group.get("d_semantisch_mean"),
            "polaritaet_verteilung": group.get("polaritaet_verteilung"),
            "dominante_dimension": dom[0] if dom else None,
            "dominante_dimension_wert": dom[1] if dom else None,
            "dimension_means": group.get("dimension_means", {}),
            "instability_metrics": instability_metrics(group),
        }
    return result


def profile_text(gid: str, group: Dict[str, Any], metrics: Dict[str, Any], emo: Dict[str, Any], escalation: Dict[str, Any]) -> str:
    dom = dominant_dimension(group)
    dim_means = group.get("dimension_means", {})
    strongest = dom[0] if dom else "nicht bestimmbar"
    strongest_value = dom[1] if dom else None

    lines = []
    lines.append(f"### Gruppe {gid}")
    lines.append("")
    lines.append(f"**Datenbasis.** Zeitraum: {group.get('zeitraum', {}).get('von')} bis {group.get('zeitraum', {}).get('bis')}. "
                 f"Es liegen {group.get('satzanzahl_sum')} semantische Bewertungseinheiten aus {group.get('n_records')} aggregierten Records, {group.get('n_termine')} Termine, "
                 f"{group.get('n_unterrichtseinheiten')} Unterrichtseinheiten und {group.get('n_teilnehmer_beobachtet')} beobachtete Teilnehmer:innen vor.")
    lines.append("")
    lines.append(f"**Methodisch-didaktisches Profil.** Die stärkste Dimension ist `{strongest}` "
                 f"({strongest_value:.3f})" if strongest_value is not None else "**Methodisch-didaktisches Profil.** Eine dominante Dimension ist nicht bestimmbar.")
    lines.append("")
    for dim in DIMENSIONS:
        v = safe_float(dim_means.get(dim))
        desc = DIDAKTISCHE_DIMENSIONEN[dim]
        if v is None:
            continue
        richtung = "stabilisierend/positiv" if v > 0 else ("belastend/negativ" if v < 0 else "neutral")
        lines.append(f"- `{dim}`: {v:.3f} → {richtung}; didaktisch: {desc}.")
    lines.append("")
    lines.append(f"**Zeitliche Dynamik.** Mittlere Polarität: {fmt(metrics.get('polaritaet_index_mean'))}; "
                 f"Negativanteil: {fmt_pct(metrics.get('negative_polaritaet_ratio'))}; "
                 f"Dominanzwechselrate: {fmt_pct(metrics.get('dominanzwechsel_rate'))}; "
                 f"Dichte-Drift: {fmt(metrics.get('d_semantisch_slope'))}.")
    if emo:
        lines.append(f"**Gruppenemotionale Zusatzspur.** Kohärenz: {fmt(emo.get('kohaerenz_mean'))}; "
                     f"Stabilität: {fmt(emo.get('stabilitaet_mean'))}; Dynamik: {fmt(emo.get('dynamik_mean'))}.")
    lines.append(f"**Neutrale Eskalationsdisposition.** {escalation.get('level')} "
                 f"(Index {escalation.get('score', 0):.3f}). Diese Aussage ist keine Kausal- oder Schuldzuweisung, "
                 f"sondern eine datenbasierte Beschreibung der gruppendynamischen Vorstruktur.")
    lines.append("")
    return "\n".join(lines)


def fmt(v: Any) -> str:
    x = safe_float(v)
    return "n/a" if x is None else f"{x:.3f}"


def fmt_pct(v: Any) -> str:
    x = safe_float(v)
    return "n/a" if x is None else f"{x*100:.1f}%"


def plot_group_dimension_bars(gid: str, group: Dict[str, Any]) -> Optional[str]:
    means = group.get("dimension_means", {})
    vals = [safe_float(means.get(dim)) or 0.0 for dim in DIMENSIONS]
    if not any(abs(v) > 0 for v in vals):
        return None
    fig = plt.figure(figsize=(10, 5))
    plt.bar(DIMENSIONS, vals)
    plt.axhline(0, linewidth=0.8)
    plt.xticks(rotation=35, ha="right")
    plt.ylabel("mittlerer FRZK-Dimensionswert")
    plt.title(f"Gruppe {gid} – methodisch-didaktisches Dimensionsprofil")
    plt.tight_layout()
    out = OUTPUT_DIR / f"gruppe_{gid}_dimensionsprofil.png"
    fig.savefig(out, dpi=160)
    plt.close(fig)
    return str(out)


def plot_group_timeline(gid: str, group: Dict[str, Any]) -> Optional[str]:
    tl = group.get("timeline", [])
    if len(tl) < 2:
        return None
    x = list(range(len(tl)))
    y = [safe_float(t.get("d_semantisch_mean")) or 0.0 for t in tl]
    labels = [t.get("datum") for t in tl]
    fig = plt.figure(figsize=(11, 5))
    plt.plot(x, y, marker="o")
    plt.xticks(x, labels, rotation=45, ha="right")
    plt.ylabel("mittlere semantische Dichte")
    plt.title(f"Gruppe {gid} – zeitliche Dichteentwicklung")
    plt.tight_layout()
    out = OUTPUT_DIR / f"gruppe_{gid}_dichte_timeline.png"
    fig.savefig(out, dpi=160)
    plt.close(fig)
    return str(out)


def analyze() -> Dict[str, Any]:
    data = load_data()
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    all_scope = data.get("scopes", {}).get("alle_lehrkraefte", {})
    groups = all_scope.get("groups", {})

    summary = {
        "meta": data.get("meta", {}),
        "gruppen": {},
        "vergleich": {},
    }

    report_lines = []
    report_lines.append("# Auswertung 24 – Gruppendynamik: neutrales gruppenbasiertes Profil")
    report_lines.append("")
    report_lines.append("Die Analyse wurde vollständig aus der exportierten JSON-Basis erzeugt. Sie nutzt den gesamten verfügbaren Zeitraum und bildet zunächst für jede Gruppe ein methodisch-didaktisches Profil. Erst danach wird geprüft, ob die Daten eine Eskalationsdisposition anzeigen. Eine Ereignisannahme wird nicht vorangestellt.")
    report_lines.append("")

    for gid, group in sorted(groups.items(), key=lambda x: int(x[0])):
        metrics = instability_metrics(group)
        emo = emotion_metrics(data, gid)
        escalation = build_escalation_disposition(metrics, emo)
        comparison = compare_scopes(data, gid)
        dim_plot = plot_group_dimension_bars(gid, group)
        tl_plot = plot_group_timeline(gid, group)

        summary["gruppen"][gid] = {
            "basisprofil": group,
            "instability_metrics": metrics,
            "emotion_metrics": emo,
            "escalation_disposition": escalation,
            "plots": {
                "dimensionsprofil": dim_plot,
                "dichte_timeline": tl_plot,
            },
        }
        summary["vergleich"][gid] = comparison
        report_lines.append(profile_text(gid, group, metrics, emo, escalation))
        if dim_plot:
            report_lines.append(f"Abbildung: `{Path(dim_plot).name}`")
        if tl_plot:
            report_lines.append(f"Abbildung: `{Path(tl_plot).name}`")
        report_lines.append("")

    # Vergleichstabelle Eskalationsdisposition
    report_lines.append("## Gruppenvergleich der neutralen Eskalationsdisposition")
    report_lines.append("")
    report_lines.append("| Gruppe | Index | Einstufung | n Termine | dominante Dimension |")
    report_lines.append("|---:|---:|---|---:|---|")
    for gid, payload in sorted(summary["gruppen"].items(), key=lambda x: int(x[0])):
        esc = payload["escalation_disposition"]
        group = payload["basisprofil"]
        dom = dominant_dimension(group)
        report_lines.append(
            f"| {gid} | {esc['score']:.3f} | {esc['level']} | {group.get('n_termine')} | {dom[0] if dom else 'n/a'} |"
        )

    REPORT_FILE.write_text("\n".join(report_lines), encoding="utf-8")
    SUMMARY_JSON.write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")
    return summary


def main() -> None:
    summary = analyze()
    print(f"Analyse abgeschlossen: {REPORT_FILE.resolve()}")
    print(f"Summary JSON: {SUMMARY_JSON.resolve()}")
    print(f"Gruppen analysiert: {len(summary.get('gruppen', {}))}")


if __name__ == "__main__":
    main()
