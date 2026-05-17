import json
from pathlib import Path

import mysql.connector
import pandas as pd

print("START Skript")

OUTPUT = Path("frzk_resonanz_lehrkraftdaten.json")

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

try:
    print("Verbinde mit MySQL...")

    conn = mysql.connector.connect(**DB_CONFIG)

    print("Verbindung OK")

    sql = """
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
        mean_regulation AS x_regulation
    FROM analyze_lehrkraftdaten
    WHERE datum IS NOT NULL
      AND teilnehmer_id IS NOT NULL
    ORDER BY teilnehmer_id, datum, id_mtr_rueckkopplung_datenmaske;
    """

    print("Führe SQL aus...")

    df = pd.read_sql(sql, conn)

    print("SQL ausgeführt")
    print("Datensätze:", len(df))
    print("Spalten:", list(df.columns))

    conn.close()
    print("Verbindung geschlossen")

    if df.empty:
        print("FEHLER: Query liefert 0 Datensätze.")
        exit()

    df["datum"] = pd.to_datetime(df["datum"])

    print("Erste Zeilen:")
    print(df.head())

    out = {
        "analyse": "2. Resonanzvorhersage des FRZK",
        "basis": "analyze_lehrkraftdaten",
        "n_datensaetze": int(len(df)),
        "n_teilnehmer": int(df["teilnehmer_id"].nunique()),
        "n_lehrkraefte": int(df["lehrkraft_id"].nunique()),
        "datum_von": str(df["datum"].min().date()),
        "datum_bis": str(df["datum"].max().date()),
    }

    OUTPUT.write_text(
        json.dumps(out, ensure_ascii=False, indent=2),
        encoding="utf-8"
    )

    print("JSON erzeugt:", OUTPUT.resolve())

except Exception as e:
    print("FEHLER AUFGETRETEN:")
    print(type(e).__name__)
    print(e)