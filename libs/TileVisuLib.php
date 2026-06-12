<?php

class TileVisuLib
{
    // Returns hex color without leading '#', or empty string if none
    public static function getProfileColorHex(int $id): string
    {
        if (!function_exists('IPS_VariableExists') || !IPS_VariableExists($id)) {
            return '';
        }
        $variable = IPS_GetVariable($id);
        $presColor = self::getPresentationColorHex($id);
        if ($presColor !== '') {
            return $presColor;
        }

        $value = GetValue($id);
        $profile = $variable['VariableCustomProfile'] ?: $variable['VariableProfile'];
        if ($profile && IPS_VariableProfileExists($profile)) {
            $p = IPS_GetVariableProfile($profile);
            if (isset($p['Associations']) && is_array($p['Associations'])) {
                foreach ($p['Associations'] as $association) {
                    if (isset($association['Value'], $association['Color']) && $association['Value'] == $value) {
                        return ($association['Color'] === -1) ? '' : sprintf('%06X', (int)$association['Color']);
                    }
                }
            }
        }
        return '';
    }

    // Convert RGB int + alpha to css rgba()
    public static function rgbaFromHexAlpha(int $hexcolor, float $alpha): string
    {
        if ($hexcolor === -1) {
            return '';
        }
        $hexColor = sprintf('%06X', $hexcolor);
        if (strlen($hexColor) === 6) {
            $r = hexdec(substr($hexColor, 0, 2));
            $g = hexdec(substr($hexColor, 2, 2));
            $b = hexdec(substr($hexColor, 4, 2));
            return "rgba($r, $g, $b, $alpha)";
        }
        return $hexColor;
    }

    // Resolve icon from VariableCustomPresentation or Profile or VarIcon
    public static function getIcon(int $id, bool $varicon): string
    {
        if (!function_exists('IPS_VariableExists') || !IPS_VariableExists($id)) {
            return 'Transparent';
        }
        $variable = IPS_GetVariable($id);
        $value = GetValue($id);
        $icon = '';

        if ($varicon) {
            $obj = IPS_GetObject($id);
            if (!empty($obj['ObjectIcon'])) {
                return $obj['ObjectIcon'];
            }
            return 'Transparent';
        }

        // Try VariableCustomPresentation (if available in this IPS version)
        if (isset($variable['VariableCustomPresentation']) && is_array($variable['VariableCustomPresentation'])) {
            $pres = $variable['VariableCustomPresentation'];
            if (!empty($pres['ICON'])) {
                $icon = $pres['ICON'];
            } elseif (!empty($pres['Icon'])) {
                $icon = $pres['Icon'];
            } elseif ($variable['VariableType'] === 0 /* boolean */) {
                $useIconFalse = $pres['USE_ICON_FALSE'] ?? true;
                if ($value && !empty($pres['ICON_TRUE'])) {
                    $icon = $pres['ICON_TRUE'];
                } elseif (!$value && $useIconFalse && !empty($pres['ICON_FALSE'])) {
                    $icon = $pres['ICON_FALSE'];
                }
            }
            if ($icon !== '') {
                return $icon;
            }
        }

        // Fallback: use profile associations
        $profile = $variable['VariableCustomProfile'] ?: $variable['VariableProfile'];
        if ($profile && IPS_VariableProfileExists($profile)) {
            $p = IPS_GetVariableProfile($profile);
            if (isset($p['Associations']) && is_array($p['Associations'])) {
                foreach ($p['Associations'] as $association) {
                    if (isset($association['Value']) && $association['Value'] == $value && !empty($association['Icon'])) {
                        return $association['Icon'];
                    }
                }
            }
            if (!empty($p['Icon'])) {
                return $p['Icon'];
            }
        }
        return 'Transparent';
    }

