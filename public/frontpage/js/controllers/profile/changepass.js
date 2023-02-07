'use strict';

/* Controllers */
  // signin controller
app.controller('ChangePasswordController', function($scope, $rootScope, $http, $state, AuthService) {
    var MESSAGE_TITLE = 'Change Password';
    $scope.user = {};
    $scope.authError = null;
    $scope.user.id = 0;
    var userInform = $rootScope.globals.currentUser;
    if(userInform != null) {
        $scope.user.id = userInform.id;
        $scope.user.username = userInform.username;
        $scope.user.oldpassword = userInform.password;
        $scope.user.prname = userInform.prname;
    }

    $scope.getPasswordInform = function() {
        var username = $scope.user.username;
        $http.post('/auth/getpassword', { username: username})
            .then(function (response) {

            }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    var profile = AuthService.GetCredentials();
    var compare_flag = profile.compare_flag;
    var minimum_length = 6;
    var password_type = 'None';
    $scope.getCpmpare_flag = function() {
        var property_id = profile.property_id;
        var username = profile.username;
        $http.post('/auth/getcompareflag', { property_id: property_id,username: username})
            .then(function (response) {
                compare_flag = response.data.compare_flag;
                minimum_length = response.data.minimum_length;
                password_type = response.data.password_type;

            }).catch(function(response) {

            })
            .finally(function() {

            });
    }
    $scope.getCpmpare_flag();

    var confirm = 0;
    $scope.onChange = function() {
        var user_id = profile.id;
        var property_id = profile.property_id;
        if(compare_flag == 1 && profile != null ) {
            var data = {};
            data.user_id = user_id;
            data.property_id = property_id;
            data.password = $scope.user.newpassword;
            $http({
                method: 'POST',
                url: '/auth/getpassgroup',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    confirm = response.data.confirm;
                    if( confirm == 1) {
                        $scope.authError = 'You cannot use password previously used!';
                        return;
                    }
                }).catch(function(response) {

                })
                .finally(function() {

                });
        }

        if(!$scope.user.oldpassword) {
            $scope.authError = 'Old password is empty!';
            return;
        }
        if($scope.user.newpassword.length < minimum_length ) {
            $scope.authError = 'Length of password Should be greater than '+minimum_length+'.';
            return;
        }
        var pattern = /^(?=.*[0-9])(?=.*[!@#$%^&*])[a-zA-Z0-9!@#$%^&*]{6,16}$/;
        if(password_type != 'None' && password_type == 'Alphanumeric') {
            pattern = /[0-9]/;
            if(!pattern.test($scope.user.newpassword)) {
                $scope.authError = "Password Should be contain number (0-9)!";
                focus('password');
                return ;
            }
            pattern = /[a-z]/;
            if(!pattern.test($scope.user.newpassword)) {
                $scope.authError = "Password Should be contain alpha (a-z)!";
                return false;
            }
        }
        if(password_type != 'None' && password_type == 'Alphanumeric_Special'){
            pattern = /[0-9]/;
            if(!pattern.test($scope.user.newpassword)) {
                $scope.authError = "Password Should be contain number (0-9)!";
                focus('password');
                return ;
            }

            pattern = /[a-z]/;
            if(!pattern.test($scope.user.newpassword)) {
                $scope.authError = "Password Should be contain alpha (a-z)!";
                return false;
            }
            pattern = /[!@#$%^&*]/;
            if(!pattern.test($scope.user.newpassword)) {
                $scope.authError = "Password Should be contain special char (!@#$%^&*)!";
                return false;
            }
        }
         if($scope.user.newpassword != $scope.user.confirmpassword) {
             $scope.authError = 'New password is not matched to confirm password!';
             return;
         }
        $scope.authError = null;
        AuthService.ChangePassword($scope.user.username, $scope.user.id, $scope.user.newpassword, $scope.user.oldpassword, property_id,  function (response) {
            if( response.data.code == 200 )
            {
                userInform.status_flag = 1;
                userInform.password = $scope.user.newpassword;
                var home_page = $scope.user.prname;
                if( home_page == undefined )
                    home_page = 'app.guestservice.dashboard';
                $state.go(home_page);
                $rootScope.$broadcast('success-login', userInform);
            }
            else
            {
                $scope.authError = response.data.message;                
            }
        });
    };
});