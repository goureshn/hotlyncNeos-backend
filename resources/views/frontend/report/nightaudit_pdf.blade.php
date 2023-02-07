<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <style type="text/Css">
        .pagenum:after {
            content: counter(page);
        }
        @media screen {
            div.footer {
                position: fixed;
                bottom: 0;
            }
        }
        table, p, b{
            font-family: 'Titillium Web';
            <?php if(empty($data['font-size'])) {?>
              font-size: 12px;
            <?php }else { ?>
            font-size: {{$data['font-size']}};
            <?php } ?>
            -webkit-font-smoothing: antialiased;
            line-height: 1.42857143;
            border-spacing: 0;
        }
        tr, td {
            border: 1px solid #ECEFF1;
            color: #212121;
        }
        td.right {
            text-align: right;
            padding-right: 5px;
        }
        td.summary {
	        	border: 0 !important;
	        	background-color: #CFD8DC;
        }
        
        .tr_summary {
            background-color:#2c3e50 !important;
            color: #fff !important;
        }
        .tr_summary td {
            color: #fff !important;
            text-align: center;
        }
        th {
            align:center;
            vertical-align:middle;
            background-color:#2c3e50 !important;
            color: #fff !important;
        }
        .total-amount {
            background-color:#E0E0E0 !important;
            color:#fff !important;
            font-weight:bold;
        }
        .grid tr:nth-child(even) {
            background-color: #F5F5F5;
        }
        .plain1 {
            border:0 !important;
            <?php if(empty($data['font-size'])) {?>
              font-size: 12px;
            <?php }else { ?>
            font-size: {{$data['font-size']}};
            <?php } ?>
            vertical-align:middle;
            background-color:#fff !important;
            color: #212121 !important;
            
        }
        #block_container {
            text-align:center;
        }
        #bloc2 {
            display:inline-block;
            width:49%;
            float:right;
            color: #212121 !important;
        }
        #bloc1 {
            display:inline-block;
            width:49%;
        }
    </style>
</head>
<body>
<?php
$http = 'http://';
if( isset($_SERVER['HTTPS'] ) )
    $http = 'https://';

$port = $_SERVER['SERVER_PORT'];
$siteurl = $http . $_SERVER['SERVER_NAME'] . ':' . $port . '/';
?>
@include('frontend.report.nightaudit.nightaudit_desc')
<div style="margin-top:20px;position: absolute;width: 90%;" align="center">
    <p align="center" style="font-size:10px; text-align: center">{{$data['title']}}</p>
</div>
@if($data['report_by'] == 'guest' )
    @include('frontend.report.nightaudit.guest')
@endif
@if($data['report_by'] == 'admin' )
    @include('frontend.report.nightaudit.admin')
@endif
</body>
</html>