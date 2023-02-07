app.controller('GuestrequestController', function ($scope, $rootScope, $http, $interval, toaster, $uibModal, GuestService, AuthService, $timeout) {
    var MESSAGE_TITLE = 'Create Guest Ticket';

    $scope.isOnlyGuestRequest = true;
    $scope.tasks = [];
    $scope.guest = {};
    $scope.main_task = {};
    $scope.quicktasks = [];
    $scope.feedback_flag = '';
    $scope.selected_room = {};
    $scope.datetime = {};
    $scope.disable_create=0;

    // new part
    $scope.isMultiple = false;
    $scope.selected_rooms = [];
    $scope.selected_guests = [];
    $scope.isLoadTasks = false;
    $scope.request_time = "";
    $scope.guest_name = "";

    $scope.historyCount = 0;
    $scope.selectedHistoryArr = [];
    $scope.isCreatingTasks = false;

    GuestService.getMaxTicketNo()
        .then(function (response) {
            var new_ticket_length = 0;
            if ($scope.newTickets)
                new_ticket_length = $scope.newTickets.length;
            $scope.max_ticket_no = response.data.max_ticket_no + new_ticket_length - 1;
            $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);
        });

    $scope.request_time = moment().format("HH:mm:ss");
    $scope.timer = $interval(function () {
        $scope.request_time = moment().format("HH:mm:ss");
    }, 1000);

    $scope.$on('$destroy', function () {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });
    $scope.feedbackFlag = function () {
        if ($scope.feedback_flag == false)
            $scope.feedback_flag = true;
        else
            $scope.feedback_flag = false;
    };

    $scope.onToggleMultiple = function() {
        $scope.isMultiple = !$scope.isMultiple;

        $scope.tasks = [];
        $scope.main_task = {};
    }

    $scope.onRoomInfoChanged = function(type) {
        if (type === 'add') {
            $scope.selected_rooms = $scope.selected_rooms.filter((item) => {
                return item.id ? true : false;
            });
        }

        $scope.selected_guests = [];
        $scope.tasks = [];
        $scope.guest_name = "";
    };

    $scope.onLoadTasks = function() {

        if ($scope.selected_rooms.length < 1) {
            toaster.pop('warning', MESSAGE_TITLE, 'Please select rooms');
            return;
        }

        let room_ids = $scope.selected_rooms.map(item => {
            return item.id;
        });

        let request = {};
        request.room_ids = room_ids;

        $scope.isLoadTasks = true;
        let promise = GuestService.getGuestDataByRoom(request);
        promise.then(function (res) {
            if (res.data.guests_checkout.length > 0) {
                $scope.showRequest(2);

                $timeout(function () {
                    if( $scope.call_agent_page !== true )
                    {
                        let checkLocationGroups = res.data.guests_checkout.map (tempItem => {
                            return tempItem.location_group;
                        });
                        let param = {
                            location_groups: checkLocationGroups,
                            isMultiple: true
                        };
                        $rootScope.$broadcast('room_selected', param);
                    }
                }, 1000);
            }

            if (res.data.guests_checkin.length > 0) {
                $scope.selected_guests = res.data.guests_checkin;

                // get response room ids
                let response_ids = $scope.selected_guests.map(item => {
                    return item.room_id;
                });

                // filter selected rooms
                $scope.selected_rooms = $scope.selected_rooms.filter(item => {
                    if ( response_ids.includes(item.id)) {
                        return true;
                    } else {
                        return false;
                    }
                });

                if ($scope.selected_guests.length === 1) {
                    $scope.guest_name = $scope.selected_guests[0].guest_name;
                } else {
                    $scope.guest_name = "";
                }

                $scope.addMainTask();
                $scope.alert = {};

                getGuestHistories();
            } else {
                $scope.$emit('onTicketCreateFinished', 1); // Close Guest Request Tab
            }
        }).catch(function (response) {
            toaster.pop('error', 'Error', 'Database error!');
        })
            .finally(function () {
                $timeout(function () {
                    $scope.isLoadTasks = false;
                }, 500);
            });

        let property_id = $scope.selected_rooms[0].property_id;
        GuestService.getQuickTaskList(1, property_id)
            .then(function (response) {
                $scope.quicktasks = response.data;
            });

        GuestService.getMainTaskList(1, property_id)
            .then(function (response) {
                $scope.maintasks = response.data;
            });
    };

    $scope.onRoomSelect = function ($item, $model, $label) {
        console.log($item);
        $scope.selected_room = angular.copy($item);

        GuestService.getGuestName($item)
            .then(function (response) {

                if ($scope.isOnlyGuestRequest == true) {
                    if ((!response.data.checkout_flag)||response.data.checkout_flag == 'checkout') {
                        $scope.showRequest(2);
                        $timeout(function () {
                            if( $scope.call_agent_page == true )
                            {

                            }
                            else
                                $rootScope.$broadcast('room_selected', $item);
                        }, 1000);

                        $scope.$emit('checkout_room_selected', $item);
                        $scope.$emit('onTicketCreateFinished', 1);      // Close Guest Request Tab
                        return;
                    }
                    if (response.data) {
                        $scope.guest = response.data;
                    }
                    else
                        $scope.guest.guest_name = 'Admin task';

                    $scope.addMainTask();
                    $scope.alert = {};
                }

                getGuestHistory();
            });

        GuestService.getQuickTaskList(1, $item.property_id)
            .then(function (response) {
                $scope.quicktasks = response.data;
            });

        GuestService.getMainTaskList(1, $item.property_id)
            .then(function (response) {
                $scope.maintasks = response.data;
            });

        GuestService.getLocationGroupFromRoom($item.id)
            .then(function (response) {
                $scope.selected_room.location_group = response.data;
            });


    };

    $scope.history = {};

    function getGuestHistories() {

        let guest_ids = $scope.selected_guests.map(item => {
            return item.guest_id;
        });

        $scope.history.limit = 0;
        $scope.selected_guest_id = 0;
        $scope.historyCount = 0;

        if (guest_ids.length < 1) {
            $scope.historylist = {};
        } else {
            //get history count
            GuestService.getPrevHistoryList(guest_ids)
                .then(function (response) {
                    $scope.historylist = response.data;

                    let keys = Object.keys($scope.historylist);

                    keys.forEach(function (key) {
                        let itemArr = $scope.historylist[key];
                        itemArr.forEach(function (item) {
                            $scope.historyCount++;
                            item.status_css = GuestService.getStatusCss(item);
                            item.ticket_no = GuestService.getTicketNumber(item);
                            item.status_css_edit = GuestService.getStatusCssInEdit(item);
                            item.status = GuestService.getStatus(item);
                            item.priority_css = GuestService.getPriorityCss(item);
                            item.ticket_item_name = GuestService.getTicketNameForList(item);
                        });
                    });

                    if (keys.length > 0) {
                        $scope.selected_guest_id = parseInt(keys[0]);
                    }
                }).catch(function (response) {
            });
            //
        }
    }

    function getGuestHistory() {
        //get history count
        $scope.history.limit = 0;
        GuestService.getPreviousHistoryList($scope.guest.guest_id)
            .then(function (response) {
                $scope.historylist = response.data.datalist;
                $scope.historylist.forEach(function(item) {
                    item.status_css = GuestService.getStatusCss(item);
                    item.ticket_no = GuestService.getTicketNumber(item);
                    item.status_css_edit = GuestService.getStatusCssInEdit(item);
                    item.status = GuestService.getStatus(item);
                    item.priority_css = GuestService.getPriorityCss(item);
                    item.ticket_item_name = GuestService.getTicketNameForList(item);
                });


            }).catch(function (response) {

        });
        //
    }

    $scope.loadMoreHistory = function () {
        $scope.history.limit += 10;
    };

    $scope.$on('room_selected', function (event, args) {

        if ($scope.isMultiple == true) {
            $scope.onLoadTasks();
        } else {
            var item = {};

            item.id = args.room_id;
            item.room = args.room;
            item.property_id = args.property_id;

            $scope.onRoomSelect(item, null, null);
        }
    });

    $scope.taskbtnview = false;
    $scope.getTaskList = function (val) {
        if (val == undefined)
            val = "";

        let property_id = 0;
        if ($scope.isMultiple == true) {
            property_id = $scope.selected_rooms[0].property_id;
        } else {
            property_id = $scope.selected_room.property_id;
        }

        return GuestService.getTaskList(val, property_id, 1)
            .then(function (response) {
                if (response.data.length == 0) $scope.taskbtnview = true;
                return response.data.filter(function (item, index, attr) {
                    return index < 200;
                });
            });
    };

    function checkDuplicatedTask($item) {
        var exist = false;

        for (var i = 0; i < $scope.tasks.length; i++) {
            if ($item.id == $scope.tasks[i].tasklist.id) {
                exist = true;
                break;
            }
        }

        if (exist == true) {
            toaster.pop('error', MESSAGE_TITLE, 'Task is already added, Please increase quantity instead');
            return true;
        }

        return false;
    }

    $scope.$on('select_new_task', function (event, args) {
        $scope.onMainTaskSelect(null, args, null, null);
    });

    $scope.onMainTaskSelect = function (task, $item, $model, $label) {
        console.log($item);

        if ($scope.isMultiple == true ) {
            if ($scope.selected_guests.length < 1) {
                toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
                return;
            }

            $scope.addMainTask();

            if (checkDuplicatedTask($item))
                return;

            $scope.main_task.schedule_flag = false;
            $scope.main_task.quantity = 1;
            $scope.main_task.tasklist = angular.copy($item);

            let request = {};
            request.task_id = $item.id;
            request.location_groups = $scope.selected_guests.map(selected_guest => {
                let temp = {};
                temp.guest_id = selected_guest.guest_id;
                temp.location_id = selected_guest.location_group.id;
                temp.room_name = selected_guest.location_group.name;
                temp.room_id = selected_guest.room_id;

                return temp;
            });

            GuestService.getTaskInfoFromTask(request)
                .then(function (response) {
                    console.log(response);
                    showSelectedDepartmentInfo(response.data);
                });
        } else {
            if (!$scope.selected_room.location_group) {
                toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
                return;
            }

            $scope.addMainTask();

            if (checkDuplicatedTask($item))
                return;

            $scope.main_task.schedule_flag = false;
            $scope.main_task.quantity = 1;
            $scope.main_task.tasklist = angular.copy($item);

            GuestService.getTaskInfo($item.id, $scope.selected_room.location_group.id)
                .then(function (response) {
                    console.log(response);

                    showSelectedDepartmentInfo(response.data);
                });
        }
    };

    $scope.onAddTask = function () {
        var task = $scope.main_task.tasklist.task;
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
            $scope.addMainTask();

            $scope.main_task.department_edit_flag = true;
            $scope.main_task.tasklist = response.data.task;
            $scope.main_task.quantity = 1;

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

    $scope.onMainDepartSelect = function (task, $item, $model, $label) {
        console.log($item);


        if ($scope.isMultiple === false && !$scope.selected_room.location_group) {
            toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
            return;
        }

        var task = $scope.main_task;

        task.department = $item.department;
        task.department_id = $item.id;
        task.dept_func = null;

        // getSelectedDepartmentInfo($item);
    };

    $scope.onMainDeptFuncSelect = function (task, $item, $model, $label) {
        console.log($item);

        if (!$scope.selected_room.location_group) {
            toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
            return;
        }

        getSelectedDepartmentInfo($item);
    };

    function getSelectedDepartmentInfo($item) {
        var task = $scope.main_task;

        task.dept_func = {};

        if (!($item.dept_func_id > 0)) {
            toaster.pop('error', MESSAGE_TITLE, 'There is valid default department function for this department');
            return;
        }

        task.dept_func.id = $item.dept_func_id;
        task.dept_func.function = $item.function;

        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = profile.property_id;
        request.task_id = task.tasklist.id;
        request.dept_func_id = $item.dept_func_id;
        request.location_id = $scope.selected_room.location_group.id;
        //window.alert(request.location_id);
        request.type = 0;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/taskinfowithgroup',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            showSelectedDepartmentInfo(response.data);
        }).catch(function (response) {
        })
            .finally(function () {

            });
    }

    function showSelectedDepartmentInfo(data) {


        var task = $scope.main_task;

        if (data.department == undefined) {
            toaster.pop('error', 'Task error', 'There is no department');
            return;
        }

        if ($scope.isMultiple == true) {
            task.department = data.department.department;
            task.department_id = data.department.id;
            task.dept_func = data.deptfunc;
            task.max_duration = (data.taskgroup.max_time/60);
            task.priority_id = $scope.prioritylist[0].id;

            task.userInfoArr = data.location_groups.map(location_group => {
                let temp = {};
                temp.userlist = location_group.staff_list ? location_group.staff_list : [];
                if (temp.userlist.length < 1) {
                    if( data.taskgroup.unassigne_flag == 1 ) {
                        temp.userlist.push({ id: 0, user_id: 0, wholename: 'Unassigned Task' });
                    } else {
                        temp.userlist.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
                    }
                }

                temp.location_group_id = location_group.location_id;
                temp.room_name = location_group.room_name;
                temp.room_id = location_group.room_id;
                temp.guest_id = location_group.guest_id;
                temp.dispatcher = temp.userlist[0];
                temp.username = temp.dispatcher.wholename;
                temp.device = temp.dispatcher.mobile;

                return temp;
            });
        } else {
            task.department = data.department.department;
            task.department_id = data.department.id;
            task.dept_func = data.deptfunc;
            task.userlist = data.staff_list;
            task.max_duration = (data.taskgroup.max_time/60);
            task.priority_id = $scope.prioritylist[0].id;

            if (task.userlist.length < 1)
            {
                if( data.taskgroup.unassigne_flag == 1 )
                {
                    task.userlist.push({ id: 0, user_id: 0, wholename: 'Unassigned Task' });
                }
                else
                {
                    toaster.pop('error', 'Task error', 'No Staff is on shift');
                    task.userlist.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
                }

            }

            task.dispatcher = task.userlist[0];
            task.username = task.dispatcher.wholename;
            task.device = task.dispatcher.mobile;
        }

    }

    $scope.getSecondTaskList = function (val) {
        if (val == undefined)
            val = "";

        if (!$scope.selected_room.property_id)
            return;

        return GuestService.getTaskListInDepartment(val, $scope.selected_room.property_id, $scope.main_task.dept_func.id, 1)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };

    $scope.addMainTask = function () {
        var date = new Date();

        var new_task = {
            ticket_no: $scope.max_ticket_no + 1,
            task_name: "",
            qunatity: 1,
            department: "",
            department_edit_flag: false,
            dept_func: "",
            dept_staff: "",
            task_group_id: 0,
            device: "",
            priority_id: $scope.prioritylist[0].id,
            max_duration: "",
            custom_message: "",
            start_date_time: moment().format("YYYY-MM-DD HH:mm:ss"),
            created_time: moment().format("YYYY-MM-DD HH:mm:ss"),
            date: moment().format("YYYY-MM-DD HH:mm:ss"),
            schedule_flag: false,
            feedback_flag: false,
            repeat_end_date: moment($scope.guest.departure).toDate(),
            repeat_flag: false,
            until_checkout_flag: false
        }

        $scope.main_task = new_task;

        $scope.main_task.files = [];
        $scope.main_task.thumbnails = [];
    }

    $scope.uploadFiles = function (files) {
        if(files.length > 0)
        {
            $scope.main_task.files = $scope.main_task.files.concat(files);

            $scope.main_task.files.forEach(item => {
                $scope.main_task.thumbnails = [];
                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    $scope.main_task.thumbnails.push(loadEvent.target.result);
                }
                reader.readAsDataURL(item);
            });
        }
    }

    $scope.removeFile = function($index) {
        $scope.main_task.files.splice($index, 1);
        $scope.main_task.thumbnails.splice($index, 1);
    }

    $scope.onStaffSelect = function (task, $item, $model, $label) {
        console.log($item);
        task.dispatcher = $item;
        task.device = $item.mobile;
    };

    $scope.addTask = function (message_flag) {
        $scope.main_task.feedback_flag = $scope.feedback_flag;
        var task = $scope.main_task;
        if (isValidTask(task, message_flag) == false)
            return;

        $scope.tasks.push(task);

        // init main task
        $scope.addMainTask();
    }

    $scope.removeTask = function (item) {
        $scope.tasks.splice($scope.tasks.indexOf(item), 1);
    }

    $scope.addSystemTask = function (task) {
        if (!($scope.selected_room.id > 0 && $scope.guest.guest_name)) {
            toaster.pop('error', 'Task error', 'Please select room and guest');
            return;
        }

        GuestService.getTaskInfo(task.id, $scope.selected_room.location_group.id)
            .then(function (response) {
                console.log(response);
                var profile = AuthService.GetCredentials();

                var date = new Date();

                var data = response.data;
                if (data.department == undefined) {
                    toaster.pop('error', 'Task error', 'There is no department');
                    return;
                }

                if (data.staff_list.length < 1) {
                    toaster.pop('error', 'Task error', 'No Staff is on shift');
                    data.staff_list.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
                }

                var staff_name = data.staff_list[0].wholename;

                var systemtask_data = {};

                systemtask_data.property_id = profile.property_id;
                systemtask_data.dept_func = data.deptfunc.id;
                systemtask_data.department_id = data.department.id;
                systemtask_data.type = 1;
                systemtask_data.priority = $scope.prioritylist[$scope.prioritylist.length - 1].id;  // high priority
                systemtask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                systemtask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                systemtask_data.end_date_time = '0000-00-00 00:00:00';
                systemtask_data.dispatcher = data.staff_list[0].user_id;

                systemtask_data.attendant = profile.id;
                systemtask_data.room = $scope.guest.room_id;
                systemtask_data.task_list = task.id;
                systemtask_data.max_time = data.taskgroup.max_time;
                systemtask_data.quantity = 1;
                systemtask_data.custom_message = '';
                systemtask_data.status_id = 1;
                systemtask_data.feedback_flag = $scope.feedback_flag;
                systemtask_data.guest_id = $scope.guest.guest_id;
                systemtask_data.location_id = $scope.selected_room.location_group.id;

                var tasklist = [];
                tasklist.push(systemtask_data);

                $rootScope.myPromise = GuestService.createTaskList(tasklist);
                $rootScope.myPromise.then(function (response) {
                    console.log(response);
                    if (response.data.invalid_task_list.length == 0) {
                        $scope.main_task = {};
                        $scope.tasks = [];
                        $scope.quicktasks = [];
                        $scope.max_ticket_no = response.data.max_ticket_no;
                        $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                        $scope.$emit('onTicketChange', tasklist);

                        $scope.onRoomSelect($scope.selected_room);

                        if (systemtask_data.dispatcher > 0)
                            toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                        else
                            toaster.pop('error', 'Create Task', task.task + ' will be escalated to Managers.');
                    }
                    else {
                        $scope.showViewTicketToast(response.data.invalid_task_list);
                    }
                    $scope.selected_room.room = '';
                }).catch(function (response) {
                    toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
                })
                    .finally(function () {

                    });
            });

    }

    $scope.addSystemTaskq = function (task) {
        if (!($scope.selected_room.id > 0 && $scope.guest.guest_name)) {
            toaster.pop('error', 'Task error', 'Please select room and guest');
            return;
        }
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/ticket/guestmodal.html',
            controller: 'GuestSystemModalCtrl',
            resolve: {
                task: function () {

                    return task;
                },
                feedback_flag: function () {

                    return $scope.feedback_flag;
                },
                selected_room: function () {

                    return $scope.selected_room;
                },
                guest: function () {

                    return $scope.guest;
                },
                prioritylist: function () {

                    return $scope.prioritylist;
                }
            }

        });
    }

    $scope.addMainTaskGroup = function(item) {
        GuestService.createMainTaskGroup(1, $scope.selected_room.location_group.id, item)
            .then(function (response) {
                console.log(response);
                if (response.data.invalid_task_list.length == 0) {
                    $scope.main_task = {};
                    $scope.tasks = [];
                    $scope.quicktasks = [];
                    $scope.max_ticket_no = response.data.max_ticket_no;
                    $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                    $scope.$emit('onTicketChange', response.data);
                    $scope.onRoomSelect($scope.selected_room);

                    toaster.pop('success', 'Create Task', item.alias + ' is created');
                }
                else {
                    $scope.showViewTicketToast(response.data.invalid_task_list);
                }
            }).catch(function (response) {
            toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
        })
            .finally(function () {

            });

    }

    $scope.$on('create_new_task', function (event, args) {
        $scope.addQuickTask(args);
    });

    $scope.addQuickTask = function (task) {

        if ($scope.isMultiple == true) {
            if ($scope.selected_rooms.length < 1 || $scope.selected_guests.length < 1) {
                toaster.pop('error', 'Task error', 'Please select rooms!');
                return;
            }

            let request = {};
            request.task_id = task.id;
            request.location_groups = $scope.selected_guests.map(selected_guest => {
                let temp = {};
                temp.guest_id = selected_guest.guest_id;
                temp.location_id = selected_guest.location_group.id;
                temp.room_name = selected_guest.location_group.name;
                temp.room_id = selected_guest.room_id;

                return temp;
            });

            GuestService.getTaskInfoFromTask(request)
                .then(function (response) {
                    var profile = AuthService.GetCredentials();
                    var date = new Date();
                    var data = response.data;
                    if (data.department == undefined) {
                        toaster.pop('error', 'Task error', 'There is no department');
                        return;
                    }

                    let quicktask_data = {};

                    quicktask_data.property_id = profile.property_id;
                    quicktask_data.dept_func = data.deptfunc.id;
                    quicktask_data.department_id = data.department.id;
                    quicktask_data.type = 1;
                    quicktask_data.priority = $scope.prioritylist[0].id;
                    quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.end_date_time = '0000-00-00 00:00:00';
                    quicktask_data.feedback_flag = $scope.feedback_flag;
                    quicktask_data.attendant = profile.id;
                    quicktask_data.task_list = task.id;
                    quicktask_data.max_time = data.taskgroup.max_time;
                    quicktask_data.quantity = 1;
                    quicktask_data.custom_message = '';
                    quicktask_data.status_id = 1;

                    quicktask_data.info_list = data.location_groups.map(location_group => {
                        let temp = {};
                        temp.userlist = location_group.staff_list ? location_group.staff_list : [];
                        if (temp.userlist.length < 1) {
                            if( data.taskgroup.unassigne_flag == 1 ) {
                                temp.userlist.push({ id: 0, user_id: 0, wholename: 'Unassigned Task' });
                            } else {
                                temp.userlist.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
                            }
                        }

                        let tempObj = {
                            dispatcher: temp.userlist[0].user_id,
                            location_id: location_group.location_id,
                            room: location_group.room_id,
                            guest_id: location_group.guest_id
                        };

                        return tempObj;
                    });

                    var tasklist = [];
                    tasklist.push(quicktask_data);

                    $scope.isCreatingTasks = true;
                    var promise = GuestService.createTasklistNew(tasklist);
                    promise.then(function (res) {
                        console.log(res);
                        if (res.data.invalid_task_list.length == 0) {
                            $scope.main_task = {};
                            $scope.tasks = [];
                            $scope.quicktasks = [];
                            $scope.max_ticket_no = res.data.max_ticket_no;
                            $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                            $scope.$emit('onTicketChange', tasklist);

                            $scope.onLoadTasks();

                            toaster.pop('success', 'Create Task', "Successfully created!");
                        }
                        else {
                            $scope.showViewTicketToast(res.data.invalid_task_list);
                        }

                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
                    })
                        .finally(function () {
                            $scope.isCreatingTasks = false;
                        });
                });
        } else {
            if (!($scope.selected_room.id > 0 && $scope.guest.guest_name)) {
                toaster.pop('error', 'Task error', 'Please select room and guest');
                return;
            }

            GuestService.getTaskInfo(task.id, $scope.selected_room.location_group.id)
                .then(function (response) {
                    console.log(response);
                    var profile = AuthService.GetCredentials();

                    var date = new Date();

                    var data = response.data;
                    if (data.department == undefined) {
                        toaster.pop('error', 'Task error', 'There is no department');
                        return;
                    }

                    if (data.staff_list.length < 1) {
                        toaster.pop('error', 'Task error', 'No Staff is on shift');
                        data.staff_list.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
                    }

                    var staff_name = data.staff_list[0].wholename;

                    var quicktask_data = {};

                    quicktask_data.property_id = profile.property_id;
                    quicktask_data.dept_func = data.deptfunc.id;
                    quicktask_data.department_id = data.department.id;
                    quicktask_data.type = 1;
                    quicktask_data.priority = $scope.prioritylist[0].id;
                    quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.end_date_time = '0000-00-00 00:00:00';
                    quicktask_data.dispatcher = data.staff_list[0].user_id;
                    quicktask_data.feedback_flag = $scope.feedback_flag;

                    quicktask_data.attendant = profile.id;
                    quicktask_data.room = $scope.guest.room_id;
                    quicktask_data.task_list = task.id;
                    quicktask_data.max_time = data.taskgroup.max_time;
                    quicktask_data.quantity = 1;
                    quicktask_data.custom_message = '';
                    quicktask_data.status_id = 1;
                    quicktask_data.guest_id = $scope.guest.guest_id;
                    quicktask_data.location_id = $scope.selected_room.location_group.id;

                    var tasklist = [];
                    tasklist.push(quicktask_data);

                    $rootScope.myPromise = GuestService.createTaskList(tasklist);
                    $rootScope.myPromise.then(function (response) {
                        console.log(response);
                        if (response.data.invalid_task_list.length == 0) {
                            $scope.main_task = {};
                            $scope.tasks = [];
                            $scope.quicktasks = [];
                            $scope.max_ticket_no = response.data.max_ticket_no;
                            $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                            $scope.$emit('onTicketChange', tasklist);

                            $scope.onRoomSelect($scope.selected_room);

                            if (quicktask_data.dispatcher > 0)
                                toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                            else
                                toaster.pop('error', 'Create Task', task.task + ' will be escalated to Managers.');
                        }
                        else {
                            $scope.showViewTicketToast(response.data.invalid_task_list);
                        }
                        $scope.selected_room.room = '';
                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
                    })
                        .finally(function () {

                        });
                });
        }
    };

    // $scope.cot=0;
    $scope.addQuickTasksq = function (task) {

        if ($scope.isMultiple == true) {
            if ($scope.selected_rooms.length < 1 || $scope.selected_guests.length < 1) {
                toaster.pop('error', 'Task error', 'Please select rooms');
                return;
            }
        } else {
            if (!($scope.selected_room.id > 0 && $scope.guest.guest_name)) {
                toaster.pop('error', 'Task error', 'Please select room and guest');
                return;
            }
            //$scope.quantity=1;
        }

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/ticket/guestmodal.html',
            controller: 'GuestModalCtrl',
            resolve: {
                task: function () {

                    return task;
                },
                feedback_flag: function () {

                    return $scope.feedback_flag;
                },
                isMultiple: function() {
                    return $scope.isMultiple;
                },
                selected_rooms: function() {
                    return $scope.selected_rooms;
                },
                selected_room: function () {

                    return $scope.selected_room;
                },
                selected_guests: function() {
                    return $scope.selected_guests;
                },
                guest: function () {

                    return $scope.guest;
                },
                prioritylist: function () {

                    return $scope.prioritylist;
                }
            }
        });
    }

    $scope.$on('onaddQuickTaskq', function (event, args) {
        //toaster.pop('error', 'Balls');

        if ($scope.isMultiple == true) {
            $scope.onLoadTasks();
        } else {
            $scope.onRoomSelect(args);
        }
        //toaster.pop('error', 'Refreshed');
    });


    function isValidTask(task, message_flag) {
        var data = {};

        if (!task.tasklist) {
            if (message_flag == true)
                toaster.pop('error', 'Error', 'Please select task list');
            return false;
        }

        if ($scope.isMultiple == true) {
            if (!task.dept_func || task.userInfoArr == undefined) {
                if (message_flag == true)
                    toaster.pop('error', 'Validate Error', 'Please input all fields');
                return false;
            }
        } else {
            if (!task.dept_func || !task.dispatcher) {
                if (message_flag == true)
                    toaster.pop('error', 'Validate Error', 'Please input all fields');
                return false;
            }
        }

        return true;
    }

    function getTaskData(task) {
        var profile = AuthService.GetCredentials();

        var data = {};

        data.property_id = profile.property_id;

        if (!task.tasklist) {
            return;
        }

        if ($scope.isMultiple == true) {
            if (!task.dept_func || task.userInfoArr == undefined) {
                return;
            }
            data.dept_func = task.dept_func.id;
            data.department_id = task.department_id;
            data.type = 1;
            data.priority = task.priority_id;

            var date = new Date();
            var date1 = angular.copy(date);
            data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");

            data.info_list = [];
            if (task.schedule_flag == false) {
                data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                data.status_id = 1;

                task.userInfoArr.forEach(userInfo => {
                    let dispatcher = userInfo.dispatcher.user_id;
                    let location_id = userInfo.location_group_id;
                    let room = userInfo.room_id;
                    let guest_id = userInfo.guest_id;

                    let temp = {
                        dispatcher,
                        location_id,
                        room,
                        guest_id
                    };

                    data.info_list.push(temp);
                });
                data.running = 1;
            }
            else {
                var date = '';
                if (task.date instanceof Date)
                    date = moment(task.date).format('YYYY-MM-DD');
                else
                    date = task.date;

                data.start_date_time = date;
                data.status_id = 5; // schedule state
                data.running = 0;

                task.userInfoArr.forEach(userInfo => {
                    let dispatcher = 0;
                    let location_id = userInfo.location_group_id;
                    let room = userInfo.room_id;
                    let guest_id = userInfo.guest_id;

                    let temp = {
                        dispatcher,
                        location_id,
                        room,
                        guest_id
                    };

                    data.info_list.push(temp);
                });
                data.running = 1;
            }

            data.repeat_flag = task.repeat_flag;
            data.until_checkout_flag = task.until_checkout_flag;
            if (task.until_checkout_flag == true) {
                data.repeat_end_date = moment(date1).format("YYYY-MM-DD");
            } else {
                if (task.repeat_end_date instanceof Date)
                    data.repeat_end_date = moment(task.repeat_end_date).format("YYYY-MM-DD");
                else
                    data.repeat_end_date = task.repeat_end_date;
            }

            data.end_date_time = '0000-00-00 00:00:00';

            data.attendant = profile.id;
            data.task_list = task.tasklist.id;
            data.max_time = (task.max_duration*60);
            data.quantity = task.quantity;
            data.custom_message = task.custom_message;
            data.feedback_flag = task.feedback_flag;

            return data;
        } else {
            if (!task.dept_func || !task.dispatcher) {
                return;
            }
            data.dept_func = task.dept_func.id;
            data.department_id = task.department_id;
            data.type = 1;
            data.priority = task.priority_id;

            var date = new Date();
            var date1 = angular.copy(date);
            data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
            if (task.schedule_flag == false) {
                data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                data.status_id = 1;
                data.dispatcher = task.dispatcher.user_id;
                data.running = 1;
            }
            else {
                var date = '';
                if (task.date instanceof Date)
                    date = moment(task.date).format('YYYY-MM-DD');
                else
                    date = task.date;

                data.start_date_time = date;
                data.status_id = 5; // schedule state
                data.running = 0;
                data.dispatcher = 0;
            }

            data.repeat_flag = task.repeat_flag;
            data.until_checkout_flag = task.until_checkout_flag;
            if (task.until_checkout_flag == true) {
                data.repeat_end_date = moment(date1).format("YYYY-MM-DD");
            } else {
                if (task.repeat_end_date instanceof Date)
                    data.repeat_end_date = moment(task.repeat_end_date).format("YYYY-MM-DD");
                else
                    data.repeat_end_date = task.repeat_end_date;
            }

            data.end_date_time = '0000-00-00 00:00:00';

            data.attendant = profile.id;
            data.room = $scope.selected_room.id;
            data.task_list = task.tasklist.id;
            data.max_time = (task.max_duration*60);
            data.quantity = task.quantity;
            data.custom_message = task.custom_message;
            data.feedback_flag = task.feedback_flag;
            data.guest_id = $scope.guest.guest_id;
            data.location_id = $scope.selected_room.location_group.id;

            return data;
        }
    }

    $scope.createTasks = function (flag) {  // 0: only create, 1: Create and another for same room, 2: Create and another for diff room
        $scope.disable_create = 1;
        var tasklist = [];

        if ($scope.isMultiple == true ) {
            if ($scope.selected_rooms.length < 1 || $scope.selected_guests.length < 1) {
                toaster.pop('error', 'Task error', 'Please select rooms');
                $scope.disable_create=0;
                return;
            }

            $scope.addTask();

            for (let i = 0; i < $scope.tasks.length; i++) {
                let task = $scope.tasks[i];
                let data = getTaskData(task);
                if (!data)
                    continue;

                tasklist.push(data);
            }

            if (tasklist.length < 1) {
                toaster.pop('error', 'Task error', 'Please add a task');
                $scope.disable_create=0;
                return;
            }

            // create
            $scope.isCreatingTasks = true;

            let promise = GuestService.createTasklistNew(tasklist);
            promise.then(function (response) {
                console.log(response);
                $scope.disable_create=0;

                var uploadCount = $scope.uploadImages(response.data.ticket_number_id, $scope.tasks);

                if (response.data.invalid_task_list.length == 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Tasks created successfully');

                    $scope.main_task = {};
                    $scope.tasks = [];
                    $scope.quicktasks = [];

                    $scope.max_ticket_no = response.data.max_ticket_no;
                    $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                    // if( uploadCount == 0 )
                    //     $scope.$emit('onTicketChange', tasklist);

                    if (flag == 0) // Create
                    {
                        $scope.selected_rooms = [];
                        $scope.room_num = '';
                        $scope.selected_guests = [];
                        $scope.$emit('onTicketCreateFinished', 1);      // Guest Request
                    }
                    if (flag == 1) // Create Create & add another for same room
                    {
                        // refresh quick task list
                        $scope.onLoadTasks();
                    }

                    if (flag == 2) // Create Create & add another for another room
                    {
                        $scope.selected_rooms = [];
                        $scope.room_num = '';
                        $scope.selected_guests = [];
                    }
                    $scope.feedback_flag = false;
                }
                else {
                    $scope.showViewTicketToast(response.data.invalid_task_list);
                }


            }).catch(function (response) {
                toaster.pop('error', 'Create Task', 'Failed to create Tasks');
                $scope.disable_create=0;
            })
                .finally(function () {
                    $scope.isCreatingTasks = false;
                });
        } else {
            if (!($scope.selected_room.id > 0 && $scope.guest.guest_name)) {
                toaster.pop('error', 'Task error', 'Please select room and guest');
                $scope.disable_create=0;
                return;
            }

            $scope.addTask();

            for (var i = 0; i < $scope.tasks.length; i++) {
                var task = $scope.tasks[i];
                var data = getTaskData(task);
                if (!data)
                    continue;

                tasklist.push(data);
            }

            if (tasklist.length < 1) {
                toaster.pop('error', 'Task error', 'Please add a task');
                $scope.disable_create=0;
                return;
            }


            $rootScope.myPromise = GuestService.createTaskList(tasklist);
            $rootScope.myPromise.then(function (response) {
                console.log(response);
                $scope.disable_create=0;

                var uploadCount = $scope.uploadImages(response.data.ticket_number_id, $scope.tasks);

                if (response.data.invalid_task_list.length == 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Tasks created successfully');

                    $scope.main_task = {};
                    $scope.tasks = [];
                    $scope.quicktasks = [];

                    $scope.max_ticket_no = response.data.max_ticket_no;
                    $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                    // if( uploadCount == 0 )
                    //     $scope.$emit('onTicketChange', tasklist);

                    if (flag == 0) // Create
                    {
                        $scope.selected_room = {};
                        $scope.room_num = '';
                        $scope.guest = {};
                        $scope.$emit('onTicketCreateFinished', 1);      // Guest Request
                    }
                    if (flag == 1) // Create Create & add another for same room
                    {
                        // refresh quick task list
                        $scope.onRoomSelect($scope.selected_room);
                    }

                    if (flag == 2) // Create Create & add another for another room
                    {
                        $scope.selected_room = {};
                        $scope.room_num = '';
                        $scope.guest = {};
                    }
                    $scope.feedback_flag = false;
                }
                else {
                    $scope.showViewTicketToast(response.data.invalid_task_list);
                }


            }).catch(function (response) {
                toaster.pop('error', 'Create Task', 'Failed to create Tasks');
                $scope.disable_create=0;
            })
                .finally(function () {

                });
        }

    }

    $scope.$watch('datetime.date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.main_task.date = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if ($view == 'day') {
            var activeDate = moment().subtract('days', 1);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        else if ($view == 'minute') {
            var activeDate = moment().subtract('minute', 5);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.history_view = false;
    $scope.historylist = {};
    $scope.showGuestHisotry = function (guest_name) {
        $scope.history_view = true;
        $scope.history.limit = 10;
    }

    $scope.getHistoryType = function (type_id) {
        if (type_id == 1) return 'Guest';
        if (type_id == 3) return 'Complaints';
    }

    $scope.hideGuestHisotry = function () {
        $scope.history_view = false;
        $scope.history.limit = 0;
    }

    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        dateDisabled: disabled,
        class: 'datepicker'
    };

    function disabled(data) {
        var date = data.date;
        var sel_date = moment(date).format('YYYY-MM-DD');
        var disabled = true;
        if (moment().add(1, 'days').format('YYYY-MM-DD') <= sel_date && sel_date <= $scope.guest.departure)
            disabled = false;
        else
            disabled = true;

        mode = data.mode;
        return mode === 'day' && disabled;
    }

    $scope.select = function (date) {
        console.log(date);

        $scope.opened = false;
    }

});

