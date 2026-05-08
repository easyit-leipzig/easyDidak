import pymysql
import json
from datetime import date, datetime

conn = pymysql.connect(
    host="localhost",
    user="root",
    password="",
    database="icas",
    charset="utf8mb4",
    cursorclass=pymysql.cursors.DictCursor
)

cursor = conn.cursor()

sql = """
SELECT 
    id,
    datum,
    teilnehmer_id,
    lehrkraft_id,
    x_kognition,
    x_sozial,
    x_affektiv,
    x_motivation,
    x_methodik,
    x_performanz,
    x_regulation
FROM frzk_semantische_dichte_lehrer_gesamt where type=2 and (fach='MAT' or fach='PHY')
ORDER BY datum ASC
"""

cursor.execute(sql)
rows = cursor.fetchall()

# 🔥 FIX: Datum serialisierbar machen
for row in rows:
    if isinstance(row["datum"], (date, datetime)):
        row["datum"] = row["datum"].isoformat()

with open("frzk_6x9_daten.json", "w", encoding="utf-8") as f:
    json.dump(rows, f, indent=2, ensure_ascii=False)

print("JSON exportiert")

cursor.close()
conn.close()