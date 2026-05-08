import json
import matplotlib.pyplot as plt

with open("alle.json", encoding="utf-8") as f:
    data_all = json.load(f)["daten"]
with open("lehrkraft1.json", encoding="utf-8") as f:
    data_l1 = json.load(f)["daten"]
with open("nicht_l1.json", encoding="utf-8") as f:
    data_nl1 = json.load(f)["daten"]

def extract_d(data):
    return [d["d_semantisch"] for d in data if d["d_semantisch"] is not None]

plt.figure()
plt.hist(extract_d(data_all), bins=40, alpha=0.5, label="alle")
plt.hist(extract_d(data_l1), bins=40, alpha=0.5, label="Lehrkraft 1")
plt.hist(extract_d(data_nl1), bins=40, alpha=0.5, label="nicht Lehrkraft 1")
plt.legend()
plt.title("Verteilung der semantischen Dichte")
plt.xlabel("d_semantisch")
plt.ylabel("Häufigkeit")
plt.show()
