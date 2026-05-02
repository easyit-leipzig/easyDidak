import json
import math
import os
from collections import Counter, defaultdict

import matplotlib.pyplot as plt


INPUT_FILE = "dominanz_polarisierung_type3.json"
OUTPUT_DIR = "dominanz_polarisierung_auswertung"


def ensure_dir(path):
    os.makedirs(path, exist_ok=True)


def load_data(path):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def pct(part, whole):
    return round((part / whole) * 100, 2) if whole else 0.0


def summarize_subset(rows):
    n = len(rows)
    dom_counter = Counter(r["berechnet"]["dominante_dimension"] for r in rows)
    pol_counter = Counter(r["berechnet"]["polaritaet"] for r in rows)

    dom_strengths = [abs(float(r["berechnet"]["dominanzstaerke"])) for r in rows]
    densities = [float(r["berechnet"]["d_semantisch"]) for r in rows]
    sums = [float(r["berechnet"]["summe_dimensionen"]) for r in rows]

    mean_dom_strength = sum(dom_strengths) / n if n else 0.0
    mean_density = sum(densities) / n if n else 0.0
    mean_sum = sum(sums) / n if n else 0.0

    return {
        "n": n,
        "dominanzverteilung": dict(dom_counter),
        "polaritaetsverteilung": dict(pol_counter),
        "mittlere_dominanzstaerke": round(mean_dom_strength, 6),
        "mittlere_semantische_dichte": round(mean_density, 6),
        "mittlere_dimensionssumme": round(mean_sum, 6),
        "positiv_quote": pct(pol_counter.get(1, 0), n),
        "negativ_quote": pct(pol_counter.get(-1, 0), n),
        "neutral_quote": pct(pol_counter.get(0, 0), n),
    }


def create_text_report(payload, output_path):
    rows = payload["daten"]
    summary = payload["zusammenfassung"]

    by_group = defaultdict(list)
    by_teacher = defaultdict(list)
    by_subject = defaultdict(list)

    for row in rows:
        by_group[str(row["gruppe_id"])].append(row)
        by_teacher[str(row["lehrkraft_id"])].append(row)
        by_subject[str(row["fach"])].append(row)

    mismatches_dom = sum(1 for r in rows if not r["konsistenzcheck"]["dominante_dimension_identisch"])
    mismatches_pol = sum(1 for r in rows if not r["konsistenzcheck"]["polaritaet_identisch"])

    lines = []
    lines.append("6.x.4 Dominanz- und Polarisierungsanalyse – Auswertungsbericht")
    lines.append("=" * 70)
    lines.append("")
    lines.append(f"Quelle: {payload['quelle']}")
    lines.append(f"Anzahl Sätze: {summary['anzahl_saetze']}")
    lines.append(f"Mittlere Dominanzstärke: {summary['mittlere_dominanzstaerke']}")
    lines.append(f"Mittlere semantische Dichte: {summary['mittlere_semantische_dichte']}")
    lines.append("")
    lines.append("Globale Dominanzverteilung:")
    for k, v in sorted(summary["dominanzverteilung"].items(), key=lambda x: (-x[1], x[0])):
        lines.append(f"  - {k}: {v}")
    lines.append("")
    lines.append("Globale Polaritätsverteilung:")
    for k, v in sorted(summary["polaritaetsverteilung"].items(), key=lambda x: str(x[0])):
        lines.append(f"  - {k}: {v}")
    lines.append("")
    lines.append("Konsistenzcheck zwischen View und Neuberechnung:")
    lines.append(f"  - Abweichungen dominante Dimension: {mismatches_dom}")
    lines.append(f"  - Abweichungen Polarität: {mismatches_pol}")
    lines.append("")

    lines.append("Analyse nach Gruppe")
    lines.append("-" * 70)
    for key in sorted(by_group.keys(), key=lambda x: int(x) if x.isdigit() else x):
        s = summarize_subset(by_group[key])
        lines.append(
            f"Gruppe {key}: n={s['n']}, "
            f"mittl. Dominanz={s['mittlere_dominanzstaerke']:.4f}, "
            f"mittl. Dichte={s['mittlere_semantische_dichte']:.4f}, "
            f"+={s['positiv_quote']:.2f}%, -={s['negativ_quote']:.2f}%, 0={s['neutral_quote']:.2f}%"
        )
        dom_sorted = sorted(s["dominanzverteilung"].items(), key=lambda x: (-x[1], x[0]))
        lines.append("    Dominanz: " + ", ".join(f"{k}={v}" for k, v in dom_sorted))
    lines.append("")

    lines.append("Analyse nach Lehrkraft")
    lines.append("-" * 70)
    for key in sorted(by_teacher.keys(), key=lambda x: int(x) if x.isdigit() else x):
        s = summarize_subset(by_teacher[key])
        lines.append(
            f"Lehrkraft {key}: n={s['n']}, "
            f"mittl. Dominanz={s['mittlere_dominanzstaerke']:.4f}, "
            f"mittl. Dichte={s['mittlere_semantische_dichte']:.4f}, "
            f"+={s['positiv_quote']:.2f}%, -={s['negativ_quote']:.2f}%, 0={s['neutral_quote']:.2f}%"
        )
        dom_sorted = sorted(s["dominanzverteilung"].items(), key=lambda x: (-x[1], x[0]))
        lines.append("    Dominanz: " + ", ".join(f"{k}={v}" for k, v in dom_sorted))
    lines.append("")

    lines.append("Analyse nach Fach")
    lines.append("-" * 70)
    for key in sorted(by_subject.keys()):
        s = summarize_subset(by_subject[key])
        lines.append(
            f"Fach {key}: n={s['n']}, "
            f"mittl. Dominanz={s['mittlere_dominanzstaerke']:.4f}, "
            f"mittl. Dichte={s['mittlere_semantische_dichte']:.4f}, "
            f"+={s['positiv_quote']:.2f}%, -={s['negativ_quote']:.2f}%, 0={s['neutral_quote']:.2f}%"
        )
        dom_sorted = sorted(s["dominanzverteilung"].items(), key=lambda x: (-x[1], x[0]))
        lines.append("    Dominanz: " + ", ".join(f"{k}={v}" for k, v in dom_sorted))

    with open(output_path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))


