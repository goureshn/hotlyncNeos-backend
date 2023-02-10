app.controller('LoginModalController', function ($scope, $http, $uibModal, $uibModalInstance, toaster, client_id) {
    var MESSAGE_TITLE = 'Login Window';

    $scope.model = {};

    $scope.onLogin = function()
    {   
        var data = angular.copy($scope.model);
        data.client_id = client_id;
        
        $http({
            method: 'POST',
            url: '/frontend/auth/checkuser',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                
                if( response.data.code == 200 )
                {
                    $uibModalInstance.close(response.data);
                }
                else
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                }
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Check User!');
            })
            .finally(function() {
            });
    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }



});

