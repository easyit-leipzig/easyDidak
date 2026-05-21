import json
import math
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt


INFILE = Path("auswertung_01_perspektivische_vektorkohaerenz.json")
OUTDIR = Path("charts_01_perspektivische_vektorkohaerenz")
OUTDIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def cosine(a, b):
    na = math.sqrt(sum(x * x for x in a))
    nb = math.sqrt(sum(x * x for x in b))
    if na == 0 or nb == 0:
        return None
    return sum(x * y for x, y in zip(a, b)) / (na * nb)


def klassifikation(c):
    if c is None:
        return "nicht berechenbar"
    if c >= 0.75:
        return "hoch"
    if c >= 0.35:
        return "mittel"
    if c >= 0.0:
        return "niedrig"
    return "negativ / Wahrnehmungsbruch"


def main():
    data = json.loads(INFILE.read_text(encoding="utf-8"))

    rows = []
    for m in data["matches"]:
        lv = [m["lehrkraft"]["vektor_7d"][d] for d in DIMENSIONS]
        tv = [m["teilnehmer_7d"]["vektor_7d"][d] for d in DIMENSIONS]
        c = cosine(lv, tv)

        row = {
            "datum": m["match_key"]["datum"],
            "teilnehmer_id": m["match_key"]["teilnehmer_id"],
            "gruppe_id": m["match_key"]["gruppe_id"],
            "cosine_similarity": c,
            "klasse": klassifikation(c),
            "lehrer_dichte": m["lehrkraft"]["d_semantisch_mean"],
            "teilnehmer_dichte": m["teilnehmer_7d"]["d_semantisch"],
            "emotion_valenz": m["teilnehmer_7d"]["emotion_valenz"],
            "emotion_aktivierung": m["teilnehmer_7d"]["emotion_aktivierung"],
            "teilnehmer_dominante_dimension": m["teilnehmer_7d"]["dominante_dimension"]
        }

        for d in DIMENSIONS:
            row[f"lk_{d}"] = m["lehrkraft"]["vektor_7d"][d]
            row[f"tn_{d}"] = m["teilnehmer_7d"]["vektor_7d"][d]
            row[f"delta_{d}"] = row[f"lk_{d}"] - row[f"tn_{d}"]

        rows.append(row)

    df = pd.DataFrame(rows)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    df = df.sort_values(["datum", "gruppe_id", "teilnehmer_id"])

    csv_path = OUTDIR / "auswertung_01_perspektivische_vektorkohaerenz_detail.csv"
    df.to_csv(csv_path, index=False, encoding="utf-8-sig")

    print("\n=== Perspektivische Vektorkohärenz ===")
    print(f"Matches: {len(df)}")

    if df.empty:
        print("Keine Matches vorhanden.")
        return

    print("\nCosine Similarity:")
    print(df["cosine_similarity"].describe())

    print("\nKlassifikation:")
    print(df["klasse"].value_counts(dropna=False))

    print("\nGruppenmittel:")
    print(df.groupby("gruppe_id")["cosine_similarity"].agg(["count", "mean", "std", "min", "max"]))

    print("\nDimensionale mittlere Differenz LK - TN:")
    delta_cols = [f"delta_{d}" for d in DIMENSIONS]
    print(df[delta_cols].mean().sort_values())

    # 1 Histogramm
    plt.figure(figsize=(10, 6))
    df["cosine_similarity"].dropna().hist(bins=20)
    plt.title("01.1 Verteilung der perspektivischen Vektorkohärenz")
    plt.xlabel("Cosine Similarity Lehrkraft-7D ↔ Teilnehmer-7D")
    plt.ylabel("Häufigkeit")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_1_histogramm_cosine_similarity.png", dpi=300)
    plt.close()

    # 2 Zeitverlauf
    ts = df.groupby("datum")["cosine_similarity"].mean().reset_index()
    plt.figure(figsize=(12, 6))
    plt.plot(ts["datum"], ts["cosine_similarity"], marker="o")
    plt.title("01.2 Zeitverlauf der mittleren perspektivischen Vektorkohärenz")
    plt.xlabel("Datum")
    plt.ylabel("mittlere Cosine Similarity")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_2_zeitverlauf_cosine_similarity.png", dpi=300)
    plt.close()

    # 3 Boxplot nach Gruppe
    groups = [g["cosine_similarity"].dropna().values for _, g in df.groupby("gruppe_id")]
    labels = [str(k) for k in df.groupby("gruppe_id").groups.keys()]
    plt.figure(figsize=(10, 6))
    plt.boxplot(groups, labels=labels)
    plt.title("01.3 Perspektivische Vektorkohärenz nach Gruppe")
    plt.xlabel("Gruppe")
    plt.ylabel("Cosine Similarity")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_3_boxplot_gruppen.png", dpi=300)
    plt.close()

    # 4 Dichtevergleich
    plt.figure(figsize=(10, 6))
    plt.scatter(df["lehrer_dichte"], df["teilnehmer_dichte"])
    plt.title("01.4 Semantische Dichte: Lehrkraftsicht vs. Teilnehmer-7D-Sicht")
    plt.xlabel("d_semantisch Lehrkraft")
    plt.ylabel("d_semantisch Teilnehmer")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_4_dichtevergleich_lk_tn.png", dpi=300)
    plt.close()

    # 5 Dimensionsabweichung
    mean_delta = df[delta_cols].mean()
    plt.figure(figsize=(10, 6))
    plt.bar([d.replace("delta_", "") for d in mean_delta.index], mean_delta.values)
    plt.title("01.5 Mittlere dimensionale Abweichung LK - TN")
    plt.xlabel("Dimension")
    plt.ylabel("mittlere Differenz")
    plt.xticks(rotation=30)
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_5_dimensionale_abweichung.png", dpi=300)
    plt.close()

    print(f"\nCSV und Grafiken erzeugt in: {OUTDIR.resolve()}")


if __name__ == "__main__":
    main()