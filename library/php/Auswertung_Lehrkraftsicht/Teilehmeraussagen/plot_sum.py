# plot_sem_d_lehrer_summe.py

import json
import matplotlib.pyplot as plt
import numpy as np

INPUT_FILE = "sem_d_lehrer_summe.json"

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def load():
    with open(INPUT_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def plot_vectors(data):

    groups = ["gesamt", "lehrkraft_1", "andere"]

    values = []
    for g in groups:
        vec = data[g]["sum_vector"]
        values.append([vec[d] for d in DIMENSIONS])

    values = np.array(values)

    x = np.arange(len(DIMENSIONS))
    width = 0.25

    plt.figure(figsize=(12, 6))

    for i, g in enumerate(groups):
        plt.bar(x + i * width, values[i], width, label=g)

    plt.xticks(x + width, DIMENSIONS, rotation=30)
    plt.title("Summierte FRZK-Dimensionen")
    plt.ylabel("Summe")
    plt.legend()
    plt.grid(axis="y")

    plt.tight_layout()
    plt.savefig("summe_dimensionen.png", dpi=300)
    plt.close()


def plot_norms(data):

    labels = ["gesamt", "lehrkraft_1", "andere"]
    norms = [data[g]["norm_sum"] for g in labels]

    plt.figure(figsize=(6, 5))
    plt.bar(labels, norms)

    plt.title("Norm der Summenvektoren")
    plt.ylabel("||Summe||")
    plt.grid(axis="y")

    plt.tight_layout()
    plt.savefig("summe_norm.png", dpi=300)
    plt.close()


def main():
    data = load()
    plot_vectors(data)
    plot_norms(data)

    print("Plots erstellt")


if __name__ == "__main__":
    main()