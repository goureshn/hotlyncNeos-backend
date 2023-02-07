define(['app', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('LocalTunnelCtrl', function ($scope, $compile, $timeout, $http, $localStorage,$sessionStorage) {
           
            $scope.app_pin = "";
            $scope.msg = {};
            function initData() {
                var request = {};

                var property_id = $sessionStorage.admin.currentUser.property_id;
                request.property_id = property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getpinsetting',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {

                        $scope.app_pin = response.data.app_pin;
                        $scope.central_server_domain = response.data.central_server_domain;
                        $scope.central_port = response.data.central_port;

                    }).catch(function(response) {

                    })
                    .finally(function() {
                    });
            }

            initData();
            
            $scope.onUpdateApp = function()
            {

                var fd = new FormData();
                fd.append('app_pin', $scope.app_pin);
            
                $http.post('/backoffice/configuration/wizard/updateapppin', fd, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined}
                })
                .success(function(response){
                    console.log(response);     
                    if( response.code != 200 )
                    {
                        $scope.msg.success_message = '';
                        $scope.msg.error_message = response.message;
                    }
                    else                   
                    {
                        $scope.msg.success_message = 'App Pin is updated successfully.';
                        $scope.msg.error_message = '';
                    }
                })        
                .error(function(data, status, headers, config){
                    $scope.msg.error_message = status;
                });         
            }   

            $scope.tunnelClientStart = function(){
                var fd = new FormData();
                fd.append('app_pin', $scope.app_pin);
                fd.append('central_server_domain', $scope.central_server_domain);
                fd.append('central_port', $scope.central_port);
                $http.post('/backoffice/configuration/wizard/tunnelClientStart', fd, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined}
                })
                    .success(function(response){
                        console.log(response);
                        if( response.code != 200 )
                        {
                            $scope.msg.success_message_tunnel = '';
                            $scope.msg.error_message_tunnel = response.message;
                        }
                        else
                        {
                            $scope.msg.success_message = 'Localtunnel Client successfully.';
                            $scope.msg.error_message_tunnel = '';
                        }
                    })
                    .error(function(data, status, headers, config){
                        $scope.msg.error_message_tunnel = status;
                    });
            }

        });
    });