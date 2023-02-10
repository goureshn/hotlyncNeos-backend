'use strict';

app.controller('RequestController', function($rootScope, $scope, $state, $cookies, $http, $interval, $stateParams, $window, $timeout, $filter,  toaster, AuthService, GuestService, socket) {
    var MESSAGE_TITLE = 'Request Page';
    $scope.condition = "first";
    $scope.basket_count = 0;

    $scope.tasks = [];
    $scope.guest = {};
    $scope.main_task = {};
    $scope.quicktasks = [];
    $scope.selected_room = {};
    $scope.datetime = {};
    $scope.datetime.start_date = new Date();
    $scope.datetime.start_time = '';
    $scope.max_ticket_no = 0;
    $scope.history = {};
    $scope.gs = GuestService;
    $scope.historylist = {};
    $scope.basketlist = {};
    $scope.basket = {};


    $scope.$watch('datetime.start_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.datetime.start_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if( $view == 'day' )
        {
            var activeDate = moment().subtract('days', 1);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        else if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 0);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.request = function(item) {
        if(item == 'new') {
            var item = {};
            var profile = AuthService.GetCredentials();
            item.id = profile.room_id;
            item.room = profile.room;
            item.property_id = profile.property_id;

            $scope.onRoomSelect(item, null, null);
            $scope.condition = "new";
            $scope.migration();
        }
        if(item == 'track') {
            $scope.condition = "track";
            $scope.migration();
        }
        $scope.getGuestHistory();
    }

    $scope.prioritylist = [];
    GuestService.getPriorityList()
        .then(function(response) {
            $scope.prioritylist = response.data;
        });

    $scope.onRoomSelect = function ($item, $model, $label) {
        console.log($item);
        $scope.selected_room = angular.copy($item);

        GuestService.getGuestName($item)
            .then(function(response){
                if(response.data.checkout_flag == 'checkout') {
                    $scope.showRequest(2);
                    $timeout(function(){
                        $rootScope.$broadcast('room_selected', $item);
                    }, 1000);

                    $scope.$emit('onTicketCreateFinished', 1);      // Close Guest Request Tab
                    return;
                }
                if( response.data )
                {
                    $scope.guest = response.data;
                }
                else
                    $scope.guest.guest_name = 'Admin task';

                var date = new Date();
                $scope.guest.request_time = moment().format("HH:mm:ss");

                $scope.addMainTask();
                $scope.alert = {};

            });

        GuestService.getQuickTaskList(1, $item.property_id)
            .then(function(response){
                $scope.quicktasks = response.data;
            });

        GuestService.getLocationGroupFromRoom($item.id)
            .then(function(response){
                $scope.selected_room.location_group = response.data;
            });


    };

    $scope.addMainTask = function() {
        var date = new Date();

        var new_task = {
            ticket_no : $scope.max_ticket_no + 1,
            task_name : "",
            qunatity : 1,
            department : "",
            department_edit_flag: false,
            dept_func : "",
            dept_staff : "",
            task_group_id : 0,
            device : "",
            //priority_id : $scope.prioritylist[0].id,
            max_duration : "",
            custom_message : "",
            start_date_time : moment().format("YYYY-MM-DD HH:mm:ss"),
            date : moment().format("YYYY-MM-DD HH:mm:ss"),
            schedule_flag: false,
            repeat_end_date : moment($scope.guest.departure).toDate(),
            // repeat_end_date : new Date(),
            repeat_flag: false,
            until_checkout_flag : false
        }

        $scope.main_task = new_task;
    }

    $scope.addMainTask();

    $scope.getTaskList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return GuestService.getTaskListForGuest(val, property_id, 1)
            .then(function(response){
                if(response.data.length == 0) $scope.taskbtnview = true;
                return response.data.filter(function(item, index, attr){
                    return index < 10;
                });
            });
    };

    $scope.onMainTaskSelect = function (task, $item, $model, $label) {
        console.log($item);

        if( !$scope.selected_room.location_group )
        {
            toaster.pop('error', MESSAGE_TITLE, 'There is no location group' );
            return;
        }

        $scope.addMainTask();

        if( checkDuplicatedTask($item) )
            return;

        $scope.main_task.schedule_flag = false;
        $scope.main_task.quantity = 1;
        $scope.main_task.tasklist = angular.copy($item);

        GuestService.getTaskInfo($item.id, $scope.selected_room.location_group.id)
            .then(function(response){
                console.log(response);

                showSelectedDepartmentInfo(response.data);
            });
    };

    function checkDuplicatedTask($item) {
        var exist = false;

        for(var i = 0; i < $scope.tasks.length; i++ )
        {
            if( $item.id == $scope.tasks[i].tasklist.id )
            {
                exist = true;
                break;
            }
        }

        if( exist == true )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Task is already added, Please increase quantity instead' );
            return true;
        }

        return false;
    }


    function showSelectedDepartmentInfo(data) {
        var task = $scope.main_task;

        if( data.department == undefined )
        {
            toaster.pop('error', 'Task error', 'There is no department');
            return;
        }

        task.department = data.department.department;
        task.department_id = data.department.id;
        task.dept_func = data.deptfunc;
        task.userlist = data.staff_list;
        task.max_duration = data.taskgroup.max_time;
        task.priority_id = $scope.prioritylist[0].id;

        if( task.userlist.length < 1 )
        {
           // toaster.pop('error', 'Task error', 'No Staff is on shift');
            task.userlist.push({id: 0, user_id: 0, wholename: 'No Staff is on shift'});
        }
        task.dispatcher = task.userlist[0];
        task.username = task.dispatcher.wholename;
        task.device = task.dispatcher.mobile;
    }

    $scope.addBasket = function () {
        var tasklist = [];
        var basket_list = $cookies.basket;
        if(basket_list != null  ) tasklist = basket_list;

        if( !($scope.selected_room.id > 0 && $scope.guest.guest_name) )
        {
            toaster.pop('error', 'Task error', 'Please select room and guest');
            return;
        }

        $scope.addTask();

        for( var i = 0; i < $scope.tasks.length; i++)
        {
            var task = $scope.tasks[i];
            var data = getTaskData(task);
            if( !data )
                continue;
            for(var j = 0 ; j < tasklist.length ; j++) {
                var original_data =  tasklist[j];
                if(original_data.task_list == data.task_list)
                    tasklist.splice(j, 1);
            }
            tasklist.push(data);
        }

        if( tasklist.length < 1 )
        {
            toaster.pop('error', 'Task error', 'Please add a task at least');
            return;
        }
        // Setting a cookie
        $cookies.basket = tasklist;
        $scope.basket_count  = $cookies.basket.length;
    }

    $scope.changeQuantity = function(index, quantity) {
        if (quantity > 9 || quantity < 0) {
            var str = quantity + '';
            str = str.substr(0, 1);
            $scope.basketlist[index].quantity = parseInt(str);
        }else {
            $scope.basketlist[index].quantity = quantity;
        }
    }

    $scope.createRequest = function () {
        var tasklist =  $scope.basketlist ;
        for(var i = 0 ;i < tasklist.length ; i ++) {
            delete tasklist[i]['task_list_name'];
            if ($scope.basket.schedule == 0) {
                tasklist[i].start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                tasklist[i].status_id = 1;
                tasklist[i].running = 1;
                //tasklist[i].dispatcher = task.dispatcher.user_id;
                tasklist[i].dispatcher = 0;
            }
            else {
               // var date = '';
               // if ($scope.basket.date instanceof Date)
                  var  date = moment($scope.datetime.start_time).format('YYYY-MM-DD HH:mm:ss');
                //else
                //    date = tasklist[i].date;
                tasklist[i].start_date_time = date;
                tasklist[i].status_id = 5; // schedule state
                tasklist[i].running = 0;
                tasklist[i].dispatcher = 0;
            }
            tasklist[i].custom_message = $scope.basket.comment;
            delete tasklist[i]['task_list_name'];
        }
        // if( !($scope.selected_room.id > 0 && $scope.guest.guest_name) )
        // {
        //     toaster.pop('error', 'Task error', 'Please select room and guest');
        //     return;
        // }
        //
        //
        // $scope.addTask();
        //
        // for( var i = 0; i < $scope.tasks.length; i++)
        // {
        //     var task = $scope.tasks[i];
        //     var data = getTaskData(task);
        //     if( !data )
        //         continue;
        //
        //     tasklist.push(data);
        // }
        //
        // if( tasklist.length < 1 )
        // {
        //     toaster.pop('error', 'Task error', 'Please add a task at least');
        //     return;
        // }

        $rootScope.myPromise = GuestService.createTaskList(tasklist);
        $rootScope.myPromise.then(function(response) {
            console.log(response);

            if( response.data.count > 0) {
                toaster.pop('success', MESSAGE_TITLE, 'Tasks are created successfully');


                $scope.main_task = {};
                $scope.tasks = [];
                $scope.quicktasks = [];
                $scope.max_ticket_no = response.data.max_ticket_no;

                $scope.selected_room = {};
                $scope.room_num = '';
                $scope.guest = {};
                //$scope.onRoomSelect($scope.selected_room);
                $cookies.basket = null; //init cookie
                $scope.basket_count = 0;
                $scope.condition = 'first';
                $scope.basket = {};
                $scope.getGuestHistory();//update count;

            }
            else {
                toaster.pop('error', 'Create Task', $scope.tasks[0].tasklist.task + ' is already opened for Room ' + $scope.selected_room.room);
            }


        }).catch(function(response) {
                toaster.pop('error', 'Create Task', 'Tasks have been failed to create');
            })
            .finally(function() {

            });
    }

    $scope.addTask = function(message_flag) {
        var task = angular.copy($scope.main_task);
        if( isValidTask(task, message_flag) == false )
            return;

        $scope.tasks.push(task);

        // init main task
        $scope.addMainTask();
    }

    function getTaskData(task) {
        var profile = AuthService.GetCredentials();

        var data = {};

        data.property_id = profile.property_id;

        if( !task.tasklist )
        {
            return;
        }

        if( !task.dept_func || !task.dispatcher )
        {
            return;
        }
        data.dept_func = task.dept_func.id;
        data.department_id = task.department_id;
        data.type = 1;
        data.priority = task.priority_id;

        var date = new Date();
        var date1 = angular.copy(date);

        if( task.schedule_flag == false ) {
            data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
            data.status_id = 1;
            data.running = 1;
            data.dispatcher = task.dispatcher.user_id;
        }
        else {
            var date = '';
            if( task.date instanceof Date )
                date = task.date.format('yyyy-MM-dd');
            else
                date = task.date;

            data.start_date_time = date;
            data.status_id = 5; // schedule state
            data.running = 0;
            data.dispatcher = 0;
        }

        data.repeat_flag = task.repeat_flag;
        data.until_checkout_flag = task.until_checkout_flag;
        if(task.until_checkout_flag == true) {
            data.repeat_end_date = date1.format("yyyy-MM-dd");
        }else {
            var convert_date = $filter('date')(task.repeat_end_date, "yyyy-MM-dd");
            if( task.repeat_end_date instanceof Date )
                //data.repeat_end_date = task.repeat_end_date.format("yyyy-MM-dd");
                data.repeat_end_date = convert_date;
            else
                data.repeat_end_date = task.repeat_end_date;
        }

        data.end_date_time = '0000-00-00 00:00:00';

        //data.attendant = profile.id;
        data.attendant = 0; // in guest page, attendant is 0.
        //data.room = $scope.guest.room_id;
        data.room = $scope.selected_room.id;
        data.task_list = task.tasklist.id;

        if( task.dispatcher.user_id > 0 )
            data.max_time = task.max_duration;
        else
            data.max_time = 0;

        data.quantity = task.quantity;
        data.custom_message = task.custom_message;
        data.guest_id = $scope.guest.guest_id;
        data.location_id = $scope.selected_room.location_group.id;

        return data;
    }

    function isValidTask(task, message_flag) {
        var data = {};

        if( !task.tasklist )
        {
            if( message_flag == true )
                toaster.pop('error', 'Error', 'Please select task list');
            return false;
        }

        if( !task.dept_func || !task.dispatcher )
        {
            if( message_flag == true )
                toaster.pop('error', 'Validate Error', 'Please input all fields');
            return false;
        }

        return true;
    }

    //track page
    $scope.getGuestHistory = function() {
        $scope.history.limit = 0;
        var profile = AuthService.GetCredentials();
        GuestService.getPreviousHistoryList(profile.guest_id)
            .then(function (response) {
                $scope.historylist = response.data.datalist;
            }).catch(function (response) {

        });
    }

    //view history
    $scope.history_detail = {};
    $scope.historyView = function(row) {
        $scope.condition = 'track_detail';
        $scope.migration();
        var task_id = row.id;
        $scope.history_detail.task_name = row.task_name;
        $scope.history_detail.start_date_time = row.start_date_time;
        $scope.history_detail.status = $scope.gs.getStatus(row);
        $scope.history_detail.quantity = row.quantity;
        $scope.history_detail.custom_message = row.custom_message;
    }

    $scope.schedule_options = [
        {'id':0, 'name':'As soon as possible'},
        {'id':1, 'name':'I want this on a future time'}
    ];
    $scope.basket.schedule = $scope.schedule_options[0].id ;

    $scope.onShowBasketList = function() {
        var basket_list = $cookies.basket;
        var url = '/guest/gettaskListfromguest';
        var request = {};
        request.tasklist = basket_list;
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.condition = 'basket';
            $scope.basketlist = response.data;
            $scope.migration();
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {

            });
    }

    //go to
    $scope.migration = function() {
        $rootScope.$broadcast('current_page', 'request_'+$scope.condition);
    }

    $scope.$on('before_page_request', function (val) {
        switch(val) {
            case 'request_new' :
                $scope.condition = 'first';
                break;
            case 'request_track' :
                $scope.condition = 'first';
                break;
            case 'request_basket' :
                $scope.condition = 'new';
                break;
            case 'request_track_detail' :
                $scope.condition = 'track';
                break;
        }
        $scope.backPage();
    });

    $scope.backPage = function(){
        var cond = $scope.condition;
        switch(cond) {
            case 'first' :
                $state.go('app.first');
                break;
            case 'new' :
                $scope.condition = 'first';
                break;
            case 'basket' :
                $scope.condition = 'new';
                break;
            case 'track' :
                $scope.condition = 'first';
                break;
            case 'track_detail' :
                $scope.condition = 'track';
                break;
        }
    }

    $scope.deleteBasket = function (item) {
        var task  = $scope.basketlist ;
        for(var i = 0 ; i < task.length; i ++) {
            if(task[i].task_list == item.task_list)
                $scope.basketlist.splice(i, 1);
        }
        $scope.main_task = {};
        $scope.tasks = [];
        $scope.quicktasks = [];

        $scope.basket = {};
        $cookies.basket = null;
        $cookies.basket =  $scope.basketlist;
        $scope.basket_count  = $cookies.basket.length;

    }

    $scope.isNumberKey = function (){
     if ($scope.main_task.quantity > 9 || $scope.main_task.quantity < 0) {
                var str = $scope.main_task.quantity + '';
                str = str.substr(0, 1);
                $scope.main_task.quantity = parseInt(str);
     }
    }
});


