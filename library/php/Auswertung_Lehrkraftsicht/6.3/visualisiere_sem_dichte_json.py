import json
from pathlib import Path

import matplotlib.pyplot as plt
import pandas as pd


INPUT_JSON = "sem_dichte_auswertung.json"

DIM_LABELS = {
    "x_kognition": "Kognition",
    "x_sozial": "Sozial",
    "x_affektiv": "Affektiv",
    "x_motivation": "Motivation",
    "x_methodik": "Methodik",
    "x_performanz": "Performanz",
    "x_regulation": "Regulation",
}


# ------------------------------------------------------------
# JSON laden
# ------------------------------------------------------------
input_path = Path(INPUT_JSON)
if not input_path.exists():
    raise FileNotFoundError(f"JSON-Datei nicht gefunden: {input_path}")

with input_path.open("r", encoding="utf-8") as f:
    data = json.load(f)

dims = data["meta"]["dimensionen"]

mean_vector = pd.Series(data["mittelwertoperator"])
var_vector = pd.Series(data["varianzoperator"])

weekly_stats = pd.DataFrame(data["wochenstatistik"])
dominance_stats = pd.DataFrame(data["dominanzverteilung"])
polarity_stats = pd.DataFrame(data["polaritaetsverteilung"])

# ------------------------------------------------------------
# Labels umbenennen
# ------------------------------------------------------------
mean_plot = mean_vector.rename(index=DIM_LABELS)
var_plot = var_vector.rename(index=DIM_LABELS)

# ------------------------------------------------------------
# 1. Mittelwertprofil
# ------------------------------------------------------------
plt.figure(figsize=(10, 5))
plt.bar(mean_plot.index, mean_plot.values)
plt.xticks(rotation=45, ha="right")
plt.ylabel("Mittelwert")
plt.title("Mittelwertoperator des Zustandsraums")
plt.tight_layout()
plt.savefig("plot_mean_vector.png", dpi=200)
plt.close()

# ------------------------------------------------------------
# 2. Varianzprofil
# ------------------------------------------------------------
plt.figure(figsize=(10, 5))
plt.bar(var_plot.index, var_plot.values)
plt.xticks(rotation=45, ha="right")
plt.ylabel("Varianz")
plt.title("Varianzoperator je Dimension")
plt.tight_layout()
plt.savefig("plot_variance_vector.png", dpi=200)
plt.close()

# ------------------------------------------------------------
# 3. Zeitreihe semantische Dichte
# ------------------------------------------------------------
if not weekly_stats.empty:
    weekly_stats["label"] = (
        weekly_stats["jahr"].astype(str) + "-KW" + weekly_stats["kw"].astype(str)
    )

    plt.figure(figsize=(12, 5))
    plt.plot(weekly_stats["label"], weekly_stats["d_semantisch_mean"], marker="o")
    plt.xticks(rotation=45, ha="right")
    plt.ylabel("Mittlere semantische Dichte")
    plt.title("Zeitreihe der semantischen Dichte")
    plt.tight_layout()
    plt.savefig("plot_weekly_d_semantisch.png", dpi=200)
    plt.close()

# ------------------------------------------------------------
# 4. Dominanzverteilung
# ------------------------------------------------------------
if not dominance_stats.empty:
    plt.figure(figsize=(8, 5))
    plt.bar(dominance_stats["dominante_dimension"], dominance_stats["n"])
    plt.xticks(rotation=45, ha="right")
    plt.ylabel("Häufigkeit")
    plt.title("Verteilung dominanter Dimensionen")
    plt.tight_layout()
    plt.savefig("plot_dominance_distribution.png", dpi=200)
    plt.close()

# ------------------------------------------------------------
# 5. Polaritätsverteilung
# ------------------------------------------------------------
if not polarity_stats.empty:
    plt.figure(figsize=(6, 4))
    plt.bar(polarity_stats["polaritaet_gesamt"].astype(str), polarity_stats["n"])
    plt.ylabel("Häufigkeit")
    plt.title("Polaritätsverteilung")
    plt.tight_layout()
    plt.savefig("plot_polarity_distribution.png", dpi=200)
    plt.close()

print("Alle Diagramme wurden aus JSON erzeugt.")