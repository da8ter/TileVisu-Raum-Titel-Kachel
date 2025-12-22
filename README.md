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

## Changelog

1.1.6
- Neu: Im Bereich "Kachel- und Raster-Einstellungen" kann der Randabstand individuell angegeben werden.
- Neu: Im Bereich "Kachel- und Raster-Einstellungen" kann der obere Randabstand auf das gleiche Maß wie der untere Randabstand gesetzt werden. Damit wird die volle Kachelhöhe genutzt.

1.1.5
- Neu: Bei Button kann ein Sprungziel innerhalb der Visualisierung angegeben werden welches beim Klick geöffnet wird. (benötigt die kommende Symcon Version 8.2)
- Neu: Variablen ohne Aktion können in der Menüleiste mit farbigem Hintergrund abgebildet werden. Infoelemente können gruppiert werden um z.B. zusammenhängend am rechten oder linken Kachelrand platziert zu werden. Transparenz der Hintergrundfarbe einstellbar.

1.1.4
- Fix: Bei Bool-Button wird die Farbe aus dem Hintergrundbild korrekt angewendet
- Fix: Doppelpunkt nach dem Label wurde fälschlicherweise auch angezeigt wenn das Label ausgeblendet ist.

1.1.3
-
1.1.2
- Neu: Statusfarben bei den Info-Badges sind jetzt auch transparent wie der Standard-Hintergrund (kann in den Einstellungen deaktiviert werden) um eine bessere optische Differenzierung zu den Button zu bekommen.
- Neu: In der Multiroom Kachel kann der Raumname über einen Schalter ausgeblendet werden.
Fix: Icon von Darstellungs-Vorlagen werden korrekt ausgelesen und angezeigt.
Fix: "Label überschreiben" funktioniert jetzt korrekt 
1.1.1
- Neu: Für den Lichtstatus kann jetzt ein zweites Bild für den Status = aus angegeben werden.
- Neu: Info-Badges können optional als Hintergrundfarbe die Farben der Profil-Assoziationen oder Darstellungen erhalten.
- Neu: In der Menüleiste kann bei einem Button eine Instanz des Moduls "Szenen-Steuerung" angegeben werden. Alle Szenen werden dann in einem Multibutton dargestellt. Icon werden aus der jeweiligen Szenenvariable ausgelesen.

1.1.0
Neue Kacheln: Room Tile (Raum Kachel) ersetzt die alte Kachel

Sinn dieser Kachel ist es in einem Raum als Titelkachel zu dienen und die Wichtigsten Infos platzsparend dort anzuzeigen.

- Mit einem Klick in den mittleren Bereich der Kachel kann in eine Kategorie der Visu gesprungen werden (Benötigt das kommende Symcon 8.2)
- Info-Bagdes oben: zum anzeigen von Informationen (Icon, Label, Value) Der Infobereich kann links und rechts oder zusammengelegt in der Mitte angezeigt werden.
- Im Infobereich können beliebig viele Variablen angezeigt werden. Keine Limitierung auf 5 Stück wie bei der alten Kachel.
- Das Hintergrundbild kann z.B. den Lichtstatus widerspiegeln. Hierfür wird eine Bool-Variable (Status Licht) und/oder eine Dimmer-Variable benötigt. Eins von beiden reicht. Ist das licht im Raum aus wird das Hintergrundbild abgedunkelt. Bei Verwendung der Dimmervariable wird graduell zum Dimmwert abgedunkelt.
- In der Menüleiste können jetzt beliebig viele Button oder Infos angezeigt werden. Keine Limitierung auf 5 Stück wie bei der alten Kachel.
- Optional: Buttonfarben aus dem Hintergrundbild extrahieren. So wird ein sehr harmonisches Erscheinungsbild erzielt.
- Ausrichtung und Verteilung der Button ist konfigurierbar


Multi-Room-Tile (Multi Raum Kachel)

Stellt mehrere Räume in einer Kachel dar und kann z.B. als Raumübersicht bzw. Einstiegsseite in der Visu verwendet werden.

- Raster frei konfigurierbar über min. und max. Breite der Raumkacheln oder Angabe einer festen Spaltenanzahl.
- Gleiche Funktionalität wie die Raum Kachel. Nur der Infobereich Mitte fehlt hier aus Platzgründen.
- Konfiguration beliebig vieler Räume möglich. Reihenfolge per Drag and Drop in der Raum-Liste konfigurierbar.
- Benötigt die Symcon Version 8.2 für das Springen in eine Kategorie der Visu. Ohne 8.2 also noch nicht sinnvoll nutzbar!

1.0.1
- Update auf die neuen Icons
1.0