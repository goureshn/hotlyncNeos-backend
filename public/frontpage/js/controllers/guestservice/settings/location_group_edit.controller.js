app.controller('LocationgroupEditController', function ($scope, $http, AuthService, toaster, $uibModalInstance, $uibModal, row) {

    $scope.isLoading = false;

    $scope.model_data = {
        id: row.id,
        client_id: row.client_id,
        name: row.name,
        description: row.description,
        locations: {}
    };


    $scope.grouptypelist = [];
    $scope.groupclientlist = [];
    $scope.selected_grouptype_id = "";
    $scope.selected_group_type = "";

    let profile = AuthService.GetCredentials();

    function getLocationTypeList() {
        $scope.locations = {};
        $scope.grouptypelist = row.detail_list.map(item => {

            let temp = {};
            temp.type_id = item.type_id;
            temp.type = item.type;

            $scope.model_data.locations[temp.type_id] = {};
            $scope.model_data.locations[temp.type_id].locations = item.locations.selected_member;
            $scope.model_data.locations[temp.type_id].all_locations = item.locations.selected_member.concat(item.locations.unselected_member);
            let strLocations = $scope.model_data.locations[temp.type_id].locations.map(subItem => {
                return subItem.name;
            }).join(", ");

            $scope.model_data.locations[temp.type_id].str_locations = strLocations.length > 200 ? strLocations.slice(0, 197) + "..." : strLocations;
            return temp;
        });
    }

    function getClientList() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettingclientlist?user_id=' + user_id)
            .then(function (response) {
                $scope.groupclientlist = response.data;
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
    }

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
            url: '/frontend/guestservice/updatesettinglocationgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .success(function(data, status, headers, config) {
                if( data) {
                    toaster.pop('success', 'Notification!', 'Updated Successfully!');
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
