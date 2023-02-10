app.controller('MytaskController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, GuestService, DateService, AuthService, uiGridConstants) {

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 94) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.gs = GuestService;
    $scope.ds = DateService;
    $scope.auth_svc = AuthService;
});

