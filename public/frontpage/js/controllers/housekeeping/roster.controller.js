app.controller('RosterController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, $q, AuthService, toaster) {
    var MESSAGE_TITLE = 'Roster Allocation';
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };
    $scope.datalist = [];
    $scope.selectedTicket=0;

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger').focus();
    }
    $scope.$on('onCreateRoaster', function (event, args) {
        $scope.pageChanged();
    });
    $scope.pageChanged = function () {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        /////////////////////
        var request = {};
        request.page = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        request.filter = $scope.filter;
        if (request.filter)
            request.filter.departure_date = moment(request.filter.departure_date).format('YYYY-MM-DD');

        request.filter_value = $scope.filter_value;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $scope.datalist = [];

        $http({
            method: 'POST',
            url: '/frontend/guestservice/rosterlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datalist = response.data.datalist;
                // $scope.datalist.forEach(function (item, index) {
                //     item.ticket_no = $scope.getTicketNumber(item);
                //     item.created_at_time = moment(item.created_at).format('D MMM YYYY hh:mm a');
                //     item.discuss_end_time_at = moment(item.discuss_end_time).format('DD MMM YYYY');
                // });

                $scope.paginationOptions.totalItems = response.data.totalcount;
                // $scope.subcount = response.data.subcount;
                // $scope.property_ids = response.data.property_ids;
                //$scope.filter_value = '';

                var numberOfPages = 0;

                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                $scope.paginationOptions.countOfPages = numberOfPages;

                // checkSelectStatus();

                // setFilterPanel(response.data.filter);

                // dept_list = response.data.dept_list;

                // console.log(response);
                // console.log(response.data.time);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.onSelectTicket = function (ticket) {
        // check select ticket
        if ($scope.selectedTicket!=0)
        var temp=$scope.selectedTicket;
        
        $scope.selectedTicket = 0;
        $timeout(function () {
            if (ticket != temp) {

                $scope.selectedTicket = ticket;
            } 
        }, 100);
       
        
    }
    $scope.getRosterID = function (ticket) {
        return sprintf('Edit %s', ticket.name);
    }

    $scope.$on('hskp_status_event', function(event, args){

        $scope.pageChanged();
        console.log("Auto Updating on dashboard of housekeeping");
    });


});
