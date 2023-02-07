define(['app', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('MobileCtrl', function ($scope, $compile, $timeout, $http, $localStorage,$sessionStorage) {
           
            $scope.apk_file = null;    
            $scope.app_version = '';
            $scope.app_base_url = '';
            $scope.app_pin = '';

            function initData() {
                var request = {};

                var property_id = $sessionStorage.admin.currentUser.property_id;
                request.property_id = property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getmobilesetting',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
                        $scope.app_base_url = response.data.mobile_app_url;
                        $scope.app_version = response.data.mobile_app_version;
                        $scope.app_pin = response.data.app_pin;
                    }).catch(function(response) {

                    })
                    .finally(function() {
                    });
            }

            initData();

            $('.noSpace').keyup(function() {
                this.value = this.value.replace(/\s/g,'');
               });
            
            $scope.onUpdateApp = function()
            {
                var property_id = $sessionStorage.admin.currentUser.property_id;

                var fd = new FormData();
                fd.append('myfile', $scope.apk_file);
                fd.append('property_id', property_id);
                fd.append('app_version', $scope.app_version);
                fd.append('app_base_url', $scope.app_base_url);
            
                $http.post('/backoffice/configuration/wizard/updatemobileapp', fd, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined}
                })
                .success(function(response){
                    console.log(response);     
                    if( response.code != 200 )
                    {
                        $scope.success_message = '';
                        $scope.error_message = response.message;
                    }
                    else                   
                    {
                        $scope.success_message = 'Mobile app is updated successfully.';
                        $scope.error_message = '';
                    }
                })        
                .error(function(data, status, headers, config){
                    $scope.error_message = status;
                });         
            }   

            $scope.savePIN = function(value) {
                var data= {};
               
                data.app_pin = value;
        
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/updateapppin',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (response) {
                        if( response.code == 200 ) {
                            $scope.message = 'The App PIN was kept in database successfully.';
                           
                                initData();
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log();
                    });

            }

        });
    });