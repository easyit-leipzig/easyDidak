import json
import mysql.connector
from pathlib import Path
from datetime import date, datetime

OUT = Path("auswertung_03_nichtlinearitaet.json")

DB = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "connection_timeout": 5,
    "use_pure": True
}

BASE_SQL = """
SELECT
    lehrkraft_id,
    token_anzahl,
    d_semantisch,
    dominante_dimension,
    polaritaet_gesamt,
    datum,
    id_mtr_rueckkopplung_datenmaske,
    mtr_rueckkopplung_datenmaske_values_id
FROM sql_semantische_dichte_lehrer_type_1
WHERE token_anzahl IS NOT NULL
  AND d_semantisch IS NOT NULL
  AND token_anzahl > 0
  {where_extra}
ORDER BY token_anzahl, lehrkraft_id, datum
"""

SCOPES = {
    "alle_lehrkraefte": "",
    "lehrkraft_1": "AND lehrkraft_id = 1",
    "ohne_lehrkraft_1": "AND lehrkraft_id <> 1"
}

def convert(v):
    if isinstance(v, (date, datetime)):
        return v.isoformat()
    return v

def fetch_scope(cursor, where_extra):
    sql = BASE_SQL.format(where_extra=where_extra)
    cursor.execute(sql)
    rows = cursor.fetchall()
    return [{k: convert(v) for k, v in row.items()} for row in rows]

def main():
    cnx = mysql.connector.connect(**DB)
    cur = cnx.cursor(dictionary=True)

    data = {
        "auswertungspunkt": "3. Nichtlinearitätsnachweis",
        "frzk_vorhersage": "Bedeutung akkumuliert nicht linear.",
        "nachweislogik": "Gleiche Tokenanzahl erzeugt unterschiedliche semantische Dichten.",
        "quelle_view": "sql_semantische_dichte_lehrer_type_1",
        "scopes": {}
    }

    for scope, where_extra in SCOPES.items():
        data["scopes"][scope] = fetch_scope(cur, where_extra)

    cur.close()
    cnx.close()

    OUT.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"JSON erzeugt: {OUT.resolve()}")

if __name__ == "__main__":
    main()