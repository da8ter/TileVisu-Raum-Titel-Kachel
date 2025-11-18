# Raum-Titel Kachel
![Raum-Titel Kachel](https://github.com/da8ter/images/blob/1c5fe63e9757e81e6d8c4c84a63e0b39fa00247c/raum_header.jpg)

Support: https://community.symcon.de/t/html-kachelsammlung-bewohnerstatus-waermepumpe-etc/


### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Kachelkonfiguration](#5-Kachelkonfiguration)

### 1. Funktionsumfang

* Die Header-Kachel ermöglicht es ein Bild und einen Rumnamen abzubilden. Links und Rechts können jeweils zwei Variable eingeblendet werden (z.B. die Raumtemperatur) Im unteren Bereich können links 5 Informationen und rechts 5 Button eingeblendet werden. (z.B. Zentral AUS oder Lichtszene starten)

![Kachelaufteilung](https://github.com/da8ter/images/blob/87a84c8b2d70f1f68b169b1b117ccbd687415eda/header_tile_bereiche.jpg)

### 2. Voraussetzungen

- IP-Symcon ab Version 7.1

### 3. Software-Installation

* Über den Module Store
* Über das Module Control folgende URL hinzufügen
https://github.com/da8ter/TileVisu-Raum-Titel-Kachel.git


### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann die Wallbox-Kachel mithilfe des Schnellfilters gefunden werden. (Suchbegriff: Raum-Titel, TileVisu oder Kachel)  
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
Variablenname|Variablenname anzeigen
Icon anzeigen|Icon aus dem Variablenprofil anzeigen
Variablenicon verwenden|Zeigt das Icon der Variable an
Wert anzeigen|Zeigt den Variablenwert an
Variablenname überschreiben|Zeigt den hier eingegebenen Text anstelle des Variablennamen an
