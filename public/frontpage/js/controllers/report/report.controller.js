app.controller('ReportController', function ($scope, $window, AuthService, $http, $rootScope, blockUI ) {

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
   
   
    $scope.onShowScheduleList = function() {
        var args = [];
        $scope.$broadcast('refresh_list', args);
    }
    

    $scope.indexname = 'callaccount';
    var permission = $rootScope.globals.currentUser.permission;
    for(var i = 0; i < permission.length; i++)
    {
        if( (permission[i].name).indexOf('app.reports.') != -1) {
            var cur_name =permission[i].name;
            var start_length = 'app.reports'.length+1;
            var last_length = cur_name.length;
            var name = permission[i].name.substring(start_length, last_length);
            $scope.indexname = name;
            break;
        }

    }





    $scope.loadContent = function(indexname) {
        $scope.indexname = indexname;
    }

    $scope.$on('$destroy', function() {
        clearDownloadChecker();
    });

    var filter_param = undefined;

    $scope.generateDownloadChecker = function(filter){
        filter_param = filter;
        
        // Block the user interface        
        blockUI.start("Please wait while the report is being generated."); 
    }

    function clearDownloadChecker() {
        // Unblock the user interface
        blockUI.stop(); 
    }

    $scope.$on('pdf_export_finished', function(event, args){
        if( filter_param && args == filter_param.timestamp )
            clearDownloadChecker();
    });
});




