# -*- coding: utf-8 -*-
"""
Analyse 01 – Stabilisierungsvorhersage des FRZK

Liest:
    auswertung_01_stabilisierungsvorhersage.json

Erzeugt:
    - CSV-Zusammenfassungen
    - Textauswertung
    - Grafiken
"""

import json
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt


INPUT_FILE = Path("auswertung_01_stabilisierungsvorhersage.json")
OUTPUT_DIR = Path("auswertung_01_stabilisierungsvorhersage_output")
OUTPUT_DIR.mkdir(exist_ok=True)


def load_data():
    with INPUT_FILE.open("r", encoding="utf-8") as f:
        return json.load(f)


def metrics_dataframe(scope_data):
    rows = scope_data["metrics"]["teilnehmer_metrics"]
    return pd.DataFrame(rows)


def save_scope_tables(data):
    all_frames = []

    for scope_name, scope_data in data["scopes"].items():
        df = metrics_dataframe(scope_data)
        if df.empty:
            continue

        df.insert(0, "scope", scope_name)
        df.to_csv(
            OUTPUT_DIR / f"metrics_{scope_name}.csv",
            index=False,
            encoding="utf-8-sig"
        )
        all_frames.append(df)

    if all_frames:
        all_df = pd.concat(all_frames, ignore_index=True)
        all_df.to_csv(
            OUTPUT_DIR / "metrics_alle_scopes.csv",
            index=False,
            encoding="utf-8-sig"
        )
        return all_df

    return pd.DataFrame()


def plot_bar_scope_summary(data):
    rows = []

    for scope_name, scope_data in data["scopes"].items():
        summary = scope_data["metrics"]["scope_summary"]
        rows.append({
            "scope": scope_name,
            "mittlere_stabilitaet": summary["mittlere_stabilitaet"],
            "mittlere_delta_bewegung": summary["mittlere_delta_bewegung"],
            "mittlere_dominanzstabilitaet": summary["mittlere_dominanzstabilitaet"],
            "mittlerer_kohaerenz_index": summary["mittlerer_kohaerenz_index"],
        })

    df = pd.DataFrame(rows)
    df.to_csv(
        OUTPUT_DIR / "scope_summary.csv",
        index=False,
        encoding="utf-8-sig"
    )

    for col in [
        "mittlere_stabilitaet",
        "mittlere_delta_bewegung",
        "mittlere_dominanzstabilitaet",
        "mittlerer_kohaerenz_index",
    ]:
        plt.figure(figsize=(10, 6))
        plt.bar(df["scope"], df[col])
        plt.title(f"Auswertung 01 – {col}")
        plt.xlabel("Scope")
        plt.ylabel(col)
        plt.xticks(rotation=20, ha="right")
        plt.tight_layout()
        plt.savefig(OUTPUT_DIR / f"abb_01_{col}.png", dpi=300)
        plt.close()


def plot_top_stable_participants(data, top_n=20):
    for scope_name, scope_data in data["scopes"].items():
        df = metrics_dataframe(scope_data)

        if df.empty or "stabilitaet_std_d_semantisch" not in df.columns:
            continue

        df = df.dropna(subset=["stabilitaet_std_d_semantisch"])
        df = df.sort_values("stabilitaet_std_d_semantisch").head(top_n)

        if df.empty:
            continue

        plt.figure(figsize=(12, 7))
        plt.bar(
            df["teilnehmer_id"].astype(str),
            df["stabilitaet_std_d_semantisch"]
        )
        plt.title(
            f"Stabilste Teilnehmer nach semantischer Varianz – {scope_name}"
        )
        plt.xlabel("Teilnehmer-ID")
        plt.ylabel("STDDEV(d_semantisch)")
        plt.xticks(rotation=45, ha="right")
        plt.tight_layout()
        plt.savefig(
            OUTPUT_DIR / f"abb_01_stabilste_teilnehmer_{scope_name}.png",
            dpi=300
        )
        plt.close()


def plot_delta_vs_stability(data):
    for scope_name, scope_data in data["scopes"].items():
        df = metrics_dataframe(scope_data)

        needed = [
            "stabilitaet_std_d_semantisch",
            "mittlere_delta_bewegung"
        ]

        if df.empty or not all(c in df.columns for c in needed):
            continue

        df = df.dropna(subset=needed)

        if df.empty:
            continue

        plt.figure(figsize=(9, 7))
        plt.scatter(
            df["stabilitaet_std_d_semantisch"],
            df["mittlere_delta_bewegung"]
        )
        plt.title(
            f"Stabilität vs. Delta-Bewegung – {scope_name}"
        )
        plt.xlabel("STDDEV(d_semantisch)")
        plt.ylabel("mittlere Delta-Bewegung")
        plt.tight_layout()
        plt.savefig(
            OUTPUT_DIR / f"abb_01_stabilitaet_vs_delta_{scope_name}.png",
            dpi=300
        )
        plt.close()


