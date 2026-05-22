# -*- coding: utf-8 -*-
"""
Auswertungspunkt 23: Didaktische Interventionssensitivitaet
Export-Skript

Erzeugt eine JSON-Datei mit drei Scopes:
1. alle Lehrkraefte
2. lehrkraft_id = 1
3. alle ausser lehrkraft_id = 1

Ziel:
Welche didaktischen Interventionselemente veraendern semantische Zustände am staerksten?
Interventionen:
- Lob
- Strukturierung
- Aktivierungsfragen
- Regelhinweise
- Metakognition
- emotionale Stuet-zung

Voraussetzung:
    pip install mysql-connector-python
"""

from __future__ import annotations

import json
import math
import re
from collections import defaultdict
from dataclasses import dataclass
from datetime import date, datetime
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Tuple

import mysql.connector

# ------------------------------------------------------------
# Standard-DB-Konfiguration
# ------------------------------------------------------------
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

OUTPUT_JSON = Path("auswertung_23_didaktische_interventionssensitivitaet.json")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

# ------------------------------------------------------------
# Heuristische Interventionskategorien
# Die Muster sind bewusst breit angelegt, damit die Analyse auch
# ohne neue Datenbanktabellen direkt auf bestehenden Bemerkungs- und
# Lexemdaten laufen kann.
# ------------------------------------------------------------
INTERVENTION_PATTERNS: Dict[str, List[str]] = {
    "lob": [
        r"\bgut\b", r"\bsehr gut\b", r"\bsuper\b", r"\btoll\b", r"\bprima\b",
        r"\bstark\b", r"\bfleißig\b", r"\bfleissig\b", r"\bbemüht\b", r"\bbemueht\b",
        r"\bgute mitarbeit\b", r"\blob\b", r"\bklasse\b",
    ],
    "strukturierung": [
        r"\bstruktur\w*\b", r"\bschritt\w*\b", r"\bplan\w*\b", r"\bvorgehen\b",
        r"\bnotiz\w*\b", r"\bunterstreich\w*\b", r"\bordnen\b", r"\bgeordnet\b",
        r"\bzusammenfassung\b", r"\bschema\b", r"\baufbau\b", r"\blösungsweg\b", r"\bloesungsweg\b",
    ],
    "aktivierungsfragen": [
        r"\bfrage\w*\b", r"\bwarum\b", r"\bwieso\b", r"\bwie\b", r"\bwas\b",
        r"\berkläre\b", r"\berklaere\b", r"\bbegründe\b", r"\bbegruende\b",
        r"\büberlege\b", r"\bueberlege\b", r"\baktivier\w*\b",
    ],
    "regelhinweise": [
        r"\bregel\w*\b", r"\bformel\w*\b", r"\bgesetz\w*\b", r"\bdefinition\w*\b",
        r"\bmerksatz\b", r"\bbeachte\b", r"\bhinweis\w*\b", r"\bzeichenregel\b",
        r"\brechenregel\b", r"\bvereinbarung\w*\b",
    ],
    "metakognition": [
        r"\breflex\w*\b", r"\bmetakogn\w*\b", r"\bselbst\w*\b", r"\bkontroll\w*\b",
        r"\bstrategie\w*\b", r"\bprüf\w*\b", r"\bpruef\w*\b", r"\bverstanden\b",
        r"\bversteh\w*\b", r"\bdenkweg\b", r"\bfehler\w*\b", r"\bkorrektur\w*\b",
    ],
    "emotionale_stuetzung": [
        r"\bermutig\w*\b", r"\bunterstütz\w*\b", r"\bunterstuetz\w*\b", r"\bmotivier\w*\b",
        r"\bsicherheit\b", r"\bvertrauen\b", r"\bruhe\b", r"\bangst\w*\b", r"\bfrust\w*\b",
        r"\bemotional\w*\b", r"\bstützung\b", r"\bstuetzung\b", r"\bzuversicht\b",
    ],
}

COMPILED_PATTERNS = {
    name: [re.compile(pattern, flags=re.IGNORECASE | re.UNICODE) for pattern in patterns]
    for name, patterns in INTERVENTION_PATTERNS.items()
}


