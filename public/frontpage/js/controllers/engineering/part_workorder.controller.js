app.controller('PartWorkorderController', function ($scope, $rootScope, $http, $interval, $uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Equipmemnt Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.workorderlist = {};

    $scope.getWokorderlist = function(){
        var request = {};
        request.part_id = $scope.part.id;
        request.property_id = $scope.property_id;
        $http({
            method: 'POST',
            url: '/frontend/equipment/equipmentworkorderlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data.datalist != null) $scope.workorderlist = response.data.datalist;
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {

            });
    }
    $scope.getWokorderlist();

    $scope.$on('part_workorder', function(event, args) {
        $scope.getWokorderlist();
    })
});

