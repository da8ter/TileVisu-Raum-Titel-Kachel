<?php
 require_once __DIR__ . '/../libs/TileVisuLib.php';
class RoomTile extends IPSModule
{
    public function Create()
    {
        parent::Create();

        // Grid globale Einstellungen
        $this->RegisterPropertyInteger('BorderRadius', 10);
        // optionale globale Defaults
        $this->RegisterPropertyInteger('Default_InfoSchriftgroesse', 14);
        $this->RegisterPropertyInteger('Default_InfoSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyInteger('Default_InfoMenueSchriftgroesse', 14);
        $this->RegisterPropertyInteger('Default_InfoMenueSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyFloat('Default_InfoMenueTransparenz', 30.0);
        $this->RegisterPropertyInteger('Default_InfoMenueHintergrundfarbe', 0x000000);
        $this->RegisterPropertyFloat('Default_InfoTopTransparenz', 30.0);
        $this->RegisterPropertyInteger('Default_InfoTopHintergrundfarbe', 0x000000);
        $this->RegisterPropertyInteger('Default_InfoTopBorderRadius', 50);
        $this->RegisterPropertyInteger('Default_Kachelhintergrundfarbe', 0x000000);
        $this->RegisterPropertyInteger('Default_RaumnameSchriftgroesse', 45);
        $this->RegisterPropertyFloat('Default_Bildtransparenz', 70.0);
        $this->RegisterPropertyInteger('Default_ButtonHeight', 25);
        $this->RegisterPropertyInteger('Default_ButtonBorderRadius', 10);
        $this->RegisterPropertyBoolean('UseImageColorsForButtons', false);
        $this->RegisterPropertyBoolean('InfoTopCentered', false);
        // Dynamic lists (no migration): Info items (top) and menu items
        $this->RegisterPropertyString('InfoItems', '[]');
        $this->RegisterPropertyString('MenuItems', '[]');

        
        $this->RegisterPropertyString('Raumname', 'Raumname');
        $this->RegisterPropertyInteger('Target', 0);
        $this->RegisterPropertyInteger('bgImage', 0);
        // Hintergrund-Filtersteuerung
        $this->RegisterPropertyInteger('Lichtstatus', 0);
        $this->RegisterPropertyInteger('Dimmwert', 0);
        
        $this->RegisterPropertyInteger('InfoLinks', 0);
        $this->RegisterPropertyBoolean('InfoLinksNameSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinksIconSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinksShowValue', true);
        $this->RegisterPropertyString('InfoLinksAltName', '');
        $this->RegisterPropertyInteger('InfoLinks2', 0);
        $this->RegisterPropertyBoolean('InfoLinks2NameSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinks2IconSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinks2ShowValue', true);
        $this->RegisterPropertyString('InfoLinks2AltName', '');
        $this->RegisterPropertyInteger('InfoRechts', 0);
        $this->RegisterPropertyBoolean('InfoRechtsNameSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechtsIconSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechtsShowValue', true);
        $this->RegisterPropertyString('InfoRechtsAltName', '');
        $this->RegisterPropertyInteger('InfoRechts2', 0);
        $this->RegisterPropertyBoolean('InfoRechts2NameSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechts2IconSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechts2ShowValue', true);
        $this->RegisterPropertyString('InfoRechts2AltName', '');
        
        $this->RegisterPropertyBoolean('InfoMenueSwitch', true);
        $this->RegisterPropertyString('SchalterAlignment', 'left');
        $this->RegisterPropertyBoolean('SchalterDistribute', false);
        for ($i = 1; $i <= 5; $i++) {
            $this->RegisterPropertyInteger('Schalter' . $i, 0);
            $this->RegisterPropertyBoolean('Schalter' . $i . 'NameSwitch', false);
            $this->RegisterPropertyBoolean('Schalter' . $i . 'IconSwitch', false);
            $this->RegisterPropertyBoolean('Schalter' . $i . 'ShowValue', false);
            $this->RegisterPropertyString('Schalter' . $i . 'AltName', '');
            $this->RegisterPropertyInteger('Schalter' . $i . 'Breite', 100);
            $this->RegisterPropertyBoolean('Schalter' . $i . 'VolleBreite', false);
        }
        $this->RegisterPropertyFloat('Bildtransparenz', 70.0);
        $this->RegisterPropertyInteger('Kachelhintergrundfarbe', -1);
        $this->RegisterPropertyInteger('RaumnameSchriftfarbe', -1);
        $this->RegisterPropertyInteger('RaumnameSchriftgroesse', 50);
        $this->RegisterPropertyInteger('InfoSchriftgroesse', -1);
        $this->RegisterPropertyInteger('InfoSchriftfarbe', -1);
        $this->RegisterPropertyInteger('InfoMenueSchriftgroesse', -1);
        $this->RegisterPropertyInteger('InfoMenueSchriftfarbe', -1);
        $this->RegisterPropertyFloat('InfoMenueTransparenz', -1);
        $this->RegisterPropertyInteger('InfoMenueHintergrundfarbe', -1);
        $this->RegisterPropertyString('Rooms', '[]');
        $this->RegisterAttributeString('VarMap', '{}');
        $this->RegisterAttributeString('HookToken', '');
        $this->SetVisualizationType(1);
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
        
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
            $altName = (string)($row['AltName'] ?? '');
            // order follows current list order (drag&drop)
            $order = $idx++;
            if ($id === '') {
                // Stable auto id independent of order (based on content)
                $seed = 'I|' . (string)$varId . '|' . $area . '|' . trim($altName);
                $id = 'i' . substr(sha1($seed), 0, 10);
            }
            $key = 'infoitem-' . $id;

            // Defaults
            $nameVal = '';
            $valueFormatted = '';
            $icon = '';
            $vt = null;
            $hasVar = ($varId > 0) && @IPS_VariableExists($varId);
            if ($hasVar) {
                try {
                    $vi = @IPS_GetVariable($varId);
                    if (is_array($vi) && array_key_exists('VariableType', $vi)) {
                        $vt = $vi['VariableType'];
                    }
                } catch (Throwable $e) {}
                try {
                    $valueFormatted = (string)@GetValueFormatted($varId);
                } catch (Throwable $e) {}
                if ($showName) {
                    try { $nameVal = $altName !== '' ? $altName : (string)@IPS_GetName($varId); } catch (Throwable $e) { $nameVal = $altName; }
                } else {
                    $nameVal = $altName; // allow override label even if ShowName=false -> will be hidden in frontend
                }
                if ($showIcon) {
                    try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) { $icon = ''; }
                }
            } else {
                // No variable -> we can still show AltName if provided
                $nameVal = $altName;
            }

            // Per-item keys for frontend applyInfo()
            if ($nameVal !== '') { $outRoom[$key . 'name'] = $nameVal; }
            if ($valueFormatted !== '') { $outRoom[$key] = $valueFormatted; $outRoom[$key . 'asso'] = $valueFormatted; }
            $outRoom[$key . 'showvalue'] = $showValue;
            $outRoom[$key . 'showicon'] = $showIcon;
            if ($icon !== '' && $icon !== 'Transparent') { $outRoom[$key . 'icon'] = $icon; }

            $items[] = [
                'id' => $id,
                'key' => $key,
                'area' => in_array($area, ['left','right','mid'], true) ? $area : 'left',
                'order' => $order,
                'variableId' => $varId,
            ];
        }
        // sort by order ascending (already sequential, but keep explicit)
        usort($items, function ($a, $b) { return ($a['order'] <=> $b['order']); });
        return $items;
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
                if (!is_array($element)) {
                    continue;
                }
                if (($element['type'] ?? '') === 'ExpansionPanel' && ($element['caption'] ?? '') === 'Raumname' && isset($element['items']) && is_array($element['items'])) {
                    foreach ($element['items'] as &$row) {
                        if (!is_array($row) || ($row['type'] ?? '') !== 'RowLayout' || !isset($row['items']) || !is_array($row['items'])) {
                            continue;
                        }
                        foreach ($row['items'] as $ctrl) {
                            if (is_array($ctrl) && ($ctrl['type'] ?? '') === 'SelectObject' && ($ctrl['name'] ?? '') === 'Target') {
                                $row['visible'] = $supportsSelectObject;
                                break;
                            }
                        }
                    }
                    unset($row);
                }
                if (($element['type'] ?? '') === 'ExpansionPanel' && ($element['caption'] ?? '') === 'Menüleiste' && isset($element['items']) && is_array($element['items'])) {
                    foreach ($element['items'] as &$item) {
                        if (!is_array($item)) {
                            continue;
                        }
                        if (($item['type'] ?? '') === 'List' && ($item['name'] ?? '') === 'MenuItems' && isset($item['columns']) && is_array($item['columns'])) {
                            foreach ($item['columns'] as &$column) {
                                if (is_array($column) && ($column['name'] ?? '') === 'OpenObjectId') {
                                    $column['visible'] = $supportsSelectObject;
                                    break;
                                }
                            }
                            unset($column);
                        }
                    }
                    unset($item);
                }
            }
            unset($element);
        }

        return json_encode($form);
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
            $showName = isset($row['ShowName']) ? (bool)$row['ShowName'] : false;
            $showIcon = isset($row['ShowIcon']) ? (bool)$row['ShowIcon'] : false;
            $showValue = isset($row['ShowValue']) ? (bool)$row['ShowValue'] : false;
            $altName = (string)($row['AltName'] ?? '');
            $width = (int)($row['Width'] ?? 100);
            $fullWidth = isset($row['FullWidth']) ? (bool)$row['FullWidth'] : false;
            // order follows current list order (drag&drop)
            $order = $idx++;
            if ($id === '') {
                // Stable auto id independent of order (based on content)
                $seed = 'M|' . (string)$varId . '|' . trim($altName);
                $id = 'm' . substr(sha1($seed), 0, 10);
            }
            $key = 'menuitem-' . $id;

            // Meta/values (optional, for later dynamic rendering)
            $valueFormatted = '';
            $icon = '';
            $hasVar = ($varId > 0) && @IPS_VariableExists($varId);
            $hasObject = ($openObjectId > 0) && @IPS_ObjectExists($openObjectId);
            $typeVal = null;
            $hasValidAction = false;
            $actionType = 'none';
            $options = [];
            $rawValue = null;
            $color = '';
            $colorOn = '';
            $colorOff = '';
            $objectIcon = '';
            $objectName = '';
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
                // Associations for multi options
                try {
                    if ($typeVal === 1) { // INTEGER
                        $opts = TileVisuLib::getIntegerAssociations($varId);
                        if (is_array($opts) && !empty($opts)) { $options = $opts; }
                    } elseif ($typeVal === 3) { // STRING
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
                } else {
                    // fallback profile color
                    $c = $this->GetColor($varId);
                    if ($c !== '') $color = '#' . $c;
                }
            }
            if (!$hasValidAction && $hasObject) {
                $hasValidAction = true;
                $actionType = 'object';
            }
            if ($hasObject) {
                try {
                    $obj = @IPS_GetObject($openObjectId);
                    if (is_array($obj)) {
                        $objectName = (string)$obj['ObjectName'] ?? '';
                        $objectIcon = (string)$obj['ObjectIcon'] ?? '';
                    }
                } catch (Throwable $e) {}
                // Force visibility for object buttons
                $showName = true;
                $showIcon = true;
                $showValue = false;
            }
            if ($altName !== '' || $showName || $hasObject) {
                $derivedName = $altName !== '' ? $altName : ($hasVar ? (string)@IPS_GetName($varId) : ($hasObject ? $objectName : ''));
                if ($derivedName !== '') { $outRoom[$key . 'name'] = $derivedName; }
            }
            if ($valueFormatted !== '' && $showValue) { $outRoom[$key] = $valueFormatted; $outRoom[$key . 'asso'] = $valueFormatted; }
            $outRoom[$key . 'showvalue'] = $showValue;
            $outRoom[$key . 'showicon'] = $showIcon || $hasObject;
            $finalIcon = $icon;
            if (($finalIcon === '' || $finalIcon === 'Transparent') && $hasObject) {
                $finalIcon = $objectIcon;
            }
            if ($finalIcon !== '' && $finalIcon !== 'Transparent') { $outRoom[$key . 'icon'] = $finalIcon; }
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
            ];
        }
        usort($items, function ($a, $b) { return ($a['order'] <=> $b['order']); });
        return $items;
    }
    
    private function NormalizeDynamicLists(): bool
    {
        $changed = false;
        // InfoItems
        $info = @json_decode($this->ReadPropertyString('InfoItems'), true);
        if (is_array($info)) {
            $seen = [];
            foreach ($info as &$row) {
                if (!is_array($row)) { $row = []; $changed = true; }
                $id = isset($row['Id']) ? (string)$row['Id'] : '';
                if ($id === '' || isset($seen[$id])) {
                    try { $id = 'i' . bin2hex(random_bytes(6)); } catch (Throwable $e) { $id = 'i' . substr(sha1(uniqid('', true)), 0, 12); }
                    $row['Id'] = $id;
                    $changed = true;
                }
                $seen[$id] = true;
            }
            unset($row);
            if ($changed) {
                IPS_SetProperty($this->InstanceID, 'InfoItems', json_encode($info));
            }
        }
        // MenuItems
        $menu = @json_decode($this->ReadPropertyString('MenuItems'), true);
        if (is_array($menu)) {
            $seen = [];
            foreach ($menu as &$row) {
                if (!is_array($row)) { $row = []; $changed = true; }
                $id = isset($row['Id']) ? (string)$row['Id'] : '';
                if ($id === '' || isset($seen[$id])) {
                    try { $id = 'm' . bin2hex(random_bytes(6)); } catch (Throwable $e) { $id = 'm' . substr(sha1(uniqid('', true)), 0, 12); }
                    $row['Id'] = $id;
                    $changed = true;
                }
                $seen[$id] = true;
            }
            unset($row);
            if ($changed) {
                IPS_SetProperty($this->InstanceID, 'MenuItems', json_encode($menu));
            }
        }
        if ($changed) {
            // Apply normalized properties and stop current ApplyChanges run
            IPS_ApplyChanges($this->InstanceID);
            return true;
        }
        return false;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        // Ensure hidden IDs are auto-assigned once
        if ($this->NormalizeDynamicLists()) {
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
        $watchProps = ['InfoLinks','InfoLinks2','InfoRechts','InfoRechts2','Schalter1','Schalter2','Schalter3','Schalter4','Schalter5','Lichtstatus','Dimmwert'];

        // Register dynamic menu items for variable updates
        $menuList = @json_decode($this->ReadPropertyString('MenuItems'), true);
        if (is_array($menuList)) {
            foreach ($menuList as $row) {
                if (!is_array($row)) continue;
                $varId = (int)($row['VariableId'] ?? 0);
                $itemId = (string)($row['Id'] ?? '');
                if ($varId > 0 && $itemId !== '' && @IPS_VariableExists($varId)) {
                    $this->RegisterReference($varId);
                    $this->RegisterMessage($varId, VM_UPDATE);
                    if (!isset($varMap[$varId]) || !is_array($varMap[$varId])) {
                        $varMap[$varId] = [];
                    }
                    $varMap[$varId][] = ['idx' => 0, 'prop' => 'menuitem:' . $itemId];
                }
            }
        }

        // Register dynamic info items for variable updates
        $infoList = @json_decode($this->ReadPropertyString('InfoItems'), true);
        if (is_array($infoList)) {
            foreach ($infoList as $row) {
                if (!is_array($row)) continue;
                $varId = (int)($row['VariableId'] ?? 0);
                $itemId = (string)($row['Id'] ?? '');
                if ($varId > 0 && $itemId !== '' && @IPS_VariableExists($varId)) {
                    $this->RegisterReference($varId);
                    $this->RegisterMessage($varId, VM_UPDATE);
                    if (!isset($varMap[$varId]) || !is_array($varMap[$varId])) {
                        $varMap[$varId] = [];
                    }
                    $varMap[$varId][] = ['idx' => 0, 'prop' => 'infoitem:' . $itemId];
                }
            }
        }

        foreach ($rooms as $idx => $room) {
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

            // Spezialfall: Lichtstatus/Dimmwert → bgfilter (0..100)
            if ($prop === 'Lichtstatus' || $prop === 'Dimmwert') {
                $boolId = (int)($room['Lichtstatus'] ?? 0);
                $dimId = (int)($room['Dimmwert'] ?? 0);
                $hasBool = $boolId > 0 && @IPS_VariableExists($boolId);
                $hasDim = $dimId > 0 && @IPS_VariableExists($dimId);
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
                    $pOut = 0.0;
                }
                if ($pOut < 0.0) {
                    $pOut = 0.0;
                }
                $delta[] = [ 'idx' => $idx, 'key' => 'bgfilter', 'value' => $pOut ];
                continue;
            }

            // Dynamic menu/info items special handling
            if (strpos($prop, 'menuitem:') === 0) {
                $itemId = substr($prop, strlen('menuitem:'));
                $varId = $SenderID; // mapped variable triggered this update
                if ($varId > 0 && @IPS_VariableExists($varId)) {
                    $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'value', 'value' => @GetValue($varId)];
                    $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'asso', 'value' => (string)@GetValueFormatted($varId)];
                    try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) { $icon = ''; }
                    if ($icon !== '' && $icon !== 'Transparent') {
                        $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'icon', 'value' => $icon];
                    }
                    try { $nm = (string)@IPS_GetName($varId); } catch (Throwable $e) { $nm = ''; }
                    if ($nm !== '') {
                        $delta[] = ['idx' => $idx, 'key' => 'menuitem-' . $itemId . 'name', 'value' => $nm];
                    }
                }
                continue;
            }
            if (strpos($prop, 'infoitem:') === 0) {
                $itemId = substr($prop, strlen('infoitem:'));
                $varId = $SenderID; // mapped variable triggered this update
                if ($varId > 0 && @IPS_VariableExists($varId)) {
                    $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'asso', 'value' => (string)@GetValueFormatted($varId)];
                    try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) { $icon = ''; }
                    if ($icon !== '' && $icon !== 'Transparent') {
                        $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'icon', 'value' => $icon];
                    }
                    try { $nm = (string)@IPS_GetName($varId); } catch (Throwable $e) { $nm = ''; }
                    if ($nm !== '') {
                        $delta[] = ['idx' => $idx, 'key' => 'infoitem-' . $itemId . 'name', 'value' => $nm];
                    }
                }
                continue;
            }

            // Wert formatiert
            $delta[] = [
                'idx' => $idx,
                'key' => strtolower($prop),
                'value' => $this->CheckAndGetValueFormattedFromId($this->ReadInt($room, $prop))
            ];

            // Farbe
            $col = $this->GetColor($this->ReadInt($room, $prop));
            $delta[] = [
                'idx' => $idx,
                'key' => strtolower($prop) . 'color',
                'value' => ($col !== '') ? ('#' . $col) : ''
            ];

            // Name/Icon/Asso/AltName wenn nicht bgImage
            if ($prop !== 'bgImage') {
                if ($this->ReadBool($room, $prop . 'NameSwitch')) {
                    $delta[] = [
                        'idx' => $idx,
                        'key' => strtolower($prop) . 'name',
                        'value' => IPS_GetName($this->ReadInt($room, $prop))
                    ];
                }
                $icon = $this->GetIconAdvanced($this->ReadInt($room, $prop));
                $delta[] = [
                    'idx' => $idx,
                    'key' => strtolower($prop) . 'icon',
                    'value' => $icon
                ];
                $delta[] = [
                    'idx' => $idx,
                    'key' => strtolower($prop) . 'asso',
                    'value' => $this->CheckAndGetValueFormattedFromId($this->ReadInt($room, $prop))
                ];
                if (isset($room[$prop . 'AltName'])) {
                    $delta[] = [
                        'idx' => $idx,
                        'key' => strtolower($prop) . 'altname',
                        'value' => (string)$room[$prop . 'AltName']
                    ];
                }
            }

            // SchalterN Rohwert als Delta für Active-State
            if (strpos($prop, 'Schalter') === 0) {
                $n = (int)substr($prop, 8);
                $delta[] = [
                    'idx' => $idx,
                    'key' => 'schalter' . $n . 'value',
                    'value' => @GetValue($this->ReadInt($room, $prop))
                ];
            }
            // InfoN Rohwert als Delta für Active-State (für interaktive Info-Buttons)
            if (strpos($prop, 'Info') === 0) {
                $n = (int)substr($prop, 4);
                $delta[] = [
                    'idx' => $idx,
                    'key' => 'info' . $n . 'value',
                    'value' => @GetValue($this->ReadInt($room, $prop))
                ];
            }
            // Dynamic menu item update
            if (strpos($prop, 'menuitem:') === 0) {
                $itemId = substr($prop, strlen('menuitem:'));
                $this->sendMenuItemDelta($itemId, $this->ReadInt($room, $prop));
                return;
            }
        }
        if (!empty($delta)) {
            $this->UpdateVisualizationValue(json_encode(['delta' => $delta]));
        }
    }

    public function RequestAction($Ident, $Value)
    {
        // Dynamic menu item action: menuitem:<Id>
        if (strpos($Ident, 'menuitem:') === 0) {
            $itemId = substr($Ident, strlen('menuitem:'));
            $menuList = @json_decode($this->ReadPropertyString('MenuItems'), true);
            if (!is_array($menuList) || empty($menuList)) {
                return;
            }
            foreach ($menuList as $row) {
                if (!is_array($row)) continue;
                $rid = (string)($row['Id'] ?? '');
                if ($rid === '' || $rid !== $itemId) continue;
                $varId = (int)($row['VariableId'] ?? 0);
                if ($varId <= 0 || !@IPS_VariableExists($varId)) return;
                $variable = @IPS_GetVariable($varId);
                if (!is_array($variable)) return;
                $vType = $variable['VariableType'] ?? 0;
                // Resolve action
                $actionId = 0;
                if (isset($variable['VariableCustomAction']) && $variable['VariableCustomAction'] > 0) {
                    $actionId = $variable['VariableCustomAction'];
                } elseif (isset($variable['VariableAction']) && $variable['VariableAction'] > 0) {
                    $actionId = $variable['VariableAction'];
                }
                $hasValidAction = ($actionId > 0) && (@IPS_InstanceExists($actionId) || @IPS_ScriptExists($actionId));
                if (!$hasValidAction) return;

                // Determine target value
                if ($vType === 0) { // BOOLEAN -> toggle if no explicit value
                    $newValue = ($Value === null) ? !@GetValue($varId) : (bool)$Value;
                } elseif ($vType === 1) { // INTEGER
                    $newValue = (int)$Value;
                } elseif ($vType === 2) { // FLOAT
                    $newValue = (float)$Value;
                } else { // STRING
                    $newValue = (string)$Value;
                }
                @RequestAction($varId, $newValue);
                // Send delta update for the changed value
                $this->sendMenuItemDelta($itemId, $varId);
                return;
            }
            return;
        }
        // Reorder Buttons aus der Form
        if ($Ident === 'reorder' && is_string($Value)) {
            $payload = json_decode($Value, true);
            if (!is_array($payload) || !isset($payload['direction'], $payload['index'])) {
                return;
            }
            $this->ReorderRoom((int)$payload['index'], (string)$payload['direction']);
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
            'borderRadius' => $this->ReadPropertyInteger('BorderRadius'),
            'buttonHeight' => $this->ReadPropertyInteger('Default_ButtonHeight'),
            'useImageColorsForButtons' => $this->ReadPropertyBoolean('UseImageColorsForButtons')
        ];

        $rooms = $this->getRooms();
        // Lese optionale Defaults aus den separaten Properties und mappe sie auf die Raum-Keys
        $defaults = [
            'InfoSchriftgroesse'       => (int)$this->ReadPropertyInteger('Default_InfoSchriftgroesse'),
            'InfoSchriftfarbe'         => (int)$this->ReadPropertyInteger('Default_InfoSchriftfarbe'),
            'InfoMenueSchriftgroesse'  => (int)$this->ReadPropertyInteger('Default_InfoMenueSchriftgroesse'),
            'InfoMenueSchriftfarbe'    => (int)$this->ReadPropertyInteger('Default_InfoMenueSchriftfarbe'),
            'InfoMenueTransparenz'     => (float)$this->ReadPropertyFloat('Default_InfoMenueTransparenz'),
            'InfoMenueHintergrundfarbe'=> (int)$this->ReadPropertyInteger('Default_InfoMenueHintergrundfarbe'),
            'InfoTopTransparenz'       => (float)$this->ReadPropertyFloat('Default_InfoTopTransparenz'),
            'InfoTopHintergrundfarbe'  => (int)$this->ReadPropertyInteger('Default_InfoTopHintergrundfarbe'),
            'InfoTopBorderRadius'      => (int)$this->ReadPropertyInteger('Default_InfoTopBorderRadius'),
            'Kachelhintergrundfarbe'   => (int)$this->ReadPropertyInteger('Default_Kachelhintergrundfarbe'),
            'RaumnameSchriftgroesse'   => (int)$this->ReadPropertyInteger('Default_RaumnameSchriftgroesse'),
            'Bildtransparenz'          => (float)$this->ReadPropertyFloat('Default_Bildtransparenz')
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
        if (isset($defaults['InfoTopHintergrundfarbe']) && (int)$defaults['InfoTopHintergrundfarbe'] === -1) {
            $defaults['InfoTopHintergrundfarbe'] = 0x000000; // Schwarz
        }
        if (isset($defaults['Kachelhintergrundfarbe']) && (int)$defaults['Kachelhintergrundfarbe'] === -1) {
            $defaults['Kachelhintergrundfarbe'] = 0x000000; // Schwarz
        }
        $resultRooms = [];

        foreach ($rooms as $idx => $room) {
            $r = [];
            $r['idx'] = $idx;

            // Styles (kombiniere Defaults + Raum-spezifisch)
            $inf = null;
            if (array_key_exists('InfoSchriftgroesse', $room)) { $inf = (int)$room['InfoSchriftgroesse']; }
            $r['infofontsize'] = ($inf === null || $inf <= 0) ? (int)($defaults['InfoSchriftgroesse'] ?? 16) : $inf;

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

            // InfoTop Hintergrundfarbe (badges) aus Defaults (Prozent -> Alpha)
            $topCol = (int)($defaults['InfoTopHintergrundfarbe'] ?? 0x000000);
            $topAlphaPercent = $this->normalizePercent((float)($defaults['InfoTopTransparenz'] ?? 30.0));
            $r['infotophintergrundfarbe'] = $this->cssRgba($topCol, $this->percentToAlpha($topAlphaPercent));
            $r['infotopborderradius'] = (int)($defaults['InfoTopBorderRadius'] ?? 50);
            $r['buttonborderradius'] = (int)$this->ReadPropertyInteger('Default_ButtonBorderRadius');

            $imcol = null;
            if (array_key_exists('InfoMenueSchriftfarbe', $room)) { $imcol = (int)$room['InfoMenueSchriftfarbe']; }
            if ($imcol === null || $imcol === -1) { $imcol = (int)($defaults['InfoMenueSchriftfarbe'] ?? 0xFFFFFF); }
            $r['infomenueschriftfarbe'] = $this->toCssHex($imcol);

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
            $r['schalteralignment'] = (string)($room['SchalterAlignment'] ?? 'left');
            $r['schalterdistribute'] = (bool)($room['SchalterDistribute'] ?? false);
            $r['transparenz'] = $this->percentToAlpha($this->normalizePercent($this->ReadNumOrDefault($room, 'Bildtransparenz', $defaults, 70.0)));
            $r['infotopcentered'] = (bool)$this->ReadPropertyBoolean('InfoTopCentered');

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
            if ($rncol === null || $rncol === -1) { $rncol = 0xFFFFFF; }
            $r['raumnameschriftfarbe'] = $this->toCssHex($rncol);

            $r['infomenueswitch'] = (bool)($room['InfoMenueSwitch'] ?? true);

            // Use image colors for buttons per room (fallback to global flag)
            $useImgCol = array_key_exists('UseImageColorsForButtons', $room)
                ? (bool)$room['UseImageColorsForButtons']
                : (bool)$this->ReadPropertyBoolean('UseImageColorsForButtons');
            $r['useimagecolors'] = $useImgCol;

            // Bild: per WebHook ausliefern (Base64 via JSON)
            $imageID = (int)($room['bgImage'] ?? 0);
            $r['image1'] = $this->BuildImageHookUrl($imageID);

            // Hintergrundfilter aus Lichtstatus/Dimmwert
            try {
                $boolId = (int)($room['Lichtstatus'] ?? 0);
                $dimId = (int)($room['Dimmwert'] ?? 0);
                $hasBool = $boolId > 0 && @IPS_VariableExists($boolId);
                $hasDim = $dimId > 0 && @IPS_VariableExists($dimId);
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
                    $pOut = 0.0;
                }
                if ($pOut < 0.0) {
                    $pOut = 0.0;
                }
                $r['bgfilter'] = $pOut;
            } catch (Throwable $e) {}

            // Dynamic lists
            $infoList = @json_decode($this->ReadPropertyString('InfoItems'), true);
            $menuList = @json_decode($this->ReadPropertyString('MenuItems'), true);
            $useDynamicInfo = is_array($infoList) && count($infoList) > 0;
            $useDynamicMenu = is_array($menuList) && count($menuList) > 0;

            if ($useDynamicInfo) {
                $r['infoitems'] = $this->buildDynamicInfo($infoList, $r);
            }
            if ($useDynamicMenu) {
                $r['menuitems'] = $this->buildDynamicMenu($menuList, $r);
            }

            // Fallback to fixed properties if dynamic lists are empty
            if (!$useDynamicInfo || !$useDynamicMenu) {
                $this->fillInfoAndButtons($r, $room);
            }

            // Schalter-Breiten, Volle Breite und Farben (fixed only)
            for ($i = 1; $i <= 5; $i++) {
                // Always send breite and vollebreite, even if no variable assigned
                $r['schalter' . $i . 'breite'] = (float)($room['Schalter' . $i . 'Breite'] ?? 100);
                $r['schalter' . $i . 'vollebreite'] = (bool)($room['Schalter' . $i . 'VolleBreite'] ?? false);
                
                $sid = (int)($room['Schalter' . $i] ?? 0);
                if (!$useImgCol && $sid > 0 && IPS_VariableExists($sid)) {
                    $col = $this->GetColor($sid);
                    if ($col !== '') {
                        $r['schalter' . $i . 'color'] = '#' . $col;
                    }
                }
            }

            // Alt-Namen (fixed only)
            for ($i = 1; $i <= 5; $i++) {
                $r['schalter' . $i . 'altname'] = (string)($room['Schalter' . $i . 'AltName'] ?? '');
                $r['info' . $i . 'altname'] = (string)($room['Info' . $i . 'AltName'] ?? '');
            }
            $r['infolinksaltname'] = (string)($room['InfoLinksAltName'] ?? '');
            $r['inforechtsaltname'] = (string)($room['InfoRechtsAltName'] ?? '');
            $r['infolinks2altname'] = (string)($room['InfoLinks2AltName'] ?? '');
            $r['inforechts2altname'] = (string)($room['InfoRechts2AltName'] ?? '');

            $resultRooms[] = $r;
        }

        $result['rooms'] = $resultRooms;
        return $result;
    }

    private function fillInfoAndButtons(array &$out, array $room)
    {
        // InfoSeiten links/rechts + Menü-Infos
        $pairs = [
            'InfoLinks' => 'infolinks',
            'InfoLinks2' => 'infolinks2',
            'InfoRechts' => 'inforechts',
            'InfoRechts2' => 'inforechts2'
        ];
        foreach ($pairs as $prop => $key) {
            $id = (int)($room[$prop] ?? 0);
            if ($id > 0 && IPS_VariableExists($id)) {
                // fallback color from profile/presentation
                $col = $this->GetColor($id);
                if ($col !== '') {
                    $out[$key . 'color'] = '#' . $col;
                }
                $val = GetValueFormatted($id);
                $out[$key] = $val;
                $out[$key . 'asso'] = $val;
                $typeLocal =  null;
                try { $viTmp = isset($varInfo) ? $varInfo : IPS_GetVariable($id); $typeLocal = $viTmp['VariableType'] ?? null; } catch (Throwable $e) {}
                $defaultShowName = ($typeLocal === 0);
                if ($this->ReadBoolOrDefault($room, $prop . 'NameSwitch', $defaultShowName)) {
                    $out[$key . 'name'] = IPS_GetName($id);
                }
                $icon = $this->GetIconAdvanced($id);
                if ($icon !== 'Transparent' && $icon !== '') {
                    $out[$key . 'icon'] = $icon;
                }
                // ShowValue flag (default true for Infos)
                $out[$key . 'showvalue'] = $this->ReadBoolOrDefault($room, $prop . 'ShowValue', true);
                // ShowIcon flag (default false for Info pairs)
                $out[$key . 'showicon'] = $this->ReadBoolOrDefault($room, $prop . 'IconSwitch', false);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            $prop = 'Info' . $i;
            $id = (int)($room[$prop] ?? 0);
            if ($id > 0 && IPS_VariableExists($id)) {
                $key = 'info' . $i;
                // fallback color from profile/presentation
                try {
                    $col = $this->GetColor($id);
                    if ($col !== '') {
                        $out[$key . 'color'] = '#' . $col;
                    }
                } catch (Throwable $e) {}
                // Meta: konfiguriert, Typ, Action vorhanden
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
                } catch (Throwable $e) {
                    // ignore
                }
                // aktueller Rohwert (für Active-State)
                try {
                    $out[$key . 'value'] = GetValue($id);
                } catch (Throwable $e) {
                    // ignore
                }
                // Assoziationen für Integer/String bereitstellen
                try {
                    $vi = isset($varInfo) ? $varInfo : IPS_GetVariable($id);
                    if (isset($vi['VariableType'])) {
                        if ($vi['VariableType'] === 1) { // INTEGER
                            $opts = TileVisuLib::getIntegerAssociations($id);
                            if (!empty($opts)) { $out[$key . 'options'] = $opts; }
                        } elseif ($vi['VariableType'] === 3) { // STRING
                            $opts = TileVisuLib::getStringAssociations($id);
                            if (!empty($opts)) { $out[$key . 'options'] = $opts; }
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
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

        for ($i = 1; $i <= 5; $i++) {
            $prop = 'Schalter' . $i;
            $id = (int)($room[$prop] ?? 0);
            if ($id > 0 && IPS_VariableExists($id)) {
                $key = 'schalter' . $i;
                // aktueller Rohwert (für Active-State im Frontend)
                try {
                    $out[$key . 'value'] = GetValue($id);
                } catch (Throwable $e) {
                    // ignore
                }
                // Markiere als konfiguriert und liefere Variablentyp
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
                    if (isset($varInfo['VariableType']) && $varInfo['VariableType'] === 0) {
                        $btnColors = $this->GetButtonColors($id);
                        if ($btnColors['on'] !== '') {
                            $out[$key . 'colorOn'] = $btnColors['on'];
                        }
                        if ($btnColors['off'] !== '') {
                            $out[$key . 'colorOff'] = $btnColors['off'];
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
                // IconSwitch-Flag aus den Raumeinstellungen an Frontend durchreichen
                try {
                    $out[$key . 'iconswitch'] = $this->ReadBoolOrDefault($room, $prop . 'IconSwitch', false);
                } catch (Throwable $e) {
                    // ignore
                }
                // ShowValue flag (default false for Schalter)
                try {
                    $out[$key . 'showvalue'] = $this->ReadBoolOrDefault($room, $prop . 'ShowValue', false);
                } catch (Throwable $e) {
                    // ignore
                }
                // ShowName flag for labels in multi-button groups
                try {
                    $out[$key . 'showname'] = $this->ReadBoolOrDefault($room, $prop . 'NameSwitch', false);
                } catch (Throwable $e) {
                    // ignore
                }
                // Assoziationen für Integer-/String-Variablen bereitstellen
                try {
                    $varInfo = isset($varInfo) ? $varInfo : IPS_GetVariable($id);
                    if (isset($varInfo['VariableType'])) {
                        if ($varInfo['VariableType'] === 1) { // INTEGER
                            $opts = TileVisuLib::getIntegerAssociations($id);
                            if (!empty($opts)) { $out[$key . 'options'] = $opts; }
                        } elseif ($varInfo['VariableType'] === 3) { // STRING
                            $opts = TileVisuLib::getStringAssociations($id);
                            if (!empty($opts)) { $out[$key . 'options'] = $opts; }
                        }
                    }
                } catch (Throwable $e) {
                    // ignore
                }
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
                // ShowIcon flag (default true for Info groups)
                $out[$key . 'showicon'] = $this->ReadBoolOrDefault($room, $prop . 'IconSwitch', true);
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
        
        $room = [];
        
        $room['Raumname'] = (string)$this->ReadPropertyString('Raumname');
        $room['Target'] = (int)$this->ReadPropertyInteger('Target');
        $room['bgImage'] = (int)$this->ReadPropertyInteger('bgImage');
        // Hintergrund-Filtersteuerung
        $room['Lichtstatus'] = (int)$this->ReadPropertyInteger('Lichtstatus');
        $room['Dimmwert'] = (int)$this->ReadPropertyInteger('Dimmwert');
        
        $room['Bildtransparenz'] = (float)$this->ReadPropertyFloat('Bildtransparenz');
        $room['Kachelhintergrundfarbe'] = (int)$this->ReadPropertyInteger('Kachelhintergrundfarbe');
        $room['RaumnameSchriftfarbe'] = (int)$this->ReadPropertyInteger('RaumnameSchriftfarbe');
        $room['RaumnameSchriftgroesse'] = (int)$this->ReadPropertyInteger('RaumnameSchriftgroesse');
        $room['InfoSchriftgroesse'] = (int)$this->ReadPropertyInteger('InfoSchriftgroesse');
        $room['InfoSchriftfarbe'] = (int)$this->ReadPropertyInteger('InfoSchriftfarbe');
        $room['InfoMenueSchriftgroesse'] = (int)$this->ReadPropertyInteger('InfoMenueSchriftgroesse');
        $room['InfoMenueSchriftfarbe'] = (int)$this->ReadPropertyInteger('InfoMenueSchriftfarbe');
        $room['InfoMenueTransparenz'] = (float)$this->ReadPropertyFloat('InfoMenueTransparenz');
        $room['InfoMenueHintergrundfarbe'] = (int)$this->ReadPropertyInteger('InfoMenueHintergrundfarbe');
        $room['UseImageColorsForButtons'] = (bool)$this->ReadPropertyBoolean('UseImageColorsForButtons');
        
        $room['InfoLinks'] = (int)$this->ReadPropertyInteger('InfoLinks');
        $room['InfoLinksNameSwitch'] = (bool)$this->ReadPropertyBoolean('InfoLinksNameSwitch');
        $room['InfoLinksIconSwitch'] = (bool)$this->ReadPropertyBoolean('InfoLinksIconSwitch');
        $room['InfoLinksShowValue'] = (bool)$this->ReadPropertyBoolean('InfoLinksShowValue');
        $room['InfoLinksAltName'] = (string)$this->ReadPropertyString('InfoLinksAltName');
        $room['InfoLinks2'] = (int)$this->ReadPropertyInteger('InfoLinks2');
        $room['InfoLinks2NameSwitch'] = (bool)$this->ReadPropertyBoolean('InfoLinks2NameSwitch');
        $room['InfoLinks2IconSwitch'] = (bool)$this->ReadPropertyBoolean('InfoLinks2IconSwitch');
        $room['InfoLinks2ShowValue'] = (bool)$this->ReadPropertyBoolean('InfoLinks2ShowValue');
        $room['InfoLinks2AltName'] = (string)$this->ReadPropertyString('InfoLinks2AltName');
        $room['InfoRechts'] = (int)$this->ReadPropertyInteger('InfoRechts');
        $room['InfoRechtsNameSwitch'] = (bool)$this->ReadPropertyBoolean('InfoRechtsNameSwitch');
        $room['InfoRechtsIconSwitch'] = (bool)$this->ReadPropertyBoolean('InfoRechtsIconSwitch');
        $room['InfoRechtsShowValue'] = (bool)$this->ReadPropertyBoolean('InfoRechtsShowValue');
        $room['InfoRechtsAltName'] = (string)$this->ReadPropertyString('InfoRechtsAltName');
        $room['InfoRechts2'] = (int)$this->ReadPropertyInteger('InfoRechts2');
        $room['InfoRechts2NameSwitch'] = (bool)$this->ReadPropertyBoolean('InfoRechts2NameSwitch');
        $room['InfoRechts2IconSwitch'] = (bool)$this->ReadPropertyBoolean('InfoRechts2IconSwitch');
        $room['InfoRechts2ShowValue'] = (bool)$this->ReadPropertyBoolean('InfoRechts2ShowValue');
        $room['InfoRechts2AltName'] = (string)$this->ReadPropertyString('InfoRechts2AltName');
        
        $room['InfoMenueSwitch'] = (bool)$this->ReadPropertyBoolean('InfoMenueSwitch');
        $room['SchalterAlignment'] = (string)$this->ReadPropertyString('SchalterAlignment');
        $room['SchalterDistribute'] = (bool)$this->ReadPropertyBoolean('SchalterDistribute');
        for ($i = 1; $i <= 5; $i++) {
            $room['Schalter' . $i] = (int)$this->ReadPropertyInteger('Schalter' . $i);
            $room['Schalter' . $i . 'NameSwitch'] = (bool)$this->ReadPropertyBoolean('Schalter' . $i . 'NameSwitch');
            $room['Schalter' . $i . 'IconSwitch'] = (bool)$this->ReadPropertyBoolean('Schalter' . $i . 'IconSwitch');
            $room['Schalter' . $i . 'ShowValue'] = (bool)$this->ReadPropertyBoolean('Schalter' . $i . 'ShowValue');
            $room['Schalter' . $i . 'AltName'] = (string)$this->ReadPropertyString('Schalter' . $i . 'AltName');
            $room['Schalter' . $i . 'Breite'] = (int)$this->ReadPropertyInteger('Schalter' . $i . 'Breite');
            $room['Schalter' . $i . 'VolleBreite'] = (bool)$this->ReadPropertyBoolean('Schalter' . $i . 'VolleBreite');
        }
        return [$room];
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
            return (float)$room[$key];
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
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
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

    private function sendMenuItemDelta(string $itemId, int $varId)
    {
        if ($varId <= 0 || !@IPS_VariableExists($varId)) return;
        $key = 'menuitem-' . $itemId;
        try {
            $val = @GetValue($varId);
            $delta = [];
            $delta[] = ['idx' => 0, 'key' => $key . 'value', 'value' => $val];
            $delta[] = ['idx' => 0, 'key' => $key . 'asso', 'value' => (string)@GetValueFormatted($varId)];
            // also send icon and name like in MessageSink
            try { $icon = (string)$this->GetIconAdvanced($varId); } catch (Throwable $e) { $icon = ''; }
            if ($icon !== '' && $icon !== 'Transparent') {
                $delta[] = ['idx' => 0, 'key' => $key . 'icon', 'value' => $icon];
            }
            try { $nm = (string)@IPS_GetName($varId); } catch (Throwable $e) { $nm = ''; }
            if ($nm !== '') {
                $delta[] = ['idx' => 0, 'key' => $key . 'name', 'value' => $nm];
            }
            $this->UpdateVisualizationValue(json_encode(['delta' => $delta]));
        } catch (Throwable $e) {
            // ignore
        }
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
