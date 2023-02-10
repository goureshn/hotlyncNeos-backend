'use strict';

app.controller('HelpDeskController', function($scope, $http, $window, $location, $interval, $timeout, $stateParams) {
    
    $scope.clicked=0;
    $scope.init = function() {   
        $scope.clicked=0;
    }

$scope.redirect=function(loc)
{
    $scope.clicked=1; 
var url=$location.absUrl();
var res = url.replace("helpdesk", loc);
$window.location.href = res;
}


});