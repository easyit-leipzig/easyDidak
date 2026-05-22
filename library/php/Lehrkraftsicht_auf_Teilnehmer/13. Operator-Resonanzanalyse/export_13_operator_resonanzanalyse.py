# -*- coding: utf-8 -*-
"""
13. Operator-Resonanzanalyse – Export-Skript

Erzeugt eine gemeinsame JSON-Datei mit drei Scopes:
1) alle_lehrkraefte
2) lehrkraft_1
3) ohne_lehrkraft_1

Ziel:
- Export der Zustände aus analyze_lehrkraftdaten
- zeitliche Vor-/Nachher-Bildung innerhalb von teilnehmer_id + gruppe_id + lehrkraft_id
- Messung operatorischer Zustandsveränderung über Delta-V, Kohärenz-/Resonanzänderung,
  Polaritätswechsel und Dominanzdimensionswechsel

Voraussetzung:
pip install mysql-connector-python
"""

from __future__ import annotations

import json
import math
from collections import defaultdict
from datetime import date, datetime
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Tuple

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

OUTPUT_JSON = Path("auswertung_13_operator_resonanzanalyse.json")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]
X_COLS = [f"x_{d}" for d in DIMENSIONS]
SUM_COLS = [f"sum_{d}" for d in DIMENSIONS]


def to_json_value(value: Any) -> Any:
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    return value


def safe_float(value: Any, default: float = 0.0) -> float:
    if value is None:
        return default
    try:
        return float(value)
    except (TypeError, ValueError):
        return default


def safe_int(value: Any, default: int = 0) -> int:
    if value is None:
        return default
    try:
        return int(value)
    except (TypeError, ValueError):
        return default


def vector_from_row(row: Dict[str, Any], cols: List[str]) -> List[float]:
    return [safe_float(row.get(col)) for col in cols]


def norm(vec: Iterable[float]) -> float:
    return math.sqrt(sum(v * v for v in vec))


def cosine(a: List[float], b: List[float]) -> Optional[float]:
    na = norm(a)
    nb = norm(b)
    if na == 0 or nb == 0:
        return None
    return sum(x * y for x, y in zip(a, b)) / (na * nb)


def split_names(value: Any) -> List[str]:
    if value is None:
        return []
    text = str(value).strip()
    if not text:
        return []
    # in der Datenbank kommen je nach Erzeugung Komma, Semikolon oder Pipe vor
    for sep in ["|", ";"]:
        text = text.replace(sep, ",")
    return [part.strip() for part in text.split(",") if part.strip()]


def fetch_rows() -> List[Dict[str, Any]]:
    sql = """
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
        ORDER BY
            lehrkraft_id,
            gruppe_id,
            teilnehmer_id,
            datum,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
            id
    """
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cur = conn.cursor(dictionary=True)
        cur.execute(sql)
        rows = cur.fetchall()
        cur.close()
        conn.close()
        return rows
    except Error as exc:
        raise RuntimeError(f"Datenbankfehler beim Export: {exc}") from exc


def normalize_row(row: Dict[str, Any]) -> Dict[str, Any]:
    x_vec = vector_from_row(row, X_COLS)
    sum_vec = vector_from_row(row, SUM_COLS)
    operators = split_names(row.get("operator_names"))
    modulators = split_names(row.get("modulator_names"))

    base = {key: to_json_value(value) for key, value in row.items()}
    base.update(
        {
            "x_vector": x_vec,
            "sum_vector": sum_vec,
            "vector_norm_x": norm(x_vec),
            "vector_norm_sum": norm(sum_vec),
            "operator_list": operators,
            "modulator_list": modulators,
            "has_operator": 1 if safe_int(row.get("has_operator")) else 0,
            "operator_count": safe_int(row.get("operator_count")),
            "modulator_count": safe_int(row.get("modulator_count")),
            "d_semantisch": safe_float(row.get("d_semantisch")),
            "polaritaet_gesamt": safe_int(row.get("polaritaet_gesamt")),
            "dominante_dimension_wert": safe_float(row.get("dominante_dimension_wert")),
            "token_anzahl": safe_int(row.get("token_anzahl")),
            "funktionsklassen_anzahl_gesamt": safe_int(row.get("funktionsklassen_anzahl_gesamt")),
        }
    )
    return base


