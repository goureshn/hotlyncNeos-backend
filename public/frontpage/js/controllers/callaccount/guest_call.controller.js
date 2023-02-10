app.controller('GuestCallController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, $interval, AuthService, toaster) {
    var MESSAGE_TITLE = 'Guest Page';
    
    $scope.full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';

    $scope.tableState = undefined;

    $scope.filters = {};
    $scope.filter = {};
    $scope.totalcharge = true;
    var search_option = '';
    var profile = AuthService.GetCredentials();
    var data = {};
    data.setting_group = 'currency' ;
    data.property_id =   profile.property_id;
    $scope.property_id =   profile.property_id;
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
    // pip
    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.total_count = 0;
    $scope.total_cost = 0;
    $scope.total_profit = 0;
    
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
    $scope.filter.buildings = [];
    
    $http.get('/frontend/buildsomelist?building_ids=' + profile.building_ids).success( function(response) {
        $scope.buildings = response;

        $scope.filter.buildings = $scope.buildings;
        for(var i = 0; i < $scope.filter.buildings.length; i++)
            $scope.filter.buildings[i].selected = true;
     
    });

    $scope.dest_list = [];
    $http.get('/list/calldestlist').success( function(response) {
        $scope.dest_list = response.map(function(item) {
            item.country_code = item.country + ' - ' + item.code;
            return item;
        });
    });
    
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
       // request.building_id = $scope.filters.building_id;
        request.search = $scope.search;
        request.call_date = moment($scope.filters.call_date).format('YYYY-MM-DD');
        request.searchoption = search_option;
        var totalcharge = 0;
        if($scope.totalcharge == true) totalcharge = 1;
        request.totalcharge = totalcharge;

        var profile = AuthService.GetCredentials();

        request.buildings = [];
        if( $scope.filter.buildings.length > 0 )
        {
            for(var i = 0; i < $scope.filter.buildings.length; i++) {
                if ($scope.filter.buildings[i].selected == true)
                    request.buildings.push($scope.filter.buildings[i].id);
            }
        }
        else
        {
            request.buildings = profile.building_ids.split(',');
        }
        
        if($scope.filter.buildings.length != request.buildings.length )
            $scope.buildingcolor = '#f2a30a';
        else
            $scope.buildingcolor = '#fff';

        request.property_id = profile.property_id;

        $http({
                method: 'POST',
                url: '/frontend/callaccount/guestcall',
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

    $scope.onChangeBuilding = function(building) {
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getDataList();
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.dateOptions = {
        showWeeks: true,
        formatYear: 'yy',
        startingDay: 1,
        maxDate: new Date(),
        class: 'datepicker'
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

    $scope.onShowCallRateDlg = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/callaccounting/modal/guest_call_rate.html',
            controller: 'GuestCallRateCalcController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                
            }
        });

        modalInstance.result.then(function (selectedItem) {

        }, function () {

        }); 
    }

});

app.controller('GuestCallRateCalcController', function ($scope, $http, $uibModal, $uibModalInstance, AuthService, toaster) {
    $scope.extension_type_list = ['Guest Call', 'Admin Call'];
    $scope.extension_type = $scope.extension_type_list[0];
    $scope.time_duration = 0;
    $scope.charge_val = 0;
    var dest_id = 0;

    $scope.onDestSelect = function($item, $model, $label) {
        dest_id = $item.id;
    }

    $scope.calculateRate = function() {
        var request = {};
        request.dest_id = dest_id;
        request.extension_type = $scope.extension_type;
        request.time_duration = $scope.time_duration;
        
        $http({
            method: 'POST',
            url: '/frontend/callaccount/calcrate',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function(response) {
            $scope.charge_val = response.data.content;
        }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function() {            
        });
    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }
});