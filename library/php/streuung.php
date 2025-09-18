<?php
// Parameter
$startwert = 50;      // Startwert
$zielwert = 100;      // Zielwert
$anzahlDaten = 90;    // Anzahl der Datenpunkte
$streuung = 5;        // Maximale Streuung (z.B. +/- 5)

// Array für die Daten
$daten = [];

// Berechne die Schrittweite für den Trend
$schrittweite = ($zielwert - $startwert) / ($anzahlDaten - 1);

// Erzeuge die Trenddaten mit Streuung
for ($i = 0; $i < $anzahlDaten; $i++) {
    // Berechne den idealen Wert
    $idealWert = $startwert + $schrittweite * $i;
    
    // Füge Streuung hinzu
    $streuungWert = rand(-$streuung, $streuung);
    
    // Füge den Wert zum Array hinzu
    $daten[] = $idealWert + $streuungWert;
}

// Ausgabe der Daten als JSON für Chart.js
echo json_encode($daten);
?>
