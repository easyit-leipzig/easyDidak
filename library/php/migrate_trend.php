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

// Erzeuge die Trenddaten mit einer Funktion zur Veränderungsrate
for ($i = 0; $i < $anzahlDaten; $i++) {
    // Berechne den idealen Wert
    $idealWert = $startwert + $schrittweite * $i;
    
    // Verwende eine Funktion, um die Veränderungsrate zu bestimmen (z.B. e^x)
    // Beispiel: f(x) = e^x, wobei wir den Exponentialfaktor modifizieren
    $veranderungsrate = exp($i / 10); // Der Faktor 10 steuert das Wachstum der Exponentialfunktion

    // Modifiziere den idealen Wert mit der Veränderungsrate
    $wertMitFunktion = $idealWert * $veranderungsrate;
    
    // Füge Streuung hinzu
    $streuungWert = rand(-$streuung, $streuung);
    
    // Füge den Wert zum Array hinzu
    $daten[] = $wertMitFunktion + $streuungWert;
}

// Ausgabe der Daten als JSON für Chart.js
echo json_encode($daten);
?>
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

// Erzeuge die Trenddaten mit einer quadratischen Funktion zur Veränderungsrate
for ($i = 0; $i < $anzahlDaten; $i++) {
    // Berechne den idealen Wert
    $idealWert = $startwert + $schrittweite * $i;
    
    // Verwende eine Funktion, um die Veränderungsrate zu bestimmen (z.B. -x^2)
    // Beispiel: f(x) = -x^2, wobei wir i in die quadratische Funktion einsetzen
    $veranderungsrate = -pow($i / 10, 2);  // Der Faktor 10 steuert die Skalierung des quadratischen Einflusses

    // Modifiziere den idealen Wert mit der Veränderungsrate
    $wertMitFunktion = $idealWert + $veranderungsrate;
    
    // Füge Streuung hinzu
    $streuungWert = rand(-$streuung, $streuung);
    
    // Füge den Wert zum Array hinzu
    $daten[] = $wertMitFunktion + $streuungWert;
}

// Ausgabe der Daten als JSON für Chart.js
echo json_encode($daten);
?>
<?php
// Parameter
$startwert = 50;      // Startwert
$zielwert = 100;      // Zielwert
$anzahlDaten = 90;    // Anzahl der Datenpunkte
$streuung = 5;        // Maximale Streuung (z.B. +/- 5)

// Array für die Daten
$daten = [];

// Berechne die Schrittweite für den linearen Trend (konstant)
$schrittweite = ($zielwert - $startwert) / ($anzahlDaten - 1);

// Konstante Werte für die lineare Funktion f(x) = m * x + b
$m = 0.1;  // Steigung
$b = 0;    // Achsenabschnitt

// Erzeuge die Trenddaten mit einer linearen Veränderungsrate
for ($i = 0; $i < $anzahlDaten; $i++) {
    // Berechne den idealen Wert mit einer linearen Veränderung (normale Steigung)
    $idealWert = $startwert + $schrittweite * $i;

    // Berechne den Einfluss der linearen Funktion auf die Veränderungsrate (z.B. f(x) = m * x + b)
    $veranderungsrate = $m * $i + $b;

    // Modifiziere den idealen Wert mit der Veränderungsrate
    $wertMitFunktion = $idealWert + $veranderungsrate;

    // Füge Streuung hinzu
    $streuungWert = rand(-$streuung, $streuung);

    // Füge den Wert zum Array hinzu
    $daten[] = $wertMitFunktion + $streuungWert;
}

// Ausgabe der Daten als JSON für Chart.js
echo json_encode($daten);
?>
