<?php

//get Building and device Ids
$ids = getArg('ids');

$tabIds = explode(":", $ids);

$buildingId = $tabIds[0];
$deviceId = $tabIds[1];

//get User and password
$userPass = getArg('userpass');

$tabUserPass = explode(":", $userPass);

$username = $tabUserPass[0];
$password = $tabUserPass[1];

//get parameters

$onoff = getArg('power', false, "");
$fanspeed = getArg('fanspeed', false, "");
$temperature = getArg('temperature', false, "");
$mode = getArg('mode', false, '');
/*
 * 1 => Heating
 * 8 => Auto
 * 7 => Fan
 * 2 => Drying
 * 3 => Cooling
 */

// session=reset : used to reset session for testing purpose
$resetSession = getArg('session', false, '');
if ($resetSession != "") {
    saveVariable('ContextKey', '');
}

//load session and try to get informations
$contextKey = loadVariable('ContextKey');
$headers = array("X-MitsContextKey: $contextKey", "Content-Type: application/json");

$json = httpQuery("https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/Get?id=$deviceId&buildingID=$buildingId", "GET", "", "", $headers, false);
$device = sdk_json_decode($json);

if (count($device) == 2) {//if no session only 2 datas are raised : { Success: false, ErrorMessage: ""}
    echo "need to reco<br>";

    $headers = array("Content-Type: application/json");
    $jsonTest = '{Email: "' . $username . '", Password: "' . $password . '", Language: 7, AppVersion: "1.15.3.0", Persist: true}';

    $test = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Login/ClientLogin', 'POST', $jsonTest, '', $headers, false);

    $loginResult = sdk_json_decode($test);

    if ($loginResult['ErrorId'] == null) {
        saveVariable('ContextKey', $loginResult['LoginData']['ContextKey']);//   echo "----1--->".$loginResult['LoginData']['ContextKey']."<----"."<br>";

        $contextKey = loadVariable('ContextKey');
        echo "ContextKey : ---->$contextKey<------";
        $headers = array("X-MitsContextKey: $contextKey", "Content-Type: application/json");
        $json = httpQuery("https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/Get?id=$deviceId&buildingID=$buildingId", "GET", "", "", $headers, false);
        $device = sdk_json_decode($json);
    } else
        echo "Erreur de login " . "<br>";//TODO : handle exceptions*/
}

$resultat = "";

if ($onoff != "") {

    $aremplacer = array('"Power":false', '"Power":true');
    $json = str_replace($aremplacer, '"Power":' . $onoff, $json);

    $aremplacer = '"EffectiveFlags":' . $device['EffectiveFlags'];
    $json = str_replace($aremplacer, '"EffectiveFlags":1', $json);

    $aremplacer = '"HasPendingCommand":false';
    $json = str_replace($aremplacer, '"HasPendingCommand":true', $json);

    $json = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/SetAta', 'POST', $json, '', $headers, false);


} else if ($fanspeed != "") {

    $aremplacer = '"SetFanSpeed":' . $device['SetFanSpeed'];
    $json = str_replace($aremplacer, '"SetFanSpeed":' . $fanspeed, $json);

    $aremplacer = '"EffectiveFlags":' . $device['EffectiveFlags'];
    $json = str_replace($aremplacer, '"EffectiveFlags":8', $json);

    $aremplacer = '"HasPendingCommand":false';
    $json = str_replace($aremplacer, '"HasPendingCommand":true', $json);

    $json = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/SetAta', 'POST', $json, '', $headers, false);

} else if ($temperature != "") {

    $aremplacer = '"SetTemperature":' . $device['SetTemperature'];
    $json = str_replace($aremplacer, '"SetTemperature":' . $temperature, $json);

    $aremplacer = '"EffectiveFlags":' . $device['EffectiveFlags'];
    $json = str_replace($aremplacer, '"EffectiveFlags":4', $json);

    $aremplacer = '"HasPendingCommand":false';
    $json = str_replace($aremplacer, '"HasPendingCommand":true', $json);

    $json = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/SetAta', 'POST', $json, '', $headers, false);
} else if ($mode != "") {

    $aremplacer = '"OperationMode":' . $device['OperationMode'];
    $json = str_replace($aremplacer, '"OperationMode":' . $mode, $json);

    $aremplacer = '"EffectiveFlags":' . $device['EffectiveFlags'];
    $json = str_replace($aremplacer, '"EffectiveFlags":6', $json);

    $aremplacer = '"HasPendingCommand":false';
    $json = str_replace($aremplacer, '"HasPendingCommand":true', $json);

    $json = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/SetAta', 'POST', $json, '', $headers, false);

}

echo jsonToXML($json);
