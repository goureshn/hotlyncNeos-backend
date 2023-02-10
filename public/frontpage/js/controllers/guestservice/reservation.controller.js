app.controller('ReservationController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, GuestService, DateService, AuthService, uiGridConstants) {
    var MESSAGE_TITLE = 'Reservation';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.ticketlist = [];
    $scope.selectedTickets = [];
    $scope.tablelist = {};

    $scope.gs = GuestService;
    $scope.ds = DateService;

    //var ref = firebase.database().ref('reservation');
    //
    //ref.set({
    //    alanisawesome: {
    //        date_of_birth: "June 23, 1912",
    //        full_name: "Alan Turing"
    //    },
    //    gracehop: {
    //        date_of_birth: "December 9, 1906",
    //        full_name: "Grace Hopper"
    //    }
    //});
    //
    //ref.on('child_added', function(data) {
    //    console.log('child_added' + data.val().author);
    //});
    //
    //ref.on('child_changed', function(data) {
    //    console.log('child_changed' + data.val().author);
    //});
    //
    //ref.on('child_removed', function(data) {
    //    console.log('child_removed' + data.val().author);
    //});

    $scope.loading = false;
    $scope.by_booking = [{label: "No Show", data: 20}, {label: "Arrived", data: 40}, {label: "Completed", data: 70} ];
    $scope.by_restaurant = [{label: "KFC", data: 20}, {label: "Osteria Francescana", data: 40}, {label: "North Myrtle Beach, SC", data: 70},
                            {label: "Fearrington House Restaurant", data: 20}, {label: "Santa Rosa, CA", data: 40}];

    $scope.period = {};
    $scope.period.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.period.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.filter = {};

    $scope.$watch('period.dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getReservationStatistics();
    });

    $scope.$watch('period.daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getReservationStatistics();
    });

    $scope.getReservationStatistics = function() {
        // TODO
        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.period.dateFilter;

        switch($scope.filter.period)
        {
            case 'Weekly':
                $scope.filter.during = 7;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Monthly':
                $scope.filter.during = 30;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                $scope.filter.during = 365;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Custom Days':
                $scope.filter.start_date = $scope.period.daterange.substring(0, '2016-01-01'.length);
                $scope.filter.end_date = $scope.period.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
                $scope.filter.during = b.diff(a, 'days');

                if( $scope.filter.during > 45 )
                {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 45 days");
                    return;
                }
                break;
        }

        $scope.loading = true;
        $http({
            method: 'POST',
            url: '/frontend/reservation/statistics',
            data: $scope.filter,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
                console.log(response.data);
                $scope.showReservationStatistics(response.data);
            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.showReservationStatistics = function(data)
    {
        var status = ['Reserved', 'Canceled', 'Walk-in', 'No Show', 'Waiting', 'Arrived', 'Completed'];
        $scope.by_booking = [];
        var by_booking = data.by_booking_count;
        for(var i = 0; i < by_booking.length; i++)
            $scope.by_booking.push({label: status[by_booking[i].status], data: by_booking[i].cnt});

        $scope.by_restaurant = [];
        var by_restaurant = data.by_restaurant_count;
        for(var i = 0; i < by_restaurant.length; i++)
            $scope.by_restaurant.push({label: by_restaurant[i].restaurant, data: by_restaurant[i].cnt});
    }

    $scope.getReservationStatistics();


    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    // pip
    $scope.isLoading = false;

    $scope.getTicketData = function getTicketData(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;


        $http({
            method: 'POST',
            url: '/frontend/guestservice/reservationlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.ticketlist;
            $scope.checkSelection($scope.ticketlist);

            $scope.paginationOptions.totalItems = response.data.totalcount;

            if( $scope.paginationOptions.totalItems < 1 )
                tableState.pagination.numberOfPages = 0;
            else
                tableState.pagination.numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

            $scope.paginationOptions.countOfPages = tableState.pagination.numberOfPages;

            var tablelist = response.data.tablelist;
            for(var i = 0; i < tablelist.length; i++)
            {
                $scope.tablelist[tablelist[i].id] = tablelist[i];
            }

            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;

        for(var i = 0; i < ticketlist.length; i++)
        {
            var index = -1;
            var ticket = ticketlist[i];
            for(var j = 0; j < $scope.selectedTickets.length; j++)
            {
                if( ticket.id == $scope.selectedTickets[j].id )
                {
                    index = j;
                    break;
                }
            }

            ticket.active = index < 0 ? false : true;
        }
    }

    $scope.getTableNumber = function(ticket){
        var res = ticket.table_id.split("|");

        var tableNumber = '';
        for(var i = 0; i < res.length; i++)
        {
            var table = $scope.tablelist[res[i]];
            if( !table )
                continue;
            if( i < res.length - 1 )
                tableNumber += table.number + ',';
            else
                tableNumber += table.number;
        }
        return tableNumber;
    }

    $scope.onSelectTicket = function(ticket){
        console.log(ticket);
        // check select ticket
        var index = -1;
        for(var i = 0; i < $scope.selectedTickets.length; i++)
        {
            if( ticket.id == $scope.selectedTickets[i].id )
            {
                index = i;
                break;
            }
        }

        if( index < 0 )    // not selected
        {
            ticket.active = true;
            $scope.selectedTickets.push(angular.copy(ticket));
        }
        else {
            ticket.active = false;
            $scope.selectedTickets.splice(index, 1);
        }
    }

    $scope.removeSelectTicket = function(item, $index) {
        if( !$scope.ticketlist )
            return;

        var index = -1;
        for(var i = 0; i < $scope.ticketlist.length; i++)
        {
            if( item.id == $scope.ticketlist[i].id )
            {
                index = i;
                $scope.ticketlist[i].active = false;
            }
        }

        $scope.selectedTickets.splice($index, 1);
    }
    $scope.$on('onTicketChange', function(event, args){
        $scope.refreshTickets();
    });
    $scope.refreshTickets = function(){
        $scope.pageChanged();
    }

    $scope.pageChanged = function() {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/reservationlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
       .then(function(response) {
               $scope.ticketlist = response.data.ticketlist;
               $scope.checkSelection($scope.ticketlist);

               $scope.paginationOptions.totalItems = response.data.totalcount;

               var tablelist = response.data.tablelist;
               for(var i = 0; i < tablelist.length; i++)
               {
                   $scope.tablelist[tablelist[i].id] = tablelist[i];
               }
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    };

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.pageChanged();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.pageChanged();
    }

});


