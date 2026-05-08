import json
import matplotlib.pyplot as plt
from mpl_toolkits.mplot3d import Axes3D

with open("lehrkraft1.json", encoding="utf-8") as f:
    data_l1 = json.load(f)["daten"]
with open("nicht_l1.json", encoding="utf-8") as f:
    data_nl1 = json.load(f)["daten"]

fig = plt.figure()
ax = fig.add_subplot(111, projection='3d')

def plot3d(data, label):
    xs = [d["vektor_normiert"]["kognition"] for d in data]
    ys = [d["vektor_normiert"]["motivation"] for d in data]
    zs = [d["d_semantisch"] for d in data]
    ax.scatter(xs, ys, zs, label=label, alpha=0.5)

plot3d(data_l1, "L1")
plot3d(data_nl1, "≠L1")

ax.set_xlabel("Kognition")
ax.set_ylabel("Motivation")
ax.set_zlabel("Dichte")

plt.legend()
plt.title("Struktur-Intensitäts-Raum")
plt.show()
