# Multi‑Raum Kachel

## Kurzbeschreibung

Die Multi‑Raum Kachel stellt mehrere Räume in einem Raster dar. Jeder Raum kann ein eigenes Bild, einen zentralen Raumnamen, Info‑Leisten und eine Menüleiste mit Schaltern besitzen. Globale Standardwerte erleichtern die einheitliche Gestaltung; pro Raum können diese Werte überschrieben werden. Optional: Ein Bildfilter simuliert den Lichtstatus über das Hintergrundbild. Licht aus = abdunkeln des Bildes, Licht an = Dimmwert bestimmt die Intensität (100 ≈ kein Effekt, 0 = maximal).

## Backend‑Konfiguration

### Kachel- und Raster-Einstellungen
- **Volle Kachelhöhe nutzen**: Reduziert den oberen Rand-Abstand auf das gleiche Maß wie der untere Rand-Abstand.
- **individueller Randabstand**: Setzt einen individuellen Randabstand. -1 = Standardwerte.
- **Minimale Kachelbreite / -höhe (px)**: Basisgröße der Kacheln für die automatische Spaltenberechnung.
- **Abstand (px)**: Abstand zwischen Kacheln.
- **Eckenradius (px)**: Abrundung der Kachelecken.
- **Spalten (0 = auto)**: Feste Spaltenanzahl, 0 = automatische Verteilung.

### Globale Standardwerte
- **Buttonfarben aus Hintergrundbild**: Leitet global Buttonfarben aus dem Kachelbild ab.
- **Infoleiste**
  - Schriftgröße
  - Schriftfarbe
  - Hintergrund‑Transparenz 0..100%
  - Hintergrundfarbe
  - Transparenz bei Statusfarben: Steuert, ob die Hintergrund‑Transparenz auch für Farben aus Profilen/Darstellungen gilt (true = ja, false = Statusfarben undurchsichtig).
- **Menü‑Leiste**
  - Schriftgröße
  - Schriftfarbe
  - Hintergrund‑Transparenz 0..100%
  - Hintergrundfarbe
  - **Priorität**: Wenn "Buttonfarben aus Hintergrundbild" aktiv ist (global oder pro Raum), werden Profil-/Assoziationsfarben ignoriert.
- **Kachel**
  - Hintergrundfarbe
  - Bildtransparenz 0..100%
- **Bildfilter für Lichtstatussimulation über Hintergrundbild**
  - Helligkeit (min/max, 0..1), Default 0.2/1.0
  - Kontrast (min/max, 0..1), Default 0.9/1.0
  - Graustufen (min/max, 0..1), Default 0.0/0.5
- **Raumname (global)**
  - Schriftgröße
  - Schriftfarbe

### Räume

#### Info‑Leiste
- **Zentriert in der Mitte**: Fasst links/rechts zusammen und zentriert.
- **Schriftgröße / Schriftfarbe**: Per Raum (−1 = globale Defaults übernehmen).
- **Hintergrund‑Transparenz / Hintergrundfarbe**: Einheitlich für links/mitte/rechts (−1 = global).
- **Info‑Elemente (Liste)**: Dynamische Einträge je Bereich (links/mitte/rechts), mit Name/Icon/Wert und optionalem Label‑Override.
 - **Profil-/Darstellungsfarbe als Hintergrund** (pro Eintrag): Übernimmt automatisch die Hintergrundfarbe aus dem Variablen‑Profil bzw. der Darstellung abhängig vom aktuellen Variablenwert. Änderungen am Variablenwert aktualisieren die Farbe live in der Kachel.
 - **Hintergrundfarbe: Status = True**: Überschreibt die Hintergrundfarbe, wenn der Status True ist. Wird z.B. benötigt bei Bool-Variablen mit der Darstellung Schalter/Switch weil dort keine Farben konfiguriert werden können.
 - **Hintergrundfarbe: Status = False**: Überschreibt die Hintergrundfarbe, wenn der Status False ist. Wird z.B. benötigt bei Bool-Variablen mit der Darstellung Schalter/Switch weil dort keine Farben konfiguriert werden können.

#### Raumname
- **Name anzeigen**: Schaltet die Anzeige des Raumnamens pro Raum ein/aus.
- **Raumname**: Anzeigetext in der Kachel.
- **Objekt welche beim Klick geöffnet wird**: Zielobjekt bei Klick (optional).
- **Schriftgröße / Schriftfarbe**: (−1 = globale Defaults übernehmen).

#### Hintergrund
- **Hintergrundbild Lichtstatus an** (Bild 1) und **Hintergrundbild Lichtstatus aus** (Bild 2):
  - Wenn Bild 2 gesetzt ist, wird der Bild‑Filter deaktiviert.
  - Stattdessen wird Bild 1 über Bild 2 gelegt und abhängig vom Lichtstatus/Dimmwert transparent.
    - Lichtstatus = aus → Bild 1 wird 100% transparent (nur Bild 2 sichtbar).
    - Mit Dimmwert → Transparenz entspricht dem Helligkeitsverlauf (0 = keine Transparenz, 100 = volle Transparenz von Bild 1).
- **Bildtransparenz**: −1..100% (−1 = globaler Default), 0..100 = explizit.
- **Hintergrundfarbe**: (−1 = globaler Default).
- **Lichtstatus / Dimmwert (0..100)**: Steuert den Bildfilter.
  - Licht aus (false) → maximaler Effekt (Dimmwert ignoriert).
  - Licht an (true) → Dimmwert bestimmt (100 ≈ kein Effekt, 0 = maximal).
  - Ohne Lichtstatus steuert nur der Dimmwert.
- **Bild‑Filterkonfiguration** (Popup): Per‑Raum Bereiche mit Vererbung.
  - Helligkeit (min/max, 0..1), −1 = global übernehmen
  - Kontrast (min/max, 0..1), −1 = global übernehmen
  - Graustufen (min/max, 0..1), −1 = global übernehmen

#### Menü‑Leiste
- **Menü anzeigen**: Menüleiste ein-/ausblenden.
- **Schriftgröße / Schriftfarbe**: (−1 = global).
- **Hintergrund‑Transparenz / Hintergrundfarbe**: (−1 = global).
- **Buttonfarben aus Hintergrundbild**: Pro Raum (zusätzlich zum globalen Schalter).
  - Wenn aktiv (global oder pro Raum), werden Profil-/Assoziationsfarben (ColorOn/ColorOff/Profilfarbe) nicht verwendet. Es gelten ausschließlich die aus dem Hintergrundbild extrahierten Palettenfarben.
- **Schalter Ausrichtung / Schalter gleichmäßig verteilen**: Layoutsteuerung.
- **Menü‑Elemente (Liste)**: Dynamische Buttons (Variable optional mit Aktion, optional „Objekt öffnen“, Name/Icon/Wert, Label‑Override, Breite/Maximale Breite).
  - Szenensteuerung: Wähle bei „Szeneninstanz“ eine Instanz des Moduls „Szenen-Steuerung“. Es wird automatisch ein Multi‑Button mit allen Szenen (Scene1..N) erzeugt.

