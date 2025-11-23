# Raum-Titel Kachel

Diese Bibliothek enthält drei Kachel-Module für IP‑Symcon zur Visualisierung von Räumen:

- RoomHeader – Header‑Kachel mit Bild, Raumname, Info links/rechts und optionalen Infos/Buttons unten (ALTE KACHEL, WIRD DURCH ROOMTILE ERSETZT)
- RoomTile – Raum‑Kachel mit Info‑Leiste, Info‑Mitte und Menüleiste. Dienst als "Überschriften Kachel" für eine Raumkategorie.
- MultiRoomTile – Multi‑Raum‑Kachel mit Raster (Grid) für mehrere Räume. Dient z.B. als Einstiegsseite für eine Visualisierung der Räume.

## Installation

- Über den Module Store, oder
- über das Module Control folgende URL hinzufügen:
  https://github.com/da8ter/TileVisu-Raum-Titel-Kachel.git

## Voraussetzungen

- IP‑Symcon ≥ 7.1

## Schnellstart

1) Instanz hinzufügen (Suchbegriffe: RoomHeader, RoomTile, MultiRoomTile)
2) Hintergrundbilder, Texte und (optional) Variablen zuweisen
3) Bei Bedarf Bildfilter für Lichtstatus-Simulation über das Hintergrundbild aktivieren (Lichtstatus/Dimmwert)

## Module im Überblick

### RoomHeader
- Bild + Raumname
- Links/Rechts je eine Variable einblendbar (z. B. Raumtemperatur)
- Unten: bis zu 5 Infos links und 5 Buttons rechts (z. B. Zentral AUS, Szenen)
- Details: RoomHeader/README.md

### RoomTile (Raum‑Kachel)
- Hintergrundbild, zentraler Raumname, Info‑Leiste, Info‑Mitte, Menüleiste
- Bild‑Filterkonfiguration (Popup) zur Simulation des Lichtstatus:
  - Helligkeit (min/max, 0..1)
  - Kontrast (min/max, 0..1)
  - Graustufen (min/max, 0..1)
- Der Filter wird über Lichtstatus (Bool) und/oder Dimmwert (0..100) gesteuert
- Details: RoomTile/README.md

### MultiRoomTile (Multi-Raum-Kachel)
- Rastereinstellungen: minimale Kachelgröße, Abstand, Eckenradius, Spalten
- Globale Standardwerte (Info‑/Menü‑Leiste, Kachelfarben, Bildtransparenz, Bildfilter‑Defaults)
- Pro Raum: eigene Einstellungen mit Vererbung (−1 = globalen Default übernehmen)
- Bild‑Filterkonfiguration pro Raum (Popup): Helligkeit/Kontrast/Graustufen in 0..1
- Optional: Buttonfarben aus Hintergrundbild
- Details: MultiRoomTile/README.md

## Hinweise zum Bildfilter (allgemein)

- Licht aus (false) → maximaler Effekt. Licht an (true) → Dimmwert steuert (100 ≈ kein Effekt, 0 = maximal)