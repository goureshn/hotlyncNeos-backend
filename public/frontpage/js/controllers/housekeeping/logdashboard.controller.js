app.controller('LogDashboardController', function($scope, $rootScope, $http, $window, AuthService, $interval, toaster) {
    var COL_COUNT = 10;

    $scope.room_status = [];

    getDataList();

    $scope.refresh = $interval(function() {        
        getDataList();
    }, 20 * 1000);

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.refresh)) {
            $interval.cancel($scope.refresh);
            $scope.refresh = undefined;
        }
    });

 
    function getDataList() {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/gethskpstatusbyfloor',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                groupByLocationGroup(response.data.datalist);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    function groupByLocationGroup(datalist) {
        var room_status = [];
        for(var i = 0; i < datalist.length; i++)
        {
            var floor = datalist[i];
            var loc_group = floor.loc_group;
            if(loc_group) {
                if( !room_status[loc_group.id] )
                {
                    room_status[loc_group.id] = {};
                    room_status[loc_group.id].name = loc_group.name;
                    room_status[loc_group.id].list = [];
                }
                room_status[loc_group.id].list.push(floor);                
            }            
        }

        $scope.room_status = [];

        var row = [];
        for (var loc_group_id in room_status) {
            
            var loc_group = room_status[loc_group_id];

            var group_cell = {};

            group_cell.type = 0;    // group
            group_cell.class = 'group';
            group_cell.span = 0;
            group_cell.data = {};
            group_cell.data.id = loc_group.id;
            group_cell.data.name = loc_group.name;
            row.push(group_cell);

            for(var i in loc_group.list)
            {
                var floor = loc_group.list[i];
                var floor_cell = {};

                floor_cell.type = 1;    // floor
                floor_cell.class = 'floor';
                floor_cell.span = (floor.room_list.length - 1) / COL_COUNT + 1;
                group_cell.span += floor_cell.span;
                floor_cell.data = {};
                floor_cell.data.id = floor.id;
                floor_cell.data.name = floor.floor;
                row.push(floor_cell);

                for(var j in floor.room_list)
                {
                    if(j % COL_COUNT == 0 && j > 0)
                    {
                        $scope.room_status.push(row);
                        row = [];
                    }

                    var room = floor.room_list[j];

                    var cell = {};
                    cell.span = 1;  
                    cell.type = 2;  // room
                    cell.class = 'room';
                    if(room.assigne_id > 0 )
                    {
                        if(room.state == 2)    // completed  
                            cell.class += ' completed';
                        if(room.state == 3)     // dnd
                            cell.class += ' dnd';
                        if(room.state == 1)     // start
                            cell.class += ' progress';
                        if(room.state == 0)     // pending
                            cell.class += ' dirty';
                        if(room.state == 4)     // declined
                            cell.class += ' declined';
                        if(room.state == 5)     // postponed
                            cell.class += ' postponed';
                    }
                    cell.data = room;

                    row.push(cell);
                    console.log(room);                    
                }
                if( row.length > 0 )
                    $scope.room_status.push(row);
                row = [];
            }  
            row = [];          
        }
    }

    $scope.onClickCell = function(cell) {
        if(cell.type == 0)  // location group
        {

        }
        else if( cell.type == 1)    // floor
        {

        }
        else if( cell.type == 2)    // room
        {
            
        }
    }

});
