app.controller('MyGuestrequestEditController', function ($scope, $rootScope, $http, $interval, toaster, GuestService,  AuthService, DateService) {
    $scope.tasks = [];
    $scope.guest = {};

    var MESSAGE_TITLE = 'Change Guest Task';
    var SELECT_ACTION = '--Select Action--';
    var COMPLETE_ACTION = 'Complete';
    var OPEN_ACTION = 'Open';
    var EXTEND_ACTION = 'Extend';
    var HOLD_ACTION = 'Hold';
    var RESUME_ACTION = 'Resume';
    var CANCEL_ACTION = 'Cancel';
    var SCHEDULED_ACTION = 'Scheduled';
    var REASSIGN = 'Reassign';

    //get Stafflist
    var getstafflist = function(task){
        var item = task.task_list;
        //var location = 1;
        //GuestService.getTaskInfo($item.id, $scope.selected_room.location_group.location_grp)
        GuestService.getLocationGroup(task.location_id)
            .then(function(response){
                GuestService.getTaskInfo(item, task.location_id)
                    .then(function(response){
                        console.log(response);
                        $scope.task.userlist = response.data.staff_list;
                    });
            });
    }

    $scope.init = function(task) {
        $scope.ticket_id = sprintf('G%05d', task.id);

        if( task.id == 0 )
            return;

        if( task.type != 1 )
            return;

        $scope.task = angular.copy(task);

        var start_time = new Date(Date.parse($scope.task.start_date_time));
        $scope.task.date = start_time.format("yyyy-MM-dd"),
        $scope.task.time = start_time;

        $scope.backuptask = angular.copy(task);
        $scope.room_num = task.room;
        $scope.guest.guest_name = task.guest_name;
        $scope.guest.request_time = task.start_date_time;
        $scope.task.extend_time_flag = false;
        $scope.task.other_wholename = $scope.task.wholename;
        $scope.initActionList($scope.task);
        getstafflist(task);
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
                    if(AuthService.isValidModule('app.guestservice.manager')) {
                        $scope.actions = [
                            SELECT_ACTION,
                            RESUME_ACTION,
                            CANCEL_ACTION,
                            REASSIGN,
                        ];
                    }else {
                        $scope.actions = [
                            SELECT_ACTION,
                            RESUME_ACTION,
                            CANCEL_ACTION,
                        ];
                    }
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
        if(action == 'Resume') $scope.changeTask();
    }
    $scope.new_dispatcher = 0;
    $scope.onStaffSelectOtherstaff = function (task, $item, $model, $label) {
        console.log($item);
        $scope.new_dispatcher = $item.id;
        //get room_id from room
    };

    $scope.onReassign = function() {
        //1. canscel the currrent status about caurrent staff
        $scope.task.action = CANCEL_ACTION;
        $scope.task.reason = "This was reassign";
        $scope.changeTask();
        //2. after change  from original staff to new staff, new task
        $scope.task.dispatcher =  $scope.new_dispatcher;
        createOtherStaffTasks(0);
    }

    $scope.room_id = 0;
    var createOtherStaffTasks = function (flag) {
        // 0: only create, 1: Create and another for same room, 2: Create and another for diff room
        var flag = 0;
        var tasklist = [];
        // get room_id from room name
        var room_name =$scope.task.room;
        GuestService.getRoomId(room_name)
            .then(function(response){
                if(response.data.id > 0) $scope.room_id = response.data.id;
                var profile = AuthService.GetCredentials();
                if( $scope.task )
                {
                    var task = $scope.task;
                    var data = {};
                    data.property_id = profile.property_id;
                    data.dept_func = task.dept_func;
                    data.department_id = task.department_id;
                    data.type = task.type;
                    data.priority = task.priority;
                    data.schedule_flag = task.schedule_flag; // current no exist
                    var date = new Date();
                    data.start_date_time = date.format("yyyy-MM-dd HH:mm:ss");
                    //data.start_date_time = task.start_date_time;
                    data.status_id = 1; //save  open status
                    data.running = task.running;
                    //data.dispatcher = task.dispatcher;
                    data.dispatcher = $scope.new_dispatcher;
                    data.repeat_flag = task.repeat_flag;
                    data.until_checkout_flag = task.until_checkout_flag;
                    data.repeat_end_date = task.repeat_end_date;
                    //data.end_date_time = task.end_date_time;
                    data.end_date_time = '0000-00-00 00:00:00';
                    data.attendant = task.attendant;
                    data.room = $scope.room_id;
                    //data.room = task.room;//
                    data.task_list = task.task_list;
                    data.max_time = task.max_time;
                    data.quantity = task.quantity;
                    data.custom_message = "";
                    data.guest_id = task.guest_id;
                    data.location_id = task.location_id;
                    tasklist.push(data);
                    $rootScope.myPromise = GuestService.createTaskList(tasklist);
                    $rootScope.myPromise.then(function(response) {
                        console.log(response);

                        if( response.data.count > 0) {
                            if( task.dispatcher > 0 )
                                toaster.pop('success', 'Create Reassign', $scope.task.task_name + ' is reassigned to Staff ' + $scope.task.other_wholename);
                            else
                                toaster.pop('error', 'Create Reassing', $scope.task.task_name + ' will be escalated to Managers.');

                            // $scope.main_task = {};
                            // $scope.tasks = [];
                            // $scope.quicktasks = [];
                            //
                            // $scope.max_ticket_no = response.data.max_ticket_no;
                            // $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                            $scope.$emit('onTicketChange', tasklist);

                            if( flag == 0 ) // Create
                            {
                                $scope.selected_room = {};
                                $scope.room_num = '';
                                $scope.guest = {};
                                $scope.$emit('onTicketCreateFinished', 1);      // Guest Request
                            }
                            // if( flag == 1 ) // Create Create & add another for same room
                            // {
                            //     // refresh quick task list
                            //     $scope.onRoomSelect($scope.selected_room);
                            // }

                            // if( flag == 2 ) // Create Create & add another for another room
                            // {
                            //     $scope.selected_room = {};
                            //     $scope.room_num = '';
                            //     $scope.guest = {};
                            // }

                        }
                        else {
                            toaster.pop('error', 'Create Reassing', $scope.task.task + ' is already opened for Room ' + $scope.task.room);
                        }

                    }).catch(function(response) {
                            toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
                        })
                        .finally(function() {

                        });
                }
            });
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