<?php
	// Google GCM
function push2Android($push_keys, $msg, $push_type = "Common")
{
	$ret = true;

	$apiKey = "AIzaSyAHqhnGZPx86bch5p956X5BtkOmrYSasTw";  //server push key
	$headers = array(
		'Authorization: key=' . $apiKey,
		'Content-Type: application/json'
	);

	$arr = array();
	$arr['data'] = array();
	$arr['data']['msg'] = urlencode($msg);
	$arr['data']['type'] = $push_type;
	$arr['registration_ids'] = array();

	$total = sizeof($push_keys);   // $push_keys = device key array of registered androids 
	$count = (int) ($total / 500);
	$remain = $total % 500;
	if ($remain !== 0)
	{
		$count = $count + 1;
	}	
	
	if ($total > 0)
	{
		for ($i = 0; $i < $count; $i++)
		{
			$registrationIDs = array();
			$k = 0;
			if (($i === ($count - 1)) && $remain != 0)
			{
				for ($j = $i * 500; $j < $i * 500 + $remain; $j++)
				{
					$registrationIDs[$k] = $push_keys[$j];
					$k ++;
				}
			}
			else
			{
				for ($j = $i * 500; $j < ($i + 1) * 500; $j++)
				{
					$registrationIDs[$k] = $push_keys[$j];
					$k ++;
				}
			}
					
			$arr['registration_ids'] = $registrationIDs;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'http://android.googleapis.com/gcm/send');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));

			// Execute post
			$reponse = curl_exec($ch);
			// Close connection
			curl_close($ch);
			
			echo json_encode($arr);

			sleep(1);
		}
	}
	else
	{
		$ret = false;
	}

	return $ret;
}
// iphone APNS
function push2IPhone($tokens, $msg, $push_type = "Common", $badge = 1)
{
	$ret = true;
	$passphrase = 'axaapnscert'; //carepetpush2014';
	/*//$apnsCert = 'D:\xampp\htdocs\smartaxa\models\masix_apns_for_dev.pem';
	//$apnsCert = '/var/www/html/ws/img/first/test/models/masix_apns_for_dev.pem';
	$apnsCert = '/home/axasmart/public_html/models/axa_apns.pem';
	$cerfile = '/home/axasmart/public_html/models/entrust_2048_ca.cer';
	$passphrase = 'axaapnscert'; //carepetpush2014';
	$msg= utf8_encode($msg);
	$payload['aps'] = array('alert' => $msg, 'badge' => $badge, 'sound' => 'default', 'type' => $push_type);
	$message = json_encode($payload);

	$index = 0;
	$remain = 0;
	$apns = NULL;
	
		//$remain = $index;// % 300

		//if ($remain === 0)
		//{
			//if ($apns)
			//{
				// Close connection
				//fclose($apns);
				//sleep(10);
			//}
			// Create stream context
			$streamContext = stream_context_create();
			stream_context_set_option($streamContext, 'ssl', 'local_cert', $apnsCert); //ssl
			stream_context_set_option($streamContext, 'ssl', 'passphrase', $passphrase);
			//stream_context_set_option($streamContext, 'ssl', 'cafile', $cerfile);

			$apns = stream_socket_client('ssl://gateway.push.apple.com:2195', $error, $errorString, 60, STREAM_CLIENT_CONNECT, $streamContext);
			
			if (!$apns)
			{
				return false;
			}
		//}
	foreach ($tokens as $token)
	{
		// Send push
		//$apnsMessage = chr ( 0 ) . chr ( 0 ) . chr ( 32 ) . pack ( 'H*', str_replace ( ' ', '', $token ) ) . chr ( 0 ) . chr ( strlen ( $message ) ) . $message;
		$apnsMessage = chr(0) . pack("n", 32) . pack('H*', str_replace(' ', '', $token)) . pack("n", strlen($message)) . $message;
		//$apnsMessage = chr(0) . pack("n", 32) . pack('H*', trim($token)) . pack("n", strlen($message)) . $message;
		try{
		$writeResult = fwrite($apns, $apnsMessage, strlen($apnsMessage));
		}catch(Exception $e){usleep(40000);}
		//sleep(3);
		
		$index ++;
            //echo $apnsMessage . "<br>";
            //echo sizeof($apnsMessage) . "<br>";
            //echo $writeResult . "<br>";
	}

	if ($apns)
	{
		// Close connection
		fclose($apns);
	}*/
	// Report all PHP errors
	error_reporting(-1);

	// Using Autoload all classes are loaded on-demand
	require_once 'ApnsPHP/Autoload.php';

	// Instanciate a new ApnsPHP_Push object
	$push = new ApnsPHP_Push(
		ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
		dirname(__FILE__) .'/axa_apns.pem'
	);
	$push->setProviderCertificatePassphrase($passphrase);
	// Set the Root Certificate Autority to verify the Apple remote peer
	$push->setRootCertificationAuthority(dirname(__FILE__) .'/entrust_2048_ca.cer');

	// Increase write interval to 100ms (default value is 10ms).
	// This is an example value, the 10ms default value is OK in most cases.
	// To speed up the sending operations, use Zero as parameter but
	// some messages may be lost.
	// $push->setWriteInterval(100 * 1000);

	// Connect to the Apple Push Notification Service
	$push->connect();
	$i = 0;
	foreach ($tokens as $token)
	{
		// Instantiate a new Message with a single recipient
		$message = new ApnsPHP_Message($token);

		// Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
		// over a ApnsPHP_Message object retrieved with the getErrors() message.
		$message->setCustomIdentifier(sprintf("Message-Badge-%03d", $i));

		// Set badge icon to "3"
		$message->setBadge($i);
		$message->setText($msg);
		// Add the message to the message queue
		$push->add($message);
		$i++;
	}

	// Send all messages in the message queue
	$push->send();

	// Disconnect from the Apple Push Notification Service
	$push->disconnect();

	// Examine the error message container
	/*$aErrorQueue = $push->getErrors();
	if (!empty($aErrorQueue)) {
		//var_dump($aErrorQueue);
	}*/
	return $ret;
}
?>