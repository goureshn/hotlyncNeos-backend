app.controller('LogPerStaffController', function($scope, $rootScope, $http, $window, AuthService, $timeout, toaster) {
    $scope.onChangeViewBy = function() {
        if( $scope.view_by == 'Shift' )
            $scope.itemlist = $scope.shift_list;

        if( $scope.view_by == 'Staff' )
            $scope.itemlist = $scope.staff_list;


    }
    function initData() {

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/roomshiftlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.shift_list = response.data.shifts;
                $scope.staff_list = response.data.attendant_list;

                var showall = {};
                showall.id = 0;
                showall.item_name = "Show all";
                $scope.shift_list.unshift(showall);

                $scope.staff_list.unshift(showall);

                $scope.ids = [0];

                $scope.onChangeViewBy();
            }).catch(function (response) {
            })
            .finally(function () {
            });

        $scope.floor_ids = [0];

        $scope.viewtypes = [
            'Shift',
            'Staff',
        ];

        $scope.view_by = $scope.viewtypes[0];
    }

    initData();

    $scope.isLoading = false;

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

    function getDataList() {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};
        var profile = AuthService.GetCredentials();
        request.dept_id = profile.dept_id;
        request.property_id = profile.property_id;
        request.ids = $scope.ids;

        if( $scope.view_by == 'Staff' ) {
            $http({
                method: 'POST',
                url: '/frontend/hskp/gethskpstatusbystaff',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    $scope.datalist = response.data.datalist;
                    console.log(response);
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                    $scope.isLoading = false;
                });
        }

        if( $scope.view_by == 'Shift' ) {
            $http({
                method: 'POST',
                url: '/frontend/hskp/gethskpstatusbyshift',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    $scope.datalist = response.data.datalist;
                    console.log(response);
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                    $scope.isLoading = false;
                });
        }

    };

    $scope.getStartTime = function(row) {
        if( row.start_time )
            return moment(row.start_time).format('h:mm:ss a');
        else
            return '--:--';
    }

    $scope.getEndTime = function(row) {
        switch( row.state )
        {
            case 0:     // complete
                if( row.end_time )
                    return moment(row.end_time).format('h:mm:ss a');
                else
                    return '--:--';

                break;
            case 1:
                break;
            case 2:     // start
                return moment.utc(moment().diff(moment(row.start_time))).format("HH:mm:ss");
                break;
            case 3:
                break;
        }
    }

    $scope.onShowList = function () {
        console.log($scope.floor_ids);
        getDataList();
    }

    $scope.max = 200;

    $scope.random = function() {
        var value = Math.floor((Math.random() * 100) + 1);
        var type;

        if (value < 25) {
            type = 'success';
        } else if (value < 50) {
            type = 'info';
        } else if (value < 75) {
            type = 'warning';
        } else {
            type = 'danger';
        }

        $scope.showWarning = (type === 'danger' || type === 'warning');

        $scope.dynamic = value;
        $scope.type = type;
    };
    $scope.random();

    $scope.randomStacked = function() {
        $scope.stacked = [];
        var types = ['success', 'info', 'warning', 'danger'];

        for (var i = 0, n = Math.floor((Math.random() * 4) + 1); i < n; i++) {
            var index = Math.floor((Math.random() * 4));
            $scope.stacked.push({
                value: Math.floor((Math.random() * 30) + 1),
                type: types[index]
            });
        }
    };
    $scope.randomStacked();
});
