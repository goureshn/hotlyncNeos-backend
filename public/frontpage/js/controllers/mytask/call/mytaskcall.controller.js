app.controller('MytaskCallController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService, uiGridConstants) {
    var MESSAGE_TITLE = 'My Task';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.tab_content_height = 'height: ' + ($window.innerHeight - 145) + 'px; overflow-y: auto';

    var request = {};

    var profile = AuthService.GetCredentials();
    request.agent_id = profile.id;

    $scope.notify_count = 0;
    $scope.datalist = [];

    function refreshNotify() {
        $http({
            method: 'POST',
            url: '/frontend/callaccount/approvalnotifylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.notify_count = 0;
                $scope.datalist = response.data;
                for(var i = 0; i < $scope.datalist.length; i++) {
                    $scope.notify_count += $scope.datalist[i].cnt;
                }


            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    refreshNotify();

    $scope.$on('refreshCall', function(event, args){
        refreshNotify();
    });

    $scope.onClickName = function(user) {
        $scope.menu = 'Approval';
        $scope.$broadcast('onSelectUser', user);
    }

});




