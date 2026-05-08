import json
from pathlib import Path

import matplotlib.pyplot as plt


# ============================================================
# FRZK 6.x.9
# Grafikerstellung aus:
#   frzk_6x9_zustaende.json
#
# Erwartete Gruppen:
#   1. alle
#   2. lehrkraft_1
#   3. nicht_lehrkraft_1
#
# Output:
#   PNG-Dateien im Ordner: frzk_6x9_plots
# ============================================================

INPUT_FILE = "frzk_6x9_zustaende.json"
OUTPUT_DIR = "frzk_6x9_plots"

GROUP_ORDER = [
    ("alle", "Alle"),
    ("lehrkraft_1", "Lehrkraft 1"),
    ("nicht_lehrkraft_1", "Nicht Lehrkraft 1"),
]

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]


def load_json(path):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def ensure_output_dir(path):
    Path(path).mkdir(parents=True, exist_ok=True)


def get_states(payload, key):
    return payload[key]["zustände"]


def get_metric(payload, key, metric):
    return payload[key][metric]


def get_series(states, field):
    return [s[field] for s in states]


def get_dimension_series(states, dim_index):
    return [s["state"][dim_index] for s in states]


def save_plot(filename):
    out = Path(OUTPUT_DIR) / filename
    plt.tight_layout()
    plt.savefig(out, dpi=300)
    plt.close()
    print(f"gespeichert: {out}")


def plot_delta_norm(payload):
    plt.figure(figsize=(12, 6))

    for key, label in GROUP_ORDER:
        states = get_states(payload, key)
        y = get_series(states, "delta_norm")
        x = list(range(len(y)))
        plt.plot(x, y, label=label)

    plt.title("FRZK 6.x.9 – Dynamikvergleich ΔS(t)")
    plt.xlabel("t")
    plt.ylabel("||ΔS(t)||")
    plt.legend()
    plt.grid(True)

    save_plot("01_delta_norm_dynamikvergleich.png")


def plot_state_norm(payload):
    plt.figure(figsize=(12, 6))

    for key, label in GROUP_ORDER:
        states = get_states(payload, key)
        y = get_series(states, "norm")
        x = list(range(len(y)))
        plt.plot(x, y, label=label)

    plt.title("FRZK 6.x.9 – Zustandsnorm ||S(t)||")
    plt.xlabel("t")
    plt.ylabel("||S(t)||")
    plt.legend()
    plt.grid(True)

    save_plot("02_zustandsnorm_vergleich.png")


def plot_coherence_local(payload):
    plt.figure(figsize=(12, 6))

    for key, label in GROUP_ORDER:
        states = get_states(payload, key)
        y = get_series(states, "kohärenz_lokal")
        x = list(range(len(y)))
        plt.plot(x, y, label=label)

    plt.title("FRZK 6.x.9 – lokale Kohärenz C(S,t)")
    plt.xlabel("t")
    plt.ylabel("lokale Kohärenz")
    plt.legend()
    plt.grid(True)

    save_plot("03_lokale_kohaerenz_vergleich.png")


def plot_bar_metrics(payload):
    labels = [label for _, label in GROUP_ORDER]
    stability = [get_metric(payload, key, "stabilität") for key, _ in GROUP_ORDER]
    coherence = [get_metric(payload, key, "kohärenz") for key, _ in GROUP_ORDER]
    variance = [get_metric(payload, key, "varianz") for key, _ in GROUP_ORDER]
    delta_mean = [get_metric(payload, key, "delta_mittel") for key, _ in GROUP_ORDER]

    # Stabilität
    plt.figure(figsize=(9, 6))
    plt.bar(labels, stability)
    plt.title("FRZK 6.x.9 – Stabilitätsvergleich")
    plt.ylabel("Stabilität")
    plt.grid(axis="y")
    save_plot("04_stabilitaet_balkenvergleich.png")

    # Kohärenz
    plt.figure(figsize=(9, 6))
    plt.bar(labels, coherence)
    plt.title("FRZK 6.x.9 – globaler Kohärenzvergleich")
    plt.ylabel("Kohärenz")
    plt.grid(axis="y")
    save_plot("05_kohaerenz_balkenvergleich.png")

    # Varianz
    plt.figure(figsize=(9, 6))
    plt.bar(labels, variance)
    plt.title("FRZK 6.x.9 – Varianzvergleich")
    plt.ylabel("Varianz")
    plt.grid(axis="y")
    save_plot("06_varianz_balkenvergleich.png")

    # mittlere Dynamik
    plt.figure(figsize=(9, 6))
    plt.bar(labels, delta_mean)
    plt.title("FRZK 6.x.9 – mittlere Dynamik")
    plt.ylabel("mean(||ΔS(t)||)")
    plt.grid(axis="y")
    save_plot("07_delta_mittel_balkenvergleich.png")


