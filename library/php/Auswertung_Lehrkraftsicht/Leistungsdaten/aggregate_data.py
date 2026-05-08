import pymysql
import json
import numpy as np

CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "icas"
}

DIMENSIONS = [
    "x_kognition",
    "x_sozial",
    "x_affektiv",
    "x_motivation",
    "x_methodik",
    "x_performanz",
    "x_regulation"
]

def fetch_data(where_clause="1=1"):
    conn = pymysql.connect(**CONFIG)
    cursor = conn.cursor(pymysql.cursors.DictCursor)

    sql = f"""
        SELECT 
            *,
            YEAR(datum)*100 + WEEK(datum, 1) AS kw
        FROM datenm_values_sem_dichte_lehrer_type_3
        WHERE {where_clause} and datum<'2025-09-01' and lehrkraft_id=1 
        ORDER BY gruppe_id, kw
    """
    cursor.execute(sql)
    rows = cursor.fetchall()
    conn.close()
    return rows


def group_data(rows):
    groups = {}
    for r in rows:
        key = (r["gruppe_id"], r["kw"])
        groups.setdefault(key, []).append(r)
    return groups


def compute_metrics(group_rows):
    n = len(group_rows)

    vectors = np.array([[r[d] for d in DIMENSIONS] for r in group_rows])
    mean_vec = np.mean(vectors, axis=0)

    std_vec = np.std(vectors, axis=0)
    polarisierungsindex = float(np.mean(std_vec))

    norm_mean = np.linalg.norm(mean_vec)
    norm_avg = np.mean([np.linalg.norm(v) for v in vectors])
    kohärenzindex = float(norm_mean / norm_avg) if norm_avg != 0 else 0

    dist = np.mean([np.linalg.norm(v - mean_vec) for v in vectors])
    stabilitaetsindex = float(1 / (1 + dist))

    d_sem = float(np.mean([r["d_semantisch"] for r in group_rows]))

    dom_idx = int(np.argmax(np.abs(mean_vec)))
    dominante_dimension = DIMENSIONS[dom_idx].replace("x_", "")

    pol = [r["polaritaet_gesamt"] for r in group_rows]
    polaritaet_positiv = pol.count(1) / n
    polaritaet_neutral = pol.count(0) / n
    polaritaet_negativ = pol.count(-1) / n

    result = {
        "gruppe_id": group_rows[0]["gruppe_id"],
        "kw": group_rows[0]["kw"],
        "n": n,
        "d_semantisch_mittel": d_sem,
        "kohärenzindex": kohärenzindex,
        "stabilitaetsindex": stabilitaetsindex,
        "polarisierungsindex": polarisierungsindex,
        "dominante_dimension": dominante_dimension,
        "polaritaet_positiv": polaritaet_positiv,
        "polaritaet_neutral": polaritaet_neutral,
        "polaritaet_negativ": polaritaet_negativ
    }

    for i, d in enumerate(DIMENSIONS):
        result[d.replace("x_", "")] = float(mean_vec[i])

    return result


def build_block(rows):
    grouped = group_data(rows)
    summary = []

    for key in sorted(grouped.keys()):
        summary.append(compute_metrics(grouped[key]))

    return {
        "summary_table": summary
    }


def main():
    # --- drei Datensichten ---
    rows_all = fetch_data()
    rows_lk1 = fetch_data("lehrkraft_id = 1")
    rows_not_lk1 = fetch_data("lehrkraft_id <> 1")

    payload = {
        "meta": {
            "quelle": "datenm_values_sem_dichte_lehrer_type_3"
        },
        "alle": build_block(rows_all),
        "lehrkraft_1": build_block(rows_lk1),
        "nicht_lehrkraft_1": build_block(rows_not_lk1)
    }

    with open("frzk_output.json", "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=2)


if __name__ == "__main__":
    main()