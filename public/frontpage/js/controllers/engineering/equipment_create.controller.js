app.controller('EquipmentCreateController', function ($scope, $http, $uibModal, AuthService, toaster, GuestService) {
    var MESSAGE_TITLE = 'Equipmemnt Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.equipment = {};
    
    $scope.datetime = {};
    $scope.group = {};
    $scope.equipment.equipment_group = [];
    $scope.equipment.part_group = [];
    $scope.part_group = {};
    $scope.supplier_group = {};
    $scope.maintenance_group = {};
    $scope.datetime.date = new Date();
    $scope.equipment.purchase_date = moment($scope.datetime.date).format('YYYY-MM-DD');
    $scope.equipment.warranty_start = moment($scope.datetime.date).format('YYYY-MM-DD');
    $scope.equipment.warranty_end = moment($scope.datetime.date).format('YYYY-MM-DD');

    $scope.image = null;
    $scope.imageFileName = '';
    $scope.uploadme = {};
    $scope.uploadme.src = '';

    $scope.life_units = [
        'days',
        'months',
        'years',
    ];

    $scope.statuses = $http.get('/frontend/equipment/statuslist')
        .then(function(response){
            $scope.statuses = response.data;
            $scope.equipment.status_id =  $scope.statuses[1].id;
        });


    $scope.equipment.life_unit = $scope.life_units[0];

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
        return promiss = $http.get('/frontend/equipment/grouplist?group_name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onGroupSelect = function (equipment, $item, $model, $label) {
        //$scope.equipment.equipment_group_id = $item.id;
        var equipments = {};
        equipments.equip_group_id = $item.id;
        equipments.name = $item.name;
        var exist_val = false;
        for(var i =0 ; i < $scope.equipment.equipment_group.length; i++) {
            var id = $scope.equipment.equipment_group[i].equip_group_id;
            if(equipments.equip_group_id == id) {
                exist_val = true;
                break;
            }
        }
        if(exist_val == false)
            $scope.equipment.equipment_group.push(equipments);

    };

    $scope.delEquipGroup = function (id) {
        for(var i=0; i < $scope.equipment.equipment_group.length;i++) {
            var group_id = $scope.equipment.equipment_group[i].equip_group_id;
            if(id == group_id) $scope.equipment.equipment_group.splice(i,1);
        }
    }

    $scope.getPartGroupList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/equipmentpartgrouplist?part_group_name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onPartGroupSelect = function (equipment, $item, $model, $label) {
        $scope.equipment.part_group_id = $item.id;
        var parts = {};
        parts.part_group_id = $item.id;
        parts.name = $item.name;
        parts.type = $item.type;
        var exist_val = false;
        for(var i = 0 ; i < $scope.equipment.part_group.length ; i++ ) {
            var id = $scope.equipment.part_group[i].part_group_id;
            if(parts.part_group_id == id) {
                exist_val = true;
                break;
            }
        }
        if(exist_val == false )
            $scope.equipment.part_group.push(parts);
    };

    $scope.delPartGroup = function (id) {
        for(var i=0; i < $scope.equipment.part_group.length;i++) {
            var group_id = $scope.equipment.part_group[i].part_group_id;
            if(id == group_id) $scope.equipment.part_group.splice(i,1);
        }
    }
    $scope.getExternalCompany = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/maintenancelist?name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    $scope.onExternalCompanySelect = function (equipment, $item, $model, $label) {
        $scope.equipment.external_maintenance_id = $item.id;
    };

    $scope.location_list = [];
    function getLocationList() {
        var profile = AuthService.GetCredentials();
        $http.get('/list/locationtotallist?client_id=' + profile.client_id)
            .then(function(response){
                $scope.location_list = response.data;
            });
    };
    getLocationList();

    $scope.onLocationSelect = function ($item, $model, $label) {        
        $scope.equipment.location_group_member_id = $item.id;      
        $scope.equipment.location_name = $item.name;      
        $scope.equipment.location_type = $item.type;
    };

    $scope.onSecLocationSelect = function ($item, $model, $label) {        
        $scope.equipment.sec_loc_id = $item.id;     
        $scope.equipment.sec_location_name = $item.name;   
        $scope.equipment.sec_location_type = $item.type;
    };

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

    $scope.applyMessage = function(val) {
        toaster.pop('error', MESSAGE_TITLE, val);
    }

    $scope.cancelEquip = function(){
        $scope.equipment = {};
        $scope.image = null;
        $scope.imageFileName = '';
        $scope.uploadme = {};
        $scope.uploadme.src = '';

    }

    $scope.createEquipment = function(){
        var data = angular.copy($scope.equipment);
        if($scope.equipment.equip_id == null) {
            $scope.applyMessage('Please enter equipment !');
            return;
        }
        if($scope.equipment.name == null) {
            $scope.applyMessage('Please enter equipment name!');
            return;
        }
       
        data.property_id = profile.property_id;
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
            url: '/frontend/equipment/createequipment',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if(response.data.id){
                    toaster.pop('success', MESSAGE_TITLE, ' Equipment has been created successfully');
                } else{
                    toaster.pop('error', MESSAGE_TITLE, ' Failed to created equipment. Already existed the same equipment name');

                }
                $scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created equipment');
            })
            .finally(function() {
            });
    }

    $scope.CreateGroup = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_group.html',
            controller: 'EquipmentGroupCtrl',
            scope: $scope,
            resolve: {
                name: function () {
                    return $scope.name;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.CreateExternalMaintenance = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_maintenance.html',
            controller: 'EquipmentGroupCtrl',
            scope: $scope,
            resolve: {
                name: function () {
                    return $scope.name;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }


    $scope.CreatePartGroup = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_part.html',
            controller: 'EquipmentGroupCtrl',
            scope: $scope,
            resolve: {
                name: function () {
                    return $scope.name;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.CreateSupplier = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_supplier.html',
            controller: 'EquipmentGroupCtrl',
            scope: $scope,
            resolve: {
                name: function () {
                    return $scope.name;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }
});


app.directive('fileDropzone', function() {
        return {
            restrict: 'A',
            scope: {
                file: '=',
                fileName: '='
            },
            link: function(scope, element, attrs) {
                var checkSize,
                    isTypeValid,
                    processDragOverOrEnter,
                    validMimeTypes;

                processDragOverOrEnter = function (event) {
                    if (event != null) {
                        event.preventDefault();
                    }
                    event.dataTransfer.effectAllowed = 'copy';
                    return false;
                };

                validMimeTypes = attrs.fileDropzone;

                checkSize = function(size) {
                    var _ref;
                    if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
                        return true;
                    } else {
                        alert("File must be smaller than " + attrs.maxFileSize + " MB");
                        return false;
                    }
                };

                isTypeValid = function(type) {
                    if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
                        return true;
                    } else {
                        alert("Invalid file type.  File must be one of following types " + validMimeTypes);
                        return false;
                    }
                };

                element.bind('dragover', processDragOverOrEnter);
                element.bind('dragenter', processDragOverOrEnter);

                return element.bind('drop', function(event) {
                    var file, name, reader, size, type;
                    if (event != null) {
                        event.preventDefault();
                    }
                    reader = new FileReader();
                    reader.onload = function(evt) {
                        if (checkSize(size) && isTypeValid(type)) {
                            return scope.$apply(function() {
                                scope.file = evt.target.result;
                                if (angular.isString(scope.fileName)) {
                                    return scope.fileName = name;
                                }
                            });
                        }
                    };
                    file = event.dataTransfer.files[0];
                    name = file.name;
                    type = file.type;
                    size = file.size;
                    reader.readAsDataURL(file);
                    return false;
                });
            }
        };
    })


    .directive("fileread", [function () {
        return {
            scope: {
                fileread: "=",
                imagename: "=",
                imagetype: "="
            },
            link: function (scope, element, attributes) {
                element.bind("change", function (changeEvent) {
                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.fileread = loadEvent.target.result;
                        });
                    }
                    scope.imagename = changeEvent.target.files[0].name;
                    scope.imagetype = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);

app.controller('EquipmentGroupCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Equipment';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.createGroup = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.name = $scope.group.name;
        if(request.name == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter Equipment Group!');
            return;
        }
        request.description = $scope.group.description;
        request.code = $scope.group.code;
        $http({
            method: 'POST',
            url: '/frontend/equipment/creategroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data == '1062') {
                toaster.pop('error', MESSAGE_TITLE, 'Equipment Group is duplicated');
            }else if(response.data == '200') {
                toaster.pop('success', MESSAGE_TITLE, ' Equipment group has been created  successfully');
                $uibModalInstance.close();
            }

        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
                toaster.pop('error', MESSAGE_TITLE, 'Connection Error!');
            })
            .finally(function() {

            });
    }

    $scope.createPartGroup = function () {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.name = $scope.part_group.name;
        if(request.name == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter Part Group!');
            return;
        }
        request.description = $scope.part_group.description;
        request.code = $scope.part_group.code;
        $http({
            method: 'POST',
            url: '/frontend/equipment/createpartgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data == '1062') {
                toaster.pop('error', MESSAGE_TITLE, 'Equipment Group is duplicated');
            }else if(response.data == '200') {
                toaster.pop('success', MESSAGE_TITLE, ' Equipment part group has been created  successfully');
                $uibModalInstance.close();
            }

        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
                toaster.pop('error', MESSAGE_TITLE, 'Connection Error!');
            })
            .finally(function() {

            });
    }

    $scope.createSupplier = function () {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.supplier = $scope.supplier_group.supplier;
        if(request.supplier == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter Supplier!');
            return;
        }
        request.contact = $scope.supplier_group.contact;
        request.phone = $scope.supplier_group.phone;
        request.email = $scope.supplier_group.email;
        request.url = $scope.supplier_group.url;
        $http({
            method: 'POST',
            url: '/frontend/equipment/createsupplier',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data == 1062) {
                toaster.pop('error', MESSAGE_TITLE, 'Supplier is duplicated.');
            }else  if(response.data == '200') {
                toaster.pop('success', MESSAGE_TITLE, ' Supplier has been created  successfully.');
                $uibModalInstance.close();
            }

        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
                toaster.pop('error', MESSAGE_TITLE, 'Conenction Error.');
            })
            .finally(function() {

            });
    }

    $scope.createMaintenance = function () {
        var profile = AuthService.GetCredentials();
        var request = {};
        request.external_maintenance = $scope.maintenance_group.external_maintenance;
        if(request.external_maintenance == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter Maintenance company!');
            return;
        }
        request.contact = $scope.maintenance_group.contact;
        request.phone = $scope.maintenance_group.phone;
        request.email = $scope.maintenance_group.email;
        request.url = $scope.maintenance_group.url;
        $http({
            method: 'POST',
            url: '/frontend/equipment/createmaintenance',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data == 1062) {
                toaster.pop('error', MESSAGE_TITLE, 'Supplier is duplicated.');
            }else  if(response.data == '200') {
                toaster.pop('success', MESSAGE_TITLE, ' Maintenance has been created  successfully');
                $uibModalInstance.close();
            }

        }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Conenction Error.');
            })
            .finally(function() {

            });
    }

});
