# export_04_operatorwirkung_v2.py

import json
import math
from pathlib import Path

import mysql.connector
import pandas as pd


OUTFILE = Path("auswertung_04_operatorwirkung.json")

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

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

SCOPES = {
    "alle_lehrkraefte": None,
    "lehrkraft_1": "lehrkraft_id == 1",
    "alle_ausser_lehrkraft_1": "lehrkraft_id != 1",
}


def safe_float(value):
    if value is None or pd.isna(value):
        return None
    return float(value)


def safe_int(value):
    if value is None or pd.isna(value):
        return None
    return int(value)


def load_data():
    conn = mysql.connector.connect(**DB_CONFIG)

    sql = """
    SELECT
        id,
        id_mtr_rueckkopplung_datenmaske,
        mtr_rueckkopplung_datenmaske_values_id,
        datum,
        lehrkraft_id,
        gruppe_id,
        teilnehmer_id,

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
        d_semantisch,

        operator_count,
        modulator_count,
        has_operator,
        operator_names,
        modulator_names
    FROM analyze_lehrkraftdaten
    WHERE d_semantisch IS NOT NULL
    ORDER BY lehrkraft_id, datum, id;
    """

    df = pd.read_sql(sql, conn)
    conn.close()

    df["datum"] = pd.to_datetime(df["datum"], errors="coerce")
    df = df.dropna(subset=["datum"])

    for col in DIMENSIONS + [
        "d_semantisch",
        "dominante_dimension_wert",
        "polaritaet_gesamt",
        "operator_count",
        "modulator_count",
        "has_operator",
    ]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")

    df["operator_count"] = df["operator_count"].fillna(0).astype(int)
    df["modulator_count"] = df["modulator_count"].fillna(0).astype(int)
    df["has_operator"] = df["has_operator"].fillna(0).astype(int)

    return df


def add_delta_columns(df):
    df = df.sort_values(["lehrkraft_id", "datum", "id"]).copy()

    df["delta_d_semantisch"] = df.groupby("lehrkraft_id")["d_semantisch"].diff()

    for dim in DIMENSIONS:
        df[f"delta_{dim}"] = df.groupby("lehrkraft_id")[dim].diff()

    delta_cols = [f"delta_{dim}" for dim in DIMENSIONS]

    df["delta_vector_norm"] = df[delta_cols].apply(
        lambda r: math.sqrt(
            sum((v if pd.notna(v) else 0.0) ** 2 for v in r)
        ),
        axis=1
    )

    df["polaritaet_shift"] = (
        df.groupby("lehrkraft_id")["polaritaet_gesamt"]
        .diff()
        .fillna(0)
        .abs()
    )

    df["dominanz_shift"] = (
        df.groupby("lehrkraft_id")["dominante_dimension"]
        .apply(lambda s: s.ne(s.shift()).astype(int))
        .reset_index(level=0, drop=True)
    )

    df["dominanz_shift"] = df["dominanz_shift"].fillna(0)

    return df