app.controller('GuestModalCtrl', function ($scope, $rootScope, $http, AuthService, feedback_flag, GuestService, $interval, toaster, $timeout, $uibModalInstance, task, guest, selected_room, isMultiple, selected_rooms, selected_guests, prioritylist) {
    /*
               $scope.ok = function () {
            $uibModalInstance.close($scope.sub);
            };
    */

    $scope.quantity = 1;
    $scope.custom_message = '';
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    if (isMultiple == true) {
        let request = {};
        request.task_id = task.id;
        request.location_groups = selected_guests.map(selected_guest => {
            let temp = {};
            temp.guest_id = selected_guest.guest_id;
            temp.location_id = selected_guest.location_group.id;
            temp.room_name = selected_guest.location_group.name;
            temp.room_id = selected_guest.room_id;

            return temp;
        });

        GuestService.getTaskInfoFromTask(request)
            .then(function (response) {
                var profile = AuthService.GetCredentials();
                var date = new Date();
                var data = response.data;
                if (data.department == undefined) {
                    toaster.pop('error', 'Task error', 'There is no department');
                    return;
                }

                let quicktask_data = {};

                quicktask_data.property_id = profile.property_id;
                quicktask_data.dept_func = data.deptfunc.id;
                quicktask_data.department_id = data.department.id;
                quicktask_data.type = 1;
                quicktask_data.priority = prioritylist[0].id;
                quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.end_date_time = '0000-00-00 00:00:00';
                quicktask_data.feedback_flag = feedback_flag;
                quicktask_data.attendant = profile.id;
                quicktask_data.task_list = task.id;
                quicktask_data.max_time = data.taskgroup.max_time;

                quicktask_data.status_id = 1;

                quicktask_data.info_list = data.location_groups.map(location_group => {
                    let temp = {};
                    temp.userlist = location_group.staff_list ? location_group.staff_list : [];
                    if (temp.userlist.length < 1) {
                        if( data.taskgroup.unassigne_flag == 1 ) {
                            temp.userlist.push({ id: 0, user_id: 0, wholename: 'Unassigned Task' });
                        } else {
                            temp.userlist.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
                        }
                    }

                    let tempObj = {
                        dispatcher: temp.userlist[0].user_id,
                        location_id: location_group.location_id,
                        room: location_group.room_id,
                        guest_id: location_group.guest_id
                    };

                    return tempObj;
                });

                $scope.create = function () {
                    quicktask_data.quantity = $scope.quantity;
                    quicktask_data.custom_message = $scope.custom_message;

                    var tasklist = [];
                    tasklist.push(quicktask_data);

                    $scope.isCreatingTasks = true;
                    var promise = GuestService.createTasklistNew(tasklist);
                    promise.then(function (res) {
                        console.log(res);
                        if (res.data.invalid_task_list.length == 0) {
                            $scope.main_task = {};
                            $scope.tasks = [];
                            $scope.quicktasks = [];
                            $scope.max_ticket_no = res.data.max_ticket_no;
                            $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                            $rootScope.$broadcast('onTicketChange', tasklist);
                            $rootScope.$broadcast('onaddQuickTaskq', selected_rooms);

                            $uibModalInstance.dismiss();

                            toaster.pop('success', 'Create Task', "Successfully created!");
                        }
                        else {
                            $scope.showViewTicketToast(res.data.invalid_task_list);
                        }

                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
                    })
                        .finally(function () {
                            $scope.isCreatingTasks = false;
                        });
                };
            });
    } else {
        GuestService.getTaskInfo(task.id, selected_room.location_group.id)
            .then(function (response) {
                console.log(response);
                var profile = AuthService.GetCredentials();

                var date = new Date();

                var data = response.data;
                if (data.department == undefined) {
                    toaster.pop('error', 'Task error', 'There is no department');
                    return;
                }

                if (data.staff_list.length < 1) {
                    toaster.pop('error', 'Task error', 'No Staff is on shift yes');
                    data.staff_list.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift ' });
                }

                var staff_name = data.staff_list[0].wholename;

                var quicktask_data = {};

                quicktask_data.property_id = profile.property_id;
                quicktask_data.dept_func = data.deptfunc.id;
                quicktask_data.department_id = data.department.id;
                quicktask_data.type = 1;
                quicktask_data.priority = prioritylist[0].id;
                quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.end_date_time = '0000-00-00 00:00:00';
                quicktask_data.dispatcher = data.staff_list[0].user_id;

                quicktask_data.attendant = profile.id;
                quicktask_data.room = guest.room_id;
                quicktask_data.task_list = task.id;
                quicktask_data.max_time = data.taskgroup.max_time;
                quicktask_data.status_id = 1;
                quicktask_data.feedback_flag = feedback_flag;
                quicktask_data.guest_id = guest.guest_id;
                quicktask_data.location_id = selected_room.location_group.id;


                $scope.create = function () {

                    quicktask_data.quantity = $scope.quantity;


                    quicktask_data.custom_message = $scope.custom_message;

                    var tasklist = [];
                    tasklist.push(quicktask_data);

                    $rootScope.myPromise = GuestService.createTaskList(tasklist);
                    $rootScope.myPromise.then(function (response) {
                        console.log(response);
                        if (response.data.invalid_task_list.length == 0) {
                            $scope.main_task = {};
                            $scope.tasks = [];
                            $scope.quicktasks = [];
                            $scope.max_ticket_no = response.data.max_ticket_no;
                            $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                            $rootScope.$broadcast('onTicketChange', tasklist);
                            $rootScope.$broadcast('onaddQuickTaskq', selected_room);
                            //$scope.onRoomSelect($scope.selected_room);

                            //$scope.$emit('onRoomSelect', selected_room);
                            //$scope.$emit('onTicketCreateFinished', 1);

                            if (quicktask_data.dispatcher > 0)
                                toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                            else
                                toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                        }
                        else {
                            $scope.showViewTicketToast(response.data.invalid_task_list);
                        }

                        selected_room.room = '';

                        $uibModalInstance.dismiss();
                        /// $scope.refreshTickets();
                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Task creation unsucessful');
                    })
                        .finally(function () {

                        });
                };
            });
    }
});

