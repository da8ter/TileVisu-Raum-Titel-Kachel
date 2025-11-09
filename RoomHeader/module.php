<?php
class TileVisuRoomHeaderTile extends IPSModule
{
    public function Create()
    {
        // Nie diese Zeile löschen!
        parent::Create();


        // Drei Eigenschaften für die dargestellten Zähler
        $this->RegisterPropertyInteger("bgImage", 1);
        $this->RegisterPropertyInteger("Target", 1);
        $this->RegisterPropertyInteger("InfoLinks", 1);
        $this->RegisterPropertyBoolean('InfoLinksNameSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinksIconSwitch', true);
        $this->RegisterPropertyBoolean('InfoLinksVarIconSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinksAssoSwitch', true);
        $this->RegisterPropertyString('InfoLinksAltName', '');
        $this->RegisterPropertyInteger("InfoLinks2", 1);
        $this->RegisterPropertyBoolean('InfoLinks2NameSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinks2IconSwitch', true);
        $this->RegisterPropertyBoolean('InfoLinks2VarIconSwitch', false);
        $this->RegisterPropertyBoolean('InfoLinks2AssoSwitch', true);
        $this->RegisterPropertyString('InfoLinks2AltName', '');
        $this->RegisterPropertyInteger("InfoRechts", 1);
        $this->RegisterPropertyBoolean('InfoRechtsNameSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechtsIconSwitch', true);
        $this->RegisterPropertyBoolean('InfoRechtsVarIconSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechtsAssoSwitch', true);
        $this->RegisterPropertyString('InfoRechtsAltName', '');
        $this->RegisterPropertyInteger("InfoRechts2", 1);
        $this->RegisterPropertyBoolean('InfoRechts2NameSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechts2IconSwitch', true);
        $this->RegisterPropertyBoolean('InfoRechts2VarIconSwitch', false);
        $this->RegisterPropertyBoolean('InfoRechts2AssoSwitch', true);
        $this->RegisterPropertyString('InfoRechts2AltName', '');
        $this->RegisterPropertyFloat('InfoSchriftgroesse', 30);
        $this->RegisterPropertyBoolean('InfoMenueSwitch', true);
        $this->RegisterPropertyFloat('InfoMenueSchriftgroesse', 20);
        $this->RegisterPropertyFloat('InfoMenueTransparenz', 0.3);
        $this->RegisterPropertyInteger('InfoMenueHintergrundfarbe', 0x000000);
        $this->RegisterPropertyFloat('Bildtransparenz', 0.7);
        $this->RegisterPropertyInteger('Kachelhintergrundfarbe', 0x000000);
        $this->RegisterPropertyInteger('InfoSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyInteger('InfoMenueSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyString('Raumname', 'Raumname');
        $this->RegisterPropertyFloat('RaumnameSchriftgroesse', 70);
        $this->RegisterPropertyInteger('RaumnameSchriftfarbe', 0xFFFFFF);
        $this->RegisterPropertyInteger('Schalter1', 1);
        $this->RegisterPropertyFloat('Schalter1Schriftgroesse', 20);
        $this->RegisterPropertyFloat('Schalter1Breite', 100);
        $this->RegisterPropertyString('Schalter1AltName', '');
        $this->RegisterPropertyInteger('Schalter2', 1);
        $this->RegisterPropertyFloat('Schalter2Schriftgroesse', 20);
        $this->RegisterPropertyFloat('Schalter2Breite', 100);
        $this->RegisterPropertyString('Schalter2AltName', '');
        $this->RegisterPropertyInteger('Schalter3', 1);
        $this->RegisterPropertyFloat('Schalter3Schriftgroesse', 20);
        $this->RegisterPropertyFloat('Schalter3Breite', 100);
        $this->RegisterPropertyString('Schalter3AltName', '');
        $this->RegisterPropertyInteger('Schalter4', 1);
        $this->RegisterPropertyFloat('Schalter4Schriftgroesse', 20);
        $this->RegisterPropertyFloat('Schalter4Breite', 100);
        $this->RegisterPropertyString('Schalter4AltName', '');
        $this->RegisterPropertyInteger('Schalter5', 1);
        $this->RegisterPropertyFloat('Schalter5Schriftgroesse', 20);
        $this->RegisterPropertyFloat('Schalter5Breite', 100);
        $this->RegisterPropertyString('Schalter5AltName', '');
        $this->RegisterPropertyInteger('Info1', 1);
        $this->RegisterPropertyString('Info1AltName', '');
        $this->RegisterPropertyInteger('Info2', 1);
        $this->RegisterPropertyString('Info2AltName', '');
        $this->RegisterPropertyInteger('Info3', 1);
        $this->RegisterPropertyString('Info3AltName', '');
        $this->RegisterPropertyInteger('Info4', 1);
        $this->RegisterPropertyString('Info4AltName', '');
        $this->RegisterPropertyInteger('Info5', 1);
        $this->RegisterPropertyString('Info5AltName', '');
        $this->RegisterPropertyBoolean('Info1NameSwitch', true);
        $this->RegisterPropertyBoolean('Info2NameSwitch', true);
        $this->RegisterPropertyBoolean('Info3NameSwitch', true);
        $this->RegisterPropertyBoolean('Info4NameSwitch', true);
        $this->RegisterPropertyBoolean('Info5NameSwitch', true);
        $this->RegisterPropertyBoolean('Info1IconSwitch', true);
        $this->RegisterPropertyBoolean('Info2IconSwitch', true);
        $this->RegisterPropertyBoolean('Info3IconSwitch', true);
        $this->RegisterPropertyBoolean('Info4IconSwitch', true);
        $this->RegisterPropertyBoolean('Info5IconSwitch', true);
        $this->RegisterPropertyBoolean('Info1VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Info2VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Info3VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Info4VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Info5VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Info1AssoSwitch', true);
        $this->RegisterPropertyBoolean('Info2AssoSwitch', true);
        $this->RegisterPropertyBoolean('Info3AssoSwitch', true);
        $this->RegisterPropertyBoolean('Info4AssoSwitch', true);
        $this->RegisterPropertyBoolean('Info5AssoSwitch', true);
        $this->RegisterPropertyBoolean('Schalter1NameSwitch', true);
        $this->RegisterPropertyBoolean('Schalter2NameSwitch', true);
        $this->RegisterPropertyBoolean('Schalter3NameSwitch', true);
        $this->RegisterPropertyBoolean('Schalter4NameSwitch', true);
        $this->RegisterPropertyBoolean('Schalter5NameSwitch', true);
        $this->RegisterPropertyBoolean('Schalter1IconSwitch', true);
        $this->RegisterPropertyBoolean('Schalter2IconSwitch', true);
        $this->RegisterPropertyBoolean('Schalter3IconSwitch', true);
        $this->RegisterPropertyBoolean('Schalter4IconSwitch', true);
        $this->RegisterPropertyBoolean('Schalter5IconSwitch', true);
        $this->RegisterPropertyBoolean('Schalter1VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Schalter2VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Schalter3VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Schalter4VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Schalter5VarIconSwitch', false);
        $this->RegisterPropertyBoolean('Schalter1AssoSwitch', true);
        $this->RegisterPropertyBoolean('Schalter2AssoSwitch', true);
        $this->RegisterPropertyBoolean('Schalter3AssoSwitch', true);
        $this->RegisterPropertyBoolean('Schalter4AssoSwitch', true);
        $this->RegisterPropertyBoolean('Schalter5AssoSwitch', true);
        // Visualisierungstyp auf 1 setzen, da wir HTML anbieten möchten
        $this->SetVisualizationType(1);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();


        //Referenzen Registrieren
        $ids = [
            $this->ReadPropertyInteger('Target'),
            $this->ReadPropertyInteger('bgImage'),
            $this->ReadPropertyInteger('InfoLinks'),
            $this->ReadPropertyInteger('InfoLinks2'),
            $this->ReadPropertyInteger('InfoRechts'),
            $this->ReadPropertyInteger('InfoRechts2'),
            $this->ReadPropertyInteger('Schalter1'),
            $this->ReadPropertyInteger('Schalter2'),
            $this->ReadPropertyInteger('Schalter3'),
            $this->ReadPropertyInteger('Schalter4'),
            $this->ReadPropertyInteger('Schalter5'),
            $this->ReadPropertyInteger('Info1'),
            $this->ReadPropertyInteger('Info2'),
            $this->ReadPropertyInteger('Info3'),
            $this->ReadPropertyInteger('Info4'),
            $this->ReadPropertyInteger('Info5')
        ];
        $refs = $this->GetReferenceList();
            foreach($refs as $ref) {
                $this->UnregisterReference($ref);
            } 
            foreach ($ids as $id) {
                if ($id > 9999) {
                    $this->RegisterReference($id);
                }
            }

        
        // Aktualisiere registrierte Nachrichten
        foreach ($this->GetMessageList() as $senderID => $messageIDs)
        {
            foreach ($messageIDs as $messageID)
            {
                $this->UnregisterMessage($senderID, $messageID);
            }
        }


        foreach (['bgImage', 'InfoLinks', 'InfoLinks2', 'InfoRechts', 'InfoRechts2', 'Schalter1', 'Schalter2', 'Schalter3', 'Schalter4', 'Schalter5', 'Info1', 'Info2', 'Info3', 'Info4', 'Info5'] as $VariableProperty)        {
            $this->RegisterMessage($this->ReadPropertyInteger($VariableProperty), VM_UPDATE);
        }

        // Schicke eine komplette Update-Nachricht an die Darstellung, da sich ja Parameter geändert haben können
        // Aber in einem eigenen Script Thread, da sonst ApplyChanges blockiert wird und auch die Konsole hängt.
        IPS_RunScriptText('RMH_ForceUpdate('.$this->InstanceID.');');
        
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $properties = [
            'bgImage', 'InfoLinks', 'InfoLinks2', 'InfoRechts', 'InfoRechts2',
            'Schalter1', 'Schalter2', 'Schalter3', 'Schalter4', 'Schalter5',
            'Info1', 'Info2', 'Info3', 'Info4', 'Info5'
        ];

        foreach ($properties as $VariableProperty) // $VariableProperty ist der Name der Eigenschaft, z.B. "Schalter1"
        {
            // Prüfen, ob der Sender (geänderte Variable) zu dieser Eigenschaft gehört
            if ($SenderID === $this->ReadPropertyInteger($VariableProperty))
            {
                // Wenn ja, und es ist eine Aktualisierungsnachricht...
                if ($Message === VM_UPDATE)
                {
                    // Erster Update-Aufruf: Sendet den Hauptwert der geänderten Variable.
                    // $SenderID ist hier die ID der Variablen.
                    $this->UpdateVisualizationValue(json_encode([
                        $VariableProperty => GetValueFormatted($SenderID) // Verwende SenderID
                    ]));
                
                    // Vorbereitung der Daten für den zweiten Update-Aufruf (assoziierte Eigenschaften).
                    $result = []; 
                    // Verwende SenderID für GetColor
                    $result[$VariableProperty . 'Color'] = $this->GetColor($SenderID);

                    if ($VariableProperty != 'bgImage')
                {
                        if ($this->ReadPropertyBoolean($VariableProperty . 'NameSwitch')) {
                            // Verwende SenderID für IPS_GetName
                            $result[$VariableProperty . 'name'] = IPS_GetName($SenderID);
                        }
                        
                        // Verwende SenderID für GetIcon
                        $iconValue = $this->GetIcon($SenderID, $this->ReadPropertyBoolean($VariableProperty . 'VarIconSwitch'));
                        if ($this->ReadPropertyBoolean($VariableProperty . 'IconSwitch') && $iconValue !== "Transparent") {
                           $result[$VariableProperty . 'icon'] = $iconValue;
                        }

                        if ($this->ReadPropertyBoolean($VariableProperty . 'AssoSwitch')) {
                            // CheckAndGetValueFormatted benötigt den Namen der Eigenschaft
                            $result[$VariableProperty . 'asso'] = $this->CheckAndGetValueFormatted($VariableProperty);
                                }
                                $result[$VariableProperty .'AltName'] =  $this->ReadPropertyString($VariableProperty .'AltName');
                            }

                    // Zweiter Update-Aufruf: Sendet die assoziierten Eigenschaften.
                            $this->UpdateVisualizationValue(json_encode($result));

                    // Da die passende Variable gefunden und verarbeitet wurde, die Schleife und Methode verlassen.
                    return; 
                }
                // Hier könnten andere Nachrichten-Typen (außer VM_UPDATE) für den gematchten SenderID behandelt werden.
            }
        }
    }


    public function RequestAction($Ident, $value) {
        // Nachrichten von der HTML-Darstellung schicken immer den Ident passend zur Eigenschaft und im Wert die Differenz, welche auf die Variable gerechnet werden soll
        $variableID = $this->ReadPropertyInteger($Ident);
        if (!IPS_VariableExists($variableID)) {
            $this->SendDebug('Error in RequestAction', 'Variable to be updated does not exist', 0);
            return;
        }
            // Umschalten des Werts der Variable
        $currentValue = GetValue($variableID);
        //SetValue($variableID, !$currentValue);
        RequestAction($variableID, !$currentValue);
    }


    public function GetVisualizationTile()
    {
        // Füge ein Skript hinzu, um beim Laden, analog zu Änderungen bei Laufzeit, die Werte zu setzen
        $initialHandling = '<script>handleMessage(' . json_encode($this->GetFullUpdateMessage()) . ')</script>';

        // Füge statisches HTML aus Datei hinzu
        $module = file_get_contents(__DIR__ . '/module.html');

        // Gebe alles zurück.
        // Wichtig: $initialHandling nach hinten, da die Funktion handleMessage erst im HTML definiert wird
        return $module . $initialHandling;
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
		$Form['elements'][4]['items'][2]['visible'] = ((float)IPS_GetKernelVersion() > 8.1);
		return json_encode($Form);
	}

    public function ForceUpdate()
    {
        $this->UpdateVisualizationValue($this->GetFullUpdateMessage());
    }

    // Generiere eine Nachricht, die alle Elemente in der HTML-Darstellung aktualisiert
    private function GetFullUpdateMessage() {
        $result = [];
    
        $infoProperties = ['InfoLinks', 'InfoLinks2', 'InfoRechts', 'InfoRechts2', 'Info1', 'Info2', 'Info3', 'Info4', 'Info5'];
        $schalterProperties = ['Schalter1', 'Schalter2', 'Schalter3', 'Schalter4', 'Schalter5'];

        foreach ($infoProperties as $propName) {
            $this->_addVisualisationProperty($result, $propName, 'info');
        }

        foreach ($schalterProperties as $propName) {
            $this->_addVisualisationProperty($result, $propName, 'schalter');
        }

        // Restliche Properties und Bildbehandlung
            $result['targetlink'] =  $this->ReadPropertyInteger('Target');
            $result['infofontsize'] =  $this->ReadPropertyFloat('InfoSchriftgroesse');
            $result['hintergrundfarbe'] =  '#' . sprintf('%06X', $this->ReadPropertyInteger('Kachelhintergrundfarbe'));
            $result['infoschriftfarbe'] =  '#' . sprintf('%06X', $this->ReadPropertyInteger('InfoSchriftfarbe'));
            $result['infomenueschriftfarbe'] =  '#' . sprintf('%06X', $this->ReadPropertyInteger('InfoMenueSchriftfarbe'));
            $result['infomenuefontsize'] =  $this->ReadPropertyFloat('InfoMenueSchriftgroesse');
            $result['infomenuetransparenz'] =  $this->ReadPropertyFloat('InfoMenueTransparenz');
            $result['infomenuehintergrundfarbe'] =  $this->GetColorRGB($this->ReadPropertyInteger('InfoMenueHintergrundfarbe'));
            $result['transparenz'] =  $this->ReadPropertyFloat('Bildtransparenz');
            $result['raumname'] =  $this->ReadPropertyString('Raumname');
            $result['raumnameschriftgroesse'] =  $this->ReadPropertyFloat('RaumnameSchriftgroesse');
            $result['raumnameschriftfarbe'] =  '#' . sprintf('%06X', $this->ReadPropertyInteger('RaumnameSchriftfarbe'));
            $result['schalter1altname'] =  $this->ReadPropertyString('Schalter1AltName');
            $result['schalter2altname'] =  $this->ReadPropertyString('Schalter2AltName');
            $result['schalter3altname'] =  $this->ReadPropertyString('Schalter3AltName');
            $result['schalter4altname'] =  $this->ReadPropertyString('Schalter4AltName');
            $result['schalter5altname'] =  $this->ReadPropertyString('Schalter5AltName');
            $result['info1altname'] =  $this->ReadPropertyString('Info1AltName');
            $result['info2altname'] =  $this->ReadPropertyString('Info2AltName');
            $result['info3altname'] =  $this->ReadPropertyString('Info3AltName');
            $result['info4altname'] =  $this->ReadPropertyString('Info4AltName');
            $result['info5altname'] =  $this->ReadPropertyString('Info5AltName');         
            $result['infolinksaltname'] =  $this->ReadPropertyString('InfoLinksAltName');
            $result['inforechtsaltname'] =  $this->ReadPropertyString('InfoRechtsAltName');    
            $result['infolinks2altname'] =  $this->ReadPropertyString('InfoLinks2AltName');
            $result['inforechts2altname'] =  $this->ReadPropertyString('InfoRechts2AltName');    
            $result['infomenueswitch'] =  $this->ReadPropertyBoolean('InfoMenueSwitch');   
            
            // Prüfe vorweg, ob ein Bild ausgewählt wurde
            $imageID = $this->ReadPropertyInteger('bgImage');
            if (IPS_MediaExists($imageID))
            {
                $image = IPS_GetMedia($imageID);
                if ($image['MediaType'] === MEDIATYPE_IMAGE)
                {
                    $imageFile = explode('.', $image['MediaFile']);
                    $imageContent = '';
                    switch (end($imageFile))
                    {
                    case 'bmp': $imageContent = 'data:image/bmp;base64,'; break;
                    case 'jpg': case 'jpeg': $imageContent = 'data:image/jpeg;base64,'; break;
                    case 'gif': $imageContent = 'data:image/gif;base64,'; break;
                    case 'png': $imageContent = 'data:image/png;base64,'; break;
                    case 'ico': $imageContent = 'data:image/x-icon;base64,'; break;
                    }
                    if ($imageContent)
                    {
                        $imageContent .= IPS_GetMediaContent($imageID);
                        $result['image1'] = $imageContent;
                    }
                }
            }
            else
            {
                $imageContent = 'data:image/png;base64,';
                $imageContent .= base64_encode(file_get_contents(__DIR__ . '/assets/placeholder.png'));
                $result['image1'] = $imageContent;
            }
        return json_encode($result);
    }

    private function _addVisualisationProperty(&$result, $propertyName, $propertyType) {
        $varID = $this->ReadPropertyInteger($propertyName);
        if (IPS_VariableExists($varID)) {
            $baseKey = strtolower($propertyName); // e.g., "infolinks", "schalter1"

            $result[$baseKey] = $this->CheckAndGetValueFormatted($propertyName);

            if ($this->ReadPropertyBoolean($propertyName . 'NameSwitch')) {
                $result[$baseKey . 'name'] = IPS_GetName($varID);
            }

            // GetIcon nur einmal aufrufen und Wert zwischenspeichern
            $iconValue = $this->GetIcon($varID, $this->ReadPropertyBoolean($propertyName . 'VarIconSwitch'));
            if ($this->ReadPropertyBoolean($propertyName . 'IconSwitch') && $iconValue !== "Transparent") {
                $result[$baseKey . 'icon'] = $iconValue;
            }

            if ($this->ReadPropertyBoolean($propertyName . 'AssoSwitch')) {
                $result[$baseKey . 'asso'] = $this->CheckAndGetValueFormatted($propertyName);
            }

            if ($propertyType === 'schalter') {
                $result[$baseKey . 'breite'] = $this->ReadPropertyFloat($propertyName . 'Breite');
                $result[$baseKey . 'color'] = $this->GetColor($varID);
            }
        }
    }
    private function CheckAndGetValueFormatted($property) {
        $id = $this->ReadPropertyInteger($property);
        if (IPS_VariableExists($id)) {
            return GetValueFormatted($id);
        }
        return false;
    }


    private function GetColor($id) {
        $variable = IPS_GetVariable($id);
        $Value = GetValue($id);
        $profile = $variable['VariableCustomProfile'] ?: $variable['VariableProfile'];

        if ($profile && IPS_VariableProfileExists($profile)) {
            $p = IPS_GetVariableProfile($profile);
            
            foreach ($p['Associations'] as $association) {
                if (isset($association['Value'], $association['Color']) && $association['Value'] == $Value) {
                    return $association['Color'] === -1 ? "" : sprintf('%06X', $association['Color']);
                    
                }
            }
        }
        return "";
    }


    private function GetColorRGB($hexcolor) {
        $transparenz = $this->ReadPropertyFloat('InfoMenueTransparenz');
        if($hexcolor != "-1")
        {
                $hexColor = sprintf('%06X', $hexcolor);
                // Prüft, ob der Hex-Farbwert gültig ist
                if (strlen($hexColor) == 6) {
                    $r = hexdec(substr($hexColor, 0, 2));
                    $g = hexdec(substr($hexColor, 2, 2));
                    $b = hexdec(substr($hexColor, 4, 2));
                    return "rgba($r, $g, $b, $transparenz)";
                } else {
                    // Fallback für ungültige Eingaben
                    return $hexColor;
                }
        }
        else {
            return "";
        }
    }

    private function GetIcon($id, $varicon) {
        $variable = IPS_GetVariable($id);
        $Value = GetValue($id);
        $icon = "";
        //Abfragen ob das Variablen-Icon oder das Profil-Icon verwendet werden soll
        if($varicon == true){
        $icon = IPS_GetObject($id);
        //print_r($icon); // Debug-Ausgabe
            if($icon['ObjectIcon'] != ""){
                $icon = $icon['ObjectIcon'];
            }
            else {
                $icon = "Transparent";
            }
        }
        else {
        // Profil-Icon abrufen
        $profile = $variable['VariableCustomProfile'] ?: $variable['VariableProfile'];
        $icon = "";

        if ($profile && IPS_VariableProfileExists($profile)) {
            $p = IPS_GetVariableProfile($profile);

            foreach ($p['Associations'] as $association) {
                if (isset($association['Value']) && $association['Icon'] != "" && $association['Value'] == $Value) {
                    $icon = $association['Icon'];
                    break;
                }
            }

            if ($icon == "" && isset($p['Icon']) && $p['Icon'] != "") {
                $icon = $p['Icon'];
            }

            if ($icon == "") {
                $icon = "Transparent";
            }
        }
        else {
            $icon = "Transparent";
        }
        
        }
        return $icon;
    }

}
