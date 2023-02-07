app.controller('CallclassifyReportController', function($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page';

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.checkdownload)) {
            $interval.cancel($scope.checkdownload);
            $scope.checkdownload = undefined;
        }
    });

    var profile = AuthService.GetCredentials();

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
   
    var iframe_style1 = {"height": ($window.innerHeight - 350) + 'px'};
    var iframe_style2 = {"height": ($window.innerHeight - 50) + 'px'};
    
    $scope.data = {isHidden: false};    
    $scope.data.iframe_style =  iframe_style1;
    $scope.onGetFrameHeight = function() {
        if(!$scope.data.isHidden)  $scope.data.iframe_style =  iframe_style1;
        else  $scope.data.iframe_style =  iframe_style2;
    }

    $scope.tableState = undefined;
    $scope.param = '';

    $scope.calltypes = [
        {id: 0, label: 'Local'},
        {id: 1, label: 'Mobile'},
        {id: 2, label: 'National'},
        {id: 3, label: 'International'},
    ];
    $scope.calltypes_hint = {buttonDefaultText: 'Select Call Type'};
    $scope.calltypes_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };

    $scope.call_type = [];

    $scope.approvals = [
        'All',
        'Waiting For Approval',
        'Pre-Approved',
        'Approved',
        'Rejected',
        'Returned',
        'Closed',
    ];
    $scope.approval = $scope.approvals[0];

    $scope.filterby = [
        'Mobile',
        'Extension',
        'User',
        'Department',
    ];
    $scope.filter_by = $scope.filterby[0];

    $scope.reporttypes = [
        'Detailed',
        'Summary',
    ];
    $scope.report_type = $scope.reporttypes[0];

    $scope.reportby_list = [
        'Call Date',
        'Call Status',
        'Department',
        'Destination',
        'Extension',
        'User',
        'Mobile',
        'Marked Date',
        'Comparison',
        'Cost Comparison',
        'Summary Cost Comparison',
    ];

    $scope.report_by = $scope.reportby_list[0];

    $scope.classifys = [
        'All',
        'Business',
        'Personal',
        'Unclassified',
    ];

    $scope.classify = $scope.classifys[0];
    $scope.callcharge_flag = true;

    $scope.extentionsorts = [
        'All',
        'LandLine',
        'Mobile',
    ];
    $scope.call_sort = [$scope.extentionsorts[0]];
    $scope.groupbysorts = [
        'Date',
        'User',
        'Department',
    ];
    $scope.group_by = [$scope.groupbysorts[0]];

    $scope.stafflist = [];
    $http.get('/frontend/call/stafflist?property_id=' + profile.property_id)
        .then(function(response) {
            for(var i = 0; i < response.data.length; i++) {
                var user = {};
                user.id = response.data[i].id;
                user.label = response.data[i].wholename;
                $scope.stafflist.push(user);
            }
        });

    $scope.stafflist_hint = {buttonDefaultText: 'Select User'};
    $scope.stafflist_hint_setting = {
        smartButtonMaxItems: 3,
        scrollable: true,
        scrollableHeight: '250px',
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };

    $scope.staff_ids = [];


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
    $scope.valid_flag = [true];

    $scope.onChangeReportType = function(){

        if($scope.report_type == 'Summary') {
            $scope.reportby_list = [
               'Call Date',
               'Call Status',
               'Department',
               'Extension',
               'Destination',
               'User',
               'Mobile',
               'Cost Comparison',
               'Summary Cost Comparison',
           ];
       }
       else{
        $scope.reportby_list = [
            'Call Date',
            'Department',
            'Extension',
            'Destination',
            'User',
            'Mobile',
            'Marked Date',
            'Comparison',
            'Cost Comparison'
        ];
       }
    }

    $scope.onChangeReportType();

    $scope.onChangeReportBy = function () {

       
        switch($scope.report_by) {
            case 'Call Date':
                $scope.valid_flag = [false];
                break;
            case 'Call Status':
                $scope.valid_flag = [false];
                break;
            case 'Department':
                $scope.valid_flag = [true];
                break;
            case 'Extension':
                $scope.valid_flag = [false];
                break;
            case 'Destination':
                $scope.valid_flag = [false];
                break;
            case 'User':
                $scope.valid_flag = [false];
                break;
            case 'Mobile':
                $scope.valid_flag = [false];
                break;
            case 'Comparison':
                $scope.valid_flag = [false];
                break;
            case 'Cost Comparison':
                $scope.valid_flag = [false];
                break;
            case 'Summary Cost Comparison':
                $scope.valid_flag = [false];
                break;
            case 'Marked Date':
                $scope.valid_flag = [false];
                break;
        }
    }

    $scope.onChangeReportBy();

    var property_id = profile.property_id;
    $scope.loadFilters = function(query, filter_name) {
        var filter = {};

        filter.property_id = profile.property_id;
        filter.filter_name = filter_name;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
    };
    $scope.onChangeRoom = function() {
        console.log($scope.room_tags);
    }

    $scope.approval_view = false;
    $scope.changeClassify = function () {
        if($scope.classify == 'Unclassified') $scope.approval_view = true;
        else $scope.approval_view = false;
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
    function generateFilter() {
        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.report_type = $scope.report_type;
        $scope.filter.call_sort = JSON.stringify($scope.call_sort);
        $scope.filter.group_by = $scope.group_by;
        //$window.alert($scope.filter.call_sort);
        var calltypelist = [];
        for(var i = 0; i < $scope.call_type.length; i++)
            calltypelist.push($scope.calltypes[$scope.call_type[i].id].label);
        $scope.filter.call_type = JSON.stringify(calltypelist);
        $scope.filter.classify = $scope.classify;
        $scope.filter.callcharge = $scope.callcharge_flag;

        var approvals =[];
        if($scope.approval != 'All')  approvals.push($scope.approval);
        // if($scope.approval == "Awaiting")
        //     approvals.push('Waiting For Approval');
        // if($scope.approval == "Closed")
        //     approvals.push('Closed');
        // if($scope.approval == "Approved")
        //     approvals.push('Approved');
        // if($scope.approval == "Unapproved") {
        //     approvals.push('Waiting For Approval');
        //     approvals.push('Rejected');
        //     approvals.push('Returned');
        // }
        // if($scope.approval == "Pre-Approved")
        //     approvals.push('Pre-Approved');

        if($scope.classify == 'Unclassified') approvals = [];

        $scope.filter.approval = JSON.stringify(approvals);

        // if($scope.approval == "Unmarked")
        //     $scope.filter.classify = 'Unclassified';

        $scope.filter.filter_by = $scope.filter_by;
        // window.alert($scope.filter_by)
        var stafflist = [];
        for(var i = 0; i < $scope.staff_ids.length; i++)
            stafflist.push($scope.staff_ids[i].id);
        $scope.filter.user_id = JSON.stringify(stafflist);
        $scope.filter.report_by = $scope.report_by;
      
        if($scope.report_by == 'Cost Comparison'){
          
            $scope.startTime = moment($scope.datetime.time,'YYYY-MM').format('YYYY-MM-01 00:00:00');
            $scope.endTime =moment($scope.datetime.time,'YYYY-MM').add(2,'M').endOf('month').format('YYYY-MM-DD 23:59:59');
          
          
        }
        else if($scope.report_by == 'Summary Cost Comparison'){
           
            $scope.startTime = moment($scope.datetime.time,'YYYY-MM').format('YYYY-MM-01 00:00:00');
            $scope.endTime =moment($scope.datetime.time,'YYYY-MM').add(2,'M').endOf('month').format('YYYY-MM-DD 23:59:59');
          
          
        }

        else if($scope.report_by == 'Marked Date'){
           
            $scope.startTime = moment($scope.datetime.time,'YYYY-MM').format('YYYY-MM-01 00:00:00');
            $scope.endTime =moment($scope.datetime.time,'YYYY-MM').add(0,'M').endOf('month').format('YYYY-MM-DD 23:59:59');
          //  $scope.callDate = moment($scope.datetime.time1,'YYYY-MM').format('YYYY-MM-01 00:00:00');
            
        }

       

        else{
          
            $scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
            $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        }
        
		
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;
        if ($scope.report_by == "Marked Date"){
        $scope.filter.calledstartdate = moment($scope.datetime.time1,'YYYY-MM').format('YYYY-MM-01 00:00:00'); 
        $scope.filter.calledenddate =moment($scope.datetime.time1,'YYYY-MM').add(0,'M').endOf('month').format('YYYY-MM-DD 23:59:59');
        }
   //     window.alert($scope.filter.calledstartdate);
   //     window.alert($scope.filter.calledenddate);

        if( $scope.valid_flag[0] )
            $scope.filter.section_tags = generateFilters($scope.section_tags);
        else
            $scope.filter.section_tags = [];

        if  (($scope.filter_by != 'User') || ($scope.report_by == 'Cost Comparison') || ($scope.report_by == 'Summary Cost Comparison') || ($scope.report_by == 'Mobile'))
            $scope.filter.department_tags = generateFilters($scope.department_tags);
        else
            $scope.filter.department_tags = [];
        if  (($scope.filter_by != 'Department' ) || ($scope.report_by == 'User') || ($scope.report_by == 'Mobile'))
            $scope.filter.staff_tags = generateFilters($scope.staff_tags);
          
        else
            $scope.filter.staff_tags = [];

        return $scope.filter;
    }

    $scope.onGenerateReport = function() {
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/callclassify_generatereport?' + $httpParamSerializer(filter);
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
            data.report_type = 'callclassify';
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
        filter.report_target = 'callclassify';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function(type) {
        var filter = generateFilter();
        filter.excel_type = type;

        $window.location.href = '/frontend/report/callcalssify_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }
	
	function initTimeRanger() {
        //if ($scope.report_by == 'Comparison'){
           // var start_time = moment().format('MM-YYYY 00:00');
       // var end_time = moment().format('MM-YYYY HH:mm');
       // }else{
        var start_time = moment().format('DD-MM-YYYY 00:00');
        var end_time = moment().format('DD-MM-YYYY HH:mm');
        //}
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
       // $window.alert($scope.report_by);
       // if ($scope.report_by == 'Comparison'){
        //$scope.start_time = $scope.time_range.substring(0, '01-2016 00:00'.length);
        //$scope.end_time = $scope.time_range.substring('01-2016 00:00 - '.length, '01-2016 00:00 - 01-2016 00:00'.length);
        //}else{
        $scope.start_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
        //}
    }


    initTimeRanger();

    $scope.$watch('datetime.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.datetime.time = moment(newValue).format('YYYY-MM');
    });

    $scope.$watch('datetime.date1', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.datetime.time1 = moment(newValue).format('YYYY-MM');
    });
   
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

       // $scope.start_time = start_time;
        //$scope.end_time = end_time;
       // $scope.time_range = start_time + ' - ' + end_time;
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
        //$scope.end_time = end_time;
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
