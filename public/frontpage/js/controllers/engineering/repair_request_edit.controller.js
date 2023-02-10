app.controller('RepairRequestEditController', function ($scope,$window, $http, $uibModal, $uibModalInstance, AuthService, toaster, Upload, repair_request) {
    var MESSAGE_TITLE = 'Work Request Edit';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;
    var dept_id = profile.dept_id;
    var user_id = profile.id;

    $scope.create_work_order = false;

    $scope.datetime = {};
    $scope.datetime.schedule_date = new Date();

    $scope.isLoadingUpdate = false;


    $http.get('/frontend/eng/repairrequest_getcategory_list?user_id='+ user_id)
        .then(function(response){
            $scope.category_list = response.data.content;
        });

    $scope.repair_request_status = [
        'Pending',
        'Assigned',
        'In Progress',
        'Completed', 
        'Pre-Approved',      
    ];
    
    $scope.staff_list = [];

    $scope.eng_setting = {};
            $http.get('/list/engsetting?property_id=' + profile.property_id).success( function(response) {
                $scope.eng_setting = response;
            });

    $scope.init = function(row)
    {
        $scope.repair_request = row;

        $scope.repair_request.description = $scope.repair_request.comments;
        $scope.repair_request.equipment_name = $scope.repair_request.equip_name;
        $scope.repair_request.requestor = $scope.repair_request.wholename;
        $scope.repair_request.category = $scope.repair_request.category_id;
        $scope.repair_request.sub_category = $scope.repair_request.sub_category_id;
        $scope.repair_request.due_date = moment($scope.repair_request.schedule_date).format('YYYY-MM-DD HH:mm:ss');
        $scope.repair_request.supplier_flag = $scope.repair_request.supplier_id > 0;
        $scope.repair_request.comment = '';
        
        var attach_files = [];
        if($scope.repair_request.attach)
              attach_files = $scope.repair_request.attach.split("&&");
        $scope.repair_request.old_files = [];
        $scope.repair_request.files = [];
        $scope.repair_request.thumbnails = [];

        for( var i = 0 ; i < attach_files.length ; i++)
        {
            $scope.repair_request.old_files.push(attach_files[i]);
        }

        $http.get('/frontend/eng/stafflist?&client_id=' + client_id + '&dept_id=' + dept_id)
            .then(function(response){
                $scope.staff_list = response.data;
            });

        $http.get('/frontend/eng/getrepaircomment?id=' + row.id)
            .then(function(response){
                $scope.comment_list = response.data.content;
                addTimeToComment();
            });    

        $http.get('/frontend/eng/repairrequest_getsubcategory_list?category_id='+$scope.repair_request.category)
            .then(function(response){
                $scope.subcategory_list = response.data.content;
            });

        if( $scope.repair_request.status_name == 'Completed' )
        {
            $scope.repair_request_status = [
                'Completed',       
                'Reopen',
                'Closed',
            ];
        }    
        else
        {
            $scope.repair_request_status = [
                'Pending',
                'Assigned',
                'In Progress',
                'On Hold',
                'Completed',       
                'Rejected',   
                'Pre-Approved',  
                'Closed',  
            ];
        }

        if ($scope.repair_request.status_name == 'Closed') {
            $scope.view_property = true;
        } else {
            $scope.view_property = false;
        }

        $scope.repair_flag_edit = false;
        $scope.desc_flag_edit = false;
    }

    $scope.init(repair_request);

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


    $scope.$watch('datetime.schedule_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.repair_request.schedule_date = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.onSelectUser = function (user, $item, $model, $label) {
        $scope.repair_request.assignee = $item.id;
        if ($scope.repair_request.status_name == 'Assigned')
            $scope.create_work_order = true;
    };

    $scope.onSelectSupplier = function(item, $item, $model, $label)
    {
        $scope.repair_request.supplier_id = $item.id;
    }

    $scope.onStaffChanged = function()
    {
        $scope.create_work_order = $scope.repair_request.staff_groups.length > 0;
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

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.setRepairrequestCategoryList = function(list){
        $scope.category_list = list;
    }
    $scope.setRepairrequestSubCategoryList = function(list){
        $scope.subcategory_list = list;
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


    $scope.updateRepairRequest = function(){
        var data = angular.copy($scope.repair_request);
        data.isCreateWO = $scope.create_work_order;
     
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

        $scope.isLoadingUpdate = true;
        
        console.log(JSON.stringify(data));
        $http({
            method: 'POST',
            url: '/frontend/eng/updaterepairrequest',
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
                    }, function (response) {
                        if (response.status > 0) {
                            $scope.errorUploadMsg = response.status + ': ' + response.data;
                        }
                    }, function (evt) {
                        $scope.upload_progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                    });

                }
                else
                {
                    if( $scope.create_another_flag )
                        init();
                    else    
                        $uibModalInstance.close();
                }
                toaster.pop('success', MESSAGE_TITLE, ' Work request has been updated successfully');
                $scope.pageChanged();
                if(response.data.createdWO == true )
                {
                    toaster.pop('success', MESSAGE_TITLE, ' Work Order has been created successfully');
                }
                $uibModalInstance.dismiss();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
                $scope.isLoadingUpdate = false;
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

    $scope.removeOldFile = function(index) {
        $scope.repair_request.old_files.splice(index, 1);
    }

    $scope.removeFile = function($index) {
        $scope.repair_request.files.splice($index, 1);
        $scope.repair_request.thumbnails.splice($index, 1);
    }


    $scope.delete = function(row){
        var data = angular.copy(row);

        if (data.status_name == 'Completed' || data.status_name == 'Closed'){

            toaster.pop('info', MESSAGE_TITLE, ' Work request cannot be deleted');
            return;
        }
        
        console.log(JSON.stringify(data));
        $http({
            method: 'POST',
            url: '/frontend/eng/deleterepairrequest',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                toaster.pop('success', MESSAGE_TITLE, ' Work request has been deleted successfully');
                $scope.pageChanged();
                
                $uibModalInstance.dismiss();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Work Request!');
            })
            .finally(function() {
            });


    }

    function addTimeToComment()
    {
        $scope.comment_list.forEach(function(row, index1){
            row.comment = row.comment.replace(/\r?\n/g,'<br/>');
            row.time = moment(row.created_at).fromNow();    
        });    
    }

    $scope.commitComment = function() {
        if( !$scope.repair_request.comment )
            return;

        $http({
            method: 'POST',
            url: '/frontend/eng/postrepaircomment',
            data: $scope.repair_request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                $scope.comment_list = response.data.content;
                addTimeToComment();
                $scope.repair_request.comment = '';
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
            });

    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }

    var summary_text = '';
    $scope.onEditSummary = function() {
        $scope.repair_flag_edit = true;        
        summary_text = $scope.repair_request.repair + '';
    }

    $scope.onSaveSummary = function() {
        $scope.repair_flag_edit = false;        
    }

    $scope.onCancelSummary = function() {
        $scope.repair_flag_edit = false;
        $scope.repair_request.repair = summary_text + '';
    }

    var desc_text = '';
    $scope.onEditDesc = function() {
        $scope.desc_flag_edit = true;        
        desc_text = $scope.repair_request.description + '';
    }

    $scope.onSaveDesc = function() {
        $scope.desc_flag_edit = false;        
    }

    $scope.onCancelDesc = function() {
        $scope.desc_flag_edit = false;
        $scope.repair_request.description = desc_text + '';
    }
});

