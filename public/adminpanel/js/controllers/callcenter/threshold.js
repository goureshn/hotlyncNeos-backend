define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ThresholdCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.property_id = 0;

            $scope.time_unit_list = [
                'Day', 
                'Hour',
            ];

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getCallCenter('call_center');
            });

            $scope.getCallCenter = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/callcenter',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {                        
                        $scope.displayCallCenter(data);                        
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.displayCallCenter = function (data) {
                $scope.call_center = angular.copy(data);                
                $scope.call_center.call_enter_threshold_flag = data.call_enter_threshold_flag == "1";
            }

            $scope.saveCallCenter = function(fieldname) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = $scope.call_center[fieldname];                
               
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savecallcenter',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.code == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'call_center' )
                                $scope.getCallCenter('call_center');
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