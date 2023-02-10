app.controller('CampaignManagerController', function ($scope, $rootScope, $http, $window, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, $aside, liveserver) {
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

    $scope.pageChanged = function() {
        /////////////////////
        var request = {};
        request.page = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        // request.filter = $scope.filter;        
        // request.filter_value = $scope.filter_value;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/campaign/list',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;
                $scope.property_ids = response.data.property_ids;
                $scope.filter_value = '';

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
 
                $scope.paginationOptions.countOfPages = numberOfPages;

                checkSelectStatus();
                // setFilterPanel(response.data.filter);

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

    $scope.refreshLogs = function(){
        $scope.isLoading = true;
        $scope.pageChanged();
    }

    $scope.getTicketNumber = function(ticket) {
        return sprintf('C%05d', ticket.id);
    }

	$scope.selectedTickets = [];
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
                $scope.selectedTickets.push(angular.copy(ticket));                                
            }
            else {
                $scope.selectedTickets.splice(index, 1);                
            }

            checkSelectStatus();

            $timeout(function() {
                if( index < 0 )
                    $scope.active = 6 + ticket.id;
            }, 100);

        }, 100);
    }

    function checkSelectStatus() {
        for(var j = 0; j < $scope.datalist.length; j++)
        {
            var ticket = $scope.datalist[j];
            var index = -1;
            for(var i = 0; i < $scope.selectedTickets.length; i++)
            {
                if( ticket.id == $scope.selectedTickets[i].id )
                {
                    index = i;
                    break;
                }
            }    
            ticket.selected = index >= 0;            
        }        
    }

});

