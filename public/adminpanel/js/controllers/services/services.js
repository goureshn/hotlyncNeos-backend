define(['app',  'directives/directive'],
    function (app) {
        app.controller('ServicesCtrl', function ($scope, $compile, $timeout,$interval, $http, $localStorage,$sessionStorage,socket) {

            $scope.setting = {};
            $scope.setting.liveserver_connected = 0;
            $scope.setting.interface_connected = 0;
            $scope.setting.mobileserver_connected = 0;
            $scope.setting.exportserver_connected = 0;

            $scope.testLiveSocket = {};
            $scope.testInterfaceSocket = {};
            $scope.testMobileSocket = {};
            $scope.testExportSocket = {};

            function initData() {

                var request = {};
                var property_id = $sessionStorage.admin.currentUser.property_id;
                request.property_id = property_id;

                $http({
                    method: 'GET',
                    url: '/backoffice/configuration/wizard/getliveserver',
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {

                        console.log(response);
                        $scope.setting.liveserver_url = response.data.live_host;
                        $scope.setting.liveserver_directory = response.data.live_directory;
                        if($scope.setting.liveserver_url)
                            $scope.testLiveSocket = io.connect($scope.setting.liveserver_url);
                        else
                            $scope.testLiveSocket = io.connect("http://127.0.0.1:8001");

                        $scope.testLiveSocket.on('connect', function() {
                            $scope.setting.liveserver_connected = 1;
                            //console.log(testIoSocket);
                        });

                        $scope.testLiveSocket.on('disconnect', function() {
                            $scope.setting.liveserver_connected = 0;
                            //console.log(testIoSocket);
                        });

                    }).catch(function(response) {
                })
                    .finally(function() {
                    });

                $http({
                    method: 'GET',
                    url: '/backoffice/configuration/wizard/getinterfaceserver',
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {

                        $scope.setting.interface_url = response.data.interface_host;
                        $scope.setting.interface_directory = response.data.interface_directory;

                        if($scope.setting.interface_url)
                            $scope.testInterfaceSocket = io.connect($scope.setting.interface_url);
                        else
                            $scope.testInterfaceSocket = io.connect("http://127.0.0.1:3000");

                        $scope.testInterfaceSocket.on('connect', function() {
                            $scope.setting.interface_connected = 1;
                            //console.log(testIoSocket);
                        });

                        $scope.testInterfaceSocket.on('disconnect', function() {
                            $scope.setting.interface_connected = 0;
                            //console.log(testIoSocket);
                        });


                    }).catch(function(response) {
                })
                    .finally(function() {
                    });

                $http({
                    method: 'GET',
                    url: '/backoffice/configuration/wizard/getmobileserver',
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {

                        $scope.setting.mobileserver_url = response.data.mobileserver_host;
                        $scope.setting.mobileserver_directory = response.data.mobileserver_directory;

                        if($scope.setting.mobileserver_url)
                            $scope.testMobileSocket = io.connect($scope.setting.mobileserver_url);
                        else
                            $scope.testMobileSocket = io.connect("http://127.0.0.1:8008");

                        $scope.testMobileSocket.on('connect', function() {
                            $scope.setting.mobileserver_connected = 1;
                            //console.log(testIoSocket);
                        });

                        $scope.testMobileSocket.on('disconnect', function() {
                            $scope.setting.mobileserver_connected = 0;
                            //console.log(testIoSocket);
                        });
                    }).catch(function(response) {

                })
                    .finally(function() {
                    });

                $http({
                    method: 'GET',
                    url: '/backoffice/configuration/wizard/getexportserver',
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {

                        $scope.setting.exportserver_url = response.data.exportserver_host;
                        $scope.setting.exportserver_directory = response.data.exportserver_directory;

                        if($scope.setting.exportserver_url)
                            $scope.testExportSocket = io.connect($scope.setting.exportserver_url);
                        else
                            $scope.testExportSocket = io.connect("http://127.0.0.1:8005");

                        $scope.testExportSocket.on('connect', function() {
                            $scope.setting.exportserver_connected = 1;
                        });

                        $scope.testExportSocket.on('disconnect', function() {
                            $scope.setting.exportserver_connected = 0;
                        });
                    }).catch(function(response) {

                })
                    .finally(function() {
                    });
            }

            initData();

        $scope.startLiveserver = function(){
            var fd = new FormData();
            fd.append('action', 'start');

            if($scope.setting.liveserver_directory)
                fd.append('liveserver_directory', $scope.setting.liveserver_directory);

            $http.post('/backoffice/configuration/wizard/updateliveserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){
                    console.log(response);
                })
                .error(function(data, status, headers, config){
                });
            $scope.checkLiveServer();
        }
        $scope.checkLiveServer = function(){
                var interval_socket = $interval(function(){
                    if($scope.testLiveSocket.connected === true)
                    {
                        $scope.setting.liveserver_connected = 1;
                        $interval.cancel(interval_socket);
                    }else {
                        $scope.setting.liveserver_connected = 0;
                    }
                },1000);
            }
        $scope.stopLiveserver = function(){
            var fd = new FormData();
            fd.append('action', 'stop');
            if($scope.setting.liveserver_directory)
                fd.append('liveserver_directory', $scope.setting.liveserver_directory);

            $http.post('/backoffice/configuration/wizard/updateliveserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){
                })
                .error(function(data, status, headers, config){
                });
        }
        $scope.restartLiveserver = function(){
            var fd = new FormData();
            fd.append('action', 'restart');
            if($scope.setting.liveserver_directory)
                fd.append('liveserver_directory', $scope.setting.liveserver_directory);

            $http.post('/backoffice/configuration/wizard/updateliveserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){
                })
                .error(function(data, status, headers, config){
                });
            $scope.checkLiveServer();
        }



        $scope.startInterfaceserver = function(){
            var fd = new FormData();
            fd.append('action', 'start');
            if($scope.setting.interface_directory)
                fd.append('interface_directory', $scope.interface_directory);

            $http.post('/backoffice/configuration/wizard/updateinterfaceserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){
                    console.log(response);
                })
                .error(function(data, status, headers, config){
                });
            $scope.checkInterface();
        }

            $scope.checkInterface = function(){
                var interval_socket = $interval(function(){
                    if($scope.testInterfaceSocket.connected === true)
                    {
                        $scope.setting.interface_connected = 1;
                        $interval.cancel(interval_socket);
                    }else {
                        $scope.setting.interface_connected = 0;
                    }
                },1000);
            }
        $scope.stopInterfaceserver = function(){
            var fd = new FormData();
            fd.append('action', 'stop');
            if($scope.setting.interface_directory)
                fd.append('interface_directory', $scope.interface_directory);

            $http.post('/backoffice/configuration/wizard/updateinterfaceserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){

                })
                .error(function(data, status, headers, config){
                });

        }
        $scope.restartInterfaceserver = function(){
            var fd = new FormData();
            fd.append('action', 'restart');
            if($scope.setting.interface_directory)
                fd.append('interface_directory', $scope.interface_directory);

            $http.post('/backoffice/configuration/wizard/updateinterfaceserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){

                })
                .error(function(data, status, headers, config){
                });
            $scope.checkInterface();
        }

        $scope.startMobileserver = function(){
            var fd = new FormData();
            fd.append('action', 'start');
            if($scope.setting.mobileserver_directory)
                fd.append('mobileserver_directory', $scope.setting.mobileserver_directory);

            $http.post('/backoffice/configuration/wizard/updatemobileserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){
                    console.log(response);
                })
                .error(function(data, status, headers, config){
                });
            $scope.checkMobileserver();
        }
        $scope.checkMobileserver = function(){
            var interval_socket = $interval(function(){
                if($scope.testMobileSocket.connected === true)
                {
                    $scope.setting.mobileserver_connected = 1;
                    $interval.cancel(interval_socket);
                }else {
                    $scope.setting.mobileserver_connected = 0;
                }
            },1000);
        }
        $scope.stopMobileserver = function(){
            var fd = new FormData();
            fd.append('action', 'stop');
            if($scope.setting.mobileserver_directory)
                fd.append('mobileserver_directory', $scope.setting.mobileserver_directory);
            $http.post('/backoffice/configuration/wizard/updatemobileserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){

                })
                .error(function(data, status, headers, config){
                });
        }
        $scope.restartMobileserver = function(){
            var fd = new FormData();
            fd.append('action', 'restart');
            if($scope.setting.mobileserver_directory)
                fd.append('mobileserver_directory', $scope.setting.mobileserver_directory);
            $http.post('/backoffice/configuration/wizard/updatemobileserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){

                })
                .error(function(data, status, headers, config){
                });
            $scope.checkMobileserver();
        }

        $scope.startExportserver = function(){
            var fd = new FormData();
            fd.append('action', 'start');
            if($scope.setting.exportserver_directory)
                fd.append('exportserver_directory', $scope.setting.exportserver_directory);

            $http.post('/backoffice/configuration/wizard/updateexportserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){
                    console.log(response);
                })
                .error(function(data, status, headers, config){
                });
            $scope.checkExportserver();
        }
        $scope.checkExportserver = function(){
            var interval_socket = $interval(function(){
                if($scope.testExportSocket.connected === true)
                {
                    $scope.setting.exportserver_connected = 1;
                    $interval.cancel(interval_socket);
                }else {
                    $scope.setting.exportserver_connected = 0;
                }
            },1000);
        }
        $scope.stopExportserver = function(){
            var fd = new FormData();
            fd.append('action', 'stop');
            if($scope.setting.exportserver_directory)
                fd.append('exportserver_directory', $scope.setting.exportserver_directory);

            $http.post('/backoffice/configuration/wizard/updateexportserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){

                })
                .error(function(data, status, headers, config){
                });
        }
        $scope.restartExportserver = function(){
            var fd = new FormData();
            fd.append('action', 'restart');
            if($scope.setting.exportserver_directory)
                fd.append('exportserver_directory', $scope.setting.exportserver_directory);

            $http.post('/backoffice/configuration/wizard/updateexportserver', fd, {
                transformRequest: angular.identity,
                headers: {'Content-Type': undefined}
            })
                .success(function(response){

                })
                .error(function(data, status, headers, config){
                });
            $scope.checkExportserver();

        }





        });
    });