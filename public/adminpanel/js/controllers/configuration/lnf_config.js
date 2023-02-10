define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('LnfConfigCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.property_id = 0;
            $scope.config = {};
            $scope.config.found_user_group_ids = [];
            $scope.config.inquiry_user_group_ids = [];

            user_group_list = [];

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                getUserGroupList($scope.property_id);
                getConfigValue('lnf');
            });

            function getUserGroupList(property_id) {
                $http.get('/list/usergroup?property_id='+property_id).success( function(response) {                    
                    user_group_list = response;                    
                });
            }

            function getConfigValue(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/config',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {                        
                        displayConfigValue(data);                        
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            function displayConfigValue(data) {
                $scope.config = angular.copy(data);                
            }

            $scope.onFoundUserGroupChanged = function() {
                saveConfigValue('found_user_group_ids');
            }

            $scope.onInquiryUserGroupChanged = function() {
                saveConfigValue('inquiry_user_group_ids');
            }

            function saveConfigValue(fieldname) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = $scope.config[fieldname].map(function(item) {
                    return item.id;
                }).join(",");
               
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveconfig',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.code == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';                            
                        }
                        else {
                            $scope.message = " Error: Can't connection databse.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

            $scope.loadFiltersValue = function(query) {	    
                return user_group_list.filter(item =>
                    item.name.toLowerCase().indexOf(query.toLowerCase()) != -1                            
                );
            }

        });
    });