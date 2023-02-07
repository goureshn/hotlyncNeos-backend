app.controller('RealTimeController', function ($scope, $rootScope, $http, $window, AuthService, $timeout, $interval,toaster) {
    var MESSAGE_TITLE = 'Real Time';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;
    $scope.total={};
    $scope.filter = {"floor_tags":[],"status_tags":[],"occ_tags":[],"status_tags":[],"bldg_tags":[],"rush":false};
    
    $scope.hide_flag=0;
    var COL_COUNT = 10;

    $scope.room_status = [];
    $scope.status = [{id:0,status_name:'Pending'},
    {id:1,status_name:'Cleaning'},
    {id:2, status_name:'Finished'},
    {id:3,status_name:'DND'},
    {id:4,status_name:'Refused'},
    {id:5,status_name:'Delay'},
    {id:6,status_name:'Pause'},
    {id:7,status_name:'Inspected'},
    {id:100,status_name:'Unassigned'}];

    getDataList();
    getFloorList();
    getStaffList();
    getOccupancyList();
    //getStatusList();
    getBuildingList();

    $scope.refresh = $interval(function () {
        getDataList();
    }, 20 * 1000);
    $scope.refreshDatalist = function () {
        //window.alert(JSON.stringify($scope.filter));
        getDataList();
    };

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
                console.log(response);
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
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
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
                console.log(response);
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
    // function getStatusList() {
        

    //     var profile = AuthService.GetCredentials();

    //     var request = {};
    //     request.property_id = profile.property_id;
    //     request.building_id = $scope.building_id;


    //     $http({
    //         method: 'POST',
    //         url: '/floor/list',
    //         data: request,
    //         headers: { 'Content-Type': 'application/json; charset=utf-8' }
    //     })
    //         .then(function (response) {
    //             $scope.floor_list = response.data;
    //             console.log(response);
    //         }).catch(function (response) {
    //             console.error('Gists error', response.status, response.data);
    //         })
    //         .finally(function () {
    //             $scope.isLoading = false;
    //         });
    // }
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
                var all_building = {};
                all_building.id = 0;
                all_building.name = 'All Buildings';
                $scope.build_list.unshift(all_building);

                $scope.building_id = 0;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }


    function getDataList() {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;
        
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.filter=$scope.filter;

        $http({
            method: 'POST',
            url: '/frontend/hskp/gethskpstatusbyfloor',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if (response.data.roomcount==0)
                    $scope.hide_flag = 1;
                else
                    $scope.hide_flag = 0;
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
           // if (loc_group) {
                if (!room_status[i]) {
                    room_status[i] = {};
                    //room_status[i].name = loc_group.name;
                    room_status[i].list = [];
                }
                
                room_status[i].list.push(floor);
                
           // }
        }

        $scope.room_status = [];

        var row = [];
        var temp;
        for (var loc_group_id in room_status) {

            var loc_group = room_status[loc_group_id];

            var group_cell = {};

            // group_cell.type = 0;    // group
            // group_cell.class = 'group';
            // group_cell.span = 0;
            // group_cell.data = {};
            // group_cell.data.id = loc_group.id;
            // group_cell.data.name = loc_group.name;
            // row.push(group_cell);

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
                //floor_cell2.data.name = '<div class="pending"> Pending</div><div class="cleaning">Cleaning</div><div class="dnd">DND</div><div class="refuse">Refuse</div><div class="delay">Delay</div><div class="unassigned">Unassigned</div><div class="finished">Finished</div>';
                // floor_cell2.data.name2 = 'Cleaning';
                // floor_cell2.data.name3 = 'DND';
                // floor_cell2.data.name4 = 'Refuse';
                // floor_cell2.data.name5 = 'Delay';
                // floor_cell2.data.name6 = 'Unassigned';
                // floor_cell2.data.name7 = 'Finished';
               // row.push(floor_cell);

                for (var j in floor.room_list) {
                    temp=0;
                    if (j % (COL_COUNT) == 0 && j > 0) {
                        

                        $scope.room_status.push(row);
                       // window.alert(j + ':  ' + JSON.stringify(row));
                        row = [];
                        if(j==COL_COUNT)
                            row.push(floor_cell2);
                       
                    }
                    //window.alert(JSON.stringify(j));

                    var room = floor.room_list[j];

                    var cell = {};
                    cell.span = 1;
                    cell.type = 2;  // room
                    cell.class = 'room';
                    cell.height = 80;
                    //window.alert(JSON.stringify(room));
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
                   // console.log(room);
                    temp=j;
                }
                if (((temp % (COL_COUNT) != 0)||(temp==0)) && temp<COL_COUNT)
                {
                    //window.alert(temp);
                    var num = (COL_COUNT) - ((parseInt(temp)+1) % (COL_COUNT));
                //     var val2 = parseInt(temp)+1;
                //    var val1= (val2 % (COL_COUNT));
                     //window.alert("Num: "+num);
                     num=(num<10)?(num+10):num;
                    for(var i=0;i<num;i++)
                    {
                        var val=(parseInt(temp) + 1 + i);
                       // window.alert("I:   "+i+", Value:  "+val);
                        if (val % (COL_COUNT) == 0) {
                           // window.alert("here");

                            $scope.room_status.push(row);
                            // window.alert(j + ':  ' + JSON.stringify(row));
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
                             //cell.data.name = '<div class="pending"> Pending</div><div class="cleaning">Cleaning</div><div class="dnd">DND</div><div class="refuse">Refuse</div><div class="delay">Delay</div><div class="unassigned">Unassigned</div><div class="finished">Finished</div>';
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
                // if(temp % (COL_COUNT) == 0)
                // {
                //     window.alert("Check: "+JSON.stringify(row));
                // }
                

               
                // var col = {};
                // col.type = 1;    // floor
                // col.class = 'floor';
                // col.colspan = COL_COUNT;
                // col.span = 1;
                // col.data = {};
                // col.data.id = '';
                // col.data.name = '';
                // $scope.room_status.push(col);


                if (row.length > 0)
                {
                    $scope.room_status.push(row);
                    row=[];
                    
                    var cell = {};
                    cell.span = 1;
                    cell.colspan=11;
                    cell.type = 2;  // room
                    cell.class = 'test';
                    cell.height = 20;
                    cell.data = '';
                    row.push(cell);
                    $scope.room_status.push(row);
                    row = [];
               
                   // window.alert(JSON.stringify($scope.room_status));
                }
                // if (j == (floor.room_list.length - 1)) {
                //     row = [];
                //     var cell = {};
                //     cell.span = 11;
                //     cell.colspan = 11;
                //     cell.type = 2;  // room
                //     cell.class = 'room';
                //     row.push(cell);
                //     $scope.room_status.push(row);
                // }
                row = [];
                
           
         }
         }
            row = [];
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

});
