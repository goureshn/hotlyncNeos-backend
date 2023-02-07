app.controller('AlarmLogController', function ($scope, $rootScope, $http, $window,AuthService, liveserver, $uibModal, $httpParamSerializer) {
    var MESSAGE_TITLE = 'Alarms Setting';
    $scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto';

    var search_option = '';
    var profile = AuthService.GetCredentials();

    $scope.$on("get_alarm_log", function(evt, data){ 
        $scope.getDataList();
    });

    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.model_value = 1;

    $scope.department_flag = false;

     if(AuthService.isValidModule('app.alarm.alldept')) {
        $scope.department_flag = true;
    }

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(60, 'd').format('YYYY-MM-DD'),
        endDate: moment().subtract(-30, 'd').format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger_').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        //$scope.pageChanged();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger_').focus();
    }

    $scope.$watch('dateFilter', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        $scope.getDataList();
    });

    $scope.$watch('daterange', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        $scope.getDataList();
    });

    // Filter
    $scope.filter = {};

    // assignee filter
    $scope.filter.staff_tags = [];

    var user_list = [];
    $http.get('/list/user')
            .then(function (response) {
                user_list = response.data;
            });


    $scope.staffTagFilter = function(query) {
        return user_list.filter(function(item) {
            return item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.filter.status_name = 'All';
    $scope.status_list = [
        'All',
        'Active',
        'Update',
        'Clear',        
    ];

    // location filter 
    $scope.filter.alarm_name_tags = [];
    var alarm_name_list = [];   
    $http.get('/frontend/alarm/setting/getalarmgrouplist?property_id=' + profile.property_id)
            .then(function(response){
                alarm_name_list = response.data;
            });

    $scope.alarmNameTagFilter = function(query) {
        return alarm_name_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 25,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.searchtext = '';
    $scope.onSearch = function () {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }

    $scope.onPrevPage = function () {
        if ($scope.paginationOptions.numberOfPages <= 1)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onNextPage = function () {
        if ($scope.paginationOptions.totalItems < 1)
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if ($scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState

        $scope.isLoading = true;

        if (tableState != undefined) {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? tableState.sort.predicate : 'created_at';
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'asc' : 'desc';
        }


        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter = $scope.filter;
        request.searchoption = search_option;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.cond = 'log';

        request.dept_id = profile.dept_id;
        request.dept_flag = $scope.department_flag;

        request.created_ids = $scope.filter.staff_tags.map(item => item.id);
        request.status_name = $scope.filter.status_name;
        request.alarm_ids = $scope.filter.alarm_name_tags.map(item => item.id);
        
        $scope.filter_apply = $scope.filter.staff_tags.length > 0 || 
                                    $scope.filter.status_name != 'All' || 
                                    $scope.filter.alarm_name_tags.length > 0;

        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/getalarmnotifilist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if (tableState != undefined)
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;


                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.showStatus = function(row) {
        var status = '';
        if(row.status == '1' ) status = "Active";
        if(row.status == '2' ) status = "Update"; // change form check to update
        if(row.status == '3' ) status = "Clear";
        return status;
    };

    $scope.showPermission= function(item) {
        var status = "";
        if(item == '1' ) status = "Active";
        if(item == '2' ) status = "Update"; //change from check to update
        if(item == '3' ) status = "Clear";
        return status;
    };
    /*
    $scope.onShowUsers = function (row) {
        $scope.$broadcast('alarm_log_users', row);
    };
    */
    $scope.onShowUsers = function(item) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/alarm/modal/show_userlists.html',
            controller: 'AlarmShowLogUserListController',
            windowClass: 'app-modal-window',
            resolve: {
                item: function () {
                    return item;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;                    
        }, function () {    
            
        });
    }

    $scope.onShowReportDialog = function(report_by) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/alarm/modal/alarm_report_dialog.html',
            controller: 'AlarmReportController',
            windowClass: 'app-modal-window',
            resolve: {
                
            }
        });
        modalInstance.result.then(function (report) {
             if( report.format == 'excel' )
                downloadExcelReport(report);           
            if( report.format == 'pdf' )
                downloadPDFReport(report);            
        }, function () {    
            
        });
    }

    function downloadExcelReport(report)
    {
        var request = {};
        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        
        request.created_ids = $scope.filter.staff_tags.map(item => item.id).join(",");
        request.status_name = $scope.filter.status_name;
        request.alarm_ids = $scope.filter.alarm_name_tags.map(item => item.id).join(",");
        request.report_by = report.report_by;
        request.dept_id = profile.dept_id;
        request.dept_flag = $scope.department_flag;

        console.log(request.dept_flag);
                                    
        request.excel_type = 'excel';

        $window.location.href = '/frontend/alarm/dash/exportlog?' + $httpParamSerializer(request);       
    }

    function downloadPDFReport(report)
    {
        var profile = AuthService.GetCredentials();

        var filter = {};
        filter.property_id = profile.property_id;
        filter.user_id = profile.id;

        filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        filter.created_ids = $scope.filter.staff_tags.map(item => item.id).join(",");
        filter.status_name = $scope.filter.status_name;
        filter.alarm_ids = $scope.filter.alarm_name_tags.map(item => item.id).join(",");
        filter.report_by = report.report_by;
        filter.report_target = 'alarm_report';  
        filter.dept_id = profile.dept_id;
        filter.dept_flag = $scope.department_flag;   
        console.log(filter.dept_flag);   
        
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }

    $scope.exportPDF = function(row) {
        
            var profile = AuthService.GetCredentials();
    
            var filter = {};
            filter.property_id = profile.property_id;
            filter.user_id = profile.id;
            filter.report_target = 'alarm_report_clear';  
            filter.id = row.id;   
            filter.name = row.alarm_name 
            
            $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }

   

});


app.controller('AlarmLogUserController', function ($scope, $http, $window, $timeout, toaster, AuthService, $uibModal) {
    $scope.notifi = {};
    $scope.userlist = [];
    $scope.notification_id = 0 ;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;
    $scope.$on("alarm_log_users", function(evt, row){ 
        $scope.notifi = row;
        $scope.notification_id = row.id;
        $scope.getUserList();
    });

    $scope.isLoading = false;

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
    
    $scope.getUserList = function getUserList(tableState) {

        $scope.isLoading = true;
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? 'id' : 'id' ;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.notification_id = $scope.notification_id;
        request.user_id = user_id;
        request.property_id = property_id;
        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/getalarmnotifiuserlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.userlist = response.data.datalist;                
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Alarm error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.showPermission= function(item) {
        var status = "";
        if(item == '1' ) status = "Acknowledge";
        if(item == '2' ) status = "Update"; // change to from check to update
        if(item == '3' ) status = "Clear";
        if(item == '0' ) status = " ";
        return status;
    };

});


app.controller('AlarmShowLogUserListController', function ($scope,$http, $rootScope, $uibModalInstance, toaster,AuthService, item, $filter) {
    $scope.item = item; 
    var profile = AuthService.GetCredentials();    
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;
    $scope.search_notify_text = '';
    $scope.bLoadedAll = false;

    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.onSearchUser = function () {
        $scope.getUserList();
    }

    $scope.onLoadMore = function() {
        if ($scope.bLoadedAll === true) {
            return;
        }
        $scope.paginationOptions.pageNumber ++;

        var request = {};

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.notification_id = item.id;
        request.user_id = user_id;
        request.property_id = property_id;
        request.search_notify_text = $scope.search_notify_text;

        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/getalarmnotifiuserlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function(response) {
            $scope.userlist = $scope.userlist.concat(response.data.datalist);
            if (response.data.datalist.length < request.pagesize) {
                $scope.bLoadedAll = true;
            }
        }).catch(function(response) {
            console.error('Alarm error', response.status, response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
    
    $scope.getUserList = function getUserList(tableState) {

        $scope.isLoading = true;
        $scope.bLoadedAll = false;
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? 'id' : 'id' ;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.notification_id = item.id;
        request.user_id = user_id;
        request.property_id = property_id;
        request.search_notify_text = $scope.search_notify_text;
        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/getalarmnotifiuserlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if (response.data.datalist.length < request.pagesize) {
                    $scope.bLoadedAll = true;
                }

                $scope.userlist = response.data.datalist;
                $scope.created_at = response.data.created_at;
                $scope.retry_count = response.data.retry_count;
                $scope.alarm_name = $scope.item.alarm_name;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Alarm error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.update_list = [];
 
    $scope.getAlarmUpdate = function(notification_id) {
        //here you could create a query string from tableState

        $scope.isLoading = true;
     
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.notification_id = notification_id;

        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/getalarmupdatelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.update_list = response.data.datalist;
           
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };



    $scope.save = function () {
        $scope.item.status_comment = $scope.status_comment;
        $uibModalInstance.close('cancel');
    };
    $scope.cancel = function () {
        $scope.item.status_comment = $scope.status_comment;
        $uibModalInstance.dismiss('cancel');
    };
});

app.controller('AlarmReportController', function ($scope,$http, $uibModalInstance, AuthService) {
    $scope.report_by_list = ['Detail', 'Summary'];
    $scope.report = {};
    $scope.report.report_by = $scope.report_by_list[0];
    $scope.report.format = 'excel';

    $scope.onDownloadReport = function () {
    
        $uibModalInstance.close($scope.report);
    };
    $scope.cancel = function () {        
        $uibModalInstance.dismiss('cancel');
    };
});











