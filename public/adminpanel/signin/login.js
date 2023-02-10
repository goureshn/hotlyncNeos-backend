app.controller('SigninFormController', function($scope, $rootScope, $http, $window, $timeout, AuthService, $localStorage,$sessionStorage, Base64) {
  $scope.user = {};
  $scope.user.username = '';
  $scope.user.password = '';

  $scope.authError = null;
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
                        $scope.authError = "Your account has been locked. Please follow the instructions sent in your email or contact your IT department.";
                        $scope.user.username = '';
                        $scope.user.password = '';
                        $scope.user.viewinform = true;
                    
                } else if(lock == 'No') {
                    $scope.login();
                }

            }).catch(function(response) {

            })
            .finally(function() {

            });
    }
    
  $scope.login = function() {
    $scope.authError = null;

    AuthService.Login($scope.user.username, $scope.user.password,0, function (response) {
      if ( response.data.code == '200' ) {
        var permission_bool = false;
        for (var i = 0; i < response.data.user.permission.length; i++) {
          if ('access.backoffice' == response.data.user.permission[i].name) {
            permission_bool = true;
            break;
          }
        }

        //if( response.data.user.job_role != 'IT Admin' && response.data.user.job_role != 'SuperAdmin' )
        if( permission_bool == false && response.data.user.job_role != 'SuperAdmin' )
        {
          $scope.authError = "Login Failed";
          return;
        }
        setLoginInfo(response.data.user);
       //AuthService.SetCredentials(response.data.user);
        $timeout(function() {
            $window.location.href = '/hotlyncBO';
            //$window.location.reload();
        }, 500);        
      }else{
        if( response.data.code == '401') {
          $scope.authError = response.data.message ;
        }else {
          $scope.authError = "Login Failed";
        }
      }
    });

  };

  function setLoginInfo(user) {
    var authdata = Base64.encode(user.id + ':' + user.access_token);

    // $localStorage.$reset();

    $rootScope.globals = {
      currentUser: user,
      authdata: 'Basic ' + authdata
    };

    $http.defaults.headers.common['Authorization'] = $rootScope.globals.authdata; // jshint ignore:line
    //$cookieStore.put('globals', $rootScope.globals);

    try {
      $localStorage.admin = $rootScope.globals;
    } 
    catch(e)
    {
      console.log(e);
    }
     // $sessionStorage.admin = $rootScope.globals;
  }

});
