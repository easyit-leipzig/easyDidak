import json
from pathlib import Path
from collections import Counter, defaultdict

import pandas as pd
import matplotlib.pyplot as plt

INPUT_FILE = "auswertung_10_systemische_kipppunkte.json"
OUTPUT_DIR = Path("auswertung_10_systemische_kipppunkte_output")
OUTPUT_DIR.mkdir(exist_ok=True)

DELTA_SPIKE_FACTOR = 2.0


def load_data():
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def to_dataframe(scope_data):
    df = pd.DataFrame(scope_data["daten"])
    if df.empty:
        return df

    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    df["d_semantisch"] = pd.to_numeric(df["d_semantisch"], errors="coerce")
    df["delta_d_semantisch"] = pd.to_numeric(df["delta_d_semantisch"], errors="coerce")
    df["polaritaet_gesamt"] = pd.to_numeric(df["polaritaet_gesamt"], errors="coerce")
    return df.sort_values(["teilnehmer_id", "datum", "id"])


def summarize_scope(scope_name, df, threshold):
    if df.empty:
        return f"\nSCOPE: {scope_name}\nKeine Daten vorhanden.\n"

    kipps = df[df["kipppunkt_kandidat"] == True]
    polar = df[df["polaritaetswechsel"] == True]
    dom = df[df["dominanzwechsel"] == True]

    delta_std = df["delta_d_semantisch"].std(skipna=True)
    delta_limit = DELTA_SPIKE_FACTOR * delta_std if pd.notna(delta_std) else threshold
    spikes = df[df["delta_d_semantisch"].abs() >= delta_limit]

    dim_counts = Counter(kipps["dominante_dimension"].dropna())
    pol_counts = Counter(kipps["polaritaet_gesamt"].dropna())

    text = []
    text.append(f"\n{'=' * 80}")
    text.append(f"SCOPE: {scope_name}")
    text.append(f"Datensätze: {len(df)}")
    text.append(f"Kipppunkt-Kandidaten gesamt: {len(kipps)}")
    text.append(f"Dichte-Schwelle d_semantisch > {threshold}: {len(df[df['d_semantisch'] > threshold])}")
    text.append(f"Polaritätswechsel: {len(polar)}")
    text.append(f"Dominanzwechsel: {len(dom)}")
    text.append(f"Delta-Spikes: {len(spikes)}")
    text.append(f"Mittlere semantische Dichte: {df['d_semantisch'].mean():.4f}")
    text.append(f"Maximale semantische Dichte: {df['d_semantisch'].max():.4f}")
    text.append(f"Standardabweichung Δd: {delta_std:.4f}" if pd.notna(delta_std) else "Standardabweichung Δd: n/a")

    text.append("\nDominante Dimensionen bei Kipppunkt-Kandidaten:")
    for dim, count in dim_counts.most_common():
        text.append(f"  {dim}: {count}")

    text.append("\nPolarität bei Kipppunkt-Kandidaten:")
    for pol, count in pol_counts.items():
        text.append(f"  {pol}: {count}")

    text.append("\nFRZK-Deutung:")
    text.append(
        "Systemische Kipppunkte liegen dort vor, wo semantische Dichte, Polarität oder dominante Dimension "
        "nicht kontinuierlich fortgeschrieben werden, sondern in einen qualitativ anderen Zustandsmodus wechseln. "
        "Besonders relevant sind Kombinationen aus hoher d_semantisch, Polaritätswechsel und Dominanzwechsel, "
        "weil sie auf eine Reorganisation des Bedeutungsraums hindeuten."
    )

    return "\n".join(text)


def plot_density_over_time(scope_name, df):
    plt.figure(figsize=(14, 7))
    for tid, g in df.groupby("teilnehmer_id"):
        plt.plot(g["datum"], g["d_semantisch"], marker="o", linewidth=1, alpha=0.7)

    plt.title(f"10.x.1 Semantische Dichte über Zeit – {scope_name}")
    plt.xlabel("Datum")
    plt.ylabel("d_semantisch")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_10_1_dichte_zeit_{scope_name}.png", dpi=300)
    plt.close()


def plot_delta_spikes(scope_name, df):
    plt.figure(figsize=(14, 7))
    plt.scatter(df["datum"], df["delta_d_semantisch"], alpha=0.7)
    plt.axhline(0, linewidth=1)

    plt.title(f"10.x.2 Delta-Spikes der semantischen Dichte – {scope_name}")
    plt.xlabel("Datum")
    plt.ylabel("Δ d_semantisch")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_10_2_delta_spikes_{scope_name}.png", dpi=300)
    plt.close()


def plot_kipp_counts_by_dimension(scope_name, df):
    kipps = df[df["kipppunkt_kandidat"] == True]
    counts = kipps["dominante_dimension"].value_counts()

    if counts.empty:
        return

    plt.figure(figsize=(10, 6))
    counts.plot(kind="bar")
    plt.title(f"10.x.3 Kipppunkte nach dominanter Dimension – {scope_name}")
    plt.xlabel("Dominante Dimension")
    plt.ylabel("Anzahl")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_10_3_kipppunkte_dimension_{scope_name}.png", dpi=300)
    plt.close()


def plot_polarity_switches(scope_name, df):
    switches = df[df["polaritaetswechsel"] == True]

    if switches.empty:
        return

    counts = switches.groupby("datum").size()

    plt.figure(figsize=(12, 6))
    counts.plot(kind="bar")
    plt.title(f"10.x.4 Polaritätswechsel nach Datum – {scope_name}")
    plt.xlabel("Datum")
    plt.ylabel("Anzahl Polaritätswechsel")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.savefig(OUTPUT_DIR / f"abb_10_4_polaritaetswechsel_{scope_name}.png", dpi=300)
    plt.close()


def export_kipppunkte_csv(scope_name, df):
    kipps = df[df["kipppunkt_kandidat"] == True].copy()
    kipps.to_csv(
        OUTPUT_DIR / f"kipppunkt_kandidaten_{scope_name}.csv",
        index=False,
        encoding="utf-8-sig"
    )


def main():
    data = load_data()
    threshold = data.get("threshold_d_semantisch", 2.5)

    report_parts = []

    for scope_name, scope_data in data["scopes"].items():
        df = to_dataframe(scope_data)

        report_parts.append(summarize_scope(scope_name, df, threshold))

        if not df.empty:
            plot_density_over_time(scope_name, df)
            plot_delta_spikes(scope_name, df)
            plot_kipp_counts_by_dimension(scope_name, df)
            plot_polarity_switches(scope_name, df)
            export_kipppunkte_csv(scope_name, df)

    report = "\n".join(report_parts)

    (OUTPUT_DIR / "bericht_10_systemische_kipppunkte.txt").write_text(
        report,
        encoding="utf-8"
    )

    print(report)
    print(f"\nAuswertung abgeschlossen. Dateien liegen in: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()