app.controller('ManagedtaskEditController', function ($scope, $rootScope, $http, $interval, toaster, GuestService, AuthService) {
    $scope.tasks = [];
    $scope.guest = {};

    var MESSAGE_TITLE = 'Change Guest Task';
    var SELECT_ACTION = '--Select Action--';
    var COMPLETE_ACTION = 'Complete';
    var CANCEL_ACTION = 'Cancel';

    $scope.selectedTable = "history";

    $scope.$on('$destroy', function() {
        if( $scope.timer )
        {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });
    //$scope.action_disable_flag = false;

    $scope.init = function(task) {
        $scope.ticket_id = sprintf('M%05d', task.id);

        if( task.id == 0 )
            return;

        if( task.type != 4 )
            return;

        $scope.task = angular.copy(task);

        var start_time = new Date(Date.parse($scope.task.start_date_time));
        $scope.task.date = start_time;
        $scope.task.time = start_time;
        $scope.task.notify_flag = task.requester_notify_flag == 1 ? true : false;

        $scope.task.group_flag = task.is_group == 'Y' ? true : false;

        if( task.subtype == 1 || task.subtype == 2 ) {
            $scope.task.assigne_name = task.wholename;
            $scope.remain_time_style = 0 ;
            $scope.timer = $interval(function() {
                if($scope.task.status_id == 1 && $scope.task.running == 0){
                    if($scope.task.evt_start_time != null&& $scope.task.evt_end_time != null) {
                        $scope.remain_time = moment($scope.task.evt_end_time, "YYYY-MM-DD HH:mm:ss") - moment($scope.task.evt_start_time, "YYYY-MM-DD HH:mm:ss");
                    }
                }else if($scope.task.status_id == 1 && $scope.task.running == 1 && $scope.task.evt_start_time != null) {
                    var max_time =  moment($scope.task.evt_end_time, "YYYY-MM-DD HH:mm:ss")-moment($scope.task.evt_start_time, "YYYY-MM-DD HH:mm:ss") ;
                    $scope.remain_time = max_time - moment.utc(moment().diff(moment($scope.task.evt_start_time, "YYYY-MM-DD HH:mm:ss")));
                }else if($scope.task.status_id == 4||$scope.task.status_id == 0){
                    var remain_time = 0;
                    remain_time = moment($scope.task.end_date_time, "YYYY-MM-DD HH:mm:ss")-moment($scope.task.start_date_time, "YYYY-MM-DD HH:mm:ss") ;
                    remain_time = $scope.task.max_time*1000 -remain_time;
                    $scope.remain_time = remain_time;
                } else {
                    $scope.remain_time = $scope.task.max_time * 1000 - moment.utc(moment().diff(moment($scope.task.start_date_time, "YYYY-MM-DD HH:mm:ss")));
                }
                if( $scope.remain_time < 0 )
                    $scope.remain_time = 0;
                $scope.remain_time_style = GuestService.getTicketStatusStyle($scope.task);
            }, 1000);
        }
        else if( task.subtype == 6 )
        {
            if( task.is_group == 'Y' )
                $scope.task.assigne_name = task.manage_user_group;
            else
                $scope.task.assigne_name = task.manage_user_name;
        }

        $scope.backuptask = angular.copy(task);

        $scope.initActionList($scope.task);

        getGuestMessageList();

        /*var profile = AuthService.GetCredentials();
        if( profile.dept_id != task.department_id && !AuthService.isValidModule('dept.gs.editdept')){
            $scope.action_disable_flag = true;
        }*/
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

        var profile = AuthService.GetCredentials();
        data.property_id = profile.property_id;

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

    $scope.notifylist = [];
       
    var getNotificationHistory = function() {
        $rootScope.myPromise = GuestService.getNotificationHistoryList($scope.task.id, 1, 1000000, 'id', 'asc' )
            .then(function(response) {
                $scope.notifylist = response.data.datalist;              
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    };

    $scope.messageList = [];
    $scope.onSelectTable = function (selectedTable) {
        $scope.selectedTable = selectedTable;
    };

    var getGuestMessageList = function() {

        var request = {};
        request.task_id = $scope.task.id;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/messagelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.messageList = response.data.messagelist;

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
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