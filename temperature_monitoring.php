<?php
session_start();

/**
 * Temperature Week Monitoring
 *
 *
 * This application performs relevant data concerning the temperature of a weather station of a Netatmo product during a week frame.
 *
 * @author Mohamed Ahmed
 * @version 1.0
**/


//Global variables
$device_id = array('70:ee:50:3f:13:36', '70:ee:50:14:53:38');
$module_id = array('02:00:00:3f:0a:54', '02:00:00:14:43:f6');
$client_id    = '600c8285f75e5f1440564e69';
$client_secret= 'yWL6w0qaoig4KY3TWjKKF1aRUyEgQpUxT';
$redirect_uri = 'http://localhost/netatmo/index.php';
$data_temperature = array();	
$date         = new DateTime();
$date_end     = $date->getTimestamp();
$frame        = 24*60*60*7;  //Define the frame of the monitoring in seconds
$date_begin   = $date_end - $frame;

/**
 * This function performs API calls through curl functions.
 *
 *
 * @param string $url
 *			The API URL
 * @param string $header 
 *			The header of the request
 * @param string $postfield
 * 			POSTFIELD content if it is a POST request
 * @param string $request
 * 			The type of the request 'GET', 'POST', ...
 *
 * @return array $data 
 *      The output of the request
**/
function cURL($url, $header, $postfield, $request){
	$curl = curl_init();
	curl_setopt_array($curl, array(
									CURLOPT_URL => $url,
									CURLOPT_RETURNTRANSFER => true,
									CURLOPT_CUSTOMREQUEST => $request,
									CURLOPT_POSTFIELDS => $postfield,
									CURLOPT_HTTPHEADER => array($header)
								)
	);
	$response = curl_exec($curl);
	$data = json_decode($response, true);
	curl_close($curl);
	  
	return $data;
}

/**
 * This function gives the minimum, maximum and the mean of a temperature set.
 *
 *
 * @param string $device_id
 *      The device ID
 * @param string $module_id
 *      The module ID
 * @param array $arr
 *      Temperature data
 * @return array
 *      Minimum, maximum and the mean of the temperature set
**/
function temperature_result($device_id, $module_id, $arr){
	$min = $arr['0'];
	$max = $arr['0'];
	$sum = 0;
	foreach($arr as $idx => $temperature){
		if ($min > $temperature){
			$min = $temperature;
		}
		if ($max < $temperature){
			$max = $temperature;
		}
		$sum += $temperature;
	}
	$mean = round($sum / count($arr),1);
	echo $device_id . '/' . $module_id .' : <br \>
		   	Minimum: ' . $min . ' <br />
			Maximum: ' . $max . ' <br />
			Mean:	 ' . $mean . ' <br />';
	$arr = array();
	
	return array($min, $max, $mean);
}

/**
 * This function makes call to the Netatmo API in order to retrieve temperature data
 *
 *
 * @api
 * @see cURL()
 * @see temperature_result()
 *
 * @param string $device_id
 *     The device ID
 * @param string $module_id
 *     The module ID
 * @param string $access_token
 *     The access token from the OAuth 2.0 Authentication
 * @param array $arr
 *     Array that contains the temperature set
 *
**/
function temperature_process($device_id, $module_id, $access_token, $arr) {
	$last_timestamp = $GLOBALS['date_begin'];
	do {
		$url = "https://api.netatmo.com/api/getmeasure?" .
			   "device_id=" . $device_id .
			   "&module_id=" . $module_id .
			   "&scale=max" .
			   "&type=temperature" .
			   "&date_begin=" . $last_timestamp .
			   "&limit=1024" .
			   "&optimize=false" . 
			   "&real_time=true";
			   $header = 'Authorization: Bearer ' . $access_token;
			   $request = 'GET';
			   $data = cURL($url, $header, '', $request);
			
		if (isset($data['body'])) {
			foreach ($data['body'] as $timestamp => $arr_temp) {
				$last_timestamp = $timestamp;
				foreach($arr_temp as $idx => $temperature) {
					array_push($arr, $temperature);
				}
			}
		}
	} while (($GLOBALS['date_end'] - $last_timestamp) > 1000);
	temperature_result($device_id, $module_id, $arr);
	if (isset($data['error'])) {
		echo 'Error code ' . $data['error']['code'] . ': ' . $data['error']['message'] . '<br />';
	}
}

//Check if the user is authenticated
if ( isset($_GET['state']) && $_GET['state'] == 'success' && isset($_GET['code']) ){

	//Check if the access token is generated
	if (!isset($_SESSION['access_token'])){
		$url = "https://api.netatmo.com/oauth2/token";
		$header = 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
		$request = 'POST';
		$postfield = "grant_type=authorization_code" .
					"&client_id=" . $client_id .
					"&client_secret=" . $client_secret .
					"&code=" . $_GET['code'] .
					"&redirect_uri=" . $redirect_uri .
					"&scope=read_station";
		$data = cURL($url, $header, $postfield, $request);
		   	
		if (isset($data['access_token'])){
			$_SESSION['access_token'] = $data['access_token'];
		}
		if (isset($data['error'])) {
			echo 'Error code: ' . $data['error']['code'] . ' ' . $data['error']['message'] . '<br />';
		}
	}
	
	//Performs the calculation
	for ($i = 0; $i < 2; $i++) {
		temperature_process($device_id[$i], $module_id[$i], $_SESSION['access_token'], $data_temperature);
	}
}

//Authentication on the Netatmo API through OAuth 2.0 protocol
else {
	header("Location: https://api.netatmo.com/oauth2/authorize?" .
			"client_id=" . $client_id .
			"&scope=read_station" .
			"&state=success");
}

?>