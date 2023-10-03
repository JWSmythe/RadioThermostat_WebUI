<?php
// Thermostat Show Temperature Graphs - v1.0
//
// This shows the thermostat temperature history in Google Charts, 
// using the data contained in the sqlite database $datadb. 
// We're using the DB, so we can select specific date (epoch timestamp) range(s).

require_once("tstat_globals.php");

$debug = 0;

$db = new SQLite3($datadb);
	if(!$db) {
	  echo $db->lastErrorMsg();
	} else {
	  if ($debug==1){ echo "Opened database successfully<br>\n"; };
	}

// The request can contain the width.  We need the width to figure out how to scale the graph. 
// The graph can only have # pixels width.  500 datapoints won't fit in a 200 graph, and it 
// will be cut off on the right side. 
if (!isset($_REQUEST['width'])){
	$width = 700;
}else{
	$width = intval($_REQUEST['width']);
};
if ($width > 2000){
	$width = 2000;
}elseif($width < 1){
	$width = 100;
};

if (!isset($_REQUEST['height'])){
	$height='400';
}else{
	$height = intval($_REQUEST['height']);
};

if (!isset($_REQUEST['days'])){
	$days = 1; 
}elseif($_REQUEST['days'] < 1){
	$days = 1;
}else{
	$days = $_REQUEST['days'];
};

// page html header
?>

  <html>
  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);

<?

// page html middle.
print "function drawChart() { \n\n"; 
// This is what our data should look like. 
/* 
        var data = google.visualization.arrayToDataTable([
          ['Year', 'Sales', 'Expenses'],
          ['2004',  1000,      400],
          ['2005',  1170,      460],
          ['2006',  660,       1120],
          ['2007',  1030,      540]
        ]);
*/ 
$now = time(); 
$past24hr = $now - (60*60*($days * 24)); 

$query = "SELECT * FROM data WHERE ts > $past24hr ORDER BY ts";
//print "QUERY: $query<br>\n";

	$result = $db->query($query);
		if(!$result){
			print $db->lastErrorMsg();
		}

$num_rows = 0;
// Spin through array once to count lines. 
while ($row = $result->fetcharray()){
	$num_rows++;
};
		
	//$num_rows = count($result);
	$ratio = $num_rows/$width;
	//$iratio = ceil($ratio*3);	// We need to round up to the next integer.
	$iratio = ceil($ratio*4);	// We need to round up to the next integer.
			
	print "	var data = google.visualization.arrayToDataTable([\n";
	print "		['TS', 'Actual Temperature', 'Target Temperature', 'Running'],\n";
	
	$count = 0; 
	while ($row = $result->fetchArray()){
		$count++; 
		if ($count == 1){
			
			// We're changing i_mode to temperature like ranges for now, to make the graph read well. 
			// Initial set of last_ts
			if (!isset($last['ts'])){
				$last['ts'] = $row['ts'];
				$last['t_actual'] = $row['t_actual'];
				$last['t_target'] = $row['t_target'];
				$last['i_mode']   = $row['i_mode'];				
			};

         /*
			// If there were missed logging events, insert some filler rows.
			while (($last['ts'] + 60) < $row['ts']){
				//print "Filler Row " . $row['ts'] . " Last " . $last['ts'] . "\n";
				$show_ts = date("m-d H:i:s", $last['ts']);
				print "\t\t  ['" . $show_ts . "', " . $last['t_actual'] . ", " . $last['t_target'] . ", 61],\n";
				$last['ts'] = $last['ts'] + 60; 
			};
         */ 
        
			// 0 == off 
			if($row['i_mode'] == 0){
				$row['i_mode'] = 63;
			// 1 == heat 
			}elseif($row['i_mode'] == 1){
				$row['i_mode'] = 65;
			// 2 == cool 
			}elseif($row['i_mode'] == 2){
				$row['i_mode'] = 65;			
			};			

			$show_ts = date("m-d H:i", $row['ts']);
			print "		['" . $show_ts . "', " . $row['t_actual'] . ", " . $row['t_target'] . ", ". $row['i_mode'] . "],\n";

		}elseif($count >= $iratio){
			// We hit the last skipped record.  Change back to 0.
			#print "... $count -reset to 0\n";
			$count = 0; 
		}else{
			#print "... $count\n";
		};
		
		$last['ts']       = $row['ts'];
		$last['t_actual'] = $row['t_actual'];
		$last['t_target'] = $row['t_target'];
		$last['i_mode']   = $row['i_mode'];
		
	};	// end while($row 
	print "	]);\n";
	$db->close();
// page html footer 
?>
        var options = {
          title: 'Thermostat History',
          legend: { position: 'bottom' },
          vAxis: {title: 'Temp'},
          hAxis: {title: 'Time'},
          seriesType: 'line',
		  series: {2: {type: 'bars'}}
        };

        var chart = new google.visualization.ComboChart(document.getElementById('curve_chart'));
        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
  <?
    //print "Width: $width  NumRows: $num_rows  Ratio: $ratio Integer Ratio: $iratio<br>\n";
  ?>
  <div id="curve_chart" style="width: <? print $width; ?>; height: <? print $height; ?>;"></div> 
  <? // <div id="curve_chart" style="height: 500px"></div> ?>
  </body>
</html>
<?

// <div id="curve_chart" style="width: 900px; height: 500px"></div>
?>