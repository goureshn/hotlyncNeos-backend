app.controller('RosterNewController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, $q, AuthService, toaster) {
    var MESSAGE_TITLE = 'Roster Allocation';
    var current_dispatcher = 0;
    $scope.td_status = "Dropped";
    $scope.td_css = 'btn-primary';
    $scope.query={};
    $scope.totalDisplayed = 30;
    $scope.totalAssignCount = 30;

    $scope.onLoadMore = function() {
        $scope.totalDisplayed += 20;
    }

    $scope.onLoadMoreAssigne = function() {
        $scope.totalAssignCount += 20;
    }

    $scope.assign_mode_list = [
        'Device Based',
        'User Based',
    ];

    $scope.assign_mode = $scope.assign_mode_list[0];
    $scope.active_device = false;

    $scope.models = [
        { listName: "Source", items: [], dragging: false },
        { listName: "Target", items: [], dragging: false }
    ];

    $scope.locations = [
        { id: 0, label: 'By Floor' },
        { id: 1, label: 'By Room' }
    ];

    $scope.filter = {};
    $scope.filter_list_ids = [];
    $scope.filter_list = [
        { id: 0, label: 'Unassigned' },
        { id: 1, label: 'Dirty' },
        { id: 2, label: 'Clean' },
        { id: 3, label: 'Inspected' },
        { id: 4, label: 'Due Out' }
    ];

    $scope.filterlist_hint = { buttonDefaultText: 'Room Status' };
    $scope.filterlist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };

    $scope.multiuserevents = {
        onItemSelect: function (item) {
            if(item.id == 0)
            {
                $scope.filter.unassigned=true;
            }
            else if (item.id == 1)
            {
                $scope.filter.dirty = true;
            }
            else if (item.id == 2)
            {
                $scope.filter.clean = true;
            }
            else if (item.id == 3)
            {
                $scope.filter.inspected = true;
            }
            else 
            {
                $scope.filter.dueout = true;
            }
            $scope.filterList();
        },
        onItemDeselect: function (item) {
            if(item.id==0)
            {
            $scope.filter.unassigned=false;
            }
            else if (item.id==1){
            $scope.filter.dirty = false;
            }
            else if (item.id==2){
                $scope.filter.clean = false;
            }
            else if (item.id==3){
                $scope.filter.inspected = false;
            }
            else {
                $scope.filter.dueout = false;
            }
            $scope.filterList();
        },
        onDeselectAll: function () {
            $scope.filter={};
            $scope.filterList();
        }
    };

    $scope.occupfilter={};
    $scope.occup_filter_list_ids=[];
    $scope.occup_filter_list = [
        { id: 0, label: 'Occupied' },
        { id: 1, label: 'Vacant' }           
    ];

    $scope.occupfilterlist_hint = { buttonDefaultText: 'Occupancy' };
    $scope.occupfilterlist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };

    $scope.occupmultiuserevents = {
        onItemSelect: function (item) {               
            if(item.id == 0)
            {
                $scope.occupfilter.occupied=true;
            }
            else 
            {
                $scope.occupfilter.vacant = true;
            }

            $scope.filterList();    
        },
        onItemDeselect: function (item) {
            if(item.id == 0)
            {
                $scope.occupfilter.occupied=false;
            }
            else 
            {
                $scope.occupfilter.vacant = false;
            }

            $scope.filterList();
        },
        onDeselectAll: function () {
            $scope.occupfilter = {};
            $scope.filterList();
        }
    };

    $scope.serviceStatus_filter_list_ids = [];
    $scope.serviceStatus_filter_list = [];
    $scope.serviceStatusFilter_hint = { buttonDefaultText: 'Service State' };

    $scope.serviceStatusFilter_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };

    $scope.serviceStatusFilterEvents = {
        onItemSelect: function (item) {
            $scope.filterList();
        },
        onItemDeselect: function (item) {
            $scope.filterList();
        },
        onDeselectAll: function () {
            $scope.serviceStatus_filter_list_ids = [];
            $scope.filterList();
        },
        onSelectAll:function() {
            $scope.serviceStatus_filter_list_ids = angular.copy($scope.serviceStatus_filter_list);
            $scope.filterList();
        }
    };

    function getInitServiceStateList() {
        $http({
            method: 'GET',
            url: '/frontend/hskp/getservicestatelist',
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.serviceStatus_filter_list = response.data;
            }).catch(function(response) {
        })
            .finally(function() {
            });
    }

    $scope.exceptions_list = [];
    $scope.casual_staff_list = [];
    $scope.casual_staff = { 'new_staff_name': null, 'id': null };
    $scope.choice = 0;
    $scope.credits = 0;

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;

    function initTimeRanger() {
        var start_time = moment().format('DD-MM-YYYY 00:00');
        var end_time = moment().format('DD-MM-YYYY HH:mm');

        $scope.dateRangeOption = {
            timePicker: true,
            timePickerIncrement: 5,
            format: 'DD-MM-YYYY HH:mm',
            startDate: start_time,
            endDate: end_time
        };

        $scope.time_range = start_time + ' - ' + end_time;

        getTimeRange();

        $scope.$watch('time_range', function (newValue, oldValue) {
            if (newValue == oldValue)
                return;

            getTimeRange();
            roomList();
        });
    }

    $scope.current = 0;
    $scope.prevList = 0;
    $scope.creditTotal = function () {
        if ($scope.models[1].items.length != $scope.prevList) {
          
            $scope.credits = 0;
            $scope.models[1].items.forEach(element => {
                $scope.credits = $scope.credits + element.credits;
            });
           
            $scope.prevList = $scope.models[1].items.length;
        }

        return $scope.credits;
    }
    $scope.vacantTotal = function () {
        $scope.dirty_vacant = 0;
        $scope.models[1].items.forEach(element => {            
            if ((element.room_status == "Dirty") && (element.occupancy == "Vacant"))
                $scope.dirty_vacant = $scope.dirty_vacant + 1;              
        });

        return $scope.dirty_vacant;
    }

    $scope.occupiedTotal = function () {
        $scope.dirty_occupied = 0;
        $scope.models[1].items.forEach(element => {             
            if ((element.room_status == "Dirty") && (element.occupancy == "Occupied"))
                $scope.dirty_occupied = $scope.dirty_occupied + 1;              
        });
           
        return $scope.dirty_occupied;
    }

    $scope.dueOut = function () {
        $scope.due_out = 0;
        $scope.models[1].items.forEach(element => {        
            if (element.departure == $scope.current_date)
                $scope.due_out = $scope.due_out + 1;          
        });

        return $scope.due_out;
    }

    $scope.linenChange = function () {
        $scope.linen_change = 0;
        $scope.models[1].items.forEach(element => {        
            if (element.linen_chng == 1)
                $scope.linen_change = $scope.linen_change + 1;          
        });

        return $scope.linen_change;
    }
   
    function getTimeRange() {
        $scope.begin_date_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_date_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
    }

    initTimeRanger();

    function roomList() {
        $scope.searchRooms();
    }
    
    $scope.room_category = '';

    // pip
    $scope.isLoading = false;

    $scope.loadFloorFilters = function (query) {
        return $scope.floor_list.filter(function (type) {
            return type.floor_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.onSelectCategory = function (category) {
        $scope.room_category = category;
        $scope.searchRooms();
    }

    $scope.onChangeBuilding = function () {
        if ($scope.choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
            getFloorList();
        }
        $scope.floor_tags = [];
        $scope.floors_tags = [];        
    }

    $scope.onChangeLocation = function (building_id, dept_func_id) {
        $scope.selected_count = 0;
        $scope.roster.unassigned = 0;
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        if ($scope.choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
            getFloorList();
        }
    }

    function getFloorList() {
        $scope.floor_tags = [];
        $scope.floors_tags = [];

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.building_id = $scope.building_id;

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

    $scope.dirty_vacant = 0;
    $scope.dirty_occupied = 0;
    
    $scope.current_date = moment(new Date()).format('YYYY-MM-DD');

    $scope.searchRooms = function () {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func = $scope.dept_func_id;
        request.job_role_id = $scope.job_role_id;
        request.building_id = $scope.building_id;
        request.floors = [];
      
        for (var i = 0; i < $scope.floor_tags.length; i++) {
            request.floors.push($scope.floor_tags[i].id);
            
        }
        request.room_name = $scope.query.query_name;
        if ($scope.filter) {
            request.filter = $scope.filter;
            
        }
        if ($scope.occupfilter) {
            request.occupfilter = $scope.occupfilter;
        }

        request.serviceStateList = $scope.serviceStatus_filter_list_ids.map(item => {
            return item.id;
        });

        request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;
        request.device_flag = $scope.assign_mode == 'Device Based' ? 1 : 0;        
        request.exceptions_list = $scope.exceptions_list;
        $scope.models[0].items = [];
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlistunassign',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.high_priority=[];
                angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected") || (item.room_status == 'Clean' && item.occupancy == 'Vacant'))
                    {
                        if(item.status == "Vacant Inspected" || (item.room_status=='Inspected' && item.occupancy == 'Vacant'))
                        { 
                            if(item.show_credit=='0') 
                            {    
                            item.credits = 0;
                            }
                        }
                        else
                            item.credits = 0;
                    }
                    if(item.priority=='1')
                    {
                        $scope.high_priority.push(item);
                        item.myColor = "red";                     
                    }
                 
                });
               
                angular.forEach($scope.high_priority, function (item) {
                var index = response.data.datalist.indexOf(item);
                
                if (index != -1){
                    var removed = response.data.datalist.splice(index,1);                    
                    response.data.datalist.unshift(removed[0]);
                }
                });
              
                $scope.models[0].items = response.data.datalist;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.floor_rooms = [];

    $scope.checkAll = function (params) {
   
        if ($scope.select_all == 1) {

            angular.forEach($scope.models[0].items, function (item) { item.selected = false; });
            $scope.select_all_text = "Select All";
            $scope.select_all = 0;

        }
        else {

            angular.forEach($scope.models[0].items, function (item) { item.selected = true; });
            $scope.select_all_text = "Unselect All";
            $scope.select_all = 1;
        }

    }

    $scope.filterList = function () {
      
        if ($scope.choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
        }
    }
    

    $scope.unAssignAll = function () {        
        $scope.exceptions_list = [];
        $scope.models[1].items = [];
        $scope.creditTotal();

        $scope.onChangeLocation($scope.building_id, $scope.dept_func_id);        
    }

    $scope.unAssignAllRosters = function (item) {
        var message = {};
        message.title = 'Clear All Rosters';

        var hskp_role = 'Attendant';

        if( $scope.assign_mode == 'Device Based' )
        {         
            var dept_func = $scope.dept_func_list.find(row => row.dept_func_id == $scope.dept_func_id );        
            message.content = 'Do you want to clear all rosters for ' + dept_func.function + ' department?';
            hskp_role = dept_func.hskp_role;
        }

        if( $scope.assign_mode == 'User Based' )
        {         
            var job_role = $scope.job_role_list.find(row => row.id == $scope.job_role_id );        
            message.content = 'Do you want to clear all rosters for ' + job_role.job_role + ' job role?';
            hskp_role = dept_func.hskp_role;
        }

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',
            controller: 'DeleteConfirmCtrl',
            resolve: {
                message: function () {
                    return message;
                }
            }
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
            {
                if( hskp_role == 'Attendant' )
                    confirmRetainCleaning(item);
                else
                    clearAllRosters(0);
            }
        }, function () {

        });
    }

    function confirmRetainCleaning() {
        var message = {};
        message.title = 'Retain Cleanning Status';
        message.content = 'Do you want to retain cleaning status?';
        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',
            controller: 'DeleteConfirmCtrl',
            resolve: {
                message: function () {
                    return message;
                }
            }
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
            {
                clearAllRosters(1);
            }
        }, function () {
            clearAllRosters(0);
        });
    }

    function clearAllRosters(retain_flag)
    {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.device_flag = $scope.assign_mode == 'Device Based' ? 1 : 0;
        request.dept_func = $scope.dept_func_id;
        request.job_role_id = $scope.job_role_id;
        request.property_id = profile.property_id;
        request.retain_flag = retain_flag;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/clearallrosters',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.$emit('onCreateRoaster');
                if (response.data.code == 200) {
                    toaster.pop('success', MESSAGE_TITLE, response.data.message);
                    getDeviceList($scope.device);
                }             
                else 
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                }
                
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    $scope.warningmodal = function(item,warning,all_flag){
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/clear_roster.html',
            controller: 'ClearRosterCtrl',
            windowClass: 'app-modal-window',
            resolve: {
                item: function () {
                    return item;
                },
                warning: function () {
                    return warning;
                }, 
                all_flag: function () {
                    return all_flag;
                } 
            }
            });
            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {
    
            });
    }
    
    $scope.onUpdateRoomList = function(){        
        var hskp_role = 'Attendant';

        if( $scope.assign_mode == 'Device Based' )
        {         
            var dept_func = $scope.dept_func_list.find(row => row.dept_func_id == $scope.dept_func_id );                 
            hskp_role = dept_func.hskp_role;
        }

        if( $scope.assign_mode == 'User Based' )
        {         
            var job_role = $scope.job_role_list.find(row => row.id == $scope.job_role_id );                    
            hskp_role = dept_func.hskp_role;
        }

        if( hskp_role == 'Supervisor' )
        {
            $scope.onUpdate(0);
            return;
        }

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/retain_roster.html',
            controller: 'RetainRosterCtrl',
            windowClass: 'app-modal-window',
            scope: $scope,
            resolve: {               
                warning: function () {
                    return 'Do you want to retain previous housekeeping status?';
                }
            }
            });
            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {
            });
    }
   
    $scope.findRoomsforFloor = function (list, items, index, td_flag) {
        //console.log(row.floor_tags);
      
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        request.building_id = $scope.building_id;
        request.floors = [];
      
        for (var i = 0; i < $scope.floors_tags.length; i++) {
            if ($scope.floors_tags[i].selected == true) {
                request.floors.push($scope.floors_tags[i].id);
          
            }
        }

        request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        request.room_name = $scope.query.query_name;
        request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;
        request.device_flag = 1;
        request.dept_func = $scope.dept_func_id;
        request.job_role_id = $scope.job_role_id;
        request.device_flag = $scope.assign_mode == 'Device Based' ? 1 : 0;        

        request.exceptions_list = $scope.exceptions_list;
        if ($scope.filter) {
            request.filter = $scope.filter;          
        }

        if ($scope.occupfilter) {
            request.occupfilter = $scope.occupfilter;            
        }
        
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlistunassign',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected") || (item.room_status == 'Clean' && item.occupancy == 'Vacant'))
                       item.credits = 0;
                });
              
                $scope.floor_rooms = response.data.datalist;
                if(td_flag == 1)
                {
                    $scope.floor_rooms.forEach((element)=>{
                        element.room_status='Turndown';
                    })
                }
          
                if ($scope.roster.unassigned == 1) {
                    var arr = [];
                    items.filter(function (item) {
                        $scope.floor_rooms.filter(function (item1) {                            
                            if (item.unassigned_list.indexOf(item1.room) != -1) {                                
                                arr.push(item1);
                            }
                        })
                    });
                   
                    $scope.floor_rooms = arr;
                }
                
                $scope.floor_rooms.filter(function (item) {

                    $scope.exceptions_list.push(item.id);
                });
               
                angular.forEach(items, function (item) { item.selected = false; });
        
                list.items = list.items.slice(0, index)
                    .concat($scope.floor_rooms)
                    .concat(list.items.slice(index));
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
    $scope.searchFloors = function () {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func_id = $scope.dept_func_id;
        request.job_role_id = $scope.job_role_id;
        request.building_id = $scope.building_id;
        
        if ($scope.filter) {
            request.filter = $scope.filter;          
        }

        if ($scope.occupfilter) {
            request.occupfilter = $scope.occupfilter;            
        }

        request.floor_name = $scope.query.floor_query_name;
        request.dispatcher = current_dispatcher;
        if( $scope.assign_mode == 'Device Based')
            request.device_flag = 1;
        else    
            request.device_flag = 0;

        $scope.models[0].items = [];
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getfloorlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.totalDisplayed = 30;
                $scope.models[0].items = response.data.floor_list;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    function refresh() {
        $scope.exceptions_list = [];
        $scope.update_flag = 0;
        $scope.selected_count = 0;
        $scope.roster = {};
        $scope.choice = 0;
        $scope.devicelist = [];
        $scope.user_list = [];
        $scope.device = {};
        $scope.user = {};
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        $scope.models[0].items = [];
        $scope.models[1].items = [];

        $scope.casual_staff = { 'new_staff_name': null, 'id': null };
        $scope.update = "Update";
    }

    function initData() {
        refresh();
        getBuildingList();
        getSupervisorList();
        getFloorList();
        getDeptFuncList();
        getJobRoleList();
        getInitServiceStateList();
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

    function getSupervisorList() {
        $scope.supervisor_id = 0;
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.val = '';
        $http({
            method: 'POST',
            url: '/frontend/guestservice/supervisorlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.supervisor_list = response.data.suplist;
               
                var all_shift = {};
                all_shift.supervisor_id = 0;
                all_shift.supervisor = 'None';
                $scope.supervisor_list.unshift(all_shift);
                $scope.supervisor = $scope.supervisor_list[0];
               
               
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    $scope.onSelectSupr = function (){
        $scope.arrclick=false;
        $scope.supervisor_id=$scope.supervisor.id;
        $scope.suprShort=$scope.supervisor.supervisor.split(' ')[0];
    }

    $scope.createStaff = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/casual_staff.html',
            controller: 'CasualStaffCtrl',
            scope: $scope,
            resolve: {
                dept_func_id: function () {
                    return $scope.dept_func_id;
                }
                ,
                casual_staff_list: function () {
                    return $scope.casual_staff_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }
    $scope.setcasualStaff = function (staff) {
        $scope.casual_staff = staff;
    }

    function getDeptFuncList() {
        $scope.dept_func_id = 0;
        $scope.attendant_selected = [];

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.gs_device = 2;

        request.val = '';
        $http({
            method: 'POST',
            url: '/frontend/hskp/hskpdeptfunclist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.dept_func_list = response.data;

                var all_shift = {};
                all_shift.dept_func_id = 0;
                all_shift.function = 'None';
                $scope.dept_func_list.unshift(all_shift);

                $scope.dept_func_id = $scope.dept_func_list[0].dept_func_id;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }

    function getJobRoleList() {
        $scope.job_role_id = 0;

        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/hskpjobrolelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.job_role_list = response.data;

                var all_shift = {};
                all_shift.id = 0;
                all_shift.job_role = 'None';
                $scope.job_role_list.unshift(all_shift);

                $scope.job_role_id = $scope.job_role_list[0].id;
            }).catch(function (response) {
            })
            .finally(function () {
            });
    }



    $scope.searchDevice = function () {
        if( $scope.assign_mode == 'Device Based' )
            getDeviceList(0);
        else    
            getUserList(0);
    }

    function getDeviceList(device) {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func = $scope.dept_func_id;
        request.name = $scope.device_name;
        request.active_flag = $scope.active_flag ? 1: 0;
        
        if ($scope.dept_func_id > 0) {
            $http({
                method: 'POST',
                url: '/frontend/hskp/hskpdevicelist',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.devicelist = response.data.device_list;

                    console.log(response);
                    
                    if (device != 0)
                        $scope.onSelectDevice(device);
                    else if ($scope.devicelist.length > 0)
                        $scope.onSelectDevice($scope.devicelist[0]);
                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function () {
                    $scope.isLoading = false;
                });
        }
    }

    function getUserList(device) {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;        
        request.job_role_id = $scope.job_role_id;
        request.name = $scope.device_name;
        request.active_flag = $scope.active_flag ? 1 : 0;
        
        if ($scope.job_role_id > 0) {
            $http({
                method: 'POST',
                url: '/frontend/hskp/hskpuserlist',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.user_list = response.data.user_list;

                    console.log(response);
                    
                    if (device != 0)
                        $scope.onSelectUser(device);
                    else if ($scope.user_list.length > 0)
                        $scope.onSelectUser($scope.user_list[0]);
                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function () {
                    $scope.isLoading = false;
                });
        }
    }

    initData();

    $scope.onChangeDeptFunc = function () {
        refresh();
        getDeviceList(0);
        var profile = AuthService.GetCredentials();

        $http.get('/list/casualstaff?property_id=' + profile.property_id + '&dept_func_id=' + $scope.dept_func_id)
            .then(function (response) {
                $scope.casual_staff_list = response.data;
            });
    }

    $scope.onChangeJobRole = function () {
        refresh();
        getUserList(0);        
    }

    $scope.getRosters = function (dept_func_id, device_id) {
        var request = {};

        request.filter = $scope.filter;
        request.dept_func_id = $scope.dept_func_id;
        request.device_id = device_id;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getrosters',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {                
                if (response.data.datalist != null) {
                    $scope.high_priority=[];
                    angular.forEach(response.data.datalist.locations, function (item) {
                        // if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected") || (item.room_status == 'Clean' && item.occupancy == 'Vacant'))
                        // {
                        //     if(item.status == "Vacant Inspected" || (item.room_status=='Inspected' && item.occupancy == 'Vacant'))
                        //     { 
                        //         if(item.show_credit=='0') 
                        //         {    
                        //             item.credits = 0;
                        //         }
                        //     }
                        //     else
                        //         item.credits = 0;
                        // }
                        if(item.priority == '1')
                        {
                            $scope.high_priority.push(item);
                            item.myColor="red";
                        }
                    });
                   
                    angular.forEach($scope.high_priority, function (item) {
                        var index = response.data.datalist.locations.indexOf(item);
                        
                        if (index != -1){
                            var removed = response.data.datalist.locations.splice(index,1);
                            response.data.datalist.locations.unshift(removed[0]);
                        }
                    });

                    $scope.roster = response.data.datalist;
                    if ($scope.roster.casual_staff_name != null)
                        $scope.casual_staff = { 'new_staff_name': $scope.roster.casual_staff_name, 'id': $scope.roster.generic_id };
                    if($scope.roster.supervisor_id==null)
                    {
                        $scope.supervisor = $scope.supervisor_list[0];
                    }
                    else
                    {
                        $scope.supervisor_list.forEach(element => {
                            if ((element.id == $scope.roster.supervisor_id))
                            {
                                $scope.supervisor = element;
                                $scope.suprShort=$scope.supervisor.supervisor.split(' ')[0];
                                $scope.arrclick=false;
                            }
                        });
                    }
                    if ($scope.roster.locations) {
                        $scope.update_flag = 1;
                        if (!$scope.roster.locations[0])
                        {
                            $scope.update = 'Assign';
                        }

                        for (var i = 0; i < $scope.roster.locations.length; i++) 
                        {
                            $scope.models[1].items[i] = $scope.roster.locations[i];
                            $scope.exceptions_list.push($scope.roster.locations[i].id);
                        }

                        $scope.totalAssignCount = 30;
                    }
                }
            
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    function getRostersWithUser(user_id) {
        var request = {};

        request.filter = $scope.filter;
        request.job_role_id = $scope.job_role_id;
        request.hskp_user_id = user_id;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/rosterroomlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {                
                if (response.data.roster != null) {              
                    $scope.roster = response.data.roster;
                    if ($scope.roster.locations) {
                        $scope.update_flag = 1;
                        if ($scope.roster.locations.length < 1)
                            $scope.update = 'Assign';
                        else    
                            $scope.update = 'Update';
                        
                        for (var i = 0; i < $scope.roster.locations.length; i++) 
                        {
                            $scope.models[1].items[i] = $scope.roster.locations[i];
                            $scope.exceptions_list.push($scope.roster.locations[i].id);
                        }

                        $scope.totalAssignCount = 30;
                    }
                }
            
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }


    function hasDuplicates(array) {
        return (new Set(array)).size !== array.length;
    }

    $scope.sort_flag = true;

    $scope.getStartTime = function (roomlist, $index) {
        var start = moment('08:00', 'HH:mm').add(30 * $index, 'minute');

        return start.format('HH:mm');
    }

    $scope.getAssigneTotalTime = function (roomlist) {
        var total = 0;
        for (var i = 0; i < models[0].items.length; i++) {
            total += models[0].items[i].max_time;
        }

        return total;
    }

    function selectItem(row)
    {
        $scope.supervisor = $scope.supervisor_list[0];
        $scope.suprShort=null;
        $scope.arrclick=true;
        
        $scope.update_flag = 0;
        $scope.models[1].items = [];
        $scope.exceptions_list = [];
        $scope.choice = 0;
        $scope.update = 'Update';
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        $scope.supervisor_id = row.supervisor_id;

        $scope.casual_staff = { 'new_staff_name': null, 'id': null };

        if ($scope.choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();           
        }

    }

    $scope.onSelectDevice = function (row) {
        $scope.device = row;
        console.log(row);

        for (var i = 0; i < $scope.devicelist.length; i++) {
            var item = $scope.devicelist[i];
            item.selected = row.id == item.id;
        }
        $scope.roster = {};
        $scope.roster.user_id = 0;
        $scope.roster.device_id = row.device_id;
        $scope.roster.device = row.id;
        $scope.roster.name = row.name + ' Roster';
        
        selectItem(row);

        $scope.getRosters($scope.dept_func_id, row.id);
    }

    $scope.onSelectUser = function (row) {
        $scope.user = row;
        console.log(row);

        for (var i = 0; i < $scope.user_list.length; i++) {
            var item = $scope.user_list[i];
            item.selected = row.id == item.id;
        }

        $scope.roster = {};
        $scope.roster.user_id = row.id;
        $scope.roster.device = 0;
        $scope.roster.name = row.wholename;
        
        selectItem(row);

        getRostersWithUser(row.id);
    }
    
    $scope.selected_count = 0;
    $scope.selection = function (item) {
        item.selected = !item.selected;
        if(item.selected==true)
            $scope.selected_count = $scope.selected_count + 1;
        else
        {
            $scope.selected_count = $scope.selected_count - 1;
        }
        if (($scope.selected_count) == 0 && $scope.td_status == "Clicked")
        {
            $scope.td_css = 'btn-primary';
            $scope.td_status = "Dropped";
        }
    }

    $scope.onTurndown = function () {
        var temp = $scope.models[0].items.filter(function (item) { if (item.selected == true) return item.id; });
        if (temp.length > 0)
        {
            if ($scope.td_status == "Dropped")
            {
                $scope.td_css = 'btn-danger';
                $scope.td_status = "Clicked";
            }
            else 
            {
                $scope.td_css = 'btn-primary';
                $scope.td_status = "Dropped";
            }
        }   
        else
        {
            $scope.td_css = 'btn-primary'; 
            $scope.td_status = "Dropped";
        }
    }

    function assignRoom() {
        console.log("Onassign");
        if ($scope.roster.device < 1 && $scope.roster.user_id < 1) {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Attendant');
            return;
        }
        if ($scope.roster.name == '') {
            toaster.pop('info', MESSAGE_TITLE, 'Please enter Roster Name');
            return;
        }

        var profile = AuthService.GetCredentials();
        var request = {};

        request.property_id = profile.property_id;
        request.dispatcher =  profile.id  ;//$scope.roster.device;
        request.device_id = $scope.roster.device_id;
        request.device = $scope.roster.device;
        request.hskp_user_id = $scope.roster.user_id;
        request.job_role_id = $scope.job_role_id;
        request.device_flag = $scope.assign_mode == 'Device Based' ? 1: 0;

        request.updated_by = profile.id;
        request.supr_device = $scope.supervisor;
        request.dept_func_id = $scope.dept_func_id;
        request.assigned_list = [];
        request.td_list = [];
        request.roster_name = $scope.roster.name;
        request.total_credits = $scope.credits;
        request.casual_staff = $scope.casual_staff;
        var j = 0;
        for (var i = 0; i < $scope.models[1].items.length; i++) {
            var row = $scope.models[1].items[i];
            request.assigned_list[i] = row.id;
            if (row.room_status == 'Turndown') {  // window.alert(JSON.stringify(row))
                request.td_list[j++] = row.id;
            }
        }

        request.supervisor_id = $scope.supervisor_id;
        console.log(request);
        $http({
            method: 'POST',
            url: '/frontend/guestservice/createrosterdevice',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if(response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);    
                    return;
                }

                $scope.$emit('onCreateRoaster');
                getDeviceList($scope.device);
              
                toaster.pop('success', MESSAGE_TITLE, response.data.message);
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, response.data.message);
            })
            .finally(function () {
                $scope.isLoading = false;
            });

    }

    $scope.onAssign = function()
    {
        var message = {};

        message.title = 'Room Assignment';

        if( $scope.assign_mode == 'Device Based')
            message.content = 'Do you want to assign room for ' + $scope.device.name + '?';
        else
            message.content = 'Do you want to assign room for ' + $scope.user.wholename + '?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',
            controller: 'DeleteConfirmCtrl',            
            resolve: {
                message: function () {
                    return message;
                }
            }
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
            {
                assignRoom();
            }
        }, function () {

        });
    }
    
    $scope.onUpdate = function (retain_flag = 0) {
        console.log("Onupdate");
        if ($scope.roster.device < 1 && $scope.roster.user_id < 1) {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Attendant');
            return;
        }
        if ($scope.roster.name == '') {
            toaster.pop('info', MESSAGE_TITLE, 'Please enter Roster Name');
            return;
        }

        var profile = AuthService.GetCredentials();
        var request = {};

        request.property_id = profile.property_id;
        request.updated_by = profile.id;
        request.dispatcher = profile.id;
        request.device_id = $scope.roster.device_id;
        request.device = $scope.roster.device;
        request.hskp_user_id = $scope.roster.user_id;
        request.job_role_id = $scope.job_role_id;
        request.device_flag = $scope.assign_mode == 'Device Based' ? 1: 0; 
        request.assigned_list = [];
        request.td_list = [];
        request.casual_staff = $scope.casual_staff;
        request.supr_device = $scope.supervisor;
        request.retain_flag = retain_flag;
        // find first rush flag with not 
        var j = 0;
        for (var i = 0; i < $scope.models[1].items.length; i++) {
            var row = $scope.models[1].items[i];
            request.assigned_list[i] = row.id;
            if(row.room_status=='Turndown')
            {  // window.alert(JSON.stringify(row))
                request.td_list[j++] = row.id;}
        }
        
        request.roster_name = $scope.roster.name;
        request.total_credits = $scope.credits;
        request.id = $scope.roster.id;        
        request.supervisor_id = $scope.supervisor_id;
        request.dept_func_id = $scope.dept_func_id;
        console.log(request);
       
        $http({
            method: 'POST',
            url: '/frontend/guestservice/updaterosterdevice',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {                
                if (response.data.code==200)
                {
                    toaster.pop('success', MESSAGE_TITLE, response.data.message);
                    if( $scope.assign_mode == 'Device Based' )
                        getDeviceList($scope.device);
                    if( $scope.assign_mode == 'User Based' )
                        getUserList($scope.user);
                }
                else
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                }
            
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    function canBeReassigned(item) {
        if (!item)
            return false;

        if (item.dispatcher < 1)
            return true;

        if (item.working_status == 1)
            return false;

        return true;
    }

    function updateCurrentAssignedValues() {
        if (current_dispatcher < 1)
            return;

        $scope.roster_name = '';
    }

    /**
     * dnd-dragging determines what data gets serialized and send to the receiver
     * of the drop. While we usually just send a single object, we send the array
     * of all selected items here.
     */
    $scope.getSelectedItemsIncluding = function (list, item) {
       
        $scope.warning = 'Room ' + item.room + ' is currently being cleaned.';
        console.log(list);

        if (canBeReassigned(item))
            item.selected = true;
        else
            {
               item.selected = true; 
            }
           
        return list.items.filter(function (item) {

            return item.selected;
           
        });
    };

    /**
     * We set the list into dragging state, meaning the items that are being
     * dragged are hidden. We also use the HTML5 API directly to set a custom
     * image, since otherwise only the one item that the user actually dragged
     * would be shown as drag image.
     */
    $scope.onDragstart = function (list, event) {
        console.log(JSON.stringify(list));
        list.dragging = true;
        //window.alert(JSON.stringify(list));
        if (event.dataTransfer.setDragImage) {
            var img = new Image();
            img.src = '/frontpage/img/ic_content_copy_black_24dp_2x.png';
            event.dataTransfer.setDragImage(img, 0, 0);
        }
        if(list.listName=='Target')
        {
           // console.log(JSON.stringify(list.items[0]));
            $scope.changeChoice(1);
        }
       
    };

    /**
     * In the dnd-drop callback, we now have to handle the data array that we
     * sent above. We handle the insertion into the list ourselves. By returning
     * true, the dnd-list directive won't do the insertion itself.
     * 
     * 
     */

    $scope.uniqueRooms = function () {
        return 1;

    }
    $scope.changeChoice = function (choice) {
       console.log($scope.dept_func_id);
        if ($scope.choice != choice) {
            $scope.choice = choice;
            $scope.onChangeLocation($scope.building_id, $scope.dept_func_id);
        }

    }
    $scope.onDrop = function (list, items, index, pos) {
        var td_flag=0;
        
        if($scope.td_status=='Clicked')
        {
            console.log(JSON.stringify(list));
            td_flag = 1;
            $scope.selected_count = 0;
            $scope.td_status='Dropped';
            $scope.td_css ='btn-primary'
        }

        if ($scope.choice == 0 && (list.listName == "Target"))
        {
            angular.forEach(items, function (item) {
                if (item.selected == true) {
                    $scope.floors_tags.push(item);
                }
            });
            $scope.findRoomsforFloor(list, items, index,td_flag);
        }
        else 
        {
            angular.forEach(items, function (item) {
                if (list.listName == "Source" && $scope.models[1].dragging == true) 
                {
                    $scope.exceptions_list.splice($scope.exceptions_list.indexOf(item.id), 1)
                }
                else
                    $scope.exceptions_list.push(item.id);

                item.selected = false;
                if(td_flag==1)
                item.room_status='Turndown';
            });

            list.items = list.items.slice(0, index)
                .concat(items)
                .concat(list.items.slice(index));
        }

        return true;
    }

    /**
     * Last but not least, we have to remove the previously dragged items in the
     * dnd-moved callback.
     */
    $scope.onMoved = function (list) {
        list.items = list.items.filter(function (item) { return !item.selected; });
    };

    $scope.addPreference = function (item) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/room_preference.html',
            controller: 'AddPreferenceCtrl',
            windowClass: 'app-modal-window',
            resolve: {
               item: function () {
                    return item;
                }
            }
        });
        modalInstance.result.then(function (row) {
            if( row )
                item.remark = row.remark + '';
            else    
                item.remark = null;
        }, function () {

        });
    };

    $scope.changeLinen = function (item) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/linen_change.html',
            controller: 'LinenChangeCtrl',
            windowClass: 'app-modal-window',
            resolve: {
               item: function () {
                    return item;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };


    $scope.deleteRemark = function (item) {
        
        $scope.remark = item; 
        $scope.remark.isOpen = false;  
        console.log($scope.remark);
        var request = $scope.remark;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/deletepreference',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
               
                toaster.pop('info', MESSAGE_TITLE, 'Preference has been deleted successfully');
                console.log(response);
                getDeviceList($scope.device);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Preference');
            })
            .finally(function () {
               // $scope.isLoading = false;
            });
    }

    $rootScope.$on('onChange', function(event, args){
        $scope.filterList();
    });

    $rootScope.$on('onUpdate', function(event, args){
        $scope.onUpdate(args);
    });

    $rootScope.$on('getdevicelist', function(event, args){
        getDeviceList($scope.device);
    });

    $rootScope.$on('updaterosterlist', function(event, args){
        onUpdate();
    });

    $scope.label = true;
    $scope.onFieldHidden = function(count, item) {
        if(count == 'remark') {
            $scope.label = false;
            item.input = true;
        }
    }

    $scope.onKeySave = function(count, item) {
        if(count == 'remark') {
            $scope.label = true;
            item.input = false;
           
            SaveRemark(item);
        }
    }

    function SaveRemark(item) {
        
        $scope.remark = item; 
        $scope.remark.isOpen = false;  
        item.regex = /^[_A-z0-9]*((-|\s)*[_A-z0-9.])*(?:\s*)$/;
        console.log($scope.remark);

        if (item.remark == undefined) {
            toaster.pop('error', MESSAGE_TITLE, 'Please do not use special characters.');
            return;
        }
        if (item.remark.length > 120) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter less than 120 characters.');
            return;
        }
        console.log(item.remark);
        var request = $scope.remark;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/updatepreference',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
               
                toaster.pop('info', MESSAGE_TITLE, 'Preference has been updated successfully');
                console.log(response);
                getDeviceList($scope.device);
    
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to update Preference');
            })
            .finally(function () {
               // $scope.isLoading = false;
            });
    }

    $scope.onTransferRoom = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/room_transfer.html',
            controller: 'RoomTransferCtrl',
            size: 'lg',
            backdrop: 'static',
            windowClass: 'app-modal-window',
            scope: $scope,
            resolve: {
                device: function() {
                    return $scope.device;
                },  
                user: function() {
                    return $scope.user;
                },      
                dept_func_id: function () {
                    return $scope.dept_func_id;
                },    
                job_role_id: function () {
                    return $scope.job_role_id;
                },    
                devicelist: function () {
                    return $scope.devicelist;
                },         
                user_list: function () {
                    return $scope.user_list;
                },              
            }
        });

        modalInstance.result.then(function (selectedItem) {
        }, function () {                
        });
    }	
});
app.controller('CasualStaffCtrl', function ($scope, $uibModalInstance, $http, AuthService, dept_func_id, casual_staff_list) {
    $scope.dept_func_id = dept_func_id;
    $scope.casual_staff_list = casual_staff_list;
    $scope.createStaff = function () {
        $uibModalInstance.dismiss();
        $scope.setcasualStaff($scope.staff);
    };
    $scope.selectStaff = function (row) {
        $scope.staff = row;
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('AddPreferenceCtrl', function ($scope, $uibModalInstance, toaster,$http, AuthService, item) {
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
        $scope.item.remark = "";
        $uibModalInstance.dismiss('cancelPreference');
    };

});

app.controller('LinenChangeCtrl', function ($scope, $uibModalInstance, $http, toaster,AuthService, item) {
    $scope.item = item;
    var MESSAGE_TITLE = 'Linen Change';

    $scope.saveLinenChange = function () {

        
    var request = $scope.item;
    request.change_linen = $scope.item.change_linen ? 1 : 0;
    if (request.change_linen == 0) {
        toaster.pop('error', MESSAGE_TITLE, 'Please enable manually.');
        return;
    }
   
    $http({
            method: 'POST',
            url: '/frontend/guestservice/changelinenmanual',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
        .then(function (response) {          
            if (response.data.code == 200)
            {         
                toaster.pop('Success', MESSAGE_TITLE, 'Manual Change has been done Successfully');
                $uibModalInstance.dismiss();
                $scope.$emit('onChange', response.data);
            }
            else
            {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Change Manually. Room not Check-in.');
                $uibModalInstance.dismiss();
                $scope.$emit('onChange', response.data);
            }

        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
            toaster.pop('error', MESSAGE_TITLE, 'Failed to Change Manually');
        })
        .finally(function () {            
        });
    };
    
    $scope.cancelChange = function () {
        $uibModalInstance.dismiss('cancelChange');
    };
});

app.controller('ClearRosterCtrl', function ($scope, $uibModalInstance, toaster,$http, AuthService, item,warning,all_flag) {
    $scope.warning1 = warning;
    $scope.item = item;

    var MESSAGE_TITLE = 'Roster Allocation';
   
    $scope.okClear = function () {
        var profile = AuthService.GetCredentials();

        var request = $scope.item;
        request.dept_func = $scope.item.dept_func_id;
        request.property_id = profile.property_id;
      

        if(all_flag == 0)
        {
            $http({
                method: 'POST',
                url: '/frontend/guestservice/clearallrostersfinal',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.$emit('onCreateRoaster');
                    // $scope.models[1].items = response.data.datalist;
                    if (response.data.code == 200) {
                        $uibModalInstance.dismiss();
                        toaster.pop('success', MESSAGE_TITLE, response.data.message);
                        $scope.$emit('getdevicelist', response.data);
                    }
                
                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE + ' Error', 'Failed to delete Rosters'); 
                })
                .finally(function () {
                });
        }
        else if (all_flag == 1) {
            request.room_id = $scope.item.current_cleaning;

            $http({
                method: 'POST',
                url: '/frontend/guestservice/unassignclear',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.$emit('onCreateRoaster');
                    if (response.data.code == 200) {
                        $uibModalInstance.dismiss();
                        toaster.pop('success', MESSAGE_TITLE, response.data.message);
                        $scope.$emit('getdevicelist', response.data);
                    }
                   
                }).catch(function (response) {
                     console.error('Gists error', response.status, response.data);
                     toaster.pop('error', MESSAGE_TITLE + ' Error', 'Failed to unassign room and clear Roster.'); 
                })
                .finally(function () {
                });
           
        }
        else{
            request.room_id = $scope.item.current_cleaning;
         
            $http({
                method: 'POST',
                url: '/frontend/guestservice/updaterosterdevice',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.$emit('onCreateRoaster');
                    if (response.data.code == 200) {
                        $uibModalInstance.dismiss();
                        toaster.pop('success', MESSAGE_TITLE, response.data.message);
                         $scope.$emit('getdevicelist', response.data);
                    }
                   
                }).catch(function (response) {
                     console.error('Gists error', response.status, response.data);
                })
                .finally(function () {
                });
        }
  
    };

    $scope.cancelClear = function () {
        
        $uibModalInstance.dismiss('cancel');
    };
});

app.controller('RetainRosterCtrl', function ($scope, $uibModalInstance, toaster,$http, AuthService ,warning) {
    $scope.warning1 = warning;
   
    $scope.ok= function () {        
        $scope.onUpdate(1);        
        $uibModalInstance.dismiss('cancel');
    };

    $scope.cancel = function () {
        $scope.onUpdate(0);
        $uibModalInstance.dismiss('cancel');
    };

});

app.controller('DeleteConfirmCtrl', function($scope, $uibModalInstance, message) {
    $scope.message = message;
    $scope.ok = function () {
        $uibModalInstance.close('ok');
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('close');
    };
});

app.controller('RoomTransferCtrl', function ($scope, $uibModalInstance, toaster, $http, AuthService, dept_func_id, device, devicelist, job_role_id, user, user_list) {
    var MESSAGE_TITLE = 'Room Transfer';    
    $scope.device_list = devicelist;

    $scope.model = {};
    $scope.old_device = angular.copy(device);
    $scope.new_device = {};
    $scope.new_device.id = 0;

    $scope.old_room_list = [];
    $scope.new_room_list = [];
    console.log($scope.old_device);

    getNewDeviceList($scope.old_device);

    function getNewDeviceList(device) {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.id = device.id;
        request.dept_func = dept_func_id;
        
        
    if (dept_func_id > 0) {
            $http({
                method: 'POST',
                url: '/frontend/hskp/newhskpdevicelist',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.newdevice_list = response.data.device_list;

                    console.log(response);
                    
                    
                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function () {
                    $scope.isLoading = false;
                });
            }
        
    }


    getAssignedRoomList(dept_func_id, device.id, 1);
   
    $scope.ok = function () {
        
    };

    $scope.cancel = function () {        
        $uibModalInstance.dismiss('cancel');
    };

    $scope.onOldDeviceSelect = function($item, $model, $label)
    {
        $scope.old_device = angular.copy($item); 
        getAssignedRoomList(dept_func_id, $item.id, 1);
        getAssignedRoomList(dept_func_id, $scope.new_device.id, 2);
    }

    $scope.onNewDeviceSelect = function($item, $model, $label)
    {
        $scope.new_device = angular.copy($item); 
        getAssignedRoomList(dept_func_id, $scope.old_device.id, 1);
        getAssignedRoomList(dept_func_id, $item.id, 2);
    }

    function getAssignedRoomList(dept_func_id, device_id, side_flag) {
        var request = {};

        request.dept_func_id = dept_func_id;
        request.device_id = device_id;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getrosters',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {           
                if( side_flag == 1 )   // old device
                    $scope.old_room_list = response.data.datalist.locations;
                else
                    $scope.new_room_list = response.data.datalist.locations;
            
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {                
            });
    }

    $scope.onSelectTotalRoom = function() {
        $scope.old_room_list.forEach(item => {
            item.selected = $scope.total_selected;
        });
    }

    $scope.onMove = function() {
        if( $scope.new_device.id < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select a new Device.');
            return;
        }

        $scope.new_room_list = $scope.new_room_list.concat($scope.old_room_list.filter(item => item.selected));
        $scope.old_room_list = $scope.old_room_list.filter(item => !item.selected);
    }

    $scope.onTransferRoom = function() {
        if( $scope.new_device.id < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select a new Device.');
            return;
        }

        if( $scope.new_device.id == $scope.old_device.id )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select a Different New Device from Old device.');
            return;
        }

        var selected_room_list = $scope.new_room_list.filter(item => item.selected);
        if( selected_room_list.length < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Room.');
            return;
        }

        var profile = AuthService.GetCredentials();
        var request = {};

        request.property_id = profile.property_id;
        request.old_device_id = $scope.old_device.id;
        request.new_device_id = $scope.new_device.id;
        request.dept_func_id = dept_func_id;
        request.selected_room_ids = selected_room_list.map(item => item.id).join(',');

        $http({
            method: 'POST',
            url: '/frontend/guestservice/transferdevice',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {                
                if( response.data.code == 200 )
                {
                    getAssignedRoomList(dept_func_id, $scope.old_device.id, 1);
                    getAssignedRoomList(dept_func_id, $scope.new_device.id, 2);
                    $scope.onChangeDeptFunc();

                    toaster.pop('success', MESSAGE_TITLE, response.data.message);                    
                }
                else
                {
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
                }
                
                
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, response.data.message);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
});