    public static function getIconAdvanced(int $id): string
    {
        if ($id <= 0 || !@IPS_VariableExists($id)) {
            return 'Transparent';
        }
        $variable = @IPS_GetVariable($id);
        $value = null;
        try { $value = @GetValue($id); } catch (\Throwable $e) {}
        $vt = $variable['VariableType'] ?? 0;
        $icon = '';

        // 1) IPS_GetVariablePresentation (löst Vorlagen, GUIDs, Vererbung automatisch auf)
        $legacyGuid = '4153A8D4-5C33-C65F-C1F3-7B61AAF99B1C';
        $isLegacy = false;
        $pres = [];
        if (function_exists('IPS_GetVariablePresentation')) {
            try {
                $resolved = @IPS_GetVariablePresentation($id);
                if (is_array($resolved) && !empty($resolved)) {
                    $pres = $resolved;
                }
            } catch (\Throwable $e) {}
        }
        if (isset($pres['PRESENTATION'])) {
            $isLegacy = (strcasecmp(trim((string)$pres['PRESENTATION'], '{} '), $legacyGuid) === 0);
        }

        if (!empty($pres) && !$isLegacy) {
            // Direktes Icon
            foreach (['ICON', 'Icon', 'icon'] as $field) {
                if (isset($pres[$field]) && (string)$pres[$field] !== '') {
                    $icon = (string)$pres[$field];
                    break;
                }
            }

            // Boolean: ICON_TRUE/ICON_FALSE
            if ($icon === '' && $vt === 0) {
                $iconTrueSet = isset($pres['ICON_TRUE']) && trim((string)$pres['ICON_TRUE']) !== '';
                $iconFalseSet = isset($pres['ICON_FALSE']) && trim((string)$pres['ICON_FALSE']) !== '';
                if ($iconTrueSet || $iconFalseSet) {
                    $useFalse = $pres['USE_ICON_FALSE'] ?? true;
                    $isTrue = ($value === true) || ((string)$value === '1') || ($value === 1);
                    if ($iconTrueSet && $isTrue) {
                        $icon = (string)$pres['ICON_TRUE'];
                    } elseif ($iconFalseSet && $useFalse && !$isTrue) {
                        $icon = (string)$pres['ICON_FALSE'];
                    }
                }
            }

            // INTERVALS für numerische Variablen (Bereichs-Icons)
            if ($icon === '' && ($vt === 1 || $vt === 2)) {
                if (isset($pres['INTERVALS'])) {
                    $intervals = is_string($pres['INTERVALS']) ? @json_decode($pres['INTERVALS'], true) : $pres['INTERVALS'];
                    $intervalsActive = isset($pres['INTERVALS_ACTIVE']) ? (bool)$pres['INTERVALS_ACTIVE'] : true;
                    if ($intervalsActive && is_array($intervals)) {
                        $current = floatval($value);
                        foreach ($intervals as $interval) {
                            if (empty($interval['IconActive']) || empty($interval['IconValue'])) continue;
                            $min = array_key_exists('IntervalMinValue', $interval) ? floatval($interval['IntervalMinValue']) : -INF;
                            $max = array_key_exists('IntervalMaxValue', $interval) ? floatval($interval['IntervalMaxValue']) : INF;
                            if ($current >= $min && $current <= $max) {
                                $icon = (string)$interval['IconValue'];
                                break;
                            }
                        }
                    }
                }
            }

            // OPTIONS aus aufgelöster Präsentation
            if ($icon === '' && isset($pres['OPTIONS'])) {
                $opts = is_string($pres['OPTIONS']) ? @json_decode($pres['OPTIONS'], true) : $pres['OPTIONS'];
                if (is_array($opts)) {
                    $isNumeric = ($vt === 1 || $vt === 2);
                    $current = $isNumeric ? floatval($value) : null;
                    foreach ($opts as $opt) {
                        if (!is_array($opt)) continue;
                        $optIcon = $opt['IconValue'] ?? ($opt['Icon'] ?? '');
                        if (empty($optIcon)) continue;
                        $hasRange = isset($opt['Min']) || isset($opt['Max']) || isset($opt['MinValue']) || isset($opt['MaxValue']);
                        if ($hasRange && $current !== null) {
                            $min = $opt['Min'] ?? ($opt['MinValue'] ?? -INF);
                            $max = $opt['Max'] ?? ($opt['MaxValue'] ?? INF);
                            if ($current >= floatval($min) && $current <= floatval($max)) {
                                $icon = (string)$optIcon;
                                break;
                            }
                        } elseif (array_key_exists('Value', $opt)) {
                            if ($current !== null && is_numeric($opt['Value'])) {
                                if (abs(floatval($opt['Value']) - $current) < 1e-9) {
                                    $icon = (string)$optIcon;
                                    break;
                                }
                            } elseif ((string)($opt['Value'] ?? '') === (string)$value) {
                                $icon = (string)$optIcon;
                                break;
                            }
                        }
                    }
                }
            }
        }

        // 2) IPS_GetVariableVisualization Fallback (ValueMappings)
        if ($icon === '' && function_exists('IPS_GetVariableVisualization')) {
            try {
                $vis = @IPS_GetVariableVisualization($id);
                if (is_array($vis) && isset($vis['ValueMappings'])) {
                    foreach ($vis['ValueMappings'] as $mapping) {
                        if (!isset($mapping['Icon']) || $mapping['Icon'] === '') continue;
                        $v = floatval($value);
                        $hasRange = isset($mapping['MinValue']) || isset($mapping['MaxValue']) || isset($mapping['Minimum']) || isset($mapping['Maximum']) || isset($mapping['Min']) || isset($mapping['Max']);
                        if ($hasRange) {
                            $min = $mapping['MinValue'] ?? ($mapping['Minimum'] ?? ($mapping['Min'] ?? -INF));
                            $max = $mapping['MaxValue'] ?? ($mapping['Maximum'] ?? ($mapping['Max'] ?? INF));
                            if ($v >= floatval($min) && $v <= floatval($max)) {
                                $icon = (string)$mapping['Icon'];
                                break;
                            }
                        } elseif (isset($mapping['Value']) && $mapping['Value'] == $value) {
                            $icon = (string)$mapping['Icon'];
                            break;
                        }
                    }
                    if ($icon === '' && isset($vis['Icon']) && $vis['Icon'] !== '') {
                        $icon = (string)$vis['Icon'];
                    }
                }
            } catch (\Throwable $e) {}
        }

        // 3) Klassische Variablenprofile (inkl. PROFILE aus Präsentation)
        if ($icon === '') {
            $profile = $variable['VariableCustomProfile'] ?: ($variable['VariableProfile'] ?? '');
            if (empty($profile) && isset($pres['PROFILE']) && !empty($pres['PROFILE'])) {
                $profile = (string)$pres['PROFILE'];
            }
            if (!empty($profile) && @IPS_VariableProfileExists($profile)) {
                $p = @IPS_GetVariableProfile($profile);
                if (isset($p['Associations']) && is_array($p['Associations'])) {
                    foreach ($p['Associations'] as $assoc) {
                        if (isset($assoc['Value'], $assoc['Icon']) && $assoc['Icon'] !== '' && $assoc['Value'] == $value) {
                            $icon = (string)$assoc['Icon'];
                            break;
                        }
                    }
                }
                if ($icon === '' && isset($p['Icon']) && $p['Icon'] !== '') {
                    $icon = (string)$p['Icon'];
                }
            }
        }

        return ($icon !== '') ? $icon : 'Transparent';
    }

