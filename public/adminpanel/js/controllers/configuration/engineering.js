define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('EngCtrl', function ($scope, $compile, $timeout, $http  /*$location, $http, initScript */) {                        
            $http.get('/list/usergroup').success( function(response) {
                $scope.usergroup = response;                 
                $scope.usergroups = [];
                for(var i = 0; i < $scope.usergroup.length ; i++) {
                    var user = {id: $scope.usergroup[i].id, label: $scope.usergroup[i].name};
                    $scope.usergroups.push(user);
                }
            });

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getStockNotifiGroup();
                $scope.getReminderContract();
                $scope.getImapConfig();
                $scope.getItImapConfig();
                $scope.getPreventiveConfig();
                $scope.getRepairRequest();
            });

            $scope.usergroups_hint = {buttonDefaultText: 'Select User Group'};
            $scope.usergroups_hint_setting = {
                smartButtonMaxItems: 3,
                smartButtonTextConverter: function(itemText, originalItem) {
                    return itemText;
                }
            };

            $scope.group_type = [];

            $scope.getStockNotifiGroup = function()
            {
                var data = {};                
                data.property_id =   $scope.property_id;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getstocknotifigroup',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        for(var i =0; i < data.group.length ; i++) {
                            for(var j = 0; j< $scope.usergroups.length ; j++) {
                                if(data.group[i] == $scope.usergroups[j].id) {
                                    var val = {id:$scope.usergroups[j].id };
                                    $scope.group_type.push(val);
                                }
                            }
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.saveStockNotifiGroup = function()
            {
                var data = {};
                data.property_id =   $scope.property_id;
                var allgroup = '';

                for(var i =0; i < $scope.group_type.length ; i++) {
                    allgroup += $scope.group_type[i].id;
                    if((i+1) < $scope.group_type.length) allgroup +=  ",";
                }
                data.group = allgroup;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savestocknotifigroup',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.contract_data = {};
            $scope.contract_data.user_group_tags = [];

            $scope.loadUserGroupFilter = function(query) {                
                return $scope.usergroup.filter(item => item.name.toLowerCase().includes(query.toLowerCase()));
            }

            $scope.saveReminderContract = function ()
            {
                var data = {};
                data.eng_user_group_ids  = $scope.contract_data.user_group_tags.map(item => item.id).join(",");
                data.eng_contract_expire_days  =  $scope.contract_data.eng_contract_expire_days;
                data.property_id =   $scope.property_id;

                console.log(data);
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveremindercontract',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        alert('Reminder Setting of Contract has been updated successfully');

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.getReminderContract = function()
            {
                var data = {};
                data.property_id =   $scope.property_id;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getremindercontract',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {

                        console.log(data);
                        $scope.contract_data.user_group_tags = data.user_group_tags;
                        $scope.contract_data.eng_contract_expire_days = data.eng_contract_expire_days;
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.imap_config = {};

            $scope.saveImapConfig = function ()
            {   
                var data = angular.copy($scope.imap_config);
                data.property_id = $scope.property_id;                

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveimapconfig',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        alert('Imap Config of Repair request has been updated successfully');

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.getImapConfig = function()
            {
                var data = {};
                data.property_id =   $scope.property_id;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getimapconfig',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        console.log(data);
                        $scope.imap_config = data;           
                        $scope.imap_config.eng_imap_tls = data.eng_imap_tls == "1";             
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.it_imap_config = {};
            
            $scope.saveItImapConfig = function ()
            {   
                var data = angular.copy($scope.it_imap_config);
                data.property_id = $scope.property_id;                

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveitimapconfig',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        alert('Helpdesk Imap Config of Repair request has been updated successfully');

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.getItImapConfig = function()
            {
                var data = {};
                data.property_id =   $scope.property_id;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getitimapconfig',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        console.log(data);
                        $scope.it_imap_config = data;           
                        $scope.it_imap_config.eng_imap_tls = data.eng_imap_tls == "1";             
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.repair_request = {};
            $scope.repair_request.user_group_tags = [];
            $scope.repair_request.repair_auth_on = false;
            $scope.repair_request.create_workorder_flag = false;

            $scope.saveRepairRequest = function ()
            {
                var data = {};
                data.repair_request_user_group_ids  = $scope.repair_request.user_group_tags.map(item => item.id).join(",");                
                data.repair_auth_on  = $scope.repair_request.repair_auth_on;                
                data.create_workorder_flag  = $scope.repair_request.create_workorder_flag;                
                data.repair_completed_timeout  = $scope.repair_request.repair_completed_timeout;
                data.repair_request_equipment_status = $scope.repair_request.repair_request_equipment_status;
                data.property_id =   $scope.property_id;

                console.log(data);
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saverepairrequest',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        alert('Repair Request setting has been updated successfully');

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.getRepairRequest = function()
            {
                var data = {};
                data.property_id =   $scope.property_id;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getrepairrequest',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        $scope.repair_request = data;                        
                        $scope.repair_request.repair_auth_on = data.repair_auth_on == "1"; 
                        $scope.repair_request.create_workorder_flag = data.create_workorder_flag == "1";
                        $scope.repair_request.repair_request_equipment_status = data.repair_request_equipment_status == "1";
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.preventive_config = {};

            $scope.days_list = [
                {id: 0, name: 'Sunday'},
                {id: 1, name: 'Monday'},
                {id: 2, name: 'Tuesday'},
                {id: 3, name: 'Wednesday'},
                {id: 4, name: 'Thursday'},
                {id: 5, name: 'Friday'},
                {id: 6, name: 'Saturday'},                
            ];

            $scope.savePreventiveConfig = function ()
            {
                var data = angular.copy($scope.preventive_config);                
                data.property_id =   $scope.property_id;

                console.log(data);
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savepreventive',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        alert('Repair Request setting has been updated successfully');

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.getPreventiveConfig = function()
            {
                var data = {};
                data.property_id =   $scope.property_id;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getpreventive',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        $scope.preventive_config = data;
                        $scope.preventive_config.preventive_week_start = parseInt(data.preventive_week_start);
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }
        });
    });