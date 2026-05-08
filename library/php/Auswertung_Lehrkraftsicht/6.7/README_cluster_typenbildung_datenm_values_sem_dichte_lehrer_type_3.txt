1. JSON-Export aus MySQL / MariaDB
---------------------------------
python export_datenm_values_sem_dichte_lehrer_type_3.py \
  --host localhost \
  --user root \
  --password "" \
  --database icas \
  --output datenm_export.json

Optional mit Filtern:
python export_datenm_values_sem_dichte_lehrer_type_3.py \
  --gruppe-id 4 \
  --lehrkraft-id 1 \
  --fach MAT \
  --date-from 2025-09-01 \
  --date-to 2026-03-31 \
  --output gruppe4_lk1_mat.json

2. Cluster- und Typenbildung
----------------------------
python cluster_typenbildung_datenm_values_sem_dichte_lehrer_type_3.py \
  --input datenm_export.json \
  --k 3 \
  --output-dir cluster_output

Erzeugte Dateien:
- cluster_output/cluster_typenbildung.json
- cluster_output/cluster_zuordnungen.csv
- cluster_output/cluster_pca.png
- cluster_output/cluster_zentren.png

Hinweis:
Die Typisierung erfolgt datenbasiert über das jeweilige Clusterzentrum im 7D-FRZK-Raum.
Standardlabels:
- kognitiv dominant
- sozial stabil
- affektiv negativ
Weitere Typen werden je nach dominanter Dimension automatisch ergänzt.
