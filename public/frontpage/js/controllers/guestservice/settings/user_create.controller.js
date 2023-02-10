app.controller('LocationGreateController', function ($scope, $http, AuthService, toaster, $uibModalInstance, $uibModal) {

    $scope.isLoading = false;

    $scope.model_data = {
        client_id: "",
        name: "",
        description: "",
        locations: null,
    };

    $scope.grouptypelist = [];
    $scope.groupclientlist = [];
    $scope.selected_grouptype_id = "";
    $scope.selected_group_type = "";

    let profile = AuthService.GetCredentials();

    function getLocationTypeList() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettinglocationtypelist?user_id=' + user_id)
            .then(function (response) {
                $scope.model_data.locations = response.data;

                // $scope.model_data.locations.forEach(item => {
                //     item.locations = [];
                // });

                $scope.grouptypelist = Object.keys(response.data).map(key => {
                    let temp = {};
                    temp.type_id = response.data[key].type_id;
                    temp.type = response.data[key].type;
                    return temp;
                });
            });
    }

    function getClientList() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettingclientlist?user_id=' + user_id)
            .then(function (response) {
                $scope.groupclientlist = response.data;
                if ($scope.groupclientlist.length > 0) {
                    $scope.model_data.client_id = $scope.groupclientlist[0].id;
                }
            });
    }

    function init() {
        getClientList();
        getLocationTypeList();
    }

    init();

    $scope.onSelectGroupType = function(item, model, label) {
        $scope.selected_grouptype_id = item.type_id;
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

        $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/addsettinglocationgroup',
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
