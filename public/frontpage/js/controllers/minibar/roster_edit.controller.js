app.controller('RosterEditController', function ($scope, $rootScope, $http,$httpParamSerializer,  $window, $uibModal, $timeout, $q, AuthService, toaster) {
    var MESSAGE_TITLE = 'Roster Edit';

    var current_dispatcher = 0;
   
//$scope.roster={};

    $scope.models = [
        { listName: "Source", items: [], dragging: false },
        { listName: "Target", items: [], dragging: false }
    ];
    function initTimeRanger() {
        var start_time = ($scope.roster) ? moment($scope.roster.begin_date_time, 'YYYY-MM-DD HH:mm').format('DD-MM-YYYY HH:mm') : moment().format('DD-MM-YYYY 00:00');
        var end_time = ($scope.roster) ? moment($scope.roster.end_date_time, 'YYYY-MM-DD HH:mm').format('DD-MM-YYYY HH:mm') : moment().format('DD-MM-YYYY 00:00');

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
    

    function getTimeRange() {
        $scope.begin_date_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_date_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
    }

    initTimeRanger();

    function roomList() {
      
        $scope.searchRooms();
    }

   // $scope.pageChanged();

   
    $scope.loadFloorFilters = function (query) {
        return $scope.floor_list.filter(function (type) {
            return type.floor_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
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
    $scope.onChangeBuilding = function () {
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
    $scope.searchDevice = function () {

        getDeviceList();
    }

    function getDeviceList() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;
        request.dept_func = $scope.roster.dept_func_id;
        request.name = $scope.roster.device_name;
       // window.alert($scope.roster.dept_func_id);
        if ($scope.roster.dept_func_id > 0) {
            $http({
                method: 'POST',
                url: '/frontend/guestservice/devicelist',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.devicelist = response.data.device_list;

                    console.log(response);
                    // window.alert($scope.devicelist.length);
                    if ($scope.devicelist.length > 0)
                    {
                    if ($scope.devicelist[0].id==$scope.roster.device) 
                        $scope.onSelectDevice($scope.devicelist[0]);
                    else
                        $scope.onSelectOtherDevice($scope.devicelist[0]);
                    }
                    // if ($scope.attendantlist.length > 0)
                    //     $scope.onSelectDevice($scope.attendantlist[0]);

                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function () {
                    $scope.isLoading = false;
                   
                });
        }
    }
    $scope.onSelectOtherDevice = function (row) {
        // var profile = AuthService.GetCredentials();
        // var request = {};
        // request.device = row.id;
        // request.property_id = profile.property_id;

        for (var i = 0; i < $scope.devicelist.length; i++) {
            var item = $scope.devicelist[i];
            item.selected = row.id == item.id;
        }
        $scope.roster.device_id = row.device_id;
        $scope.roster.device = row.id;
    }
    $scope.onSelectDevice = function (row) {
        var profile = AuthService.GetCredentials();
        var request = {};
        request.device = row.id;
        request.property_id = profile.property_id;

        for (var i = 0; i < $scope.devicelist.length; i++) {
            var item = $scope.devicelist[i];
            item.selected = row.id == item.id;
        }

       // $scope.models[1].items = [];
        $http({
            method: 'POST',
            url: '/frontend/guestservice/assignedroomlistdevices',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
              //  $scope.models[1].items = response.data.datalist;

                //  hskp_setting_value = response.data.hskp_setting_value;

                $scope.roster.device = row.id;
                $scope.roster.device_id = row.device_id;
               // updateCurrentAssignedValues();
                for (var i = 0; i < $scope.roster.locations.length; i++) {
                    $scope.models[1].items[i] = $scope.roster.locations[i];
                }

                $scope.searchRooms();
                
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });

    }
    //getDeviceList();
    $scope.init = function (roster) {
        var profile = AuthService.GetCredentials();



        $scope.roster = roster;
        // current_dispatcher=$scope.roster.device;
        // window.alert($scope.roster.device_name);
        // $scope.roster_name=roster.name;


        initTimeRanger();
        getBuildingList();
        getFloorList();
        // getDeptFuncList();
        getDeviceList();



    }
    //getDeviceList();
    $scope.onUpdate = function () {
        if (this.roster.device < 1) {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Attendant');
            return;
        }

        var profile = AuthService.GetCredentials();
        var request = {};

        request.property_id = profile.property_id;
        request.dispatcher = this.roster.device;
        request.assigned_list = [];

        // find first rush flag with not 
        var count = 0;
        for (var i = 0; i < $scope.models[1].items.length; i++) {
            var row = $scope.models[1].items[i];
            request.assigned_list[i] = row.id;
        }

        request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        request.end_date_time = moment($scope.end_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        // window.alert($scope.roster_name);
        request.roster_name = $scope.roster.name;
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
                toaster.pop('success', MESSAGE_TITLE, response.data.message);
                $scope.onSelectTicket($scope.roster);
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
    $scope.searchRooms = function () {
        //console.log(row.floor_tags);
        // window.alert("here");
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        request.building_id = $scope.building_id;
        request.floors = [];
       
        for (var i = 0; i < $scope.floor_tags.length; i++)
            request.floors.push($scope.floor_tags[i].id);
        request.begin_date_time = moment($scope.begin_date_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        request.room_name = $scope.query_name;
        request.room_category = $scope.room_category;
        request.dispatcher = $scope.roster.device;
        request.device_flag = 1;
        $scope.models[0].items = [];
        $http({
            method: 'POST',
            url: '/frontend/guestservice/getroomlist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.models[0].items = response.data.datalist;
                // $scope.sub_count = response.data.sub_count;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.getSelectedItemsIncluding = function (list, item) {
        if (canBeReassigned(item))
            item.selected = true;
        else
            item.selected = false;

        return list.items.filter(function (item) { return item.selected; });
    };

    /**
     * We set the list into dragging state, meaning the items that are being
     * dragged are hidden. We also use the HTML5 API directly to set a custom
     * image, since otherwise only the one item that the user actually dragged
     * would be shown as drag image.
     */
    $scope.onDragstart = function (list, event) {
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
    $scope.onDrop = function (list, items, index) {
        angular.forEach(items, function (item) { item.selected = false; });
        list.items = list.items.slice(0, index)
            .concat(items)
            .concat(list.items.slice(index));

        //updateCurrentAssignedValues();

        return true;
    }

    /**
     * Last but not least, we have to remove the previously dragged items in the
     * dnd-moved callback.
     */
    $scope.onMoved = function (list) {
        list.items = list.items.filter(function (item) { return !item.selected; });
        //  updateCurrentAssignedValues();
    };


});