@dataclass
class Row:
    id_mtr_rueckkopplung_datenmaske: int
    mtr_rueckkopplung_datenmaske_values_id: Optional[int]
    lehrkraft_id: Optional[int]
    gruppe_id: Optional[int]
    teilnehmer_id: Optional[int]
    datum: Optional[str]
    thema: Optional[str]
    bemerkung: str
    vector: Dict[str, float]
    d_semantisch: Optional[float]
    dominante_dimension: Optional[str]
    dominante_dimension_wert: Optional[float]
    polaritaet_gesamt: Optional[int]


def json_default(obj: Any) -> str:
    if isinstance(obj, (datetime, date)):
        return obj.isoformat()
    return str(obj)


def connect():
    return mysql.connector.connect(**DB_CONFIG)


def safe_float(value: Any) -> float:
    if value is None:
        return 0.0
    try:
        return float(value)
    except Exception:
        return 0.0


def norm(vec: Dict[str, float]) -> float:
    return math.sqrt(sum(float(vec[d]) ** 2 for d in DIMENSIONS))


def vector_delta(a: Dict[str, float], b: Dict[str, float]) -> Dict[str, float]:
    return {d: float(b[d]) - float(a[d]) for d in DIMENSIONS}


def euclidean_delta(a: Dict[str, float], b: Dict[str, float]) -> float:
    return math.sqrt(sum((float(b[d]) - float(a[d])) ** 2 for d in DIMENSIONS))


def cosine_similarity(a: Dict[str, float], b: Dict[str, float]) -> Optional[float]:
    na = norm(a)
    nb = norm(b)
    if na == 0 or nb == 0:
        return None
    return sum(float(a[d]) * float(b[d]) for d in DIMENSIONS) / (na * nb)


def detect_interventions(text: str, lexemes: Iterable[str]) -> List[str]:
    joined = " ".join([text or "", " ".join([x or "" for x in lexemes])]).lower()
    hits = []
    for name, patterns in COMPILED_PATTERNS.items():
        if any(p.search(joined) for p in patterns):
            hits.append(name)
    return hits or ["ohne_klassifizierte_intervention"]


def fetch_rows(cursor, where_sql: str = "", params: Tuple[Any, ...] = ()) -> List[Row]:
    # analyze_lehrkraftdaten enthaelt laut SQL-Dump die zentralen Analysefelder inkl.
    # lehrkraft_id, Bemerkung, Vektorkomponenten, dominante Dimension und Dichte.
    sql = f"""
        SELECT
            id_mtr_rueckkopplung_datenmaske,
            mtr_rueckkopplung_datenmaske_values_id,
            lehrkraft_id,
            gruppe_id,
            teilnehmer_id,
            datum,
            thema,
            bemerkung,
            x_kognition, x_sozial, x_affektiv, x_motivation,
            x_methodik, x_performanz, x_regulation,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        FROM sql_semantische_dichte_lehrer_type_1
        WHERE id_mtr_rueckkopplung_datenmaske IS NOT NULL
          AND datum IS NOT NULL
          {where_sql}
        ORDER BY lehrkraft_id, gruppe_id, teilnehmer_id, datum,
                 id_mtr_rueckkopplung_datenmaske,
                 mtr_rueckkopplung_datenmaske_values_id
    """
    cursor.execute(sql, params)
    rows = []
    for r in cursor.fetchall():
        rows.append(Row(
            id_mtr_rueckkopplung_datenmaske=int(r["id_mtr_rueckkopplung_datenmaske"]),
            mtr_rueckkopplung_datenmaske_values_id=r.get("mtr_rueckkopplung_datenmaske_values_id"),
            lehrkraft_id=r.get("lehrkraft_id"),
            gruppe_id=r.get("gruppe_id"),
            teilnehmer_id=r.get("teilnehmer_id"),
            datum=json_default(r.get("datum")) if r.get("datum") is not None else None,
            thema=r.get("thema"),
            bemerkung=r.get("bemerkung") or "",
            vector={d: safe_float(r[f"x_{d}"]) for d in DIMENSIONS},
            d_semantisch=safe_float(r.get("d_semantisch")),
            dominante_dimension=r.get("dominante_dimension"),
            dominante_dimension_wert=safe_float(r.get("dominante_dimension_wert")),
            polaritaet_gesamt=r.get("polaritaet_gesamt"),
        ))
    return rows


