app.controller('PartDetailController', function ($scope, $rootScope, $http, $uibModal, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Part Detail';

    $scope.email = {};
    $scope.email.property_id = $scope.part.property_id;
    $scope.email.to = $scope.part.maintenance_email;

    $scope.getExternalCompany = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/maintenancelist?name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    $scope.email.to = item.email;
                    return item;
                });
            });
    };
    $scope.getExternalCompany($scope.part.external_maintenance_company);

    $scope.SendEmail = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_email.html',
            controller: 'PartEmailCtrl',
            scope: $scope,
            resolve: {
                name: function () {
                    return $scope.name;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

});

app.controller('PartEmailCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Part';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };


    $scope.sendEmail = function () {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.to = $scope.email.to;
        request.title = $scope.email.title;
        request.content = $scope.email.content;
        if($scope.email.to != '' && $scope.email.to != null) {
            $http({
                method: 'POST',
                url: '/frontend/equipment/sendemail',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function (response) {
                console.log(response);
                toaster.pop('success', MESSAGE_TITLE, ' External maintenance email  has been sent successfully');
                $uibModalInstance.close();

            }).catch(function (response) {
                    // CASE 3: NO Asignee Found on shift : Default Asignee
                })
                .finally(function () {

                });
        }else {
            $scope.email.error = "There is no emmail adress in external maintenance company.";
        }
    }

});


