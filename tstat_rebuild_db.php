<?php
// Thermostat Poll for MRTG - v1.0
//
// This script is to rebuild the database from the cvs files. 
// This was mostly needed during development, but 

//https://fw.jwsmythe.com/thermostat/tstat_api_gw.php/tstat


//Notes:
//https://stackoverflow.com/questions/1711631/improve-insert-per-second-performance-of-sqlite


require_once("tstat_globals.php");

// If you want to test in a safe sandbox.
//$datadb = "data/testing.sqlite";

// Back up old DB file. 
$datadb_back = $datadb . '.' . date('Ymd.His') . '.bak';
print  "Backing up $datadb to $datadb_back \n\n";
rename($datadb, $datadb_back);

if (!file_exists($datadb)){
	print "DB file $datadb missing.  Creating new DB\n";
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


// Open database 
$db = new SQLite3($datadb);
   if(!$db) {
      echo $db->lastErrorMsg();
   } else {
      echo "Opened database successfully\n";
   }

// Drop the index for speed.
/* 
	$query = "DROP INDEX ts_idx";
	$return = $db->exec($query);
	if(!$return){
		print $db->lastErrorMsg();
	}else{
		print "index dropped successfully.\n";
	}			
*/ 

	// This speeds up inserts.  Without it, there is a pause telling the filesystem to sync on each write.  
	// Disabling it lets the OS handle writes at it's leisure.   It'll be active when the normal code runs.
	$query = "	CREATE TABLE data
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
	
	/*
	$query = "PRAGMA synchronous = OFF;";
	$return = $db->exec($query);
	if(!$return){
		print $db->lastErrorMsg();
	}else{
		print "PRAGMA synchronous = OFF;\n";
	}			
	*/
	/*
	$query = "PRAGMA journal_mode = MEMORY";
	$return = $db->exec($query);
	if(!$return){
		print $db->lastErrorMsg();
	}else{
		print "PRAGMA journal_mode = MEMORY\n";
	}				
	*/

$files = `ls -1 --sort=time data/*csv`;  
// */	// Notepad++ is having problems parsing the previous line.  This fixes it.
$files = explode("\n", ltrim(rtrim($files)));

// Iterate through the files. 
foreach ($files as $thisfile){
		print "curfile: $thisfile\n";
		$raw = explode("\n", ltrim(rtrim(file_get_contents($thisfile))));
		$num_rows = count($raw);
		print "I detected $num_rows rows in insert file.\n";
		$count=0;
		$query = "
		INSERT INTO data 
		(ts, t_actual, t_target, i_mode, s_mode)
		VALUES ";
		
		// Iterate through the rows. 
		$goodrow = 0;
		foreach ($raw as $thisline){
			$thisrow = explode(", ", $thisline);
			print "THIS ROW: |" . print_r($thisrow, TRUE) . "|\n";

				if ($thisrow[0] > 0){
						$goodrow++;
						$query .= "(" . $thisrow[0]  . ", " . $thisrow[1] . ", " . $thisrow[2] . ", " . $thisrow[3] . ", \"" . $thisrow[4] . "\")";
						if ($count < $num_rows-1){
							$query .= ",";
						};
					$count++;
				}else{
					print "Skipping blank row\n";
				};
		};
		
		if ($goodrow > 0){
			print "QUERY\n$query\n";

			$return = $db->exec($query);
			if(!$return){
				print $db->lastErrorMsg();
			}else{
				print "Bulk Record inserted successfully.\n";
			}			
		};

};

	/* 
	$query = "PRAGMA synchronous = ON;";
	$return = $db->exec($query);
	if(!$return){
		print $db->lastErrorMsg();
	}else{
		print "PRAGMA synchronous = ON;\n";
	}			
	*/ 
	
/*
// Recreate index. 

	$query = "CREATE INDEX ts_idx ON data(ts);";
	$return = $db->exec($query);
	if(!$return){
		print $db->lastErrorMsg();
	}else{
		print "index recreated successfully.\n";
	}			
*/


$db->close();
$check_cmd = "sqlite3 $datadb '.schema'; sqlite3 $datadb 'select count(*) from data;'";
print "\n";
print "==============================================================\n";
print "=== Checking results with:   =================================\n";
print "$check_cmd \n\n";
print `$check_cmd`;
print "==============================================================\n";
exit;


?>