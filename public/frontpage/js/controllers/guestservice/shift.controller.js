app.controller('ShiftController', function($scope, $http, $window, $timeout, $compile, DateService, toaster, $uibModal, AuthService) {
    var MESSAGE_TITLE = 'Shift Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';

    var profile = AuthService.GetCredentials();
    var dept_id = profile.dept_id;

    $scope.shift = [];
    $scope.shift.staff_list = [];
    $scope.shift.dept_func_list = [];
    $scope.shift.location_group_list = [];
    $scope.shift.shift_id = 0;
    $scope.shift.day_off = [];
    $scope.shift.task_group_list = [];
    $scope.shift.dept_id = dept_id;

    $scope.staff_list = [];
    $scope.shift_group_member = [];
    $scope.total_task_group_list = [];

    var date = new Date();
    $scope.shift.vacation = date.format('yyyy-MM-dd - yyyy-MM-dd');

    $scope.days = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: date.format('yyyy-MM-dd'),
        endDate: date.format('yyyy-MM-dd')
    };


    $http.get('/frontend/guestservice/shiftinfolist?dept_id=' + dept_id)
        .then(function(response) {
            $scope.staff_list = response.data.staff_list;
            $scope.dept_func_list = response.data.dept_func;
            $scope.location_group_list = response.data.location_group;
            $scope.shift_list = response.data.shifts;
            $scope.total_task_group_list = response.data.task_group_list;
            $scope.shift.shift_id = $scope.shift_list[0].shift;

            $scope.shift_group_member = response.data.shift_group_member;
            $scope.getTaskLocationGroupList();

            $('.calendar').fullCalendar( 'refetchEvents' );

        }).catch(function(response) {
        })
        .finally(function() {
        });

    $scope.getTaskLocationGroupList = function() {
        $scope.location_group_list_data = {};
        for(var i = 0; i < $scope.location_group_list.length; i++)
            $scope.location_group_list_data[$scope.location_group_list[i]['id']] = $scope.location_group_list[i];

        $scope.task_group_list_data = {};
        for(var i = 0; i < $scope.total_task_group_list.length; i++)
            $scope.task_group_list_data[$scope.total_task_group_list[i]['id']] = $scope.total_task_group_list[i];

        for(var i = 0; i < $scope.shift_group_member.length; i++)
        {
            var shift_group_member = $scope.shift_group_member[i];
            var location_grp_ids = JSON.parse(shift_group_member['location_grp_id']);
            var task_grp_ids = JSON.parse(shift_group_member['task_group_id']);
            $scope.shift_group_member[i].task_grp_ids = task_grp_ids;

            $scope.shift_group_member[i].task_group_list = [];
            for(var j = 0; j < task_grp_ids.length; j++)
                $scope.shift_group_member[i].task_group_list.push($scope.task_group_list_data[task_grp_ids[j]]);

            $scope.shift_group_member[i].location_group_list = [];
            for(var j = 0; j < location_grp_ids.length; j++)
                $scope.shift_group_member[i].location_group_list.push($scope.location_group_list_data[location_grp_ids[j]]);
        }
    }

    $scope.getLocationgroupList = function(location_group_ids) {

    }


    $scope.onSelectDeptfunc = function() {
        var data = {};
        data.dept_func_list = $scope.shift.dept_func_list;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/taskgrouplist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.task_group_list = response.data;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    $scope.createShift = function() {
        var data = {};

        if( $scope.shift.staff_list.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select at least one staff');
            return;
        }

        if( $scope.shift.task_group_list.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select at least one task group');
            return;
        }

        if( $scope.shift.location_group_list.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select at least one location group');
            return;
        }

        data.dept_id = $scope.shift.dept_id;
        data.shift_id = $scope.shift.shift_id;
        data.staff_list = $scope.shift.staff_list;
        data.location_group_list = $scope.shift.location_group_list;
        data.task_group_list = $scope.shift.task_group_list;

        var days = "";
        var count = 0;
        $.each($scope.days, function(i,e){
            if ($scope.shift.day_off.indexOf(e) > -1) {
            } else {
                //Not in the array
                if( count == 0 )
                    days += e;
                else
                    days += "," + e;

                count++;
            }


        });
        data.day_of_week = days;


        data.vaca_start_date = $scope.shift.vacation.substring(0, '2016-01-01'.length);
        data.vaca_end_date = $scope.shift.vacation.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        $scope.changeShift(data);
    }

    var date = new Date();
    var d = date.getDate();
    var m = date.getMonth();
    var y = date.getFullYear();

    var checkDay = function (day, shift_group_member) {
        var check_day = shift_group_member.day_of_week.includes(day.format('dddd'));

        var startDate = moment(shift_group_member.vaca_start_date)
            , endDate   = moment(shift_group_member.vaca_end_date);

        var check_vacation = startDate <= day && endDate >= day;
        return check_day && !check_vacation;
    }

    var matchingDaysBetween = function (start, end, shift_group_members, test) {
        var events = [];
        for (var day = moment(start); day.isBefore(end); day.add(1, 'd')) {
            var start_time = new Date(day.format('YYYY-MM-DD 00:00:59')).getTime();
            var end_time = new Date(day.format('YYYY-MM-DD 11:59:00')).getTime();

            // check cover 24
            var task_cover_flag = {};
            for(var j = 0; j < $scope.total_task_group_list.length; j++) {
                var task_group_id = $scope.total_task_group_list[j].id;
                var temp = [];
                for (var i = 0; i < shift_group_members.length; i++) {
                    var shift_group_member = shift_group_members[i];
                    if (test(day, shift_group_member) &&  shift_group_member.task_grp_ids.indexOf(task_group_id) >= 0 ) {   // exist task group
                        var interval = [];
                        interval[0] = new Date(day.format('YYYY-MM-DD ') + shift_group_member.start_time).getTime();
                        interval[1] = new Date(day.format('YYYY-MM-DD ') + shift_group_member.end_time).getTime();
                        temp.push(interval);
                    }
                }

                var cover = false;
                var intervals = mergeIntervals(temp);
                if( intervals.length != 1  )  // not cover
                    cover = false;
                else
                {
                    if( intervals[0][0] < start_time && end_time < intervals[0][1] )    // cover
                        cover = true;
                    else
                        cover = false;
                }

                task_cover_flag[task_group_id] = cover;

            }
            for( var i = 0; i < shift_group_members.length; i++ )
            {
                var shift_group_member = shift_group_members[i];
                if (test(day, shift_group_member)) {
                    var start_date_time = day.format('YYYY-MM-DD ') + shift_group_member.start_time;
                    var end_date_time = day.format('YYYY-MM-DD ') + shift_group_member.end_time;
                    var event = {};
                    event.start = moment(start_date_time);
                    event.end = moment(end_date_time);
                    event.title = shift_group_member.wholename;
                    event.className = ['b-l b-2x b-info'];
                    event.info = shift_group_member.shname;
                    event.task_group_list = [];
                    event.user_id = shift_group_member.user_id;
                    event.vaca_start_date = shift_group_member.vaca_start_date;
                    event.vaca_end_date = shift_group_member.vaca_end_date;
                    event.dept_id = $scope.shift.dept_id;
                    event.shift_id = $scope.shift_list[0].shift;


                    shift_group_member.task_group_list;
                    for( var j = 0; j < shift_group_member.task_group_list.length; j++ )
                    {
                        var data = angular.copy(shift_group_member.task_group_list[j]);
                        if( data == undefined )
                            continue;

                        data.cover = task_cover_flag[data.id];
                        event.task_group_list.push(data);
                    }
                    event.location_group_list = shift_group_member.location_group_list;
                    events.push(event); // push a copy of day
                }
            }

        }
        return events;
    }

    $scope.events = function(start, end, timezone, callback) {
        var total_events = [];
        //for( var i = 0; i < $scope.shift_group_member.length; i++ )
        //{
        //    var shift_group_member = $scope.shift_group_member[i];
            var events = matchingDaysBetween(start, end, $scope.shift_group_member, checkDay);
        //
        //    total_events = total_events.concat(events);
        //}

        callback(events);
    }

    /* alert on Drop */
    $scope.alertOnDrop = function(event, delta, revertFunc, jsEvent, ui, view){
        $scope.alertMessage = ('Event Droped to make dayDelta ' + delta);
    };
    /* alert on Resize */
    $scope.alertOnResize = function(event, delta, revertFunc, jsEvent, ui, view){
        $scope.alertMessage = ('Event Resized to make dayDelta ' + delta);
    };

    $scope.overlay = $('.fc-overlay');
    $scope.alertOnMouseOver = function( event, jsEvent, view ){
        $scope.event = event;
        $scope.overlay.removeClass('left right top').find('.arrow').removeClass('left right top pull-up');
        var wrap = $(jsEvent.target).closest('.fc-event');
        var cal = wrap.closest('.calendar');
        var left = wrap.offset().left - cal.offset().left;
        var right = cal.width() - (wrap.offset().left - cal.offset().left + wrap.width());
        var top = cal.height() - (wrap.offset().top - cal.offset().top + wrap.height());
        if( right > $scope.overlay.width() ) {
            $scope.overlay.addClass('left').find('.arrow').addClass('left pull-up')
        }else if ( left > $scope.overlay.width() ) {
            $scope.overlay.addClass('right').find('.arrow').addClass('right pull-up');
        }else{
            $scope.overlay.find('.arrow').addClass('top');
        }
        if( top < $scope.overlay.height() ) {
            $scope.overlay.addClass('top').find('.arrow').removeClass('pull-up').addClass('pull-down')
        }
        (wrap.find('.fc-overlay').length == 0) && wrap.append( $scope.overlay );
    }

    /* config object */
    $scope.uiConfig = {
        calendar:{
            height: 650,
            editable: false,
            header:{
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            dayClick: $scope.alertOnEventClick,
            eventDrop: $scope.alertOnDrop,
            eventResize: $scope.alertOnResize,
            eventMouseover: $scope.alertOnMouseOver
        }
    };

    /* add custom event*/
    $scope.addEvent = function() {
        $scope.events.push({
            title: 'New Event',
            start: new Date(y, m, d),
            className: ['b-l b-2x b-info']
        });
    };

    /* remove event */
    $scope.remove = function(index) {
        $scope.events.splice(index,1);
    };

    /* Change View */
    $scope.changeView = function(view, calendar) {
        $('.calendar').fullCalendar('changeView', view);
    };

    $scope.today = function(calendar) {
        $('.calendar').fullCalendar('today');
    };

    ///* event sources array*/
    $scope.eventSources = [$scope.events];

    // The main function that takes a set of intervals, merges
    // overlapping intervals and prints the result
    function mergeIntervals(intervals)
    {
        // Test if the given set has at least one interval
        if (intervals.length <= 0)
            return [];

        // Create an empty stack of intervals
        var stack = [], last;

        // sort the intervals based on start time
        intervals.sort(function(a,b) {
            return a[0] - b[0];
        });

        // push the first interval to stack
        stack.push(intervals[0]);

        // Start from the next interval and merge if necessary
        for (var i = 1, len = intervals.length ; i < len; i++ ) {
            // get interval from last item
            last = stack[stack.length - 1];

            // if current interval is not overlapping with stack top,
            // push it to the stack
            if (last[1] <= intervals[i][0]) {
                stack.push( intervals[i] );
            }

            // Otherwise update the ending time of top if ending of current
            // interval is more
            else if (last[1] < intervals[i][1]) {
                last[1] = intervals[i][1];

                stack.pop();
                stack.push(last);
            }
        }

        return stack;
    }

    $scope.getTaskCss = function(item) {
        return item.cover ? 'text-primary' : 'text-danger';
    }

    $scope.changeShift = function(data) {
        $http({
            method: 'POST',
            url: '/frontend/guestservice/createshiftgrouplist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Shifts have been changed successfully');

                $scope.shift_group_member = response.data.shift_group_member;

                $scope.getTaskLocationGroupList();
                $('.calendar').fullCalendar( 'refetchEvents' );

            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Shifts have not been changed');
            })
            .finally(function () {
            });
    }

    $scope.deleteShift = function(event) {
        var data = {};
        data.staff_id = event.user_id;
        data.dept_id = $scope.shift.dept_id;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/deleteshiftgrouplist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Shifts have been changed successfully');

                $scope.shift_group_member = response.data.shift_group_member;

                $scope.getTaskLocationGroupList();
                $('.calendar').fullCalendar( 'refetchEvents' );

            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Shifts have not been changed');
            })
            .finally(function () {
            });
    }

    $scope.onEditShift = function(event) {
        console.log(event);

        var param = {};

        param.shift = event;
        param.dept_func_list = $scope.dept_func_list;
        param.location_group_list = $scope.location_group_list;
        param.shift_list = $scope.shift_list;
        param.total_task_group_list = $scope.total_task_group_list;
        param.shift_id = $scope.shift.shift_id;

        var size = '';
        var modalInstance = $uibModal.open({
            templateUrl: 'edit_shift_dialog.html',
            controller: 'EditShiftController',
            size: size,
            resolve: {
                param: function () {
                    return param;
                }
            }
        });

        modalInstance.result.then(function (data) {
            $scope.changeShift(data);
        }, function () {

        });
    }

    $scope.onDeleteShift = function(event) {
        console.log(event);
        var message = {};

        message.title = 'Delete Shift';
        message.content = 'Do you want to delete shift for ' + event.title + '?';

        var modalInstance = $uibModal.open({
            templateUrl: 'confirm_modal.html',
            controller: 'DeleteConfirmCtrl',
            resolve: {
                message: function () {
                    return message;
                }
            }
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
            {
                $scope.deleteShift(event);
            }
        }, function () {

        });

        $scope.deleteShift(event);
    }

});


