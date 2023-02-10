app.controller('RequestListController', function ($scope, $rootScope, $http, $window, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, liveserver) {
    var MESSAGE_TITLE = 'Wakeup List';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 100;
    $scope.tab_height = $window.innerHeight - 125;

    $scope.subcount = {};

    $scope.filter_value = '';

    $scope.subcount.pending = 0;
    $scope.subcount.resolved = 0;
    $scope.subcount.rejected = 0;
    $scope.subcount.escalated = 0;
    $scope.subcount.timeout = 0;
    $scope.subcount.total = 0;


    var profile = AuthService.GetCredentials();


    $scope.selectedTickets = [];


    $timeout(function(){
        $scope.active = 1;
    }, 100);

    $scope.isLoading = false;
    $scope.datalist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.$on('$destroy', function() {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.filter = 'Default';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.onClickDateFilter = function() {
        angular.element('#dateranger').focus();
    }

    $scope.onFilter = function getFilter(param) {
        $scope.filter =param;
        $scope.pageChanged();
    }

    $scope.searchEngRequest = function(value) {
        $scope.pageChanged();
    }

    $scope.pageChanged = function() {
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
        request.filter_value = $scope.filter_value;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/eng/requestlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;
                $scope.subcount = response.data.subcount;
                $scope.filter_value = '';

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
                console.log(response.data.time);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.pageChanged();

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
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
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.pageChanged();
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('DD-MMM-YYYY HH:mm:ss');
    }

    $scope.refreshLogs = function(){
        $scope.isLoading = true;
        $scope.filter = 'Default';
        $scope.pageChanged();
    }


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

            $timeout(function() {
                if( index < 0 )
                    $scope.active = 6 + ticket.id;
            }, 100);

        }, 100);
    }


    $scope.getTicketNumber = function(ticket) {
        return sprintf('E%05d', ticket.id);
    }

    $scope.$on('eng_request_post', function(event, args){
        $scope.pageChanged();
    });

    $scope.$on('request_refresh', function (event, args) {
        $scope.pageChanged();
    });


    $scope.onDownloadPDF = function(){
        var filter = {};
        filter.report_by = 'Summary';
        filter.report_type = 'Summary';
        filter.report_target = 'eng_request_summary';
        var profile = AuthService.GetCredentials();
        filter.property_id = profile.property_id;
        filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }
});


