<?php
// PlotId.php
// header("Cache-Control: no-cache, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: Mon,26 Jul 1997 05:00:00 GMT");
	include "./common_OilGasMonitor.inc";
	date_default_timezone_set("America/Denver");
// Download selected time period
// https://database.guide/how-to-save-a-mysql-query-result-to-a-csv-file/	

?>

<html>
<head>
	
<!-- MODEL Software: PlotId -->

<!-- PlotId -->

	<link rel="stylesheet" type="text/css" href="msStyle.css" />
	<title>Citizen's Monitor: Plot</title>
<?php
	global $link_id;
	global $id;
	global $date, $startDate, $endDate, $loc;
	global $MaxD, $MaxF, $MaxH, $MaxM, $MaxT, $MinT, $rangeT, $MaxV, $MaxY, $MinP, $MaxP, $rangeP, $MinkP, $MaxkP, $MaxS, $MaxW, $MaxSDivBy10;
	global $pm1Ave, $pm25Ave, $pm10Ave, $dbMax, $hzAtMaxDb, $dateMaxDb, $minQual, $dateMinQual, $maxVoc, $dateMaxVoc;
	$link_id = db_connect();


	$id = $_GET["id"];
	//echo "DO NOT TRUST THIS PLOT PAGE -- I AM WORKING ON IT<br>";
	//echo "Id=$id<br>";
	// 24 hr * 60 min = 1440 records
	//tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, SoundDb, Freq)
	//$qry = "SELECT Max(Date) FROM tblMonitor WHERE Loc = $id";
	//echo "$qry<br>";
	//$rs = mysqli_query($link_id, $qry);
	//$i = mysqli_num_rows($rs);
	//echo "i=$i<br>";
	//if ($i == 1) {  // should be one only row
	//	mysqli_data_seek($rs, $i);
	//	$row = mysqli_fetch_row($rs); 
	//$date = $row[0]; 		
	$date = date("Y-m-d H:i");
	//}
	//echo "date:$date<br>";	
	$startDate = date("Y-m-d H:i", strtotime('-24 hours', strtotime($date)));
	//echo "startDate:$startDate<br>";
	$endDate = $date;
	$loc = $id;
	
	Function PlotProfileMonitor($plt) {  
		global $Profile;
		global $StateCycle;
		global $startDate, $date, $endDate, $loc;
		global $link_id;
		global $id;
		global $MaxD, $MaxF, $MaxH, $MaxM, $MaxT, $MinT, $rangeT, $MaxV, $MaxY, $MinP, $MaxP, $rangeP, $MinkP, $MaxkP, $MaxW, $MaxS, $MaxSDivBy10;
		global $pm1Ave, $pm25Ave, $pm10Ave, $dbMax, $hzAtMaxDb, $dateMaxDb, $minQual, $dateMinQual, $maxVoc, $dateMaxVoc;
		echo "Start Date: $startDate -- End Date: $date<br>";

		if ($plt == "voc") {
			//table stores ppb -- see LoadMonitor.php
			//tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, VOC, Methane, SoundDb, Freq)
			$qry = "SELECT Max(VOC), Max(VOC) FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			//echo "$qry<br>";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			//echo "i=$i<br>";
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$MaxQ = 100;
			$MaxV = $row[0];
			$minQual = 100;
			$dateMinQual = "";
			$maxVoc = 0;
			$dateMaxVoc = $startDate;
			$MaxVI = pow(10,intval(strlen(strval($MaxV))-1));
			$MaxV= intval($MaxV/$MaxVI)*$MaxVI+$MaxVI;
			if ($MaxV < 500) {
				$MaxV = 500;
			}
			//$MaxM = $row[1];
			//$MaxMI = pow(10,intval(strlen(strval($MaxM))-1));
			//$MaxM = intval($MaxM/$MaxMI)*$MaxMI+$MaxMI;
			//echo "MaxM: $MaxM <br>";
			Header("Content-type: image/png");  // let the server know its not html
			$imageV = ImageCreate(721,256);
			$white = ImageColorAllocate($imageV,255,255,255);
			$green = ImageColorAllocate($imageV,0,255,0);
			$red = ImageColorAllocate($imageV,255,0,0); 
			$blue = ImageColorAllocate($imageV,0,0,255);
			$black = ImageColorAllocate($imageV,0,0,0);
			ImageLine($imageV,0,127,720,127,$black);     
			ImageLine($imageV,0,0,720,0,$black);  
			ImageLine($imageV,720,0,720,255,$black);  
			ImageLine($imageV,720,255,0,255,$black);  
			ImageLine($imageV,0,255,0,0,$black);  			
			$qry = "SELECT Quality, VOC, Date FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			//echo "$qry<br>";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			//echo "cntr=$cntr<br>";
			
			$lineCntr = 2;
			$prev[$lineCntr];
			$clr[$lineCntr];
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$prev[0] = $row[0];
			//ImageLine($imageV,0,255-($prev[0]/$MaxQ)*255,0,255-($prev[0]/$MaxQ)*255,$black);
			$prev[1] = $row[1];
			//ImageLine($imageV,0,255-($prev[1]/$MaxV)*255,0,255-($prev[1]/$MaxV)*255,$black);
			//ImageLine($imageV,0,255-($prev[2]/$MaxM)*255,0,255-($prev[2]/$MaxM)*255,$green);
			$calcDate = $startDate;
			$m = 0;			
			for ($i = 1; $i< $cntr; $i++) { 
				mysqli_data_seek($rs, $i);
				$row = mysqli_fetch_row($rs); 
				$plotDate = $row[2];
				$minutes = round((strtotime($plotDate) - strtotime($calcDate)) /60)-1;
				$m += $minutes; // missing
				//echo "plotDate: $plotDate calcDate: $calcDate minutes $minutes m: $m<br>" ;
				$j = ($i+$m)/2;
				$k = $j+1;
				if ($minQual > $row[0]) {
					$minQual = $row[0];
					$dateMinQual = $row[2];
				}
				if ($maxVoc < $row[1]) {
					$maxVoc = $row[1];
					$dateMaxVoc = $row[2];
				}
				if ($minutes > 1) {
					$clr[0] = $white;
					$clr[1] = $white;
				} else {
					$clr[0] = $blue;
					$clr[1] = $red;
				}
				$time = strtotime($plotDate);
				if (date('i', $time) == 0) {
					ImageLine($imageV,$j,0,$j,5,$black);
					$hr = date('h', $time);
					if ($hr == 6 or $hr == 18) {
						ImageLine($imageV,$j,0,$j,10,$black);
					}
					if ($hr == 0 or $hr == 12) {
						ImageLine($imageV,$j,0,$j,15,$black);
					}
				}
				ImageLine($imageV,$j,255-($prev[1]/$MaxV)*255,$k,255-($row[1]/$MaxV)*255,$clr[1]);
				ImageLine($imageV,$j,255-($prev[0]/$MaxQ)*255,$k,255-($row[0]/$MaxQ)*255,$clr[0]);

				//ImageLine($imageV,$j,255-($prev[2]/$MaxM)*255,$k,255-($row[2]/$MaxM)*255,$green);
				$prev[0] = $row[0];
				$prev[1] = $row[1];
				$calcDate = $plotDate;
				//$prev[2] = $row[2];
			}
			
			ImagePNG($imageV,"imageV.png");
			ImageDestroy($imageV);     
		}

		if ($plt == "trh") {
			//tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, Methane, SoundDb, Freq, WindDir, WindSpd)
			$qry = "SELECT Max(Temp), Min(Temp), Max(WindSpd) FROM tblMonitor WHERE Loc = $id AND Date > '$startDate'";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$MaxT = $row[0];
			$MaxT += 10;
			$MaxT = intval($MaxT / 10);
			$MaxT = intval($MaxT * 10);
			$MinT = $row[1];
			$MinT = intval($MinT / 10);
			$MinT = intval($MinT * 10);
			$rangeT = $MaxT - $MinT;
			$MaxW = 360;  // direction
			$MaxS = $row[2];  // windspeed is saved X10 -- i.e. 6.9 is saved as 69
			//$MaxSDivBy10 = intval($MaxS/40)*4+4;
			$MaxSDivBy10 = intval($MaxS/20)*2+2;
			$MaxS = intval($MaxSDivBy10 * 10);
		
			$qry = "SELECT Min(Press), Max(Press) FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			mysqli_data_seek($rs, 0);
			// 83809 to	84220 -- I want 83000 to 85000
			$row = mysqli_fetch_row($rs); 
			$MinP = $row[0];  // 83809
			$MaxP = $row[1];  // 84220
			//echo "MinP first pass: $MinP<br>";
			While ($MinP < 78000) { // bad number
				$qry = "SELECT Min(Press), Max(Press) FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' AND Press > $MinP ORDER BY Date";
				$rs = mysqli_query($link_id, $qry);
				$cntr = mysqli_num_rows($rs);
				mysqli_data_seek($rs, 0);
				// 83809 to	84220 -- I want 83000 to 85000
				$row = mysqli_fetch_row($rs); 
				$MinP = $row[0];  // 83809
				//echo "MinP Retry: $MinP<br>";
			}
			$MinP = intval($MinP / 1000); // 83
			$MinP = intval($MinP * 1000); 
			$MaxP = $row[1];  // 84220
			$MaxP += 1000; 	  // 85220
			$MaxP = intval($MaxP / 1000);  // 85 			
			$MaxP = intval($MaxP * 1000);  
			$rangeP = $MaxP - $MinP;
			$MinkP = $MinP / 1000;
			$MaxkP = $MaxP / 1000;
			//$MaxTI = pow(10,intval(strlen(strval($MaxT))-1)); 
			//$MaxT = intval($MaxT/$MaxTI)*$MaxTI+$MaxTI;  			
			$MaxH = 100;

			Header("Content-type: image/png");  // let the server know its not html
			$imageT = ImageCreate(721,256);
			$white = ImageColorAllocate($imageT,255,255,255);
			$red = ImageColorAllocate($imageT,255,0,0); 
			$blue = ImageColorAllocate($imageT,0,0,255);
			$green = ImageColorAllocate($imageT,0,255,0); 
			$purple = ImageColorAllocate($imageT,128,0,128);  // direction
			$orange = ImageColorAllocate($imageT,255,165,0);  // speed
			$black = ImageColorAllocate($imageT,0,0,0);
			ImageLine($imageT,0,128,720,128,$black);     
			ImageLine($imageT,0,0,720,0,$black);  
			ImageLine($imageT,720,0,720,255,$black);  
			ImageLine($imageT,720,255,0,255,$black);  
			ImageLine($imageT,0,255,0,0,$black);  			
			
			$qry = "SELECT Temp, RH, Press, WindDir, WindSpd, Date FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			//echo "$qry<br>";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			//echo "cntr=$cntr<br>";
			
			$lineCntr = 5;
			$prev[$lineCntr];
			$clr[$lineCntr];
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$prev[0] = $row[0];
			$prev[1] = $row[1];
			$prev[2] = $row[2];
			$prev[3] = $row[3];
			$prev[4] = $row[4];
			$calcDate = $startDate;
			$m = 0;			
			for ($i = 1; $i< $cntr; $i++) { 
				mysqli_data_seek($rs, $i);
				$row = mysqli_fetch_row($rs); 
				$plotDate = $row[5];
				$minutes = round((strtotime($plotDate) - strtotime($calcDate)) /60)-1;
				$m += $minutes; // missing
				$j = ($i+$m)/2;
				$k = $j+1;
				if ($minutes > 1) {
					$clr[0] = $white;
					$clr[1] = $white;
					$clr[2] = $white;
					$clr[3] = $white;
					$clr[4] = $white;
				} else {
					$clr[0] = $red;
					$clr[1] = $blue;
					$clr[2] = $green;
					$clr[3] = $purple;
					$clr[4] = $orange;
				}
				if ($row[2] < 77000) {
					$clr[2] = $white;
				}


				$time = strtotime($plotDate);
				if (date('i', $time) == 0) {
					ImageLine($imageT,$j,0,$j,5,$black);
					$hr = date('h', $time);
					if ($hr == 6 or $hr == 18) {
						ImageLine($imageT,$j,0,$j,10,$black);
					}
					if ($hr == 0 or $hr == 12) {
						ImageLine($imageT,$j,0,$j,15,$black);
					}
				}
				// the noisy ones first and thus in the back
				ImageLine($imageT,$j,255-($prev[4]/$MaxS)*255,$k,255-($row[4]/$MaxS)*255,$clr[4]);

                // wrap the wind around north
				if ($prev[3]>300 && $row[3]<60) {
					ImageLine($imageT,$j,255-($prev[3]/$MaxW)*255,$k,255-(360/$MaxW)*255,$clr[3]);
					ImageLine($imageT,$j,255-(0/$MaxW)*255,$k,255-($row[3]/$MaxW)*255,$clr[3]);
				} else if ($prev[3]<60 && $row[3]>300) {  
					ImageLine($imageT,$j,255-($prev[3]/$MaxW)*255,$k,255-(0/$MaxW)*255,$clr[3]);
					ImageLine($imageT,$j,255-(360/$MaxW)*255,$k,255-($row[3]/$MaxW)*255,$clr[3]);
				} else {
					ImageLine($imageT,$j,255-($prev[3]/$MaxW)*255,$k,255-($row[3]/$MaxW)*255,$clr[3]);
				}
				ImageLine($imageT,$j,255-(($prev[0]-$MinT)/$rangeT)*255,$k,255-(($row[0]-$MinT)/$rangeT)*255,$clr[0]);
				ImageLine($imageT,$j,255-($prev[1]/$MaxH)*255,$k,255-($row[1]/$MaxH)*255,$clr[1]);
				ImageLine($imageT,$j,255-(($prev[2]-$MinP)/$rangeP)*255,$k,255-(($row[2]-$MinP)/$rangeP)*255,$clr[2]);

				$prev[0] = $row[0];
				$prev[1] = $row[1];
				$prev[2] = $row[2];
				$prev[3] = $row[3];
				$prev[4] = $row[4];
				$calcDate = $plotDate;
			}
			ImagePNG($imageT,"imageT.png");
			ImageDestroy($imageT);     
		}
		
		if ($plt == "dbf") {
			//tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, Methane, SoundDb, Freq)
			$qry = "SELECT Max(SoundDb), Max(Freq) FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			$dbAve = 0;
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$MaxD = $row[0]; // 0 to 120 db //130;  //  minus 125 to plus 130 = range 255
			$MaxF = $row[1]; // 0 to 8000 hz //22050;  // 44100 / 2
			$MaxDI = pow(10,intval(strlen(strval($MaxD))-1));
			$MaxD= intval($MaxD/$MaxDI)*$MaxDI+$MaxDI;
			if ($MaxD < 40) {
				$MaxD = 40;
			}
			$MaxFI = pow(10,intval(strlen(strval($MaxF))-1));
			$MaxF= intval($MaxF/$MaxFI)*$MaxFI+$MaxFI;
			if ($MaxF < 500) {
				$MaxF = 500;
			}
			//echo "row0: $row[0] MaxDI: $MaxDI MaxD: $MaxD <br>";
			//echo "row1: $row[1] MaxFI: $MaxFI MaxF: $MaxF <br>";
			Header("Content-type: image/png");  // let the server know its not html
			$imageD = ImageCreate(721,256);
			$white = ImageColorAllocate($imageD,255,255,255);
			$red = ImageColorAllocate($imageD,255,0,0); 
			$blue = ImageColorAllocate($imageD,0,0,255);
			$black = ImageColorAllocate($imageD,0,0,0);
			ImageLine($imageD,0,127,720,127,$black);    // 60 
			ImageLine($imageD,0,0,720,0,$black);  
			ImageLine($imageD,720,0,720,255,$black);  
			ImageLine($imageD,720,255,0,255,$black);  
			ImageLine($imageD,0,255,0,0,$black);  			
			// Draw the text 'PHP Manual' using font size 13
			//$font_file = './arial.ttf';
			//imagefttext($imageD, 13, 0, 105, 55, $black, $font_file, 'PHP Manual');
			//$font = imageloadfont('./04b.gdf');
			//imagestring($im, $font, 0, 0, 'Hello', $black);
						
			$qry = "SELECT SoundDb, Freq, Date FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			//echo "$qry<br>";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			//echo "cntr=$cntr<br>";
			$lineCntr = 2;
			$prev[$lineCntr];
			$clr[$lineCntr];
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$prev[0] = $row[0];
			//ImageLine($imageD,0,255-($prev[0]/$MaxD)*255,0,255-($prev[0]/$MaxD)*255,$black);
			$prev[1] = $row[1];
			//ImageLine($imageD,0,255-($prev[1]/$MaxF)*255,0,255-($prev[1]/$MaxF)*255,$black);
			$dbMax = 0;
			$hzAtMaxDb = 0;
			$dateMaxDb = "";
			$calcDate = $startDate;
			$m = 0;			
			for ($i = 1; $i< $cntr; $i++) { 
				mysqli_data_seek($rs, $i);
				$row = mysqli_fetch_row($rs); 
				$plotDate = $row[2];			
				$minutes = round((strtotime($plotDate) - strtotime($calcDate)) /60)-1;
				$m += $minutes; // missing
				$j = ($i+$m)/2;
				$k = $j+1;
				if ($dbMax < $row[0]) {
					$dbMax = $row[0];
					$hzAtMaxDb = $row[1];
					$dateMaxDb = $row[2];
				}
				if ($minutes > 1) {
					$clr[0] = $white;
					$clr[1] = $white;
				} else {
					$clr[0] = $red;
					$clr[1] = $blue;
				}
				$time = strtotime($plotDate);
				if (date('i', $time) == 0) {
					ImageLine($imageD,$j,0,$j,5,$black);
					$hr = date('h', $time);
					if ($hr == 6 or $hr == 18) {
						ImageLine($imageD,$j,0,$j,10,$black);
					}
					if ($hr == 0 or $hr == 12) {
						ImageLine($imageD,$j,0,$j,15,$black);
					}
				}
				ImageLine($imageD,$j,255-($prev[0]/$MaxD)*255,$k,255-($row[0]/$MaxD)*255,$clr[0]);
				ImageLine($imageD,$j,255-($prev[1]/$MaxF)*255,$k,255-($row[1]/$MaxF)*255,$clr[1]);
				$prev[0] = $row[0];
				$prev[1] = $row[1];
				$calcDate = $plotDate;
			}
			ImagePNG($imageD,"imageD.png");
			ImageDestroy($imageD);     
		}

		if ($plt == "pms") {
			//tblMonitor (Loc, Date, PM1, PM25, PM10, Temp, Press, RH, Ohms, Quality, Methane, SoundDb, Freq)
			//https://cdn-shop.adafruit.com/product-files/3686/plantower-pms5003-manual_v2-3.pdf
			$qry = "SELECT Max(PM1), Max(PM25), Max(PM10) FROM tblMonitor WHERE Loc = $id AND Date > '$startDate'";
			//echo "$qry<br>";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);			
			mysqli_data_seek($rs, $cntr);
			$row = mysqli_fetch_array($rs); 
			$MaxY = $row[0];
			if ($MaxY < $row[1]) 
				$MaxY = $row[1];
			if ($MaxY < $row[2])
				$MaxY = $row[2];
			$MaxYI = pow(10,intval(strlen(strval($MaxY))-1)); 
			$MaxY = intval($MaxY/$MaxYI)*$MaxYI+$MaxYI;  

			Header("Content-type: image/png");  // let the server know its not html
			$imageP = ImageCreate(721,256);
			$white = ImageColorAllocate($imageP,255,255,255);
			$red = ImageColorAllocate($imageP,255,0,0); 
			$green = ImageColorAllocate($imageP,0,255,0);
			$blue = ImageColorAllocate($imageP,0,0,255);
			$black = ImageColorAllocate($imageP,0,0,0);
			ImageLine($imageP,0,128,720,128,$black);     
			ImageLine($imageP,0,0,720,0,$black);  
			ImageLine($imageP,720,0,720,255,$black);  
			ImageLine($imageP,720,255,0,255,$black);  
			ImageLine($imageP,0,255,0,0,$black);  			
			
			$qry = "SELECT PM1, PM25, PM10, Date FROM tblMonitor WHERE Loc = $id AND Date > '$startDate' ORDER BY Date";
			$rs = mysqli_query($link_id, $qry);
			$cntr = mysqli_num_rows($rs);
			//echo "i=$i<br>";
			$pm25Ave = 0; 
			$pm10Ave = 0; 
			$lineCntr = 3;
			$prev[$lineCntr];
			$clr[$lineCntr];
			mysqli_data_seek($rs, 0);
			$row = mysqli_fetch_row($rs); 
			$prev[0] = $row[0];
			//ImageLine($imageP,0,255-($prev[0]/$MaxY)*255,0,255-($prev[0]/$MaxY)*255,$black);
			$prev[1] = $row[1];
			//ImageLine($imageP,0,255-($prev[1]/$MaxY)*255,0,255-($prev[1]/$MaxY)*255,$black);
			$prev[2] = $row[2];
			//ImageLine($imageP,0,255-($prev[2]/$MaxY)*255,0,255-($prev[2]/$MaxY)*255,$black);
			$calcDate = $startDate;
			$m = 0;			
			for ($i = 1; $i< $cntr; $i++) { 
				mysqli_data_seek($rs, $i);
				$row = mysqli_fetch_row($rs); 
				$plotDate = $row[3];			
				$minutes = round((strtotime($plotDate) - strtotime($calcDate)) /60)-1;
				$m += $minutes; // missing
				$j = ($i+$m)/2;
				$k = $j+1;
				$pm1Ave += $row[0];
				$pm25Ave += $row[1];
				$pm10Ave += $row[2];
				if ($minutes > 1) {
					$clr[0] = $white;
					$clr[1] = $white;
					$clr[2] = $white;
				} else {
					$clr[0] = $blue;
					$clr[1] = $red;
					$clr[2] = $green;
				}
				$time = strtotime($plotDate);
				if (date('i', $time) == 0) {
					ImageLine($imageP,$j,0,$j,5,$black);
					$hr = date('h', $time);
					if ($hr == 6 or $hr == 18) {
						ImageLine($imageP,$j,0,$j,10,$black);
					}
					if ($hr == 0 or $hr == 12) {
						ImageLine($imageP,$j,0,$j,15,$black);
					}
				}
				ImageLine($imageP,$j,255-($prev[0]/$MaxY)*255,$k,255-($row[0]/$MaxY)*255,$clr[0]);
				ImageLine($imageP,$j,255-($prev[1]/$MaxY)*255,$k,255-($row[1]/$MaxY)*255,$clr[1]);
				ImageLine($imageP,$j,255-($prev[2]/$MaxY)*255,$k,255-($row[2]/$MaxY)*255,$clr[2]);
				$prev[0] = $row[0];
				$prev[1] = $row[1];
				$prev[2] = $row[2];
				$calcDate = $plotDate;
			}
			$pm1Ave /= $cntr;
			$pm1Ave = Round($pm1Ave);
			$pm25Ave /= $cntr;
			$pm25Ave = Round($pm25Ave);
			$pm10Ave /= $cntr;
			$pm10Ave = Round($pm10Ave);
			ImagePNG($imageP,"imageP.png");
			ImageDestroy($imageP);     
		}
      
	}
		

