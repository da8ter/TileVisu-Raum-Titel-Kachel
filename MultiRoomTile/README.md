# RoomHeaderGrid

Kurzbeschreibung: Aggregator-Kachel für mehrere Räume mit Bild, Raumnamen, Info-Badges links/rechts und Menüleiste mit Schaltern.

## Neues Feature
- Buttonfarben aus Hintergrundbild (optional)
  - Global aktivierbar unter: Globale Standardwerte → "Buttonfarben aus Hintergrundbild".
  - Pro Raum aktivierbar unter: Räume → Design → Hintergrund → "Buttonfarben aus Hintergrundbild".
  - Bei Multi-Buttons erhält jeder Button eine andere, aus dem Bild abgeleitete Farbe.
  - Bei Einzel-Buttons wird eine Bildfarbe als Hintergrund genutzt.
 
- Feste Spaltenanzahl (optional)
  - Konfigurierbar unter: Rastereinstellungen → "Spalten (0 = auto)".
  - 0 = automatische Spaltenanordnung via minimaler Kachelbreite.
  - > 0 = fixe Anzahl an Kacheln pro Zeile.
  
- Info-Badge-Hintergrund (links/mitte/rechts)
  - Globale Defaults unter: Globale Standardwerte → "Hintergrund-Transparenz (Info …)" und "Hintergrundfarbe (Info …)" für links/mitte/rechts.
  - Pro Raum konfigurierbar unter: Räume → Info-Leiste → entsprechende Felder für links/mitte/rechts.
  - Die mittlere Info-Zone (Mitte) ist jetzt aktiv und kann über die Bereichs-Auswahl "Mitte" in den Info-Elementen genutzt werden.

Hinweis: Die Farben werden aus dem aktuell gesetzten Hintergrundbild der Kachel extrahiert und an die Farbstimmung angepasst.

## Änderungen
- Schalter können alternativ ein Objekt öffnen; die Kachel rendert dann einen klickbaren Button, der `openObject` für das Ziel aufruft.
- Hintergrund: Bildfilter-Steuerung
  - Lichtstatus: Bool-Variable für Ein/Aus.
    - `false` → maximaler Filtereffekt (Dimmwert wird ignoriert).
    - `true`  → Filterintensität richtet sich nach dem Dimmwert (falls vorhanden).
  - Dimmwert (0..100): Integer/Float-Variable für die Intensität.
    - 100 = kein Effekt, 0 = maximaler Effekt.
    - Ohne Lichtstatus steuert ausschließlich der Dimmwert.
    - Wenn kein Dimmwert vorhanden ist und Lichtstatus = true, bleibt der Filter aus.
  - Maximaler Effekt: `brightness(0.2) contrast(0.9) grayscale(0.5)`.

- Transparenzen auf Prozent (0–100%) umgestellt
  - Alle Formularfelder zur Transparenz (Info-Badges links/mittig/rechts, Menü-Hintergrund, Bildtransparenz) verwenden jetzt 0–100%.
  - Backend skaliert automatisch auf CSS-Alpha (0–1).
  - Abwärtskompatibel: frühere Werte im Bereich 0–1 werden weiterhin korrekt interpretiert.

## Voraussetzungen
- IP-Symcon ≥ 7.1

## Support
- https://community.symcon.de/
