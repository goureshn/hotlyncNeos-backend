'use strict';

/* Controllers */
// signin controller
app.controller('ForgotFormController', function($scope, $window,$rootScope, $http, $state, $location, AuthService) {
    var MESSAGE_TITLE = 'Forgot Password';
    $scope.user = {};
    $scope.authError = '';
    $scope.user.id = 0;

    $scope.gotoSignPage = function () {
        $location.path('access.signin');
       
    }
    $scope.sendMessage = function () {

      if($scope.user.username == null) {
          $scope.authError = 'Please enter Username.';
          return;
      }
        $http.post('/auth/forgotsendpassword', {username: $scope.user.username})
            .then(function (response) {
                $scope.message = response.data.message;
               if(response.data == '401') {
                   $scope.authError = 'The username you have entered is invalid. Kindly contact IT Department to verify your username.';
                   return;
               }else if($scope.message == '200') {
                   $scope.gotoSignPage();
                   $scope.email = response.data.user_email;
                   $window.alert('We sent an email to '+ $scope.email + ' with a link to get back into your account.')
                  // $scope.authError = 'Please check your email to use new password.';
               }

            }).catch(function (response) {

            })
            .finally(function () {

            });
    }
})
;