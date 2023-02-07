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
        table{
            page-break-inside: avoid !important;
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

$call_sort =  $data['call_sort'];

?>
    @include('frontend.report.callaccount.callaccount_desc')
<div style="margin-top:20px;position: absolute;width: 98%;text-align: center;margin-bottom: 30px;">
 @if ($data['report_by'] == 'Call Date' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Call Date </p>
   
@endif
   @if ($data['report_by'] == 'Department' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Department </p>
@endif 
 
@if ($data['report_by'] == 'Room' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Room </p>
@endif 

 @if ($data['report_by'] == 'Extension' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Extension </p>
@endif 

 @if ($data['report_by'] == 'Property' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}}  Report by Property</p>
@endif

@if ($data['report_by'] == 'Destination' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Destination</p>
@endif

@if ($data['report_by'] == 'Access Code' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Access Code</p>
@endif

@if ($data['report_by'] == 'Called Number' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Called Number</p>
@endif

@if ($data['report_by'] == 'Hour Status' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Hour Status</p>
@endif

@if ($data['report_by'] == 'Frequency' )
<p align="right"; style="font-size:10px; margin-top:0;text-align:center"> {{$data['report_type']}} Report by Frequency</p>
@endif
</div>
<br>
@if( $data['report_by'] == 'Hour Status' )
    @include('frontend.report.callaccount.callaccount_per_hour')
@elseif( $data['report_by'] == 'Frequency' )
    @include('frontend.report.callaccount.callaccount_frequency')
@else
@if( $data['report_by'] != 'Room' )
    @include('frontend.report.callaccount.callaccount_grunt_total')
@endif

{{--@if( $data['call_sort'] == 'All' || $data['call_sort'] == 'Guest Call' )--}}
@if( in_array('All', $call_sort) ||in_array('Guest Call', $call_sort))
    @if( $data['report_by'] != 'Department' && $data['report_by'] != 'Access Code' )
        @include('frontend.report.callaccount.callaccount_grunt_total_guest')
    @endif
@endif

{{--@if( $data['call_sort'] == 'All' || $data['call_sort'] == 'Business Centre' )--}}
@if( in_array('All', $call_sort) ||in_array('Business Centre', $call_sort) )
    @if( $data['report_by'] != 'Room' && $data['report_by'] != 'Access Code' )
        @include('frontend.report.callaccount.callaccount_grunt_total_business_centre')
    @endif
    @if ($data['report_by'] == 'Property' || $data['report_by'] == 'Department' )
        @include('frontend.report.callaccount.callaccount_grunt_total_business_centre_by_building_department')
    @endif
@endif

{{--@if( $data['call_sort'] == 'All' || $data['call_sort'] == 'Admin Call' )--}}
@if( in_array('All', $call_sort) ||in_array('Admin Call', $call_sort) )
    @if( $data['report_by'] != 'Room' )
        @include('frontend.report.callaccount.callaccount_grunt_total_admin')
    @endif

    @if ($data['report_by'] == 'Property' || $data['report_by'] == 'Department' )
        @include('frontend.report.callaccount.callaccount_grunt_total_admin_by_building_department')
    @endif
@endif

@if ($data['report_by'] == 'Call Date' )

    @include('frontend.report.callaccount.callaccount_grunt_total_by_building_calldate')

@endif

@if ($data['report_by'] == 'Property' )
    @if ($data['report_type'] == 'Detailed')
        @include('frontend.report.callaccount.callaccount_detail_by_building_room_department')
    @endif
@else

    @if( (in_array('All', $call_sort) || in_array('Guest Call', $call_sort)) && $data['report_type'] == 'Detailed'&& ($data['report_by'] != 'Department' && $data['report_by'] != 'Access Code'))
        @include('frontend.report.callaccount.callaccount_detail_guest')
    @endif

    @if( (in_array('All', $call_sort) || in_array('Business Centre', $call_sort)) && $data['report_type'] == 'Detailed'&& ($data['report_by'] != 'Room'&& $data['report_by'] != 'Access Code'))
        @include('frontend.report.callaccount.callaccount_detail_business_centre')
    @endif

    @if( (in_array('All', $call_sort) || in_array('Admin Call', $call_sort)) && $data['report_type'] == 'Detailed'&& $data['report_by'] != 'Room')
        @include('frontend.report.callaccount.callaccount_detail_admin')
    @endif


@endif
@if ($data['report_by'] == 'Extension' )
    @if ($data['report_type'] == 'Summary')
        @include('frontend.report.callaccount.callaccount_summary_by_extension')
    @endif
@endif
@endif
{{--<div id="chart1" style="height: 300px; width: 500px; position: relative;" class="jqplot-target">--}}

{{--</div>--}}

{{--<script type="text/javascript" src="/frontpage/bower_components/jquery/dist/jquery.min.js"></script>--}}
{{--<script type="text/javascript" src="/frontpage/bower_components/jqplot/jquery.jqplot.js"></script>--}}
{{--<script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.json2.js"></script>--}}

{{--<script>--}}
{{--//    $(document).ready(function(){--}}
        {{--// Our data renderer function, returns an array of the form:--}}
        {{--// [[[x1, sin(x1)], [x2, sin(x2)], ...]]--}}
        {{--var sineRenderer = function() {--}}
            {{--var data = [[]];--}}
            {{--for (var i=0; i<13; i+=0.5) {--}}
                {{--data[0].push([i, Math.sin(i)]);--}}
            {{--}--}}
            {{--return data;--}}
        {{--};--}}

        {{--// we have an empty data array here, but use the "dataRenderer"--}}
        {{--// option to tell the plot to get data from our renderer.--}}
        {{--var plot1 = $.jqplot('chart1',[],{--}}
            {{--title: 'Sine Data Renderer',--}}
            {{--dataRenderer: sineRenderer--}}
        {{--});--}}
{{--//    });--}}


{{--</script>--}}





</div>


</body>
</html>