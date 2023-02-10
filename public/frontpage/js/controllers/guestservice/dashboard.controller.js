app.controller('GusetserviceDashboardController', function($scope, $rootScope, $http, $httpParamSerializer, $window, AuthService, toaster, $timeout, $uibModal, $interval, toaster, ImageUtilsService) {
    var MESSAGE_TITLE = 'Guest Service Dashboard';

    $scope.full_height = 'height: ' + ($window.innerHeight - 50) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    /***************************************/

    var chart_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 20) / 2;

    $scope.xaxis = {font: { color: '#ccc' }};
    $scope.yaxis = {font: { color: '#ccc' }};

    $scope.table_body_style = {
            "height": (chart_height) + 'px'
        };

    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;

        layoutChartGraph(fullmode);
    };

    $scope.selectedFilter = 'Total';

    $scope.onChangeFilterItem = function(item) {
        $scope.selectedFilter = item;

        if (item === 'Total') {
            $scope.status_data = $scope.old_status_data;
        } else {
            $scope.status_data = $scope.old_status_data.filter(sItem => {
                return sItem.key === item ? true : false;
            });
        }

    };

    $scope.refresh = $interval(function() {
        $scope.getTicketStatistics();
    }, 120 * 1000);

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.refresh)) {
            $interval.cancel($scope.refresh);
            $scope.refresh = undefined;
        }
    });


    $scope.dispatcherList = [];
    var todays = [];
    $scope.status_options = {
        chart: {
            type: 'lineChart',
            height: chart_height,
            margin : {
                top: 5,
                right: 10,
                bottom: 40,
                left: 30
            },
            x: function(d){ return d.x; },
            y: function(d){ return d.y; },
            useInteractiveGuideline: true,
            dispatch: {
                stateChange: function(e){ console.log("stateChange"); },
                changeState: function(e){ console.log("changeState"); },
                tooltipShow: function(e){ console.log("tooltipShow"); },
                tooltipHide: function(e){ console.log("tooltipHide"); }
            },
            xAxis: {
                //axisLabel: 'Time (ms)',
                //ticks: [24],
                tickFormat: function(d) {
                    return todays[d];
                    // if($scope.filter.period == 'Today')
                    //     return todays[d];
                    // else if($scope.filter.period == 'Weekly')
                    //     return dayofweeks[d];
                    // else
                    // return Math.round(d);

                }

            },
            yAxis: {
                ticks: [5],
                axisLabel: 'Count (v)',
                tickFormat: function(d){
                    //return d3.format('.02f')(d);
                    return Math.round(d);
                },
               // axisLabelDistance: -10
            },
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 10
                }
            },
            callback: function(chart){
                console.log("!!! lineChart callback !!!");
            },
            legendPosition: 'bottom',
        },
        title: {
            enable: true,
            text: 'Ticket Status',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };


    $scope.filter = {};

    /*Random Data Generator */
    $scope.ticketStatus = function(count_info) {
        var ontime = [],escalated = [],timeout = [], canceled=[], complaint = [], scheduled = [], hold = [];
        /////////
        for (var i = 0; i <  count_info.length; i++) {
            todays.push(count_info[i].ticket_count.xtime);
            ontime.push({x: i, y: Number( count_info[i].ticket_count.ontime)});
            escalated.push({x: i, y: Number(count_info[i].ticket_count.escalated)});
            timeout.push({x: i, y: Number(count_info[i].ticket_count.timeout)});
            canceled.push({x: i, y: Number(count_info[i].ticket_count.canceled)});
            scheduled.push({x: i, y: Number(count_info[i].ticket_count.scheduled)});
            hold.push({x: i, y: Number(count_info[i].ticket_count.hold)});
            complaint.push({x: i, y: Number(count_info[i].ticket_count.complaint)});
        }
        //Line chart data should be sent as an array of series objects.

        $scope.status_data = [
            {
                values: ontime,      //values - represents the array of {x,y} data points
                key: 'Ontime', //key  - the name of the series.
                color: '#27c24c'  //color - optional: choose your own line color.
            },
            {
                values: escalated,
                key: 'Escalated',
                color: '#FF9100'
            },
            {
                values: timeout,
                key: 'Timeout',
                color: '#F44336'

            },
            {
                values: canceled,
                key: 'Canceled',
                color: '#23b7e5'
            },
            {
                values: scheduled,
                key: 'Scheduled',
                color: '#6a93ee'
            },
            {
                values: complaint,
                key: 'Complaint',
                color: '#7266ba'
            },
            {
                values: hold,
                key: 'Hold',
                color: '#d4e157'
            }
        ];

        $scope.old_status_data = angular.copy($scope.status_data);
    };

    /***********departmnet**************/
    $scope.department_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: chart_height,
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
            text: 'Department',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.dept_count_list = [];

    $scope.ticketDepartment = function (datalist) {
        $scope.dept_count_list = datalist;
        var ontime = [], escalated = [], timeout = [], canceled = [], hold = [];
        /////////

        for (var i = 0; i < datalist.length; i++) {
            ontime.push({label: datalist[i].department, value: Math.round(Number(datalist[i].ontime))});
            escalated.push({label: datalist[i].department, value: Math.round(Number(datalist[i].escalated))});
            timeout.push({label: datalist[i].department, value: Math.round(Number(datalist[i].timeout))});
            canceled.push({label: datalist[i].department, value: Math.round(Number(datalist[i].canceled))});
            hold.push({label: datalist[i].department, value: Math.round(Number(datalist[i].hold))});
        }

        $scope.department_data = [
            {
                "key": "Ontime",
                "color": "#27c24c",
                "values": ontime
            },
            {
                "key": "Escalated",
                "color": "#FF9100",
                "values": escalated
            },
            {
                "key": "Timeout",
                "color": "#F44336",
                "values": timeout
            },
            {
                "key": "Canceled",
                "color": "#23b7e5",
                "values": canceled
            },
            {
                "key": "Hold",
                "color": "#7266ba",
                "values": hold
            }
        ]
    }
    /**************************task**********/
    $scope.task_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: chart_height,
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
            text: 'Task',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.ticketTask = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        for (var i = 0; i < datalist.length; i++) {
            requested.push({label: datalist[i].task, value: Math.round(Number(datalist[i].cnt))});
            escalated.push({label: datalist[i].task, value: Math.round(Number(datalist[i].escalated))});
            timeout.push({label: datalist[i].task, value: Math.round(Number(datalist[i].timeout))});
        }

        $scope.task_data = [
            {
                "key": "Requested",
                "color": "#27c24c",
                "values": requested
            },
            {
                "key": "Escalated",
                "color": "#FF9100",
                "values": escalated
            },
            {
                "key": "Timeout",
                "color": "#F44336",
                "values": timeout
            }];
    };


    $scope.category_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: chart_height,
            margin : {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
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
                fontSize: 11
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
	//
        title: {
            enable: true,
            text: 'Category',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
	//
    };



    $scope.ticketCategory = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        var count_info = datalist;
        for (var i = 0; i < count_info.length; i++) {
            requested.push({label: count_info[i].label, value: Math.round(Number(count_info[i].cnt))});
        }

        $scope.category_data = [
        {
            "key": " Category",
            "color": "#b32ebf",
            "values": requested
        }];
    };

    $scope.loading = true;

    $scope.$watch('dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getTicketStatistics();
        $scope.getGSDispatcherList();
    });

    $scope.$on("changed_auth_status", function(evt, data){
        $scope.getGSDispatcherList();
    });

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getTicketStatistics();
        $scope.getGSDispatcherList();
    });

    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.filter = {};

    $scope.getTicketStatistics = function() {
        todays = [];
        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.dateFilter;
        $scope.filter.user_id = profile.id;

        switch($scope.filter.period)
        {
            case 'Today':
                $scope.xaxis.ticks = [];
                let minute = moment().format('mm');
                for(var i = 0; i < 12; i++) {
                    var time = sprintf('%02d:' + minute, i * 2);
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
                $scope.filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
                $scope.filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
                $scope.filter.during = b.diff(a, 'days') + 1;

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

        var param = $httpParamSerializer($scope.filter);
        console.log(param);
        $scope.loading = true;
        $http.get('/frontend/guestservice/statistics?' + param)
            .then(function(response) {

                $scope.total = response.data.total;
                $scope.ticketStatus(response.data.count_info);
                $scope.ticketDepartment(response.data.by_department_count);
                $scope.ticketTask(response.data.by_task_count);
                $scope.ticketCategory(response.data.by_category_count);

            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    };

    $scope.getGSDispatcherList = function() {
        let profile = AuthService.GetCredentials();

        let request = {
            property_id: profile.property_id,
            period: $scope.dateFilter
        };

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getgsdispatcherlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.dispatcherList = response.data;
            }).catch(function(response) {
            console.error('gsdispatcher error', response.status, response.data);
        })
            .finally(function() {

            });
    };

    $scope.getTicketStatistics();
    $scope.getGSDispatcherList();

    $scope.onClickOnline = function(row) {
        var request = {};
        request.id = row.id;
        request.active_status = 0;

        $http({
            method: 'POST',
            url: '/frontend/user/setactivestatus',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                row.active_status = 0;
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    }

    $scope.department_table_body_style = {
            "height": chart_height + 'px'
        };

    var chart_view_options = [
                                $scope.status_options,
                                $scope.department_options,
                                $scope.task_options,
                                $scope.category_options ,
                            ];

    var table_view_style = [
                                $scope.department_table_body_style
                            ];

    $scope.expand_flag = [false, false, false, false, false];
    $scope.visible_flag = [true, true, true, true, true];
    $scope.table_view_visible = [false, false, false, false, false];

    $scope.onExpandDashboard = function(num) {
        $scope.expand_flag[num] = !$scope.expand_flag[num];
        for(var i = 0; i < $scope.expand_flag.length; i++)
        {
            if( $scope.expand_flag[num] == false )
            {
                $scope.visible_flag[i] = true;
            }
            else
            {
                $scope.visible_flag[i] = i == num;
            }
        }

        layoutChartGraph($rootScope.fullmode);
    }

    function layoutChartGraph(fullmode) {
        if( fullmode == true )
            chart_height = ($window.innerHeight - 55  - 4 - 109 - 10 - 20) / 2;
        else
            chart_height = ($window.innerHeight - 55  - 4 - 109 - 10 - 20) / 2;

        for(var i = 0; i < chart_view_options.length; i++)
        {

            if( $scope.expand_flag[i] )
            {
                if( fullmode == false )
                    chart_view_options[i].chart.height = $window.innerHeight-152;
                else
                    chart_view_options[i].chart.height = $window.innerHeight-116;
            }
            else
            {
                if( fullmode == false )
                    chart_view_options[i].chart.height = chart_height - 20;
                else
                    chart_view_options[i].chart.height = chart_height - 20;

                $scope.table_view_visible[i] = false;
            }
        }

        for(var i = 0; i < table_view_style.length; i++)
        {
            if( fullmode == false )
                table_view_style[i].height = $window.innerHeight-139;
            else
                table_view_style[i].height = $window.innerHeight-102;
        }

        $scope.table_body_style = {
                "height": (chart_height - 10) + 'px'
            };

        $timeout(function() {
            var chart_api = [
                                $scope.status_api,
                                $scope.department_api,
                                $scope.task_api,
                                $scope.category_api
                            ];

            for(var i = 0; i < chart_api.length; i++)
            {
                chart_api[i].refresh();
            }
        }, 100);
    }

    $scope.onChangeChart = function(num) {
        $timeout(function() {
            var chart_api = [
                                $scope.status_api,
                                $scope.department_api,
                                $scope.task_api,
                                $scope.category_api
                            ];

            chart_api[num].refresh();

        }, 100);
    }

    var exported_file_name = [
        'Status',
        'Department',
        'Task',
        'Category'
    ];

    function generateExportedFileName(num) {
        var start = '';
        var end = '';
        switch($scope.filter.period)
        {
            case 'Today':
                start = moment().format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
                break;
            case 'Weekly':
                start = moment().subtract(7, "days").format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
                break;
            case 'Monthly':
                start = moment().subtract(30, "days").format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                start = moment().subtract(1, "years").format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');
                break;
            case 'Custom Days':
                start = $scope.filter.start_date;
                end = $scope.filter.end_date;
                break;
        }

        var filename = start + '_' + end;

        return filename;
    }

    $scope.onExportToImage = function(num) {
        var selector = '';
        switch(num)
        {
            case 0:
                selector = '#status';
                break;
            case 1:
                selector = '#department';
                break;
            case 2:
                selector = '#task';
                break;
            case 3:
                selector = '#category';
                break;
        }

        var container = jQuery(selector),
        thesvg = container.find('svg');

        var cssStyleText = '';

        cssStyleText = '.tick line{ stroke: #e5e5e5; opacity: 0.2}';
        cssStyleText += 'svg text{ fill: white;}';
        if( num == 0 )  // black panel
            cssStyleText += 'svg{ background-color: #141414;}';    // panel background color
        else
            cssStyleText += 'svg{ background-color: #2a2a2a;}';    // panel background color

        cssStyleText += '.nvd3 .nv-legend text{ font: normal 10px Arial;}';
        cssStyleText += '.nvd3.nv-pie .nv-slice text{ fill: white !important;}';
        cssStyleText += '.nvd3 .nv-discretebar .nv-groups text, .nvd3 .nv-multibarHorizontal .nv-groups text{ fill: rgba(255,255,255,1);stroke: rgba(255,255,255,0);}';
        cssStyleText += '.status-view:hover{ color:#0b2ef4;}';

        if( num == 0 )  // black panel
            cssStyleText += '.nv-background rect{ fill: rgb(20, 20, 20)}';  // for linechart, default is black, so change it with custom color
        else
            cssStyleText += '.nv-background rect{ fill: rgb(42, 42, 42)}';  // for linechart, default is black, so change it with custom color


        var svgString = ImageUtilsService.getSVGString(thesvg[0], cssStyleText);

        var width = $window.innerWidth - 110;
        if( $rootScope.fullmode )
            width = $window.innerWidth - 60;

        var height = chart_view_options[num].chart.height;

        ImageUtilsService.svgString2Image(exported_file_name[num], svgString, width, height, 'png', save ); // passes Blob and filesize String to the callback

        function save( dataBlob, filesize ){
            var filename = exported_file_name[num];

            filename += generateExportedFileName(num);

            filename += '.png';

            saveAs( dataBlob, filename ); // FileSaver.js function
        }
    }


});
