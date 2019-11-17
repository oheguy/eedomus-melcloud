<?php


/**
 * Build context header
 *
 * */
function sdk_getHeader(){
    $contextKey = loadVariable('ContextKey');
    return array("X-MitsContextKey: $contextKey", "Content-Type: application/json");
}

/**
 * Get Building Id and DeviceId from deviceName
 * DeviceNme could be full name or serial number recored in melcloud
 *
 * */
function sdk_setIds($moduleId, $deviceName, $username, $password){

    //echo "---------------------------------------------------------sdk_setIds<br>";
    $json = httpQuery("https://app.melcloud.com/Mitsubishi.Wifi.Client/User/ListDevices", "GET", "", "", sdk_getHeader(), false);
    $result = sdk_json_decode($json);

    if (isset($result['Success'])) {
        sdk_connect($username, $password);
        $json = httpQuery("https://app.melcloud.com/Mitsubishi.Wifi.Client/User/ListDevices", "GET", "", "", sdk_getHeader(), false);
        $result = sdk_json_decode($json);
    }

    $devices = $result[0]['Structure']['Devices'];


    foreach ($devices as $device){
        if ($device['DeviceName'] == $deviceName || $device['SerialNumber'] == $deviceName){
            saveVariable($moduleId.'-buildingId', $device['BuildingID']);
            saveVariable($moduleId.'-deviceId', $device['DeviceID']);

        }
    }

}

/**
 * Log and set ContextId
 *
 * */
function sdk_connect( $username, $password ){

    //echo "---------------------------------------------------------sdk_connect<br>";
    $headers = array("Content-Type: application/json");
    $jsonTest = '{Email: "' . $username . '", Password: "' . $password . '", Language: 7, AppVersion: "1.15.3.0", Persist: true}';

    $relogin = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Login/ClientLogin', 'POST', $jsonTest, '', sdk_getHeader(), false);

    //echo $relogin."<br>";
    $loginResult = sdk_json_decode($relogin);

    if ($loginResult['ErrorId'] == null) {
        $contextKey = $loginResult['LoginData']['ContextKey'];
        saveVariable('ContextKey', $contextKey);
    } else
        echo "Erreur de login " . "<br>";//TODO : handle exceptions*/

}

/**
 * Get informations from MelCloud
 * rebuild context if needed
 * rebuild buildingId and DeviceId if needed
 *
 * */
function sdk_get($moduleId, $deviceName, $username, $password){

    //echo "---------------------------------------------------------sdk_get<br>";
    //if no contextKey reconnect
    $contextKey = loadVariable('ContextKey');
    if ($contextKey == '')
        sdk_connect($username, $password);

    //get building ids
    $buildingId = loadVariable($moduleId.'-buildingId');
    $deviceId = loadVariable($moduleId.'-deviceId');
    if ($buildingId == ''){
        sdk_setIds($moduleId, $deviceName, $username, $password);
        $buildingId = loadVariable($moduleId.'-buildingId');
        $deviceId = loadVariable($moduleId.'-deviceId');
    }

    $json = httpQuery("https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/Get?id=$deviceId&buildingID=$buildingId", "GET", "", "", sdk_getHeader(), false);

    $result = sdk_json_decode($json);

    //if (count($device) == 2) {//if no session only 2 datas are raised : { Success: false, ErrorMessage: ""}
    if (isset($result['Success']) &&  $result['Success'] == false) {
        sdk_connect($username, $password);
        $json = httpQuery("https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/Get?id=$deviceId&buildingID=$buildingId", "GET", "", "", sdk_getHeader(), false);
    }

    return $json;

}

//get devicename
$deviceName = getArg('deviceName');


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

$moduleId =  getArg('eedomus_controller_module_id');

/**
 * Session managing
 *
 * off => nothing recorded
 * contextOnly => buildingid and contextid not recorded
 * idsOnly => contextKey not recorded
 * azzerty => change contextKey to defaut value to test timeouts
 *
 */

$resetSession = getArg('session', false, '');
if ($resetSession == "off") {
    saveVariable('ContextKey', '');
    saveVariable($moduleId.'-buildingId', '');
    saveVariable($moduleId.'-deviceId', '');

}else if ($resetSession == "contextOnly") {
    saveVariable($moduleId.'-buildingId', '');
    saveVariable($moduleId.'-deviceId', '');

} else if ($resetSession == "idsOnly") {
    saveVariable('ContextKey', '');

    sdk_connect($username, $password);
} else if ($resetSession == "azerty") {
    saveVariable('ContextKey', 'azerty');
}

$json = sdk_get($moduleId, $deviceName, $username, $password);


$device = sdk_json_decode($json);
$effectiveFlag = 0;

$resultat = "";

if ($onoff != "") {

    $aremplacer = array('"Power":false', '"Power":true');
    $json = str_replace($aremplacer, '"Power":' . $onoff, $json);

    $effectiveFlag += 1;

}
if ($fanspeed != "") {

    $aremplacer = '"SetFanSpeed":' . $device['SetFanSpeed'];
    $json = str_replace($aremplacer, '"SetFanSpeed":' . $fanspeed, $json);

    $effectiveFlag += 8;

}
if ($temperature != "") {

    $aremplacer = '"SetTemperature":' . $device['SetTemperature'];
    $json = str_replace($aremplacer, '"SetTemperature":' . $temperature, $json);

    $effectiveFlag += 4;

}
if ($mode != "") {

    $aremplacer = '"OperationMode":' . $device['OperationMode'];
    $json = str_replace($aremplacer, '"OperationMode":' . $mode, $json);

    $effectiveFlag += 2;

}

$aremplacer = '"EffectiveFlags":' . $device['EffectiveFlags'];
$json = str_replace($aremplacer, '"EffectiveFlags":'.$effectiveFlag, $json);

$aremplacer = '"HasPendingCommand":false';
$json = str_replace($aremplacer, '"HasPendingCommand":true', $json);

$json = httpQuery('https://app.melcloud.com/Mitsubishi.Wifi.Client/Device/SetAta', 'POST', $json, '', sdk_getHeader(), false);

echo jsonToXML($json);

