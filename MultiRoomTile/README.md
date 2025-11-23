# Multi‑Raum Kachel

## Kurzbeschreibung

Die Multi‑Raum Kachel stellt mehrere Räume in einem Raster dar. Jeder Raum kann ein eigenes Bild, einen zentralen Raumnamen, Info‑Leisten und eine Menüleiste mit Schaltern besitzen. Globale Standardwerte erleichtern die einheitliche Gestaltung; pro Raum können diese Werte überschrieben werden. Optional: Ein Bildfilter simuliert den Lichtstatus über das Hintergrundbild. Licht aus = abdunkeln des Bildes, Licht an = Dimmwert bestimmt die Intensität (100 ≈ kein Effekt, 0 = maximal).

## Backend‑Konfiguration

### Rastereinstellungen
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
- **Menüleiste**
  - Schriftgröße
  - Schriftfarbe
  - Hintergrund‑Transparenz 0..100%
  - Hintergrundfarbe
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

#### Raumname
- **Raumname**: Anzeigetext in der Kachel.
- **Objekt welche beim Klick geöffnet wird**: Zielobjekt bei Klick (optional).
- **Schriftgröße / Schriftfarbe**: (−1 = globale Defaults übernehmen).

#### Hintergrund
- **Hintergrundbild**: Medienobjekt (Bild) des Raums.
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
- **Schalter Ausrichtung / Schalter gleichmäßig verteilen**: Layoutsteuerung.
- **Menü‑Elemente (Liste)**: Dynamische Buttons (Variable optional mit Aktion, optional „Objekt öffnen“, Name/Icon/Wert, Label‑Override, Breite/Maximale Breite).

### Hinweise
- „−1“ in den Spinners oder Transparent bei Farben bedeutet: Wert vom globalen Default übernehmen.
