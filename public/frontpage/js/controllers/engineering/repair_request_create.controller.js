app.controller('RepairRequestCreateController', function ($scope, $http, $uibModal, $uibModalInstance, AuthService, toaster, Upload) {
    var MESSAGE_TITLE = 'Work Request Create';

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;

    $scope.create_another_flag = false;

    $scope.isLoadingCreate = false;

    function init()
    {
        $scope.repair_request = {};
        $scope.repair_request.priority = $scope.prioritys[0];
        $scope.repair_request.files = [];
        $scope.repair_request.thumbnails = [];
    }

    init();

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.repair_request.requestor_id = $item.id;
        $scope.repair_request.requestor_type = $item.type;
        $scope.repair_request.wholename = $item.wholename;
    };

     $scope.equip_list = [];

    function getEquipmentList() {
        $http.get('/list/equipmentlist?property_id=' + property_id)
            .then(function(response){
                $scope.equip_list = response.data;                
            });
    };

    getEquipmentList();
    
    $scope.onEquipmentSelect = function (repair_request, $item, $model, $label) {
        $scope.repair_request.equipment_id = $item.id; 
        $scope.repair_request.equip_id = $item.equip_id; 

       if($item.loc_id != 0){
            $scope.repair_request.location_id = $item.loc_id; 
            $scope.repair_request.location_name = $item.location_name; 
            $scope.repair_request.location_type = $item.location_type;
       }      
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.repair_request.location_id = $item.id;        
        $scope.repair_request.location_type = $item.type;        
    };

    $http.get('/frontend/eng/repairrequest_getcategory_list')
            .then(function(response){
                $scope.category_list = response.data.content;
            });

    $http.get('/frontend/eng/repairrequest_tenant_list')
            .then(function(response){
                $scope.tenant_list = response.data.content;
            });

    $scope.eng_setting = {};
            $http.get('/list/engsetting?property_id=' + profile.property_id).success( function(response) {
                $scope.eng_setting = response;
            });
      
    $scope.createRepairRequest = function(){
        var data = angular.copy($scope.repair_request);

        data.property_id = profile.property_id;
        data.user_id = profile.id;

        if (!(data.equipment_id) && $scope.eng_setting.eng_equip_mandatory > 0 ){
            toaster.pop('error', MESSAGE_TITLE, 'Please select Equipment from List');
            return;
        }

        if (!(data.category) && $scope.eng_setting.eng_category_mandatory > 0 ){
            toaster.pop('error', MESSAGE_TITLE, 'Please select Category from List');
            return;
        }

        $scope.isLoadingCreate = true;
        
        $http({
            method: 'POST',
            url: '/frontend/eng/createrepairrequest',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                var files = $scope.repair_request.files;

                if(files && files.length > 0)
                {
                    Upload.upload({
                        url: '/frontend/eng/upload_repair_attach',
                        data: {
                            id: response.data.id,
                            files: files
                        }
                    }).then(function (response) {
                        if( $scope.create_another_flag )
                            init();
                        else    
                            $uibModalInstance.close();

                        console.log(response);
                        $scope.isLoadingCreate = false;

                    }, function (response) {
                        if (response.status > 0) {
                            $scope.errorUploadMsg = response.status + ': ' + response.data;
                        }
                        $scope.isLoadingCreate = false;

                    }, function (evt) {
                        $scope.upload_progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                        $scope.isLoadingCreate = false;
                    });

                }
                else
                {
                    if( $scope.create_another_flag )
                        init();
                    else    
                        $uibModalInstance.close();

                    $scope.isLoadingCreate = false;

                }

                toaster.pop('success', MESSAGE_TITLE, ' Work request has been created successfully');

                $scope.pageChanged();

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
            });
    }

    $scope.uploadFiles = function (files) {
        if(files.length > 0)
        {
            $scope.repair_request.files = $scope.repair_request.files.concat(files);
            files.forEach(row => {
                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    $scope.repair_request.thumbnails.push(loadEvent.target.result);
                }
                reader.readAsDataURL(row);
            });
        }
    }
    $scope.removeFile = function($index) {
        $scope.repair_request.files.splice($index, 1);
        $scope.repair_request.thumbnails.splice($index, 1);
    }

    $scope.createCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/modal/repairrequest_category.html',
            controller: 'RepairRequestCategoryCtrl',
            scope: $scope,
            resolve: {
                repair_request: function () {
                    return $scope.repair_request;
                },
                category_list: function () {
                    return $scope.category_list;
                }
            }
        });

        modalInstance.result.then(function (row) {
            $scope.repair_request.category = row.id;
            $scope.selectMainCategory();
        }, function () {

        });
    }

    $scope.createSubCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/modal/repairrequest_subcategory.html',
            controller: 'RepairRequestSubCategoryCtrl',
            scope: $scope,
            resolve: {
                repair_request: function () {
                    return $scope.repair_request;
                },
                subcategory_list: function () {
                    return $scope.subcategory_list;
                },
                category_list: function () {
                    return $scope.category_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem,row) {
            $scope.selected = selectedItem;
            console.log(row);
        }, function () {

        });
    }

    $scope.addrequestor = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/modal/repairrequest_requestor.html',
            controller: 'RepairRequestRequestorCtrl',
            scope: $scope,
            resolve: {
                repair_request: function () {
                    return $scope.repair_request;
                },
                tenant_list: function () {
                    return $scope.tenant_list;
                }
            }
        });

        modalInstance.result.then(function (row) {
            $scope.repair_request.wholename = row.tenant_name;
            $scope.repair_request.requestor_id = row.id;
            $scope.repair_request.requestor_type = 'Tenant';
        }, function () {

        });
    }

    $scope.setRepairrequestCategoryList = function(list){
        $scope.category_list = list;
    }

    $scope.setRepairrequestTenantList = function(list){
        $scope.tenant_list = list;
    }
    $scope.setRepairrequestSubCategoryList = function(list){
        $scope.subcategory_list = list;
    }

    $scope.setRepairrequestStaffList = function(){
     //   $scope.staff_list = [];
        $http.get('/frontend/eng/requestorlist')
                .then(function(response){
                    $scope.staff_list = response.data.content;
                });
            
    }

    $scope.setSubCategory = function(row){
        $scope.repair_request.sub_category = row.id;
    };

    $scope.selectMainCategory = function()
    {
        $http.get('/frontend/eng/repairrequest_getsubcategory_list?category_id='+$scope.repair_request.category)
            .then(function(response){
                $scope.subcategory_list = response.data.content;
            });
    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }
});

