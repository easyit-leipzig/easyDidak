import json
import numpy as np
import matplotlib.pyplot as plt

json2 = [
    {
        "gruppe_id": 1,
        "kohärenz_mean": 0.9054,
        "kohärenz_std": 0.0468,
        "stabilitaet_mean": 0.9588,
        "stabilitaet_std": 0.0329,
        "dynamik_mean": 0.2604,
        "dynamik_std": 0.2038,
        "n": 12
    },
    {
        "gruppe_id": 2,
        "kohärenz_mean": 0.8651,
        "kohärenz_std": 0.0519,
        "stabilitaet_mean": 0.9157,
        "stabilitaet_std": 0.0595,
        "dynamik_mean": 0.1576,
        "dynamik_std": 0.1374,
        "n": 10
    },
    {
        "gruppe_id": 3,
        "kohärenz_mean": 0.9016,
        "kohärenz_std": 0.0604,
        "stabilitaet_mean": 0.9468,
        "stabilitaet_std": 0.0454,
        "dynamik_mean": 0.3357,
        "dynamik_std": 0.2745,
        "n": 7
    },
    {
        "gruppe_id": 4,
        "kohärenz_mean": 0.8902,
        "kohärenz_std": 0.0496,
        "stabilitaet_mean": 0.9481,
        "stabilitaet_std": 0.0431,
        "dynamik_mean": 0.327,
        "dynamik_std": 0.2967,
        "n": 11
    },
    {
        "gruppe_id": 5,
        "kohärenz_mean": 0.912,
        "kohärenz_std": 0.0551,
        "stabilitaet_mean": 0.9568,
        "stabilitaet_std": 0.0603,
        "dynamik_mean": 0.2699,
        "dynamik_std": 0.2625,
        "n": 11
    },
    {
        "gruppe_id": 6,
        "kohärenz_mean": 0.883,
        "kohärenz_std": 0.0373,
        "stabilitaet_mean": 0.9464,
        "stabilitaet_std": 0.0316,
        "dynamik_mean": 0.2928,
        "dynamik_std": 0.1812,
        "n": 10
    },
    {
        "gruppe_id": 7,
        "kohärenz_mean": 0.8504,
        "kohärenz_std": 0.0754,
        "stabilitaet_mean": 0.9011,
        "stabilitaet_std": 0.0741,
        "dynamik_mean": 0.3618,
        "dynamik_std": 0.3406,
        "n": 11
    },
    {
        "gruppe_id": 8,
        "kohärenz_mean": 0.8646,
        "kohärenz_std": 0.0789,
        "stabilitaet_mean": 0.9092,
        "stabilitaet_std": 0.0975,
        "dynamik_mean": 0.228,
        "dynamik_std": 0.1527,
        "n": 12
    },
    {
        "gruppe_id": 9,
        "kohärenz_mean": 0.9134,
        "kohärenz_std": 0.0362,
        "stabilitaet_mean": 0.9681,
        "stabilitaet_std": 0.0215,
        "dynamik_mean": 0.123,
        "dynamik_std": 0.0778,
        "n": 9
    },
    {
        "gruppe_id": 10,
        "kohärenz_mean": 1.0,
        "kohärenz_std": 0.0,
        "stabilitaet_mean": 0.0,
        "stabilitaet_std": 0.0,
        "dynamik_mean": 0.0,
        "dynamik_std": 0.0,
        "n": 1
    }
]
json3 = [
    {
        "gruppe_id": 1,
        "mean_abs_dh": 0.260415,
        "var_dh": 0.0415291,
        "loop_density": 0.75
    },
    {
        "gruppe_id": 2,
        "mean_abs_dh": 0.157639,
        "var_dh": 0.0188675,
        "loop_density": 0.8
    },
    {
        "gruppe_id": 3,
        "mean_abs_dh": 0.335713,
        "var_dh": 0.0753518,
        "loop_density": 0.285714
    },
    {
        "gruppe_id": 4,
        "mean_abs_dh": 0.327021,
        "var_dh": 0.0880181,
        "loop_density": 0.454545
    },
    {
        "gruppe_id": 5,
        "mean_abs_dh": 0.26995,
        "var_dh": 0.0688998,
        "loop_density": 0.454545
    },
    {
        "gruppe_id": 6,
        "mean_abs_dh": 0.292777,
        "var_dh": 0.03282,
        "loop_density": 0.6
    },
    {
        "gruppe_id": 7,
        "mean_abs_dh": 0.361784,
        "var_dh": 0.115991,
        "loop_density": 0.545455
    },
    {
        "gruppe_id": 8,
        "mean_abs_dh": 0.227962,
        "var_dh": 0.0233248,
        "loop_density": 0.333333
    },
    {
        "gruppe_id": 9,
        "mean_abs_dh": 0.122963,
        "var_dh": 0.00604693,
        "loop_density": 0.777778
    }
]

