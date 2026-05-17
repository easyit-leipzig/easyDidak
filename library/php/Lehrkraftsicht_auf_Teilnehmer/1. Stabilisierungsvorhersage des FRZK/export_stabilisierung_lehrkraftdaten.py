# export_stabilisierung_lehrkraftdaten.py

import json
import traceback
from pathlib import Path

import numpy as np
import pandas as pd
import mysql.connector

OUTPUT = Path.cwd() / "frzk_stabilisierung_lehrkraftdaten.json"
BASE_TABLE = "analyze_lehrkraftdaten"

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "connection_timeout": 5,
    "use_pure": True
}

MEAN_DIMS = [
    "mean_kognition",
    "mean_sozial",
    "mean_affektiv",
    "mean_motivation",
    "mean_methodik",
    "mean_performanz",
    "mean_regulation"
]

VAR_DIMS = [
    "var_kognition",
    "var_sozial",
    "var_affektiv",
    "var_motivation",
    "var_methodik",
    "var_performanz",
    "var_regulation"
]

RANGE_DIMS = [
    "range_kognition",
    "range_sozial",
    "range_affektiv",
    "range_motivation",
    "range_methodik",
    "range_performanz",
    "range_regulation"
]


def safe_float(v):
    if v is None or pd.isna(v):
        return None
    return float(v)


def analyze_subset(df, label):
    result = {
        "gruppe": label,
        "n_unterrichtseinheiten": int(len(df)),
        "n_teilnehmer": int(df["teilnehmer_id"].nunique()) if len(df) else 0,
        "n_lehrkraefte": int(df["lehrkraft_id"].nunique()) if len(df) else 0,
        "zeitraum": {
            "von": str(df["datum"].min().date()) if len(df) else None,
            "bis": str(df["datum"].max().date()) if len(df) else None
        }
    }

    teilnehmer_liste = []

    for tid, g in df.groupby("teilnehmer_id"):
        g = g.sort_values(["datum", "id_mtr_rueckkopplung_datenmaske"]).copy()

        delta_values = g["delta_vektor"].dropna()

        mean_varianz_summe = safe_float(g["varianz_summe"].mean())
        mean_range_summe = safe_float(g["range_summe"].mean())
        mean_sem_breite = safe_float(g["semantische_breite"].mean())
        std_sem_breite = safe_float(g["semantische_breite"].std(ddof=0))
        mean_delta = safe_float(delta_values.mean())
        std_delta = safe_float(delta_values.std(ddof=0))
        dichte_std = safe_float(g["d_semantisch_mean"].std(ddof=0))
        polaritaet_std = safe_float(g["polaritaet_index"].std(ddof=0))
        dominanz_std = safe_float(g["dominanz_breite"].std(ddof=0))

        stabilisierungsindex = 1 / (
            1
            + (mean_sem_breite or 0)
            + (std_sem_breite or 0)
            + (mean_delta or 0)
            + (dichte_std or 0)
            + (polaritaet_std or 0)
            + (dominanz_std or 0)
        )

        teilnehmer_liste.append({
            "teilnehmer_id": int(tid),
            "n_unterrichtseinheiten": int(len(g)),
            "datum_von": str(g["datum"].min().date()),
            "datum_bis": str(g["datum"].max().date()),

            "satzanzahl_summe": int(g["satzanzahl"].sum()),
            "satzanzahl_mean": safe_float(g["satzanzahl"].mean()),

            "semantische_breite_mean": mean_sem_breite,
            "semantische_breite_std": std_sem_breite,

            "varianz_summe_mean": mean_varianz_summe,
            "range_summe_mean": mean_range_summe,

            "d_semantisch_mean": safe_float(g["d_semantisch_mean"].mean()),
            "d_semantisch_std": dichte_std,

            "delta_vektor_mean": mean_delta,
            "delta_vektor_std": std_delta,

            "polaritaet_index_mean": safe_float(g["polaritaet_index"].mean()),
            "polaritaet_index_std": polaritaet_std,

            "dominanz_breite_mean": safe_float(g["dominanz_breite"].mean()),
            "dominanz_breite_std": dominanz_std,

            "stabilisierungsindex": safe_float(stabilisierungsindex)
        })

    tdf = pd.DataFrame(teilnehmer_liste)

    result["teilnehmer"] = teilnehmer_liste

    result["gesamt"] = {
        "semantische_breite_mean": safe_float(df["semantische_breite"].mean()) if len(df) else None,
        "semantische_breite_std": safe_float(df["semantische_breite"].std(ddof=0)) if len(df) else None,
        "delta_vektor_mean": safe_float(df["delta_vektor"].dropna().mean()) if len(df) else None,
        "delta_vektor_std": safe_float(df["delta_vektor"].dropna().std(ddof=0)) if len(df) else None,
        "d_semantisch_mean": safe_float(df["d_semantisch_mean"].mean()) if len(df) else None,
        "d_semantisch_std": safe_float(df["d_semantisch_mean"].std(ddof=0)) if len(df) else None,
        "polaritaet_index_mean": safe_float(df["polaritaet_index"].mean()) if len(df) else None,
        "polaritaet_index_std": safe_float(df["polaritaet_index"].std(ddof=0)) if len(df) else None,
        "dominanz_breite_mean": safe_float(df["dominanz_breite"].mean()) if len(df) else None,
        "mittlerer_stabilisierungsindex": safe_float(tdf["stabilisierungsindex"].mean()) if not tdf.empty else None,
        "min_stabilisierungsindex": safe_float(tdf["stabilisierungsindex"].min()) if not tdf.empty else None,
        "max_stabilisierungsindex": safe_float(tdf["stabilisierungsindex"].max()) if not tdf.empty else None
    }

    return result


