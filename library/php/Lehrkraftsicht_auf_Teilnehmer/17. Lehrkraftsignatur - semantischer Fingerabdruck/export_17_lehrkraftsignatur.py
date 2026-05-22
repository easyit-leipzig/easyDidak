import json
import math
import mysql.connector
from pathlib import Path
from collections import Counter, defaultdict
from statistics import mean, pstdev

DB_CONFIG = {
    "host": "127.0.0.1",
    "port": 3306,
    "user": "root",
    "password": "",
    "database": "icas_19_4_2",
    "charset": "utf8mb4",
    "connection_timeout": 5,
    "use_pure": True
}

OUTPUT_FILE = Path("auswertung_17_lehrkraftsignatur.json")

DIMENSIONS = [
    "x_kognition", "x_sozial", "x_affektiv", "x_motivation",
    "x_methodik", "x_performanz", "x_regulation"
]


def safe_float(v):
    return float(v) if v is not None else 0.0


def fetch_rows(where_clause="", params=None):
    sql = f"""
        SELECT
            id,
            lehrkraft_id,
            gruppe_id,
            teilnehmer_id,
            fach,
            datum,
            thema,
            dominante_dimension,
            dominante_dimension_wert,
            polaritaet_gesamt,
            d_semantisch,
            operator_count,
            modulator_count,
            has_operator,
            operator_names,
            {", ".join(DIMENSIONS)}
        FROM analyze_lehrkraftdaten
        {where_clause}
        ORDER BY lehrkraft_id, datum, id
    """

    con = mysql.connector.connect(**DB_CONFIG)
    cur = con.cursor(dictionary=True)
    cur.execute(sql, params or [])
    rows = cur.fetchall()
    cur.close()
    con.close()
    return rows


def cosine_distance(a, b):
    dot = sum(x * y for x, y in zip(a, b))
    na = math.sqrt(sum(x * x for x in a))
    nb = math.sqrt(sum(y * y for y in b))
    if na == 0 or nb == 0:
        return 0.0
    return 1.0 - (dot / (na * nb))


def summarize_scope(rows):
    by_teacher = defaultdict(list)

    for r in rows:
        by_teacher[r["lehrkraft_id"]].append(r)

    teacher_profiles = []

    for lehrkraft_id, items in by_teacher.items():
        vectors = [[safe_float(r[d]) for d in DIMENSIONS] for r in items]

        mean_vector = [
            mean([v[i] for v in vectors]) if vectors else 0.0
            for i in range(len(DIMENSIONS))
        ]

        std_vector = [
            pstdev([v[i] for v in vectors]) if len(vectors) > 1 else 0.0
            for i in range(len(DIMENSIONS))
        ]

        density_values = [safe_float(r["d_semantisch"]) for r in items]
        polarity_values = [safe_float(r["polaritaet_gesamt"]) for r in items]

        dominant_counter = Counter(
            r["dominante_dimension"] for r in items if r["dominante_dimension"]
        )

        operators = []
        for r in items:
            if r["operator_names"]:
                operators.extend([
                    x.strip() for x in str(r["operator_names"]).split(",") if x.strip()
                ])

        operator_counter = Counter(operators)

        drift_values = []
        for i in range(len(vectors) - 1):
            drift_values.append(cosine_distance(vectors[i], vectors[i + 1]))

        dominance_changes = 0
        dom_seq = [r["dominante_dimension"] for r in items if r["dominante_dimension"]]
        for i in range(len(dom_seq) - 1):
            if dom_seq[i] != dom_seq[i + 1]:
                dominance_changes += 1

        signature = {
            "lehrkraft_id": lehrkraft_id,
            "n_records": len(items),
            "mean_vector": dict(zip(DIMENSIONS, mean_vector)),
            "std_vector": dict(zip(DIMENSIONS, std_vector)),
            "mean_semantic_density": mean(density_values) if density_values else 0.0,
            "std_semantic_density": pstdev(density_values) if len(density_values) > 1 else 0.0,
            "mean_polarity": mean(polarity_values) if polarity_values else 0.0,
            "dominant_dimensions": dict(dominant_counter),
            "primary_dominant_dimension": dominant_counter.most_common(1)[0][0] if dominant_counter else None,
            "operator_usage": dict(operator_counter),
            "operator_total": sum(operator_counter.values()),
            "mean_operator_count": mean([safe_float(r["operator_count"]) for r in items]) if items else 0.0,
            "mean_modulator_count": mean([safe_float(r["modulator_count"]) for r in items]) if items else 0.0,
            "mean_drift": mean(drift_values) if drift_values else 0.0,
            "std_drift": pstdev(drift_values) if len(drift_values) > 1 else 0.0,
            "dominance_change_count": dominance_changes,
            "dominance_change_rate": dominance_changes / max(1, len(dom_seq) - 1)
        }

        teacher_profiles.append(signature)

    return {
        "n_rows": len(rows),
        "n_teachers": len(by_teacher),
        "teacher_profiles": teacher_profiles
    }


def main():
    scopes = {
        "alle_lehrkraefte": fetch_rows(),
        "lehrkraft_1": fetch_rows("WHERE lehrkraft_id = %s", [1]),
        "ohne_lehrkraft_1": fetch_rows("WHERE lehrkraft_id <> %s", [1])
    }

    output = {
        "auswertungspunkt": "17. Lehrkraftsignatur / semantischer Fingerabdruck",
        "beschreibung": "Semantische Signatur jeder Lehrkraft als stabiler Zustandsoperator im FRZK-Raum.",
        "scopes": {
            name: summarize_scope(rows)
            for name, rows in scopes.items()
        }
    }

    OUTPUT_FILE.write_text(json.dumps(output, ensure_ascii=False, indent=2), encoding="utf-8")
    print(f"Export abgeschlossen: {OUTPUT_FILE}")


if __name__ == "__main__":
    main()