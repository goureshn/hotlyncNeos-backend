<!DOCTYPE html>
<html lang="en">
@include('frontend.report.report_header')
<body>
<?php
$http = 'http://';
if( isset($_SERVER['HTTPS'] ) )
    $http = 'https://';

$port = $_SERVER['SERVER_PORT'];
$siteurl = $http . $_SERVER['SERVER_NAME'] . ':' . $port . '/';
?>

<div style="text-align: center">
    <b>{{$data['title']}}</b>
</div> 

@if( $data['report_by'] == 'Detail' )
    @include('frontend.report.alarm.alarm_detail')
@else
    @include('frontend.report.alarm.alarm_summary')
@endif    

</body>
</html>

