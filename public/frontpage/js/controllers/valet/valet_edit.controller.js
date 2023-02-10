app.controller('ValetEditController', function ($scope, $rootScope, $http, $uibModal, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Valet Edit';

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.getValetDetail = function () {
        var request = {};
        request.valet_id = $scope.valet.id;
        request.property_id = property_id;
        var url = '/frontend/valet/getvaletdetail';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.valet = response.data.datalist[0];
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }
    $scope.getValetDetail();

    $scope.part_group = [];
    $scope.datetime = {};

    $scope.frequency_units = [
        'Days',
        'Weeks',
        'Months',
        'Years',
    ];

    $scope.prioritys = [
        'Low',
        'Medium',
        'High',
        'Urgent'
    ];

    $scope.work_order_types = [
        'Repairs',
        'Requests',
        'Preventive',
        'Upgrade',
        'New',
    ];

    $scope.init = function() {
        if ($scope.valet.status == 'Pending') {
            $scope.view_property = false;
        } else {
            $scope.view_property = true;
        }

    }
    $scope.init();

    $scope.valet.frequency_unit = $scope.frequency_units[0];


    $scope.getEquipmentList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/equipmentlist?equipment=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onEquipmentSelect = function (valet, $item, $model, $label) {
        $scope.valet.equipment_id = $item.id;
    };

    $scope.getCheckList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/getchecklist?name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onChecklistSelect = function (valet, $item, $model, $label) {
        $scope.valet.checklist_id = $item.id;
        $scope.valet.equipment_id = $item.equip_id;
        $scope.valet.equipment_name = $item.equip_name;
        $scope.valet.work_order_type = $item.work_order_type;
    };

    // $scope.getStaffList = function(val) {
    //     if( val == undefined )
    //         val = "";
    //     var profile = AuthService.GetCredentials();
    //     var property_id = profile.property_id;
    //     return GuestService.getStaffList(val)
    //         .then(function(response){
    //             return response.data.map(function(item){
    //                 return item;
    //             });
    //         });
    // };

    $scope.getStaffList = function(query) {
        if( query == undefined )
            query = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return promiss = $http.get('/frontend/valet/getstaffgrouplist?staff_group_name='+query+'&property_id='+property_id)
            .then(function(response){
                var staff_tags = response.data;
                return staff_tags.filter(function(type) {
                    return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
                });
            });
    };

    $scope.onStaffSelect = function (valet, $item, $model, $label) {
        $scope.valet.staff_id = $item.id;
        $scope.valet.staff_name = $item.name;
        $scope.valet.staff_cost = $item.cost;
        if($scope.valet.staff_cost == null) $scope.valet.staff_cost = 0;
        $scope.valet.staff_type = $item.type;
    };

    $scope.getPartList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return promiss = $http.get('/frontend/valet/partlist?part_name='+val+"&property_id="+property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onPartelect = function (valet, $item, $model, $label) {
        $scope.valet.part_id = $item.id;
        $scope.valet.part_stock = $item.quantity;
        $scope.valet.part_cost = $item.purchase_cost;
    };

    $scope.$watch('datetime.start_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.valet.purpose_start_date = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.$watch('datetime.end_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.valet.purpose_end_date = moment(newValue).format('YYYY-MM-DD');
    });


    $scope.UpdateValet = function(){
        var data = angular.copy($scope.valet);
        data.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/valet/updatevalet',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Part has been updated successfully');
                $scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    $scope.cancelValet = function(){
        $scope.valet = {};
        $scope.part_group = [];
    }
    

    
});

app.controller('ValetPartCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

