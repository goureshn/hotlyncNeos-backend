app.controller('ValetUpdateController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, GuestService, AuthService, DateService, uiGridConstants) {
    var MESSAGE_TITLE = 'Valet Update';

    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = 'height: ' + ($window.innerHeight - 115) + 'px; overflow-y: auto;';

    $scope.gs = GuestService;
    $scope.sendbutton = 'Post Create';

    $scope.getTicketDescription = function(ticket){
        var substr = "";
        var length = 48;
        if(!ticket)
            substr = 'This data is empty';
        if(ticket.length > length) substr = ticket.substr(0,length)+"...";
        else substr = ticket;
        return substr;
    }

    $scope.ticketlist = [];

    $scope.getValetHistory = function(preserve) {
        $scope.ticketlist = [];
        var request = {};
        request.valet_id = $scope.valet.id;

        var url = '/frontend/valet/getvalethistorylist';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.datalist;
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.getValetHistory();
    
    $scope.history_details = {};
    $scope.historydetail_view = '';

    $scope.onSelectHistoryDetail = function(row) {
        $scope.history_details = angular.copy(row);
        $scope.sendbutton = 'Post Update';
        if(row.status != 'Custom' ) // updated data from valet detail page
            $scope.historydetail_view = 'true';
        else {
            //manualy updated data from this page
            $scope.historydetail_view = '';
        }
    }
    
    $scope.onUpdateHistoryDetail = function() {
        var request = {};
        if($scope.history_details.id > 0) {
            request = angular.copy($scope.history_details);
            request.description = $scope.history_details.description;
        }else{
            var profile = AuthService.GetCredentials();
            request.valet_id = $scope.valet.id;
            request.user_id = profile.id;
            request.description = $scope.history_details.description;
            request.status = 'Custom';
        }
        $http({
            method: 'POST',
            url: '/frontend/valet/updatevalethistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            toaster.pop('success', MESSAGE_TITLE, ' Valet has been deleted successfully');
            $scope.history_details = {};
            $scope.getValetHistory();
        }).catch(function (response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function () {
    
            });        
    }

    $scope.onCancelHistoryDetail = function(){
        $scope.history_details = {};
        $scope.sendbutton = 'Post Create';
    }
    
    $scope.onDeleteHistoryDetail = function (row) {
        if(row.valet_id > 0) {
            var modalInstance = $uibModal.open({
                templateUrl: 'valet_history_delete.html',
                controller: 'ValetHistoryDeleteCtrl',
                scope: $scope,
                resolve: {
                    row: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {

            });
        }
    }

});

app.controller('ValetHistoryDeleteCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster , row) {
    var MESSAGE_TITLE = 'Valet History ';
    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();
        var request = {};
        request = angular.copy(row);
        $http({
            method: 'POST',
            url: '/frontend/valet/deletevalethistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.history_details = {};
            toaster.pop('success', MESSAGE_TITLE, ' Valet History has been deleted successfully');
            $uibModalInstance.close();
            $scope.getValetHistory();
        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
    }
});
