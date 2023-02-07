app.controller('ValetDetailController', function ($scope, $rootScope, $http, $uibModal, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Valet Detail';

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    $scope.part_group = $scope.valet.part_group;
    $scope.getValetDetail = function () {
        var request = {};

        request.valet_id = $scope.valet.id;
        request.property_id = property_id;
        var url = '/frontend/valet/getvaletdetail';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.valet = response.data.datalist[0];
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }
    //$scope.getvaletDetail();

   
    $scope.UpdateValet = function(){
        var data = angular.copy($scope.valet);
        $http({
            method: 'POST',
            url: '/frontend/valet/updatevalet',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                //toaster.pop('success', MESSAGE_TITLE, 'Part has been updated successfully');
                $scope.pageChanged();
            }).catch(function(response) {
                //toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    $scope.changeStatus = function(val) {
        switch (val) {
            case 'Pending':
                $scope.pendingcolor='btn-danger';
                $scope.progresscolor='btn-grey';
                $scope.holdcolor='btn-grey';
                $scope.completcolor='btn-grey';
                break;
            case 'In Progress':
                $scope.pendingcolor='btn-grey';
                $scope.progresscolor='btn-progres';
                $scope.holdcolor='btn-grey';
                $scope.completcolor='btn-grey';
                break;
            case 'On Hold':
                $scope.pendingcolor='btn-grey';
                $scope.progresscolor='btn-grey';
                $scope.holdcolor='btn-hold';
                $scope.completcolor='btn-grey';
                break;
            case 'Completed':
                $scope.pendingcolor='btn-grey';
                $scope.progresscolor='btn-grey';
                $scope.holdcolor='btn-grey';
                $scope.completcolor='btn-success';
                break;

        }
        $scope.valet.status = val;
    }

    $scope.changeStatus($scope.valet.status);

    $scope.confirmChangeStatus = function(status) {
        var curretnstatus = $scope.valet.status;
        if(status == 'Pending') {
            if( curretnstatus == 'In Progress' || curretnstatus == 'On Hold' || curretnstatus == 'Completed'  ) return false;
            else false;
        }
        if(status == 'In Progress') {
            if( curretnstatus == 'Pending' || curretnstatus == 'On Hold' ) return true;
            else return false;
        }
        if(status == 'On Hold') {
            if( curretnstatus == 'In Progress' ) return true;
            else return false;
        }
        if(status == 'Completed') {
            if( curretnstatus == 'In Progress' || curretnstatus == 'On Hold') return true;
            else return false;
        }

        return false;

    }
    $scope.changeStatusValet= function(val) {
        if($scope.confirmChangeStatus(val) == true) {
            $scope.changeStatus(val);
            $scope.UpdateValet();
        }
    }

    $scope.$watch('valet.status', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;
        $scope.changeStatus(newValue);
    });


});