data2 = [d for d in json2 if d["n"] > 1]
g2 = np.array([d["gruppe_id"] for d in data2], dtype=float)
c2 = np.array([d["kohärenz_mean"] for d in data2], dtype=float)
s2 = np.array([d["stabilitaet_mean"] for d in data2], dtype=float)
d2 = np.array([d["dynamik_mean"] for d in data2], dtype=float)
cs2 = np.array([d["kohärenz_std"] for d in data2], dtype=float)
ss2 = np.array([d["stabilitaet_std"] for d in data2], dtype=float)

x2 = np.linspace(0.5, 9.5, 240)
y2 = np.linspace(0.05, 0.42, 220)
X2, Y2 = np.meshgrid(x2, y2)

Z2 = np.zeros_like(X2)
for gid, coh, sta, dyn, coh_std, sta_std in zip(g2, c2, s2, d2, cs2, ss2):
    amp = coh * sta
    sigma_x = 0.25 + 3.0 * coh_std
    sigma_y = 0.015 + 1.5 * (coh_std + sta_std) / 2
    Z2 += amp * np.exp(-(((X2 - gid) ** 2) / (2 * sigma_x**2) + ((Y2 - dyn) ** 2) / (2 * sigma_y**2)))

fig = plt.figure(figsize=(12, 8))
ax = fig.add_subplot(111, projection="3d")
ax.plot_surface(X2, Y2, Z2, linewidth=0, antialiased=True, alpha=0.92)
ax.set_title("FRZK-3D-Simulation zu JSON 2: Kohärenz-Stabilitäts-Feld")
ax.set_xlabel("Gruppenfeld (gruppe_id)")
ax.set_ylabel("Dynamikachse D")
ax.set_zlabel("Zustandsdichte h_CS(x,D)")
plt.tight_layout()
plt.show()

g3 = np.array([d["gruppe_id"] for d in json3], dtype=float)
m3 = np.array([d["mean_abs_dh"] for d in json3], dtype=float)
v3 = np.array([d["var_dh"] for d in json3], dtype=float)
l3 = np.array([d["loop_density"] for d in json3], dtype=float)

v3_norm = v3 / (v3.max() + 1e-9)

x3 = np.linspace(0.5, 9.5, 240)
y3 = np.linspace(0.20, 0.85, 220)
X3, Y3 = np.meshgrid(x3, y3)

Z3 = np.zeros_like(X3)
for gid, mean_dh, var_dh, loop_d in zip(g3, m3, v3, l3):
    amp = mean_dh * (1 - (var_dh / (v3.max() + 1e-9)) * 0.6) + 0.25 * loop_d
    sigma_x = 0.28 + 2.2 * var_dh
    sigma_y = 0.02 + 0.08 * (1 - loop_d)
    Z3 += amp * np.exp(-(((X3 - gid) ** 2) / (2 * sigma_x**2) + ((Y3 - loop_d) ** 2) / (2 * sigma_y**2)))

fig = plt.figure(figsize=(12, 8))
ax = fig.add_subplot(111, projection="3d")
ax.plot_surface(X3, Y3, Z3, linewidth=0, antialiased=True, alpha=0.92)
ax.set_title("FRZK-3D-Simulation zu JSON 3: Dynamik-Loop-Feld")
ax.set_xlabel("Gruppenfeld (gruppe_id)")
ax.set_ylabel("Loop-Dichte L")
ax.set_zlabel("Transformationsdichte h_DL(x,L)")
plt.tight_layout()
plt.show()
