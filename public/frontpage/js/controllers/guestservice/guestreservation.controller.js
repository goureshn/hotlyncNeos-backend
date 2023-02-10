app.controller('GuestReservationController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, AuthService, toaster, $location, $httpParamSerializer) {
    var MESSAGE_TITLE = 'Guest Reservation Page';

    var profile = AuthService.GetCredentials();

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 22,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.room_list = [];
    function getDataList()
    {        
        $http.get('/guest/roomlist?property_id=' + profile.property_id)
            .then(function(response) {            
                $scope.room_list = response.data.room_list;                       
        });

        $http.get('/list/country')
            .then(function(response) {            
                $scope.country_list = response.data;                       
        });
    }
    getDataList();

    $scope.status_list = [
        'Booking','Arrival','Canceled','In-House','Departed'
    ];

    

    $scope.getGuestList = function getAlarmList(tableState) {
        //here you could create a query string from tableState
        
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
        request.page = $scope.paginationOptions.pageNumber;//($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.searchtext = $scope.searchtext;
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        $http({
                method: 'POST',
                url: '/frontend/guestservice/guestreservationlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.list = response.data.list;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onSearch = function() {
        $scope.getGuestList();
    }

    $scope.onEditData = function(row) {
        if( !row )
            row = {};

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/guest_reservation/guest_reservation_edit.html',
            controller: 'GuestReservationEditController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                model: function () {
                    return row;
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.getGuestList();
        }, function () {

        }); 
    }
});

app.controller('GuestReservationEditController', function ($scope,$window, $http, $uibModal, $uibModalInstance, AuthService, toaster, model) {
    var MESSAGE_TITLE = 'Guest Reservation';

    var profile = AuthService.GetCredentials();
    
    $scope.model = angular.copy(model);
    $scope.model.property_id = profile.property_id;

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();

    $scope.datetime = {};
    $scope.model.start_date = moment(model.start_date).toDate();
    $scope.model.end_date = moment(model.end_date).toDate();

    $scope.onRoomSelect = function($item, $model, $label) {
        $scope.model.room_id = $item.id;        
    }

    $scope.createReservation = function() {
        var request = $scope.model;        
        request.start_date = moment($scope.model.start_date).format('YYYY-MM-DD');
        request.end_date = moment($scope.model.end_date).format('YYYY-MM-DD');
        
        $http({
                method: 'POST',
                url: '/frontend/guestservice/createguestreservation',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                if( response.data.code == 200 )
                {
                    toaster.pop('success', MESSAGE_TITLE, 'Reservation is Created')
                    $uibModalInstance.close(response.data.data);
                }
                else
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message)
                }
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();        
    }


});

