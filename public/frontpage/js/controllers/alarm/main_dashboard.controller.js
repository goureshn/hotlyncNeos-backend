app.controller('AlarmMainDashboardController', function ($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Alarm Dashboard Main Page';
    $scope.full_height = 'height: ' + ($window.innerHeight - 110) + 'px; overflow-y: auto';
    $scope.full_main_height = 'height: ' + ($window.innerHeight - 90) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto';

    setTimeout(function () {
        $scope.back_color = 'background:#141414;';
    }, 100);

    $scope.active = 1;
    $scope.flags=0;
    $scope.indexname = '';
    $scope.loadContent = function(indexname , cond) {
        $scope.indexname = indexname;
        if(cond == 0) {
            if(indexname == 'active') {
                $scope.getAlarmActive();
            }
            if(indexname == 'alarm_log'){
                $scope.getAlarmLog();
            }
        }
        if(cond != 0) {
            $scope.getDashAlarms(cond);
        }
    }

    if(navigator.appVersion.indexOf('Trident') === -1)
    {
	    if(navigator.appVersion.indexOf('Edge') != -1)
	    {        
         $scope.flags=1;
         }
     }
    else	
    {
         //alert("IE 11");
         $scope.flags=1;
         }

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;

    function initData() {
        $scope.active_number = 0;
        $scope.dash_id = 0;        
        $scope.dash_list = [];
        $scope.dash_name_list = [];
        $scope.alarm_list = [];
        getUserDashBoard();                
    }

    initData();

    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;
    }
    
    function getUserDashBoard() {
        var request = {};
        request.property_id = property_id;
        request.user_id = user_id;
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getuserdash',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.dash_name_list = response.data.datalist;
                $scope.tab_index = $scope.dash_name_list.length;
                $scope.dash_id = response.data.datalist[0].id;
                $rootScope.dash_id = response.data.datalist[0].id;
                $scope.getDashAlarms($scope.dash_id);
            }).catch(function (response) {
                // console.error('Gists error', response.status, response.data);
            });
    }

    $scope.getDashAlarms = function (dash_id) {
        var request = {};
        request.property_id = property_id;
        request.user_id = user_id;
        request.dash_id = dash_id;
        request.searchtext = $scope.searchtext;
        $scope.dash_id = dash_id;   
        $scope.$broadcast('set_dash_id' , dash_id);     
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getdashalarms',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.alarm_list = response.data.datalist;  
                //$scope.target_alarms = response.data.target_alarms;              
            }).catch(function (response) {
                // console.error('Gists error', response.status, response.data);
            });
    }

    $scope.$on("update_alarm_list", function(evt, data){
        $scope.getDashAlarms($scope.dash_id);
    });

    $scope.getAlarmActive = function() {
        $scope.active_number = 100;
        $scope.$broadcast('get_alarm_active'); 
    }

    $scope.getAlarmUpdate = function() {
        $scope.active_number = 101;
        $scope.$broadcast('get_alarm_update'); 
    }

    $scope.getAlarmLog = function() {
        $scope.active_number = 102;
        $scope.$broadcast('get_alarm_log'); 
    }

});

