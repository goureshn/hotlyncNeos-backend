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
@include('frontend.report.lnf.lostfound_detail_desc')
@include('frontend.report.lnf.lostfound_detail_body')

</body>
</html>

