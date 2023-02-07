app.controller('FinanceController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService) {
    var MESSAGE_TITLE = 'Finance';
    $scope.flags = 0;

    $scope.auth_svc = AuthService;

    if (navigator.appVersion.indexOf('Trident') === -1) {
        if (navigator.appVersion.indexOf('Edge') != -1) {
            //alert("Edge");
            $scope.flags = 1;
        }
    }
    else {
        //alert("IE 11");
        $scope.flags = 1;
    }
    $scope.indexname = 'adminfinance';
    var permission = $rootScope.globals.currentUser.permission;
    //window.alert(JSON.stringify(permission));
    for (var i = 0; i < permission.length; i++) {
        if ((permission[i].name).indexOf('app.callaccounting.') != -1) {
            var cur_name = permission[i].name;
            var start_length = 'app.callaccounting'.length + 1;
            var last_length = cur_name.length;
            var name = permission[i].name.substring(start_length, last_length);
            //window.alert(name);
            if (name == 'adminfinance' || name == 'mobilefinance')
            {$scope.indexname = name;
            //window.alert($scope.indexname);
            //$scope.loadContent($scope.indexname);
            break;
            }
        }

    }





    $scope.loadContent = function (indexname) {
        $scope.indexname = indexname;
    }
    $scope.loadContent($scope.indexname);

});



