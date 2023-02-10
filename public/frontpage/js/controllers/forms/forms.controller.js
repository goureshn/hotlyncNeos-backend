app.controller('FormsController', function ($scope, $window, AuthService, $http, $rootScope, blockUI ) {

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 108) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.active = 1;
//      var ua = $window.navigator.appName;
     $scope.flags=0;

    $scope.auth_svc = AuthService;
    
    if(navigator.appVersion.indexOf('Trident') === -1)
    {
	    if(navigator.appVersion.indexOf('Edge') != -1)
	    {
         //alert("Edge");
         $scope.flags=1;
         }
     }
    else	
    {
         //alert("IE 11");
         $scope.flags=1;
         }
   
   

    $scope.indexname = 'hotworkpermit';
    var permission = $rootScope.globals.currentUser.permission;
    for(var i = 0; i < permission.length; i++)
    {
        if( (permission[i].name).indexOf('app.forms.') != -1) {
            var cur_name =permission[i].name;
            var start_length = 'app.forms'.length+1;
            var last_length = cur_name.length;
            var name = permission[i].name.substring(start_length, last_length);
            $scope.indexname = name;
            break;
        }

    }

    $scope.loadContent = function(indexname) {
        $scope.indexname = indexname;
    }

   
    
});




