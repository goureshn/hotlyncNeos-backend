define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('GuestServiceCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.property_id = 0;
           

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getGuestService('guestservice');
            });

            $scope.getGuestService = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/guestservice',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.minibar) {
                            $scope.displayGuestService(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.displayGuestService = function (data) {

                
                
            }

            $scope.saveGuestService = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
               

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveguestservice',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'guestservice' )
                                $scope.getGuestService('guestservice');
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