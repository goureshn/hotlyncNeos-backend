app.controller('LiveCallDataController', function ($scope, AuthService, $window) {
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 145) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';
    $scope.index=0;
    $scope.guest_call = AuthService.isValidModule('app.callaccounting.guest_call');
    $scope.admin_call = AuthService.isValidModule('app.callaccounting.admin_call');
    $scope.bc_calls = AuthService.isValidModule('app.callaccounting.bc_call');
    $scope.manual_post = AuthService.isValidModule('app.callaccounting.manual_post'); 
    $scope.guest = AuthService.isValidModule('app.callaccounting.guest_call') ? (1) : $scope.index;
    $scope.admin = AuthService.isValidModule('app.callaccounting.admin_call') ? ($scope.guest_call + 1) : $scope.index;
    $scope.bc_call = AuthService.isValidModule('app.callaccounting.bc_call') ? ($scope.guest_call + $scope.admin_call + 1) : $scope.index;
    $scope.manual = AuthService.isValidModule('app.callaccounting.manual_post') ? ($scope.guest_call + $scope.admin_call + $scope.bc_calls + 1) : $scope.index;
    
});

