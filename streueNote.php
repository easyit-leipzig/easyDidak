<?php
// --- Datenbankverbindung ---
$dsn = "mysql:host=localhost;dbname=icas;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}

// --- Teilnehmer abrufen ---
$stmt = $pdo->query("SELECT DISTINCT teilnehmer_id, concat(Vorname, ' ', Nachname) as tn FROM mtr_persoenlichkeit, std_teilnehmer where mtr_persoenlichkeit.teilnehmer_id=std_teilnehmer.id ORDER BY Vorname");
$teilnehmer = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Notenentwicklungsformular (PDO)</title>
</head>
<body>
<h2>Notenentwicklungsformular</h2>

<form method="POST">
    <label for="teilnehmer_id">Teilnehmer:</label>
    <select name="teilnehmer_id" id="teilnehmer_id" required onchange="this.form.submit()">
            <option value="">-- Teilnehmer wählen --</option>
            <?php foreach ($teilnehmer as $row): ?>
                <option value="<?= htmlspecialchars($row['teilnehmer_id']) ?>"
                    <?= (isset($_POST['teilnehmer_id']) && $_POST['teilnehmer_id'] == $row['teilnehmer_id']) ? 'selected' : '' ?>><?= htmlspecialchars($row['tn']) ?>
                </option>
        <?php endforeach; ?>
    </select>
</form>

<?php
// --- Falls Teilnehmer gewählt ---
if (!empty($_POST['teilnehmer_id']) && empty($_POST['update'])) {
    $id = (int) $_POST['teilnehmer_id'];

    $stmt = $pdo->prepare("
        SELECT MIN(datum) AS erstes, MAX(datum) AS letztes
        FROM mtr_persoenlichkeit
        WHERE teilnehmer_id = :id
    ");
    $stmt->execute(['id' => $id]);
    $dates = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<p>Erstes Datum: <b>" . htmlspecialchars($dates['erstes']) . "</b><br>";
    echo "Letztes Datum: <b>" . htmlspecialchars($dates['letztes']) . "</b></p>";
    ?>

    <form method="POST">
        <input type="hidden" name="teilnehmer_id" value="<?= $id ?>">
        <label>Startnote: <input type="number" step="0.1" name="startnote" required></label><br>
        <label>Zielnote: <input type="number" step="0.1" name="zielnote" required></label><br>
        <label>Streuung (max. ±): <input type="number" step="0.01" name="streuung" required></label><br><br>
        <button type="submit" name="update" value="1">Noten aktualisieren</button>
    </form>
<?php
}

// --- Wenn Formular abgeschickt wird ---
if (isset($_POST['update'])) {
    $id = (int) $_POST['teilnehmer_id'];
    $start = (float) $_POST['startnote'];
    $ziel = (float) $_POST['zielnote'];
    $streu = (float) $_POST['streuung'];

    $stmt = $pdo->prepare("
        SELECT id, datum FROM mtr_persoenlichkeit
        WHERE teilnehmer_id = :id ORDER BY datum ASC
    ");
    $stmt->execute(['id' => $id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $anzahl = count($records);
    if ($anzahl === 0) {
        echo "<p style='color:red;'>Keine Datensätze gefunden.</p>";
    } else {
        $update = $pdo->prepare("UPDATE mtr_persoenlichkeit SET note = :note WHERE id = :id");

        $aktuelle_note = $start;
        foreach ($records as $i => $row) {
            $progress = $anzahl > 1 ? $i / ($anzahl - 1) : 0;

            // Gewichtung: nähert sich Zielwert progressiv an
            $richtung = ($ziel - $aktuelle_note);
            $konvergenz = 0.3 + 0.7 * $progress; // wird stärker Richtung Ende
            $zufall = (mt_rand(-1000, 1000) / 1000) * $streu * (1 - $progress); // Streuung nimmt ab

            // neue Note = alte + (Annäherung + Zufall)
            $aktuelle_note += $richtung * $konvergenz * 0.1 + $zufall;

            // optional kleine Dämpfung, damit sie sich dem Ziel nähert
            $aktuelle_note = $aktuelle_note + 0.1 * ($ziel - $aktuelle_note);

            // Begrenzung und Rundung
            $aktuelle_note = max(1, min(6, round($aktuelle_note, 2)));

            // letzte Note exakt Zielwert setzen
            if ($i === $anzahl - 1) $aktuelle_note = $ziel;

            $update->execute(['note' => $aktuelle_note, 'id' => $row['id']]);
        }

        echo "<p style='color:green;'>Noten für Teilnehmer <b>$id</b> wurden mit konvergierender Streuung aktualisiert.</p>";
    }
}
?>
</body>
</html>
