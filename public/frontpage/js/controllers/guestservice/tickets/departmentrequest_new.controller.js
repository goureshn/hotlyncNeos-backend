app.controller('DepartmentrequestController', function ($scope, $rootScope, $http, $interval, $uibModal, toaster, GuestService, AuthService, Upload) {
    var MESSAGE_TITLE = 'Create Department Task';

    $scope.tasks = [];
    $scope.location = {};
    $scope.quicktasks = [];
    $scope.datetime = {};
    $scope.feedback_flag = '';
    $scope.disable_create=0;

    // multiple part
    $scope.isMultiple = false;
    $scope.request_time = "";
    $scope.location_type = "";
    $scope.requester = {};
    $scope.isCreatingTasks = false;
    $scope.selected_locations = [];

    GuestService.getMaxTicketNo()
        .then(function (response) {
            var new_ticket_length = 0;
            if ($scope.newTickets)
                new_ticket_length = $scope.newTickets.length;
            $scope.max_ticket_no = response.data.max_ticket_no + new_ticket_length - 1;

            $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);
        });

    $scope.request_time = moment().format("HH:mm:ss");
    $scope.timer = $interval(function () {
        $scope.request_time = moment().format("HH:mm:ss");
    }, 1000);

    $scope.feedbackFlag = function () {
        if ($scope.feedback_flag == false)
            $scope.feedback_flag = true;
        else
            $scope.feedback_flag = false;
    }

    $scope.onLocationInfoChanged = function(type) {
        if (type === 'add') {
            $scope.selected_locations = $scope.selected_locations.filter((item) => {
                return item.id ? true : false;
            });
        }

        $scope.tasks = [];
        $scope.location_type = "";
        $scope.tasks = [];
    };

    $scope.onLoadTasks = function() {
        if ($scope.selected_locations.length < 1) {
            toaster.pop('warning', MESSAGE_TITLE, 'Please select locations!');
            return;
        }
        var profile = AuthService.GetCredentials();
        $scope.requester = profile;
        $scope.requester.wholename = profile.first_name + ' ' + profile.last_name;
        $scope.addMainTask();

        if ($scope.selected_locations.length == 1) {
            $scope.location_type = $scope.selected_locations[0].type;
        }

        let property_id = $scope.selected_locations[0].property_id;
        GuestService.getQuickTaskList(2, property_id)
            .then(function (response) {
                $scope.quicktasks = response.data;
            });

        GuestService.getMainTaskList(2, property_id)
            .then(function (response) {
                $scope.maintasks = response.data;
            });
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.location = angular.copy($item);

        if( $item.type == 'Room')
        {
            $scope.$emit('checkin_room_selected', $item);
        }

        var profile = AuthService.GetCredentials();
        $scope.location.requester = profile;
        $scope.location.requester.wholename = profile.first_name + ' ' + profile.last_name;

        $scope.addMainTask();

        GuestService.getQuickTaskList(2, $item.property_id)
            .then(function (response) {
                $scope.quicktasks = response.data;
            });

        GuestService.getMainTaskList(2, $item.property_id)
            .then(function (response) {
                $scope.maintasks = response.data;
            });
    };

    $scope.$on('room_selected', function (event, args) {

        if (args.isMultiple == true) {
            $scope.selected_locations = args.location_groups;
            $scope.isMultiple = args.isMultiple;
            $scope.onLoadTasks();
        } else {
            GuestService.getLocationGroupFromRoom(args.id)
                .then(function (response) {
                    var location = response.data;
                    location.name = args.room;
                    location.property_id = args.property_id;
                    $scope.onLocationSelect(location, null, null);
                });
        }
    });

    $scope.$on('checkout_room_selected1', function (event, args) {
        GuestService.getLocationGroupFromRoom(args.id)
            .then(function (response) {
                var location = response.data;
                location.name = args.room;
                location.property_id = args.property_id;
                $scope.onLocationSelect(location, null, null);
            });
    });

    $scope.$on('close_dialog', function (event, args) {
        // $uibModalInstance.close();
    });

    $scope.getStaffList = function (val) {
        if (val == undefined)
            val = "";

        return GuestService.getStaffList(val)
            .then(function (response) {
                return response.data.filter(function (item, index, attr) {
                    return index < 10;
                });
            });
    };

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.location.requester = $item;

    };

    $scope.addMainTask = function () {
        var date = new Date();

        $scope.main_task = {
            ticket_no: $scope.max_ticket_no + 1,
            task_name: "",
            qunatity: 1,
            department: "",
            dept_func: "",
            dept_staff: "",
            device: "",
            priority_id: $scope.prioritylist[0].id,
            max_duration: "",
            custom_message: "",
            feedback_flag: false,
            created_time: moment().format("YYYY-MM-DD HH:mm:ss"),
            start_date_time: moment().format("YYYY-MM-DD HH:mm:ss"),
            date: moment().format("YYYY-MM-DD HH:mm:ss"),
            schedule_flag: false,

            repeat_end_date: new Date(),
            repeat_flag: false,
            until_checkout_flag: false
        }

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

    $scope.getTaskList = function (val) {
        if (val == undefined)
            val = "";
        console.log("task select");
        let property_id = 0;

        if ($scope.isMultiple == true) {
            property_id = $scope.selected_locations[0].property_id;
        } else {
            property_id = $scope.location.property_id;
        }

        return GuestService.getTaskList(val, property_id, 2)
            .then(function (response) {
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

    $scope.onMainTaskSelect = function (task, $item, $model, $label) {
        console.log($item);

        if ($scope.isMultiple == true) {
            if ($scope.selected_locations.length < 1) {
                toaster.pop('error', MESSAGE_TITLE, 'Please select locations!');
                return;
            }

            $scope.addMainTask();

            if (checkDuplicatedTask($item))
                return;

            $scope.main_task.schedule_flag = false;
            $scope.main_task.quantity = 1;
            $scope.main_task.tasklist = $item;

            let request = {};
            request.task_id = $item.id;
            request.location_groups = $scope.selected_locations.map(selected_location => {
                let temp = {};
                temp.location_id = selected_location.id;
                temp.room_name = selected_location.name;
                temp.room_id = selected_location.type == 'Room' ? selected_location.room_id : 0;
                temp.location_type = selected_location.type;

                return temp;
            });

            GuestService.getTaskInfoFromTask(request)
                .then(function (response) {
                    console.log(response);
                    showSelectedDepartmentInfo(response.data);
                });
        } else {
            if (!$scope.location) {
                toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
                return;
            }

            $scope.addMainTask();

            if (checkDuplicatedTask($item))
                return;

            $scope.main_task.schedule_flag = false;
            $scope.main_task.quantity = 1;
            $scope.main_task.tasklist = $item;

            GuestService.getTaskInfo($item.id, $scope.location.id)
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

        var task = $scope.main_task;

        task.department = $item.department;
        task.department_id = $item.id;
        task.dept_func = null;

        // getSelectedDepartmentInfo($item);
    };

    $scope.onToggleMultiple = function() {
        $scope.isMultiple = !$scope.isMultiple;

        $scope.tasks = [];
        $scope.main_task = {};
    };

    $scope.onMainDeptFuncSelect = function (task, $item, $model, $label) {
        console.log($item);

        if (!$scope.location) {
            toaster.pop('error', MESSAGE_TITLE, 'There is no location group');
            return;
        }

        getSelectedDepartmentInfo($item);
    };

    function getSelectedDepartmentInfo($item) {
        var task = $scope.main_task;

        task.dept_func = {};

        if (!($item.dept_func_id > 0)) {
            toaster.pop('error', MESSAGE_TITLE, 'There is valid default department function for this departmetn');
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
        request.location_group_id = $scope.location.location_grp;
        request.location_id = $scope.location.id;
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
                temp.location_type = location_group.location_type;
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
            if (!task.dept_func || task.userInfoArr === undefined) {
                return;
            }
            data.dept_func = task.dept_func.id;
            data.department_id = task.department_id;
            data.type = 2;
            data.priority = task.priority_id;

            var date = new Date();
            data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");

            data.info_list = [];
            // window.alert(data.created_time);
            if (task.schedule_flag == false) {
                data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                data.status_id = 1;

                task.userInfoArr.forEach(userInfo => {
                    let dispatcher = userInfo.dispatcher.user_id;
                    let location_id = userInfo.location_group_id;
                    let room = userInfo.location_type == 'Room' ? userInfo.room_id : 0;
                    let guest_id = 0;

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
                task.userInfoArr.forEach(userInfo => {
                    let dispatcher = 0;
                    let location_id = userInfo.location_group_id;
                    let room = userInfo.location_type == 'Room' ? userInfo.room_id : 0;
                    let guest_id = 0;

                    let temp = {
                        dispatcher,
                        location_id,
                        room,
                        guest_id
                    };

                    data.info_list.push(temp);
                });

                data.running = 0;
            }

            data.repeat_flag = task.repeat_flag;
            data.until_checkout_flag = task.until_checkout_flag;
            if (task.until_checkout_flag == true) {
                // data.repeat_end_date = date1.format("YYYY-MM-DD");
            } else {
                if (task.repeat_end_date instanceof Date)
                    data.repeat_end_date = moment(task.repeat_end_date).format("YYYY-MM-DD");
                else
                    data.repeat_end_date = task.repeat_end_date;
            }

            data.end_date_time = '0000-00-00 00:00:00';

            // data.dispatcher = task.dispatcher.user_id;
            data.attendant = profile.id;
            data.task_list = task.tasklist.id;
            data.max_time = (task.max_duration*60);
            data.quantity = task.quantity;
            data.custom_message = task.custom_message;

            data.requester_id = $scope.requester.id;
            data.requester_name = $scope.requester.wholename;
            data.requester_job_role = $scope.job_role;

            data.requester_notify_flag = $scope.notify_flag ? 1 : 0;
            data.requester_email = $scope.requester.email;
            data.requester_mobile = $scope.requester.mobile;
            data.feedback_flag = task.feedback_flag;
        } else {
            if (!task.dept_func || !task.dispatcher) {
                return;
            }
            data.dept_func = task.dept_func.id;
            data.department_id = task.department_id;
            data.type = 2;
            data.priority = task.priority_id;

            var date = new Date();
            data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
            // window.alert(data.created_time);
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
                // data.repeat_end_date = date1.format("YYYY-MM-DD");
            } else {
                if (task.repeat_end_date instanceof Date)
                    data.repeat_end_date = moment(task.repeat_end_date).format("YYYY-MM-DD");
                else
                    data.repeat_end_date = task.repeat_end_date;
            }

            data.end_date_time = '0000-00-00 00:00:00';

            // data.dispatcher = task.dispatcher.user_id;
            data.attendant = profile.id;

            if ($scope.location.type == 'Room')
                data.room = $scope.location.room_id;
            else
                data.room = 0;

            data.task_list = task.tasklist.id;
            data.max_time = (task.max_duration*60);
            data.quantity = task.quantity;
            data.custom_message = task.custom_message;
            data.guest_id = 0;
            data.location_id = $scope.location.id;

            data.requester_id = $scope.location.requester.id;
            data.requester_name = $scope.location.requester.wholename;
            data.requester_job_role = $scope.location.requester.job_role;

            data.requester_notify_flag = $scope.location.notify_flag ? 1 : 0;
            data.requester_email = $scope.location.requester.email;
            data.requester_mobile = $scope.location.requester.mobile;
            data.feedback_flag = task.feedback_flag;
        }

        return data;
    }

    $scope.createTasks = function (flag) {
        $scope.disable_create = 1;

        if ($scope.isMultiple == true) {
            if ($scope.selected_locations.length < 0) {
                toaster.pop('error', MESSAGE_TITLE, 'Please select locations!')
                $scope.disable_create=0;
                return;
            }

            $scope.addTask(false);

            var tasklist = [];
            for (var i = 0; i < $scope.tasks.length; i++) {
                var task = $scope.tasks[i];
                var data = getTaskData(task);
                if (!data)
                    continue;

                tasklist.push(data);
            }

            if (tasklist.length < 1) {
                toaster.pop('error', 'Task error', 'Please add a task!');
                $scope.disable_create=0;
                return;
            }
            console.log(tasklist);

            // create
            $scope.isCreatingTasks = true;

            $rootScope.myPromise = GuestService.createTasklistNew(tasklist);
            $rootScope.myPromise.then(function (response) {
                console.log(response);

                var uploadCount = $scope.uploadImages(response.data.ticket_number_id, $scope.tasks);

                $scope.disable_create=0;
                if (response.data.invalid_task_list.length == 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Tasks created successfully');

                    $scope.main_task = {};
                    $scope.tasks = [];
                    $scope.quicktasks = [];
                    $scope.max_ticket_no = response.data.max_ticket_no;
                    $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                    // if( uploadCount == 0 )
                    //     $scope.$emit('onTicketChange', tasklist);

                    if (flag == 0) // Create
                    {
                        $scope.selected_locations = [];
                        $scope.$emit('onTicketCreateFinished', 2);      // Department Request
                    }
                    if (flag == 1) // Create Create & add another for same room
                    {
                        // refresh quick task list
                        $scope.onLoadTasks();
                    }

                    if (flag == 2) // Create Create & add another for same room
                    {
                        $scope.selected_locations = [];
                    }
                    $scope.feedback_flag = false;
                }
                else {
                    $scope.showViewTicketToast(response.data.invalid_task_list);
                }
            }).catch(function (response) {
                $scope.disable_create=0;
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Tasks!');
            })
                .finally(function () {
                    $scope.isCreatingTasks = false;
                });
        } else {
            if (!($scope.location.id > 0 && $scope.location.requester && $scope.location.requester.wholename.length > 0)) {
                toaster.pop('error', MESSAGE_TITLE, 'Please select location and requester')
                $scope.disable_create=0;
                return;
            }

            $scope.addTask(false);

            var tasklist = [];
            for (var i = 0; i < $scope.tasks.length; i++) {
                var task = $scope.tasks[i];
                var data = getTaskData(task);
                if (!data)
                    continue;

                tasklist.push(data);
            }

            if (tasklist.length < 1) {
                toaster.pop('error', 'Task error', 'Please add a task!');
                $scope.disable_create=0;
                return;
            }
            console.log(tasklist);
            $rootScope.myPromise = GuestService.createTaskList(tasklist);
            $rootScope.myPromise.then(function (response) {
                console.log(response);

                var uploadCount = $scope.uploadImages(response.data.ticket_number_id, $scope.tasks);

                $scope.disable_create=0;
                if (response.data.invalid_task_list.length == 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Tasks created successfully');

                    $scope.main_task = {};
                    $scope.tasks = [];
                    $scope.quicktasks = [];
                    $scope.max_ticket_no = response.data.max_ticket_no;
                    $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                    // if( uploadCount == 0 )
                    //     $scope.$emit('onTicketChange', tasklist);

                    if (flag == 0) // Create
                    {
                        $scope.location = {};
                        $scope.$emit('onTicketCreateFinished', 2);      // Department Request
                    }
                    if (flag == 1) // Create Create & add another for same room
                    {
                        // refresh quick task list
                        $scope.onLocationSelect($scope.location);
                    }

                    if (flag == 2) // Create Create & add another for same room
                    {
                        $scope.location = {};
                    }
                    $scope.feedback_flag = false;
                }
                else {
                    $scope.showViewTicketToast(response.data.invalid_task_list);
                }
            }).catch(function (response) {
                $scope.disable_create=0;
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Tasks!');
            })
                .finally(function () {

                });
        }
    };

    $scope.addQuickTask = function (task) {

        if ($scope.isMultiple == true) {
            if ($scope.selected_locations.length < 1) {
                toaster.pop('error', 'Task error', 'Please select locations!');
                return;
            }

            let request = {};
            request.task_id = task.id;
            request.location_groups = $scope.selected_locations.map(selected_location => {
                let temp = {};
                temp.location_id = selected_location.id;
                temp.room_name = selected_location.name;
                temp.room_id = selected_location.type == 'Room' ? selected_location.room_id : 0;
                temp.location_type = selected_location.type;

                return temp;
            });

            GuestService.getTaskInfoFromTask(request)
                .then(function (response) {
                    console.log(response);
                    var date = new Date();
                    var profile = AuthService.GetCredentials();

                    var data = response.data;
                    if (data.department == undefined) {
                        toaster.pop('error', 'Task error', 'There is no department');
                        return;
                    }

                    var quicktask_data = {};

                    quicktask_data.property_id = profile.property_id;
                    quicktask_data.dept_func = data.deptfunc.id;
                    quicktask_data.department_id = data.department.id;
                    quicktask_data.type = 2;
                    quicktask_data.priority = $scope.prioritylist[0].id;
                    quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.end_date_time = '0000-00-00 00:00:00';

                    quicktask_data.attendant = profile.id;
                    quicktask_data.task_list = task.id;
                    quicktask_data.max_time = data.taskgroup.max_time;
                    quicktask_data.quantity = 1;
                    quicktask_data.custom_message = '';
                    quicktask_data.status_id = 1;
                    quicktask_data.running = 1;
                    quicktask_data.feedback_flag = $scope.feedback_flag;
                    quicktask_data.requester_id = $scope.requester.id;
                    quicktask_data.requester_name = $scope.requester.wholename;
                    quicktask_data.requester_job_role = $scope.requester.job_role;

                    quicktask_data.requester_notify_flag = $scope.notify_flag ? 1 : 0;
                    quicktask_data.requester_email = $scope.requester.email;
                    quicktask_data.requester_mobile = $scope.requester.mobile;

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
                            dispatcher : temp.userlist[0].user_id,
                            location_id : location_group.location_id,
                            room: location_group.location_type == 'Room' ? location_group.room_id : 0,
                            guest_id: 0
                        };

                        return tempObj;
                    });

                    var tasklist = [];
                    tasklist.push(quicktask_data);

                    $rootScope.myPromise = GuestService.createTasklistNew(tasklist);
                    $rootScope.myPromise.then(function (res) {
                        console.log(res);
                        if (res.data.invalid_task_list.length == 0) {
                            $scope.main_task = {};
                            $scope.tasks = [];
                            $scope.quicktasks = [];
                            $scope.max_ticket_no = res.data.max_ticket_no;
                            $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                            $scope.$emit('onTicketChange', tasklist);

                            $scope.onLoadTasks();

                            toaster.pop('success', 'Create Task', "Successfully created!");
                        }
                        else {
                            $scope.showViewTicketToast(res.data.invalid_task_list);
                        }
                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
                    })
                        .finally(function () {
                            $scope.isCreatingTasks = false;
                        });
                });
        } else {
            if (!($scope.location.id > 0 && $scope.location.requester && $scope.location.requester.wholename.length > 0)) {
                toaster.pop('error', 'Task error', 'Please select room and guest');
                return;
            }

            GuestService.getTaskInfo(task.id, $scope.location.id)
                .then(function (response) {
                    console.log(response);
                    var date = new Date();

                    var profile = AuthService.GetCredentials();

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
                    quicktask_data.type = 2;
                    quicktask_data.priority = $scope.prioritylist[0].id;
                    quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                    quicktask_data.end_date_time = '0000-00-00 00:00:00';

                    quicktask_data.dispatcher = data.staff_list[0].user_id;
                    quicktask_data.attendant = profile.id;

                    if ($scope.location.type == 'Room')
                        quicktask_data.room = $scope.location.room_id;
                    else
                        quicktask_data.room = 0;

                    quicktask_data.task_list = task.id;
                    quicktask_data.max_time = data.taskgroup.max_time;
                    quicktask_data.quantity = 1;
                    quicktask_data.custom_message = '';
                    quicktask_data.status_id = 1;
                    quicktask_data.running = 1;
                    quicktask_data.guest_id = 0;
                    quicktask_data.feedback_flag = $scope.feedback_flag;
                    quicktask_data.location_id = $scope.location.id;
                    quicktask_data.requester_id = $scope.location.requester.id;
                    quicktask_data.requester_name = $scope.location.requester.wholename;
                    quicktask_data.requester_job_role = $scope.location.requester.job_role;

                    quicktask_data.requester_notify_flag = $scope.location.notify_flag ? 1 : 0;
                    quicktask_data.requester_email = $scope.location.requester.email;
                    quicktask_data.requester_mobile = $scope.location.requester.mobile;

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
                            $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                            $scope.$emit('onTicketChange', tasklist);

                            $scope.onLocationSelect($scope.location);

                            if (quicktask_data.dispatcher > 0)
                                toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                            else
                                toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                        }
                        else {
                            $scope.showViewTicketToast(response.data.invalid_task_list);
                        }
                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
                    })
                        .finally(function () {

                        });
                });
        }
    };

    $scope.addQuickTaskq = function (task) {
        
        if ($scope.isMultiple == true) {
            if ($scope.selected_locations.length < 1) {
                toaster.pop('error', 'Task error', 'Please select locations!');
                return;
            }
        } else {
            if (!($scope.location.id > 0 && $scope.location.requester && $scope.location.requester.wholename.length > 0)) {
                toaster.pop('error', 'Task error', 'Please select room and guest');
                return;
            }
        }

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/ticket/guestDmodal.html',
            controller: 'DeptModalCtrl',
            resolve: {
                task: function () {

                    return task;
                },
                selected_locations: function() {
                    return $scope.selected_locations;
                },
                isMultiple: function() {
                    return $scope.isMultiple;
                },
                location: function () {

                    return $scope.location;
                },
                feedback_flag: function () {

                    return $scope.feedback_flag;
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

    $scope.addMainTaskq = function (task) {
        console.log('here mt : ' + JSON.stringify(task));
        if ($scope.isMultiple == true) {
            if ($scope.selected_locations.length < 1) {
                toaster.pop('error', 'Task error', 'Please select locations!');
                return;
            }
        } else {
            if (!($scope.location.id > 0 && $scope.location.requester && $scope.location.requester.wholename.length > 0)) {
                toaster.pop('error', 'Task error', 'Please select room and guest');
                return;
            }
        }


        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/ticket/guestDmodal.html',
            controller: 'DeptModalCtrl',
            resolve: {
                task: function () {

                    return task;
                },
                selected_locations: function() {
                    return $scope.selected_locations;
                },
                isMultiple: function() {
                    return $scope.isMultiple;
                },
                location: function () {

                    return $scope.location;
                },
                feedback_flag: function () {

                    return $scope.feedback_flag;
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

    $scope.$on('onaddQuickDTaskq', function (event, args) {

        if ($scope.isMultiple == true) {
            $scope.onLoadTasks();
        } else {
            $scope.onLocationSelect(args);
        }
        //toaster.pop('error', 'Refreshed');
    });


    $scope.addSystemTask = function (task) {
        if (!($scope.location.id > 0 && $scope.location.requester && $scope.location.requester.wholename.length > 0)) {
            toaster.pop('error', 'Task error', 'Please select room and guest');
            return;
        }

        GuestService.getTaskInfo(task.id, $scope.location.id)
            .then(function (response) {
                console.log(response);
                var date = new Date();

                var profile = AuthService.GetCredentials();

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
                systemtask_data.type = 2;
                systemtask_data.priority = $scope.prioritylist[0].id;
                systemtask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                systemtask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                systemtask_data.end_date_time = '0000-00-00 00:00:00';

                systemtask_data.dispatcher = data.staff_list[0].user_id;
                systemtask_data.attendant = profile.id;
                systemtask_data.feedback_flag = $scope.feedback_flag;

                if ($scope.location.type == 'Room')
                    systemtask_data.room = $scope.location.room_id;
                else
                    systemtask_data.room = 0;

                systemtask_data.task_list = task.id;
                systemtask_data.max_time = data.taskgroup.max_time;
                systemtask_data.quantity = 1;
                systemtask_data.custom_message = '';
                systemtask_data.status_id = 1;

                systemtask_data.running = 1;
                systemtask_data.guest_id = 0;

                systemtask_data.location_id = $scope.location.id;
                systemtask_data.requester_id = $scope.location.requester.id;
                systemtask_data.requester_name = $scope.location.requester.wholename;
                systemtask_data.requester_job_role = $scope.location.requester.job_role;

                systemtask_data.requester_notify_flag = $scope.location.notify_flag ? 1 : 0;
                systemtask_data.requester_email = $scope.location.requester.email;
                systemtask_data.requester_mobile = $scope.location.requester.mobile;

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
                        $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                        $scope.$emit('onTicketChange', tasklist);

                        $scope.onLocationSelect($scope.location);

                        if (systemtask_data.dispatcher > 0)
                            toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                        else
                        if (task.schedule_flag == false)
                            toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                    }
                    else {
                        $scope.showViewTicketToast(response.data.invalid_task_list);
                    }
                }).catch(function (response) {
                    toaster.pop('error', 'Create Task', 'Failed to create Tasks');
                })
                    .finally(function () {

                    });
            });

    }

    $scope.addSystemTaskq = function (task) {
        if (!($scope.location.id > 0 && $scope.location.requester && $scope.location.requester.wholename.length > 0)) {
            toaster.pop('error', 'Task error', 'Please select room and guest');
            return;
        }
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/ticket/guestDmodal.html',
            controller: 'DeptSystemModalCtrl',
            resolve: {
                task: function () {

                    return task;
                },
                location: function () {

                    return $scope.location;
                },
                feedback_flag: function () {

                    return $scope.feedback_flag;
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
        GuestService.createMainTaskGroup(2, $scope.location.id, item)
            .then(function (response) {
                console.log(response);
                if (response.data.invalid_task_list.length == 0) {
                    $scope.main_task = {};
                    $scope.tasks = [];
                    $scope.quicktasks = [];
                    $scope.max_ticket_no = response.data.max_ticket_no;
                    $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                    //$scope.$emit('onTicketChange', tasklist);

                    $scope.onLocationSelect($scope.location);

                    // if (quicktask_data.dispatcher > 0)
                    //     toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                    // else
                    //     toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
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
        if (moment().add(1, 'days').format('YYYY-MM-DD') <= sel_date)
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

app.controller('DeptModalCtrl', function ($scope, $rootScope, $http, AuthService, GuestService, $interval, toaster, $timeout, feedback_flag, $uibModalInstance, task, guest, location, selected_locations, isMultiple, prioritylist) {

    $scope.quantity = 1;
    $scope.custom_message = '';
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    if (isMultiple == true) {
        let request = {};
        request.task_id = task.id;
        request.location_groups = $scope.selected_locations.map(selected_location => {
            let temp = {};
            temp.location_id = selected_location.id;
            temp.room_name = selected_location.name;
            temp.room_id = selected_location.type == 'Room' ? selected_location.room_id : 0;
            temp.location_type = selected_location.type;

            return temp;
        });

        GuestService.getTaskInfoFromTask(request)
            .then(function (response) {
                console.log(response);
                var date = new Date();

                var profile = AuthService.GetCredentials();

                var data = response.data;
                if (data.department == undefined) {
                    toaster.pop('error', 'Task error', 'There is no department');
                    return;
                }

                var quicktask_data = {};

                quicktask_data.property_id = profile.property_id;
                quicktask_data.dept_func = data.deptfunc.id;
                quicktask_data.department_id = data.department.id;
                quicktask_data.type = 2;
                quicktask_data.priority = prioritylist[0].id;
                quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.end_date_time = '0000-00-00 00:00:00';
                quicktask_data.feedback_flag = feedback_flag;
                quicktask_data.attendant = profile.id;
                quicktask_data.task_list = task.id;
                quicktask_data.max_time = data.taskgroup.max_time;

                quicktask_data.status_id = 1;
                quicktask_data.running = 1;

                quicktask_data.requester_id = $scope.requester.id;
                quicktask_data.requester_name = $scope.requester.wholename;
                quicktask_data.requester_job_role = $scope.requester.job_role;

                quicktask_data.requester_notify_flag = $scope.notify_flag ? 1 : 0;
                quicktask_data.requester_email = $scope.requester.email;
                quicktask_data.requester_mobile = $scope.requester.mobile;

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
                        room: location_group.location_type == 'Room' ? location_group.room_id : 0,
                        guest_id: 0
                    };

                    return tempObj;
                });

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
                            $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                            $rootScope.$broadcast('onTicketChange', tasklist);

                            $rootScope.$broadcast('onaddQuickDTaskq', selected_locations);

                            if (quicktask_data.dispatcher > 0)
                                toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                            else
                                toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                        }
                        else {
                            $scope.showViewTicketToast(response.data.invalid_task_list);
                        }
                        $uibModalInstance.dismiss();
                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
                    })
                        .finally(function () {
                        });
                };
            });
    } else {
        GuestService.getTaskInfo(task.id, location.id)
            .then(function (response) {
                console.log(response);
                var date = new Date();

                var profile = AuthService.GetCredentials();

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
                quicktask_data.type = 2;
                quicktask_data.priority = prioritylist[0].id;
                quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                quicktask_data.end_date_time = '0000-00-00 00:00:00';

                quicktask_data.dispatcher = data.staff_list[0].user_id;
                quicktask_data.feedback_flag = feedback_flag;
                quicktask_data.attendant = profile.id;

                if (location.type == 'Room')
                    quicktask_data.room = location.room_id;
                else
                    quicktask_data.room = 0;

                quicktask_data.task_list = task.id;
                quicktask_data.max_time = data.taskgroup.max_time;

                quicktask_data.status_id = 1;
                quicktask_data.running = 1;
                quicktask_data.guest_id = 0;

                quicktask_data.location_id = location.id;
                quicktask_data.requester_id = location.requester.id;
                quicktask_data.requester_name = location.requester.wholename;
                quicktask_data.requester_job_role = location.requester.job_role;

                quicktask_data.requester_notify_flag = location.notify_flag ? 1 : 0;
                quicktask_data.requester_email = location.requester.email;
                quicktask_data.requester_mobile = location.requester.mobile;



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
                            $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                            $rootScope.$broadcast('onTicketChange', tasklist);

                            $rootScope.$broadcast('onaddQuickDTaskq', location);

                            if (quicktask_data.dispatcher > 0)
                                toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                            else
                                toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                        }
                        else {
                            $scope.showViewTicketToast(response.data.invalid_task_list);
                        }
                        $uibModalInstance.dismiss();
                    }).catch(function (response) {
                        toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
                    })
                        .finally(function () {

                        });
                };
            });
    }
});

