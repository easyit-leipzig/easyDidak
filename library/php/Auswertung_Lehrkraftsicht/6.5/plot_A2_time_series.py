import json
import pandas as pd
import matplotlib.pyplot as plt

def to_df(data):
    df = pd.DataFrame(data)
    df["datum"] = pd.to_datetime(df["datum"])
    return df.sort_values("datum")

with open("lehrkraft1.json", encoding="utf-8") as f:
    data_l1 = json.load(f)["daten"]
with open("nicht_l1.json", encoding="utf-8") as f:
    data_nl1 = json.load(f)["daten"]

df_l1 = to_df(data_l1)
df_nl1 = to_df(data_nl1)

plt.figure()
plt.plot(df_l1["datum"], df_l1["d_semantisch"], label="Lehrkraft 1")
plt.plot(df_nl1["datum"], df_nl1["d_semantisch"], label="nicht Lehrkraft 1")
plt.legend()
plt.title("Zeitverlauf der semantischen Dichte")
plt.xlabel("Datum")
plt.ylabel("d_semantisch")
plt.xticks(rotation=45)
plt.tight_layout()
plt.show()