try:
    print("============================================================")
    print("START: 1. Stabilisierungsvorhersage des FRZK")
    print("============================================================")

    conn = mysql.connector.connect(**DB_CONFIG)

    sql = f"""
    SELECT
        id_mtr_rueckkopplung_datenmaske,
        datum,
        teilnehmer_id,
        lehrkraft_id,
        gruppe_id,
        satzanzahl,

        mean_kognition,
        mean_sozial,
        mean_affektiv,
        mean_motivation,
        mean_methodik,
        mean_performanz,
        mean_regulation,

        var_kognition,
        var_sozial,
        var_affektiv,
        var_motivation,
        var_methodik,
        var_performanz,
        var_regulation,

        range_kognition,
        range_sozial,
        range_affektiv,
        range_motivation,
        range_methodik,
        range_performanz,
        range_regulation,

        semantische_breite,
        d_semantisch_mean,
        d_semantisch_std,
        polaritaet_index,
        dominanz_breite
    FROM {BASE_TABLE}
    WHERE datum IS NOT NULL
    ORDER BY teilnehmer_id, datum, id_mtr_rueckkopplung_datenmaske;
    """

    df = pd.read_sql(sql, conn)
    conn.close()

    print("Geladene UE-Datensätze:", len(df))

    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")

    numeric_cols = (
        ["id_mtr_rueckkopplung_datenmaske", "teilnehmer_id", "lehrkraft_id",
         "gruppe_id", "satzanzahl", "semantische_breite", "d_semantisch_mean",
         "d_semantisch_std", "polaritaet_index", "dominanz_breite"]
        + MEAN_DIMS
        + VAR_DIMS
        + RANGE_DIMS
    )

    for col in numeric_cols:
        df[col] = pd.to_numeric(df[col], errors="coerce")

    df = df.dropna(subset=[
        "id_mtr_rueckkopplung_datenmaske",
        "datum",
        "teilnehmer_id",
        "lehrkraft_id",
        "semantische_breite",
        "d_semantisch_mean",
        "polaritaet_index",
        "dominanz_breite"
    ] + MEAN_DIMS)

    print("UE-Datensätze nach Bereinigung:", len(df))

    if df.empty:
        raise ValueError("Keine auswertbaren Datensätze nach Bereinigung.")

    df = df.sort_values(
        ["teilnehmer_id", "datum", "id_mtr_rueckkopplung_datenmaske"]
    ).reset_index(drop=True)

    df["varianz_summe"] = df[VAR_DIMS].sum(axis=1)
    df["range_summe"] = df[RANGE_DIMS].sum(axis=1)

    df["delta_vektor"] = np.nan

    for tid, g in df.groupby("teilnehmer_id"):
        indices = g.index.to_list()
        values = g[MEAN_DIMS].to_numpy(dtype=float)

        deltas = [np.nan]

        for i in range(1, len(values)):
            delta = np.linalg.norm(values[i] - values[i - 1])
            deltas.append(float(delta))

        df.loc[indices, "delta_vektor"] = deltas

    out = {
        "analyse": "1. Stabilisierungsvorhersage des FRZK",
        "basis": BASE_TABLE,
        "auswertungsebene": "UE-aggregierter Zustandsraum der satzweisen Lehrkraftsicht",
        "interpretation": (
            "FRZK-konforme Stabilisierung liegt vor, wenn die Lehrkraftsicht auf "
            "Teilnehmende im Zeitverlauf geringere semantische Breite, geringere "
            "Dimensionsvarianz, geringere Vektordrift, stabilere Polarität und "
            "stabilere Dominanzbreite zeigt."
        ),
        "formel_stabilisierungsindex": (
            "SI = 1 / (1 + mean(semantische_breite) + std(semantische_breite) "
            "+ mean(delta_vektor) + std(d_semantisch_mean) "
            "+ std(polaritaet_index) + std(dominanz_breite))"
        ),
        "datenbasis": {
            "n_unterrichtseinheiten": int(len(df)),
            "n_teilnehmer": int(df["teilnehmer_id"].nunique()),
            "n_lehrkraefte": int(df["lehrkraft_id"].nunique()),
            "zeitraum_von": str(df["datum"].min().date()),
            "zeitraum_bis": str(df["datum"].max().date())
        },
        "daten": {
            "alle_lehrkraefte": analyze_subset(df, "alle_lehrkraefte"),
            "lehrkraft_1": analyze_subset(df[df["lehrkraft_id"] == 1], "lehrkraft_1"),
            "nicht_lehrkraft_1": analyze_subset(df[df["lehrkraft_id"] != 1], "nicht_lehrkraft_1")
        }
    }

    OUTPUT.write_text(
        json.dumps(out, ensure_ascii=False, indent=2),
        encoding="utf-8"
    )

    print("JSON geschrieben:", OUTPUT.resolve())
    print("Dateigröße:", OUTPUT.stat().st_size, "Bytes")
    print("FERTIG")

except Exception:
    print("FEHLER")
    traceback.print_exc()

input("Enter drücken zum Beenden ...")