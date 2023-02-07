app.controller('PartCreateController', function ($scope, $rootScope, $http, $interval, $uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Part Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.part = {};
    $scope.location = {};
    $scope.datetime = {};
    $scope.group = {};
    $scope.part.part_group = [];
    $scope.part_group = {};
    $scope.supplier_group = {};
    $scope.datetime.date = new Date();
    $scope.part.purchase_date = moment($scope.datetime.date).format('YYYY-MM-DD');
    $scope.part.warranty_start = moment($scope.datetime.date).format('YYYY-MM-DD');
    $scope.part.warranty_end = moment($scope.datetime.date).format('YYYY-MM-DD');

    $scope.image = null;
    $scope.imageFileName = '';
    $scope.uploadme = {};
    $scope.uploadme.src = '';


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

    $scope.applyMessage = function(val) {
        toaster.pop('error', MESSAGE_TITLE, val);
    }
    $scope.cancelPart = function(){
        $scope.part = {};
        $scope.image = null;
        $scope.imageFileName = '';
        $scope.uploadme = {};
        $scope.uploadme.src = '';
       
    }
    
    $scope.createPart = function(){
        var data = angular.copy($scope.part);
        data.property_id = profile.property_id;
        if($scope.part.part_id == null) {
            $scope.applyMessage('Please enter part ID!');
            return;
        }
        if($scope.part.name == null) {
            $scope.applyMessage('Please enter part name!');
            return;
        }
        if($scope.part.part_group_name == null) {
            $scope.applyMessage('Please enter part group!');
            return;
        }
        if($scope.part.quantity == null) {
            $scope.applyMessage('Please enter quantity!');
            return;
        }
        if($scope.part.minquantity == null) {
            $scope.applyMessage('Please enter minimun quantity!');
            return;
        }
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
            url: '/frontend/part/createpart',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, ' Notification has been created successfully');
                $scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    $scope.CreateSupplier = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_supplier.html',
            controller: 'PartGroupCtrl',
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
        for(var i = 0; i < $scope.part.part_group.length;i++) {
            if($item.name == $scope.part.part_group[i].name){
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

    $scope.CreatePartGroup = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_part.html',
            controller: 'PartGroupCtrl',
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

app.controller('PartGroupCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Part';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.createSupplier = function () {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.supplier = $scope.supplier_group.supplier;
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
            if(response.data == '1062') {
                toaster.pop('error', MESSAGE_TITLE, 'Supplier is duplicated');
            }else if(response.data == '200') {
                //console.log(response);
                toaster.pop('success', MESSAGE_TITLE, ' Supplier has been created  successfully');
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
        request.description = $scope.part_group.description;
        request.code = $scope.part_group.code;
        $http({
            method: 'POST',
            url: '/frontend/equipment/createpartgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data == '1062') {
                toaster.pop('error', MESSAGE_TITLE, 'Part group is duplicated');
            }else if(response.data == '200') {
                //console.log(response);
                toaster.pop('success', MESSAGE_TITLE, 'Part group has been created  successfully');
                $uibModalInstance.close();
            }

        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
                toaster.pop('error', MESSAGE_TITLE, 'Connection Error!');
            })
            .finally(function() {

            });
    }


});
