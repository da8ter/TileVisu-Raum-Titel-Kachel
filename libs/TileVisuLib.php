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
}
