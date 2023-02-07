<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <link rel="stylesheet" href="/libs/assets/font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="/libs/assets/simple-line-icons/css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="/libs/angular/angular-material/angular-material.css" type="text/css" />
    <link rel="stylesheet" href="/libs/jquery/bootstrap/dist/css/bootstrap.css" type="text/css" />

    <!-- build:css css/app.material.css -->
    <link rel="stylesheet" href="/frontpage/css/material-design-icons.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/md.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/font.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/app.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/style.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/tabs.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/tabstyles.css" type="text/css" />
    <link rel="stylesheet" href="/frontpage/css/normalize.css" type="text/css" />

    <link rel="stylesheet" type="text/css" href="/frontpage/bower_components/jqplot/jquery.jqplot.min.css" />

    <style>
        tbody > tr > th, tfoot > tr > th, tbody > tr > td, tfoot > tr > td {
            /*padding: 1px 1px;*/
            text-align: center;
            vertical-align: middle;
        }

        .jqplot-target {
            color:#ffffff;
            background-color: #2a2a2a;
        }
        .jqplot-table-legend {
            /*background-color: #595959;*/
            background-color: #2a2a2a;
            font-size: 0.9em;
            border: 1px solid #2a2a2a;
            padding-top:1em;
            padding-left:1em;
        }
        .jqplot-title {
            padding-top: 0.6em;
            font-size: 1.2em;
        }

        div.jqplot-table-legend-swatch-outline {
            border: 1px solid #2a2a2a;
        }

    </style>

</head>

<?php
    $status_param = array(
        'Online' => array('icon_status' => 'bg-info', 'button_status' => 'label-info', 'button_icon' => 'icon-power'),
        'Available' => array('icon_status' => 'bg-success', 'button_status' => 'label-success', 'button_icon' => 'fa fa-check'),
        'Busy' => array('icon_status' => 'bg-danger', 'button_status' => 'label-danger', 'button_icon' => 'icon-earphones-alt'),
        'Ringing' => array('icon_status' => 'bg-danger', 'button_status' => 'label-danger', 'button_icon' => 'icon-call-in'),
        'Idle' => array('icon_status' => 'bg-warning', 'button_status' => 'label-warning', 'button_icon' => 'fa fa-clock-o'),
        'On Break' => array('icon_status' => 'bg-warning', 'button_status' => 'label-warning', 'button_icon' => 'fa fa-coffee'),
        'Log out' => array('icon_status' => 'bg-default', 'button_status' => 'label-default', 'button_icon' => 'fa fa-sign-out'),
        'Wrapup' => array('icon_status' => 'bg-primary', 'button_status' => 'label-primary', 'button_icon' => 'fa fa-pencil'),
    );
