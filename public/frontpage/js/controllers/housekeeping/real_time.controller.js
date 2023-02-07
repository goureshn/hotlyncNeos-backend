app.controller('RealTimeController', function ($scope, $rootScope, $http, $window, AuthService, $uibModal, $interval,toaster) {
    var MESSAGE_TITLE = 'Real Time';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;
    $scope.total={};
    $scope.filter = {
                        "bldg_tags":[],
                        "floor_tags":[],
                        "attendant_tags":[],
                        "supervisor_tags":[],
                        "status_tags":[],"occ_tags":[],"rush":false, "search_room": ""};
    
    $scope.hide_flag=0;
    var COL_COUNT = 10;

    $scope.room_data = {};

    $scope.room_data.room_status_list = [];
    $scope.room_data.room_status = [];
    $scope.room_data.room_status_list_group = {};

    $http.get('/list/roomcleaningstatelist')
            .then(function (response) {
                $scope.status = response.data;
                console.log($scope.status);
            });

    $scope.view_style_list = ["Grid","List"];
    $scope.order_list = ["Room","Status","Staff","Room Type"];
    $scope.group_list = [ "Floor","Status","Staff","Room Type"];
    $scope.filter.sort_by = "Room";    
    $scope.filter.view_style = "List";

    getDataList();
    getFloorList();
    getStaffList();
    getOccupancyList();    
    getBuildingList();

    $scope.refreshDatalist = function () {        
        getDataList();
    };

    $scope.refreshDataOnBuilding = function () {        
        $scope.filter.floor_tags = [];
        getFloorList();
        $scope.refreshDatalist();
    };

    $scope.getStatusNameById = function(id)
    {
        for (var i = 0 ; i <  $scope.status.length ; i++)
        {
            if($scope.status[i].status_id == id)
                return $scope.status[i].status_name;
        }
    }

    $scope.$on('$destroy', function () {
        if (angular.isDefined($scope.refresh)) {
            $interval.cancel($scope.refresh);
            $scope.refresh = undefined;
        }
    });
    $scope.loadFloorFilters = function (query) {
        
        return $scope.floor_list.filter(function (type) {
            return type.floor_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    function getFloorList() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.building_id = $scope.building_id;
        if($scope.filter.bldg_tags)
            request.building_tags = $scope.filter.bldg_tags;

        $http({
            method: 'POST',
            url: '/floor/list',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.floor_list = response.data;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.loadStaffFilters = function (query) {
        return $scope.staff_list.filter(function (type) {
            return type.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    function getStaffList() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept = 'Housekeeping';

        $http.get('/list/userlist?property_id=' + request.property_id + '&dept=' + request.dept)
            .then(function (response) {
                $scope.staff_list = response.data;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    attendant_list = [];
    supervisor_list = [];
   

    $scope.loadAttendantFilters = function (query) {
        return attendant_list.filter(function (type) {
            return type.roster_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.loadSupervisorFilters = function (query) {
        return supervisor_list.filter(function (type) {
            return type.roster_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };


    $scope.loadOccupancyFilters = function (query) {
        return $scope.occ_list.filter(function (type) {window.alert
            return type.status.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    function getOccupancyList() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.building_id = $scope.building_id;

        $http.get('/list/hskpstatus')
            .then(function (response) {
                $scope.occ_list = response.data;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.loadStatusFilters = function (query) {

        return $scope.status.filter(function (type) {
            return type.status_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };
    
    $scope.loadBuildingFilters = function (query) {

        return $scope.build_list.filter(function (type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    function getBuildingList() {
       
        $scope.building_id = 0;

        var profile = AuthService.GetCredentials();
        var request = {};

        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/build/list',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.build_list = response.data;
                /*var all_building = {};
                all_building.id = 0;
                all_building.name = 'All Buildings';
                $scope.build_list.unshift(all_building);*/

                $scope.building_id = 0;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    $scope.onClickCleaning = function(row)
    {
        if( row.state == -1)
        {
            $scope.filter.status_tags = [];
        }
        else
        {
            $scope.filter.status_tags = $scope.status.filter(item => item.status_id == row.state);
        }

        getDataList();
    }

    function getDataList() {
        $scope.filter_apply = $scope.filter.bldg_tags.length > 0 ||                                     
            $scope.filter.floor_tags.length > 0 ||
            $scope.filter.attendant_tags.length > 0 ||
            $scope.filter.supervisor_tags.length > 0 ||
            $scope.filter.status_tags.length > 0;
            
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;
        
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.filter = $scope.filter;

        console.log(request.filter);

        $scope.cleaning_room_count = [];

        $http({
            method: 'POST',
            url: '/frontend/hskp/gethskpstatusbyfloor',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                console.log(response.data);
                if (response.data.roomcount==0)
                    $scope.hide_flag = 1;
                else
                    $scope.hide_flag = 0;

                attendant_list = response.data.attendant_list;
                supervisor_list = response.data.supervisor_list;
                $scope.cleaning_room_count = response.data.cleaning_room_count;

                groupByLocationGroup(response.data.datalist, response.data.roomcount);

                
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    function groupByLocationGroup(datalist,count) {
        
        $scope.total.total=count;
        $scope.total.pending = 0, $scope.total.dnd = 0, $scope.total.inspected = 0, $scope.total.refuse = 0,
            $scope.total.delay = 0, $scope.total.cleaning = 0, $scope.total.finished = 0;
        var room_status = [];
        for (var i = 0; i < datalist.length; i++) {
            var floor = datalist[i];
            var loc_group = floor.loc_group;

            if (!room_status[i]) {
                room_status[i] = {};
                room_status[i].list = [];
            }
            
            room_status[i].list.push(floor);
        }

        $scope.room_data.room_status = [];

        var row = [];
        var temp;
        for (var loc_group_id in room_status) {

            var loc_group = room_status[loc_group_id];

            for (var i in loc_group.list) {
                var floor = loc_group.list[i];
                var floor_cell = {};
                if (floor.room_list.length>0)
                {
                    floor_cell.type = 1;    // floor
                    floor_cell.class = 'floor';
                    floor_cell.span = 1;
                    floor_cell.room_len = floor.room_list.length;
                // group_cell.span += floor_cell.span;
                    floor_cell.data = {};
                    floor_cell.data.id = floor.id;
                    floor_cell.data.name = floor.floor + ' -' + floor_cell.room_len + ' Rooms';
                    floor_cell.data.height = 40;
                    row.push(floor_cell);

                    var floor_cell2 = {};

                    floor_cell2.type = 0;    // floor
                    floor_cell2.class = 'status';
                    floor_cell2.span = ((floor.room_list.length - 1) / COL_COUNT) ;
                    floor_cell2.room_len = floor.room_list.length;
                    floor_cell.data.height = 50;
                // group_cell2.span += floor_cell.span;
                    floor_cell2.data = {};
                    floor_cell2.data.id = floor.id;
                    floor_cell2.data.name='';
                    for(var k in floor.state_list)
                    {   
                        var state = {};
                        var curr_state = floor.state_list[k];
                        
                            switch(curr_state.state_name)
                            {
                                case 0: state.class = '"pending"';
                                    state.state_name ="Pending";
                                    state.state_count = curr_state.state_count;
                                    break;

                                case 1: state.class = '"cleaning"';
                                    state.state_name = "Cleaning";
                                    state.state_count = curr_state.state_count;
                                    break;


                                case 2: state.class = '"finished"';
                                    state.state_name = "Finished";
                                    state.state_count = curr_state.state_count;
                                    break;

                                case 3: state.class = '"dnd"';
                                    state.state_name = "DND";
                                    state.state_count = curr_state.state_count;
                                    break;


                                case 4: state.class = '"refuse"';
                                    state.state_name = "Refused";
                                    state.state_count = curr_state.state_count;
                                    break;


                                case 5: state.class = '"delay"';
                                    state.state_name = "Delay";
                                    state.state_count = curr_state.state_count;
                                    break;

                                default: state.class = '"unassigned"';
                                    state.state_name = "Unassigned";
                                    state.state_count = curr_state.state_count;
                                    break;
                            }
                        floor_cell2.data.name += '<div class=' + state.class + '>' + state.state_name + '&nbsp&nbsp:' + state.state_count +'</div>';
                        

                    }
                    
                    for (var j in floor.room_list) {
                        temp = 0;
                        if (j % (COL_COUNT) == 0 && j > 0) {
                            $scope.room_data.room_status.push(row);
                            row = [];
                            if(j == COL_COUNT)
                                row.push(floor_cell2);                       
                        }

                        var room = floor.room_list[j];

                        room.floor_description = floor.description;

                        var cell = {};
                        cell.span = 1;
                        cell.type = 2;  // room
                        cell.class = 'room';
                        cell.height = 80;

                        if (room.assigne_id > 0) {
                            if (room.state == 2) 
                            {   // completed  
                                cell.class += ' completed';
                                $scope.total.finished += 1;
                            }
                            if (room.state == 3) 
                            {    // dnd
                                cell.class += ' dnd';
                                $scope.total.dnd += 1;
                            }
                            if (room.state == 1) 
                            {    // start
                                cell.class += ' progress';
                                $scope.total.cleaning += 1;
                            }
                            if (room.state == 0)  
                            {   // pending
                                cell.class += ' dirty';
                                $scope.total.pending += 1;
                            }
                            if (room.state == 4)
                            {     // declined
                                cell.class += ' declined';
                                $scope.total.refuse += 1;


                            }
                                if (room.state == 5)     // postponed
                            {   cell.class += ' postponed';
                                    $scope.total.delay += 1;
                            }
                        }
                        else
                        {
                            cell.class += ' unassigned';
                            room.assigne_to="Unassigned";
                        }
                        cell.data = room;
                        cell.data.name = room.room + '<br/>' + room.assigne_to + '<br/>' + room.type + '<br/>' +room.duration;
                        row.push(cell);
                        temp = j;
                    }
                    if (((temp % (COL_COUNT) != 0)||(temp==0)) && temp<COL_COUNT)
                    {
                        var num = (COL_COUNT) - ((parseInt(temp)+1) % (COL_COUNT));
                        num = (num < 10) ? (num + 10) : num;
                        for(var i = 0; i < num; i++)
                        {
                            var val=(parseInt(temp) + 1 + i);
                            if (val % (COL_COUNT) == 0) {
                                $scope.room_data.room_status.push(row);
                                row = [];
                            }
                            if (val == COL_COUNT)
                            {
                                var cell = {};
                                cell.span = 1;
                                cell.type = 0;  // room
                                cell.class = 'status';
                                cell.height = 50;
                                cell.data = {};
                                cell.data.name = '';
                                for (var k in floor.state_list) {
                                    var state = {};
                                    var curr_state = floor.state_list[k];

                                    switch (curr_state.state_name) {
                                        case 0: state.class = '"pending"';
                                            state.state_name = "Pending";
                                            state.state_count = curr_state.state_count;
                                            break;

                                        case 1: state.class = '"cleaning"';
                                            state.state_name = "Cleaning";
                                            state.state_count = curr_state.state_count;
                                            break;


                                        case 2: state.class = '"finished"';
                                            state.state_name = "Finished";
                                            state.state_count = curr_state.state_count;
                                            break;

                                        case 3: state.class = '"dnd"';
                                            state.state_name = "DND";
                                            state.state_count = curr_state.state_count;
                                            break;


                                        case 4: state.class = '"refuse"';
                                            state.state_name = "Refused";
                                            state.state_count = curr_state.state_count;
                                            break;


                                        case 5: state.class = '"delay"';
                                            state.state_name = "Delay";
                                            state.state_count = curr_state.state_count;
                                            break;

                                        default: state.class = '"unassigned"';
                                            state.state_name = "Unassigned";
                                            state.state_count = curr_state.state_count;
                                            break;
                                    }
                                    cell.data.name += '<div class=' + state.class + '>' + state.state_name + '&nbsp&nbsp:' + state.state_count + '</div>';
                                }
                            }
                            else
                            {
                                var cell = {};
                                cell.span = 1;
                                cell.type = 2;  // room
                                cell.class = 'noclass';
                                cell.height = 80;
                                cell.data='';
                            }
                            row.push(cell);
                        }
                    }

                    if (row.length > 0)
                    {
                        $scope.room_data.room_status.push(row);
                        row = [];
                        
                        var cell = {};
                        cell.span = 1;
                        cell.colspan=11;
                        cell.type = 2;  // room
                        cell.class = 'test';
                        cell.height = 20;
                        cell.data = '';
                        row.push(cell);

                        $scope.room_data.room_status.push(row);
                        row = [];
                    }
                    
                    row = [];
                    
           
                }
            }
            row = [];
        }


        // $scope.room_status_list
        $scope.room_data.room_status_list = [];
        for(var i = 0 ; i < $scope.room_data.room_status.length; i++)
        {
            for(var j = 0 ; j < $scope.room_data.room_status[i].length; j++)
            {
                if( $scope.room_data.room_status[i][j]["class"] !== "noclass" &&  $scope.room_data.room_status[i][j]["class"] !== "floor"  && $scope.room_data.room_status[i][j]["class"] !== "noclass" && $scope.room_data.room_status[i][j]["class"] !== "status" && $scope.room_data.room_status[i][j]["class"] !== "test")
                {
                    $scope.room_data.room_status_list.push($scope.room_data.room_status[i][j]["data"]);
                }
            }
        }

        console.log($scope.room_data.room_status_list);
        $interval(function() {
            for(var i = 0 ; i < $scope.room_data.room_status_list.length; i++) {
                var room_status = $scope.room_data.room_status_list[i]
                var state = room_status.state;
                switch(state)
                {
                    case 100: // Unassigned                                                                
                    case 0: // Pending                                                                
                    case 3: // DND   
                    case 4: // Refused   
                    case 9: // Rejected   
                        room_status.duration = '00:00:00';
                        break;    
                    case 1: // Cleanning                                        
                        var ms = moment().diff(moment(room_status.start_time));                        
                        room_status.duration = moment.utc(ms).format("HH:mm:ss");                                                                 
                        break;        
                    case 2: // Finished                                        
                    case 6: // Paused
                    case 7: // Inspected
                        var ms = moment(room_status.end_time).diff(moment(room_status.start_time));                        
                        room_status.duration = moment.utc(ms).format("HH:mm:ss");                                                                 
                        break;           
                    case 5: // Delay                                        
                        var ms = moment(room_status.start_time).diff(moment());                        
                        room_status.duration = moment.utc(ms).format("HH:mm:ss");                                                                 
                        break;                     
                    default:
                        break;
                }
            }
        }, 1000);

        $scope.changedGroup();
        $scope.changedSort();
    }

    $scope.changedSort =  function(){

        if($scope.filter.sort_by == "Room"){
            $scope.room_data.room_status_list.sort(function(a, b) {
                return ( a.room * 1 - b.room * 1  );
                //return (  a.room.localeCompare(b.room) );

            });
        }
        else if($scope.filter.sort_by == "Status" )
        {
            $scope.room_data.room_status_list.sort(function(a, b) {
                return (  a.state - b.state);

            });
        }
        else if($scope.filter.sort_by == "Staff" )
        {
            $scope.room_data.room_status_list.sort(function(a, b) {
                return (  a.assigne_to.localeCompare(b.assigne_to) );

            });
        }
        else if($scope.filter.sort_by == "Room Type" )
        {
            $scope.room_data.room_status_list.sort(function(a, b) {
                return (  a.type.localeCompare(b.type) );

            });
        }
    }

    $scope.changedGroup = function(){
        if($scope.filter.view_style == "List")
            $scope.filter.view_style = "Grid";
        $scope.room_data.room_status_list_group = {};
        for(var i = 0 ; i < $scope.room_data.room_status_list.length; i++) {

            if($scope.filter.group_by == "Floor")
            {

                if( $scope.room_data.room_status_list_group.hasOwnProperty($scope.room_data.room_status_list[i].floor_name))
                {
                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].floor_name].push($scope.room_data.room_status_list[i]);
                }else {
                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].floor_name] = [];

                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].floor_name].push($scope.room_data.room_status_list[i]);

                }

            }else if($scope.filter.group_by == "Status")
            {
                var state = $scope.room_data.room_status_list[i].state;
                var state_name = $scope.getStatusNameById(state)
                if( $scope.room_data.room_status_list_group.hasOwnProperty(state_name))
                {
                    $scope.room_data.room_status_list_group[state_name].push($scope.room_data.room_status_list[i]);
                }else {
                    $scope.room_data.room_status_list_group[state_name] = [];

                    $scope.room_data.room_status_list_group[state_name].push($scope.room_data.room_status_list[i]);

                }
            }
            else if($scope.filter.group_by == "Staff")
            {
                if( $scope.room_data.room_status_list_group.hasOwnProperty($scope.room_data.room_status_list[i].assigne_to))
                {
                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].assigne_to].push($scope.room_data.room_status_list[i]);
                }else {
                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].assigne_to] = [];

                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].assigne_to].push($scope.room_data.room_status_list[i]);

                }
            }
            else if($scope.filter.group_by == "Room Type")
            {
                if( $scope.room_data.room_status_list_group.hasOwnProperty($scope.room_data.room_status_list[i].type))
                {
                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].type].push($scope.room_data.room_status_list[i]);
                }else {
                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].type] = [];

                    $scope.room_data.room_status_list_group[$scope.room_data.room_status_list[i].type].push($scope.room_data.room_status_list[i]);

                }
            }
        }
        $scope.room_data.group_keys = Object.keys($scope.room_data.room_status_list_group);
    }

    $scope.changedViewStyle = function()
    {
        if($scope.filter.view_style == "List")
        {
            $scope.filter.group_by = undefined;
        }
    }
    
    $scope.onClickCell = function (cell) {
        if (cell.type == 0)  // location group
        {
        }
        else if (cell.type == 1)    // floor
        {
        }
        else if (cell.type == 2)    // room
        {
        }
    }


    $scope.$on('hskp_status_event', function(event, args){

        $scope.refreshDatalist();
        console.log("Auto Updating on housekeeping");
    });

    $scope.onClickRoom = function(row) {
        $scope.room_editable = AuthService.isValidModule('app.housekeeping.roomstatus_edit');
        if  ($scope.room_editable == false)
            return;
        var modalInstance = $uibModal.open({
            templateUrl: "tpl/housekeeping/modal/room_status.html",
            backdrop: 'static',
            scope: $scope,
            controller: 'RoomStatusCtrl',
            resolve: {
                room_status: function () {
                    return row;
                },                
            },
        });
        modalInstance.result.then(function (selectedItem) {
        }, function () {

        });
    }
});


app.controller('RoomStatusCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, AuthService, room_status) {    
    $scope.room_status = room_status;

    console.log($scope.room_status);

    $scope.room_status.new_cleaning_state = angular.copy(room_status.status_name);
    $scope.room_status.new_room_status = angular.copy(room_status.room_status);
    $scope.room_status.new_service_state = angular.copy(room_status.service_state);
    $scope.room_status.new_schedule = angular.copy(room_status.schedule);
    $scope.room_status.new_rush_flag = room_status.rush_flag == 1;
    $scope.room_status.new_adult = angular.copy(room_status.adult);
    $scope.room_status.new_chld = angular.copy(room_status.chld);
    
    $scope.staff_list = [];
    $scope.schedule_list = [];
    $scope.service_state_list = ['Available', 'OOO', 'OOS'];
    $scope.cleaning_state_list = ['Cleaning', 'Finished', 'Inspected'];

    $scope.room_status_list = [        
        'Inspected',
        'Dirty',
        'Clean',        
    ];

    $http.get('/list/schedulelist')
    .then(function (response) {
        $scope.schedule_list = response.data;
        var alloption = {id: 0, name : 'Not Applicable'};
				$scope.schedule_list.unshift(alloption);	
        console.log($scope.schedule_list);
    });

    var profile = AuthService.GetCredentials();

    function getAttendnatList() {
        var request = {};
        request.property_id = profile.property_id;
        request.device_flag = $scope.room_status.hskp_user_id > 0 ? 0 : 1;
        $http({
            method: 'POST',
            url: '/frontend/hskp/gethskpattendantlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            var unassigned = {
                id: 0,
                wholename: 'Unassigned'
            };

            $scope.staff_list = response.data;
            $scope.staff_list.unshift(unassigned);		
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Reassign is failed.');
        })
            .finally(function () {
            });
    }

    getAttendnatList();
   
    var assigne_edit_flag = false;

    $scope.onAssignerSelect = function (row, $item, $model, $label) {
        if( $scope.room_status.assigne_id != $item.id )
        {
            assigne_edit_flag = true;
            $scope.room_status.assigne_id = $item.id;
        }
    };

    function updateCleaningState()
    {
        if( $scope.room_status.new_cleaning_state == room_status.status_name )
            return;

        // Reassign 
        var request = {};
        request.room_id = $scope.room_status.id;
        request.cleaning_state = $scope.room_status.new_cleaning_state;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updatecleaningstate',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                room_status.working_status = response.data.content.working_status;
                room_status.state = response.data.content.working_status;            
                room_status.new_cleaning_state = angular.copy(response.data.content.cleaning_state);
                room_status.status_name = angular.copy(response.data.content.cleaning_state);
                
                toaster.pop('info', "Successful", response.data.message);                                
            }
            else    
                toaster.pop('info', "Failed", response.data.message);
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room State Change is failed.');
        })
            .finally(function () {
            });
    }

    function reassignRoster()
    {
        if( assigne_edit_flag == false )
            return;

        // Reassign 
        var request = {};
        request.room_id = room_status.id;
        request.assigner_id = $scope.room_status.assigne_id;
        request.device_flag = $scope.room_status.hskp_user_id > 0 ? 0 : 1;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/reassignroster',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            room_status.working_status = response.data.content.working_status;
            room_status.state = response.data.content.working_status;            
            room_status.new_cleaning_state = angular.copy(response.data.content.cleaning_state);
            room_status.status_name = angular.copy(response.data.content.cleaning_state);
            room_status.assigne_to = angular.copy($scope.room_status.assigne_to);
            if( response.data.code == 200 )
                toaster.pop('info', "Successful", 'Roster is reassigned successfully.');
            else    
                toaster.pop('info', "Failed", 'Reassign is failed.');
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Reassign is failed.');
        })
            .finally(function () {
            });
    }

    function updateRoomServiceState()
    {
        if( $scope.room_status.new_service_state == room_status.service_state )
            return;

        // Reassign 
        var request = {};
        request.room_id = $scope.room_status.id;
        request.comment = $scope.room_status.comment;
        request.service_state = $scope.room_status.new_service_state;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updateservicestate',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                room_status.service_state = angular.copy($scope.room_status.new_service_state);
                toaster.pop('info', "Successful", response.data.message);                                
            }
            else    
                toaster.pop('info', "Failed", 'Room State Change is failed.');
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room State Change is failed.');
        })
            .finally(function () {
            });
    }

    function updateRoomStatus()
    {
        if( $scope.room_status.new_room_status == room_status.room_status )
            return;
            
        // Reassign 
        var request = {};
        request.room_id = $scope.room_status.id;        
        request.room_status = $scope.room_status.new_room_status;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updateroomstatusmanually',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                room_status.room_status = angular.copy(request.room_status);
                room_status.working_status = response.data.hskp_room_status.working_status;
                room_status.state = room_status.working_status;
                room_status.status_name = angular.copy(response.data.hskp_room_status.cleaning_state);
                room_status.new_cleaning_state = angular.copy(response.data.hskp_room_status.cleaning_state);
                toaster.pop('info', "Successful", 'Room Status is changed successfully.');                
            }
            else    
                toaster.pop('info', "Failed", 'Room State Change is failed.');
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room State Change is failed.');
        })
        .finally(function () {
        });
    }

    function updateRushFlag() {
        if( $scope.room_status.new_rush_flag == true && room_status.rush_flag == 1 ||
            $scope.room_status.new_rush_flag == false && room_status.rush_flag == 0 )
            return;

        // Reassign 
        var request = {};
        request.room_id = $scope.room_status.id;
        request.rush_flag = $scope.room_status.new_rush_flag ? 1 : 0;
        request.method = 'Agent';
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updaterushclean',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                room_status.rush_flag = request.rush_flag;
                toaster.pop('info', "Successful", response.data.message);                                
            }
            else    
                toaster.pop('info', "Failed", 'Room State Change is failed.');
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room State Change is failed.');
        })
            .finally(function () {
            });
    }

    function updateSchedule()
    {
        if( $scope.room_status.new_schedule == room_status.schedule )
            return;

        // Reassign 
        var request = {};
        request.room_id = $scope.room_status.id;
        request.comment = $scope.room_status.comment;
        request.schedule = $scope.room_status.new_schedule;
        console.log(request.schedule);
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updateroomschedule',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                room_status.schedule = angular.copy($scope.room_status.new_schedule);
                room_status.state = response.data.content.working_status;
                toaster.pop('info', "Successful", response.data.message);                                
            }
            else    
                toaster.pop('info', "Failed", 'Room Schedule Change failed.');
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room Schedule Change is failed.');
        })
            .finally(function () {
            });
    }

    function updateDiscrepancy()
    {
        if( $scope.room_status.new_adult == room_status.adult  && $scope.room_status.new_chld == room_status.chld)
            return;

        var profile = AuthService.GetCredentials();
        var request = {};
        request.room_id = $scope.room_status.id;
        request.adult = $scope.room_status.new_adult;
        request.chld = $scope.room_status.new_chld;
        request.user_id = profile.id;
        console.log(request);

       
        $http({
            method: 'POST',
            url: '/frontend/hskp/updateroomdiscrepancy',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                room_status.adult = angular.copy($scope.room_status.new_adult);
                room_status.chld = angular.copy($scope.room_status.new_chld);
                room_status.state = response.data.content.working_status;
                toaster.pop('info', "Successful", response.data.message);                                
            }
            else    
                toaster.pop('info', "Failed", 'Room Discrepancy Change failed.');
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room Discrepancy Change is failed.');
        })
            .finally(function () {
            });
    }

    function getRoomHistory()
    {
        $scope.room_history = [];

        var request = {};
        request.room_id = $scope.room_status.id;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/getroomhistory',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                $scope.room_history = response.data.list;
            }            
                
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Room State Change is failed.');
        })
            .finally(function () {
            });
    }

    getRoomHistory();


    $scope.onClickUpdate = function() {        
        updateCleaningState();
        reassignRoster();        
        updateRoomServiceState();
        updateRoomStatus();    
        updateRushFlag();    
        updateSchedule();
        updateDiscrepancy();

        
    }

    $scope.addPreference = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/room_preference.html',
            controller: 'EditPreferenceCtrl',
            windowClass: 'app-modal-window',
            resolve: {
               item: function () {
                    return $scope.room_status;
                }
            }
        });
        modalInstance.result.then(function (item) {
            if( item )
                $scope.room_status.remark = item.remark + '';
            else    
                $scope.room_status.remark = null;

        }, function () {

        });
    };

    
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    
});

