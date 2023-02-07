app.controller('ComplaintsReportController', function ($scope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page for guest service';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';


    var iframe_style1 = { "height": ($window.innerHeight - 430) + 'px' };
    var iframe_style2 = { "height": ($window.innerHeight - 100) + 'px' };

    $scope.data = { isHidden: false };
    $scope.data.iframe_style = iframe_style1;
    $scope.data.csv_report_hide = true;

    $scope.onGetFrameHeight = function () {
        if (!$scope.data.isHidden) $scope.data.iframe_style = iframe_style1;
        else $scope.data.iframe_style = iframe_style2;
    }

    $scope.tableState = undefined;
    $scope.param = '';

    $scope.status_flag = false;
    $scope.escalated_flag = false;

    $scope.reporttypes = [
        'Detailed',
        'Summary',
        'Frequency',
        'Category'
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
        'Type',
        'Source',
        'Executive',
        'Open Feedback'
    ];

    $scope.group_by = $scope.groupby_list[0];

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

        $scope.$watch('time_range', function (newValue, oldValue) {
            if (newValue == oldValue)
                return;

            getTimeRange();
        });
    }


    function getTimeRange() {
        $scope.start_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
    }

    initTimeRanger();

    $scope.onShowSchedule = function () {
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
            if (schedule == undefined)
                return;
            var data = angular.copy(schedule);
            data.report_type = 'complaintreport';
            var profile = AuthService.GetCredentials();
            data.property_id = profile.property_id;
            data.submitter = profile.id;
            $http({
                method: 'POST',
                url: '/frontend/report/createschedulereport',
                data: data,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    console.log(response.data);
                    toaster.pop('success', MESSAGE_TITLE, 'Schedule report has been added successfully');
                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Schedule report');
                })
                .finally(function () {
                });

        }, function () {

        });
    }

    $scope.guest_type_tags = [
        { id: 1, label: 'Arrival' },
        { id: 2, label: 'House Complaint' },
        { id: 3, label: 'In-House' },
        { id: 4, label: 'Checkout' },
        { id: 5, label: 'Walk-in' },
    ];
    $scope.guest_type_tags_hint = { buttonDefaultText: 'Select Guest Type' };
    $scope.guest_type_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };
    $scope.guest_type = [];

    $scope.status_tags = [
        { id: 0, label: 'Acknowledge' },
        { id: 1, label: 'Canceled' },
        { id: 2, label: 'Escalated' },
        { id: 3, label: 'Forwarded' },
        { id: 4, label: 'Pending' },
        { id: 5, label: 'Rejected' },
        { id: 6, label: 'Resolved' },
        { id: 7, label: 'Timeout' },
        { id: 8, label: 'Unresolved' },
    ];
    $scope.status_tags_hint = { buttonDefaultText: 'Select Status' };
    $scope.status_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };
    $scope.status = [];

    $scope.sub_status_tags = [
        { id: 0, id1: 1, label: 'Pending' },
        { id: 1, id1: 2, label: 'Completed' },
        { id: 2, id1: 3, label: 'Escalated' },
        { id: 3, id1: 4, label: 'Re-routing' },
        { id: 4, id1: 5, label: 'Canceled' },
        { id: 5, id1: 6, label: 'Timeout' },
        { id: 6, id1: 7, label: 'Re-opened' },

    ];
    $scope.sub_status_tags_hint = { buttonDefaultText: 'Select Status' };
    $scope.sub_status_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };
    $scope.sub_status = [];

    $scope.property_tags = [];
    $scope.building_tags = [];
    $scope.feedback_type_tags = [];
    $scope.feedback_source_tags = [];

    $scope.group_by_view = true;
    $scope.category_view = false;
    $scope.category_tags = [];
    $scope.sub_category_tags = [];
    $scope.location_tags = [];
    $scope.location_type_tags = [];
    $scope.main_category_tags = [];
    $scope.serverity_tags = [];
    $scope.department_tags = [];
    $scope.guest_name_tags = [];
    $scope.guest_id_tags = [];
    $scope.guest_email_tags = [];
    $scope.guest_mobile_tags = [];
    $scope.items;
    $scope.prop = [];
    $scope.prop_count = 0;

    filter_value = {};

    function initFilterValues() {
        var request = {};

        // find assignee
        $http({
            method: 'POST',
            url: '/frontend/complaint/reportfilter',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            //console.log(response);
            filter_value = response.data;

            $scope.onChangeDepartment();
        }).catch(function (response) {
        })
            .finally(function () {

            });
    }

    initFilterValues();

    $scope.loadTagFilter = function (query, list, key) {
        return filter_value[list].filter(item =>
            item[key].toLowerCase().indexOf(query.toLowerCase()) != -1
        );
    }

    $scope.loadFiltersValue = function (query, value) {

        var filter = generateFilter();

        filter.filter_name = value;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        $scope.items = $http.get('/frontend/report/filterlist?' + param);
        if (value == 'Property') {
            $scope.items.then(function (response) {
                $scope.prop = response.data;
                response.data = response.data.map(function (element) {
                    element = element.slice(0, element.indexOf(':'));
                    return element;
                });
            });

            $scope.prop_count = $scope.prop.length;
        }

        return $scope.items;
    };

    var location_type_list = [];
    $http.get('/list/locationgroup')
        .then(function (response) {
            location_type_list = response.data;
        });

    $scope.getLocationTypeList = function (query) {
        return location_type_list;
    };

    $scope.onChangeReportType = function () {

        if ($scope.report_type == 'Summary') {
            $scope.reportby_list = [
                'Complaint',
                'Sub-complaint',
                'Compensation',
                'Consolidated',
                'Periodical',
            ];
        }
        else {
            $scope.reportby_list = [
                'Complaint',
                'Sub-complaint',
                'Compensation',
            ];
        }

        $scope.onChangeReportBy();
    }

    $scope.onChangeReportBy = function () {
        $scope.group_by_view = true;
        if ($scope.report_by == 'Sub-complaint') {
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
                'Type',
                'Source',
                'Response Rate',
            ];
        }
        else if ($scope.report_by == 'Complaint') {
            $scope.category_view = false;
            $scope.groupby_list = [
                'Date',
                'Location',
                'Guest Type',
                'Property',
                'Status',
                'Severity',
                'Category',
                'Type',
                'Source',
                'Monthly',
            ];
        }
        else if ($scope.report_by == 'Consolidated') {
            $scope.group_by_view = false;

            $scope.status = [];
            $scope.sub_status = [];

            $scope.property_tags = [];
            $scope.building_tags = [];
            $scope.feedback_type_tags = [];
            $scope.feedback_source_tags = [];
            $scope.category_tags = [];
            $scope.sub_category_tags = [];
            $scope.location_tags = [];
            $scope.location_type_tags = [];
            $scope.guest_name_tags = [];
            $scope.guest_id_tags = [];
            $scope.guest_email_tags = [];
            $scope.guest_mobile_tags = [];
            $scope.main_category_tags = [];
            $scope.serverity_tags = [];
            $scope.department_tags = [];
            $scope.items;
            $scope.prop = [];
            $scope.prop_count = 0;

            $scope.groupby_list = [
            ];
        }
        else if ($scope.report_by == 'Compensation' && $scope.report_type == 'Summary') {
            $scope.group_by_view = false;
            $scope.category_view = false;

        }
        else {
            $scope.category_view = false;
            //     if( $scope.report_by == 'Daily' || $scope.report_by == 'Monthly' )
            if ($scope.report_by == 'Periodical')
                $scope.group_by_view = false;

            $scope.groupby_list = [
                'Date',
                'Location',
                'Guest Type',
                'Property',
                'Status',
                'Severity',
                'Type',
                'Source',
                'Department',
            ];
        }

        $scope.data.csv_report_hide = $scope.report_by != 'Consolidated';
    }
    $scope.onChangeGroupBy = function () {
        if ($scope.group_by == "Open Feedback") {
            $scope.dateMonths(6)

            $scope.status_tags.forEach(element => {
                if(element.label == "Acknowledge" || element.label == "Pending")
                {
                    $scope.status.push(element)
                }
            })

            
        }
    }

    function generateFilters(tags) {
        var report_tags = [];
        if (tags) {
            for (var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].text);
        }

        return JSON.stringify(report_tags);
    }

    $scope.onChangeDepartment = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.dept_ids = $scope.department_tags.map(item => item.id);

        // find assignee
        $http({
            method: 'POST',
            url: '/frontend/complaint/getdeptloctypelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            //console.log(response);
            filter_value.dept_loc_list = response.data.dept_loc_list;
            filter_value.dept_loc_type_list = response.data.dept_loc_type_list;
        }).catch(function (response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
            .finally(function () {

            });
    }

    function generateFiltersID(tags) {
        var report_tags = [];
        if (tags) {
            for (var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].id);
        }

        return JSON.stringify(report_tags);
    }

    function generateFiltersIDKey(tags, key) {
        var report_tags = [];
        if (tags) {
            for (var i = 0; i < tags.length; i++)
                report_tags.push(tags[i][key]);
        }

        return JSON.stringify(report_tags);
    }

    $scope.onGenerateReport = function () {
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/complaintreport_generatereport?' + $httpParamSerializer(filter);
    }

    $scope.onDownloadPDF = function () {
        var filter = generateFilter();
        filter.report_target = 'complaintreport';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function (type) {
        var filter = generateFilter();
        filter.excel_type = type;

        $window.location.href = '/frontend/report/complaintreport_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.dateShort = function (period) {
        $scope.date_short = period;
        switch (period) {
            case 'Today': var start_time = moment().format('DD-MM-YYYY 00:00');
                var end_time = moment().format('DD-MM-YYYY HH:mm');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            case 'Yesterday': var start_time = moment().subtract(1, 'days').format('DD-MM-YYYY 00:00');
                var end_time = moment().subtract(1, 'days').format('DD-MM-YYYY 23:59');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            case 'This Week': var start_time = moment().startOf('week').format('DD-MM-YYYY 00:00');
                var end_time = moment().format('DD-MM-YYYY HH:mm');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            case 'Last Week': var start_time = moment().startOf('week').subtract(7, 'days').format('DD-MM-YYYY 00:00');
                var end_time = moment().startOf('week').subtract(1, 'days').format('DD-MM-YYYY 23:59');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            case 'This Month': var start_time = moment().startOf('month').format('DD-MM-YYYY 00:00');
                var end_time = moment().format('DD-MM-YYYY HH:mm');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            case 'Last Month': var start_time = moment().subtract(1, 'months').startOf('month').format('DD-MM-YYYY 00:00');
                var end_time = moment().startOf('month').subtract(1, 'days').format('DD-MM-YYYY 23:59');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            case 'This Year': var start_time = moment().startOf('year').format('DD-MM-YYYY 00:00');
                var end_time = moment().format('DD-MM-YYYY HH:mm');
                $scope.time_range = start_time + ' - ' + end_time;
                break;
            default:

        }
    }
    $scope.dateMonths = function (period) {
        var start_time = moment().subtract(period, 'months').format('DD-MM-YYYY 00:00');
        var end_time = moment().format('DD-MM-YYYY HH:mm');
        $scope.time_range = start_time + ' - ' + end_time;
    }

    $scope.report_date = new Date(moment().add(-1, 'days'));

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker',
    };


    $scope.open = function ($event) {
        $scope.dateOptions.minMode = $scope.report_by == 'Daily' ? 'day' : 'month';

        $scope.report_date_opened = true;
    };

    $scope.date_format = 'yyyy-MM-dd';

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
        for (var i = 0; i < $scope.status.length; i++)
            statuslist.push($scope.status_tags[$scope.status[i].id].label);
        $scope.filter.status_tags = JSON.stringify(statuslist);

        var substatuslist = [];
        for (var i = 0; i < $scope.sub_status.length; i++)
            substatuslist.push($scope.sub_status_tags[$scope.sub_status[i].id].id1);
        $scope.filter.sub_status_tags = JSON.stringify(substatuslist);

        var guest_typelist = [];
        for (var i = 0; i < $scope.guest_type.length; i++)
            guest_typelist.push($scope.guest_type_tags[$scope.guest_type[i].id - 1].label);
        $scope.filter.guest_type_tags = JSON.stringify(guest_typelist);

        $scope.filter.report_by = $scope.report_by;
        $scope.startTime = moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;

        $scope.filter.property_tags = generateFilters($scope.property_tags);
        $scope.filter.building_tags = generateFilters($scope.building_tags);
        $scope.filter.feedback_type_tags = generateFilters($scope.feedback_type_tags);
        $scope.filter.feedback_source_tags = generateFilters($scope.feedback_source_tags);
        $scope.filter.location_tags = generateFiltersID($scope.location_tags);
        $scope.filter.location_type_tags = generateFiltersID($scope.location_type_tags);
        $scope.filter.guest_name_tags = generateFilters($scope.guest_name_tags);
        //   window.alert($scope.guest_name_tags);
        $scope.filter.guest_id_tags = generateFilters($scope.guest_id_tags);
        $scope.filter.guest_email_tags = generateFilters($scope.guest_email_tags);
        $scope.filter.guest_mobile_tags = generateFilters($scope.guest_mobile_tags);
        $scope.filter.department_tags = generateFiltersID($scope.department_tags);
        $scope.filter.serverity_tags = generateFilters($scope.serverity_tags);
        $scope.filter.main_category_tags = generateFilters($scope.main_category_tags);
        $scope.filter.category_tags = generateFilters($scope.category_tags);
        $scope.filter.sub_category_tags = generateFilters($scope.sub_category_tags);
        $scope.filter.report_date = moment($scope.report_date).format('YYYY-MM-DD');

        return $scope.filter;
    }
});



