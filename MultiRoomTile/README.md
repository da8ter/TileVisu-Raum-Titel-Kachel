# Multi‑Raum Kachel (Grid)
Support: https://community.symcon.de/t/html-kachelsammlung-bewohnerstatus-waermepumpe-etc/

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Kachelkonfiguration](#5-Kachelkonfiguration)

### 1. Funktionsumfang

Aggregator-Kachel für mehrere Räume mit Bild, Raumnamen (zentral), Info‑Badges (links/mitte/rechts) und Menüleiste mit Schaltern.

- Optional: Buttonfarben automatisch aus dem Hintergrundbild (global und pro Raum)
- Optional: Feste Spaltenanzahl im Grid (0 = auto)
- Dynamische Info‑ und Menü‑Elemente (Listen)
- Einheitliche Hintergrund‑Transparenz/Farbe für die Info‑Badges (links/mitte/rechts)

### 2. Voraussetzungen

- IP‑Symcon ≥ 7.1

### 3. Software-Installation

- Über den Module Store
- Über das Module Control folgende URL hinzufügen
https://github.com/da8ter/TileVisu-Raum-Titel-Kachel.git

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann die Multi‑Raum‑Kachel mithilfe des Schnellfilters gefunden werden. (Suchbegriff: MultiRoom, TileVisu oder Kachel)  
- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

### 5. Kachelkonfiguration

Grundsätzlicher Hinweis:
Standardmäßig sind alle Objekte in der Kachelansicht ausgeblendet. Sie werden nur angezeigt, wenn du sie entsprechend konfigurierst. Bitte beachte, dass nicht alle Änderungen an der Konfiguration automatisch in der Kachelansicht sichtbar sind. Sollten Änderungen nicht sofort erscheinen, lade bitte die Seite oder den iFrame neu.

__Rastereinstellungen__
Name     | Beschreibung
-------- | ------------------
Minimale Kachelbreite (px)|Minimale Breite jeder Raumkachel (für automatische Spalten)
Abstand (px)|Abstand zwischen den Kacheln
Eckenradius (px)|Abrundung der Kachel-Ecken
Spalten (0 = auto)|Feste Anzahl der Spalten; 0 = automatische Spaltenaufteilung

__Globale Standardwerte__
Name     | Beschreibung
-------- | ------------------
Buttonfarben aus Hintergrundbild|Leitet Buttonfarben aus dem Kachelbild ab (optional)
Schriftgröße (Info)|Schriftgröße der Info‑Badges (oben)
Schriftfarbe (Info)|Schriftfarbe der Info‑Badges
Hintergrund‑Transparenz (Infoleiste)|Transparenz der Info‑Badges (oben)
Hintergrundfarbe (Infoleiste)|Hintergrundfarbe der Info‑Badges (oben)
Schriftgröße (Menü)|Schriftgröße der Menüleiste
Schriftfarbe (Menü)|Schriftfarbe der Menüleiste
Hintergrund‑Transparenz (Menü)|Transparenz der Menüleiste
Hintergrundfarbe (Menü)|Hintergrundfarbe der Menüleiste
Hintergrundfarbe (Kachel)|Kachelhintergrundfarbe (unter dem Bild)
Bildtransparenz|Transparenz des Hintergrundbildes
Schriftgröße (Raumname)|Schriftgröße des zentralen Raumnamens
Schriftfarbe (Raumname)|Schriftfarbe des zentralen Raumnamens

__Räume__
Name     | Beschreibung
-------- | ------------------
Raumname|Anzeige-Name des Raums (zentral in der Kachel)
Objekt welche beim Klick geöffnet wird:|Objekt-ID, die beim Klicken geöffnet wird
Info‑Leiste: Hintergrund‑Transparenz|Transparenz der Info‑Badges (einheitlich für links/mitte/rechts)
Info‑Leiste: Hintergrundfarbe|Hintergrundfarbe der Info‑Badges (einheitlich)
Info‑Elemente (Liste)|Dynamische Liste von Info‑Elementen (Bereich: links/mitte/rechts, Name/Icon/Wert)
Menü‑Leiste anzeigen|Ein-/Ausblenden der Menüleiste
Menü‑Elemente (Liste)|Dynamische Liste von Menü‑Elementen (Buttons/Optionen, optional Objekt öffnen)
Schalter‑Ausrichtung|Ausrichtung der Menü‑Elemente (links/rechts)
Schalter gleichmäßig verteilen|Verteilung der Menü‑Elemente über die Breite
Hintergrundbild|Medienobjekt (Bild) für die Kachel
Bildtransparenz|Transparenz des Hintergrundbildes
Hintergrundfarbe|Farbe unter dem Bild (sichtbar bei Bildtransparenz)
Lichtstatus / Dimmwert|Optionaler Bildfilter: Bool (ein/aus) und/oder 0..100 für die Intensität

Hinweise zum Bildfilter:
- `false` (Licht aus) → maximaler Effekt (Dimmwert wird ignoriert)
- `true` (Licht an) → Dimmwert steuert; fehlt Dimmwert, ist der Effekt aus
- Ohne Lichtstatus steuert ausschließlich der Dimmwert (100 = kein Effekt, 0 = max)
