app.controller('AlarmSettingController', function($scope, $http, $window, $timeout, toaster, AuthService,$uibModal) {
    var MESSAGE_TITLE = 'Alarms Setting';
    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.dash_box_height = $window.innerHeight/2 - 250;
    
    $scope.tabDashClick = function () {
        $scope.$broadcast('reloadDashboard');
    }
});

