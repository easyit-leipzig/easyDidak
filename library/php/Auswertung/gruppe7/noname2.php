<?php
  SELECT 
  max(kw),
  avg(x_kognition),  
  avg(x_sozial),  
  avg(x_affektiv),  
  avg(x_motivation),  
  avg(x_methodik),  
  avg(x_performanz),  
  avg(x_regulation)  
  
  FROM `datenm_values_sem_dichte_lehrer_type_3` WHERE teilnehmer_id in (
  select teilnehmer_ids from tn_per_gruppe where gruppe_id=7
  ) and datum>'2025-09-01' and lehrkraft_id=1 and (fach="MAT" or fach="PHY") group by kw order by datum
  
      id    int(11)            Nein    0        
    2    gruppe_id    int(11)            Nein    kein(e)        
    3    teilnehmer_id    int(11)            Nein    kein(e)        
    4    fach    varchar(10)    utf8mb4_general_ci        Ja    NULL        
    5    datum    date            Ja    NULL        
    6    thema    varchar(255)    utf8mb4_general_ci        Ja    NULL        
    7    bemerkung    text    utf8mb4_general_ci        Ja    NULL        
    8    wochentag    tinyint(4)            Ja    NULL        
    9    day_number    tinyint(4)            Ja    NULL        
    10    lehrkraft_id    int(11)            Ja    NULL        
    11    id_mtr_rueckkopplung_datenmaske    int(11)            Nein    kein(e)        
    12    type    int(1)            Nein    0        
    13    x_kognition    double            Ja    NULL        
    14    x_sozial    double            Ja    NULL        
    15    x_affektiv    double            Ja    NULL        
    16    x_motivation    double            Ja    NULL        
    17    x_methodik    double            Ja    NULL        
    18    x_performanz    double            Ja    NULL        
    19    x_regulation    double            Ja    NULL        
    20    dominante_dimension    varchar(10)    utf8mb4_unicode_ci        Ja    NULL        
    21    dominante_dimension_wert    double            Ja    NULL        
    22    polaritaet_gesamt    int(2)            Ja    NULL        
    23    d_semantisch    double            Ja    NULL    
  
  
  ?>
