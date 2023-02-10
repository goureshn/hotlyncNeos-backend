app.controller('MyManagedtaskEditController', function ($scope, $rootScope, $http, $interval, toaster, GuestService, DateService) {
    $scope.tasks = [];
    $scope.guest = {};

    var MESSAGE_TITLE = 'Change Guest Task';
    var SELECT_ACTION = '--Select Action--';
    var COMPLETE_ACTION = 'Complete';
    var CANCEL_ACTION = 'Cancel';

    $scope.init = function(task) {
        $scope.ticket_id = sprintf('M%05d', task.id);

        if( task.id == 0 )
            return;

        if( task.type != 4 )
            return;

        $scope.task = angular.copy(task);

        var start_time = new Date(Date.parse($scope.task.start_date_time));
        $scope.task.date = start_time.format("yyyy-MM-dd"),
        $scope.task.time = start_time;
        $scope.task.notify_flag = task.requester_notify_flag == 1 ? true : false;

        $scope.task.group_flag = task.is_group == 'Y' ? true : false;

        if( task.subtype == 1 || task.subtype == 2 )
            $scope.task.assigne_name = task.wholename;
        else if( task.subtype == 6 )
        {
            if( task.is_group == 'Y' )
                $scope.task.assigne_name = task.manage_user_group;
            else
                $scope.task.assigne_name = task.manage_user_name;
        }

        $scope.backuptask = angular.copy(task);

        $scope.initActionList($scope.task);

        getNotificationHistory();
    }

    $scope.initActionList = function(task)
    {
        $scope.actions = [];
        switch(task.status_id) {
            case 1: // Open
            case 2: // Escalated
                if( task.running == 1 ) {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMPLETE_ACTION,
                        CANCEL_ACTION,
                    ];
                }
                else
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        CANCEL_ACTION,
                    ];
                }

                break;
        }

        if( $scope.actions.length > 0 )
            task.action =  $scope.actions[0];
    }

    $scope.onChangeAction = function(action) {
        console.log(action);
        $scope.task.action = angular.copy(action);
    }

    $scope.changeTask = function() {
        var data = {};

        if( !$scope.task.action )
            return;

        if( $scope.task.action == SELECT_ACTION )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select action');
            return;
        }

        var data = {};

        if( $scope.task.action == COMPLETE_ACTION )
        {
            data.status_id = 0; // Complete State
            data.running = 0;
            data.log_type = 'Completed';
        }
        else if( $scope.task.action == CANCEL_ACTION )
        {
            if( !$scope.task.reason )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason' );
                return;
            }
            data.status_id = 4;     // Cancel state
            data.running = 0;
            data.log_type = 'Canceled';
        }

        data.task_id = $scope.task.id;
        data.comment = $scope.task.reason;

        data.original_status_id = $scope.task.status_id;

        $rootScope.myPromise = $http({
            method: 'POST',
            url: '/frontend/guestservice/changemanagedtask',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {

                console.log(response.data);

                $scope.task = response.data.ticket;
                $scope.$emit('onTicketChange', $scope.task);

                if( response.data.code && response.data.code == 'NOTSYNC' )
                    toaster.pop('error', MESSAGE_TITLE, 'Ticket data is not synced' );
                if( response.data.code && response.data.code == 'SUCCESS' )
                    toaster.pop('success', MESSAGE_TITLE, 'Task is changed successfully');

                $scope.init($scope.task);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Task is fail to change');
            })
            .finally(function() {

            });
    }

    $scope.cancelChangeTask = function() {
        $scope.task = angular.copy($scope.backuptask);
    }

    var paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'asc',
        field: 'id',
    };

    $scope.columns = [
        {
            field : 'id',
            displayName : "ID",
            width: 50,
            enableCellEdit: false,
        },
        {
            field : 'staff',
            displayName : "Staff",
            enableCellEdit: false,
        },
        {
            field : 'send_time',
            displayName : "Time",
            enableCellEdit: false,
        },
        {
            field : 'type',
            displayName : "Type",
            enableCellEdit: false,
        },
        {
            field : 'job_role',
            displayName : "Job Role",
            enableCellEdit: false,
        },
        {
            field : 'mode',
            displayName : "Method",
            enableCellEdit: false,
        },
    ];

    $scope.gridOptions =
    {
        enableGridMenu: true,
        enableColumnResizing: true,
        paginationPageSizes: [20, 40, 60, 80],
        paginationPageSize: 20,
        useExternalPagination: true,
        useExternalSorting: true,
        columnDefs: $scope.columns,
    };

    $scope.gridOptions.onRegisterApi = function( gridApi ) {
        $scope.gridApi = gridApi;
        gridApi.selection.on.rowSelectionChanged($scope,function(row){
            console.log(row.entity);
        });
        gridApi.core.on.sortChanged($scope, function(grid, sortColumns) {
            if (sortColumns.length == 0) {
                paginationOptions.sort = 'asc';
                paginationOptions.field = 'id';
            } else {
                paginationOptions.sort = sortColumns[0].sort.direction;
                paginationOptions.field = sortColumns[0].name;
            }
            getNotificationHistory();
        });
        gridApi.pagination.on.paginationChanged($scope, function (newPage, pageSize) {
            paginationOptions.pageNumber = newPage;
            paginationOptions.pageSize = pageSize;
            getNotificationHistory();
        });
    };

    var getNotificationHistory = function() {
        $rootScope.myPromise = GuestService.getNotificationHistoryList($scope.task.id, paginationOptions.pageNumber, paginationOptions.pageSize, paginationOptions.field, paginationOptions.sort )
            .then(function(response) {
                $scope.gridOptions.totalItems = response.data.totalcount;
                $scope.gridOptions.data = response.data.datalist;
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    };

    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };

    $scope.format = 'yyyy-MM-dd';

});

app.controller('DatetimeController', function ($scope) {
    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };

    $scope.disabled = function(date, mode) {
        var cur_date = new Date();
        return cur_date.getTime() >= date.getTime();
    };
});