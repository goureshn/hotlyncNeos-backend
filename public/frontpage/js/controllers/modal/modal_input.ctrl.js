'use strict';

/* Controllers */
app.controller('ModalInputCtrl', function($scope, $rootScope, $uibModalInstance, title, min_length) {
    $scope.data = {};

    $scope.title = title;
    $scope.data.comment = '';
    $scope.min_length = 0;
    if( min_length > 0 )
        $scope.min_length = min_length;

    $scope.save = function () {
        $uibModalInstance.close($scope.data.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});