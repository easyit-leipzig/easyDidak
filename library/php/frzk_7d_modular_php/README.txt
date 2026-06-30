FRZK 7D Modular PHP v3
======================

Diese Version ist die vollständig überarbeitete v3 der modularen 7D-Teilnehmersicht.

Wesentliche Änderungen gegenüber v2:
1. Modul 01 berechnet getrennt:
   - V_S: Skalenvektor aus Rückkopplungswerten
   - V_E: Emotionsvektor aus _mtr_emotionen
   - V_F: finaler FRZK-Fusionszustand
2. Emotionen werden nicht mehr additiv in einzelne Dimensionen eingerechnet, sondern als 7D-Operator verarbeitet.
3. Die Fusion erfolgt über:
   V_F = V_S + alpha * V_E * exp(-beta * ||V_S||) - lambda * (V_S o V_E) + delta * cos(V_S,V_E) * V_E
4. Tabelle frzk_semantische_dichte_teilnehmer_7d speichert zusätzlich:
   - emotion_vector_*
   - skala_*
   - fusion_alpha, fusion_beta, fusion_lambda, fusion_delta
   - emotion_cosine
5. Tabelle frzk_group_semantische_dichte_7d speichert zusätzlich Gruppenmittel für:
   - mean_emotion_vector_*
   - mean_skala_*

Start:
  php run_7d.php
oder:
  php fill_7d_semantische_dichte_combined.php

Datenbank:
  host=127.0.0.1
  database=icas_19_4_2
  user=root
  password=
