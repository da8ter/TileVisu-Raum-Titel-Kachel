<?php

class TileVisuLib
{
    // Returns hex color without leading '#', or empty string if none
    public static function getProfileColorHex(int $id): string
    {
        if (!function_exists('IPS_VariableExists') || !IPS_VariableExists($id)) {
            return '';
        }
        $presColor = self::getPresentationColorHex($id);
        if ($presColor !== '') {
            return $presColor;
        }

        $variable = IPS_GetVariable($id);
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
            $color = null;
            if ($variable['VariableType'] === 0) {
                $useColorFalse = $pres['USE_COLOR_FALSE'] ?? true;
                if ($value && isset($pres['COLOR_TRUE'])) {
                    $color = $pres['COLOR_TRUE'];
                } elseif (!$value && $useColorFalse && isset($pres['COLOR_FALSE'])) {
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
            if (is_int($color)) {
                return ($color === -1) ? '' : sprintf('%06X', $color);
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
