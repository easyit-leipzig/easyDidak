# -*- coding: utf-8 -*-
"""
18. Zeitliche Unterrichtsphasen-Erkennung – Export

Erzeugt eine JSON-Datei mit drei Scopes:
1. alle_lehrkraefte
2. lehrkraft_1
3. ohne_lehrkraft_1

Grundlage ist analyze_lehrkraftdaten bzw. frzk_semantische_dichte_lehrer mit zeitlicher Ordnung.
Die Auswertung bleibt bewusst lehrkraftseitig und benötigt keine Teilnehmersicht.
"""

from __future__ import annotations

import json
import math
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

OUTPUT_FILE = Path("auswertung_18_zeitliche_unterrichtsphasen.json")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

VECTOR_COLUMNS = [f"x_{d}" for d in DIMENSIONS]
SUM_COLUMNS = [f"sum_{d}" for d in DIMENSIONS]

SCOPES = {
    "alle_lehrkraefte": "1=1",
    "lehrkraft_1": "lehrkraft_id = 1",
    "ohne_lehrkraft_1": "(lehrkraft_id <> 1 OR lehrkraft_id IS NULL)",
}


def json_default(obj: Any) -> str:
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def safe_float(value: Any, default: float = 0.0) -> float:
    try:
        if value is None:
            return default
        value = float(value)
        if math.isnan(value) or math.isinf(value):
            return default
        return value
    except Exception:
        return default


def vector_from_row(row: Dict[str, Any], prefix: str = "x_") -> List[float]:
    return [safe_float(row.get(f"{prefix}{dim}")) for dim in DIMENSIONS]


def norm(vec: List[float]) -> float:
    return math.sqrt(sum(v * v for v in vec))


def cosine(a: List[float], b: List[float]) -> float:
    na = norm(a)
    nb = norm(b)
    if na == 0 or nb == 0:
        return 0.0
    return sum(x * y for x, y in zip(a, b)) / (na * nb)


