import json
from pathlib import Path

# ---------------------------
# Pfade
# ---------------------------
JSON_PATH = Path("Orientierungsverlüst_create_Schuelerprofil_Text.json")
OUT_TEXT  = Path("Orientierungsverlüst_create_Schuelerprofil_Text.md")

# ---------------------------
# JSON laden
# ---------------------------
with open(JSON_PATH, encoding="utf-8") as f:
    data = json.load(f)

# ---------------------------
# Kapiteltext generieren
# ---------------------------
lines = []
lines.append("# Kapitel 6 – Semantische Dichte und Schülerprofile\n")

for p in data["profile"]:
    lines.append(f"## {p['titel']}\n")

    # 6.X.1 Definition
    lines.append("### 6.X.1 Profildefinition\n")
    for t in p["kapiteltext"].get("profil_definition", []):
        lines.append(t + "\n")

    # 6.X.2 Interpretation
    lines.append("### 6.X.2 Interpretation\n")
    for t in p["kapiteltext"].get("interpretation", []):
        lines.append(t + "\n")

    # 6.X.3 Didaktische Ableitung
    lines.append("### 6.X.3 Didaktische Ableitung\n")

    for t in p["kapiteltext"].get("didaktik", []):
        lines.append("**Didaktik:** " + t + "\n")

    for t in p["kapiteltext"].get("fehler", []):
        lines.append("**Typischer Fehler:** " + t + "\n")

    for t in p["kapiteltext"].get("intervention", []):
        lines.append("**Interventionslogik:** " + t + "\n")

    # Interventionspfad explizit
    if p["interventionspfad"]:
        lines.append("**Interventionspfad:**\n")
        for step in p["interventionspfad"]:
            lines.append(
                f"- Schritt {step['schritt']}: "
                f"{step['phase'].upper()} – {step['text']}"
            )
        lines.append("")

# ---------------------------
# Schreiben
# ---------------------------
OUT_TEXT.write_text("\n".join(lines), encoding="utf-8")

print("Kapitel-6-Text erfolgreich erzeugt:", OUT_TEXT)
