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
            padding-left: 5px;;
        }

        td.right {
            text-align: right;
            padding-right: 5px;;
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
@include('frontend.report.audit.audit_desc')
<div style="margin-top:5px;position: absolute;width: 98%;text-align: center;margin-bottom: 30px;">
<p align="center"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report
    @if($data['report_by'] != '')
         @if($data['report_type'] == 'Users' || $data['report_type'] == 'Room' )
            by  {{$data['report_by']}}
            @endif
    @endif
</p>
</div>
<br>

@if( $data['report_by'] == 'Department' && $data['report_type'] == 'Users'  )
    @include('frontend.report.audit.department')
@endif
@if( $data['report_by'] == 'Job Role' && $data['report_type'] == 'Users' )
    @include('frontend.report.audit.job_role')
@endif
@if( $data['report_by'] == 'Permission' && $data['report_type'] == 'Users' )
    @include('frontend.report.audit.permission')
@endif
@if( $data['report_by'] == 'Building' && $data['report_type'] == 'Room' )
    @include('frontend.report.audit.building')
@endif
@if( $data['report_by'] == 'Room Type' && $data['report_type'] == 'Room' )
    @include('frontend.report.audit.room_type')
@endif
@if( $data['report_type'] == 'Guest Rate Charges' )
    @include('frontend.report.audit.guest_rate_charge')
@endif
@if( $data['report_type'] == 'Extension' )
    @include('frontend.report.audit.extension')
@endif
@if( $data['report_type'] == 'Minibar' )
    @include('frontend.report.audit.minibar')
@endif

</body>
</html>