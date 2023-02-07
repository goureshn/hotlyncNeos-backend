app.controller('ReservationNewController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, DateService, uiGridConstants) {
    var MESSAGE_TITLE = 'Reservation';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.patron = {};
    $scope.reservation = {};
    $scope.restaurant = {};

    $scope.seats = [];
    var max_seats_count = 15;
    $scope.available = 0;
    $scope.reservation.wait_flag = false;

    for(var i = 0; i < max_seats_count; i++ )
    {
        $scope.seats[i] = i + 1;
    }

    var date = new Date();

    $scope.reservation.seat = 1;
    $scope.reservation.date = date;
    $scope.reservation.time = date;

    $scope.tables = [];

    $http.get('/frontend/guestservice/maxreservationno')
        .then(function(response) {
            $scope.max_ticket_no = response.data.max_ticket_no;
            $scope.ticket_id = sprintf('R%05d', $scope.max_ticket_no + 1);
        });

    var date = new Date();
    $scope.request_time = date.format("HH:mm:ss");
    $scope.timer = $interval(function() {
        var date = new Date();
        $scope.request_time = date.format("HH:mm:ss");
    }, 1000);

    $scope.getPatronList = function(name) {
        return $http.get('/frontend/guestservice/patronlist?name=' + name)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    }

    $scope.onSelectPatron = function ($item, $model, $label) {
        $scope.patron = $item;
    };

    // get
    $scope.getRestaurantList = function(name) {
        return $http.get('/frontend/guestservice/restaurantlist?name=' + name)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    }

    $scope.onRestaurantSelect = function ($item, $model, $label) {
        $scope.restaurant = $item;
        $scope.restaurant.query_flag = true;

        $scope.searchTables();

        $http.get('/frontend/guestservice/promotionlist?restaurant_id=' + $scope.restaurant.id)
            .then(function(response){

            });
    };

    $scope.onChangeAction = function ($item, $model, $label) {
        $scope.restaurant.query_flag = true;
        $scope.searchTables();
    };

    $scope.onSearchTables = function() {
        $scope.restaurant.query_flag = true;

        if( !($scope.restaurant.id > 0) )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select restaurant');
            return;
        }
        $scope.searchTables();
    }


    $scope.searchTables = function() {
        var data = {};

        data.restaurant_id = $scope.restaurant.id;
        data.query = $scope.restaurant.query_flag;

        if( $scope.reservation.date instanceof Date )
            data.date = $scope.reservation.date.format('yyyy-MM-dd');
        else
            data.date = $scope.reservation.date;

        data.time = $scope.reservation.time.format('HH:mm:ss');
        data.seat = $scope.reservation.seat;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/tablelist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
                $scope.tables = response.data.tablelist;
                $scope.avaliable_count = 0;
                for(var i = 0; i < $scope.tables.length; i++)
                {
                    if( $scope.tables[i].availability == 'No')
                        $scope.tables[i].status = 0;
                    else
                    {
                        $scope.avaliable_count++;
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

    $scope.createReservation = function() {
        if( !($scope.patron.id > 0) )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select Patron');
            return;
        }

        if( !($scope.restaurant.id > 0) )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select Restaurant');
            return;
        }

        var status = 0;
        if( !($scope.available < 0 && $scope.reservation.wait_flag) )
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

            if( total_seats < $scope.reservation.seat )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please select more table');
                return;
            }

            if( min_total_seats > $scope.reservation.seat )
            {
                toaster.pop('error', MESSAGE_TITLE, 'Please remove some tables');
                return;
            }

        }
        else
        {
            status = 4; // waiting
        }

        var data = {};

        data.patron_id = $scope.patron.id;

        var date = '';
        if( $scope.reservation.date instanceof Date )
            date = $scope.reservation.date.format('yyyy-MM-dd');
        else
            date = $scope.reservation.date;

        var time = $scope.reservation.time.format('HH:mm:ss');

        data.start_time = date + ' ' + time;

        data.restaurant_id = $scope.restaurant.id;
        data.seats = $scope.reservation.seat;
        data.confirmation = 'No';
        data.reminder = 'Yes';
        data.status = status;
        data.message = $scope.reservation.comment;

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

        $http({
            method: 'POST',
            url: '/frontend/guestservice/createreservation',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
            $scope.patron_name = '';
            $scope.restaurant_name = '';
            $scope.patron = {};
            $scope.restaurant = {};
            $scope.avaliable_count = 0;
            $scope.tables = [];

            $scope.$emit('onTicketChange', $scope.restaurant);

            toaster.pop('success', MESSAGE_TITLE, 'Reservation is created successfully.');
        });
    }
});

app.controller('DatetimeController', function ($scope) {
    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    //$scope.dateOptions = {
    //    formatYear: 'yy',
    //    startingDay: 1,
    //    class: 'datepicker'
    //};

    $scope.dateOptions = {
        dateDisabled: disabled,
        formatYear: 'yy',
        maxDate: new Date(2020, 5, 22),
        minDate: new Date(),
        startingDay: 1
    };


    $scope.disabled = function(date, mode) {
        var cur_date = new Date();
        return cur_date.getTime() >= date.getTime();
    };
});

