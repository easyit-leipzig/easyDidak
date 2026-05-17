# export_resonanz_lehrkraftdaten_v2.py
#
# WICHTIG:
# Die Resonanz wird JE TEILGRUPPE neu berechnet.
# Dadurch entstehen keine "Übersprünge"
# zwischen Lehrkraft 1 und Nicht-Lehrkraft 1.
#
# Ergebnis:
# - frzk_resonanz_lehrkraftdaten_v2.json

import json
from pathlib import Path

import mysql.connector
import pandas as pd

OUTPUT = Path("frzk_resonanz_lehrkraftdaten_v2.json")

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "connection_timeout": 5,
    "use_pure": True,
}

SQL = """
SELECT
    id_mtr_rueckkopplung_datenmaske AS id,
    datum,
    lehrkraft_id,
    teilnehmer_id,
    gruppe_id,

    d_semantisch_mean AS d_semantisch,
    d_semantisch_std,

    polaritaet_index AS polaritaet_gesamt,

    dominanz_breite,
    semantische_breite,
    satzanzahl,

    mean_kognition AS x_kognition,
    mean_sozial AS x_sozial,
    mean_affektiv AS x_affektiv,
    mean_motivation AS x_motivation,
    mean_methodik AS x_methodik,
    mean_performanz AS x_performanz,
    mean_regulation AS x_regulation,

    CASE

        WHEN ABS(mean_kognition) >= GREATEST(
            ABS(mean_sozial),
            ABS(mean_affektiv),
            ABS(mean_motivation),
            ABS(mean_methodik),
            ABS(mean_performanz),
            ABS(mean_regulation)
        ) THEN 'kognition'

        WHEN ABS(mean_sozial) >= GREATEST(
            ABS(mean_kognition),
            ABS(mean_affektiv),
            ABS(mean_motivation),
            ABS(mean_methodik),
            ABS(mean_performanz),
            ABS(mean_regulation)
        ) THEN 'sozial'

        WHEN ABS(mean_affektiv) >= GREATEST(
            ABS(mean_kognition),
            ABS(mean_sozial),
            ABS(mean_motivation),
            ABS(mean_methodik),
            ABS(mean_performanz),
            ABS(mean_regulation)
        ) THEN 'affektiv'

        WHEN ABS(mean_motivation) >= GREATEST(
            ABS(mean_kognition),
            ABS(mean_sozial),
            ABS(mean_affektiv),
            ABS(mean_methodik),
            ABS(mean_performanz),
            ABS(mean_regulation)
        ) THEN 'motivation'

        WHEN ABS(mean_methodik) >= GREATEST(
            ABS(mean_kognition),
            ABS(mean_sozial),
            ABS(mean_affektiv),
            ABS(mean_motivation),
            ABS(mean_performanz),
            ABS(mean_regulation)
        ) THEN 'methodik'

        WHEN ABS(mean_performanz) >= GREATEST(
            ABS(mean_kognition),
            ABS(mean_sozial),
            ABS(mean_affektiv),
            ABS(mean_motivation),
            ABS(mean_methodik),
            ABS(mean_regulation)
        ) THEN 'performanz'

        ELSE 'regulation'

    END AS dominante_dimension,

    GREATEST(
        ABS(mean_kognition),
        ABS(mean_sozial),
        ABS(mean_affektiv),
        ABS(mean_motivation),
        ABS(mean_methodik),
        ABS(mean_performanz),
        ABS(mean_regulation)
    ) AS dominante_dimension_wert

FROM analyze_lehrkraftdaten

WHERE datum IS NOT NULL
  AND teilnehmer_id IS NOT NULL

ORDER BY
    teilnehmer_id,
    datum,
    id_mtr_rueckkopplung_datenmaske;
"""


def mean_or_none(series):

    s = series.dropna()

    return float(s.mean()) if len(s) else None


def std_or_none(series):

    s = series.dropna()

    return float(s.std(ddof=0)) if len(s) else None


def cosine_similarity(a, b):

    num = (a * b).sum()

    den = (
        ((a * a).sum() ** 0.5)
        *
        ((b * b).sum() ** 0.5)
    )

    if den == 0:
        return None

    return float(num / den)


