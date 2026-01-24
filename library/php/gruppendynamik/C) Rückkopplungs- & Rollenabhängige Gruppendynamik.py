import json
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D

# -------------------------------------------------
# JSON laden
# -------------------------------------------------
with open("C) Rückkopplungs- & Rollenabhängige Gruppendynamik.json", encoding="utf-8") as f:
    data = json.load(f)

# -------------------------------------------------
# Explizite Feldzuordnung (WICHTIG)
# -------------------------------------------------
x = [d["mean_abs_dh"] for d in data]      # Rückkopplungsstärke
y = [d["var_dh"] for d in data]           # Volatilität
z = [d["loop_density"] for d in data]     # Selbstregulation
g = [d["gruppe_id"] for d in data]        # Gruppennummer

# -------------------------------------------------
# Plot
# -------------------------------------------------
fig = plt.figure()
ax = fig.add_subplot(111, projection="3d")

ax.scatter(x, y, z)

# Gruppennummern einzeichnen
for i, gid in enumerate(g):
    ax.text(
        x[i], y[i], z[i],
        f"G{gid}",
        fontsize=9,
        horizontalalignment="center"
    )

# Achsenbeschriftung (Kapitel-3-konform)
ax.set_xlabel("⟨|Δh|⟩ Rückkopplung")
ax.set_ylabel("Var(dh/dt) Volatilität")
ax.set_zlabel("Loop-Dichte Selbstregulation")

ax.set_title("C) Rückkopplungs- & Regulationsabhängigkeit")

plt.tight_layout()
plt.show()
