define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('AutoWakeupCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.duty_manager_notify_check = false;
            $scope.awu_record_flag_check = false;
            $scope.property_id = 0;

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getWakeupSetting('wakeup');
            });

            $scope.device_list = [];

            //edit permission check
            var permission = $scope.globals.currentUser.permission;
            $scope.edit_flag = 0;
            for(var i = 0; i < permission.length; i++)
            {
                if( permission[i].name == "access.superadmin" ) {
                    $scope.edit_flag = 1;
                    break;
                }
            }
            //end///
            $scope.getWakeupSetting = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/wakeup',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.wakeup) {
                            $scope.displayWakeupSetting(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.duty_manager_devices = {};
            $scope.duty_managers = {};
            $scope.displayWakeupSetting = function (data) {
                if( data.wakeup.duty_manager_notify == 'YES') {
                    $scope.duty_manager_notify_check = true;
                }else if(data.wakeup.duty_manager_notify == 'NO') {
                    $scope.duty_manager_notify_check = false;
                }
                $scope.duty_manager_devices = data.wakeup.duty_manager_device.split("|");
                $scope.duty_managers = data.wakeup.duty_manager.split("|");
                $scope.duty_manager_device =  $scope.duty_manager_devices[0];
                $scope.device_list = [];
                $scope.device_list.push($scope.duty_manager_devices[0]);
                $scope.device_list.push($scope.duty_manager_devices[1]);
                $scope.duty_manager =  $scope.duty_managers[0];
                $scope.awu_retry_attemps =  data.wakeup.awu_retry_attemps;
                $scope.awu_retry_mins =  data.wakeup.awu_retry_mins;
                $scope.awu_max_snooze =  data.wakeup.awu_max_snooze;
                $scope.snooze_time =  data.wakeup.snooze_time;
                $scope.inprogress_max_wait =  data.wakeup.inprogress_max_wait;
                if(data.wakeup.awu_record_flag == 'ON') {
                    $scope.awu_record_flag_check = true;
                }else if(data.wakeup.awu_record_flag == 'OFF') {
                    $scope.awu_record_flag_check = false;
                }
            }

            $scope.saveWakeup = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
                if($scope.duty_manager_device =="email" && fieldname == "duty_manager" ) {
                    var pattern = /^(([^<>()\[\]\.,;:\s@\"]+(\.[^<>()\[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/ ;
                    if(!pattern.test($scope.duty_manager)) {
                        $scope.message = " Error: Please enter correct email format.";
                        return;
                    }else {
                        data.fieldvalue = $scope.duty_manager+"|"+$scope.duty_managers[1];
                    }
                }
                if($scope.duty_manager_device =="mobile" && fieldname == "duty_manager" ) {
                    var pattern = /^\+?\d{10,13}$/;
                    if(!pattern.test($scope.duty_manager)) {
                        $scope.message = " Error: Please enter correct mobile format.";
                        return;
                    }else {
                        data.fieldvalue = $scope.duty_managers[0]+"|"+$scope.duty_manager;
                    }
                }

                if(fieldname == 'awu_retry_attemps' || fieldname == 'awu_max_snooze'){
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 0 && parseInt(value) <= 10) {
                    }else{
                        $scope.message = " Error: Please enter correct number format. Can't save this one.";
                        return;
                    }
                }

                if(fieldname == 'awu_retry_mins' || fieldname == 'snooze_time' || fieldname == 'inprogress_max_wait'){
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&&parseInt(value) >= 1 && parseInt(value) <= 300) {
                        if(fieldname == 'inprogress_max_wait' && parseInt(value) % 60 != 0)
                        {
                            $scope.message = " Error: Please enter correct seconds. Can't save this one.";
                            return;    
                        }
                        else
                            $scope.message = "";
                    }else{
                        $scope.message = " Error: Please enter correct number format. Can't save this one.";
                        return;
                    }
                }

                if(fieldname == 'duty_manager_device') {
                    if($scope.duty_manager_device == 'email')  $scope.duty_manager = $scope.duty_managers[0];
                    if($scope.duty_manager_device == 'mobile')  $scope.duty_manager = $scope.duty_managers[1];
                    return;
                }

                if(fieldname == 'duty_manager_notify') {
                    if($scope.duty_manager_notify_check == true) {
                        data.fieldvalue = "NO";
                    }else {
                        data.fieldvalue = "YES";
                    }
                }
                if(fieldname == 'awu_record_flag') {
                    if($scope.awu_record_flag_check == true) {
                        data.fieldvalue = "OFF";
                    }else{
                        data.fieldvalue = "ON";
                    }
                }
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savegeneral',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'wakeup' )
                                $scope.getWakeupSetting('wakeup');
                        }
                        else {
                            $scope.message = " Error: Can't connection databse.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

        });
    });