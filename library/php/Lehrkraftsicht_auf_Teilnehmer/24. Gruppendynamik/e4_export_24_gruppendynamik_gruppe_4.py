#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Auswertungspunkt 24: Gruppendynamik – Exportskript

Erzeugt eine gemeinsame JSON-Datei mit drei Perspektiven:
1) alle Lehrkräfte
2) lehrkraft_id = 1 (Herr Thiele)
3) alle außer lehrkraft_id = 1

Besonderer Ereignisanker:
- Urlaub / Abwesenheit ab 2025-12-12
- Gruppe 4 als Fokusgruppe
- dokumentierter Konflikt-/Vertretungshinweis vom 2026-01-08

Voraussetzung:
    pip install mysql-connector-python
"""

from __future__ import annotations

import json
from datetime import date
from pathlib import Path
from typing import Any, Dict, List, Optional

import mysql.connector
from mysql.connector import Error

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

OUTPUT_FILE = Path("auswertung_24_gruppendynamik_export.json")
FOCUS_GROUP_ID = 4
FOCUS_TEACHER_ID = 1
ABSENCE_START = date(2025, 12, 12)
RETURN_ASSUMPTION = date(2026, 1, 12)
INCIDENT_DATE = date(2026, 1, 8)

INCIDENT_NOTE = (
    "Am 08.01.2026 wurde dokumentiert, dass Paula sich gemeinsam mit Lia Schubert "
    "und Carlotta Körber über Herrn Bellot beschwerte. Thematisch beschrieben wurden "
    "Handynutzung, ausgedruckte Aufgaben, fehlende Unterlagen, Unterrichtsstörungen, "
    "fehlender Respekt gegenüber der Vertretungslehrkraft und der Vergleich mit Herrn Thiele."
)

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation", "methodik", "performanz", "regulation"
]


def connect():
    return mysql.connector.connect(**DB_CONFIG)


def table_exists(cur, table_name: str) -> bool:
    cur.execute(
        """
        SELECT COUNT(*) AS n
        FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name = %s
        """,
        (table_name,),
    )
    row = cur.fetchone()
    return bool(row and row["n"])


def period_label(d: date) -> str:
    if d < ABSENCE_START:
        return "vor_abwesenheit"
    if ABSENCE_START <= d < RETURN_ASSUMPTION:
        return "abwesenheit_vertretung"
    return "nach_rueckkehr"


def serialize_row(row: Dict[str, Any]) -> Dict[str, Any]:
    out: Dict[str, Any] = {}
    for k, v in row.items():
        if hasattr(v, "isoformat"):
            out[k] = v.isoformat()
        else:
            out[k] = v
    if "datum" in row and row["datum"] is not None:
        out["zeitphase"] = period_label(row["datum"])
        out["ist_fokusgruppe_4"] = int(row.get("gruppe_id") == FOCUS_GROUP_ID)
        out["ist_lehrkraft_1_thiele"] = int(row.get("lehrkraft_id") == FOCUS_TEACHER_ID)
        out["tage_zum_ereignis_080126"] = (row["datum"] - INCIDENT_DATE).days
    return out


def fetch_teacher_rows(cur, category: str, where_extra: str = "", params: Optional[List[Any]] = None) -> List[Dict[str, Any]]:
    params = params or []
    # Bevorzugt wird die aggregierte Tabelle type_1, weil sie pro Unterrichtseinheit bereits Satzanzahl,
    # Streuung, Kohärenz- und Konfliktindex enthält. Falls sie nicht existiert, wird auf analyze_lehrkraftdaten
    # ausgewichen.
    if table_exists(cur, "frzk_semantische_dichte_lehrer_gesamt_type_1"):
        sql = f"""
            SELECT
                id,
                id_mtr_rueckkopplung_datenmaske,
                datum,
                lehrkraft_id,
                gruppe_id,
                satz_anzahl,
                token_anzahl_gesamt,
                funktionsklassen_anzahl_gesamt,
                x_kognition, x_sozial, x_affektiv, x_motivation, x_methodik, x_performanz, x_regulation,
                sum_kognition, sum_sozial, sum_affektiv, sum_motivation, sum_methodik, sum_performanz, sum_regulation,
                dominante_dimension,
                dominante_dimension_wert,
                dominante_dimension_anteil,
                polaritaet_gesamt,
                polaritaet_spannung,
                d_semantisch,
                d_semantisch_mean,
                d_semantisch_max,
                streuung_gesamt,
                kohärenz_index AS kohaerenz_index,
                konflikt_index
            FROM frzk_semantische_dichte_lehrer_gesamt_type_1
            WHERE datum IS NOT NULL {where_extra}
            ORDER BY datum, gruppe_id, lehrkraft_id, id
        """
    else:
        sql = f"""
            SELECT
                id,
                id_mtr_rueckkopplung_datenmaske,
                mtr_rueckkopplung_datenmaske_values_id,
                datum,
                lehrkraft_id,
                gruppe_id,
                teilnehmer_id,
                fach,
                thema,
                bemerkung,
                x_kognition, x_sozial, x_affektiv, x_motivation, x_methodik, x_performanz, x_regulation,
                sum_kognition, sum_sozial, sum_affektiv, sum_motivation, sum_methodik, sum_performanz, sum_regulation,
                token_anzahl,
                funktionsklassen_anzahl_gesamt,
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
            WHERE datum IS NOT NULL {where_extra}
            ORDER BY datum, gruppe_id, lehrkraft_id, id
        """
    cur.execute(sql, params)
    return [{"perspektive": category, **serialize_row(r)} for r in cur.fetchall()]


def fetch_group_emotion(cur) -> List[Dict[str, Any]]:
    if not table_exists(cur, "frzk_group_emotion"):
        return []
    cur.execute(
        """
        SELECT
            id, gruppe_id, zeitpunkt, z_affektiv,
            kohärenz AS kohaerenz,
            stabilitaet, dynamik, emotionaler_status, emotionaler_modus, bemerkung
        FROM frzk_group_emotion
        ORDER BY zeitpunkt, gruppe_id, id
        """
    )
    rows = []
    for r in cur.fetchall():
        out = serialize_row(r)
        # zeitpunkt kann datetime sein; für Phasenbildung Datum extrahieren
        z = r.get("zeitpunkt")
        if z is not None:
            d = z.date() if hasattr(z, "date") else date.fromisoformat(str(z)[:10])
            out["zeitphase"] = period_label(d)
            out["ist_fokusgruppe_4"] = int(r.get("gruppe_id") == FOCUS_GROUP_ID)
            out["tage_zum_ereignis_080126"] = (d - INCIDENT_DATE).days
        rows.append(out)
    return rows


def main() -> None:
    try:
        conn = connect()
        cur = conn.cursor(dictionary=True)

        payload = {
            "auswertungspunkt": 24,
            "titel": "Gruppendynamik im FRZK-Raum",
            "ereignisrahmen": {
                "fokusgruppe_id": FOCUS_GROUP_ID,
                "fokuslehrkraft_id": FOCUS_TEACHER_ID,
                "fokuslehrkraft_name": "Herr Thiele",
                "abwesenheit_ab": ABSENCE_START.isoformat(),
                "angenommene_rueckkehr_ab": RETURN_ASSUMPTION.isoformat(),
                "ereignisdatum": INCIDENT_DATE.isoformat(),
                "ereignisnotiz": INCIDENT_NOTE,
                "phasenlogik": {
                    "vor_abwesenheit": f"datum < {ABSENCE_START.isoformat()}",
                    "abwesenheit_vertretung": f"{ABSENCE_START.isoformat()} <= datum < {RETURN_ASSUMPTION.isoformat()}",
                    "nach_rueckkehr": f"datum >= {RETURN_ASSUMPTION.isoformat()}",
                },
            },
            "dimensionen": DIMENSIONS,
            "daten": {
                "alle_lehrkraefte": fetch_teacher_rows(cur, "alle_lehrkraefte"),
                "lehrkraft_1_thiele": fetch_teacher_rows(
                    cur, "lehrkraft_1_thiele", "AND lehrkraft_id = %s", [FOCUS_TEACHER_ID]
                ),
                "ohne_lehrkraft_1": fetch_teacher_rows(
                    cur, "ohne_lehrkraft_1", "AND lehrkraft_id <> %s", [FOCUS_TEACHER_ID]
                ),
                "gruppenemotion": fetch_group_emotion(cur),
            },
        }

        OUTPUT_FILE.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"Export abgeschlossen: {OUTPUT_FILE.resolve()}")
        print(f"Datensätze alle Lehrkräfte: {len(payload['daten']['alle_lehrkraefte'])}")
        print(f"Datensätze Lehrkraft 1: {len(payload['daten']['lehrkraft_1_thiele'])}")
        print(f"Datensätze ohne Lehrkraft 1: {len(payload['daten']['ohne_lehrkraft_1'])}")
        print(f"Gruppenemotion-Datensätze: {len(payload['daten']['gruppenemotion'])}")

    except Error as exc:
        raise SystemExit(f"MySQL-Fehler: {exc}") from exc
    finally:
        try:
            cur.close()
            conn.close()
        except Exception:
            pass


if __name__ == "__main__":
    main()
