app.controller('ReservationEditController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, DateService, uiGridConstants) {
    var MESSAGE_TITLE = 'Reservation';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    var SELECT_ACTION = '--Select Action--';
    var RESERVE_ACTION = 'Reserve';
    var CHANGE_ACTION = 'Change time and seat';
    var CANCEL_ACTION = 'Cancel';
    var ARRIVED_ACTION = 'Arrived';
    var WAIT_ACTION = 'Wait';
    var COMPLETE_ACTION = 'Complete';

    $scope.reservation = {};

    $scope.seats = [];
    var max_seats_count = 15;
    $scope.available = 0;
    $scope.reservation.wait_flag = false;

    $scope.init = function(ticket) {
        $scope.reservation = ticket;
        var start_time = new Date(Date.parse($scope.reservation.start_time));
        $scope.reservation.date = start_time.format('yyyy-MM-dd');
        $scope.reservation.time = start_time;

        $scope.ticket_id = sprintf('R%05d', $scope.reservation.id);

        $scope.searchTables();
        $scope.initActionList();
    }

    $scope.initActionList = function()
    {
        $scope.actions = [];
        switch($scope.reservation.status) {
            case 0: // Reserved
                $scope.actions = [
                    SELECT_ACTION,
                    CHANGE_ACTION,
                    CANCEL_ACTION,
                    ARRIVED_ACTION,
                    WAIT_ACTION,
                    COMPLETE_ACTION
                ];

                break;
            case 2: // Walk in
                $scope.actions = [
                    SELECT_ACTION,
                    RESERVE_ACTION,
                    CANCEL_ACTION,
                ];

                break;
            case 4: // Waiting
                $scope.actions = [
                    SELECT_ACTION,
                    RESERVE_ACTION,
                    CANCEL_ACTION,
                ];
                break;
            case 5: // Arrived
                $scope.actions = [
                    SELECT_ACTION,
                    CANCEL_ACTION,
                    WAIT_ACTION,
                    COMPLETE_ACTION
                ];
                break;
        }

        if( $scope.actions.length > 0 )
            $scope.reservation.action =  $scope.actions[0];
    }


    for(var i = 0; i < max_seats_count; i++ )
    {
        $scope.seats[i] = i + 1;
    }

    $scope.tables = [];

    var date = new Date();
    $scope.request_time = date.format("HH:mm:ss");
    $scope.timer = $interval(function() {
        var date = new Date();
        $scope.request_time = date.format("HH:mm:ss");
    }, 1000);

    $scope.onSearchTables = function() {
        if( !($scope.reservation.restaurant_id > 0) )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select restaurant');
            return;
        }
        $scope.searchTables();
    }


    $scope.searchTables = function() {
        var data = {};

        data.query = true;
        data.task_id = $scope.reservation.id;
        data.restaurant_id = $scope.reservation.restaurant_id;

        if( $scope.reservation.date instanceof Date )
            data.date = $scope.reservation.date.format('yyyy-MM-dd');
        else
            data.date = $scope.reservation.date;

        data.time = $scope.reservation.time.format('HH:mm:ss');
        data.seat = $scope.reservation.seats;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/tablelist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
                $scope.tables = response.data.tablelist;
                $scope.avaliable_count = 0;

                var res = $scope.reservation.table_id.split("|");

                for(var i = 0; i < $scope.tables.length; i++)
                {
                    if( $scope.tables[i].availability == 'No')
                        $scope.tables[i].status = 0;
                    else
                    {
                        $scope.avaliable_count++;

                        var find_flag = false;
                        for(var j = 0; j < res.length; j++)
                        {
                            if( $scope.tables[i].id == res[j] ) {
                                find_flag = true;
                                break;
                            }
                        }

                        if( find_flag == true )
                            $scope.tables[i].status = 3;
                        else
                            $scope.tables[i].status = 2;
                    }
                }

                $scope.available = $scope.checkAvaliablity();
            });
    }

    $scope.checkAvaliablity = function() {
        var total_seats = 0;
        var min_total_seats = 0;
        for(var i = 0; i < $scope.tables.length; i++)
        {
            if( $scope.tables[i].status >= 2 )  // available
            {
                total_seats += $scope.tables[i].seats;
                min_total_seats += $scope.tables[i].min_seats;
            }
        }

        if( total_seats < $scope.reservation.seat )
        {
            return -1;
        }

        if( min_total_seats > $scope.reservation.seat )
        {
            return -2;
        }

        return 0;
    }

    $scope.changeReservation = function() {
        var status = 0;
        var data = {};

        if( !$scope.reservation.action )
            return;

        if( $scope.reservation.action == SELECT_ACTION )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select action');
            return;
        }

        data.task_id = $scope.reservation.id;
        data.original_status_id =  $scope.reservation.status;

        var table_id = '';
        if( status == 0 )
        {
            for(var i = 0; i < $scope.tables.length; i++)
            {
                if( $scope.tables[i].status != 3 )  // selected
                    continue;

                if( i < $scope.tables.length - 1 )
                    table_id += $scope.tables[i].id + '|';
                else
                    table_id += $scope.tables[i].id;
            }
        }
        else
        {
            table_id = '';
        }

        data.table_id = table_id;

        if( $scope.reservation.action == CHANGE_ACTION || $scope.reservation.action == RESERVE_ACTION )
        {
            var total_seats = 0;
            var min_total_seats = 0;
            for(var i = 0; i < $scope.tables.length; i++)
            {
                if( $scope.tables[i].status == 3 )  // selected
                {
                    total_seats += $scope.tables[i].seats;
                    min_total_seats += $scope.tables[i].min_seats;
                }
            }

            if( total_seats < $scope.reservation.seats )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please select more table');
                return;
            }

            if( min_total_seats > $scope.reservation.seats )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please remove some tables');
                return;
            }

            var date = '';
            if( $scope.reservation.date instanceof Date )
                date = $scope.reservation.date.format('yyyy-MM-dd');
            else
                date = $scope.reservation.date;

            var time = $scope.reservation.time.format('HH:mm:ss');

            data.start_time = date + ' ' + time;
            data.status = 0;    // Reserved
            data.seats = $scope.reservation.seats;
        }
        else if( $scope.reservation.action == CANCEL_ACTION )
        {
            data.status = 1;
            data.table_id = undefined;
        }
        else if( $scope.reservation.action == ARRIVED_ACTION )
        {
            data.status = 5;
            data.table_id = undefined;
        }
        else if( $scope.reservation.action == WAIT_ACTION )
        {
            data.status = 4;
            data.table_id = undefined;
        }

        else if( $scope.reservation.action == COMPLETE_ACTION )
        {
            data.status = 6;
            data.table_id = undefined;
        }

        $http({
            method: 'POST',
            url: '/frontend/guestservice/changereservation',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
            $scope.reservation = response.data.ticket;
            $scope.$emit('onTicketChange', $scope.reservation);

            if( response.data.code && response.data.code == 'NOTSYNC' )
                toaster.pop('error', MESSAGE_TITLE, 'Ticket data is not synced' );
            if( response.data.code && response.data.code == 'SUCCESS' )
                toaster.pop('success', MESSAGE_TITLE, 'Task is changed successfully');

            $scope.init($scope.reservation);
        });
    }
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
        return cur_date.getDate() > date.getDate();
    };
});

