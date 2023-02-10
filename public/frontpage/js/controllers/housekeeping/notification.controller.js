app.controller('WorkflowNotificationController', function($scope, $rootScope, $http, $httpParamSerializer, toaster, AuthService, GuestService) {
    var MESSAGE_TITLE = 'Trigger Task';

    function initData() {
        $scope.id = 0;
        $scope.status_tags = [];
        $scope.type_tags = [];
        $scope.guesttype_tags = [];
        $scope.inspection = 0;
        $scope.sms = 0;
        $scope.email = 0;
        $scope.notifygroup_flag = 0;
        $scope.notifytype_tags = [];
        $scope.task = {id: 0, task: ''};
        $scope.action_button = 'Add';
    }

    initData();

    $scope.loadRoomTypeFilters = function(query) {
        return $scope.room_type.filter(function(type) {
            return type.type.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.loadHskpStatusFilters = function(query) {
        return $scope.room_status.filter(function(type) {
            return type.status.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.loadGuestTypeFilters = function(query) {
        return $scope.guest_type.filter(function(type) {
            return type.guest_type.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.loadNotifyGroupFilters = function(query) {
        return $scope.user_group.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.getTaskList = function(val) {
        if( val == undefined )
            val = "";

        var profile = AuthService.GetCredentials();

        return GuestService.getTaskList(val, profile.property_id, 0)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.add = function() {
        var request = {};

        request.id = $scope.id;
        var profile = AuthService.GetCredentials();
        request.attendant = profile.id;

        request.room_status = [];
        for(var i = 0; i < $scope.status_tags.length; i++)
            request.room_status.push($scope.status_tags[i].id);

        request.room_status = JSON.stringify(request.room_status);

        request.room_type = [];
        for(var i = 0; i < $scope.type_tags.length; i++)
            request.room_type.push($scope.type_tags[i].id);

        request.room_type = JSON.stringify(request.room_type);

        request.guest_type = [];
        for(var i = 0; i < $scope.guesttype_tags.length; i++)
            request.guest_type.push($scope.guesttype_tags[i].id);

        request.guest_type = JSON.stringify(request.guest_type);

        request.inspection = $scope.inspection ? 1 : 0;
        request.sms = $scope.sms ? 1 : 0;
        request.email = $scope.email ? 1 : 0;

        request.notifygroup_flag = $scope.notifygroup_flag ? 1 : 0;

        if( $scope.notifygroup_flag > 0 && $scope.notifytype_tags.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'You must select at least notify group');
            return;
        }
        request.notify_group = [];
        for(var i = 0; i < $scope.notifytype_tags.length; i++)
            request.notify_group.push($scope.notifytype_tags[i].id);

        request.notify_group = JSON.stringify(request.notify_group);

        request.task_id = $scope.task.id;

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/hskp/createtriggertask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Tasks have been created successfully');
                $scope.cancel();
                $scope.getDataList();

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.isLoading = false;

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
        var profile = AuthService.GetCredentials();
        request.attendant = profile.id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/hskp/triggertasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                for(var i = 0; i < $scope.datalist.length; i++)
                    $scope.datalist[i].active_flag = $scope.datalist[i].active == 1 ? true : false;

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

                $scope.room_type = response.data.room_type;
                $scope.room_status = response.data.room_status;
                $scope.guest_type = response.data.guest_type;
                $scope.user_group = response.data.user_group;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.cancel = function() {
        initData();
    }

    $scope.getRoomStatus = function(row) {
        return getValuefromID(row.room_status, $scope.room_status, 'status');
    }

    $scope.getRoomTypes = function(row) {
        return getValuefromID(row.room_type, $scope.room_type, 'type');
    }

    $scope.getGuestType = function(row) {
        return getValuefromID(row.guest_type, $scope.guest_type, 'guest_type');
    }

    $scope.getNotifyGroup = function(row) {
        return getValuefromID(row.notify_group, $scope.user_group, 'name');
    }

    $scope.edit = function(row) {
        $scope.id = row.id;
        $scope.status_tags = getArrayfromID(row.room_status, $scope.room_status);

        $scope.type_tags = getArrayfromID(row.room_type, $scope.room_type);
        $scope.guesttype_tags = getArrayfromID(row.guest_type, $scope.guest_type);
        $scope.inspection = row.inspection == 1 ? true : false;
        $scope.sms = row.sms == 1 ? true : false;
        $scope.email = row.email == 1 ? true : false;
        $scope.notifygroup_flag = row.notifygroup_flag == 1 ? true : false;
        $scope.notifytype_tags = getArrayfromID(row.notify_group, $scope.user_group);
        $scope.task = {};
        $scope.task.id = row.task_id;
        $scope.task.task = row.task_name;

        $scope.action_button = 'Update';
    }

    $scope.delete = function(row) {
        var request = {};
        request.id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/hskp/deletetriggertask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Tasks have been deleted successfully');
                $scope.cancel();
                $scope.getDataList();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.onTotalActiveChange = function() {
        console.log($scope.active_flag);
        updateActiveState(0, $scope.active_flag);
    }

    $scope.onActiveChange = function(row) {
        updateActiveState(row.id, row.active_flag);
    }

    function updateActiveState(id, active_flag) {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.id = id;
        request.attendant = profile.id;
        request.property_id = profile.property_id;
        request.active = active_flag  ? 1 : 0;

        $http({
            method: 'POST',
            url: '/frontend/hskp/activetriggertask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( active_flag == true )
                {
                    toaster.pop('success', MESSAGE_TITLE, 'Triggers have been activated');
                }
                else
                {
                    toaster.pop('success', MESSAGE_TITLE, 'Triggers have been inactivated');
                }
                for(var i = 0; i < $scope.datalist.length; i++)
                    $scope.datalist[i].active_flag = active_flag;
                
                //$scope.getDataList();

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
    function getValuefromID(ids, values, key)
    {
        var ids = JSON.parse(ids);
        var result = '';
        var index = 0;
        for(var i = 0; i < ids.length; i++)
        {
            for( var j = 0; j < values.length; j++)
            {
                if( ids[i] == values[j].id )
                {
                    if( index > 0 )
                        result += ', ';
                    result +=  values[j][key];
                    index++;
                    break;
                }
            }
        }

        return result;
    }

    function getArrayfromID(ids, values)
    {
        var ids = JSON.parse(ids);
        var result = [];
        for(var i = 0; i < ids.length; i++)
        {
            for( var j = 0; j < values.length; j++)
            {
                if( ids[i] == values[j].id )
                {
                    result.push(values[j]);
                    break;
                }
            }
        }

        return result;
    }

});
