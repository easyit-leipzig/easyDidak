import json
import math
from pathlib import Path
from statistics import mean, median, stdev

import matplotlib.pyplot as plt


INPUT_FILE = "auswertung_14_resonanzfenster_optimale_nachhilfe.json"
OUTPUT_DIR = Path("charts_14_resonanzfenster_optimale_nachhilfe")
OUTPUT_DIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation"
]


def safe_float(value):
    try:
        return float(value)
    except (TypeError, ValueError):
        return 0.0


def vector(row):
    return [safe_float(row.get(dim)) for dim in DIMENSIONS]


def norm(v):
    return math.sqrt(sum(x * x for x in v))


def cosine(a, b):
    na = norm(a)
    nb = norm(b)
    if na == 0 or nb == 0:
        return 0.0
    return sum(x * y for x, y in zip(a, b)) / (na * nb)


def euclidean(a, b):
    return math.sqrt(sum((x - y) ** 2 for x, y in zip(a, b)))


def minmax(values):
    if not values:
        return []
    mn = min(values)
    mx = max(values)
    if mx == mn:
        return [0.5 for _ in values]
    return [(v - mn) / (mx - mn) for v in values]


def gaussian_window(value, center, width):
    if width <= 0:
        return 1.0
    return math.exp(-((value - center) ** 2) / (2 * width ** 2))


def group_trajectories(rows):
    trajectories = {}

    for row in rows:
        key = (
            row.get("lehrkraft_id"),
            row.get("gruppe_id"),
            row.get("teilnehmer_id")
        )
        trajectories.setdefault(key, []).append(row)

    for key in trajectories:
        trajectories[key].sort(
            key=lambda r: (
                str(r.get("datum") or ""),
                int(r.get("id") or 0)
            )
        )

    return trajectories


def enrich_rows(rows):
    trajectories = group_trajectories(rows)
    enriched = []

    for key, seq in trajectories.items():
        previous_v = None
        drift_history = []

        for idx, row in enumerate(seq):
            v = vector(row)

            if previous_v is None:
                drift = 0.0
                coherence = 1.0
            else:
                drift = euclidean(v, previous_v)
                coherence = (cosine(v, previous_v) + 1) / 2

            drift_history.append(drift)

            if len(drift_history) >= 3:
                recent = drift_history[-3:]
                instability = stdev(recent) if len(set(recent)) > 1 else 0.0
            else:
                instability = 0.0

            enriched_row = dict(row)
            enriched_row["trajectory_key"] = str(key)
            enriched_row["trajectory_index"] = idx
            enriched_row["drift"] = drift
            enriched_row["coherence"] = coherence
            enriched_row["instability"] = instability
            enriched_row["motivation"] = safe_float(row.get("x_motivation"))
            enriched_row["polarity_positive"] = 1 if int(row.get("polaritaet_gesamt") or 0) > 0 else 0
            enriched.append(enriched_row)

            previous_v = v

    return enriched


def compute_resonance_scores(rows):
    drifts = [r["drift"] for r in rows]
    coherences = [r["coherence"] for r in rows]
    motivations = [r["motivation"] for r in rows]
    instabilities = [r["instability"] for r in rows]

    drift_center = median(drifts) if drifts else 0.0
    drift_width = stdev(drifts) if len(set(drifts)) > 1 else 1.0

    instability_center = median(instabilities) if instabilities else 0.0
    instability_width = stdev(instabilities) if len(set(instabilities)) > 1 else 1.0

    motivation_scaled = minmax(motivations)
    coherence_scaled = minmax(coherences)

    for i, row in enumerate(rows):
        drift_score = gaussian_window(row["drift"], drift_center, drift_width)
        instability_score = gaussian_window(row["instability"], instability_center, instability_width)

        row["drift_score"] = drift_score
        row["instability_score"] = instability_score
        row["motivation_score"] = motivation_scaled[i]
        row["coherence_score"] = coherence_scaled[i]

        row["resonance_score"] = (
            0.25 * drift_score +
            0.25 * row["coherence_score"] +
            0.20 * row["polarity_positive"] +
            0.20 * row["motivation_score"] +
            0.10 * instability_score
        )

    return rows


def classify_window(score):
    if score >= 0.75:
        return "optimales_resonanzfenster"
    if score >= 0.60:
        return "adaptives_resonanzfenster"
    if score >= 0.45:
        return "uebergangsfenster"
    return "instabiles_oder_traeges_fenster"


