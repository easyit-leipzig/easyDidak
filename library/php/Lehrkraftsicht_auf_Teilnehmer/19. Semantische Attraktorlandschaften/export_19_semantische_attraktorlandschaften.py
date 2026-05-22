# -*- coding: utf-8 -*-
"""
Export 19: Semantische Attraktorlandschaften
Erzeugt eine JSON-Datei mit drei Auswertungssichten:
1) alle Lehrkräfte
2) nur lehrkraft_id = 1
3) alle außer lehrkraft_id = 1

Datenbasis: sql_semantische_dichte_lehrer_type_1, ersatzweise analyze_lehrkraftdaten.
"""

import json
from datetime import date, datetime
from pathlib import Path
from typing import Any, Dict, List

import mysql.connector

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

OUTFILE = Path("auswertung_19_semantische_attraktorlandschaften.json")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

BASE_SELECT = """
SELECT
    id,
    ue_id,
    id_mtr_rueckkopplung_datenmaske,
    mtr_rueckkopplung_datenmaske_values_id,
    teilnehmer_id,
    gruppe_id,
    lehrkraft_id,
    fach,
    datum,
    COALESCE(dat_ges, CONCAT(datum, ' 00:00:00')) AS dat_ges,
    thema,
    bemerkung,
    x_kognition, x_sozial, x_affektiv, x_motivation,
    x_methodik, x_performanz, x_regulation,
    sum_kognition, sum_sozial, sum_affektiv, sum_motivation,
    sum_methodik, sum_performanz, sum_regulation,
    token_anzahl,
    funktionsklassen_anzahl_gesamt,
    dominante_dimension,
    dominante_dimension_wert,
    polaritaet_gesamt,
    d_semantisch,
    created_at
FROM {view_name}
WHERE {where_clause}
ORDER BY lehrkraft_id, gruppe_id, teilnehmer_id, dat_ges, ue_id, id
"""

SCOPES = {
    "alle_lehrkraefte": "1=1",
    "lehrkraft_1": "lehrkraft_id = 1",
    "ohne_lehrkraft_1": "lehrkraft_id <> 1",
}


def json_default(obj: Any) -> str:
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def find_view(cursor) -> str:
    """Bevorzugt den zeitlich geordneten View; fällt auf analyze_lehrkraftdaten zurück."""
    candidates = ["sql_semantische_dichte_lehrer_type_1", "analyze_lehrkraftdaten"]
    cursor.execute("SHOW FULL TABLES")
    rows = cursor.fetchall()
    existing = {str(list(r.values())[0]) for r in rows}
    for candidate in candidates:
        if candidate in existing:
            return candidate
    raise RuntimeError("Kein geeigneter View gefunden: sql_semantische_dichte_lehrer_type_1 oder analyze_lehrkraftdaten fehlt.")


def fetch_scope(cursor, view_name: str, where_clause: str) -> List[Dict[str, Any]]:
    sql = BASE_SELECT.format(view_name=view_name, where_clause=where_clause)
    try:
        cursor.execute(sql)
    except mysql.connector.Error:
        # analyze_lehrkraftdaten besitzt je nach Dump kein dat_ges-Feld.
        sql = sql.replace("COALESCE(dat_ges, CONCAT(datum, ' 00:00:00')) AS dat_ges,", "CONCAT(datum, ' 00:00:00') AS dat_ges,")
        cursor.execute(sql)
    return list(cursor.fetchall())


def build_payload() -> Dict[str, Any]:
    conn = get_connection()
    try:
        cursor = conn.cursor(dictionary=True)
        view_name = find_view(cursor)
        scopes = {}
        for scope_name, where_clause in SCOPES.items():
            rows = fetch_scope(cursor, view_name, where_clause)
            scopes[scope_name] = {
                "beschreibung": {
                    "alle_lehrkraefte": "Alle Datensätze der Lehrkraftsicht",
                    "lehrkraft_1": "Nur Datensätze mit lehrkraft_id = 1",
                    "ohne_lehrkraft_1": "Alle Datensätze außer lehrkraft_id = 1",
                }[scope_name],
                "where_clause": where_clause,
                "n": len(rows),
                "records": rows,
            }
        return {
            "auswertung": "19_semantische_attraktorlandschaften",
            "beschreibung": "Zustandslandschaften aus FRZK-Lehrkraftsicht: Attraktorbecken, Übergangswahrscheinlichkeiten, metastabile Räume und Kipppunktzonen.",
            "quelle": view_name,
            "dimensionen": DIMENSIONS,
            "created_at": datetime.now().isoformat(timespec="seconds"),
            "scopes": scopes,
        }
    finally:
        conn.close()


def main() -> None:
    payload = build_payload()
    OUTFILE.write_text(json.dumps(payload, ensure_ascii=False, indent=2, default=json_default), encoding="utf-8")
    print(f"JSON exportiert: {OUTFILE.resolve()}")
    for name, scope in payload["scopes"].items():
        print(f"- {name}: {scope['n']} Datensätze")


if __name__ == "__main__":
    main()
