
app.config(['$compileProvider', function($compileProvider) {
    $compileProvider.imgSrcSanitizationWhitelist(/^\s*(https?|local|data):/);
}]);

app.controller('AgentStatusController', function ($scope,  hotkeys, $rootScope, $http, $window,  AuthService, CountryService, GuestService, toaster,$uibModal, $httpParamSerializer, liveserver, $aside, $timeout, blockUI) {
    var MESSAGE_TITLE = 'Call AgentStatus';

    

    $scope.auth_svc = AuthService;
    $scope.softphone_show = false;
    $scope.gs = GuestService;

	

    var profile = AuthService.GetCredentials();
    $scope.user_id = profile.id;


    $scope.bMyCall = true;
    $rootScope.$on('callSearchBook', function(){
        $scope.onSearchBook();
    });

    $scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';

    function outgoing(caller_id) {
        profile = AuthService.GetCredentials();
        var data = {};
        data.caller_id = caller_id;
        data.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/call/outgoing',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Call');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    var ticket_id = 0;
    function incoming(caller_id) {
        profile = AuthService.GetCredentials();
        var data = {};
        data.caller_id = caller_id;
        data.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/call/incoming',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Call');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    var last_answer_time = 0;
    function answerCall() {
        if( last_answer_time > 0 )
        {
            var current_time = Date.now();
            if( current_time - last_answer_time < 3000 ) // twice call
            {
                // prevent twice call.
                last_answer_time = current_time;
                return;
            }
        }

        last_answer_time = current_time;

        var request = {};

        request.ticket_id = $rootScope.agent_status.ticket_id;
        request.bridge_id = $rootScope.agent_status.ticket.bridge_id;
        request.channel = $rootScope.agent_status.ticket.channel;

        $http({
            method: 'POST',
            url: liveserver.api + 'answer',
		data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }

    function endCall() {
        var request = {};

        request.ticket_id = $rootScope.agent_status.ticket_id;
        request.wrapup_flag = $scope.callcenter_config.auto_wrapup_flag ? 1 : 0;

        $http({
            method: 'POST',
            url: '/call/endcall',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }

    function hangupCall() {
        var request = {};

        request.ticket_id = $rootScope.agent_status.ticket_id;
        request.wrapup_flag = $scope.callcenter_config.auto_wrapup_flag ? 1 : 0;

        $http({
            method: 'POST',
            url: '/call/hangup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }



    $scope.$on('$destroy', function() {
        if( $rootScope.agent_status.status == 'Available' )
            $scope.onChangeCallStatus('Away');
    });


    $scope.onChangeCallStatus = function(status) {
        if( status == 'Available')
        {
            if( $scope.callcenter_config.softphone_enabled == true )
            {
                var iframe = document.getElementById("softphone");
                if( iframe.contentWindow.webphone_api.isregistered() == false )
                    return;
            }
        }

        var root_status = $rootScope.agent_status.status;
        var page_status = status;
        if(root_status == page_status) return ;
        var agentstatus = {};
        profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        var first_name = profile.first_name;
        agentstatus.agent_id = profile.id;
        agentstatus.status = status;
        //agentstatus.extension = $rootScope.agent_status.extension;
        agentstatus.property_id = profile.property_id;

        if(agentstatus.status) {
            $http({
                method: 'POST',
                url: '/frontend/call/changestatus',
                data: agentstatus,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('success', MESSAGE_TITLE, 'Status has been changed Successfully!');
                    $rootScope.agent_status.status = response.data.status;
                    $rootScope.agent_status.created_at = response.data.created_at;
                    $window.document.title = 'HotLync | Ennovatech';
                    $scope.onClickStatus($rootScope.agent_status.status);
                    if($rootScope.agent_status.status == 'Available' ||
                        $rootScope.agent_status.status == 'Log out') {

                    }
                    console.log(response);
                }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to change Agent status');
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                    $scope.isLoading = false;
                });
        }
    }


    function onAppStateChange(e) {
        if( $scope.callcenter_config.softphone_enabled == false )
            return;

        var state = e.detail;
        console.debug('Softphone App', state);

        if( state == 'loaded' )
        {
            changeExtension($rootScope.agent_status);
        }
    }

    window.document.addEventListener('onAppStateChange', onAppStateChange, false);

    if( $rootScope.agent_status.status == 'Available' )
        $scope.onChangeCallStatus('Away');

    function onRegStateChange(e) {
        if( $scope.callcenter_config.softphone_enabled == false )
            return;

        var state = e.detail;
        console.debug('Softphone Reg', state);
        switch( state )
        {
            case 'registered':
                $scope.softphone_show = true;
                break;
            case 'failed':
                $scope.softphone_show = false;
                break;
            case 'unregistered':
                $scope.softphone_show = false;
                break;
            default:
                $scope.softphone_show = false;
                break;
        }

        if( $rootScope.agent_status.status == 'Available' && state != 'registered' )
        {
            $scope.onChangeCallStatus('Away');
        }

        if( $rootScope.agent_status.status == 'Away' && state == 'registered' )
        {
            $scope.onChangeCallStatus('Available');
        }
    }

    window.document.addEventListener('onRegStateChange', onRegStateChange, false);

    var previousEvent = 0; // 0: nomal, 1: incoming, 2: outgoing, 3: endCall, 4: hangupCall, 5: answerCall

	function onResetPrevEvent() {
        $timeout(function() {
            previousEvent = 0;
        }, 3000);
    }

    var previousEvent = 0; // 0: nomal, 1: incoming, 2: outgoing, 3: endCall, 4: hangupCall, 5: answerCall

    function onResetPrevEvent() {
        $timeout(function() {
            previousEvent = 0;
        }, 3000);
    }

    function onCallStateChange(e) {
        var data = e.detail;
        console.debug(data);
        var event = data.event;
        var caller_id = data.peername;
        var direction = data.direction;

        if( event == 'setup' ) {
            if( direction == 1 ) {
                if (previousEvent != 2) {
                    previousEvent = 2;
                    outgoing(caller_id);
                    onResetPrevEvent();
                }
            }
            else{
                if (previousEvent != 1) {
                    previousEvent = 1;
                    incoming(caller_id);
                    onResetPrevEvent();
                }
            }
        } else if( event == 'disconnected' ) {
            if( $rootScope.agent_status.ticket.dial_status == 'Answered' ) {
                if (previousEvent != 3) {
                    previousEvent = 3;
                    endCall();
                    onResetPrevEvent();
                }
            } else {
                if (previousEvent != 4) {
                    previousEvent = 4;
                    hangupCall();
                    onResetPrevEvent();
                }
            }
        } else if( event == 'connected' ) {

            if (previousEvent != 5) {
                previousEvent = 5;
                answerCall();
                onResetPrevEvent();
            }
        }
    }

    window.document.addEventListener('onCallStateChange', onCallStateChange, false);

    if( $rootScope.agent_status.caller == undefined )
        $rootScope.agent_status.caller = {};

    $scope.isHidden = true;

    $scope.ticket = {};

    var search_option = '';

    $scope.queue_flag = 0;

    $scope.countrylist = CountryService.countrylist;

    $scope.ticket.type = 'Other';
    //$scope.ticket.channel = 'Others';
    $scope.ticket.sendconfirm = 'Email';
    $scope.agent_profile = AuthService.GetCredentials();
    // pip
    $scope.isLoading = false;
    $scope.datalist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'ivr.id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };
    $http.get('/list/channels').success(function (response) {
        $scope.channels = response;
    });
    $http.get('/list/call_types').success(function (response) {
        $scope.types = response;
    });

    $scope.isLoadingSecond = false;
    $scope.isLoadingthird = false;
    $scope.datalistSecond = [];
    $scope.datalistthird = [];

    $scope.isLoadingBook = true;
    $scope.dataListBook = [];
    $scope.searchtextBook = '';

    //  pagination
    $scope.paginationOptionsSecond = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'ivr.id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.loadMoreBook = {
        pageNumber: 0,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        curCount: 20
    };

    $scope.statuses = [
        'Online',
        'Available',
        'On Break',
        'Not Available',
        'Log out',
        'Away'
    ];

    $scope.priority_list=[
        "Normal",
        "Medium",
        "High"
    ];

    $scope.onShowImage = function() {
        var size = 'lg';
        var modalInstance = $uibModal.open({
            templateUrl: 'imageLoadModal.html',
            controller: 'ImgLoadCropCtrl',
            size: size
        });
    }

    var sip_contact_list = [];
    $scope.callcenter_config = {
        auto_wrapup_flag: true,
        caller_info_save_flag: true
    };
    function initData() {
        var request = {};
        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.user_id = profile.id;

        // get call center setting
        $http({
            method: 'POST',
            url: '/frontend/call/config',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.callcenter_config = response.data.content;
                $scope.callcenter_config.auto_wrapup_flag = $scope.callcenter_config.auto_wrapup_flag == "1";
                $scope.callcenter_config.caller_info_save_flag = $scope.callcenter_config.caller_info_save_flag == "1";
                $scope.callcenter_config.call_center_widget = $scope.callcenter_config.call_center_widget == "1";
                $scope.callcenter_config.softphone_enabled = $scope.callcenter_config.softphone_enabled == "1";
            }).catch(function(response) {

            })
            .finally(function() {
            });


        $http({
            method: 'POST',
            url: '/frontend/call/agentextlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.extensionlist = response.data;
                console.log($scope.extensionlist);
            }).catch(function(response) {

            })
            .finally(function() {
            });

        // get sip contact list
        $http({
                method: 'POST',
                url: '/frontend/call/sipcontactlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    sip_contact_list = response.data.content;
                }).catch(function(response) {

                })
                .finally(function() {
                });

        $scope.prioritylist = [];
        GuestService.getPriorityList()
            .then(function(response) {
            $scope.prioritylist = response.data;
            });

        $scope.buildings = [];
        $http.get('/backoffice/property/wizard/buildlist?property_id='+profile.property_id)
            .success(function(response){
                $scope.buildings = response;
                $scope.buildings.unshift({id:0, property_id:profile.property_id, name:"Select Building","description":""});
            });

        $scope.room_list = [];
        GuestService.getRoomList("")
            .then(function(response){
                $scope.room_list = response.data;
            });

        $scope.location_list = [];
        GuestService.getLocationList("")
            .then(function(response){
                $scope.location_list = response.data;
            });

        $scope.staff_list = [];
        GuestService.getStaffList("")
            .then(function(response){
                $scope.staff_list = response.data;
            });

        $scope.dept_list = [];
        GuestService.getDepartList()
            .then(function(response) {
                $scope.dept_list = response.data.departlist;
            });

        $scope.usergroup_list = [];
        $http.get('/frontend/guestservice/usergrouplist')
            .then(function(response){
                $scope.usergroup_list = response.data;
            });

        $scope.system_task_list = [];
        profile = AuthService.GetCredentials();
        $http.get('/frontend/guestservice/systemtasklist?property_id=' + profile.property_id +'&user_id='+profile.id)
            .then(function(response) {
                $scope.system_task_list = response.data;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });

        getGuestTaskItemList();

        console.debug("call_agentstatus controller enter");

        if( $rootScope.agent_status.status == 'Available' )
        {
            // $scope.onChangeCallStatus('Online');
        }
        else
            $scope.select_status = $rootScope.agent_status.status + '';
    }

    $scope.remainTimeCC = function(row) {

        var remain_time = 0;
        var curr = moment(row.browser_time, "YYYY-MM-DD HH:mm:ss");
        if (row.browser_time != undefined)
            var duration = moment.duration(moment().diff(row.browser_time)).asSeconds();

        var current = curr.add(duration, 's');
            remain_time = moment(current, "YYYY-MM-DD HH:mm:ss") - moment(row.created_at, "YYYY-MM-DD HH:mm:ss") + 0;

        if (remain_time < 0)
                remain_time = 0;


        return remain_time;


    }

    function getGuestTaskItemList() {
        profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.type = 1;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/tasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.task_list_item = response.data;
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    initData();

    $scope.$on('$destroy', function() {
        console.debug("call_agentstatus controller leave");
    });

    hotkeys.add({
        combo: 'alt+1',
        description: 'This one goes to 11',
        callback: function() {
            $status = 'Available';
            console.log("Trying to Change Status To available")
            $scope.onChangeCallStatus($status);

        }
    });

    $scope.select_status_view = false;
    $scope.onClickStatus = function(status)
    {
      $scope.select_status = status + '';
      //  var propertyValue = item;
        if($scope.select_status == 'Online') {
            $scope.status_color = "background-color: #23b7e5; border: 1px solid #23b7e5; ";
            $scope.statuses = [
                'Available',
                'On Break',
                'Log out',
                'Away'
            ];
        }
        if($scope.select_status == 'Available') {
            $scope.status_color = "background-color: #27c24c; border: 1px solid #27c24c; ";
            $scope.statuses = [
                'Online',
                'On Break',
                'Log out',
                'Away'
            ];
        }
        if($scope.select_status == 'On Break') {
            $scope.status_color = "background-color: #fad733; border: 1px solid #fad733; ";
            $scope.statuses = [
                'Available',
                'Online',
                'Log out'
            ];
        }
        if($scope.select_status == 'Away') {
            $scope.status_color = "background-color: #fad733; border: 1px solid #fad733; ";
            $scope.statuses = [
                'Available',
                'Online',
                'Log out'
            ];
        }
        if($scope.select_status == 'Log out') {
            $scope.status_color = "background-color: #cfd8dc; border: 1px solid #cfd8dc; ";
            $scope.statuses = [
                //'Log out',
                'Online',
                'Available',
            ];
        }
        if($scope.select_status == 'Idle') {
            $scope.status_color = "background-color: #fad733; border: 1px solid #fad733; ";
            $scope.statuses = [
                'Available',
                'Online',
                'On Break',
                'Log out'
            ];
        }

        if($scope.select_status == 'Ringing') {
            $scope.status_color = "background-color: #f05050; border: 1px solid #e4b9b9; ";
            $scope.statuses = [
                'Ringing',
            ];
        }

        if($scope.select_status == 'Outgoing') {
            $scope.status_color = "background-color: #f05050; border: 1px solid #e4b9b9; ";
            $scope.statuses = [
                //'Outgoing',
                'Available',
                'Online',
                'On Break',
                'Log out',
            ];
        }
        if($scope.select_status == 'Wrapup') {
            $scope.status_color = "background-color: #337ab7; border: 1px solid #e4b9b9; ";
            $scope.statuses = [
                'Available',
                'On Break',
                'Log out',
            ];
        }

        if($scope.select_status == 'Busy') {
            $scope.status_color = "background-color: #f05050; border: 1px solid #e4b9b9; ";
            $scope.statuses = [
                'Busy',
            ];
        }

        if($rootScope.agent_status.status == 'Available' ||
            $rootScope.agent_status.status ==  'Online'||
            $rootScope.agent_status.status == 'On Break' ||
            $rootScope.agent_status.status == 'Log out' ||
            $rootScope.agent_status.status == 'Idle' ||
            $rootScope.agent_status.status == 'Wrapup' ||
            $rootScope.agent_status.status == 'Away' ||
            $rootScope.agent_status.status == 'Not Available') $scope.select_status_view = false;
        else $scope.select_status_view = true;
    }

    $scope.onClickStatus($rootScope.agent_status.status);

    $scope.onChangeExtension = function() {
        profile = AuthService.GetCredentials();
        var agentstatus = {};
        agentstatus.agent_id = profile.id;
        agentstatus.extension = $rootScope.agent_status.extension;
        agentstatus.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/call/changeextension',
            data: agentstatus,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Agent extension has been added successfully');
                changeExtension($rootScope.agent_status);
                console.log(response);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Agent extension');
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    $scope.extension_status = '';
    //$scope.extension_status = 'Transfer';
    $scope.transfer_number = '';
    $scope.onClickExtension = function() {
        $scope.extension_status = 'Transfer' ;
    }

    $scope.onClickTransfer = function () {
        $scope.transfer_number = $scope.dial.number ;
        if( $scope.transfer_number == '' )
        {
            toaster.pop('error', 'Please input Transfer Number');
            return;
        }

       // $scope.extension_status = '';

        // var request = {};
        // request.ticket_id = $rootScope.agent_status.ticket.id;
        // request.channel_id = $rootScope.agent_status.ticket.channel_id;
        // request.transfer_ext = $scope.transfer_number;
        // //$scope.transfer_number = '';
        // $http({
        //     method: 'POST',
        //     url: liveserver.api + 'transfer',
        //     data: request,
        //     headers: {'Content-Type': 'application/json; charset=utf-8'}
        // })
        //     .then(function(response) {
        //         console.log(response);
        //     }).catch(function(response) {
        //     })
        //     .finally(function() {
        //     });
    }

    $scope.onClickConference = function() {
        var request = {};
        $http({
            method: 'POST',
            url: liveserver.api + '/channels/' + $rootScope.agent_status.ticket.channel_id + '/hold',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }

    $scope.onClickHoldResume = function() {
        var request = {};

        request.ticket_id = $rootScope.agent_status.ticket.id;
        request.channel_id = $rootScope.agent_status.ticket.channel_id;
        request.status = $rootScope.agent_status.ticket.dial_status;

        $http({
            method: 'POST',
            url: liveserver.api + 'holdresume',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }

    $scope.onClickHangup = function() {
        var request = {};

        request.ticket_id = $rootScope.agent_status.ticket.id;
        request.channel_id = $rootScope.agent_status.ticket.channel_id;
        request.status = $rootScope.agent_status.ticket.dial_status;

        $http({
            method: 'POST',
            url: liveserver.api + 'hangup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }

    $scope.onClickMuteUnmute = function() {
        var request = {};

        request.ticket_id = $rootScope.agent_status.ticket.id;
        request.channel_id = $rootScope.agent_status.ticket.channel_id;
        request.status = $rootScope.agent_status.ticket.dial_status;
        if( $rootScope.agent_status.ticket.mute_flag == 0 )
            request.mute = 1;
        else
            request.mute = 0;

        $http({
            method: 'POST',
            url: liveserver.api + 'mute',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
            })
            .finally(function() {
            });
    }


    $scope.onClick = function(row){

        if (!$scope.auth_svc.isValidModule('app.access.agent.status')) {
            return;
        }

        if(!row.selected)
        row.selected=0;

        row.new_priority=row.priority;
        if(row.selected==0)
        row.selected=1;
        else
        row.selected=0;

    }

    $scope.onSelect = function(row) {

        if ($scope.user_id != row.user_id && !$scope.auth_svc.isValidModule('app.access.agent.status')) {
            return;
        }

        if(row.new_priority==row.priority)
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select new Priority');
            return;
        }
        var request = {};
        request.priority = row.new_priority;
        request.id = row.id;

        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/call/changequeuepriority',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Priority has been changed successfully');
                row.priority = row.new_priority;
                row.selected=0;
                console.log(response);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Priority');
                console.error('Log Out status error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;

            });
    }

    $scope.getCallTicketID = function(agent_status) {
        if( agent_status && agent_status.ticket )
            return sprintf('%06d', agent_status.ticket.id);
        else
            '';
    }


    $scope.onSaveProfile = function() {
        var request = {};
        profile = AuthService.GetCredentials();
        request = angular.copy($rootScope.agent_status.caller);

        request.property_id = profile.property_id;
        request.callerid = $rootScope.agent_status.ticket.callerid;

        request.user_id = profile.id;
        request.ticket_id = $rootScope.agent_status.ticket.id;
       // window.alert($scope.ticket.type);
        request.type = $scope.ticket.type;
        request.channel = $rootScope.agent_status.ticket.channel;
        request.comment = $scope.ticket.comment;
        request.follow = $scope.ticket.follow;
        request.success = $scope.ticket.success;
        request.confirm = $scope.ticket.confirm;
        request.sendconfirm = $scope.ticket.sendconfirm;


        $http({
            method: 'POST',
            url: '/frontend/call/savecalllog',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                toaster.pop('success', MESSAGE_TITLE, 'Caller profile has been added successfully');
                if($rootScope.agent_status.status == 'Outgoing')
                    $rootScope.agent_status.status = 'Available';
                $scope.ticket = {};
                $scope.ticket.type = 'Other';
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isChildLoading = false;
            });
    }

    $scope.category = '';
    $scope.onSelectCategory = function (selCategory) {
        $scope.category = selCategory;
        $scope.getDataList();
    }

    // $scope.refresh = $interval(function() {
    //     $scope.getDataListThird();
    // }, 5 * 1000);

    // $scope.$on('$destroy', function() {
    //     if (angular.isDefined($scope.refresh)) {
    //         $interval.cancel($scope.refresh);
    //         $scope.refresh = undefined;
    //     }
    // });

    $scope.answered = 0;
    $scope.miss = 0;
    $scope.abandoned = 0;
    $scope.followup = 0;
    $scope.callback = 0;
    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        request.user_id = $scope.bMyCall === true ? profile.id : 0;
        request.day = moment().format("YYYY-MM-DD");
        request.category = $scope.category;
        request.searchoption = search_option;

        $http({
            method: 'POST',
            url: '/frontend/call/agentcalllist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                $scope.answered = response.data.subcount.answered;
                $scope.miss = response.data.subcount.missed;
                $scope.abandoned = response.data.subcount.abandoned;
                $scope.followup = response.data.subcount.followup;
                $scope.outgoing = response.data.subcount.outgoing;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onRefresh = function() {
        $scope.searchtext = "";
        $scope.paginationOptions = {
            pageNumber: 1,
            pageSize: 20,
            sort: 'desc',
            field: 'ivr.id',
            totalItems: 0,
            numberOfPages : 1,
            countOfPages: 1
        };

        $scope.getDataList();
    };

    $scope.getDataListThird = function getDataListThird(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoadingthird = true;
        profile = AuthService.GetCredentials();

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptionsThird.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptionsThird.pageSize = pagination.number || $scope.paginationOptionsThird.pageSize;  // Number of entries showed per page.
            $scope.paginationOptionsThird.field = tableState.sort.predicate;
            $scope.paginationOptionsThird.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }


        var request = {};
        request.page = $scope.paginationOptionsThird.pageNumber;
        request.pagesize = $scope.paginationOptionsThird.pageSize;
        request.field = $scope.paginationOptionsThird.field;
        request.sort = $scope.paginationOptionsThird.sort;
        request.dept_id= profile.dept_id;
        request.user_id= profile.id;

        $http({
            method: 'POST',
            url: '/frontend/call/queuecalllist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {

                $scope.datalistthird = response.data.datalist;
                $scope.datalistthird.forEach(function(item, index) {
                    item.browser_time = moment();
                });
                if(response.data.totalcount != 0){
                   $scope.queue_flag = 1;
                }
                else
                    $scope.queue_flag = 0;

                $scope.paginationOptionsThird.totalItems = response.data.totalcount;
                $scope.total = response.data.totalcount;
                console.log($scope.datalistthird);
                var numberOfPages = 0;

                if( $scope.paginationOptionsThird.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptionsThird.totalItems - 1) / $scope.paginationOptionsThird.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptionsThird.countOfPages = numberOfPages;



                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoadingthird = false;
            });
    };

    $scope.answered = 0;
    $scope.miss = 0;
    $scope.abandoned = 0;
    $scope.followup = 0;
    $scope.callback = 0;
    $scope.queue = 0;
    $scope.outgoing = 0;

    $scope.edit = function(row){

        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/caller_edit.html',
            backdrop: 'static',
            size: 'lg',
            resolve: {
                caller: function () {
                    return row;
                }
            },
            controller: function ($scope, $rootScope, $uibModalInstance, AuthService, toaster, caller) {
                $scope.caller = caller;
                $scope.isLoadingCallerEdit = false;

                $scope.getCallTicket = function(caller) {
                    if( caller === undefined || caller == null )
                        return '';

                    return sprintf('%06d', caller.ticketid);
                };

                $scope.onUpdateProfile = function() {
                    var request = {};
                    profile = AuthService.GetCredentials();
                    request = angular.copy($scope.caller);

                    $scope.isLoadingCallerEdit = true;

                    $http({
                        method: 'POST',
                        url: '/frontend/call/updatecalllog',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .then(function(response) {
                            console.log(response);
                            toaster.pop('success', MESSAGE_TITLE, 'Caller profile has been updated successfully');
                            $uibModalInstance.close('ok');
                            row = $scope.caller;
                        }).catch(function(response) {
                        console.error('Gists error', response.status, response.data);
                    })
                        .finally(function() {
                            $scope.isLoadingCallerEdit = false;
                        });

                };

                $scope.onCancel = function () {
                    $uibModalInstance.dismiss();
                };

            },
        });

    };

    var removeBookRow = function(row, index) {
        var request = angular.copy(row);

        $http({
            method: 'POST',
            url: '/frontend/call/removephonebook',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if (response.data.status) {
                    toaster.pop("success", 'Delete Book Row', "Successfully removed book row");
                    $scope.dataListBook.splice(index, 1);
                    $scope.loadMoreBook.pageNumber--;
                } else {
                    toaster.pop("error", 'Delete Book Row', "Failed to remove");
                }
            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {
            });
    };

    $scope.onDownloadExcel = function(type) {
        profile = AuthService.GetCredentials();
        var request = {
            'user_id': profile.id,
            'property_id': profile.property_id,
            'type': type
        };

        $window.location.href = '/frontend/call/exportphonebook?' + $httpParamSerializer(request);
        // Block the user interface
    };

    $scope.onShowAddUpdateModal = function(row) {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/control_phonebook.html',
            resolve: {
                salutation: function () {
                    return row === undefined ? "" : row.salutation;
                },
                first_name: function () {
                    return row === undefined ? "" : row.first_name;
                },
                last_name: function () {
                    return row === undefined ? "" : row.last_name;
                },
                last_name: function () {
                    return row === undefined ? "" : row.last_name;
                },
                nationality: function () {
                    return row === undefined ? "" : row.nationality;
                },
                company: function () {
                    return row === undefined ? "" : row.company;
                },
                email: function () {
                    return row === undefined ? "" : row.email;
                },
                alt_no: function () {
                    return row === undefined ? "" : row.alt_no;
                },
                blacklist: function () {
                    return row === undefined ? "" : row.blacklist;
                },
                vip: function () {
                    return row === undefined ? "" : row.vip;
                },
                calledno: function() {
                    return row === undefined ? "" : row.calledno
                },
                id: function() {
                    return row === undefined ? 0: row.id
                }
            },
            controller: function ($scope, $rootScope, $uibModalInstance, AuthService,salutation, first_name, last_name,nationality, company, email, alt_no, blacklist, vip, calledno, id, toaster) {

                $scope.isLoadingAddModal = false;
                $scope.info = {};
                $scope.info.salutation = salutation;
                $scope.info.first_name = first_name;
                $scope.info.last_name = last_name;
                $scope.info.nationality = nationality;
                $scope.info.company = company;
                $scope.info.email = email;
                $scope.info.alt_no = alt_no;
                $scope.info.blacklist = blacklist;
                $scope.info.vip = vip;
                $scope.info.calledno = calledno;
                $scope.info.id = id;

                $scope.title = id === 0 ? "Add Contact" : "Update Contact";

                $scope.ok = function (e) {
                    $scope.isLoadingAddModal = true;

                    var request = $scope.info;
                    profile = AuthService.GetCredentials();
                    request.property_id = profile.property_id;
                    request.user_id = profile.id;

                    $http({
                        method: 'POST',
                        url: '/frontend/call/addusertophonebook',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .then(function(response) {
                            if (response.data.success === true) {
                                toaster.pop('success', "Result", response.data.message);
                                $uibModalInstance.close('ok');

                                if (row !== undefined) {
                                    row.salutation = $scope.info.salutation;
                                    row.first_name = $scope.info.first_name;
                                    row.last_name = $scope.info.last_name;
                                    row.nationality = $scope.info.nationality;
                                    row.company = $scope.info.company;
                                    row.email = $scope.info.email;
                                    row.alt_no = $scope.info.alt_no;
                                    row.blacklist = $scope.info.blacklist;
                                    row.vip = $scope.info.vip;
                                    row.calledno = $scope.info.calledno;
                                } else {
                                    $rootScope.$emit('callSearchBook', {});
                                }

                            } else {
                                toaster.pop('warning', "Result", response.data.message);
                            }
                        }).catch(function(response) {
                        console.error('Gists error', response.status, response.data);
                    })
                        .finally(function() {
                            $scope.isLoadingAddModal = false;
                        });
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();
                };
            },
        });

    };


    $scope.onRemoveBookRow = function(row, index) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Do you really need to remove?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',
            resolve: {
                message: function () {
                    return message;
                }
            },
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {

                    removeBookRow(row, index);
                    $uibModalInstance.close('ok');
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();
                };
            },
        });
    };



    $scope.onLoadMoreBook = function() {
        $scope.loadMoreBook.pageNumber += $scope.loadMoreBook.pageSize;

        $scope.getCallPhonebook();
    };

    $scope.onSearchBook = function() {
        $scope.dataListBook = [];
        $scope.loadMoreBook.pageNumber = 0;
        $scope.loadMoreBook.curCount = 20;

        $scope.getCallPhonebook();
    };

    var checkInclude = function(arr, checkItem) {
        let bResult = false;

        for (let i = 0; i < arr.length; i++) {
            let item = removeMarks(arr[i], '"');
            if (item === checkItem) {
                bResult = true;
                arr[i] = checkItem;
                break;
            }
        }

        return bResult;
    };

    $scope.onCallFromPhoneNumber = function(phonenumber) {
        let phoneElement = $('#phone_number');
        let phoneButton = $('#btn_call button');

        if (phoneElement && phoneButton) {
            phoneElement.val("phonenumber");
            phoneButton.trigger("click");
        }
    };

    $scope.getCallPhonebook = function getCallPhonebook(tableState = undefined) {
        //here you could create a query string from tableState
        //fake ajax call

        if ($scope.loadMoreBook.curCount < $scope.loadMoreBook.pageSize) {
            return;
        }

        var request = {};
        request.page = $scope.loadMoreBook.pageNumber;
        request.pagesize = $scope.loadMoreBook.pageSize;
        request.field = $scope.loadMoreBook.field;
        request.sort = $scope.loadMoreBook.sort;
        request.searchoption = $scope.searchtextBook;

        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;

        $scope.isLoadingBook = true;

        $scope.isLoadingDownload = false;

        $http({
            method: 'POST',
            url: '/frontend/call/callphonebook',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.dataListBook = $scope.dataListBook.concat(response.data.datalist);
                $scope.loadMoreBook.curCount = response.data.datalist.length;
            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {
                $scope.isLoadingBook = false;
            });
    };

    var onLoadExcelModal = function(excelArr) {

        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/load_excel_data.html',
            backdrop: 'static',
            windowClass: "load-excel-modal",
            size: 'lg',
            resolve: {
                loadData: function () {
                    return excelArr;
                }
            },
            controller: function ($scope, $rootScope, $uibModalInstance, AuthService, loadData, toaster) {

                $scope.excelDataArr = loadData;
                $scope.isLoadingExcelData = false;

                $scope.tempRow = {
                    'salutation': '',
                    'first_name': '',
                    'last_name': '',
                    'nationality': '',
                    'company': '',
                    'email': '',
                    'alt_no': '',
                    'blacklist': '',
                    'vip': '',
                    'calledno': ''
                };

                $scope.onRowEdit = function(row) {
                  if (row.bEdit) { // save
                      row.salutation = $scope.tempRow.salutation;
                      row.first_name = $scope.tempRow.first_name;
                      row.last_name = $scope.tempRow.last_name;
                      row.nationality = $scope.tempRow.nationality;
                      row.company = $scope.tempRow.company;
                      row.email = $scope.tempRow.email;
                      row.alt_no = $scope.tempRow.alt_no;
                      row.blacklist = $scope.tempRow.blacklist;
                      row.vip = $scope.tempRow.vip;
                      row.calledno = $scope.tempRow.calledno;

                      row.bEdit = false;
                  } else { // edit
                      $scope.tempRow.salutation = row.salutation;
                      $scope.tempRow.first_name = row.first_name;
                      $scope.tempRow.last_name = row.last_name;
                      $scope.tempRow.nationality = row.nationality;
                      $scope.tempRow.company = row.company;
                      $scope.tempRow.email = row.email;
                      $scope.tempRow.alt_no = row.alt_no;
                      $scope.tempRow.blacklist = row.blacklist;
                      $scope.tempRow.vip = row.vip;
                      $scope.tempRow.calledno = row.calledno;
                      row.bEdit = true;
                  }
                };

                $scope.onRowRemove = function(row, index) {
                    if (row.bEdit) { // cancel
                        row.bEdit = false;
                    } else { //remove
                        if (index >= 0) {
                            $scope.excelDataArr.splice(index, 1);
                        }
                    }
                };

                $scope.ok = function (e) {

                    $scope.isLoadingExcelData = true;

                    var request = {};
                    request.data = $scope.excelDataArr;
                    profile = AuthService.GetCredentials();
                    request.property_id = profile.property_id;
                    request.user_id = profile.id;

                    $http({
                        method: 'POST',
                        url: '/frontend/call/addexceldata',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .then(function(response) {
                            toaster.pop('success', "Notification", "Successfully saved!");
                            $uibModalInstance.close('ok');
                            $rootScope.$emit('callSearchBook', {});
                        }).catch(function(response) {
                        console.error('Gists error', response.status, response.data);
                    })
                        .finally(function() {
                            $scope.isLoadingAddModal = false;
                        });
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();
                };
            },
        });

    };

    var removeMarks = function(str, mark) {
        while (true) {
            str = str.replace(mark, '');

            if (str.indexOf(mark) < 0) {
                break;
            }
        }

        return str;
    };

    $scope.onShowUploadModal = function(event) {
        var files = event.target.files;
        if (files.length > 0) {
            var file = files[0];
            let fileReader = new FileReader();

            if (file.name.includes(".csv")) {
                fileReader.onload = (e) => {
                    var rows = e.target.result.trim().split("\n");
                    var resultArr = [];
                    var keys = [];

                    if (rows.length > 0) {
                        keys = rows[0].trim().split(',');
                    }

                    if (keys.length < 1 ) {
                        toaster.pop("info", "Uploading issue", "There is no data!");
                        return;
                    }

                    if (checkInclude(keys, "salutation") && checkInclude(keys, "first_name") && checkInclude(keys, "last_name") && checkInclude(keys, "nationality") && checkInclude(keys, "company") && checkInclude(keys, "email") && checkInclude(keys, "alt_no") && checkInclude(keys, "blacklist") && checkInclude(keys, "vip") && checkInclude(keys, "calledno")) {
                        for (let i = 1; i < rows.length; i++) {
                            let row = rows[i];
                            let tempArr = row.trim().split(',');

                            let tempObj = {};
                            for (let j = 0; j < tempArr.length; j++) {
                                tempObj[keys[j]] = removeMarks(tempArr[j], '"');
                            }

                            resultArr.push(tempObj);
                        }

                        onLoadExcelModal(resultArr);
                    } else {
                        toaster.pop("warning", "Uploading issue", "This is not phone book structure");
                    }
                };

                fileReader.readAsText(file);
            } else {
                fileReader.onload = (e) => {
                    var arrayBuffer = fileReader.result;
                    var data = new Uint8Array(arrayBuffer);
                    var arr = new Array();
                    for(var i = 0; i < data.length; i++) {
                        arr[i] = String.fromCharCode(data[i]);
                    }
                    var bstr = arr.join("");
                    var workbook = XLSX.read(bstr, {type:"binary"});
                    var first_sheet_name = workbook.SheetNames[0];
                    var worksheet = workbook.Sheets[first_sheet_name];
                    console.log(XLSX.utils.sheet_to_json(worksheet,{raw:true}));

                    let resultArr = [];
                    resultArr = XLSX.utils.sheet_to_json(worksheet,{raw:true});

                    if (resultArr.length < 1) {
                        toaster.pop("warning", 'Upload issue', 'There is no data!');
                        return;
                    }
                    let keys = [];
                    keys = Object.keys(resultArr[0]);

                    if (keys.includes("salutation") && keys.includes("first_name") && keys.includes("last_name") && keys.includes("nationality") && keys.includes("company") && keys.includes("email") && keys.includes("alt_no") && keys.includes("blacklist") && keys.includes("vip") && keys.includes("calledno")) {
                        onLoadExcelModal(resultArr);
                    } else {
                        toaster.pop("warning", "Uploading issue", "This is not phone book structure");
                    }
                };

                fileReader.readAsArrayBuffer(file);
            }
        }
    }



    $scope.getDataListSecond = function getDataListSecond(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptionsSecond.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptionsSecond.pageSize = pagination.number || $scope.paginationOptionsSecond.pageSize;  // Number of entries showed per page.
            $scope.paginationOptionsSecond.field = tableState.sort.predicate;
            $scope.paginationOptionsSecond.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.page = $scope.paginationOptionsSecond.pageNumber;
        request.pagesize = $scope.paginationOptionsSecond.pageSize;
        request.field = $scope.paginationOptionsSecond.field;
        request.sort = $scope.paginationOptionsSecond.sort;

        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        if( $rootScope.agent_status && $rootScope.agent_status.ticket && $rootScope.agent_status.ticket.callerid )
        {
            $scope.isLoadingSecond = true;
            request.caller_id = $rootScope.agent_status.ticket.callerid;
            $http({
                method: 'POST',
                url: '/frontend/call/agentcalllist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    $scope.datalistSecond = response.data.datalist;
                    $scope.paginationOptionsSecond.totalItems = response.data.totalcount;

                    var numberOfPages = 0;

                    if( $scope.paginationOptionsSecond.totalItems < 1 )
                        numberOfPages = 0;
                    else
                        numberOfPages = parseInt(($scope.paginationOptionsSecond.totalItems - 1) / $scope.paginationOptionsSecond.pageSize + 1);

                    if( tableState != undefined )
                        tableState.pagination.numberOfPages = numberOfPages;
                    else
                        $scope.tableState.pagination.numberOfPages = numberOfPages;

                    $scope.paginationOptionsSecond.countOfPages = numberOfPages;

                    console.log(response);
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                    $scope.isLoadingSecond = false;
                });
        }
    };

    $scope.call_detail = {};

    function getCallDetail() {
        var request = {};
        request.ticket_id = $rootScope.agent_status.ticket.id;
        $http({
            method: 'POST',
            url: '/frontend/call/getcalldetail',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.call_detail = response.data.content;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoadingSecond = false;
            });

    };

    function getCallDetailForTest() {
        var request = {};
        request.ticket_id = 6568;
        $http({
            method: 'POST',
            url: '/frontend/call/getcalldetail',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.call_detail = response.data.content;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoadingSecond = false;
            });

    };

    getCallDetailForTest();

    $scope.onLoadMoreCallHistory = function() {
        console.log('onLoadMoreCallHistory');
    }

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.isCallbackLoading = false;
    $scope.getCallbackList = function() {
        var request = {};
        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        $scope.isCallbackLoading = true;
        $http({
            method: 'POST',
            url: '/frontend/call/callbacklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.callbacklist = response.data.datalist;
                $scope.callback = response.data.callback;
                $scope.callback_take = response.data.callback_take;
                $scope.followup = response.data.followup;
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isCallbackLoading = false;
            });
    }
    $scope.commentCallback = function (row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/callcomment.html',
            controller: 'CommentCtrl',
            windowClass: 'app-modal-window',
            resolve: {
                call: function () {
                    return row;
                },
                type: function () {
                    return 'callback';
                },
                types: function(){
                    return $scope.types;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }
    $scope.commentMissed = function (row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/callcomment.html',
            controller: 'CommentCtrl',
            windowClass: 'app-modal-window',
            resolve: {
                call: function () {
                    return row;
                },
                type: function () {
                    return 'missed';
                },
                types: function () {
                    return $scope.types;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }
    $scope.commentAbandon = function (row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/callcomment.html',
            controller: 'CommentCtrl',
            windowClass: 'app-modal-window',
            resolve: {
                call: function () {
                    return row;
                },
                type: function () {
                    return 'abandoned';
                },
                types: function () {
                    return $scope.types;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.onTakeCallback = function(row) {
        var request = {};
        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.ticket_id = row.id;

        $http({
            method: 'POST',
            url: '/frontend/call/takecallback',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                if(response.data.code==201)
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
                else
                {
                    //window.alert(JSON.stringify(response.data));
                    if (response.data.callback_flag==2)
                        $scope.getCallbackList();
                    else if (response.data.missed_flag == 2)
                        $scope.getMissedList();
                    else if (response.data.abandon_flag == 2)
                        $scope.getAbandonList();
                }
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }
    $scope.getMissedList = function () {
        var request = {};
        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        $scope.isMissedLoading = true;
        $http({
            method: 'POST',
            url: '/frontend/call/missedlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.missedlist = response.data.datalist;
                $scope.missed_take = response.data.missed_take;
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isMissedLoading = false;
            });
    }

    $scope.getAbandonList = function () {
        var request = {};
        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        $scope.isAbandonLoading = true;
        $http({
            method: 'POST',
            url: '/frontend/call/abandonlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.abandonlist = response.data.datalist;
                $scope.abandon_take = response.data.abandon_take;
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isAbandonLoading = false;
            });
    }

    $scope.viewDay = function(day) {

    }
     $scope.Ticketnumber = function(ticket){
         return sprintf('%05d', ticket);
         if( ticket == undefined )
             return 0;
    }

    $scope.$on('call_event', function(event, args) {
        console.log(args);
        profile = AuthService.GetCredentials();
        if(args.user_id != profile.id )
            return;

        $scope.getDataList();
        $scope.getDataListSecond();
        getCallDetail();

        $scope.onClickStatus($rootScope.agent_status.status);
    });

    $scope.$on('agent_status_change', function(event, args) {
        console.log(args);
        profile = AuthService.GetCredentials();
        if(args.user_id != profile.id )
            return;

        // if( $rootScope.agent_status.status == 'Online' )
        // {
        //     $scope.onChangeExtension();
        // }

        $scope.onClickStatus($rootScope.agent_status.status);
        getAgentList();
    });

    $scope.$on('callback_event', function(event, args) {
        console.log(args);
        $scope.getCallbackList();
    });

    $scope.$on('queue_event', function(event, args) {
        console.log(args);
        $scope.getDataListThird();
    });

    function changeExtension(agent_status)
    {
        if( $scope.callcenter_config.softphone_enabled == false )
            return;

        console.log(agent_status);

        var ext = $scope.extensionlist.find(item => item.extension == agent_status.extension);
        if( !ext )
            return;

        var iframe = document.getElementById("softphone");
        iframe.contentWindow.setSipSetting(ext, $rootScope.sip_server);
        iframe.contentWindow.setContactList(sip_contact_list);
    }

    $scope.dial = {};
    $scope.dial.number = '';
    var dialnumberorigin = '';
    //$scope.agent_status.status = 'Busy';
    //$scope.extension_status = 'Transfer';
    $scope.onDial = function(event,number) {
        var current_number = '';
        var keyCode = event.which || event.keyCode;
        if(number == 'key') {

        }
        if(number != 'key') {
            if ( $scope.dial.number == undefined)  $scope.dial.number = '';
            if (number == 'del') {
                $scope.dial.number = '';
            } else if (number == 'back') {
                $scope.dial.number =  $scope.dial.number.substr(0,  $scope.dial.number.length - 1);
            } else {
                current_number =  $scope.dial.number + number;
                $scope.dial.number = current_number;
                dialnumberorigin = $scope.dial.number;
            }
            focus('text');
        }
    }

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }



    $scope.callAgent = function(callerid) {
        console.log(callerid);
        if( $scope.callcenter_config.softphone_enabled == false )
            return;

        if( $rootScope.agent_status.status == 'Available')
        {
            var iframe = document.getElementById("softphone");
            iframe.contentWindow.webphone_api.call(callerid);
        }
    }

    $scope.takeQueueCall = function(row) {
        console.log(row);
        var request = {};
        profile = AuthService.GetCredentials();
        request.caller_id = row.callerid;
        request.agent_id = profile.id;
        request.id = row.id;

            $http({
            method: 'POST',
            url: '/frontend/call/takequeuecall',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }

    $scope.callCallbackAgent = function(row) {
        $scope.callAgent(row.callerid);
        if( $rootScope.agent_status.status == 'Available')
        {
            $scope.onTakeCallback(row);
        }
    }

    $scope.onShowPhonebook = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/phonebook.html',
            controller: 'PhonebookCtrl',
            scope: $scope,
        });
        modalInstance.result.then(function () {
            addUserToPhonebook();
        }, function () {

        });
    }

    function addUserToPhonebook() {
        var request = $scope.call_detail;
        profile = AuthService.GetCredentials();

        request.user_id = profile.id;
        request.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/call/addusertophonebook',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if (response.data.success === true) {
                    toaster.pop('success', "Notice!", response.data.message);
                    $scope.onSearchBook();
                } else {
                    toaster.pop('warning', "Notice!", response.data.message);
                }
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoadingSecond = false;
            });

    };

    $scope.call_agent_page = true;
    $scope.duration_disabled = true;
    $scope.feedback_hide_flag = true;
    $scope.attatch_no_need_flag = true;


    $scope.$on('checkin_room_selected', function (event, args) {
        room = sip_contact_list.find(function(item) {
            if( item.type == 2 && item.room_id == args.room_id)
                return item;
        });

        function postClose(filter) {
            $rootScope.asideState.open = false;
        }

        if( room )
        {
            $scope.$broadcast('close_dialog', {});

            var template_url = 'tpl/calls/modal/guestservice.aside.html';
            var controllerName = 'GuestrequestController';

            $aside.open({
                templateUrl: template_url,
                placement: 'right',
                scope: $scope,
                size: 'lg',
                backdrop: true,
                controller: controllerName,
            }).result.then(postClose, postClose);

            $timeout(function() {
                $scope.$broadcast('room_selected', room);
            }, 2000);
        }

        $timeout(function() {
            $scope.getPreviousHistory();
        }, 2000);
    });

    $scope.showRequest = function(num) {

    };

    $scope.$on('checkout_room_selected', function (event, args) {
        function postClose(filter) {
            $rootScope.asideState.open = false;
        }

        $scope.$broadcast('close_dialog', {});

        var template_url = 'tpl/calls/modal/departmentrequest.aside.html';
        var controllerName = 'DepartmentrequestController';


        $aside.open({
            templateUrl: template_url,
            placement: 'right',
            scope: $scope,
            size: 'lg',
            backdrop: true,
            controller: controllerName,
        }).result.then(postClose, postClose);

        $timeout(function() {
            $scope.$broadcast('checkout_room_selected1', args);
            $scope.getPreviousHistory();
        }, 2000);
    });

    $scope.openGuestServicePanel = function() {
        var template_url = 'tpl/calls/modal/departmentrequest.aside.html';
        var controllerName = 'DepartmentrequestController';

        var room = undefined;

        if( $rootScope.agent_status.ticket &&
            ($rootScope.agent_status.status == 'Ringing' ||
            $rootScope.agent_status.status == 'Wrapup' ||
            $rootScope.agent_status.status == 'Busy' ) )
        {
            var caller_id = $rootScope.agent_status.ticket.callerid;

            room = sip_contact_list.find(function(item) {
                if( item.type == 2 && item.extension == caller_id)
                    return item;
            });
        }

        $rootScope.asideState = {
            open: true,
        };

        function postClose(filter) {
            $rootScope.asideState.open = false;
        }

        if( room )
        {
            template_url = 'tpl/calls/modal/guestservice.aside.html';
            controllerName = 'GuestrequestController';
        }

        $scope.selectedTickets = [];
        $scope.selected_index = 100;
        $scope.filter_value = '';

        $aside.open({
            templateUrl: template_url,
            placement: 'right',
            scope: $scope,
            size: 'lg',
            backdrop: true,
            controller: controllerName,
        }).result.then(postClose, postClose);


        if( room )
        {
            $timeout(function() {
                $scope.$broadcast('room_selected', room);
            }, 2000);
        }

        $timeout(function() {
            $scope.selected_index = 100;
        }, 2000);
    }

    $scope.uploadImages = function(ticket_number_id, tasks) {
        var upload_item_count = 0;

        ticket_number_id.forEach(item => {
            var num = item.num;
            var id = item.id;

            var files = tasks[num].files;
            if( files && files.length > 0 )
            {
                upload_item_count++;
                Upload.upload({
                    url: '/frontend/guestservice/uploadfiles',
                    data: {
                        id: id,
                        files: files
                    }
                }).then(function (response) {

                }, function (response) {
                }, function (evt) {
                });
            }
        });


        return upload_item_count;
    }

    $scope.remainTime = function(row) {
        // return 1000;
        return GuestService.getRemainTime(row);
    }

    $scope.removeNewSelectTicket = function() {
        // $aside.close();
    }

    $scope.filter_value = '';
    $scope.searchTicket = function(filter_value) {
        $scope.filter_value = filter_value;
        $scope.getPreviousHistory();
    }

    $scope.getPreviousHistory = function() {
        $scope.prev_history = [];

        profile = AuthService.GetCredentials();

        var request = {};
        request.page = 0;
        request.pagesize = 20;
        request.sort = 'desc';
        request.property_id = profile.property_id;
        request.attendant = profile.id;
        request.lang = profile.lang_id;
        request.searchoption = $scope.filter_value;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/ticketlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.prev_history = response.data.ticketlist;
            $scope.prev_history.forEach(function(item, index) {
                item.status_css = GuestService.getStatusCss(item);
                item.ticket_no = GuestService.getTicketNumber(item);
                item.status_css_edit = GuestService.getStatusCssInEdit(item);
                item.status = GuestService.getStatus(item);
                item.priority_css = GuestService.getPriorityCss(item);
                item.ticket_item_name = GuestService.getTicketNameForList(item);
            });
        }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function() {
        });
    }

    $scope.$on('onTicketChange', function(event, args){
        $scope.getPreviousHistory();
    });

    $scope.selectedTickets = [];
    $scope.selected_index = 100;
    $scope.onSelectTicket = function(ticket) {
        $timeout(function(){

            // check select ticket
            var index = -1;
            for(var i = 0; i < $scope.selectedTickets.length; i++)
            {
                if( ticket.id == $scope.selectedTickets[i].id )
                {
                    index = i;
                    break;
                }
            }

            if( index < 0 )    // not selected
            {
                //ticket.active = 1;
                $scope.selectedTickets.push(angular.copy(ticket));
                $timeout(function(){
                    $scope.selected_index = 100 + ticket.id;
                }, 100);
            }
            else {
                //ticket.active = false;
                $scope.selectedTickets.splice(index, 1);
                index = -1;
                $scope.selected_index = 2;
            }
        }, 100);
    }

    $scope.openWakeupPanel = function() {
        var template_url = 'tpl/calls/modal/wakeup.aside.html';
        var controllerName = 'WakeupCreateController';

        var room = undefined;

        if( $rootScope.agent_status.ticket )
        {
            var caller_id = $rootScope.agent_status.ticket.callerid;

            room = sip_contact_list.find(function(item) {
                if( item.type == 2 && item.extension == caller_id)
                    return item;
            });
        }


        $rootScope.asideState = {
            open: true,
        };

        function postClose(filter) {
            $rootScope.asideState.open = false;
        }

        $aside.open({
            templateUrl: template_url,
            placement: 'right',
            scope: $scope,
            size: 'lg',
            backdrop: true,
            controller: controllerName,
        }).result.then(postClose, postClose);

        if( room )
        {
            $timeout(function() {
                $scope.$broadcast('room_selected', room);
            }, 2000);
        }
    }

    $scope.openManualPostingPanel = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/modal/manual_posting.html',
            controller: 'ManualPostingCtrl',
            windowClass: 'app-modal-window',
            size: 'lg',
            resolve: {
                roomlist: function () {
                    return $scope.room_list;
                },
                buildings: function () {
                    return $scope.buildings;
                }
            }
        });

        modalInstance.result.then(function () {
        }, function () {

        });
    }

    //  pagination
    $scope.paginationOptionsWakeup = {
        pageNumber: 1,
        pageSize: 25,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.wakeup_list = [];
    $scope.getWakeupList = function getWakeupList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptionsWakeup.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptionsWakeup.pageSize = pagination.number || $scope.paginationOptionsWakeup.pageSize;  // Number of entries showed per page.
            $scope.paginationOptionsWakeup.field = tableState.sort.predicate;
            $scope.paginationOptionsWakeup.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        /////////////////////
        var request = {};
        request.page = $scope.paginationOptionsWakeup.pageNumber;
        request.pagesize = $scope.paginationOptionsWakeup.pageSize;
        request.field = $scope.paginationOptionsWakeup.field;
        request.sort = $scope.paginationOptionsWakeup.sort;
        request.filter = $scope.filter;
        request.searchoption = search_option;

        request.start_date = moment().add(-7, 'Days').format('YYYY-MM-DD');
        request.end_date = moment().format('YYYY-MM-DD');

        profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/wakeup/list',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.wakup_list = response.data.datalist;
                $scope.paginationOptionsWakeup.totalItems = response.data.totalcount;
                $scope.subcount = response.data.subcount;

                var numberOfPages = 0;

                if( $scope.paginationOptionsWakeup.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptionsWakeup.totalItems - 1) / $scope.paginationOptionsWakeup.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptionsWakeup.countOfPages = numberOfPages;


                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    document.onkeyup = function(e) {
        console.log(e.altKey, e.which);
        if( $scope.callcenter_config.softphone_enabled == false )
            return;

        var iframe = document.getElementById("softphone");
        iframe.contentWindow.dispatchKeyEvent(e);
    };

    document.onkeydown = function(e) {
        console.log(e.altKey, e.which);
        if( $scope.callcenter_config.softphone_enabled == false )
            return;

        var iframe = document.getElementById("softphone");
        if( iframe && iframe.contentWindow && iframe.contentWindow.dispatchKeyEvent1 )
            iframe.contentWindow.dispatchKeyEvent1(e);
    };

    $scope.moveUp = function(row) {
        var request = {};
        request.id = row.id;

        $http({
            method: 'POST',
            url: '/frontend/call/moveup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getDataListThird();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    $scope.moveDown = function(row) {
        var request = {};
        request.id = row.id;

        $http({
            method: 'POST',
            url: '/frontend/call/movedown',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getDataListThird();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    $scope.agent_list = [];
    threshold_setting = {};

    var agentLoadMoreOption = {
        number: 0,
        targetSize : 20,
        loadedSize : 20
    };

    profile = AuthService.GetCredentials();
    function getAgentList() {
        $http.get('/frontend/call/agentlist1?property_id=' + profile.property_id + '&size=' + agentLoadMoreOption.targetSize + '&number=' + agentLoadMoreOption.number)
            .then(function(response) {
                $scope.agent_list = response.data.agentlist;
                agentLoadMoreOption.loadedSize = response.data.agentlist.length;
                threshold_setting = response.data.threshold;
            });
    }
    getAgentList();

    $scope.onLoadMoreAgentList = function() {
        if (agentLoadMoreOption.loadedSize < agentLoadMoreOption.targetSize) {
            return;
        }
        agentLoadMoreOption.number ++;

        getAgentList();
    };

    $scope.getDuration = function(row) {
        var duration =  moment.utc(moment().diff(moment(row.created_at,"YYYY-MM-DD HH:mm:ss"))).format("HH:mm:ss");
        // row.status = 'Busy';
        // duration = '00:00:15';
        row.duration_style = getWarningStyleForTime(threshold_setting, 'current_call_dur', duration);

        if( row.status != 'Busy' )
            row.duration_style = {};

        return duration;
    }

    function timeToSec(tm)
    {
        if( !tm )
            return 0;

        if( tm.length > 5 )
            return moment(tm + '', 'HH:mm:ss').diff(moment().startOf('day'), 'seconds');

        return moment('00:' + tm + '', 'HH:mm:ss').diff(moment().startOf('day'), 'seconds');
    }

    function getWarningStyleForTime(threshold, key, val)
    {
        var yellow = timeToSec(threshold['call_center_' + key + '_yellow']);
        var red = timeToSec(threshold['call_center_' + key + '_red']);
        var value = timeToSec(val);
        var color = 'black';
        if( value < yellow )
            color = '';
        else if( value < red )
            color = '#f3a83b';
        else
            color = '#eb3223';

        var style = {'color': color};

        return style;
    }

});

app.controller('CommentCtrl', function ($scope, $rootScope, $window, call,type, types, $uibModalInstance, AuthService, $http, toaster, $interval) {

    var MESSAGE_TITLE = 'Call Center';
     $scope.call = call;
    $scope.types = types;
    $scope.stat = type[0].toUpperCase()+(type.substr(1,type.length-1));
    //window.alert(type);
    $scope.comment_disable = false;
    // $scope.call.contact_name = '';
    // $scope.call.contact_no = '';
    // $scope.call.auto_classify = 0;
    // $scope.call.classify_comment = '';
    // $scope.call.type = 'Personal';
    //window.alert(JSON.stringify(types));
    var flag=0;
    $scope.types.filter(function (item) {
        if(item.label=='Other')
            {
                flag=1;
                //break
            }
    });
    if(flag!=1)
    $scope.types.push({id:($scope.types.length-1), label:'Other'});

    $scope.type = $scope.call.type;
//window.alert($scope.type);
    $scope.added = 'Update';
    //window.alert($scope.call.comment);
    if ($scope.call.comment!=null)
    {
        $scope.comment_disable=true;
        $scope.added = 'View';
    }



    $scope.addComment = function () {
        if($scope.call.comment.length<4)
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter valid comment');
            return
        }
        if (($scope.call.type == $scope.type) && $scope.comment_disable==true) {
            toaster.pop('error', MESSAGE_TITLE, 'Please select a different Call Type');
            return
        }

        var request = {};

        var profile = AuthService.GetCredentials();
        request.user_id = profile.id;

        request.call = $scope.call;
        //request.type = $scope.call.type;
        /*
          if ((preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', request.contact_no)))
          {
              toaster.pop('Success', MESSAGE_TITLE, 'Error');
          }
      */


        $http({
            method: 'POST',
            url: '/frontend/call/addcomment/'+type,
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if (response.data.code == 201)
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
                else
                {
                if($scope.comment_disable != true)
                    toaster.pop('success', MESSAGE_TITLE, 'Comment has been added successfully');
                else
                    toaster.pop('success', MESSAGE_TITLE, 'Details have been updated successfully');
                }
                //$window.location.reload();
                $scope.cancel();
                console.log(response);

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });

    };

    $scope.onDelete = function (row) {
        var data = {};
        data.id = row.id;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/deletephonebook',
            data: data,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                console.log(response.data);
                $scope.getHist();
                toaster.pop('success', MESSAGE_TITLE, 'Entry has been deleted successfully');

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Entry');
            })
            .finally(function () {
            });
    }



    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});


app.controller('PhonebookCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Phonebook';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});