def summarize(rows):
    if not rows:
        return {}

    for r in rows:
        r["resonance_class"] = classify_window(r["resonance_score"])

    classes = {}
    for r in rows:
        classes[r["resonance_class"]] = classes.get(r["resonance_class"], 0) + 1

    top_rows = sorted(rows, key=lambda r: r["resonance_score"], reverse=True)[:20]

    return {
        "n": len(rows),
        "mittelwert_resonance_score": mean(r["resonance_score"] for r in rows),
        "median_resonance_score": median(r["resonance_score"] for r in rows),
        "mittelwert_drift": mean(r["drift"] for r in rows),
        "mittelwert_coherence": mean(r["coherence"] for r in rows),
        "mittelwert_motivation": mean(r["motivation"] for r in rows),
        "mittelwert_instability": mean(r["instability"] for r in rows),
        "anteil_positive_polaritaet": mean(r["polarity_positive"] for r in rows),
        "klassen": classes,
        "top_20_resonanzfenster": [
            {
                "id": r.get("id"),
                "lehrkraft_id": r.get("lehrkraft_id"),
                "gruppe_id": r.get("gruppe_id"),
                "teilnehmer_id": r.get("teilnehmer_id"),
                "datum": r.get("datum"),
                "thema": r.get("thema"),
                "resonance_score": round(r["resonance_score"], 4),
                "drift": round(r["drift"], 4),
                "coherence": round(r["coherence"], 4),
                "motivation": round(r["motivation"], 4),
                "instability": round(r["instability"], 4),
                "polaritaet_gesamt": r.get("polaritaet_gesamt"),
                "dominante_dimension": r.get("dominante_dimension"),
                "resonance_class": r["resonance_class"]
            }
            for r in top_rows
        ]
    }


def save_scatter(scope_name, rows):
    x = [r["drift"] for r in rows]
    y = [r["coherence"] for r in rows]
    sizes = [40 + 160 * r["resonance_score"] for r in rows]

    plt.figure(figsize=(10, 7))
    plt.scatter(x, y, s=sizes, alpha=0.65)
    plt.xlabel("Drift ΔS(t)")
    plt.ylabel("Kohärenz cos(S_t, S_t-1)")
    plt.title(f"Resonanzfenster: Drift vs. Kohärenz – {scope_name}")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_01_drift_vs_kohaerenz.png", dpi=200)
    plt.close()


def save_score_distribution(scope_name, rows):
    scores = [r["resonance_score"] for r in rows]

    plt.figure(figsize=(10, 6))
    plt.hist(scores, bins=20, alpha=0.75)
    plt.xlabel("Resonance Score")
    plt.ylabel("Häufigkeit")
    plt.title(f"Verteilung der Resonanzfenster – {scope_name}")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_02_resonance_score_verteilung.png", dpi=200)
    plt.close()


def save_class_bar(scope_name, summary_data):
    classes = summary_data.get("klassen", {})
    labels = list(classes.keys())
    values = list(classes.values())

    plt.figure(figsize=(11, 6))
    plt.bar(labels, values)
    plt.xticks(rotation=25, ha="right")
    plt.ylabel("Anzahl")
    plt.title(f"Klassen funktionaler Resonanzfenster – {scope_name}")
    plt.grid(True, axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_03_resonanzklassen.png", dpi=200)
    plt.close()


def save_motivation_resonance(scope_name, rows):
    x = [r["motivation"] for r in rows]
    y = [r["resonance_score"] for r in rows]

    plt.figure(figsize=(10, 7))
    plt.scatter(x, y, alpha=0.65)
    plt.xlabel("Motivation")
    plt.ylabel("Resonance Score")
    plt.title(f"Motivation und optimales Nachhilfe-Resonanzfenster – {scope_name}")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"{scope_name}_04_motivation_vs_resonance.png", dpi=200)
    plt.close()


def main():
    data = json.loads(Path(INPUT_FILE).read_text(encoding="utf-8"))

    output = {
        "auswertung": data.get("auswertung"),
        "modell": {
            "resonanzfenster": (
                "Ein optimales Nachhilfe-Resonanzfenster liegt vor, wenn Drift weder "
                "zu niedrig noch zu hoch ist, Kohärenz hoch bleibt, Polarität positiv ist, "
                "Motivation hoch ausfällt und Instabilität moderat bleibt."
            ),
            "score": (
                "0.25*mittlere_Drift + 0.25*Kohärenz + 0.20*positive_Polarität "
                "+ 0.20*Motivation + 0.10*moderate_Instabilität"
            )
        },
        "scopes": {}
    }

    for scope_name, scope_data in data["scopes"].items():
        rows = scope_data.get("daten", [])
        enriched = enrich_rows(rows)
        scored = compute_resonance_scores(enriched)
        summary_data = summarize(scored)

        output["scopes"][scope_name] = summary_data

        if scored:
            save_scatter(scope_name, scored)
            save_score_distribution(scope_name, scored)
            save_class_bar(scope_name, summary_data)
            save_motivation_resonance(scope_name, scored)

    out_file = OUTPUT_DIR / "auswertung_14_resonanzfenster_optimale_nachhilfe_summary.json"
    out_file.write_text(json.dumps(output, ensure_ascii=False, indent=2), encoding="utf-8")

    print("Analyse abgeschlossen.")
    print(f"Ergebnisse: {out_file}")
    print(f"Diagramme: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()