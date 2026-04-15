import json
import numpy as np
import matplotlib.pyplot as plt

data = [
    {
        "gruppe_id": 1,
        "z_mean": 1.7638891637325287,
        "z_std": 0.3151889629436303,
        "n": 12
    },
    {
        "gruppe_id": 2,
        "z_mean": 1.8775000095367431,
        "z_std": 0.24560136468765115,
        "n": 10
    },
    {
        "gruppe_id": 3,
        "z_mean": 1.3571428571428572,
        "z_std": 0.3499271061118826,
        "n": 7
    },
    {
        "gruppe_id": 4,
        "z_mean": 2.3704545389522207,
        "z_std": 0.4502573383799269,
        "n": 11
    },
    {
        "gruppe_id": 5,
        "z_mean": 1.8825754469091243,
        "z_std": 0.4574084945828843,
        "n": 11
    },
    {
        "gruppe_id": 6,
        "z_mean": 1.7191670060157775,
        "z_std": 0.364311983959819,
        "n": 10
    },
    {
        "gruppe_id": 7,
        "z_mean": 1.6363636363636365,
        "z_std": 0.5164865865538595,
        "n": 11
    },
    {
        "gruppe_id": 8,
        "z_mean": 2.0520833233992257,
        "z_std": 0.27737018954525544,
        "n": 12
    },
    {
        "gruppe_id": 9,
        "z_mean": 2.1999999947018094,
        "z_std": 0.11493367182109622,
        "n": 9
    }
]

group_ids = np.array([d["gruppe_id"] for d in data], dtype=float)
z_mean = np.array([d["z_mean"] for d in data], dtype=float)
z_std = np.array([d["z_std"] for d in data], dtype=float)
n_vals = np.array([d["n"] for d in data], dtype=float)

y_pos = (n_vals - n_vals.min()) / (n_vals.max() - n_vals.min() + 1e-9)

x = np.linspace(0.5, 9.5, 220)
y = np.linspace(-0.1, 1.1, 180)
X, Y = np.meshgrid(x, y)

Z = np.zeros_like(X)
for gid, mu_z, sigma_z, n_i, y_i in zip(group_ids, z_mean, z_std, n_vals, y_pos):
    sigma_x = 0.28 + 1.2 * sigma_z
    sigma_y = 0.08 + 0.02 * (12 - n_i)
    amplitude = mu_z * (0.92 + 0.08 * (n_i / n_vals.max()))
    Z += amplitude * np.exp(
        -(((X - gid) ** 2) / (2 * sigma_x ** 2) + ((Y - y_i) ** 2) / (2 * sigma_y ** 2))
    )

dZ_dy, dZ_dx = np.gradient(Z, y, x)
grad_mag = np.sqrt(dZ_dx**2 + dZ_dy**2)

fig = plt.figure(figsize=(12, 8))
ax = fig.add_subplot(111, projection="3d")
ax.plot_surface(X, Y, Z, linewidth=0, antialiased=True, alpha=0.9)
ax.set_title("FRZK-3D-Simulation der Soll-Daten: affektives Grundniveau")
ax.set_xlabel("Gruppenfeld (gruppe_id)")
ax.set_ylabel("soziale Einbettung (normiertes n)")
ax.set_zlabel("affektive Soll-Dichte h_aff(x,y)")
for gid, mu_z, y_i in zip(group_ids, z_mean, y_pos):
    ax.text(gid, y_i, mu_z + 0.08, f"G{int(gid)}", fontsize=9)
plt.tight_layout()
plt.show()

plt.figure(figsize=(10, 6))
plt.imshow(
    grad_mag,
    extent=[x.min(), x.max(), y.min(), y.max()],
    origin="lower",
    aspect="auto",
)
plt.title("FRZK-Transitionsintensität |∇h_aff|")
plt.xlabel("Gruppenfeld (gruppe_id)")
plt.ylabel("soziale Einbettung (normiertes n)")
plt.colorbar(label="Gradientenstärke")
plt.tight_layout()
plt.show()
