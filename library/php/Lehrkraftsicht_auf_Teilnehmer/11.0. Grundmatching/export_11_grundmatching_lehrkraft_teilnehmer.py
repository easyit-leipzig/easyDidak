#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
11. Grundmatching Lehrkraftsicht ↔ Teilnehmersicht
Export-Skript: liest ICAS/MySQL, matched Lehrkraft- und Teilnehmervektoren
und schreibt alle drei Scopes in eine gemeinsame JSON-Datei:
  - alle_lehrkraefte
  - lehrkraft_1
  - ohne_lehrkraft_1

Matching-Logik:
  Lehrkraftsicht: frzk_semantische_dichte_lehrer f
                 JOIN mtr_rueckkopplung_datenmaske m
  Teilnehmersicht: frzk_semantische_dichte_teilnehmer_7d t
  Primärschlüssel: teilnehmer_id + gruppe_id + Datum(t.zeitpunkt)=m.datum
  Ergänzend: ue_id wird mitgeführt; da in den vorliegenden Daten ue_id häufig 0 ist,
             wird sie nicht hart als Ausschlussbedingung verwendet.
"""
from __future__ import annotations

import json
import math
from collections import defaultdict
from datetime import date, datetime
from pathlib import Path
from typing import Any, Dict, List, Tuple

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

OUTPUT_JSON = Path("auswertung_11_grundmatching_lehrkraft_teilnehmer.json")

DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation"
]


def to_float(value: Any, default: float = 0.0) -> float:
    if value is None:
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def json_default(obj: Any) -> str:
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def mean(values: List[float]) -> float:
    return sum(values) / len(values) if values else 0.0


def norm(vec: List[float]) -> float:
    return math.sqrt(sum(v * v for v in vec))


def dominant_dimension(vec: List[float]) -> Tuple[str | None, float]:
    if not vec:
        return None, 0.0
    idx = max(range(len(vec)), key=lambda i: abs(vec[i]))
    return DIMENSIONS[idx], vec[idx]


def polarity(vec: List[float]) -> int:
    s = sum(vec)
    return 1 if s > 0 else (-1 if s < 0 else 0)


def fetch_rows() -> List[Dict[str, Any]]:
    sql = """
    SELECT
        m.id AS lehrer_datenmaske_id,
        m.datum AS datum,
        m.gruppe_id AS gruppe_id,
        m.teilnehmer_id AS teilnehmer_id,
        m.fach AS fach,
        m.thema AS thema,
        m.lehrkraft_id AS lehrkraft_id,
        f.id AS lehrer_vector_id,
        f.ue_id AS lehrer_ue_id,
        f.mtr_rueckkopplung_datenmaske_values_id AS lehrer_values_id,
        f.x_kognition AS l_x_kognition,
        f.x_sozial AS l_x_sozial,
        f.x_affektiv AS l_x_affektiv,
        f.x_motivation AS l_x_motivation,
        f.x_methodik AS l_x_methodik,
        f.x_performanz AS l_x_performanz,
        f.x_regulation AS l_x_regulation,
        f.sum_kognition AS l_sum_kognition,
        f.sum_sozial AS l_sum_sozial,
        f.sum_affektiv AS l_sum_affektiv,
        f.sum_motivation AS l_sum_motivation,
        f.sum_methodik AS l_sum_methodik,
        f.sum_performanz AS l_sum_performanz,
        f.sum_regulation AS l_sum_regulation,
        f.token_anzahl AS l_token_anzahl,
        f.funktionsklassen_anzahl_gesamt AS l_funktionsklassen_anzahl_gesamt,
        f.dominante_dimension AS l_dominante_dimension,
        f.dominante_dimension_wert AS l_dominante_dimension_wert,
        f.polaritaet_gesamt AS l_polaritaet_gesamt,
        f.d_semantisch AS l_d_semantisch,
        t.id AS teilnehmer_vector_id,
        t.rueckkopplung_teilnehmer_id AS rueckkopplung_teilnehmer_id,
        t.ue_id AS teilnehmer_ue_id,
        t.ue_zuweisung_teilnehmer_id AS ue_zuweisung_teilnehmer_id,
        t.zeitpunkt AS teilnehmer_zeitpunkt,
        t.x_kognition AS t_x_kognition,
        t.x_sozial AS t_x_sozial,
        t.x_affektiv AS t_x_affektiv,
        t.x_motivation AS t_x_motivation,
        t.x_methodik AS t_x_methodik,
        t.x_performanz AS t_x_performanz,
        t.x_regulation AS t_x_regulation,
        t.sum_kognition AS t_sum_kognition,
        t.sum_sozial AS t_sum_sozial,
        t.sum_affektiv AS t_sum_affektiv,
        t.sum_motivation AS t_sum_motivation,
        t.sum_methodik AS t_sum_methodik,
        t.sum_performanz AS t_sum_performanz,
        t.sum_regulation AS t_sum_regulation,
        t.emotion_ids AS t_emotion_ids,
        t.emotion_valenz AS t_emotion_valenz,
        t.emotion_aktivierung AS t_emotion_aktivierung,
        t.emotion_anzahl AS t_emotion_anzahl,
        t.dominante_dimension AS t_dominante_dimension,
        t.dominante_dimension_wert AS t_dominante_dimension_wert,
        t.polaritaet_gesamt AS t_polaritaet_gesamt,
        t.d_semantisch AS t_d_semantisch
    FROM frzk_semantische_dichte_lehrer f
    INNER JOIN mtr_rueckkopplung_datenmaske m
        ON m.id = f.id_mtr_rueckkopplung_datenmaske
    INNER JOIN frzk_semantische_dichte_teilnehmer_7d t
        ON t.teilnehmer_id = m.teilnehmer_id
       AND t.gruppe_id = m.gruppe_id
       AND DATE(t.zeitpunkt) = m.datum
    ORDER BY m.datum, m.gruppe_id, m.teilnehmer_id, m.lehrkraft_id, t.id, f.id
    """
    cnx = mysql.connector.connect(**DB_CONFIG)
    try:
        cur = cnx.cursor(dictionary=True)
        cur.execute(sql)
        return list(cur.fetchall())
    finally:
        cnx.close()


def aggregate_matches(rows: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    grouped: Dict[Tuple[Any, ...], List[Dict[str, Any]]] = defaultdict(list)
    for r in rows:
        key = (
            r["lehrer_datenmaske_id"],
            r["teilnehmer_vector_id"],
            r["datum"],
            r["gruppe_id"],
            r["teilnehmer_id"],
            r["lehrkraft_id"],
        )
        grouped[key].append(r)

    records: List[Dict[str, Any]] = []
    for key, group in grouped.items():
        first = group[0]
        l_vec = [mean([to_float(r[f"l_x_{d}"]) for r in group]) for d in DIMENSIONS]
        l_sum_vec = [sum(to_float(r[f"l_sum_{d}"]) for r in group) for d in DIMENSIONS]
        t_vec = [to_float(first[f"t_x_{d}"]) for d in DIMENSIONS]
        t_sum_vec = [to_float(first[f"t_sum_{d}"]) for d in DIMENSIONS]
        l_dom, l_dom_val = dominant_dimension(l_vec)

        records.append({
            "match_id": f"M{len(records)+1:06d}",
            "datum": json_default(first["datum"]),
            "gruppe_id": first["gruppe_id"],
            "teilnehmer_id": first["teilnehmer_id"],
            "fach": first.get("fach"),
            "thema": first.get("thema"),
            "lehrkraft_id": first["lehrkraft_id"],
            "matching": {
                "modus": "teilnehmer_id + gruppe_id + DATE(teilnehmer.zeitpunkt) = lehrkraft.datum",
                "lehrer_datenmaske_id": first["lehrer_datenmaske_id"],
                "teilnehmer_vector_id": first["teilnehmer_vector_id"],
                "lehrer_ue_ids": sorted({r["lehrer_ue_id"] for r in group}),
                "teilnehmer_ue_id": first["teilnehmer_ue_id"],
                "teilnehmer_zeitpunkt": json_default(first["teilnehmer_zeitpunkt"]),
                "satzvektoren_lehrkraft_n": len(group),
            },
            "lehrkraft": {
                "vector_x": dict(zip(DIMENSIONS, l_vec)),
                "vector_sum": dict(zip(DIMENSIONS, l_sum_vec)),
                "d_semantisch": norm(l_vec),
                "d_semantisch_satzmittel": mean([to_float(r["l_d_semantisch"]) for r in group]),
                "token_anzahl": int(sum(to_float(r["l_token_anzahl"]) for r in group)),
                "funktionsklassen_anzahl_gesamt": int(sum(to_float(r["l_funktionsklassen_anzahl_gesamt"]) for r in group)),
                "dominante_dimension": l_dom,
                "dominante_dimension_wert": l_dom_val,
                "polaritaet_gesamt": polarity(l_vec),
                "satz_dominanzen": [r.get("l_dominante_dimension") for r in group],
            },
            "teilnehmer": {
                "vector_x": dict(zip(DIMENSIONS, t_vec)),
                "vector_sum": dict(zip(DIMENSIONS, t_sum_vec)),
                "d_semantisch": to_float(first["t_d_semantisch"]),
                "dominante_dimension": first.get("t_dominante_dimension"),
                "dominante_dimension_wert": to_float(first.get("t_dominante_dimension_wert")),
                "polaritaet_gesamt": first.get("t_polaritaet_gesamt"),
                "emotion_ids": first.get("t_emotion_ids"),
                "emotion_valenz": to_float(first.get("t_emotion_valenz"), None),
                "emotion_aktivierung": to_float(first.get("t_emotion_aktivierung"), None),
                "emotion_anzahl": int(to_float(first.get("t_emotion_anzahl"))),
            },
        })
    return records


def split_scopes(records: List[Dict[str, Any]]) -> Dict[str, List[Dict[str, Any]]]:
    return {
        "alle_lehrkraefte": records,
        "lehrkraft_1": [r for r in records if r["lehrkraft_id"] == 1],
        "ohne_lehrkraft_1": [r for r in records if r["lehrkraft_id"] != 1],
    }


def main() -> None:
    rows = fetch_rows()
    records = aggregate_matches(rows)
    data = {
        "auswertung": "11_grundmatching_lehrkraftsicht_teilnehmersicht",
        "beschreibung": "Matching und Export gemeinsamer FRZK-Zustandsvektoren L_t und T_t.",
        "dimensionen": DIMENSIONS,
        "db_config_used": {k: v for k, v in DB_CONFIG.items() if k != "password"},
        "matching_hinweis": "ue_id wird mitgeführt, aber nicht hart gefiltert, weil in den vorliegenden Tabellen ue_id häufig 0 ist.",
        "scopes": split_scopes(records),
        "metadaten": {
            "raw_join_rows": len(rows),
            "matched_records": len(records),
            "created_at": datetime.now().isoformat(timespec="seconds"),
        },
    }
    OUTPUT_JSON.write_text(json.dumps(data, ensure_ascii=False, indent=2, default=json_default), encoding="utf-8")
    print(f"Export abgeschlossen: {OUTPUT_JSON.resolve()}")
    print(f"Raw-Join-Zeilen: {len(rows)} | Match-Datensätze: {len(records)}")


if __name__ == "__main__":
    main()
