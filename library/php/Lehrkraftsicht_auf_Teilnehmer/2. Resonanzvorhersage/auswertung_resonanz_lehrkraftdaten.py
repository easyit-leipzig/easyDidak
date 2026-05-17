# auswertung_resonanz_lehrkraftdaten.py

import json
import pandas as pd
import matplotlib.pyplot as plt
from pathlib import Path

INPUT = Path("frzk_resonanz_lehrkraftdaten.json")
OUTDIR = Path("plots_resonanz")
OUTDIR.mkdir(exist_ok=True)

data = json.loads(INPUT.read_text(encoding="utf-8"))

rows = []

for key, block in data["daten"].items():
    for t in block["teilnehmer"]:
        rows.append({
            "gruppe": key,
            "teilnehmer_id": t["teilnehmer_id"],
            "n": t["n"],
            "hauptdominanz": t["hauptdominanz"],
            "hauptdominanz_anteil": t["hauptdominanz_anteil"],
            "dominanz_persistenz": t["dominanz_persistenz"],
            "polaritaet_persistenz": t["polaritaet_persistenz"],
            "cosine_resonanz_mean": t["cosine_resonanz_mean"],
            "cosine_resonanz_std": t["cosine_resonanz_std"],
            "d_semantisch_mean": t["d_semantisch_mean"],
            "d_semantisch_std": t["d_semantisch_std"],
            "resonanzindex": t["resonanzindex"]
        })

df = pd.DataFrame(rows)

summary = df.groupby("gruppe").agg(
    n_teilnehmer=("teilnehmer_id", "nunique"),
    resonanzindex_mean=("resonanzindex", "mean"),
    resonanzindex_std=("resonanzindex", "std"),
    cosine_mean=("cosine_resonanz_mean", "mean"),
    dominanz_persistenz_mean=("dominanz_persistenz", "mean"),
    polaritaet_persistenz_mean=("polaritaet_persistenz", "mean"),
    hauptdominanz_anteil_mean=("hauptdominanz_anteil", "mean")
).reset_index()

print("\n=== Zusammenfassung Resonanz ===")
print(summary.to_string(index=False))

plt.figure(figsize=(10, 6))
summary.plot(
    x="gruppe",
    y="resonanzindex_mean",
    kind="bar",
    legend=False
)
plt.title("FRZK-Resonanzindex nach Lehrkraftgruppe")
plt.xlabel("Vergleichsgruppe")
plt.ylabel("mittlerer Resonanzindex")
plt.tight_layout()
plt.savefig(OUTDIR / "abb_1_resonanzindex.png", dpi=300)
plt.close()

plt.figure(figsize=(10, 6))
summary.plot(
    x="gruppe",
    y="cosine_mean",
    kind="bar",
    legend=False
)
plt.title("Mittlere Cosine-Selbstähnlichkeit aufeinanderfolgender Zustände")
plt.xlabel("Vergleichsgruppe")
plt.ylabel("Cosine Similarity")
plt.tight_layout()
plt.savefig(OUTDIR / "abb_2_cosine_resonanz.png", dpi=300)
plt.close()

plt.figure(figsize=(10, 6))
summary.plot(
    x="gruppe",
    y="dominanz_persistenz_mean",
    kind="bar",
    legend=False
)
plt.title("Persistenz dominanter Dimensionen")
plt.xlabel("Vergleichsgruppe")
plt.ylabel("Anteil gleicher Dominanz zum Vorzustand")
plt.tight_layout()
plt.savefig(OUTDIR / "abb_3_dominanz_persistenz.png", dpi=300)
plt.close()

plt.figure(figsize=(10, 6))
for gruppe in df["gruppe"].unique():
    subset = df[df["gruppe"] == gruppe]
    plt.scatter(
        subset["cosine_resonanz_mean"],
        subset["resonanzindex"],
        label=gruppe
    )

plt.title("Resonanzindex in Abhängigkeit von Cosine-Selbstähnlichkeit")
plt.xlabel("mittlere Cosine Similarity")
plt.ylabel("Resonanzindex")
plt.legend()
plt.tight_layout()
plt.savefig(OUTDIR / "abb_4_resonanz_vs_cosine.png", dpi=300)
plt.close()

dominance_rows = []

for key, block in data["daten"].items():
    for t in block["teilnehmer"]:
        for dim, count in t["dominanzverteilung"].items():
            dominance_rows.append({
                "gruppe": key,
                "teilnehmer_id": t["teilnehmer_id"],
                "dimension": dim,
                "count": count
            })

dom_df = pd.DataFrame(dominance_rows)

if not dom_df.empty:
    dom_summary = dom_df.groupby(["gruppe", "dimension"])["count"].sum().reset_index()

    for gruppe in dom_summary["gruppe"].unique():
        subset = dom_summary[dom_summary["gruppe"] == gruppe]

        plt.figure(figsize=(10, 6))
        plt.bar(subset["dimension"], subset["count"])
        plt.title(f"Dominanzverteilung im Resonanzraum: {gruppe}")
        plt.xlabel("dominante Dimension")
        plt.ylabel("Häufigkeit")
        plt.xticks(rotation=45, ha="right")
        plt.tight_layout()
        plt.savefig(OUTDIR / f"abb_5_dominanzverteilung_{gruppe}.png", dpi=300)
        plt.close()

print("\n=== Textuelle Interpretation ===")

for _, row in summary.iterrows():
    print(f"""
Gruppe: {row['gruppe']}
Die Gruppe umfasst {int(row['n_teilnehmer'])} Teilnehmende.
Der mittlere Resonanzindex beträgt {row['resonanzindex_mean']:.4f}.
Die mittlere Cosine-Selbstähnlichkeit beträgt {row['cosine_mean']:.4f}.
Die Dominanzpersistenz beträgt {row['dominanz_persistenz_mean']:.4f}.
Die Polaritätspersistenz beträgt {row['polaritaet_persistenz_mean']:.4f}.
Der mittlere Anteil der Hauptdominanz beträgt {row['hauptdominanz_anteil_mean']:.4f}.

FRZK-Deutung:
Ein hoher Resonanzindex zeigt, dass aufeinanderfolgende Lehrkraftsicht-Zustände nicht zufällig streuen, sondern in ähnliche semantische Richtungen zurückkehren.
Hohe Cosine-Selbstähnlichkeit belegt strukturelle Anschlussfähigkeit.
Hohe Dominanzpersistenz zeigt, dass bestimmte Dimensionen als stabile Bedeutungsachsen wirken.
""")

print(f"\nGrafiken gespeichert in: {OUTDIR}")