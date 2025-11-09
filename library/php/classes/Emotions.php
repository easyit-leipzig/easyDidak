<?php
/* 
thile.olaf
08.11.25
Emotionsanalyse aus Daten
*/
    class Emotions {
        private     $pdo;
        public      $parm;
        public      $table;
        private     $record;
        private     $behavior;
        private     $query;
        public function __construct( $param ) {
            // content
            if( isset( $param -> pdo ) ) $this -> pdo = $param -> pdo;
            if( !isset( $param -> behavior ) ) $this -> behavior = "databaseGroups";
            if( !isset( $param -> table )) $param -> table = "frzk_group_semantische_dichte";
            $this -> createAllGroupTables( new \stdClass() );
            $this-> record = [];
            $this-> query = "";
            switch( $this -> behavior ) {
                case "databaseGroups":
//                    $this->truncAllTables(new \stdClass());
                    $this->dropAllTables(new \stdClass());
                    $this->createAllGroupTables( $param );
                    if( isset( $param -> id) and ( !isset( $param -> date ) and !isset( $param -> group_id ) ) ) {
                        $this -> query = "select emotions from " . $param -> table . " where id = " . $param -> id;
                    } else {
                        if( isset( $param -> date) and isset( $param -> group_id ) ) {
                            $this -> query = "select emotions from " . $param -> table . " where zeitpunkt = '" . $param -> date . "' and gruppe_id = " . $param -> group_id;
                            
                        }
                    }
                    if( $this -> query !== "" ) {
                        $this-> record = $this -> pdo -> query( $q ) -> fetchAll();
                    }
                
                break;
            }
        }
        public function truncAllTables( $param ) {
                switch( $this -> behavior ) {
                    case "databaseGroups":
                        $tables = ["frzk_group_frzk_transitions", "frzk_group_hubs", "frzk_group_interdependenz", "frzk_group_loops", "frzk_group_operatoren", "frzk_group_reflexion", 
                                    "frzk_group_semantische_dichte", "frzk_group_transitions]"];
                        foreach( $tables as $t) {
                            $this -> pdo -> exec( "truncate table $t");
                        }
                    break;
                }
            
            return $this-> record;
        }
        public function dropAllTables( $param ) {
                switch( $this -> behavior ) {
                    case "databaseGroups":
                        $tables = ["frzk_group_frzk_transitions", "frzk_group_hubs", "frzk_group_interdependenz", "frzk_group_loops", "frzk_group_operatoren", "frzk_group_reflexion", 
                                    "frzk_group_semantische_dichte", "frzk_group_transitions"];
                        foreach( $tables as $t) {
                            try{
                                $this -> pdo -> exec( "drop table $t");
                            }
                            catch (exception $e){
                                
                            }
                        }
                    break;
                }
            
            return $this-> record;
        }
        public function createAllGroupTables( $param ) {
                switch( $this -> behavior ) {
                    case "databaseGroups":
                            $this -> dropAllTables( new \stdClass());
                            $this -> pdo -> exec( "
                            
                            CREATE TABLE `frzk_group_semantische_dichte` (
                                  `id` int(11) NOT NULL,
                                  `ue_id` int(11) NOT NULL,
                                  `gruppe_id` int(11) DEFAULT NULL,
                                  `anz_tn` int(11) DEFAULT NULL,
                                  `zeitpunkt` datetime NOT NULL,
                                  `x_kognition` float NOT NULL,
                                  `y_sozial` float NOT NULL,
                                  `z_affektiv` float NOT NULL,
                                  `h_bedeutung` float NOT NULL,
                                  `dh_dt` float DEFAULT NULL,
                                  `cluster_id` int(11) DEFAULT NULL,
                                  `stabilitaet_score` float DEFAULT NULL,
                                  `transitions_marker` varchar(50) DEFAULT NULL,
                                  `emotions` varchar(2048) NOT NULL,
                                  `bemerkung` text DEFAULT NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                ALTER TABLE `frzk_group_semantische_dichte`
                                  ADD PRIMARY KEY (`id`),
                                  ADD KEY `gruppe_id` (`gruppe_id`),
                                  ADD KEY `zeitpunkt` (`zeitpunkt`),
                                  ADD UNIQUE KEY `idx_gruppe_zeitpunkt` (`gruppe_id`,`zeitpunkt`);
                                ALTER TABLE `frzk_group_semantische_dichte`
                                  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                                
                            
                                CREATE TABLE `frzk_group_reflexion` (
                                  `id` int(11) NOT NULL,
                                  `gruppe_id` int(11) NOT NULL,
                                  `zeitpunkt` datetime NOT NULL,
                                  `reflexionsgrad` float DEFAULT NULL,
                                  `meta_kohärenz` float DEFAULT NULL,
                                  `selbstbezug_index` float DEFAULT NULL,
                                  `reflexions_marker` varchar(20) DEFAULT NULL,
                                  `bemerkung` text DEFAULT NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                ALTER TABLE `frzk_group_reflexion`
                                  ADD PRIMARY KEY (`id`),
                                  ADD UNIQUE KEY `idx_grruppe_zeitpunkt` (`gruppe_id`,`zeitpunkt`),
                                  ADD KEY `gruppe_id` (`gruppe_id`),
                                  ADD KEY `zeitpunkt` (`zeitpunkt`);
                                ALTER TABLE `frzk_group_reflexion`
                                  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                                CREATE TABLE `frzk_group_transitions` (
                                  `id` int(11) NOT NULL,
                                  `gruppe_id` int(11) NOT NULL,
                                  `zeitpunkt` datetime NOT NULL,
                                  `von_cluster` int(11) DEFAULT NULL,
                                  `nach_cluster` int(11) DEFAULT NULL,
                                  `delta_h` float DEFAULT NULL,
                                  `delta_stabilitaet` float DEFAULT NULL,
                                  `transition_typ` varchar(50) DEFAULT NULL,
                                  `transition_intensitaet` float DEFAULT NULL,
                                  `marker` varchar(10) DEFAULT NULL,
                                  `bemerkung` text DEFAULT NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                ALTER TABLE `frzk_group_transitions`
                                  ADD PRIMARY KEY (`id`),
                                  ADD UNIQUE KEY `idx_gruppe_zeitpunkt` (`gruppe_id`,`zeitpunkt`),
                                  ADD KEY `gruppe_id` (`gruppe_id`),
                                  ADD KEY `zeitpunkt` (`zeitpunkt`);
                                ALTER TABLE `frzk_group_transitions`
                                  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                                CREATE TABLE `frzk_group_operatoren` (
                                  `id` int(11) NOT NULL,
                                  `gruppe_id` int(11) NOT NULL,
                                  `zeitpunkt` datetime NOT NULL,
                                  `x_kognition` float DEFAULT NULL,
                                  `y_sozial` float DEFAULT NULL,
                                  `z_affektiv` float DEFAULT NULL,
                                  `h_bedeutung` float DEFAULT NULL,
                                  `dh_dt` float DEFAULT NULL,
                                  `stabilitaet_score` float DEFAULT NULL,
                                  `operator_sigma` float DEFAULT NULL,
                                  `operator_meta` float DEFAULT NULL,
                                  `operator_resonanz` float DEFAULT NULL,
                                  `operator_emer` float DEFAULT NULL,
                                  `operator_level` float DEFAULT NULL,
                                  `dominanter_operator` varchar(20) DEFAULT NULL,
                                  `bemerkung` text DEFAULT NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                ALTER TABLE `frzk_group_operatoren`
                                  ADD PRIMARY KEY (`id`),
                                  ADD UNIQUE KEY `idx_gruppe_zeitpunkt` (`gruppe_id`,`zeitpunkt`),
                                  ADD KEY `gruppe_id` (`gruppe_id`),
                                  ADD KEY `zeitpunkt` (`zeitpunkt`);
                                ALTER TABLE `frzk_group_operatoren`
                                  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                                  
                                CREATE TABLE `frzk_group_hubs` (
                                  `id` int(11) NOT NULL,
                                  `gruppe_id` int(11) NOT NULL,
                                  `zeitpunkt` datetime NOT NULL,
                                  `operator_sigma` float DEFAULT NULL,
                                  `operator_meta` float DEFAULT NULL,
                                  `operator_resonanz` float DEFAULT NULL,
                                  `operator_emer` float DEFAULT NULL,
                                  `stabilitaet_score` float DEFAULT NULL,
                                  `hub_score` float DEFAULT NULL,
                                  `hub_typ` varchar(50) DEFAULT NULL,
                                  `bedeutungszentrum` varchar(100) DEFAULT NULL,
                                  `bemerkung` text DEFAULT NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                ALTER TABLE `frzk_group_hubs`
                                  ADD PRIMARY KEY (`id`),
                                  ADD UNIQUE KEY `idx_gruppe_id_zeitpunkt` (`gruppe_id`,`zeitpunkt`),
                                  ADD UNIQUE KEY `idx_gruppe_zeitpunkt` (`gruppe_id`,`zeitpunkt`),
                                  ADD KEY `gruppe_id` (`gruppe_id`),
                                  ADD KEY `zeitpunkt` (`zeitpunkt`);
                                ALTER TABLE `frzk_group_hubs`
                                  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                                CREATE TABLE `frzk_group_interdependenz` (
                                  `id` int(11) NOT NULL,
                                  `gruppe_id` int(11) NOT NULL,
                                  `zeitpunkt` datetime NOT NULL,
                                  `x_kognition` float DEFAULT NULL,
                                  `y_sozial` float DEFAULT NULL,
                                  `z_affektiv` float DEFAULT NULL,
                                  `h_bedeutung` float DEFAULT NULL,
                                  `korrelationsscore` float DEFAULT NULL,
                                  `kohärenz_index` float DEFAULT NULL,
                                  `varianz_xyz` float DEFAULT NULL,
                                  `bemerkung` text DEFAULT NULL
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                ALTER TABLE `frzk_group_interdependenz`
                                  ADD PRIMARY KEY (`id`),
                                  ADD UNIQUE KEY `idx_gruppe_zeitpunkt` (`gruppe_id`,`zeitpunkt`),
                                  ADD KEY `gruppe_id` (`gruppe_id`),
                                  ADD KEY `zeitpunkt` (`zeitpunkt`);
                                ALTER TABLE `frzk_group_interdependenz`
                                  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                                  CREATE TABLE `frzk_group_loops` (
                                      `id` int(11) NOT NULL,
                                      `gruppe_id` int(11) NOT NULL,
                                      `loop_start` datetime NOT NULL,
                                      `loop_ende` datetime NOT NULL,
                                      `dauer` int(11) DEFAULT NULL,
                                      `typ` varchar(30) DEFAULT NULL,
                                      `intensitaet` float DEFAULT NULL,
                                      `zyklus_muster` text DEFAULT NULL,
                                      `marker` varchar(10) DEFAULT NULL,
                                      `bemerkung` text DEFAULT NULL
                                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                                    ALTER TABLE `frzk_group_loops`
                                      ADD PRIMARY KEY (`id`),
                                      ADD KEY `gruppe_id` (`gruppe_id`),
                                      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
                                ");
                                /*
                                Auf-/ZuklappenStrukturfrzk_group_frzk_transitions
frzk_group_hubs
frzk_group_interdependenz
Auf-/ZuklappenStrukturfrzk_group_loops
Auf-/ZuklappenStrukturfrzk_group_operatoren
Auf-/ZuklappenStrukturfrzk_group_reflexion
Auf-/ZuklappenStrukturfrzk_group_semantische_dichte
Auf-/ZuklappenStrukturfrzk_group_transitions
*/
                    break;
        }
        }
        
        public function get( $param ) {
            return $this-> record;
        }
        public function aggregate( $param ) {
            
            return $this-> record;
        }
        public function createEmTmpTable() {
            $this -> pdo -> exec("
CREATE TABLE `frzk_tmp_group_semantische_dichte` (
  `id` int(11) NOT NULL,
  `ue_id` int(11) NOT NULL,
  `gruppe_id` int(11) DEFAULT NULL,
  `zeitpunkt` datetime NOT NULL,
  `anz_tn` int(11) DEFAULT NULL,
  `x_kognition` float NOT NULL,
  `y_sozial` float NOT NULL,
  `z_affektiv` float NOT NULL,
  `h_bedeutung` float NOT NULL,
  `dh_dt` float DEFAULT NULL,
  `cluster_id` int(11) DEFAULT NULL,
  `stabilitaet_score` float DEFAULT NULL,
  `transitions_marker` varchar(50) DEFAULT NULL,
  `emotions` varchar(2048) NOT NULL,
  `bemerkung` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `frzk_tmp_group_semantische_dichte`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `frzk_tmp_group_semantische_dichte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;"    
            );
        }
        public function dropEmTmpTable() {
            $this -> pdo -> exec("
drop TABLE `frzk_tmp_group_semantische_dichte`

            ");
            
}
        private function setGroupValues() {
            
        }
        private function setGroupEmotions() {

              $sql="select ue_id from frzk_group_semantische_dichte";
            $rows = $this -> pdo ->query($sql)->fetchAll();

            foreach ($rows as $r) {
                $sql = "SELECT frzk_group_semantische_dichte.* FROM frzk_group_semantische_dichte order by id";
                $rows_tn = $this -> pdo ->query($sql)->fetchAll();
                $l = count( $rows_tn );
                $i = 0;
                while( $i < $l ) {
                    $sql_sd_tn = "select count(id) as anz_tn, avg(x_kognition) as x_kognition, avg(y_sozial) as y_sozial, avg(z_affektiv) as z_affektiv from frzk_semantische_dichte where gruppe_id= " . $rows_tn[$i]["gruppe_id"] . " and zeitpunkt='" . $rows_tn[$i]["zeitpunkt"] . "'";
                    $rows_sd_tn = $this -> pdo ->query($sql_sd_tn)->fetchAll();
                    //$this -> pdo ->exec("update frzk_tmp_group_semantische_dichte set anz_tn=" . $rows_sd_tn[0]["anz_tn"] . ", x_kognition=" . $rows_sd_tn[0]["x_kognition"] . ", y_sozial=" . $rows_sd_tn[0]["y_sozial"] . ", z_affektiv=" . $rows_sd_tn[0]["z_affektiv"] . "  where id=" . $rows_tn[$i]["id"]);
                    $tnIds = "";
                    /*
                    foreach ($rows_sd_tn as $sd_tn) {
                        $tnIds .= $sd_tn["teilnehmer_id"] . ",";
                    }
                    $tnIds = substr($tnIds, 0, -1);
                    */
                    $sql_sd_em = "select emotions from frzk_semantische_dichte where gruppe_id= " . $rows_tn[$i]["gruppe_id"] . " and zeitpunkt='" . $rows_tn[$i]["zeitpunkt"] . "'";
                    $rows_sd_em = $this -> pdo ->query($sql_sd_em)->fetchAll();
                    $tnEmotions = "";
                    foreach ($rows_sd_em as $sd_em) {
                        $tnEmotions .= $sd_em["emotions"] . ",";
                    }
                    $tnEmotions = substr($tnEmotions, 0, -1);
                    $tnEmotionsArr = explode( ",", $tnEmotions );
                        $stmt = $this -> pdo ->query("SELECT id, emotion, valenz, aktivierung FROM _mtr_emotionen");
                        $emotionMatrix = [];
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $emotionMatrix[(int)$row['id']] = [
                                'emotion' => $row['emotion'],
                                'valenz' => (float)$row['valenz'],
                                'aktivierung' => (float)$row['aktivierung'],
                            ];
                        }

                        // --- 2) Schwellenwerte für „wesentliche“ Emotionen ---
                        $minValenz = 0.7;
                        $minAktivierung = 0.5;
                        $datensaetze[$rows_tn["id"]]['emotionen']=$tnEmotionsArr;
                        // --- 3) JSON-Ausgabe vorbereiten ---
                        $ergebnisse = [];

                        foreach ($datensaetze as $datensatz) {
                            $alle = $datensatz['emotionen'];
                            $anzahl = array_count_values($alle);

                            $wesentliche = [];

                            foreach ($anzahl as $id => $count) {
                                if (!isset($emotionMatrix[$id])) continue;
                                $val = $emotionMatrix[$id]['valenz'];
                                $act = $emotionMatrix[$id]['aktivierung'];

                                // Bedingung: mehrfach & hohe Gewichtung
                                if (/*$count > 1 && */$val >= $minValenz && $act >= $minAktivierung) {
                                    $wesentliche[] = [
                                        'id' => $id,
                                        'emotion' => $emotionMatrix[$id]['emotion'],
                                        'anzahl' => $count,
                                        'valenz' => $val,
                                        'aktivierung' => $act,
                                        'score' => ($val + $act) / 2
                                    ];
                                }
                            }

                            $ergebnisse[] = [
                                //'datensatz_id' => $rows_tn[$i]["id"],
                                //'gruppe_id' => $datensatz['gruppe_id'],
                                'alle_emotionen' => $alle,
                                'anzahl_emotionen' => $anzahl,
                                'wesentliche_emotionen' => $wesentliche
                            ];
                        }
                        $js = json_encode( $ergebnisse );
                        $this -> pdo ->exec("update frzk_group_semantische_dichte set emotions ='" . json_encode( $ergebnisse ) . "' where id=" . $rows_tn[$i]["id"]);
                    $i += 1;
                }
                if( $tnIds != "") {
                }
            }
            
        }
        public function quantEmAnalyse( $param) {
                // ===============================
                // Quantitative Emotionsfrequenzanalyse (PDO-Version)
                // ===============================

                // 1. Datenbankverbindung (PDO)
                $dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
                $user = 'root';       // ggf. anpassen
                $pass = '';           // ggf. anpassen

                try {
                    $pdo = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                } catch (PDOException $e) {
                    die("Verbindung fehlgeschlagen: " . $e->getMessage());
                }

                // 2. Alle JSON-Datensätze auslesen
                $sql = "SELECT id, emotions FROM frzk_tmp_group_semantische_dichte";
                $stmt = $pdo->query($sql);

                $emotionCounts = [];  // aggregierte Häufigkeiten

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $json = $row['emotions'];

                    // JSON in Array umwandeln
                    $data = json_decode($json, true);

                    if (is_array($data) && isset($data[0]['anzahl_emotionen'])) {
                        $counts = $data[0]['anzahl_emotionen'];

                        foreach ($counts as $emotionId => $anzahl) {
                            $emotionId = trim($emotionId);
                            if (!isset($emotionCounts[$emotionId])) {
                                $emotionCounts[$emotionId] = 0;
                            }
                            $emotionCounts[$emotionId] += (int)$anzahl;
                        }
                    }
                }

                // 3. Sortieren nach Häufigkeit
                arsort($emotionCounts);

                // 4. Ausgabe
                echo "<h2>📊 Quantitative Emotionsfrequenzanalyse</h2>";
                echo "<table border='1' cellpadding='6' cellspacing='0'>";
                echo "<tr><th>Emotion-ID</th><th>Gesamtanzahl</th></tr>";

                foreach ($emotionCounts as $emotionId => $summe) {
                    echo "<tr><td>$emotionId</td><td>$summe</td></tr>";
                }
                echo "</table>";

                // Optional: Gesamtzahl und Verteilung
                $total = array_sum($emotionCounts);
                echo "<p><b>Gesamtzahl erfasster Emotionen:</b> $total</p>";
                    
        }
        public function ValenzAktivierungEmAnalyse( $param) {
// ============================================
// ⚙️ Valenz- und Aktivierungsanalyse (PDO-Version)
// ============================================

// 1️⃣ Datenbankverbindung
$dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// 2️⃣ Alle JSON-Felder abrufen
$sql = "SELECT id, emotions FROM frzk_tmp_group_semantische_dichte";
$stmt = $pdo->query($sql);

$emotionStats = []; // [emotion] => ['valenz_sum' => ..., 'aktiv_sum' => ..., 'score_sum' => ..., 'anzahl' => N]

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data = json_decode($row['emotions'], true);

    if (!is_array($data)) continue;
    foreach ($data as $block) {
        if (!isset($block['wesentliche_emotionen'])) continue;

        foreach ($block['wesentliche_emotionen'] as $em) {
            if (!isset($em['emotion'])) continue;
            $emotion = trim($em['emotion']);
            $valenz = (float)($em['valenz'] ?? 0);
            $aktiv  = (float)($em['aktivierung'] ?? 0);
            $score  = (float)($em['score'] ?? 0);

            if (!isset($emotionStats[$emotion])) {
                $emotionStats[$emotion] = [
                    'valenz_sum' => 0,
                    'aktiv_sum' => 0,
                    'score_sum' => 0,
                    'anzahl' => 0
                ];
            }

            $emotionStats[$emotion]['valenz_sum'] += $valenz;
            $emotionStats[$emotion]['aktiv_sum']  += $aktiv;
            $emotionStats[$emotion]['score_sum']  += $score;
            $emotionStats[$emotion]['anzahl']++;
        }
    }
}

// 3️⃣ Durchschnittswerte berechnen
foreach ($emotionStats as $emotion => $vals) {
    $emotionStats[$emotion]['valenz_avg'] = $vals['valenz_sum'] / $vals['anzahl'];
    $emotionStats[$emotion]['aktiv_avg']  = $vals['aktiv_sum']  / $vals['anzahl'];
    $emotionStats[$emotion]['score_avg']  = $vals['score_sum']  / $vals['anzahl'];
}

// 4️⃣ Ausgabe
echo "<h2>⚙️ Valenz- und Aktivierungsanalyse</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Emotion</th><th>Anzahl</th><th>Ø Valenz</th><th>Ø Aktivierung</th><th>Ø Score</th></tr>";

foreach ($emotionStats as $emotion => $vals) {
    printf(
        "<tr><td>%s</td><td>%d</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>",
        htmlspecialchars($emotion),
        $vals['anzahl'],
        $vals['valenz_avg'],
        $vals['aktiv_avg'],
        $vals['score_avg']
    );
}
echo "</table>";

// 5️⃣ Gesamtdurchschnitt (optional)
$totalValenz = $totalAktiv = $totalScore = $count = 0;
foreach ($emotionStats as $vals) {
    $totalValenz += $vals['valenz_sum'];
    $totalAktiv  += $vals['aktiv_sum'];
    $totalScore  += $vals['score_sum'];
    $count       += $vals['anzahl'];
}
if ($count > 0) {
    echo "<p><b>Gesamtdurchschnitt aller Emotionen:</b><br>";
    echo "Valenz = " . round($totalValenz/$count, 2) . 
         " | Aktivierung = " . round($totalAktiv/$count, 2) .
         " | Score = " . round($totalScore/$count, 2) . "</p>";
}
    }
        public function dynEmVerlaufe( $param) {

// ===============================================
// 🔄 Dynamische Emotionsverläufe (PDO-Version)
// ===============================================
/*Wir wollen sehen, wie sich Emotionen über die Zeit verändern, welche Gruppen emotional stabil oder volatil sind, und wann Übergänge auftreten.

Ich zeige dir gleich ein komplettes, produktives PHP/PDO-Skript, das:

alle wesentliche_emotionen ausliest,

sie nach zeitpunkt sortiert,

pro Emotion Durchschnittswerte über die Zeit berechnet (Valenz, Aktivierung, Score),

optionale Filter nach Gruppe zulässt,

und die Daten in ein JSON-Format bringt, das du z. B. direkt mit Chart.js oder Plotly visualisieren kannst.
*/
$dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Optional: gruppenspezifisch filtern
$gruppe_id = isset($_GET['gruppe']) ? (int)$_GET['gruppe'] : null;
$sql = "SELECT zeitpunkt, emotions FROM frzk_tmp_group_semantische_dichte";
if ($gruppe_id) {
    $sql .= " WHERE gruppe_id = :gid";
}
$sql .= " ORDER BY zeitpunkt ASC";

$stmt = $pdo->prepare($sql);
if ($gruppe_id) $stmt->bindParam(':gid', $gruppe_id);
$stmt->execute();

$timeline = []; // [zeitpunkt][emotion] => ['valenz'=>x, 'aktiv'=>y, 'score'=>z, 'anzahl'=>n]

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $zeit = $row['zeitpunkt'];
    $data = json_decode($row['emotions'], true);

    if (!is_array($data)) continue;

    foreach ($data as $block) {
        if (!isset($block['wesentliche_emotionen'])) continue;

        foreach ($block['wesentliche_emotionen'] as $em) {
            $emotion = trim($em['emotion']);
            $valenz = (float)($em['valenz'] ?? 0);
            $aktiv  = (float)($em['aktivierung'] ?? 0);
            $score  = (float)($em['score'] ?? 0);

            if (!isset($timeline[$zeit][$emotion])) {
                $timeline[$zeit][$emotion] = ['valenz' => 0, 'aktiv' => 0, 'score' => 0, 'anzahl' => 0];
            }

            $timeline[$zeit][$emotion]['valenz'] += $valenz;
            $timeline[$zeit][$emotion]['aktiv']  += $aktiv;
            $timeline[$zeit][$emotion]['score']  += $score;
            $timeline[$zeit][$emotion]['anzahl']++;
        }
    }
}

// Durchschnitt pro Zeitpunkt & Emotion berechnen
foreach ($timeline as $zeit => $emotions) {
    foreach ($emotions as $emotion => $vals) {
        $timeline[$zeit][$emotion]['valenz'] /= $vals['anzahl'];
        $timeline[$zeit][$emotion]['aktiv']  /= $vals['anzahl'];
        $timeline[$zeit][$emotion]['score']  /= $vals['anzahl'];
    }
}

// Ausgabe als HTML & JSON für Visualisierung
echo "<h2>🔄 Dynamische Emotionsverläufe</h2>";
if ($gruppe_id) echo "<p><b>Gefiltert auf Gruppe:</b> $gruppe_id</p>";

echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Zeitpunkt</th><th>Emotion</th><th>Ø Valenz</th><th>Ø Aktivierung</th><th>Ø Score</th></tr>";

foreach ($timeline as $zeit => $emotions) {
    foreach ($emotions as $emotion => $vals) {
        printf(
            "<tr><td>%s</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>",
            htmlspecialchars($zeit),
            htmlspecialchars($emotion),
            $vals['valenz'],
            $vals['aktiv'],
            $vals['score']
        );
    }
}
echo "</table>";

// JSON für JS-Visualisierung (z. B. Chart.js oder Plotly)
header('Content-Type: application/json');
echo "\n\n/* JSON-Daten für Visualisierung */\n";
echo json_encode($timeline, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
/*
📊 Ergebnis & Interpretation

Damit kannst du:

Trends erkennen → z. B. steigt Valenz über die Zeit?

Ereignisse korrelieren → Übergänge (transitions_marker) oder Lernphasen.

Gruppenvergleiche → über Parameter ?gruppe=3 Filter anwenden.

emotionale Stabilität quantifizieren: geringe Schwankungen = hohe Kohärenz.

Möchtest du, dass ich das Skript um eine Korrelation mit z_affektiv oder dh_dt erweitere (z. B. um zu prüfen, ob sich affektive Valenz mit semantischer Dichte verändert)?
Das wäre der Schritt 4 der Gesamtanalyse (emotionale Dynamik ↔ Kohärenz).
*/        
        
        }
        public function GroupEmKohaerenz( $param) {
// ===============================================
// 👥 Gruppenemotionale Kohärenz (PDO-Version)
// ===============================================
/*
Hier geht es darum, wie homogen oder divergent die emotionalen Zustände innerhalb einer Gruppe sind — also ob sie affektiv „kohärent“ oder „fragmentiert“ agieren.

Diese Analyse misst im Prinzip:

🧩 Wie ähnlich sind die emotionalen Profile der Teilnehmenden einer Gruppe über die Zeit hinweg?

🧠 Theoretischer Bezug (FRZK)

Im Rahmen deines FRZK-Modells entspricht das der affektiven Kohärenzebene von 
𝐾
𝑎
𝑓
𝑓
K
aff
    ​

, also der Homogenität der emotionalen Operatorenverteilung 
𝑜
𝑖
o
i
    ​

 innerhalb eines Gruppensystems.
Mathematisch:

𝐾
𝑎
𝑓
𝑓
(
𝑡
,
𝑔
)
=
1
−
𝜎
(
𝑉
𝑔
(
𝑡
)
)
𝜇
(
𝑉
𝑔
(
𝑡
)
)
+
𝜖
K
aff
    ​

(t,g)=1−
μ(V
g
    ​

(t))+ϵ
σ(V
g
    ​

(t))
    ​


→ je niedriger die Varianz (σ), desto höher die Kohärenz.

🎯 Ziel der Analyse

Pro Gruppe (gruppe_id) und Zeitpunkt (zeitpunkt):

Durchschnittliche Valenz, Aktivierung, Score

Standardabweichung (σ) dieser Werte = Maß der emotionalen Streuung

Berechneter Kohärenzindex (zwischen 0 und 1)

Optional: Aggregation über Zeit → emotionale Stabilität der Gruppe
*/
$dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

$sql = "SELECT gruppe_id, zeitpunkt, emotions FROM frzk_tmp_group_semantische_dichte";
$stmt = $pdo->query($sql);

$groups = []; // [gruppe_id][zeitpunkt] => [valenz => [...], aktiv => [...], score => [...]]

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $gid = $row['gruppe_id'];
    $zeit = $row['zeitpunkt'];
    $data = json_decode($row['emotions'], true);

    if (!is_array($data)) continue;

    foreach ($data as $block) {
        if (!isset($block['wesentliche_emotionen'])) continue;

        foreach ($block['wesentliche_emotionen'] as $em) {
            $val = (float)($em['valenz'] ?? 0);
            $act = (float)($em['aktivierung'] ?? 0);
            $scr = (float)($em['score'] ?? 0);

            $groups[$gid][$zeit]['valenz'][] = $val;
            $groups[$gid][$zeit]['aktiv'][]  = $act;
            $groups[$gid][$zeit]['score'][]  = $scr;
        }
    }
}

// Hilfsfunktionen
function avg($arr) { return count($arr) ? array_sum($arr) / count($arr) : 0; }
function stddev($arr) {
    $n = count($arr);
    if ($n <= 1) return 0;
    $mean = avg($arr);
    $sumSq = 0;
    foreach ($arr as $v) $sumSq += pow($v - $mean, 2);
    return sqrt($sumSq / ($n - 1));
}
function coherenceIndex($arr) {
    $mean = avg($arr);
    $sd   = stddev($arr);
    return ($mean + 0.0001) > 0 ? 1 - ($sd / ($mean + 0.0001)) : 0;
}

// Analyse
$results = []; // [gruppe][zeitpunkt] => ['valenz_avg', 'aktiv_avg', 'score_avg', 'koh_valenz', 'koh_aktiv', 'koh_score']

foreach ($groups as $gid => $times) {
    foreach ($times as $zeit => $vals) {
        $results[$gid][$zeit] = [
            'valenz_avg'  => avg($vals['valenz']),
            'aktiv_avg'   => avg($vals['aktiv']),
            'score_avg'   => avg($vals['score']),
            'koh_valenz'  => coherenceIndex($vals['valenz']),
            'koh_aktiv'   => coherenceIndex($vals['aktiv']),
            'koh_score'   => coherenceIndex($vals['score'])
        ];
    }
}

// Ausgabe
echo "<h2>👥 Gruppenemotionale Kohärenz</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Gruppe</th><th>Zeitpunkt</th><th>Ø Valenz</th><th>Kohärenz Valenz</th><th>Ø Aktivierung</th><th>Kohärenz Aktivierung</th><th>Ø Score</th><th>Kohärenz Score</th></tr>";

foreach ($results as $gid => $times) {
    foreach ($times as $zeit => $r) {
        printf(
            "<tr><td>%d</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><td>%.2f</td></tr>",
            $gid,
            htmlspecialchars($zeit),
            $r['valenz_avg'], $r['koh_valenz'],
            $r['aktiv_avg'],  $r['koh_aktiv'],
            $r['score_avg'],  $r['koh_score']
        );
    }
}
echo "</table>";
        
/*
📊 Interpretation der Ergebnisse
Metrik    Bedeutung    Interpretation
Ø Valenz / Aktivierung / Score    Durchschnittliche emotionale Lage    Hohe Valenz = positive Stimmung, hohe Aktivierung = Energie
Kohärenz Valenz (0–1)    Affektive Homogenität    Werte >0.8 = Gruppe emotional kohärent
Kohärenz Aktivierung (0–1)    Energiehomogenität    Hohe Werte → synchronisierte Aktivität
Kohärenz Score (0–1)    Gesamtstabilität der emotionalen Struktur    Maß für affektive Kohärenz im FRZK
🧩 Erweiterungen
Ziel    Methode
Zeitliche Kohärenztrends    Mittelwert der Kohärenz pro Gruppe über Zeit → AVG(koh_score)
Vergleich zwischen Gruppen    Balkendiagramm oder Radarplot
Verbindung zu semantischen Dimensionen    Korrelation koh_score ↔ z_affektiv oder dh_dt
Visualisierung    Heatmap (x = Zeit, y = Gruppe, Farbe = Kohärenzwert)
*/        
        
        
        }
        public function EmotionaleSemantiknetzwerke( $param) {
/*
Perfekt 🌐 — jetzt kommen wir zur höchsten emergenten Ebene der Emotionsanalyse: den emotionalen Semantiknetzwerken.
Hier verbinden wir alles, was du bisher aufgebaut hast (Häufigkeiten, Valenz, Dynamik, Kohärenz), in ein graphisches Netzwerkmodell, das zeigt:

🔗 Welche Emotionen treten gemeinsam auf – und welche bilden stabile semantische Cluster?

Dieses Netzwerk ist im Sinne des FRZK-Modells die affektive Emergenzebene:
Emotionen interagieren wie Operatoren 
𝑜
𝑖
o
i
    ​

, und ihre Co-Occurrences erzeugen Bedeutungsräume (semantische Dichtefelder).

🧩 Konzept (FRZK-Bezug)

Im FRZK-Kontext gilt:

𝐸
𝑖
↔
𝑜
𝑖
:
𝑆
→
𝑆
′
E
i
    ​

↔o
i
    ​

:S→S
′

Emotionen werden zu Operatoren, die semantische Zustände transformieren.
Das Netzwerk beschreibt also nicht nur statistische Co-Occurrence,
sondern auch funktionale Resonanz: stabile emotionale Kopplungen sind die Grundlage emergenter Bedeutung.

🎯 Ziel

Identifiziere Co-Occurrences von Emotionen innerhalb desselben Datensatzes (emotions JSON).

Berechne Kantengewichte = Häufigkeit gemeinsamer Auftretens.

Konstruiere ein Netzwerk (Nodes = Emotionen, Edges = Co-Occurrences).

Exportiere Daten als JSON-Graph, den du z. B. mit D3.js oder Cytoscape visualisieren kannst.
*/
// ===================================================
// 🌐 Emotionale Semantiknetzwerke (PDO-Version)
// ===================================================

$dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// 1️⃣ Alle Datensätze abrufen
$sql = "SELECT id, gruppe_id, emotions FROM frzk_tmp_group_semantische_dichte";
$stmt = $pdo->query($sql);

$edges = [];  // emotion1_emotion2 => weight
$nodes = [];  // emotion => frequency

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $data = json_decode($row['emotions'], true);
    if (!is_array($data)) continue;

    foreach ($data as $block) {
        if (!isset($block['alle_emotionen']) || !is_array($block['alle_emotionen'])) continue;

        $emotionen = array_unique($block['alle_emotionen']); // doppelte vermeiden
        sort($emotionen);

        // Zähle einzelne Emotionen
        foreach ($emotionen as $e) {
            $e = trim($e);
            if ($e === '') continue;
            if (!isset($nodes[$e])) $nodes[$e] = 0;
            $nodes[$e]++;
        }

        // Erzeuge alle Paarungen (Co-Occurrence)
        $n = count($emotionen);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $emotionen[$i];
                $b = $emotionen[$j];
                if ($a === $b) continue;
                $key = $a . '|' . $b;

                if (!isset($edges[$key])) $edges[$key] = 0;
                $edges[$key]++;
            }
        }
    }
}

// 2️⃣ Netzwerkstruktur erzeugen (Nodes + Edges)
$graph = [
    'nodes' => [],
    'edges' => []
];

// Knoten
foreach ($nodes as $emotion => $count) {
    $graph['nodes'][] = [
        'id' => $emotion,
        'label' => $emotion,
        'frequency' => $count
    ];
}

// Kanten
foreach ($edges as $pair => $weight) {
    [$a, $b] = explode('|', $pair);
    $graph['edges'][] = [
        'source' => $a,
        'target' => $b,
        'weight' => $weight
    ];
}

// 3️⃣ Ausgabe: Tabelle + JSON
echo "<h2>🌐 Emotionale Semantiknetzwerke</h2>";
echo "<p>Anzahl Emotionen: " . count($nodes) . "<br>Anzahl Kanten: " . count($edges) . "</p>";

echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Emotion A</th><th>Emotion B</th><th>Co-Occurrence</th></tr>";

foreach ($edges as $pair => $weight) {
    [$a, $b] = explode('|', $pair);
    echo "<tr><td>$a</td><td>$b</td><td>$weight</td></tr>";
}
echo "</table>";

// JSON für Visualisierung (z. B. D3.js oder Cytoscape)
file_put_contents('emotions_network.json', json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "<p>💾 Netzwerkdaten gespeichert als <code>emotions_network.json</code></p>";
/*
🌈 Interpretation
Aspekt    Bedeutung
Knotenfrequenz    Wie oft eine Emotion insgesamt auftritt → Relevanz
Kantengewicht    Wie oft zwei Emotionen gemeinsam vorkommen → semantische Bindung
Clusterbildung    Gruppen von Emotionen mit starker wechselseitiger Kopplung → affektive Themenfelder
FRZK-Bezug    Diese Cluster entsprechen stabilen Kohärenzfeldern — also „emergenten Bedeutungsinseln“ im Funktionsraum
🕸️ Visualisierung (D3.js oder Cytoscape)

Beispiel: Interaktives Force-Directed Network

<div id="net"></div>
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
d3.json("emotions_network.json").then(graph => {
  const width = 800, height = 600;
  const svg = d3.select("#net").append("svg").attr("width", width).attr("height", height);

  const simulation = d3.forceSimulation(graph.nodes)
      .force("link", d3.forceLink(graph.edges).id(d => d.id).distance(80).strength(0.2))
      .force("charge", d3.forceManyBody().strength(-120))
      .force("center", d3.forceCenter(width / 2, height / 2));

  const link = svg.append("g").selectAll("line")
      .data(graph.edges)
      .enter().append("line")
      .attr("stroke-width", d => Math.sqrt(d.weight));

  const node = svg.append("g").selectAll("circle")
      .data(graph.nodes)
      .enter().append("circle")
      .attr("r", d => 5 + Math.sqrt(d.frequency))
      .attr("fill", "steelblue")
      .call(d3.drag()
          .on("start", dragstarted)
          .on("drag", dragged)
          .on("end", dragended));

  const label = svg.append("g").selectAll("text")
      .data(graph.nodes)
      .enter().append("text")
      .text(d => d.label)
      .attr("font-size", "10px")
      .attr("dy", -8);

  simulation.on("tick", () => {
    link
      .attr("x1", d => d.source.x)
      .attr("y1", d => d.source.y)
      .attr("x2", d => d.target.x)
      .attr("y2", d => d.target.y);

    node
      .attr("cx", d => d.x)
      .attr("cy", d => d.y);

    label
      .attr("x", d => d.x)
      .attr("y", d => d.y);
  });

  function dragstarted(event, d) { if (!event.active) simulation.alphaTarget(0.3).restart(); d.fx = d.x; d.fy = d.y; }
  function dragged(event, d) { d.fx = event.x; d.fy = event.y; }
  function dragended(event, d) { if (!event.active) simulation.alphaTarget(0); d.fx = null; d.fy = null; }
});
</script>

🧠 Erweiterungen
Analyse    Beschreibung
Gewichtete Netzwerke pro Gruppe (gruppe_id)    emotionale Clusterbildung pro Gruppe
Valenz-gewichtete Kantenfarben    durchschnittliche Valenz zwischen Knoten einfärben
Community Detection (Louvain)    semantische Cluster automatisch erkennen
Zeitabhängige Netzwerke    Snapshots pro zeitpunkt erzeugen (→ Netzwerk-Animation)
Kohärenz-Metriken    Dichte, Clustering-Koeffizient, Betweenness → FRZK-Maße der semantischen Stabilität

Möchtest du, dass ich dir den Code erweitere, damit pro Gruppe ein eigenes Netzwerk entsteht (z. B. emotions_network_group_3.json), um zu sehen, welche affektiven Cluster innerhalb jeder Gruppe emergieren?
*/

        }        
        public function SemantiknetzwerkeProGruppe(){
/*

Sehr gut 👥🌐 — dann erweitern wir das Netzwerkmodell so, dass für jede Gruppe (gruppe_id) ein eigenes emotionales Semantiknetzwerk entsteht.
Das erlaubt dir, affektive Clusterstrukturen pro Gruppe zu erkennen — also wie sich emotionale Resonanzfelder in unterschiedlichen sozialen Kontexten ausbilden (z. B. „motivationsgetriebene Gruppe 3“ vs. „reflexiv-ruhige Gruppe 7“).

*/
// ===================================================
// 🌐 Emotionale Semantiknetzwerke pro Gruppe (PDO-Version)
// ===================================================

$dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// 1️⃣ Alle Datensätze abrufen
$sql = "SELECT gruppe_id, emotions FROM frzk_tmp_group_semantische_dichte";
$stmt = $pdo->query($sql);

$groupGraphs = []; // [gruppe_id]['nodes'], [gruppe_id]['edges']

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $gid = $row['gruppe_id'];
    $data = json_decode($row['emotions'], true);
    if (!is_array($data)) continue;

    foreach ($data as $block) {
        if (!isset($block['alle_emotionen']) || !is_array($block['alle_emotionen'])) continue;

        $emotionen = array_unique($block['alle_emotionen']);
        sort($emotionen);

        // Initialisiere Gruppe
        if (!isset($groupGraphs[$gid])) {
            $groupGraphs[$gid] = ['nodes' => [], 'edges' => []];
        }

        // Zähle Knoten (Frequenzen)
        foreach ($emotionen as $e) {
            $e = trim($e);
            if ($e === '') continue;
            if (!isset($groupGraphs[$gid]['nodes'][$e])) $groupGraphs[$gid]['nodes'][$e] = 0;
            $groupGraphs[$gid]['nodes'][$e]++;
        }

        // Erzeuge Kanten (Co-Occurrence)
        $n = count($emotionen);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $emotionen[$i];
                $b = $emotionen[$j];
                if ($a === $b) continue;
                $key = $a . '|' . $b;
                if (!isset($groupGraphs[$gid]['edges'][$key])) $groupGraphs[$gid]['edges'][$key] = 0;
                $groupGraphs[$gid]['edges'][$key]++;
            }
        }
    }
}

// 2️⃣ JSON-Dateien pro Gruppe erzeugen
foreach ($groupGraphs as $gid => $data) {
    $nodes = [];
    $edges = [];

    foreach ($data['nodes'] as $emotion => $count) {
        $nodes[] = [
            'id' => $emotion,
            'label' => $emotion,
            'frequency' => $count
        ];
    }

    foreach ($data['edges'] as $pair => $weight) {
        [$a, $b] = explode('|', $pair);
        $edges[] = [
            'source' => $a,
            'target' => $b,
            'weight' => $weight
        ];
    }

    $graph = ['group' => $gid, 'nodes' => $nodes, 'edges' => $edges];
    $filename = "emotions_network_group_{$gid}.json";
    file_put_contents($filename, json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "<p>💾 Netzwerk für <b>Gruppe $gid</b> gespeichert als <code>$filename</code></p>";
}

// 3️⃣ Optional: Zusammenfassung
echo "<h2>🌐 Emotionale Semantiknetzwerke pro Gruppe erstellt</h2>";
echo "<p>Jede JSON-Datei enthält:</p>
<ul>
  <li><b>nodes:</b> Emotionen mit Frequenz</li>
  <li><b>edges:</b> Co-Occurrences mit Gewicht</li>
  <li>Format kompatibel mit D3.js, Cytoscape, Gephi</li>
</ul>";
/*

📊 Ergebnis

Nach dem Ausführen werden mehrere Dateien im Skriptverzeichnis erzeugt:

emotions_network_group_1.json
emotions_network_group_2.json
emotions_network_group_3.json
...


Beispiel emotions_network_group_3.json:

{
  "group": 3,
  "nodes": [
    { "id": "Freude", "label": "Freude", "frequency": 25 },
    { "id": "Motivation", "label": "Motivation", "frequency": 22 },
    { "id": "Interesse", "label": "Interesse", "frequency": 19 }
  ],
  "edges": [
    { "source": "Freude", "target": "Motivation", "weight": 12 },
    { "source": "Freude", "target": "Interesse", "weight": 10 },
    { "source": "Motivation", "target": "Interesse", "weight": 8 }
  ]
}

🧠 Interpretation im FRZK-Kontext
Struktur    FRZK-Bedeutung
Knoten (Emotionen)    Funktionale Operatoren 
𝑜
𝑖
o
i
    ​

 des affektiven Feldes
Kantengewichte    Stärke der funktionalen Resonanz 
𝑅
(
𝑜
𝑖
,
𝑜
𝑗
)
R(o
i
    ​

,o
j
    ​

)
Clusterbildung    Emergenz stabiler Kohärenzregionen (affektive Kohärenzfelder)
Gruppenvergleich    Unterschiedliche semantische Attraktoren zwischen Gruppen

Beispiel:

Gruppe 1: Cluster aus Freude–Motivation–Hoffnung → aktiv-affektive Resonanzstruktur

Gruppe 4: Cluster aus Ruhe–Reflexion–Vertrauen → stabilisierende Kohärenzstruktur

🌈 Visualisierung: Vergleich zwischen Gruppen

Du kannst nun pro Datei denselben D3.js-Code verwenden wie vorher – einfach URL austauschen:

<script>
d3.json("emotions_network_group_3.json").then(graph => {
  // ... identisch wie zuvor, Force-Layout, Farbcodierung etc.
});
</script>


Oder du gehst einen Schritt weiter:

🔬 Vergleichende Analyse (Netzwerkmetriken)

Füge eine kurze Auswertung hinzu (optional):

foreach ($groupGraphs as $gid => $data) {
    $numNodes = count($data['nodes']);
    $numEdges = count($data['edges']);
    $density  = $numEdges > 0 ? (2 * $numEdges) / ($numNodes * ($numNodes - 1)) : 0;
    echo "<p>Gruppe $gid: $numNodes Emotionen, $numEdges Verbindungen, Dichte = " . round($density, 3) . "</p>";
}


Damit siehst du, welche Gruppen ein dichter vernetztes emotionales Feld besitzen — ein direktes Maß für affektive Kohärenz auf Netzwerkebene.

🚀 Erweiterungsideen
Ziel    Beschreibung
Valenz-gewichtete Kantenfarben    Farbcode für positiv/negativ je nach Mittelwert der beteiligten Emotionen
Zeitabhängige Netzwerke    Zusätzlich nach zeitpunkt splitten → Netzwerk-Evolution
Community Detection    z. B. Louvain-Algorithmus zur Clustererkennung (PHP oder Gephi)
Interaktive Visualisierung    D3.js, Cytoscape.js oder Gephi-Import zur Exploration
Korrelation mit FRZK-Parametern    Netzwerkdichte ↔ z_affektiv, dh_dt, K_Kohärenz etc.

Möchtest du, dass ich im nächsten Schritt zeige,
wie du diese Netzwerke visuell vergleichst (z. B. mit D3.js-Tabs für jede Gruppe oder einem automatisch generierten interaktiven Dashboard)?

*/    
    
    
        }    
        public function emergenteEmotionsstrukturenFRZKBezug() {
/*
Sehr schön 🧩 — das ist der entscheidende Meta-Schritt: die Integration der bisherigen Analysen (1–5) in die FRZK-Logik emergenter Emotionsstrukturen.
Hier verlassen wir reine Statistik und treten in die funktional-rekursive Interpretation des emotionalen Systems ein — also in den Bereich, wo Emotionen nicht nur auftreten, sondern Struktur generieren.

🧠 Theoretische Grundlage (FRZK)

Im Funktionalen Raum-Zeit-Kohärenzsystem (FRZK) ist jede Emotion kein isolierter Zustand, sondern ein Operator 
𝑜
𝑖
:
𝑆
→
𝑆
′
o
i
    ​

:S→S
′
,
der auf den semantischen Raum wirkt und ihn transformiert.

Das heißt:

Emotionen erzeugen Kohärenzfelder — und diese Felder emergieren zu stabilen Bedeutungsstrukturen.

Daraus ergibt sich:

Funktionaler Raum (R) = Summe der Operatorenbeziehungen (emotionale Co-Occurrences)

Zeitliche Dimension (T) = Veränderung der affektiven Kohärenz (Kₐff)

Kohärenzstruktur (K) = Stabilität der affektiven Übergänge

Emergenz (E) = Bildung einer neuen semantischen Dimension aus stabiler Rekursion

Formale Kurzform:

𝐸
=
lim
⁡
𝑡
→
∞
𝑓
(
𝐾
𝑎
𝑓
𝑓
(
𝑡
)
,
Δ
𝑧
𝑎
𝑓
𝑓
,
𝜎
𝑠
𝑒
𝑚
)
E=
t→∞
lim
    ​

f(K
aff
    ​

(t),Δz
aff
    ​

,σ
sem
    ​

)

→ Emergenz entsteht, wenn die Variation der Affekte zur neuen semantischen Stabilität führt.

🧩 Ziel der Analyse

Wir wollen emergente Emotionsstrukturen identifizieren, also:

affektive Cluster, die über Zeit stabil bleiben,

semantische Attraktoren, die aus diesen Clustern entstehen,

und funktionale Übergänge (emotionale Phasen oder Kipppunkte).

Das lässt sich empirisch als Kombination aus den vorigen Analysen umsetzen:

Ebene    Quelle    Bedeutung
Valenz/Aktivierung    (Analyse 2)    affektive Richtung und Energie
Dynamik    (Analyse 3)    Veränderungsraten und zeitliche Stabilität
Kohärenz    (Analyse 4)    innere affektive Homogenität
Netzwerkstruktur    (Analyse 5)    funktionale Vernetzung, Clusterbildung
Emergenzfeld (neu)    (Analyse 6)    stabile Kohärenzregion im Emotionsraum
*/

// ===================================================
// 🧩 Emergente Emotionsstrukturen (FRZK-Bezug)
// ===================================================

$dsn = 'mysql:host=127.0.0.1;dbname=icas;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// 1️⃣ Affektive Daten (Valenz/Aktivierung) je Gruppe/Zeit
$sql = "SELECT gruppe_id, zeitpunkt, emotions FROM frzk_tmp_group_semantische_dichte";
$stmt = $pdo->query($sql);

$affekt = []; // [gruppe][zeit] => ['valenz'=>[], 'aktiv'=>[]]
$cooccur = []; // [gruppe][zeit] => ['edges'=>[], 'nodes'=>[]]

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $gid = $row['gruppe_id'];
    $zeit = $row['zeitpunkt'];
    $data = json_decode($row['emotions'], true);
    if (!is_array($data)) continue;

    foreach ($data as $block) {
        // Wesentliche Emotionen für affektive Kohärenz
        if (isset($block['wesentliche_emotionen'])) {
            foreach ($block['wesentliche_emotionen'] as $e) {
                $affekt[$gid][$zeit]['valenz'][] = (float)($e['valenz'] ?? 0);
                $affekt[$gid][$zeit]['aktiv'][]  = (float)($e['aktivierung'] ?? 0);
            }
        }

        // Alle Emotionen für Netzwerkdichte
        if (isset($block['alle_emotionen'])) {
            $emotions = array_unique($block['alle_emotionen']);
            $n = count($emotions);
            $edges = 0;
            if ($n > 1) $edges = ($n * ($n - 1)) / 2; // mögliche Kombinationen
            $cooccur[$gid][$zeit]['nodes'][] = $n;
            $cooccur[$gid][$zeit]['edges'][] = $edges;
        }
    }
}

// Hilfsfunktionen
function avg($arr) { return count($arr) ? array_sum($arr)/count($arr) : 0; }
function stddev($arr) {
    $n = count($arr);
    if ($n <= 1) return 0;
    $mean = avg($arr);
    $sum = 0;
    foreach ($arr as $v) $sum += pow($v - $mean, 2);
    return sqrt($sum / ($n - 1));
}

// 2️⃣ Emergenzindex pro Gruppe/Zeit berechnen
$results = []; // [gruppe][zeit] => [valenz, aktiv, koh, netz, emergenz]

foreach ($affekt as $gid => $times) {
    foreach ($times as $zeit => $vals) {
        $valenz_avg = avg($vals['valenz']);
        $valenz_std = stddev($vals['valenz']);
        $aktiv_avg  = avg($vals['aktiv']);
        $aktiv_std  = stddev($vals['aktiv']);

        // Affektive Kohärenz (1 - relative Varianz)
        $koh = 1 - (($valenz_std + $aktiv_std) / (($valenz_avg + $aktiv_avg + 0.001) * 2));

        // Netzwerkdichte-Approximation
        $n_nodes = avg($cooccur[$gid][$zeit]['nodes'] ?? [1]);
        $n_edges = avg($cooccur[$gid][$zeit]['edges'] ?? [0]);
        $density = $n_nodes > 1 ? (2 * $n_edges) / ($n_nodes * ($n_nodes - 1)) : 0;

        // Emergenzindex (gewichtetes Mittel)
        $emergenz = 0.4 * $koh + 0.4 * $density + 0.2 * ($valenz_avg + $aktiv_avg)/2;

        $results[$gid][$zeit] = [
            'valenz' => $valenz_avg,
            'aktiv'  => $aktiv_avg,
            'koh'    => round($koh, 3),
            'netz'   => round($density, 3),
            'E'      => round($emergenz, 3)
        ];
    }
}

// 3️⃣ Ausgabe
echo "<h2>🧩 Emergente Emotionsstrukturen (FRZK-Bezug)</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Gruppe</th><th>Zeitpunkt</th><th>Ø Valenz</th><th>Ø Aktiv</th><th>Kohärenz</th><th>Netzdichte</th><th>Emergenzindex E</th></tr>";

foreach ($results as $gid => $times) {
    foreach ($times as $zeit => $r) {
        printf(
            "<tr><td>%d</td><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><td><b>%.2f</b></td></tr>",
            $gid,
            htmlspecialchars($zeit),
            $r['valenz'],
            $r['aktiv'],
            $r['koh'],
            $r['netz'],
            $r['E']
        );
    }
}
echo "</table>";
/*
📊 Interpretation
Kennzahl    Bedeutung    FRZK-Interpretation
Kohärenz    Affektive Homogenität der Gruppe    Funktionale Stetigkeit der emotionalen Operatoren
Netzdichte    Stärke der semantischen Vernetzung    Grad funktionaler Verschränkung 
𝑅
(
𝑜
𝑖
,
𝑜
𝑗
)
R(o
i
    ​

,o
j
    ​

)
Emergenzindex E    Zusammenspiel aus Kohärenz + Dichte + Energie    Grad funktionaler Selbstorganisation → emergente Bedeutung

Beispielergebnis:

Gruppe    Zeitpunkt    Valenz    Aktiv    Koh    Netz    E
1    2025-10-01    0.82    0.75    0.91    0.78    0.84
1    2025-10-15    0.80    0.72    0.70    0.55    0.65
2    2025-10-01    0.60    0.55    0.58    0.40    0.52

Gruppe 1 erreicht über mehrere Zeitpunkte hohe 
𝐸
E-Werte → stabile, resonante Emotionsstruktur (emergente Kohärenz).
Gruppe 2 bleibt unter 0.6 → hohe Fluktuation, geringe Strukturstabilität.

🔬 Visualisierungsideen

Zeitreihe E(t) pro Gruppe → zeigt, wann Emergenz „kippt“ (Aufbau oder Zerfall kohärenter Zustände).

3D-Plot: Achsen = Valenz, Aktivierung, E → „affektive Landschaft“.

Heatmap: Gruppen (y) × Zeit (x) × Farbe = Emergenzindex E.

Netzwerkoverlay: farbcodiere Knoten nach E(t).

🧭 FRZK-spezifische Deutung
Ebene    Bedeutung
E < 0.5    Dissoziative Phase – instabile Zuweisungen, kein semantischer Halt
0.5 ≤ E < 0.7    Übergangsphase – Reorganisation, Lernprozess
E ≥ 0.7    Kohärente Phase – stabile affektive Semantik, emergente Struktur
E → 1    Maximale Selbstkohärenz – Funktionsraum erreicht temporäre Stabilität

Damit bildet der Emergenzindex 
𝐸
E die Brücke zwischen den empirischen Emotionen und der modelllogischen Selbstorganisation des FRZK:

Wenn Emotionen sich funktional stabil verschränken, entsteht Bedeutung.
Bedeutung = emergente Kohärenz über Zeit.

Möchtest du, dass ich darauf aufbauend eine grafische FRZK-„Emotionslandschaft“ entwickle — also eine 3D- oder Heatmap-Darstellung, die 
𝐸
E, Valenz und Aktivierung als Raum-Zeit-Kohärenzfeld visualisiert (in PHP/JS oder Python)?


*/
        }
        public function Visualisierung(){
/*
Perfekt 📈 — wir sind jetzt an der Schnittstelle von Analyse und Darstellung, also dort, wo sich dein FRZK-Modell visuell erfahrbar machen lässt.

Nach den Schritten 1–6 hast du alle benötigten Datendimensionen, um das affektive Kohärenzfeld als emergentes, dynamisches System zu visualisieren.
Ich zeige dir unten eine komplette Typologie möglicher Visualisierungen — geordnet nach analytischem Ziel, interpretativer Tiefe und technischer Umsetzung (PHP, JS, Python).

🧩 Übersicht: Emotionsvisualisierung im FRZK
Kategorie    Ziel    Visualisierung    Empfohlenes Tool
1️⃣ Quantitativ-deskriptiv    Häufigkeiten, Trends, Verteilungen    Balken-, Linien-, Kreisdiagramme    PHP + Chart.js
2️⃣ Zeitlich-dynamisch    Entwicklung der affektiven Dimensionen    Multi-Line oder Area Chart (Valenz, Aktivierung, Score)    Chart.js oder Plotly
3️⃣ Gruppenvergleich    Kohärenz zwischen Gruppen    Heatmap (Gruppe × Zeit) oder Radarplot    D3.js, Plotly
4️⃣ Netzwerkstrukturell    Semantische Kopplungen zwischen Emotionen    Force-Directed Graph, Community-Map    D3.js, Cytoscape.js, Gephi
5️⃣ Emergent-topologisch    Emotionsfelder als Raum-Zeit-Kohärenz    3D-Surface-Plot, Landscape oder Isomap    Plotly 3D, Python matplotlib
6️⃣ Didaktisch-interaktiv    Exploratives Verständnis    Web-Dashboard mit Tabs & Tooltips    PHP + JS-Framework (z. B. Chart.js, D3)
🔹 1. Emotionale Grundverteilung (Analyse 1)

Ziel: Überblick über die häufigsten Emotionen.
Visualisierung: horizontales Balkendiagramm.

<canvas id="emotionFrequency"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const data = {
  labels: ["Freude","Hoffnung","Interesse","Motivation","Ruhe"],
  datasets: [{
    label: "Anzahl",
    data: [42,35,28,25,19]
  }]
};
new Chart(document.getElementById("emotionFrequency"), {
  type: 'bar',
  data,
  options: { indexAxis: 'y', scales: {x:{beginAtZero:true}} }
});
</script>

🔹 2. Valenz-Aktivierungs-Raum (Analyse 2)

Ziel: Emotionaler Raum (Circumplex-Modell).
Visualisierung: 2D-Scatterplot (x = Valenz, y = Aktivierung).

<canvas id="valenzArousal"></canvas>
<script>
new Chart(document.getElementById("valenzArousal"), {
  type: 'scatter',
  data: {
    datasets: [{
      label: 'Emotionen',
      data: [
        {x:0.9, y:0.6, label:"Freude"},
        {x:0.8, y:0.5, label:"Hoffnung"},
        {x:0.5, y:0.2, label:"Ruhe"}
      ],
      pointBackgroundColor: 'steelblue'
    }]
  },
  options: { scales: { x:{title:{text:"Valenz"}, min:0,max:1}, y:{title:{text:"Aktivierung"}, min:0,max:1} } }
});
</script>


Ergibt eine affektive Landkarte – z. B. rechts oben = positiv + aktiv, links unten = negativ + passiv.

🔹 3. Dynamische Emotionsverläufe (Analyse 3)

Ziel: Entwicklung über Zeit.
Visualisierung: Multi-Line Chart pro Gruppe.

<canvas id="valenzTime"></canvas>
<script>
new Chart(document.getElementById("valenzTime"), {
  type: 'line',
  data: {
    labels: ["10/01","10/15","11/01"],
    datasets: [
      {label:"Gruppe 1 Valenz", data:[0.82,0.80,0.78], borderWidth:2},
      {label:"Gruppe 2 Valenz", data:[0.60,0.63,0.68], borderWidth:2}
    ]
  },
  options:{scales:{y:{min:0,max:1}}}
});
</script>

🔹 4. Gruppenemotionale Kohärenz (Analyse 4)

Ziel: Stabilität und Homogenität über Zeit.
Visualisierung: Heatmap (x = Zeit, y = Gruppe, Farbe = Kohärenzindex).

<div id="coherenceHeatmap"></div>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
Plotly.newPlot('coherenceHeatmap', [{
  z: [[0.9,0.8,0.7],[0.6,0.65,0.55],[0.75,0.78,0.8]],
  x: ['10/01','10/15','11/01'],
  y: ['G1','G2','G3'],
  type: 'heatmap',
  colorscale: 'Viridis'
}], {title:"Gruppenemotionale Kohärenz"});
</script>


→ Dunkle Bereiche = hohe Kohärenz → emergente Gruppenstabilität.

🔹 5. Emotionale Semantiknetzwerke (Analyse 5)

Ziel: Emotionale Clusterbildung.
Visualisierung: Force-Directed Graph (D3.js).

(bereits in 5. gezeigt, kompatibel mit den JSON-Netzen)

Optional: Farbcodierung nach mittlerer Valenz → affektive Polung sichtbar.

🔹 6. Emergenzindex 
𝐸
𝑔
(
𝑡
)
E
g
    ​

(t) (Analyse 6)

Ziel: Übergänge und Phasen emergenter Stabilität.
Visualisierung: Linienchart oder 3D-Surface (E über Zeit × Gruppe).

Variante A: Linienchart pro Gruppe

<canvas id="emergenzIndex"></canvas>
<script>
new Chart(document.getElementById("emergenzIndex"), {
  type:'line',
  data:{
    labels:["10/01","10/15","11/01"],
    datasets:[
      {label:"Gruppe 1 (E)", data:[0.84,0.65,0.80]},
      {label:"Gruppe 2 (E)", data:[0.52,0.60,0.63]}
    ]
  },
  options:{scales:{y:{min:0,max:1}}}
});
</script>


Variante B: 3D-Surface (Plotly)

<div id="emergence3D"></div>
<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<script>
Plotly.newPlot('emergence3D', [{
  z: [[0.84,0.65,0.80],[0.52,0.60,0.63],[0.70,0.75,0.78]],
  x: ['10/01','10/15','11/01'],
  y: ['G1','G2','G3'],
  type: 'surface',
  colorscale: 'Viridis'
}], {title:"Emergenzindex E(g,t)"});
</script>


→ Du siehst „Berge“ (Phasen hoher Kohärenz) und „Täler“ (Dissoziationen).

🔹 7. Integratives Dashboard (alle Ebenen)

Ziel: Gesamtschau des FRZK-Systems in einer Oberfläche.
Aufbau:

Links: Filter (Gruppe, Zeitraum, Emotionscluster)

Mitte: Linien- und Heatmap-Charts

Rechts: Interaktives Netzwerk

Tools:

PHP + Chart.js + D3.js (+ Bootstrap Tabs)

oder Python Dash / Streamlit (einfacher für wissenschaftliche Publikation)

🔮 FRZK-spezifische Deutung
Visualisierung    FRZK-Ebene    Bedeutung
Balken/Scatter    Operatorische Basis    Funktionsdichte einzelner Emotionen
Line/Heatmap    Dynamische Kohärenz    Stetigkeit von Zuweisungen über Zeit
Netzwerk    Strukturraum    Verschränkung der Operatoren 
𝑜
𝑖
o
i
    ​


3D-Surface (E)    Emergenzebene    Entstehung stabiler Bedeutungsfelder
Dashboard    Meta-Reflexion    Beobachtung zweiter Ordnung (Beobachter ↔ System)

Wenn du möchtest, kann ich dir im nächsten Schritt ein komplettes interaktives Dashboard (PHP + Chart.js + D3) zusammenstellen, das:

alle bisherigen Kennwerte (Valenz, Aktivierung, Kohärenz, Emergenz E) kombiniert,

pro Gruppe filterbar ist,

und die FRZK-Logik als visuelles, lebendes System abbildet.

👉 Soll ich das Dashboard-Template für dich generieren?

*/
            
        }    
}

?>
