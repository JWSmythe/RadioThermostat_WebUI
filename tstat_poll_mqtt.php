<?php
// Thermostat Poll for MRTG - v1.0
//
// This script basically polls the thermostat, and makes the data 
// available for MRTG, MQTT, or whatever.  It is intended to be 
// called by a cron or scheduled event.

//https://fw.jwsmythe.com/thermostat/tstat_api_gw.php/tstat
// Using https://github.com/php-mqtt/client for MQTT communications.
require_once("tstat_globals.php");
require("phpMQTT.php"); // For MQTT

use PhpMqtt\Client\Examples\Shared\SimpleLogger;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use Psr\Log\LogLevel;


// Get the hardware ID.  We need it for the MQTT stuff.
$req_url = $base_url ."tstat_api_gw.php/sys";
$req = file_get_contents($req_url);
$data = json_decode($req,1);

$this_uuid = $data['uuid'];

#print "UUID: $this_uuid\n";

// Poll thermostat for current stats.
$req_url = $base_url ."tstat_api_gw.php/tstat";
$req = file_get_contents($req_url);

$data = json_decode($req,1);

// Set a target temperature, regardless of the mode.
if (isset($data['t_cool'])){
   $data['t_target'] = $data['t_cool'];
}elseif(isset($data['t_heat'])){
   $data['t_target'] = $data['t_heat'];
}else{
   $data['t_target'] = "0";
};

// Get if the AC/heat is actually running or cycled off. 
if ($data['tstate'] == 0){
   $data['state'] = "off";
}elseif($data['tstate'] == 1){
   $data['state'] = "heat";
}elseif($data['tstate'] == 2){
   $data['state'] = "cool";
}else{
   $data['state'] = "unknown";
};

//print "URL Requested: $req_url<br>";
//print "<PRE>" . print_r($data, TRUE) . "</PRE>";
//print "Actual Temperature <b>" . $data['temp'] . "</b><br>";
//print "Target Temperature <b>" . $data['t_target'] . "</b><br>";
//print "Running State <b>" . $data['state'] . "</b><br>";
//print $data['temp'] . "\n";      // The actual temperature
//print $data['t_target'] . "\n";  // The target temperature, ignoring mode.
//print $data['tstate'] . "\n";    // Numeric state 0=off 1=heat 2=cool 
//print $data['state'] . "\n";     // Word state off heat cool

$mqtt = new bluerhinos\phpMQTT($mqtt_server, $mqtt_port, $mqtt_clientId);

if ($mqtt->connect(true,NULL,$mqtt_user,$mqtt_pass)) {
   
   // Discovery
   // Send discovery MQTT message.  https://www.home-assistant.io/integrations/mqtt/#discovery-messages
   // We should be sending 4 of these, for the 4 topics available.   This needs more work.
   // Discovery - Temperature 
   $discovery_topic = "homeassistant/sensor/$mqtt_topic/temperature/config";
  
   $discovery_payload = "
   {
      \"dev_cla\": \"temperature\",
      \"unit_of_meas\": \"°F\",
      \"stat_cla\": \"measurement\",
      \"name\": \"temperature\",
      \"ic\": \"mdi:thermometer\",
      \"stat_t\": \"$mqtt_topic/sensor/temperature/state\",
      \"uniq_id\": \"$mqtt_topic" . "_temperature\",
      \"dev\": {
            \"ids\": \"$this_uuid\",
            \"name\": \"$mqtt_topic\",
            \"sw\": \"RadioThermostat_JWSmythe_PHP_v1.0\",
            \"mdl\": \"CT50\",
            \"mf\": \"RadioThermostat\"
         }
   }
   ";
   
   print "
      Discovery\n
      Topic: $discovery_topic
      Payload: $discovery_payload
   ";
   $mqtt->publish($discovery_topic,$discovery_payload, 0, 1);

/*
   // Discovery - Target Temperature

   $discovery_topic = "homeassistant/sensor/$mqtt_topic/target_temperature/config";
   // Discovery Target Temperature
   $discovery_payload = "
   {
      \"dev_cla\": \"temperature\",
      \"unit_of_meas\": \"°F\",
      \"stat_cla\": \"measurement\",
      \"name\": \"target_temperature\",
      \"ic\": \"mdi:thermometer\",
      \"stat_t\": \"$mqtt_topic/sensor/target_temperature/state\",
      \"uniq_id\": \"$mqtt_topic" . "_target_temperature\",
      \"dev\": {
            \"ids\": \"$this_uuid\",
            \"name\": \"$mqtt_topic\",
            \"sw\": \"RadioThermostat_JWSmythe_PHP_v1.0\",
            \"mdl\": \"CT50\",
            \"mf\": \"RadioThermostat\"
         }
   }
   ";
   
   $mqtt->publish($discovery_topic,$discovery_payload, 0, 1);
*/

   // Data messages

   $mqtt->publish($mqtt_topic."/sensor/temperature/state",$data['temp'], 0,1);
   $mqtt->publish($mqtt_topic."/sensor/target/state",$data['t_target'], 0,1);
   $mqtt->publish($mqtt_topic."/sensor/tstate/state",$data['tstate'], 0, 1);
   $mqtt->publish($mqtt_topic."/sensor/state/state",$data['state'], 0, 1);
   $mqtt->publish($mqtt_topic."/status","online", 0, 1);


   $mqtt->close();
}else{
  echo "Fail or time out
";
}


?>