?>
<body layout="row">
    <div layout="column" flex>
        <div ui-view class="wrapper-md">
            <div style="background-color: #141414; height: 100vh; padding-bottom:55px ; margin: -11px; overflow-y: scroll;">
                <div class="wrapper-md bg-light lt" style="background-color: #141414">
                    <div class="col-sm-6 col-xs-12">
                        <h1 class="m-n font-thin h3 text-black" style="color:#337ab7">
                            <label style="font-size: 1.5vw"><i class="glyphicon glyphicon-dashboard"></i>
                                &nbsp;&nbsp;Agent Performance Dashboard
                            </label>
                        </h1>
                    </div>
                </div>

                <!-- / main header -->
                <div style="margin-top: 4px;">

                    <!-------- MAIN STATISTICS --------->
                    <div class="col-sm-12 wrapper-md1">
                        <div class="row row-sm text-center">
                            <div class="col-lg-2" >
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="font-thin h1 block" style="color:#f89406">{{$data['total_queue_count']}}</span>
                                    <span class="text-muted text-xs">Calls on Queue</span>
                                </a>
                            </div>
                            <div class="col-lg-1">
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="text-warning font-thin h1 block" >{{$data['total_callback_count']}}</span>
                                    <span class="text-muted text-xs">Callback</span>
                                </a>
                            </div>
                            <div class="col-lg-2">
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="text-success font-thin h1 block">{{$data['total_answered_count']}}</span>
                                    <span class="text-muted text-xs">Answered</span>
                                </a>
                            </div>
                            <div class="col-lg-2">
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="text-danger font-thin h1 block">{{$data['total_abandoned_count']}}</span>
                                    <span class="text-muted text-xs">Abandoned</span>
                                </a>
                            </div>
                            <div class="col-lg-2" >
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="font-thin h1 block" style="color:#f89406">{{$data['total_missed_count']}}</span>
                                    <span class="text-muted text-xs">Missed Call</span>
                                </a>
                            </div>
                            <div class="col-lg-1">
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="text-warning font-thin h1 block">{{$data['total_follow_count']}}</span>
                                    <span class="text-muted text-xs">Modify</span>
                                </a>
                            </div>
                            <div class="col-lg-2">
                                <a href class="block padder-v bg-dark" style="border-radius: 6px">
                                    <span class="text-info font-thin h1 block">{{$data['total_count']}}</span>
                                    <span class="text-muted text-xs">Total Calls</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>

                <!-------- AGENT STATISTICS --------->
                <!-------first part--------->
                <div class="col-md-5" style="padding-right: 0px">
                    <div class="col-md-12" style="background-color: #2a2a2a; margin-bottom: 10px; border-radius: 6px">
                        <div class="panel-body" style="padding: 0px">
                            <div  style="background-color: #2a2a2a">
                                <!--<h4 class="font-thin m-t-none m-b-none text-primary-lt" style="color: #ffffff">Agent Statistics</h4>-->
                                <div class="table table-responsive " >
                                    <table class="table ticket table-striped" style="margin-top: 3px">
                                        <thead>
                                        <tr class="tblhead-sm" style="font-size: .7vw">
                                            <th class="text-center" width="200px;">
                                                <b>Agent</b></th>
                                            <th class="text-center" width="10px;"><b>Status</b></th>
                                            <th class="text-center" width="70px;"><b>Duration</b></th>
                                            <th class="text-center" style="white-space: nowrap"><b>Avg Ans Time</b></th>
                                            <th class="text-center" style="white-space: nowrap"><b>Time on Call</b></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($data['agent_list'] as $key => $row )
                                        <tr style="height:1vw">
                                            <td class="text-center">
                                                <span class="thumb-sm avatar pull-left thumb-xs m-r-xs">
                                                    <img src="{{$row->picture}}" alt="...">
                                                    <i class="{{$status_param[$row->status]['icon_status']}} b-white bottom"></i>
                                                    <span class="hidden-xs hidden-sm">&nbsp;&nbsp;&nbsp;&nbsp;{{$row->agent}}</span>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <label class="label {{$status_param[$row->status]['button_status']}} m-l-xs">
                                                    <i class="{{$status_param[$row->status]['button_icon']}}"></i>&nbsp;&nbsp;{{$row->status}}
                                                </label>
                                            </td>
                                            <td class="text-center"></td>
                                            <td class="text-center"></td>
                                            <td class="text-center"></td>
                                        </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" style="background-color: #2a2a2a; border-radius: 6px">
                        <div class="panel-body" style="padding: 0px;">
                            <div id="call_statis_chart" style="height: 300px;" class="jqplot-target">
                            </div>
                        </div>
                    </div>
                </div>
                <!-------second part-------->
                <div class="col-md-5">
                    <div class="col-md-12" style="background-color: #2a2a2a;margin-bottom: 10px; border-radius: 6px">
                        <div class="panel-body" style="padding: 0px;">
                            <div id="agent_statis_chart" style="height: 300px;" class="jqplot-target"></div>
                        </div>
                    </div>
                    <div class="col-sm-12"  style="background-color: #2a2a2a; border-radius: 6px">
                        <div class="panel-body" style="padding: 0px;">
                            <div id="hourly_chart" style="height: 300px;" class="jqplot-target"></div>
                        </div>
                    </div>
                </div>
                <!-------third part--------->
                <div class="col-md-2" style="padding-left:0px">
                    <div class="col-sm-12"  style="background-color: #2a2a2a;margin-bottom: 10px; border-radius: 6px; padding:1px">
                        <div class="panel-body"  style="background-color: #2a2a2a;padding: 0px; margin: -1px; border-radius: 6px">
                            <div id="call_success_chart" style="height: 196px;padding: -2px; margin: 0px; border-radius: 6px" class="jqplot-target"></div>
                        </div>
                    </div>
                    <div class="col-sm-12"  style="background-color: #2a2a2a;margin-bottom: 10px; border-radius: 6px; padding:1px">
                        <div class="panel-body"  style="background-color: #2a2a2a;padding: 0px; margin: -1px; border-radius: 6px">
                            <div id="call_type_chart" style="height: 196px;padding: -2px; margin: 0px; border-radius: 6px" class="jqplot-target"></div>
                        </div>
                    </div>
                    <div class="col-sm-12"  style="background-color: #2a2a2a; border-radius: 6px">
                        <div class="panel-body"  style="background-color: #2a2a2a;padding: 0px;">
                            <div id="classify_chart" style="height: 196px;padding: -2px; margin: 0px; border-radius: 6px" class="jqplot-target"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" src="/frontpage/bower_components/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="/libs/jquery/moment/moment.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/jquery.jqplot.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.json2.js"></script>

    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.barRenderer.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.pieRenderer.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.cursor.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.highlighter.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.donutRenderer.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.pointLabels.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.enhancedLegendRenderer.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
    <script type="text/javascript" src="/frontpage/bower_components/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>

