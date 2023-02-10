app.controller('LNFReportController', function($scope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page for guest service';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';


    iframe_style1 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 460) + 'px;';
    iframe_style2 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 100) + 'px;';
    
    $scope.data = {isHidden: false};    
    $scope.data.iframe_style =  iframe_style1;
    $scope.onGetFrameHeight = function() {
        if(!$scope.data.isHidden)  $scope.data.iframe_style =  iframe_style1;
        else  $scope.data.iframe_style =  iframe_style2;
    }

    $scope.tableState = undefined;
    $scope.param = '';

    // $scope.yearview = false;
    // $scope.monthview = false;
    // $scope.dateview = true;
    $scope.status_flag = false;
    $scope.escalated_flag = false;

    $scope.reporttypes = [
        'Detailed',
        'Summary',
        'Frequency'
    ];
    $scope.report_type = $scope.reporttypes[0];

    $scope.reportby_list = [
        'Complaint',
        'Sub-complaint',
        'Compensation',

    ];
    $scope.report_by = $scope.reportby_list[0];

    $scope.groupby_list = [
        'Date',
        'Location',
        'Guest Type',
        'Property',
        'Status',
        'Severity',
        //'Department',
        'Category',
        //'Sub-category',
    ];

    $scope.group_by = $scope.groupby_list[0];

    // var hour = moment().format("H");
    // var minute = moment().format("m");
    // var total_minute = parseInt(hour) * 60 + parseInt(minute);

    // $scope.myDatetimeRange =
    // {
    // date: {
    // from: new Date(), // start date ( Date object )
    // to: new Date() // end date ( Date object )
    // },
    // time: {
    // from: 0, // default start time (in minutes)
    // to: total_minute, // default end time (in minutes)
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
    // $scope.end_time = end_time;
    // $scope.time_range = start_time + ' - ' + end_time;
    // }

    // $scope.getTimeRange();

    // $scope.$watch('datetime.date', function(newValue, oldValue) {
    // if( newValue == oldValue )
    // return;

    // console.log(newValue);
    // $scope.datetime.time = moment(newValue).format('YYYY-MM');
    // });
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
            data.report_type = 'lnfreport';
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

    $scope.guest_type_tags = [
        {id: 1, label : 'Arrival'},
        {id: 2, label : 'House Complaint'},
        {id: 3, label : 'In-House CI'},
        {id: 4, label : 'In-House CO'},
        {id: 5, label : 'Walk-in'},
    ];
    $scope.guest_type_tags_hint = {buttonDefaultText: 'Select Guest Type'};
    $scope.guest_type_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.guest_type = [];

    $scope.status_tags = [
        {id: 0, label: 'Acknowledge'},
        {id: 1, label: 'Canceled'},
        {id: 2, label: 'Escalated'},
        {id: 3, label: 'Forwarded'},
        {id: 4, label: 'Pending'},
        {id: 5, label: 'Rejected'},
        {id: 6, label: 'Resolved'},
        {id: 7, label: 'Timeout'},
        {id: 8, label: 'Unresolved'},
    ];
    $scope.status_tags_hint = {buttonDefaultText: 'Select Status'};
    $scope.status_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.status = [];

    $scope.property_tags = [];

    $scope.category_view = false;
    $scope.category_tags = [];
    $scope.sub_category_tags = [];
    $scope.location_tags = [];
    $scope.main_category_tags = [];
    $scope.serverity_tags = [];
    $scope.department_tags = [];
    $scope.items;
    $scope.prop=[];
    $scope.prop_count=0;

    $scope.loadFiltersValue = function(query, value) {

        var filter = generateFilter();

        filter.filter_name = value;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        $scope.items= $http.get('/frontend/report/filterlist?' + param);
        if(value=='Property')
        {
            $scope.items.then(function(response){
                $scope.prop=response.data;
                response.data= response.data.map(function( element ) {
                    element=element.slice(0,element.indexOf(':'));
                    return element;
                });
            });
            $scope.prop_count = $scope.prop.length;
        }
        return $scope.items;
    };

    $scope.onChangeReportBy = function () {

        if($scope.report_by == 'Sub-complaint') {
            $scope.category_view = true;
            $scope.groupby_list = [
                'Date',
                'Location',
                'Guest Type',
                'Property',
                'Status',
                'Severity',
                'Department',
                'Category',
                'Sub-category',
            ];
        }else  if($scope.report_by == 'Complaint'){
            $scope.category_view = false;
            $scope.groupby_list = [
                'Date',
                'Location',
                'Guest Type',
                'Property',
                'Status',
                'Severity',
                'Category',
            ];
        }else {
            $scope.category_view = false;
            $scope.groupby_list = [
                'Date',
                'Location',
                'Guest Type',
                'Property',
                'Status',
                'Severity',
            ];
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

    function generateFiltersID(tags) {
        var report_tags = [];
        if( tags )
        {
            for(var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].id);
        }

        return JSON.stringify(report_tags);
    }

    $scope.getLocationList = function($query) {
        $scope.prop_len=0;
        if($scope.property_tags.length>0)
        {
            $scope.prop_len=$scope.property_tags.length;
        }
        var profile = AuthService.GetCredentials();
        var client_id =  profile.client_id;
        var req;
        if($scope.prop_len==0|| (($scope.prop_len)==($scope.prop_count))){
            //console.log("here");
            return $http.get('/list/locationtotallist?location=' +$query + '&client_id=' + client_id)
                .then(function(response){
                    // console.log(JSON.stringify(response));
                    var locations = response.data;
                    var list = [];
                    for(var i=0; i <  locations.length; i++) {
                        var check_val = false;
                        for(var j = 0, len = list.length; j < len; j++) {
                            if( list[ j ].key === locations[i].name +"_"+ locations[i].type) {
                                check_val = true;
                                continue;
                            }
                        }
                        if(check_val == false) {
                            list.push({'id':locations[i].id ,
                                'name': locations[i].name,
                                'type': locations[i].type,
                                'lg_id' : locations[i].lg_id,
                                'location_grp' : locations[i].location_grp,
                                'type_id' : locations[i].type_id,
                                'key':locations[i].name +"_"+ locations[i].type});
                        }
                    }
                    return locations = list;
                });
        }
        else{

            var n= $scope.checkPropID();


            return $http.get('/list/locationtotallistprop?location=' +$query + '&client_id=' + client_id+'&property_ids=' +n)
                .then(function(response){
                    // console.log(JSON.stringify(response));
                    var locations = response.data;
                    var list = [];
                    for(var i=0; i <  locations.length; i++) {
                        var check_val = false;
                        for(var j = 0, len = list.length; j < len; j++) {
                            if( list[ j ].key === locations[i].name +"_"+ locations[i].type) {
                                check_val = true;
                                continue;
                            }
                        }
                        if(check_val == false) {
                            list.push({'id':locations[i].id ,
                                'name': locations[i].name,
                                'type': locations[i].type,
                                'lg_id' : locations[i].lg_id,
                                'location_grp' : locations[i].location_grp,
                                'type_id' : locations[i].type_id,
                                'key':locations[i].name +"_"+ locations[i].type});
                        }
                    }
                    return locations = list;
                });
        }
    };
    $scope.checkPropID = function() {

        var i=0;
        $scope.prop_ids=[];
        $scope.property_tags.forEach(function(ele){
            $scope.prop.forEach(function(eles){
                if(ele.text==(eles.slice(0,eles.indexOf(':'))))
                {
                    $scope.prop_ids[i++]=(eles.slice((eles.indexOf(':'))+1,(eles.length)));
                }

            });
        });

        return $scope.prop_ids.join();

    }



    $scope.onGenerateReport = function() {
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/lnf_generatereport?' + $httpParamSerializer(filter);
    }
    $scope.onDownloadPDF = function() {
        var filter = generateFilter();
        filter.report_target = 'lnfreport';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function(type) {
        var filter = generateFilter();
        filter.excel_type = type;
        
        $window.location.href = '/frontend/report/lnf_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
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
        $scope.filter = {};
        var profile = AuthService.GetCredentials();
        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.report_type = $scope.report_type;
        $scope.filter.group_by = $scope.group_by;
        //var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;

        var statuslist = [];
        for(var i = 0; i < $scope.status.length; i++)
            statuslist.push($scope.status_tags[$scope.status[i].id].label);
        $scope.filter.status_tags = JSON.stringify(statuslist);

        var guest_typelist = [];
        for(var i = 0; i < $scope.guest_type.length; i++)
            guest_typelist.push($scope.guest_type_tags[$scope.guest_type[i].id - 1].label);
        $scope.filter.guest_type_tags = JSON.stringify(guest_typelist);

        $scope.filter.report_by = $scope.report_by;
        $scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;

        $scope.filter.property_tags = generateFilters($scope.property_tags);
        $scope.filter.location_tags = generateFiltersID($scope.location_tags);
        $scope.filter.department_tags = generateFilters($scope.department_tags);
        $scope.filter.serverity_tags = generateFilters($scope.serverity_tags);
        $scope.filter.main_category_tags = generateFilters($scope.main_category_tags);
        $scope.filter.category_tags = generateFilters($scope.category_tags);
        $scope.filter.sub_category_tags = generateFilters($scope.sub_category_tags);

        return $scope.filter;
    }
});



