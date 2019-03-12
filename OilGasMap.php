<?php
// contract.php
// header("Cache-Control: no-cache, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: Mon,26 Jul 1997 05:00:00 GMT");
	include "./common_OilGasMonitor.inc";
	date_default_timezone_set("America/Denver");
	
?>

<!DOCTYPE html >
	<head>
	<!--<meta http-equiv="refresh" content="60"/>-->
	<!--<meta name="description" content="<?php echo $meta1; ?>">-->
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<title>Citizen's Monitor: Map</title>
	<style>
	/* Always set the map height explicitly to define the size of the div
	* element that contains the map. */
	#map {
		height: 100%;
	}
	/* Optional: Makes the sample page fill the window. */
	html, body {
		height: 100%;
		margin: 0;
		padding: 0;
	}
	</style>
<?php
	global $link_id;
	$link_id = db_connect();
	global $refresh, $warning, $alarm, $refreshMinutes;
	global $last;
	$refresh = $_COOKIE["refresh"];
	$refreshMinutes = $_COOKIE["refreshMinutes"]; 
	if ($refreshMinutes == null) {
		$refreshMinutes = 1;
	}
	$warning = $_COOKIE["warning"];
	$alarm = $_COOKIE["alarm"];
	$qry = "Select Max(Loc) from tblLocation";
	$rs = mysqli_query($link_id, $qry);
	$i = mysqli_num_rows($rs);
	$row = mysqli_fetch_row($rs); 
	$last = $row[0] + 1;
	//echo "refresh:$refresh minutes:$refreshMinutes warning:$warning alarm:$alarm<br>";
