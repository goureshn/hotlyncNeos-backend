app.controller('DepartmentrequestEditController', function ($scope, $rootScope, $http, $interval, toaster, $uibModal, GuestService, AuthService, $filter) {
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
    var ASSIGN = 'Assign';
    var SCHEDULED_ACTION = 'Scheduled';
    var REASSIGN = 'Reassign';
    var CLOSE_ACTION = 'Close';
    var COMMENT_ACTION = 'Comment';

    $scope.selectedTable = "history";

    $scope.$on('$destroy', function () {
        if ($scope.timer) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.StatusCss = function (action) {
        if (action == COMPLETE_ACTION)    // complete
            return 'bg-status-completed';
        if (action == OPEN_ACTION)    // Open
            return 'bg-status-assigned';
        if (action == 'Escalated')    // Escalated
            return 'bg-urgency-medium';
        if (action == CANCEL_ACTION)    // cancel
            return 'bg-status-cancelled';
        if (action == HOLD_ACTION)    // hold
            return 'btn-danger';
        if (action == EXTEND_ACTION)    // extend
            return 'bg-status-assigned';
        if (action == RESUME_ACTION)    // resume
            return 'bg-status-assigned';
        if (action == SCHEDULED_ACTION)    // Scheduled
            return 'bg-status-onhold';
        if (action == CLOSE_ACTION)    // Closed
            return 'bg-status-completed';
        if (action == COMMENT_ACTION)    // complete
            return 'bg-status-completed';
    }

    $scope.action_disable_flag = false;
    $scope.datetime = {};

    $scope.init = function (task) {
        $scope.ticket_id = sprintf('D%05d', task.id);

        if (task.id == 0)
            return;

        if (task.type != 2)
            return;

        $scope.task = angular.copy(task);
        if (task.feedback_flag == 0)
            $scope.task.feedback = false;
        else
            $scope.task.feedback = true;

        if (task.feedback_type == 1)
            $scope.task.feedback_choice = 'Positive';
        else if (task.feedback_type == 2)
            $scope.task.feedback_choice = 'Negative';

        var start_time = new Date(Date.parse($scope.task.start_date_time));
        $scope.task.date = start_time;
        $scope.task.time = start_time;
        $scope.task.username = '';

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
        $scope.task.pictureflag = false;
        if(task.picture_path != '' && (task.picture_path != '[]' && task.picture_path != null)){
            var path1 = JSON.parse(task.picture_path);
            $scope.task.picturepath = path1;
            $scope.task.pictureflag = true;
        }else{
            $scope.task.picturepath = '';
        }

        $scope.task.repeat_flag = task.repeat_flag == 1;
        $scope.task.repeat_end_date = new Date(task.repeat_end_date);
        $scope.task.until_checkout_flag = task.until_checkout_flag == 1;
        $scope.task.schedule_flag = true;

        getGuestMessageList(task.id);

        $scope.initActionList($scope.task);

        getStaffList($scope.task);

        getNotificationHistory();
        $scope.remain_time_style = 0;
        $scope.timer = $interval(function () {
            $scope.remain_time = GuestService.getRemainTime($scope.task);
            $scope.remain_time_style = GuestService.getTicketStatusStyle($scope.task);
        }, 1000);
        var profile = AuthService.GetCredentials();
        if( profile.dept_id != task.department_id && AuthService.isValidModule('dept.gs.editdept')){
            $scope.action_disable_flag = true;
        }
    }

    var getStaffList = function (task) {
        var item = task.task_list;
        if (task.dispatcher < 1) {
            GuestService.getTaskInfoWithAssign(item, task.location_id)
                .then(function (response) {
                    $scope.task.userlist = response.data.staff_list;
                });

        }
        else if (task.reassigned_flag == 0) {
            GuestService.getTaskInfo(item, task.location_id)
                .then(function (response) {
                    console.log(response);
                    $scope.task.userlist = response.data.staff_list;
                });
        }
        else
        {
            GuestService.getTaskInfoWithReassign(item, task.location_id)
                .then(function (response) {
                    console.log(response);
                    $scope.task.userlist = response.data.staff_list;
                });
        }
           
    }

    $scope.$on('guest_ticket_event', function (event, args) {
        var ticket_id = args.content.notification_id;
        if (ticket_id != $scope.task.id)
            return;

        $http.get('/frontend/guestservice/ticketdetail?id=' + ticket_id)
            .then(function (response) {
                $scope.init(response.data);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {

            });
    });

    $scope.messageList = [];
    $scope.onSelectTable = function (selectedTable) {
        $scope.selectedTable = selectedTable;
    };

    var getGuestMessageList = function (task_id) {

        var request = {};
        request.task_id = task_id;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/messagelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.messageList = response.data.messagelist;

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.initActionList = function (task) {
        $scope.actions = [];
        if ((task.dispatcher < 1 && ((task.status_id != 3) && (task.status_id != 4))))
            $scope.actions = [ASSIGN];
        else {
        switch (task.status_id) {
            case 1: // Open
                if (task.running == 1)     // running
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMMENT_ACTION,
                        COMPLETE_ACTION,
                        CANCEL_ACTION,
                        HOLD_ACTION,
                        EXTEND_ACTION,
                    ];
                }
                else {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMMENT_ACTION,
                        RESUME_ACTION,
                        CANCEL_ACTION,
                    ];
                }

                if (AuthService.isValidModule('app.guestservice.reassign'))
                    $scope.actions.push(REASSIGN);

                break;
            case 2: // Escalated
                if (task.running == 1)     // running
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMMENT_ACTION,
                        COMPLETE_ACTION,
                        CANCEL_ACTION,
                        HOLD_ACTION,
                    ];
                }
                else {
                    $scope.actions = [
                        SELECT_ACTION,
                        COMMENT_ACTION,
                        RESUME_ACTION,
                        CANCEL_ACTION,
                    ];
                }

                if (AuthService.isValidModule('app.guestservice.reassign'))
                    $scope.actions.push(REASSIGN);

                break;
            case 3: // Timeout
                if (task.closed_flag == 0)     // not closed
                {
                    $scope.actions = [
                        SELECT_ACTION,
                        CLOSE_ACTION,
                        COMMENT_ACTION,
                    ];
                }

                break;          
        }
    }

        if ($scope.actions.length > 0)
            task.action = $scope.actions[0];
    }

    var paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'asc',
        field: 'id',
    };

    $scope.onChangeAction = function (action) {
        console.log(action);
        $scope.task.action = angular.copy(action);

        if (action == RESUME_ACTION || action == CLOSE_ACTION)
            $scope.changeTask();
    }

    $scope.onStaffSelect = function ($item, $model, $label) {
        console.log($item);
        $scope.task.reassign = $item;
    };

    $scope.onReassign = function () {
        var data = {};

        var profile = AuthService.GetCredentials();

        if (!$scope.task.reassign || !($scope.task.reassign.id > 0)) {
            toaster.pop('info', MESSAGE_TITLE, 'Please select assignee');
            return;
        }

        data.assign_id = $scope.task.reassign.id;
        data.property_id = profile.property_id;
        data.start_date_time = moment().format('YYYY-MM-DD HH:mm:ss');
        data.status_id = 1; // Open State
        data.running = 1;
        data.log_type = $scope.task.action + 'ed To';
        data.max_time = $scope.task.max_time;

        data.task_id = $scope.task.id;
        data.comment = $scope.task.reason;

        data.original_status_id = $scope.task.status_id;


        $rootScope.myPromise = GuestService.changeTaskState(data)
            .then(function (response) {
                if (response.data.code && response.data.code == 200) {
                    $scope.$emit('onTicketChange', $scope.task);
                    toaster.pop('success', MESSAGE_TITLE, 'Task is changed successfully');
                    $scope.task = response.data.ticket;
                    $scope.init($scope.task);
                }
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Task is fail to change');
            })
            .finally(function () {

            });
    }

    $scope.onForward = function () {
        var request = $scope.task;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/forward',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Ticket is forwarded to duty manager');
            $scope.task.forward_flag = 1;
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to forward ticket.');
        })
            .finally(function () {

            });
    }
    $scope.comment_edit_flag = false;
    var comment_text = '';

    $scope.editComment = function(task) {     
    
        comment_text = task.custom_message + '';
        $scope.comment_edit_flag = true;
    }

    $scope.cancelComment = function(task) {
     
        task.custom_message = comment_text + '';;
        $scope.comment_edit_flag = false;
    }

    $scope.changeComment = function (task) {
        var request = $scope.task;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/custommessage',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.comment_edit_flag = false;
            toaster.pop('success', MESSAGE_TITLE, 'Comment has been Updated Successfully.');
            $scope.task.forward_flag = 1;
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Comment.');
        })
            .finally(function () {

            });
    }

    $scope.changeFeedback = function () {
        var request = $scope.task;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/guestfeedback',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Guest Feedback has been Updated Succesfully.');
            $scope.task.forward_flag = 1;
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Guest Feedback.');
        })
            .finally(function () {

            });
    }

    $scope.onFeedback = function (task) {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/ticket/feedback.html',
            controller: 'FeedBackDModalCtrl',
            resolve: {
                task: function () {

                    return task;
                },
                feedback_flag: function () {

                    return task.feedback_flag;
                }
            }

        });
    }

    $scope.feedbackFlag = function () {
        // window.alert($scope.task.feedback_flag);
        // if ($scope.feedback_flag == false)
        //     $scope.feedback_flag = true;
        // else
        //     $scope.feedback_flag = false;
        // $scope.feedback = 1;

        var data = {};

        // if ($scope.feedback == 0)
        //     return;

        if ($scope.task.feedback == true)
            $scope.task.feedback_flag = 1;
        else
            $scope.task.feedback_flag = 0;


        var profile = AuthService.GetCredentials();
        data.property_id = profile.property_id;

        data.start_date_time = $scope.start_date_time;
        data.task_id = $scope.task.id;
        data.user_id = profile.id;



        data.feedback_flag = $scope.task.feedback_flag;

        $rootScope.myPromise = GuestService.FeedbackState(data)
            .then(function (response) {
                if (response.data.code && response.data.code == 200) {
                    // $scope.$emit('onTicketChange', $scope.task);
                    toaster.pop('success', MESSAGE_TITLE, 'FeedBack has been changed successfully');
                    // $scope.task = response.data.ticket;
                    // $scope.init($scope.task);
                }
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Feedback failed to change');
            })
            .finally(function () {

            });
    }

    $scope.cancelrepeatFlag = function () {
      
        var data = {};


        if ($scope.task.cancel_repeat_flag == true)
            $scope.task.cancel_repeat  = 1;
        else
            $scope.task.cancel_repeat  = 0;


        var profile = AuthService.GetCredentials();
        data.property_id = profile.property_id;

        data.start_date_time = $scope.start_date_time;
        data.task_id = $scope.task.id;
        data.user_id = profile.id;



      
        $rootScope.myPromise = GuestService.RepeatState(data)
            .then(function (response) {
                if (response.data.code && response.data.code == 200) {
                     $scope.$emit('onTicketChange', $scope.task);
                    toaster.pop('success', MESSAGE_TITLE, 'Repeat has been cancelled successfully');
                     $scope.task = response.data.ticket;
                     $scope.init($scope.task);
                }
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Repeat failed to Cancel');
            })
            .finally(function () {

            });
    }

    $scope.cancelScheduleTicket = function() {
       
        var ticket = $scope.task;
        
        var data = {};

        data.status_id = 4;     // Cancel state
        data.running = 0;
        data.log_type = 'Canceled';

        data.task_id = ticket.id;
        data.max_time = ticket.max_time;
    //    data.comment = comment;

        data.original_status_id = ticket.status_id;

        if( ticket.type == 1 || ticket.type == 2 || ticket.type == 3 )
            $rootScope.myPromise = GuestService.changeTaskState(data)
        if( ticket.type == 4 )
            $rootScope.myPromise = $http({
                method: 'POST',
                url: '/frontend/guestservice/changemanagedtask',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            });


        $rootScope.myPromise.then(function(response) {
                console.log(response.data);

            //    $scope.refreshTickets();

                if( response.data.code && response.data.code == 'NOTSYNC' )
                    toaster.pop('error', MESSAGE_TITLE, 'Ticket data is not synced' );
                if( response.data.code && response.data.code == 'SUCCESS' ){
                    toaster.pop('success', MESSAGE_TITLE, 'Task has been updated successfully');
                    $scope.$emit('onTicketChange', $scope.task);
                }

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Task.');
            })
            .finally(function() {

            });

   

    }

    $scope.$on('onfeedback', function (event, args) {
        //toaster.pop('error', 'Balls');
        $scope.task = args;
        $scope.init(args);
        $scope.$emit('onTicketChange', args);

        //toaster.pop('error', 'Refreshed');
    });

    $scope.onUpdateTime = function () {
        var data = {};

        if ($scope.task.repeat_end_date instanceof Date)
            data.repeat_end_date = moment($scope.task.repeat_end_date).format('YYYY-MM-DD');
        else
            data.repeat_end_date = $scope.task.repeat_end_date;
    

        data.repeat_flag = $scope.task.repeat_flag ? 1 : 0;
        data.start_date_time = $scope.task.start_date_time;
        
        data.status_id = 5; // schedule state
        data.running = 0;
        data.log_type = 'Scheduled';
        data.max_time = $scope.task.max_time;

        data.task_id = $scope.task.id;
        data.max_time = $scope.task.max_time;
        data.comment = $scope.task.reason;

        data.original_status_id = $scope.task.status_id;


        $rootScope.myPromise = GuestService.changeTaskState(data)
        .then(function (response) {
            if (response.data.code && response.data.code == 200) {
                $scope.$emit('onTicketChange', $scope.task);
                toaster.pop('success', MESSAGE_TITLE, 'Task is changed successfully');
                $scope.task = response.data.ticket;
                $scope.init($scope.task);
            }
            else
                toaster.pop('error', MESSAGE_TITLE, response.data.message);

        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Task is fail to change');
        })
        .finally(function () {

        });
    }

    $scope.changeTask = function () {
        if ($scope.task.action == SELECT_ACTION) {
            toaster.pop('error', 'Change ticket', 'Please select action');
            return;
        }

        var data = {};

        var profile = AuthService.GetCredentials();
        data.property_id = profile.property_id;

        data.start_date_time = $scope.start_date_time;

        if ($scope.task.action == COMPLETE_ACTION) {
            if (!($scope.task.dispatcher > 0)) {
                toaster.pop('error', MESSAGE_TITLE, 'Please set dispatcher');
                return;
            }

            data.status_id = 0; // Complete State
            data.running = 0;
            data.log_type = 'Completed';
            data.user_id = $scope.task.dispatcher;

        }
        else if ($scope.task.action == CANCEL_ACTION) {
            if (!$scope.task.reason) {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                return;
            }
            data.status_id = 4;     // Cancel state
            data.running = 0;
            data.log_type = 'Canceled';
        }
        else if ($scope.task.action == COMMENT_ACTION) {
            if (!$scope.task.reason) {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                return;
            }
            data.status_id = $scope.task.status_id;
            data.log_type = 'Comment';
        }
        else if ($scope.task.action == EXTEND_ACTION) {
         //   if ($scope.task.max_time <= 0)    // not extended
         //   {
         //       toaster.pop('error', MESSAGE_TITLE, 'Please set max time bigger than 0');
         //       return;
         //   }

         var extend_time = $scope.task.extend_time * 60;

         if (!(extend_time > 0))    // not extended
         {
             toaster.pop('error', MESSAGE_TITLE, 'Should not be able to select quantity less than 1.');
             return;
         }

            if (!$scope.task.reason) {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                return;
            }

            data.status_id = 1; // Open State
            data.running = 0;
            data.log_type = 'Extended';
            data.max_time = extend_time;
        }
        else if ($scope.task.action == OPEN_ACTION) {
            var date = new Date();
            data.start_date_time = moment(date).format('YYYY-MM-DD HH:mm:ss');
            data.status_id = 1; // Open State
            data.running = 1;
            data.log_type = 'Assigned';
        }
        else if ($scope.task.action == SCHEDULED_ACTION) {
            var date = '';
            if ($scope.task.date instanceof Date)
                date = moment($scope.task.date).format('YYYY-MM-DD');
            else
                date = $scope.task.date;

            var time = moment($scope.task.time).format('HH:mm:ss');
            data.start_date_time = date + ' ' + time;
            data.status_id = 5; // schedule state
            data.running = 0;
            data.log_type = 'Scheduled';
            data.max_time = $scope.task.max_time;
        }
        else if ($scope.task.action == HOLD_ACTION) {
            if (!$scope.task.reason) {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                return;
            }

            data.running = 0;
            data.status_id = $scope.task.status_id; // restore original state
            data.log_type = 'On-Hold';
        }
        else if ($scope.task.action == RESUME_ACTION) {
            data.running = 1;
            data.status_id = $scope.task.status_id; // restore original state
            data.log_type = 'Resume';
        }
        else if ($scope.task.action == CLOSE_ACTION) {
            data.status_id = 3;
            data.log_type = 'Closed';
            data.user_id = profile.id;
        }

        data.task_id = $scope.task.id;
        if ($scope.task.action != EXTEND_ACTION)
            data.max_time = $scope.task.max_time;
        data.comment = $scope.task.reason;

        data.original_status_id = $scope.task.status_id;


        $rootScope.myPromise = GuestService.changeTaskState(data)
            .then(function (response) {
                if (response.data.code && response.data.code == 200) {
                    $scope.$emit('onTicketChange', $scope.task);
                    toaster.pop('success', MESSAGE_TITLE, 'Task is changed successfully');
                    $scope.task = response.data.ticket;
                    $scope.init($scope.task);
                }
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);

            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Task is fail to change');
            })
            .finally(function () {

            });
    }

    $scope.cancelChangeTask = function () {
        $scope.task = angular.copy($scope.backuptask);
    }

    $scope.notifylist = [];

    var getNotificationHistory = function () {
        $rootScope.myPromise = GuestService.getNotificationHistoryList($scope.task.id, 1, 1000000, 'id', 'asc')
            .then(function (response) {
                $scope.notifylist = response.data.datalist;
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {

            });
    };

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

    $scope.$watch('datetime.date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.task.start_date_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
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


});