def load_scope_rows(cursor: mysql.connector.cursor.MySQLCursorDict, where_sql: str) -> List[Dict[str, Any]]:
    """
    Nutzt analyze_lehrkraftdaten, weil dort lehrkraft_id, datum, gruppe_id,
    teilnehmer_id, Fach/Thema und die FRZK-Dimensionen zusammengeführt sind.
    """
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
            mtr_rueckkopplung_datenmaske_values_id,
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
            operator_count,
            modulator_count,
            has_operator,
            operator_names,
            modulator_names
        FROM analyze_lehrkraftdaten
        WHERE {where_sql}
          AND datum IS NOT NULL
        ORDER BY lehrkraft_id, gruppe_id, teilnehmer_id, datum,
                 id_mtr_rueckkopplung_datenmaske, mtr_rueckkopplung_datenmaske_values_id
    """
    cursor.execute(sql)
    return cursor.fetchall()


def enrich_rows(rows: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    """Berechnet zeitliche Folgegrößen für jede lehrkraft-gruppe-teilnehmer-Trajektorie."""
    enriched: List[Dict[str, Any]] = []
    last_by_path: Dict[str, Dict[str, Any]] = {}

    for row in rows:
        vec = vector_from_row(row, "x_")
        sum_vec = vector_from_row(row, "sum_")
        path_key = f"lk={row.get('lehrkraft_id')}|g={row.get('gruppe_id')}|tn={row.get('teilnehmer_id')}"
        prev = last_by_path.get(path_key)

        drift_norm = 0.0
        cosine_prev = None
        delta_dichte = 0.0
        polaritaetswechsel = 0
        dominanzwechsel = 0

        if prev is not None:
            prev_vec = prev["vector"]
            drift_vec = [b - a for a, b in zip(prev_vec, vec)]
            drift_norm = norm(drift_vec)
            cosine_prev = cosine(prev_vec, vec)
            delta_dichte = safe_float(row.get("d_semantisch")) - safe_float(prev.get("d_semantisch"))
            polaritaetswechsel = int(row.get("polaritaet_gesamt") != prev.get("polaritaet_gesamt"))
            dominanzwechsel = int(row.get("dominante_dimension") != prev.get("dominante_dimension"))

        item = {
            "id": row.get("id"),
            "path_key": path_key,
            "lehrkraft_id": row.get("lehrkraft_id"),
            "gruppe_id": row.get("gruppe_id"),
            "teilnehmer_id": row.get("teilnehmer_id"),
            "fach": row.get("fach"),
            "datum": row.get("datum"),
            "thema": row.get("thema"),
            "bemerkung": row.get("bemerkung"),
            "id_mtr_rueckkopplung_datenmaske": row.get("id_mtr_rueckkopplung_datenmaske"),
            "mtr_rueckkopplung_datenmaske_values_id": row.get("mtr_rueckkopplung_datenmaske_values_id"),
            "vector": vec,
            "sum_vector": sum_vec,
            "d_semantisch": safe_float(row.get("d_semantisch")),
            "dominante_dimension": row.get("dominante_dimension"),
            "dominante_dimension_wert": safe_float(row.get("dominante_dimension_wert")),
            "polaritaet_gesamt": int(row.get("polaritaet_gesamt") or 0),
            "token_anzahl": int(row.get("token_anzahl") or 0),
            "funktionsklassen_anzahl_gesamt": int(row.get("funktionsklassen_anzahl_gesamt") or 0),
            "operator_count": int(row.get("operator_count") or 0),
            "modulator_count": int(row.get("modulator_count") or 0),
            "has_operator": int(row.get("has_operator") or 0),
            "operator_names": row.get("operator_names"),
            "modulator_names": row.get("modulator_names"),
            "temporal": {
                "drift_norm": drift_norm,
                "cosine_prev": cosine_prev,
                "delta_dichte": delta_dichte,
                "polaritaetswechsel": polaritaetswechsel,
                "dominanzwechsel": dominanzwechsel,
            },
        }
        enriched.append(item)
        last_by_path[path_key] = item

    return enriched


def aggregate_scope(items: List[Dict[str, Any]]) -> Dict[str, Any]:
    n = len(items)
    if n == 0:
        return {"n": 0}

    drift_values = [x["temporal"]["drift_norm"] for x in items]
    density_values = [x["d_semantisch"] for x in items]
    cosine_values = [x["temporal"]["cosine_prev"] for x in items if x["temporal"]["cosine_prev"] is not None]

    dominant_counts: Dict[str, int] = {}
    phase_candidate_counts = {
        "polaritaetswechsel": 0,
        "dominanzwechsel": 0,
        "operator_aktiv": 0,
    }

    for item in items:
        dim = item.get("dominante_dimension") or "unbekannt"
        dominant_counts[dim] = dominant_counts.get(dim, 0) + 1
        phase_candidate_counts["polaritaetswechsel"] += item["temporal"]["polaritaetswechsel"]
        phase_candidate_counts["dominanzwechsel"] += item["temporal"]["dominanzwechsel"]
        phase_candidate_counts["operator_aktiv"] += item["has_operator"]

    return {
        "n": n,
        "unique_lehrkraefte": sorted({x.get("lehrkraft_id") for x in items if x.get("lehrkraft_id") is not None}),
        "unique_gruppen": sorted({x.get("gruppe_id") for x in items if x.get("gruppe_id") is not None}),
        "unique_teilnehmer": sorted({x.get("teilnehmer_id") for x in items if x.get("teilnehmer_id") is not None}),
        "mean_d_semantisch": sum(density_values) / n,
        "mean_drift_norm": sum(drift_values) / n,
        "max_drift_norm": max(drift_values),
        "mean_cosine_prev": (sum(cosine_values) / len(cosine_values)) if cosine_values else None,
        "dominante_dimension_counts": dominant_counts,
        "phase_signal_counts": phase_candidate_counts,
    }


def main() -> None:
    conn = mysql.connector.connect(**DB_CONFIG)
    cursor = conn.cursor(dictionary=True)

    result: Dict[str, Any] = {
        "analysis_id": "18_zeitliche_unterrichtsphasen_erkennung",
        "description": "Zeitliche Unterrichtsphasen-Erkennung aus Lehrkraftsicht im FRZK-Raum",
        "dimensions": DIMENSIONS,
        "scopes": {},
    }

    for scope_name, where_sql in SCOPES.items():
        rows = load_scope_rows(cursor, where_sql)
        items = enrich_rows(rows)
        result["scopes"][scope_name] = {
            "where": where_sql,
            "summary": aggregate_scope(items),
            "items": items,
        }

    cursor.close()
    conn.close()

    OUTPUT_FILE.write_text(
        json.dumps(result, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8",
    )
    print(f"Export abgeschlossen: {OUTPUT_FILE.resolve()}")


if __name__ == "__main__":
    main()
