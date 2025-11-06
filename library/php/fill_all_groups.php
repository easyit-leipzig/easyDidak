<?php
// ====================================================================
// fill_all_groups.php
// Aggregiert alle FRZK-Metriken pro Gruppe innerhalb einer Unterrichtseinheit
// (basierend auf mtr_rueckkopplung_teilnehmer.gruppe_id)
// ====================================================================

$dsn = "mysql:host=localhost;dbname=icas;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}

// --------------------------------------------------------------------
// Hilfsfunktion: Tabellen anlegen, falls sie noch nicht existieren
// --------------------------------------------------------------------
function create_group_tables(PDO $pdo) {
    $tables = [
        "frzk_group_semantische_dichte" => "
            CREATE TABLE IF NOT EXISTS frzk_group_semantische_dichte (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                anzahl_teilnehmer INT DEFAULT 0,
                avg_h FLOAT DEFAULT NULL,
                var_h FLOAT DEFAULT NULL,
                stabilitaet_avg FLOAT DEFAULT NULL,
                dh_dt_avg FLOAT DEFAULT NULL,
                dh_dt_std FLOAT DEFAULT NULL,
                kohärenz_score FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "frzk_group_transitions" => "
            CREATE TABLE IF NOT EXISTS frzk_group_transitions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                avg_dh_dt FLOAT DEFAULT NULL,
                std_dh_dt FLOAT DEFAULT NULL,
                kohärenz_index FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "frzk_group_reflexion" => "
            CREATE TABLE IF NOT EXISTS frzk_group_reflexion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                avg_reflexion FLOAT DEFAULT NULL,
                var_reflexion FLOAT DEFAULT NULL,
                z_reflexion FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "frzk_group_operatoren" => "
            CREATE TABLE IF NOT EXISTS frzk_group_operatoren (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                avg_operatoren FLOAT DEFAULT NULL,
                var_operatoren FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "frzk_group_loops" => "
            CREATE TABLE IF NOT EXISTS frzk_group_loops (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                avg_loops FLOAT DEFAULT NULL,
                var_loops FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "frzk_group_interdependenz" => "
            CREATE TABLE IF NOT EXISTS frzk_group_interdependenz (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                avg_interdependenz FLOAT DEFAULT NULL,
                var_interdependenz FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "frzk_group_hubs" => "
            CREATE TABLE IF NOT EXISTS frzk_group_hubs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                unterrichtseinheit_id INT NOT NULL,
                gruppe_id INT NOT NULL,
                avg_x FLOAT DEFAULT NULL,
                avg_y FLOAT DEFAULT NULL,
                avg_z FLOAT DEFAULT NULL,
                avg_h FLOAT DEFAULT NULL,
                var_h FLOAT DEFAULT NULL,
                z_h FLOAT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY (unterrichtseinheit_id, gruppe_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }
}

create_group_tables($pdo);

// --------------------------------------------------------------------
// 1. Unterrichtseinheiten ermitteln (über Beziehungskette)
// --------------------------------------------------------------------
echo "Ermittle Unterrichtseinheiten...\n";

$sql = "
    SELECT DISTINCT uet.ue_unterrichtseinheit_id AS unterrichtseinheit_id
    FROM mtr_rueckkopplung_teilnehmer mrt
    JOIN ue_zuweisung_teilnehmer uzt ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
    JOIN ue_unterrichtseinheit_zw_thema uet ON uzt.ue_unterrichtseinheit_zw_thema_id = uet.id
";
$unterrichtseinheiten = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);

if (empty($unterrichtseinheiten)) {
    die('Keine Unterrichtseinheiten gefunden.');
}

// --------------------------------------------------------------------
// 2. Aggregation pro Unterrichtseinheit / Gruppe
// --------------------------------------------------------------------
foreach ($unterrichtseinheiten as $ue_id) {

    // Gruppen innerhalb der Unterrichtseinheit finden (direkt aus mtr_rueckkopplung_teilnehmer)
    $gruppen_sql = "
        SELECT DISTINCT mrt.gruppe_id
        FROM mtr_rueckkopplung_teilnehmer mrt
        JOIN ue_zuweisung_teilnehmer uzt ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
        JOIN ue_unterrichtseinheit_zw_thema uet ON uzt.ue_unterrichtseinheit_zw_thema_id = uet.id
        WHERE uet.ue_unterrichtseinheit_id = :ue
    ";
    $stmt = $pdo->prepare($gruppen_sql);
    $stmt->execute(['ue' => $ue_id]);
    $gruppen = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($gruppen as $gruppe_id) {

        // Teilnehmer dieser Gruppe ermitteln
        $teilnehmer_sql = "
            SELECT mrt.id
            FROM mtr_rueckkopplung_teilnehmer mrt
            JOIN ue_zuweisung_teilnehmer uzt ON mrt.ue_zuweisung_teilnehmer_id = uzt.id
            JOIN ue_unterrichtseinheit_zw_thema uet ON uzt.ue_unterrichtseinheit_zw_thema_id = uet.id
            WHERE uet.ue_unterrichtseinheit_id = :ue
              AND mrt.gruppe_id = :gid
        ";
        $tstmt = $pdo->prepare($teilnehmer_sql);
        $tstmt->execute(['ue' => $ue_id, 'gid' => $gruppe_id]);
        $teilnehmer = $tstmt->fetchAll(PDO::FETCH_COLUMN);
        if (empty($teilnehmer)) continue;

        $id_list = implode(',', array_map('intval', $teilnehmer));
        $anzahl = count($teilnehmer);

        echo "Unterrichtseinheit $ue_id – Gruppe $gruppe_id ($anzahl Teilnehmer)\n";

        // -------------------------------------
        // Beispielhafte Aggregation: Semantische Dichte
        // -------------------------------------
        $agg_sd = $pdo->query("
            SELECT
              AVG(fd.avg_h) AS avg_h,
              VAR_POP(fd.avg_h) AS var_h,
              AVG(fd.stabilitaet_avg) AS stabilitaet_avg,
              AVG(fd.dh_dt_avg) AS dh_dt_avg,
              STDDEV_POP(fd.dh_dt_avg) AS dh_dt_std,
              AVG(fd.kohärenz_score) AS kohärenz_score
            FROM frzk_semantische_dichte fd
            WHERE fd.teilnehmer_id IN ($id_list)
        ")->fetch();

        $insert = $pdo->prepare("
            INSERT INTO frzk_group_semantische_dichte
            (unterrichtseinheit_id, gruppe_id, anzahl_teilnehmer, avg_h, var_h, stabilitaet_avg, dh_dt_avg, dh_dt_std, kohärenz_score)
            VALUES (:ue, :gid, :anz, :avg_h, :var_h, :stab, :dhdt, :dhstd, :koh)
            ON DUPLICATE KEY UPDATE
              anzahl_teilnehmer = VALUES(anzahl_teilnehmer),
              avg_h = VALUES(avg_h),
              var_h = VALUES(var_h),
              stabilitaet_avg = VALUES(stabilitaet_avg),
              dh_dt_avg = VALUES(dh_dt_avg),
              dh_dt_std = VALUES(dh_dt_std),
              kohärenz_score = VALUES(kohärenz_score)
        ");
        $insert->execute([
            'ue' => $ue_id,
            'gid' => $gruppe_id,
            'anz' => $anzahl,
            'avg_h' => $agg_sd['avg_h'],
            'var_h' => $agg_sd['var_h'],
            'stab' => $agg_sd['stabilitaet_avg'],
            'dhdt' => $agg_sd['dh_dt_avg'],
            'dhstd' => $agg_sd['dh_dt_std'],
            'koh' => $agg_sd['kohärenz_score']
        ]);

        // -------------------------------------
        // Weitere FRZK-Tabellen (automatisch aggregiert)
        // -------------------------------------
        $tables = [
            'transitions'   => ['table' => 'frzk_transitions',   'target' => 'frzk_group_transitions',   'fields' => ['avg_dh_dt', 'std_dh_dt', 'kohärenz_index']],
            'reflexion'     => ['table' => 'frzk_reflexion',     'target' => 'frzk_group_reflexion',     'fields' => ['avg_reflexion', 'var_reflexion', 'z_reflexion']],
            'operatoren'    => ['table' => 'frzk_operatoren',    'target' => 'frzk_group_operatoren',    'fields' => ['avg_operatoren', 'var_operatoren']],
            'loops'         => ['table' => 'frzk_loops',         'target' => 'frzk_group_loops',         'fields' => ['avg_loops', 'var_loops']],
            'interdependenz'=> ['table' => 'frzk_interdependenz','target' => 'frzk_group_interdependenz','fields' => ['avg_interdependenz', 'var_interdependenz']],
            'hubs'          => ['table' => 'frzk_hubs',          'target' => 'frzk_group_hubs',          'fields' => ['avg_x', 'avg_y', 'avg_z', 'avg_h', 'var_h', 'z_h']]
        ];

        foreach ($tables as $t) {
            $f = implode(', ', array_map(fn($x) => "AVG($x) AS $x", $t['fields']));
            $agg = $pdo->query("SELECT $f FROM {$t['table']} WHERE teilnehmer_id IN ($id_list)")->fetch();
            if (!$agg) continue;

            $cols = implode(', ', array_keys($agg));
            $params = implode(', ', array_map(fn($x) => ':' . $x, array_keys($agg)));
            $updates = implode(', ', array_map(fn($x) => "$x = VALUES($x)", array_keys($agg)));

            $sql_insert = "
                INSERT INTO {$t['target']}
                (unterrichtseinheit_id, gruppe_id, anzahl_teilnehmer, $cols)
                VALUES (:ue, :gid, :anz, $params)
                ON DUPLICATE KEY UPDATE
                  anzahl_teilnehmer = VALUES(anzahl_teilnehmer),
                  $updates
            ";
            $stmt = $pdo->prepare($sql_insert);
            $stmt->execute(array_merge(
                ['ue' => $ue_id, 'gid' => $gruppe_id, 'anz' => $anzahl],
                $agg
            ));
        }

        echo " → aggregiert.\n";
    }
}

echo "\nFertig: Alle Gruppen pro Unterrichtseinheit aggregiert.\n";
