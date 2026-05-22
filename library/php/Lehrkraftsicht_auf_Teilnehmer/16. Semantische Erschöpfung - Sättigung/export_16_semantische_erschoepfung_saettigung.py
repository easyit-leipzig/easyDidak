# -*- coding: utf-8 -*-
"""
16. Semantische Erschöpfung / Sättigung – Exportskript

Erzeugt eine gemeinsame JSON-Datei für:
1. alle Lehrkräfte
2. lehrkraft_id = 1
3. alle außer lehrkraft_id = 1

Datenbasis: analyze_lehrkraftdaten
DB: icas_19_4_2
"""

import json
import math
from pathlib import Path
from datetime import date, datetime
from typing import Any, Dict, List, Optional

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

OUTPUT_JSON = Path("auswertung_16_semantische_erschoepfung_saettigung.json")
BETA = 0.15
DIMENSIONS = [
    "kognition", "sozial", "affektiv", "motivation",
    "methodik", "performanz", "regulation",
]
VECTOR_COLS = [f"sum_{d}" for d in DIMENSIONS]

SCOPES = {
    "alle_lehrkraefte": "1=1",
    "lehrkraft_1": "lehrkraft_id = 1",
    "ohne_lehrkraft_1": "lehrkraft_id <> 1",
}


def json_default(obj: Any) -> str:
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def safe_float(value: Any, default: float = 0.0) -> float:
    try:
        if value is None:
            return default
        return float(value)
    except (TypeError, ValueError):
        return default


def norm(values: List[float]) -> float:
    return math.sqrt(sum(v * v for v in values))


def mean(values: List[float]) -> Optional[float]:
    values = [v for v in values if v is not None]
    return sum(values) / len(values) if values else None


def percentile(values: List[float], q: float) -> Optional[float]:
    values = sorted(v for v in values if v is not None)
    if not values:
        return None
    if len(values) == 1:
        return values[0]
    pos = (len(values) - 1) * q
    lo = math.floor(pos)
    hi = math.ceil(pos)
    if lo == hi:
        return values[int(pos)]
    return values[lo] * (hi - pos) + values[hi] * (pos - lo)


def fetch_scope_rows(where_clause: str) -> List[Dict[str, Any]]:
    sql = f"""
        SELECT
            id,
            lehrkraft_id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            thema,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
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
            modulator_names,
            sum_kognition,
            sum_sozial,
            sum_affektiv,
            sum_motivation,
            sum_methodik,
            sum_performanz,
            sum_regulation
        FROM analyze_lehrkraftdaten
        WHERE {where_clause}
          AND d_semantisch IS NOT NULL
          AND token_anzahl IS NOT NULL
        ORDER BY
            lehrkraft_id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
            id
    """
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(sql)
        rows = cur.fetchall()
    finally:
        conn.close()
    return rows


def add_dynamic_metrics(rows: List[Dict[str, Any]]) -> List[Dict[str, Any]]:
    enriched: List[Dict[str, Any]] = []
    previous_by_path: Dict[str, Dict[str, Any]] = {}

    for row in rows:
        vector = [safe_float(row.get(col)) for col in VECTOR_COLS]
        d = safe_float(row.get("d_semantisch"))
        tokens = max(int(row.get("token_anzahl") or 0), 0)
        damping = math.exp(-BETA * d)

        path_key = "|".join(str(row.get(k, "")) for k in [
            "lehrkraft_id", "gruppe_id", "teilnehmer_id", "fach"
        ])
        prev = previous_by_path.get(path_key)

        if prev:
            prev_vector = prev["_vector"]
            drift = norm([vector[i] - prev_vector[i] for i in range(len(vector))])
            density_delta = d - safe_float(prev.get("d_semantisch"))
            token_delta = tokens - int(prev.get("token_anzahl") or 0)
            drift_per_token = drift / max(tokens, 1)
            density_delta_per_token = density_delta / max(abs(token_delta), 1)
        else:
            drift = None
            density_delta = None
            token_delta = None
            drift_per_token = None
            density_delta_per_token = None

        row_out = dict(row)
        row_out["vector_sum"] = {DIMENSIONS[i]: vector[i] for i in range(len(DIMENSIONS))}
        row_out["damping_beta_0_15"] = damping
        row_out["aktivierung_pro_token"] = d / max(tokens, 1)
        row_out["drift_zum_vorwert"] = drift
        row_out["drift_pro_token"] = drift_per_token
        row_out["dichte_delta_zum_vorwert"] = density_delta
        row_out["dichte_delta_pro_token"] = density_delta_per_token
        row_out["token_delta_zum_vorwert"] = token_delta
        row_out["trajectory_key"] = path_key
        row_out["_vector"] = vector

        previous_by_path[path_key] = row_out
        enriched.append(row_out)

    for row in enriched:
        row.pop("_vector", None)
    return enriched


