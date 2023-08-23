<?php
// Thermostat API gateway - v1.0
//
// Because of security reasons, browser don't let javascript to access other
// servers, unless both sides have CORS enabled.  This is a simple PHP script
// to act as a gateway.  It will accept a GET or POST request, and pass them
// along as necessary.

// Sample session with headers
/* 
   # telnet 192.168.1.101 80
   Trying 192.168.1.101...
   Connected to 192.168.1.101.
   Escape character is '^]'.
   GET /tstat

   HTTP/1.1 200 OK
   Server: Marvell 8688WM
   Connection: close
   Transfer-Encoding: chunked
   Content-Type: application/json
   Cache-Control: no-store, no-cache, must-revalidate
   Cache-Control: post-check=0, pre-check=0
   Pragma: no-cache

   94
   {"temp":74.00,"tmode":2,"fmode":0,"override":0,"hold":0,"t_cool":72.00,"tstate":2,"fstate":1,"time":{"day":5,"hour":11,"minute":46},"t_type_post":0}
   0
*/

require_once("tstat_globals.php");
$ts = date('Y.m.d H:i:s');

if (isset($_SERVER['PATH_INFO'])) {
  $path = $_SERVER['PATH_INFO'];
} else {
  $path = '';
}

$url = "http://$host_name$path";
if (isset($_SERVER['HTTP_USER_AGENT'])){
   $UA = $_SERVER['HTTP_USER_AGENT'];
}else{
   $UA = "browser 1.0";
};
$url = $host_name . $path;

header("Content-Type: application/json");

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $postData = file_get_contents('php://input');
  curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
  curl_setopt($ch, CURLOPT_POST, 1);
}

$response = curl_exec($ch);
print $response;

curl_close($ch);

// If you want some debugging info, uncomment this block.
/* 
$log = "-----\n";
$log .= "$ts\n";
$log .= "PATH: $path\n";
$log .= "URL: $url\n";
$log .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $log .= "POST: \n";
  $log .= $postData . "\n";
}

$log .= "GET:\n";
$log .= print_r($_GET, TRUE);
$log .= "Response:\n";
$log .= print_r($response, TRUE);
$log .= "\n-----\n";


file_put_contents("logfile.txt", $log, FILE_APPEND);
file_put_contents("logfile.txt", "\n", FILE_APPEND);
*/ 
?>