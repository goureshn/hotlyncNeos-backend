app.controller('ComplaintEditController', function ($scope, $rootScope, $http, $interval, toaster, GuestService, AuthService) {
    $scope.tasks = [];
    $scope.guest = {};

    var MESSAGE_TITLE = 'Change Complaint Task';
    var SELECT_ACTION = '--Select Action--';
    var COMPLETE_ACTION = 'Resolve';

    var COMMENT_ACTION = 'Comment';

    $scope.timer = $interval(function() {
        $scope.remain_time = $scope.task.max_time * 1000 - moment.utc(moment().diff(moment($scope.task.start_date_time,"YYYY-MM-DD HH:mm:ss")));
        if( $scope.remain_time < 0 )
            $scope.remain_time = 0;
    }, 1000);

    $scope.$on('$destroy', function() {
        $interval.cancel($scope.timer);
        $scope.timer = undefined;
    });

    $scope.init = function(task) {
        $scope.ticket_id = sprintf('M%05d', task.id);

        if( task.id == 0 )
            return;

        if( task.type != 3 )
            return;

        $scope.task = angular.copy(task);

        var start_time = new Date(Date.parse($scope.task.start_date_time));
        $scope.task.date = start_time;
        $scope.task.time = start_time;

        $scope.backuptask = angular.copy(task);
        $scope.room_num = task.room;
        $scope.guest.guest_name = task.guest_name;
        $scope.guest.request_time = task.start_date_time;
        $scope.task.extend_time_flag = false;

        $scope.initActionList($scope.task);
        $scope.initCompensationActionList($scope.task);

        getNotificationHistory();
    }

    $scope.initActionList = function(task)
    {
        $scope.actions = [];
        switch(task.status_id) {
            case 1: // Open
            case 2: // Escalated
                    $scope.actions = [
                        SELECT_ACTION,
                        COMPLETE_ACTION,
                    ];
                break;
        }

        if( $scope.actions.length > 0 )
            task.action =  $scope.actions[0];
    }

    $scope.initCompensationActionList = function(task)
    {
        $scope.compensation_actions = [];
        switch(task.compensation_status) {
            case 3: // Request comment
                $scope.compensation_actions = [
                    SELECT_ACTION,
                    COMMENT_ACTION,
                ];
                break;
        }

        if( $scope.compensation_actions.length > 0 )
            task.compensation_action =  $scope.compensation_actions[0];
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

    $scope.onChangeCompensationAction = function(action) {
        console.log(action);
        $scope.task.compensation_action = angular.copy(action);
    }

    $scope.changeTask = function() {
        var data = {};

        if( !$scope.task.action )
            return;

        if( $scope.task.action == SELECT_ACTION )
        {
            toaster.pop('error', 'Change ticket', 'Please select action');
            return;
        }

        var profile = AuthService.GetCredentials();

        var data = {};

        data.property_id = profile.property_id;
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
            data.log_type = 'Resolved';
            data.user_id = $scope.task.dispatcher;
        }

        data.task_id = $scope.task.id;
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

    $scope.changeCompensation = function() {
        var data = {};

        if( !$scope.task.compensation_action )
            return;

        if( $scope.task.compensation_action == SELECT_ACTION )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select action');
            return;
        }

        var data = {};

        if( $scope.task.compensation_action == COMMENT_ACTION )
        {
            if( !($scope.task.compensation_comment > 0) )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please set comment' );
                return;
            }

            data.status_id = 1; // On route
            data.running = 1;
            data.log_type = 'Comment';
            data.user_id = 1;
            data.comment = $scope.task.compensation_comment;
        }

        data.task_id = $scope.task.id;
        data.original_status_id = $scope.task.compensation_status;

        $rootScope.myPromise = GuestService.commentCompensationState(data)
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
        enableGridMenu: false,
        enableRowHeaderSelection: false,
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