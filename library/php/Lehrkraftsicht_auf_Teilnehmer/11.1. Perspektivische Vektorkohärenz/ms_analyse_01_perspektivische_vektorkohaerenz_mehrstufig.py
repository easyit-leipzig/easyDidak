# -*- coding: utf-8 -*-
"""
Analyse für Auswertungspunkt 1: Perspektivische Vektorkohärenz – mehrstufiges FRZK-Matching

Liest auswertung_01_perspektivische_vektorkohaerenz_mehrstufig.json und erzeugt:
- CSV-Dateien für alle Kandidaten und Best-Matches
- Textauswertung auf der Konsole
- Grafiken für direkte, zeitversetzte und semantische Resonanzkopplungen
"""

import json
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt


INFILE = Path("auswertung_01_perspektivische_vektorkohaerenz_mehrstufig.json")
OUTDIR = Path("charts_01_perspektivische_vektorkohaerenz_mehrstufig")
OUTDIR.mkdir(exist_ok=True)

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def flatten(records):
    rows = []
    for c in records:
        row = {
            "pair_id": c["pair_id"],
            "strategies": ";".join(c["strategies"]),
            "teilnehmer_id": c["matching"]["teilnehmer_id"],
            "gruppe_id": c["matching"]["gruppe_id"],
            "lehrer_datum": c["matching"]["lehrer_datum"],
            "teilnehmer_datum": c["matching"]["teilnehmer_datum"],
            "day_delta_tn_minus_lk": c["matching"]["day_delta_tn_minus_lk"],
            "abs_day_delta": c["matching"]["abs_day_delta"],
            "recursive_lag_rank": c["matching"].get("recursive_lag_rank"),
            "ue_match": c["matching"]["ue_match"],
            "dominance_match": c["matching"]["dominance_match"],
            "polarity_match": c["matching"]["polarity_match"],
            "cosine_similarity": c["metrics"]["cosine_similarity"],
            "cosine_klasse": c["metrics"]["cosine_klasse"],
            "match_score": c["metrics"]["match_score"],
            "lehrer_dichte": c["metrics"]["lehrer_dichte"],
            "teilnehmer_dichte": c["metrics"]["teilnehmer_dichte"],
            "dichte_delta_lk_minus_tn": c["metrics"]["dichte_delta_lk_minus_tn"],
            "lk_dominante_dimension": c["lehrkraft"].get("dominante_dimension"),
            "tn_dominante_dimension": c["teilnehmer_7d"].get("dominante_dimension"),
            "emotion_valenz": c["teilnehmer_7d"].get("emotion_valenz"),
            "emotion_aktivierung": c["teilnehmer_7d"].get("emotion_aktivierung"),
        }
        for d in DIMENSIONS:
            row[f"lk_{d}"] = c["lehrkraft"]["vektor_7d"][d]
            row[f"tn_{d}"] = c["teilnehmer_7d"]["vektor_7d"][d]
            row[f"delta_{d}"] = c["dimension_delta_lk_minus_tn"][d]
        rows.append(row)
    df = pd.DataFrame(rows)
    if not df.empty:
        df["lehrer_datum"] = pd.to_datetime(df["lehrer_datum"], errors="coerce")
        df["teilnehmer_datum"] = pd.to_datetime(df["teilnehmer_datum"], errors="coerce")
    return df


def strategy_mask(df, name):
    return df["strategies"].str.contains(name, regex=False, na=False)


def print_stats(label, df):
    print(f"\n=== {label} ===")
    print(f"n = {len(df)}")
    if df.empty:
        return
    print(df["cosine_similarity"].describe())
    print("\nKlassifikation:")
    print(df["cosine_klasse"].value_counts(dropna=False))
    print("\nMittlere Scores:")
    print(df[["cosine_similarity", "match_score", "abs_day_delta", "lehrer_dichte", "teilnehmer_dichte"]].mean(numeric_only=True))


def save_hist(df, filename, title):
    plt.figure(figsize=(10, 6))
    df["cosine_similarity"].dropna().hist(bins=25)
    plt.title(title)
    plt.xlabel("Cosine Similarity LK ↔ TN")
    plt.ylabel("Häufigkeit")
    plt.tight_layout()
    plt.savefig(OUTDIR / filename, dpi=300)
    plt.close()


