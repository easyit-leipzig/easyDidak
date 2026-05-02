import json
from pathlib import Path

import numpy as np
import pandas as pd
from sqlalchemy import create_engine


# ------------------------------------------------------------
# Konfiguration
# ------------------------------------------------------------
DB_USER = "root"
DB_PASS = ""
DB_HOST = "localhost"
DB_NAME = "icas"

FILTER_DATE_FROM = "2025-09-01"
FILTER_FAECHER = ["MAT", "PHY"]

OUTPUT_JSON = "sem_dichte_auswertung.json"

DIMS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


# ------------------------------------------------------------
# Hilfsfunktionen
# ------------------------------------------------------------
def to_builtin(value):
    """Konvertiert numpy/pandas-Typen in JSON-kompatible Python-Typen."""
    if pd.isna(value):
        return None
    if isinstance(value, (np.integer,)):
        return int(value)
    if isinstance(value, (np.floating,)):
        return float(value)
    if isinstance(value, (np.bool_,)):
        return bool(value)
    return value


def series_to_dict(series: pd.Series) -> dict:
    return {str(k): to_builtin(v) for k, v in series.items()}


def dataframe_records(df: pd.DataFrame) -> list:
    records = df.to_dict(orient="records")
    clean = []
    for row in records:
        clean.append({str(k): to_builtin(v) for k, v in row.items()})
    return clean


# ------------------------------------------------------------
# DB-Verbindung
# ------------------------------------------------------------
engine = create_engine(
    f"mysql+pymysql://{DB_USER}:{DB_PASS}@{DB_HOST}/{DB_NAME}?charset=utf8mb4"
)

sql = f"""
SELECT
    id,
    gruppe_id,
    teilnehmer_id,
    fach,
    datum,
    thema,
    bemerkung,
    wochentag,
    day_number,
    lehrkraft_id,
    id_mtr_rueckkopplung_datenmaske,
    type,
    x_kognition,
    x_sozial,
    x_affektiv,
    x_motivation,
    x_methodik,
    x_performanz,
    x_regulation,
    dominante_dimension,
    dominante_dimension_wert,
    polaritaet_gesamt,
    d_semantisch
FROM datenm_values_sem_dichte_lehrer_type_3
WHERE datum >= '{FILTER_DATE_FROM}'
  and lehrkraft_id=1 and weekofyear(datum)<>44 and fach IN ({",".join([repr(x) for x in FILTER_FAECHER])})
"""

df = pd.read_sql(sql, engine)
df["datum"] = pd.to_datetime(df["datum"])

if df.empty:
    raise ValueError("Die Abfrage hat keine Daten geliefert.")

# ------------------------------------------------------------
# 1. Mittelwertoperator
# ------------------------------------------------------------
mean_vector = df[DIMS].mean()

# ------------------------------------------------------------
# 2. Varianzoperator je Dimension
# ------------------------------------------------------------
var_vector = df[DIMS].var(ddof=0)
std_vector = df[DIMS].std(ddof=0)

# ------------------------------------------------------------
# 3. Zustandsvarianz relativ zum Schwerpunkt
#    Var(S) = (1/n) * Sum ||Si - S_mean||^2
# ------------------------------------------------------------
centered = df[DIMS] - mean_vector
sq_dist = (centered ** 2).sum(axis=1)
dist = np.sqrt(sq_dist)

var_state_total = float(sq_dist.mean())
mean_dist_to_centroid = float(dist.mean())

df["sq_dist_to_centroid"] = sq_dist
df["dist_to_centroid"] = dist

# ------------------------------------------------------------
# 4. Gruppenauswertung
# ------------------------------------------------------------
group_mean = df.groupby("gruppe_id")[DIMS].mean().reset_index()
group_var = df.groupby("gruppe_id")[DIMS].var(ddof=0).reset_index()
group_n = df.groupby("gruppe_id").size().reset_index(name="n")

group_stats = group_mean.merge(group_var, on="gruppe_id", suffixes=("_mean", "_var"))
group_stats = group_stats.merge(group_n, on="gruppe_id")

