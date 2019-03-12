<?php
// contract.php
// header("Cache-Control: no-cache, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: Mon,26 Jul 1997 05:00:00 GMT");
	include "./common_OilGasMonitor.inc";
	date_default_timezone_set("America/Denver");
?>

<html>
<head>
<!-- OilGasMonitor -->
<!-- LoadMonitor -->


<?php
	global $link_id;
	global $remote;
	global $FileDate;
	global $FailFilePath;
	
	$link_id = db_connect();
	$date = date("Y-m-d H:i:s");
	echo "MonitorFile: $date <br>";
	MonitorFile();  
	$date = date("Y-m-d H:i:s");
	echo "UpdateMap: $date <br>";
	UpdateMap();
	$date = date("Y-m-d H:i:s");
	echo "Done: $date <br>";
	
    function MonitorFile() {
		global $link_id;
		global $FileDate;		
		global $FailFilePath;
		global $windDir, $windSpd;
		// tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, SoundDb, Freq) 
		// this picks any file by file name (YYYY-MO-DD HH:MM_1.txt) from the scan folder
		// It has been FTPed to web /Scan folder from remote locations
		// doesn't matter which one - the location is the last number before .txt
		// now I count the pieces in $minuteAve
		// $LastCol = 12;  // does not include Loc and Date i.e. starts with MP1
		$isQuitNow=0;
		$FailFilePath = "./Fail/";
		$ScanFilePath = "./Scan/";
		chdir( $ScanFilePath );		
		$ThisFile = "";		
		// *************************** outer while 
		While ($isQuitNow==0) { // the outer loop		
			echo "ScanFilePath = $ScanFilePath<br>";
			// get new file:
			$PrevFile = $ThisFile;
			$ThisFile = NextFileName($ScanFilePath);  
			echo "ThisFile = $ThisFile" . "<br>";	
			If ($ThisFile == $PrevFile OR empty($ThisFile)) { // no more files
				$isQuitNow = 1;
				//If ($isQuitNow==1) {
				echo "No more files " . date('Y-m-d H:i:s') . "<br>";
				break;
			}
			
			If (substr($ThisFile, 0, 6) == "Error:") {
				echo "ThisFile=$ThisFile<br>";
				exit(-1);  //error out
			} 
			/*
			Inputs:
			PM2.5 Air Quality Sensor - I2C
			https://learn.adafruit.com/pm25-air-quality-sensor
			0	PM 1.0 um
			1	PM 2.5 um
			2	PM 10  um
			BMI680 Air Quality - I2C
			3	Temp Degrees C x 100
			4	Pressure Pa (hPa x 100)
			5	RH   Percent (x 100)
			6	Ohms 0-300,000 Ohms
			7	Methane -- not used -- will be "smog"
			Weather page
			8	WindDir
			9 	WindSpd
			Samson Microphone - USB
			http://www.samsontech.com/samson/products/microphones/usb-microphones/gomic/
			10	SoundDb  dB
			11	Freq 	Hz
			*/
			$handle = @fopen($ThisFile, "r");
			if ($handle) {
				while (($line = fgets($handle, 128)) !== false) {
					echo "line: $line " . "<br>";
					$MinuteAve = explode(',', $line);
					$LastCol = count($MinuteAve);
					echo "LastCol:$LastCol<br>";
					for ($j = 0; $j<$LastCol; $j++) {
						echo "MinuteAve: $MinuteAve[$j]" . "<br>";
					}
				}	
				if (!feof($handle)) {
					echo "Error: unexpected fgets()" . "<br>";
					exit(-2);
				}
				fclose($handle);
			}

			// save the data in the database
			$Underscore = strpos($ThisFile, "_",15); // 2017-01-05 15_51_1.txt -- get the second underscore
			$Extn = strpos($ThisFile, ".txt");
			echo "Underscore:$Underscore Extn:$Extn " . "<br>";
			$FileDate = substr($ThisFile, 0, $Underscore) . ":00";  // 2017-01-05 15_51:00
			$FileDate = str_replace("_", ":", $FileDate); // 2017-01-05 15:51:00
			$iLoc = substr($ThisFile,$Underscore+1,$Extn-$Underscore-1); // 1			
			echo "FileDate: $FileDate Loc: $iLoc " . "<br>";
			$quality = CalcQual($iLoc,$MinuteAve); // quality
			$voc = CalcVoc($iLoc,$MinuteAve); // voc
			// windDir and windSpd is now calculated in monitor.py
			// $qry = "INSERT INTO tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, WindDir, WindSpd, SoundDb, Freq) 
			//							VALUES (1,'2017-01-05 15:55:00',272,460,281,75,84400,27,200000,80,1,0,239,21,20,1000)";
			$qry = "INSERT INTO tblMonitor VALUES ($iLoc,'$FileDate',$MinuteAve[0],$MinuteAve[1],$MinuteAve[2],$MinuteAve[3],
								$MinuteAve[4],$MinuteAve[5],$MinuteAve[6],$quality,$voc,$MinuteAve[7],$MinuteAve[8],$MinuteAve[9],$MinuteAve[10],$MinuteAve[11])";
			echo $qry . "<br>";
			$rsNew = mysqli_query($link_id, $qry);
			$w = mysqli_affected_rows($link_id);
			
			if ($w == 1) {
				echo "Delete File: $ThisFile" . "<br>";
				unlink($ThisFile); // delete it				
			}
			if ($w < 1) { // 0 or negative -- it has failed rename it 
				echo "<h3>Unknown Failure to Insert: $ThisFile</h3>";  
				$filto = "z" . $ThisFile;
				$filto = str_replace (" " , "_" , $filto);
				$sta = rename($ThisFile, $filto);
				DoEvents(); 
				echo "Move $ThisFile to $filto Status: $sta<br>";
			}
			DoEvents(); 
		}		
	}

	function NextFileName($dir) {
		global $FailFilePath;
		// Open a directory, and read its contents
		echo "NextFileName $dir" . "<br>";
		foreach (glob("*.*") as $filename) {
			echo "$filename " . "<br>";
			if ($filename < "z") { 
				return $filename; // take the first one
			}
		}
		return "";
	}
		
	Function CalcQual($iLoc,$MinuteAve)	{
		global $link_id;
		global $FileDate;
		// I think I paniced last time and went from 48 to 24 -- I am going to work back to 48 ... maybe more. looking for stability or repeatablity.
		// < 2019-02-28 -- 24 hrs
		// 2019-03-09 -- 30 hrs
		$StartDate = date("Y-m-d H:i:s", strtotime('-30 hours', strtotime($FileDate)));

		// PM1, PM25, PM10, Temp, Press, RH, Ohms, Methane, SoundDb, Freq		
		// tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, SoundDb, Freq)
		// Set the humidity baseline to 40%, an optimal indoor humidity.
		$hum_baseline = 40.0;
		// This sets the balance between humidity and gas reading in the 
		//calculation of air_quality_score (25:75, humidity:gas)
		$hum_weighting = 0.25;
		// get the best quality air (highest ohms) for this loc
		$qry = "SELECT Ohms FROM tblMonitor WHERE Loc = $iLoc AND Date > '$StartDate'
				ORDER BY Ohms DESC Limit 10";
		$rs = mysqli_query($link_id, $qry);
		$cnt = mysqli_num_rows($rs);
		if ($cnt == 0) {
			$gas_baseline = $MinuteAve[6];
		} else {	
			$gas_baseline = 0;
			for ($i = 0; $i<$cnt; $i++) {
				mysqli_data_seek($rs, $i);
				$row = mysqli_fetch_row($rs); 
				$gas_baseline += $row[0];
			}
			$gas_baseline /= $cnt; // gas base quality (voc gas highest ohms) 
		}
		echo " baseline: $gas_baseline, cnt: $cnt<br>";		
		$gas = $MinuteAve[6]; // Ohms
		$gas_offset = $gas_baseline - $gas;

		$hum = $MinuteAve[5];
		$hum_offset = $hum - $hum_baseline;

		// Calculate hum_score as the distance from the hum_baseline.
		if ($hum_offset > 0) {
			$hum_score = (100 - $hum_baseline - $hum_offset) / (100 - $hum_baseline) * ($hum_weighting * 100);
		} else {
			$hum_score = ($hum_baseline + $hum_offset) / $hum_baseline * ($hum_weighting * 100);
		}
		// Calculate gas_score as the distance from the gas_baseline.
		if ($gas_offset > 0) {
			$gas_score = ($gas / $gas_baseline) * (100 - ($hum_weighting * 100));
		} else {
			$gas_score = 100 - ($hum_weighting * 100);
		}
		// Calculate air_quality_score. 
		$air_quality_score = $hum_score + $gas_score;
		$quality = round($air_quality_score); // Quality
		echo " Gas:$MinuteAve[6] Ohms, humidity:$MinuteAve[5] %RH, air quality: $quality<br>";
		return $quality;
	}	

	Function CalcVoc($iLoc,$MinuteAve)	{
		global $link_id;
		global $FileDate;
		// see changes above for start
		$StartDate = date("Y-m-d H:i:s", strtotime('-30 hours', strtotime($FileDate)));
		
		// PM1, PM25, PM10, Temp, Press, RH, Ohms, Qual, SoundDb, Freq		
		// tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, SoundDb, Freq)
		$qry = "SELECT Ohms FROM tblMonitor WHERE Loc = $iLoc AND Date > '$StartDate' ORDER BY Ohms DESC Limit 10";
		$rs = mysqli_query($link_id, $qry);
		$cnt = mysqli_num_rows($rs);
		if ($cnt == 0) {
			$gas_baseline = $MinuteAve[6];
		} else {	
			$gas_baseline = 0;
			echo "StartDate: $StartDate cnt: $cnt<br>";		
			for ($i = 0; $i<$cnt; $i++) {
				mysqli_data_seek($rs, $i);
				$row = mysqli_fetch_row($rs); 
				$gas_baseline += $row[0];
				//echo " $i, $gas_baseline";		
			}
			//echo "<br>";		
			$gas_baseline /= $cnt; // gas base quality (voc gas highest ohms) 
		}
		echo "baseline: $gas_baseline <br>";		
		$gas = $MinuteAve[6]; // Ohms
		$gas_parts = ($gas_baseline - $gas) / $gas_baseline; // parts
		$ppb = round(($gas_parts * 1000000000) / $gas_baseline);   // parts/baseline = ppb/1000000000 -> 1000000000*parts=ppb*baseline -> (1000000000*parts)/baseline
		echo "ppb baseline: $gas_baseline, gas: $gas, parts: $gas_parts, ppb: $ppb<br>";
		return $ppb;

	}


	Function updateMap() {		

		global $link_id;
		$qry = "SELECT Max(Loc) as maxLoc FROM tblLocation";
		$rs = mysqli_query($link_id, $qry);
		$k = mysqli_num_rows($rs); // it will be one row
		mysqli_data_seek($rs, $k);
		$row = mysqli_fetch_row($rs); 
		$maxLoc = $row[0];
		echo "UpdateMap() maxLoc=$maxLoc<br>";
		$XmlName = "OilGasMarkers.xml";
		chdir("../"); // move up a level
		chdir("./OilGasMonitor/");  
		echo "realpath:" . realpath('.') . "<br>";
		$fpx = fopen(realpath('.') . "/" . "$XmlName", "w");
		//$fpx = fopen($XmlName, "w"); // For Output 
		echo "open file: $XmlName<br>";
		$nl = chr(13) . chr(10);
		fwrite($fpx, "<?xml version=\"1.0\" ?>" . $nl);
		fwrite($fpx, "<markers>" . $nl);
			// ********** see and match plotId.php body
		$wQual = 25; // less than %
		$aQual = 15; // less than %
		$wVoc = 1000; // ppm
		$aVoc = 20000; // ppm
		$wDb = 60; // greater than decibels
		$aDb = 80; // greater than decibels
		$w25 = 11; // 30% of 35 ug/m3
		$a25 = 18; // 50% of 35 ug/m3
		$w10 = 45; // 30% of 150 ug/m3
		$a10 = 75; // 50% of 150 ug/m3
	// ********** see and match plotId.php body

		for ($id = -10; $id <= $maxLoc; $id++) {
			$qry = "SELECT * FROM tblLocation WHERE Loc = $id";
			$rs = mysqli_query($link_id, $qry);
			$k = mysqli_num_rows($rs);
			//echo "id=$id k=$k <br>";
			if ($k == 1) {
				mysqli_data_seek($rs, $k);
				$row = mysqli_fetch_row($rs); 
				$lat = $row[1];
				$lng = $row[2];
				//echo "id=$id lat=$lat lng=$lng <br>"; 
				$qry = "SELECT MAX(Date) FROM tblMonitor WHERE Loc = $id";
				$rs = mysqli_query($link_id, $qry);
				$i = mysqli_num_rows($rs);
				if ($i == 1) {  // should be one only row
					mysqli_data_seek($rs, $i);
					$row = mysqli_fetch_row($rs); 
					$date = $row[0];  // MAX DATE For Loc Id
					$qry = "SELECT * FROM tblMonitor WHERE Loc = $id and Date = '$date'";
					echo "$qry<br>";
					$rs = mysqli_query($link_id, $qry);
					$j = mysqli_num_rows($rs);
					if ($j == 1) {
						mysqli_data_seek($rs, $j);
						$row = mysqli_fetch_row($rs); 
						// tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, WindDir, WindSpd, SoundDb, Freq)
						//https://www3.epa.gov/region1/airquality/pm-aq-standards.html
						// quality warn 80%, alarm 60% -- VOC warn 10 ppb; alarm 100 ppb
						//In 2006, EPA revised 24-hour PM2.5 standard to 35 μg/m3. The Agency retained the 24-hour PM10 standard of 150 μg/m3.
						//Particles currently set at Warning at 30%; pm25=11, pm10=45 , Alarm at 50%; pm25=18, pm10=75
						//Extraction promised max 20 db at site border -- but I can't find where they said that and I don't believe that.
						$loc = $row[0];
						$date = $row[1];
						fwrite($fpx, "<marker id=\"$row[0]\"" );
						fwrite($fpx, " lat=\"$lat\"");
						fwrite($fpx, " lng=\"$lng\"");
						fwrite($fpx, " date=\"$row[1]\"");
						fwrite($fpx, " pm1=\"$row[2]\"");
						$row3type = "";
						if ($row[3] > $a25) {
							$row3type = ".A";
						} elseif ($row[3] > $w25) {
							$row3type = ".W";
						}
						fwrite($fpx, " pm25=\"$row[3]$row3type\"");  // w25, a25
						$row4type = "";
						if ($row[4] > $a10) {
							$row4type = ".A";
						} elseif ($row[4] > $w10) {
							$row3type = ".W";
						}
						fwrite($fpx, " pm10=\"$row[4]$row4type\"");  // w10, a10
						fwrite($fpx, " temp=\"$row[5]\"");
						fwrite($fpx, " press=\"$row[6]\"");
						fwrite($fpx, " rh=\"$row[7]\""); 
						$row9type = "";
						if ($row[9] < $aQual) {
							$row9type = ".A";
						} elseif ($row[9] < $wQual) {
							$row9type = ".W";
						}
						fwrite($fpx, " qual=\"$row[9]$row9type\"");  // wQual, aQual
						$row10type = "";
						if ($row[10] > $aVoc) {
							$row10type = ".A";
						} elseif ($row[10] > $wVoc) {
							$row10type = ".W";
						}
						fwrite($fpx, " voc=\"$row[10]$row10type\"");  // wVoc, aVoc
						//fwrite($fpx, " ch4=\"$row[11]\""); 
						fwrite($fpx, " wdir =\"$row[12]\"");  
						fwrite($fpx, " wspd=\"$row[13]\"");
						$row14type = "";
						if ($row[14] > $aDb) {
							$row14type = ".A";
						} elseif ($row[14] > $wDb) {
							$row14type = ".W";
						}
						fwrite($fpx, " db=\"$row[14]$row14type\"");  // wDb aDb
						fwrite($fpx, " hz=\"$row[15]\"");
						$type = "I";
						if ($row[3] > $a25 || $row[4] > $a10 || $row[9] < $aQual || $row[10] > $aVoc || $row[14] > $aDb) {
							$type = "A";
						} elseif ($row[3] > $w25 || $row[4] > $w10 || $row[9] < $wQual || $row[10] > $wVoc || $row[14] > $wDb) {
							$type = "W";
						}
						if ($id < 0) {
							$type = "X";
						}
						fwrite($fpx, " type=\"$type\" />" . $nl);
					}
				}	
			}		
		}		
		fwrite($fpx, "</markers>" . $nl);
		fclose($fpx);
		echo "write markers<br>";
	}
	
	function DoEvents() {
		ob_flush();
	} // DoEvents
	
?>

	<link rel="stylesheet" type="text/css" href="msStyle.css" />
	<title>Monitor</title>

	</head>
	<body>
	<form method="post" action="Monitor.php" name="frmMonitor" >

	</form>

	</body>
</html>
