app.controller('LogPerFloorController', function($scope, $rootScope, $http, $window, AuthService, $timeout, toaster) {
    function initData() {

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/shiftlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.shift_list = response.data.shifts;
                $scope.shift_group_id = $scope.shift_list[0].id;
                $scope.floor_list = response.data.floors;

                var showall = {};
                showall.id = 0;
                showall.floor_name = "Show all";
                $scope.floor_list.unshift(showall);

            }).catch(function (response) {
            })
            .finally(function () {
            });

        $scope.floor_ids = [0];
    }

    initData();

    function showFloorList() {

    }

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

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.floor_ids = $scope.floor_ids;

        $http({
            method: 'POST',
            url: '/frontend/hskp/gethskpstatusbyfloor',
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
    };

    $scope.onClickRow = function(row, index) {
        row.collapse = !row.collapse;
        for(var i = 0; i < $scope.datalist.length; i++)
        {
            if( i == index )
                continue;

            $scope.datalist[i].collapse = false;
        }
    }

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

    $scope.onShowFloor = function () {
        console.log($scope.floor_ids);
        getDataList();
    }


});