def main():
    data = json.loads(INFILE.read_text(encoding="utf-8"))
    df_all = flatten(data.get("candidate_matches", []))
    df_best = flatten(data.get("best_matches_per_lehrer_event", []))

    df_all.to_csv(OUTDIR / "auswertung_01_mehrstufig_all_candidates.csv", index=False, encoding="utf-8-sig")
    df_best.to_csv(OUTDIR / "auswertung_01_mehrstufig_best_matches.csv", index=False, encoding="utf-8-sig")

    print("\n=== Summary aus JSON ===")
    print(json.dumps(data.get("summary", {}), ensure_ascii=False, indent=2))

    print_stats("Alle Kandidatenbeziehungen", df_all)
    print_stats("Bestes Match je Lehrkrafttermin", df_best)

    if df_all.empty:
        print("Keine Kandidaten vorhanden.")
        return

    for strategy in ["exact_sync", "temporal_window", "recursive_lag_1", "recursive_lag_2", "recursive_lag_3", "semantic_neighborhood"]:
        subset = df_all[strategy_mask(df_all, strategy)]
        print_stats(f"Strategie: {strategy}", subset)

    save_hist(df_all, "abb_01_m1_histogramm_alle_kandidaten.png", "01.M1 Verteilung der perspektivischen Kohärenz – alle Matching-Kandidaten")
    save_hist(df_best, "abb_01_m2_histogramm_best_matches.png", "01.M2 Verteilung der perspektivischen Kohärenz – beste Matches je Lehrkrafttermin")

    # Lag-Profil
    lag_profile = df_all.groupby("day_delta_tn_minus_lk")["cosine_similarity"].agg(["count", "mean", "std"]).reset_index()
    lag_profile.to_csv(OUTDIR / "auswertung_01_lag_profile.csv", index=False, encoding="utf-8-sig")
    plt.figure(figsize=(12, 6))
    plt.plot(lag_profile["day_delta_tn_minus_lk"], lag_profile["mean"], marker="o")
    plt.title("01.M3 Rekursives Resonanzprofil nach Zeitversatz")
    plt.xlabel("Zeitversatz Teilnehmerdatum minus Lehrkraftdatum in Tagen")
    plt.ylabel("mittlere Cosine Similarity")
    plt.axvline(0, linestyle="--")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_m3_lag_resonanzprofil.png", dpi=300)
    plt.close()

    # Scatter: Zeitversatz vs. Cosine, Punktgröße nicht verändert, da keine Stilvorgabe nötig
    plt.figure(figsize=(12, 6))
    plt.scatter(df_all["day_delta_tn_minus_lk"], df_all["cosine_similarity"])
    plt.title("01.M4 Zeitversatz und perspektivische Vektorkohärenz")
    plt.xlabel("Zeitversatz Teilnehmerdatum minus Lehrkraftdatum in Tagen")
    plt.ylabel("Cosine Similarity")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_m4_scatter_lag_cosine.png", dpi=300)
    plt.close()

    # Strategievergleich
    strategy_rows = []
    for strategy in ["exact_sync", "temporal_window", "recursive_lag_1", "recursive_lag_2", "recursive_lag_3", "semantic_neighborhood"]:
        subset = df_all[strategy_mask(df_all, strategy)]
        if not subset.empty:
            strategy_rows.append({
                "strategie": strategy,
                "n": len(subset),
                "cosine_mean": subset["cosine_similarity"].mean(),
                "score_mean": subset["match_score"].mean(),
                "high_share": (subset["cosine_similarity"] >= 0.75).mean(),
                "negative_share": (subset["cosine_similarity"] < 0).mean(),
            })
    strat = pd.DataFrame(strategy_rows)
    strat.to_csv(OUTDIR / "auswertung_01_strategievergleich.csv", index=False, encoding="utf-8-sig")

    plt.figure(figsize=(12, 6))
    plt.bar(strat["strategie"], strat["cosine_mean"])
    plt.title("01.M5 Mittlere Kohärenz nach Matching-Strategie")
    plt.xlabel("Matching-Strategie")
    plt.ylabel("mittlere Cosine Similarity")
    plt.xticks(rotation=30, ha="right")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_m5_strategievergleich_cosine.png", dpi=300)
    plt.close()

    # Dimensionale Abweichung: best matches
    delta_cols = [f"delta_{d}" for d in DIMENSIONS]
    mean_delta = df_best[delta_cols].mean().rename(index=lambda x: x.replace("delta_", ""))
    mean_delta.to_csv(OUTDIR / "auswertung_01_dimensionale_abweichung_best_matches.csv", encoding="utf-8-sig")
    plt.figure(figsize=(10, 6))
    plt.bar(mean_delta.index, mean_delta.values)
    plt.title("01.M6 Mittlere dimensionale Abweichung – beste mehrstufige Matches")
    plt.xlabel("Dimension")
    plt.ylabel("LK minus TN")
    plt.xticks(rotation=30, ha="right")
    plt.tight_layout()
    plt.savefig(OUTDIR / "abb_01_m6_dimensionale_abweichung_best_matches.png", dpi=300)
    plt.close()

    # Semantische Nachbarschaft separat
    sem = df_all[strategy_mask(df_all, "semantic_neighborhood")]
    if not sem.empty:
        plt.figure(figsize=(10, 6))
        plt.scatter(sem["lehrer_dichte"], sem["teilnehmer_dichte"])
        plt.title("01.M7 Semantische Nachbarschaft: Dichte LK ↔ TN")
        plt.xlabel("d_semantisch Lehrkraft")
        plt.ylabel("d_semantisch Teilnehmer")
        plt.tight_layout()
        plt.savefig(OUTDIR / "abb_01_m7_semantische_nachbarschaft_dichte.png", dpi=300)
        plt.close()

    print(f"\nCSV und Grafiken erzeugt in: {OUTDIR.resolve()}")


if __name__ == "__main__":
    main()