app.controller('EditShiftController', function ($scope, $uibModalInstance, $http, toaster, param) {
    var MESSAGE_TITLE = 'Shift Page';

    $scope.shift = {};
    $scope.shift.wholename = param.shift.title;
    $scope.shift.staff_list = [param.shift.user_id];
    $scope.shift.dept_func_list = [];
    $scope.shift.location_group_list = [];
    $scope.shift.shift_id = param.shift_id;
    $scope.shift.day_off = [];
    $scope.shift.task_group_list = [];
    $scope.shift.dept_id = param.shift.dept_id;

    $scope.days = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    $scope.dept_func_list = param.dept_func_list;
    $scope.location_group_list = param.location_group_list;
    $scope.shift_list = param.shift_list;
    $scope.total_task_group_list = param.total_task_group_list;
    $scope.shift.shift_id = param.shift_id;

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: param.shift.vaca_start_date,
        endDate: param.shift.vaca_end_date
    };

    $scope.shift.vacation = param.shift.vaca_start_date + ' - ' + param.shift.vaca_end_date;

    $scope.onSelectDeptfunc = function() {
        var data = {};
        data.dept_func_list = $scope.shift.dept_func_list;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/taskgrouplist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.task_group_list = response.data;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    $scope.onClickOK = function () {
        if( $scope.shift.task_group_list.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select at least one task group');
            return;
        }

        if( $scope.shift.location_group_list.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select at least one location group');
            return;
        }

        var data = {};

        data.dept_id = $scope.shift.dept_id;
        data.shift_id = $scope.shift.shift_id;
        data.staff_list = $scope.shift.staff_list;
        data.location_group_list = $scope.shift.location_group_list;
        data.task_group_list = $scope.shift.task_group_list;

        var days = "";
        var count = 0;
        $.each($scope.days, function(i,e){
            if ($scope.shift.day_off.indexOf(e) > -1) {
            } else {
                //Not in the array
                if( count == 0 )
                    days += e;
                else
                    days += "," + e;

                count++;
            }


        });
        data.day_of_week = days;


        data.vaca_start_date = $scope.shift.vacation.substring(0, '2016-01-01'.length);
        data.vaca_end_date = $scope.shift.vacation.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        $uibModalInstance.close(data);
    };

    $scope.onClickCancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});

app.controller('DeleteConfirmCtrl', function($scope, $uibModalInstance, message) {
    $scope.message = message;
    $scope.ok = function () {
        $uibModalInstance.close('ok');
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('close');
    };
});
