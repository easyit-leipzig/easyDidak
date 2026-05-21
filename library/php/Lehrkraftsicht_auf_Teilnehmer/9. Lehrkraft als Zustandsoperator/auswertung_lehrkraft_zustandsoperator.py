import json
from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd


INPUT_JSON = "auswertung_09_lehrkraft_zustandsoperator.json"
OUTPUT_DIR = Path("auswertung_09_lehrkraft_zustandsoperator_output")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation"
]


def ensure_output_dir():
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)


def load_data():
    with open(INPUT_JSON, "r", encoding="utf-8") as f:
        return json.load(f)


def transitions_to_dataframe(scope_name, scope_data):
    transitions = scope_data.get("transitions", [])

    rows = []
    for t in transitions:
        row = {
            "scope": scope_name,
            "teilnehmer_id": t.get("teilnehmer_id"),
            "datum_before": t.get("datum_before"),
            "datum_after": t.get("datum_after"),
            "lehrkraft_id_before": t.get("lehrkraft_id_before"),
            "lehrkraft_id_after": t.get("lehrkraft_id_after"),
            "d_semantisch_before": t.get("d_semantisch_before"),
            "d_semantisch_after": t.get("d_semantisch_after"),
            "delta_d_semantisch": t.get("delta_d_semantisch"),
            "driftgeschwindigkeit": t.get("driftgeschwindigkeit"),
            "stabilisierung": t.get("stabilisierung"),
            "resonanz_cosine": t.get("resonanz_cosine"),
            "resonanzbildung": t.get("resonanzbildung"),
            "dominante_dimension_before": t.get("dominante_dimension_before"),
            "dominante_dimension_after": t.get("dominante_dimension_after"),
            "polaritaet_before": t.get("polaritaet_before"),
            "polaritaet_after": t.get("polaritaet_after"),
        }

        for dim in DIMENSIONS:
            row[f"delta_{dim}"] = t.get("delta_vector", {}).get(dim)

        rows.append(row)

    return pd.DataFrame(rows)


def records_to_dataframe(scope_name, scope_data):
    records = scope_data.get("records", [])

    rows = []
    for r in records:
        row = {
            "scope": scope_name,
            "id": r.get("id"),
            "datum": r.get("datum"),
            "teilnehmer_id": r.get("teilnehmer_id"),
            "gruppe_id": r.get("gruppe_id"),
            "lehrkraft_id": r.get("lehrkraft_id"),
            "d_semantisch": r.get("d_semantisch"),
            "dominante_dimension": r.get("dominante_dimension"),
            "dominante_dimension_wert": r.get("dominante_dimension_wert"),
            "polaritaet_gesamt": r.get("polaritaet_gesamt"),
            "operator_count": r.get("operator_count"),
            "modulator_count": r.get("modulator_count"),
        }

        for dim in DIMENSIONS:
            row[f"x_{dim}"] = r.get(f"x_{dim}")

        rows.append(row)

    return pd.DataFrame(rows)


def save_summary_text(data, all_transitions):
    lines = []
    lines.append("9. Lehrkraft als Zustandsoperator – FRZK-Auswertung")
    lines.append("=" * 70)
    lines.append("")

    for scope_name, scope_data in data["scopes"].items():
        s = scope_data.get("summary", {})
        lines.append(f"SCOPE: {scope_name}")
        lines.append("-" * 70)
        lines.append(f"Datensätze: {s.get('records', 0)}")
        lines.append(f"Zustandsübergänge: {s.get('transitions', 0)}")
        lines.append(f"Teilnehmer: {s.get('unique_teilnehmer', 0)}")
        lines.append(f"Lehrkräfte: {s.get('unique_lehrkraefte', 0)}")
        lines.append(f"Mittlere semantische Dichte: {s.get('mean_d_semantisch')}")
        lines.append(f"Mittlere Driftgeschwindigkeit: {s.get('mean_driftgeschwindigkeit')}")
        lines.append(f"Mittlere Stabilisierung: {s.get('mean_stabilisierung')}")
        lines.append(f"Mittlere Resonanz/Cosine: {s.get('mean_resonanz_cosine')}")
        lines.append(f"Mittlere ΔDichte: {s.get('mean_delta_d_semantisch')}")
        lines.append("")

    lines.append("FRZK-Interpretation")
    lines.append("-" * 70)
    lines.append(
        "Die Lehrkraft wird hier nicht als Bewertungsinstanz modelliert, "
        "sondern als Operator, dessen Wirkung an Zustandsübergängen sichtbar wird. "
        "Ein Übergang S(t) → S(t+1) gilt als lehrkraftbezogene Transformation, "
        "wenn sich semantische Dichte, Drift, Stabilisierung und Resonanz zwischen "
        "aufeinanderfolgenden Teilnehmerzuständen messbar verändern."
    )
    lines.append("")
    lines.append(
        "Hohe Driftwerte zeigen starke Transformationen im semantischen Raum. "
        "Hohe Stabilisierung bei gleichzeitig positiver Resonanz zeigt, dass die "
        "Transformation nicht beliebig streut, sondern in einen anschlussfähigen "
        "Zustandsraum überführt. Genau darin liegt die FRZK-Bedeutung der Lehrkraft "
        "als Zustandsoperator."
    )
    lines.append("")

    all_transitions.to_csv(
        OUTPUT_DIR / "auswertung_09_transitions_gesamt.csv",
        index=False,
        encoding="utf-8-sig"
    )

    Path(OUTPUT_DIR / "auswertung_09_summary.txt").write_text(
        "\n".join(lines),
        encoding="utf-8"
    )


