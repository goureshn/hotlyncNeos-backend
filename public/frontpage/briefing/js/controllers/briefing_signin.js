'use strict';

/* Controllers */
  // signin controller
app.controller('SigninFormController', function($scope, $rootScope, $http, $state, AuthService) {
    var MESSAGE_TITLE = 'Change Password';
    $scope.user = {};
    $scope.authError = null;
    $scope.user.id = 0;

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

    // already login
    if( AuthService.isAuthenticated() && AuthService.isValidModule('app.complaint.briefing_view'))
    {
      var profile = AuthService.GetCredentials();
      var request = {};
      request.property_id = profile.property_id;
      request.user_id = profile.id;

      $http({
          method: 'POST',
          url: '/frontend/complaint/currentbriefing',
          data: request,
          headers: {'Content-Type': 'application/json; charset=utf-8'}
      })
          .then(function(response) {
              if( response.data.code == 200 )
              {
                  $state.go('app.complaint.briefing_view');
              }

          }).catch(function(response) {
             // console.error('Gists error', response.status, response.data);
          })
          .finally(function() {
              
          });

    }
    else
      AuthService.ClearCredentials();


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
          var request = {};
          request.username = $scope.user.username;
          request.password = $scope.user.password;
          request.user_type = 'briefing';

          $http.post('/auth/login', request)
              .then(function (response) {
                  if (response.data.code == '200') {
                    AuthService.SetCredentials(response.data.user);
                    var home_page = 'app.complaint.briefing_view';
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
                            alert('You are first login.You should change password for security.');
                            home_page = 'access.changepass';
                        }
                    }
                    $state.go(home_page);
                    $rootScope.$broadcast('success-login', response.data.user);
                } 
                else if( response.data.code = 201 )
                {
                  $scope.authError = response.data.message;                  
                }
                else {
                    $scope.authError = response.data.message;
                    compare_flag = response.data.compare_flag;
                    attempt_time = response.data.attempt_time;
                    count_time++;

                }
              }).catch(function(response) {
                  
              })
              .finally(function() {

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