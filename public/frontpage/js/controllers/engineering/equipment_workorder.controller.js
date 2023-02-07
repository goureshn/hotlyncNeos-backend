app.controller('EquipmentWorkorderController', function ($scope, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Equipment Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.workorderlist = {};
   
    $scope.getWorkorderlist = function(){
        var request = {};
        request.equipment_id = $scope.equipment.id;
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

    $scope.getWorkorderlist();

    $scope.$on('equipment_workorder', function(event, args) {
        $scope.getWorkorderlist();
    });

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'WO00000';
        return sprintf('WO%05d', ticket.id);
    }

});

