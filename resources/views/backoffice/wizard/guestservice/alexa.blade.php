@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ALEXA";
		
	$current_url = '/backoffice/guestservice/wizard/alexa';
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];	
?>

Alexa