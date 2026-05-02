import json
import math
from collections import Counter, defaultdict
from datetime import date, datetime

import pymysql


DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "icas",
    "charset": "utf8mb4",
    "cursorclass": pymysql.cursors.DictCursor,
}

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation",
]

DIMENSION_LABELS = {
    "x_kognition": "kognition",
    "x_sozial": "sozial",
    "x_affektiv": "affektiv",
    "x_motivation": "motivation",
    "x_methodik": "methodik",
    "x_performanz": "performanz",
    "x_regulation": "regulation",
}


def to_serializable(value):
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    if isinstance(value, float):
        if math.isnan(value) or math.isinf(value):
            return None
        return round(value, 6)
    return value


def compute_dominant_dimension(row):
    best_key = None
    best_val = None

    for dim in DIMENSIONS:
        val = float(row.get(dim) or 0.0)
        if best_val is None or abs(val) > abs(best_val):
            best_key = dim
            best_val = val

    return DIMENSION_LABELS[best_key], float(best_val or 0.0)


def compute_polarity(row):
    total = sum(float(row.get(dim) or 0.0) for dim in DIMENSIONS)
    if total > 0:
        return 1, total
    if total < 0:
        return -1, total
    return 0, total


def build_summary(rows):
    summary = {
        "anzahl_saetze": len(rows),
        "dominanzverteilung": {},
        "polaritaetsverteilung": {},
        "mittlere_dominanzstaerke": 0.0,
        "mittlere_semantische_dichte": 0.0,
        "gruppen": {},
        "lehrkraefte": {},
        "faecher": {},
    }

    dom_counter = Counter()
    pol_counter = Counter()
    dom_strengths = []
    densities = []

    by_group = defaultdict(list)
    by_teacher = defaultdict(list)
    by_subject = defaultdict(list)

    for row in rows:
        dom = row["berechnet"]["dominante_dimension"]
        pol = row["berechnet"]["polaritaet"]
        dom_strength = abs(float(row["berechnet"]["dominanzstaerke"]))
        density = float(row["berechnet"]["d_semantisch"])

        dom_counter[dom] += 1
        pol_counter[str(pol)] += 1
        dom_strengths.append(dom_strength)
        densities.append(density)

        by_group[str(row["gruppe_id"])].append(row)
        by_teacher[str(row["lehrkraft_id"])].append(row)
        by_subject[str(row["fach"])].append(row)

    summary["dominanzverteilung"] = dict(dom_counter)
    summary["polaritaetsverteilung"] = dict(pol_counter)
    summary["mittlere_dominanzstaerke"] = round(sum(dom_strengths) / len(dom_strengths), 6) if dom_strengths else 0.0
    summary["mittlere_semantische_dichte"] = round(sum(densities) / len(densities), 6) if densities else 0.0

    def aggregate_subset(subset_rows):
        if not subset_rows:
            return {}

        sub_dom = Counter(r["berechnet"]["dominante_dimension"] for r in subset_rows)
        sub_pol = Counter(str(r["berechnet"]["polaritaet"]) for r in subset_rows)
        sub_dom_strength = [abs(float(r["berechnet"]["dominanzstaerke"])) for r in subset_rows]
        sub_density = [float(r["berechnet"]["d_semantisch"]) for r in subset_rows]

        return {
            "n": len(subset_rows),
            "dominanzverteilung": dict(sub_dom),
            "polaritaetsverteilung": dict(sub_pol),
            "mittlere_dominanzstaerke": round(sum(sub_dom_strength) / len(sub_dom_strength), 6),
            "mittlere_semantische_dichte": round(sum(sub_density) / len(sub_density), 6),
        }

    summary["gruppen"] = {k: aggregate_subset(v) for k, v in by_group.items()}
    summary["lehrkraefte"] = {k: aggregate_subset(v) for k, v in by_teacher.items()}
    summary["faecher"] = {k: aggregate_subset(v) for k, v in by_subject.items()}

    return summary


