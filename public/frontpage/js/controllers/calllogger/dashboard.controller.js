app.config(['$compileProvider', function($compileProvider) {
    $compileProvider.imgSrcSanitizationWhitelist(/^\s*(https?|local|data):/);
}]);

app.controller('CallloggerDashboardController', function($scope, $rootScope, $http, $state, $httpParamSerializer, $window, AuthService, toaster, $timeout, $uibModal, $timeout, toaster, $interval) {
    var MESSAGE_TITLE = 'Guest Service Dashboard';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 50) + 'px; overflow-y: auto;';
    //$scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.box_height1 = 'height: ' + ($window.innerHeight - 70) + 'px; overflow-y: auto;';

    $scope.two_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 10) / 2;
    $scope.three_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 20) / 3;
    $scope.status_list=["Log out",
    "Online",
    "Available"];
    $scope.table_body_style = {
            "height": ($scope.two_height) + 'px'
        };
    
    $scope.d1_1 = [[1,1]];
    $scope.d1_2 = [[1,1]];
    $scope.d1_3 = [[1,1]];

    var average_time = [[1,1]];
    var call_count = [[1,1]];

    $scope.xaxis = {font: { color: '#ccc' }};
    $scope.yaxis = {font: { color: '#ccc' }};

    $scope.filter = {};

    $scope.loading = true;

    $scope.avg_time_answer = [{ data: average_time, label: 'Gouresh1', points: { show: true, radius: 6}, lines: { show: true, tension: 0.45, lineWidth: 3, fill: 0 } }];
    $scope.total_call_account = [{ data: call_count, label: 'Gouresh1', points: { show: true, radius: 6}, lines: { show: true, tension: 0.45, lineWidth: 3, fill: 0 } }];

    $rootScope.fullmode = false;

    $scope.filter.skill_group_tags = [];

    
    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;
        if( fullmode == true ) {
            $scope.two_height = ($window.innerHeight - 52 - 4 - 109 - 10 - 10) / 2;
            $scope.three_height = ($window.innerHeight - 52 - 4 - 109 - 10 - 20) / 3;
        }
        else
        {
            $scope.two_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 10) / 2;
            $scope.three_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 20) / 3;
        }

        //$('.nv-legend').attr('transform', 'translate(80px, 280px)');
        //$('.nv-legendWrap').attr('transform', 'translate(10,270)');

        $scope.call_status_options.chart.height = $scope.two_height - 20;
        $scope.agent_status_options.chart.height = $scope.two_height - 20;
        $scope.hourly_options.chart.height = $scope.two_height - 20;
        $scope.call_success_options.chart.height = $scope.three_height - 20;
        $scope.call_type_options.chart.height = $scope.three_height - 20;
        $scope.call_classify_options.chart.height = $scope.three_height - 20;

        $scope.table_body_style = {
            "height": ($scope.two_height) + 'px'
        };

        //$scope.getTicketStatistics();

    }
    $scope.onEnterCallModule = function() {
        //var agentstatus = {};
        //var profile = AuthService.GetCredentials();
        //agentstatus.agent_id = profile.id;
        //agentstatus.status = 'Online';
        //    $http({
        //        method: 'POST',
        //        url: '/frontend/call/changestatus',
        //        data: agentstatus,
        //        headers: {'Content-Type': 'application/json; charset=utf-8'}
        //    })
        //        .then(function(response) {
        //            //$rootScope.agent_status.status = response.data.status;
        //            //$rootScope.agent_status.created_at = response.data.created_at;
        //              $window.document.title = 'HotLync | Ennovatech';
        //        }).catch(function(response) {
        //            console.error('Gists error', response.status, response.data);
        //        })
        //        .finally(function() {
        //            $scope.isLoading = false;
        //        });
    }
 
    $scope.$watch('dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getTicketStatistics();
    });

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getTicketStatistics();
    });

    //$scope.timer = $interval(function() {
    //    $scope.getTicketStatistics();
    //}, 1000*1);

    //$scope.timer = $interval(function() {
    //    $window.location.reload();
    //}, 1000*60*30);



    $scope.$on('$destroy', function() {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        timePicker: true,
        timePickerIncrement: 5,
        format: 'YYYY-MM-DD HH:mm',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD HH:mm'),
        endDate: moment().format('YYYY-MM-DD HH:mm')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    //$scope.selected=0;
    $scope.onClick = function(row){
        if(!row.selected)
        row.selected=0;

        row.new_status=row.status;
        if(row.selected==0)
        row.selected=1;
        else
        row.selected=0;

    }
    $scope.onSelect = function(row) {
        if(row.new_status==row.status)
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select new status');  
            return
        }
        var request = {};
        request.status = row.new_status;
        request.extension = row.extension;
        request.agent_id = row.user_id;
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/call/changestatus',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Agent status has been changed successfully');
                row.status = row.new_status;
                row.selected=0;
                console.log(response);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to add Agent status');
                console.error('Log Out status error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
                
            });
    }

   
    var skill_group_list = [];
    function getSkillGroupList() 
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        var url = '/frontend/call/skillgrouplist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            skill_group_list = response.data;
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }

    getSkillGroupList();

    function getSkillGroupListUser() 
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        var url = '/frontend/call/skillgrouplistuser';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.skill_group_name = response.data;
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }

    getSkillGroupListUser();

    $scope.skillGroupTagFilter = function(query) {
        return skill_group_list.filter(function(item) {
            return item.group_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.openFilterPanel = function() {

            openFilter();
    }
    function openFilter() {
 
        var filter = {};
        var profile = AuthService.GetCredentials();
        filter.user_id = profile.id;
        filter.skill_group_ids = $scope.filter.skill_group_tags.map(item => item.id).join(",");
        console.log(filter);

        $http({
            method: 'POST',
            url: '/frontend/call/storecallcenterprofile',
            data: filter,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.dash_filter = response.data.profile;
            filter = response.data;
         //   $uibModalInstance.close(filter);
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    $scope.getTicketStatistics = function() {


        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.user_id = profile.id;

        $scope.filter.period = $scope.dateFilter;
        

        switch($scope.filter.period)
        {
            case 'Today':
                $scope.xaxis.ticks = [];
                for(var i = 0; i < 12; i++) {
                    var time = sprintf('%02d:00', i * 2);
                    $scope.xaxis.ticks.push([i, time]);
                }
                break;
            case 'Weekly':
                $scope.xaxis.ticks = [];
                for(var i = 0; i < 7; i++) {
                    var time = moment().subtract(6-i,'d').format('dddd');
                    $scope.xaxis.ticks.push([i, time]);
                }
                $scope.filter.during = 7;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Monthly':
                $scope.xaxis.ticks = [];
                for(var i = 0; i < 30; i++) {
                    var time = moment().subtract(29-i,'d').format('MM-DD');
                    $scope.xaxis.ticks.push([i, time]);
                }

                $scope.filter.during = 30;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                $scope.xaxis.ticks = [];
                for(var i = 0; i < 12; i++) {
                    var time = moment().subtract(11-i,'month').format('YYYY-MM');
                    $scope.xaxis.ticks.push([i, time]);
                }

                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Custom Days':
                $scope.filter.start_date = $scope.daterange.substring(0, '2016-01-01 00:00'.length);
                $scope.filter.end_date = $scope.daterange.substring('2016-01-01 00:00 - '.length, '2016-01-01 00:00 - 2016-01-01 00:00'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
               // $scope.filter.during = b.diff(a, 'DD HH:mm:ss');
                //window.alert($scope.filter.during);
                $scope.xaxis.ticks = [];
                for(var i = 0; i < $scope.filter.during; i++) {
                    var time = moment().subtract($scope.filter.during - 1 - i,'d').format('MM-DD');
                    $scope.xaxis.ticks.push([i, time]);
                }

                if( $scope.filter.during > 45 )
                {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 45 days");
                    return;
                }
                break;
        }

        $scope.filter.skill_group_ids = $scope.filter.skill_group_tags.map(item => item.id).join(",");
        $scope.filter_apply = $scope.filter.skill_group_tags.length > 0;

        $scope.loading = true;
        $http({
            method: 'POST',
            url: '/frontend/call/statistics',
            data: $scope.filter,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })        
            .then(function(response) {
                console.log(response.data);
                $scope.showStatistics(response.data);

            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.getTicketStatistics();

    function timeToSec(tm)
    {
        if( !tm )
            return 0;

        if( tm.length > 5 )
            return moment(tm + '', 'HH:mm:ss').diff(moment().startOf('day'), 'seconds');                

        return moment('00:' + tm + '', 'HH:mm:ss').diff(moment().startOf('day'), 'seconds');
    }

    function getWarningStyleForTime(threshold, key, val)
    {
        var yellow = timeToSec(threshold['call_center_' + key + '_yellow']);
        var red = timeToSec(threshold['call_center_' + key + '_red']);
        var value = timeToSec(val);
        var color = 'black';
        if( value < yellow )
            color = '';
        else if( value < red )
            color = '#f3a83b';
        else    
            color = '#eb3223';

        var style = {'color': color};    

        return style;
    }

    function getWarningStyleForNumber(threshold, key, val)
    {
        var yellow = parseInt(threshold['call_center_' + key + '_yellow']);
        var red = parseInt(threshold['call_center_' + key + '_red']);        
        var color = 'black';
        var value = parseInt(val);

        if( value < yellow )
            color = '';
        else if( value < red )
            color = '#f3a83b';
        else    
            color = '#eb3223';

        var style = {'color': color};    

        return style;
    }

    var threshold_setting = {};

    $scope.showStatistics = function(data) {
        $scope.total_queue_count = data.total_queue_count;
        $scope.total_answered_count = data.total_answered_count;
        $scope.total_abandoned_count = data.total_abandoned_count;
        $scope.total_count = data.total_count;
        
		$scope.Math = window.Math;
	
		
        $scope.total_callback_count = data.total_callback_count;
		$scope.total_missed_count = data.total_missed_count;
        $scope.total_outgoing_count = data.total_outgoing_count;
        $scope.total_dropped_count = data.total_dropped_count;
		$scope.m_count = $scope.total_answered_count+$scope.total_abandoned_count+$scope.total_missed_count;
		$scope.total_ans_percentage = ($scope.Math.round((data.total_answered_count/($scope.m_count))*100))||0;
		$scope.total_tta = moment(data.total_tta['total_tta'],'hh:mm:ss').format('mm:ss');
		$scope.total_att = moment(data.total_tta['total_att'],'hh:mm:ss').format('mm:ss');
		//window.alert($scope.total_tta);
        //$scope.total_follow_count = data.total_follow_count;

        $scope.datalist = data.agent_list;

        // set color for threshold
        var threshold = data.threshold;
        threshold_setting = threshold;
        $scope.datalist.map(row => {            
            row.avg_time_style = getWarningStyleForTime(threshold, 'avg_speed_answer', row.avg_time);
            row.time_call_style = getWarningStyleForTime(threshold, 'avg_handling_time', row.time_call);
            row.abandoned_style = getWarningStyleForNumber(threshold, 'abandoned', row.abandoned);
            row.wrapup_time_style = getWarningStyleForTime(threshold, 'acw_dur', row.wrapup_time);
            row.aux_dur_style = getWarningStyleForTime(threshold, 'aux_dur', row.aux_dur_time);
        });

		$scope.total_peak =(data.hourly_statistics['mcalls'].indexOf($scope.Math.max.apply($scope.Math, data.hourly_statistics['mcalls'])));
		if($scope.total_peak>0)
		{
			//$scope.total_peak++;
		
		 $scope.total_peak = moment($scope.total_peak, 'HH').format('HH:mm');
		 }
		 //$scope.total_au=data.summary_status['online'];
		 var total={all:0, busy:0};
		 //window.alert(data.summary_status.length);
		  angular.forEach(data.summary_status ,function(value,key){
			 var i=1;
			 //window.alert("key:"+key);
			 angular.forEach(value, function(value1,key1){
				 if(i!=1)
				 {
					// window.alert(key1);
				 total['all']=total['all']+parseInt(value1);
					if(key1=='busy')
					{
						total['busy']=total['busy']+parseInt(value1);
					}
				 }
				 else
				 {
					 i++;
				 }
			 });
		 });
		 
		 //window.alert(total['busy']);
		 //window.alert(total['all']);
		 $scope.total_au=($scope.Math.round((total['busy']/total['all'])*100))||0;
		 
        showCallStatisticsGraph(data.agent_list);
        showAgentStatisticsGraph(data.agent_list);
        showHourlyStatistics(data.hourly_statistics, data.agent_list);
        showSuccessStatistics(data.by_call_type);
        showCalltypeStatistics(data.by_call_type);
        showClassifyTypeStatistics(data.by_classify_type);
        //showRepeatStatistics(data.new_calls, data.repeat_calls);
    }

    $scope.statusIcon = function (val) {
        var style = '';
        if(val == 'Online') style = '<i class="icon-power"></i>&nbsp;&nbsp;Online';
        return style;
    }

    $scope.getDuration = function(row) {                
        var duration =  moment.utc(moment().diff(moment(row.created_at,"YYYY-MM-DD HH:mm:ss"))).format("HH:mm:ss");   
        // row.status = 'Busy';
        // duration = '00:00:15';
        row.duration_style = getWarningStyleForTime(threshold_setting, 'current_call_dur', duration);
                
        if( row.status != 'Busy' )
            row.duration_style = {};

        return duration;
    }

    $scope.onDownloadPDF = function() {
        html2canvas(document.querySelector("#capture")).then(canvas => {
            var data = canvas.toDataURL("image/png");
            var docDefinition = {
                content: [{
                    image: data,
                    width: 800,
                }],
                pageSize: 'A4',
                pageOrientation: 'landscape',
                pageMargins: [ 10, 10, 10, 10 ],
            };

            var filename = 'Call Center Dashboard ' + moment().format('YYYY-MM-DD') + '.pdf';
            pdfMake.createPdf(docDefinition).download(filename);
        });
    }

    $scope.$on('call_event', function(event, args) {
        console.log(args);
        $scope.getTicketStatistics();
    });

    $scope.$on('agent_status_change', function(event, args) {
        console.log(args);
        $scope.getTicketStatistics();
    });

    $scope.$on('callback_event', function(event, args) {
        console.log(args);
        if( !(args.user_id > 0) )
            $scope.getTicketStatistics();
    });

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

    $scope.call_status_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.two_height - 20,
            margin : {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
            },
            x: function(d){return d.label;},
            y: function(d){return d.value;},
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function(d){
                    return Math.round(d);
                },
                fontSize: 11
            },
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 5
                },
                fontSize: 7
            },
            legendPosition: 'bottom',
            stacked: true,
        },

        title: {
            enable: true,
            text: 'Call Statistics',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showCallStatisticsGraph(datalist) {


        var answered = [];
        var abandoned = [];
        var callback = [];
        var missed = [];

        for(var i = 0 ; i < datalist.length; i++)
        {
            answered.push({label: datalist[i].agent, value: Number(datalist[i].answered)});
            abandoned.push({label: datalist[i].agent, value: Number(datalist[i].abandoned)});
            callback.push({label: datalist[i].agent, value: Number(datalist[i].callback)});
            missed.push({label: datalist[i].agent, value: Number(datalist[i].missed)});
        }

        $scope.call_status_data = [
            {
                "key": "Answered",
                "color": "#27c24c",
                "values": answered
            },
            {
                "key": "Abandoned",
                "color": "#f05050",
                "values": abandoned
            },
            {
                "key": "Callback",
                "color": "#F89406",
                "values": callback
            },
            {
                "key": "Missed",
                "color": "#23b7e5",
                "values": missed
            },
        ]
    }

    $scope.agent_status_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.two_height - 20,
            margin : {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
            },
            x: function(d){return d.label;},
            y: function(d){return d.value;},
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function(d){
                    return Math.round(d);
                },
                fontSize: 11
            },
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 5
                }
            },
            //legendPosition: 'bottom',
            stacked: true,
        },

        title: {
            enable: true,
            text: 'Agent Statistics',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showAgentStatisticsGraph(datalist) {


        var online = [];
        var available = [];
        var on_break = [];
        var busy = [];
        var idle = [];
        var wrapup = [] ;
	var away = [];

        for(var i = 0 ; i < datalist.length; i++)
        {
            var gap1 = 0, gap2 = 0, gap3 = 0, gap4 = 0, gap5 = 0, gap6 = 0; var gap7 = 0;

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
            if( datalist[i].status == 'Wrapup')
                gap6 = elapse_time;
	    if( datalist[i].status == 'Away')
                gap7 = elapse_time;


            online.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].online) + gap1) / 60)});
            available.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].available) + gap2) / 60)});
            on_break.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].on_break) + gap3) / 60)});
            busy.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].busy) + gap4) / 60)});
            idle.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].idle) + gap5) / 60)});
            wrapup.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].wrapup) + gap6) / 60)});
	    away.push({label: datalist[i].agent, value: Math.round((Number(datalist[i].away) + gap7) / 60)});
        }

        $scope.agent_status_data = [
            {
                "key": "Online",
                "color": "#23b7e5",
                "values": online
            },
            {
                "key": "Avaliable",
                "color": "#27c24c",
                "values": available
            },
            {
                "key": "On Break",
                "color": "#6254b2",
                "values": on_break
            },
            {
                "key": "Busy",
                "color": "#f05050",
                "values": busy
            },
            {
                "key": "Idle",
                "color": "#f89406",
                "values": idle
            },
            {
                "key": "Wrapup",
                "color": "#beb411",
                "values": wrapup
            },
  	    {
                "key": "Away",
                "color": "#C71585",
                "values": away
            },
        ]
    }

    $scope.hourly_options = {
        chart: {
            type: 'lineChart',
            height: $scope.two_height - 20,
            margin : {
                top: 5,
                right: 20,
                bottom: 40,
                left: 55
            },
            x: function(d){ return d.x; },
            y: function(d){ return d.y; },
            xAxis: {
                ticks: [24],

            },
            yAxis: {
                ticks: [5],
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
            //forceY: value_max,
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 10
                }
            },
            //legendPosition: 'bottom',
            useInteractiveGuideline: true,
            dispatch: {
                stateChange: function(e){ console.log("stateChange"); },
                changeState: function(e){ console.log("changeState"); },
                tooltipShow: function(e){ console.log("tooltipShow"); },
                tooltipHide: function(e){ console.log("tooltipHide"); }
            },
            callback: function(chart){
                console.log("!!! lineChart callback !!!");
            },
            legendPosition: 'bottom',
        },
        title: {
            enable: true,
            text: 'Calls',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showHourlyStatistics(datalist, agentlist) {
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

        var answered = [];
        var abandoned = [];
        var missed = [];
        var tta = [];
        var waiting = [];
        
        for(var i = 0; i < datalist.calls.length; i++ )
        {
            answered.push({x:i, y:Number(datalist.answered[i])});
            abandoned.push({x:i, y:Number(datalist.abandoned[i])});
            missed.push({x:i, y:Number(datalist.missed[i])});
            tta.push({x:i, y:Number(datalist.tta[i])});
            waiting.push({x:i, y:Number(datalist.waiting[i])});

            if( answered[i].y > max ) max = answered[i].y;
            if( abandoned[i].y > max ) max = abandoned[i].y;
            if( missed[i].y > max ) max = missed[i].y;
            if( tta[i].y > max ) max = tta[i].y;
            if( waiting[i].y > max ) max = waiting[i].y;
        }

        var value_max = (Math.round(max / 10) + 1) * 10;

        $scope.hourly_data = [
            {
                values: answered,
                key: 'Answered',
                color: '#27c24c',
            },
            {
                values: abandoned,
                key: 'Abandoned',
                color: '#F44336',
            },
            {
                values: missed,
                key: 'Missed',
                color: '#FF9100',
            },
            {
                values: tta,
                key: 'TTA',
                color: '#23b7e5',
            },
            {
                values: waiting,
                key: 'Waiting',
                color: '#7266ba',
            },

        ];


    }

    $scope.call_success_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.three_height - 20,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 30
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,
            valueFormat: function(d){
                return Math.round(d);
            },
            duration: 500,
            xAxis: {
                fontSize: 8
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
        },
        title: {
            enable: true,
            text: 'Call Stats',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };


    function showSuccessStatistics(datalist) {

        $scope.call_success_data = [
            {
                key: "Cumulative Return",
                values: [
                    {
                        "label" : "Abandoned" ,
                        "value" : $scope.total_abandoned_count,
                        color: '#F44336',
                    } ,
                    {
                        "label" : "Answered" ,
                        "value" : $scope.total_answered_count,
                        color: '#4CAF50',
                    } ,
                    {
                        "label" : "Missed" ,
                        "value" : $scope.total_missed_count,
                        color: '#FF9100',
                    } ,
                    {
                        "label" : "Call Back" ,
                        "value" : $scope.total_callback_count,
                        color: '#FFEA00',
                    } ,
                ]
            }
        ]
    }

    $scope.call_type_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.three_height - 20,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 30
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,
            valueFormat: function(d){
                return Math.round(d);
            },
            duration: 500,
            xAxis: {
                fontSize: 8
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
        },
        title: {
            enable: true,
            text: 'Call Type',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showCalltypeStatistics(datalist) {


        $scope.call_type_data = [
            {
                key: "Cumulative Return",
                values: [
                    {
                        "label" : "Local" ,
                        "value" : Number(datalist.local),
                        color: '#9C27B0',
                    } ,
                    {
                        "label" : "Mobile" ,
                        "value" : Number(datalist.mobile),
                        color: '#3F51B5',
                    } ,
                    {
                        "label" : "Internal" ,
                        "value" : Number(datalist.internal),
                        color: '#FF5722',
                    } ,
                    {
                        "label" : "Intl" ,
                        "value" : Number(datalist.international),
                        color: '#009688',
                    } ,
                    {
                        "label" : "National" ,
                        "value" : Number(datalist.national),
                        color: '#ee900a',
                    } ,
                ]
            }
        ]
    }

    $scope.call_classify_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.three_height - 20,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 30
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
	    showControls: false,
            showValues: true,
            valueFormat: function(d){
                return Math.round(d);
            },
            duration: 500,
            xAxis: {
		showMaxMin: false,
                fontSize: 8
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
	    legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 5
                }
            },
            stacked: true,
        },
	/*
        title: {
            enable: true,
            text: 'Type',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
	*/
    };

    function showClassifyTypeStatistics(datalist) {

/*
        $scope.call_classify_data = [
            {
                key: "Cumulative Return",
                values: [
                    {
                        "label" : "Room Av" ,
                        "value" : Number(datalist.roomav),
                        color: '#9C27B0',
                    } ,
                    {
                        "label" : "Rate" ,
                        "value" : Number(datalist.rate),
                        color: '#3F51B5',
                    } ,
                    {
                        "label" : "Res BK" ,
                        "value" : Number(datalist.resbk),
                        color: '#FF9100',
                    } ,
                    {
                        "label" : "Recon" ,
                        "value" : Number(datalist.recon),
                        color: '#4CAF50',
                    } ,
                    {
                        "label" : "Con Bk" ,
                        "value" : Number(datalist.conbk),
                        color: '#23b7e5',
                    } ,
                    {
                        "label" : "DIGI" ,
                        "value" : Number(datalist.digi),
                        color: '#009688',
                    } ,
                    {
                        "label" : "Others" ,
                        "value" : Number(datalist.other),
                        color: '#FF5722',
                    } ,
                    
                ]
            }
        ]
*/
	 var requested = [], escalated = [], timeout = [];
        /////////
        var count_info = datalist;  
        for (var i = 0; i < count_info.length; i++) {
            requested.push({label: count_info[i].label, value: Math.round(Number(count_info[i].cnt))});
        }

        $scope.call_classify_data = [
        {
            "key": " Skill Group",
            "color": "#b32ebf",
            "values": requested
        }];    
	    
    }

    function showRepeatStatistics(new_calls, repeat_calls) {
        var s11 = [Number(repeat_calls), Number(new_calls)];
        $scope.calls2_count = s11;
        var xticks = ['Repeat', 'New'];
        var max = s11[0] > s11[1] ? s11[0] : s11[1];
        var yticks = getTicks(max, 5);
        var series_array = [{ label:'Answered'}, { label:"Abandoned"}, { label:"Call Back"}];
        $scope.calls2_option = {
            //title:'Calls',
			title:{
				text:"Calls",
				fontSize: 12,
			},
            stackSeries: true,
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                shadowAngle: 135,
                rendererOptions: {
                    highlightMouseDown: true,
                    barWidth: 25,
                },
                pointLabels: {show: true, formatString: '%d'}
            },
            grid: {
                drawGridLines: true,
                gridLineColor: '#6b6a6a',
                background: '#2a2a2a',
                borderColor: '#595959'
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.DateAxisRenderer,
                    ticks:  xticks,
                },
                yaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks:  yticks,
                    tickOptions:{
                        showGridline: false,
                        textColor: '#ffffff'
                    },
                },
            },
            seriesColors: [ "#9C27B0", "#3F51B5" ],
            series: series_array,

        };
    }

    $scope.onChangeCallStatusLogOut = function(row) {
        var agentstatus = {};
        var profile = AuthService.GetCredentials();
        agentstatus.agent_id = row.user_id;
        agentstatus.status = "Log out";
        agentstatus.extension = row.extension;
        agentstatus.property_id = profile.property_id;
            $http({
                method: 'POST',
                url: '/frontend/call/changestatus',
                data: agentstatus,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('success', MESSAGE_TITLE, 'Agent status has been Log Out successfully');
                    row.status = 'Log out';
                    console.log(response);
                }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Agent status');
                    console.error('Log Out status error', response.status, response.data);
                })
                .finally(function() {

                });

    }

});
