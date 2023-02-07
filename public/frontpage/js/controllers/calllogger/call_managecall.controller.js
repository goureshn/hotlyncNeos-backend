app.config(['$compileProvider', function($compileProvider) {
    $compileProvider.imgSrcSanitizationWhitelist(/^\s*(https?|local|data):/);
}]);

app.controller('ManagecallController', function ($scope, $rootScope, $http, $timeout, $window, $interval, AuthService, CountryService, toaster,$uibModal, liveserver, $filter) {
    var MESSAGE_TITLE = 'Manage Call Controller';
    //$scope.screenheight = $window.innerHeight;

    if( $rootScope.agent_status.caller == undefined )
        $rootScope.agent_status.caller = {};

    $scope.ticket = {};

    var search_option = '';

    $scope.countrylist = CountryService.countrylist;

    $scope.ticket.type = 'Other';
    $scope.ticket.sendconfirm = 'Email';

    // pip
    $scope.isLoading = false;
    $scope.datalist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'vr.id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.isLoadingSecond = false;
    $scope.datalistSecond = [];
    //  pagination
    $scope.paginationOptionsSecond = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'vr.id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.statuses = [
        'Online',
        'Available',
        'On Break',
        'Not Available',
        'Log out'
    ];
    $scope.onShowImage = function() {
        var size = 'lg';
        var modalInstance = $uibModal.open({
            templateUrl: 'imageLoadModal.html',
            controller: 'ImgLoadCropCtrl',
            size: size
        });
    }

    function initData() {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/call/agentextlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.extensionlist = response.data;
            }).catch(function(response) {

            })
            .finally(function() {
            });

        $scope.select_status = $rootScope.agent_status.status;
    }

    initData();
    $scope.select_status_view = false;
    $scope.changeProperty = function()
    {
        var propertyValue = $scope.select_status;
        //  var propertyValue = item;
        if(propertyValue == 'Online') {
            $scope.statuses = [
                'Online',
                'On Break',
                'Available',
                'Log out'
            ];
        }
        if(propertyValue == 'Available') {
            $scope.statuses = [
                'Available',
                'On Break',
                'Log out',
            ];
        }
        if(propertyValue == 'On Break') {
            $scope.statuses = [
                'On Break',
                'Available',
                'Log out'
            ];
        }
        if(propertyValue == 'Log out') {
            $scope.statuses = [
                'Log out',
                'Available',
            ];
        }
        if(propertyValue == 'Idle') {
            $scope.statuses = [
                'Idle',
                'Available',
                'On Break',
                'Log out'
            ];
        }

        if(propertyValue == 'Ringing') {
            $scope.statuses = [
                'Ringing',
            ];
        }

        if(propertyValue == 'Busy') {
            $scope.statuses = [
                'Busy',
            ];
        }


        $scope.select_status = $scope.statuses[0];
        if($rootScope.agent_status.status == 'Available' ||
            $rootScope.agent_status.status ==  'Online'||
            $rootScope.agent_status.status == 'On Break' ||
            $rootScope.agent_status.status == 'Log out' ||
            $rootScope.agent_status.status == 'Idle' ||
            $rootScope.agent_status.status == 'Not Available') $scope.select_status_view = false;
        else $scope.select_status_view = true;
    }
    $scope.changeProperty();
    $scope.onChangeCallStatus = function() {

        var root_status = $rootScope.agent_status.status;
        var page_status = $scope.select_status;
        if(root_status == page_status) return ;
        var agentstatus = {};
        var profile = AuthService.GetCredentials();
        agentstatus.agent_id = profile.id;
        agentstatus.status = $scope.select_status;
        agentstatus.extension = $rootScope.agent_status.extension;

        var profile = AuthService.GetCredentials();
        agentstatus.property_id = profile.property_id;

        if(agentstatus.status&&agentstatus.extension) {
            $http({
                method: 'POST',
                url: '/frontend/call/changestatus',
                data: agentstatus,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('success', MESSAGE_TITLE, 'Agent status has been added successfully');
                    $rootScope.agent_status.status = response.data.status;
                    $rootScope.agent_status.created_at = response.data.created_at;
                    $window.document.title = 'HotLync | Ennovatech';
                    $scope.changeProperty();

                    console.log(response);
                }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Agent status');
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                    $scope.isLoading = false;
                });
        }
    }

    $scope.extension_status = '';
    $scope.transfer_number = '';
    $scope.onClickExtension = function() {
        $scope.extension_status = 'Transfer' ;
    }

    $scope.onClickTransfer = function() {
        if( $scope.transfer_number == '' )
        {
            toaster.pop('error', 'Please input Transfer Number');
            return;
        }

        $scope.extension_status = '';

        var request = {};
        request.ticket_id = $rootScope.agent_status.ticket.id;
        request.channel_id = $rootScope.agent_status.ticket.channel_id;
        request.transfer_ext = $scope.transfer_number;
        $scope.transfer_number = '';

        $http({
            method: 'POST',
            url: liveserver.api + 'transfer',
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
        request.mute_flag = $rootScope.agent_status.ticket.mute_flag;

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

    $scope.getCallTicketID = function(agent_status) {
        if( agent_status && agent_status.ticket )
            return sprintf('%06d', agent_status.ticket.id);
        else
            '';
    }

    $scope.onSaveProfile = function() {
        var request = {};
        var profile = AuthService.GetCredentials();
        request = angular.copy($rootScope.agent_status.caller);

        request.property_id = profile.property_id;
        request.callerid = $rootScope.agent_status.ticket.callerid;

        request.user_id = profile.id;
        request.ticket_id = $rootScope.agent_status.ticket.id;
        request.type = $scope.ticket.type;
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
                if($rootScope.agent_status.status = 'Wrapup')
                    $rootScope.agent_status.status = 'Available';
                $scope.ticket = {};
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
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        var day = new Date(); day = moment(day).format("YYYY-MM-DD");
        request.day = day;
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
                $scope.abandoned = response.data.subcount.abandoned;
                $scope.followup = response.data.subcount.followup;
                $scope.callback = response.data.subcount.callback;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.answered = 0;
    $scope.abandoned = 0;
    $scope.followup = 0;
    $scope.callback = 0;
    $scope.queue = 0;

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

        var profile = AuthService.GetCredentials();
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


    $scope.viewDay = function(day) {

    }
    $scope.Ticketnumber = function(ticket){
        return sprintf('%05d', ticket);
        if( ticket == undefined )
            return 0;
    }

    $scope.$on('call_event', function(event, args) {
        console.log(args);
        var profile = AuthService.GetCredentials();
        if(args.user_id != profile.id )
            return;

        $scope.getDataList();
        $scope.getDataListSecond();

        $scope.select_status = $rootScope.agent_status.status;
        $scope.changeProperty();
    });

    $scope.$on('agent_status_change', function(event, args) {
        console.log(args);
        var profile = AuthService.GetCredentials();
        if(args.user_id != profile.id )
            return;
        $scope.select_status = $rootScope.agent_status.status;
        $scope.changeProperty();
    });

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }
});
