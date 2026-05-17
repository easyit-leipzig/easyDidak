import json
from pathlib import Path
from collections import defaultdict
import statistics as stats

import pandas as pd

import matplotlib
matplotlib.use("Agg")
print("Matplotlib geladen", flush=True)

import matplotlib.pyplot as plt

IN = Path("auswertung_03_nichtlinearitaet.json")
OUT_DIR = Path("grafiken_03_nichtlinearitaet")
OUT_DIR.mkdir(exist_ok=True)

data = json.loads(IN.read_text(encoding="utf-8"))

for scope, rows in data["scopes"].items():
    groups = defaultdict(list)

    for r in rows:
        token = int(r["token_anzahl"])
        d = float(r["d_semantisch"])
        groups[token].append(d)

    tokens = sorted(groups.keys())
    means = [stats.mean(groups[t]) for t in tokens]
    stds = [stats.stdev(groups[t]) if len(groups[t]) >= 2 else 0 for t in tokens]
    mins = [min(groups[t]) for t in tokens]
    maxs = [max(groups[t]) for t in tokens]
    ranges = [max(groups[t]) - min(groups[t]) for t in tokens]

    # 1. Mittelwert mit Standardabweichung
    plt.figure(figsize=(12, 7))
    plt.errorbar(tokens, means, yerr=stds, fmt="o-", capsize=5)
    plt.title(f"Nichtlinearitätsnachweis: Mittelwert und Streuung – {scope}")
    plt.xlabel("Tokenanzahl")
    plt.ylabel("Semantische Dichte d_semantisch")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUT_DIR / f"{scope}_01_mittelwert_streuung.png", dpi=300)
    plt.close()

    # 2. Rohwertstreuung
    x = []
    y = []
    for token, values in groups.items():
        for v in values:
            x.append(token)
            y.append(v)

    plt.figure(figsize=(12, 7))
    plt.scatter(x, y, alpha=0.45)
    plt.title(f"Rohwertstreuung gleicher Tokenanzahlen – {scope}")
    plt.xlabel("Tokenanzahl")
    plt.ylabel("Semantische Dichte d_semantisch")
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUT_DIR / f"{scope}_02_rohwertstreuung.png", dpi=300)
    plt.close()

    # 3. Min-Max-Spannweite
    plt.figure(figsize=(12, 7))
    plt.vlines(tokens, mins, maxs)
    plt.scatter(tokens, mins, label="Minimum")
    plt.scatter(tokens, maxs, label="Maximum")
    plt.title(f"Spannweite der Dichte bei gleicher Tokenanzahl – {scope}")
    plt.xlabel("Tokenanzahl")
    plt.ylabel("Semantische Dichte d_semantisch")
    plt.legend()
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUT_DIR / f"{scope}_03_min_max_spannweite.png", dpi=300)
    plt.close()

    # 4. Range je Tokenklasse
    plt.figure(figsize=(12, 7))
    plt.bar(tokens, ranges)
    plt.title(f"Nichtlinearitätsstärke je Tokenklasse – {scope}")
    plt.xlabel("Tokenanzahl")
    plt.ylabel("Range von d_semantisch")
    plt.grid(True, axis="y", alpha=0.3)
    plt.tight_layout()
    plt.savefig(OUT_DIR / f"{scope}_04_range_tokenklasse.png", dpi=300)
    plt.close()

print(f"Grafiken erzeugt in: {OUT_DIR.resolve()}")
input("ENTER zum Beenden...")