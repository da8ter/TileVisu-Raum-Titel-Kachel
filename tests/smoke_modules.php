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
    // Hook-URLs enthalten einen Cache-Buster: früher &ts=<time()>, jetzt &v=<MediaCRC|mtime>.
    $s = preg_replace('/&ts=\d+/', '&ts=TS', $s);
    return preg_replace('/&v=[^&"]*/', '&v=V', $s);
}

function payloadOf(string $tile): string
{
    if (preg_match('/<script>handleMessage\((.*)\)<\/script>$/s', $tile, $m)) {
        return $m[1];
    }
    return 'NICHT GEFUNDEN';
}

$ifaces = [];
foreach (MODULES as $name => $module) {
    $id = IPS\ObjectManager::registerObject(1 /* Instance */);
    ob_start(); // LogMessage-Ausgaben der Stubs nicht in den Abdruck mischen
    IPS\InstanceManager::createInstance($id, $module);
    $iface = IPS\InstanceManager::getInstanceInterface($id);
    $ifaces[$name] = $iface;
    $form = (string)$iface->GetConfigurationForm();
    $tile = (string)$iface->GetVisualizationTile();
    $log = ob_get_clean();

    $form = normalize($form);
    $tile = normalize($tile);
    echo "== $name ==\n";
    echo 'form.len=' . strlen($form) . ' form.sha1=' . sha1($form) . "\n";
    echo 'tile.len=' . strlen($tile) . ' tile.sha1=' . sha1($tile) . "\n";
    // Der eingebettete Full-Update-Payload ist der eigentliche PHP→HTML-Kontrakt:
    echo 'payload=' . normalize(payloadOf($tile)) . "\n";
    echo 'log=' . trim(preg_replace('/\s+/', ' ', $log)) . "\n\n";
}

// ---------------------------------------------------------------------------
// Konfiguriertes Szenario: Variablen + Raum-Konfiguration, deckt die
// GetFullUpdateMessage-/buildDynamic*-/fillInfoAndButtons-Pfade ab
// ---------------------------------------------------------------------------

$light = IPS_CreateVariable(0);
SetValue($light, true);
// ENUMERATION: einzige nicht-triviale Presentation, die der
// GetValueFormatted-Stub formatieren kann
IPS_SetVariableCustomPresentation($light, [
    'PRESENTATION' => VARIABLE_PRESENTATION_ENUMERATION,
    'OPTIONS'      => json_encode([
        ['Value' => true, 'Caption' => 'An', 'Color' => 0x00FF00, 'IconValue' => 'Bulb'],
        ['Value' => false, 'Caption' => 'Aus', 'Color' => 0x333333, 'IconValue' => 'Bulb'],
    ]),
]);
IPS_SetName($light, 'Licht');

$dim = IPS_CreateVariable(1);
SetValue($dim, 75);
IPS_SetName($dim, 'Dimmer');

$temp = IPS_CreateVariable(2);
SetValue($temp, 21.5);
IPS_SetName($temp, 'Temperatur');

$infoItems = [[
    'Id' => 'i1', 'Area' => 'left', 'VariableId' => $temp,
    'ShowName' => true, 'ShowIcon' => true, 'ShowValue' => true,
    'UseVarColor' => false, 'AltName' => '',
]];
$menuItems = [[
    'Id' => 'm1', 'VariableId' => $light, 'OpenObjectId' => 0, 'SceneControlId' => 0,
    'ShowName' => true, 'ShowIcon' => true, 'ShowValue' => false,
    'UseVarColor' => true, 'ColorTrue' => -1, 'ColorFalse' => -1,
    'AltName' => '', 'Width' => 100, 'FullWidth' => false,
]];

ob_start();
$rt = $ifaces['RoomTile'];
$rt->SetProperty('RoomName', 'Wohnzimmer');
$rt->SetProperty('LightStatus', $light);
$rt->SetProperty('DimValue', $dim);
$rt->SetProperty('Switch1', $light);
$rt->SetProperty('InfoLeft', $temp);
$rt->SetProperty('InfoItems', json_encode($infoItems));
$rt->SetProperty('MenuItems', json_encode($menuItems));
$rt->ApplyChanges();

$mr = $ifaces['MultiRoomTile'];
$mr->SetProperty('Rooms', json_encode([[
    'RoomName'  => 'Küche',
    'Switch1'   => $light,
    'InfoItems' => $infoItems,
    'MenuItems' => $menuItems,
]]));
$mr->ApplyChanges();
ob_end_clean();

foreach (['RoomTile' => $rt, 'MultiRoomTile' => $mr] as $name => $iface) {
    ob_start();
    $tile = (string)$iface->GetVisualizationTile();
    ob_end_clean();
    echo "== $name (konfiguriert) ==\n";
    echo 'payload=' . normalize(payloadOf($tile)) . "\n\n";
}
