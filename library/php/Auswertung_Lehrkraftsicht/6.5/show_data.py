# show_semantische_dichte_json.py

import json
from pathlib import Path

import pandas as pd
import matplotlib.pyplot as plt


INPUT_FILE = "datenm_values_sem_dichte_lehrer_type_3.json"


def load_data(filepath):
    payload = json.loads(Path(filepath).read_text(encoding="utf-8"))
    records = []

    for item in payload["daten"]:
        row = {
            "id": item["id"],
            "gruppe_id": item["gruppe_id"],
            "teilnehmer_id": item["teilnehmer_id"],
            "fach": item["fach"],
            "datum": item["datum"],
            "thema": item["thema"],
            "lehrkraft_id": item["lehrkraft_id"],
            "dominante_dimension": item["dominante_dimension"],
            "dominante_dimension_wert": item["dominante_dimension_wert"],
            "polaritaet_gesamt": item["polaritaet_gesamt"],
            "d_semantisch": item["d_semantisch"],
            "x_kognition": item["vektor_normiert"]["kognition"],
            "x_sozial": item["vektor_normiert"]["sozial"],
            "x_affektiv": item["vektor_normiert"]["affektiv"],
            "x_motivation": item["vektor_normiert"]["motivation"],
            "x_methodik": item["vektor_normiert"]["methodik"],
            "x_performanz": item["vektor_normiert"]["performanz"],
            "x_regulation": item["vektor_normiert"]["regulation"],
        }
        records.append(row)

    df = pd.DataFrame(records)
    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    return df


def print_overview(df):
    print("\n=== Überblick ===")
    print(df.head(10).to_string(index=False))

    print("\n=== Statistische Kurzbeschreibung ===")
    print(df[[
        "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
        "x_methodik", "x_performanz", "x_regulation", "d_semantisch"
    ]].describe().to_string())

    print("\n=== Dominante Dimensionen ===")
    print(df["dominante_dimension"].value_counts(dropna=False).to_string())

    print("\n=== Polarität ===")
    print(df["polaritaet_gesamt"].value_counts(dropna=False).sort_index().to_string())


def plot_d_semantisch_over_time(df):
    df_plot = df.dropna(subset=["datum"]).sort_values("datum")

    plt.figure(figsize=(12, 5))
    plt.plot(df_plot["datum"], df_plot["d_semantisch"], marker="o")
    plt.title("Semantische Dichte d_semantisch im Zeitverlauf")
    plt.xlabel("Datum")
    plt.ylabel("d_semantisch")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.show()


def plot_dimension_means(df):
    dims = [
        "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
        "x_methodik", "x_performanz", "x_regulation"
    ]
    means = df[dims].mean().sort_values(ascending=False)

    plt.figure(figsize=(10, 5))
    means.plot(kind="bar")
    plt.title("Mittlere normierte FRZK-Dimensionen")
    plt.xlabel("Dimension")
    plt.ylabel("Mittelwert")
    plt.xticks(rotation=45)
    plt.tight_layout()
    plt.show()


def plot_groupwise_density(df):
    group_df = (
        df.groupby("gruppe_id", dropna=False)["d_semantisch"]
        .mean()
        .sort_values(ascending=False)
    )

    plt.figure(figsize=(12, 5))
    group_df.plot(kind="bar")
    plt.title("Mittlere semantische Dichte nach Gruppe")
    plt.xlabel("Gruppe")
    plt.ylabel("mittlere d_semantisch")
    plt.tight_layout()
    plt.show()


def main():
    df = load_data(INPUT_FILE)
    print_overview(df)
    plot_d_semantisch_over_time(df)
    plot_dimension_means(df)
    plot_groupwise_density(df)


if __name__ == "__main__":
    main()