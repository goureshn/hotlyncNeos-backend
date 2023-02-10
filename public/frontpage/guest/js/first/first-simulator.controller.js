'use strict';

app.controller('FirstSimulatorController', function($scope, $rootScope, $http, $interval, $stateParams,$state, $window, $timeout, toaster, AuthService, socket) {
    var MESSAGE_TITLE = 'First Page';

    $scope.phonenumber = '';

    $scope.onGotoChat = function() {

        localStorage.setItem('simulatorPhoneNumber', $scope.phonenumber);
        $state.go('app.chat-simulator', {phonenumber:$scope.phonenumber});
    };
});
