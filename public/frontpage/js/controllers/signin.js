'use strict';

/* Controllers */
  // signin controller
app.controller('SigninFormController', function($scope, $rootScope, $http, $state, AuthService) {
    var MESSAGE_TITLE = 'Change Password';
    $scope.user = {};
    $scope.authError = null;
    $scope.user.id = 0;
    $scope.user.override = 0;
    $scope.stay_signin = AuthService.getStaySiginIn();

    if( $rootScope.globals && $rootScope.globals.currentUser )
    {
        var userInform = $rootScope.globals.currentUser;
        if(userInform != null) {
            $scope.user.id = userInform.id;
            $scope.user.username = userInform.username;
            $scope.user.oldpassword = userInform.password;
            $scope.user.prname = userInform.prname;
        }
    }

    $scope.license_info = {};

    $scope.onChangeSignin = function()
    {
        AuthService.setStaySignin($scope.stay_signin);
    }


    function checkLicense() 
    {
        // check license
        $http({
            method: 'POST',
            url: '/hotlync/checklicense',
            data: {},
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.license_info = response.data;
                console.log(response);
            }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    checkLicense();
    

    // already login
    if( AuthService.isAuthenticated() )
    {
        var profile = AuthService.GetCredentials();

        if(profile.status_flag == 0 || profile.compare_flag == 1 && profile.expiry_day < 1 ) {
            AuthService.ClearCredentials();
        }
        else
        {
            var agentstatus = {};
            agentstatus.agent_id = profile.id;
            agentstatus.status = 'Online';
            agentstatus.property_id = profile.property_id;
    
            $http({
                method: 'POST',
                url: '/frontend/call/changestatus',
                data: agentstatus,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    if(profile.status_flag == 0) {
                        home_page = 'access.changepass';
                    }
                    if(profile.compare_flag == 1 && profile.expiry_day < 1 ) {
                        home_page = 'access.changepass';
                    }
                    
                    var home_page = profile.prname;
    
                    if( home_page == undefined ) {
                        home_page = 'app.guestservice.dashboard';
                    }
                    
                    $state.go(home_page);
                }).catch(function(response) {
    
                })
                .finally(function() {
    
                });
        }
    }

    $scope.user.viewinform = false;
    var attempt_time = 0 ;
    var count_time = 0;
    var compare_flag = 0;
    var lock = 'No';
    var password_confirm_day = [];
    $scope.getCpmpare_flag = function() {
        var property_id = '0' ;
        var username = $scope.user.username
        $http.post('/auth/getcompareflag', { property_id: property_id, username: username})
            .then(function (response) {
                compare_flag = response.data.compare_flag;
                lock = response.data.lock;
                password_confirm_day = JSON.parse(response.data.password_expire_confirm_day);
                if(lock == 'Yes') {
                    AuthService.SendPassword($scope.user.username, attempt_time,  function (response) {
                        $scope.authError = "Your account has been locked. Please follow the instructions sent in your email or contact your IT department.";
                        $scope.user.username = '';
                        $scope.user.password = '';
                        $scope.user.viewinform = true;
                    });
                } else if(lock == 'No') {
                    $scope.login();
                }

            }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    //if expiry day equals with property_setting 's value(password_expire_confirm_day), send mail
    $scope.sendExpiryMail = function (username, expiry_day) {
        $http.post('/auth/sendexpirymail', { username: username, expiry_day: expiry_day})
            .then(function (response) {

            }).catch(function(response) {

            })
            .finally(function() {

            });;
    }

    $scope.confirmExpire = function (index) {
        var cur_expireday = 0;
        for(var i = 0; i < password_confirm_day.length ; i++ ) {
           if((i == index) ) {
               cur_expireday = password_confirm_day[index];
           }
        }
        return cur_expireday;
    }

    $scope.login = function() {
      $scope.authError = null;
      if(count_time > 0 && count_time >= attempt_time && compare_flag ==1 ) {
          AuthService.SendPassword($scope.user.username, attempt_time,  function (response) {
              $scope.authError = "Your account has been locked. Please follow the instructions sent in your email or contact your IT department.";
              $scope.user.username = '';
              $scope.user.password = '';
              $scope.user.viewinform = true;
              return;
          });

      }else {
          // Try to login
          AuthService.Login($scope.user.username, $scope.user.password, $scope.user.override, function (response) {
              if (response.data.code == '200') {
                  AuthService.SetCredentials(response.data.user);
                  var home_page = response.data.user.prname;
                  if(response.data.auth.expiry_day == $scope.confirmExpire(1) || response.data.auth.expiry_day == $scope.confirmExpire(0)) {
                      alert('Your expiry day is '+response.data.auth.expiry_day+' day.');
                      //send mail
                      $scope.sendExpiryMail($scope.user.username,response.data.auth.expiry_day );
                  }
                  if (response.data.auth != null && (response.data.auth.expiry_day < $scope.confirmExpire(2))) {
                      alert('Password Expired! Please Set New Password.');
                      home_page = 'access.changepass';
                  } else {
                      if (home_page == undefined)
                          home_page = 'app.guestservice.dashboard';
                      if (response.data.user.status_flag == 0) {
                          alert('Your password is default. Please change to the password of your choice for security.');
                          home_page = 'access.changepass';
                          $state.go(home_page);
                          return;
                      }
                  }
                  $state.go(home_page);
                  $rootScope.$broadcast('success-login', response.data.user);
              } else {
                  if(response.data.code == '-1') {
                      $scope.authError = response.data.message;
                  }
                  else if (response.data.code == '402')
                  {
                      //alert(response.data.message);
                     // var txt;
                      var r = confirm(response.data.message);
                      if (r == true) {
                          $scope.user.override=1;
                          $scope.login();
                      } else {
                        //   txt = "You pressed Cancel!";
                      }
                  }
                  else {
                     
                      $scope.authError = response.data.message;
                      compare_flag = response.data.compare_flag;
                      attempt_time = response.data.attempt_time;
                      count_time++;
                  }
              }
          });
      }
    };
    
    $scope.onChange = function() {
         if($scope.user.newpassword != $scope.user.confirmpassword) {
             $scope.authError = 'New Password not corrrect!';
             return;
         }
        $scope.authError = null;
        //change password
          AuthService.ChangePassword($scope.user.username, $scope.user.id, $scope.user.newpassword, function (response) {
              if ( response.status == '200' ) {
                  userInform.status_flag = 1;
                  userInform.password = $scope.user.newpassword;
                  var home_page = $scope.user.prname;
                  if( home_page == undefined )
                      home_page = 'app.guestservice.dashboard';
                  $state.go(home_page);
                  $rootScope.$broadcast('success-login', userInform);
              }else{
                  $scope.authError = "Your Password no changed.";
              }
          });
    };
  })
;