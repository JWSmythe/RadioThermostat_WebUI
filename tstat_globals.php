<?php

// This file holds global variables, so you only need to set it once. 
// What is the hostname or IP of your thermostat?  Set it here. 
$host_name = "192.168.1.101";
// change the /thermostat/ part to whatever your local URL is.  
// For example, if your main page is http://localhost/thermostat/tstat_main.html , 
// you would set "/thermostat/";
$base_path = "/thermostat/";

// This makes that into the fully qualified URL.  No need to edit this.
//$base_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $base_path;
// The cron needs the URL to get to our own pages.
$base_url = "https://firewall.local/$base_path";

// MQTT information.  Only used by optional tstat_poll_mqtt.php
$mqtt_server   = "YOUR_MQTT_SERVER";
$mqtt_port     = "1883";
$mqtt_clientId = "CT50 Thermostat";
$mqtt_topic    = "CT50-Thermostat";
$mqtt_user     = "YOUR_MQTT_USER";
$mqtt_pass     = "YOUR_MQTT_PASSWORD";
$mqtt_version  = "MqqtClient::MQTT_3_1_1";

?>