# ------------------------------------------------------------
# 5. Lehrkraftauswertung
# ------------------------------------------------------------
teacher_mean = df.groupby("lehrkraft_id")[DIMS].mean().reset_index()
teacher_var = df.groupby("lehrkraft_id")[DIMS].var(ddof=0).reset_index()
teacher_n = df.groupby("lehrkraft_id").size().reset_index(name="n")

teacher_stats = teacher_mean.merge(teacher_var, on="lehrkraft_id", suffixes=("_mean", "_var"))
teacher_stats = teacher_stats.merge(teacher_n, on="lehrkraft_id")

# ------------------------------------------------------------
# 6. Zeitreihe pro Kalenderwoche
# ------------------------------------------------------------
iso = df["datum"].dt.isocalendar()
df["jahr"] = iso.year.astype(int)
df["kw"] = iso.week.astype(int)

weekly_mean = df.groupby(["jahr", "kw"])[DIMS + ["d_semantisch"]].mean().reset_index()
weekly_var = df.groupby(["jahr", "kw"])[DIMS + ["d_semantisch"]].var(ddof=0).reset_index()
weekly_n = df.groupby(["jahr", "kw"]).size().reset_index(name="n")

weekly_stats = weekly_mean.merge(
    weekly_var, on=["jahr", "kw"], suffixes=("_mean", "_var")
).merge(
    weekly_n, on=["jahr", "kw"]
)

# ------------------------------------------------------------
# 7. Dominanz- und Polaritätsverteilung
# ------------------------------------------------------------
dominance_stats = (
    df.groupby("dominante_dimension")
    .agg(
        n=("dominante_dimension", "size"),
        mean_dom_value=("dominante_dimension_wert", "mean"),
        mean_d_semantisch=("d_semantisch", "mean"),
    )
    .reset_index()
    .sort_values("n", ascending=False)
)

polarity_stats = (
    df.groupby("polaritaet_gesamt")
    .agg(
        n=("polaritaet_gesamt", "size"),
        mean_d_semantisch=("d_semantisch", "mean"),
    )
    .reset_index()
    .sort_values("polaritaet_gesamt")
)

# ------------------------------------------------------------
# 8. Rohdaten leicht reduziert für JSON
# ------------------------------------------------------------
raw_export = df[
    [
        "id",
        "gruppe_id",
        "teilnehmer_id",
        "fach",
        "datum",
        "lehrkraft_id",
        "dominante_dimension",
        "dominante_dimension_wert",
        "polaritaet_gesamt",
        "d_semantisch",
        "sq_dist_to_centroid",
        "dist_to_centroid",
        *DIMS,
    ]
].copy()

raw_export["datum"] = raw_export["datum"].dt.strftime("%Y-%m-%d")

# ------------------------------------------------------------
# 9. JSON-Payload
# ------------------------------------------------------------
payload = {
    "meta": {
        "quelle": "datenm_values_sem_dichte_lehrer_type_3",
        "filter_date_from": FILTER_DATE_FROM,
        "filter_faecher": FILTER_FAECHER,
        "n_datensaetze": int(len(df)),
        "dimensionen": DIMS,
    },
    "mittelwertoperator": series_to_dict(mean_vector),
    "varianzoperator": series_to_dict(var_vector),
    "standardabweichung": series_to_dict(std_vector),
    "zustandsraum": {
        "var_state_total": var_state_total,
        "mean_dist_to_centroid": mean_dist_to_centroid,
    },
    "gruppenstatistik": dataframe_records(group_stats),
    "lehrkraftstatistik": dataframe_records(teacher_stats),
    "wochenstatistik": dataframe_records(weekly_stats),
    "dominanzverteilung": dataframe_records(dominance_stats),
    "polaritaetsverteilung": dataframe_records(polarity_stats),
    "rohdaten": dataframe_records(raw_export),
}

# ------------------------------------------------------------
# 10. JSON speichern
# ------------------------------------------------------------
output_path = Path(OUTPUT_JSON)
with output_path.open("w", encoding="utf-8") as f:
    json.dump(payload, f, ensure_ascii=False, indent=2)

print(f"JSON-Auswertung gespeichert: {output_path.resolve()}")