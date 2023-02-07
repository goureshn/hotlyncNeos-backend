app.controller('MyDepartmentrequestEditController', function ($scope, $rootScope, $http, $interval, toaster, GuestService, DateService) {
    $scope.tasks = [];
    $scope.location = {};

    var MESSAGE_TITLE = 'Change Department Task';
    var SELECT_ACTION = '--Select Action--';
    var COMPLETE_ACTION = 'Complete';
    var OPEN_ACTION = 'Open';
    var EXTEND_ACTION = 'Extend';
    var HOLD_ACTION = 'Hold';
    var RESUME_ACTION = 'Resume';
    var CANCEL_ACTION = 'Cancel';
    var SCHEDULED_ACTION = 'Scheduled';

    $scope.init = function(task) {
        $scope.ticket_id = sprintf('D%05d', task.id);

        if( task.id == 0 )
            return;

        if( task.type != 2 )
            return;

        $scope.task = angular.copy(task);

        var start_time = new Date(Date.parse($scope.task.start_date_time));
        $scope.task.date = start_time.format("yyyy-MM-dd"),
        $scope.task.time = start_time;

        $scope.backuptask = angular.copy(task);
        $scope.task.extend_time_flag = false;

        $scope.location.name = task.lgm_name;
        $scope.location.type = task.lgm_type;
        $scope.location.requester_name = task.requester_name;
        $scope.location.requester_job_role = task.requester_job_role;
        $scope.location.request_time = task.start_date_time;
        $scope.location.notify_flag = task.requester_notify_flag == 1 ? true : false;
        $scope.location.requester_mobile = task.requester_mobile;
        $scope.location.requester_email = task.requester_email;

        $scope.initActionList($scope.task);

        getNotificationHistory();
    }

    $scope.initActionList = function(task)
    {
        $scope.actions = [];
        switch(task.status_id) {
            case 1: // Open
                if( task.running == 1 )     // running
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMPLETE_ACTION,
                        CANCEL_ACTION,
                        HOLD_ACTION,
                        EXTEND_ACTION,
                    ];
                }
                else
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        RESUME_ACTION,
                        CANCEL_ACTION,
                    ];
                }
                break;
            case 2: // Escalated
                if( task.running == 1 )     // running
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMPLETE_ACTION,
                        CANCEL_ACTION,
                        HOLD_ACTION,
                    ];
                }
                else
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        RESUME_ACTION,
                        CANCEL_ACTION,
                    ];
                }
                break;
            case 5: // Scheduled
                $scope.actions = [
                    SELECT_ACTION,
                    SCHEDULED_ACTION,
                    OPEN_ACTION
                ];
                break;
        }

        if( $scope.actions.length > 0 )
            task.action =  $scope.actions[0];
    }

    var paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'asc',
        field: 'id',
    };

    $scope.onChangeAction = function(action) {
        console.log(action);
        $scope.task.action = angular.copy(action);
    }

    $scope.changeTask = function() {
        var data = {};

        if( $scope.task.action == SELECT_ACTION )
        {
            toaster.pop('error', 'Change ticket', 'Please select action');
            return;
        }

        var data = {};

        data.start_date_time = $scope.start_date_time;

        if( $scope.task.action == COMPLETE_ACTION )
        {
            if( !($scope.task.dispatcher > 0) )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please set dispatcher' );
                return;
            }

            data.status_id = 0; // Complete State
            data.running = 0;
            data.log_type = 'Completed';
            data.user_id = $scope.task.dispatcher;

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
        else if( $scope.task.action == EXTEND_ACTION )
        {
            if( $scope.task.max_time <= $scope.backuptask.max_time )    // not extended
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please set max time bigger than ' + $scope.backuptask.max_time + 'min' );
                return;
            }

            if( !$scope.task.reason )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason' );
                return;
            }

            data.status_id = 1; // Open State
            data.running = 0;
            data.log_type = 'Extended';
        }
        else if( $scope.task.action == OPEN_ACTION )
        {
            var date = new Date();
            data.start_date_time = date.format('yyyy-MM-dd HH:mm:ss');
            data.status_id = 1; // Open State
            data.running = 1;
            data.log_type = 'Assigned';
        }
        else if( $scope.task.action == SCHEDULED_ACTION )
        {
            var date = '';
            if( $scope.task.date instanceof Date )
                date = $scope.task.date.format('yyyy-MM-dd');
            else
                date = $scope.task.date;

            var time = $scope.task.time.format('HH:mm:ss');
            data.start_date_time = date + ' ' + time;
            data.status_id = 5; // schedule state
            data.running = 0;
            data.log_type = 'Scheduled';
            data.max_time = $scope.task.max_time;
        }
        else if( $scope.task.action == HOLD_ACTION )
        {
            if( !$scope.task.reason )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason' );
                return;
            }

            data.running = 0;
            data.status_id = $scope.task.status_id; // restore original state
            data.log_type = 'On-Hold';
        }
        else if( $scope.task.action == RESUME_ACTION )
        {
            data.running = 1;
            data.status_id = $scope.task.status_id; // restore original state
            data.log_type = 'Resume';
        }

        data.task_id = $scope.task.id;
        data.max_time = $scope.task.max_time;
        data.comment = $scope.task.reason;

        data.original_status_id = $scope.task.status_id;


        $rootScope.myPromise = GuestService.changeTaskState(data)
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