app.controller('FeedBackDModalCtrl', function ($scope, $rootScope, $http, AuthService, GuestService, $interval, toaster, $timeout, $uibModalInstance, task, feedback_flag) {
    /*
               $scope.ok = function () {
            $uibModalInstance.close($scope.sub);
            };
    */
    var MESSAGE_TITLE = 'Change Guest Task';
    $scope.feedback = {};
    // $scope.quantity = 1;
    $scope.feedback.comment = '';
    $scope.cancel = function () {
        // window.alert($scope.feedback.choice);
        $uibModalInstance.dismiss();
    };
    $scope.onChangeradio = function (num) {
        $scope.feedback.choice = num;

    }
    $scope.create = function () {
        // $scope.feedback.choice = num;
        var data = {};

        // if ($scope.feedback == 0)
        //     return;


        if ($scope.feedback.choice == undefined) {
            toaster.pop('error', 'Complete Feedback', 'Please choose feedback type.');
            return;
        }
        if ($scope.feedback.comment == '') {
            toaster.pop('error', 'Complete Feedback', 'Please fill in the guest feedback.');
            return;
        }

        var profile = AuthService.GetCredentials();
        data.property_id = profile.property_id;
        //window.alert("Before http:" + $scope.feedback.choice);

        data.task_id = task.id;
        data.user_id = profile.id;
        data.comment = $scope.feedback.comment;
        data.choice = $scope.feedback.choice;


        data.feedback_flag = task.feedback_flag;

        GuestService.FeedbackState(data)
            .then(function (response) {
                if (response.data.code && response.data.code == 200) {
                    // $scope.$emit('onTicketChange', $scope.task);
                    toaster.pop('success', MESSAGE_TITLE, 'Feedback has been changed successfully');
                    task = response.data.ticket;
                    $rootScope.$broadcast('onfeedback', task);
                    // $scope.task = response.data.ticket;
                    //window.alert("Choice"+response.data.choice);
                    // $scope.init($scope.task);

                }
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Feedback failed to change');
            })
            .finally(function () {

            });
        $uibModalInstance.dismiss();
    }

});
