<?php
include('PHP/src/GlobeApi.php');

$globe = new GlobeApi();
$sms = $globe->sms(7625);

// $test = array(1,2,3,4,5);
// print_r(array_splice($test, 1));

$json = file_get_contents('php://input');
$json = stripslashes($json);
$message = json_decode($json, true);

//if we received a message
if($message) {
	if(!isset($message['inboundSMSMessageList']['inboundSMSMessage'])) {
		return 'Not Set inboundSMSMessage';
	}

	foreach($message['inboundSMSMessageList']['inboundSMSMessage'] as $item) {
		if(!isset($item['message'], $item['senderAddress'])) {
			continue;	
		}

		$link = mysqli_connect("localhost","root","P@ssw0rd","kumusta") or die("Error " . mysqli_error($link));
		$result = $link->query('SELECT * FROM users WHERE phoneNumber = \''.str_replace('tel:+63', '', $item['senderAddress']).'\' LIMIT 1;');
		$user = $result->fetch_row();

		if($user) {
			if(strpos(strtoupper($item['message']), 'SEARCH') === 0) {
				$name = split(" ", strtoupper($item['message']));
				$name = implode(" ", array_splice($name, 1));
				//if searching
				$response = $sms->sendMessage(
					$user['2'],
					$user['1'],
					'You will be receiving the list containing '.$name
				);

				$query = 'INSERT INTO search VALUES(%s);';
				$data = array('NULL', '\''.$user[0].'\'', '\''.$name.'\'', '\''.date('Y-m-d H:i:s').'\'','NULL');

				$query = sprintf($query, implode(',', $data));
				$response = $link->query($query);

				echo $query;
				print_r($response);
				//logic for pull here
			}

			if(strpos(strtoupper($item['message']), 'SUBSCRIBE SEARCH') === 0) {
				$name = split(" ", strtoupper($item['message']));
				$name = implode(" ", array_splice($name, 2));
				//if subscribing to search
				$sms->sendMessage(
					$user['2'],
					$user['1'],
					'You will be receiving the list containing your '.$name.' every <time interval here>'
				);

				//logic for adding to cron job here
			}

			if(strpos(strtoupper($item['message']), 'END SUBSCRIBE SEARCH') === 0) {
				$name = split(" ", strtoupper($item['message']));
				$name = implode(" ", array_splice($name, 3));
				//if ending subscription
				$sms->sendMessage(
					$user['2'],
					$user['1'],
					'You have successfully ended your subscription for updates about '.$name
				);
			}
		}
	}
}
?>