app.controller('MinibarPostController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, $q, AuthService, toaster) {
    var MESSAGE_TITLE = 'Minibar Post';
    var current_dispatcher = 0;
    $scope.td_status = "Dropped";
    $scope.td_css = 'btn-primary';

    $scope.assign_rooms = [];
    $scope.currency = "";

    $scope.filter = {};
    $scope.occupy_filter_list_ids = [];
    $scope.occupy_filter_list = [
        {id: 0, label: 'Occupied'},
        {id: 1, label: 'Vacant'}
    ];
    $scope.postingstatus_filter_list_ids = [];
    $scope.postingstatus_filter_list = [

        {id: 1, label: 'Pending'},
        {id: 2, label: 'No Post'},
        {id: 3, label: 'DND'},
        {id: 4, label: 'Posted'},
        {id: 5, label: 'Clear'}
    ];
    $scope.filterlist_hint = {buttonDefaultText: 'Select Filters'};
    $scope.filterlist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };
    $scope.occupy_multiuserevents = {
        onItemSelect: function (item) {

            if (item.id == 1) {
                $scope.filter.vacant = true;
            }
            else
                $scope.filter.occupied = true;
            $scope.filterList();

        },
        onItemDeselect: function (item) {
            if (item.id == 1)
                $scope.filter.vacant = false;
            else
                $scope.filter.occupied = false;
            $scope.filterList();
        },
        onDeselectAll: function () {
            $scope.filter = {};
            $scope.filterList();
        },
        onSelectAll: function () {
            $scope.filter.vacant = true;
            $scope.filter.occupied = true;
            $scope.filterList();
        }
    };

    $scope.postingstatus_multiuserevents = {
        onItemSelect: function () {

            $scope.filterList();

        },
        onItemDeselect: function () {

            $scope.filterList();
        },
        onDeselectAll: function () {

            $scope.filterList();
        },
        onSelectAll: function () {

            $scope.filterList();
        }
    };
    $scope.exceptions_list = [];
    $scope.casual_staff_list = [];
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

    $scope.room_category = '';


    // var hskp_setting_value = {};
    // hskp_setting_value.pax_allowance = 0;
    // hskp_setting_value.adult_pax_allowance = 5;
    // hskp_setting_value.child_pax_allowance = 5;

    // pip
    $scope.isLoading = false;

    function initData() {

        //getDeptFuncList()
        refresh();
        //getBuildingList();
        // getDeviceList(0);
        getCurrentDevice();
        getServiceList();
        getServiceCategoryList();
        getCurrency();
    }

    function refresh() {

        $scope.assign_rooms = [];
        $scope.update_flag = 0;
        $scope.selected_count = 0;
        $scope.location_choice = 0;
        $scope.devicelist = [];
        $scope.device = {};
        $scope.select_all = 0;
        $scope.select_all_text = "Select All";
        $scope.roomservice_list = [];
    }

    initData();


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
    $scope.floor_list = [];

    function getServiceList(category_id) {
        var profile = AuthService.GetCredentials();
        var request = {};
        request.property_id = profile.property_id;
        if (category_id)
            request.category_id = category_id;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomservicelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.roomservice_list = response.data.datalist;
                console.log(response);
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.servicecategory_list = [];

    function getServiceCategoryList() {
        var profile = AuthService.GetCredentials();
        var request = {};
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomservicecategorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.servicecategory_list = response.data.datalist;
                var all_cat = {};
                all_cat.id = 0;
                all_cat.name = 'All Categorys';
                $scope.servicecategory_list.unshift(all_cat);
                console.log(response);
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    //$scope.service.category_id = 0;
    $scope.changedServiceCategory = function () {
        console.log($scope.service.category_id);
        getServiceList($scope.service.category_id);
    }

    $scope.service = {};
    $scope.onServiceSelect = function (row) {
        for (var i = 0; i < $scope.minibarpost_list.length; i++) {
            if ($scope.minibarpost_list[i].id == row.id) {
                $scope.service = {};
                return;
            }

        }
        $scope.service = row;
    }

    $scope.addService = function () {
        $scope.service.quantity = 1;
        $scope.minibarpost_list.push($scope.service);
        $scope.service = {};
    }
    $scope.clearServicePost = function () {
        $scope.minibarpost_list = [];
    }

    $scope.removePostItem = function (item) {
        if ($scope.minibarpost_list.indexOf(item) != -1)
            $scope.minibarpost_list.splice($scope.minibarpost_list.indexOf(item), 1);
    }

    $scope.countTotalItems = function () {
        var countitem = 0;
        for (var i = 0; i < $scope.minibarpost_list.length; i++) {
            countitem += $scope.minibarpost_list[i].quantity * 1;
        }
        return countitem * 1;

    }

    $scope.aedTotal = function () {

        var totalcharge = 0;
        for (var i = 0; i < $scope.minibarpost_list.length; i++) {
            totalcharge += $scope.minibarpost_list[i].charge * $scope.minibarpost_list[i].quantity;
        }
        return totalcharge * 1;
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
        if ($scope.filter_list) {
            request.filter = $scope.filter;

        }

        request.room_category = $scope.room_category;
        request.dispatcher = current_dispatcher;
        request.device_flag = 1;
        request.exceptions_list = $scope.exceptions_list;
        $scope.models[0].items = [];

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                        item.credits = 0;
                });
                $scope.models[0].items = response.data.datalist;
                // $scope.sub_count = response.data.sub_count;
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
    $scope.isLoading = false;

    $scope.searchRoomsForDevice = function (last_id, new_flag) {

        if(new_flag)
        {
            $scope.device.last_id  = 0;
        }

        if($scope.device.last_id < 0)
            return;
        console.log("searchRoomsForDevice!");
        console.log(last_id);

        var row = $scope.device;
        var profile = AuthService.GetCredentials();
        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func = $scope.dept_func_id;
        request.last_id = last_id;
        if (row.location_list)
            request.room_id_list = JSON.parse(row.location_list);

        request.room_name = $scope.query_name;
        if ($scope.filter) {
            request.filter = $scope.filter;
        }
        var status_ids = [];
        for (var i = 0; i < $scope.postingstatus_filter_list_ids.length; i++) {
            status_ids.push($scope.postingstatus_filter_list_ids[i].id);
        }
        request.status_ids = status_ids;
        //$scope.device.isLoading = true;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlistassign',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                /*angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                        item.credits = 0;
                });*/

                console.log(response.data.datalist);

                $scope.device.last_id = response.data.last_id;
                var list = response.data.datalist;

                if( new_flag )
                    $scope.assign_rooms = list;
                else
                {
                    if( !$scope.assign_rooms )
                        $scope.assign_rooms = [];
                    $scope.assign_rooms = $scope.assign_rooms.concat(list);
                }

                console.log($scope.assign_rooms);
                $scope.device.isLoading = false;
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.device.isLoading = false;
            });
    };

    $scope.getLastPostName = function(insVal) {
        let tempArr = insVal.split(' ');
        let arr1 = tempArr[0].split('-');
        let arr2 = tempArr[1].split(':');

        let dd = arr1[2];
        let mn = arr1[1];
        let yy = arr1[0];

        let hh = arr2[0];
        let minute = arr2[1];

        return dd + "-" + mn + "-" + yy + " " + hh + ":" + minute;
    };

    $scope.floor_rooms = [];

    $scope.checkAll = function (params) {

        if ($scope.select_all == 1) {

            angular.forEach($scope.models[0].items, function (item) {
                item.selected = false;
            });
            $scope.select_all_text = "Select All";
            $scope.select_all = 0;

        }
        else {

            angular.forEach($scope.models[0].items, function (item) {
                item.selected = true;
            });
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
        $scope.searchRoomsForDevice(0,true);
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
            headers: {'Content-Type': 'application/json; charset=utf-8'}
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
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                angular.forEach(response.data.datalist, function (item) {
                    if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                        item.credits = 0;
                });

                $scope.floor_rooms = response.data.datalist;
                if (td_flag == 1) {
                    $scope.floor_rooms.forEach((element) => {
                        element.room_status = 'Turndown';
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

                angular.forEach(items, function (item) {
                    item.selected = false;
                });

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
            headers: {'Content-Type': 'application/json; charset=utf-8'}
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

    $scope.searchDevice = function () {
        getDeviceList(0);
    }
    $scope.onSelectCurrentDevice = function () {
        $scope.searchRoomsForDevice(0,true);
        $scope.selected_room = {};
    }

    function getCurrency() {
        var request = {};
        $http({
            method: 'GET',
            url: '/getcurrency',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if (response.data) {
                    $scope.currency = response.data.currency;
                }
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        });
    }

    function getCurrentDevice()
    {
        var profile = AuthService.GetCredentials();

            var request = {};
            request.property_id = profile.property_id;
            request.device_id = '0';
            if(profile.device_id)
                request.device_id = profile.device_id;

            request.case_type = "Minibar";
            $scope.device.isLoading = true;
            $http({
                method: 'POST',
                url: '/frontend/guestservice/devicelist',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    if(response.data.device_list.length > 0)
                        $scope.device = response.data.device_list[0];

                    console.log("Device List!");
                    $scope.onSelectCurrentDevice();
                    $scope.dept_func_id = response.data.minibar_dept_func_id;

                }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
                .finally(function () {

                });

    }

    function getDeviceList(device) {
        var profile = AuthService.GetCredentials();
        var request = {};
        request.property_id = profile.property_id;
        //request.name = $scope.device_name;
        request.case_type = "Minibar";

            $http({
                method: 'POST',
                url: '/frontend/guestservice/devicelist',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.devicelist = response.data.device_list;
                    for (var i = 0 ; i < $scope.devicelist.length ; i++)
                    {
                        if(profile.device_id == $scope.devicelist[i].device_id)
                        {
                            $scope.device = $scope.devicelist[i];
                            break;
                        }
                    }

                    if (device != 0)
                        $scope.onSelectDevice(device);
                    else if ($scope.devicelist.length > 0)
                        $scope.onSelectDevice($scope.devicelist[0]);/**/

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
            url: '/frontend/guestservice/getrosters',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                // $scope.roster_list = response.data.datalist;
                // $scope.roster_count = response.data.totalcount;
                //  $scope.models[0].items = response.data.datalist;
                if (response.data.datalist != null) {

                    angular.forEach(response.data.datalist.locations, function (item) {
                        if (item.checkout_flag == "checkout" && (item.status == "Vacant Clean" || item.status == "Vacant Inspected"))
                            item.credits = 0;
                    });

                    $scope.roster = response.data.datalist;
                    if ($scope.roster.casual_staff_name != null)
                        $scope.casual_staff = { 'new_staff_name': $scope.roster.casual_staff_name, 'id': $scope.roster.generic_id };

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
        $scope.searchRoomsForDevice(0,true);
        $scope.selected_room = {};
        $scope.getRosters($scope.dept_func_id, row.id);

    }

    $scope.selected_count=0;
    $scope.selected_room = {};

    $scope.selection=function (row) {
        for (var i = 0; i < $scope.assign_rooms.length; i++) {
            var item = $scope.assign_rooms[i];
            item.selected = row.id == item.id;
        }
        $scope.selected_room = row;
        //$scope.getRoomPost();
    }

    $scope.minibarpost_list = [];
    $scope.postBtnName = "Post";
    $scope.minibarpost_list_ids = [];
    $scope.getRoomPost = function()
    {

        var request = {};
        request.property_id = profile.property_id;
        request.room_id = $scope.selected_room.id;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getminibarroomposts',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
         })
             .then(function (response) {


                 var records  = response.data.datalist;
                 for(var i = 0 ; i < records.length ; i++)
                 {
                     $scope.minibarpost_list_ids.push(records[i].id);
                 }

                 $scope.minibarpost_list = response.data.item_list_total;

                 if( $scope.minibarpost_list.length > 0 ){

                     $scope.postBtnName = "Save";
                 }else{

                     $scope.postBtnName = "Post";
                 }
                  //toaster.pop('success', MESSAGE_TITLE, response.data.message);
                  console.log(response);
             }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, response.data.message);
              })
              .finally(function () {
                  $scope.isLoading = false;
              });

        }

    $scope.newServicePost = function()
    {

        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.posted_by = profile.id;
        request.room_id = $scope.selected_room.id;

        request.user_id = $scope.device.user_id;

        var item_ids_arr = [];
        var quantity_arr = [];
        var total_amount = 0;
        for(var i = 0 ; i < $scope.minibarpost_list.length ; i++)
        {
            item_ids_arr.push($scope.minibarpost_list[i].id);
            quantity_arr.push($scope.minibarpost_list[i].quantity * 1);
            total_amount += $scope.minibarpost_list[i].charge * $scope.minibarpost_list[i].quantity;
        }

        request.item_ids = JSON.stringify(item_ids_arr);
        request.quantity = JSON.stringify(quantity_arr);
        request.total_amount = total_amount;
        if($scope.postBtnName == "Save")
            request.minibarpost_list_ids = $scope.minibarpost_list_ids;

        $http({
            method: 'POST',
            url: '/frontend/minibar/postminibaritem',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
               toaster.pop('success', MESSAGE_TITLE, "Minibar service posted successfully.");
               $scope.minibarpost_list = [];
                $scope.minibarpost_list_ids = [];
                $scope.searchRoomsForDevice(0 , true);
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
            toaster.pop('error', MESSAGE_TITLE, response.data.message);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.changePostingStatus = function(item)
    {
        var old_status_name = item.posting_status_name;
        console.log(old_status_name);
        var status_id = item.posting_status_id;
        if(status_id == 2)  // "No Post"
        {
            for(var i = 0 ; i < $scope.postingstatus_filter_list.length ; i++)
            {
                if($scope.postingstatus_filter_list[i].label == old_status_name)
                {

                    if(old_status_name)
                    {
                        item.posting_status_id = $scope.postingstatus_filter_list[i].id;
                        item.posting_status_name = old_status_name;
                        return;
                    }

                }
            }

            item.posting_status_id = null;
            item.posting_status_name = null;
            return;
        }

        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.posted_by = profile.id;
        request.room_id = $scope.selected_room.id;

        request.item_id = item.post_id;
        request.posting_status_id = status_id;

        $http({
            method: 'POST',
            url: '/frontend/minibar/postminibaritemstatuschange',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {

                // if($scope.postingstatus_filter_list;
                for(var i = 0 ; i < $scope.postingstatus_filter_list.length ; i++)
                {
                    if($scope.postingstatus_filter_list[i].id == status_id)
                    {
                        item.posting_status_name = $scope.postingstatus_filter_list[i].label;
                    }
                }
                toaster.pop('success', MESSAGE_TITLE, "Minibar post status updated successfully.");

            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
            toaster.pop('error', MESSAGE_TITLE, response.data.message);
        })
            .finally(function () {
            });

    }

    $scope.loadMore = function() {
        console.log("Load More");
        $scope.searchRoomsForDevice( $scope.device.last_id , false);
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
        request.dispatcher = $scope.roster.device;
        request.updated_by = profile.id;

        request.assigned_list = [];
        request.td_list = [];
        request.roster_name = $scope.roster.name;
        request.total_credits = $scope.credits;
        request.casual_staff = $scope.casual_staff;
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
            url: '/frontend/guestservice/createrosterdevice',
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
        request.dispatcher = $scope.roster.device;
        request.assigned_list = [];
        request.td_list = [];
        request.casual_staff = $scope.casual_staff;

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
            url: '/frontend/guestservice/updaterosterdevice',
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
