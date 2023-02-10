app.controller('WakeupEditController', function ($scope, $rootScope, $http, $interval, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Wakeup Edit';

    $scope.init = function(wakeup) {
        $scope.selected_room = {};

        $scope.selected_room.id = wakeup.room_id;
        $scope.selected_room.room = wakeup.room;

        $scope.datetime = {};

        $scope.wakeup = wakeup;

        $scope.wakeup.date = wakeup.time;
        $scope.wakeup.repeat = wakeup.repeat_flag == 1;
        $scope.wakeup.until_checkout_flag = wakeup.until_checkout_flag == 1;
        $scope.wakeup.repeat_end_date = moment(wakeup.repeat_end_date).toDate();
        $scope.wakeup.is_date_open = false;

        if( wakeup.status == 'Pending' )
            $scope.wakeup.editable = true;
        else
            $scope.wakeup.editable = false;

        if( wakeup.status == 'Pending' || wakeup.status == 'Busy' || wakeup.status == 'Snooze' || wakeup.status == 'No Answer' || wakeup.status == 'Waiting' ||
        wakeup.status == 'In-Progress' && wakeup.attempts > 0 )
            $scope.wakeup.cancelable = true;
        else
            $scope.wakeup.cancelable = false;

        var room_info = {};
        room_info.id = wakeup.room_id
        GuestService.getGuestName(room_info)
            .then(function(response){
                $scope.model = response.data;
            });

        getNotificationHistory();
    }

    $scope.getRoomList = function(val) {
        if( val == undefined )
            val = "";

        return GuestService.getRoomList(val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.selected_room = $item;

        GuestService.getGuestName($item)
            .then(function(response){
                $scope.model = response.data;
            });

    };

    $scope.$watch('wakeup.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.wakeup.time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if( $view == 'day' )
        {
            var activeDate = moment().subtract('days', 1);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        else if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 0);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.wakeup.is_date_open = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        dateDisabled: disabled,
        class: 'datepicker'
    };

    function disabled(data) {        
        var date = data.date;
        var sel_date = moment($scope.wakeup.time).format('YYYY-MM-DD');
        var disabled = true;
        if( moment(data.date).format('YYYY-MM-DD') > sel_date )
            disabled = false;
        else
            disabled = true;

        mode = data.mode;
        return mode === 'day' && disabled;
    }

    $scope.select = function(date) {
        console.log(date);

        $scope.wakeup.is_date_open = false;
    }

    $scope.updateWakeupRequest = function() {
        var request = {};

        var profile = AuthService.GetCredentials();

        request.id = $scope.wakeup.id;
        request.room_id = $scope.wakeup.room_id;
        request.guest_id = $scope.model.guest_id;
        request.time = $scope.wakeup.time;
        request.repeat_flag = $scope.wakeup.repeat ? 1 : 0;
        request.set_by_id = profile.id;
        request.set_by = profile.first_name + ' ' + profile.last_name;
        request.until_checkout_flag = $scope.wakeup.until_checkout_flag ? 1 : 0;
        request.repeat_end_date = moment($scope.wakeup.repeat_end_date).format('YYYY-MM-DD');

        if( !request.room_id )
        {
            toaster.pop('info', MESSAGE_TITLE, 'You did not select room');
            return;
        }

        var request_date = moment(request.time).format('YYYY-MM-DD');
        if( request_date < $scope.model.arrival || request_date > $scope.model.departure )
        {
            toaster.pop('info', MESSAGE_TITLE, 'You can select date between ' + $scope.model.arrival + ' and ' + $scope.model.departure);
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/wakeup/update',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

            if( response.data.code != 200 )
            {
                toaster.pop('error', MESSAGE_TITLE, response.data.message);
                return;
            }

            $scope.$emit('onChangedWakeup', response.data);

            toaster.pop('success', MESSAGE_TITLE, 'Wakeup call have been updated successfully');
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Wakeup call have been failed to update');
        })
        .finally(function() {

        });

    }

    $scope.cancelWakeupRequest = function() {
        var request = {};

        var profile = AuthService.GetCredentials();

        request.id = $scope.wakeup.id;
        request.set_by_id = profile.id;
        request.set_by = profile.first_name + ' ' + profile.last_name;

        $http({
            method: 'POST',
            url: '/frontend/wakeup/cancel',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

            //$scope.$emit('onChangedWakeup', response.data);
            $scope.init(response.data);
            toaster.pop('success', MESSAGE_TITLE, 'Wakeup call have been canceled');
        }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Wakeup call have been failed to update');
            })
            .finally(function() {

            });

    }

    var paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'asc',
        field: 'id',
    };

    var columns = [
        {
            field : 'al_status_by',
            displayName : "Status",
            enableCellEdit: false,
        },
        {
            field : 'action_by',
            displayName : "User",
            enableCellEdit: false,
        },
        {
            field : 'timestamp',
            displayName : "Created",
            enableCellEdit: false,
        },
        {
            field : 'record_path',
            displayName : "Recording",
            enableCellEdit: false,
            cellTemplate: '<div class="ui-grid-cell-contents"><audio ng-show="row.entity.record_path" style="width:100%;margin-top:-5px" src="{{ row.entity.record_path }}" controls></audio></div>'
        },
    ];

    $scope.gridOptions =
    {
        enableGridMenu: false,
        enableRowHeaderSelection: false,
        enableColumnResizing: true,
        paginationPageSizes: [20, 40, 60, 80],
        paginationPageSize: 20,
        useExternalPagination: true,
        useExternalSorting: true,
        columnDefs: columns,
    };

    $scope.gridOptions.onRegisterApi = function( gridApi ) {
        $scope.gridApi = gridApi;
        //gridApi.selection.on.rowSelectionChanged($scope,function(row){
        //    console.log(row.entity);
        //});
        gridApi.core.on.sortChanged($scope, function(grid, sortColumns) {
            if (sortColumns.length == 0) {
                paginationOptions.sort = 'asc';
                paginationOptions.field = 'id';
            } else {
                paginationOptions.sort = sortColumns[0].sort.direction;
                paginationOptions.field = sortColumns[0].name;
            }
            getNotificationHistory();
        });
        gridApi.pagination.on.paginationChanged($scope, function (newPage, pageSize) {
            paginationOptions.pageNumber = newPage;
            paginationOptions.pageSize = pageSize;
            getNotificationHistory();
        });
    };

    var getNotificationHistory = function() {
        var request = {};

        request.id = $scope.wakeup.id;
        request.page = paginationOptions.pageNumber;
        request.pagesize = paginationOptions.pageSize;
        request.field = paginationOptions.field;
        request.sort = paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/wakeup/logs',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
                $scope.gridOptions.totalItems = response.data.totalcount;
                $scope.gridOptions.data = response.data.datalist;
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    };

    $scope.removeCurrentRequest = function() {
        $scope.onSelectTicket($scope.wakeup);
    }

    $scope.$on('onChangedWakeup', function(event, args){
        if( $scope.wakeup.id != args.id )
            return;

        $scope.init(args);
    });

});

