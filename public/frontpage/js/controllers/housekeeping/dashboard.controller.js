app.controller('HousekeepDashboardController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, AuthService, toaster) {
    var MESSAGE_TITLE = 'Guest Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';

    var chart_height = ($window.innerHeight + 300) / 2;

    $scope.table_body_style = {
        "height": (chart_height - 160) + 'px'
    };

    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;
        if( fullmode == true ) {
            $scope.chart_height = ($window.innerHeight + 1600) / 2;
        }
        else
        {
            $scope.chart_height = ($window.innerHeight + 200) / 2;
        }
    }
    

    $scope.loading = false;
    $scope.by_status = [{label: "Occupied Clean", data: 20}, {label: "Occupied Dirty", data: 40}, {label: "Vacant Clean", data: 70},
                        {label: "Vacant Dirty", data: 10}, {label: "Occupied Inspected", data: 30}, {label: "Vacant Inspected", data: 45},    ];
    $scope.by_user = [{label: "Rino Iype", data: 25}, {label: "Sushant Shinde", data: 19}, {label: "Justin Lee", data: 15},
                      {label: "Sebastian D", data: 12}, {label: "Kate Shores", data: 10}, {label: "Camille Dantes", data: 8}];

    $scope.$watch('dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getHskpStatistics();
    });

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getHskpStatistics();
    });

    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

  //  $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.daterange = $scope.dateRangeOption.endDate;

    $scope.filter = {};

    $scope.getHskpStatistics = function() {
        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.dateFilter;

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
                $scope.filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
                $scope.filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
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
            url: '/frontend/hskp/statistics',
            data: $scope.filter,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            $scope.total = response.data.total;
            $scope.showHskpStatistics(response.data);
        }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.showHskpStatistics = function(data)
    {
        $scope.by_status = [];
        var by_status = data.by_status_count;
        for(var i = 0; i < by_status.length; i++)
            $scope.by_status.push({label: by_status[i].status, data: by_status[i].cnt});

        $scope.by_user = [];
        var by_user = data.by_user_count;
        for(var i = 0; i < by_user.length; i++)
            $scope.by_user.push({label: by_user[i].wholename, data: by_user[i].cnt});
    }

    $scope.getHskpStatistics();

    $scope.$on('hskp_status_event', function(event, args){

        $scope.getHskpStatistics();
        console.log("Auto Updating on dashboard of housekeeping");
    });
    //$rootScope.$broadcast('hskp_change');
});
