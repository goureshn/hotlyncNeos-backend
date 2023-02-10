app.controller('BCCallController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, $interval, AuthService, toaster) {
  var MESSAGE_TITLE = 'Guest Page';

    $scope.tableState = undefined;

    $scope.filters = {};
    $scope.totalcharge = true;
    var search_option = '';

    // pip
    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.total_count = 0;
    $scope.total_cost = 0;
    $scope.total_profit = 0;
    var profile = AuthService.GetCredentials();
    var data = {};
    data.setting_group = 'currency' ;
    data.property_id =   profile.property_id;
    $http({
        method: 'POST',
        url: '/backoffice/configuration/wizard/general',
        data: data,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    })
        .success(function (data, status, headers, config) {
            $scope.currency = data.currency.currency;
        })
        .error(function (data, status, headers, config) {
            console.log(status);
        });
    $scope.filters.call_date = new Date();
    $scope.calltypes = [
        'All',
        'Internal',
        'Received',
        'Local',
        'Mobile',
        'Missed',
        'National',
        'International',
    ];

    $scope.filters.call_type = $scope.calltypes[0];
    $scope.search = "";

    $scope.refresh = $interval(function() {
        $scope.getDataList();
    }, 60 * 1000);

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.refresh)) {
            $interval.cancel($scope.refresh);
            $scope.refresh = undefined;
        }
    });

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.onTotalCharge = function(){
        $scope.getDataList();
    }
    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
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
        request.call_type = $scope.filters.call_type;
        request.search = $scope.search;
        request.call_date = moment($scope.filters.call_date).format('YYYY-MM-DD');
        request.searchoption = search_option;
        var totalcharge = 0;
        if($scope.totalcharge == true) totalcharge = 1;
        request.totalcharge = totalcharge;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
                method: 'POST',
                url: '/frontend/callaccount/bccall',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;
                $scope.total_count = response.data.totalcount;
                $scope.total_cost = response.data.total_cost;
                $scope.total_profit = response.data.total_profit;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onChangeCallType = function() {
        search_option = '';
        $scope.$emit('erase_search');

        $scope.getDataList();
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

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
        return cur_date.getTime() <= date.getTime();
    };

    $scope.select = function(date) {
        console.log(date);

        $scope.date_selector_is_open = false;

        search_option = '';
        $scope.$emit('erase_search');

        $scope.getDataList();
    }

    $scope.getRoundValue = function(value) {
        return sprintf("%1.2f", value);
    }

    $scope.getDuration = function(row) {
        return moment.utc(row.duration * 1000).format("HH:mm:ss")
    }

    $scope.$on('search-list', function(event, args) {
        console.log(args);
        search_option = args.filter;

        $scope.paginationOptions.pageNumber = 0;

        $scope.getDataList();
    });

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }
    //var ref = firebase.database().ref('callaccount/guest_call');
    //ref.on('child_changed', function(data) {
    //    console.log('child_changed' + data.val().author);
    //});
});