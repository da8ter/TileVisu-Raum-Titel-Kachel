<?php

declare(strict_types=1);

/**
 * Repliziert die Marker-Substitution aus TileVisuRoomHelpers::renderVisualizationTile()
 * außerhalb von Symcon und schreibt das zusammengesetzte HTML nach
 * /tmp/tilevisu_assembled_<Modul>.html.
 *
 * Verwendung (Vergleich gegen einen Referenzstand, z.B. vor dem Dedup):
 *   php tests/assemble_html.php
 *   git show <ref>:RoomTile/module.html > /tmp/ref_rt.html
 *   python3 tests/verify_assembled.py /tmp/ref_rt.html /tmp/tilevisu_assembled_RoomTile.html
 */

$root = dirname(__DIR__);

foreach (['RoomTile', 'MultiRoomTile'] as $name) {
    $html = (string)file_get_contents($root . '/' . $name . '/module.html');
    foreach ([
        '/*__TILEVISU_SHARED_CSS__*/' => $root . '/libs/html/tilevisu-shared.css',
        '/*__TILEVISU_SHARED_JS__*/'  => $root . '/libs/html/tilevisu-shared.js',
    ] as $marker => $assetPath) {
        if (strpos($html, $marker) === false) {
            fwrite(STDERR, "FEHLER: Marker $marker fehlt in $name/module.html\n");
            exit(1);
        }
        $asset = file_get_contents($assetPath);
        if (!is_string($asset)) {
            fwrite(STDERR, "FEHLER: $assetPath nicht lesbar\n");
            exit(1);
        }
        $html = str_replace($marker, $asset, $html);
    }
    $out = '/tmp/tilevisu_assembled_' . $name . '.html';
    file_put_contents($out, $html);
    echo "$name: " . strlen($html) . " Bytes -> $out\n";
}