def main():
    output_file = "dominanz_polarisierung_type3.json"

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
        FROM datenm_values_sem_dichte_lehrer_type_3 where lehrkraft_id<>1 
        ORDER BY datum ASC, id ASC
    """

    with pymysql.connect(**DB_CONFIG) as conn:
        with conn.cursor() as cursor:
            cursor.execute(sql)
            raw_rows = cursor.fetchall()

    export_rows = []

    for row in raw_rows:
        calc_dom, calc_dom_value = compute_dominant_dimension(row)
        calc_pol, calc_sum = compute_polarity(row)
        density = math.sqrt(sum((float(row.get(dim) or 0.0) ** 2) for dim in DIMENSIONS))

        export_row = {
            "id": row["id"],
            "gruppe_id": row["gruppe_id"],
            "teilnehmer_id": row["teilnehmer_id"],
            "fach": row["fach"],
            "datum": to_serializable(row["datum"]),
            "thema": row["thema"],
            "bemerkung": row["bemerkung"],
            "wochentag": row["wochentag"],
            "day_number": row["day_number"],
            "lehrkraft_id": row["lehrkraft_id"],
            "id_mtr_rueckkopplung_datenmaske": row["id_mtr_rueckkopplung_datenmaske"],
            "type": row["type"],
            "vektor": {
                "x_kognition": to_serializable(float(row["x_kognition"] or 0.0)),
                "x_sozial": to_serializable(float(row["x_sozial"] or 0.0)),
                "x_affektiv": to_serializable(float(row["x_affektiv"] or 0.0)),
                "x_motivation": to_serializable(float(row["x_motivation"] or 0.0)),
                "x_methodik": to_serializable(float(row["x_methodik"] or 0.0)),
                "x_performanz": to_serializable(float(row["x_performanz"] or 0.0)),
                "x_regulation": to_serializable(float(row["x_regulation"] or 0.0)),
            },
            "gespeichert_im_view": {
                "dominante_dimension": row["dominante_dimension"],
                "dominante_dimension_wert": to_serializable(float(row["dominante_dimension_wert"] or 0.0)),
                "polaritaet_gesamt": int(row["polaritaet_gesamt"] or 0),
                "d_semantisch": to_serializable(float(row["d_semantisch"] or 0.0)),
            },
            "berechnet": {
                "dominante_dimension": calc_dom,
                "dominanzstaerke": to_serializable(calc_dom_value),
                "polaritaet": calc_pol,
                "summe_dimensionen": to_serializable(calc_sum),
                "d_semantisch": to_serializable(density),
            },
            "konsistenzcheck": {
                "dominante_dimension_identisch": row["dominante_dimension"] == calc_dom,
                "dominanzwert_delta": to_serializable(float(row["dominante_dimension_wert"] or 0.0) - calc_dom_value),
                "polaritaet_identisch": int(row["polaritaet_gesamt"] or 0) == calc_pol,
                "d_semantisch_delta": to_serializable(float(row["d_semantisch"] or 0.0) - density),
            }
        }

        export_rows.append(export_row)

    payload = {
        "quelle": "datenm_values_sem_dichte_lehrer_type_3",
        "analyse": "6.x.4 Dominanz- und Polarisierungsanalyse",
        "formeln": {
            "dominante_dimension": "d(S_i) = argmax_j |x_j|",
            "dominanzstaerke": "delta(S_i) = max_j |x_j|",
            "polaritaet": "p(S_i) = sign(sum_{j=1}^{7} x_j)",
            "semantische_dichte": "||S_i||_2 = sqrt(sum_{j=1}^{7} x_j^2)"
        },
        "dimensionen": [DIMENSION_LABELS[d] for d in DIMENSIONS],
        "exportiert_am": datetime.now().isoformat(),
        "zusammenfassung": build_summary(export_rows),
        "daten": export_rows,
    }

    with open(output_file, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)

    print(f"JSON-Export abgeschlossen: {output_file}")
    print(f"Anzahl exportierter Sätze: {len(export_rows)}")


if __name__ == "__main__":
    main()