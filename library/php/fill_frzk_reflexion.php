<?php
// 🪞 fill_frzk_reflexion.php
// Erzeugt und befüllt die Tabelle frzk_reflexion auf Basis von frzk_semantische_dichte
// Variante A – ohne FOREIGN KEY, mit INDEX(teilnehmer_id)

header('Content-Type: text/plain; charset=utf-8');

// --- DB-Verbindung ---
$pdo = new PDO("mysql:host=localhost;dbname=icas;charset=utf8mb4", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- Tabelle anlegen (Variante A) ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS frzk_reflexion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teilnehmer_id INT NOT NULL,
    zeitpunkt DATETIME NOT NULL,
    reflexionsgrad FLOAT DEFAULT NULL,
    meta_kohärenz FLOAT DEFAULT NULL,
    selbstbezug_index FLOAT DEFAULT NULL,
    reflexions_marker VARCHAR(20) DEFAULT NULL,
    bemerkung TEXT DEFAULT NULL,
    INDEX (teilnehmer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
");

// --- Alle semantischen Dichte-Daten laden ---
$stmt = $pdo->query("SELECT * FROM frzk_semantische_dichte ORDER BY teilnehmer_id, zeitpunkt");
$rows = $stmt->fetchAll();

// --- Schlüsselbegriffe für Selbstbezug erkennen ---
$selfWords = ["selbst", "ich", "motivation", "zweifel", "bewusst", "reflexion", "identität", "selbstvertrauen"];
$reflexiveTypes = ["reflexiv", "metakognitiv"];

// --- Gruppieren nach Teilnehmer ---
$grouped = [];
foreach ($rows as $r) {
    $tid = $r["teilnehmer_id"];
    $grouped[$tid][] = $r;
}

// --- Insert vorbereiten ---
$insert = $pdo->prepare("
    INSERT INTO frzk_reflexion
    (teilnehmer_id, zeitpunkt, reflexionsgrad, meta_kohärenz, selbstbezug_index, reflexions_marker, bemerkung)
    VALUES (:tid, :zeit, :grad, :meta, :self, :marker, :bem)
");

// --- Berechnung pro Teilnehmer ---
foreach ($grouped as $tid => $data) {

    // 1️⃣ Meta-Kohärenz – Kohärenz der Kohärenz (1 - Varianz von dh_dt)
    $dhValues = array_column($data, "dh_dt");
    $meanDh = count($dhValues) ? array_sum($dhValues) / count($dhValues) : 0;
    $varDh = count($dhValues) > 1
        ? array_sum(array_map(fn($v) => pow($v - $meanDh, 2), $dhValues)) / count($dhValues)
        : 0;
    $meta = max(0, 1 - $varDh);

    // 2️⃣ Selbstbezug-Index (Anteil selbstreferenzieller Emotionen)
    $selfCount = 0;
    $emoCount = 0;
    foreach ($data as $d) {
        if (!empty($d["emotions"])) {
            $emos = array_map("trim", explode(",", strtolower($d["emotions"])));
            foreach ($emos as $e) {
                if ($e === "") continue;
                $emoCount++;
                foreach ($selfWords as $w) {
                    if (str_contains($e, $w)) {
                        $selfCount++;
                        break;
                    }
                }
            }
        }
    }
    $selfIndex = $emoCount > 0 ? min(1, $selfCount / $emoCount) : 0;

    // 3️⃣ Stabilität (Mittelwert aus stabilitaet_score)
    $stabValues = array_column($data, "stabilitaet_score");
    $stabilitaet = count($stabValues) ? array_sum($stabValues) / count($stabValues) : 0;

    // 4️⃣ Reflexionsgrad (gewichtetes Mittel)
    $grad = 0.6 * $stabilitaet + 0.4 * $selfIndex;

    // 5️⃣ Marker
    if ($grad < 0.33) $marker = "niedrig";
    elseif ($grad < 0.66) $marker = "mittel";
    else $marker = "hoch";

    // 6️⃣ Bemerkung
    $bem = sprintf(
        "Reflexionsgrad: %.2f | Meta-Kohärenz: %.2f | Selbstbezug: %.2f | Stabilität: %.2f",
        $grad, $meta, $selfIndex, $stabilitaet
    );

    // 7️⃣ Insert (aktueller Zeitwert)
    $insert->execute([
        ":tid"    => $tid,
        ":zeit"   => end($data)["zeitpunkt"],
        ":grad"   => $grad,
        ":meta"   => $meta,
        ":self"   => $selfIndex,
        ":marker" => $marker,
        ":bem"    => $bem
    ]);
}

echo "✅ Tabelle frzk_reflexion erfolgreich erstellt und befüllt (Variante A, ohne Foreign Key).\n";
?>
