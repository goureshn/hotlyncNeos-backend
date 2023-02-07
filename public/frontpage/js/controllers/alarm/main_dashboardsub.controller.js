app.controller('AlarmMainDashboardSubController', function ($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster,$interval, $timeout) {
    var MESSAGE_TITLE = 'Alarm Dashboard Main Page';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;

    $scope.targrt_models = {
        selected: null,
        lists: {"key1": [], "key2": [], "key3": [],"key4": [],"key5": [],"key6": [],
                "key7": [], "key8": [], "key9": [],"key10": [],"key11": [],"key12": [],
                "key13": [], "key14": [], "key15": [],"key16": [],"key17": [],"key18": [],
                "key19": [], "key20": [], "key21": [],"key22": [],"key23": [],"key24": []}
    };
    
    function initAlarmTrigger() {
        $scope.trigger1_action = 'IVR' ;
        $scope.percent = '60';        
        $scope.trigger1_time = '60000';
        $scope.trigger1_flag = '0'; //disabe
        $scope.trigger1_duration = 6000;
        $scope.trigger1_loop = 1;
        $scope.trigger2_action = 'IVR';
        $scope.trigger2_time = '60000';
        $scope,trigger2_flag = '0'; //disable
    }
    initAlarmTrigger();

    $scope.dash_id_ = 0;    
    $scope.$on("set_dash_id", function(evt, data){ 
        $scope.dash_id_ = data;
    });

    $scope.$on('alarm_dash_response', function(evt, data) {
        var url = data.acknowledge;  
        $http.get(url)
        .then(function(response){
            console.log(response.data);
        });
    });

    $scope.goActive = function(row) {
        $scope.$emit('get_alarm_active', row);
    };

    var trigger1_stop; 
    var trigger2_stop;
    $scope.onClick=function(alarm_group) {
        if(alarm_group.alarm_id == 0) return;
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/alarm/modal/alarm.html',
            controller: 'AlarmSendController',
            windowClass: 'app-modal-window',
            resolve: {
                alarm_group: function () {
                    return alarm_group;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;                    
        }, function (alarm_item) {            
            $scope.$emit('update_alarm_list');
            //modalInstance.close();
            //check acknowledge status after one minutes.
            if(alarm_item != 'cancel') {
                $scope.trigger1_action = alarm_item.trigger1_action ;
                $scope.percent = alarm_item.percent;        
                $scope.trigger1_time = alarm_item.trigger1_time;
                $scope.trigger1_flag = alarm_item.trigger1_flag; 
                $scope.trigger1_duration = alarm_item.trigger1_duration;
                if($scope.trigger1_duration == 0) $scope.trigger1_duration = 100; 
                $scope.trigger1_loop = alarm_item.trigger1_loop; 
                if($scope.trigger1_loop == 0) $scope.trigger1_loop =1;
                $scope.trigger2_action = alarm_item.trigger2_action;
                $scope.trigger2_time = alarm_item.trigger2_time;
                $scope,trigger2_flag = alarm_item.trigger2_flag;
                if(alarm_item.trigger1_flag == '1') {
                    $timeout(function(){
                        trigger1_stop = $interval(function() {
                            $scope.startTrigger1(alarm_item);
                          }, $scope.trigger1_duration, $scope.trigger1_loop, alarm_item);   

                    },  $scope.trigger1_time);                   
                } 
                if(alarm_item.trigger2_flag == '1') {
                    $timeout(function(){
                        trigger2_stop = $interval(function() {
                            $scope.startTrigger2(alarm_item);
                          }, $scope.trigger1_duration, $scope.trigger1_loop, alarm_item);       
                    },  $scope.trigger2_time);                    
                }
            }
        });
    };
    
    $scope.$on('$destroy', function() {
        $interval.cancel(trigger1_stop);
        $interval.cancel(trigger2_stop);
    });  

    $scope.startTrigger1 = function(alarm) {        
        //get acknowledge and compare percent
        var notifi_id = alarm.notifi_id;
        var action = alarm.trigger1_action;    
        $http.get('/frontend/alarm/setting/getacknow?notifi_id='+notifi_id)
            .then(function (response) {
                notifi = response.data.notifi;
                if(notifi) {
                    if(notifi.percent <  $scope.percent) {
                        //send esacalation
                        alarmEscalate(alarm ,'1');     
                    }
                }
        });
    };

    function alarmEscalate(alarm , cond) {
        var data = {};
        data.notifi_id = alarm.notifi_id;
        data.alarm_id = alarm.alarm_id;        
        var property_id = profile.property_id;
        data.property_id = property_id;
        data.user_id = user_id;
        data.alarm_backcolor = alarm.alarm_backcolor;
        var action = '';
        if(cond == '1') action = alarm.trigger1_action;
        if(cond == '2') action = alarm.trigger2_action;  
        data.notifi_type = action;
        $http({
            method: 'POST',
            url: '/frontend/alarm/dash/sendescalation',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code == 200 ) 
                    toaster.pop('success', MESSAGE_TITLE, 'Alarm message has been sent successfully');
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);                                       
            });
    }

    $scope.startTrigger2 = function(alarm) {        
       //get acknowledge and compare percent
       var notifi_id = alarm.notifi_id;
       var action = alarm.trigger1_action;    
       $http.get('/frontend/alarm/setting/getnotifistatus?notifi_id='+notifi_id)
           .then(function (response) {
               notifi = response.data.notifi;
               if(notifi) {
                   if(notifi.status == 2 || notifi.status == 3 ) {
                       //send esacalation
                       alarmEscalate(alarm , '2');     
                   }
               }
       });
    };

    $scope.getDashAlarms_ = function (dash_id) {
        var request = {};
        request.property_id = property_id;
        request.user_id = user_id;
        request.dash_id = dash_id;
        request.searchtext = $scope.searchtext;
        $rootScope.dash_id = dash_id;        
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getdashalarms',
            data: request,
            async : false,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.alarm_list = response.data.datalist;
                //$scope.target_alarms = response.data.target_alarms; 

            }).catch(function (response) {
                // console.error('Gists error', response.status, response.data);
            });
    }

    $scope.onSearchAlarm = function () {
        var dash_id = $scope.dash_id_;   
        if(dash_id == 0) dash_id = $rootScope.dash_id;   
        $scope.getDashAlarms_(dash_id);
    }
    
});

