app.controller('TurndownAssignmentController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, $q, AuthService, toaster) {
    var MESSAGE_TITLE = 'Room Assignment';

    $scope.models = [
        {listName: "Source", items: [], dragging: false},
        {listName: "Target", items: [], dragging: false}
    ];

    $scope.sub_count = {};
    $scope.sub_count.check_in = 0;
    $scope.sub_count.check_out = 0;
    $scope.sub_count.rush_clean = 0;
    $scope.sub_count.dirty = 0;
    $scope.sub_count.clean = 0;
    $scope.sub_count.due_out = 0;
    $scope.sub_count.arrival = 0;

    $scope.selected_sub_count = {};
    $scope.selected_sub_count.total = 0;
    $scope.selected_sub_count.check_in = 0;
    $scope.selected_sub_count.due_out = 0;
    $scope.selected_sub_count.arrival = 0;
    $scope.selected_sub_count.check_out = 0;
    $scope.selected_sub_count.duration = 0;

    $scope.room_category = '';

    var current_dispatcher = 0;
    var hskp_setting_value = {};
    hskp_setting_value.pax_allowance = 0;
    hskp_setting_value.adult_pax_allowance = 5;
    hskp_setting_value.child_pax_allowance = 5;

    // pip
    $scope.isLoading = false;

    $scope.loadFloorFilters = function(query) {
        return $scope.floor_list.filter(function(type) {
            return type.floor_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.onSelectCategory = function(category) {
        $scope.room_category = category;
        $scope.searchRooms();
    }

    $scope.onChangeBuilding = function() {
        $scope.searchRooms();   
        $scope.floor_tags = [];
        getFloorList();
    }

    function getFloorList() {
        $scope.floor_tags = [];        

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.building_id = $scope.building_id;


        $http({
            method: 'POST',
            url: '/floor/list',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.floor_list = response.data;
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.searchRooms = function() {
        //console.log(row.floor_tags);
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        request.building_id = $scope.building_id;
        request.floors = [];

        for(var i = 0; i < $scope.floor_tags.length; i++)
            request.floors.push($scope.floor_tags[i].id);

        request.room_name = $scope.query_name;
        request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;

        $scope.models[0].items = [];
        $http({
            method: 'POST',
            url: '/frontend/hskp/getroomlistforturndown',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {            
                $scope.models[0].items = response.data.datalist;
                $scope.sub_count = response.data.sub_count;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    function initData() {
        getBuildingList();
        getFloorList();        
        getShiftList();
        getAttendantList();
    }

    function getBuildingList() {
        $scope.building_id = 0;

        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/build/list',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
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

    function getShiftList() {
        $scope.shift_group_id = 0;
        $scope.attendant_selected = [];    

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/shiftlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.shift_list = response.data.shifts;

                var all_shift = {};
                all_shift.shift = 0;
                all_shift.name = 'All Shifts';
                $scope.shift_list.unshift(all_shift);

                $scope.shift_group_id = $scope.shift_list[0].shift;
                $scope.floor_list = response.data.floors;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    $scope.searchStaff = function() {
        getAttendantList();
    }

    function getAttendantList() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.shift = $scope.shift_group_id;
        request.name = $scope.attendant_name;

        $http({
            method: 'POST',
            url: '/frontend/hskp/attendantlistforturndown',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.attendantlist = response.data.attendant_list;
                $scope.supervisorlist = response.data.supervisor_list;
                console.log(response);

                if( $scope.attendantlist.length > 0 )
                    $scope.onSelectAttendant($scope.attendantlist[0] );

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    initData();

    $scope.onChangeShift = function() {
        getAttendantList();
    }

    function hasDuplicates(array) {
        return (new Set(array)).size !== array.length;
    }

    $scope.sort_flag = true;

    $scope.getStartTime = function (roomlist, $index) {
        var start = moment('08:00', 'HH:mm').add(30 * $index, 'minute');

        return start.format('HH:mm');
    }

    $scope.onSelectAttendant = function(row) {
        var profile = AuthService.GetCredentials();
        var request = {};
        request.dispatcher = row.id;
        request.property_id = profile.property_id;

        for(var i = 0; i < $scope.attendantlist.length; i++ )
        {
            var item = $scope.attendantlist[i];
            item.selected = row.id == item.id;
        }
        
        $scope.models[1].items = [];
        $http({
            method: 'POST',
            url: '/frontend/hskp/assignedroomlistforturndown',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.models[1].items = response.data.datalist;  

                hskp_setting_value = response.data.hskp_setting_value;

                current_dispatcher = row.id;
                $scope.selected_sub_count = response.data.sub_count;
                updateCurrentAssignedValues();

                $scope.searchRooms();
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
     
    }

    $scope.onAssign = function() {
        if( current_dispatcher < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Attendant');
            return;
        }

        var profile = AuthService.GetCredentials();
        var request = {};
        
        request.property_id = profile.property_id;
        request.dispatcher = current_dispatcher;
        request.assigned_list = [];

        // find first rush flag with not 
        var count = 0;

        for(var i = 0; i < $scope.models[1].items.length; i++ )
        {
            var row = $scope.models[1].items[i];
            request.assigned_list[i] = row.id;
        }

         $http({
            method: 'POST',
            url: '/frontend/hskp/createroomassignmentforturndown',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.models[1].items = response.data.datalist;                  
                updateCurrentAssignedValues();
                $scope.searchRooms();
                console.log(response);


            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }

    $scope.onAutoAssign = function() {
        var profile = AuthService.GetCredentials();
        var request = {};
        
        request.property_id = profile.property_id;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/assignroomwithautoforturndown',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                getAttendantList();

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    function canBeReassigned(item) {
        if( !item )
            return false;

        if( item.dispatcher < 1 )
            return true;

        if( item.working_status == 1 )
            return false;

        return true;
    }

    function updateCurrentAssignedValues() {
        if( current_dispatcher < 1 )
            return;

        $scope.selected_sub_count.total = $scope.models[1].items.length;

        for(var i = 0; i < $scope.attendantlist.length; i++ )
        {
            var row = $scope.attendantlist[i];
            if( row.id == current_dispatcher )
            {
                row.assigned_count = $scope.models[1].items.length;
            }
        }

        for(var i = 0; i < $scope.models[1].items.length; i++)
        {
            var row = $scope.models[1].items[i];
            $scope.selected_sub_count.duration += row.max_time;
            $scope.selected_sub_count.duration += parseInt(hskp_setting_value.adult_pax_allowance);
        }
    }

    /**
     * dnd-dragging determines what data gets serialized and send to the receiver
     * of the drop. While we usually just send a single object, we send the array
     * of all selected items here.
     */
    $scope.getSelectedItemsIncluding = function(list, item) {
        if( canBeReassigned(item) )
            item.selected = true;
        else
            item.selected = false;

        return list.items.filter(function(item) { return item.selected; });
    };

    /**
     * We set the list into dragging state, meaning the items that are being
     * dragged are hidden. We also use the HTML5 API directly to set a custom
     * image, since otherwise only the one item that the user actually dragged
     * would be shown as drag image.
     */
    $scope.onDragstart = function(list, event) {
        list.dragging = true;
        if (event.dataTransfer.setDragImage) {
            var img = new Image();
            img.src = '/frontpage/img/ic_content_copy_black_24dp_2x.png';
            event.dataTransfer.setDragImage(img, 0, 0);
        }
    };

    /**
     * In the dnd-drop callback, we now have to handle the data array that we
     * sent above. We handle the insertion into the list ourselves. By returning
     * true, the dnd-list directive won't do the insertion itself.
     */
    $scope.onDrop = function(list, items, index) {
        angular.forEach(items, function(item) { item.selected = false; });
        list.items = list.items.slice(0, index)
                  .concat(items)
                  .concat(list.items.slice(index));

        updateCurrentAssignedValues();
                  
      return true;
    }

    /**
     * Last but not least, we have to remove the previously dragged items in the
     * dnd-moved callback.
     */
    $scope.onMoved = function(list) {        
        list.items = list.items.filter(function(item) { return !item.selected; });
        updateCurrentAssignedValues();
    };
});
