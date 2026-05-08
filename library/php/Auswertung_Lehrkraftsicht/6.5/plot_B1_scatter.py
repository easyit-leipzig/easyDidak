import json
import matplotlib.pyplot as plt

with open("lehrkraft1.json", encoding="utf-8") as f:
    data_l1 = json.load(f)["daten"]
with open("nicht_l1.json", encoding="utf-8") as f:
    data_nl1 = json.load(f)["daten"]

def extract_xy(data):
    x = []
    y = []
    for d in data:
        if d["dominante_dimension_wert"] and d["d_semantisch"]:
            x.append(d["dominante_dimension_wert"])
            y.append(d["d_semantisch"])
    return x, y

x1, y1 = extract_xy(data_l1)
x2, y2 = extract_xy(data_nl1)

plt.figure()
plt.scatter(x1, y1, alpha=0.5, label="Lehrkraft 1")
plt.scatter(x2, y2, alpha=0.5, label="nicht Lehrkraft 1")
plt.legend()
plt.xlabel("Dominanzstärke δ")
plt.ylabel("Semantische Dichte d")
plt.title("Dominanz vs. semantische Dichte")
plt.show()
