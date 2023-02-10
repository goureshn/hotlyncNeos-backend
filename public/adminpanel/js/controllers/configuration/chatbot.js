define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('ChatbotCtrl', function ($scope, $compile, $timeout, $http) {
            $scope.property_id = -1;

            $scope.chatbot_limit_time = 0;

            function getChatbotSettingInfo() {
                let request = {};
                request.property_id = $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getchatbotsettinginfo',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.chatbot_limit_time = data.chatbot_limit_time;
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                getChatbotSettingInfo();
            });

            $scope.saveChatbotSetting = function (item, val) {
                let request = {};
                request.property_id = $scope.property_id;
                request.fieldname = item;
                request.fieldvalue = val;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savechatbotsettinginfo',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            $timeout(function () {
                                $scope.message = '';
                            }, 3000);
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            };
        });
    });
