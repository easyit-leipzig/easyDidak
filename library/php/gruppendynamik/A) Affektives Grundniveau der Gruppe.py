import json
import matplotlib.pyplot as plt

# ----------------------------
# Daten laden
# ----------------------------
with open("A) Affektives Grundniveau der Gruppe.json", encoding="utf-8") as f:
    data = json.load(f)

group_ids = [d["gruppe_id"] for d in data]
x = group_ids                          # x = Gruppennummer
y = [0 for _ in data]                  # Dummy-Achse
z = [d["z_mean"] for d in data]        # ⟨z⟩ Affekt

# ----------------------------
# 3D-Plot
# ----------------------------
fig = plt.figure()
ax = fig.add_subplot(projection='3d')

ax.scatter(x, y, z)

# 👉 Gruppen-Labels direkt an den Punkten
for gid, xi, yi, zi in zip(group_ids, x, y, z):
    ax.text(
        xi, yi, zi,
        f"G{gid}",
        fontsize=9,
        horizontalalignment='center',
        verticalalignment='bottom'
    )

# ----------------------------
# Achsen & Titel
# ----------------------------
ax.set_xlabel("Gruppe")
ax.set_ylabel("Index (fixiert)")
ax.set_zlabel("⟨z⟩ Affektives Grundniveau")
ax.set_title("A) Affektives Grundniveau der Gruppen")

plt.show()
