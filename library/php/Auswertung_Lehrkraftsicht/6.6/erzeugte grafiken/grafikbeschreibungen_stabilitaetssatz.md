# Grafiken zum Stabilitätssatz des korrelationsgebundenen FRZK-Zustandsraums

## 1. Korrelationsmatrix – ALL
Datei: grafik_stabilitaet_heatmap_all.png
Inhalt: Heatmap der empirischen Korrelationsmatrix des vollständigen Datensatzes `korrelationsstruktur_lehrkraftsicht_alle.json` mit n=571. Dargestellt sind alle paarweisen Korrelationen der sieben FRZK-Dimensionen. Die Grafik zeigt die mittlere Gesamtstruktur des Systems und macht sichtbar, welche Achsen im Gesamtdatensatz gemeinsam variieren und welche sich antagonistisch verhalten.

## 2. Korrelationsmatrix – Lehrkraft 1
Datei: grafik_stabilitaet_heatmap_lehrkraft_1.png
Inhalt: Heatmap der empirischen Korrelationsmatrix des Teilraums `korrelationsstruktur_lehrkraftsicht_1.json` mit n=360. Die Grafik visualisiert die stärkere Verdichtung der funktionalen Kopplungen bei Lehrkraft 1 und dient als empirische Grundlage für die Annahme eines stabilen Attraktorraums.

## 3. Korrelationsmatrix – nicht Lehrkraft 1
Datei: grafik_stabilitaet_heatmap_nicht_lehrkraft_1.png
Inhalt: Heatmap der empirischen Korrelationsmatrix des Teilraums `korrelationsstruktur_lehrkraftsicht_nicht1.json` mit n=211. Die Grafik zeigt die schwächere und partiell fragmentierte Kopplungsordnung der übrigen Lehrkräfte.

## 4. Kohärenzgradient der Korrelationsstruktur
Datei: grafik_stabilitaet_kohärenzgradient.png
Inhalt: Balkendiagramm der mittleren absoluten Off-Diagonal-Korrelation |ρ| für die drei Vergleichsräume. Die Größe dient als empirischer Kohärenzindikator: Je höher der Wert, desto dichter ist die funktionale Verschränkung des Zustandsraums. Die Grafik illustriert den im Text formulierten Gradienten C(LK1) > C(ALL) > C(nicht LK1).

## 5. Zustandsraumprojektion – ALL
Datei: grafik_stabilitaet_pca_all.png
Inhalt: Projektion der Realdaten des Gesamtdatensatzes auf die ersten beiden Hauptkomponenten. Jeder Punkt entspricht einem beobachteten Zustandsvektor. Die Grafik zeigt die räumliche Verdichtung beziehungsweise Streuung der empirischen Zustände im reduzierten Zustandsraum.

## 6. Zustandsraumprojektion – Lehrkraft 1
Datei: grafik_stabilitaet_pca_lehrkraft_1.png
Inhalt: Projektion der Realdaten von Lehrkraft 1 auf die ersten beiden Hauptkomponenten. Die kompaktere Punktwolke verweist auf eine höhere strukturelle Stabilität des Teilraums.

## 7. Zustandsraumprojektion – nicht Lehrkraft 1
Datei: grafik_stabilitaet_pca_nicht_lehrkraft_1.png
Inhalt: Projektion der Realdaten der übrigen Lehrkräfte auf die ersten beiden Hauptkomponenten. Die tendenziell breitere Streuung zeigt eine geringere Zustandsbindung.

## 8. Simulation eines korrelationsgebundenen Attraktorfelds – ALL
Datei: grafik_stabilitaet_simulation_all.png
Inhalt: Modellnahe 2D-Simulation eines Attraktorfelds, dessen Kontraktionsstärke an die empirische mittlere Korrelationsdichte des Gesamtdatensatzes gekoppelt ist. Die Pfeile zeigen, wie Zustände im theoretischen Raum in Richtung eines kohärenten Zentrums gezogen werden.

## 9. Simulation eines korrelationsgebundenen Attraktorfelds – Lehrkraft 1
Datei: grafik_stabilitaet_simulation_lehrkraft_1.png
Inhalt: Modellnahe 2D-Simulation des Attraktorfelds für Lehrkraft 1. Die stärkere Kontraktion visualisiert den im Stabilitätssatz beschriebenen engeren Attraktorraum.

## 10. Simulation eines korrelationsgebundenen Attraktorfelds – nicht Lehrkraft 1
Datei: grafik_stabilitaet_simulation_nicht_lehrkraft_1.png
Inhalt: Modellnahe 2D-Simulation des Attraktorfelds der übrigen Lehrkräfte. Die schwächere Kontraktion steht für einen diffuseren und weniger stabil gebundenen Zustandsraum.
