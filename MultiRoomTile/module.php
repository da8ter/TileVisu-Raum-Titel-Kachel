<?php
 require_once __DIR__ . '/../libs/TileVisuLib.php';
class MultiRoomTile extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Grid globale Einstellungen
        $this->RegisterPropertyInteger('MinWidth', 300);
        $this->RegisterPropertyInteger('MinHeight', 220);
        $this->RegisterPropertyInteger('Gap', 12);
        $this->RegisterPropertyInteger('BorderRadius', 10);
        $this->RegisterPropertyInteger('Columns', 0);
        // optionale globale Defaults
        $this->RegisterPropertyInteger('Default_InfoSchriftgroesse', 14);
        $this->RegisterPropertyInteger('Default_InfoSchriftfarbe', 0xFFFFFF);
        // Höhe der Infoleiste (Row-Top) in Pixeln
        $this->RegisterPropertyInteger('Default_Infohoehe', 24);
        $this->RegisterPropertyInteger('Default_InfoMenueSchriftgroesse', 14);
        $this->RegisterPropertyInteger('Default_InfoMenueSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyFloat('Default_InfoMenueTransparenz', 30.0);
        $this->RegisterPropertyInteger('Default_InfoMenueHintergrundfarbe', 0x000000);
        $this->RegisterPropertyInteger('Default_Kachelhintergrundfarbe', 0x000000);
        $this->RegisterPropertyInteger('Default_RaumnameSchriftgroesse', 45);
        $this->RegisterPropertyInteger('Default_RaumnameSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyFloat('Default_Bildtransparenz', 70.0);
        // Info-Top Badge Hintergrund (einheitlich)
        $this->RegisterPropertyFloat('Default_InfoTopTransparenz', 30.0);
        $this->RegisterPropertyInteger('Default_InfoTopHintergrundfarbe', 0x000000);
        // Transparenz bei Statusfarben (Infoleiste)
        $this->RegisterPropertyBoolean('TransparentStatusColors', true);
        $this->RegisterPropertyBoolean('UseImageColorsForButtons', false);
        $this->RegisterPropertyBoolean('TransparentMenuStatusColors', true);
        $this->RegisterPropertyBoolean('GroupMenuInfoElements', false);
        // Image filter defaults (brightness/contrast/grayscale ranges)
        $this->RegisterPropertyFloat('Default_BgFilterBrightnessMin', 0.2);
        $this->RegisterPropertyFloat('Default_BgFilterBrightnessMax', 1.0);
        $this->RegisterPropertyFloat('Default_BgFilterContrastMin', 0.9);
        $this->RegisterPropertyFloat('Default_BgFilterContrastMax', 1.0);
        $this->RegisterPropertyFloat('Default_BgFilterGrayscaleMin', 0.0);
        $this->RegisterPropertyFloat('Default_BgFilterGrayscaleMax', 0.5);

        // Räume als JSON-Array
        $this->RegisterPropertyString('Rooms', '[]');

        // internes Mapping VarID -> { idx, prop }
        $this->RegisterAttributeString('VarMap', '{}');
        $this->RegisterAttributeString('HookToken', '');

        // HTML Kachel
        $this->SetVisualizationType(1);

        // Register for kernel ready to (re)register WebHook
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function GetConfigurationForm()
    {
        $form = json_decode(@file_get_contents(__DIR__ . '/form.json'), true);
        if (!is_array($form)) {
            return json_encode(['elements' => []]);
        }
        $supportsSelectObject = ((float)IPS_GetKernelVersion() > 8.1);
        if (isset($form['elements']) && is_array($form['elements'])) {
            foreach ($form['elements'] as &$element) {
                if (!is_array($element)) continue;
                if (($element['type'] ?? '') === 'ExpansionPanel' && ($element['caption'] ?? '') === 'Räume') {
                    foreach ($element['items'] as &$roomsItem) {
                        if (!is_array($roomsItem)) continue;
                        if (($roomsItem['type'] ?? '') === 'List' && ($roomsItem['name'] ?? '') === 'Rooms') {
                            if (isset($roomsItem['form']) && is_array($roomsItem['form'])) {
                                foreach ($roomsItem['form'] as &$subPanel) {
                                    if (!is_array($subPanel)) continue;
                                    if (($subPanel['type'] ?? '') === 'ExpansionPanel' && ($subPanel['caption'] ?? '') === 'Raumname') {
                                        foreach ($subPanel['items'] as &$row) {
                                            if (!is_array($row)) continue;
                                            if (($row['type'] ?? '') === 'RowLayout' && isset($row['items']) && is_array($row['items'])) {
                                                foreach ($row['items'] as &$ctrl) {
                                                    if (is_array($ctrl) && ($ctrl['type'] ?? '') === 'SelectObject' && ($ctrl['name'] ?? '') === 'Target') {
                                                        $row['visible'] = $supportsSelectObject;
                                                        break;
                                                    }
                                                }
                                                unset($ctrl);
                                            }
                                        }
                                        unset($row);
                                    }
                                    if (($subPanel['type'] ?? '') === 'ExpansionPanel' && ($subPanel['caption'] ?? '') === 'Menü-Leiste') {
                                        foreach ($subPanel['items'] as &$mi) {
                                            if (!is_array($mi)) continue;
                                            if (($mi['type'] ?? '') === 'List' && ($mi['name'] ?? '') === 'MenuItems' && isset($mi['columns']) && is_array($mi['columns'])) {
                                                foreach ($mi['columns'] as &$col) {
                                                    if (!is_array($col)) continue;
                                                    $n = (string)($col['name'] ?? '');
                                                    if (in_array($n, ['ShowName','ShowIcon','ShowValue','FullWidth'], true)) {
                                                        $col['visible'] = false;
                                                    }
                                                }
                                                unset($col);
                                            }
                                        }
                                        unset($mi);
                                    }
                                }
                                unset($subPanel);
                            }
                        }
                    }
                    unset($roomsItem);
                }
            }
            unset($element);
        }
        return json_encode($form);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Ensure hidden IDs are auto-assigned once (per room)
        if ($this->NormalizeRoomLists()) {
            return;
        }

        // WebHook für Bildauslieferung registrieren
        $this->RegisterHook('/hook/roomgridimages/' . $this->InstanceID);

        // Referenzen säubern
        foreach ($this->GetReferenceList() as $ref) {
            $this->UnregisterReference($ref);
        }

        // Nachrichten säubern
        foreach ($this->GetMessageList() as $senderID => $messageIDs) {
            foreach ($messageIDs as $messageID) {
                $this->UnregisterMessage($senderID, $messageID);
            }
        }

        $rooms = $this->getRooms();
        $varMap = [];
        $watchProps = ['Lichtstatus','Dimmwert'];

        foreach ($rooms as $idx => $room) {
            // Dynamische Info-Items
            $infoList = $this->parseRoomList($room['InfoItems'] ?? []);
            $hasDynamicInfo = false;
            foreach ($infoList as $row) {
                if (!is_array($row)) continue;
                $varId = (int)($row['VariableId'] ?? 0);
                $itemId = (string)($row['Id'] ?? '');
                if ($varId > 0 && $itemId !== '' && @IPS_VariableExists($varId)) {
                    $hasDynamicInfo = true;
                    $this->RegisterReference($varId);
                    $this->RegisterMessage($varId, VM_UPDATE);
                    if (!isset($varMap[$varId]) || !is_array($varMap[$varId])) {
                        $varMap[$varId] = [];
                    }
                    $varMap[$varId][] = ['idx' => $idx, 'prop' => 'infoitem:' . $itemId];
                }
            }

            // Dynamische Menü-Items
            $menuList = $this->parseRoomList($room['MenuItems'] ?? []);
            $hasDynamicMenu = false;
            foreach ($menuList as $row) {
                if (!is_array($row)) continue;
                $varId = (int)($row['VariableId'] ?? 0);
                $itemId = (string)($row['Id'] ?? '');
                if ($varId > 0 && $itemId !== '' && @IPS_VariableExists($varId)) {
                    $hasDynamicMenu = true;
                    $this->RegisterReference($varId);
                    $this->RegisterMessage($varId, VM_UPDATE);
                    if (!isset($varMap[$varId]) || !is_array($varMap[$varId])) {
                        $varMap[$varId] = [];
                    }
                    $varMap[$varId][] = ['idx' => $idx, 'prop' => 'menuitem:' . $itemId];
                }
                // SceneControl ActiveScene tracking
                $sceneControlId = (int)($row['SceneControlId'] ?? 0);
                if ($sceneControlId > 0 && $itemId !== '' && @IPS_InstanceExists($sceneControlId)) {
                    $hasDynamicMenu = true;
                    $activeVar = 0;
                    foreach ((array)@IPS_GetChildrenIDs($sceneControlId) as $cid) {
                        if (@IPS_VariableExists($cid)) {
                            $o = @IPS_GetObject($cid);
                            if (($o['ObjectIdent'] ?? '') === 'ActiveScene') { $activeVar = $cid; break; }
                        }
                    }
                    if ($activeVar > 0) {
                        $this->RegisterReference($activeVar);
                        $this->RegisterMessage($activeVar, VM_UPDATE);
                        if (!isset($varMap[$activeVar]) || !is_array($varMap[$activeVar])) {
                            $varMap[$activeVar] = [];
                        }
                        $varMap[$activeVar][] = ['idx' => $idx, 'prop' => 'menuitem:' . $itemId];
                    }
                }
            }

            if (!$hasDynamicInfo) {
                $staticInfoProps = ['InfoLinks','InfoLinks2','InfoRechts','InfoRechts2','Info1','Info2','Info3','Info4','Info5'];
                foreach ($staticInfoProps as $prop) {
                    if (!isset($room[$prop])) {
                        continue;
                    }
                    $id = (int)$room[$prop];
                    if ($id > 0 && IPS_VariableExists($id)) {
                        $this->RegisterReference($id);
                        $this->RegisterMessage($id, VM_UPDATE);
                        if (!isset($varMap[$id]) || !is_array($varMap[$id])) {
                            $varMap[$id] = [];
                        }
                        $varMap[$id][] = ['idx' => $idx, 'prop' => $prop];
                    }
                }
            }

            if (!$hasDynamicMenu) {
                $staticSwitchProps = ['Schalter1','Schalter2','Schalter3','Schalter4','Schalter5'];
                foreach ($staticSwitchProps as $prop) {
                    if (!isset($room[$prop])) {
                        continue;
                    }
                    $id = (int)$room[$prop];
                    if ($id > 0 && IPS_VariableExists($id)) {
                        $this->RegisterReference($id);
                        $this->RegisterMessage($id, VM_UPDATE);
                        if (!isset($varMap[$id]) || !is_array($varMap[$id])) {
                            $varMap[$id] = [];
                        }
                        $varMap[$id][] = ['idx' => $idx, 'prop' => $prop];
                    }
                }
            }

            foreach ($watchProps as $prop) {
                if (!isset($room[$prop])) {
                    continue;
                }
                $id = (int)$room[$prop];
                if ($id > 0 && IPS_VariableExists($id)) {
                    $this->RegisterReference($id);
                    $this->RegisterMessage($id, VM_UPDATE);
                    if (!isset($varMap[$id]) || !is_array($varMap[$id])) {
                        $varMap[$id] = [];
                    }
                    $varMap[$id][] = ['idx' => $idx, 'prop' => $prop];
                }
            }
            // bgImage ist Media, nicht Variable -> keine Message
        }

        $this->WriteAttributeString('VarMap', json_encode($varMap));

        // Initiales Full Update
        $this->UpdateVisualizationValue(json_encode($this->GetFullUpdateMessage()));
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message === IPS_KERNELMESSAGE) {
            if (isset($Data[0]) && $Data[0] === KR_READY) {
                $this->RegisterHook('/hook/roomgridimages/' . $this->InstanceID);
            }
            return;
        }
        if ($Message !== VM_UPDATE) {
            return;
        }
        $map = json_decode($this->ReadAttributeString('VarMap'), true) ?: [];
        if (!isset($map[$SenderID])) {
            return;
        }
        $mappings = $map[$SenderID];
        if (isset($mappings['idx'])) {
            $mappings = [$mappings];
        }

        $rooms = $this->getRooms();
        $delta = [];
        foreach ($mappings as $mp) {
            if (!is_array($mp) || !isset($mp['idx'], $mp['prop'])) {
                continue;
            }
            $idx = (int)$mp['idx'];
            $prop = (string)$mp['prop'];
            if (!isset($rooms[$idx])) {
                continue;
            }
            $room = $rooms[$idx];

            // Spezialfall: Lichtstatus/Dimmwert → bgfilter/bgfade (0..100)
            if ($prop === 'Lichtstatus' || $prop === 'Dimmwert') {
                $boolId = (int)($room['Lichtstatus'] ?? 0);
                $dimId = (int)($room['Dimmwert'] ?? 0);
                $hasBool = $boolId > 0 && @IPS_VariableExists($boolId);
                $hasDim = $dimId > 0 && @IPS_VariableExists($dimId);
                $boolVal = false;
                $dimVal = 0.0;
                if ($hasBool) {
                    try { $boolVal = (bool)@GetValue($boolId); } catch (Throwable $e) { $boolVal = false; }
                }
                if ($hasDim) {
                    try { $dimVal = (float)@GetValue($dimId); } catch (Throwable $e) { $dimVal = 0.0; }
                    if ($dimVal < 0) $dimVal = 0.0; if ($dimVal > 100) $dimVal = 100.0;
                }
                // Gating: Bool=false => Effekt aus, sonst von Dimmwert abhängig (falls vorhanden)
                $pOut = 0.0;
                if ($hasBool) {
                    if ($boolVal === false) {
                        $pOut = 100.0;
                    } else {
                        if ($hasDim) {
                            $pOut = 100.0 - $dimVal;
                        }
                    }
                } elseif ($hasDim) {
                    $pOut = 100.0 - $dimVal;
                }

                if (!$hasBool && !$hasDim) {
                    // Wenn kein Lichtstatus/Dimmwert: Standardfilter nur bei einem Bild
                    $imageID2 = (int)($room['bgImage2'] ?? 0);
                    // Validierung: Ungültige Media-ID ignorieren
                    if ($imageID2 > 0 && !@IPS_MediaExists($imageID2)) { $imageID2 = 0; }
                    $pOut = ($imageID2 > 0) ? 0.0 : 0;
                } else {
                    $imageID2 = (int)($room['bgImage2'] ?? 0);
                    // Validierung: Ungültige Media-ID ignorieren
                    if ($imageID2 > 0 && !@IPS_MediaExists($imageID2)) { $imageID2 = 0; }
                }
                if ($pOut < 0.0) {
                    $pOut = 0.0;
                }
                // Wenn Bild 2 konfiguriert ist, Filter deaktivieren (bgfilter = 0)
                if ($imageID2 > 0) { $pOut = 0.0; }
                $delta[] = [ 'idx' => $idx, 'key' => 'bgfilter', 'value' => $pOut ];
                // bgfade nur senden wenn zweites Bild konfiguriert ist
                if ($imageID2 > 0) {
                    $fade = 0.0;
                    if ($hasBool && $boolVal === false) { $fade = 100.0; }
                    elseif ($hasDim) { $fade = 100.0 - $dimVal; }
                    if ($fade < 0.0) $fade = 0.0; if ($fade > 100.0) $fade = 100.0;
                    $delta[] = [ 'idx' => $idx, 'key' => 'bgfade', 'value' => $fade ];
                }
                continue;
            }

            if (strpos($prop, 'menuitem:') === 0) {
                $itemId = substr($prop, strlen('menuitem:'));
                $varId = $SenderID;
                if ($varId > 0 && @IPS_VariableExists($varId)) {
                    // Prüfe ob SceneControl: dann Label -> Index mappen
                    $value = @GetValue($varId);
                    $rooms = $this->getRooms();
                    $sceneId = 0;
                    $menuList = $this->parseRoomList($rooms[$idx]['MenuItems'] ?? []);
                    foreach ($menuList as $row) {
                        if (!is_array($row)) continue;
                        $rid = (string)($row['Id'] ?? '');
                        if ($rid === $itemId) { $sceneId = (int)($row['SceneControlId'] ?? 0); break; }
                    }
                    if ($sceneId > 0 && @IPS_InstanceExists($sceneId)) {
                        if (!is_numeric($value)) {
                            $mapped = 0;
                            foreach ((array)@IPS_GetChildrenIDs($sceneId) as $cid) {
                                if (!@IPS_VariableExists($cid)) continue;
                                $o = @IPS_GetObject($cid);
                                $ident = (string)($o['ObjectIdent'] ?? '');
                                if (preg_match('/^Scene(\d+)$/i', $ident, $m)) {
                                    $label = (string)($o['ObjectName'] ?? $ident);
                                    if ((string)$label === (string)$value) { $mapped = (int)$m[1]; break; }
                                }
                            }
                            $value = $mapped;
                        } else {
                            $value = (int)$value;
                        }
                        $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'value', 'value' => $value];
                        $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'asso', 'value' => (string)@GetValueFormatted($varId)];
                        continue;
                    }
                    // Fallback: Standard-Delta
                    $delta = array_merge($delta, $this->buildMenuItemDelta($idx, $itemId, $varId));
                }
                continue;
            }
            if (strpos($prop, 'infoitem:') === 0) {
                $itemId = substr($prop, strlen('infoitem:'));
                $varId = $SenderID;
                if ($varId > 0 && @IPS_VariableExists($varId)) {
                    $delta = array_merge($delta, $this->buildInfoItemDelta($idx, $itemId, $varId));
                }
                continue;
            }

            if (strpos($prop, 'info:') === 0) {
                $itemId = substr($prop, strlen('info:'));
                $varId = $SenderID;
                if ($varId > 0 && @IPS_VariableExists($varId)) {
                    $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'asso', 'value' => (string)@GetValueFormatted($varId)];
                }
                continue;
            }

            if (strpos($prop, 'switch:') === 0) {
                $itemId = substr($prop, strlen('switch:'));
                $varId = $SenderID;
                if ($varId > 0 && @IPS_VariableExists($varId)) {
                    $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'value', 'value' => @GetValue($varId)];
                }
                continue;
            }
        }
        if (!empty($delta)) {
            $this->UpdateVisualizationValue(json_encode(['delta' => $delta]));
        }
    }

    public function RequestAction($Ident, $Value)
    {
        // Reorder Buttons aus der Form
        if ($Ident === 'reorder' && is_string($Value)) {
            $payload = json_decode($Value, true);
            if (!is_array($payload) || !isset($payload['direction'], $payload['index'])) {
                return;
            }
            $this->ReorderRoom((int)$payload['index'], (string)$payload['direction']);
            return;
        }

        // Menü-Item Aktionen
        if (strpos($Ident, 'menuitem:') === 0) {
            $itemId = substr($Ident, strlen('menuitem:'));
            $rooms = $this->getRooms();
            foreach ($rooms as $idx => $room) {
                $menuList = $this->parseRoomList($room['MenuItems'] ?? []);
                foreach ($menuList as $row) {
                    if (!is_array($row)) continue;
                    $rid = (string)($row['Id'] ?? '');
                    if ($rid === '' || $rid !== $itemId) continue;
                    // SceneControl Aktion
                    $sceneControlId = (int)($row['SceneControlId'] ?? 0);
                    if ($sceneControlId > 0 && @IPS_InstanceExists($sceneControlId)) {
                        try {
                            $target = $Value;
                            if (!is_numeric($target)) {
                                $target = 0;
                                foreach ((array)@IPS_GetChildrenIDs($sceneControlId) as $cid) {
                                    if (!@IPS_VariableExists($cid)) continue;
                                    $o = @IPS_GetObject($cid);
                                    $ident = (string)($o['ObjectIdent'] ?? '');
                                    if (preg_match('/^Scene(\d+)$/i', $ident, $m)) {
                                        $label = (string)($o['ObjectName'] ?? $ident);
                                        if ((string)$label === (string)$Value) { $target = (int)$m[1]; break; }
                                    }
                                }
                            } else {
                                $target = (int)$target;
                            }
                            if ($target > 0 && function_exists('SZS_CallScene')) {
                                @SZS_CallScene($sceneControlId, $target);
                            }
                        } catch (Throwable $e) {}
                        return;
                    }
                    $varId = (int)($row['VariableId'] ?? 0);
                    if ($varId <= 0 || !@IPS_VariableExists($varId)) return;
                    $variable = @IPS_GetVariable($varId);
                    if (!is_array($variable)) return;
                    $vType = $variable['VariableType'] ?? 0;
                    $actionId = 0;
                    if (isset($variable['VariableCustomAction']) && $variable['VariableCustomAction'] > 0) {
                        $actionId = $variable['VariableCustomAction'];
                    } elseif (isset($variable['VariableAction']) && $variable['VariableAction'] > 0) {
                        $actionId = $variable['VariableAction'];
                    }
                    $hasValidAction = ($actionId > 0) && (@IPS_InstanceExists($actionId) || @IPS_ScriptExists($actionId));
                    if (!$hasValidAction) return;

                    if ($vType === 0) {
                        $newValue = ($Value === null) ? !@GetValue($varId) : (bool)$Value;
                    } elseif ($vType === 1) {
                        $newValue = (int)$Value;
                    } elseif ($vType === 2) {
                        $newValue = (float)$Value;
                    } else {
                        $newValue = (string)$Value;
                    }
                    @RequestAction($varId, $newValue);
                    $this->sendMenuItemDelta($idx, $itemId, $varId);
                    return;
                }
            }
            return;
        }

        // Schalter/Info aus der Kachel: room:<idx>:SchalterN | InfoN
        if (strpos($Ident, 'room:') === 0) {
            $parts = explode(':', $Ident);
            if (count($parts) === 3) {
                $idx = (int)$parts[1];
                $property = $parts[2];
                $rooms = $this->getRooms();
                if (!isset($rooms[$idx]) || !isset($rooms[$idx][$property])) {
                    return;
                }
                $varId = (int)$rooms[$idx][$property];
                if (!IPS_VariableExists($varId)) {
                    $this->SendDebug('RequestAction', 'Variable for switch not existing', 0);
                    return;
                }
                // Typ-spezifisches Setzen
                $variable = IPS_GetVariable($varId);
                $vType = $variable['VariableType'];
                // Prüfe ob eine Action vorhanden ist (VariableCustomAction oder VariableAction)
                $actionId = 0;
                if (isset($variable['VariableCustomAction']) && $variable['VariableCustomAction'] > 0) {
                    $actionId = $variable['VariableCustomAction'];
                } elseif (isset($variable['VariableAction']) && $variable['VariableAction'] > 0) {
                    $actionId = $variable['VariableAction'];
                }
                $hasValidAction = ($actionId > 0) && (@IPS_InstanceExists($actionId) || @IPS_ScriptExists($actionId));

                // Zielwert je nach Variablentyp ermitteln
                if ($vType === 0) { // BOOLEAN -> toggle
                    $newValue = !@GetValue($varId);
                } elseif ($vType === 1) { // INTEGER
                    $newValue = (int)$Value;
                } elseif ($vType === 3) { // STRING
                    $newValue = (string)$Value;
                } else {
                    // Fallback: toggle
                    $newValue = !@GetValue($varId);
                }

                if ($hasValidAction) {
                    @RequestAction($varId, $newValue);
                } else {
                    // Keine gültige Action: Interaktion ignorieren
                    return;
                }
            }
            return;
        }
    }

    public function GetVisualizationTile()
    {
        $mapping = '';
        $mapPath = dirname(__DIR__) . '/RoomHeader/assets/iconMapping.json';
        if (@file_exists($mapPath)) {
            $json = @file_get_contents($mapPath);
            if (is_string($json) && $json !== '') {
                $mapping = '<script>window.iconMapping=' . $json . ';</script>';
            }
        }
        $initial = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ')</script>';
        $module = file_get_contents(__DIR__ . '/module.html');
        if ($mapping !== '') {
            $module = str_replace('<script src="/icons.js" crossorigin="anonymous"></script>', '<script src="/icons.js" crossorigin="anonymous"></script>' . $mapping, $module);
        }
        return $module . $initial;
    }

    private function GetFullUpdateMessage()
    {
        $result = [];
        $result['grid'] = [
            'minWidth' => $this->ReadPropertyInteger('MinWidth'),
            'minHeight' => $this->ReadPropertyInteger('MinHeight'),
            'gap' => $this->ReadPropertyInteger('Gap'),
            'borderRadius' => $this->ReadPropertyInteger('BorderRadius'),
            'useImageColorsForButtons' => $this->ReadPropertyBoolean('UseImageColorsForButtons'),
            'transparentStatusColors' => $this->ReadPropertyBoolean('TransparentStatusColors'),
            'transparentMenuStatusColors' => $this->ReadPropertyBoolean('TransparentMenuStatusColors'),
            'groupMenuInfoElements' => $this->ReadPropertyBoolean('GroupMenuInfoElements'),
            'columns' => $this->ReadPropertyInteger('Columns')
        ];

        $rooms = $this->getRooms();
        // Lese optionale Defaults aus den separaten Properties und mappe sie auf die Raum-Keys
        // Robust: Infohöhe kann in bestehenden Instanzen noch nicht existieren
        $defInfoHoehe = @($this->ReadPropertyInteger('Default_Infohoehe'));
        if (!is_int($defInfoHoehe) || $defInfoHoehe <= 0) {
            // Fallback auf evtl. ältere Schreibweise mit Umlaut
            $alt = @($this->ReadPropertyInteger('Default_Infohöhe'));
            if (is_int($alt) && $alt > 0) {
                $defInfoHoehe = $alt;
            } else {
                $defInfoHoehe = 0;
            }
        }

        $defaults = [
            'InfoSchriftgroesse'       => (int)$this->ReadPropertyInteger('Default_InfoSchriftgroesse'),
            'InfoSchriftfarbe'         => (int)$this->ReadPropertyInteger('Default_InfoSchriftfarbe'),
            'InfoHoehe'                => (int)$defInfoHoehe,
            'InfoMenueSchriftgroesse'  => (int)$this->ReadPropertyInteger('Default_InfoMenueSchriftgroesse'),
            'InfoMenueSchriftfarbe'    => (int)$this->ReadPropertyInteger('Default_InfoMenueSchriftfarbe'),
            'InfoMenueTransparenz'     => (float)$this->ReadPropertyFloat('Default_InfoMenueTransparenz'),
            'InfoMenueHintergrundfarbe'=> (int)$this->ReadPropertyInteger('Default_InfoMenueHintergrundfarbe'),
            'Kachelhintergrundfarbe'   => (int)$this->ReadPropertyInteger('Default_Kachelhintergrundfarbe'),
            'RaumnameSchriftgroesse'   => (int)$this->ReadPropertyInteger('Default_RaumnameSchriftgroesse'),
            'RaumnameSchriftfarbe'     => (int)$this->ReadPropertyInteger('Default_RaumnameSchriftfarbe'),
            'Bildtransparenz'          => (float)$this->ReadPropertyFloat('Default_Bildtransparenz'),
            'InfoTopTransparenz'       => (float)$this->ReadPropertyFloat('Default_InfoTopTransparenz'),
            'InfoTopHintergrundfarbe'  => (int)$this->ReadPropertyInteger('Default_InfoTopHintergrundfarbe'),
            // Image filter defaults
            'BgFilterBrightnessMin'    => (float)$this->ReadPropertyFloat('Default_BgFilterBrightnessMin'),
            'BgFilterBrightnessMax'    => (float)$this->ReadPropertyFloat('Default_BgFilterBrightnessMax'),
            'BgFilterContrastMin'      => (float)$this->ReadPropertyFloat('Default_BgFilterContrastMin'),
            'BgFilterContrastMax'      => (float)$this->ReadPropertyFloat('Default_BgFilterContrastMax'),
            'BgFilterGrayscaleMin'     => (float)$this->ReadPropertyFloat('Default_BgFilterGrayscaleMin'),
            'BgFilterGrayscaleMax'     => (float)$this->ReadPropertyFloat('Default_BgFilterGrayscaleMax')
        ];
        // Normalisiere Transparent(-1) für globale Defaults auf sinnvolle Standardwerte
        if (isset($defaults['InfoSchriftfarbe']) && (int)$defaults['InfoSchriftfarbe'] === -1) {
            $defaults['InfoSchriftfarbe'] = 0xFFFFFF; // Weiß
        }
        if (isset($defaults['InfoMenueSchriftfarbe']) && (int)$defaults['InfoMenueSchriftfarbe'] === -1) {
            $defaults['InfoMenueSchriftfarbe'] = 0xFFFFFF; // Weiß
        }
        if (isset($defaults['InfoMenueHintergrundfarbe']) && (int)$defaults['InfoMenueHintergrundfarbe'] === -1) {
            $defaults['InfoMenueHintergrundfarbe'] = 0x000000; // Schwarz
        }
        if (isset($defaults['Kachelhintergrundfarbe']) && (int)$defaults['Kachelhintergrundfarbe'] === -1) {
            $defaults['Kachelhintergrundfarbe'] = 0x000000; // Schwarz
        }
        if (isset($defaults['RaumnameSchriftfarbe']) && (int)$defaults['RaumnameSchriftfarbe'] === -1) {
            $defaults['RaumnameSchriftfarbe'] = 0xFFFFFF; // Weiß
        }
        if (isset($defaults['InfoTopHintergrundfarbe']) && (int)$defaults['InfoTopHintergrundfarbe'] === -1) {
            $defaults['InfoTopHintergrundfarbe'] = 0x000000; // Schwarz
        }
        $resultRooms = [];

        foreach ($rooms as $idx => $room) {
            $r = [];
            $r['idx'] = $idx;

            // Styles (kombiniere Defaults + Raum-spezifisch)
            $inf = null;
            if (array_key_exists('InfoSchriftgroesse', $room)) { $inf = (int)$room['InfoSchriftgroesse']; }
            $r['infofontsize'] = ($inf === null || $inf <= 0) ? (int)($defaults['InfoSchriftgroesse'] ?? 16) : $inf;
            // Höhe der Infoleiste (nur globaler Default)
            $r['infoheight'] = (int)($defaults['InfoHoehe'] ?? 0);

            $imf = null;
            if (array_key_exists('InfoMenueSchriftgroesse', $room)) { $imf = (int)$room['InfoMenueSchriftgroesse']; }
            $r['infomenuefontsize'] = ($imf === null || $imf <= 0) ? (int)($defaults['InfoMenueSchriftgroesse'] ?? 16) : $imf;

            $kcol = null;
            if (array_key_exists('Kachelhintergrundfarbe', $room)) { $kcol = (int)$room['Kachelhintergrundfarbe']; }
            if ($kcol === null || $kcol === -1) { $kcol = (int)($defaults['Kachelhintergrundfarbe'] ?? 0x000000); }
            $r['hintergrundfarbe'] = $this->toCssHex($kcol);

            $icol = null;
            if (array_key_exists('InfoSchriftfarbe', $room)) { $icol = (int)$room['InfoSchriftfarbe']; }
            if ($icol === null || $icol === -1) { $icol = (int)($defaults['InfoSchriftfarbe'] ?? 0xFFFFFF); }
            $r['infoschriftfarbe'] = $this->toCssHex($icol);

            $imcol = null;
            if (array_key_exists('InfoMenueSchriftfarbe', $room)) { $imcol = (int)$room['InfoMenueSchriftfarbe']; }
            if ($imcol === null || $imcol === -1) { $imcol = (int)($defaults['InfoMenueSchriftfarbe'] ?? 0xFFFFFF); }
            $r['infomenueschriftfarbe'] = $this->toCssHex($imcol);

            // Info-Top Layout: Centered in the middle?
            $r['infotopcentered'] = (bool)($room['InfoTopCentered'] ?? false);

            // Info-Top Badge Hintergrund (einheitlich für links/mitte/rechts)
            // Farbe: zuerst neues Feld, sonst alte per-Side Felder als Fallback
            if (array_key_exists('InfoTopHintergrundfarbe', $room) && (int)$room['InfoTopHintergrundfarbe'] !== -1) {
                $topCol = (int)$room['InfoTopHintergrundfarbe'];
            } elseif (array_key_exists('InfoTopLeftHintergrundfarbe', $room) && (int)$room['InfoTopLeftHintergrundfarbe'] !== -1) {
                $topCol = (int)$room['InfoTopLeftHintergrundfarbe'];
            } elseif (array_key_exists('InfoTopMidHintergrundfarbe', $room) && (int)$room['InfoTopMidHintergrundfarbe'] !== -1) {
                $topCol = (int)$room['InfoTopMidHintergrundfarbe'];
            } elseif (array_key_exists('InfoTopRightHintergrundfarbe', $room) && (int)$room['InfoTopRightHintergrundfarbe'] !== -1) {
                $topCol = (int)$room['InfoTopRightHintergrundfarbe'];
            } else {
                $topCol = (int)($defaults['InfoTopHintergrundfarbe'] ?? 0x000000);
            }

            // Transparenz: zuerst neues Feld, sonst alte per-Side Felder als Fallback
            if (array_key_exists('InfoTopTransparenz', $room)) {
                $tmp = (float)$room['InfoTopTransparenz'];
                $topAlphaPercent = ($tmp < 0)
                    ? $this->normalizePercent((float)($defaults['InfoTopTransparenz'] ?? 30.0))
                    : $this->normalizePercent($tmp);
            } elseif (array_key_exists('InfoTopLeftTransparenz', $room)) {
                $topAlphaPercent = $this->normalizePercent((float)$room['InfoTopLeftTransparenz']);
            } elseif (array_key_exists('InfoTopMidTransparenz', $room)) {
                $topAlphaPercent = $this->normalizePercent((float)$room['InfoTopMidTransparenz']);
            } elseif (array_key_exists('InfoTopRightTransparenz', $room)) {
                $topAlphaPercent = $this->normalizePercent((float)$room['InfoTopRightTransparenz']);
            } else {
                $topAlphaPercent = $this->normalizePercent((float)($defaults['InfoTopTransparenz'] ?? 30.0));
            }
            $topRgba = $this->cssRgba($topCol, $this->percentToAlpha($topAlphaPercent));
            $r['infotopleftbg'] = $topRgba;
            $r['infomidbg'] = $topRgba;
            $r['infotoprightbg'] = $topRgba;

            if (array_key_exists('InfoMenueHintergrundfarbe', $room) && (int)$room['InfoMenueHintergrundfarbe'] !== -1) {
                $bgCol = (int)$room['InfoMenueHintergrundfarbe'];
                $alphaVal = null;
                if (array_key_exists('InfoMenueTransparenz', $room)) { $alphaVal = (float)$room['InfoMenueTransparenz']; }
                $bgAlphaPercent = ($alphaVal === null || $alphaVal < 0)
                    ? $this->normalizePercent((float)($defaults['InfoMenueTransparenz'] ?? 30.0))
                    : $this->normalizePercent($alphaVal);
            } else {
                $bgCol = (int)($defaults['InfoMenueHintergrundfarbe'] ?? 0x000000);
                $bgAlphaPercent = $this->normalizePercent((float)($defaults['InfoMenueTransparenz'] ?? 30.0));
            }
            $r['infomenuehintergrundfarbe'] = $this->cssRgba((int)$bgCol, $this->percentToAlpha($bgAlphaPercent));
            $r['menuetransparenz'] = $bgAlphaPercent; // For menu status color transparency
            $r['schalteralignment'] = (string)($room['SchalterAlignment'] ?? 'left');
            $r['schalterdistribute'] = (bool)($room['SchalterDistribute'] ?? false);
            $r['transparenz'] = $this->percentToAlpha($this->normalizePercent($this->ReadNumOrDefault($room, 'Bildtransparenz', $defaults, 70.0)));

            // Background image filter parameters (merged defaults + per-room overrides)
            $r['bgfilterbrightnessmin'] = (float)$this->ReadNumOrDefault($room, 'BgFilterBrightnessMin', $defaults, 0.2);
            $r['bgfilterbrightnessmax'] = (float)$this->ReadNumOrDefault($room, 'BgFilterBrightnessMax', $defaults, 1.0);
            $r['bgfiltercontrastmin']   = (float)$this->ReadNumOrDefault($room, 'BgFilterContrastMin', $defaults, 0.9);
            $r['bgfiltercontrastmax']   = (float)$this->ReadNumOrDefault($room, 'BgFilterContrastMax', $defaults, 1.0);
            $r['bgfiltergrayscalemin']  = (float)$this->ReadNumOrDefault($room, 'BgFilterGrayscaleMin', $defaults, 0.0);
            $r['bgfiltergrayscalemax']  = (float)$this->ReadNumOrDefault($room, 'BgFilterGrayscaleMax', $defaults, 0.5);

            $r['raumname'] = (string)($room['Raumname'] ?? 'Raumname');
            $r['targetlink'] = (int)($room['Target'] ?? 0);
            $rn = null;
            if (array_key_exists('RaumnameSchriftgroesse', $room)) {
                $rn = (int)$room['RaumnameSchriftgroesse'];
            }
            if ($rn === null || $rn === -1) {
                $r['raumnameschriftgroesse'] = (int)($defaults['RaumnameSchriftgroesse'] ?? 64);
            } else {
                $r['raumnameschriftgroesse'] = $rn;
            }
            $rncol = null;
            if (array_key_exists('RaumnameSchriftfarbe', $room)) { $rncol = (int)$room['RaumnameSchriftfarbe']; }
            if ($rncol === null || $rncol === -1) { $rncol = (int)($defaults['RaumnameSchriftfarbe'] ?? 0xFFFFFF); }
            $r['raumnameschriftfarbe'] = $this->toCssHex($rncol);
            // Sichtbarkeit des Raumnamens (Default: true)
            $r['showroomname'] = array_key_exists('ShowRoomName', $room) ? (bool)$room['ShowRoomName'] : true;

            $r['infomenueswitch'] = (bool)($room['InfoMenueSwitch'] ?? true);

            // Use image colors for buttons per room (fallback to global flag)
            $useImgCol = array_key_exists('UseImageColorsForButtons', $room)
                ? (bool)$room['UseImageColorsForButtons']
                : (bool)$this->ReadPropertyBoolean('UseImageColorsForButtons');
            $r['useimagecolors'] = $useImgCol || $this->ReadPropertyBoolean('UseImageColorsForButtons');

            // Group menu info elements per room (fallback to global flag)
            $r['groupmenuinfoelements'] = array_key_exists('GroupMenuInfoElements', $room)
                ? (bool)$room['GroupMenuInfoElements']
                : (bool)$this->ReadPropertyBoolean('GroupMenuInfoElements');

            // Bilder: per WebHook ausliefern (Base64 via JSON)
            $imageID = (int)($room['bgImage'] ?? 0);
            $imageID2 = (int)($room['bgImage2'] ?? 0);
            // Prüfe ob Media-IDs gültig sind
            if ($imageID2 > 0 && !@IPS_MediaExists($imageID2)) {
                $imageID2 = 0; // Ungültige Media-ID ignorieren
            }
            $r['image1'] = $this->BuildImageHookUrl($imageID);
            if ($imageID2 > 0) {
                $r['image2'] = $this->BuildImageHookUrl($imageID2);
                $r['image2enabled'] = true;
            } else {
                $r['image2enabled'] = false;
                // image2 nicht setzen, um 404-Fehler zu vermeiden
            }

            // Hintergrundfilter aus Lichtstatus/Dimmwert
            try {
                $boolId = (int)($room['Lichtstatus'] ?? 0);
                $dimId = (int)($room['Dimmwert'] ?? 0);
                $hasBool = $boolId > 0 && IPS_VariableExists($boolId);
                $hasDim = $dimId > 0 && IPS_VariableExists($dimId);
                $boolVal = false;
                $dimVal = 0.0;
                if ($hasBool) { try { $boolVal = (bool)@GetValue($boolId); } catch (Throwable $e) { $boolVal = false; } }
                if ($hasDim) { try { $dimVal = (float)@GetValue($dimId); } catch (Throwable $e) { $dimVal = 0.0; } }
                if ($dimVal < 0) {
                    $dimVal = 0.0;
                } elseif ($dimVal > 100) {
                    $dimVal = 100.0;
                }

                $pOut = 0.0;
                if ($hasBool) {
                    if ($boolVal === false) {
                        $pOut = 100.0;
                    } else {
                        if ($hasDim) {
                            $pOut = 100.0 - $dimVal;
                        }
                    }
                } elseif ($hasDim) {
                    $pOut = 100.0 - $dimVal;
                }

                if (!$hasBool && !$hasDim) {
                    // Wenn kein Lichtstatus/Dimmwert: Standardfilter nur bei einem Bild
                    $pOut = ($imageID2 > 0) ? 0.0 : 0.0;
                }
                if ($pOut < 0.0) {
                    $pOut = 0.0;
                }
                // Wenn Bild 2 konfiguriert ist, Filter deaktivieren (bgfilter = 0)
                if ($imageID2 > 0) { $pOut = 0.0; }
                $r['bgfilter'] = $pOut;
                // bgfade nur senden wenn zweites Bild konfiguriert ist
                if ($imageID2 > 0) {
                    $fade = 0.0;
                    if ($hasBool && !$boolVal) { $fade = 100.0; }
                    elseif ($hasDim) { $fade = 100.0 - $dimVal; }
                    if ($fade < 0.0) $fade = 0.0; if ($fade > 100.0) $fade = 100.0;
                    $r['bgfade'] = $fade;
                } else {
                    $r['bgfade'] = 0.0;
                }
            } catch (Throwable $e) {}

            $roomInfoList = $this->parseRoomList($room['InfoItems'] ?? []);
            $roomMenuList = $this->parseRoomList($room['MenuItems'] ?? []);
            $infoItems = $this->buildDynamicInfo($roomInfoList, $r);
            $menuItems = $this->buildDynamicMenu($roomMenuList, $r);

            if (!empty($infoItems)) {
                $r['infoitems'] = $infoItems;
            }
            if (!empty($menuItems)) {
                $r['menuitems'] = $menuItems;
            }

            $needStaticInfo = empty($infoItems);
            $needStaticMenu = empty($menuItems);
            if ($needStaticInfo || $needStaticMenu) {
                $this->fillInfoAndButtons($r, $room, $needStaticInfo, $needStaticMenu);
            }

            $resultRooms[] = $r;
        }

        $result['rooms'] = $resultRooms;
        return $result;
    }

    private function buildDynamicInfo(array $list, array &$outRoom): array
    {
        $items = [];
        $idx = 0;
        foreach ($list as $row) {
            if (!is_array($row)) continue;
            $id = (string)($row['Id'] ?? '');
            $area = (string)($row['Area'] ?? 'left');
            $varId = (int)($row['VariableId'] ?? 0);
            $showName = isset($row['ShowName']) ? (bool)$row['ShowName'] : false;
            $showIcon = isset($row['ShowIcon']) ? (bool)$row['ShowIcon'] : false;
            $showValue = isset($row['ShowValue']) ? (bool)$row['ShowValue'] : true;
            $useVarColor = isset($row['UseVarColor']) ? (bool)$row['UseVarColor'] : false;
            $altName = (string)($row['AltName'] ?? '');
            $order = $idx++;
            if ($id === '') {
                $seed = 'I|' . (string)$varId . '|' . $area . '|' . trim($altName);
                $id = 'i' . substr(sha1($seed), 0, 10);
            }
            $key = 'infoitem-' . $id;

            $nameVal = '';
            $valueFormatted = '';
            $icon = '';
            $hasVar = ($varId > 0) && @IPS_VariableExists($varId);
            $bgColor = '';
            if ($hasVar) {
                $vt = null;
                try {
                    $viTmp = @IPS_GetVariable($varId);
                    if (is_array($viTmp)) { $vt = $viTmp['VariableType'] ?? null; }
                } catch (Throwable $e) {}
                try { $valueFormatted = (string)@GetValueFormatted($varId); } catch (Throwable $e) {}
                if ($showName) {
                    try { $nameVal = $altName !== '' ? $altName : (string)@IPS_GetName($varId); } catch (Throwable $e) { $nameVal = $altName; }
                } else {
                    $nameVal = $altName;
                }
                if ($showIcon) {
                    try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) {}
                }
                // Fallback: Profil-/Präsentationsfarbe
                if ($useVarColor) {
                    try {
                        $col = (string)$this->GetColor($varId);
                        if ($col !== '') { $bgColor = '#' . $col; }
                    } catch (Throwable $e) {}
                }
                // Explizite Überschreibung für Bool mit ColorTrue/ColorFalse, wenn gesetzt (nicht Transparent)
                $ct = isset($row['ColorTrue']) ? (int)$row['ColorTrue'] : -1;
                $cf = isset($row['ColorFalse']) ? (int)$row['ColorFalse'] : -1;
                if ($vt === 0 && ($ct !== -1 || $cf !== -1)) {
                    $isOn = false;
                    try { $isOn = (bool)@GetValue($varId); } catch (Throwable $e) { $isOn = false; }
                    if ($isOn && $ct !== -1) {
                        $bgColor = '#' . sprintf('%06X', $ct);
                    } elseif (!$isOn && $cf !== -1) {
                        $bgColor = '#' . sprintf('%06X', $cf);
                    }
                }
            } else {
                $nameVal = $altName;
            }

            if ($nameVal !== '') { $outRoom[$key . 'name'] = $nameVal; }
            if ($valueFormatted !== '') { $outRoom[$key] = $valueFormatted; $outRoom[$key . 'asso'] = $valueFormatted; }
            $outRoom[$key . 'showvalue'] = $showValue;
            $outRoom[$key . 'showicon'] = $showIcon;
            if ($icon !== '' && $icon !== 'Transparent') { $outRoom[$key . 'icon'] = $icon; }

            // Normalisierte Area (left/mid/right)
            $areaNorm = 'left';
            if (in_array($area, ['left','mid','right'], true)) {
                $areaNorm = $area;
            }

            $items[] = [
                'id' => $id,
                'key' => $key,
                'area' => $areaNorm,
                'order' => $order,
                'variableId' => $varId,
                'color' => $bgColor,
            ];
        }
        usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
        return $items;
    }

    private function buildDynamicMenu(array $list, array &$outRoom): array
    {
        $items = [];
        $idx = 0;
        foreach ($list as $row) {
            if (!is_array($row)) continue;
            $id = (string)($row['Id'] ?? '');
            $varId = (int)($row['VariableId'] ?? 0);
            $openObjectId = (int)($row['OpenObjectId'] ?? 0);
            $sceneControlId = (int)($row['SceneControlId'] ?? 0);
            $showName = isset($row['ShowName']) ? (bool)$row['ShowName'] : false;
            $showIcon = isset($row['ShowIcon']) ? (bool)$row['ShowIcon'] : false;
            $showValue = isset($row['ShowValue']) ? (bool)$row['ShowValue'] : false;
            $useVarColor = isset($row['UseVarColor']) ? (bool)$row['UseVarColor'] : false;
            $colorTrue = isset($row['ColorTrue']) ? (int)$row['ColorTrue'] : -1;
            $colorFalse = isset($row['ColorFalse']) ? (int)$row['ColorFalse'] : -1;
            $altName = (string)($row['AltName'] ?? '');
            $width = (int)($row['Width'] ?? 0);
            $fullWidth = isset($row['FullWidth']) ? (bool)$row['FullWidth'] : false;
            $order = $idx++;
            if ($id === '') {
                $seed = 'M|' . (string)$varId . '|' . trim($altName);
                $id = 'm' . substr(sha1($seed), 0, 10);
            }
            $key = 'menuitem-' . $id;

            $valueFormatted = '';
            $icon = '';
            $options = [];
            $rawValue = null;
            $color = '';
            $colorOn = '';
            $colorOff = '';
            $statusBgColor = '';
            $hasVar = ($varId > 0) && @IPS_VariableExists($varId);
            $hasObject = ($openObjectId > 0) && @IPS_ObjectExists($openObjectId);
            $typeVal = null;
            $hasValidAction = false;
            $actionType = 'none';
            if ($hasVar) {
                try {
                    $vi = @IPS_GetVariable($varId);
                    if ($vi && is_array($vi)) {
                        $typeVal = $vi['VariableType'] ?? null;
                        $actionId = 0;
                        if (isset($vi['VariableCustomAction']) && $vi['VariableCustomAction'] > 0) { $actionId = $vi['VariableCustomAction']; }
                        elseif (isset($vi['VariableAction']) && $vi['VariableAction'] > 0) { $actionId = $vi['VariableAction']; }
                        $hasValidAction = ($actionId > 0) && (@IPS_InstanceExists($actionId) || @IPS_ScriptExists($actionId));
                        if ($hasValidAction) {
                            $actionType = 'variable';
                        }
                    }
                } catch (Throwable $e) {}
                try { $valueFormatted = (string)@GetValueFormatted($varId); } catch (Throwable $e) {}
                try { $rawValue = @GetValue($varId); } catch (Throwable $e) {}
                try {
                    if ($typeVal === 1) {
                        $opts = TileVisuLib::getIntegerAssociations($varId);
                        if (is_array($opts) && !empty($opts)) { $options = $opts; }
                    } elseif ($typeVal === 3) {
                        $opts = TileVisuLib::getStringAssociations($varId);
                        if (is_array($opts) && !empty($opts)) { $options = $opts; }
                    }
                } catch (Throwable $e) {}
                if ($showIcon) { try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) {} }
                // Colors for boolean
                if ($typeVal === 0) {
                    $btnColors = $this->GetButtonColors($varId);
                    if (!empty($btnColors['on'])) { $colorOn = $btnColors['on']; }
                    if (!empty($btnColors['off'])) { $colorOff = $btnColors['off']; }
                }
                // Status background color for no-action items - same logic as Info Badges
                if ($useVarColor) {
                    // First try GetColor (works for all variable types including Bool)
                    $c = $this->GetColor($varId);
                    if ($c !== '') {
                        $statusBgColor = '#' . $c;
                        $color = '#' . $c;
                    }
                    // Explicit override for Bool using ColorTrue/ColorFalse when set (not Transparent/-1)
                    if ($typeVal === 0 && ($colorTrue !== -1 || $colorFalse !== -1)) {
                        $isOn = false;
                        try { $isOn = (bool)@GetValue($varId); } catch (Throwable $e) {}
                        if ($isOn && $colorTrue !== -1) {
                            $statusBgColor = '#' . sprintf('%06X', $colorTrue);
                        } elseif (!$isOn && $colorFalse !== -1) {
                            $statusBgColor = '#' . sprintf('%06X', $colorFalse);
                        }
                    }
                }
            } elseif ($sceneControlId > 0 && @IPS_InstanceExists($sceneControlId)) {
                // Szenensteuerung: Baue Optionen aus Scene1..N inkl. Icons, ermittle aktive Szene
                $hasValidAction = true;
                $actionType = 'scenecontrol';
                $options = [];
                $labelToIndex = [];
                try {
                    foreach ((array)@IPS_GetChildrenIDs($sceneControlId) as $cid) {
                        if (!@IPS_VariableExists($cid)) continue;
                        $o = @IPS_GetObject($cid);
                        $ident = (string)($o['ObjectIdent'] ?? '');
                        if (preg_match('/^Scene(\\d+)$/i', $ident, $m)) {
                            $idxScene = (int)$m[1];
                            $label = (string)($o['ObjectName'] ?? $ident);
                            $iconName = (string)($o['ObjectIcon'] ?? '');
                            $opt = ['value' => $idxScene, 'label' => $label];
                            if ($iconName !== '') { $opt['icon'] = $iconName; }
                            $options[] = $opt;
                            $labelToIndex[$label] = $idxScene;
                        } elseif ($ident === 'ActiveScene') {
                            // Wird später zum Auslesen genutzt
                        }
                    }
                    if (!empty($options)) {
                        usort($options, fn($a, $b) => ((int)($a['value'] ?? 0)) <=> ((int)($b['value'] ?? 0)));
                    }
                } catch (Throwable $e) {}
                try {
                    $active = null;
                    if (function_exists('SZS_GetActiveScene')) {
                        $active = @SZS_GetActiveScene($sceneControlId);
                    } else {
                        $activeVar = 0;
                        foreach ((array)@IPS_GetChildrenIDs($sceneControlId) as $cid) {
                            if (!@IPS_VariableExists($cid)) continue;
                            $o = @IPS_GetObject($cid);
                            if (($o['ObjectIdent'] ?? '') === 'ActiveScene') { $activeVar = $cid; break; }
                        }
                        if ($activeVar > 0) { $active = @GetValue($activeVar); }
                    }
                    if (is_numeric($active)) {
                        $rawValue = (int)$active;
                    } elseif (is_string($active) && isset($labelToIndex[$active])) {
                        $rawValue = (int)$labelToIndex[$active];
                    }
                } catch (Throwable $e) {}
                // Optional: assoziierter Text
                if (isset($activeVar) && $activeVar > 0) {
                    try { $valueFormatted = (string)@GetValueFormatted($activeVar); } catch (Throwable $e) {}
                }
            }
            if (!$hasValidAction && $hasObject) {
                $hasValidAction = true;
                $actionType = 'object';
            }
            if ($altName !== '' || $showName || $hasObject) {
                $derivedName = $altName !== '' ? $altName : ($hasVar ? (string)@IPS_GetName($varId) : ($hasObject ? (string)@IPS_GetName($openObjectId) : ''));
                if ($derivedName !== '') { $outRoom[$key . 'name'] = $derivedName; }
            }
            if ($valueFormatted !== '' && $showValue) { $outRoom[$key] = $valueFormatted; $outRoom[$key . 'asso'] = $valueFormatted; }
            $outRoom[$key . 'showvalue'] = $showValue;
            $outRoom[$key . 'showicon'] = $showIcon || $hasObject;
            if (($icon === '' || $icon === 'Transparent') && $hasObject) {
                try { $icon = (string)@IPS_GetObject($openObjectId)['ObjectIcon']; } catch (Throwable $e) { $icon = ''; }
            }
            if ($icon !== '' && $icon !== 'Transparent') { $outRoom[$key . 'icon'] = $icon; }
            if ($hasObject) {
                $outRoom[$key . 'openobject'] = $openObjectId;
            }

            $items[] = [
                'id' => $id,
                'key' => $key,
                'order' => $order,
                'variableId' => $varId,
                'openObjectId' => $hasObject ? $openObjectId : 0,
                'type' => $typeVal,
                'hasaction' => $hasValidAction,
                'action' => $actionType,
                'width' => $width,
                'fullWidth' => $fullWidth,
                'showName' => $showName,
                'showValue' => $showValue,
                'showIcon' => $showIcon,
                'options' => $options,
                'value' => $rawValue,
                'color' => $color,
                'colorOn' => $colorOn,
                'colorOff' => $colorOff,
                'useVarColor' => $useVarColor,
                'statusBgColor' => $statusBgColor,
            ];
        }
        usort($items, fn($a, $b) => $a['order'] <=> $b['order']);
        return $items;
    }

    private function NormalizeRoomLists(): bool
    {
        $rooms = $this->getRooms();
        $changed = false;
        foreach ($rooms as &$room) {
            $rawInfo = $room['InfoItems'] ?? [];
            $info = $this->parseRoomList($rawInfo);
            $resultInfo = $this->ensureListIds($info, 'i');
            $infoJson = json_encode($resultInfo['list']);
            if (!is_string($rawInfo) || $resultInfo['changed'] || $rawInfo !== $infoJson) {
                $room['InfoItems'] = $infoJson !== false ? $infoJson : '[]';
                $changed = true;
            } else {
                // ensure value is stored as string even if already normalized
                $room['InfoItems'] = $infoJson !== false ? $infoJson : '[]';
            }

            $rawMenu = $room['MenuItems'] ?? [];
            $menu = $this->parseRoomList($rawMenu);
            $resultMenu = $this->ensureListIds($menu, 'm');
            $menuJson = json_encode($resultMenu['list']);
            if (!is_string($rawMenu) || $resultMenu['changed'] || $rawMenu !== $menuJson) {
                $room['MenuItems'] = $menuJson !== false ? $menuJson : '[]';
                $changed = true;
            } else {
                $room['MenuItems'] = $menuJson !== false ? $menuJson : '[]';
            }
        }
        unset($room);

        if ($changed) {
            IPS_SetProperty($this->InstanceID, 'Rooms', json_encode($rooms));
            IPS_ApplyChanges($this->InstanceID);
            return true;
        }
        return false;
    }

    private function fillInfoAndButtons(array &$out, array $room, bool $includeInfo, bool $includeMenu): void
    {
        if ($includeInfo) {
            $pairs = [
                'InfoLinks' => 'infolinks',
                'InfoLinks2' => 'infolinks2',
                'InfoRechts' => 'inforechts',
                'InfoRechts2' => 'inforechts2'
            ];
            foreach ($pairs as $prop => $key) {
                $id = (int)($room[$prop] ?? 0);
                if ($id > 0 && IPS_VariableExists($id)) {
                    $col = $this->GetColor($id);
                    if ($col !== '') {
                        $out[$key . 'color'] = '#' . $col;
                    }
                    $val = GetValueFormatted($id);
                    $out[$key] = $val;
                    $out[$key . 'asso'] = $val;
                    $typeLocal = null;
                    try { $viTmp = isset($varInfo) ? $varInfo : IPS_GetVariable($id); $typeLocal = $viTmp['VariableType'] ?? null; } catch (Throwable $e) {}
                    $defaultShowName = ($typeLocal === 0);
                    if ($this->ReadBoolOrDefault($room, $prop . 'NameSwitch', $defaultShowName)) {
                        $out[$key . 'name'] = IPS_GetName($id);
                    }
                    $icon = $this->GetIconAdvanced($id);
                    if ($icon !== 'Transparent' && $icon !== '') {
                        $out[$key . 'icon'] = $icon;
                    }
                    $out[$key . 'showvalue'] = $this->ReadBoolOrDefault($room, $prop . 'ShowValue', true);
                    $out[$key . 'showicon'] = $this->ReadBoolOrDefault($room, $prop . 'IconSwitch', false);
                }
            }

            for ($i = 1; $i <= 5; $i++) {
                $prop = 'Info' . $i;
                $id = (int)($room[$prop] ?? 0);
                if ($id > 0 && IPS_VariableExists($id)) {
                    $key = 'info' . $i;
                    try {
                        $col = $this->GetColor($id);
                        if ($col !== '') {
                            $out[$key . 'color'] = '#' . $col;
                        }
                    } catch (Throwable $e) {}
                    try {
                        $varInfo = IPS_GetVariable($id);
                        $out[$key . 'configured'] = true;
                        if (isset($varInfo['VariableType'])) {
                            $out[$key . 'type'] = $varInfo['VariableType'];
                        }
                        $actionId = 0;
                        if (isset($varInfo['VariableCustomAction']) && $varInfo['VariableCustomAction'] > 0) {
                            $actionId = $varInfo['VariableCustomAction'];
                        } elseif (isset($varInfo['VariableAction']) && $varInfo['VariableAction'] > 0) {
                            $actionId = $varInfo['VariableAction'];
                        }
                        $hasValidAction = ($actionId > 0) && (@IPS_InstanceExists($actionId) || @IPS_ScriptExists($actionId));
                        $out[$key . 'hasaction'] = $hasValidAction;
                    } catch (Throwable $e) {}
                    try { $out[$key . 'value'] = GetValue($id); } catch (Throwable $e) {}
                    try {
                        $vi = isset($varInfo) ? $varInfo : IPS_GetVariable($id);
                        if (isset($vi['VariableType'])) {
                            if ($vi['VariableType'] === 1) {
                                $opts = TileVisuLib::getIntegerAssociations($id);
                                if (!empty($opts)) { $out[$key . 'options'] = $opts; }
                            } elseif ($vi['VariableType'] === 3) {
                                $opts = TileVisuLib::getStringAssociations($id);
                                if (!empty($opts)) { $out[$key . 'options'] = $opts; }
                            }
                        }
                    } catch (Throwable $e) {}
                    $val = GetValueFormatted($id);
                    $out[$key] = $val;
                    $out[$key . 'asso'] = $val;
                    if ($this->ReadBoolOrDefault($room, $prop . 'NameSwitch', false)) {
                        $out[$key . 'name'] = IPS_GetName($id);
                    }
                    $icon = $this->GetIconAdvanced($id);
                    if ($icon !== 'Transparent' && $icon !== '') {
                        $out[$key . 'icon'] = $icon;
                    }
                    $out[$key . 'showvalue'] = $this->ReadBoolOrDefault($room, $prop . 'ShowValue', true);
                }
            }
        }

        if ($includeMenu) {
            for ($i = 1; $i <= 5; $i++) {
                $prop = 'Schalter' . $i;
                $openProp = $prop . 'OpenObjectId';
                $varId = (int)($room[$prop] ?? 0);
                $openObjectId = (int)($room[$openProp] ?? 0);
                $hasVar = ($varId > 0) && IPS_VariableExists($varId);
                $hasObject = ($openObjectId > 0) && IPS_ObjectExists($openObjectId);
                if (!$hasVar && !$hasObject) {
                    continue;
                }
                $key = 'schalter' . $i;
                $out[$key . 'configured'] = true;

                $showName = $this->ReadBoolOrDefault($room, $prop . 'NameSwitch', false);
                $showIcon = $this->ReadBoolOrDefault($room, $prop . 'IconSwitch', true);
                $showValue = $this->ReadBoolOrDefault($room, $prop . 'ShowValue', false);
                $altName = (string)($room[$prop . 'AltName'] ?? '');

                $valueFormatted = '';
                $rawValue = null;
                $typeVal = null;
                $options = [];
                $color = '';
                $colorOn = '';
                $colorOff = '';
                $iconFromVar = '';
                $hasValidAction = false;

                if ($hasVar) {
                    try {
                        $varInfo = IPS_GetVariable($varId);
                        if (isset($varInfo['VariableType'])) {
                            $typeVal = $varInfo['VariableType'];
                        }
                        $actionId = 0;
                        if (isset($varInfo['VariableCustomAction']) && $varInfo['VariableCustomAction'] > 0) {
                            $actionId = $varInfo['VariableCustomAction'];
                        } elseif (isset($varInfo['VariableAction']) && $varInfo['VariableAction'] > 0) {
                            $actionId = $varInfo['VariableAction'];
                        }
                        $hasValidAction = ($actionId > 0) && (@IPS_InstanceExists($actionId) || @IPS_ScriptExists($actionId));
                        if ($typeVal === 0 && $hasValidAction) {
                            $btnColors = $this->GetButtonColors($varId);
                            if ($btnColors['on'] !== '') { $colorOn = $btnColors['on']; }
                            if ($btnColors['off'] !== '') { $colorOff = $btnColors['off']; }
                        } elseif ($typeVal !== null) {
                            $col = $this->GetColor($varId);
                            if ($col !== '') {
                                $color = '#' . $col;
                            }
                        }
                    } catch (Throwable $e) {}
                    try { $valueFormatted = (string)GetValueFormatted($varId); } catch (Throwable $e) { $valueFormatted = ''; }
                    try { $rawValue = GetValue($varId); } catch (Throwable $e) { $rawValue = null; }
                    try {
                        if ($typeVal === 1) {
                            $opts = TileVisuLib::getIntegerAssociations($varId);
                            if (!empty($opts)) { $options = $opts; }
                        } elseif ($typeVal === 3) {
                            $opts = TileVisuLib::getStringAssociations($varId);
                            if (!empty($opts)) { $options = $opts; }
                        }
                    } catch (Throwable $e) {}
                    try { $iconFromVar = $this->GetIconAdvanced($varId); } catch (Throwable $e) { $iconFromVar = ''; }

                    $out[$key] = $valueFormatted;
                    $out[$key . 'asso'] = $valueFormatted;
                    if ($rawValue !== null) {
                        $out[$key . 'value'] = $rawValue;
                    }
                    if ($altName === '' && $showName) {
                        try { $out[$key . 'name'] = IPS_GetName($varId); } catch (Throwable $e) {}
                    }
                } else {
                    $out[$key] = '';
                    $out[$key . 'asso'] = '';
                }

                if ($iconFromVar !== '' && $iconFromVar !== 'Transparent') {
                    $out[$key . 'icon'] = $iconFromVar;
                }
                if ($color !== '') {
                    $out[$key . 'color'] = $color;
                }
                if ($colorOn !== '') {
                    $out[$key . 'colorOn'] = $colorOn;
                }
                if ($colorOff !== '') {
                    $out[$key . 'colorOff'] = $colorOff;
                }
                if (!empty($options)) {
                    $out[$key . 'options'] = $options;
                }
                if ($typeVal !== null) {
                    $out[$key . 'type'] = $typeVal;
                }
                if ($altName !== '') {
                    $out[$key . 'name'] = $altName;
                }

                if ($hasObject) {
                    $out[$key . 'openobject'] = $openObjectId;
                    try {
                        $object = IPS_GetObject($openObjectId);
                        $objectName = (string)($object['ObjectName'] ?? '');
                        $objectIcon = (string)($object['ObjectIcon'] ?? '');
                    } catch (Throwable $e) {
                        $objectName = '';
                        $objectIcon = '';
                    }
                    if ($objectName !== '' && $altName === '') {
                        $out[$key . 'name'] = $objectName;
                    }
                    if ($objectIcon !== '' && $objectIcon !== 'Transparent') {
                        $out[$key . 'icon'] = $objectIcon;
                    }
                    $hasValidAction = true;
                } else {
                    $out[$key . 'openobject'] = 0;
                }

                $out[$key . 'hasaction'] = $hasValidAction || $hasObject;
                $out[$key . 'showvalue'] = $showValue;
                $out[$key . 'showname'] = $showName;
                $out[$key . 'showicon'] = $showIcon;
                $out[$key . 'iconswitch'] = $showIcon;
            }
        }
    }

    private function ReadBoolOrDefault(array $room, string $key, bool $default): bool
    {
        if (array_key_exists($key, $room)) {
            return (bool)$room[$key];
        }
        return $default;
    }

    private function ReorderRoom(int $index, string $direction)
    {
        $rooms = $this->getRooms();
        $count = count($rooms);
        if ($count === 0 || $index < 0 || $index >= $count) {
            return;
        }
        if ($direction === 'up' && $index > 0) {
            $tmp = $rooms[$index - 1];
            $rooms[$index - 1] = $rooms[$index];
            $rooms[$index] = $tmp;
        } elseif ($direction === 'down' && $index < $count - 1) {
            $tmp = $rooms[$index + 1];
            $rooms[$index + 1] = $rooms[$index];
            $rooms[$index] = $tmp;
        } else {
            return;
        }
        IPS_SetProperty($this->InstanceID, 'Rooms', json_encode($rooms));
        IPS_ApplyChanges($this->InstanceID);
    }

    private function getRooms(): array
    {
        $json = $this->ReadPropertyString('Rooms');
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    private function parseRoomList($value): array
    {
        if (is_string($value)) {
            $decoded = @json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        if (is_array($value)) {
            return $value;
        }
        return [];
    }

    private function ensureListIds(array $list, string $prefix): array
    {
        $seen = [];
        $changed = false;
        foreach ($list as &$row) {
            if (!is_array($row)) {
                $row = [];
                $changed = true;
            }
            $id = isset($row['Id']) ? (string)$row['Id'] : '';
            if ($id === '' || isset($seen[$id])) {
                try { $id = $prefix . bin2hex(random_bytes(6)); } catch (Throwable $e) { $id = $prefix . substr(sha1(uniqid('', true)), 0, 12); }
                $row['Id'] = $id;
                $changed = true;
            }
            $seen[$id] = true;
        }
        unset($row);
        return ['list' => $list, 'changed' => $changed];
    }

    private function sendMenuItemDelta(int $idx, string $itemId, int $varId): void
    {
        if ($varId <= 0 || !@IPS_VariableExists($varId)) {
            return;
        }
        try {
            $delta = $this->buildMenuItemDelta($idx, $itemId, $varId);
            if (!empty($delta)) {
                $this->UpdateVisualizationValue(json_encode(['delta' => $delta]));
            }
        } catch (Throwable $e) {
        }
    }

    private function sendInfoItemDelta(int $idx, string $itemId, int $varId): void
    {
        if ($varId <= 0 || !@IPS_VariableExists($varId)) {
            return;
        }
        try {
            $delta = $this->buildInfoItemDelta($idx, $itemId, $varId);
            if (!empty($delta)) {
                $this->UpdateVisualizationValue(json_encode(['delta' => $delta]));
            }
        } catch (Throwable $e) {
        }
    }

    private function buildMenuItemDelta(int $idx, string $itemId, int $varId): array
    {
        $delta = [];
        try {
            $value = @GetValue($varId);
            $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'value', 'value' => $value];
            $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'asso', 'value' => (string)@GetValueFormatted($varId)];
            try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) { $icon = ''; }
            if ($icon !== '' && $icon !== 'Transparent') {
                $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'icon', 'value' => $icon];
            }
            // Determine correct name: prefer AltName; then object name; else only use variable name when ShowName is true
            $altName = ''; $showName = false; $openObjectId = 0; $objectName = '';
            try {
                $rooms = $this->getRooms();
                if (isset($rooms[$idx]) && is_array($rooms[$idx])) {
                    $room = $rooms[$idx];
                    $list = $this->parseRoomList($room['MenuItems'] ?? []);
                    foreach ($list as $row) {
                        if (!is_array($row)) continue;
                        $rid = (string)($row['Id'] ?? '');
                        if ($rid === $itemId) {
                            $altName = (string)($row['AltName'] ?? '');
                            $showName = isset($row['ShowName']) ? (bool)$row['ShowName'] : false;
                            $openObjectId = (int)($row['OpenObjectId'] ?? 0);
                            break;
                        }
                    }
                }
            } catch (Throwable $e) {}
            if ($openObjectId > 0 && @IPS_ObjectExists($openObjectId)) {
                try { $obj = @IPS_GetObject($openObjectId); $objectName = (string)($obj['ObjectName'] ?? ''); } catch (Throwable $e) { $objectName = ''; }
            }
            if ($altName !== '') {
                $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'name', 'value' => $altName];
            } elseif ($openObjectId > 0 && $objectName !== '') {
                $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'name', 'value' => $objectName];
            } elseif ($showName) {
                try { $name = (string)@IPS_GetName($varId); } catch (Throwable $e) { $name = ''; }
                if ($name !== '') {
                    $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'name', 'value' => $name];
                }
            }
        } catch (Throwable $e) {
        }
        return $delta;
    }

    private function buildInfoItemDelta(int $idx, string $itemId, int $varId): array
    {
        $delta = [];
        try {
            $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'asso', 'value' => (string)@GetValueFormatted($varId)];
            try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) { $icon = ''; }
            if ($icon !== '' && $icon !== 'Transparent') {
                $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'icon', 'value' => $icon];
            }
            // Determine correct name: prefer AltName if set; else only use variable name when ShowName is true
            $useColor = false; $ct = -1; $cf = -1; $altName = ''; $showName = false;
            try {
                $rooms = $this->getRooms();
                if (isset($rooms[$idx]) && is_array($rooms[$idx])) {
                    $room = $rooms[$idx];
                    $list = $this->parseRoomList($room['InfoItems'] ?? []);
                    foreach ($list as $row) {
                        if (!is_array($row)) continue;
                        $rid = (string)($row['Id'] ?? '');
                        if ($rid === $itemId) {
                            $useColor = isset($row['UseVarColor']) ? (bool)$row['UseVarColor'] : false;
                            $ct = isset($row['ColorTrue']) ? (int)$row['ColorTrue'] : -1;
                            $cf = isset($row['ColorFalse']) ? (int)$row['ColorFalse'] : -1;
                            $altName = (string)($row['AltName'] ?? '');
                            $showName = isset($row['ShowName']) ? (bool)$row['ShowName'] : false;
                            break;
                        }
                    }
                }
            } catch (Throwable $e) {}
            if ($altName !== '') {
                $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'name', 'value' => $altName];
            } elseif ($showName) {
                try { $name = (string)@IPS_GetName($varId); } catch (Throwable $e) { $name = ''; }
                if ($name !== '') {
                    $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'name', 'value' => $name];
                }
            }
            // Bestimme zu sendende Farbe: Override für Bool > Profil-Farbe > leer
            $sendColor = null;
            $vt = null; $isOn = false;
            try { $viTmp = @IPS_GetVariable($varId); if (is_array($viTmp)) { $vt = $viTmp['VariableType'] ?? null; } } catch (Throwable $e) {}
            if ($vt === 0) {
                try { $isOn = (bool)@GetValue($varId); } catch (Throwable $e) { $isOn = false; }
                if ($isOn && $ct !== -1) {
                    $sendColor = '#' . sprintf('%06X', $ct);
                } elseif (!$isOn && $cf !== -1) {
                    $sendColor = '#' . sprintf('%06X', $cf);
                } elseif ($useColor) {
                    try { $c = (string)$this->GetColor($varId); $sendColor = ($c !== '' ? ('#' . $c) : ''); } catch (Throwable $e) { $sendColor = ''; }
                }
            } elseif ($useColor) {
                try { $c = (string)$this->GetColor($varId); $sendColor = ($c !== '' ? ('#' . $c) : ''); } catch (Throwable $e) { $sendColor = ''; }
            }
            if ($sendColor !== null) {
                $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'color', 'value' => $sendColor];
            }
        } catch (Throwable $e) {
        }
        return $delta;
    }

    private function ReadInt(array $room, string $key): int
    {
        return (int)($room[$key] ?? 0);
    }

    private function ReadBool(array $room, string $key): bool
    {
        return (bool)($room[$key] ?? false);
    }

    private function ReadNumOrDefault(array $room, string $key, array $defaults, float $fallback): float
    {
        if (array_key_exists($key, $room)) {
            $v = (float)$room[$key];
            if ($v < 0) {
                if (array_key_exists($key, $defaults)) {
                    return (float)$defaults[$key];
                }
                return $fallback;
            }
            return $v;
        }
        if (array_key_exists($key, $defaults)) {
            return (float)$defaults[$key];
        }
        return $fallback;
    }

    private function ReadIntOrDefault(array $room, string $key, array $defaults, int $fallback): int
    {
        if (array_key_exists($key, $room)) {
            return (int)$room[$key];
        }
        if (array_key_exists($key, $defaults)) {
            return (int)$defaults[$key];
        }
        return $fallback;
    }

    private function toCssHex(int $rgb): string
    {
        return '#' . sprintf('%06X', $rgb);
    }

    private function cssRgba(int $rgb, float $alpha): string
    {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        return 'rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $alpha . ')';
    }

    private function percentToAlpha(float $percent): float
    {
        if ($percent < 0.0) $percent = 0.0;
        if ($percent > 100.0) $percent = 100.0;
        return $percent / 100.0;
    }

    private function normalizePercent(float $val): float
    {
        // Backwards compatibility: if value is in 0..1, interpret as fraction and convert to percent
        if ($val >= 0.0 && $val <= 1.0) {
            $val = $val * 100.0;
        }
        if ($val < 0.0) $val = 0.0;
        if ($val > 100.0) $val = 100.0;
        return $val;
    }

    private function GetImageDataURI(int $imageID): string
    {
        if ($imageID > 0 && IPS_MediaExists($imageID)) {
            $image = IPS_GetMedia($imageID);
            if ($image['MediaType'] === MEDIATYPE_IMAGE) {
                $imageFile = explode('.', $image['MediaFile']);
                $imageContent = '';
                switch (strtolower((string)end($imageFile))) {
                    case 'bmp': $imageContent = 'data:image/bmp;base64,'; break;
                    case 'jpg':
                    case 'jpeg': $imageContent = 'data:image/jpeg;base64,'; break;
                    case 'gif': $imageContent = 'data:image/gif;base64,'; break;
                    case 'png': $imageContent = 'data:image/png;base64,'; break;
                    case 'ico': $imageContent = 'data:image/x-icon;base64,'; break;
                }
                if ($imageContent) {
                    $imageContent .= IPS_GetMediaContent($imageID);
                    return $imageContent;
                }
            }
        }
        // Fallback: nutze Placeholder aus RoomHeader/assets
        $fallbackPath = dirname(__DIR__) . '/RoomHeader/assets/placeholder.png';
        if (@file_exists($fallbackPath)) {
            return 'data:image/png;base64,' . base64_encode(@file_get_contents($fallbackPath));
        }
        return '';
    }

    private function BuildImageHookUrl(int $mediaId): string
    {
        $base = '/hook/roomgridimages/' . $this->InstanceID;
        $token = $this->ReadAttributeString('HookToken');
        $q = '';
        if ($mediaId > 0) {
            $q = 'mid=' . (int)$mediaId;
        } else {
            $q = 'placeholder=1';
        }
        if ($token !== '') {
            $q .= '&token=' . rawurlencode($token);
        }
        $q .= '&ts=' . time();
        return $base . '?' . $q;
    }

    private function RegisterHook(string $hookPath): void
    {
        $webhookModuleId = '{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}';
        $ids = @IPS_GetInstanceListByModuleID($webhookModuleId);
        if (!is_array($ids) || count($ids) === 0) {
            $this->LogMessage('WebHook Control not found. Skipping hook registration.', KL_WARNING);
            return;
        }
        $whId = $ids[0];
        $instToken = $this->ReadAttributeString('HookToken');
        if ($instToken === '') {
            try {
                $instToken = bin2hex(random_bytes(16));
            } catch (Throwable $e) {
                $instToken = substr(sha1(uniqid('', true)), 0, 32);
            }
            $this->WriteAttributeString('HookToken', $instToken);
        }
        $hooks = @json_decode(IPS_GetProperty($whId, 'Hooks'), true);
        if (!is_array($hooks)) {
            $hooks = [];
        }
        $found = false;
        foreach ($hooks as &$h) {
            if (isset($h['Hook']) && $h['Hook'] === $hookPath) {
                $h['TargetID'] = $this->InstanceID;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $hooks[] = [
                'Hook' => $hookPath,
                'TargetID' => $this->InstanceID
            ];
        }
        IPS_SetProperty($whId, 'Hooks', json_encode($hooks));
        IPS_ApplyChanges($whId);
    }

    private function ensureHookScript(): int
    {
        return 0;
    }

    // Handle WebHook requests directly in the module (no script target)
    protected function ProcessHookData()
    {
        $instT = $this->ReadAttributeString('HookToken');
        $token = isset($_GET['token']) ? (string)$_GET['token'] : '';
        if (!is_string($token) || $token === '' || $instT === '' || !hash_equals($instT, $token)) {
            http_response_code(403);
            return;
        }
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        $mid = isset($_GET['mid']) ? (int)$_GET['mid'] : 0;
        if ($mid > 0 && @IPS_MediaExists($mid)) {
            $m = @IPS_GetMedia($mid);
            if (is_array($m) && isset($m['MediaType']) && $m['MediaType'] === MEDIATYPE_IMAGE) {
                $b64 = @IPS_GetMediaContent($mid);
                if (is_string($b64) && $b64 !== '') {
                    $bin = @base64_decode($b64, true);
                    if ($bin !== false) {
                        $mime = 'application/octet-stream';
                        if (strlen($bin) >= 12) {
                            $hdr = substr($bin, 0, 12);
                            if (strncmp($hdr, "\xFF\xD8\xFF", 3) === 0) {
                                $mime = 'image/jpeg';
                            } elseif (strncmp($hdr, "\x89PNG\x0D\x0A\x1A\x0A", 8) === 0) {
                                $mime = 'image/png';
                            } elseif (strncmp($hdr, 'GIF87a', 6) === 0 || strncmp($hdr, 'GIF89a', 6) === 0) {
                                $mime = 'image/gif';
                            } elseif (substr($hdr, 0, 4) === 'RIFF' && substr($hdr, 8, 4) === 'WEBP') {
                                $mime = 'image/webp';
                            }
                        }
                        header('Content-Type: ' . $mime);
                        header('Content-Length: ' . strlen($bin));
                        echo $bin;
                        return;
                    }
                }
            }
        }

        if (isset($_GET['placeholder'])) {
            $placeholder = dirname(__DIR__) . '/RoomHeader/assets/placeholder.png';
            if (@is_file($placeholder)) {
                $bin = @file_get_contents($placeholder);
                if ($bin !== false) {
                    header('Content-Type: image/png');
                    header('Content-Length: ' . strlen($bin));
                    echo $bin;
                    return;
                }
            }
        }

        http_response_code(404);
    }

    private function GetColor(int $id): string
    {
        return TileVisuLib::getProfileColorHex($id);
    }

    private function GetColorRGBFrom(int $hexcolor, float $transparenz): string
    {
        if ($hexcolor === -1) {
            return '';
        }
        $hexColor = sprintf('%06X', $hexcolor);
        if (strlen($hexColor) === 6) {
            $r = hexdec(substr($hexColor, 0, 2));
            $g = hexdec(substr($hexColor, 2, 2));
            $b = hexdec(substr($hexColor, 4, 2));
            return "rgba($r, $g, $b, $transparenz)";
        }
        return $hexColor;
    }

    private function GetIcon(int $id, bool $varicon): string
    {
        return TileVisuLib::getIcon($id, $varicon);
    }

    private function GetIconAdvanced(int $id): string
    {
        $debug = false;
        if ($id <= 0 || !@IPS_VariableExists($id)) {
            if ($debug) $this->SendDebug('GetIconAdvanced', 'Variable does not exist: ' . $id, 0);
            return 'Transparent';
        }
        $variable = @IPS_GetVariable($id);
        $value = null;
        try { $value = @GetValue($id); } catch (Throwable $e) {}

        $pres = [];
        if (isset($variable['VariableCustomPresentation']) && is_array($variable['VariableCustomPresentation'])) {
            $pres = $variable['VariableCustomPresentation'];
        } elseif (isset($variable['VariablePresentation']) && is_array($variable['VariablePresentation'])) {
            $pres = $variable['VariablePresentation'];
        }

        if (!empty($pres)) {
            if (!empty($pres['ICON'])) {
                return (string)$pres['ICON'];
            }
            if (!empty($pres['Icon'])) {
                return (string)$pres['Icon'];
            }
            if (isset($variable['VariableType']) && $variable['VariableType'] === 0) {
                $iconTrueSet = isset($pres['ICON_TRUE']) && trim((string)$pres['ICON_TRUE']) !== '';
                $iconFalseSet = isset($pres['ICON_FALSE']) && trim((string)$pres['ICON_FALSE']) !== '';
                if ($iconTrueSet || $iconFalseSet) {
                    $useFalse = $pres['USE_ICON_FALSE'] ?? true;
                    $sameBoth = ($iconTrueSet && $iconFalseSet && (string)$pres['ICON_TRUE'] === (string)$pres['ICON_FALSE']);
                    if ($sameBoth) {
                        return (string)$pres['ICON_TRUE'];
                    }
                    $isTrue = ($value === true) || ((string)$value === '1') || ($value === 1);
                    $isFalse = ($value === false) || ((string)$value === '0') || ($value === 0) || ((string)$value === '');
                    if ($iconTrueSet && $isTrue) {
                        return (string)$pres['ICON_TRUE'];
                    }
                    if ($iconFalseSet && $useFalse && $isFalse) {
                        return (string)$pres['ICON_FALSE'];
                    }
                }
            }
            if (isset($pres['OPTIONS']) && !empty($pres['OPTIONS'])) {
                $opts = is_string($pres['OPTIONS']) ? @json_decode($pres['OPTIONS'], true) : $pres['OPTIONS'];
                if (is_array($opts)) {
                    $vtLocal = $variable['VariableType'] ?? 0;
                    $valNorm = ($vtLocal === 0) ? (((($value === true) || ((string)$value === '1') || ($value === 1)) ? '1' : '0')) : (string)$value;
                    foreach ($opts as $opt) {
                        if (!is_array($opt)) continue;
                        if (!array_key_exists('Value', $opt)) continue;
                        $optVal = $opt['Value'];
                        $optNorm = ($vtLocal === 0) ? (((($optVal === true) || ($optVal === 1) || ((string)$optVal === '1')) ? '1' : '0')) : (string)$optVal;
                        if ($vtLocal === 0 ? ($optNorm === $valNorm) : ((string)$optVal === (string)$value)) {
                            $iconVal = $opt['IconValue'] ?? ($opt['Icon'] ?? '');
                            if (!empty($iconVal)) return (string)$iconVal;
                        }
                    }
                }
            }
            if (isset($pres['TEMPLATE']) && function_exists('IPS_GetTemplate')) {
                $tpl = @IPS_GetTemplate($pres['TEMPLATE']);
                // Direct ICON from template default values
                if (is_array($tpl) && isset($tpl['Values']) && is_array($tpl['Values'])) {
                    $vals = $tpl['Values'];
                    if (!empty($vals['ICON'])) { return (string)$vals['ICON']; }
                    if (!empty($vals['Icon'])) { return (string)$vals['Icon']; }
                }
                if (is_array($tpl) && isset($tpl['Values']['OPTIONS'])) {
                    $optsRaw = $tpl['Values']['OPTIONS'];
                    $opts = is_string($optsRaw) ? @json_decode($optsRaw, true) : $optsRaw;
                    if (is_array($opts)) {
                        $vtLocal = $variable['VariableType'] ?? 0;
                        $valNorm = ($vtLocal === 0) ? (((($value === true) || ((string)$value === '1') || ($value === 1)) ? '1' : '0')) : (string)$value;
                        foreach ($opts as $opt) {
                            if (!is_array($opt)) continue;
                            if (!array_key_exists('Value', $opt)) continue;
                            $optVal = $opt['Value'];
                            $optNorm = ($vtLocal === 0) ? (((($optVal === true) || ($optVal === 1) || ((string)$optVal === '1')) ? '1' : '0')) : (string)$optVal;
                            if ($vtLocal === 0 ? ($optNorm === $valNorm) : ((string)$optVal === (string)$value)) {
                                $iconVal = $opt['IconValue'] ?? ($opt['Icon'] ?? '');
                                if (!empty($iconVal)) return (string)$iconVal;
                            }
                        }
                    }
                }
            }
            if (isset($pres['PRESENTATION']) && !empty($pres['PRESENTATION'])) {
                $guidRaw = (string)$pres['PRESENTATION'];
                $guid = $guidRaw;
                if (strpos($guid, '{') === false) {
                    $guid = '{' . $guid . '}';
                }
                try {
                    $pdata = null;
                    $guidExists = false;
                    if (function_exists('IPS_PresentationExists')) {
                        $guidExists = @IPS_PresentationExists($guid);
                    }
                    if ($guidExists) {
                        if (function_exists('IPS_GetPresentation')) {
                            $pdata = IPS_GetPresentation($guid);
                        }
                    } else {
                    }
                } catch (Exception $e) {
                }
                if (is_string($pdata) && !empty($pdata)) {
                    $arr = @json_decode($pdata, true);
                    if (is_array($arr)) { $pdata = $arr; }
                }
                if (is_array($pdata)) {
                    $vt = $variable['VariableType'] ?? 0;
                    $pp = isset($pdata['presentationParameters']) && is_array($pdata['presentationParameters']) ? $pdata['presentationParameters'] : [];
                    if (!empty($pp)) {
                        if (!empty($pp['ICON'])) {
                            return (string)$pp['ICON'];
                        }
                        if (!empty($pp['Icon'])) {
                            return (string)$pp['Icon'];
                        }
                        if ($vt === 0) {
                            $useFalse = $pp['USE_ICON_FALSE'] ?? true;
                            $iconTrueSet = isset($pp['ICON_TRUE']) && trim((string)$pp['ICON_TRUE']) !== '';
                            $iconFalseSet = isset($pp['ICON_FALSE']) && trim((string)$pp['ICON_FALSE']) !== '';
                            if ($iconTrueSet || $iconFalseSet) {
                                $sameBoth = ($iconTrueSet && $iconFalseSet && (string)$pp['ICON_TRUE'] === (string)$pp['ICON_FALSE']);
                                if ($sameBoth) {
                                    return (string)$pp['ICON_TRUE'];
                                }
                                $isTrue = ($value === true) || ((string)$value === '1') || ($value === 1);
                                $isFalse = ($value === false) || ((string)$value === '0') || ($value === 0) || ((string)$value === '');
                                if ($iconTrueSet && $isTrue) {
                                    return (string)$pp['ICON_TRUE'];
                                }
                                if ($iconFalseSet && $useFalse && $isFalse) {
                                    return (string)$pp['ICON_FALSE'];
                                }
                            }
                        }
                        $optsRaw = $pp['OPTIONS'] ?? null;
                        if ($optsRaw) {
                            $opts = is_string($optsRaw) ? @json_decode($optsRaw, true) : $optsRaw;
                            if (is_array($opts)) {
                                foreach ($opts as $opt) {
                                    if (!is_array($opt)) continue;
                                    $optVal = $opt['Value'] ?? null;
                                    if (array_key_exists('Value', $opt)) {
                                        $match = ((string)$optVal === (string)$value) || ($vt === 0 && ($optVal === false && (($value === false) || (string)$value === '0' || (string)$value === '')));
                                        if ($match) {
                                            $iconVal = $opt['IconValue'] ?? ($opt['Icon'] ?? '');
                                            if (!empty($iconVal)) {
                                                return (string)$iconVal;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $fallback = $this->GetIcon($id, false);
        return $fallback;
    }

    private function GetButtonColors(int $id): array
    {
        $colors = ['on' => '', 'off' => ''];
        if (!function_exists('IPS_VariableExists') || !IPS_VariableExists($id)) {
            return $colors;
        }
        $variable = IPS_GetVariable($id);
        if (!isset($variable['VariableType']) || $variable['VariableType'] !== 0) {
            return $colors;
        }
        $profile = $variable['VariableCustomProfile'] ?: $variable['VariableProfile'];
        if ($profile && IPS_VariableProfileExists($profile)) {
            $p = IPS_GetVariableProfile($profile);
            if (isset($p['Associations']) && is_array($p['Associations'])) {
                foreach ($p['Associations'] as $a) {
                    if (isset($a['Value'], $a['Color']) && $a['Color'] !== -1) {
                        $hex = '#' . sprintf('%06X', (int)$a['Color']);
                        if ($a['Value'] == 1 || $a['Value'] === true) {
                            $colors['on'] = $hex;
                        } elseif ($a['Value'] == 0 || $a['Value'] === false) {
                            $colors['off'] = $hex;
                        }
                    }
                }
            }
        }
        if (isset($variable['VariableCustomPresentation']) && is_array($variable['VariableCustomPresentation'])) {
            $pres = $variable['VariableCustomPresentation'];
            $useFalse = $pres['USE_COLOR_FALSE'] ?? true;
            if (isset($pres['COLOR_TRUE']) && is_int($pres['COLOR_TRUE']) && $pres['COLOR_TRUE'] !== -1) {
                $colors['on'] = '#' . sprintf('%06X', (int)$pres['COLOR_TRUE']);
            }
            if ($useFalse && isset($pres['COLOR_FALSE']) && is_int($pres['COLOR_FALSE']) && $pres['COLOR_FALSE'] !== -1) {
                $colors['off'] = '#' . sprintf('%06X', (int)$pres['COLOR_FALSE']);
            }
        }
        return $colors;
    }

    private function CheckAndGetValueFormattedFromId(int $id)
    {
        if ($id > 0 && IPS_VariableExists($id)) {
            return GetValueFormatted($id);
        }
        return false;
    }
}
?>