app.controller('RepairRequestCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, repair_request, category_list) {
    $scope.repair_request = repair_request;
    $scope.cateory_list = category_list;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.repair_request.category_new_name;
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/eng/repairrequest_savecategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.repair_request.category_new_name = '';
            $scope.category_list = response.data;
            $scope.setRepairrequestCategoryList($scope.category_list);
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.selectRow = function(row){        
        $uibModalInstance.close(row);
    }

});


app.controller('RepairRequestSubCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, repair_request, subcategory_list, category_list) {
    $scope.repair_request = repair_request;
    $scope.subcategory_list = subcategory_list;
    $scope.category_list = category_list;
    $scope.category = {};
    for(var i = 0 ; i < $scope.category_list.length;i++)
    {
        if($scope.category_list[i].id == $scope.repair_request.category)
        {
            $scope.category = $scope.category_list[i];
        }
    }

    $scope.createSubCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.repair_request.subcategory_new_name;
        request.user_id = profile.id;
        request.property_id = profile.property_id;
        request.category_id = $scope.repair_request.category;
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/eng/repairrequest_savesubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.repair_request.subcategory_new_name = '';
            $scope.subcategory_list = response.data;

            //var alloption = {id: 0, name : 'Unclassified'};
            //$scope.category_list.unshift(alloption);

            $scope.setRepairrequestSubCategoryList($scope.subcategory_list);
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    $scope.selectRow = function(row){
        $scope.setSubCategory(row);
        $uibModalInstance.dismiss();
    }
});

app.directive('myEsc', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 27) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEsc);
                });

                event.preventDefault();
            }
        });
    };
});

app.controller('RepairRequestRequestorCtrl', function($scope, $uibModalInstance, $http, toaster, AuthService, repair_request, tenant_list) {
    var MESSAGE_TITLE = 'Add New Requestor';
    $scope.repair_request = repair_request;
    $scope.tenant_list = tenant_list;

    $scope.addrequestor = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.repair_request.new_requestor;
        request.email = $scope.repair_request.new_email;
        request.contact = $scope.repair_request.new_contact;
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        if( !request.name ){
            toaster.pop('info', MESSAGE_TITLE, 'Please enter Name');
            return;
        }

        if( !request.email ){
            toaster.pop('info', MESSAGE_TITLE, ' Please enter Email');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/eng/repairrequest_savetenant',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.repair_request.new_requestor = '';
            $scope.repair_request.new_email = '';
            $scope.repair_request.new_contact = '';
            $scope.tenant_list = response.data.list;
            $scope.setRepairrequestTenantList($scope.tenant_list);
            $scope.setRepairrequestStaffList();
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.selectRow = function(row){        
        $uibModalInstance.close(row);
    }

});
