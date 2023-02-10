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
@include('frontend.report.complaint.desc_mod_checklist')

@include('frontend.report.complaint.mod_checklist')

</body>
</html>

