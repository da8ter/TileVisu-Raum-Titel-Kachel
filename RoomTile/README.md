# Raum-Kachel
Support: https://community.symcon.de/t/html-kachelsammlung-bewohnerstatus-waermepumpe-etc/

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Kachelkonfiguration](#5-Kachelkonfiguration)

Kachel für einen einzelnen Raum mit großem Titel, Bild, Info-Badges (links/rechts) und Menüleiste mit Schaltern.

### 1. Funktionsumfang
- Einzelraum-Ansicht (kein Grid, keine Raumliste)
- Optional: Buttonfarben automatisch aus dem Hintergrundbild
  - Global unter „Globale Standardwerte → Buttonfarben aus Hintergrundbild“
  - Pro Raum unter „Raum → Hintergrund → Buttonfarben aus Hintergrundbild“

## Konfiguration
- Globale Standardwerte: optionale Defaults für Schriftgrößen/-farben etc.
- Raum:
  - Hintergrund (Bild, Transparenz, Hintergrundfarbe)
  - Transparenzen auf Prozent (0–100%) umgestellt: Alle Transparenz-Felder (Infoleiste, Menü-Hintergrund, Bildtransparenz) nutzen jetzt Prozentwerte; Backend skaliert automatisch auf CSS-Alpha (0–1). Werte im alten Bereich 0–1 werden weiterhin korrekt interpretiert.
  - Hintergrund: Bildfilter-Steuerung
    - Lichtstatus: Bool-Variable (ein/aus)
    - Dimmwert (0..100): Integer/Float-Variable für die Intensität
    - Verhalten:
      - Lichtstatus vorhanden:
        - `false` → maximaler Filtereffekt (Dimmwert wird ignoriert).
        - `true`  → Filterintensität richtet sich nach dem Dimmwert; fehlt der Dimmwert, bleibt der Filter aus.
      - Kein Lichtstatus → ausschließlich der Dimmwert steuert den Effekt.
      - Dimmwert (falls verwendet): 100 = kein Effekt, 0 = maximaler Effekt.
      - Maximaler Effekt: brightness(0.2) contrast(0.9) grayscale(0.5).
  - Raumname (Farbe, Größe)
  - Info-Leiste (Info Links/Rechts/Links2/Rechts2 inkl. Name/Icon/Wert/Label)
  - Info-Leiste: Hintergrundfarbe und Transparenz der oberen Badges (row-top)
  - Info-Leiste: Eckenradius der Badges (left/right)
  - Menü-Leiste (bis zu 5 Schalter, Ausrichtung, Verteilung, Breiten, Labels)
  - Menü-Leiste: Eckenradius der Buttons

### 2. Voraussetzungen
- IP-Symcon ≥ 7.1

### 3. Software-Installation

- Über den Module Store
- Über das Module Control folgende URL hinzufügen
https://github.com/da8ter/TileVisu-Raum-Titel-Kachel.git

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann die Raum-Kachel mithilfe des Schnellfilters gefunden werden. (Suchbegriff: Room Tile, TileVisu oder Kachel)  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

### 5. Kachelkonfiguration

Grundsätzlicher Hinweis:
Standardmäßig sind alle Objekte in der Kachelansicht ausgeblendet. Sie werden nur angezeigt, wenn du sie entsprechend konfigurierst. Bitte beachte, dass nicht alle Änderungen an der Konfiguration automatisch in der Kachelansicht sichtbar sind. Sollten Änderungen nicht sofort erscheinen, lade bitte die Seite oder den iFrame neu.

__Raumname und Bild__
Name     | Beschreibung
-------- | ------------------
Foto|Hintergrundbild der Kachel (Medienobjekt Typ Bild).
Transparenz Foto|Einstellung der Transparenz des Hintergrundbildes, um es abzudunkeln oder farblich anzupassen. 
Kachelhintergrundfarbe|Farbe des Kachelhintergrunds (wird nur bei eingestellter Bildtransparenz sichtbar)
Raumname|Der Raumname welcher in der Kachelmitte angezeigt wird.
Schriftfarbe|Schriftfarbe Raumname
Schriftgröße|Schriftgröße Raumname in px

__Infobereich__
Name     | Beschreibung
-------- | ------------------
Variable links 1|Die erste anzuzeigende Variable im linken Infobereich
Variable links 2|Die zweite anzuzeigende Variable im linken Infobereich
Variable rechts 1|Die erste anzuzeigende Variable im rechten Infobereich
Variable rechts 2|Die zweite anzuzeigende Variable im rechten Infobereich
Schriftfarbe|Schriftfarbe Infobereich
Schriftgröße|Schriftgröße Infobereich in px

__Einstellungen (Variablenanzeige)__
Name     | Beschreibung
-------- | ------------------
Variablenname|Variablenname anzeigen
Icon anzeigen|Icon aus dem Variablenprofil anzeigen
Variablenicon verwenden|Zeigt das Icon der Variable an
Wert anzeigen|Zeigt den Variablenwert an
Variablenname überschreiben|Zeigt den hier eingegebenen Text anstelle des Variablennamen an

__Menüleiste__
Name     | Beschreibung
-------- | ------------------
Info 1-5|Variablen die im linken Bereich der Menüleiste angezeigt werden sollen
Schalter 1-5|Variablen die im rechten Bereich der Menüleiste als Button angezeigt werden sollen. Anforderung: Bool-Variable mit einem Variablenprofil mit Assoziationen. Die Buttonfarbe ist die Farbe welche im Profil eingestellt ist.
Schriftfarbe|Schriftfarbe Info und Button
Schriftgröße|Schriftgröße Infobereich und Button in px
Hintergrund Transparenz|Transparenz der Menüleiste
Hintergrund Farbe|Hintergrundfarbe Menüleiste

__Einstellungen (Infovariablen und Button)__
Name     | Beschreibung
-------- | ------------------
Breite (nur Button)|Die Breite des Button in px

| Name     | Beschreibung |
| -------- | ------------------ |
| Variablenname | Variablenname anzeigen |
| Icon anzeigen | Icon aus dem Variablenprofil anzeigen |
| Variablenicon verwenden | Zeigt das Icon der Variable an |
| Wert anzeigen | Zeigt den Variablenwert an |
| Variablenname überschreiben | Zeigt den hier eingegebenen Text anstelle des Variablennamen an |

#### Menüleiste

| Name     | Beschreibung |
| -------- | ------------------ |
| Info 1-5 | Variablen die im linken Bereich der Menüleiste angezeigt werden sollen |
| Schalter 1-5 | Variablen die im rechten Bereich der Menüleiste als Button angezeigt werden sollen. Anforderung: Bool-Variable mit einem Variablenprofil mit Assoziationen. Die Buttonfarbe ist die Farbe welche im Profil eingestellt ist. |
| Schriftfarbe | Schriftfarbe Info und Button |
| Schriftgröße | Schriftgröße Infobereich und Button in px |
| Hintergrund Transparenz | Transparenz der Menüleiste |
| Hintergrund Farbe | Hintergrundfarbe Menüleiste |

#### Einstellungen (Infovariablen und Button)

| Name     | Beschreibung |
| -------- | ------------------ |
| Breite (nur Button) | Die Breite des Button in px |
| Variablenname | Variablenname anzeigen |
| Icon anzeigen | Icon aus dem Variablenprofil anzeigen |
| Variablenicon verwenden | Zeigt das Icon der Variable an |
| Wert anzeigen | Zeigt den Variablenwert an |
| Variablenname überschreiben | Zeigt den hier eingegebenen Text anstelle des Variablennamen an |