app.controller('EditPreferenceCtrl', function ($scope, $uibModalInstance, toaster,$http, AuthService, item) {
    $scope.item = angular.copy(item);
    var MESSAGE_TITLE = 'Add Preference';
    $scope.item.regex = /^[_A-z0-9]*((-|\s)*[_A-z0-9.])*(?:\s*)$/;
  
    $scope.savePreference = function () {

        if ($scope.item.remark == '') {
            toaster.pop('error', MESSAGE_TITLE, 'Please add preference.');
            return;
        }

        if ($scope.item.remark == undefined) {
            toaster.pop('error', MESSAGE_TITLE, 'Please do not use special characters.');
            return;
        }
        if ($scope.item.remark.length > 120) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter less than 120 characters.');
            return;
        }
        
        var request = $scope.item;
        request.repeat = $scope.item.repeat ? 1 : 0;
        console.log($scope.item);
     
        $http({
            method: 'POST',
            url: '/frontend/guestservice/addpreference',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
        .then(function (response) {
            if( response.data.code == 200 )
            {
                toaster.pop('Success', MESSAGE_TITLE, 'Preference has been added successfully');        
                $uibModalInstance.close($scope.item);

                return;
            }
            
            toaster.pop('info', "Remark", response.data.message);

        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
            toaster.pop('error', MESSAGE_TITLE, 'Failed to add Preference');
        })
        .finally(function () {
          
        });
  
    };

    $scope.deletePreference = function () {
        var request = $scope.item;
        
        $http({
            method: 'POST',
            url: '/frontend/guestservice/deletepreference',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
        .then(function (response) {
            if( response.data.code == 200 )
            {
                toaster.pop('Success', MESSAGE_TITLE, 'Preference has been deleted successfully');        
                $uibModalInstance.close();
                return;
            }            

        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
            toaster.pop('error', MESSAGE_TITLE, 'Failed to add Preference');
        })
        .finally(function () {
          
        });
  
    };

    $scope.cancelPreference = function () {        
        $uibModalInstance.dismiss();
    };

});