def classify_exhaustion(rows: List[Dict[str, Any]]) -> Dict[str, Any]:
    densities = [safe_float(r.get("d_semantisch")) for r in rows]
    tokens = [safe_float(r.get("token_anzahl")) for r in rows]
    drifts = [r.get("drift_zum_vorwert") for r in rows if r.get("drift_zum_vorwert") is not None]
    dpt = [r.get("drift_pro_token") for r in rows if r.get("drift_pro_token") is not None]

    q75_density = percentile(densities, 0.75)
    q75_tokens = percentile(tokens, 0.75)
    q25_drift = percentile(drifts, 0.25)
    q25_dpt = percentile(dpt, 0.25)

    for r in rows:
        high_density = q75_density is not None and safe_float(r.get("d_semantisch")) >= q75_density
        high_tokens = q75_tokens is not None and safe_float(r.get("token_anzahl")) >= q75_tokens
        low_drift = q25_drift is not None and r.get("drift_zum_vorwert") is not None and r["drift_zum_vorwert"] <= q25_drift
        low_drift_per_token = q25_dpt is not None and r.get("drift_pro_token") is not None and r["drift_pro_token"] <= q25_dpt

        r["saturation_flags"] = {
            "hohe_semantische_dichte": bool(high_density),
            "viele_tokens": bool(high_tokens),
            "geringe_drift": bool(low_drift),
            "geringe_drift_pro_token": bool(low_drift_per_token),
        }
        r["semantische_erschoepfung_kandidat"] = bool(
            high_density and high_tokens and (low_drift or low_drift_per_token)
        )

    candidates = [r for r in rows if r.get("semantische_erschoepfung_kandidat")]
    return {
        "thresholds": {
            "q75_d_semantisch": q75_density,
            "q75_token_anzahl": q75_tokens,
            "q25_drift_zum_vorwert": q25_drift,
            "q25_drift_pro_token": q25_dpt,
        },
        "candidate_count": len(candidates),
        "candidate_share": len(candidates) / len(rows) if rows else 0,
    }


def summarize(rows: List[Dict[str, Any]]) -> Dict[str, Any]:
    drifts = [r.get("drift_zum_vorwert") for r in rows if r.get("drift_zum_vorwert") is not None]
    damping_values = [r.get("damping_beta_0_15") for r in rows if r.get("damping_beta_0_15") is not None]
    candidates = [r for r in rows if r.get("semantische_erschoepfung_kandidat")]

    dominant_counts: Dict[str, int] = {}
    for r in rows:
        dim = r.get("dominante_dimension") or "unbekannt"
        dominant_counts[dim] = dominant_counts.get(dim, 0) + 1

    return {
        "n": len(rows),
        "mean_d_semantisch": mean([safe_float(r.get("d_semantisch")) for r in rows]),
        "mean_token_anzahl": mean([safe_float(r.get("token_anzahl")) for r in rows]),
        "mean_drift": mean(drifts),
        "mean_damping_beta_0_15": mean(damping_values),
        "mean_aktivierung_pro_token": mean([r.get("aktivierung_pro_token") for r in rows]),
        "semantische_erschoepfung_kandidaten": len(candidates),
        "semantische_erschoepfung_anteil": len(candidates) / len(rows) if rows else 0,
        "dominante_dimension_counts": dominant_counts,
    }


def main() -> None:
    output: Dict[str, Any] = {
        "auswertung": "16. Semantische Erschöpfung / Sättigung",
        "modell": {
            "damping": "exp(-beta * ||V||)",
            "beta": BETA,
            "interpretation": "Hohe semantische Dichte bei geringer Drift und vielen Tokens wird als Sättigungs-/Erschöpfungskandidat markiert.",
        },
        "scopes": {},
    }

    for scope_name, where_clause in SCOPES.items():
        rows = fetch_scope_rows(where_clause)
        rows = add_dynamic_metrics(rows)
        classification = classify_exhaustion(rows)
        summary = summarize(rows)

        output["scopes"][scope_name] = {
            "where": where_clause,
            "summary": summary,
            "classification": classification,
            "rows": rows,
        }
        print(f"{scope_name}: {len(rows)} Datensätze, {classification['candidate_count']} Sättigungskandidaten")

    OUTPUT_JSON.write_text(json.dumps(output, ensure_ascii=False, indent=2, default=json_default), encoding="utf-8")
    print(f"JSON erzeugt: {OUTPUT_JSON.resolve()}")


if __name__ == "__main__":
    main()
