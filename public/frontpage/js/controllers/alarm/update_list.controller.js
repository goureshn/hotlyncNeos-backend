app.controller('AlarmUpdateController', function ($scope, $rootScope, $http, $window, $timeout, toaster, AuthService, $uibModal) {
    $scope.isLoading = false;
    $scope.update_list = [];
 
    $scope.getAlarmUpdate = function(notification_id) {
        //here you could create a query string from tableState

        $scope.isLoading = true;
     
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.notification_id = notification_id;

        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/getalarmupdatelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.update_list = response.data.datalist;
           
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };


});
