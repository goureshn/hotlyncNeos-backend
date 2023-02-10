app.controller('MyGuestserviceController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, AuthService, GuestService) {
    var MESSAGE_TITLE = 'My Task';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';
    $scope.gs = GuestService;

    $scope.filter_option = {};

    $scope.guestservices = [
        {name: 'My Tasks', badge: '2'},
        {name: 'Escalations', badge: '2'},
    ];

    $scope.currentFilter = $scope.guestservices[0];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
      $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
      $scope.pageChanged();
    });

    $scope.openFilterPanel = function(position, backdrop) {
        $rootScope.asideState = {
            open: true,
            position: position
        };

        function postClose(filter) {
            $rootScope.asideState.open = false;
            if( filter.name == undefined )
                return;

            $scope.currentFilter = filter;
            search_option = '';
            $scope.$emit('erase_search');

            $scope.refreshTickets();
        }

        $aside.open({
            templateUrl: 'tpl/toolbar/mytaskfilter.aside.html',
            placement: position,
            scope: $scope,
            size: 'sm',
            backdrop: backdrop,
            controller: function($scope, $uibModalInstance) {
                $scope.ok = function(e) {
                    $uibModalInstance.close();
                    e.stopPropagation();
                };
                $scope.cancel = function(e) {
                    $uibModalInstance.dismiss();
                    e.stopPropagation();
                };
                $scope.onSelectFilter = function(filter) {
                    $uibModalInstance.close(filter);
                }
            },
        }).result.then(postClose, postClose);
    }


    $scope.onSelectItem = function(item) {
        $scope.currentFilter = item;
        filter = 'Total';
        $scope.initPageNum();
        $scope.refreshTickets();
    }
    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = $window.innerHeight - 85;

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.ticketlist = [];
    $scope.selectedTicket = [];
    $scope.taskName = 'Unknown';

    var filter = '-1';
    $scope.onFilter = function getFilter(param) {
        filter = param;
        $scope.pageChanged();
    }


    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
    }

    $scope.pageChanged = function() {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);

        $scope.ticketlist = [];

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        var filtername = '';
        if( $scope.currentFilter != undefined )
            filtername = $scope.currentFilter.name;

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;
        request.dept_id = profile.dept_id;
        request.job_role_id = profile.job_role_id;
        request.filter = filter;
        request.mytask_mode = $scope.currentFilter.name;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        
        var url = '/frontend/guestservice/mytasklist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.content;

            if( $scope.ticketlist.length > 0) {
                $scope.selectedTicket = [];
                $scope.selectedTicket[0] = $scope.ticketlist[0];
               
                $scope.selectedNum = 0;
            }
            $scope.checkSelection($scope.ticketlist);
            $scope.paginationOptions.totalItems = response.data.totalcount;

            if( $scope.paginationOptions.totalItems < 1 )
                $scope.paginationOptions.countOfPages = 0;
            else
                $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

        getFilterList();    

    };

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.pageChanged();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.pageChanged();
    }

    $scope.refreshTickets = function(){
        $scope.pageChanged();        
    }

    $scope.onChangeFlagFilter = function() {
        $scope.pageChanged();           
    }

    function getFilterList() {
        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;    
        request.dispatcher = profile.id;
        request.job_role_id = profile.job_role_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/myfilterlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.guestservices = response.data;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });    
    }

    $scope.refreshTickets();

    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;

        for(var i = 0; i < ticketlist.length; i++)
        {
            var index = -1;
            for(var j = 0; j < $scope.selectedTicket.length; j++ )
            {
                if( ticketlist[i].id == $scope.selectedTicket[j].id)
                {
                    index = j;
                    break;
                }
            }
            ticketlist[i].active = index >= 0 ? true : false;
        }
    }

    $scope.onSelectTicket = function(ticket, event){
        // if( event.ctrlKey == false )
        {
            $scope.selectedTicket = [];
            $scope.selectedTicket[0] = ticket;
            $scope.selectedNum = 0;
        }
        // else
        // {
        //     var index = $scope.getSelected(ticket);

        //     if( index < 0 ) // new selected
        //         $scope.selectedTicket.push(ticket);
        //     else
        //         $scope.selectedTicket.splice(index, 1);
        // }

        $scope.checkSelection($scope.ticketlist);
    }

    $scope.getSelected = function(ticket) {
        var index = -1;
        for( var i = 0; i < $scope.selectedTicket.length; i++)
        {
            if( ticket.id == $scope.selectedTicket[i].id )
            {
                index = i;
                break;
            }
        }
        return index;
    }

    var getKeyboardEventResult = function (keyEvent, keyEventDesc)
    {
        console.log(keyEventDesc + " (keyCode: " + (window.event ? keyEvent.keyCode : keyEvent.which) + ")");
    };

    // Event handlers
    $scope.onKeyDown = function ($event) {
        getKeyboardEventResult($event, "Key down");
    };

    $scope.onKeyUp = function ($event) {
        getKeyboardEventResult($event, "Key up");
    };

    $scope.onKeyPress = function ($event) {
        getKeyboardEventResult($event, "Key press");
    };

    $scope.onRightClick = function(ticket) {
        var index = $scope.getSelected(ticket);
        if( index < 0 )
        {
            $scope.selectedTicket = [];
            $scope.selectedTicket[0] = ticket;
            $scope.selectedNum = 0;

            $scope.checkSelection($scope.ticketlist);
        }
    }

    // Context menu
    $scope.menuOptions = [
        ['Mark as Read', function ($itemScope) {

        }],
        null,
        ['Mark as Complete', function ($itemScope) {
            console.log($itemScope.row);
        }, function ($itemScope) {
            return true;
        }],
        null,
        ['More...', [
            ['Alert Cost', function ($itemScope) {
                //alert($itemScope.item.cost);
            }],
            ['Alert Player Gold', function ($itemScope) {
                //alert($scope.player.gold);
            }]
        ]]
    ];

    hotkeys.add({
        combo: 'a', // Approve
        description: 'Approve ticket',
        callback: function() {
            $scope.changeCompensationList(1, 1);
        }
    });
    hotkeys.add({
        combo: 'c',
        description: 'Close ticket',
        callback: function() {

        }
    });
    hotkeys.add({
        combo: 'u',
        description: 'Return ticket',
        callback: function() {
            $scope.changeCompensationList(3, 0);
        }
    });
    hotkeys.add({
        combo: 'r',
        description: 'Reject ticket',
        callback: function() {
            $scope.changeCompensationList(2, 0);
        }
    });

    hotkeys.add({
        combo: 'v',
        description: 'View ticket',
        callback: function() {

        }
    });
});