def plot_dimension_profiles(payload):
    for key, label in GROUP_ORDER:
        states = get_states(payload, key)

        plt.figure(figsize=(13, 7))

        for i, dim in enumerate(DIMENSIONS):
            y = get_dimension_series(states, i)
            x = list(range(len(y)))
            plt.plot(x, y, label=dim)

        plt.title(f"FRZK 6.x.9 – Dimensionsverlauf: {label}")
        plt.xlabel("t")
        plt.ylabel("Dimensionswert")
        plt.legend()
        plt.grid(True)

        safe_key = key.replace("ä", "ae").replace("ö", "oe").replace("ü", "ue")
        save_plot(f"08_dimensionsverlauf_{safe_key}.png")


def plot_dimension_spread(payload):
    """
    Dimensionsstreuung als Kohärenzgegenmaß:
    hohe Streuung = niedrigere interne Kopplung
    niedrige Streuung = höhere interne Kopplung
    """
    plt.figure(figsize=(12, 6))

    for key, label in GROUP_ORDER:
        states = get_states(payload, key)
        spreads = []

        for s in states:
            values = s["state"]
            m = sum(values) / len(values)
            spread = (sum((x - m) ** 2 for x in values) / len(values)) ** 0.5
            spreads.append(spread)

        x = list(range(len(spreads)))
        plt.plot(x, spreads, label=label)

    plt.title("FRZK 6.x.9 – Dimensionsstreuung als Kohärenzgegenmaß")
    plt.xlabel("t")
    plt.ylabel("Standardabweichung der Dimensionen")
    plt.legend()
    plt.grid(True)

    save_plot("09_dimensionsstreuung_kohaerenzgegenmass.png")


def plot_phase_space(payload):
    """
    Phasenraum-Projektion:
    x-Achse = Zustandsnorm ||S(t)||
    y-Achse = Dynamik ||ΔS(t)||
    Ein stabiler Attraktor erscheint als Punktwolke mit geringer vertikaler Streuung.
    """
    plt.figure(figsize=(10, 7))

    for key, label in GROUP_ORDER:
        states = get_states(payload, key)
        x = get_series(states, "norm")
        y = get_series(states, "delta_norm")
        plt.scatter(x, y, label=label, alpha=0.75)

    plt.title("FRZK 6.x.9 – Phasenraumprojektion: ||S(t)|| × ||ΔS(t)||")
    plt.xlabel("Zustandsnorm ||S(t)||")
    plt.ylabel("Dynamik ||ΔS(t)||")
    plt.legend()
    plt.grid(True)

    save_plot("10_phasenraum_norm_delta.png")


def main():
    input_path = Path(INPUT_FILE)

    if not input_path.exists():
        raise FileNotFoundError(
            f"Datei nicht gefunden: {INPUT_FILE}\n"
            "Lege dieses Skript in denselben Ordner wie frzk_6x9_zustaende.json "
            "oder passe INPUT_FILE an."
        )

    ensure_output_dir(OUTPUT_DIR)
    payload = load_json(input_path)

    plot_delta_norm(payload)
    plot_state_norm(payload)
    plot_coherence_local(payload)
    plot_bar_metrics(payload)
    plot_dimension_profiles(payload)
    plot_dimension_spread(payload)
    plot_phase_space(payload)

    print()
    print("Alle FRZK-Grafiken wurden erstellt.")
    print(f"Ordner: {OUTPUT_DIR}")


if __name__ == "__main__":
    main()
