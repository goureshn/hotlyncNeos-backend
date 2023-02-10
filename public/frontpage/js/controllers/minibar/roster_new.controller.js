app.controller('MinibarRosterNewController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, $q, AuthService, toaster) {
    var MESSAGE_TITLE = 'Roster Allocation';
    var current_dispatcher = 0;
    $scope.td_status = "Dropped";
    $scope.td_css = 'btn-primary';

    $scope.models = [
        { listName: "Source", items: [], dragging: false },
        { listName: "Target", items: [], dragging: false }
    ];
    $scope.locations = [
        { id: 0, label: 'By Floor' },
        { id: 1, label: 'By Room' }
    ];
    $scope.filter={};
    $scope.filter_list_ids=[];
    $scope.filter_list = [
        { id: 0, label: 'Vacant' },
        { id: 1, label: 'Occupied' }
    ];
    $scope.filterlist_hint = { buttonDefaultText: 'Select Filters' };
    $scope.filterlist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };
    $scope.multiuserevents = {
        onItemSelect: function (item) {
           
            if(item.id==0)
            {
            $scope.filter.vacant=true;
            }
            else
            $scope.filter.occupied = true;
            $scope.filterList();

        },
        onItemDeselect: function (item) {
            if (item.id == 0)
                $scope.filter.vacant = false;
            else
                $scope.filter.occupied = false;
            $scope.filterList();
        },
        onDeselectAll: function () {
           $scope.filter={};
            $scope.filterList();
        },
        onSelectAll: function () {
            $scope.filter.vacant = true;
            $scope.filter.occupied = true;
            $scope.filterList();
        }};
    $scope.exceptions_list = [];
    $scope.location_choice = 0;
    $scope.credits = 0;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
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
    function getTimeRange() {
        $scope.begin_date_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_date_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
    }

    initTimeRanger();

    function roomList() {
        $scope.searchRooms();
    }
    // $scope.sub_count = {};
    // $scope.sub_count.check_in = 0;
    // $scope.sub_count.check_out = 0;
    // $scope.sub_count.rush_clean = 0;
    // $scope.sub_count.dirty = 0;
    // $scope.sub_count.clean = 0;
    // $scope.sub_count.due_out = 0;
    // $scope.sub_count.arrival = 0;

    // $scope.selected_sub_count = {};
    // $scope.selected_sub_count.total = 0;
    // $scope.selected_sub_count.check_in = 0;
    // $scope.selected_sub_count.due_out = 0;
    // $scope.selected_sub_count.arrival = 0;
    // $scope.selected_sub_count.check_out = 0;
    // $scope.selected_sub_count.duration = 0;

    $scope.room_category = '';


    // pip
    $scope.isLoading = false;

    function initData() {

        //getDeptFuncList()
        refresh();
        getBuildingList();
        getFloorList();
        getDeviceList(0);
    }
    function refresh() {
        $scope.exceptions_list = [];
        $scope.update_flag = 0;
        $scope.selected_count = 0;
        $scope.roster = {};
        $scope.location_choice = 0;
        $scope.devicelist = [];
        $scope.device = {};
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        $scope.models[0].items = [];
        $scope.models[1].items = [];

        $scope.update = "Update";
    }
    initData();

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

        if ($scope.location_choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
        }
        getFloorList();
    }

    $scope.onChangeLocation = function (building_id, dept_func_id) {
        $scope.selected_count = 0;
        $scope.roster.unassigned = 0;
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        if ($scope.location_choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
            getFloorList();
        }
        //$scope.floor_tags = [];

    }

    $scope.floor_tags = [];
    $scope.floors_tags = [];
    $scope.floor_list  = [];
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

    $scope.searchRooms = function () {
        //console.log(row.floor_tags);
       
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func = $scope.dept_func_id;
        request.building_id = $scope.building_id;
        request.floors = [];
      
        for (var i = 0; i < $scope.floor_tags.length; i++) {
            request.floors.push($scope.floor_tags[i].id);
            
        }
        //  request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        request.room_name = $scope.query_name;
        if ($scope.filter) {
            request.filter = $scope.filter;
            
        }

        request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;
        request.device_flag = 1;
        request.exceptions_list = $scope.exceptions_list;
        $scope.models[0].items = [];

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlistunassign',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                        item.credits = 0;
                });
                $scope.models[0].items = response.data.datalist;
                console.log(response.data.datalist);
                // $scope.sub_count = response.data.sub_count;
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

    $scope.filterUnassigned = function () {
        if ($scope.roster.unassigned == 1) {
            if ($scope.location_choice == 0) {
                var arr = $scope.models[0].items.filter(function (item) {

                    if (item.unassigned_count > 0) {
                      
                        return item
                    }
                });
               
                $scope.models[0].items = arr;
            }
            else {
                var arr = $scope.models[0].items.filter(function (item) {

                    if (item.assigned_device_count == 0) {
                      
                        return item
                    }
                });
                
                $scope.models[0].items = arr;
            }
        }
        // angular.forEach($scope.models[0].items, function (item) { item.selected = true; });
        else {
            $scope.onChangeLocation($scope.building_id, $scope.dept_func_id);
        }

        // angular.forEach($scope.models[0].items, function (item) { item.selected = false; });
    }

    $scope.filterList = function () {
      
        if ($scope.location_choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
            //getFloorList();
        }
    }
    

    $scope.unAssignAll = function () {
        $scope.exceptions_list = [];
        $scope.models[1].items = [];
        $scope.onChangeLocation($scope.building_id, $scope.dept_func_id);
        //$scope.update = 'Save';
        // $scope.onUpdate();
    }
    $scope.unAssignAllRosters = function () {
        var request = {};
        request.dept_func = $scope.dept_func_id
        $http({
            method: 'POST',
            url: '/frontend/guestservice/clearallrosters',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.$emit('onCreateRoaster');
                // $scope.models[1].items = response.data.datalist;
                if (response.data.code == 200) {
                    toaster.pop('success', MESSAGE_TITLE, response.data.message);
                    getDeviceList($scope.device);
                }
                else if (response.data.code == 500) {
                    toaster.pop('error', MESSAGE_TITLE + ' Error', response.data.message);
                    getDeviceList($scope.device);
                    // getDeviceList($scope.device);
                }
                
            }).catch(function (response) {
            })
            .finally(function () {
            });
        // $scope.exceptions_list = [];
        // $scope.models[1].items = [];
        // $scope.onChangeLocation($scope.building_id, $scope.dept_func_id);
        // $scope.update = 'Save';
        // $scope.onUpdate();
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
        request.room_name = $scope.query_name;
        request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;
        request.device_flag = 1;
        request.dept_func = $scope.dept_func_id;
        request.exceptions_list = $scope.exceptions_list;
        //$scope.models[0].items = [];
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                       item.credits = 0;
                });
              
                $scope.floor_rooms = response.data.datalist;
                if(td_flag==1)
                {
                $scope.floor_rooms.forEach((element)=>{
                    element.room_status='Turndown';
                })
                }
                // if ($scope.choice == 0) {
                //     var arr = $scope.models[0].items.filter(function (item) {

                //         if (item.unassigned_count > 0) {
                //             
                //             return item
                //         }
                //     });   
                // }

                if ($scope.roster.unassigned == 1) {
                    //  angular.forEach(items, function (item) { 
                    //      if(item.)
                    //   });
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
                //     });

                //     $scope.models[0].items = arr;
                // }

                $scope.floor_rooms.filter(function (item) {

                    $scope.exceptions_list.push(item.id);
                });
               
                angular.forEach(items, function (item) { item.selected = false; });
        
                list.items = list.items.slice(0, index)
                    .concat($scope.floor_rooms)
                    .concat(list.items.slice(index));


                // $scope.sub_count = response.data.sub_count;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
    $scope.searchFloors = function () {
        //console.log(row.floor_tags);
      
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func = $scope.dept_func_id;
        request.building_id = $scope.building_id;

        if ($scope.filter) {
            request.filter = $scope.filter;
          
        }
        request.floor_name = $scope.floor_query_name;
        // request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;
        request.device_flag = 1;
        $scope.models[0].items = [];
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getfloorlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.models[0].items = response.data.filter(function (item) {
                    if (item.room_count > 0)
                        return item;
                });
                // $scope.sub_count = response.data.sub_count;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
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

    $scope.searchDevice = function () {

        getDeviceList(0);
    }

    function getDeviceList(device) {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.name = $scope.device_name;
        request.case_type = "Minibar";

            $http({
                method: 'POST',
                url: '/frontend/guestservice/devicelist',
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
                    // if ($scope.attendantlist.length > 0)
                    //     $scope.onSelectDevice($scope.attendantlist[0]);

                    $scope.dept_func_id = response.data.minibar_dept_func_id;

                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function () {
                    $scope.isLoading = false;
                });

    }

    $scope.getRosterData = function () {
      
    }
    $scope.getRosters = function (dept_func_id, device_id) {

        var request = {};

        request.filter = $scope.filter;
        request.dept_func_id = $scope.dept_func_id;
        request.device_id = device_id;

        //request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        //request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        //$scope.roster={};
        //$scope.datalist = [];

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getrosters_minibar',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                // $scope.roster_list = response.data.datalist;
                // $scope.roster_count = response.data.totalcount;
                //  $scope.models[0].items = response.data.datalist;

                console.log( response.data.datalist);

                if (response.data.datalist != null) {

                    angular.forEach(response.data.datalist.locations, function (item) {
                        if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                            item.credits = 0;
                    });

                    $scope.roster = response.data.datalist;

                    if ($scope.roster.locations) {
                      
                        $scope.update_flag = 1;

                        if (!$scope.roster.locations[0])
                            {
                                $scope.update = 'Assign';
                            //$scope.assign_disable = ($scope.models[1].items.length<=0)?1:0;
                            }

                        for (var i = 0; i < $scope.roster.locations.length; i++) {
                            $scope.models[1].items[i] = $scope.roster.locations[i];
                            $scope.exceptions_list.push($scope.roster.locations[i].id);
                        }
                    }
                }


                //$scope.getRosterData();
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


    $scope.onSelectDevice = function (row) {

        $scope.device = row;
        console.log(row);
        for (var i = 0; i < $scope.devicelist.length; i++) {
            var item = $scope.devicelist[i];
            item.selected = row.id == item.id;
        }

        $scope.roster = {};
        $scope.roster.device = row.id;
        $scope.roster.name = row.name + ' Roster For Minibar';
        $scope.update_flag = 0;
        $scope.models[1].items = [];
        $scope.exceptions_list = [];
        $scope.location_choice = 0;
        $scope.update = 'Update';
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        $scope.casual_staff = { 'new_staff_name': null, 'id': null };


        if ($scope.location_choice == 0)
            $scope.searchFloors();
        else {
            $scope.searchRooms();
           
        }
        $scope.getRosters($scope.dept_func_id, row.id);

    }

    // $scope.tdEnable = function(){
    //    // window.alert("here");
    //     var temp = $scope.models[0].items.filter(function (item)  { if(item.selected == true) return item.id; });
    //    //window.alert(JSON.stringify(temp)); 
    //    if(!$scope.td_enable)
    //     $scope.td_enable=1;
    //     else if(temp.length>0)
    //     $scope.td_enable=0;
    // }
    // $scope.$watch('td_status', function (newValue, oldValue) {
    //     if (newValue == oldValue)
    //         return;

       
    //    if(newValue=='Clicked')
    //    {
    //        $scope.models[0].items.forEach(element => {
               
    //            if (element.selected == true) 
    //             {

    //             }
    //          });
    //    }
    //    else{

    //    }
    //    // $scope.complaint.reminder_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    // });
    $scope.selected_count=0;
    $scope.selection=function (item) {
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
             else {
                 $scope.td_css = 'btn-primary';
                 $scope.td_status = "Dropped";
             }
            }   
        else
            {
             $scope.td_css = 'btn-primary'; 
             $scope.td_status = "Dropped";
         }
         //angular.forEach($scope.models[0].items, function (item) { if (item.selected == true) window.alert(JSON.stringify(item)) });
    //    // console.log("Onassign");
    //     if ($scope.roster.device < 1) {
    //         toaster.pop('info', MESSAGE_TITLE, 'Please select Attendant');
    //         return;
    //     }
    //     if ($scope.roster.name == '') {
    //         toaster.pop('info', MESSAGE_TITLE, 'Please enter Roster Name');
    //         return;
    //     }


    //     var profile = AuthService.GetCredentials();
    //     var request = {};

    //     request.property_id = profile.property_id;
    //     request.dispatcher = $scope.roster.device;
    //     request.updated_by = profile.id;

    //     request.td_list = [];
    //     request.roster_name = $scope.roster.name;
    //     request.total_credits = $scope.credits;
    //     request.casual_staff = $scope.casual_staff;
    //     // find first rush flag with not 
    //     var count = 0;
    //     for (var i = 0; i < $scope.models[1].items.length; i++) {
    //         var row = $scope.models[1].items[i];
    //         request.td_list[i] = row.id;
    //     }
    //     request.id = $scope.roster.id;

    //     //   request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
    //     //   request.end_date_time = moment($scope.end_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');

    //     // request.roster_name=$scope.roster_name;
    //    // console.log(request);
    //     $http({
    //         method: 'POST',
    //         url: '/frontend/guestservice/turndownroster',
    //         data: request,
    //         headers: { 'Content-Type': 'application/json; charset=utf-8' }
    //     })
    //         .then(function (response) {
    //             $scope.$emit('onCreateRoaster');
    //             // $scope.models[1].items = response.data.datalist;
    //             getDeviceList($scope.device);

    //             // $scope.onChangeLocation();
    //             // $scope.searchRooms();
    //             toaster.pop('success', MESSAGE_TITLE, response.data.message);
    //             console.log(response);
    //         }).catch(function (response) {
    //             console.error('Gists error', response.status, response.data);
    //             toaster.pop('error', MESSAGE_TITLE, response.data.message);
    //         })
    //         .finally(function () {
    //             $scope.isLoading = false;
    //         });

     }

    $scope.onAssign = function () {
        console.log("Onassign");
        if ($scope.roster.device < 1) {
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
        request.device = $scope.roster.device;
        request.dispatcher = profile.id;
        request.updated_by = profile.id;

        request.assigned_list = [];
        request.td_list = [];
        request.roster_name = $scope.roster.name;
        request.total_credits = $scope.credits;
        // find first rush flag with not
        var j = 0;
        for (var i = 0; i < $scope.models[1].items.length; i++) {
            var row = $scope.models[1].items[i];
            request.assigned_list[i] = row.id;
            if (row.room_status == 'Turndown') {  // window.alert(JSON.stringify(row))
                request.td_list[j++] = row.id;
            }
        }

        //   request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        //   request.end_date_time = moment($scope.end_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        
        // request.roster_name=$scope.roster_name;
        console.log(request);
        $http({
            method: 'POST',
            url: '/frontend/guestservice/createrosterdevice_minibar',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.$emit('onCreateRoaster');
                // $scope.models[1].items = response.data.datalist;
                getDeviceList($scope.device);

                // $scope.onChangeLocation();
                // $scope.searchRooms();
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
    $scope.onUpdate = function () {
        console.log("Onupdate");
        if ($scope.roster.device < 1) {
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
        request.device = $scope.roster.device;
        request.dispatcher = profile.id;
        request.assigned_list = [];
        request.td_list = [];

        // find first rush flag with not 
        var j = 0;
        for (var i = 0; i < $scope.models[1].items.length; i++) {
            var row = $scope.models[1].items[i];
            request.assigned_list[i] = row.id;
            if(row.room_status=='Turndown')
            {  // window.alert(JSON.stringify(row))
                request.td_list[j++] = row.id;}
        }

        // request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        // request.end_date_time = moment($scope.end_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        
        request.roster_name = $scope.roster.name;
        request.total_credits = $scope.credits;
        request.id = $scope.roster.id;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/updaterosterdevice_minibar',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.$emit('onCreateRoaster');
                // $scope.models[1].items = response.data.datalist;
                if (response.data.code==200)
                {
                toaster.pop('success', MESSAGE_TITLE, response.data.message);
                getDeviceList($scope.device);
                }
                else if(response.data.code == 500)
                {
                    toaster.pop('error', MESSAGE_TITLE+' Error', response.data.message);
                    getDeviceList($scope.device);
                   // getDeviceList($scope.device);
                }
                //$scope.onChangeLocation();
                // updateCurrentAssignedValues();
                // $scope.searchRooms();
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

        // $scope.selected_sub_count.total = $scope.models[1].items.length;
        // $scope.selected_sub_count.check_in = 0;
        // $scope.selected_sub_count.due_out = 0;
        // $scope.selected_sub_count.arrival = 0;
        // $scope.selected_sub_count.check_out = 0;
        // $scope.selected_sub_count.duration = 0;

        $scope.roster_name = '';


        // for (var i = 0; i < $scope.models[1].items.length; i++) {
        //     var row = $scope.models[1].items[i];
        //     if (row.occupancy == 'Occupied')
        //         $scope.selected_sub_count.check_in++;
        //     else
        //         $scope.selected_sub_count.check_out++;

        //     if (row.arrival == 1)
        //         $scope.selected_sub_count.arrival++;

        //     if (row.due_out == 1)
        //         $scope.selected_sub_count.due_out++;

        //     $scope.selected_sub_count.duration += row.max_time;
        //     // if (hskp_setting_value.pax_allowance == 1)
        //     //     $scope.selected_sub_count.duration += parseInt(hskp_setting_value.adult_pax_allowance);
        // }
    }

    /**
     * dnd-dragging determines what data gets serialized and send to the receiver
     * of the drop. While we usually just send a single object, we send the array
     * of all selected items here.
     */
    $scope.getSelectedItemsIncluding = function (list, item) {
        


        if (canBeReassigned(item))
            item.selected = true;
        else
            {
                item.selected = false;
                toaster.pop('error', MESSAGE_TITLE + ' Error', 'Room ' + item.room + ' is currently being cleaned.');
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
        if ($scope.location_choice != choice) {
            $scope.location_choice = choice;
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
        if ($scope.location_choice == 0 && (list.listName == "Target"))
        {
            angular.forEach(items, function (item) {
                if (item.selected == true) {
                    

                    $scope.floors_tags.push(item);

                   
                }

            });
            $scope.findRoomsforFloor(list, items, index,td_flag);
        }


        else {
     


            //  if ($scope.uniqueRooms()) {

            angular.forEach(items, function (item) {
                // if ($scope.exceptions_list.includes(item.id))
               
                if (list.listName == "Source" && $scope.models[1].dragging == true) {
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

        //updateCurrentAssignedValues();

        return true;
    }

    /**
     * Last but not least, we have to remove the previously dragged items in the
     * dnd-moved callback.
     */
    $scope.onMoved = function (list) {
        // console.log(JSON.stringify(list));
        list.items = list.items.filter(function (item) { return !item.selected; });
    //   if(list.listName=='Target' && (list.items.length ==1) && list.dragging==true )
    //     $scope.update='Save';
        //  if ($scope.models[1].items.length>=0)
        //  {

        //      $scope.credits = 0;
        //      $scope.models[1].items.forEach(element => {
        //          $scope.credits = $scope.credits+element.credits;
        //      });
        //    
        //  }
        //  updateCurrentAssignedValues();
    };
});
