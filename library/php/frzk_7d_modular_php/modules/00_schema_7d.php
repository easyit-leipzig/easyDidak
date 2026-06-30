<?php
function frzk7d_schema(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_semantische_dichte_teilnehmer_7d (
        id INT AUTO_INCREMENT PRIMARY KEY,
        rueckkopplung_teilnehmer_id INT NULL,
        ue_id INT NULL,
        ue_zuweisung_teilnehmer_id INT NULL,
        teilnehmer_id INT NOT NULL,
        gruppe_id INT NULL,
        zeitpunkt DATETIME NOT NULL,
        x_kognition DOUBLE DEFAULT 0, x_sozial DOUBLE DEFAULT 0, x_affektiv DOUBLE DEFAULT 0,
        x_motivation DOUBLE DEFAULT 0, x_methodik DOUBLE DEFAULT 0, x_performanz DOUBLE DEFAULT 0, x_regulation DOUBLE DEFAULT 0,
        sum_kognition DOUBLE DEFAULT 0, sum_sozial DOUBLE DEFAULT 0, sum_affektiv DOUBLE DEFAULT 0,
        sum_motivation DOUBLE DEFAULT 0, sum_methodik DOUBLE DEFAULT 0, sum_performanz DOUBLE DEFAULT 0, sum_regulation DOUBLE DEFAULT 0,
        emotion_ids TEXT NULL, emotion_valenz DOUBLE NULL, emotion_aktivierung DOUBLE NULL, emotion_anzahl INT DEFAULT 0,
        emotion_vector_kognition DOUBLE DEFAULT 0, emotion_vector_sozial DOUBLE DEFAULT 0, emotion_vector_affektiv DOUBLE DEFAULT 0,
        emotion_vector_motivation DOUBLE DEFAULT 0, emotion_vector_methodik DOUBLE DEFAULT 0, emotion_vector_performanz DOUBLE DEFAULT 0, emotion_vector_regulation DOUBLE DEFAULT 0,
        skala_kognition DOUBLE DEFAULT 0, skala_sozial DOUBLE DEFAULT 0, skala_affektiv DOUBLE DEFAULT 0,
        skala_motivation DOUBLE DEFAULT 0, skala_methodik DOUBLE DEFAULT 0, skala_performanz DOUBLE DEFAULT 0, skala_regulation DOUBLE DEFAULT 0,
        fusion_alpha DOUBLE DEFAULT NULL, fusion_beta DOUBLE DEFAULT NULL, fusion_lambda DOUBLE DEFAULT NULL, fusion_delta DOUBLE DEFAULT NULL,
        emotion_cosine DOUBLE DEFAULT NULL,
        dominante_dimension VARCHAR(50) NULL, dominante_dimension_wert DOUBLE DEFAULT 0, polaritaet_gesamt INT DEFAULT 0, d_semantisch DOUBLE DEFAULT 0,
        drift_norm DOUBLE DEFAULT NULL, d_semantisch_delta DOUBLE DEFAULT NULL, dominanzwechsel TINYINT(1) DEFAULT NULL,
        stabilitaet DOUBLE DEFAULT NULL, transition_marker VARCHAR(80) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tn_time (teilnehmer_id, zeitpunkt), INDEX idx_group_time (gruppe_id, zeitpunkt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    foreach([
        'drift_norm'=>'DOUBLE DEFAULT NULL','d_semantisch_delta'=>'DOUBLE DEFAULT NULL','dominanzwechsel'=>'TINYINT(1) DEFAULT NULL','stabilitaet'=>'DOUBLE DEFAULT NULL','transition_marker'=>'VARCHAR(80) DEFAULT NULL',
        'emotion_vector_kognition'=>'DOUBLE DEFAULT 0','emotion_vector_sozial'=>'DOUBLE DEFAULT 0','emotion_vector_affektiv'=>'DOUBLE DEFAULT 0','emotion_vector_motivation'=>'DOUBLE DEFAULT 0','emotion_vector_methodik'=>'DOUBLE DEFAULT 0','emotion_vector_performanz'=>'DOUBLE DEFAULT 0','emotion_vector_regulation'=>'DOUBLE DEFAULT 0',
        'skala_kognition'=>'DOUBLE DEFAULT 0','skala_sozial'=>'DOUBLE DEFAULT 0','skala_affektiv'=>'DOUBLE DEFAULT 0','skala_motivation'=>'DOUBLE DEFAULT 0','skala_methodik'=>'DOUBLE DEFAULT 0','skala_performanz'=>'DOUBLE DEFAULT 0','skala_regulation'=>'DOUBLE DEFAULT 0',
        'fusion_alpha'=>'DOUBLE DEFAULT NULL','fusion_beta'=>'DOUBLE DEFAULT NULL','fusion_lambda'=>'DOUBLE DEFAULT NULL','fusion_delta'=>'DOUBLE DEFAULT NULL','emotion_cosine'=>'DOUBLE DEFAULT NULL'
    ] as $c=>$d){ addColumnIfMissing($pdo,'frzk_semantische_dichte_teilnehmer_7d',$c,$d); }
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_interdependenz_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, teilnehmer_id INT NOT NULL, gruppe_id INT NULL, zeitpunkt DATETIME NOT NULL,
        x_kognition DOUBLE, x_sozial DOUBLE, x_affektiv DOUBLE, x_motivation DOUBLE, x_methodik DOUBLE, x_performanz DOUBLE, x_regulation DOUBLE,
        d_semantisch DOUBLE, korrelationsscore DOUBLE, kohaerenz_index DOUBLE, varianz_7d DOUBLE, bemerkung TEXT,
        INDEX idx_tn_time (teilnehmer_id, zeitpunkt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_loops_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, teilnehmer_id INT NOT NULL, start_zeit DATETIME NOT NULL, end_zeit DATETIME NOT NULL,
        schleifen_typ VARCHAR(50), dauer INT, drift_avg DOUBLE, verdichtungsgrad DOUBLE, stabilitaet DOUBLE, pausenmarker VARCHAR(50), bemerkung TEXT,
        INDEX idx_tn (teilnehmer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_operatoren_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, teilnehmer_id INT NOT NULL, gruppe_id INT NULL, zeitpunkt DATETIME NOT NULL,
        sigma DOUBLE, M DOUBLE, R DOUBLE, E DOUBLE, operator_status VARCHAR(80), bemerkung TEXT,
        INDEX idx_tn_time (teilnehmer_id, zeitpunkt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_reflexion_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, teilnehmer_id INT NOT NULL, zeitpunkt DATETIME NOT NULL,
        reflexionsgrad DOUBLE, meta_kohaerenz DOUBLE, selbstbezug_index DOUBLE, stabilitaet DOUBLE, marker VARCHAR(50), bemerkung TEXT,
        INDEX idx_tn (teilnehmer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_transitions_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, teilnehmer_id INT NOT NULL, zeitpunkt_von DATETIME NOT NULL, zeitpunkt_nach DATETIME NOT NULL,
        dominante_dimension_von VARCHAR(50), dominante_dimension_nach VARCHAR(50), d_von DOUBLE, d_nach DOUBLE,
        delta DOUBLE, transition_typ VARCHAR(80), dominanzwechsel TINYINT(1), bemerkung TEXT,
        INDEX idx_tn (teilnehmer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_group_semantische_dichte_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, gruppe_id INT NOT NULL, zeitpunkt DATE NOT NULL, anz_tn INT DEFAULT 0,
        mean_kognition DOUBLE, mean_sozial DOUBLE, mean_affektiv DOUBLE, mean_motivation DOUBLE, mean_methodik DOUBLE, mean_performanz DOUBLE, mean_regulation DOUBLE,
        d_semantisch_mean DOUBLE, dominante_dimension VARCHAR(50), dominante_dimension_wert DOUBLE, polaritaet_gesamt INT,
        gruppen_drift_norm DOUBLE, gruppen_stabilitaet DOUBLE, gruppen_transition_marker VARCHAR(80),
        mean_emotion_valenz DOUBLE NULL, mean_emotion_aktivierung DOUBLE NULL, emotion_n INT DEFAULT 0,
        mean_emotion_vector_kognition DOUBLE DEFAULT 0, mean_emotion_vector_sozial DOUBLE DEFAULT 0, mean_emotion_vector_affektiv DOUBLE DEFAULT 0,
        mean_emotion_vector_motivation DOUBLE DEFAULT 0, mean_emotion_vector_methodik DOUBLE DEFAULT 0, mean_emotion_vector_performanz DOUBLE DEFAULT 0, mean_emotion_vector_regulation DOUBLE DEFAULT 0,
        mean_skala_kognition DOUBLE DEFAULT 0, mean_skala_sozial DOUBLE DEFAULT 0, mean_skala_affektiv DOUBLE DEFAULT 0,
        mean_skala_motivation DOUBLE DEFAULT 0, mean_skala_methodik DOUBLE DEFAULT 0, mean_skala_performanz DOUBLE DEFAULT 0, mean_skala_regulation DOUBLE DEFAULT 0,
        bemerkung TEXT, UNIQUE KEY uq_group_date (gruppe_id, zeitpunkt)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    foreach([
        'mean_emotion_vector_kognition'=>'DOUBLE DEFAULT 0','mean_emotion_vector_sozial'=>'DOUBLE DEFAULT 0','mean_emotion_vector_affektiv'=>'DOUBLE DEFAULT 0','mean_emotion_vector_motivation'=>'DOUBLE DEFAULT 0','mean_emotion_vector_methodik'=>'DOUBLE DEFAULT 0','mean_emotion_vector_performanz'=>'DOUBLE DEFAULT 0','mean_emotion_vector_regulation'=>'DOUBLE DEFAULT 0',
        'mean_skala_kognition'=>'DOUBLE DEFAULT 0','mean_skala_sozial'=>'DOUBLE DEFAULT 0','mean_skala_affektiv'=>'DOUBLE DEFAULT 0','mean_skala_motivation'=>'DOUBLE DEFAULT 0','mean_skala_methodik'=>'DOUBLE DEFAULT 0','mean_skala_performanz'=>'DOUBLE DEFAULT 0','mean_skala_regulation'=>'DOUBLE DEFAULT 0'
    ] as $c=>$d){ addColumnIfMissing($pdo,'frzk_group_semantische_dichte_7d',$c,$d); }

    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_group_transitions_7d LIKE frzk_transitions_7d");
    addColumnIfMissing($pdo,'frzk_group_transitions_7d','gruppe_id','INT NULL AFTER id');
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_group_reflexion_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, gruppe_id INT NOT NULL, zeitpunkt DATE NOT NULL, reflexionsgrad DOUBLE, meta_kohaerenz DOUBLE, stabilitaet DOUBLE, marker VARCHAR(50), bemerkung TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS frzk_group_loops_7d (
        id INT AUTO_INCREMENT PRIMARY KEY, gruppe_id INT NOT NULL, start_zeit DATE NOT NULL, end_zeit DATE NOT NULL, schleifen_typ VARCHAR(50), dauer INT, drift_avg DOUBLE, stabilitaet DOUBLE, bemerkung TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
function frzk7d_truncate(PDO $pdo): void {
    foreach(['frzk_interdependenz_7d','frzk_loops_7d','frzk_operatoren_7d','frzk_reflexion_7d','frzk_transitions_7d','frzk_group_semantische_dichte_7d','frzk_group_transitions_7d','frzk_group_reflexion_7d','frzk_group_loops_7d','frzk_semantische_dichte_teilnehmer_7d'] as $t){ $pdo->exec("TRUNCATE TABLE `$t`"); }
}
?>