def enrich_transitions(records: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    """Bildet Vor-/Nachher-Differenzen innerhalb gleicher Lehrkraft-Gruppe-Teilnehmer-Bahn."""
    grouped: Dict[Tuple[Any, Any, Any], List[Dict[str, Any]]] = defaultdict(list)
    for rec in records:
        grouped[(rec.get("lehrkraft_id"), rec.get("gruppe_id"), rec.get("teilnehmer_id"))].append(rec)

    enriched: List[Dict[str, Any]] = []
    for _, items in grouped.items():
        items.sort(
            key=lambda r: (
                str(r.get("datum") or ""),
                safe_int(r.get("id_mtr_rueckkopplung_datenmaske")),
                safe_int(r.get("mtr_rueckkopplung_datenmaske_values_id")),
                safe_int(r.get("id")),
            )
        )
        previous: Optional[Dict[str, Any]] = None
        for idx, rec in enumerate(items):
            out = dict(rec)
            out["sequence_index"] = idx
            if previous is None:
                out.update(
                    {
                        "has_previous_state": 0,
                        "prev_id": None,
                        "delta_vector": None,
                        "delta_norm": None,
                        "delta_d_semantisch": None,
                        "cosine_prev_current": None,
                        "resonance_change": None,
                        "polaritaet_change": 0,
                        "dominanz_change": 0,
                        "dominanz_from": None,
                        "dominanz_to": rec.get("dominante_dimension"),
                    }
                )
            else:
                prev_vec = previous["sum_vector"]
                cur_vec = rec["sum_vector"]
                delta_vec = [c - p for c, p in zip(cur_vec, prev_vec)]
                cos_pc = cosine(prev_vec, cur_vec)
                # Resonanzänderung: 1 - Distanz im Richtungsraum; None falls kein Vorgängervektor
                resonance_change = None if cos_pc is None else 1.0 - cos_pc
                out.update(
                    {
                        "has_previous_state": 1,
                        "prev_id": previous.get("id"),
                        "delta_vector": delta_vec,
                        "delta_norm": norm(delta_vec),
                        "delta_d_semantisch": rec["d_semantisch"] - previous["d_semantisch"],
                        "cosine_prev_current": cos_pc,
                        "resonance_change": resonance_change,
                        "polaritaet_change": abs(rec["polaritaet_gesamt"] - previous["polaritaet_gesamt"]),
                        "dominanz_change": 1 if rec.get("dominante_dimension") != previous.get("dominante_dimension") else 0,
                        "dominanz_from": previous.get("dominante_dimension"),
                        "dominanz_to": rec.get("dominante_dimension"),
                    }
                )
            previous = rec
            enriched.append(out)
    enriched.sort(
        key=lambda r: (
            safe_int(r.get("lehrkraft_id")),
            safe_int(r.get("gruppe_id")),
            safe_int(r.get("teilnehmer_id")),
            str(r.get("datum") or ""),
            safe_int(r.get("id")),
        )
    )
    return enriched


def mean(values: List[float]) -> Optional[float]:
    values = [v for v in values if v is not None and not math.isnan(v)]
    if not values:
        return None
    return sum(values) / len(values)


def summarize_scope(records: List[Dict[str, Any]]) -> Dict[str, Any]:
    transitions = [r for r in records if r.get("has_previous_state")]
    op_records = [r for r in records if r.get("has_operator")]
    no_op_records = [r for r in records if not r.get("has_operator")]
    op_transitions = [r for r in transitions if r.get("has_operator")]
    no_op_transitions = [r for r in transitions if not r.get("has_operator")]

    operator_counter: Dict[str, int] = defaultdict(int)
    operator_delta: Dict[str, List[float]] = defaultdict(list)
    operator_resonance: Dict[str, List[float]] = defaultdict(list)
    operator_density_delta: Dict[str, List[float]] = defaultdict(list)
    operator_polarity: Dict[str, int] = defaultdict(int)
    operator_dominance: Dict[str, int] = defaultdict(int)
    operator_dim_delta: Dict[str, Dict[str, List[float]]] = defaultdict(lambda: defaultdict(list))

    for rec in op_transitions:
        names = rec.get("operator_list") or ["__operator_unbenannt__"]
        for name in names:
            operator_counter[name] += 1
            if rec.get("delta_norm") is not None:
                operator_delta[name].append(float(rec["delta_norm"]))
            if rec.get("resonance_change") is not None:
                operator_resonance[name].append(float(rec["resonance_change"]))
            if rec.get("delta_d_semantisch") is not None:
                operator_density_delta[name].append(float(rec["delta_d_semantisch"]))
            if rec.get("polaritaet_change"):
                operator_polarity[name] += 1
            if rec.get("dominanz_change"):
                operator_dominance[name] += 1
            if rec.get("delta_vector") is not None:
                for dim, val in zip(DIMENSIONS, rec["delta_vector"]):
                    operator_dim_delta[name][dim].append(float(val))

    by_operator = []
    for name in sorted(operator_counter.keys()):
        n = operator_counter[name]
        by_operator.append(
            {
                "operator": name,
                "n_transitions": n,
                "mean_delta_norm": mean(operator_delta[name]),
                "mean_resonance_change": mean(operator_resonance[name]),
                "mean_delta_d_semantisch": mean(operator_density_delta[name]),
                "polaritaet_change_rate": operator_polarity[name] / n if n else None,
                "dominanz_change_rate": operator_dominance[name] / n if n else None,
                "mean_delta_by_dimension": {
                    dim: mean(operator_dim_delta[name][dim]) for dim in DIMENSIONS
                },
            }
        )
    by_operator.sort(key=lambda x: (x["mean_delta_norm"] is None, -(x["mean_delta_norm"] or 0)))

    return {
        "n_records": len(records),
        "n_transitions": len(transitions),
        "n_operator_records": len(op_records),
        "n_operator_transitions": len(op_transitions),
        "n_operatorfreie_records": len(no_op_records),
        "n_operatorfreie_transitions": len(no_op_transitions),
        "operator_record_rate": len(op_records) / len(records) if records else None,
        "mean_delta_norm_operatorhaltig": mean([r.get("delta_norm") for r in op_transitions]),
        "mean_delta_norm_operatorfrei": mean([r.get("delta_norm") for r in no_op_transitions]),
        "mean_resonance_change_operatorhaltig": mean([r.get("resonance_change") for r in op_transitions]),
        "mean_resonance_change_operatorfrei": mean([r.get("resonance_change") for r in no_op_transitions]),
        "mean_delta_d_semantisch_operatorhaltig": mean([r.get("delta_d_semantisch") for r in op_transitions]),
        "mean_delta_d_semantisch_operatorfrei": mean([r.get("delta_d_semantisch") for r in no_op_transitions]),
        "polaritaet_change_rate_operatorhaltig": (sum(1 for r in op_transitions if r.get("polaritaet_change")) / len(op_transitions)) if op_transitions else None,
        "polaritaet_change_rate_operatorfrei": (sum(1 for r in no_op_transitions if r.get("polaritaet_change")) / len(no_op_transitions)) if no_op_transitions else None,
        "dominanz_change_rate_operatorhaltig": (sum(1 for r in op_transitions if r.get("dominanz_change")) / len(op_transitions)) if op_transitions else None,
        "dominanz_change_rate_operatorfrei": (sum(1 for r in no_op_transitions if r.get("dominanz_change")) / len(no_op_transitions)) if no_op_transitions else None,
        "by_operator": by_operator,
    }


def build_scopes(records: List[Dict[str, Any]]) -> Dict[str, Any]:
    scopes_raw = {
        "alle_lehrkraefte": records,
        "lehrkraft_1": [r for r in records if safe_int(r.get("lehrkraft_id")) == 1],
        "ohne_lehrkraft_1": [r for r in records if safe_int(r.get("lehrkraft_id")) != 1],
    }
    scopes = {}
    for name, scope_records in scopes_raw.items():
        enriched = enrich_transitions(scope_records)
        scopes[name] = {
            "summary": summarize_scope(enriched),
            "records": enriched,
        }
    return scopes


def main() -> None:
    rows = fetch_rows()
    records = [normalize_row(r) for r in rows]
    payload = {
        "analysis_id": "13_operator_resonanzanalyse",
        "created_at": datetime.now().isoformat(timespec="seconds"),
        "database": DB_CONFIG["database"],
        "source_view": "analyze_lehrkraftdaten",
        "dimensions": DIMENSIONS,
        "method": {
            "state_vector": "sum_vector = [sum_kognition ... sum_regulation]",
            "transition_grouping": "lehrkraft_id + gruppe_id + teilnehmer_id, ordered by datum and source ids",
            "delta_v": "V_t - V_(t-1)",
            "delta_norm": "euklidische Norm von delta_v",
            "resonance_change": "1 - cosine(V_(t-1), V_t)",
            "operator_comparison": "has_operator = 1 vs has_operator = 0",
        },
        "scopes": build_scopes(records),
    }
    OUTPUT_JSON.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Export abgeschlossen: {OUTPUT_JSON.resolve()}")
    for scope_name, scope in payload["scopes"].items():
        s = scope["summary"]
        print(
            f"{scope_name}: records={s['n_records']}, transitions={s['n_transitions']}, "
            f"operator_transitions={s['n_operator_transitions']}"
        )


if __name__ == "__main__":
    main()
