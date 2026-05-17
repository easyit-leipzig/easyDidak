import json
from pathlib import Path
from collections import defaultdict
import statistics as stats

IN = Path("auswertung_03_nichtlinearitaet.json")
OUT = Path("bericht_nichtlinearitaet_debug.txt")

print("Analyse startet...", flush=True)

data = json.loads(IN.read_text(encoding="utf-8"))

lines = []

for scope, rows in data["scopes"].items():
    lines.append("=" * 80)
    lines.append(f"SCOPE: {scope}")
    lines.append(f"Datensätze: {len(rows)}")

    groups = defaultdict(list)

    for r in rows:
        token = int(r["token_anzahl"])
        d = float(r["d_semantisch"])
        groups[token].append(d)

    mehrfach = {k: v for k, v in groups.items() if len(v) >= 2}

    max_range = 0
    best_token = None

    for token, values in mehrfach.items():
        spannweite = max(values) - min(values)
        if spannweite > max_range:
            max_range = spannweite
            best_token = token

    lines.append(f"Tokenklassen gesamt: {len(groups)}")
    lines.append(f"Mehrfach belegte Tokenklassen: {len(mehrfach)}")
    lines.append(f"Maximale Dichtespannweite: {max_range:.6f}")
    lines.append(f"Stärkste Nichtlinearität bei token_anzahl = {best_token}")

    lines.append("")
    lines.append("Token | n | Mittelwert | Std | Min | Max | Range")

    for token in sorted(groups):
        values = groups[token]
        mean = stats.mean(values)
        std = stats.stdev(values) if len(values) >= 2 else 0
        rmin = min(values)
        rmax = max(values)
        rr = rmax - rmin

        lines.append(
            f"{token:>5} | {len(values):>3} | {mean:>10.6f} | "
            f"{std:>10.6f} | {rmin:>10.6f} | {rmax:>10.6f} | {rr:>10.6f}"
        )

    lines.append("")

text = "\n".join(lines)
print(text, flush=True)
OUT.write_text(text, encoding="utf-8")

print(f"\nBericht geschrieben: {OUT.resolve()}", flush=True)
input("ENTER zum Beenden...")