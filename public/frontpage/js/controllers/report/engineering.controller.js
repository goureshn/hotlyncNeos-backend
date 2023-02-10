app.controller('EngineeringReportController', function($scope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Shift Page';

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;

//    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.checkdownload)) {
            $interval.cancel($scope.checkdownload);
            $scope.checkdownload = undefined;
        }
    });


    var iframe_style1 = {"height": ($window.innerHeight - 450) + 'px'};
    var iframe_style2 = {"height": ($window.innerHeight - 160) + 'px'};

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
        'Work Request',
        'Work Order',
        
    ];
    $scope.report_by = $scope.reportby_list[0];

    $scope.wo_flag = false;

    // pip
    $scope.isLoading = false;
    $scope.ticketlist = [];

    $scope.filter = {};

    $scope.report_tags = [];
   
    $scope.staff_tags = [];
    $scope.status_tags = [];
    $scope.wo_status_tags = [];
    $scope.equip_tags = [];
    $scope.equip_id_tags = [];
    $scope.location_tags = [];
    $scope.category_tags = [];

    $scope.status_list = [
        { id: 1, name: 'Pending' },
        { id: 2, name: 'Assigned'},
        { id: 3, name: 'In Progress'},
        { id: 4, name: 'On Hold'},
        { id: 5, name: 'Completed'},
        { id: 6, name: 'Rejected'},
        { id: 7, name: 'Closed'},
        { id: 8, name: 'Pre-Approved'}
    ];

    $scope.statusNameFilter = function(query) {
        return $scope.status_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.wo_status_list = [
        { id: 1, name: 'Pending' },
        { id: 2, name: 'In Progress'},
        { id: 3, name: 'On Hold'},
        { id: 4, name: 'Completed'},
    ];

    $scope.wostatusNameFilter = function(query) {
        return $scope.wo_status_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

     $http.get('/list/equipmentlist')
    .then(function(response){
        $scope.equipment_list = response.data;
    });

    $scope.equipmentTagFilter = function(query) {
        return $scope.equipment_list.filter(function(item) {
         return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $http.get('/frontend/equipment/idlist')
           .then(function(response){
               $scope.equip_id_list = response.data;                              
           });
   
    $scope.equipIdTagFilter = function(query) {
           return $scope.equip_id_list.filter(function(item) {
             return item.equip_id.toLowerCase().indexOf(query.toLowerCase()) != -1;
           });
    }

    $http.get('/list/locationtotallisteng?client_id=' + profile.client_id + '&user_id=' + profile.id)
            .then(function(response){
                $scope.location_list = response.data; 
            });        

    $scope.locationTagFilter = function(query) {
        return $scope.location_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $http.get('/frontend/eng/repairrequest_getcategory_list?user_id='+ profile.id)
            .then(function(response){
                $scope.category_list = response.data.content;
            });

    $scope.categoryTagFilter = function(query) {
        return $scope.category_list.filter(function(item) {
            return item.category_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.onChangeReportBy = function () {
        $scope.report_tags = [];
    }

    $scope.loadFilters = function(query , option) {
        var filter = {};

        var profile = AuthService.GetCredentials();
        filter.property_id = profile.property_id;
        filter.filter_name = option;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
    };

    $scope.onGenerateReport = function() {
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));

        $scope.param = '/frontend/report/engineering_generatereport?' + $httpParamSerializer(filter);
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

    $scope.onDownloadPDF = function() {
        var filter = generateFilter();
        filter.report_target = 'engineering';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function(type) {
        var filter = generateFilter();
        filter.excel_type = type;
        
        $window.location.href = '/frontend/report/engineering_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }


   
	
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

    function generateFilter() {

        var profile = AuthService.GetCredentials();

        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.report_type = $scope.report_type;
        $scope.filter.report_by = $scope.report_by;
        $scope.filter.wo_flag = $scope.wo_flag;
        $scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
		//window.alert($scope.start_time);
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;

        var staff_tags = [];
        for(var i = 0; i < $scope.staff_tags.length; i++)
            staff_tags.push($scope.staff_tags[i].text);

        $scope.filter.staff_tags = JSON.stringify(staff_tags);

        var status_tags = [];
        for(var i = 0; i < $scope.status_tags.length; i++)
            status_tags.push($scope.status_tags[i].name);

        $scope.filter.status_tags = JSON.stringify(status_tags);

        var wo_status_tags = [];
        for(var i = 0; i < $scope.wo_status_tags.length; i++)
            wo_status_tags.push($scope.wo_status_tags[i].name);

        $scope.filter.wo_status_tags = JSON.stringify(wo_status_tags);

        var equip_tags = [];
        for(var i = 0; i < $scope.equip_tags.length; i++)
            equip_tags.push($scope.equip_tags[i].id);

        $scope.filter.equip_tags = JSON.stringify(equip_tags);

        var equip_id_tags = [];
        for(var i = 0; i < $scope.equip_id_tags.length; i++)
            equip_id_tags.push($scope.equip_id_tags[i].equip_id);

        $scope.filter.equip_id_tags = JSON.stringify(equip_id_tags);

        var location_tags = [];
        for(var i = 0; i < $scope.location_tags.length; i++)
            location_tags.push($scope.location_tags[i].id);

        $scope.filter.location_tags = JSON.stringify(location_tags);

        var category_tags = [];
        for(var i = 0; i < $scope.category_tags.length; i++)
            category_tags.push($scope.category_tags[i].id);

        $scope.filter.category_tags = JSON.stringify(category_tags);


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
            data.report_type = 'engineering';
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

});

