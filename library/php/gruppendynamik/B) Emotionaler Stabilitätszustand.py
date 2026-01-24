import json
import matplotlib.pyplot as plt

# ---------------------------------
# Daten laden
# ---------------------------------
with open("B) Emotionaler Stabilitätszustand.json", encoding="utf-8") as f:
    data = json.load(f)

gruppen = [d["gruppe_id"] for d in data]
x = [d["kohärenz_mean"] for d in data]
y = [d["stabilitaet_mean"] for d in data]
z = [d["dynamik_mean"] for d in data]

# ---------------------------------
# 3D-Plot
# ---------------------------------
fig = plt.figure()
ax = fig.add_subplot(projection="3d")

ax.scatter(x, y, z)

# 👉 Gruppennummern direkt am Punkt
for gid, xi, yi, zi in zip(gruppen, x, y, z):
    ax.text(
        xi, yi, zi,
        f"G{gid}",
        fontsize=9,
        horizontalalignment="center",
        verticalalignment="bottom"
    )

# ---------------------------------
# Achsen & Titel
# ---------------------------------
ax.set_xlabel("⟨K⟩ Kohärenz")
ax.set_ylabel("⟨S⟩ Stabilität")
ax.set_zlabel("⟨D⟩ Dynamik")

ax.set_title("B) Strukturelle Kohärenz & Stabilität der Gruppen")

plt.show()