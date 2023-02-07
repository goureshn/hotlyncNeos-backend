app.controller('AuditReportController', function($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page';

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.checkdownload)) {
            $interval.cancel($scope.checkdownload);
            $scope.checkdownload = undefined;
        }
    });

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    iframe_style1 = 'margin:0px;padding:0px;overflow:hidden;height: ' + ($window.innerHeight - 290) + 'px;';
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
        'Users',
        'Room',
        'Guest Rate Charges',
        'Extension',
        'Minibar',
    ];
    $scope.report_type = $scope.reporttypes[0];

    $scope.reportby_list = [];

    $scope.reportby_list_1 = [
        'Department',
        'Job Role',
        'Permission',
    ];

    $scope.reportby_list_2 = [
        'Building',
        'Room Type',
    ];

    $scope.reportby_list = $scope.reportby_list_1;
    $scope.report_by = $scope.reportby_list[0];

    $scope.onChangeReportType = function () {
        var reporttype = $scope.report_type;
        if(reporttype == 'Users' )  $scope.reportby_list = $scope.reportby_list_1;
        if(reporttype == 'Room' )  $scope.reportby_list = $scope.reportby_list_2;
        if(reporttype == 'Guest Rate Charges' || reporttype == 'Extension')  $scope.reportby_list = [];
        $scope.report_by = $scope.reportby_list[0];
    }
    
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

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
    $scope.loadFilters = function(query, filter_name) {
        var filter = {};
        filter.property_id = profile.property_id;
        filter.filter_name = filter_name;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
    };


    function generateFilter() {
        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.report_type = $scope.report_type;
        $scope.filter.report_by = $scope.report_by;

        return $scope.filter;
    }

    $scope.onGenerateReport = function() {
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/audit_generatereport?' + $httpParamSerializer(filter);
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
            data.report_type = 'audit';
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
        filter.report_target = 'audit';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function(type) {
        var filter = generateFilter();
        filter.excel_type = type;

        $window.location.href = '/frontend/report/audit_excelreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }
});




