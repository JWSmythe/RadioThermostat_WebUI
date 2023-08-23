<?php
// Thermostat Poll for MRTG - v1.0
//
// This script basically polls the thermostat, and makes the data 
// available for MRTG, MQTT, or whatever.  It is intended to be 
// called by a cron or scheduled event.

//https://fw.jwsmythe.com/thermostat/tstat_api_gw.php/tstat
require_once("tstat_globals.php");

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
//print "Running T-State <b>" . $data['tstate'] . "</b><br>";
//print "Running State <b>" . $data['state'] . "</b><br>";
print $data['temp'] . "\n";      // The actual temperature
print $data['t_target'] . "\n";  // The target temperature, ignoring mode.
print $data['tstate'] . "\n";    // Numeric state 0=off 1=heat 2=cool 
#print $data['state'] . "\n";     // Word state off|heat|cool
?>