app.controller('DeptSystemModalCtrl', function ($scope, $rootScope, feedback_flag, $http, AuthService, GuestService, $interval, toaster, $timeout, $uibModalInstance, task, guest, location, prioritylist) {
    $scope.quantity = 1;
    $scope.custom_message = '';
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    GuestService.getTaskInfo(task.id, location.id)
        .then(function (response) {
            console.log(response);
            var date = new Date();

            var profile = AuthService.GetCredentials();

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
            systemtask_data.type = 2;
            systemtask_data.priority = prioritylist[0].id;
            systemtask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
            systemtask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
            systemtask_data.end_date_time = '0000-00-00 00:00:00';
            systemtask_data.feedback_flag = feedback_flag;
            systemtask_data.dispatcher = data.staff_list[0].user_id;
            systemtask_data.attendant = profile.id;

            if (location.type == 'Room')
                systemtask_data.room = location.room_id;
            else
                systemtask_data.room = 0;

            systemtask_data.task_list = task.id;
            systemtask_data.max_time = data.taskgroup.max_time;
            systemtask_data.status_id = 1;
            systemtask_data.running = 1;
            systemtask_data.guest_id = 0;

            systemtask_data.location_id = location.id;
            systemtask_data.requester_id = location.requester.id;
            systemtask_data.requester_name = location.requester.wholename;
            systemtask_data.requester_job_role = location.requester.job_role;

            systemtask_data.requester_notify_flag = location.notify_flag ? 1 : 0;
            systemtask_data.requester_email = location.requester.email;
            systemtask_data.requester_mobile = location.requester.mobile;

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
                        $scope.ticket_id = sprintf('D%05d', $scope.max_ticket_no + 1);

                        $rootScope.$broadcast('onTicketChange', tasklist);

                        $rootScope.$broadcast('onaddQuickDTaskq', location);

                        if (systemtask_data.dispatcher > 0)
                            toaster.pop('success', 'Create Task', task.task + ' is assigned to Staff ' + staff_name);
                        else
                        if (task.schedule_flag == false)
                            toaster.pop('error', 'Create Task', task.task + ' will be escalated.');
                    }
                    else {
                        $scope.showViewTicketToast = function(task_list) {
                            task_list.forEach(ele => {
                                toaster.pop({
                                        type: 'error',
                                        title: 'Create Task',
                                        body: 'guide-toast-message',
                                        bodyOutputType: 'directive',
                                        directiveData: {
                                            name: ele.id,
                                            message: ele.message,
                                        },
                                        timeout: 0,
                                });
                                // toaster.pop('error', 'Create Task', ele.message);
                            });
                        }

                        $scope.showViewTicketToast(response.data.invalid_task_list);
                        
                    }
                    $uibModalInstance.dismiss();
                }).catch(function (response) {
                    toaster.pop('error', 'Create Task', 'Failed to create Tasks!');
                })
                    .finally(function () {

                    });
            };
        });

});



app.directive('sgldclick', ['$parse', function ($parse) {
    return {
        restrict: 'A',
        link: function (scope, element, attr) {
            var fn = $parse(attr['sgldclick']);
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


