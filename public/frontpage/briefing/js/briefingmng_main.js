'use strict';

/* Controllers */

angular.module('app')
    .controller('AppCtrl',
        function(  $rootScope, $scope,   $localStorage, $window, $state, $http, $interval, $timeout, AuthService, Base64, socket, liveserver, webNotification) {
            $scope.auth_svc = AuthService;

            // add 'ie' classes to html
            var isIE = !!navigator.userAgent.match(/MSIE/i);
            if(isIE){ angular.element($window.document.body).addClass('ie');}
            if(isSmartDevice( $window ) ){ angular.element($window.document.body).addClass('smart')};
    
            function isSmartDevice( $window )
            {
                // Adapted from http://www.detectmobilebrowsers.com
                var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
                // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
                return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
            }

            //====== Authentifcation ====================================================
            if( AuthService.isAuthenticated() )
            {
                $rootScope.profile = AuthService.GetCredentials();
            }

            $scope.complaint_brief_mng_logout = true;
            $scope.logout = function () {
                var profile = AuthService.GetCredentials();

                var request = {};
                request.user_id = profile.id;
                $http({
                    method: 'POST',
                    url: '/frontend/logout',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                }).then(function(response) {

                });

                var room = 'property_' + profile.property_id;
                var individual = 'user_' + profile.id;

                socket.emit('logout', profile.id);
                socket.emit('leaveRoom', room);
                socket.emit('leaveRoom', individual);

                console.log('logout');
                AuthService.ClearCredentials();

                $state.go('access.signin');
            }

            $scope.server_param = {};
            $scope.init = function(server_param) {
                if( !server_param )
                    return;

                server_param = Base64.decode(server_param);
                $scope.server_param = JSON.parse(server_param);
            }
            //==============================================================================
            
            //===== notification ==============================================================
            function showWebpushNotification(title, body, data) {
                webNotification.showNotification(title, {
                    body: body,
                    icon: '/images/mark.png',
                    onClick: function onNotificationClicked() {
                       
                    },
                    autoClose: 5000 //auto close the notification after 4 seconds (you can manually close it via hide function)
                }, function onShow(error, hide) {
                    if (error) {
                        // window.alert('Unable to show notification: ' + error.message);
                    } else {
                        console.log('Notification Shown.');
                    }
                });
            }


            // ============= after login action ============================
            $scope.$on('success-login', function(event, args) {
                console.log(args);

                onLoginSuccess(args);
            });



            function onLoginSuccess(data) {
	            var request = {};
                request.property_id = data.property_id;
                request.user_id = data.id;

                $http({
                    method: 'POST',
                    url: '/frontend/complaint/currentbriefing',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
	            
                        var config = {};
                        config.source = 'web';
                        config.user_id = data.id;
                        config.property_id = data.property_id;
                        socket.emit('login', config);
                    }).catch(function(response) {
                        console.error('Gists error', response.status, response.data);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            
                
            }

            socket.on('server_live', function (message) {
                if( AuthService.isAuthenticated() == false ||  !AuthService.isValidModule('app.complaint.briefing_mng')  )
                {
                    return;
                }

                var profile = AuthService.GetCredentials();
                onLoginSuccess(profile);
            });

            socket.on('complaint_event', function (message) {
                var type = message.type;
                var data = message.data;

                var profile = AuthService.GetCredentials();
          
                if( data.sub_type == 'briefing' )   // new complaint is posted
                {
                    if( AuthService.isValidModule('app.complaint.briefing_view') )
                    {
                       // showWebpushNotification('Briefing', data.message, data);
                        $scope.$broadcast('briefing', data);
                    }
                    if( AuthService.isValidModule('app.complaint.briefing_mng') )
                    {                     
                        $scope.$broadcast('briefing_selected', data);
                    }
                    // if( AuthService.isValidModule('app.engineering.dashboard') )
                    // {                     
                    //     $scope.$broadcast('briefing_selected', data);
                    // }
                }

                if( data.sub_type == 'briefing_ended' )   // new complaint is posted
                {
                    if( AuthService.isValidModule('app.complaint.briefing_view') )
                    {
                       // showWebpushNotification('Briefing', data.message, data);
                        $scope.$broadcast('briefing', data);
                    }
                    if( AuthService.isValidModule('app.complaint.briefing_mng') )
                    {                     
                        $scope.$broadcast('briefing_ended', data);
                    }
                }

                if( data.sub_type == 'briefing_status' )   // new complaint is posted
                {
                    if( AuthService.isValidModule('app.complaint.briefing_mng') )
                    {                     
                        $scope.$broadcast('briefing_status', data);
                    }
                }

                if( data.sub_type == 'participant_added' )   // new complaint is posted
                {
                    if( AuthService.isValidModule('app.complaint.briefing_mng') )
                    {
                        showWebpushNotification('Briefing', data.message, data);
                        $scope.$broadcast('participant_added', data);
                    }                    
                }
/*
                if( data.message == 'Briefing is ended' )
                    $scope.logout();
*/
            });
        });
