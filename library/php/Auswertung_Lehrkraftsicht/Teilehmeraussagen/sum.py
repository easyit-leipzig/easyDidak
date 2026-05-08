# export_sem_d_lehrer_summe.py

import json
import math
import pymysql
from datetime import datetime
from pathlib import Path

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

OUTPUT_FILE = "sem_d_lehrer_summe.json"


def to_float(v):
    try:
        return float(v)
    except:
        return 0.0


def norm(vec):
    return math.sqrt(sum(v * v for v in vec.values()))


def sum_vector(rows):
    result = {d: 0.0 for d in DIMENSIONS}
    for r in rows:
        for d in DIMENSIONS:
            result[d] += to_float(r.get(d))
    return result


def aggregate(rows):
    vec = sum_vector(rows)

    return {
        "n": len(rows),
        "sum_vector": vec,
        "norm_sum": norm(vec),
        "sum_d_semantisch": sum(to_float(r.get("d_semantisch")) for r in rows),
    }


def main():

    sql = f"""
        SELECT
            lehrkraft_id,
            {", ".join(DIMENSIONS)},
            d_semantisch
        FROM sem_d_lehrer_datenmaske
        WHERE lehrkraft_id IS NOT NULL
    """

    conn = pymysql.connect(**DB_CONFIG)

    with conn.cursor() as cursor:
        cursor.execute(sql)
        rows = cursor.fetchall()

    conn.close()

    lk1 = [r for r in rows if int(r["lehrkraft_id"]) == 1]
    andere = [r for r in rows if int(r["lehrkraft_id"]) != 1]

    output = {
        "timestamp": datetime.now().isoformat(),
        "gesamt": aggregate(rows),
        "lehrkraft_1": aggregate(lk1),
        "andere": aggregate(andere),
    }

    Path(OUTPUT_FILE).write_text(
        json.dumps(output, indent=2, ensure_ascii=False)
    )

    print("Fertig:", OUTPUT_FILE)


if __name__ == "__main__":
    main()