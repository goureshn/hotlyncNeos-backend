define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('HskpCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.property_id = 0;
            $scope.hskp_inspection = false ;

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getHouseKeeping('housekeeping');
            });

            $scope.getHouseKeeping = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/housekeeping',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.housekeeping) {
                            $scope.displayHouseKeeping(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.displayHouseKeeping = function (data) {

               
                $scope.max_turndown_duration = data.housekeeping.max_turndown_duration;
                $scope.turn_down_service = data.housekeeping.turn_down_service;

                if(data.housekeeping.hskp_inspection == 'true') {
                    $scope.hskp_inspection = true ;
                }

                if(data.housekeeping.hskp_inspection == 'false') {
                    $scope.hskp_inspection = false ;
                }
                $scope.hskp_cleaning_time = data.housekeeping.hskp_cleaning_time;
                $scope.housekeeping_dept_id = data.housekeeping.housekeeping_dept_id;
                $scope.supervisor_job_role = data.housekeeping.supervisor_job_role;
               
            }

            $scope.saveHouseKeeping = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
               

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savehousekeeping',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'housekeeping' )
                                $scope.getHouseKeeping('housekeeping');
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

        });
    });