def add_resonance(group):

    group = group.sort_values(
        ["datum", "id"]
    ).copy()

    vectors = (
        group[DIMENSIONS]
        .fillna(0)
        .to_numpy()
    )

    cosines = [None]
    same_dom = [None]
    same_pol = [None]

    d_delta = [None]
    dom_delta = [None]

    for i in range(1, len(group)):

        cosines.append(
            cosine_similarity(
                vectors[i - 1],
                vectors[i]
            )
        )

        same_dom.append(
            1 if (
                group.iloc[i]["dominante_dimension"]
                ==
                group.iloc[i - 1]["dominante_dimension"]
            )
            else 0
        )

        same_pol.append(
            1 if (
                group.iloc[i]["polaritaet_gesamt"]
                ==
                group.iloc[i - 1]["polaritaet_gesamt"]
            )
            else 0
        )

        d_delta.append(
            float(
                group.iloc[i]["d_semantisch"]
                -
                group.iloc[i - 1]["d_semantisch"]
            )
            if (
                pd.notna(group.iloc[i]["d_semantisch"])
                and
                pd.notna(group.iloc[i - 1]["d_semantisch"])
            )
            else None
        )

        dom_delta.append(
            float(
                group.iloc[i]["dominante_dimension_wert"]
                -
                group.iloc[i - 1]["dominante_dimension_wert"]
            )
            if (
                pd.notna(group.iloc[i]["dominante_dimension_wert"])
                and
                pd.notna(group.iloc[i - 1]["dominante_dimension_wert"])
            )
            else None
        )

    group["cosine_to_previous"] = cosines
    group["same_dominance_as_previous"] = same_dom
    group["same_polarity_as_previous"] = same_pol

    group["d_semantisch_delta"] = d_delta
    group["dominante_dimension_wert_delta"] = dom_delta

    return group


def calculate_resonance(dataframe):

    if dataframe.empty:
        return dataframe.copy()

    out = []

    for _, group in dataframe.groupby(
        "teilnehmer_id",
        dropna=False
    ):

        out.append(
            add_resonance(group)
        )

    return pd.concat(
        out,
        ignore_index=True
    )


def analyze_subset(dataframe, label):

    dataframe = calculate_resonance(dataframe)

    teilnehmer_liste = []

    if dataframe.empty:

        return {
            "gruppe": label,
            "n_datensaetze": 0,
            "n_teilnehmer": 0,
            "n_lehrkraefte": 0,
            "zeitraum": {
                "von": None,
                "bis": None
            },
            "gesamt": {},
            "teilnehmer": []
        }

    for tid, g in dataframe.groupby(
        "teilnehmer_id",
        dropna=False
    ):

        dom_counts = (
            g["dominante_dimension"]
            .value_counts()
            .to_dict()
        )

        main_dom = (
            max(dom_counts, key=dom_counts.get)
            if dom_counts else None
        )

        cosine_mean = mean_or_none(
            g["cosine_to_previous"]
        )

        dom_persist = mean_or_none(
            g["same_dominance_as_previous"]
        )

        pol_persist = mean_or_none(
            g["same_polarity_as_previous"]
        )

        resonanzindex = float(
            (
                (cosine_mean if cosine_mean is not None else 0)
                +
                (dom_persist if dom_persist is not None else 0)
                +
                (pol_persist if pol_persist is not None else 0)
            ) / 3
        )

        teilnehmer_liste.append({

            "teilnehmer_id":
                int(tid),

            "n":
                int(len(g)),

            "datum_von":
                str(g["datum"].min().date()),

            "datum_bis":
                str(g["datum"].max().date()),

            "dominanzverteilung":
                dom_counts,

            "hauptdominanz":
                main_dom,

            "hauptdominanz_anteil":
                (
                    float(dom_counts[main_dom] / len(g))
                    if main_dom else None
                ),

            "dominanz_persistenz":
                dom_persist,

            "polaritaet_persistenz":
                pol_persist,

            "cosine_resonanz_mean":
                cosine_mean,

            "cosine_resonanz_std":
                std_or_none(
                    g["cosine_to_previous"]
                ),

            "d_semantisch_mean":
                mean_or_none(
                    g["d_semantisch"]
                ),

            "d_semantisch_std":
                std_or_none(
                    g["d_semantisch"]
                ),

            "d_semantisch_delta_mean":
                mean_or_none(
                    g["d_semantisch_delta"]
                ),

            "dominante_dimension_wert_mean":
                mean_or_none(
                    g["dominante_dimension_wert"]
                ),

            "dominante_dimension_wert_delta_mean":
                mean_or_none(
                    g["dominante_dimension_wert_delta"]
                ),

            "semantische_breite_mean":
                mean_or_none(
                    g["semantische_breite"]
                ),

            "dominanz_breite_mean":
                mean_or_none(
                    g["dominanz_breite"]
                ),

            "satzanzahl_mean":
                mean_or_none(
                    g["satzanzahl"]
                ),

            "resonanzindex":
                resonanzindex
        })

    resonanz_df = pd.DataFrame(
        teilnehmer_liste
    )

    return {

        "gruppe":
            label,

        "n_datensaetze":
            int(len(dataframe)),

        "n_teilnehmer":
            int(
                dataframe["teilnehmer_id"]
                .nunique()
            ),

        "n_lehrkraefte":
            int(
                dataframe["lehrkraft_id"]
                .nunique()
            ),

        "zeitraum": {

            "von":
                str(
                    dataframe["datum"]
                    .min()
                    .date()
                ),

            "bis":
                str(
                    dataframe["datum"]
                    .max()
                    .date()
                )
        },

        "gesamt": {

            "cosine_resonanz_mean":
                mean_or_none(
                    dataframe["cosine_to_previous"]
                ),

            "cosine_resonanz_std":
                std_or_none(
                    dataframe["cosine_to_previous"]
                ),

            "dominanz_persistenz":
                mean_or_none(
                    dataframe["same_dominance_as_previous"]
                ),

            "polaritaet_persistenz":
                mean_or_none(
                    dataframe["same_polarity_as_previous"]
                ),

            "d_semantisch_mean":
                mean_or_none(
                    dataframe["d_semantisch"]
                ),

            "d_semantisch_std":
                std_or_none(
                    dataframe["d_semantisch"]
                ),

            "d_semantisch_delta_mean":
                mean_or_none(
                    dataframe["d_semantisch_delta"]
                ),

            "dominante_dimension_wert_mean":
                mean_or_none(
                    dataframe["dominante_dimension_wert"]
                ),

            "dominante_dimension_wert_delta_mean":
                mean_or_none(
                    dataframe["dominante_dimension_wert_delta"]
                ),

            "semantische_breite_mean":
                mean_or_none(
                    dataframe["semantische_breite"]
                ),

            "dominanz_breite_mean":
                mean_or_none(
                    dataframe["dominanz_breite"]
                ),

            "satzanzahl_mean":
                mean_or_none(
                    dataframe["satzanzahl"]
                ),

            "mittlerer_resonanzindex":
                (
                    float(
                        resonanz_df["resonanzindex"]
                        .mean()
                    )
                    if not resonanz_df.empty
                    else None
                )
        },

        "teilnehmer":
            teilnehmer_liste
    }


