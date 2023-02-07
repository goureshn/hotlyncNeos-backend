app.controller('AlarmLogsController', function($scope, $http, $window, $timeout, toaster, AuthService) {
    var MESSAGE_TITLE = 'Shift Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;

    $scope.tags = [
        { text: 'Tag1' },
        { text: 'Tag2' },
        { text: 'Tag3' }
    ];

    // pip
    $scope.isLoading = false;
    $scope.ticketlist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };
    
    $scope.cancelAlarm = function() {	    
        $scope.data = {};
        $scope.alarm_group = {};
        $scope.alarm_group.id=" ";
        $scope.users = [];
        $scope.alarm_group_name='';
       
        $scope.comment = '';
        val = '';
    }

    $scope.getAlarmList = function getAlarmList(tableState) {
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

        $http({
                method: 'POST',
                url: '/frontend/guestservice/alarmlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.alarmlist = response.data.alarmlist;
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

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.getDate = function(row) {
        return moment(row.time).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.time).format('hh:mm:ss a');
    }

    $scope.getAlarmGroupList = function(val) {
        if( val == undefined )
            val = '';
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/frontend/guestservice/alarmgroup?val=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGroupSelect = function ($item, $model, $label) {
        $scope.alarm_group = $item;

        $http.get("/backoffice/guestservice/wizard/alarmgroup/userlist?alarm_id=" + $scope.alarm_group.id)
            .then(function(response){
                console.log(response.data);

                $scope.users = [];
                for(var i = 0; i < response.data[1].length; i++)
                    $scope.users.push({text: response.data[1][i].name});
            });
    };

    $scope.onSendAlarm = function() {
        var data = {};
        if( $scope.alarm_group == undefined || $scope.alarm_group.id == undefined )
            return;

        data.notification_group = $scope.alarm_group.id;

        var profile = AuthService.GetCredentials();
        data.user_id = profile.id;
        data.message = $scope.comment;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/sendalarm',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code == 200 )
                    toaster.pop('success', MESSAGE_TITLE, 'Alarm message has been sent successfully');
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);

                $scope.paginationOptions.pageNumber = 0;
                $scope.getAlarmList();
                $scope.cancelAlarm();
            }).catch(function(response) {

            })
            .finally(function() {

            });
    }


});

