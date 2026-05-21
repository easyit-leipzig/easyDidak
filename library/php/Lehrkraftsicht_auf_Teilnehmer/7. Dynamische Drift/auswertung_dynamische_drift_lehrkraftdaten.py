import json
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt


INFILE = Path("auswertung_07_dynamische_drift.json")
OUTDIR = Path("auswertung_07_dynamische_drift_charts")
OUTDIR.mkdir(exist_ok=True)


def load_scope(data, scope):
    return pd.DataFrame(data["scopes"][scope]["transitions"])


def print_summary(data):
    print("=" * 80)
    print("7. Dynamische Drift – FRZK-Auswertung")
    print("=" * 80)

    for scope, content in data["scopes"].items():
        s = content["summary"]
        print(f"\nSCOPE: {scope}")
        print(f"Datensätze: {s['datensaetze']}")
        print(f"Teilnehmer: {s['teilnehmer']}")
        print(f"Transitionen: {s['transitionen']}")
        print(f"Δ min: {s['delta_min']}")
        print(f"Δ max: {s['delta_max']}")
        print(f"Δ Mittelwert: {s['delta_mittelwert']}")
        print(f"Dominanzwechsel: {s['dominanzwechsel_anzahl']}")
        print(f"Polaritätswechsel: {s['polaritaetswechsel_anzahl']}")


def plot_delta_distribution(df, scope):
    if df.empty:
        return

    plt.figure(figsize=(10, 6))
    plt.hist(df["delta_bewegung"].dropna(), bins=30)
    plt.title(f"7.x.1 Dynamische Drift – Verteilung der Δ-Bewegung ({scope})")
    plt.xlabel("Δ-Bewegung im 7D-FRZK-Raum")
    plt.ylabel("Häufigkeit")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"abb_7_1_delta_verteilung_{scope}.png", dpi=300)
    plt.close()


def plot_delta_over_time(df, scope):
    if df.empty:
        return

    df = df.copy()
    df["datum_b"] = pd.to_datetime(df["datum_b"])
    daily = df.groupby("datum_b")["delta_bewegung"].mean().reset_index()

    plt.figure(figsize=(12, 6))
    plt.plot(daily["datum_b"], daily["delta_bewegung"], marker="o")
    plt.title(f"7.x.2 Dynamische Drift im Zeitverlauf ({scope})")
    plt.xlabel("Datum")
    plt.ylabel("mittlere Δ-Bewegung")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTDIR / f"abb_7_2_delta_zeitverlauf_{scope}.png", dpi=300)
    plt.close()


def plot_dimension_drift(df, scope):
    if df.empty:
        return

    delta_cols = [
        "delta_kognition",
        "delta_sozial",
        "delta_affektiv",
        "delta_motivation",
        "delta_methodik",
        "delta_performanz",
        "delta_regulation",
    ]

    means = df[delta_cols].abs().mean().sort_values(ascending=False)

    plt.figure(figsize=(10, 6))
    means.plot(kind="bar")
    plt.title(f"7.x.3 Mittlere dimensionsbezogene Drift ({scope})")
    plt.xlabel("FRZK-Dimension")
    plt.ylabel("mittlere absolute Drift")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"abb_7_3_dimensionale_drift_{scope}.png", dpi=300)
    plt.close()


def plot_cosine_vs_delta(df, scope):
    if df.empty or "cosine_similarity" not in df.columns:
        return

    tmp = df.dropna(subset=["cosine_similarity", "delta_bewegung"])

    plt.figure(figsize=(10, 6))
    plt.scatter(tmp["cosine_similarity"], tmp["delta_bewegung"], alpha=0.6)
    plt.title(f"7.x.4 Richtungsstabilität vs. Driftintensität ({scope})")
    plt.xlabel("Cosine Similarity S(t), S(t+1)")
    plt.ylabel("Δ-Bewegung")
    plt.tight_layout()
    plt.savefig(OUTDIR / f"abb_7_4_cosine_vs_delta_{scope}.png", dpi=300)
    plt.close()


def textual_interpretation(df, scope):
    if df.empty:
        return f"\n{scope}: Keine auswertbaren Transitionen vorhanden."

    mean_delta = df["delta_bewegung"].mean()
    max_delta = df["delta_bewegung"].max()
    median_delta = df["delta_bewegung"].median()
    dom_rate = df["dominanzwechsel"].mean()
    pol_rate = df["polaritaetswechsel"].mean()
    cos_mean = df["cosine_similarity"].dropna().mean()

    top_dims = (
        df[
            [
                "delta_kognition",
                "delta_sozial",
                "delta_affektiv",
                "delta_motivation",
                "delta_methodik",
                "delta_performanz",
                "delta_regulation",
            ]
        ]
        .abs()
        .mean()
        .sort_values(ascending=False)
    )

    return f"""
SCOPE: {scope}

Die dynamische Drift zeigt eine mittlere Bewegung von {mean_delta:.4f} im siebendimensionalen FRZK-Raum.
Der Median liegt bei {median_delta:.4f}, der Maximalwert bei {max_delta:.4f}. Damit wird sichtbar,
ob die Zustandsentwicklung eher kontinuierlich-verlaufend oder sprunghaft-transformativ ausgeprägt ist.

Die mittlere Richtungsähnlichkeit aufeinanderfolgender Zustände beträgt {cos_mean:.4f}.
Hohe Cosine-Werte bei gleichzeitig vorhandener Δ-Bewegung sprechen FRZK-konform nicht gegen Bewegung,
sondern für gerichtete Trajektorien: Der Teilnehmer verändert seine semantische Lage, bleibt aber im
gleichen funktionalen Bewegungsfeld.

Dominanzwechsel treten in {dom_rate:.2%} der Übergänge auf.
Polaritätswechsel treten in {pol_rate:.2%} der Übergänge auf.
Dominanzwechsel markieren Wechsel des primären Bedeutungsfokus; Polaritätswechsel markieren echte
semantische Umschläge.

Stärkste Drift-Dimensionen:
{top_dims.to_string()}

FRZK-Bewertung:
Die Vorhersage „Teilnehmer bewegen sich kontinuierlich durch den Zustandsraum“ ist dann bestätigt,
wenn viele nicht-null Transitionen auftreten, die Drift nicht ausschließlich zufällig verteilt ist und
die Zustandsfolgen je Teilnehmer zeitlich geordnete Bewegungsmuster zeigen.
"""


def main():
    data = json.loads(INFILE.read_text(encoding="utf-8"))

    print_summary(data)

    interpretations = []

    for scope in data["scopes"].keys():
        df = load_scope(data, scope)

        plot_delta_distribution(df, scope)
        plot_delta_over_time(df, scope)
        plot_dimension_drift(df, scope)
        plot_cosine_vs_delta(df, scope)

        interpretations.append(textual_interpretation(df, scope))

    report = "\n".join(interpretations)
    report_file = OUTDIR / "bericht_07_dynamische_drift.txt"
    report_file.write_text(report, encoding="utf-8")

    print("\nAuswertung abgeschlossen.")
    print(f"Grafiken und Bericht gespeichert in: {OUTDIR.resolve()}")


if __name__ == "__main__":
    main()