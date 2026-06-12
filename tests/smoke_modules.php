<?php

declare(strict_types=1);

/**
 * Smoke-Test: instanziiert RoomTile und MultiRoomTile unter den SymconStubs,
 * ruft Create/ApplyChanges (implizit), GetConfigurationForm und
 * GetVisualizationTile auf und gibt einen normalisierten, deterministischen
 * Abdruck auf stdout aus (Zeitstempel in Hook-URLs werden maskiert).
 *
 * Verwendung:
 *   php tests/smoke_modules.php > tests/smoke_master.txt          # Abdruck fixieren
 *   php tests/smoke_modules.php | diff tests/smoke_master.txt -   # Regression prüfen
 */

require_once __DIR__ . '/stubs/autoload.php';

mt_srand(7);
IPS\Kernel::reset();

require_once __DIR__ . '/../RoomTile/module.php';
require_once __DIR__ . '/../MultiRoomTile/module.php';

const MODULES = [
    'RoomTile' => [
        'ModuleID'   => '{0E33BB6A-B5A7-4D25-883A-EDF0551DB5C3}',
        'ModuleName' => 'RoomTile',
        'ModuleType' => 3,
        'Class'      => 'RoomTile',
    ],
    'MultiRoomTile' => [
        'ModuleID'   => '{5D1D3B42-2B8E-4C5E-AE9A-4E8E2C00E9F4}',
        'ModuleName' => 'MultiRoomTile',
        'ModuleType' => 3,
        'Class'      => 'MultiRoomTile',
    ],
];

function normalize(string $s): string
{
    // Hook-URLs enthalten einen time()-Cachebuster
    return preg_replace('/&ts=\d+/', '&ts=TS', $s);
}

foreach (MODULES as $name => $module) {
    $id = IPS\ObjectManager::registerObject(1 /* Instance */);
    ob_start(); // LogMessage-Ausgaben der Stubs nicht in den Abdruck mischen
    IPS\InstanceManager::createInstance($id, $module);
    $iface = IPS\InstanceManager::getInstanceInterface($id);
    $form = (string)$iface->GetConfigurationForm();
    $tile = (string)$iface->GetVisualizationTile();
    $log = ob_get_clean();

    $form = normalize($form);
    $tile = normalize($tile);
    echo "== $name ==\n";
    echo 'form.len=' . strlen($form) . ' form.sha1=' . sha1($form) . "\n";
    echo 'tile.len=' . strlen($tile) . ' tile.sha1=' . sha1($tile) . "\n";
    // Der eingebettete Full-Update-Payload ist der eigentliche PHP→HTML-Kontrakt:
    if (preg_match('/<script>handleMessage\((.*)\)<\/script>$/s', $tile, $m)) {
        echo "payload=" . $m[1] . "\n";
    } else {
        echo "payload=NICHT GEFUNDEN\n";
    }
    echo 'log=' . trim(preg_replace('/\s+/', ' ', $log)) . "\n\n";
}