def fetch_lexemes(cursor, sentence_ids: Iterable[int]) -> Dict[int, List[str]]:
    ids = sorted({int(x) for x in sentence_ids if x is not None})
    result: Dict[int, List[str]] = defaultdict(list)
    if not ids:
        return result

    chunk_size = 500
    for i in range(0, len(ids), chunk_size):
        chunk = ids[i:i + chunk_size]
        placeholders = ",".join(["%s"] * len(chunk))
        cursor.execute(f"""
            SELECT mtr_rueckkopplung_datenmaske_values_id, lexem
            FROM frzk_lexem_datenmaske_lexem_funktionsklasse_weight
            WHERE mtr_rueckkopplung_datenmaske_values_id IN ({placeholders})
            ORDER BY mtr_rueckkopplung_datenmaske_values_id, id
        """, tuple(chunk))
        for r in cursor.fetchall():
            result[int(r["mtr_rueckkopplung_datenmaske_values_id"])].append(r["lexem"])
    return result


def summarize_scope(scope_name: str, rows: List[Row], lexemes_by_sentence: Dict[int, List[str]]) -> Dict[str, Any]:
    events = []
    by_intervention: Dict[str, Dict[str, Any]] = defaultdict(lambda: {
        "n": 0,
        "delta_norm_values": [],
        "delta_dichte_values": [],
        "cosine_shift_values": [],
        "dimension_delta_sums": {d: 0.0 for d in DIMENSIONS},
        "dominanzwechsel": 0,
        "polaritaetswechsel": 0,
        "examples": [],
    })

    # Vorher-Nachher-Folge je Lehrkraft/Gruppe/Teilnehmer. Falls Teilnehmer fehlt,
    # bleibt die Sequenz trotzdem ueber Gruppe/Lehrkraft/Datenreihenfolge stabil.
    grouped: Dict[Tuple[Any, Any, Any], List[Row]] = defaultdict(list)
    for row in rows:
        grouped[(row.lehrkraft_id, row.gruppe_id, row.teilnehmer_id)].append(row)

    for key, seq in grouped.items():
        seq = sorted(seq, key=lambda r: (
            r.datum or "",
            r.id_mtr_rueckkopplung_datenmaske,
            r.mtr_rueckkopplung_datenmaske_values_id or 0,
        ))
        for prev, curr in zip(seq, seq[1:]):
            sid = curr.mtr_rueckkopplung_datenmaske_values_id
            lexemes = lexemes_by_sentence.get(int(sid), []) if sid is not None else []
            interventions = detect_interventions(curr.bemerkung, lexemes)
            dvec = vector_delta(prev.vector, curr.vector)
            delta_n = euclidean_delta(prev.vector, curr.vector)
            delta_density = safe_float(curr.d_semantisch) - safe_float(prev.d_semantisch)
            cos_prev_curr = cosine_similarity(prev.vector, curr.vector)
            cosine_shift = None if cos_prev_curr is None else 1.0 - cos_prev_curr
            dom_change = int((prev.dominante_dimension or "") != (curr.dominante_dimension or ""))
            pol_change = int((prev.polaritaet_gesamt or 0) != (curr.polaritaet_gesamt or 0))

            event = {
                "scope": scope_name,
                "lehrkraft_id": curr.lehrkraft_id,
                "gruppe_id": curr.gruppe_id,
                "teilnehmer_id": curr.teilnehmer_id,
                "datum": curr.datum,
                "id_mtr_rueckkopplung_datenmaske": curr.id_mtr_rueckkopplung_datenmaske,
                "mtr_rueckkopplung_datenmaske_values_id": curr.mtr_rueckkopplung_datenmaske_values_id,
                "thema": curr.thema,
                "interventions": interventions,
                "delta_norm": delta_n,
                "delta_dichte": delta_density,
                "cosine_shift": cosine_shift,
                "dimension_delta": dvec,
                "dominanzwechsel": dom_change,
                "polaritaetswechsel": pol_change,
                "dominante_dimension_vorher": prev.dominante_dimension,
                "dominante_dimension_nachher": curr.dominante_dimension,
                "polaritaet_vorher": prev.polaritaet_gesamt,
                "polaritaet_nachher": curr.polaritaet_gesamt,
                "bemerkung": curr.bemerkung,
                "lexeme": lexemes,
            }
            events.append(event)

            for intervention in interventions:
                agg = by_intervention[intervention]
                agg["n"] += 1
                agg["delta_norm_values"].append(delta_n)
                agg["delta_dichte_values"].append(delta_density)
                if cosine_shift is not None:
                    agg["cosine_shift_values"].append(cosine_shift)
                for d in DIMENSIONS:
                    agg["dimension_delta_sums"][d] += dvec[d]
                agg["dominanzwechsel"] += dom_change
                agg["polaritaetswechsel"] += pol_change
                if len(agg["examples"]) < 5:
                    agg["examples"].append({
                        "datum": curr.datum,
                        "lehrkraft_id": curr.lehrkraft_id,
                        "delta_norm": delta_n,
                        "delta_dichte": delta_density,
                        "bemerkung": curr.bemerkung[:500],
                    })

    intervention_summary = {}
    for name, agg in by_intervention.items():
        n = agg["n"]
        intervention_summary[name] = {
            "n": n,
            "mean_delta_norm": sum(agg["delta_norm_values"]) / n if n else 0.0,
            "mean_abs_delta_dichte": sum(abs(x) for x in agg["delta_dichte_values"]) / n if n else 0.0,
            "mean_delta_dichte": sum(agg["delta_dichte_values"]) / n if n else 0.0,
            "mean_cosine_shift": (sum(agg["cosine_shift_values"]) / len(agg["cosine_shift_values"])) if agg["cosine_shift_values"] else None,
            "dominanzwechsel_rate": agg["dominanzwechsel"] / n if n else 0.0,
            "polaritaetswechsel_rate": agg["polaritaetswechsel"] / n if n else 0.0,
            "mean_dimension_delta": {d: agg["dimension_delta_sums"][d] / n if n else 0.0 for d in DIMENSIONS},
            "examples": agg["examples"],
        }

    ranked = sorted(
        intervention_summary.items(),
        key=lambda kv: (
            kv[1]["mean_delta_norm"],
            kv[1]["mean_abs_delta_dichte"],
            kv[1]["dominanzwechsel_rate"],
        ),
        reverse=True,
    )

    return {
        "scope": scope_name,
        "row_count": len(rows),
        "transition_count": len(events),
        "intervention_summary": intervention_summary,
        "ranking_by_effect_strength": [
            {"intervention": k, **v} for k, v in ranked
        ],
        "events": events,
    }