def aggregate_scope(df_scope):
    result = {
        "n": int(len(df_scope)),
        "operatorhaltige_saetze": int(df_scope["has_operator"].sum()),
        "operatoren_gesamt": int(df_scope["operator_count"].sum()),
        "modulatoren_gesamt": int(df_scope["modulator_count"].sum()),
        "anteil_operatorhaltig": safe_float(df_scope["has_operator"].mean()),
        "mean_operator_count": safe_float(df_scope["operator_count"].mean()),
        "mean_modulator_count": safe_float(df_scope["modulator_count"].mean()),
        "polaritaet": [],
        "operatorvergleich": [],
        "zeitreihe": [],
        "dominante_dimensionen": [],
        "operatornamen": [],
        "modulatornamen": [],
        "rohwerte": [],
    }

    if df_scope.empty:
        return result

    by_pol = df_scope.groupby("polaritaet_gesamt", dropna=False).agg(
        n=("d_semantisch", "count"),
        mean_dichte=("d_semantisch", "mean"),
        std_dichte=("d_semantisch", "std"),
        var_dichte=("d_semantisch", "var"),
        mean_delta_dichte=("delta_d_semantisch", "mean"),
        mean_delta_vector=("delta_vector_norm", "mean"),
        mean_polaritaet_shift=("polaritaet_shift", "mean"),
        mean_dominanz_shift=("dominanz_shift", "mean"),
        operator_anteil=("has_operator", "mean"),
        operatoren_gesamt=("operator_count", "sum"),
        modulatoren_gesamt=("modulator_count", "sum"),
    ).reset_index()

    result["polaritaet"] = by_pol.where(pd.notna(by_pol), None).to_dict(orient="records")

    by_op = df_scope.groupby("has_operator", dropna=False).agg(
        n=("d_semantisch", "count"),
        mean_dichte=("d_semantisch", "mean"),
        std_dichte=("d_semantisch", "std"),
        var_dichte=("d_semantisch", "var"),
        mean_delta_dichte=("delta_d_semantisch", "mean"),
        mean_delta_vector=("delta_vector_norm", "mean"),
        mean_polaritaet_shift=("polaritaet_shift", "mean"),
        mean_dominanz_shift=("dominanz_shift", "mean"),
        mean_operator_count=("operator_count", "mean"),
        mean_modulator_count=("modulator_count", "mean"),
        operatoren_gesamt=("operator_count", "sum"),
        modulatoren_gesamt=("modulator_count", "sum"),
    ).reset_index()

    result["operatorvergleich"] = by_op.where(pd.notna(by_op), None).to_dict(orient="records")

    weekly = df_scope.set_index("datum").resample("W").agg(
        n=("d_semantisch", "count"),
        mean_dichte=("d_semantisch", "mean"),
        std_dichte=("d_semantisch", "std"),
        var_dichte=("d_semantisch", "var"),
        mean_delta_vector=("delta_vector_norm", "mean"),
        mean_polaritaet_shift=("polaritaet_shift", "mean"),
        mean_dominanz_shift=("dominanz_shift", "mean"),
        operator_anteil=("has_operator", "mean"),
        operatoren_gesamt=("operator_count", "sum"),
        modulatoren_gesamt=("modulator_count", "sum"),
    ).reset_index()

    weekly["datum"] = weekly["datum"].dt.strftime("%Y-%m-%d")
    result["zeitreihe"] = weekly.where(pd.notna(weekly), None).to_dict(orient="records")

    by_dim = df_scope.groupby("dominante_dimension", dropna=False).agg(
        n=("d_semantisch", "count"),
        mean_dichte=("d_semantisch", "mean"),
        mean_delta_vector=("delta_vector_norm", "mean"),
        operator_anteil=("has_operator", "mean"),
        operatoren_gesamt=("operator_count", "sum"),
        modulatoren_gesamt=("modulator_count", "sum"),
    ).reset_index()

    result["dominante_dimensionen"] = by_dim.where(pd.notna(by_dim), None).to_dict(orient="records")

    operator_names = collect_names(df_scope, "operator_names")
    modulator_names = collect_names(df_scope, "modulator_names")

    result["operatornamen"] = operator_names
    result["modulatornamen"] = modulator_names

    export_cols = [
        "id",
        "id_mtr_rueckkopplung_datenmaske",
        "mtr_rueckkopplung_datenmaske_values_id",
        "datum",
        "lehrkraft_id",
        "gruppe_id",
        "teilnehmer_id",
        "polaritaet_gesamt",
        "d_semantisch",
        "delta_d_semantisch",
        "delta_vector_norm",
        "polaritaet_shift",
        "dominanz_shift",
        "dominante_dimension",
        "dominante_dimension_wert",
        "operator_count",
        "modulator_count",
        "has_operator",
        "operator_names",
        "modulator_names",
        *DIMENSIONS,
    ]

    raw = df_scope[export_cols].copy()
    raw["datum"] = raw["datum"].dt.strftime("%Y-%m-%d")
    result["rohwerte"] = raw.where(pd.notna(raw), None).to_dict(orient="records")

    return result


def collect_names(df, column):
    counter = {}

    if column not in df.columns:
        return []

    for value in df[column].dropna():
        if not isinstance(value, str):
            continue

        parts = [p.strip() for p in value.split(",") if p.strip()]

        for part in parts:
            counter[part] = counter.get(part, 0) + 1

    rows = [
        {"name": name, "n": count}
        for name, count in sorted(counter.items(), key=lambda x: x[1], reverse=True)
    ]

    return rows


def main():
    df = load_data()
    df = add_delta_columns(df)

    export = {
        "auswertungspunkt": "04_operatorwirkung",
        "datenquelle": "analyze_lehrkraftdaten",
        "hinweis": "Operator- und Modulatordaten werden direkt aus der durch PHP erzeugten Tabellenstruktur gelesen.",
        "frzk_vorhersage": "Operatoren verändern den gesamten semantischen Folgeraum.",
        "messgroessen": [
            "Operatoranteil",
            "Operatoranzahl",
            "Modulatoranzahl",
            "semantische Dichte",
            "Varianz der semantischen Dichte",
            "Delta-Bewegung",
            "Polaritätsverschiebung",
            "Dominanzwechsel",
        ],
        "gesamt": {
            "n": int(len(df)),
            "operatorhaltige_saetze": int(df["has_operator"].sum()),
            "operatoren_gesamt": int(df["operator_count"].sum()),
            "modulatoren_gesamt": int(df["modulator_count"].sum()),
            "anteil_operatorhaltig": safe_float(df["has_operator"].mean()),
        },
        "scopes": {},
    }

    for scope_name, query in SCOPES.items():
        if query is None:
            df_scope = df.copy()
        else:
            df_scope = df.query(query).copy()

        export["scopes"][scope_name] = aggregate_scope(df_scope)

    OUTFILE.write_text(
        json.dumps(export, ensure_ascii=False, indent=2),
        encoding="utf-8"
    )

    print(f"JSON erzeugt: {OUTFILE.resolve()}")
    print(f"Datensätze: {len(df)}")
    print(f"Operatorhaltige Sätze: {int(df['has_operator'].sum())}")
    print(f"Operatoren gesamt: {int(df['operator_count'].sum())}")
    print(f"Modulatoren gesamt: {int(df['modulator_count'].sum())}")


if __name__ == "__main__":
    main()