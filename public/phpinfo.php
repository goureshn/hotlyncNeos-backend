<?php
// echo phpinfo();

$ch = curl_init();

curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',		
));

$param = array();

$param['infile'] = '"title": {"text": "Steep Chart"}, "xAxis": {"categories": ["Jan", "Feb", "Mar"]}, "series": [{"data": [29.9, 71.5, 106.4]}]}';

curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8005');
curl_setopt($ch, CURLOPT_POST,1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$result=curl_exec ($ch);
curl_close ($ch);
echo json_encode($result);