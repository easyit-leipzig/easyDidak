#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Auswertungspunkt 24 – Gruppendynamik
Exportskript V2: neutrale gruppenbasierte Vollzeitraum-Datenbasis.

WICHTIG:
Dieses Skript verwendet analyze_lehrkraftdaten exakt als aggregierte Primärsicht.
Es erwartet die von Olaf Thiele angegebene View-Struktur:
- id_mtr_rueckkopplung_datenmaske
- datum
- teilnehmer_id
- lehrkraft_id
- gruppe_id
- satzanzahl
- mean_*, var_*, range_*
- semantische_breite
- d_semantisch_mean, d_semantisch_std
- polaritaet_index
- dominanz_breite

Das Skript trifft keine Ereignisannahme, keine Sonderannahme zu Gruppe 4
und keine Vorinterpretation einer Eskalation. Es erzeugt nur eine JSON-Basis
für:
1. alle Lehrkräfte
2. lehrkraft_id = 1
3. alle außer lehrkraft_id = 1
"""

from __future__ import annotations

import json
import math
import statistics
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

OUTPUT_FILE = Path("auswertung_24_gruppendynamik_neutral_basis.json")
SOURCE_VIEW = "analyze_lehrkraftdaten"

DIMENSIONS = [
    "kognition",
    "sozial",
    "affektiv",
    "motivation",
    "methodik",
    "performanz",
    "regulation",
]

SCOPES = {
    "alle_lehrkraefte": "1=1",
    "lehrkraft_1": "lehrkraft_id = 1",
    "ohne_lehrkraft_1": "lehrkraft_id <> 1",
}

REQUIRED_COLUMNS = [
    "id_mtr_rueckkopplung_datenmaske", "datum", "teilnehmer_id", "lehrkraft_id", "gruppe_id",
    "satzanzahl", "semantische_breite", "d_semantisch_mean", "d_semantisch_std",
    "polaritaet_index", "dominanz_breite",
]
for d in DIMENSIONS:
    REQUIRED_COLUMNS.extend([f"mean_{d}", f"var_{d}", f"range_{d}"])


def connect():
    return mysql.connector.connect(**DB_CONFIG)


def parse_dt(value: Any) -> Optional[str]:
    if value is None:
        return None
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    return str(value)


def safe_float(value: Any) -> Optional[float]:
    if value is None:
        return None
    try:
        value = float(value)
        if math.isnan(value) or math.isinf(value):
            return None
        return value
    except Exception:
        return None


def safe_int(value: Any) -> Optional[int]:
    if value is None:
        return None
    try:
        return int(value)
    except Exception:
        return None


def mean(values: Iterable[Any]) -> Optional[float]:
    xs = [safe_float(v) for v in values]
    xs = [x for x in xs if x is not None]
    return statistics.fmean(xs) if xs else None


def weighted_mean(pairs: Iterable[Tuple[Any, Any]]) -> Optional[float]:
    total_w = 0.0
    total = 0.0
    for value, weight in pairs:
        v = safe_float(value)
        w = safe_float(weight)
        if v is None:
            continue
        if w is None or w <= 0:
            w = 1.0
        total += v * w
        total_w += w
    return total / total_w if total_w else None


def stdev(values: Iterable[Any]) -> Optional[float]:
    xs = [safe_float(v) for v in values]
    xs = [x for x in xs if x is not None]
    return statistics.stdev(xs) if len(xs) >= 2 else None


def table_exists(cur, table_name: str) -> bool:
    cur.execute("SHOW TABLES LIKE %s", (table_name,))
    return cur.fetchone() is not None


def get_columns(cur, table_name: str) -> List[str]:
    cur.execute(f"SHOW COLUMNS FROM `{table_name}`")
    return [row["Field"] for row in cur.fetchall()]


def assert_source_view(cur) -> None:
    if not table_exists(cur, SOURCE_VIEW):
        raise RuntimeError(f"Die benötigte View/Tabelle `{SOURCE_VIEW}` existiert nicht.")
    available = set(get_columns(cur, SOURCE_VIEW))
    missing = [c for c in REQUIRED_COLUMNS if c not in available]
    if missing:
        raise RuntimeError(
            "Die Struktur von analyze_lehrkraftdaten passt nicht zur erwarteten aggregierten View. "
            f"Fehlende Spalten: {', '.join(missing)}"
        )


def fetch_scope_rows(cur, scope_where: str) -> List[Dict[str, Any]]:
    select_cols = [
        "id_mtr_rueckkopplung_datenmaske", "datum", "teilnehmer_id", "lehrkraft_id", "gruppe_id",
        "satzanzahl",
    ]
    for d in DIMENSIONS:
        select_cols += [f"mean_{d}", f"var_{d}", f"range_{d}"]
    select_cols += [
        "semantische_breite", "d_semantisch_mean", "d_semantisch_std",
        "polaritaet_index", "dominanz_breite",
    ]

    sql = f"""
        SELECT {', '.join('`' + c + '`' for c in select_cols)}
        FROM `{SOURCE_VIEW}`
        WHERE gruppe_id IS NOT NULL
          AND datum IS NOT NULL
          AND {scope_where}
        ORDER BY gruppe_id, datum, teilnehmer_id, lehrkraft_id, id_mtr_rueckkopplung_datenmaske
    """
    cur.execute(sql)
    rows = cur.fetchall() or []
    for r in rows:
        r["datum"] = parse_dt(r.get("datum"))
        for k in list(r.keys()):
            if k == "datum":
                continue
            if k in ["id_mtr_rueckkopplung_datenmaske", "teilnehmer_id", "lehrkraft_id", "gruppe_id", "satzanzahl", "dominanz_breite"]:
                r[k] = safe_int(r.get(k))
            else:
                r[k] = safe_float(r.get(k))
    return rows


def fetch_group_emotion_profiles(cur) -> Dict[str, Any]:
    if not table_exists(cur, "frzk_group_emotion"):
        return {}
    cur.execute("""
        SELECT id, gruppe_id, zeitpunkt, z_affektiv, `kohärenz` AS kohaerenz,
               stabilitaet, dynamik, emotionaler_status, emotionaler_modus, bemerkung
        FROM frzk_group_emotion
        WHERE gruppe_id IS NOT NULL
        ORDER BY gruppe_id, zeitpunkt, id
    """)
    rows = cur.fetchall() or []
    by_group: Dict[int, List[Dict[str, Any]]] = defaultdict(list)
    for r in rows:
        r["zeitpunkt"] = parse_dt(r.get("zeitpunkt"))
        r["gruppe_id"] = safe_int(r.get("gruppe_id"))
        for k in ["z_affektiv", "kohaerenz", "stabilitaet", "dynamik"]:
            r[k] = safe_float(r.get(k))
        if r["gruppe_id"] is not None:
            by_group[r["gruppe_id"]].append(r)

    out = {}
    for gid, grows in sorted(by_group.items()):
        times = [r["zeitpunkt"] for r in grows if r.get("zeitpunkt")]
        out[str(gid)] = {
            "n_messpunkte": len(grows),
            "zeitraum": {"von": min(times) if times else None, "bis": max(times) if times else None},
            "z_affektiv_mean": mean(r.get("z_affektiv") for r in grows),
            "kohaerenz_mean": mean(r.get("kohaerenz") for r in grows),
            "stabilitaet_mean": mean(r.get("stabilitaet") for r in grows),
            "dynamik_mean": mean(r.get("dynamik") for r in grows),
            "dynamik_max": max([r.get("dynamik") for r in grows if r.get("dynamik") is not None], default=None),
            "emotionaler_status_verteilung": dict(Counter(r.get("emotionaler_status") for r in grows if r.get("emotionaler_status"))),
            "emotionaler_modus_verteilung": dict(Counter(r.get("emotionaler_modus") for r in grows if r.get("emotionaler_modus"))),
            "timeline": grows,
        }
    return out


def aggregate_group(rows: List[Dict[str, Any]]) -> Dict[str, Any]:
    if not rows:
        return {}
    dates = sorted({r["datum"] for r in rows if r.get("datum")})
    satzgewicht = lambda r: r.get("satzanzahl") or 1

    dimension_means = {
        d: weighted_mean((r.get(f"mean_{d}"), satzgewicht(r)) for r in rows)
        for d in DIMENSIONS
    }
    dimension_variances = {
        d: weighted_mean((r.get(f"var_{d}"), satzgewicht(r)) for r in rows)
        for d in DIMENSIONS
    }
    dimension_ranges = {
        d: max([r.get(f"range_{d}") for r in rows if r.get(f"range_{d}") is not None], default=None)
        for d in DIMENSIONS
    }
    dimension_temporal_stdev = {
        d: stdev(r.get(f"mean_{d}") for r in rows)
        for d in DIMENSIONS
    }

    by_date: Dict[str, List[Dict[str, Any]]] = defaultdict(list)
    for r in rows:
        if r.get("datum"):
            by_date[r["datum"]].append(r)

    timeline = []
    for d, drows in sorted(by_date.items()):
        timeline.append({
            "datum": d,
            "n_records": len(drows),
            "satzanzahl_sum": sum((r.get("satzanzahl") or 0) for r in drows),
            "n_teilnehmer_beobachtet": len({r.get("teilnehmer_id") for r in drows if r.get("teilnehmer_id") is not None}),
            "n_lehrkraefte": len({r.get("lehrkraft_id") for r in drows if r.get("lehrkraft_id") is not None}),
            "dimension_means": {
                dim: weighted_mean((r.get(f"mean_{dim}"), satzgewicht(r)) for r in drows)
                for dim in DIMENSIONS
            },
            "dimension_variances": {
                dim: weighted_mean((r.get(f"var_{dim}"), satzgewicht(r)) for r in drows)
                for dim in DIMENSIONS
            },
            "semantische_breite_mean": weighted_mean((r.get("semantische_breite"), satzgewicht(r)) for r in drows),
            "d_semantisch_mean": weighted_mean((r.get("d_semantisch_mean"), satzgewicht(r)) for r in drows),
            "d_semantisch_std_mean": weighted_mean((r.get("d_semantisch_std"), satzgewicht(r)) for r in drows),
            "polaritaet_index": weighted_mean((r.get("polaritaet_index"), satzgewicht(r)) for r in drows),
            "dominanz_breite_mean": weighted_mean((r.get("dominanz_breite"), satzgewicht(r)) for r in drows),
        })

    participant_counter = Counter(str(r.get("teilnehmer_id")) for r in rows if r.get("teilnehmer_id") is not None)
    teacher_counter = Counter(str(r.get("lehrkraft_id")) for r in rows if r.get("lehrkraft_id") is not None)

    return {
        "zeitraum": {"von": dates[0] if dates else None, "bis": dates[-1] if dates else None},
        "n_records": len(rows),
        "satzanzahl_sum": sum((r.get("satzanzahl") or 0) for r in rows),
        "n_unterrichtseinheiten": len({r.get("id_mtr_rueckkopplung_datenmaske") for r in rows if r.get("id_mtr_rueckkopplung_datenmaske") is not None}),
        "n_termine": len(dates),
        "n_teilnehmer_beobachtet": len(participant_counter),
        "n_lehrkraefte": len(teacher_counter),
        "teilnehmer_verteilung_records": dict(participant_counter),
        "lehrkraft_verteilung_records": dict(teacher_counter),
        "dimension_means": dimension_means,
        "dimension_variances": dimension_variances,
        "dimension_ranges_max": dimension_ranges,
        "dimension_temporal_stdev": dimension_temporal_stdev,
        "semantische_breite_mean": weighted_mean((r.get("semantische_breite"), satzgewicht(r)) for r in rows),
        "semantische_breite_stdev": stdev(r.get("semantische_breite") for r in rows),
        "d_semantisch_mean": weighted_mean((r.get("d_semantisch_mean"), satzgewicht(r)) for r in rows),
        "d_semantisch_std_mean": weighted_mean((r.get("d_semantisch_std"), satzgewicht(r)) for r in rows),
        "polaritaet_index_mean": weighted_mean((r.get("polaritaet_index"), satzgewicht(r)) for r in rows),
        "polaritaet_index_stdev": stdev(r.get("polaritaet_index") for r in rows),
        "dominanz_breite_mean": weighted_mean((r.get("dominanz_breite"), satzgewicht(r)) for r in rows),
        "dominanz_breite_max": max([r.get("dominanz_breite") for r in rows if r.get("dominanz_breite") is not None], default=None),
        "timeline": timeline,
    }


def fetch_participant_counts(cur) -> List[Dict[str, Any]]:
    rows = fetch_scope_rows(cur, "1=1")
    by_group: Dict[int, set] = defaultdict(set)
    for r in rows:
        gid = r.get("gruppe_id")
        tid = r.get("teilnehmer_id")
        if gid is not None and tid is not None:
            by_group[int(gid)].add(int(tid))
    return [
        {"gruppe_id": gid, "teilnehmer_anzahl_beobachtet": len(tids)}
        for gid, tids in sorted(by_group.items())
    ]


def build_export() -> Dict[str, Any]:
    conn = connect()
    try:
        cur = conn.cursor(dictionary=True)
        assert_source_view(cur)

        export: Dict[str, Any] = {
            "meta": {
                "auswertungspunkt": 24,
                "titel": "Gruppendynamik – neutrale gruppenbasierte Vollzeitraumanalyse",
                "created_at": datetime.now().isoformat(timespec="seconds"),
                "source_view": SOURCE_VIEW,
                "db_config": {k: v for k, v in DB_CONFIG.items() if k != "password"},
                "dimensionen": DIMENSIONS,
                "methodischer_hinweis": (
                    "Primärbasis ist die aggregierte View analyze_lehrkraftdaten. "
                    "Exportiert werden alle Gruppen über den gesamten verfügbaren Zeitraum ohne Ereignisannahme. "
                    "Die Trennung erfolgt in alle Lehrkräfte, lehrkraft_id=1 und alle außer lehrkraft_id=1."
                ),
            },
            "participant_counts": fetch_participant_counts(cur),
            "group_emotion_profiles": fetch_group_emotion_profiles(cur),
            "scopes": {},
        }

        for scope_name, where_clause in SCOPES.items():
            rows = fetch_scope_rows(cur, where_clause)
            by_group: Dict[int, List[Dict[str, Any]]] = defaultdict(list)
            for r in rows:
                if r.get("gruppe_id") is not None:
                    by_group[int(r["gruppe_id"])].append(r)

            groups = {str(gid): aggregate_group(grows) for gid, grows in sorted(by_group.items())}
            export["scopes"][scope_name] = {
                "filter": where_clause,
                "n_records": len(rows),
                "satzanzahl_sum": sum((r.get("satzanzahl") or 0) for r in rows),
                "n_groups": len(groups),
                "groups": groups,
                "rows": rows,
            }

        return export
    finally:
        conn.close()


def main() -> None:
    data = build_export()
    OUTPUT_FILE.write_text(json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Export abgeschlossen: {OUTPUT_FILE.resolve()}")
    for scope, payload in data["scopes"].items():
        print(f"{scope}: {payload['n_records']} Records, {payload['satzanzahl_sum']} Sätze, {payload['n_groups']} Gruppen")


if __name__ == "__main__":
    main()
