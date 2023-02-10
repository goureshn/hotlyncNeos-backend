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
        th {
            align:center;
            vertical-align:middle;
            background-color:#2c3e50 !important;
            color: #fff !important;
        }
        th.subth {
            align:center;
            border: 1px solid #ECEFF1;
            vertical-align:middle;
            background-color:#ffffff !important;
            color: #212121 !important;
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
        b.title {
            font-size: 9px;
        }
        b.title1 {
            font-size: 7px;
        }

        td.right {
            text-align: right;
            padding-right: 5px;;
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
@include('frontend.report.callclassify.callclassify_desc')
<div style="margin-top:10px;position: absolute;width: 90%;" align="center">
    <p align="center"; style="font-size:10px; margin-top:0; text-align: center">{{$data['title']}}</p>
</div>
<br><br>
@if ($data['report_by'] == 'Call Date' )
    @include('frontend.report.callclassify.callclassify_calldate')
@endif
@if ($data['report_by'] == 'Call Status' )
    @include('frontend.report.callclassify.callclassify_callstatus')
@endif
@if ($data['report_by'] == 'Department' )
    @include('frontend.report.callclassify.callclassify_department')
@endif

@if ($data['report_by'] == 'Extension' )
    @include('frontend.report.callclassify.callclassify_extension')
@endif

@if ($data['report_by'] == 'Destination' )
  @include('frontend.report.callclassify.callclassify_destination')
@endif

@if ($data['report_by'] == 'User' )
    @include('frontend.report.callclassify.callclassify_user')
@endif

@if ($data['report_by'] == 'Mobile' )
    @include('frontend.report.callclassify.callclassify_mobile')
@endif

@if ($data['report_by'] == 'Comparison' )
    @include('frontend.report.callclassify.callclassify_comparison')
@endif

@if ($data['report_by'] == 'Cost Comparison' )
    @include('frontend.report.callclassify.callclassify_cost')
@endif

@if ($data['report_by'] == 'Summary Cost Comparison' )
    @include('frontend.report.callclassify.callclassify_cost')
@endif

@if ($data['report_by'] == 'Marked Date' )
    @include('frontend.report.callclassify.callclassify_markeddate')
@endif


</body>
</html>