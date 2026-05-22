# -*- coding: utf-8 -*-
"""
15. Dominanzwechsel-Analyse – Export-Skript

Erzeugt eine JSON-Datei mit drei Auswertungssichten:
1. alle_lehrkraefte
2. lehrkraft_1
3. ohne_lehrkraft_1

Datenbasis: analyze_lehrkraftdaten
Zielgröße: Wechsel der dominanten semantischen Dimension über zeitlich geordnete Unterrichts-/Bewertungseinheiten.
"""

from __future__ import annotations

import json
from collections import Counter, defaultdict
from datetime import date, datetime
from pathlib import Path
from typing import Any, Dict, Iterable, List, Optional, Tuple

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

OUTPUT_JSON = Path("auswertung_15_dominanzwechsel.json")

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

SQL_BASE = """
SELECT
    id,
    gruppe_id,
    teilnehmer_id,
    fach,
    datum,
    thema,
    wochentag,
    day_number,
    lehrkraft_id,
    id_mtr_rueckkopplung_datenmaske,
    mtr_rueckkopplung_datenmaske_values_id,
    x_kognition,
    x_sozial,
    x_affektiv,
    x_motivation,
    x_methodik,
    x_performanz,
    x_regulation,
    sum_kognition,
    sum_sozial,
    sum_affektiv,
    sum_motivation,
    sum_methodik,
    sum_performanz,
    sum_regulation,
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
WHERE dominante_dimension IS NOT NULL
  AND dominante_dimension <> ''
{where_clause}
ORDER BY
    lehrkraft_id ASC,
    gruppe_id ASC,
    teilnehmer_id ASC,
    datum ASC,
    id_mtr_rueckkopplung_datenmaske ASC,
    mtr_rueckkopplung_datenmaske_values_id ASC,
    id ASC
"""


def json_default(value: Any) -> Any:
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    return str(value)


def fetch_rows(where_clause: str = "", params: Optional[Tuple[Any, ...]] = None) -> List[Dict[str, Any]]:
    query = SQL_BASE.format(where_clause=where_clause)
    conn = mysql.connector.connect(**DB_CONFIG)
    try:
        cur = conn.cursor(dictionary=True)
        cur.execute(query, params or ())
        rows = list(cur.fetchall())
    finally:
        conn.close()
    return rows


def normalize_dimension(value: Any) -> Optional[str]:
    if value is None:
        return None
    value = str(value).strip().lower()
    aliases = {
        "selbstregulation": "regulation",
        "sozial_interaktion": "sozial",
        "soziale_interaktion": "sozial",
    }
    value = aliases.get(value, value)
    return value if value in DIMENSIONS else value


def transition_key(row: Dict[str, Any]) -> Tuple[Any, Any, Any]:
    """
    Sequenzen werden zunächst je Lehrkraft, Gruppe und Teilnehmer gebildet.
    Dadurch entstehen Dominanzwechsel innerhalb konkreter Nachhilfeverläufe.
    """
    return (row.get("lehrkraft_id"), row.get("gruppe_id"), row.get("teilnehmer_id"))


def make_transitions(rows: Iterable[Dict[str, Any]]) -> List[Dict[str, Any]]:
    grouped: Dict[Tuple[Any, Any, Any], List[Dict[str, Any]]] = defaultdict(list)
    for row in rows:
        row["dominante_dimension"] = normalize_dimension(row.get("dominante_dimension"))
        grouped[transition_key(row)].append(row)

    transitions: List[Dict[str, Any]] = []
    for key, seq in grouped.items():
        seq = sorted(
            seq,
            key=lambda r: (
                r.get("datum") or date.min,
                r.get("id_mtr_rueckkopplung_datenmaske") or 0,
                r.get("mtr_rueckkopplung_datenmaske_values_id") or 0,
                r.get("id") or 0,
            ),
        )
        for idx in range(len(seq) - 1):
            a = seq[idx]
            b = seq[idx + 1]
            d_from = a.get("dominante_dimension")
            d_to = b.get("dominante_dimension")
            if not d_from or not d_to:
                continue
            transitions.append(
                {
                    "sequence_key": {
                        "lehrkraft_id": key[0],
                        "gruppe_id": key[1],
                        "teilnehmer_id": key[2],
                    },
                    "from": d_from,
                    "to": d_to,
                    "is_change": d_from != d_to,
                    "from_date": a.get("datum"),
                    "to_date": b.get("datum"),
                    "from_record_id": a.get("id"),
                    "to_record_id": b.get("id"),
                    "from_sentence_id": a.get("mtr_rueckkopplung_datenmaske_values_id"),
                    "to_sentence_id": b.get("mtr_rueckkopplung_datenmaske_values_id"),
                    "from_value": float(a.get("dominante_dimension_wert") or 0.0),
                    "to_value": float(b.get("dominante_dimension_wert") or 0.0),
                    "delta_dominance_value": float(b.get("dominante_dimension_wert") or 0.0)
                    - float(a.get("dominante_dimension_wert") or 0.0),
                    "from_polarity": a.get("polaritaet_gesamt"),
                    "to_polarity": b.get("polaritaet_gesamt"),
                    "polarity_change": a.get("polaritaet_gesamt") != b.get("polaritaet_gesamt"),
                    "from_d_semantisch": float(a.get("d_semantisch") or 0.0),
                    "to_d_semantisch": float(b.get("d_semantisch") or 0.0),
                    "delta_d_semantisch": float(b.get("d_semantisch") or 0.0)
                    - float(a.get("d_semantisch") or 0.0),
                    "from_operator_count": int(a.get("operator_count") or 0),
                    "to_operator_count": int(b.get("operator_count") or 0),
                    "from_has_operator": int(a.get("has_operator") or 0),
                    "to_has_operator": int(b.get("has_operator") or 0),
                }
            )
    return transitions


