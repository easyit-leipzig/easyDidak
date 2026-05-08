import argparse
import json
from pathlib import Path
from typing import Any, Dict, List

import mysql.connector


DEFAULT_DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Exportiert datenm_values_sem_dichte_lehrer_type_3 als JSON."
    )
    parser.add_argument("--host", default="localhost")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="")
    parser.add_argument("--database", default="icas")
    parser.add_argument(
        "--output",
        default="datenm_export.json",
        help="Zieldatei für den JSON-Export",
    )
    parser.add_argument("--gruppe-id", type=int, default=None)
    parser.add_argument("--lehrkraft-id", type=int, default=None)
    parser.add_argument("--fach", default=None, help="z. B. MAT oder PHY")
    parser.add_argument("--date-from", default=None, help="Format YYYY-MM-DD")
    parser.add_argument("--date-to", default=None, help="Format YYYY-MM-DD")
    parser.add_argument("--limit", type=int, default=None)
    return parser.parse_args()



def build_query(args: argparse.Namespace) -> tuple[str, List[Any]]:
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
            type,
            x_kognition,
            x_sozial,
            x_affektiv,
            x_motivation,
            x_methodik,
            x_performanz,
            x_regulation,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch
        FROM datenm_values_sem_dichte_lehrer_type_3
        WHERE 1 = 1 and datum>='2025-09-01' and (fach='MAT' or fach='PHY') and weekofyear(datum)<>44 and lehrkraft_id<>1 
    """
    params: List[Any] = []

    if args.gruppe_id is not None:
        sql += " AND gruppe_id = %s"
        params.append(args.gruppe_id)
    if args.lehrkraft_id is not None:
        sql += " AND lehrkraft_id = %s"
        params.append(args.lehrkraft_id)
    if args.fach:
        sql += " AND fach = %s"
        params.append(args.fach)
    if args.date_from:
        sql += " AND datum >= %s"
        params.append(args.date_from)
    if args.date_to:
        sql += " AND datum <= %s"
        params.append(args.date_to)

    sql += " ORDER BY datum ASC, id ASC"

    if args.limit is not None:
        sql += " LIMIT %s"
        params.append(args.limit)

    return sql, params



def normalize_row(row: Dict[str, Any]) -> Dict[str, Any]:
    out: Dict[str, Any] = {}
    for key, value in row.items():
        if hasattr(value, "isoformat"):
            out[key] = value.isoformat()
        elif isinstance(value, float):
            out[key] = round(value, 6)
        else:
            out[key] = value
    return out



def build_metadata(rows: List[Dict[str, Any]]) -> Dict[str, Any]:
    gruppen = sorted({r["gruppe_id"] for r in rows if r["gruppe_id"] is not None})
    lehrkraefte = sorted({r["lehrkraft_id"] for r in rows if r["lehrkraft_id"] is not None})
    faecher = sorted({r["fach"] for r in rows if r["fach"]})
    daten = [r["datum"] for r in rows if r.get("datum")]

    return {
        "anzahl_datensaetze": len(rows),
        "dimensionen": DEFAULT_DIMENSIONS,
        "gruppen_ids": gruppen,
        "lehrkraft_ids": lehrkraefte,
        "faecher": faecher,
        "zeitraum": {
            "von": min(daten) if daten else None,
            "bis": max(daten) if daten else None,
        },
    }



def main() -> None:
    args = parse_args()
    output_path = Path(args.output)

    conn = mysql.connector.connect(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
    )

    try:
        cursor = conn.cursor(dictionary=True)
        sql, params = build_query(args)
        cursor.execute(sql, params)
        rows = [normalize_row(row) for row in cursor.fetchall()]
    finally:
        conn.close()

    payload = {
        "quelle": "datenm_values_sem_dichte_lehrer_type_3",
        "filter": {
            "gruppe_id": args.gruppe_id,
            "lehrkraft_id": args.lehrkraft_id,
            "fach": args.fach,
            "date_from": args.date_from,
            "date_to": args.date_to,
            "limit": args.limit,
        },
        "metadaten": build_metadata(rows),
        "daten": rows,
    }

    output_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Export abgeschlossen: {output_path.resolve()}")
    print(f"Datensätze: {len(rows)}")


if __name__ == "__main__":
    main()
