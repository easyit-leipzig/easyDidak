
FRZK Grid-Search Clusteranalyse

Datei:
- frzk_cluster_gridsearch.py

Zweck:
- dimensionsspezifische Gewichtung der 7 FRZK-Dimensionen
- Grid-Search über Gewichtungen 1..10
- K-Means für mehrere Clusterzahlen
- automatische Auswahl des besten Settings

Wichtige Idee:
- Ein globaler Faktor für alle Dimensionen ändert die Clustergeometrie nicht.
- Nur anisotrope Gewichtung (unterschiedliche Faktoren pro Dimension / Dimensionsgruppen) verändert die Metrik sinnvoll.

Beispiel 1: interpretierbare 3-Gruppen-Gewichtung
python frzk_cluster_gridsearch.py ^
  --input datenm_export.json ^
  --output-dir out_grouped ^
  --mode grouped ^
  --k-min 3 --k-max 8 ^
  --weight-min 1 --weight-max 10 ^
  --step 1

Dabei gelten:
- struktur = kognition + methodik + performanz
- sozio_affektiv = sozial + affektiv
- selbststeuerung = motivation + regulation

Beispiel 2: vollständige 7-dimensionale Gewichtung
Achtung: sehr viele Kombinationen.
python frzk_cluster_gridsearch.py ^
  --input datenm_export.json ^
  --output-dir out_full ^
  --mode full ^
  --k-min 3 --k-max 6 ^
  --weight-min 1 --weight-max 3 ^
  --step 1 ^
  --max-combinations 5000

Ausgaben:
- gridsearch_ergebnisse.csv
- gridsearch_top_settings.csv
- bestes_setting_clusterprofile.csv
- bestes_setting_clusterzuordnung.csv
- bestes_setting_clusterzentren_gewichteter_raum.csv
- bestes_setting_cluster_pca.png
- summary.json
