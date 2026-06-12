<?php

declare(strict_types=1);

/**
 * Property-Rename-Migrationen für TileVisu-Kacheln.
 *
 * Wird ausschließlich über die Fassade TileVisuLib aufgerufen.
 */
final class TileVisuMigration
{
    /**
     * Property-Rename-Migration v2: German → English property names.
     * Returns true if migration was performed (caller should return from ApplyChanges).
     * Akzeptiert beide Basisklassen: RoomHeader (IPSModule) und die
     * Module-Strict-Kacheln (IPSModuleStrict).
     */
    public static function migrateV2(IPSModule|IPSModuleStrict $module, int $instanceId): bool
    {
        $map = [
            'Bildtransparenz' => 'ImageTransparency',
            'bgImage' => 'BackgroundImage',
            'bgImage2' => 'BackgroundImage2',
            'Raumname' => 'RoomName',
            'RaumnameSchriftgroesse' => 'RoomNameFontSize',
            'RaumnameSchriftfarbe' => 'RoomNameFontColor',
            'Kachelhintergrundfarbe' => 'TileBackgroundColor',
            'Lichtstatus' => 'LightStatus',
            'Dimmwert' => 'DimValue',
            'InfoSchriftgroesse' => 'InfoFontSize',
            'InfoSchriftfarbe' => 'InfoFontColor',
            'Infohoehe' => 'InfoHeight',
            'InfoLinks' => 'InfoLeft',
            'InfoLinks2' => 'InfoLeft2',
            'InfoRechts' => 'InfoRight',
            'InfoRechts2' => 'InfoRight2',
            'InfoLinksNameSwitch' => 'InfoLeftNameSwitch',
            'InfoLinksIconSwitch' => 'InfoLeftIconSwitch',
            'InfoLinksShowValue' => 'InfoLeftShowValue',
            'InfoLinksVarIconSwitch' => 'InfoLeftVarIconSwitch',
            'InfoLinksAssoSwitch' => 'InfoLeftAssoSwitch',
            'InfoLinksAltName' => 'InfoLeftAltName',
            'InfoLinks2NameSwitch' => 'InfoLeft2NameSwitch',
            'InfoLinks2IconSwitch' => 'InfoLeft2IconSwitch',
            'InfoLinks2ShowValue' => 'InfoLeft2ShowValue',
            'InfoLinks2VarIconSwitch' => 'InfoLeft2VarIconSwitch',
            'InfoLinks2AssoSwitch' => 'InfoLeft2AssoSwitch',
            'InfoLinks2AltName' => 'InfoLeft2AltName',
            'InfoRechtsNameSwitch' => 'InfoRightNameSwitch',
            'InfoRechtsIconSwitch' => 'InfoRightIconSwitch',
            'InfoRechtsShowValue' => 'InfoRightShowValue',
            'InfoRechtsVarIconSwitch' => 'InfoRightVarIconSwitch',
            'InfoRechtsAssoSwitch' => 'InfoRightAssoSwitch',
            'InfoRechtsAltName' => 'InfoRightAltName',
            'InfoRechts2NameSwitch' => 'InfoRight2NameSwitch',
            'InfoRechts2IconSwitch' => 'InfoRight2IconSwitch',
            'InfoRechts2ShowValue' => 'InfoRight2ShowValue',
            'InfoRechts2VarIconSwitch' => 'InfoRight2VarIconSwitch',
            'InfoRechts2AssoSwitch' => 'InfoRight2AssoSwitch',
            'InfoRechts2AltName' => 'InfoRight2AltName',
            'InfoMiddleLeftNameSwitch' => 'InfoMiddleLeftShowName',
            'InfoMiddleLeftIconSwitch' => 'InfoMiddleLeftShowIcon',
            'InfoMiddleRightNameSwitch' => 'InfoMiddleRightShowName',
            'InfoMiddleRightIconSwitch' => 'InfoMiddleRightShowIcon',
            'InfoMenueSwitch' => 'MenuSwitch',
            'InfoMenueSchriftgroesse' => 'MenuFontSize',
            'InfoMenueSchriftfarbe' => 'MenuFontColor',
            'InfoMenueTransparenz' => 'MenuTransparency',
            'InfoMenueHintergrundfarbe' => 'MenuBackgroundColor',
            'SchalterAlignment' => 'SwitchAlignment',
            'SchalterDistribute' => 'SwitchDistribute',
            'Default_Bildtransparenz' => 'Default_ImageTransparency',
            'Default_InfoSchriftgroesse' => 'Default_InfoFontSize',
            'Default_InfoSchriftfarbe' => 'Default_InfoFontColor',
            'Default_Infohoehe' => 'Default_InfoHeight',
            'Default_InfoMenueSchriftgroesse' => 'Default_MenuFontSize',
            'Default_InfoMenueSchriftfarbe' => 'Default_MenuFontColor',
            'Default_InfoMenueTransparenz' => 'Default_MenuTransparency',
            'Default_InfoMenueHintergrundfarbe' => 'Default_MenuBackgroundColor',
            'Default_InfoTopTransparenz' => 'Default_InfoTopTransparency',
            'Default_InfoTopHintergrundfarbe' => 'Default_InfoTopBackgroundColor',
            'Default_Kachelhintergrundfarbe' => 'Default_TileBackgroundColor',
            'Default_RaumnameSchriftgroesse' => 'Default_RoomNameFontSize',
            'Default_RaumnameSchriftfarbe' => 'Default_RoomNameFontColor',
        ];

        // Schalter1..5 + suffixes
        $suffixes = ['', 'NameSwitch', 'IconSwitch', 'ShowValue', 'AltName', 'Breite', 'VolleBreite', 'Schriftgroesse', 'VarIconSwitch', 'AssoSwitch', 'OpenObjectId'];
        $newSuffixes = ['', 'NameSwitch', 'IconSwitch', 'ShowValue', 'AltName', 'Width', 'FullWidth', 'FontSize', 'VarIconSwitch', 'AssoSwitch', 'OpenObjectId'];
        for ($i = 1; $i <= 5; $i++) {
            for ($j = 0; $j < count($suffixes); $j++) {
                $map['Schalter' . $i . $suffixes[$j]] = 'Switch' . $i . $newSuffixes[$j];
            }
        }

        // RoomHeader: Info1..5 suffix renames
        for ($i = 1; $i <= 5; $i++) {
            $map['Info' . $i . 'NameSwitch'] = 'Info' . $i . 'ShowName';
            $map['Info' . $i . 'IconSwitch'] = 'Info' . $i . 'ShowIcon';
            $map['Info' . $i . 'VarIconSwitch'] = 'Info' . $i . 'UseVarIcon';
            $map['Info' . $i . 'AssoSwitch'] = 'Info' . $i . 'ShowAssociation';
            $map['Info' . $i . 'AltName'] = 'Info' . $i . 'AltLabel';
        }

        // Room-level keys (inside Rooms JSON)
        $roomKeyMap = array_merge($map, [
            'InfoTopHintergrundfarbe' => 'InfoTopBackgroundColor',
            'InfoTopLeftHintergrundfarbe' => 'InfoTopLeftBackgroundColor',
            'InfoTopMidHintergrundfarbe' => 'InfoTopMidBackgroundColor',
            'InfoTopRightHintergrundfarbe' => 'InfoTopRightBackgroundColor',
            'InfoTopTransparenz' => 'InfoTopTransparency',
            'InfoTopLeftTransparenz' => 'InfoTopLeftTransparency',
            'InfoTopMidTransparenz' => 'InfoTopMidTransparency',
            'InfoTopRightTransparenz' => 'InfoTopRightTransparency',
            'InfoHoehe' => 'InfoHeight',
            'ShowRaumname' => 'ShowRoomName',
        ]);

        $config = @json_decode(IPS_GetConfiguration($instanceId), true);
        if (!is_array($config)) {
            return false;
        }

        $needsMigration = false;
        $changed = false;

        // 1) Check top-level properties for old names
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $config)) {
                $needsMigration = true;
                break;
            }
        }

        // 2) Also check Rooms JSON keys (Rooms property itself was not renamed,
        //    but the keys inside each room object were)
        $rooms = null;
        if (array_key_exists('Rooms', $config)) {
            $roomsRaw = $config['Rooms'];
            $rooms = is_string($roomsRaw) ? @json_decode($roomsRaw, true) : $roomsRaw;
            if (is_array($rooms) && !$needsMigration) {
                foreach ($rooms as $room) {
                    if (!is_array($room)) continue;
                    foreach ($roomKeyMap as $old => $new) {
                        if ($old !== $new && array_key_exists($old, $room)) {
                            $needsMigration = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if (!$needsMigration) {
            return false;
        }

        // 3) Migrate top-level properties
        foreach ($map as $old => $new) {
            if (array_key_exists($old, $config)) {
                IPS_SetProperty($instanceId, $new, $config[$old]);
                $changed = true;
            }
        }

        // 4) Migrate Rooms JSON keys
        if (is_array($rooms)) {
            $roomsChanged = false;
            foreach ($rooms as &$room) {
                if (!is_array($room)) continue;
                foreach ($roomKeyMap as $old => $new) {
                    if ($old !== $new && array_key_exists($old, $room)) {
                        $room[$new] = $room[$old];
                        unset($room[$old]);
                        $roomsChanged = true;
                    }
                }
            }
            unset($room);
            if ($roomsChanged) {
                IPS_SetProperty($instanceId, 'Rooms', json_encode($rooms));
                $changed = true;
            }
        }

        if ($changed) {
            IPS_LogMessage('TileVisu', 'Migrated instance #' . $instanceId . ' to V2 property names');
            IPS_ApplyChanges($instanceId);
            return true;
        }

        return false;
    }
}