def main() -> None:
    conn = connect()
    try:
        cursor = conn.cursor(dictionary=True)

        scopes = {
            "alle_lehrkraefte": ("", ()),
            "lehrkraft_1": ("AND lehrkraft_id = %s", (1,)),
            "ohne_lehrkraft_1": ("AND (lehrkraft_id <> %s OR lehrkraft_id IS NULL)", (1,)),
        }

        output: Dict[str, Any] = {
            "auswertungspunkt": 23,
            "titel": "Didaktische Interventionssensitivitaet",
            "created_at": datetime.now().isoformat(timespec="seconds"),
            "db_config": {k: v for k, v in DB_CONFIG.items() if k != "password"},
            "dimensions": DIMENSIONS,
            "intervention_patterns": INTERVENTION_PATTERNS,
            "methodik": {
                "zustandsaenderung": "euklidische Distanz zwischen aufeinanderfolgenden FRZK-7D-Zustaenden je Lehrkraft/Gruppe/Teilnehmer",
                "effektstaerke": "mean_delta_norm, mean_abs_delta_dichte, mean_cosine_shift, Dominanzwechselrate und Polaritaetswechselrate pro Interventionskategorie",
                "hinweis": "Die Interventionskategorien werden heuristisch aus Bemerkung und Lexemen erkannt. Fuer eine spaetere Publikationsfassung kann daraus eine eigene Mapping-Tabelle entstehen.",
            },
            "scopes": {},
        }

        for scope_name, (where_sql, params) in scopes.items():
            rows = fetch_rows(cursor, where_sql, params)
            sentence_ids = [r.mtr_rueckkopplung_datenmaske_values_id for r in rows if r.mtr_rueckkopplung_datenmaske_values_id]
            lexemes = fetch_lexemes(cursor, sentence_ids)
            output["scopes"][scope_name] = summarize_scope(scope_name, rows, lexemes)

        OUTPUT_JSON.write_text(json.dumps(output, ensure_ascii=False, indent=2, default=json_default), encoding="utf-8")
        print(f"OK: JSON exportiert nach {OUTPUT_JSON.resolve()}")
    finally:
        conn.close()


if __name__ == "__main__":
    main()
