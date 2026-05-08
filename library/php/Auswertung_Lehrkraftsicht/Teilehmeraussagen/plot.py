import json
import numpy as np
import matplotlib.pyplot as plt

# JSON laden
with open("frzk_auswertung_teilnehmeraussagen_alle_lk1_andere.json", "r", encoding="utf-8") as f:
    data = json.load(f)

gruppen = data["gruppen"]

dims = ["kognition","sozial","affektiv","motivation","methodik","performanz","regulation"]

# ==============================
# 1. RADAR CHART
# ==============================

def radar_plot():
    labels = dims
    angles = np.linspace(0, 2*np.pi, len(labels), endpoint=False).tolist()
    angles += angles[:1]

    def get_values(group):
        vals = [gruppen[group]["mean_vector"][d] for d in labels]
        vals += vals[:1]
        return vals

    fig = plt.figure()
    ax = plt.subplot(111, polar=True)

    ax.plot(angles, get_values("lehrkraft_1"))
    ax.plot(angles, get_values("andere"))

    ax.set_xticks(angles[:-1])
    ax.set_xticklabels(labels)

    plt.title("Abbildung 6.x.1: Mittlerer Zustandsvektor")
    plt.show()


# ==============================
# 2. BOXPLOT DICHTE
# ==============================

def boxplot_density():
    d1 = [b["d_semantisch"] for b in gruppen["lehrkraft_1"]["belege"]]
    d2 = [b["d_semantisch"] for b in gruppen["andere"]["belege"]]

    plt.figure()
    plt.boxplot([d1, d2])
    plt.xticks([1,2], ["Lehrkraft 1", "Andere"])

    plt.title("Abbildung 6.x.2: Semantische Dichte")
    plt.show()


# ==============================
# 3. ZEITVERLAUF
# ==============================

def time_series():
    def extract(group):
        kw = gruppen[group]["zeitverlauf_kw"]
        x = list(kw.keys())
        y = [kw[k]["d_semantisch_mean"] for k in x]
        return x, y

    x1,y1 = extract("lehrkraft_1")
    x2,y2 = extract("andere")

    plt.figure()
    plt.plot(x1, y1)
    plt.plot(x2, y2)

    plt.xticks(rotation=45)
    plt.title("Abbildung 6.x.3: Zeitverlauf der semantischen Dichte")
    plt.show()


# ==============================
# 4. DOMINANTE DIMENSION
# ==============================

def dominant_bar():
    d1 = gruppen["lehrkraft_1"]["dominante_dimensionen"]
    d2 = gruppen["andere"]["dominante_dimensionen"]

    keys = dims

    v1 = [d1.get(k,0) for k in keys]
    v2 = [d2.get(k,0) for k in keys]

    x = np.arange(len(keys))

    plt.figure()
    plt.bar(x - 0.2, v1, width=0.4)
    plt.bar(x + 0.2, v2, width=0.4)

    plt.xticks(x, keys, rotation=45)
    plt.title("Abbildung 6.x.4: Dominante Dimensionen")
    plt.show()


# ==============================
# RUN
# ==============================

radar_plot()
boxplot_density()
time_series()
dominant_bar()