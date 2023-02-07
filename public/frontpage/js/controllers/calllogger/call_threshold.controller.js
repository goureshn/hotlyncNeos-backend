app.controller('CallThresholdController', function ($scope, $rootScope, $http, $interval, AuthService, toaster, $uibModal) {        
    $scope.setting = {};
    function getCallCenter()
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        var url = '/frontend/call/threshold';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.setting = response.data;
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }

    getCallCenter();

    $scope.saveCallCenter = function(key)
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.key = key;
        request.value = $scope.setting[key];
        
        var url = '/frontend/call/savethreshold';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }
});


