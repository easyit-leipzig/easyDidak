import json
from pathlib import Path

import numpy as np
import pandas as pd
import matplotlib.pyplot as plt

INFILE = Path("auswertung_02_semantische_distanz_lk1_teilnehmer7d.json")
OUTDIR = Path("charts_02_semantische_distanz_lk1_teilnehmer7d")
OUTDIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def classify_distance(d):
    if d < 0.25:
        return "sehr_kleine_distanz_gekoppelt"
    if d < 0.50:
        return "kleine_distanz_tendenziell_gekoppelt"
    if d < 0.85:
        return "mittlere_distanz_teilweise_divergent"
    return "grosse_distanz_divergent"


def main():
    data = json.loads(INFILE.read_text(encoding="utf-8"))
    records = data["records"]

    if not records:
        print("Keine Matching-Datensätze gefunden.")
        return

    df = pd.DataFrame(records)
    df["D_LT_Klasse"] = df["D_LT"].apply(classify_distance)
    df["datum"] = pd.to_datetime(df["lehrer_datum"])

    for d in DIMENSIONS:
        df[f"delta_{d}"] = df["delta_vector_L_minus_T"].apply(lambda x: x[d])

    summary = {
        "anzahl_matches": int(len(df)),
        "D_LT_mean": float(df["D_LT"].mean()),
        "D_LT_median": float(df["D_LT"].median()),
        "D_LT_std": float(df["D_LT"].std(ddof=0)),
        "D_LT_min": float(df["D_LT"].min()),
        "D_LT_max": float(df["D_LT"].max()),
        "cosine_mean": float(df["cosine_LT"].dropna().mean()),
        "dominanz_match_quote": float(df["dominanz_match"].mean()),
        "polaritaet_match_quote": float(df["polaritaet_match"].mean()),
        "distanzklassen": df["D_LT_Klasse"].value_counts().to_dict(),
        "dimensionale_delta_mittelwerte": {
            d: float(df[f"delta_{d}"].mean()) for d in DIMENSIONS
        },
        "dimensionale_delta_abs_mittelwerte": {
            d: float(df[f"delta_{d}"].abs().mean()) for d in DIMENSIONS
        }
    }

    Path(OUTDIR / "summary_02_semantische_distanz.json").write_text(
        json.dumps(summary, ensure_ascii=False, indent=2),
        encoding="utf-8"
    )

    df.to_csv(OUTDIR / "details_02_semantische_distanz.csv", index=False, encoding="utf-8-sig")

    # 1. Histogramm der semantischen Distanz
    plt.figure(figsize=(10, 6))
    plt.hist(df["D_LT"], bins=30)
    plt.title("Abb. 6.x.2.1 – Verteilung der semantischen Distanz D_LT")
    plt.xlabel("D_LT = ||L_t - T_t||")
    plt.ylabel("Häufigkeit")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_6_x_2_1_distanz_histogramm.png", dpi=300)
    plt.close()

    # 2. Verlauf der Distanz über Zeit
    daily = df.groupby("datum", as_index=False)["D_LT"].mean()

    plt.figure(figsize=(12, 6))
    plt.plot(daily["datum"], daily["D_LT"], marker="o")
    plt.title("Abb. 6.x.2.2 – Zeitlicher Verlauf der mittleren semantischen Distanz")
    plt.xlabel("Datum")
    plt.ylabel("mittlere D_LT")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_6_x_2_2_distanz_zeitverlauf.png", dpi=300)
    plt.close()

    # 3. Dimensionale Abweichungen
    abs_delta = [df[f"delta_{d}"].abs().mean() for d in DIMENSIONS]

    plt.figure(figsize=(10, 6))
    plt.bar(DIMENSIONS, abs_delta)
    plt.title("Abb. 6.x.2.3 – Mittlere absolute Dimensionsabweichung")
    plt.xlabel("FRZK-Dimension")
    plt.ylabel("|L_t - T_t|")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_6_x_2_3_dimensionale_abweichung.png", dpi=300)
    plt.close()

    # 4. Cosine vs. Distanz
    plt.figure(figsize=(10, 6))
    plt.scatter(df["D_LT"], df["cosine_LT"], alpha=0.7)
    plt.title("Abb. 6.x.2.4 – Semantische Distanz und Richtungsähnlichkeit")
    plt.xlabel("D_LT")
    plt.ylabel("Cosine Similarity L_t ↔ T_t")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_6_x_2_4_distanz_vs_cosine.png", dpi=300)
    plt.close()

    # 5. Distanz nach Teilnehmern
    teilnehmer = df.groupby("teilnehmer_id", as_index=False)["D_LT"].mean()
    teilnehmer = teilnehmer.sort_values("D_LT", ascending=False)

    plt.figure(figsize=(12, 6))
    plt.bar(teilnehmer["teilnehmer_id"].astype(str), teilnehmer["D_LT"])
    plt.title("Abb. 6.x.2.5 – Mittlere Distanz nach Teilnehmer")
    plt.xlabel("Teilnehmer-ID")
    plt.ylabel("mittlere D_LT")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_6_x_2_5_distanz_nach_teilnehmer.png", dpi=300)
    plt.close()

    print("Analyse abgeschlossen.")
    print(json.dumps(summary, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()