app.controller('WakeupController', function ($scope, $rootScope, $http, $httpParamSerializer, $timeout, $uibModal, $window, AuthService, toaster) {
    var MESSAGE_TITLE = 'Wakeup List';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    //$scope.tab_full_height = 'height: ' + ($window.innerHeight - 230) + 'px; overflow-y: auto';

	$scope.tab_full_height = 'height: ' + ($window.innerHeight - 230) + 'px; overflow-y: auto';

		$scope.tab_full_height = 'height: ' + ($window.innerHeight - 230) + 'px; overflow-y: auto';

    $scope.full_height = $window.innerHeight - 40;
		//$scope.tab_full_height = $window.innerHeight - 230;
		
    $scope.subcount = {};
    

    $scope.subcount.success = 0;
    $scope.subcount.failed = 0;
    $scope.subcount.unanswer = 0;
    $scope.subcount.snooze = 0;
    $scope.subcount.pending = 0;
    $scope.subcount.total = 0;

    var search_option = '';

    var profile = AuthService.GetCredentials();

    $scope.newTickets = [];
    $scope.selectedTickets = [];

    $scope.newTickets[0] = {
        "id" : 0,
        "Number" : 1,
        "groupName" : "Create Wakeup",
    };

    $timeout(function(){
        $scope.active = 1;
    }, 100);


    $scope.isLoading = false;
    $scope.datalist = [];
      
    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(0,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
      $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
    //   $scope.pageChanged();
      $scope.getDataList();
    });

    $scope.onClickDateFilter = function() {
        angular.element('#dateranger').focus();
    }


    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 25,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.$watch('dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getDataList();
    });

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getDataList();
    });

    $scope.$on('$destroy', function() {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.filter = 'Total';
    $scope.onFilter = function getFilter(param) {
        $scope.filter =param;
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

        /////////////////////
        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter = $scope.filter;
        request.searchoption = search_option;
        
        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/wakeup/list',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;
                $scope.subcount = response.data.subcount;

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

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.refreshLogs = function(){
        $scope.getDataList(null);
    }


    $scope.getRowCss = function(row) {
        if( row.active )
            return 'active';
        else
            return '';
    }

    $scope.getWakeupNumber = function(log){
        if( log == undefined )
            return 0;

        return sprintf('%05d', log.id);
    }

    $scope.getDurationInMinute = function(duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    $scope.removeTicket = function() {

    }

    $scope.$on('onChangedWakeup', function(event, args){
        $scope.getDataList();
    });

    $scope.onSelectTicket = function(ticket){
        // check select ticket
        $timeout(function(){
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
                //ticket.active = 1;
                $scope.selectedTickets.push(angular.copy(ticket));
            }
            else {
                //ticket.active = false;
                $scope.selectedTickets.splice(index, 1);
            }

            $timeout(function(){
                if( index < 0 )
                    $scope.active = 6 + ticket.id;
            }, 100);
        }, 100);    
    }

    $scope.getTicketNumber = function(ticket) {
        return sprintf('W%05d', ticket.id);
    }

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }


});
