app.controller('AlarmActiveController', function ($scope, $rootScope, $http, $window, $timeout, toaster, AuthService, $uibModal) {
    var MESSAGE_TITLE = 'Alarms Setting';
    $scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto';

    var search_option = '';

    var profile = AuthService.GetCredentials();    
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;


    $scope.department_flag = false;

     if(AuthService.isValidModule('app.alarm.alldept')) {
        $scope.department_flag = true;
    }

    $scope.$on("get_alarm_active", function(evt, data){ 
        $scope.getDataList();
    });

    $scope.$on("serach_active", function(evt, row){
        if(row) {
            search_option = row.name;
            $scope.searchtext = row.name;
        }
        $scope.getDataList();
    });


    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.model_value = 1;

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(60, 'd').format('YYYY-MM-DD'),
        endDate: moment().subtract(-30, 'd').format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        //$scope.pageChanged();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger').focus();
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

    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 25,
        sort: 'desc',
        field: 'created_at',
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
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'desc';
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
        request.dept_id = profile.dept_id;
        request.dept_flag = $scope.department_flag;

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
                // else
                //     $scope.tableState.pagination.numberOfPages = numberOfPages;

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
        if(row.status == '2' ) status = "Update";//from check to update
        if(row.status == '3' ) status = "Clear";
        return status;
    };

    $scope.onShowUsers = function (row) {
        $scope.$broadcast('notifi_show_users', row);
    };

    $scope.showPermission= function(item) {
        var status = "";
        if(item == '1' ) status = "Active";
        if(item == '2' ) status = "Update";//fro check to update
        if(item == '3' ) status = "Clear";
        return status;
    };

    // $scope.checkPermission= function(item, row) {
    //     var permission = row.permission;
    //     var val = false;
    //     if(permission.includes(item)) val = true;
    //     return val;
    // };

    $scope.changeStatus = function(item,val, $event) {
        $event.stopPropagation();
        $event.preventDefault();
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/alarm/modal/send_notifi.html',
            controller: 'AlarmSendNotifiController',
            windowClass: 'app-modal-window',
            resolve: {
                item: function () {
                    return item;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;                    
        }, function (item) { 
            if(item === 'backdrop click') {
                return;
            }

            var send_condition = '0'; // no
            if(item.cond === 'send') {
                send_condition = '1'; // yes
            }
            var request = {};
            request.status = val;
            request.id = item.id;
            request.send_user = user_id;
            request.send_condition = send_condition;
            request.send_flag = item.send_flag ; //'yes' , 'no'

            if(val === 2) {
                request.check_message = item.status_comment;
                request.clear_message = "";
            }
            else if(val === 3) {
                request.check_message = item.check_message;
                request.clear_message = item.status_comment;
            }
            $http({
                method: 'POST',
                url: '/frontend/alarm/dash/changealarmstatus',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.getDataList();
                }).catch(function (response) {

                //console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
            
        });
        //////////////////
        /*var request = {};
        request.status = val;
        request.id = item.id;
        request.send_user = user_id;
        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/changealarmstatus',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.getDataList();
            }).catch(function (response) {

            //console.error('Gists error', response.status, response.data);
        })
        .finally(function () {
            $scope.isLoading = false;
        });*/
    };

    $scope.showUserLists = function(item) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/alarm/modal/show_userlists.html',
            controller: 'AlarmShowUserListController',
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
});

app.controller('AlarmActiveUserController', function ($scope, $http, $window, $timeout, toaster, AuthService, $uibModal) {
    $scope.notifi = {};
    $scope.userlist = [];
    $scope.notification_id = 0 ;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;

    $scope.bLoadedAll = false;

    $scope.$on("notifi_show_users", function(evt, row){ 
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
        bLoadedAll = true;
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? tableState.sort.predicate : 'created_at' ;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'asc' : 'desc';
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

                if (response.data.datalist.length < 20) {
                    bLoadedAll = true;
                }
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
        if(item == '2' ) status = "Update"; //from check to update
        if(item == '3' ) status = "Clear";
        if(item == '0' ) status = " ";
        return status;
    };

    $scope.checkPermission= function(item, row) {
        var permission = row.permission;
        var val = false;
        if(permission.includes(item)) val = true;
        return val;
    };

    $scope.changeStatusOfUser = function(item,val) {
        var request = {};
        request.status = val;
        request.alarm_id = item.alarm_id;
        request.user_id = user_id;
        request.location = item.location;
        request.notification_id = item.notification_id;
        request.property_id = property_id;
        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/changealarmstatusofuser',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.getUserList();
            }).catch(function (response) {

            //console.error('Gists error', response.status, response.data);
        })
        .finally(function () {
            $scope.isLoading = false;
        });
    };
});


app.controller('AlarmSendNotifiController', function ($scope,$http, $rootScope, $uibModalInstance, toaster,AuthService, item, $filter) {
    $scope.item = item;    
    $scope.item.send_flag = 'no';
    $scope.onSendAlarm = function() {
        $scope.item.status_comment = $scope.status_comment;
        $scope.item.cond = 'send' ; 
        $uibModalInstance.dismiss($scope.item);
    }  
    $scope.save = function () {
        $scope.item.status_comment = $scope.status_comment;
        $scope.item.cond = 'cancel' ; 
        $uibModalInstance.close($scope.item);
    };
    $scope.cancel = function () {
     //   $scope.item.status_comment = $scope.status_comment;
     //   $scope.item.cond = 'cancel' ; 
        $uibModalInstance.dismiss($scope.item);
    };
    $scope.changeOption = function(cond) {
        if(cond === 'yes') {
            $scope.item.send_flag = 'yes';
        }
        else if(cond === 'no') {
            $scope.item.send_flag = 'no';
        }
    }
});

app.controller('AlarmShowUserListController', function ($scope,$http, $rootScope, $uibModalInstance, toaster,AuthService, item, $filter) {
    $scope.item = item; 
    var profile = AuthService.GetCredentials();    
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;

    $scope.bLoadedAll = false;
    $scope.search_notify_text = '';

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
    };

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
                console.log(response);
            }).catch(function(response) {
            console.error('Alarm error', response.status, response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });

    };
    
    $scope.getUserList = function getUserList(tableState) {
        $scope.isLoading = true;
        $scope.bLoadedAll = false;
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? tableState.sort.predicate : 'created_at' ;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'asc' : 'desc';
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
                $scope.userlist = response.data.datalist;
                $scope.created_at = response.data.created_at;
                $scope.retry_count = response.data.retry_count;
                $scope.alarm_name = $scope.item.alarm_name;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                if (response.data.datalist.length < request.pagesize) {
                    $scope.bLoadedAll = true;
                }

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

