app.controller('WorkorderEditController', function ($scope, $rootScope, $http, $uibModal, $uibModalInstance, Upload, AuthService, workorder, toaster) {
    var MESSAGE_TITLE = 'Part Edit';

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.workorder = workorder;

    $scope.getWorkorderDetail = function () {
        var request = {};
        request.id = $scope.workorder.id;
        request.property_id = property_id;
        var url = '/frontend/eng/getworkorderdetail';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.workorder = response.data.content;
            $scope.workorder.staff_cost = 0;
            $scope.workorder.part_cost = 0;
            $scope.workorder.inspected = $scope.workorder.inspected == 1;
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }
    $scope.getWorkorderDetail();

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
        if ($scope.workorder.status == 'Pending') {
            $scope.view_property = false;
        } else {
            $scope.view_property = true;
        }

    }
    $scope.init();

    $scope.workorder.frequency_unit = $scope.frequency_units[0];


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

    $scope.onEquipmentSelect = function (workorder, $item, $model, $label) {
        $scope.workorder.equipment_id = $item.id;
        $scope.workorder.eq_id = $item.equip_id; 
        $scope.workorder.location_id = $item.location_group_member_id;
        $scope.workorder.location_name = $item.location_name;
        $scope.workorder.location_type = $item.location_type;
    };

    $scope.getCheckList = function(val) {
        if( val == undefined )
            val = "";
        var equipment_id = $scope.workorder.equipment_id;
        var work_order_type = $scope.workorder.work_order_type;
        var location_id = $scope.workorder.location_idd;
        return promiss = $http.get('/frontend/equipment/getchecklist?name='+val+
            '&equipment_id='+equipment_id+
            '&work_order_type='+work_order_type+
            '&location_id='+location_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onChecklistSelect = function (workorder, $item, $model, $label) {
        $scope.workorder.checklist_id = $item.id;
        $scope.workorder.equipment_id = $item.equip_id;
        $scope.workorder.equipment_name = $item.equip_name;
        $scope.workorder.work_order_type = $item.work_order_type;
    };


    function getCheckListData()
    {
        var request = {};
         
        request.workorder_id = $scope.workorder.id;
        
        var url = '/frontend/eng/workorderchecklist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.workorder.inspected = response.data.content.list.filter(item => {
                return item.check_flag == 0;
            }).length == 0;
            
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                
            });
    }

    $scope.staff_list = [];
    function getStaffList() 
    {      
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        $http.get('/frontend/eng/getstaffgrouplist?property_id='+property_id)
            .then(function(response){
                $scope.staff_list = response.data.content;             
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    };

    getStaffList();

    $scope.onStaffSelect = function (workorder, $item, $model, $label) {
        $scope.workorder.staff_id = $item.id;
        $scope.workorder.staff_name = $item.name;
        $scope.workorder.staff_cost = $item.cost;
        if($scope.workorder.staff_cost == null) $scope.workorder.staff_cost = 0;
        $scope.workorder.staff_type = $item.type;
    };

    $scope.getPartList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return promiss = $http.get('/frontend/eng/partlist?part_name='+val+"&property_id="+property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onPartelect = function (workorder, $item, $model, $label) {
        $scope.workorder.part_id = $item.id;
        $scope.workorder.part_stock = $item.quantity;
        $scope.workorder.part_cost = $item.purchase_cost;
    };

    $scope.$watch('datetime.schedule_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.workorder.schedule_date = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.updateWorkorder = function(){
        var data = angular.copy($scope.workorder);
        data.property_id = profile.property_id;
        data.inspected = $scope.workorder.inspected ? 1 : 0;
        $http({
            method: 'POST',
            url: '/frontend/eng/updateworkorder',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Work order cannot be updated');
                    return;
                }
                toaster.pop('success', MESSAGE_TITLE, 'Work order has been updated successfully');
                $uibModalInstance.dismiss();        
                $scope.pageChanged();
            }).catch(function(response) {
                
            })
            .finally(function() {
            });
    }

    $scope.cancelWorkorder = function(){
        $uibModalInstance.dismiss();                
    }
    $scope.CreateParts = function(){
        var parts = {};
        parts.part_id = $scope.workorder.part_id;
        parts.part_name = $scope.workorder.part_name;
        parts.part_number = $scope.workorder.part_number;
        parts.part_number_original = 0;
        parts.part_stock = $scope.workorder.part_stock;
        parts.part_cost = $scope.workorder.part_cost;

        $scope.part_duplicate = false;
        for(var i = 0 ; i<$scope.workorder.part_group.length ; i++ ) {
            if($scope.workorder.part_group[i].part_id == parts.part_id ) {
                $scope.part_duplicate = true;                
            }
        }

        $scope.partstock = parts.part_stock;

        if(parts.part_number > parts.part_stock || $scope.part_duplicate == true ) {
            var modalInstance = $uibModal.open({
                templateUrl: 'tpl/engineering/workorder/workorder_partmodal.html',
                controller: 'WorkorderPartCtrl',
                scope: $scope,
                resolve: {
                    workorder: function () {
                        return $scope.workorder;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {

            });
        }else {
            $scope.workorder.part_group.push(parts);

            clearPartFields();
        }
    }

    function clearPartFields()
    {
        $scope.workorder.part_id = 0;
        $scope.workorder.part_name = '';
        $scope.workorder.part_number = '';        
        $scope.workorder.part_stock = 0;
        $scope.workorder.part_cost = 0;
    }

    $scope.deleteParts = function (part_id) {
        for(var i=0; i < $scope.workorder.part_group.length;i++) {
            var id = $scope.workorder.part_group[i].part_id;
            if(id == part_id) $scope.workorder.part_group.splice(i,1);
        }
    }

    $scope.CreateStaffs = function(){
        var staff = {};
        $scope.staff_duplicate = false;
        staff.staff_id = $scope.workorder.staff_id ;
        staff.staff_cost = $scope.workorder.staff_cost;
        staff.staff_name = $scope.workorder.staff_name;
        staff.staff_type = $scope.workorder.staff_type;
        for(var i = 0 ; i<$scope.workorder.staff_group.length ; i++ ) {
            if($scope.workorder.staff_group[i].staff_id == staff.staff_id) {
                $scope.staff_duplicate = true;
                break;
            }
        }
        if($scope.staff_duplicate == true) return;
        $scope.workorder.staff_group.push(staff);

        clearStaffFields();
    }

    function clearStaffFields()
    {
        $scope.workorder.staff_id = 0;
        $scope.workorder.staff_cost = 0;
        $scope.workorder.staff_name = '';
        $scope.workorder.staff_type = '';
    }

    $scope.deleteStaffs = function (staff_id) {
        for(var i=0; i < $scope.workorder.staff_group.length;i++) {
            var id = $scope.workorder.staff_group[i].staff_id;
            if(id == staff_id) $scope.workorder.staff_group.splice(i,1);
        }
    }

    $scope.uploadFiles = function (files) {
        var profile = AuthService.GetCredentials();
        Upload.upload({
            url: '/frontend/eng/uploadfilestoworkorder',
            data: {
                id: $scope.workorder.id,              
                user_id: profile.id,  
                files: files
            }
        }).then(function (response) {
            if( response.data.code == 200 )
            {
                $scope.workorder.filelist = response.data.content;
                $scope.pageChanged();
            }
        }, function (response) {
            
        }, function (evt) {
            
        });
    };

    $scope.deletefile = function(f)
    {
        var request = {};
        request.id = f.id;        
        var url = '/frontend/eng/deletefilefromworkorder';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if( response.data.code == 200 )
            {
                $scope.workorder.filelist = $scope.workorder.filelist.filter(item => item.id != f.id);
                $scope.pageChanged();
            }
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
            });

    }

    $scope.onViewCheckList = function()
    {
        // find selected ticket        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_checklist.html',
            controller: 'WorkorderChecklistController',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {        
                workorder: function() {
                    return $scope.workorder;
                }        
            }
        });

        modalInstance.result.then(function (data) {
            getCheckListData();
        }, function () {

        });
    }

    $scope.$on('workorder_status_change', function(event, args) {
        console.log(args);
       
        if( $scope.workorder.id == args.id )
        {            
            $scope.workorder.status = args.status;            
        }        
    });


});

app.controller('WorkorderPartCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