app.controller('GuestSystemModalCtrl', function ($scope, $rootScope, $http, AuthService, GuestService, $interval, toaster, feedback_flag, $timeout, $uibModalInstance, task, guest, selected_room, prioritylist) {
    /*
               $scope.ok = function () {
            $uibModalInstance.close($scope.sub);
            };
    */
    $scope.quantity = 1;
    $scope.custom_message = '';
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    GuestService.getTaskInfo(task.id, selected_room.location_group.id)
        .then(function (response) {
            console.log(response);
            var profile = AuthService.GetCredentials();

            var date = new Date();

            var data = response.data;
            if (data.department == undefined) {
                toaster.pop('error', 'Task error', 'There is no department');
                return;
            }

            if (data.staff_list.length < 1) {
                toaster.pop('error', 'Task error', 'No Staff is on shift');
                data.staff_list.push({ id: 0, user_id: 0, wholename: 'No Staff is on shift' });
            }

            var staff_name = data.staff_list[0].wholename;

            var systemtask_data = {};

            systemtask_data.property_id = profile.property_id;
            systemtask_data.dept_func = data.deptfunc.id;
            systemtask_data.department_id = data.department.id;
            systemtask_data.type = 1;
            systemtask_data.priority = prioritylist[prioritylist.length - 1].id;  // high priority
            systemtask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
            systemtask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
            systemtask_data.end_date_time = '0000-00-00 00:00:00';
            systemtask_data.dispatcher = data.staff_list[0].user_id;

            systemtask_data.attendant = profile.id;
            systemtask_data.room = guest.room_id;
            systemtask_data.task_list = task.id;
            systemtask_data.feedback_flag = feedback_flag;
            systemtask_data.max_time = data.taskgroup.max_time;
            /*
                            systemtask_data.quantity = 1;
                            systemtask_data.custom_message = '';
            */
            systemtask_data.status_id = 1;
            systemtask_data.guest_id = guest.guest_id;
            systemtask_data.location_id = selected_room.location_group.id;

            $scope.create = function () {
                systemtask_data.quantity = $scope.quantity;


                systemtask_data.custom_message = $scope.custom_message;

                var tasklist = [];
                tasklist.push(systemtask_data);

                $rootScope.myPromise = GuestService.createTaskList(tasklist);
                $rootScope.myPromise.then(function (response) {
                    console.log(response);
                    if (response.data.invalid_task_list.length == 0) {
                        $scope.main_task = {};
                        $scope.tasks = [];
                        $scope.quicktasks = [];
                        $scope.max_ticket_no = response.data.max_ticket_no;
                        $scope.ticket_id = sprintf('G%05d', $scope.max_ticket_no + 1);

                        $rootScope.$broadcast('onTicketChange', tasklist);
                        $rootScope.$broadcast('onaddQuickTaskq', selected_room);

                        if (systemtask_data.dispatcher > 0)
                            toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                        else
                            toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                    }
                    else {
                        $scope.showViewTicketToast(response.data.invalid_task_list);
                    }
                    selected_room.room = '';
                    $uibModalInstance.dismiss();
                }).catch(function (response) {
                    toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
                })
                    .finally(function () {

                    });

            };
        });


});


app.directive('sglguestclick', ['$parse', function ($parse) {

    return {
        restrict: 'A',
        link: function (scope, element, attr) {
            var fn = $parse(attr['sglguestclick']);
            var delay = 300, clicks = 0, timer = null;
            element.on('click', function (event) {
                clicks++;  //count clicks
                if (clicks === 1) {
                    timer = setTimeout(function () {
                        scope.$apply(function () {
                            fn(scope, { $event: event });
                        });
                        clicks = 0;             //after action performed, reset counter
                    }, delay);
                } else {
                    clearTimeout(timer);    //prevent single-click action
                    clicks = 0;             //after action performed, reset counter
                }
            });
        }
    };
}]);

app.directive('myEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEnter);
                });

                event.preventDefault();
            }
        });
    };
});

app.directive('myEsc', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 27) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEsc);
                });

                event.preventDefault();
            }
        });
    };
});


