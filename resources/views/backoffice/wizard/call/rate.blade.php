@extends('backoffice.wizard.call.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ADMIN";
		
	$current_url = '/backoffice/guestservice/wizard/alarm';
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];	
?>

<div style="margin:auto;width:97%">
	<h1> Rate </h1> 
</div>
@stop