def plot_coherence_distribution(data):
    for scope_name, scope_data in data["scopes"].items():
        df = metrics_dataframe(scope_data)

        if df.empty or "kohaerenz_index" not in df.columns:
            continue

        df = df.dropna(subset=["kohaerenz_index"])

        if df.empty:
            continue

        plt.figure(figsize=(10, 6))
        plt.hist(df["kohaerenz_index"], bins=20)
        plt.title(f"Verteilung des Kohärenzindex – {scope_name}")
        plt.xlabel("Kohärenzindex = 1 / (1 + STDDEV(d_semantisch))")
        plt.ylabel("Anzahl Teilnehmer")
        plt.tight_layout()
        plt.savefig(
            OUTPUT_DIR / f"abb_01_kohaerenzverteilung_{scope_name}.png",
            dpi=300
        )
        plt.close()


def write_text_report(data):
    lines = []

    lines.append("# Auswertung 01 – Stabilisierungsvorhersage des FRZK\n")
    lines.append(
        "FRZK-Vorhersage: Wiederholte kohärente Interaktion erzeugt "
        "stabile Zustandsräume.\n"
    )

    for scope_name, scope_data in data["scopes"].items():
        metrics = scope_data["metrics"]
        summary = metrics["scope_summary"]

        lines.append(f"## Scope: {scope_name}\n")
        lines.append(f"- Datensätze: {metrics['anzahl_datensaetze']}")
        lines.append(f"- Teilnehmer: {metrics['anzahl_teilnehmer']}")
        lines.append(
            f"- Mittlere Stabilität STDDEV(d_semantisch): "
            f"{summary['mittlere_stabilitaet']}"
        )
        lines.append(
            f"- Mittlere Delta-Bewegung: "
            f"{summary['mittlere_delta_bewegung']}"
        )
        lines.append(
            f"- Mittlere Dominanzstabilität: "
            f"{summary['mittlere_dominanzstabilitaet']}"
        )
        lines.append(
            f"- Mittlerer Kohärenzindex: "
            f"{summary['mittlerer_kohaerenz_index']}\n"
        )

        df = metrics_dataframe(scope_data)
        if not df.empty:
            stable = df.dropna(
                subset=["stabilitaet_std_d_semantisch"]
            ).sort_values("stabilitaet_std_d_semantisch").head(5)

            lines.append("### Stabilste Teilnehmer\n")
            for _, row in stable.iterrows():
                lines.append(
                    f"- Teilnehmer {row['teilnehmer_id']}: "
                    f"Stabilität={row['stabilitaet_std_d_semantisch']}, "
                    f"Delta={row['mittlere_delta_bewegung']}, "
                    f"Kohärenz={row['kohaerenz_index']}"
                )

        lines.append("\n")

    lines.append("## FRZK-Interpretation\n")
    lines.append(
        "Die Stabilisierungsvorhersage gilt als empirisch gestützt, "
        "wenn Teilnehmer mit längeren Interaktionsfolgen geringe "
        "semantische Varianz, geringe Delta-Bewegung, hohe "
        "Dominanzstabilität und hohe Kohärenzwerte zeigen. "
        "In diesem Fall bilden sich im FRZK-Sinn attractorähnliche "
        "Zustandsräume: Die semantischen Zustände bleiben nicht zufällig, "
        "sondern kehren in wiedererkennbare Bereiche des sieben-"
        "dimensionalen Bedeutungsraums zurück."
    )

    report_path = OUTPUT_DIR / "auswertung_01_stabilisierungsvorhersage_report.md"
    report_path.write_text("\n".join(lines), encoding="utf-8")

    print(f"Textreport erzeugt: {report_path.resolve()}")


def main():
    data = load_data()

    save_scope_tables(data)
    plot_bar_scope_summary(data)
    plot_top_stable_participants(data)
    plot_delta_vs_stability(data)
    plot_coherence_distribution(data)
    write_text_report(data)

    print(f"Analyse abgeschlossen. Ausgabeordner: {OUTPUT_DIR.resolve()}")


if __name__ == "__main__":
    main()