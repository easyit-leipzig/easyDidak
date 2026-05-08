import json
import numpy as np
import matplotlib.pyplot as plt

# JSON laden
with open("frzk_6x9_daten.json", "r", encoding="utf-8") as f:
    data = json.load(f)

# Vektoren extrahieren
vectors = []
dates = []

for row in data:
    vec = np.array([
        row["x_kognition"],
        row["x_sozial"],
        row["x_affektiv"],
        row["x_motivation"],
        row["x_methodik"],
        row["x_performanz"],
        row["x_regulation"]
    ], dtype=float)

    vectors.append(vec)
    dates.append(row["datum"])

vectors = np.array(vectors)

# ΔS(t) berechnen
delta = np.diff(vectors, axis=0)

# Normen der Änderungen
delta_norm = np.linalg.norm(delta, axis=1)

# Stabilität
stab = 1 - np.mean(delta_norm)

# Varianz
var = np.var(vectors)

# mittlere Änderung
delta_mean = np.mean(delta_norm)

# Kohärenz
coherence = (1 / (1 + var)) * (1 / (1 + delta_mean))

print("\n--- FRZK 6.x.9 ---")
print(f"Stabilität: {stab:.4f}")
print(f"Kohärenz:   {coherence:.4f}")

# Plot
plt.figure()
plt.plot(delta_norm)
plt.title("ΔS(t) – Dynamik des Systems")
plt.xlabel("t")
plt.ylabel("||ΔS(t)||")
plt.grid()

plt.figure()
plt.plot(np.linalg.norm(vectors, axis=1))
plt.title("Semantische Zustandsnorm ||S(t)||")
plt.xlabel("t")
plt.ylabel("Norm")
plt.grid()

plt.show()