    public static function isObjectHidden(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }
        if (!function_exists('IPS_ObjectExists') || !@IPS_ObjectExists($id)) {
            return false;
        }
        try {
            $o = @IPS_GetObject($id);
            return (bool)($o['ObjectIsHidden'] ?? false);
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function getPresentationColorHex(int $id): string
    {
        if (!function_exists('IPS_VariableExists') || !IPS_VariableExists($id)) {
            return '';
        }
        $variable = IPS_GetVariable($id);
        $value = GetValue($id);
        if (isset($variable['VariableCustomPresentation']) && is_array($variable['VariableCustomPresentation'])) {
            $pres = $variable['VariableCustomPresentation'];
            // Prefer per-value colors from Associations/OPTIONS if available
            $vt = $variable['VariableType'] ?? 0;
            $valNorm = ($vt === 0)
                ? ((($value === true) || ((string)$value === '1') || ($value === 1)) ? '1' : '0')
                : (string)$value;

            // Associations (ASSOCIATIONS/Associations)
            $assocKeys = [];
            if (isset($pres['ASSOCIATIONS']) && is_array($pres['ASSOCIATIONS'])) { $assocKeys[] = 'ASSOCIATIONS'; }
            if (isset($pres['Associations']) && is_array($pres['Associations'])) { $assocKeys[] = 'Associations'; }
            foreach ($assocKeys as $k) {
                foreach ($pres[$k] as $a) {
                    if (!is_array($a)) continue;
                    $av = $a['Value'] ?? ($a['value'] ?? null);
                    if ($av === null) continue;
                    $avNorm = ($vt === 0)
                        ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                        : (string)$av;
                    if ((string)$avNorm === (string)$valNorm) {
                        if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                            // Explicitly disabled color
                        } else {
                            if (isset($a['Color']) && is_numeric($a['Color']) && (int)$a['Color'] !== -1) {
                                return sprintf('%06X', (int)$a['Color']);
                            }
                            if (isset($a['COLOR']) && is_numeric($a['COLOR']) && (int)$a['COLOR'] !== -1) {
                                return sprintf('%06X', (int)$a['COLOR']);
                            }
                            if (isset($a['ColorValue']) && is_numeric($a['ColorValue']) && (int)$a['ColorValue'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorValue']);
                            }
                            if (isset($a['ColorDisplay']) && is_numeric($a['ColorDisplay']) && (int)$a['ColorDisplay'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorDisplay']);
                            }
                        }
                    }
                }
            }

            // OPTIONS (stringified JSON or array)
            $optRaw = null;
            if (isset($pres['OPTIONS'])) { $optRaw = $pres['OPTIONS']; }
            elseif (isset($pres['Options'])) { $optRaw = $pres['Options']; }
            if ($optRaw !== null) {
                $opts = [];
                if (is_string($optRaw)) {
                    $decoded = @json_decode($optRaw, true);
                    if (is_array($decoded)) { $opts = $decoded; }
                } elseif (is_array($optRaw)) { $opts = $optRaw; }
                foreach ($opts as $a) {
                    if (!is_array($a)) continue;
                    if (!array_key_exists('Value', $a)) continue;
                    $av = $a['Value'];
                    $avNorm = ($vt === 0)
                        ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                        : (string)$av;
                    if ((string)$avNorm === (string)$valNorm) {
                        if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                            // Explicitly disabled color
                        } else {
                            if (isset($a['Color']) && is_numeric($a['Color']) && (int)$a['Color'] !== -1) {
                                return sprintf('%06X', (int)$a['Color']);
                            }
                            if (isset($a['COLOR']) && is_numeric($a['COLOR']) && (int)$a['COLOR'] !== -1) {
                                return sprintf('%06X', (int)$a['COLOR']);
                            }
                            if (isset($a['ColorValue']) && is_numeric($a['ColorValue']) && (int)$a['ColorValue'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorValue']);
                            }
                            if (isset($a['ColorDisplay']) && is_numeric($a['ColorDisplay']) && (int)$a['ColorDisplay'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorDisplay']);
                            }
                        }
                    }
                }
            }

            // TEMPLATE: resolve via IPS_GetTemplate and read Values.OPTIONS
            if (isset($pres['TEMPLATE']) && function_exists('IPS_GetTemplate')) {
                try {
                    $tpl = @IPS_GetTemplate($pres['TEMPLATE']);
                    if (is_array($tpl) && isset($tpl['Values']) && is_array($tpl['Values'])) {
                        $vals = $tpl['Values'];
                        $optRaw = $vals['OPTIONS'] ?? ($vals['Options'] ?? null);
                        if ($optRaw !== null) {
                            $opts = is_string($optRaw) ? (@json_decode($optRaw, true) ?: []) : (is_array($optRaw) ? $optRaw : []);
                            foreach ($opts as $a) {
                                if (!is_array($a) || !array_key_exists('Value', $a)) continue;
                                $av = $a['Value'];
                                $avNorm = ($vt === 0)
                                    ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                                    : (string)$av;
                                if ((string)$avNorm === (string)$valNorm) {
                                    if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                                        // disabled
                                    } else {
                                        if (isset($a['Color']) && is_numeric($a['Color']) && (int)$a['Color'] !== -1) {
                                            return sprintf('%06X', (int)$a['Color']);
                                        }
                                        if (isset($a['COLOR']) && is_numeric($a['COLOR']) && (int)$a['COLOR'] !== -1) {
                                            return sprintf('%06X', (int)$a['COLOR']);
                                        }
                                        if (isset($a['ColorValue']) && is_numeric($a['ColorValue']) && (int)$a['ColorValue'] !== -1) {
                                            return sprintf('%06X', (int)$a['ColorValue']);
                                        }
                                        if (isset($a['ColorDisplay']) && is_numeric($a['ColorDisplay']) && (int)$a['ColorDisplay'] !== -1) {
                                            return sprintf('%06X', (int)$a['ColorDisplay']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {}
            }

            // PRESENTATION GUID: resolve via IPS_GetPresentation and read presentationParameters.OPTIONS
            if (isset($pres['PRESENTATION']) && function_exists('IPS_GetPresentation')) {
                try {
                    $guidRaw = (string)$pres['PRESENTATION'];
                    $guid = (strpos($guidRaw, '{') === false) ? ('{' . $guidRaw . '}') : $guidRaw;
                    $pdata = @IPS_GetPresentation($guid);
                    if (is_string($pdata)) {
                        $decoded = @json_decode($pdata, true);
                        if (is_array($decoded)) { $pdata = $decoded; }
                    }
                    if (is_array($pdata)) {
                        $pp = isset($pdata['presentationParameters']) && is_array($pdata['presentationParameters']) ? $pdata['presentationParameters'] : [];
                        if (!empty($pp)) {
                            $optRaw = $pp['OPTIONS'] ?? ($pp['Options'] ?? null);
                            if ($optRaw !== null) {
                                $opts = is_string($optRaw) ? (@json_decode($optRaw, true) ?: []) : (is_array($optRaw) ? $optRaw : []);
                                foreach ($opts as $a) {
                                    if (!is_array($a) || !array_key_exists('Value', $a)) continue;
                                    $av = $a['Value'];
                                    $avNorm = ($vt === 0)
                                        ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                                        : (string)$av;
                                    if ((string)$avNorm === (string)$valNorm) {
                                        if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                                            // disabled
                                        } else {
                                            if (isset($a['Color']) && is_numeric($a['Color']) && (int)$a['Color'] !== -1) {
                                                return sprintf('%06X', (int)$a['Color']);
                                            }
                                            if (isset($a['COLOR']) && is_numeric($a['COLOR']) && (int)$a['COLOR'] !== -1) {
                                                return sprintf('%06X', (int)$a['COLOR']);
                                            }
                                            if (isset($a['ColorValue']) && is_numeric($a['ColorValue']) && (int)$a['ColorValue'] !== -1) {
                                                return sprintf('%06X', (int)$a['ColorValue']);
                                            }
                                            if (isset($a['ColorDisplay']) && is_numeric($a['ColorDisplay']) && (int)$a['ColorDisplay'] !== -1) {
                                                return sprintf('%06X', (int)$a['ColorDisplay']);
                                            }
                                        }
                                    }
                                }
                            }
                            // General color from parameters
                            if ($vt === 0) {
                                $useFalse = $pp['USE_COLOR_FALSE'] ?? true;
                                if (isset($pp['COLOR_TRUE']) && is_numeric($pp['COLOR_TRUE']) && (int)$pp['COLOR_TRUE'] !== -1 && ($value === true || (string)$value === '1' || $value === 1)) {
                                    return sprintf('%06X', (int)$pp['COLOR_TRUE']);
                                }
                                if ($useFalse && isset($pp['COLOR_FALSE']) && is_numeric($pp['COLOR_FALSE']) && (int)$pp['COLOR_FALSE'] !== -1 && ($value === false || (string)$value === '0' || (string)$value === '' || $value === 0)) {
                                    return sprintf('%06X', (int)$pp['COLOR_FALSE']);
                                }
                            }
                            if (isset($pp['COLOR']) && is_numeric($pp['COLOR']) && (int)$pp['COLOR'] !== -1) {
                                return sprintf('%06X', (int)$pp['COLOR']);
                            }
                            if (isset($pp['Color']) && is_numeric($pp['Color']) && (int)$pp['Color'] !== -1) {
                                return sprintf('%06X', (int)$pp['Color']);
                            }
                        }
                    }
                } catch (Throwable $e) {}
            }

            // General presentation color fallbacks
            $color = null;
            if ($vt === 0) {
                $useColorFalse = $pres['USE_COLOR_FALSE'] ?? true;
                $valBool = ($value === true || (string)$value === '1' || $value === 1);
                if ($valBool && isset($pres['COLOR_TRUE'])) {
                    $color = $pres['COLOR_TRUE'];
                } elseif (!$valBool && $useColorFalse && isset($pres['COLOR_FALSE'])) {
                    $color = $pres['COLOR_FALSE'];
                }
            }
            if ($color === null) {
                if (isset($pres['COLOR'])) {
                    $color = $pres['COLOR'];
                } elseif (isset($pres['Color'])) {
                    $color = $pres['Color'];
                }
            }
            if (is_numeric($color)) {
                $ci = (int)$color;
                return ($ci === -1) ? '' : sprintf('%06X', $ci);
            }
        }
        if (isset($variable['VariablePresentation']) && is_array($variable['VariablePresentation'])) {
            $pres = $variable['VariablePresentation'];
            $vt = $variable['VariableType'] ?? 0;
            $valNorm = ($vt === 0)
                ? ((($value === true) || ((string)$value === '1') || ($value === 1)) ? '1' : '0')
                : (string)$value;

            $assocKeys = [];
            if (isset($pres['ASSOCIATIONS']) && is_array($pres['ASSOCIATIONS'])) { $assocKeys[] = 'ASSOCIATIONS'; }
            if (isset($pres['Associations']) && is_array($pres['Associations'])) { $assocKeys[] = 'Associations'; }
            foreach ($assocKeys as $k) {
                foreach ($pres[$k] as $a) {
                    if (!is_array($a)) continue;
                    $av = $a['Value'] ?? ($a['value'] ?? null);
                    if ($av === null) continue;
                    $avNorm = ($vt === 0)
                        ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                        : (string)$av;
                    if ((string)$avNorm === (string)$valNorm) {
                        if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                            // disabled color
                        } else {
                            if (isset($a['Color']) && is_int($a['Color']) && $a['Color'] !== -1) {
                                return sprintf('%06X', (int)$a['Color']);
                            }
                            if (isset($a['COLOR']) && is_int($a['COLOR']) && $a['COLOR'] !== -1) {
                                return sprintf('%06X', (int)$a['COLOR']);
                            }
                            if (isset($a['ColorValue']) && is_int($a['ColorValue']) && $a['ColorValue'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorValue']);
                            }
                            if (isset($a['ColorDisplay']) && is_int($a['ColorDisplay']) && $a['ColorDisplay'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorDisplay']);
                            }
                        }
                    }
                }
            }

            $optRaw = null;
            if (isset($pres['OPTIONS'])) { $optRaw = $pres['OPTIONS']; }
            elseif (isset($pres['Options'])) { $optRaw = $pres['Options']; }
            if ($optRaw !== null) {
                $opts = [];
                if (is_string($optRaw)) {
                    $decoded = @json_decode($optRaw, true);
                    if (is_array($decoded)) { $opts = $decoded; }
                } elseif (is_array($optRaw)) { $opts = $optRaw; }
                foreach ($opts as $a) {
                    if (!is_array($a)) continue;
                    if (!array_key_exists('Value', $a)) continue;
                    $av = $a['Value'];
                    $avNorm = ($vt === 0)
                        ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                        : (string)$av;
                    if ((string)$avNorm === (string)$valNorm) {
                        if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                            // disabled color
                        } else {
                            if (isset($a['Color']) && is_int($a['Color']) && $a['Color'] !== -1) {
                                return sprintf('%06X', (int)$a['Color']);
                            }
                            if (isset($a['COLOR']) && is_int($a['COLOR']) && $a['COLOR'] !== -1) {
                                return sprintf('%06X', (int)$a['COLOR']);
                            }
                            if (isset($a['ColorValue']) && is_int($a['ColorValue']) && $a['ColorValue'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorValue']);
                            }
                            if (isset($a['ColorDisplay']) && is_int($a['ColorDisplay']) && $a['ColorDisplay'] !== -1) {
                                return sprintf('%06X', (int)$a['ColorDisplay']);
                            }
                        }
                    }
                }
            }

            // TEMPLATE (standard presentation)
            if (isset($pres['TEMPLATE']) && function_exists('IPS_GetTemplate')) {
                try {
                    $tpl = @IPS_GetTemplate($pres['TEMPLATE']);
                    if (is_array($tpl) && isset($tpl['Values']) && is_array($tpl['Values'])) {
                        $vals = $tpl['Values'];
                        $optRaw = $vals['OPTIONS'] ?? ($vals['Options'] ?? null);
                        if ($optRaw !== null) {
                            $opts = is_string($optRaw) ? (@json_decode($optRaw, true) ?: []) : (is_array($optRaw) ? $optRaw : []);
                            foreach ($opts as $a) {
                                if (!is_array($a) || !array_key_exists('Value', $a)) continue;
                                $av = $a['Value'];
                                $avNorm = ($vt === 0)
                                    ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                                    : (string)$av;
                                if ((string)$avNorm === (string)$valNorm) {
                                    if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                                        // disabled
                                    } else {
                                        if (isset($a['Color']) && is_numeric($a['Color']) && (int)$a['Color'] !== -1) {
                                            return sprintf('%06X', (int)$a['Color']);
                                        }
                                        if (isset($a['COLOR']) && is_numeric($a['COLOR']) && (int)$a['COLOR'] !== -1) {
                                            return sprintf('%06X', (int)$a['COLOR']);
                                        }
                                        if (isset($a['ColorValue']) && is_numeric($a['ColorValue']) && (int)$a['ColorValue'] !== -1) {
                                            return sprintf('%06X', (int)$a['ColorValue']);
                                        }
                                        if (isset($a['ColorDisplay']) && is_numeric($a['ColorDisplay']) && (int)$a['ColorDisplay'] !== -1) {
                                            return sprintf('%06X', (int)$a['ColorDisplay']);
                                        }
                                    }
                                }
                            }
                        }
                        // General color fallback from template values
                        if ($vt === 0) {
                            $useFalse = $vals['USE_COLOR_FALSE'] ?? true;
                            if (isset($vals['COLOR_TRUE']) && is_numeric($vals['COLOR_TRUE']) && (int)$vals['COLOR_TRUE'] !== -1 && ($value === true || (string)$value === '1' || $value === 1)) {
                                return sprintf('%06X', (int)$vals['COLOR_TRUE']);
                            }
                            if ($useFalse && isset($vals['COLOR_FALSE']) && is_numeric($vals['COLOR_FALSE']) && (int)$vals['COLOR_FALSE'] !== -1 && ($value === false || (string)$value === '0' || (string)$value === '' || $value === 0)) {
                                return sprintf('%06X', (int)$vals['COLOR_FALSE']);
                            }
                        }
                        if (isset($vals['COLOR']) && is_numeric($vals['COLOR']) && (int)$vals['COLOR'] !== -1) {
                            return sprintf('%06X', (int)$vals['COLOR']);
                        }
                        if (isset($vals['Color']) && is_numeric($vals['Color']) && (int)$vals['Color'] !== -1) {
                            return sprintf('%06X', (int)$vals['Color']);
                        }
                    }
                } catch (Throwable $e) {}
            }

            // PRESENTATION GUID (standard presentation)
            if (isset($pres['PRESENTATION']) && function_exists('IPS_GetPresentation')) {
                try {
                    $guidRaw = (string)$pres['PRESENTATION'];
                    $guid = (strpos($guidRaw, '{') === false) ? ('{' . $guidRaw . '}') : $guidRaw;
                    $pdata = @IPS_GetPresentation($guid);
                    if (is_string($pdata)) {
                        $decoded = @json_decode($pdata, true);
                        if (is_array($decoded)) { $pdata = $decoded; }
                    }
                    if (is_array($pdata)) {
                        $pp = isset($pdata['presentationParameters']) && is_array($pdata['presentationParameters']) ? $pdata['presentationParameters'] : [];
                        if (!empty($pp)) {
                            $optRaw = $pp['OPTIONS'] ?? ($pp['Options'] ?? null);
                            if ($optRaw !== null) {
                                $opts = is_string($optRaw) ? (@json_decode($optRaw, true) ?: []) : (is_array($optRaw) ? $optRaw : []);
                                foreach ($opts as $a) {
                                    if (!is_array($a) || !array_key_exists('Value', $a)) continue;
                                    $av = $a['Value'];
                                    $avNorm = ($vt === 0)
                                        ? ((($av === true) || ($av === 1) || ((string)$av === '1')) ? '1' : '0')
                                        : (string)$av;
                                    if ((string)$avNorm === (string)$valNorm) {
                                        if (isset($a['ColorActive']) && $a['ColorActive'] === false) {
                                            // disabled
                                        } else {
                                            if (isset($a['Color']) && is_int($a['Color']) && $a['Color'] !== -1) {
                                                return sprintf('%06X', (int)$a['Color']);
                                            }
                                            if (isset($a['COLOR']) && is_int($a['COLOR']) && $a['COLOR'] !== -1) {
                                                return sprintf('%06X', (int)$a['COLOR']);
                                            }
                                            if (isset($a['ColorValue']) && is_int($a['ColorValue']) && $a['ColorValue'] !== -1) {
                                                return sprintf('%06X', (int)$a['ColorValue']);
                                            }
                                            if (isset($a['ColorDisplay']) && is_int($a['ColorDisplay']) && $a['ColorDisplay'] !== -1) {
                                                return sprintf('%06X', (int)$a['ColorDisplay']);
                                            }
                                        }
                                    }
                                }
                            }
                            if ($vt === 0) {
                                $useFalse = $pp['USE_COLOR_FALSE'] ?? true;
                                if (isset($pp['COLOR_TRUE']) && is_int($pp['COLOR_TRUE']) && $pp['COLOR_TRUE'] !== -1 && ($value === true || (string)$value === '1' || $value === 1)) {
                                    return sprintf('%06X', (int)$pp['COLOR_TRUE']);
                                }
                                if ($useFalse && isset($pp['COLOR_FALSE']) && is_int($pp['COLOR_FALSE']) && $pp['COLOR_FALSE'] !== -1 && ($value === false || (string)$value === '0' || (string)$value === '' || $value === 0)) {
                                    return sprintf('%06X', (int)$pp['COLOR_FALSE']);
                                }
                            }
                            if (isset($pp['COLOR']) && is_int($pp['COLOR']) && $pp['COLOR'] !== -1) {
                                return sprintf('%06X', (int)$pp['COLOR']);
                            }
                            if (isset($pp['Color']) && is_int($pp['Color']) && $pp['Color'] !== -1) {
                                return sprintf('%06X', (int)$pp['Color']);
                            }
                        }
                    }
                } catch (Throwable $e) {}
            }

            $color = null;
            if ($vt === 0) {
                $useColorFalse = $pres['USE_COLOR_FALSE'] ?? true;
                $valBool = ($value === true || (string)$value === '1' || $value === 1);
                if ($valBool && isset($pres['COLOR_TRUE'])) {
                    $color = $pres['COLOR_TRUE'];
                } elseif (!$valBool && $useColorFalse && isset($pres['COLOR_FALSE'])) {
                    $color = $pres['COLOR_FALSE'];
                }
            }
            if ($color === null) {
                if (isset($pres['COLOR'])) {
                    $color = $pres['COLOR'];
                } elseif (isset($pres['Color'])) {
                    $color = $pres['Color'];
                }
            }
            if (is_numeric($color)) {
                $ci = (int)$color;
                return ($ci === -1) ? '' : sprintf('%06X', $ci);
            }
        }
        return '';
    }

    // Generic associations reader
    public static function getAssociations(int $id): array
    {
        $out = [];
        if (!function_exists('IPS_VariableExists') || !IPS_VariableExists($id)) {
            return $out;
        }
        $variable = IPS_GetVariable($id);
        // Prefer associations from VariableCustomPresentation if present
        if (isset($variable['VariableCustomPresentation']) && is_array($variable['VariableCustomPresentation'])) {
            $pres = $variable['VariableCustomPresentation'];
            $assocKeys = [];
            if (isset($pres['ASSOCIATIONS']) && is_array($pres['ASSOCIATIONS'])) {
                $assocKeys[] = 'ASSOCIATIONS';
            }
            if (isset($pres['Associations']) && is_array($pres['Associations'])) {
                $assocKeys[] = 'Associations';
            }
            foreach ($assocKeys as $k) {
                foreach ($pres[$k] as $a) {
                    $item = [
                        'value' => $a['Value'] ?? ($a['value'] ?? ''),
                        'label' => $a['Name'] ?? ($a['Label'] ?? ($a['label'] ?? '')),
                    ];
                    $iconVal = $a['Icon'] ?? ($a['ICON'] ?? null);
                    if (!empty($iconVal)) {
                        $item['icon'] = $iconVal;
                    }
                    $colVal = $a['Color'] ?? ($a['COLOR'] ?? null);
                    if (isset($colVal) && $colVal !== -1) {
                        $item['color'] = '#' . sprintf('%06X', (int)$colVal);
                    }
                    $out[] = $item;
                }
            }
            // Support OPTIONS from VariableCustomPresentation (stringified JSON or array)
            if (empty($out)) {
                $optRaw = null;
                if (isset($pres['OPTIONS'])) {
                    $optRaw = $pres['OPTIONS'];
                } elseif (isset($pres['Options'])) {
                    $optRaw = $pres['Options'];
                }
                if ($optRaw !== null) {
                    $opts = [];
                    if (is_string($optRaw)) {
                        $decoded = @json_decode($optRaw, true);
                        if (is_array($decoded)) {
                            $opts = $decoded;
                        }
                    } elseif (is_array($optRaw)) {
                        $opts = $optRaw;
                    }
                    foreach ($opts as $a) {
                        if (!is_array($a)) continue;
                        $item = [
                            'value' => $a['Value'] ?? ($a['value'] ?? ''),
                            'label' => $a['Caption'] ?? ($a['Name'] ?? ($a['Label'] ?? ($a['label'] ?? ''))),
                        ];
                        $iconVal = $a['IconValue'] ?? ($a['Icon'] ?? null);
                        if (!empty($iconVal)) {
                            $item['icon'] = $iconVal;
                        }
                        if (isset($a['Color'])) {
                            $colVal = (int)$a['Color'];
                            if ($colVal !== -1) {
                                $item['color'] = '#' . sprintf('%06X', $colVal);
                            }
                        }
                        $out[] = $item;
                    }
                }
            }
            if (!empty($out)) {
                return $out;
            }
        }
        $profile = $variable['VariableCustomProfile'] ?: $variable['VariableProfile'];
        if ($profile && IPS_VariableProfileExists($profile)) {
            $p = IPS_GetVariableProfile($profile);
            if (isset($p['Associations']) && is_array($p['Associations'])) {
                foreach ($p['Associations'] as $a) {
                    $item = [
                        'value' => $a['Value'] ?? '',
                        'label' => $a['Name'] ?? '',
                    ];
                    if (!empty($a['Icon'])) {
                        $item['icon'] = $a['Icon'];
                    }
                    if (isset($a['Color']) && $a['Color'] !== -1) {
                        $item['color'] = '#' . sprintf('%06X', (int)$a['Color']);
                    }
                    $out[] = $item;
                }
            }
        }
        return $out;
    }

    public static function getIntegerAssociations(int $id): array
    {
        return self::getAssociations($id);
    }

    public static function getStringAssociations(int $id): array
    {
        return self::getAssociations($id);
    }

    /**
     * Property-Rename-Migration v2: German → English property names.
     * Returns true if migration was performed (caller should return from ApplyChanges).
     */
    public static function migrateV2(IPSModule $module, int $instanceId): bool
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
