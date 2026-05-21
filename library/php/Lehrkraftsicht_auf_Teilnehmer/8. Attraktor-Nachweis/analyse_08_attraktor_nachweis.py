import json
import math
from pathlib import Path

import matplotlib.pyplot as plt
import numpy as np


INPUT_JSON = "auswertung_08_attraktor_nachweis.json"
OUTPUT_DIR = Path("auswertung_08_attraktor_nachweis_output")
OUTPUT_DIR.mkdir(exist_ok=True)


def safe_mean(values):
    values = [v for v in values if v is not None and not math.isnan(v)]
    return float(np.mean(values)) if values else 0.0


def safe_std(values):
    values = [v for v in values if v is not None and not math.isnan(v)]
    return float(np.std(values)) if values else 0.0


def classify_attractor(similarity, drift, strength):
    if similarity >= 0.90 and drift <= 0.25:
        return "starker Attraktor"
    if similarity >= 0.80 and drift <= 0.50:
        return "mittlerer Attraktor"
    if similarity >= 0.70:
        return "schwacher Attraktor"
    return "kein stabiler Attraktor"


def save_bar_chart(scope_name, labels, values, title, ylabel, filename):
    plt.figure(figsize=(10, 6))
    plt.bar(labels, values)
    plt.title(title)
    plt.ylabel(ylabel)
    plt.xticks(rotation=20, ha="right")
    plt.tight_layout()

    out = OUTPUT_DIR / filename
    plt.savefig(out, dpi=300)
    plt.close()

    return str(out)


def save_scatter(scope_name, x, y, title, xlabel, ylabel, filename):
    plt.figure(figsize=(8, 6))
    plt.scatter(x, y)
    plt.title(title)
    plt.xlabel(xlabel)
    plt.ylabel(ylabel)
    plt.tight_layout()

    out = OUTPUT_DIR / filename
    plt.savefig(out, dpi=300)
    plt.close()

    return str(out)


def save_histogram(scope_name, values, title, xlabel, filename):
    plt.figure(figsize=(8, 6))
    plt.hist(values, bins=20)
    plt.title(title)
    plt.xlabel(xlabel)
    plt.ylabel("Häufigkeit")
    plt.tight_layout()

    out = OUTPUT_DIR / filename
    plt.savefig(out, dpi=300)
    plt.close()

    return str(out)


def save_top_participants_chart(scope_name, participants):
    top = sorted(
        participants,
        key=lambda p: p.get("attractor_strength", 0),
        reverse=True
    )[:15]

    labels = [str(p["teilnehmer_id"]) for p in top]
    values = [p.get("attractor_strength", 0) for p in top]

    return save_bar_chart(
        scope_name,
        labels,
        values,
        f"Top-Attraktorstärken – {scope_name}",
        "Attraktorstärke",
        f"{scope_name}_top_attraktorstaerke.png"
    )


def analyse_scope(scope_name, scope_data):
    participants = scope_data.get("participants", [])

    similarities = [
        float(p.get("mean_cosine_similarity", 0))
        for p in participants
    ]

    drifts = [
        float(p.get("mean_drift", 0))
        for p in participants
    ]

    strengths = [
        float(p.get("attractor_strength", 0))
        for p in participants
    ]

    n_datensaetze = scope_data.get("n_datensaetze", 0)
    n_teilnehmer = scope_data.get("n_teilnehmer", len(participants))

    global_similarity = float(scope_data.get("global_similarity", safe_mean(similarities)))
    global_drift = float(scope_data.get("global_drift", safe_mean(drifts)))
    global_strength = float(scope_data.get("global_attractor_strength", 0))

    classification = classify_attractor(
        global_similarity,
        global_drift,
        global_strength
    )

    print("\n" + "=" * 80)
    print(f"SCOPE: {scope_name}")
    print("=" * 80)
    print(f"Datensätze: {n_datensaetze}")
    print(f"Teilnehmer mit Verlauf: {n_teilnehmer}")
    print(f"Mittlere Cosine Similarity: {global_similarity:.6f}")
    print(f"Mittlere Drift: {global_drift:.6f}")
    print(f"Globale Attraktorstärke: {global_strength:.6f}")
    print(f"FRZK-Einstufung: {classification}")

    print("\nVerteilungskennzahlen:")
    print(f"Similarity Mittelwert: {safe_mean(similarities):.6f}")
    print(f"Similarity Std:        {safe_std(similarities):.6f}")
    print(f"Drift Mittelwert:      {safe_mean(drifts):.6f}")
    print(f"Drift Std:             {safe_std(drifts):.6f}")
    print(f"Attraktor Mittelwert:  {safe_mean(strengths):.6f}")
    print(f"Attraktor Std:         {safe_std(strengths):.6f}")

    strong = [
        p for p in participants
        if p.get("mean_cosine_similarity", 0) >= 0.90
        and p.get("mean_drift", 999) <= 0.25
    ]

    medium = [
        p for p in participants
        if p.get("mean_cosine_similarity", 0) >= 0.80
        and p.get("mean_drift", 999) <= 0.50
        and p not in strong
    ]

    weak = [
        p for p in participants
        if p.get("mean_cosine_similarity", 0) >= 0.70
        and p not in strong
        and p not in medium
    ]

    print("\nAttraktor-Kandidaten:")
    print(f"Starke Attraktoren:   {len(strong)}")
    print(f"Mittlere Attraktoren: {len(medium)}")
    print(f"Schwache Attraktoren: {len(weak)}")

    print("\nTop 10 Teilnehmer nach Attraktorstärke:")
    top10 = sorted(
        participants,
        key=lambda p: p.get("attractor_strength", 0),
        reverse=True
    )[:10]

    for p in top10:
        print(
            f"Teilnehmer {p['teilnehmer_id']}: "
            f"n={p.get('anzahl_zustaende', 0)}, "
            f"cos={p.get('mean_cosine_similarity', 0):.4f}, "
            f"drift={p.get('mean_drift', 0):.4f}, "
            f"attr={p.get('attractor_strength', 0):.4f}"
        )

    chart_files = []

    if participants:
        chart_files.append(
            save_top_participants_chart(scope_name, participants)
        )

    if similarities:
        chart_files.append(
            save_histogram(
                scope_name,
                similarities,
                f"Verteilung der Cosine Similarity – {scope_name}",
                "Cosine Similarity",
                f"{scope_name}_hist_cosine_similarity.png"
            )
        )

    if drifts:
        chart_files.append(
            save_histogram(
                scope_name,
                drifts,
                f"Verteilung der Drift – {scope_name}",
                "Drift",
                f"{scope_name}_hist_drift.png"
            )
        )

    if similarities and drifts:
        chart_files.append(
            save_scatter(
                scope_name,
                drifts,
                similarities,
                f"Attraktor-Nachweis: Drift vs. Selbstähnlichkeit – {scope_name}",
                "mittlere Drift",
                "mittlere Cosine Similarity",
                f"{scope_name}_scatter_drift_vs_similarity.png"
            )
        )

    return {
        "scope": scope_name,
        "n_datensaetze": n_datensaetze,
        "n_teilnehmer": n_teilnehmer,
        "global_similarity": global_similarity,
        "global_drift": global_drift,
        "global_attractor_strength": global_strength,
        "classification": classification,
        "strong_attractors": len(strong),
        "medium_attractors": len(medium),
        "weak_attractors": len(weak),
        "chart_files": chart_files,
        "top10": top10
    }


