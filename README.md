# Raum-Titel Kachel

![Raum-Header-Kachel](https://github.com/da8ter/images/blob/main/raum_header.jpg)

Die Header-Kachel ermöglicht es ein Bild und einen Rumnamen abzubilden. Links und Rechts kann jeweils eine Variable eingeblendet werden (z.B. die Rsumtemperatur) Im unteren Bereich können links 5 Informationen und rechts 5 Button eingeblendet werden. (z.B. Zentral AUS oder Lichtszene starten)

[Dokumentation](https://github.com/da8ter/TileVisu-Raum-Titel-Kachel/blob/134893cb79bbb21f9049d42aba193500e3d18cf2/RoomHeader/README.md)

## Neu

- RoomHeaderGrid: Bildfilter-Steuerung für Hintergrundbilder
  - Konfiguration (unter "Design" → "Hintergrund"):
    - Lichtstatus: Bool-Variable (ein/aus).
    - Dimmwert (0..100): Integer/Float-Variable für die Intensität.
  - Verhalten:
    - Bool vorhanden: Lichtstatus = false → Effekt aus (unabhängig vom Dimmwert). Lichtstatus = true → Effekt ggf. über Dimmwert.
    - Aktiv nur wenn (Lichtstatus fehlt oder true) UND (Dimmwert fehlt oder ≠ 100).
    - Dimmwert steuert linear: 100 = kein Effekt, 0 = maximaler Effekt.
    - Maximaler Effekt: brightness(0.2) contrast(0.9) grayscale(0.5).

## Weitere Module

- RoomTile (Einzel-Kachel für einen Raum): https://github.com/da8ter/TileVisu-Raum-Titel-Kachel/tree/main/RoomTile/README.md
