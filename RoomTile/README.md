# Raum Kachel

## Kurzbeschreibung

Das RoomTile‑Modul visualisiert einen einzelnen Raum als Kachel mit Hintergrundbild, zentralem Raumnamen, Info‑Leiste oben, optionaler Info‑Mitte sowie einer Menüleiste mit Schaltern. Ein konfigurierbarer Bildfilter kann den Lichtstatus/die Helligkeit über das Hintergrundbild simulieren.

## Backend‑Konfiguration

### Infoleiste
- **Zentriert in der Mitte**: Legt die Info‑Leiste zusammen und zentriert sie.
- **Schriftgröße (Info)**: Textgröße der Info‑Badges oben.
- **Schriftfarbe (Info)**: Textfarbe der Info‑Badges.
- **Hintergrund‑Transparenz (Infoleiste)**: 0..100% Transparenz für alle Info‑Badges.
- **Hintergrundfarbe (Infoleiste)**: Hintergrundfarbe der Info‑Badges.
- **Eckenradius (Infoleiste)**: Rundung der Ecken der Info‑Badges.
- **Info‑Elemente (Liste)**: Dynamische Info‑Einträge (Variable, Name/Icon/Wert anzeigen, Label überschreiben). Bereiche links/rechts.

### Raumname
- **Raumname**: Anzeigetext in der Mitte der Kachel.
- **Objekt welche beim Klick geöffnet wird**: Optionales Zielobjekt für Klick. Benötigt Symcon 8.2.
- **Schriftgröße / Schriftfarbe**: Darstellung des Raumnamens.

### Info‑Mitte
- **Variable (links/rechts)**: Zwei optionale Variablen im Kachelzentrum.
- **Name anzeigen / Wert anzeigen / Icon anzeigen**: Sichtbarkeit je Seite.
- **Icon‑Größe / Textgröße**: Größen für Icon und Text.
- **Farbe (Icon/Text)**: Gemeinsame Farbe für beide Seiten.

### Hintergrund
- **Hintergrund (Kachel)**: Medienobjekt Bild.
- **Bildtransparenz**: 0..100%.
- **Hintergrundfarbe**: Farbe unter dem Bild (sichtbar bei Bildtransparenz).
- **Lichtstatus / Dimmwert (0..100)**: Steuert die Stärke des Bildfilters. So kann der Lichtstatus über das Hintergrundbild angezeigt werden.
  - Licht aus (false) → maximaler Effekt.
  - Licht an (true) → Dimmwert bestimmt die Intensität (100 ≈ kein Effekt, 0 = maximal).
  - Ohne Lichtstatus steuert nur der Dimmwert.
- **Bild‑Filterkonfiguration** (Popup): Bereiche für den Filterverlauf bei 0..100% Effekt.
  - Helligkeit (min/max, 0..1) – nur Abdunkeln (Default: 0.2/1.0)
  - Kontrast (min/max, 0..1) – nur verringern (Default: 0.9/1.0)
  - Graustufen (min/max, 0..1) (Default: 0.0/0.5)

### Menüleiste
- **Menü anzeigen**: Menüleiste ein-/ausblenden.
- **Schriftgröße (Menü) / Schriftfarbe (Menü)**: Darstellung der Menüelemente.
- **Buttonhöhe (px)**: Höhe der Buttons.
- **Hintergrund‑Transparenz (Menü)**: 0..100% Transparenz der Menüleiste.
- **Hintergrundfarbe (Menü)**: Hintergrundfarbe der Menüleiste.
- **Eckenradius (Buttons)**: Rundung der Button‑Ecken.
- **Buttonfarben aus Hintergrundbild**: Leitet Farben aus dem Kachelbild ab (falls vorhanden).
- **Schalter Ausrichtung / Schalter gleichmäßig verteilen**: Layout der Schalter.
- **Menü‑Elemente (Liste)**: Dynamische Buttons (Variable optional mit Aktion, optional „Objekt öffnen“, Name/Icon/Wert, Label‑Override, Breite/Maximale Breite).
