import pandas as pd
import matplotlib.pyplot as plt
import json

with open("alle.json", encoding="utf-8") as f:
    data_all = json.load(f)["daten"]
with open("1.json", encoding="utf-8") as f:
    data_l1 = json.load(f)["daten"]
with open("nicht 1.json", encoding="utf-8") as f:
    data_nl1 = json.load(f)["daten"]

def to_df(data):
    df = pd.DataFrame(data)
    df["datum"] = pd.to_datetime(df["datum"])
    return df.sort_values("datum")

df_l1 = to_df(data_l1)
df_nl1 = to_df(data_nl1)

plt.figure()
plt.boxplot([
    extract_d(data_all),
    extract_d(data_l1),
    extract_d(data_nl1)
], labels=["alle", "L1", "≠L1"])
plt.title("Vergleich der semantischen Dichte")
plt.ylabel("d_semantisch")
plt.show()