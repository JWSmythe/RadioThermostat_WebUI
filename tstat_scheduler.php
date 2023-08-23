<html>
<head>
   <title>Radio Thermostat Thermostat</title>
</head>
<body>
   <style>
      body {
         background-color: #f0f0f0;
         font-family: Arial, Helvetica, sans-serif;
      }
      a {
         color: inherit; 
         text-decoration: inherit;
      }

      table {
         // border-collapse: collapse; 
      }

      tr:nth-child(even) {background-color: #ccccf2;}
      tr:nth-child(odd) {background-color: #ccf2cc;}
   </style>

<?php
require_once("tstat_globals.php");
//$host_name = "192.168.1.101";
$days = array("Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun");

if (isset($_REQUEST['mode'])){
   if($_REQUEST['mode'] == "cool"){
      $mode = "cool";
   }elseif($_REQUEST['mode'] == "heat"){
      $mode = "heat";
   }else{
      $mode = "Unknown";
   };
}else{
   $mode = "cool";   // Just default to cooling mode.   That's more likely in my region.
};


if (isset($_REQUEST['action'])){
   // ===== Acquire from POST, and store to thermostat ==========================
   if ($_POST['action'] == "Update Schedule"){
      //print "<HR>POST:<PRE>" . print_r($_POST, true) . "</PRE><HR>";

      $output = "{";
      for ($day = 0; $day < 7; $day++){
         $output .= "\"$day\":[";
         for ($slot = 0; $slot < 4; $slot++){
            $time = $_POST["Time_" . $day . "_" . $slot];
            $time_m = time_to_min($time);
            $temp = $_POST["Temp_" . $day . "_" . $slot];
            $output .= "$time_m,$temp";
            if ($slot < 3){
               $output .= ",";
            }
         }
         if ($day < 6){
            $output .= "],";
         }else{
            $output .= "]";
         }
      }

      $output .= "}";

      //print "<HR>UPDATER OUTPUT:<br>\n<PRE>$output</PRE><HR>";
      post_info("/tstat/program/$mode", $output);
   };
   //foreach($_POST as $key => $val){
   //   print "KEY $key => VAL $val<BR>";
   //};
};

// ===== Import the data from the thermostat and show the form. ==============
print "<form action='" . $_SERVER['PHP_SELF'] ."' method='post'>";
print "<table>";
$info = array();
get_info("/tstat/program/$mode");

//print "SCHEDULE<HR><PRE>" . print_r($info['/tstat/program/cool'], TRUE) . "</PRE><HR>";

// header row
print "<tr>";
      print "<td></td>";
   foreach ($days as $day) {
      // We need two cells wide for the time and temp.
      print "<td colspan='2'>$day</td>";
   }
print "</tr>";

print "
<tr style='font-size: 12px;'><td></td>";
for ($i = 0 ; $i < 7 ; $i++){
      print "<td>Time</td><td>Temp</td>";
}
print "
</tr>
";

   for ($slot = 0 ; $slot < 4 ; $slot++){
      print "<tr>";
         print "<td>Slot $slot</td>";

         $this_time_slot = $slot * 2;
         $this_temp_slot = $this_time_slot + 1;

         foreach ($days as $day){
            //$day_num = date('w');
            $day_num = date('w', strtotime($day)) - 1;

            if ($day == "Sun"){
               $day_num = 6;
            }
           
            print "
            <td>
               <input type='text' name='Time.$day_num.$slot' size='2' value='" . min_to_time($info['/tstat/program/' . $mode][$day_num][$this_time_slot]) . "'> 
            </td>";
            //             //Time.$day_num.$slot
            print "<td>
               <input type='text' name='Temp.$day_num.$slot' size='1' value='" . $info['/tstat/program/' . $mode][$day_num][$this_temp_slot] . "'>
            </td>";
            // Temp.$day_num.$slot
         };
      print "</tr>";
   };
   print "<tr><td colspan='7'>Current Mode: $mode | Set Mode:";
   print "
   <a href='" . $_SERVER['PHP_SELF'] . "?mode=cool'>[ Cool ]</a> |
   <a href='" . $_SERVER['PHP_SELF'] . "?mode=heat'>[ Heat ]</a> 
   ";

print "</td>";
print "<td colspan='8'><input type='submit' name='action' value='Update Schedule'></td></tr>";
print "</table>";
print "</form>";

print "
<table>
   <tr>
      <td>
         <a href='tstat_main.html'>[Thermostat]</a>
         <a href='tstat_info.php'>[API Info]</a>
         <a href='tstat_scheduler.php'>[Schedule]</a>
         <a href='tstat_api_gw.php'>[API Gateway]</a>
      </td>
   </tr>
</table>
<ul>
<li>There are 4 programmable time slots per day.  The times can be adjusted as desired. 
<li>Each day has two columns, one for the time and one for the temperature of each slot per day.
<li>Set all time and temperature pairs, and hit Update to save the schedule.
</ul>
";

// ===== BEGIN PHP FUNCTIONS =================================================

function get_info($key){
   global $host_name;
   global $host_port;
   global $info;
   $ch = curl_init("$host_name"."$key");
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
   $response =  curl_exec($ch);
   //print "<HR>Thermostat Stored Settings RESPONSE:<HR><PRE>" . print_r($response, true) . "</PRE><HR>";
   curl_close($ch);
   $info[$key] = json_decode($response, true);
   if(isset($info[$key]['bssid'])){
      $info[$key]['bssid'] = "[REDACTED]";
   }
   if(isset($info[$key]['ssid'])){
      $info[$key]['ssid'] = "[REDACTED]";
   }      
   return(true);
}

function post_info($key, $data){
   global $host_name;
   global $host_port;
   global $info;
   $ch = curl_init("$host_name"."$key");
   $postData = $data;
   curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
   curl_setopt($ch, CURLOPT_POST, 1); 
   $response = curl_exec($ch);
   print $response;  
   curl_close($ch);
   return(true);
};

// PHP function to convert minutes since midnight to a 24 hour time string 
function min_to_time($min){
   $hour = floor($min / 60);
   $min = $min % 60;
   return sprintf("%02d:%02d", $hour, $min);
}

// PHP function to convert 24 hour time string to minutes since midnight.
function time_to_min($time){
   $time = trim($time);
   $hour = substr($time, 0, 2);
   $min = substr($time, 3, 2);
   $ampm = substr($time, 6, 2);
   if ($ampm == "PM"){
      $hour = $hour + 12;
   }
   return $hour * 60 + $min;
}

// ===== END PHP FUNCTIONS ===================================================

?>
   <script>

// ===== BEGIN JS FUNCTIONS ==================================================
function read_schedule_cool() {
   fetch('tstat_api_gw.php/tstat/program/<?php print $mode; ?>')
      .then(response => response.json())
      .then(data => {
         //console.log(data);
         //document.getElementById('disp_temp').innerHTML = data.temp;
         document.querySelector("disp_temp").innerHTML = data.temp;


         if(data.tmode == 0){
            document.querySelector("hvac_mode").innerHTML = "Off";
         }else if(data.tmode == 1 ){
            document.querySelector("hvac_mode").innerHTML = "Heat";
         }else if(data.tmode == 2){
            document.querySelector("hvac_mode").innerHTML = "Cool";
         }else if(data.tmode == 3){
            document.querySelector("hvac_mode").innerHTML = "Auto";
         }else{
            document.querySelector("hvac_mode").innerHTML = "Unknown";
         }

      });
   }
</script>