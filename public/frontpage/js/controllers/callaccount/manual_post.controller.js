app.controller('ManualPostController', function($scope, $http ) {
    var MESSAGE_TITLE = 'Manual Post';

    $scope.data = {};
    $scope.data.src_config = {};

    $scope.data.property_id = 4;
    $scope.data.channel_id = 7;

    $scope.data.src_config.src_property_id = 4;
    $scope.data.src_config.src_build_id = 7;
    $scope.data.src_config.src_channel_id = 11;
    $scope.data.src_config.accept_build_id = ["-1"];
    $scope.param = '';

    $scope.submit = function() {
        $http({
            method: 'POST',
            url: '/interface/process/callcharge_manual',
            data: $scope.data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response.data);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

});