?>
	</head>

	<body onload="currentCookies()"> 
		<!-- onload is actually afterload -->
 		<link rel="stylesheet" type="text/css" href="msStyle.css" />
		<audio id="monitor_wrn">
			<source src="beep-07.mp3" type="audio/mpeg">
		</audio>
		<audio id="monitor_alm">
			<source src="beep-beep.mp3" type="audio/mpeg">
		</audio>
		<div id="map"></div>

		<script>
		var refresh = <?php echo $refresh ?>;
		var refreshMinutes = <?php echo $refreshMinutes ?>;
		if (refresh == true) {
			var refreshInterval = refreshMinutes * 60000;
			setTimeout(function () {
				updateMap();
			}, refreshInterval); 
		}
		var warning = <?php echo $warning ?>;
		var alarm = <?php echo $alarm ?>;
		var last = <?php echo $last ?>;
	    var existing = [];
	    var current = [];
		//https://www.soundjay.com/beep-sounds-1.html
		var audio_wrn = document.getElementById("monitor_wrn");
		var audio_alm = document.getElementById("monitor_alm");
		
		function currentCookies() {
			//lst = "compare cookies: \n";	
			//lst += "existing:" + existing + "\n";
			//lst += "current:" + current + "\n";
			//alert (lst);
			var i;
			var cntA = 0;
			var cntW = 0;
			for (i = 1; i<last; i++) {
				if (alarm == true) {
					if (existing[i] == "I" && current[i] == "A") {
						//alert (i + " was information now alarm")
						cntA++;
						// add to log
					}
					if (existing[i] == "W" && current[i] == "A") {
						//alert (i + " was warning now alarm")
						cntA++;
						// add to log
					}
				}
				if (warning == true) {
					if (existing[i] == "I" && current[i] == "W") {
						//alert (i + " was information now warning")
						cntW++;
						// add to log
					}
				}
			}

			if (cntA > 0) {
				playAlarm();
			} else {
				if (cntW > 0) {
					playWarning();
				}
			}
		}

		function updateMap() {
			location.reload();	
		}	
		
		function existingCookies() {
			//var lst = "existing cookies: \n";	
			var i;
			for (i = 1; i<last; i++) {
				var cname = "status" + i;
				var cx = getCookie(cname);
				existing[i] = cx;
				//lst += "existing:" + cx + "\n";	
            }	
			//alert (lst);
		}


		function getCookie(cname) {
			var name = cname + "=";
			//alert ("name:" + name);
			var decodedCookie = decodeURIComponent(document.cookie);
			var ca = decodedCookie.split(';');
			//alert ("ca.length:" + ca.length);
			for(var i = 0; i <ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0) == ' ') {
					c = c.substring(1);
				}
				if (c.indexOf(name) == 0) {
					return c.substring(name.length, c.length);
				}
			}
			return "";
		}
		var customLabel = {
			W: {
				label: 'W'
			},
			A: {
				label: 'A'
			},
			I: {
				label: 'I'
			},  
			X: {
				label: 'X'
			}  
			
		};
		
		function playAlarm() {
			audio_alm.play();
		}

		function playWarning() {
			audio_wrn.play();
		}


		function initMap() {
			var map = new google.maps.Map(
				document.getElementById('map'), {
					center: new google.maps.LatLng(39.985860,-105.020848),
					zoom: 12
				}
			);
			var infoWindow = new google.maps.InfoWindow;

			// Change this depending on the name of your PHP or XML file
			// NOTE: the warnings and alarms have been moved to LoadMonitor.php -- See UpdateMap()
			downloadUrl('https://www.modelsw.com/OilGasMonitor/OilGasMarkers.xml', function(data) {
				var xml = data.responseXML;
				var markers = xml.documentElement.getElementsByTagName('marker');
				Array.prototype.forEach.call(markers, function(markerElem) {
					var id = markerElem.getAttribute('id');
					var dat = markerElem.getAttribute('date');
					var qual = markerElem.getAttribute('qual');
					var voc = markerElem.getAttribute('voc');
					//var ch4 = markerElem.getAttribute('ch4');
					var temp = markerElem.getAttribute('temp');
					var press = markerElem.getAttribute('press');
					var rh = markerElem.getAttribute('rh');
					var wdir = markerElem.getAttribute('wdir');
					var wspd = markerElem.getAttribute('wspd');
					var db = markerElem.getAttribute('db');
					var hz = markerElem.getAttribute('hz');
					var pm1 = markerElem.getAttribute('pm1');
					var pm25 = markerElem.getAttribute('pm25');
					var pm10 = markerElem.getAttribute('pm10');
					var type = markerElem.getAttribute('type');
					var point = new google.maps.LatLng(
						parseFloat(markerElem.getAttribute('lat')),
						parseFloat(markerElem.getAttribute('lng')));

					if (id > 0) {
						document.cookie="status" + id + "=" +type+";max-age=86400;"; // one day
						current[id] = type;
					}
					
					var infowincontent = document.createElement('div');
				    var btn = document.createElement('button');
					var btntxt = document.createTextNode('Plot');
					btn.appendChild(btntxt);
					btn.onclick = function() {
						plotid(id);
					};
					infowincontent.appendChild(btn);
					var text = document.createElement('text');
					text.textContent = " Citizen's Monitor";			
					infowincontent.appendChild(text);
			
					infowincontent.appendChild(document.createElement('br'));
					
					text = document.createElement('text');
					text.textContent = " id = " + id + ", date = " + dat;			
					infowincontent.appendChild(text);
					infowincontent.appendChild(document.createElement('br'));
					
					text = document.createElement('text');
					text.textContent = " qual = " + qual + ", voc = " + voc;
					infowincontent.appendChild(text);
					infowincontent.appendChild(document.createElement('br'));
					
					text = document.createElement('text');
					text.textContent = " temp = " + temp + ", Pa = " + press + ", rh = " + rh;
					infowincontent.appendChild(text);
					infowincontent.appendChild(document.createElement('br'));	
					
					text = document.createElement('text');
					if (wspd.length == 1) {
						wspd = "0" + wspd;
					}
					var dot = ".";
					var position = -1;
					var wspdD10 = [wspd.slice(0, position), dot, wspd.slice(position)].join('');
					text.textContent = " wind dir = " + wdir + " spd = " + wspdD10;
					infowincontent.appendChild(text);
					infowincontent.appendChild(document.createElement('br'));	
					
					text = document.createElement('text');
					text.textContent = " sound db = " + db + " @hz = " + hz;
					infowincontent.appendChild(text);
					infowincontent.appendChild(document.createElement('br'));	
					
					text = document.createElement('text');
					text.textContent = " pm<1.0=" + pm1 + ", pm<2.5=" + pm25 + ", pm<10.0=" + pm10;
					infowincontent.appendChild(text);
					infowincontent.appendChild(document.createElement('br'));		
					
					var icon = customLabel[type] || {};
					var marker = new google.maps.Marker({
						map: map,
						position: point,
						label: icon.label
					});
					marker.addListener('click', function() {
						infoWindow.setContent(infowincontent);
						infoWindow.open(map, marker);
					});
				});
			});
        }

		
		function plotid(id) {
			window.location.href = "https://www.modelsw.com/OilGasMonitor/PlotId.php?id="+id;
		}


		function downloadUrl(url, callback) {
			existingCookies();  // read the cookies before you load the map
			var request = window.ActiveXObject ?
				new ActiveXObject('Microsoft.XMLHTTP') :
				new XMLHttpRequest;

			request.onreadystatechange = function() {
				if (request.readyState == 4) {
					request.onreadystatechange = doNothing;
					callback(request, request.status);
				}
			};

			request.open('GET', url, true);
			request.send(null);
		}


		function doNothing() {}
		</script>
		<script async defer
			src="https://maps.googleapis.com/maps/api/js?key=API_KEY_HERE&callback=initMap">
		</script>

		
	<form name="OilGasMap" id="OilGasMap">
		
	<table width = "100%">
		<tr><td colspan = "5"><hr /></td>
		<tr><td><a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/"><img alt="Creative Commons License" style="border-width:0" src=" https://i.creativecommons.org/l/by-nc-sa/4.0/88x31.png" /></a>23Jun2017</td>
		<td><input type="checkbox" name="refresh" value="refresh" <?php if ($refresh==true) { echo "checked"; } ?> onClick="activeRefresh(checked)"> refresh 
		    <input type="text" name="refreshMinutes" size = "4" value="<?php echo $refreshMinutes ?>" onChange="activeMinutes(this.value)"> minutes</td>
		<td><input type="checkbox" name="warning" value="warning" <?php if ( $warning==true) { echo "checked"; } ?> onClick="activeWarning(checked)"> warning </td>
		<td><input type="checkbox" name="alarm" value="alarm" <?php if ( $alarm==true) { echo "checked"; } ?> onClick="activeAlarm(checked)"> alarm </td>
		<td style="float:right"><a href = "index.html" target="_top">Return to Homepage</a>
		<img src="Images/x.jpg" width = "33px" height = "38px" /></a></td></tr>
		<tr><td colspan = "5">This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/4.0/">Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License</a> &nbsp; &nbsp; <?php echo Date("Y:m:d H:i:s") ?> </td></tr>
    </table>      		
	</form>
	
	</body>
<script>
	function activeRefresh(checked) {
		document.cookie="refresh="+checked+";max-age=604800;";
        location.reload();  // will force the timeout to start (if true) or stop (if false)
		//alert ("refresh:" + checked);
	}
	function activeMinutes(val) { 	
		var strmin = val.toString();
	    document.cookie="refreshMinutes="+strmin+";max-age=604800;"; 	
		//location.reload();  // will force the timeout to start (if true) or stop (if false)
	}
	function activeWarning(checked) { 
		document.cookie="warning="+checked+";max-age=604800;";
		//location.reload();
		//alert ("warning:" + checked);
	}
	function activeAlarm(checked) { 
		document.cookie="alarm="+checked+";max-age=604800;";
		//location.reload();
		//alert ("alarm:" + checked);
	}
</script>
</html>
