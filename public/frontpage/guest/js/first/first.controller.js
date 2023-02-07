'use strict';

app.controller('FirstController', function($scope, $rootScope, $http, $interval, $stateParams,$state, $window, $timeout, toaster, AuthService, socket) {
    var MESSAGE_TITLE = 'First Page';

    var property_id = $stateParams.property_id;
    $scope.property = {};

    $http.get('/guest/roomlist?property_id='+property_id)
        .then(function(response) {
            $scope.property = response.data.property;
        });

});