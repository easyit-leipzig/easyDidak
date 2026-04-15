import numpy as np
import matplotlib.pyplot as plt
from pathlib import Path

# Zielordner
out_dir = Path("./frzk_grafiken")
out_dir.mkdir(exist_ok=True)

# Kohärenzwerte (aus deinen Daten)
strengths = {
    "ALL": 0.386,
    "Lehrkraft_1": 0.476,
    "nicht_Lehrkraft_1": 0.310
}

# Verstärkung (nichtlinear → sichtbar!)
def amp(s):
    return (s ** 2) * 5.0

# Gitter für Vektorfeld
xx, yy = np.meshgrid(np.linspace(-2.5, 2.5, 20),
                     np.linspace(-2.5, 2.5, 20))

# feste Startpunkte
starts = np.array([
    [-2.0, -1.8],
    [-2.1,  1.7],
    [-1.4,  0.8],
    [-0.8, -2.1],
    [ 0.9,  1.9],
    [ 1.7, -1.6],
    [ 2.0,  1.3],
    [ 1.5, -0.4],
])

n_steps = 16

for name, s in strengths.items():

    a = amp(s)

    # Vektorfeld
    u = -(0.2 + a) * xx - 0.25 * yy
    v =  0.25 * xx - (0.2 + a) * yy

    # Übergangsmatrix
    A = np.array([
        [1 - (0.2 + a), -0.25],
        [0.25, 1 - (0.2 + a)]
    ])

    fig, ax = plt.subplots(figsize=(7, 6))

    # Feld zeichnen
    ax.quiver(xx, yy, u, v, angles="xy")

    # Trajektorien
    for p0 in starts:
        pts = [p0]
        p = p0.copy()

        for _ in range(n_steps):
            p = A @ p
            pts.append(p.copy())

        pts = np.array(pts)

        ax.plot(pts[:, 0], pts[:, 1], linewidth=2)
        ax.scatter(pts[0, 0], pts[0, 1], marker="x", s=30)
        ax.scatter(pts[-1, 0], pts[-1, 1], s=25)

    # Achsen
    ax.axhline(0)
    ax.axvline(0)
    ax.set_xlim(-2.5, 2.5)
    ax.set_ylim(-2.5, 2.5)

    ax.set_xlabel("Zustandsachse 1")
    ax.set_ylabel("Zustandsachse 2")

    ax.set_title(f"Attraktorfeld + Trajektorien – {name}")

    # speichern
    file_path = out_dir / f"grafik_stabilitaet_kombiniert_{name}.png"
    fig.savefig(file_path, dpi=300, bbox_inches="tight")

    plt.close(fig)

print("Fertig. Dateien liegen in:", out_dir)