<script>
    var series_array = [{ label:'Answered'}, { label:"Abandoned"}, { label:"Call Back"}];

    function getTicks(max, count) {
        // calculate x-axis
        var base_scale = 10;
        var scale = [];
        for(var i = 0; i < 10; i++)
        {
            for(var j = 1; j < 10; j++)
            {
                scale.push(base_scale * j);
            }
            base_scale *= 10;
        }

        var start = 10;
        for( var i = 1; i < scale.length; i++)
        {
            if( scale[i] > max ) {
                start = scale[i];
                break;
            }
        }

        var xticks = [];
        start = start / 5;
        for(var i = 0; i < count; i++ )
        {
            xticks.push(String(start * i));
        }

        return xticks;
    }

    function showCallStatisticsGraph(datalist) {
        var s11 = [];
        var s12 = [];
        var s13 = [];
        var yticks = [];

        var max = 0;
        //for(var i = 0; i < datalist.length; i++)
        for(var i = datalist.length-1 ; i >= 0; i--)
        {
            s11.push(Number(datalist[i].answered));
            s12.push(Number(datalist[i].abandoned));
            s13.push(Number(datalist[i].callback));
            var sum = s11[datalist.length-1-i] + s12[datalist.length-1-i] + s13[datalist.length-1-i];
            if( sum > max )
                max = sum;
            yticks.push(datalist[i].agent);
        }

        var xticks = getTicks(max, 6);

        var series_array = [{ label:'Answered'}, { label:"Abandoned"}, { label:"Call Back"}];
        var plot1 = $.jqplot('call_statis_chart', [s11, s12, s13], {
            gridPadding: {bottom:50},
            //title:'Call Statistics',
            title:{
                text:"Call Statistics",
                fontSize: 12,
            },
            stackSeries: true,
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                shadowAngle: 135,
                rendererOptions: {
                    barDirection: 'horizontal',
                    highlightMouseDown: true,
                    barWidth: 12,
                },
                pointLabels: {show: true, formatString: '%d', location: 'n', hideZeros: true}
            },
            legend: {
                // This renderer is needed for advance legends.
                renderer: jQuery.jqplot.EnhancedLegendRenderer,
                show: true,
                location: 's',
                marginTop: '25px',
                placement: 'outside',
                // Breaks the ledgend into horizontal.
                rendererOptions: {
                    numberRows: '1',
                    numberColumns: '5'
                },
                seriesToggle: true
            },
            grid: {
                drawGridLines: true,
                gridLineColor: '#6b6a6a',
                background: '#2a2a2a',
                borderColor: '#2a2a2a'
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    //label: 'Call Status',
                    ticks:  xticks,
                    labelOptions: {
                        fontSize: '9pt'
                    }
                },
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks:  yticks,
                    tickOptions:{
                        showGridline: false,
                        textColor: '#ffffff'
                    },
                    //label:'Agent',
                    labelOptions: {
                        fontSize: '9pt'
                    }
                    //labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                },
            },
            seriesColors: [ "#27c24c", "#f05050", "#F89406","#29c7da","#91da29","#d829da"],
            series: series_array,

        });
    }

    function showAgentStatisticsGraph(datalist) {
        var s1 = [];
        var s2 = [];
        var s3 = [];
        var s4 = [];
        var s5 = [];
        var yticks = [];

        var max = 0;

        //for(var i = 0; i < datalist.length; i++)
        for(var i = datalist.length-1 ; i >= 0; i--)
        {
            var gap1 = 0, gap2 = 0, gap3 = 0, gap4 = 0, gap5 = 0;

            var elapse_time = 0 + moment.utc(moment().diff(moment(datalist[i].created_at,"YYYY-MM-DD HH:mm:ss")));
            elapse_time = elapse_time / 1000;

            if( datalist[i].status == 'Online')
                gap1 = elapse_time;
            if( datalist[i].status == 'Available')
                gap2 = elapse_time;
            if( datalist[i].status == 'On Break')
                gap3 = elapse_time;
            if( datalist[i].status == 'Busy')
                gap4 = elapse_time;
            if( datalist[i].status == 'Idle')
                gap5 = elapse_time;

            s1.push(Math.round((Number(datalist[i].online) + gap1) / 60));
            s2.push(Math.round((Number(datalist[i].available) + gap2) / 60));
            s3.push(Math.round((Number(datalist[i].on_break) + gap3) / 60));
            s4.push(Math.round((Number(datalist[i].busy) + gap4) / 60));
            s5.push(Math.round((Number(datalist[i].idle) + gap5) / 60));
            var sum = 0 + s1[datalist.length-1-i] + s2[datalist.length-1-i] + s3[datalist.length-1-i] + s4[datalist.length-1-i] + s5[datalist.length-1-i];
            if( sum > max )
                max = sum;
            yticks.push(datalist[i].agent);
        }

        var xticks = getTicks(max, 6);

        var series_array = [{ label:'Online'}, { label:"Available"}, { label:"On Break"}, { label:"Busy"}, { label:"Idle"}];

        var plot1 = $.jqplot('agent_statis_chart', [s1, s2, s3, s4, s5], {
            gridPadding: {bottom:50},
            title: {
                text:"Agent Statistics",
                fontSize: 12,
            },
            stackSeries: true,
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                shadowAngle: 135,
                rendererOptions: {
                    barDirection: 'horizontal',
                    highlightMouseDown: true,
                    barWidth: 12,
                },
                pointLabels: {show: true, formatString: '%d', location:'n', placement: 'outside', hideZeros: true}
            },
            legend: {
                // This renderer is needed for advance legends.
                renderer: jQuery.jqplot.EnhancedLegendRenderer,
                show: true,
                location: 's',
                marginTop: '25px',
                placement: 'outside',
                // Breaks the ledgend into horizontal.
                rendererOptions: {
                    numberRows: '1',
                    numberColumns: '5'
                },
                seriesToggle: true
            },
            grid: {
                drawGridLines: true,
                gridLineColor: '#6b6a6a',
                background: '#2a2a2a',
                borderColor: '#2a2a2a'
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    //label: 'Agent Status',
                    ticks:  xticks,
                    labelOptions: {
                        fontSize: '9pt'
                    }
                },
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks:  yticks,
                    tickOptions:{
                        showGridline: false,
                        textColor: '#ffffff'
                    },
                    //label:'Agent',
                    //labelOptions: {
                    //    fontSize: '9pt'
                    //}
                    //labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                },
            },
            seriesColors: [ "#23b7e5", "#27c24c", "#6254b2","#f05050","#f89406","#549c68"],
            series: series_array,
        });

    }

    function showHourlyStatistics(datalist, agentlist) {
        var line1 = [];
        var line2 = [];
        var line3 = [];
        var line4 = [];
        var line5 = [];
        var xticks = [];

        var scale = 0;
        for(var i = 0; i < agentlist.length; i++)
        {
            var sec = moment.duration(agentlist[i].avg_time, "HH:mm:ss: A").asSeconds();
            scale += sec;
        }

        if( agentlist.length > 0 )
            scale /= agentlist.length;

        if( scale < 1 )
            scale = 4;

        var max = 0;
        for(var i = 0; i < datalist.calls.length; i++ )
        {
            line1.push([i, Number(datalist.answered[i])]);
            line2.push([i, Number(datalist.abandoned[i])]);
            line3.push([i, Number(datalist.missed[i])]);
            line4.push([i, Number(datalist.tta[i]) / scale]);
            line5.push([i, Number(datalist.waiting[i]) / scale]);

            if( line1[i][1] > max ) max = line1[i][1];
            if( line2[i][1] > max ) max = line2[i][1];
            if( line3[i][1] > max ) max = line3[i][1];
            if( line4[i][1] > max ) max = line4[i][1];
            if( line5[i][1] > max ) max = line5[i][1];

            xticks.push(String(i));
        }

        var yticks = getTicks(max, 5);

        var series_array = [{ label:'Answered'}, { label:'Abandoned'}, { label:'Missed'}, { label:"TTA"}, { label:"Waiting Time"}];
        var plot1 = $.jqplot('hourly_chart', [line1,line2,line3,line4,line5], {
            gridPadding: {bottom:50},
            title:{
                text: "Calls",
                fontSize: 12,
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    //label: 'CALLS  TTA  Waiting Time',
                    ticks: xticks,
                    tickRenderer: $.jqplot.CanvasAxisTickRenderer,
                    tickOptions: {
                        // labelPosition: 'middle',
                        angle: 15
                    },
                    labelOptions: {
                        fontSize: '9pt'
                    }

                },
                yaxis: {
                    ticks: yticks,
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    labelOptions: {
                        fontSize: '9pt'
                    }
                }
            },
            legend: {
                // This renderer is needed for advance legends.
                renderer: jQuery.jqplot.EnhancedLegendRenderer,
                show: true,
                location: 's',
                marginTop: '25px',
                placement: 'outside',
                // Breaks the ledgend into horizontal.
                rendererOptions: {
                    numberRows: '1',
                    numberColumns: '5'
                },
                seriesToggle: true
            },
            grid: {
                drawGridLines: true,
                gridLineColor: '#6b6a6a',
                background: '#2a2a2a',
                borderColor: '#2a2a2a'
            },
            series: series_array,
            seriesColors: [ "#27c24c", "#F44336", "#FF9100","#23b7e5","#7266ba","#549c68"],
        });
    }

    function showSuccessStatistics(datalist, data) {
        var call_success = [];
        var unknown_cnt = 0;
        /*
         for(var i = 0; i < datalist.length; i++) {
         if( datalist[i].type == undefined || datalist[i].type == 'Unknown' ) {
         unknown_cnt += Number(datalist[i].cnt);
         continue;
         }

         call_success.push([datalist[i].type, Number(datalist[i].cnt)]);
         }

         call_success.push(['Unknown', unknown_cnt]);
         */
        call_success.push(['Abandoned', data.total_abandoned_count]);
        call_success.push(['Answered', data.total_answered_count]);
        call_success.push(['Missed', data.total_missed_count]);
        call_success.push(['Call Back', data.total_callback_count]);

        var plot1 = $.jqplot('call_success_chart', [call_success], {
            //title:'Call Success',
            title:{
                text: "Call Stats",
                fontSize: 12,
            },
            grid: {
                drawBorder: false,
                drawGridlines: false,
                background: '#2a2a2a',
                shadow:false
            },
            axes:{
                xaxis:{
                    renderer: $.jqplot.CategoryAxisRenderer
                }
            },
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    varyBarColor: true,
                    showDataLabels: true,
                    diameter: 70,
                }
            },
            legend: {
                show: false,
                rendererOptions: {
                    numberColumns: 1,
                    numberRows : 3
                },
                location: 'ne'
            },
            seriesColors: [ "#F44336", "#4CAF50","#FF9100", "#FFEA00"],
        });
    }

    function showCalltypeStatistics(datalist) {
        var call_type = [];

        call_type.push(['Local', Number(datalist.local)]);
        call_type.push(['Mobile', Number(datalist.mobile)]);
        call_type.push(['Internal', Number(datalist.internal)]);
        call_type.push(['Intern\r\national', Number(datalist.international)]);
        call_type.push(['National', Number(datalist.national)]);

        var plot1 = $.jqplot('call_type_chart', [call_type], {
            //title:'Destination',
            title:{
                text:"Origin",
                fontSize:12,
            },
            grid: {
                drawBorder: false,
                drawGridlines: false,
                background: '#2a2a2a',
                shadow:false
            },
            axes:{
                xaxis:{
                    renderer: $.jqplot.CategoryAxisRenderer
                }
            },
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    varyBarColor: true,

                }
            },
            legend: {
                show: false,
                rendererOptions: {
                    numberColumns: 1,
                    numberRows: 5
                },
                location: 'ne'
            },
            seriesColors: [ "#9C27B0", "#3F51B5", "#FF5722", "#FFEB3B", "#009688", "#ee900a"],
        });
    }

    function showClassifyTypeStatistics(datalist) {
        var classify = [];

        classify.push(['Booking', Number(datalist.booking)]);
        classify.push(['Inquiry', Number(datalist.inquiry)]);
        classify.push(['Other', Number(datalist.other)]);
        classify.push(['Followup', Number(datalist.followup)]);

        var plot1 = $.jqplot('classify_chart', [classify], {
            //title:'Destination',
            title:{
                text:"Type",
                fontSize:12,
            },
            grid: {
                drawBorder: false,
                drawGridlines: false,
                background: '#2a2a2a',
                shadow:false
            },
            axes:{
                xaxis:{
                    renderer: $.jqplot.CategoryAxisRenderer
                }
            },
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                rendererOptions: {
                    varyBarColor: true,

                }
            },
            legend: {
                show: false,
                rendererOptions: {
                    numberColumns: 1,
                    numberRows: 5
                },
                location: 'ne'
            },
            seriesColors: [ "#9C27B0", "#3F51B5", "#FF5722", "#FFEB3B", "#009688", "#ee900a"],
        });
    }


    var data = <?php echo json_encode($data); ?>

    showCallStatisticsGraph(data.agent_list);
    showAgentStatisticsGraph(data.agent_list);
    showHourlyStatistics(data.hourly_statistics, data.agent_list);
    showSuccessStatistics(data.by_call_type, data);
    showCalltypeStatistics(data.by_call_type);
    showClassifyTypeStatistics(data.by_classify_type);

</script>
</body>
</html>

