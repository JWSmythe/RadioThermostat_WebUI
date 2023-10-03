<?php 
// This is the combined poller.  The individual pollers all hit the API once each, 
// and since the thermstat is single threaded, it lagged and possibly 
// would crash the thermostat.

require_once("tstat_globals.php");

// Enable various modes.
$enable_datalog = 1; 
$enable_datadb  = 1; 
$enable_mqtt   	= 1; 
$enable_mrtg 	= 0; 	// MRTG gets it's own file.  MRTG calls the script, and it's STDOUT is used as input.

$ts = time(); 				// Timstamp entry for logfile and filetag.
$filetag = date('Ym', $ts);	// Get year and month, to make monthly logfiles.  These can get big. 
$req_url = $base_url ."tstat_api_gw.php/tstat";
#$req = file_get_contents($req_url);
#$data = json_decode($req,1);

// Predefine to prevent warning. on while loop below.
$data['t_target'] = 0;

// Set a target temperature, regardless of the mode.
// Sometimes this was returning 0.  Retry until there's a valid response.
$count = 0; 
while($data['t_target'] < 1){
	$count++;
	if ($count > 10){
		print "10 tries failed.  Aborting.\n";
		exit;
	};
	print "$count Requesting data\n";
	// re-request data. 
	$req = file_get_contents($req_url);
	$data = json_decode($req,1);

	if (isset($data['t_cool'])){
	   $data['t_target'] = $data['t_cool'];
	}elseif(isset($data['t_heat'])){
	   $data['t_target'] = $data['t_heat'];
	}else{
	   $data['t_target'] = "0";
	};
	sleep(1);
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
print "Actual Temperature <b>" . $data['temp'] . "</b><br>\n";
print "Target Temperature <b>" . $data['t_target'] . "</b><br>\n";
print "Running T-State <b>" . $data['tstate'] . "</b><br>\n";
print "Running State <b>" . $data['state'] . "</b><br>\n";


if($enable_datalog == 1){
	$out = $ts . ", " . $data['temp'] . ", " . $data['t_target'] . ", " . $data['tstate'] . ", " . $data['state'] . "\n";
	print "Writing to log" . $datalog . "_" . $filetag ."<br>\ndata: <br>\n$out<br>\n";
	file_put_contents($datalog . "_" . $filetag . ".csv", $out, FILE_APPEND); 
};

if($enable_datadb == 1){
	if (!file_exists($datadb)){
		print "DB file $datadb missing.  Creating new DB<br>\n";
		$db = new SQLite3($datadb);
		$query = "
		CREATE TABLE data
			(
				ID INTEGER PRIMARY KEY,
				ts INT NOT NULL,
				t_actual REAL NOT NULL, 
				t_target FLOAT NOT NULL, 
				i_mode   INT NOT NULL, 
				s_mode   VARCHAR(8)
			);
		CREATE INDEX ts_idx ON data(ts);
		";
		$return = $db->exec($query);
			if(!$return){
				print $db->lastErrorMsg();
			}else{
				print "Table created successfully\n";
			}
		$db->close();
	};

	$db = new SQLite3($datadb);
	   if(!$db) {
		  echo $db->lastErrorMsg();
	   } else {
		  echo "Opened database successfully\n";
	   }

	$query = "
		INSERT INTO data 
		(ts, t_actual, t_target, i_mode, s_mode)
		VALUES
		(" . $ts  . ", " . $data['temp'] . ", " . $data['t_target'] . ", " . $data['tstate'] . ", \"" . $data['state'] . "\");
	";
		
	print "QUERY\n$query\n";

	$return = $db->exec($query);
		if(!$return){
			print $db->lastErrorMsg();
		}else{
			print "Record inserted successfully.\n";
		}
	$db->close();	   
};

if($enable_mqtt == 1){
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

		// Data messages
		$mqtt->publish($mqtt_topic."/sensor/temperature/state",$data['temp'], 0,1);
		$mqtt->publish($mqtt_topic."/sensor/target/state",$data['t_target'], 0,1);
		$mqtt->publish($mqtt_topic."/sensor/tstate/state",$data['tstate'], 0, 1);
		$mqtt->publish($mqtt_topic."/sensor/state/state",$data['state'], 0, 1);
		$mqtt->publish($mqtt_topic."/status","online", 0, 1);

		$mqtt->close();
	}else{
		echo "Fail or time out\n";
	}
};

?>