def plot_global_dominance(rows, output_dir):
    counter = Counter(r["berechnet"]["dominante_dimension"] for r in rows)
    labels = list(counter.keys())
    values = list(counter.values())

    plt.figure(figsize=(10, 6))
    plt.bar(labels, values)
    plt.title("Globale Dominanzverteilung")
    plt.xlabel("Dominante Dimension")
    plt.ylabel("Häufigkeit")
    plt.xticks(rotation=30)
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, "01_globale_dominanzverteilung.png"), dpi=200)
    plt.close()


def plot_global_polarity(rows, output_dir):
    counter = Counter(r["berechnet"]["polaritaet"] for r in rows)
    labels = [str(k) for k in sorted(counter.keys())]
    values = [counter[int(k)] for k in labels]

    plt.figure(figsize=(8, 5))
    plt.bar(labels, values)
    plt.title("Globale Polaritätsverteilung")
    plt.xlabel("Polarität (-1 / 0 / +1)")
    plt.ylabel("Häufigkeit")
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, "02_globale_polaritaetsverteilung.png"), dpi=200)
    plt.close()


def plot_density_by_group(rows, output_dir):
    by_group = defaultdict(list)
    for r in rows:
        by_group[str(r["gruppe_id"])].append(float(r["berechnet"]["d_semantisch"]))

    labels = sorted(by_group.keys(), key=lambda x: int(x) if x.isdigit() else x)
    values = [sum(by_group[k]) / len(by_group[k]) for k in labels]

    plt.figure(figsize=(12, 6))
    plt.bar(labels, values)
    plt.title("Mittlere semantische Dichte nach Gruppe")
    plt.xlabel("Gruppe")
    plt.ylabel("Mittlere semantische Dichte")
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, "03_dichte_nach_gruppe.png"), dpi=200)
    plt.close()


def plot_dominance_strength_hist(rows, output_dir):
    values = [abs(float(r["berechnet"]["dominanzstaerke"])) for r in rows]

    plt.figure(figsize=(9, 5))
    plt.hist(values, bins=20)
    plt.title("Verteilung der Dominanzstärke")
    plt.xlabel("Dominanzstärke |δ(S_i)|")
    plt.ylabel("Häufigkeit")
    plt.tight_layout()
    plt.savefig(os.path.join(output_dir, "04_hist_dominanzstaerke.png"), dpi=200)
    plt.close()


def main():
    ensure_dir(OUTPUT_DIR)
    payload = load_data(INPUT_FILE)
    rows = payload["daten"]

    create_text_report(payload, os.path.join(OUTPUT_DIR, "auswertungsbericht.txt"))
    plot_global_dominance(rows, OUTPUT_DIR)
    plot_global_polarity(rows, OUTPUT_DIR)
    plot_density_by_group(rows, OUTPUT_DIR)
    plot_dominance_strength_hist(rows, OUTPUT_DIR)

    print("Auswertung abgeschlossen.")
    print(f"Ergebnisse liegen in: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()