def summarize(rows: List[Dict[str, Any]], transitions: List[Dict[str, Any]]) -> Dict[str, Any]:
    dimensions = [normalize_dimension(r.get("dominante_dimension")) for r in rows]
    dimension_counts = Counter(d for d in dimensions if d)
    transition_counts = Counter(f"{t['from']}->{t['to']}" for t in transitions)
    change_counts = Counter(f"{t['from']}->{t['to']}" for t in transitions if t["is_change"])
    stays = sum(1 for t in transitions if not t["is_change"])
    changes = sum(1 for t in transitions if t["is_change"])
    total = len(transitions)

    by_from: Dict[str, Counter] = {d: Counter() for d in DIMENSIONS}
    for t in transitions:
        by_from.setdefault(t["from"], Counter())[t["to"]] += 1

    markov_counts = {
        d_from: {d_to: int(by_from.get(d_from, Counter()).get(d_to, 0)) for d_to in DIMENSIONS}
        for d_from in DIMENSIONS
    }
    markov_probabilities = {}
    for d_from in DIMENSIONS:
        row_sum = sum(markov_counts[d_from].values())
        markov_probabilities[d_from] = {
            d_to: (markov_counts[d_from][d_to] / row_sum if row_sum else 0.0)
            for d_to in DIMENSIONS
        }

    return {
        "n_records": len(rows),
        "n_sequences": len({transition_key(r) for r in rows}),
        "n_transitions": total,
        "n_changes": changes,
        "n_stays": stays,
        "change_rate": changes / total if total else 0.0,
        "stability_rate": stays / total if total else 0.0,
        "dimension_counts": dict(dimension_counts),
        "transition_counts": dict(transition_counts),
        "change_counts_only": dict(change_counts),
        "top_transitions": transition_counts.most_common(20),
        "top_changes": change_counts.most_common(20),
        "markov_counts": markov_counts,
        "markov_probabilities": markov_probabilities,
    }


def build_scope(name: str, where_clause: str = "", params: Optional[Tuple[Any, ...]] = None) -> Dict[str, Any]:
    rows = fetch_rows(where_clause, params)
    transitions = make_transitions(rows)
    return {
        "scope": name,
        "filters": {"where_clause": where_clause.strip(), "params": list(params or ())},
        "summary": summarize(rows, transitions),
        "records": rows,
        "transitions": transitions,
    }


def main() -> None:
    export = {
        "analysis_id": "15_dominanzwechsel_analyse",
        "title": "15. Dominanzwechsel-Analyse",
        "description": "Markov-Übergänge der dominanten semantischen Dimension in der Lehrkraftsicht.",
        "database": DB_CONFIG["database"],
        "source_view": "analyze_lehrkraftdaten",
        "dimension_order": DIMENSIONS,
        "generated_at": datetime.now().isoformat(timespec="seconds"),
        "scopes": {
            "alle_lehrkraefte": build_scope("alle_lehrkraefte"),
            "lehrkraft_1": build_scope("lehrkraft_1", "AND lehrkraft_id = %s", (1,)),
            "ohne_lehrkraft_1": build_scope("ohne_lehrkraft_1", "AND (lehrkraft_id IS NULL OR lehrkraft_id <> %s)", (1,)),
        },
    }

    OUTPUT_JSON.write_text(
        json.dumps(export, ensure_ascii=False, indent=2, default=json_default),
        encoding="utf-8",
    )
    print(f"JSON erzeugt: {OUTPUT_JSON.resolve()}")
    for scope_name, scope in export["scopes"].items():
        s = scope["summary"]
        print(
            f"{scope_name}: Datensätze={s['n_records']} | Sequenzen={s['n_sequences']} | "
            f"Übergänge={s['n_transitions']} | Wechselquote={s['change_rate']:.3f}"
        )


if __name__ == "__main__":
    main()
