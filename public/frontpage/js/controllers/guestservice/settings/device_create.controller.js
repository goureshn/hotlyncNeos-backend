app.controller('DeviceSettingGreateController', function ($scope, $http, AuthService, toaster, $uibModalInstance, $uibModal) {

    $scope.isLoading = false;

    $scope.model_data = {
        name: "",
        number: "",
        type: "",
        device_id: "",
        priority_flag: false,
        enabled: true
    };

    $scope.grouptypelist = [];
    $scope.groupclientlist = [];
    $scope.selected_grouptype_id = "";
    $scope.selected_group_type = "";

    $scope.phoneTypes = [];

    $scope.copy_device_name = "";
    $scope.device_profile_prelist = [];

    $scope.dept_func_list = [];

    $scope.dept_func_array_id = [];
    $scope.sec_dept_func = [];
    $scope.deptfunc_hint = { buttonDefaultText: 'Department Function' };

    $scope.loc_grp_array_id = [];
    $scope.sec_loc_grp_array_id = [];
    $scope.location_group_hint = { buttonDefaultText: 'Location Group' };

    $scope.building_ids = [];
    $scope.building_hint = { buttonDefaultText: 'Building' };

    $scope.locaton_group_list = [];
    $scope.building_list = [];

    // limit days
    $scope.limit_days = [
        {id: 'Sun', label: 'Sun'},
        {id: 'Mon', label: 'Mon'},
        {id: 'Tue', label: 'Tue'},
        {id: 'Wed', label: 'Wed'},
        {id: 'Thu', label: 'Thu'},
        {id: 'Fri', label: 'Fri'},
        {id: 'Sat', label: 'Sat'}
    ];


    $scope.days_hint = { buttonDefaultText: 'Limit Days' };

    $scope.sec_limit_days = [
        {
            $$hashKey: "object:3139",
            id: "Mon",
            label: "Mon"}
    ];

    function str2array(str) {
        var ids = [];
        if( str )
        {
            let val = str.split(',');
            val.forEach(element => {
                var val = { id: parseInt(element) };
                ids.push(val);
            });
        }

        return ids;
    }

    function array2str(ids)
    {
        let temp = "";
        ids.forEach((element, index) => {
            if(index > 0)
                temp += ",";

            temp += element.id;
        });

        return temp;
    }

    let profile = AuthService.GetCredentials();

    function getBuildingList() {
        let user_id = profile.id;

        $http.get('/frontend/guestservice/getsettingbuildings?user_id=' + user_id)
            .then(function (response) {
                response.data.forEach(element => {
                    var item = { id: element.id, label: element.name };
                    $scope.building_list.push(item);
                });
            });
    }

    function getDeviceList() {
        let user_id = profile.id;

        $http.get('/frontend/guestservice/getsettingdevices?user_id=' + user_id)
            .then(function (response) {
                $scope.devices = response.data.map(item => {
                    return item.device_id;
                });
            });
    }

    function getLocationGroups() {
        let user_id = profile.id;

        $http.get('/frontend/guestservice/getsettinglocationgroups?user_id=' + user_id)
            .then(function (response) {
                $scope.loc_grps = response.data;

                for (var i = 0; i < $scope.loc_grps.length; i++) {
                    var item = { id: $scope.loc_grps[i].id, label: $scope.loc_grps[i].name };
                    $scope.locaton_group_list.push(item);
                }
            });

    }
    function getPhoneTypeList() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettingphonetypelist?user_id=' + user_id)
            .then(function (response) {
                $scope.phoneTypes = response.data;

                let keys = Object.keys($scope.phoneTypes);
                $scope.model_data.type = keys.length > 0 ? $scope.phoneTypes[keys[0]] : "";
            });
    }

    function getDeptFunclist() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettingdeftfuncs?user_id=' + user_id)
            .then(function (response) {
                $scope.dept_func_list = [];
                response.data.forEach((ele) => {
                    var item = {
                        id: ele.id,
                        function: ele.function
                    };

                    var label = ele.function + " - " + "(";
                    switch(ele.gs_device)
                    {
                        case "0":
                            label += "User";
                            break;
                        case "1":
                            label += "Device";
                            break;
                        case "2":
                            label += "Roster";
                            break;
                    }

                    item.label = label + ")";

                    $scope.dept_func_list.push(item);
                });
            });
    }

    function getDeviceProfilePrelist() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettingdeviceprofileprelist?user_id=' + user_id)
            .then(function (response) {
                $scope.device_profile_prelist = response.data;
            });
    }

    function init() {
        getDeviceProfilePrelist();
        getDeptFunclist();
        getPhoneTypeList();
        getLocationGroups();
        getBuildingList();
        getDeviceList();
    }

    init();

    $scope.build_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };

    $scope.hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function (itemText, originalItem) {
            return itemText;
        }
    };

    $scope.onSelectDeviceProfile = function(item, model, label) {
        $scope.dept_func_array_id = str2array(item.dept_func_array_id);
        $scope.sec_dept_func = str2array(item.sec_dept_func);
        $scope.loc_grp_array_id = str2array(item.loc_grp_array_id);
        $scope.sec_loc_grp_array_id = str2array(item.sec_loc_grp_id);
        $scope.building_ids = str2array(item.building_ids);
    };

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onSetCurrentGroupType = function(typeInfo) {
        $scope.selected_grouptype_id = typeInfo.type_id;
        $scope.selected_group_type = typeInfo.type;
    };

    $scope.onLocationAdded = function(type_id, bEnd = false) {

        if (bEnd == true && $scope.model_data.locations[type_id]["locations"]) {
            $scope.model_data.locations[type_id]["locations"] = $scope.model_data.locations[type_id]["locations"].filter(item => {
                return item.type_id ? true: false
            });
        }
        let str_locations = $scope.model_data.locations[type_id]["locations"] ? $scope.model_data.locations[type_id]["locations"].map(item => item.name).join(", "): "";
        $scope.model_data.locations[type_id].str_locations = str_locations.length > 200 ? str_locations.slice(0, 197) + "..." : str_locations;
    };

    $scope.onLocationFilter = function (query, type_id) {
        if (type_id == "" || type_id == undefined) {
            return [];
        }
        let all_locations = $scope.model_data.locations[type_id].all_locations;
        let result = all_locations.filter(item => item.name.toLowerCase().includes(query.toLowerCase()));
        return result;
    };

    $scope.onSave = function () {

        let request = angular.copy($scope.model_data);

        request.dept_func_array_id = array2str($scope.dept_func_array_id);
        request.sec_dept_func = array2str($scope.sec_dept_func);

        request.loc_grp_array_id = array2str($scope.loc_grp_array_id);
        request.sec_loc_grp_id = array2str($scope.sec_loc_grp_array_id);

        request.building_ids = array2str($scope.building_ids);

        request.limit_days = array2str($scope.sec_limit_days);
        request.duration = $('#start_time').val() + "~" + $('#end_time').val();

        // $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/addsettingdeviceprofile',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .success(function(data, status, headers, config) {
                if( data) {
                    toaster.pop('success', 'Notification!', 'Created Successfully!');
                    $uibModalInstance.close('ok');
                }
            })
            .error(function(data, status, headers, config) {
                toaster.pop('error', 'Error', 'Error');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
});