def write_summary(results):
    summary_path = OUTPUT_DIR / "auswertung_08_attraktor_nachweis_summary.txt"

    with open(summary_path, "w", encoding="utf-8") as f:
        f.write("Auswertung 08 – Attraktor-Nachweis\n")
        f.write("=" * 80 + "\n\n")

        f.write(
            "FRZK-Prüflogik:\n"
            "Ein semantischer Attraktor liegt vor, wenn Zustände eines Teilnehmers "
            "wiederholt in ähnliche Bereiche des siebendimensionalen FRZK-Raums "
            "zurückkehren. Operationalisiert wird dies über hohe Cosine Similarity, "
            "geringe Drift und daraus abgeleitete Attraktorstärke.\n\n"
        )

        for r in results:
            f.write("-" * 80 + "\n")
            f.write(f"SCOPE: {r['scope']}\n")
            f.write("-" * 80 + "\n")
            f.write(f"Datensätze: {r['n_datensaetze']}\n")
            f.write(f"Teilnehmer mit Verlauf: {r['n_teilnehmer']}\n")
            f.write(f"Mittlere Cosine Similarity: {r['global_similarity']:.6f}\n")
            f.write(f"Mittlere Drift: {r['global_drift']:.6f}\n")
            f.write(f"Globale Attraktorstärke: {r['global_attractor_strength']:.6f}\n")
            f.write(f"FRZK-Einstufung: {r['classification']}\n")
            f.write(f"Starke Attraktoren: {r['strong_attractors']}\n")
            f.write(f"Mittlere Attraktoren: {r['medium_attractors']}\n")
            f.write(f"Schwache Attraktoren: {r['weak_attractors']}\n\n")

            f.write("Grafiken:\n")
            for chart in r["chart_files"]:
                f.write(f"- {chart}\n")

            f.write("\nTop 10 Teilnehmer nach Attraktorstärke:\n")
            for p in r["top10"]:
                f.write(
                    f"- Teilnehmer {p['teilnehmer_id']}: "
                    f"n={p.get('anzahl_zustaende', 0)}, "
                    f"cos={p.get('mean_cosine_similarity', 0):.4f}, "
                    f"drift={p.get('mean_drift', 0):.4f}, "
                    f"attr={p.get('attractor_strength', 0):.4f}\n"
                )

            f.write("\n")

    return summary_path


def main():
    with open(INPUT_JSON, "r", encoding="utf-8") as f:
        data = json.load(f)

    results = []

    # Variante A: neue direkte Struktur:
    # {
    #   "alle_lehrkraefte": {...},
    #   "nur_lehrkraft_1": {...},
    #   "ohne_lehrkraft_1": {...}
    # }
    if "scopes" not in data:
        scope_items = data.items()

    # Variante B: alte verschachtelte Struktur:
    # {
    #   "scopes": {
    #       "alle_lehrkraefte": {...}
    #   }
    # }
    else:
        scope_items = data["scopes"].items()

    for scope_name, scope_data in scope_items:
        results.append(analyse_scope(scope_name, scope_data))

    summary_file = write_summary(results)

    print("\n" + "=" * 80)
    print("AUSWERTUNG ABGESCHLOSSEN")
    print("=" * 80)
    print(f"Output-Ordner: {OUTPUT_DIR}")
    print(f"Summary: {summary_file}")


if __name__ == "__main__":
    main()