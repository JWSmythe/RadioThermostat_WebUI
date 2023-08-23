<?php 
//
// This will show all the info available from the thermostat's API.  

require_once("tstat_globals.php");
//$host_name = "192.168.1.101"; // change this to your thermostat's IP address


function get_info($key){
   global $host_name;
   global $host_port;
   global $info;
   $ch = curl_init("$host_name"."$key");
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
   $response =  curl_exec($ch);
   curl_close($ch);
   $info[$key] = json_decode($response, true);
   // If you're going to screen shot the results to share, you may want to 
   // mask the bssid and ssid.  There are sites that use net stumbling data, 
   // to show where any SSID might be.  As I've found, they're scary complete.
   
   if(isset($info[$key]['bssid'])){
      $info[$key]['bssid'] = "[REDACTED]";
   }
   if(isset($info[$key]['ssid'])){
      $info[$key]['ssid'] = "[REDACTED]";
   } 
     
   // Either show info on each request, or show the full $info array at the
   // end. If your PHP max runtime is less than 5 minutes, it will fail before
   // it has a chance to print.
   //show_info($key);
   flush();
   return(true);
}

function show_info($key){
   global $info;
   print "--------------------<br>
          Info ($key)
          --------------------<br>
          <PRE>" . print_r($info[$key], true) . "</PRE>
          --------------------<br>\n";
   flush();
};

print "<TABLE BORDER=1>\n";
print "<TR><TD>Key</TD><TD>GET</TD><TD>POST</TD></TR>\n";

// $info[sys/services][httpd_handlers] lists out all the available services.
// Each has an array under it.  

// The first column is if it supports GET.
// The second is if it expects POST.
get_info("/sys/services");

foreach($info['/sys/services']['httpd_handlers'] as $this_key => $this_val){
   if ($info['/sys/services']['httpd_handlers'][$this_key][0] == 1){
      $GET = 1;
   }else{
      $GET = 0;
   };

   if ($info['/sys/services']['httpd_handlers'][$this_key][0] == 1){
      $POST = 1;
   }else{
      $POST = 0;
   };
      
   if ($info['/sys/services']['httpd_handlers'][$this_key][1] == 1){
      get_info($this_key);
   }

   print "<TR><TD>$this_key</TD><TD>$GET</TD><TD>$POST</TD></TR>\n";
};
print "</TABLE>";

// Other keys have more information.  You may or may not need to include it
// yourself.  Reference the API manual (the PDF) for more information on 
// what keys are available.  If you do a key multiple times, it'll just
// update the $info array.  It will show fine, but takes longer to acquire.
// The API isn't really recursive, so some keys have to be requested manually.
get_info("/sys");
//get_info("/sys/services"); // already run above.
get_info("/tstat");
get_info("/sys/name");
get_info("/sys/network");
get_info("/tstat/model");
get_info("/tstat/remote_temp");
get_info("/tstat/lock");
get_info("/tstat/simple_mode");
get_info("/tstat/save_energy");
get_info("/tstat/tswing");
get_info("/tstat/cool");
get_info("/tstat/heat");
get_info("/tstat/stage_delay");
get_info("/tstat/fan_ctime");
get_info("/tstat/humidity");
get_info("/tstat/thumidity");
get_info("/tstat/humidifier");
get_info("/tstat/time/format");
get_info("/tstat/air_baffle");
get_info("/tstat/hvac_settings");

// These are programming modes.  This is for referencing, we aren't going to 
// try to show this here. 
get_info("/tstat/program");
get_info("/tstat/program/cool");
get_info("/tstat/program/heat");
/*  // These are all reported from the above cool and heat lines.
get_info("/tstat/program/cool/0");  // Mon
get_info("/tstat/program/cool/1");  // Tue
get_info("/tstat/program/cool/2");  // Wed
get_info("/tstat/program/cool/3");  // Thu
get_info("/tstat/program/cool/4");  // Fri
get_info("/tstat/program/cool/5");  // Sat
get_info("/tstat/program/cool/6");  // Sun
get_info("/tstat/program/heat/0");
get_info("/tstat/program/heat/1");
get_info("/tstat/program/heat/2");
get_info("/tstat/program/heat/3");
get_info("/tstat/program/heat/4");
get_info("/tstat/program/heat/5");
get_info("/tstat/program/heat/6");
*/


// These don't work on my CT50 V1.94 FW 1.04.84
//get_info("/tstat/led");
//get_info("/tstat/tstat/pma"); // This is a write only to put a message on the thermostat's LCD.  uma works on the CT80.
//get_info("/tstat/energy_led");
//get_info("/tstat/night_light");
//get_info("/tstat/ext_dehumidifier");   // CT80 only

// If we get this far, show the whole array.  It has been timing out, so 
// I'm showing each key as it's acquired.  This is just the full sorted summary.
ksort($info);

print "--------------------<br>
      Full API Info
      --------------------<br>
      <PRE>" . print_r($info, true) . "</PRE>
      --------------------<br>\n";
flush();

?>