app.controller('AlarmSendController', function ($scope,$http, $rootScope, $uibModalInstance, toaster,AuthService, alarm_group, $filter) {
    $scope.disable_create=0;
    $scope.alarm_group = alarm_group;
    $scope.alarm_group.trigger1_time = $scope.alarm_group.trigger1_time * 1000 * 60;//1s=1000ms,60s = 1min 
    $scope.alarm_group.trigger1_duration = $scope.alarm_group.trigger1_duration * 1000 * 60;
    $scope.alarm_group.trigger2_time = $scope.alarm_group.trigger2_time * 1000 * 60;
    var MESSAGE_TITLE = 'Alarms';  
    
        var user_list = [];
        $http.get('/list/user')
            .then(function (response) {
                user_list = response.data;
            });

        //$scope.user_name_list =[];
        $scope.loadFiltersUser = function (query) {       
            $scope.wholename = user_list.filter(function (item) {
                if (item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1)
                    return item.wholename;
            });      
            $scope.users = $scope.wholename.map(function (tag) { 
                return tag;
            });
            return $scope.users;
        }

        $scope.userlist = [];
        $http.get("/frontend/alarm/dash/getuserlistofalarm?alarm_id=" + $scope.alarm_group.id)
            .then(function(response){
                console.log(response.data);
                console.log($scope.alarm_group.id);
                $scope.user_name_list = [];
                for(var i = 0; i < response.data.length; i++) {
                    $scope.user_name_list.push(response.data[i]);
                    $scope.userlist.push(response.data[i].id);
                }
                //$scope.users.push({text: response.data[i].username});
                $scope.getNotyfictionType($scope.userlist, $scope.alarm_group.id)

            });
        
        $scope.getNotyfictionType = function(userlist, alarm_id) {
            $http.get("/frontend/alarm/dash/getnotificationtype?alarm_id=" + alarm_id+"&userlist="+userlist)
            .then(function(response){
                console.log(response.data);
                $scope.notification_type =response.data.toString();
            });
        }   
    
        $scope.onSendAlarm = function() {
            $scope.disable_create=1;
            var data = {};
            if( $scope.alarm_group == undefined || $scope.alarm_group.id == undefined ){
                $scope.disable_create=0;
                return;
            }
            data.alarm_id = $scope.alarm_group.id;    
            var profile = AuthService.GetCredentials();
            data.send_user = profile.id;
            data.recv_users = $scope.user_name_list;            
            data.message = $scope.comment;
            data.location = $scope.location;
            if(!$scope.location)  {
                $scope.location_err = "Enter location.";
                $scope.disable_create=0;
                return false;
            }else {
                $scope.location_err = "";
            }
            if(!$scope.comment)  {
                data.message = "";
            }
            var property_id = profile.property_id;
            data.property_id = property_id;
            data.description = $scope.alarm_group.description;
            data.alarm_backcolor = $scope.alarm_group.alarm_backcolor;
            data.notification_type = $scope.notification_type;
            data.kind = $scope.alarm_group.escalation ? 1 : 0;
    
            $http({
                method: 'POST',
                url: '/frontend/alarm/dash/sendalarm',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    if( response.data.code == 200 ) {
                        $scope.disable_create=0;
                        toaster.pop('success', MESSAGE_TITLE, 'Alarm message has been sent successfully');
                    }
                    else
                        toaster.pop('error', MESSAGE_TITLE, response.data.message);                        
                    var notifi_id = response.data.notifi_id; 
                    $scope.alarm_group.notifi_id = notifi_id; 
                    $uibModalInstance.dismiss($scope.alarm_group);
                }).catch(function(response) {
                    $scope.disable_create=0;   
                })
                .finally(function() {
    
                });
        }
  
    $scope.save = function () {
        //$uibModalInstance.close($scope.comment);
        $uibModalInstance.close('cancel');
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});