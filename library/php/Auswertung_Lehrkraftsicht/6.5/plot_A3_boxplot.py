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
plt.boxplot([
    extract_d(data_all),
    extract_d(data_l1),
    extract_d(data_nl1)
], labels=["alle", "L1", "≠L1"])

plt.title("Vergleich der semantischen Dichte")
plt.ylabel("d_semantisch")
plt.show()
