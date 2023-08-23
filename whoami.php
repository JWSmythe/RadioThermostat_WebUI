<?php

require_once("tstat_globals.php");
$page_name = 'WhoAmI';
//include_once("header.php");

$ip = $_SERVER['REMOTE_ADDR'];
$hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);

print "BASE_URL = $base_url<br>"; // from tstat_globals.php
/*
$path_parts = pathinfo($_SERVER['REQUEST_URI']);
$path_key = urldecode($path_parts['filename']);
$path_key =preg_replace( '/[^A-Za-z0-9]/', '', $path_key);
$_SERVER['PATH_KEY'] = $path_key;

if (
      ($path_key == "whoami") ||
      ($path_key == "whoami.php") ||
      ($path_key == "")
   ){
      $path_key = date('Ymd.His');
};

$path_key = "$path_key.html";
*/
date_default_timezone_set('America/New_York');
$path_key = date('Ymd.His');

$cached_url = "https://" . $_SERVER['HTTP_HOST'] . "/whoami_cache/$path_key";
$_SERVER['CACHE_URL'] = "<a href='$cached_url'>$cached_url</a>";

$out = "<HR>";
$out .= "<table style='font-size: 12px; font-family:arial,sans-serif;'>";
$out .= "<tr><td>IP</td><td>$ip</td></tr>";
$out .= "<tr><td>HOSTNAME</td><td>$hostname</td></tr>";
foreach ($_SERVER  as $key => $val){ $out .= "<tr><td>\$_SERVER['$key']</td><td>$val</td></tr>"; };
foreach ($_GET     as $key => $val){ $out .= "<tr><td>\$_GET['$key']</td><td>$val</td></tr>"; };
foreach ($_POST    as $key => $val){ $out .= "<tr><td>\$_POST['$key']</td><td>$val</td></tr>"; };
foreach ($_COOKIE  as $key => $val){ $out .= "<tr><td>\$_COOKIE['$key']</td><td>$val</td></tr>"; };
foreach ($_REQUEST as $key => $val){ $out .= "<tr><td>\$_REQUEST['$key']</td><td>$val</td></tr>"; };
//$out .= "<tr><td>\$_SERVER['PHP_VERSION']</td><td>" . phpversion() . "</td></tr>";

$out .= "</table>";

$file_out = "<HR>" . date('M d, Y H:i:s') . " GMT " . $out;
file_put_contents("./whoami_cache/$path_key.html", $file_out, FILE_APPEND | LOCK_EX);

print $out;
//include_once("footer.php");
?>

