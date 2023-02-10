app.controller('MangedtaskNewController', function ($scope, $rootScope, $http, $interval, toaster, GuestService, AuthService) {
    var MESSAGE_TITLE = 'Create Managed Tasks';

    var SELECT_TYPE = '--Select Type--';
    var GUEST_REQUEST = 'Guest Request';
    var DEPARTMENT_REQUEST = 'Department Request';
    var OTHER_REQUEST = 'Other Request';
    var MAX_TASK_COUNT = 5;

    $scope.tasks = [];
    $scope.selected_room = {};

    $scope.request_types = [
        SELECT_TYPE,
        GUEST_REQUEST,
        DEPARTMENT_REQUEST,
        OTHER_REQUEST,
    ];

    $scope.bAddAllow = false;

    $scope.getShowTime = function(date, bShowDate = false) {

        let strDate = "";
        if (bShowDate === true) {
            let year = date.getFullYear();
            let month = date.getMonth() + 1;
            let day = date.getDate();

            strDate += year + "-";
            if (month < 10) {
                strDate += "0";
            }

            strDate += month + "-";

            if (day < 10) {
                strDate += "0";
            }
            strDate += day;
        }

        let hours = date.getHours();
        let minutes = date.getMinutes();
        let seconds = date.getSeconds();
        
        let strResultTime = "";
        
        if (hours < 10) {
            strResultTime += "0";
        } 
        
        strResultTime += hours + ":";
        
        if (minutes < 10) {
            strResultTime += "0";
        }
        strResultTime += minutes + ":";
        
        if(seconds < 10) {
            strResultTime += "0";
        }
        
        strResultTime += seconds;

        if (bShowDate === true) {
            strResultTime = strDate + " " + strResultTime;
        }
        
        return strResultTime;
    };

    var checkAddAllowStatus = function(tasks) {
        let bResult = false;
        
        for (let i = 0; i < tasks.length; i++) {
            let subType = tasks[i].subtype;
            
            if (subType !== '--Select Type--') {
                bResult = true;
                break;
            }
        }

        $scope.bAddAllow = bResult;
    };

    var date = new Date();
    // $scope.request_time = $scope.getShowTime(date);
    // $scope.timer = $interval(function () {
    //     var date = new Date();
    //     $scope.request_time = $scope.getShowTime(date);
    // }, 1000);

    GuestService.getMaxTicketNo()
        .then(function (response) {
            $scope.max_ticket_no = response.data.max_ticket_no + $scope.newTickets.length - 1;
        });

    $scope.addTask = function () {
        if ($scope.tasks.length >= MAX_TASK_COUNT) {
            toaster.pop('error', MESSAGE_TITLE, 'You can not create managed task more than ' + MAX_TASK_COUNT);
            return;
        }

        var task = {};

        task.type = 5;
        task.subtype = SELECT_TYPE;
        task.request_time = $scope.request_time + '';
        task.quantity = 1;

        $scope.tasks.push(task);

        checkAddAllowStatus($scope.tasks);
    }

    $scope.addTask();

    $scope.removeTask = function (index) {
        $scope.tasks.splice(index, 1);

        checkAddAllowStatus($scope.tasks);
    }

    $scope.getTicketNumber = function (index) {
        return sprintf('M%05d', $scope.max_ticket_no + index + 1);
    }

    $scope.onChangeType = function (task, $index) {
        task.priority_id = $scope.prioritylist[0].id;

        checkAddAllowStatus($scope.tasks);
    };

    $scope.getRoomList = function (val) {
        if (val == undefined)
            val = "";

        return GuestService.getRoomList(val)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };

    $scope.onRoomSelect = function (task, $item, $model, $label) {
        task.selected_room = $item;
        task.property_id = $item.property_id;

        GuestService.getGuestName($item)
            .then(function (response) {
                if (response.data)
                    task.guest = response.data;
                else {
                    task.guest = {};
                    task.guest.guest_name = 'Admin task';
                }
            });

        GuestService.getLocationGroupFromRoom($item.id)
            .then(function (response) {
                task.location_group = response.data;
                task.location_id = task.location_group.id;
                task.location_group_id = task.location_group.location_grp;
            });

    };


    $scope.getLocationList = function (val) {
        if (val == undefined)
            val = "";
        return GuestService.getLocationList(val)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };

    $scope.onLocationSelect = function (task, $item, $model, $label) {
        task.location = $item;
        task.property_id = $item.property_id;
        task.location_id = task.location.id;
        task.location_group_id = task.location.location_grp;
    };

    $scope.getUserGroupList = function (val) {
        if (val == undefined)
            val = "";
        return $http.get('/frontend/guestservice/usergrouplist?value=' + val)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };

    $scope.onRequesterSelect = function (task, $item, $model, $label) {
        task.requester = $item;
    };

    $scope.getTaskList = function (task, val) {
        if (val == undefined)
            val = "";

        type = 0;
        if (task.subtype == 'Guest Request')
            type = 1;
        if (task.subtype == 'Department Request')
            type = 2;
        return GuestService.getTaskList(val, task.property_id, type)
            .then(function (response) {
                return response.data.filter(function (item, index, attr) {
                    return index < 10;
                });
            });
    };

    $scope.getDepartmentTaskList = function (task, val) {
        if (val == undefined)
            val = "";
        return GuestService.getTaskList(val, task.property_id, task.type)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };

    $scope.onTaskSelect = function (task, $item, $model, $label) {
        console.log($item);

        if (!task.location_group) {
            toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
            return;
        }

        task.quantity = 1;
        task.tasklist = $item;

        // for(var i = 0; i < $scope.tasks.length - 1; i++)
        // {
        //     if( $scope.tasks[i].tasklist.id == $item.id )
        //     {
        //         task.tasklist.task = '';
        //         toaster.pop('error', MESSAGE_TITLE, 'Task is already added, Please increase quantity instead' );
        //         return;
        //     }
        // }


        GuestService.getTaskInfo($item.id, task.location_id)
            .then(function (response) {
                console.log(response);

                showSelectedDepartmentInfo(task, response.data);
            });
    };

    $scope.onAddTask = function (selected_task) {
        var task = selected_task.tasklist.task;
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = profile.property_id;
        request.task = task;
        request.type = 0;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/addtask',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            selected_task.department_edit_flag = true;
            selected_task.tasklist = response.data.task;
            selected_task.department = '';
            selected_task.department_id = 0;
            task.dept_func = {};
            task.userlist = [];
            task.max_duration = 0;
            task.priority_id = $scope.prioritylist[0].id;
            task.dispatcher = '';
            task.username = '';
            task.device = '';
        }).catch(function (response) {
        })
            .finally(function () {

            });
    }

    $scope.getDepartList = function (val) {
        return GuestService.getDepartList()
            .then(function (response) {
                return response.data.departlist.map(function (item) {
                    return item;
                });
            });
    };

    $scope.onDepartSelect = function (task, $item, $model, $label) {
        console.log(task);

        task.department = $item.department;
        task.department_id = $item.id;
        task.dept_func = {};

        task.dept_func.id = $item.dept_func_id;
        task.dept_func.function = $item.function;
        task.task_group_id = $item.task_group_id;

        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = profile.property_id;
        request.task_id = task.tasklist.id;
        request.task_group_id = $item.default_task_group_id;
        request.location_group_id = task.location_group_id;
        request.location_id = task.location_id;
        request.type = 0;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/taskinfowithgroup',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            showSelectedDepartmentInfo(task, response.data);
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    function showSelectedDepartmentInfo(task, data) {
        if (data.department == undefined) {
            toaster.pop('error', 'Task error', 'There is no department');
            return;
        }
        task.department = data.department.department;
        task.department_id = data.department.id;
        task.dept_func = data.deptfunc;
        task.userlist = data.staff_list;
        task.max_duration = (data.taskgroup.max_time/60);
        task.priority_id = $scope.prioritylist[0].id;

        if (task.userlist.length < 1) {
            toaster.pop('error', 'Task error', 'No Staff is on shift');
            task.userlist.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
        }
        task.dispatcher = task.userlist[0];
        task.username = task.dispatcher.wholename;
        task.device = task.dispatcher.mobile;
    }


    $scope.onStaffSelect = function (task, $item, $model, $label) {
        console.log($item);
        task.dispatcher = $item;
        task.device = $item.mobile;
    };

    $scope.onUserSelect = function (task, $item, $model, $label) {
        task.requester = $item;
    };

    $scope.onUserGroupSelect = function (task, $item, $model, $label) {
        task.requester_group = $item;
    };

    $scope.createTasks = function (flag) {  // 0: only create, 1: Create and another for same room, 2: Create and another for diff room
        var tasklist = [];

        var profile = AuthService.GetCredentials();

        var date = new Date();

        for (var i = 0; i < $scope.tasks.length; i++) {
            var data = {};
            data.property_id = profile.property_id;
            data.type = 4;
            data.attendant = profile.id;
            data.status_id = 1;
            data.running = 1;

            data.start_date_time = $scope.getShowTime(date, true);
            data.end_date_time = '0000-00-00 00:00:00';

            var task = $scope.tasks[i];

            if (task.subtype == SELECT_TYPE)
                continue;



            if (task.subtype == GUEST_REQUEST) {
                if (!task.selected_room || !task.guest) {
                    toaster.pop('error', 'Task error', 'Please select room and guest');
                    return;
                }

                if (!task.tasklist) {
                    toaster.pop('error', 'Error', 'Please select task list');
                    return;
                }

                if (!task.dept_func || !task.dispatcher) {
                    toaster.pop('error', 'Validate Error', 'Please input all fields');
                    return;
                }

                data.dept_func = task.dept_func.id;
                data.department_id = task.department_id;
                data.priority = task.priority_id;
                data.subtype = 1;



                data.dispatcher = task.dispatcher.user_id;
                data.task_list = task.tasklist.id;
                data.max_time = (task.max_duration*60);
                data.quantity = task.quantity;
                data.location_id = task.location_id;

                data.guest_id = task.guest.id;
                data.room = task.selected_room.id;
            }

            if (task.subtype == DEPARTMENT_REQUEST) {
                if (!task.location || !task.requester) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please select room and guest');
                    return;
                }

                if (!task.tasklist) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please select task list');
                    return;
                }

                if (!task.dept_func || !task.dispatcher) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please input all fields');
                    return;
                }

                data.dept_func = task.dept_func.id;
                data.department_id = task.department_id;
                data.priority = task.priority_id;
                data.subtype = 2;

                data.start_date_time = $scope.getShowTime(date, true);
                data.end_date_time = '0000-00-00 00:00:00';

                data.dispatcher = task.dispatcher.user_id;
                data.attendant = profile.id;
                data.task_list = task.tasklist.id;
                data.max_time = (task.max_duration * 60);
                data.quantity = task.quantity;
                data.location_id = task.location_id;

                data.requester_id = task.requester.id;
                data.requester_name = task.requester.wholename;
                data.requester_job_role = task.requester.job_role;

                data.requester_notify_flag = task.notify_flag ? 1 : 0;
                data.requester_email = task.requester.email;
                data.requester_mobile = task.requester.mobile;

                data.guest_id = 0;
                data.room = 0;
            }

            if (task.subtype == OTHER_REQUEST) {
                if (!task.group_flag)
                    task.group_flag = false;
                if (task.group_flag != true && !task.requester) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please select user');
                    return;
                }

                if (task.group_flag == true && !task.requester_group) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please select user group');
                    return;
                }

                data.subtype = 6;
                if (task.group_flag) {
                    data.is_group = 'Y';
                    data.group_id = task.requester_group.id;
                }
                else {
                    data.is_group = 'N';
                    data.user_id = task.requester.id;
                }
            }

            data.custom_message = task.custom_message;

            tasklist.push(data);
        }

        $rootScope.myPromise = $http({
            method: 'POST',
            url: '/frontend/guestservice/createmanagedtasklist',
            data: tasklist,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        });

        $rootScope.myPromise.then(function (response) {
            console.log(response);
            $scope.tasks = [];
            $scope.max_ticket_no = response.data.max_ticket_no;

            $scope.$emit('onTicketChange', tasklist);

            $scope.selected_room = {};
            $scope.room_num = '';
            $scope.guest = {};
            // $scope.$emit('onTicketCreateFinished', 5);      // Managed Task Request

            toaster.pop('success', 'Create Task', 'Tasks have been created successfully');
        }).catch(function (response) {
            toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
        })
            .finally(function () {

            });

    }

});

app.controller('DatetimeController', function ($scope) {
    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };

    $scope.disabled = function (date, mode) {
        var cur_date = new Date();
        return cur_date.getTime() >= date.getTime();
    };
});
