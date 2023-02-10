'use strict';

/* Controllers */

angular.module('app')
    .controller('AppCtrl',
        function(  $rootScope, $scope,   $localStorage, $window, $state, $http, $interval, $timeout, socket, liveserver, AuthService) {

            // add 'ie' classes to html
            let isIE = !!navigator.userAgent.match(/MSIE/i);
            if(isIE){ angular.element($window.document.body).addClass('ie');}
            if(isSmartDevice( $window ) ){
                angular.element($window.document.body).addClass('smart');
            }


            //$scope.toolbar_full_height = 'height: ' + ($window.innerHeight-30) + 'px; overflow-y: auto; overflow-x: hidden';

            // config
            $scope.app = {
                name: 'HOTLYNC',
                version: '2.2.0',
                // for chart colors
                color: {
                    primary: '#7266ba',
                    info:    '#23b7e5',
                    success: '#27c24c',
                    warning: '#fad733',
                    danger:  '#f05050',
                    light:   '#e8eff0',
                    dark:    '#3a3f51',
                    black:   '#1c2b36'
                },
                settings: {
                    themeID: 1,
                    navbarHeaderColor: 'bg-black',
                    navbarCollapseColor: 'bg-white-only',
                    asideColor: 'bg-black',
                    headerFixed: true,
                    asideFixed: false,
                    asideFolded: false,
                    asideDock: false,
                    container: false
                }
            };

            $scope.guest_info = {};

            // save settings to local storage
            if ( angular.isDefined($localStorage.settings) ) {
                $scope.app.settings = $localStorage.settings;
            } else {
                $localStorage.settings = $scope.app.settings;
            }
            $scope.$watch('app.settings', function(){
                if( $scope.app.settings.asideDock  &&  $scope.app.settings.asideFixed ){
                    // aside dock and fixed must set the header fixed.
                    $scope.app.settings.headerFixed = true;
                }
                // for box layout, add background image
                $scope.app.settings.container ? angular.element('html').addClass('bg') : angular.element('html').removeClass('bg');
                // save to local storage
                $localStorage.settings = $scope.app.settings;
            }, true);

            $rootScope.fullmode = false;

            function isSmartDevice( $window )
            {
                // Adapted from http://www.detectmobilebrowsers.com
                let ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
                // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
                return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
            }

            $scope.server_param = {};
            $scope.init = function(server_param) {
                // if( !server_param ) {
                //     return;
                // }
            };

            $scope.logout = function () {
                let profile = AuthService.GetCredentials();

                let property_id = profile.property_id;

                socket.emit('guest_logout', profile.guest_id);

                let room = 'guest_' + profile.guest_id;
                socket.emit('leaveRoom', room);

                AuthService.ClearCredentials();

                $state.go('access.login', {property_id: property_id});
            };

            // ============= after login action ============================
            $scope.$on('success-login', function(event, args) {
                console.log(args);

                onLoginSuccess(args);
            });

            function onLoginSuccess(data) {
                // let room = 'guest_' + data.guest_id;

                let config = {};
                config.source = 'web';
                config.guest_id = data.guest_id;

                $scope.guest_info = angular.copy(data);

                socket.emit('guest_login', config);
            }

            //go to page.
            $scope.$on('current_page', function(event, args) {
                $scope.current =  args;
            });

            $scope.goPage = function () {

                switch($scope.current) {
                    case 'promotion_list':
                        $state.go('app.first');
                        break;
                    case 'promotion_view':
                        $scope.$broadcast('before_page', 'promotion_view');
                        break;
                    case 'promotion_insert':
                        $scope.$broadcast('before_page', 'promotion_insert');
                        break;
                    case 'request_new':
                        $scope.$broadcast('before_page_request', 'request_new');
                        break;
                    case 'request_track':
                        $scope.$broadcast('before_page_request', 'request_track');
                        break;
                    case 'request_basket':
                        $scope.$broadcast('before_page_request', 'request_basket');
                        break;
                    case 'request_track_detail':
                        $scope.$broadcast('before_page_request', 'request_track_detail');
                        break;
                    default :
                        $state.go('app.first');
                }
            };

            socket.on('server_live', function (message) {
                if( AuthService.isAuthenticated() === false )
                    return;

                let profile = AuthService.GetCredentials();
                onLoginSuccess(profile);
            });

            socket.on('chat_event', function(message){
                console.log(message);

                $scope.$broadcast('agent_chat_event', message);
            });

            socket.on('agent_message', function (message) {
                // let profile = AuthService.GetCredentials();

                if( message.guest_id != $scope.guest_info.guest_id )
                    return;

                $scope.$broadcast('agent_message', message);
            });

            socket.on('agent_typing', function (message) {
                // let profile = AuthService.GetCredentials();

                if( message.guest_id != $scope.guest_info.guest_id )
                    return;

                $scope.$broadcast('agent_typing', message);
            });

            socket.on('guest_message', function (message) {
                alert(JSON.message);
                // let profile = AuthService.GetCredentials();

                if( message.guest_id == $scope.guest_info.guest_id )
                    return;

                $scope.$broadcast('guest_message', message);
            });

        });
