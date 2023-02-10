app.controller('WakeupCallReportController', function($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page';

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.checkdownload)) {
            $interval.cancel($scope.checkdownload);
            $scope.checkdownload = undefined;
        }
    });

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    
    iframe_style1 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 400) + 'px;';
    iframe_style2 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 100) + 'px;';
    
    $scope.data = {isHidden: false};    
    $scope.data.iframe_style =  iframe_style1;
    $scope.onGetFrameHeight = function() {
        if(!$scope.data.isHidden)  $scope.data.iframe_style =  iframe_style1;
        else  $scope.data.iframe_style =  iframe_style2;
    }

    $scope.tableState = undefined;
    $scope.param = '';

    $scope.reporttypes = [
        'Detailed',
        'Summary',
    ];
    $scope.report_type = $scope.reporttypes[0];
    $scope.reportby_list = [
        'Date',
        'Room',
        'Status',
        
    ];
    $scope.report_by = $scope.reportby_list[0];

    $scope.agentlist = [];
    $scope.agentlist_hint = {buttonDefaultText: 'Select Agents'};
    $scope.agentlist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.agent_ids = [];

    $scope.statuslist = [
        {id: 'Success', label: "Success"},
        {id: 'Failed', label: "Failed"},
        {id: 'Canceled', label: "Canceled"},
        ];
    $scope.statuslist_hint = {buttonDefaultText: 'Select Status'};
    $scope.statuslist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.status_ids = [];



    var profile = AuthService.GetCredentials();
    $http.get('/frontend/call/agentlist?property_id=' + profile.property_id)
        .then(function(response) {
            $scope.agentlist = response.data;
        });
    $http.get('/build/list?property_id=' + profile.property_id)
        .then(function(response) {
            $scope.building_list = response.data;
            var all = {};
            all.id = 0;
            all.name = 'All';
            $scope.building_list.unshift(all);

            $scope.building_id = $scope.building_list[0].id;
        });

    // pip
    $scope.isLoading = false;
    $scope.ticketlist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.filter = {};

    $scope.report_tags = [];

    var property_id = profile.property_id;

    $scope.loadFilters = function(query, filter_name) {
        var filter = {};

        var profile = AuthService.GetCredentials();

        filter.property_id = profile.property_id;
        filter.filter_name = filter_name;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
    };

    $scope.onChangeRoom = function() {
        console.log($scope.room_tags);
    }

    function generateFilter() {
        var profile = AuthService.GetCredentials();

        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.building_id = $scope.building_id;
        $scope.filter.report_type = $scope.report_type;
        $scope.filter.report_by = $scope.report_by;
        //window.alert($scope.report_by);
        var agentlist = [];
        for(var i = 0; i < $scope.agent_ids.length; i++)
            agentlist.push($scope.agent_ids[i].id);
        $scope.filter.agent_tags = JSON.stringify(agentlist);
        var statuslist = [];
        for(var i = 0; i < $scope.status_ids.length; i++)
            statuslist.push($scope.status_ids[i].id);
        $scope.filter.status_tags = JSON.stringify(statuslist);
		$scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
		//window.alert($scope.start_time);
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;
        //$scope.filter.start_time = $scope.start_time;
        $scope.filter.call_date = $scope.call_date;
        //$scope.filter.end_time = $scope.end_time;
        $scope.filter.room_tags = generateFilters($scope.room_tags);
        return $scope.filter;
    }

    $scope.onGenerateReport = function() {
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/wakeupcall_generatereport?' + $httpParamSerializer(filter);
    }
    //$scope.onChangeReportBy();
    $scope.dateShort = function(period)
    {
        $scope.date_short = period;
        switch(period){
            case 'Today':       var start_time = moment().format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'Yesterday':   var start_time = moment().subtract(1, 'days').format('DD-MM-YYYY 00:00');
                                var end_time = moment().subtract(1, 'days').format('DD-MM-YYYY 23:59');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'This Week':   var start_time = moment().startOf('week').format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'Last Week':  var start_time = moment().startOf('week').subtract(7, 'days').format('DD-MM-YYYY 00:00');
                                var end_time = moment().startOf('week').subtract(1, 'days').format('DD-MM-YYYY 23:59');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'This Month':  var start_time = moment().startOf('month').format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'Last Month':  var start_time = moment().subtract(1,'months').startOf('month').format('DD-MM-YYYY 00:00');
                                var end_time = moment().startOf('month').subtract(1, 'days').format('DD-MM-YYYY 23:59');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'This Year':   var start_time = moment().startOf('year').format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            default:
            
        }
    }
    $scope.onShowSchedule = function() {
        var size = 'lg';
        var filter = generateFilter();
        var modalInstance = $uibModal.open({
            templateUrl: 'scheduleReportModal.html',
            controller: 'ScheduleReportController',
            size: size,
            resolve: {
                filter: function () {
                    return filter;
                }
            }
        });

        modalInstance.result.then(function (schedule) {
            if(schedule == undefined)
                return;
            var data = angular.copy(schedule);
            data.report_type = 'wakeupcall';
            var profile = AuthService.GetCredentials();
            data.property_id = profile.property_id;
            data.submitter = profile.id;
            $http({
                method: 'POST',
                url: '/frontend/report/createschedulereport',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    console.log(response.data);
                    toaster.pop('success', MESSAGE_TITLE, 'Shedule report has been added successfully');
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Shedule report');
                })
                .finally(function() {
                });

        }, function () {

        });
    }

    function generateFilters(tags) {
        var report_tags = [];
        if( tags )
        {
            for(var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].text);
        }

        return JSON.stringify(report_tags);
    }

    function getBase64FromImageUrl(url) {
        var img = new Image();

        img.setAttribute('crossOrigin', 'anonymous');

        img.onload = function () {
            var canvas = document.createElement("canvas");
            canvas.width =this.width;
            canvas.height =this.height;

            var ctx = canvas.getContext("2d");
            ctx.drawImage(this, 0, 0);

            var dataURL = canvas.toDataURL("image/png");

            console.log(dataURL);
        };

        img.src = url;
    }

    $scope.onDownloadPDF = function() {
        var filter = generateFilter();
        filter.report_target = 'wakeupcall';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function(type) {
        var filter = generateFilter();
        filter.excel_type = type;

        $window.location.href = '/frontend/report/wakeupcall_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }


    // $scope.myDatetimeRange =
    // {
        // date: {
            // from: new Date(), // start date ( Date object )
            // to: new Date() // end date ( Date object )
        // },
        // time: {
            // from: 0, // default start time (in minutes)
            // to: new Date(), // default end time (in minutes)
            // step: 15, // step width
            // minRange: 15, // min range
            // hours24: false // true = 00:00:00 | false = 00:00 am/pm
        // },
        // "hasDatePickers": true,
        // "hasTimeSliders": true
    // }
    // $scope.myDatetimeLabels =
    // {
        // date: {
            // from: 'Start date',
            // to: 'End date'
        // }
    // }

    // $scope.time_range = '';

    // $scope.$watch('myDatetimeRange.date.from', function(newValue, oldValue) {
        // if (newValue === oldValue)
            // return;
        // $scope.getTimeRange();
    // });
    // $scope.$watch('myDatetimeRange.date.to', function(newValue, oldValue) {
        // if (newValue === oldValue)
            // return;
        // $scope.getTimeRange();
    // });
    // $scope.$watch('myDatetimeRange.time.from', function(newValue, oldValue) {
        // if (newValue === oldValue)
            // return;
        // $scope.getTimeRange();
    // });
    // $scope.$watch('myDatetimeRange.time.to', function(newValue, oldValue) {
        // if (newValue === oldValue)
            // return;
        // $scope.getTimeRange();
    // });

    // $scope.getTimeRange = function() {
        // var start_time = moment($scope.myDatetimeRange.date.from)
            // .set({
                // 'hour' : 0,
                // 'minute'  : 0,
                // 'second' : 0
            // })
            // .add('minute', $scope.myDatetimeRange.time.from)
            // .format('YYYY-MM-DD HH:mm:ss');

        // var end_time = moment($scope.myDatetimeRange.date.to)
            // .set({
                // 'hour' : 0,
                // 'minute'  : 0,
                // 'second' : 0
            // })
            // .add('minute', $scope.myDatetimeRange.time.to)
            // .format('YYYY-MM-DD HH:mm:ss');

        ////$scope.start_time = start_time;
        ////$scope.end_time = end_time;
        ////$scope.time_range = start_time + ' - ' + end_time;
        // var time_range = $scope.time_range;
        // time_range= time_range.substr(21, time_range.length);
        // var hms = time_range.substr(12,time_range.length);
        // var d = new Date(time_range);
        // var h = d.getHours();
        // var m = d.getMinutes();
        // var s = d.getSeconds();
        // var end_time1 = moment($scope.myDatetimeRange.date.to)
            // .set({
                // 'hour' : 0,
                // 'minute'  : 0,
                // 'second' : 0
            // })
            // .add('hour', h)
            // .add('minute', m)
            // .add('second', s)
            // .format('YYYY-MM-DD HH:mm:ss');
        // $scope.start_time = start_time;
        ////$scope.end_time = end_time;
        // if(hms == "00:00:00" || hms == "") {
            // $scope.end_time = end_time;
            // $scope.time_range = start_time + ' - ' + end_time;
        // }else{
            // $scope.end_time = end_time1;
            // $scope.time_range = start_time + ' - ' + end_time1;
        // }
    // }

    // $scope.getTimeRange();
	function initTimeRanger() {
        var start_time = moment().format('DD-MM-YYYY 00:00');
        var end_time = moment().format('DD-MM-YYYY HH:mm');

        $scope.dateRangeOption = {
            timePicker: true,
            timePickerIncrement: 5,
            format: 'DD-MM-YYYY HH:mm',
            startDate: start_time,
            endDate: end_time
        };

        $scope.time_range = start_time + ' - ' + end_time;

        getTimeRange();

        $scope.$watch('time_range', function(newValue, oldValue) {
            if( newValue == oldValue )
                return;


       // $scope.start_time = start_time;
        //$scope.end_time = end_time;
        //$scope.time_range = start_time + ' - ' + end_time;

            getTimeRange();
        });
    }

    
    function getTimeRange() {
        $scope.start_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);

    }

    initTimeRanger();

});


