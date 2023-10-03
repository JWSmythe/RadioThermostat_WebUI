<?php

// This file holds global variables, so you only need to set it once. 
// What is the hostname or IP of your thermostat?  Set it here. 
// On mine, if it gets the IP via DHCP, the hostname works.  With a static 
// IP, I have to call it by IP.  If you set the IP in DNS somewhere, you can 
// use that hostname.
//$host_name = "192.168.1.128";  // Static IP
//$host_name = "thermostat-DF-BA-C9"; // Hostname
$host_name = "192.168.1.20";

// change the /thermostat/ part to whatever your local URL is.  
// For example, if your main page is http://localhost/thermostat/tstat_main.html , 
// you would set "/thermostat/";
$base_path = "/thermostat/";

// This makes that into the fully qualified URL.  No need to edit this.
//$base_url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $base_path;
// The cron needs the URL to get to our own pages.
$base_url = "https://fw.jwsmythe.com/$base_path";

// A CSV log containing temperature samples for graphing.    This gets yyyymm.csv appended to it.
$datalog = dirname(__FILE__) . "/data/tstat_datalog";
$datadb  = dirname(__FILE__) . "/data/tstat_datadb.sqlite";

// MQTT information.  Only used by optional tstat_poll_mqtt.php
$mqtt_server   = "192.168.1.10";
$mqtt_port     = "1883";
$mqtt_clientId = "CT50 Thermostat";
$mqtt_topic    = "CT50-Thermostat";
$mqtt_user     = "YOUR_MQTT_USER";
$mqtt_pass     = "YOUR_MQTT_PASSWORD";
$mqtt_version  = "MqqtClient::MQTT_3_1_1";

?>