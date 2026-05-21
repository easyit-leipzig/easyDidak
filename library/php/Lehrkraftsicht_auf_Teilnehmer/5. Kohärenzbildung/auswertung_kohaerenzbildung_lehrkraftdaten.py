import json
from pathlib import Path
import pandas as pd
import matplotlib.pyplot as plt

INFILE = Path("auswertung_05_kohaerenzbildung.json")
OUTDIR = Path("plots_05_kohaerenzbildung")
OUTDIR.mkdir(exist_ok=True)

def load_scope(data, scope):
    return pd.DataFrame(data["scopes"][scope]["by_teilnehmer"])

def save_bar(df, scope):
    if df.empty:
        return None

    df = df.sort_values("kohaerenz_index_mean")
    plt.figure(figsize=(12, 6))
    plt.bar(df["teilnehmer_id"].astype(str), df["kohaerenz_index_mean"])
    plt.title(f"Auswertung 5 – Kohärenzindex je Teilnehmer ({scope})")
    plt.xlabel("teilnehmer_id")
    plt.ylabel("mittlerer Kohärenzindex")
    plt.xticks(rotation=90)
    plt.tight_layout()

    path = OUTDIR / f"abb_05_1_kohaerenzindex_{scope}.png"
    plt.savefig(path, dpi=300)
    plt.close()
    return path

def save_scatter(df, scope):
    if df.empty:
        return None

    plt.figure(figsize=(8, 6))
    plt.scatter(df["kohaerenz_1_mean"], df["kohaerenz_2_mean"])
    plt.title(f"Auswertung 5 – Kohärenzachsen kognitiv/methodisch ({scope})")
    plt.xlabel("Ø |x_kognition - x_motivation|")
    plt.ylabel("Ø |x_methodik - x_regulation|")
    plt.tight_layout()

    path = OUTDIR / f"abb_05_2_kohaerenzachsen_{scope}.png"
    plt.savefig(path, dpi=300)
    plt.close()
    return path

def save_polaritaet(df, scope):
    if df.empty:
        return None

    plt.figure(figsize=(10, 5))
    plt.bar(df["teilnehmer_id"].astype(str), df["polaritaetswechsel"])
    plt.title(f"Auswertung 5 – Polaritätswechsel je Teilnehmer ({scope})")
    plt.xlabel("teilnehmer_id")
    plt.ylabel("Anzahl Polaritätswechsel")
    plt.xticks(rotation=90)
    plt.tight_layout()

    path = OUTDIR / f"abb_05_3_polaritaetswechsel_{scope}.png"
    plt.savefig(path, dpi=300)
    plt.close()
    return path

def interpret(scope, summary):
    if not summary:
        return f"\n{scope}: keine Daten vorhanden.\n"

    k = summary["kohaerenz_index_mean"]
    std = summary["kohaerenz_index_std"]
    p = summary["polaritaetswechsel_sum"]

    if k < 0.20:
        wertung = "sehr hohe interne Kohärenz"
    elif k < 0.35:
        wertung = "hohe bis mittlere interne Kohärenz"
    elif k < 0.50:
        wertung = "mittlere Kohärenz mit erkennbarer Dimensionsstreuung"
    else:
        wertung = "geringe Kohärenz bzw. deutliche Dimensionsstreuung"

    return f"""
{scope}
Datensätze: {summary['n_records']}
Teilnehmer: {summary['n_teilnehmer']}
Ø Kohärenz 1 |Kognition–Motivation|: {summary['kohaerenz_1_mean']:.4f}
Ø Kohärenz 2 |Methodik–Regulation|: {summary['kohaerenz_2_mean']:.4f}
Ø Kohärenzindex: {k:.4f}
Std Kohärenzindex: {std:.4f}
Polaritätswechsel gesamt: {p}

FRZK-Deutung:
Der Scope zeigt {wertung}. Niedrige Werte bedeuten, dass kognitive, motivationale,
methodische und regulative Bewertungsanteile enger gekoppelt auftreten. Im Sinne der
FRZK-Vorhersage spricht dies für einen stabilisierten semantischen Raum. Hohe
Polaritätswechsel würden dagegen auf semantische Instabilität oder Wechsel zwischen
Resonanz- und Irritationsphasen hinweisen.
"""

def main():
    data = json.loads(INFILE.read_text(encoding="utf-8"))

    report = [
        "Auswertung 5 – Kohärenzbildung",
        data.get("frzk_vorhersage", ""),
        data.get("interpretation", data.get("messlogik", {}).get("interpretation", "")),
        "",
    ]
    for scope in data["scopes"]:
        df = load_scope(data, scope)
        summary = data["scopes"][scope]["summary"]

        paths = [
            save_bar(df, scope),
            save_scatter(df, scope),
            save_polaritaet(df, scope),
        ]

        report.append(interpret(scope, summary))
        report.append("Grafiken:")
        for p in paths:
            if p:
                report.append(f"- {p}")
        report.append("")

    outfile = OUTDIR / "bericht_05_kohaerenzbildung.txt"
    outfile.write_text("\n".join(report), encoding="utf-8")

    print(f"Bericht erzeugt: {outfile.resolve()}")
    print(f"Grafiken erzeugt in: {OUTDIR.resolve()}")

if __name__ == "__main__":
    main()