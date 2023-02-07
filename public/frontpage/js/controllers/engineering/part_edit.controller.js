app.controller('PartEditController', function ($scope, $rootScope, $http, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Part Edit';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.datetime = {};
    $scope.uploadme = {};
    $http.get('/frontend/equipment/getimage?image_url='+$scope.part.image_url).then(function(response) {
        $scope.uploadme.src = response.data;
        var url = $scope.part.image_url;
        $scope.uploadme.imagetype = 'image/' + url.substr(url.lastIndexOf(".") + 1, url.length);
    });


    $scope.getSupplierList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/supplierlist?supplier='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onSupplierSelect = function (part, $item, $model, $label) {
        $scope.part.supplier_id = $item.id;
    };

    $scope.$watch('datetime.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.part.purchase_date = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.$watch('datetime.date_warranty_start', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.part.warranty_start = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.$watch('datetime.date_warranty_end', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.part.warranty_end = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.UpdatePart = function(){
        var data = angular.copy($scope.part);
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
            url: '/frontend/part/updatepart',
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

    $scope.getPartGroupList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/onlypartgrouplist?part_group_name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onPartGroupSelect = function (equipment, $item, $model, $label) {
        $scope.part.part_group_id = $item.id;
        var parts = {};
        parts.part_group_id = $item.id;
        parts.name = $item.name;
        var exist_part = false;
        for(var i = 0 ; i < $scope.part.part_group.length ; i++) {
            if($item.name == $scope.part.part_group[i].name) {
                exist_part = true;
                break;
            }
        }
        if(exist_part == false)
            $scope.part.part_group.push(parts);
    };

    $scope.delPartGroup = function (id) {
        for(var i=0; i < $scope.part.part_group.length;i++) {
            var group_id = $scope.part.part_group[i].part_group_id;
            if(id == group_id) $scope.part.part_group.splice(i,1);
        }
    }
});

