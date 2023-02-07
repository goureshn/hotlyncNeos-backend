app.controller('HskpReportController', function($scope, $http, $uibModal, $httpParamSerializer, $window, $timeout, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
   
    $scope.iframe_style1 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 460) + 'px;';
    $scope.iframe_style2 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 100) + 'px;';
    
    $scope.data = {isHidden: false};    
    //$scope.data.iframe_style =  iframe_style1;
    $scope.onGetFrameHeight = function() {
        if(!$scope.data.isHidden)  $scope.data.iframe_style =  iframe_style1;
        else  $scope.data.iframe_style =  iframe_style2;
    }

    $scope.tableState = undefined;
    $scope.param = '';

    $scope.status_flag = false;

    $scope.reporttypes = [
        'Detailed',
        'Summary',
        'Task Sheet'
    ];
    $scope.report_type = $scope.reporttypes[0];

    $scope.reportby_list = [
        'Date',
        'Room',
        'Housekeeping Status',
        'Posted by',
        'Cleaning Status',
        'Roster Allocation',
        'Discrepancy'
    ];
    $scope.report_by = $scope.reportby_list[0];

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

            getTimeRange();
        });
    }

    
    function getTimeRange() {
        $scope.start_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
    }

    initTimeRanger();

    // pip
    $scope.isLoading = false;
    $scope.ticketlist = [];

    $scope.filter = {};
    
    $scope.report_tags = [];
    

    $scope.status_tags = [
        {id: 0, label: 'Clean Occupied'},
        {id: 1, label: 'Clean Vacant'},
        {id: 2, label: 'Inspected Vacant'},
        {id: 3, label: 'Inspected Occupied'},
        {id: 4, label: 'Dirty Vacant'},
        {id: 5, label: 'Dirty Occupied'},
        
    ];
    $scope.status_tags_hint = {buttonDefaultText: 'Select Status'};
    $scope.status_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.status = [];

    $scope.staff_tags = [];
    $scope.room_tags = [];
    $scope.room_type_tags = [];
    $scope.room_status_tags = [];
    $scope.occupancy_tags = [];
    $scope.res_status_tags = [];
    
    
    $scope.loadFiltersValue = function(query,value) {
        
        var filter = generateFilter();
    
        filter.filter_name = value;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
        
     };
 
    $scope.onChangeReportBy = function () {
        $scope.room_tags = [];
        $scope.staff_tags = [];
        $scope.status = [];
    }

    $scope.onChangeReportBy();

    $scope.onChangeRoom = function() {
        console.log($scope.room_tags);
    }
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

    function generateFilters(tags) {
        var report_tags = [];
        if( tags )
        {
            for(var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].text);
        }

        return JSON.stringify(report_tags);
    }

    $scope.onGenerateReport = function() {
    //    blockUI.stop();
    //    blockUI.start("Please wait while the report is being generated."); 

        var filter = generateFilter();

        //$scope.filter.report_type = $scope.report_type;
        //$scope.filter.report_by = $scope.report_by;
        //$scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        //$scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
		//window.alert($scope.start_time);
        //$scope.filter.start_time = $scope.startTime;
        //$scope.filter.end_time = $scope.endTime;

        
        console.log($httpParamSerializer(filter));

        $scope.param = '/frontend/report/hskp_generatereport?' + $httpParamSerializer($scope.filter);
    }

    $scope.onDownloadPDF = function() {
        var filter = generateFilter();
        filter.report_target = 'hskpreport';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }
    $scope.onDownloadExcel = function() {
        var filter = generateFilter();
        $window.location.href = '/frontend/report/hskp_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    function generateFilter() {
        $scope.filter = {};
        var profile = AuthService.GetCredentials();
        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.report_type = $scope.report_type;
        //$scope.filter.group_by = $scope.group_by;
        //var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;

        var statuslist = [];
        for(var i = 0; i < $scope.status.length; i++)
            statuslist.push($scope.status_tags[$scope.status[i].id].label);
        $scope.filter.status_tags = JSON.stringify(statuslist);

        $scope.filter.report_by = $scope.report_by;
		$scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;

       $scope.filter.staff_tags = generateFilters($scope.staff_tags);
       $scope.filter.room_tags = generateFilters($scope.room_tags);
       $scope.filter.room_type_tags = generateFilters($scope.room_type_tags);
       $scope.filter.room_status_tags = generateFilters($scope.room_status_tags);
       $scope.filter.occupancy_tags = generateFilters($scope.occupancy_tags);
       $scope.filter.res_status_tags = generateFilters($scope.res_status_tags);
        
        

        return $scope.filter;
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
            data.report_type = 'hskpreport';
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
                    toaster.pop('success', MESSAGE_TITLE, 'Schedule report has been added successfully');
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Schedule report');
                })
                .finally(function() {
                });

        }, function () {
           
        });
    }

    
    // $scope.myDatetimeRange =
    // {
        // date: {
            // from: new Date('2016-06-01'), // start date ( Date object )
                // to: new Date() // end date ( Date object )
        // },
        // time: {
            // from: 480, // default start time (in minutes)
                // to: 1020, // default end time (in minutes)
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
	
		

});

