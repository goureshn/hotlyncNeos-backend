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
            border:0px;
        }
        tr, td {
            border: 1px solid #ECEFF1;
			color: #212121;
        }
        td.right {
            text-align: right;
            padding-right: 5px;;
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
        p{
            font-size: 10px;
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
    @include('frontend.report.callcenter.callcenter_desc')
<div style="margin-top:10px;position: absolute;width: 90%;margin-bottom: 10px;" align="center">
    <p align="center"; style="font-size:10px; margin-top:0; text-align: center">{{$data['title']}}</p>
</div>
@include('frontend.report.callcenter.callcenter_chart')

@if( $data['report_by'] == 'Agent')
    @include('frontend.report.callcenter.agent')
@endif
@if( $data['report_by'] == 'Call Status')
    @include('frontend.report.callcenter.callstatus')
@endif
@if( $data['report_by'] == 'Date')
    @include('frontend.report.callcenter.date')
@endif
@if( $data['report_by'] == 'Origin')
    @include('frontend.report.callcenter.origin')
@endif
@if( $data['report_by'] == 'Per Hour')
    @include('frontend.report.callcenter.perhour')
@endif
@if( $data['report_by'] == 'Call Type')
    @include('frontend.report.callcenter.calltype')
@endif
@if( $data['report_by'] == 'Channel')
    @include('frontend.report.callcenter.channel')
@endif
@if( $data['report_by'] == 'Agent Status')
    @include('frontend.report.callcenter.agentstatus')
@endif
@if( $data['report_by'] == 'Auto Attendant')
    @include('frontend.report.callcenter.auto_attendant')
@endif

<!------before list--------->
@if( $data['report_by'] == 'Abandoned Summary')
    @include('frontend.report.callcenter.abandoned_calls')
@endif
@if( $data['report_by'] == 'Agent Call Detailed')
    @include('frontend.report.callcenter.agent_call_detailed')
@endif
@if( $data['report_by'] == 'Agent Activity')
    @include('frontend.report.callcenter.agent_activity')
@endif
@if( $data['report_by'] == 'Call Type Summary by Agent')
    @include('frontend.report.callcenter.calltype_summary_by_agent')
@endif
@if( $data['report_by'] == 'Agent Activity Summary')
    @include('frontend.report.callcenter.agent_activity_summary')
@endif
@if( $data['report_by'] == 'Call Trafic Time Analysis')
    @include('frontend.report.callcenter.call_traffic_time')
@endif

</body>
</html>