print("Verbinde mit MySQL...")

conn = mysql.connector.connect(
    **DB_CONFIG
)

print("Verbindung OK")

print("Lade Daten...")

df = pd.read_sql(
    SQL,
    conn
)

conn.close()

print("Datensätze geladen:", len(df))

if df.empty:
    raise ValueError(
        "Keine Daten gefunden."
    )

df["datum"] = pd.to_datetime(
    df["datum"]
)

for col in DIMENSIONS + [

    "d_semantisch",
    "d_semantisch_std",
    "polaritaet_gesamt",
    "dominanz_breite",
    "semantische_breite",
    "satzanzahl",
    "dominante_dimension_wert"

]:
    df[col] = pd.to_numeric(
        df[col],
        errors="coerce"
    )

print("Berechne Analysen...")

out = {

    "analyse":
        "2. Resonanzvorhersage des FRZK",

    "basis":
        "analyze_lehrkraftdaten",

    "methodischer_hinweis":
        (
            "Die Resonanz wurde "
            "für jede Teilgruppe "
            "(alle / Lehrkraft 1 / "
            "nicht Lehrkraft 1) "
            "separat berechnet."
        ),

    "interpretation":
        (
            "Hohe Selbstähnlichkeit "
            "aufeinanderfolgender "
            "Zustände deutet auf "
            "Resonanzbildung "
            "im FRZK-Raum hin."
        ),

    "daten": {

        "alle_lehrkraefte":
            analyze_subset(
                df,
                "alle_lehrkraefte"
            ),

        "lehrkraft_1":
            analyze_subset(
                df[
                    df["lehrkraft_id"] == 1
                ],
                "lehrkraft_1"
            ),

        "nicht_lehrkraft_1":
            analyze_subset(
                df[
                    df["lehrkraft_id"] != 1
                ],
                "nicht_lehrkraft_1"
            )
    }
}

OUTPUT.write_text(

    json.dumps(
        out,
        ensure_ascii=False,
        indent=2
    ),

    encoding="utf-8"
)

print()
print("JSON erfolgreich erzeugt:")
print(OUTPUT.resolve())