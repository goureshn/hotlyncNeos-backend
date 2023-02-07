'use strict';

/* Controllers */

angular.module('app')
    .controller('AppCtrl',
        function(  $rootScope, $scope,   $localStorage, $window, $state, $http, $interval, $timeout, $location, AuthService, Base64, socket, liveserver, webNotification) {
            $scope.auth_svc = AuthService;

            // add 'ie' classes to html
            var isIE = !!navigator.userAgent.match(/MSIE/i);
            if(isIE){ angular.element($window.document.body).addClass('ie');}
            if(isSmartDevice( $window ) ){ angular.element($window.document.body).addClass('smart')};

            //highlight of left side
            $scope.highlight = function(val) {
                alert(val);
            }

            //$scope.toolbar_full_height = 'height: ' + ($window.innerHeight-30) + 'px; overflow-y: auto; overflow-x: hidden';

            // config
            $scope.app = {
                name: 'HOTLYNC',
                version: '3.0.2',
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
            }

            // $http.defaults.headers.common['Keep-Alive'] = 'timeout=2, max=100';

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

            // angular translate
            //$scope.lang = { isopen: false };
            //$scope.langs = {en:'English', de_DE:'German', it_IT:'Italian'};
            //$scope.selectLang = $scope.langs[$translate.proposedLanguage()] || "English";
            //$scope.setLang = function(langKey, $event) {
            //  // set the current lang
            //  $scope.selectLang = $scope.langs[langKey];
            //  // You can change the language during runtime
            //  $translate.use(langKey);
            //  $scope.lang.isopen = !$scope.lang.isopen;
            //};

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

            $scope.doFavouriteMenu = function(module_link , hint , icon)
            {
                //console.log(module_link , hint , icon);
            }

            $scope.favourite_menus = [];

            if( AuthService.isAuthenticated() )
            {
                var profile = AuthService.GetCredentials();
                $http.get('/frontend/getfavouritemenu?user_id='+profile.id)
                    .then(function(response){
                        var menus = response.data.list;
                        //console.log(menus);
                        $scope.favourite_menus = menus;

                    });
            }


            $scope.doDblClick = function(el){

                el.preventDefault();
                el.stopPropagation();

                if( AuthService.isAuthenticated() ) {
                    var profile = AuthService.GetCredentials();

                    var currentElement = el.target;
                    var hint;
                    var module_link;
                    var icon;
                    if (currentElement.tagName === "LI") {
                        hint = currentElement.attributes["data-hint"];
                        if (currentElement.attributes["ui-sref"]) {
                            module_link = currentElement.attributes["ui-sref"];
                        }

                        if (currentElement.children.length > 0) {
                            var childElement = currentElement.children[0];
                            if (childElement.tagName === "A") {
                                if (childElement.attributes["ui-sref"])
                                    module_link = childElement.attributes["ui-sref"];
                                if (childElement.children.length > 0) {
                                    if (childElement.children[0].tagName === "I") {
                                        if (childElement.children[0].attributes["class"])
                                            icon = childElement.children[0].attributes["class"];
                                    }
                                }
                            }
                            else if (childElement.tagName === "I") {
                                if (childElement.attributes["class"])
                                    icon = childElement.attributes["class"];

                            }
                        }

                    } else if (currentElement.tagName === "A") {
                    }
                    else if (currentElement.tagName === "I") {
                        if (currentElement.attributes["class"])
                            icon = currentElement.attributes["class"]
                        if (currentElement.parentNode.tagName === "A") {
                            if (currentElement.parentNode.attributes["ui-sref"])
                                module_link = currentElement.parentNode.attributes["ui-sref"];
                            if (currentElement.parentNode.parentNode.tagName === "LI") {
                                if (currentElement.parentNode.parentNode.attributes["ui-sref"])
                                    module_link = currentElement.parentNode.parentNode.attributes["ui-sref"];
                                hint = currentElement.parentNode.parentNode.attributes["data-hint"];

                            }
                        }
                        else if (currentElement.parentNode.tagName === "LI") {
                            if (currentElement.parentNode.attributes["ui-sref"])
                                module_link = currentElement.parentNode.attributes["ui-sref"];
                            hint = currentElement.parentNode.attributes["data-hint"];

                        }

                    }

                    if (module_link.nodeValue && hint.nodeValue && icon.nodeValue) {
                        var menu_id = 0;
                        for (var i = 0; i < $scope.favourite_menus.length; i++) {
                            if (module_link.nodeValue == $scope.favourite_menus[i].module_link) {
                                menu_id = $scope.favourite_menus[i].id;
                                break;
                            }
                        }

                        if (menu_id > 0) {
                            if (confirm("Remove from favourite?")) {

                                $http.get('/frontend/removefavouritemenu?menu_id=' + menu_id + "&user_id=" + profile.id)
                                    .then(function (response) {
                                        var menus = response.data.list;
                                        $scope.favourite_menus = menus;
                                    });
                            }
                        }
                        else {
                            if (confirm("Add to favourite?")) {

                                $http.get('/frontend/addfavouritemenu?module_link=' + module_link.nodeValue + '&hint=' + hint.nodeValue + '&icon=' + icon.nodeValue + "&user_id=" + profile.id)
                                    .then(function (response) {
                                        console.log(response);
                                        var menus = response.data.list;
                                        $scope.favourite_menus = menus;
                                    });

                            }
                        }

                    }
                }
            }

            //=============== Call Event ===================================
            $scope.event_flag = 0;

            $interval(checkCallEvent, 1000);

            $scope.call_alarm_css = '';
            $scope.call_duration = '00:00:00';
            $scope.call_status = 'Ringing';
            $scope.agent_name = '';

            $rootScope.agent_status = {
                status: 'Online',
                wholename: '',
            };

            $rootScope.sip_server = 'developdxb.myhotlync.com';

            function  checkCallEvent() {
                $rootScope.elapse_time = 0 + moment.utc(moment().diff(moment($rootScope.agent_status.created_at,"YYYY-MM-DD HH:mm:ss")));
            }

            function getAgentStatus() {
                var request = {};

                var profile = AuthService.GetCredentials();
                request.agent_id = profile.id;
                $http({
                    method: 'POST',
                    url: '/frontend/call/agentstatus',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                }).then(function(response) {
                    $rootScope.sip_server = response.data.sip_server;
                    $scope.displayAgentStatus(response.data);
                }).catch(function(response) {
                        console.error('Gists error', response.status, response.data);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            }


            $scope.displayAgentStatus = function(data) {
                var profile = AuthService.GetCredentials();
                if(profile.id != data.user_id)
                    return;

                $rootScope.agent_status = data;

                $scope.$broadcast('agent_status_change', data);

                console.log(data);
                if( data.status == 'Ringing' || data.status == 'Wrapup' )
                    $scope.event_flag = 0;
                else
                    $scope.event_flag = 0;


                if( data.status == 'Abandoned')
                    $scope.call_alarm_css = 'border-style: solid; border-color: #f44336;';
                else
                    $scope.call_alarm_css = '';

                if( data.caller && data.caller.id > 0 )
                {
                    $rootScope.agent_status.caller_name = data.caller.firstname + ' ' + data.caller.lastname;
                    data.caller.spam = (data.caller.spam == 1);
                    data.caller.company = (data.caller.company == 1);
                }
                else
                {
                    if( $rootScope.agent_status.ticket )
                        $rootScope.agent_status.caller_name = $rootScope.agent_status.ticket.callerid;
                    else
                        $rootScope.agent_status.caller_name = '';

                    if( data.caller )
                        data.caller.salutation = 'Mr.';
                }

                if( data.ticket && data.ticket.dial_status == 'Abandoned' )
                    showWebpushNotification('Abandoned Call', 'You have Abandoned a Call!', data);

                if( data.status == 'Idle' )
                    $window.document.title = 'Idle - HotLync | Ennovatech';
                else
                    $window.document.title = 'HotLync | Ennovatech';
            }

            $scope.changeStatus = function(status) {
                var request = {};

                $rootScope.agent_status = status;

                var profile = AuthService.GetCredentials();
                request.agent_id = profile.id;
                request.status = status;

                $http({
                    method: 'POST',
                    url: '/frontend/call/changestatus',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                }).then(function(response) {

                }).catch(function(response) {
                        console.error('Gists error', response.status, response.data);
                    })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            }

            //============================================================================

            //==== search bar ==========================================================
            $scope.header = {};
            $scope.header.search_filter = '';
            $scope.header.isopen = false;
            $scope.onSearch = function() {
                var filter = { filter: $scope.header.search_filter };
                $scope.$broadcast('search-list', filter);
            }

            $scope.$on('erase_search', function(event, args) {
                console.log(args);
                $scope.header.search_filter = '';
            });

            //===== notification ==============================================================
            function showWebpushNotification(title, body, data) {
                console.log('webnotification');
                console.log(body);
                webNotification.showNotification(title, {
                    body: body,
                    icon: '/images/mark.png',
                    onClick: function onNotificationClicked() {
                        //hide();
                        $scope.onClickNotifyItem(data);
                        console.log('Notification clicked.');
                    },
                    autoClose: 5000 //auto close the notification after 4 seconds (you can manually close it via hide function)
                }, function onShow(error, hide) {
                    if (error) {
                        // window.alert('Unable to show notification: ' + error.message);
                    } else {
                        var profile = AuthService.GetCredentials();
                        var data = {};
                        $scope.soundfile = '';
                        data.setting_group = 'soundfile' ;
                        data.property_id =   profile.property_id;
                        $http({
                            method: 'POST',
                            url: '/backoffice/configuration/wizard/general',
                            data: data,
                            headers: {'Content-Type': 'application/json; charset=utf-8'}
                        })
                            .success(function (data, status, headers, config) {
                                $scope.soundfile = data.soundfile.soundfile;
                            })
                            .error(function (data, status, headers, config) {
                                console.log(status);
                            });
                        console.log('Notification Shown.');
                        if(title == 'Wake up Call Failure'){
                            if($scope.auth_svc.isValidModule('app.guestservice.wakeup')) {
                                var profile = AuthService.GetCredentials();
                                if (profile.wakeupnoti_status) {
                                    if ($scope.soundfile == '') {
                                        var audio = new Audio('/sound/notify.mp3');
                                        audio.play();
                                    } else {
                                        var audio = new Audio($scope.soundfile);
                                        audio.play();
                                    }
                                }
                            }
                        }else{
                            if($scope.auth_svc.isValidModule('app.guestservice')){
                                var profile = AuthService.GetCredentials();
                                if($scope.soundfile == ''){
                                    if(profile.notify_status) {
                                        var audio = new Audio('/sound/notify.mp3');
                                        audio.play();
                                    }
                                }else{
                                    if(profile.notify_status) {
                                        var audio = new Audio($scope.soundfile);
                                        audio.play();
                                    }
                                }
                            }else if($scope.auth_svc.isValidModule('app.callaccounting')){
                                var profile = AuthService.GetCredentials();
                                if($scope.soundfile == '') {
                                    //if (profile.callaccountingnotify_status) {
                                        var audio = new Audio('/sound/notify.mp3');
                                        audio.play();
                                    //}
                                }else{
                                    //if (profile.callaccountingnotify_status) {
                                        var audio = new Audio($scope.soundfile);
                                        audio.play();
                                    //}
                                }
                            }
                        }
                    }
                });
            }

            $rootScope.notify_count = 0;
            $rootScope.mytask_notify_count = 0;
            $rootScope.unread_chat_cnt = 0;
            $rootScope.mytask_complaint_notify_count = 0;
            $rootScope.mytask_workorder_notify_count = 0;
            $rootScope.mytask_guestservice_notify_count = 0;
            $rootScope.unread_chat_cnt = 0;
            $scope.notifylist = [];
            $scope.header.isopen = false;

            $scope.onClickNotify = function() {
                if ($scope.header.isopen == true) {
                    $scope.header.isopen = false;
                    return;
                }
                var request = {};
                var profile = AuthService.GetCredentials();

                request.property_id = profile.property_id;
                request.user_id = profile.id;

                $scope.notify_loading = true;
                $http({
                    method: 'POST',
                    url: '/frontend/notify/list',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function (response) {
                        $scope.header.isopen = true;
                        $scope.notifylist = response.data;
                        AuthService.setNotifyCount(0);

                        if ($scope.notifylist) {
                            for (var i = 0; i < $scope.notifylist.length; i++) {
                                $scope.notifylist[i].fromnow = moment($scope.notifylist[i].created_at).fromNow();
                            }
                        }
                    }).catch(function (response) {
                    })
                    .finally(function () {
                        $scope.notify_loading = false;
                    });
            }

            $scope.clearAll = function() {
                var max_read_no = 0;
                if( $scope.notifylist.length > 0 )
                {
                    max_read_no = $scope.notifylist[0].id;
                }

                var request = {};
                request.max_read_no = max_read_no;

                $http({
                    method: 'POST',
                    url: '/frontend/notify/clearall',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                .then(function (response) {
                    $scope.notifylist = [];
                    $scope.header.isopen = false;
                }).catch(function (response) {
                })
                .finally(function () {
                    $scope.notify_loading = false;
                });

            }

            $scope.onClickNotifyItem = function(notify) {
                $scope.header.isopen = false;

                if( notify.type == 'app.guestservice.notify' )
                {
                    $state.go('app.guestservice.ticket', {ticket_id: notify.notification_id});
                }

                if( notify.type == 'app.guestservice.chat' )
                {
                    $state.go('app.guestservice.chat');

                }
                //if( notify.type == 'app.alarm.dashboard' && notify.send_type == '0' )
                if( notify.type == 'app.alarm.dashboard')
                {
                    $scope.$broadcast('alarm_dash_response', notify);
                }
            }

            $scope.onDblClickNotifyItem = function(notify) {
                $scope.header.isopen = false;

                if( notify.type == 'app.guestservice.chat' )    // send broadcast for accepting
                {
                    acceptChat(notify.notification_id);
                }
            }

            //click sub menu for highlight
            $scope.subMenuColor = function(page) {
                var cur_page = '';
                if($state.current.name != null)
                    var cur_page = $state.current.name;
                if(page == cur_page) return 'subbackofleft';
                else return '';
            }

            function acceptChat(session_id) {
                var request = {};
                var profile = AuthService.GetCredentials();

                request.session_id = session_id;
                request.agent_id = profile.id;

                $http({
                    method: 'POST',
                    url: '/frontend/guestservice/acceptchat',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                .then(function (response) {
                    var data = response.data;

                    if( data.code != 200)
                    {
                        alert(data.message);
                        return;
                    }

                    var guest_id = data.session.guest_id;
                    var path = $location.path();
                    if( path.indexOf('/app/guestservice/ticket') == -1 &&
                        path.indexOf('/app/guestservice/chat') == -1 )  // not chat page
                    {
                        $state.go('app.guestservice.chat');
                    }
                }).catch(function (response) {
                })
                .finally(function () {

                });
            }

            // ============= after login action ============================
            $scope.$on('success-login', function(event, args) {
                console.log(args);

                onLoginSuccess(args);
            });

            //if( AuthService.isAuthenticated() )
            //{
            //    var profile = AuthService.GetCredentials();
            //    onLoginSuccess(profile);
            //}

            function totalCnt(data) {
                if( !data )
                    return 0;
                $rootScope.mytask_complaint_notify_count = data.complaint_cnt;
                $rootScope.mytask_workorder_notify_count = data.workorder_cnt;
                $rootScope.mytask_guestservice_notify_count = data.guestservice_cnt;
                return data.complaint_cnt + data.workorder_cnt + data.guestservice_cnt;
            }

            function onLoginSuccess(data) {
                if( AuthService.isAuthenticated() )
                    getAgentStatus();

                var room = 'property_' + data.property_id;
                var individual = 'user_' + data.id;
                //socket.emit('joinRoom', room);
                //socket.emit('joinRoom', individual);

                var config = {};
                config.source = 'web';
                config.user_id = data.id;
                config.property_id = data.property_id;
                socket.emit('login', config);

                $rootScope.notify_count = data.unread;
                $rootScope.mytask_notify_count = totalCnt(data.mytask_notify);
                getUnreadChatCount();
            }

            function getUnreadChatCount() {
                var request = {};
                var profile = AuthService.GetCredentials();

                request.agent_id = profile.id;

                $http({
                    method: 'POST',
                    url: '/frontend/chat/unreadcount',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                .then(function (response) {
                    $rootScope.unread_chat_cnt = response.data.content.chat_notify_cnt;
                }).catch(function (response) {
                })
                .finally(function () {

                });
            }
            socket.on('app.alarm.dashboard', function(message){
                console.log(message);

                if( AuthService.isValidModule('app.alarm.dashboard') )
                {
                    if( $rootScope.notify_count == undefined )
                        $rootScope.notify_count = 0;
                    $rootScope.notify_count++;
                    AuthService.setNotifyCount($rootScope.notify_count);
                    showWebpushNotification(message.content.type, message.content.content, message.content);
                }
            });

            socket.on('app.checklisttask.updated', function(message){
                console.log(message);
                var profile = AuthService.GetCredentials();

                if (AuthService.isValidModule('app.mod.checklist') ) {
                    $scope.$broadcast('checklisttask_updated', message);
                }
            });

            socket.on('app.checklisttask.updatestatus', function(message){
                console.log(message);
                var profile = AuthService.GetCredentials();

                if (AuthService.isValidModule('app.mod.checklist') ) {
                    $scope.$broadcast('checklisttask_updatestatus', message);
                }
            });

            socket.on('app.checklisttask.created', function(message){
                console.log(message);
                var profile = AuthService.GetCredentials();

                if (AuthService.isValidModule('app.mod.checklist') ) {
                    $scope.$broadcast('checklisttask_created', message);
                }
            });
            socket.on('app.guestservice.notify', function(message){
                console.log(message);
                var profile = AuthService.GetCredentials();
               // window.alert(JSON.stringify(profile));
                if (AuthService.isValidModule('app.guestservice.notify') )
                {
                    if (message.isRefresh != undefined && message.isRefresh != false) {
                        $scope.$broadcast('guest_ticket_event', message);
                    }
                    if ((AuthService.isValidModule('dept.gs.deptnotif') && (profile.dept_id == message.content.dept_id)) || (!(AuthService.isValidModule('dept.gs.deptnotif'))))
                    {
                    if( $rootScope.notify_count == undefined )
                        $rootScope.notify_count = 0;
                    $rootScope.notify_count++;
                    AuthService.setNotifyCount($rootScope.notify_count);
                    showWebpushNotification(message.content.type, message.content.content, message.content);

                     }
            }
            });

            socket.on('app.guestservice.chat', function(message){
                console.log(message);

                if( AuthService.isValidModule('app.guestservice.chat') )
                {
                    if( $rootScope.notify_count == undefined )
                        $rootScope.notify_count = 0;
                    $rootScope.notify_count++;
                    AuthService.setNotifyCount($rootScope.notify_count);
                    showWebpushNotification(message.content.type, message.content.content, message.content);
                }
            });

            socket.on('chat_event', function(message){
                console.log(message);
                console.log('calllllllllllllllllll ---- main');

                if( AuthService.isValidModule('app.guestservice.chat') )
                {
                    $scope.$broadcast('guest_chat_event', message);
                }
            });

            socket.on('app.guestservice.wakeup', function(message){
                console.log(message);

                if( AuthService.isValidModule('app.guestservice.wakeup') )
                {
                    if( $rootScope.notify_count == undefined )
                        $rootScope.notify_count = 0;
                    $rootScope.notify_count++;
                    AuthService.setNotifyCount($rootScope.notify_count);
                    showWebpushNotification('Wake up Call Failure', message.content.content, message.content);
                }
            });


            socket.on('server_live', function (message) {
                if( AuthService.isAuthenticated() == false )
                    return;

                var profile = AuthService.GetCredentials();
                onLoginSuccess(profile);
            });

            socket.on('call_event', function (message) {
                var type = message.type;
                var data = message.data;

                $scope.displayAgentStatus(data);
                $scope.$broadcast('call_event', data);
            });

            socket.on('idle_event', function (message) {
                var type = message.type;
                var data = message.data;
                console.log(data);

                $scope.displayAgentStatus(data);
                $scope.$broadcast('idle_event', data);
            });

            socket.on('agent_status_change', function (message) {
                var type = message.type;
                var data = message.data;
                console.log(data);

                $scope.displayAgentStatus(data);
                

                $scope.$broadcast('agent_status_change', data);
            });

            socket.on('callback_event', function (message) {
                var type = message.type;
                var data = message.data;
                console.log(data);
                if( data.user_id > 0 )
                {
                    $scope.displayAgentStatus(data);
                }

                $scope.$broadcast('callback_event', data);
            });

            socket.on('queue_event', function (message) {
                var data = message.data;
                $scope.$broadcast('queue_event', data);
            });

            socket.on('extension_invalid', function (message) {
                var type = message.type;
                var data = message;

                console.log(data);
                if( data.user_id > 0 )
                {
                    //$rootScope.agent_status.extension = data.extension;
                    showWebpushNotification('Extension Assign Error', data.data, data);
                }

            });

            socket.on('wakeup_event', function (message) {
                var type = message.type;
                var data = message.data;

                $scope.$broadcast('onChangedWakeup', data);
            });
            socket.on('sync_start', function (message) {
                var type = message.type;
                var data = message.data;
              // window.alert("Here in syncstart socket" + JSON.stringify(data));
                $scope.$broadcast('onSyncMobileList', data);
            });

            socket.on('complaint_event', function (message) {
                var type = message.type;
                var data = message.data;

                var profile = AuthService.GetCredentials();

                $scope.$broadcast(data.sub_type, data);

                if( data.sub_type == 'post' )   // new complaint is posted
                {
                    if( AuthService.isValidModule('app.complaint.complaint') )
                    {
                        var profile = AuthService.GetCredentials();
                        var complaint_setting = profile.complaint_setting;
                        console.log('Complaint is Posted: ' + data.content);
                        if( complaint_setting.complaint_create == true )
                        {
                            showWebpushNotification('Complaint is posted', data.content, data);
                        }
                        $scope.$broadcast('complaint_post', data);
                    }
                }

                if( data.sub_type == 'assign_subcomplaint' )   // sub complaint is assigned
                {
                    if( data.assignee_id == profile.id )
                    {
                        console.log('Sub Complaint is Assigned: ' + data.content);
                        showWebpushNotification('Sub complaint is assigned', data.content, data);
                        $scope.$broadcast('subcomplaint_assigned', data);
                    }
                }

                if( data.sub_type == 'escalate_subcomplaint' )   // sub complaint is assigned
                {
                    if( data.assignee_id == profile.id )
                    {
                        console.log('Sub Complaint is escalated: ' + data.content);
                        showWebpushNotification('Sub complaint is escalated', data.content, data);
                        $scope.$broadcast('subcomplaint_escalated', data);
                    }
                }

                if( data.sub_type == 'post_compensation' )   // new complaint is posted
                {
                    if( data.assignee_id == profile.id )
                    {
                        console.log('Compensation is Posted: ' + data.content);
                        showWebpushNotification('Compensation is posted', data.content, data);
                        $scope.$broadcast('compensation_post', data);
                    }
                }

                if( data.sub_type == 'compensation_approve' )   // compensation is approved
                {
                    if( AuthService.isValidModule('app.complaint.complaint') )
                    {
                        console.log('Compensation Status is Changed: ' + data.content);
                        showWebpushNotification('Compensation status is changed', data.content, data);
                        $scope.$broadcast('compensation_approve', data);
                    }
                }

                if( data.sub_type == 'briefing' )   // new complaint is posted
                {
                    if( AuthService.isValidModule('app.complaint.briefing_view') )
                    {
                        showWebpushNotification('Briefing', data.message, data);
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
                        showWebpushNotification('Briefing', data.message, data);
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

            });

            socket.on('eng_request_event', function (message) {
                var type = message.type;
                var data = message.data;

                var profile = AuthService.GetCredentials();

                if( data.sub_type == 'post' )   // new request is posted
                {
                    if( AuthService.isValidModule('app.engineering.request') )
                    {
                        console.log('Engineering request is Posted: ' + data.content);
                        showWebpushNotification('Engineering request is posted', data.content, data);
                        $scope.$broadcast('eng_request_post', data);
                    }
                }

                if( data.sub_type == 'create_workorder' )   // new request is posted
                {
                    if( AuthService.isValidModule('app.engineering.workorder') )
                    {
                        showWebpushNotification('Workroder is created', data.content, data);
                        $scope.$broadcast('create_workorder', data);
                    }
                }

            });

            socket.on('repair_request_event', function (message) {
                var type = message.type;
                var data = message.data;

                $scope.$broadcast(data.sub_type, data);
            });

            socket.on('workorder_status_event', function (message) {
                var type = message.type;
                var data = message.data;

                $scope.$broadcast(data.sub_type, data);
            });

            socket.on('guest_message', function (message) {
                var path = $location.path();
                if( path.indexOf('/app/guestservice/ticket') == -1 &&
                    path.indexOf('/app/guestservice/chat') == -1 )  // not chat page
                {
                    // show web notification
                    var profile = AuthService.GetCredentials();
                    if( message.agent_id == profile.id)
                    {
                        message.type = 'app.guestservice.chat';
                        showWebpushNotification('Guest - ' + message.guest_name + ' for Room ' + message.room, message.text, message);
                    }
                }

                $scope.$broadcast('guest_message', message);
            });

            socket.on('agent_message', function (message) {
                var profile = AuthService.GetCredentials();
                if( message.agent_id == profile.id )
                    return;

                $scope.$broadcast('agent_message', message);
            });

            socket.on('guest_typing', function (message) {
                $scope.$broadcast('guest_typing', message);
            });

            socket.on('agent_agent_msg', function (message) {
                var profile = AuthService.GetCredentials();
                if( message.to_id != profile.id )
                    return;

                console.log(message);

                var path = $location.path();
                if( path.indexOf('/app/guestservice/chat') == -1 )  // not chat page
                {
                    // show web notification
                    var profile = AuthService.GetCredentials();
                    message.type = 'app.guestservice.chat';
                    showWebpushNotification(message.from_name, message.text, message);
                    $rootScope.unread_chat_cnt = message.unread_total_cnt;

                    var audio = new Audio('sounds/notify.mp3');
                    audio.play();
                }
                else
                    $scope.$broadcast('agent_agent_msg', message);
            });

            socket.on('group_msg_receive', function (message) {
                var profile = AuthService.GetCredentials();
                if( message.to_ids.indexOf(profile.id) == -1 )
                    return;

                var path = $location.path();
                if( path.indexOf('/app/guestservice/chat') == -1 )  // not chat page
                {
                    // show web notification
                    var profile = AuthService.GetCredentials();
                    message.type = 'app.guestservice.chat';
                    showWebpushNotification(message.from_name, message.text, message);
                    $rootScope.unread_chat_cnt = message.unread_total_cnt;

                    var audio = new Audio('sounds/notify.mp3');
                    audio.play();
                }
                else
                    $scope.$broadcast('group_msg', message);
            });

            socket.on('agent_agent_typing', function (message) {
                $scope.$broadcast('agent_agent_typing', message);
            });

            socket.on('group_typing', function (message) {
                $scope.$broadcast('group_typing', message);
            });

            socket.on('agent_chat_event', function (message) {
                var profile = AuthService.GetCredentials();
                if( message.agent_id == profile.id )
                    return;

                $scope.$broadcast('agent_chat_event', message);
            });



            socket.on('group_chat_event', function (message) {
                var profile = AuthService.GetCredentials();
                if( message.from_id == profile.id )
                    return;

                $scope.$broadcast('group_chat_event', message);
            });

            socket.on('agent_status_event', function (message) {
                var profile = AuthService.GetCredentials();
                if( message.agent_id == profile.id )
                    return;

                $scope.$broadcast('agent_status_event', message);
            });

            socket.on('changed_auth_status', function (message) {
                $scope.$broadcast('changed_auth_status', message);
            });

            socket.on('hskp_status_event', function (message) {
                $scope.$broadcast('hskp_status_event', message);
            });

            socket.on('guest_status', function (message) {
                var type = message.type;
                var data = message.data;
                console.log(data);

                showWebpushNotification('Guest Status', 'Guest ' + data.guest_name + ' from Room ' + data.room + ' is ' + data.status, data);

                $scope.$broadcast('guest_status', data);
            });

            socket.on('promotion_status', function (message) {
                var type = message.type;
                var data = message.data;
                console.log(data);

                showWebpushNotification('Promotion Status', 'Promotion ' + data.outlet_name +' is ' + data.status, data);

                $scope.$broadcast('promotion_status', data);
            });

            socket.on('ack', function (message) {
                $scope.$broadcast(message.notify_type, message);
            });

            socket.on('pdf_export_finished', function (message) {
                $scope.$broadcast('pdf_export_finished', message);
            });

            socket.on('mytask_notify', function (message) {
                var type = message.type;
                var data = message;
                console.log(data);
                if( data.user_id > 0 )
                {
                    $rootScope.mytask_notify_count = totalCnt(data.content);
                    AuthService.setMytaskNotifyCount(data.content);
                }
            });

            $window.onfocus = function() {

                console.log('enter');
            }

            $window.onblur  = function() {

                console.log('out');
            }

            var vis = (function(){
                var stateKey, eventKey, keys = {
                    hidden: "visibilitychange",
                    webkitHidden: "webkitvisibilitychange",
                    mozHidden: "mozvisibilitychange",
                    msHidden: "msvisibilitychange"
                };
                for (stateKey in keys) {
                    if (stateKey in document) {
                        eventKey = keys[stateKey];
                        break;
                    }
                }
                return function(c) {
                    if (c) document.addEventListener(eventKey, c);
                    return !document[stateKey];
                }
            })();

            $rootScope.license_info = {};

            function checkLicense()
            {
                // check license
                $http({
                    method: 'POST',
                    url: '/hotlync/checklicense',
                    data: {},
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
                        $rootScope.license_info = response.data;
                        //console.log(response);
                    }).catch(function(response) {

                    })
                    .finally(function() {

                    });
            }

            checkLicense();

            $scope.$on('onViewTicket', function(event, args) {
                console.log('onViewTicket', 'broadcast', args);
                $scope.$broadcast('onViewGuestserviceTicket', args);
            });

        })
.config(function(blockUIConfig) {
  // Disable automatically blocking of the user interface
  blockUIConfig.autoBlock = false;
});
