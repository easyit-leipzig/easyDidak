# auswertung_stabilisierung_json.py

import json
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt


INPUT = Path.cwd() / "frzk_stabilisierung_lehrkraftdaten.json"
OUTPUT_DIR = Path.cwd() / "auswertung_stabilisierung"
OUTPUT_DIR.mkdir(exist_ok=True)

REPORT = OUTPUT_DIR / "bericht_stabilisierung.txt"


def load_json(path):
    if not path.exists():
        raise FileNotFoundError(f"JSON nicht gefunden: {path}")
    return json.loads(path.read_text(encoding="utf-8"))


def teilnehmer_df(block):
    return pd.DataFrame(block["teilnehmer"])


def write_report(data, frames):
    lines = []

    lines.append("AUSWERTUNG: 1. STABILISIERUNGSVORHERSAGE DES FRZK")
    lines.append("=" * 70)
    lines.append("")
    lines.append(data.get("interpretation", ""))
    lines.append("")
    lines.append("Datenbasis:")
    for k, v in data["datenbasis"].items():
        lines.append(f"- {k}: {v}")

    lines.append("")

    for name, df in frames.items():
        lines.append("-" * 70)
        lines.append(name)
        lines.append("-" * 70)

        if df.empty:
            lines.append("Keine Daten.")
            continue

        lines.append(f"Teilnehmer: {len(df)}")
        lines.append(f"Mittlerer Stabilisierungsindex: {df['stabilisierungsindex'].mean():.4f}")
        lines.append(f"Minimum Stabilisierungsindex: {df['stabilisierungsindex'].min():.4f}")
        lines.append(f"Maximum Stabilisierungsindex: {df['stabilisierungsindex'].max():.4f}")
        lines.append(f"Mittlere semantische Breite: {df['semantische_breite_mean'].mean():.4f}")
        lines.append(f"Mittlere Vektordrift: {df['delta_vektor_mean'].mean():.4f}")
        lines.append("")

        top = df.sort_values("stabilisierungsindex", ascending=False).head(5)
        low = df.sort_values("stabilisierungsindex", ascending=True).head(5)

        lines.append("Stabilste Teilnehmer:")
        for _, r in top.iterrows():
            lines.append(
                f"- TN {int(r['teilnehmer_id'])}: SI={r['stabilisierungsindex']:.4f}, "
                f"n={int(r['n_unterrichtseinheiten'])}"
            )

        lines.append("")
        lines.append("Instabilste Teilnehmer:")
        for _, r in low.iterrows():
            lines.append(
                f"- TN {int(r['teilnehmer_id'])}: SI={r['stabilisierungsindex']:.4f}, "
                f"n={int(r['n_unterrichtseinheiten'])}"
            )

        lines.append("")

    REPORT.write_text("\n".join(lines), encoding="utf-8")
    print("Bericht geschrieben:", REPORT)


def plot_bar(df, title, column, filename, ylabel):
    if df.empty:
        return

    df = df.sort_values(column, ascending=False)

    plt.figure(figsize=(14, 7))
    plt.bar(df["teilnehmer_id"].astype(str), df[column])
    plt.title(title)
    plt.xlabel("Teilnehmer-ID")
    plt.ylabel(ylabel)
    plt.xticks(rotation=90)
    plt.tight_layout()
    out = OUTPUT_DIR / filename
    plt.savefig(out, dpi=300)
    plt.close()
    print("Grafik geschrieben:", out)


def plot_scatter(df, title, xcol, ycol, filename, xlabel, ylabel):
    if df.empty:
        return

    plt.figure(figsize=(9, 7))
    plt.scatter(df[xcol], df[ycol])
    plt.title(title)
    plt.xlabel(xlabel)
    plt.ylabel(ylabel)
    plt.tight_layout()
    out = OUTPUT_DIR / filename
    plt.savefig(out, dpi=300)
    plt.close()
    print("Grafik geschrieben:", out)


def main():
    data = load_json(INPUT)

    frames = {
        "alle_lehrkraefte": teilnehmer_df(data["daten"]["alle_lehrkraefte"]),
        "lehrkraft_1": teilnehmer_df(data["daten"]["lehrkraft_1"]),
        "nicht_lehrkraft_1": teilnehmer_df(data["daten"]["nicht_lehrkraft_1"]),
    }

    write_report(data, frames)

    for name, df in frames.items():
        if df.empty:
            continue

        plot_bar(
            df,
            f"Stabilisierungsindex pro Teilnehmer – {name}",
            "stabilisierungsindex",
            f"{name}_stabilisierungsindex.png",
            "Stabilisierungsindex"
        )

        plot_bar(
            df,
            f"Semantische Breite pro Teilnehmer – {name}",
            "semantische_breite_mean",
            f"{name}_semantische_breite.png",
            "mittlere semantische Breite"
        )

        plot_bar(
            df,
            f"Mittlere Vektordrift pro Teilnehmer – {name}",
            "delta_vektor_mean",
            f"{name}_delta_vektor.png",
            "mittlere Vektordrift"
        )

        plot_scatter(
            df,
            f"Stabilisierung vs. semantische Breite – {name}",
            "semantische_breite_mean",
            "stabilisierungsindex",
            f"{name}_scatter_breite_stabilisierung.png",
            "mittlere semantische Breite",
            "Stabilisierungsindex"
        )

        plot_scatter(
            df,
            f"Stabilisierung vs. Vektordrift – {name}",
            "delta_vektor_mean",
            "stabilisierungsindex",
            f"{name}_scatter_delta_stabilisierung.png",
            "mittlere Vektordrift",
            "Stabilisierungsindex"
        )

    print("Fertig. Ausgabeordner:", OUTPUT_DIR.resolve())


if __name__ == "__main__":
    main()