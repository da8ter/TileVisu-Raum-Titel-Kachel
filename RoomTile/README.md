# Raum Kachel

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Kachelkonfiguration](#5-kachelkonfiguration)

### 1. Funktionsumfang

* Visualisiert einen einzelnen Raum als Kachel mit Hintergrundbild, zentralem Raumnamen, Info‑Leiste oben, optionaler Info‑Mitte sowie einer Menüleiste mit Schaltern.
* Ein konfigurierbarer Bildfilter kann den Lichtstatus/die Helligkeit über das Hintergrundbild simulieren.
* Dynamisches Hintergrundbild über eine URL‑Variable (z. B. Wetterbild, Webcam).

### 2. Voraussetzungen

- IP-Symcon ab Version 7.1

### 3. Software-Installation

* Über den Module Store, oder
* Über das Module Control folgende URL hinzufügen:
  `https://github.com/da8ter/TileVisu-Raum-Titel-Kachel.git`

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann die Raum Kachel mithilfe des Schnellfilters gefunden werden (Suchbegriff: RoomTile, TileVisu oder Kachel).
- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

### 5. Kachelkonfiguration

#### Infoleiste
- **Zentriert in der Mitte**: Legt die Info‑Leiste zusammen und zentriert sie.
- **Schriftgröße (Info)**: Textgröße der Info‑Badges oben.
- **Schriftfarbe (Info)**: Textfarbe der Info‑Badges.
- **Hintergrund‑Transparenz (Infoleiste)**: 0..100% Transparenz für alle Info‑Badges.
- **Hintergrundfarbe (Infoleiste)**: Hintergrundfarbe der Info‑Badges.
- **Transparenz bei Statusfarben**: Steuert, ob die Hintergrund‑Transparenz auch für Statusfarben aus Profilen/Darstellungen gilt (true = ja, false = Statusfarben undurchsichtig).
- **Eckenradius (Infoleiste)**: Rundung der Ecken der Info‑Badges.
- **Info‑Elemente (Liste)**: Dynamische Info‑Einträge (Variable, Name/Icon/Wert anzeigen, Label überschreiben). Bereiche links/rechts.
- **Profil-/Darstellungsfarbe als Hintergrund**: Wenn aktiviert, wird die Hintergrundfarbe je nach Variablenstatus aus Profil‑Assoziationen bzw. Präsentation (inkl. Template/Guid‑Präsentationen) übernommen. Farbe „-1“ bewirkt Standard‑Hintergrund. Live‑Updates werden unterstützt.
- **Hintergrundfarbe: Status = True**: Überschreibt die Hintergrundfarbe, wenn der Status True ist. Wird z.B. benötigt bei Bool-Variablen mit der Darstellung Schalter/Switch weil dort keine Farben konfiguriert werden können.
- **Hintergrundfarbe: Status = False**: Überschreibt die Hintergrundfarbe, wenn der Status False ist. Wird z.B. benötigt bei Bool-Variablen mit der Darstellung Schalter/Switch weil dort keine Farben konfiguriert werden können.

#### Raumname
- **Raumname**: Anzeigetext in der Mitte der Kachel.
- **Objekt welche beim Klick geöffnet wird**: Optionales Zielobjekt für Klick. Benötigt Symcon 8.2.
- **Schriftgröße / Schriftfarbe**: Darstellung des Raumnamens.

#### Info‑Mitte
- **Variable (links/rechts)**: Zwei optionale Variablen im Kachelzentrum.
- **Name anzeigen / Wert anzeigen / Icon anzeigen**: Sichtbarkeit je Seite.
- **Icon‑Größe / Textgröße**: Größen für Icon und Text.
- **Farbe (Icon/Text)**: Gemeinsame Farbe für beide Seiten.

#### Hintergrund
- **URL Hintergrundbild (String-Variable)**: Optionale String‑Variable mit einer Bild‑URL. Überschreibt das statische Hintergrundbild. Der Bildfilter wird bei aktivem dynamischen Hintergrundbild automatisch deaktiviert. Anwendungsbeispiele: Wetterbild, Webcam, externe Bilder.
- **Hintergrundbild Lichtstatus an** (Bild 1) und **Hintergrundbild Lichtstatus aus** (Bild 2):
  - Wenn Bild 2 gesetzt ist, wird der Bild‑Filter deaktiviert.
  - Stattdessen wird Bild 1 über Bild 2 gelegt und abhängig vom Lichtstatus/Dimmwert transparent.
    - Lichtstatus = aus → Bild 1 wird 100% transparent (nur Bild 2 sichtbar).
    - Mit Dimmwert → Transparenz entspricht dem Helligkeitsverlauf (0 = keine Transparenz, 100 = volle Transparenz von Bild 1).
- **Bildtransparenz**: 0..100% (Basis‑Transparenz von Bild 1 im Ein‑Bild‑Modus bzw. Basisfaktor im Zwei‑Bild‑Modus).
- **Hintergrundfarbe**: Farbe unter dem Bild (sichtbar bei Bildtransparenz).
- **Lichtstatus / Dimmwert (0..100)**: Steuert die Stärke des Bildfilters. So kann der Lichtstatus über das Hintergrundbild angezeigt werden.
  - Licht aus (false) → maximaler Effekt.
  - Licht an (true) → Dimmwert bestimmt die Intensität (100 ≈ kein Effekt, 0 = maximal).
  - Ohne Lichtstatus steuert nur der Dimmwert.
- **Bild‑Filterkonfiguration** (Popup): Bereiche für den Filterverlauf bei 0..100% Effekt.
  - Helligkeit (min/max, 0..1) – nur Abdunkeln (Default: 0.2/1.0)
  - Kontrast (min/max, 0..1) – nur verringern (Default: 0.9/1.0)
  - Graustufen (min/max, 0..1) (Default: 0.0/0.5)

#### Menüleiste
- **Menü anzeigen**: Menüleiste ein-/ausblenden.
- **Schriftgröße (Menü) / Schriftfarbe (Menü)**: Darstellung der Menüelemente.
- **Buttonhöhe (px)**: Höhe der Buttons.
- **Hintergrund‑Transparenz (Menü)**: 0..100% Transparenz der Menüleiste.
- **Hintergrundfarbe (Menü)**: Hintergrundfarbe der Menüleiste.
- **Eckenradius (Buttons)**: Rundung der Button‑Ecken.
- **Buttonfarben aus Hintergrundbild**: Leitet Farben aus dem Kachelbild ab (falls vorhanden).
  - Wenn aktiv, werden Profil-/Assoziationsfarben (ColorOn/ColorOff/Profilfarbe) ignoriert. Es gelten ausschließlich die aus dem Bild extrahierten Palettenfarben.
- **Schalter Ausrichtung / Schalter gleichmäßig verteilen**: Layout der Schalter.
- **Menü‑Elemente (Liste)**: Dynamische Buttons (Variable optional mit Aktion, optional „Objekt öffnen“, Name/Icon/Wert, Label‑Override, Breite/Maximale Breite).
  - Szenensteuerung: Wähle bei „Szeneninstanz“ eine Instanz des Moduls „Szenen-Steuerung“. Es wird automatisch ein Multi‑Button mit allen Szenen (Scene1..N) erzeugt.
