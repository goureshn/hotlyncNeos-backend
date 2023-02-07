define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('HelpdeskCtrl', function ($scope, $compile, $timeout, $http  /*$location, $http, initScript */) {                        

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
              
                $scope.getItImapConfig();
            
            });

            $scope.it_imap_config = {};
            
            $scope.saveItImapConfig = function ()
            {   
                var data = angular.copy($scope.it_imap_config);
                data.property_id = $scope.property_id;                

                console.log(data);
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveitimapconfig',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        alert('Helpdesk Imap Config  has been updated successfully');

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
                        $scope.it_imap_config.it_imap_tls = data.it_imap_tls == "1";      
                        $scope.it_imap_config.it_helpdesk_update_notify = data.it_helpdesk_update_notify == "1";            
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


           
        });
    });