?>
</head>

	<body bgcolor="#FAF0E6">
	<?php
		// also used in OilGasMap.php 
		// NOTE: moved to LoadMonitor.php UpdateMap()
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
	?>
    <h3>Citizen's Monitor: Plots &nbsp;&nbsp;&nbsp;&nbsp;id <?php echo $id ?></h3>
	<!-- <b>Returned to 24 hr baseline on 2019-02-06 10:00 -- was 48 hr for the last 3 days -- no changes that I can see.</b> <br> -->
	<form enctype="multipart/form-data" action="Download.php" method="POST">
		<?php 
			global $loc,$startDate,$endDate,$outFile;
			$outFile = "monitor" . $loc . ".csv";
		?>
		<a href='/OilGasMonitor/Download.php?
			loc=<?php echo $loc ?>&startDate=<?php echo $startDate?>&endDate=<?php echo $endDate?>&action=none&outFile=<?php echo $outFile ?>'>
			Download to monitor.csv file</a>
	</form>
	
		<ul>		
			<li>Quality Warning < <?php echo $wQual ?>%, Alarm < <?php echo $aQual ?>% -- VOC Warning > <?php echo $wVoc ?> ppb, Alarm > <?php echo $aVoc ?> ppb</li>
				<?php PlotProfileMonitor("voc") ?> 
				<IMG SRC="imageV.png"><br>
				Quality (Blue)<?php echo " 0 to 100 % " ?> VOC (Red)<?php echo " 0 to $MaxV ppb " ?> <br>
				24 hour Minimum Quality on <?php echo " $dateMinQual " ?> -- Quality (Blue) = 
				<?php 
				echo " $minQual %";
				if ($minQual < $aQual)
					echo " ALARM";
				else if ($minQual < $wQual)
					echo " WARNING";
				?><br>
				24 hour Maximum VOC on <?php echo " $dateMaxVoc " ?> -- VOC (Red) = 
				<?php 
				echo " $maxVoc ppb";
				if ($maxVoc > $aVoc)
					echo " ALARM";
				else if ($maxVoc > $wVoc)
					echo " WARNING";
				?><br>
				<br>
			<li>Temperature, Relative Humidity, Pressure, Wind Direction, and Speed</li>
				<?php PlotProfileMonitor("trh") ?> 
				<IMG SRC="imageT.png"><IMG SRC="NESWwide.png"><br>
				Temperature (Red)<?php echo " $MinT to $MaxT deg F" ?> 
				-- Relative Humidity (Blue)<?php echo " 0 to $MaxH %" ?> 
				-- Pressure (Green)<?php echo " $MinkP to $MaxkP kPa" ?> <br>
				Wind Direction (Purple) <?php echo " 0 to $MaxW Deg " ?> 
				-- Wind Speed (Orange) <?php echo " 0 to $MaxSDivBy10 mph" ?> <br>
				<br>
			<li>Sound: Decibels and Frequency -- Warning <?php echo $wDb ?> db, Alarm <?php echo $aDb ?> db</li>			
				<?php PlotProfileMonitor("dbf") ?> 
				<IMG SRC="imageD.png"><br>
				Decibels (Red)<?php echo " 0 to $MaxD db " ?> -- Frequency (Blue)<?php echo " 0 to $MaxF hz" ?> <br>
				24 hour maximum on <?php echo " $dateMaxDb " ?> -- Decibels (Red) = 
				<?php echo " $dbMax db "; 
				if ($dbMax > $aDb)
					echo "ALARM ";
				else if ($dbMax > $wDb)
					echo "WARNING ";
				?> 
				-- Frequency (Blue) =<?php echo " $hzAtMaxDb " ?>hz<br>
				<br>
			<li>Particles -- EPA 24 hour limits PM1.0 n/a; PM2.5 = 35 ug/m3; PM10.0 = 150 ug/m3<br>
				Warning 30%; PM2.5 = <?php echo $w25 ?>, PM10 = <?php echo $w10 ?> -- Alarm 50%; PM2.5 = <?php echo $a25 ?>, PM10 = <?php echo $a10 ?></li>

				<?php PlotProfileMonitor("pms") ?> 
				<IMG SRC="imageP.png"><br>
				PM 0.3 to 1.0 um (Red)  -- PM 1.0 to 2.5 um (Blue) -- PM 2.5 to 10.0 um (Green) <?php echo " 0 to $MaxY " ?>ug/m3 <br>
				24 hour average -- PM 1.0 (Red) =<?php echo " $pm1Ave " ?>ug/m3 -- PM 2.5 (Blue) = 
				<?php echo " $pm25Ave ug/m3 ";
				if ($pm25Ave > $a25)
					echo "ALARM ";
				else if ($pm25Ave > $w25)
					echo "WARNING ";
				?>
				-- PM 10 (Green) =<?php 
				echo " $pm10Ave ug/m3 ";
				if ($pm10Ave > $a10)
					echo "ALARM ";
				else if ($pm10Ave > $w10)
					echo "WARNING ";
				?><br>
				<br>
		</ul>

	  
   <table width = "100%">
      <tr><td colspan = "2"><hr /></td>
          <td rowspan = "2" width = "33px" height = "38px">
            <a href = "index.html" target="_top">
              <img src="Images/x.jpg" 
              width = "33px" height = "38px"
              alt="Image: Corporate image" /></a></td></tr>
	     <tr><td><h5>23June2017 <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License</a>.<br></h5></td>
         <td><h5 style="text-align:right">Return to Homepage</h5></td></tr>
     </table>       
  </body>
</html>
