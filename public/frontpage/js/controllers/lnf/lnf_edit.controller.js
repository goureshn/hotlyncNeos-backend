app.controller('LNFEditController', function ($scope, $rootScope, $http, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Equipmemnt Edit';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.datetime = {};
    $scope.uploadme = {};
    $scope.location = {};
    $scope.location = $scope.equipment.location;
    $http.get('/frontend/lnf/getimage?image_url='+$scope.equipment.image_url).then(function(response) {
        $scope.uploadme.src = response.data;
        var url = $scope.equipment.image_url;
        $scope.uploadme.imagetype = 'image/' + url.substr(url.lastIndexOf(".") + 1, url.length);
    });

    $scope.life_units = [
        'days',
        'months',
        'years',
    ];

    $scope.statuses = $http.get('/frontend/lnf/statuslist')
        .then(function(response){
            $scope.statuses = response.data;
        });

    $scope.getDepartmentList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return GuestService.getDepartSearchList(val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onDepartmentSelect = function (equipment, $item, $model, $label) {
        $scope.equipment.dept_id = $item.id;
    };

    $scope.getGroupList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/lnf/grouplist?group_name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onGroupSelect = function (equipment, $item, $model, $label) {
        $scope.equipment.equipment_group_id = $item.id;
    };

    $scope.getPartGroupList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/lnf/partgrouplist?part_group_name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onPartGroupSelect = function (equipment, $item, $model, $label) {
        $scope.equipment.part_group_id = $item.id;
    };

    $scope.getExternalCompany = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/lnf/maintenancelist?name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onExternalCompanySelect = function (equipment, $item, $model, $label) {
        $scope.equipment.external_maintenance_id = $item.id;
    };

    $scope.getLocationList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.location = $item;
        $scope.equipment.location_group_member_id = $item.id;
    };

    $scope.getSupplierList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/lnf/supplierlist?supplier='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onSupplierSelect = function (equipment, $item, $model, $label) {
        $scope.equipment.supplier_id = $item.id;
    };

    $scope.$watch('datetime.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.equipment.purchase_date = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.$watch('datetime.date_warranty_start', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.equipment.warranty_start = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.$watch('datetime.date_warranty_end', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.equipment.warranty_end = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.UpdateEquipment = function(){
        var data = angular.copy($scope.equipment);
        var currentdate = new Date();
        var datetime = currentdate.getFullYear()+"-"+
            (currentdate.getMonth()+1) +"_"+
            currentdate.getDate() + "_"+
            currentdate.getHours() +"_"+
            currentdate.getMinutes() +"_"+
            currentdate.getSeconds()+"_";
        var url =  datetime + Math.floor((Math.random() * 100) + 1);
        var imagetype = $scope.uploadme.imagetype;
        var imagename = $scope.uploadme.imagename;
        if(imagetype != undefined) {
            var extension = imagetype.substr(imagetype.indexOf("/") + 1, imagetype.length);
            data.image_url = url + "." + extension;
        }
        data.image_src = $scope.uploadme.src;
        $http({
            method: 'POST',
            url: '/frontend/lnf/updateequipment',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Equipment has been updated successfully');
                $scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }
});