def plot_metric_by_scope(all_transitions, metric, title, filename):
    if all_transitions.empty:
        return

    grouped = all_transitions.groupby("scope")[metric].mean().sort_index()

    plt.figure(figsize=(10, 6))
    grouped.plot(kind="bar")
    plt.title(title)
    plt.ylabel(metric)
    plt.xlabel("Scope")
    plt.xticks(rotation=25, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / filename, dpi=300)
    plt.close()


def plot_density_before_after(all_transitions):
    if all_transitions.empty:
        return

    grouped = all_transitions.groupby("scope")[
        ["d_semantisch_before", "d_semantisch_after"]
    ].mean()

    plt.figure(figsize=(10, 6))
    grouped.plot(kind="bar")
    plt.title("Vorher-Nachher-Vergleich der semantischen Dichte")
    plt.ylabel("mittlere semantische Dichte")
    plt.xlabel("Scope")
    plt.xticks(rotation=25, ha="right")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_09_1_dichte_vorher_nachher.png", dpi=300)
    plt.close()


def plot_dimension_delta_heatmap(all_transitions):
    if all_transitions.empty:
        return

    delta_cols = [f"delta_{dim}" for dim in DIMENSIONS]
    heat = all_transitions.groupby("scope")[delta_cols].mean()

    plt.figure(figsize=(11, 5))
    plt.imshow(heat.values, aspect="auto")
    plt.colorbar(label="mittleres Δ je Dimension")
    plt.xticks(range(len(delta_cols)), DIMENSIONS, rotation=45, ha="right")
    plt.yticks(range(len(heat.index)), heat.index)
    plt.title("Dimensionale Transformationswirkung der Lehrkraft")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_09_5_dimensionale_transformationswirkung.png", dpi=300)
    plt.close()


def plot_scatter_resonance_drift(all_transitions):
    if all_transitions.empty:
        return

    plt.figure(figsize=(8, 6))
    plt.scatter(
        all_transitions["driftgeschwindigkeit"],
        all_transitions["resonanz_cosine"],
        alpha=0.65
    )
    plt.title("Driftgeschwindigkeit vs. Resonanzbildung")
    plt.xlabel("Driftgeschwindigkeit")
    plt.ylabel("Resonanz/Cosine")
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / "abb_09_4_drift_vs_resonanz.png", dpi=300)
    plt.close()


def main():
    ensure_output_dir()
    data = load_data()

    transition_frames = []
    record_frames = []

    for scope_name, scope_data in data["scopes"].items():
        df_t = transitions_to_dataframe(scope_name, scope_data)
        df_r = records_to_dataframe(scope_name, scope_data)

        if not df_t.empty:
            df_t.to_csv(
                OUTPUT_DIR / f"transitions_{scope_name}.csv",
                index=False,
                encoding="utf-8-sig"
            )
            transition_frames.append(df_t)

        if not df_r.empty:
            df_r.to_csv(
                OUTPUT_DIR / f"records_{scope_name}.csv",
                index=False,
                encoding="utf-8-sig"
            )
            record_frames.append(df_r)

    all_transitions = (
        pd.concat(transition_frames, ignore_index=True)
        if transition_frames else pd.DataFrame()
    )

    save_summary_text(data, all_transitions)

    plot_density_before_after(all_transitions)

    plot_metric_by_scope(
        all_transitions,
        "driftgeschwindigkeit",
        "Mittlere Driftgeschwindigkeit nach Scope",
        "abb_09_2_driftgeschwindigkeit_scope.png"
    )

    plot_metric_by_scope(
        all_transitions,
        "stabilisierung",
        "Mittlere Stabilisierung nach Scope",
        "abb_09_3_stabilisierung_scope.png"
    )

    plot_scatter_resonance_drift(all_transitions)
    plot_dimension_delta_heatmap(all_transitions)

    print(f"Analyse abgeschlossen. Ergebnisse in: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()