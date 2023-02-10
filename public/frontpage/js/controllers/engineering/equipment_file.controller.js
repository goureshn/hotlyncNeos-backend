app.controller('EquipmentFileController', function ($scope, $http, $uibModal, AuthService) {
    var MESSAGE_TITLE = 'Equipmemnt Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    //$scope.equipment = {};
    $scope.image = null;
    $scope.imageFileName = '';
    $scope.file = {};
    $scope.file.src = '';

    $scope.CreateFile = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_file.html',
            controller: 'EquipmentFileCtrl',
            scope: $scope,
            resolve: {
                equipment: function () {
                   // return $scope.name;
                    return $scope.equipment;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
            $scope.getFilelist();
        }, function () {

        });
    }
    $scope.delEquipFile = function(id) {
        var request = {};
        request.id = id;
        $http({
            method: 'POST',
            url: '/frontend/equipment/equipmentinfiledel',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data.filelist != null) $scope.equipment.filelist = response.data.filelist;
            $scope.getFilelist();
        }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    $scope.getFilelist = function(){
        var request = {};
        request.equip_id = $scope.equipment.id;
        $http({
            method: 'POST',
            url: '/frontend/equipment/equipmentinformlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data.filelist != null) $scope.equipment.filelist = response.data.filelist;
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {

            });
    }

});


app.directive('fileDropzoneinfile', function() {
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


   .directive("filereadinfile", [function () {
        return {
            scope: {
                filereadinfile: "=",
                imagename: "=",
                imagetype: "="
            },
            link: function (scope, element, attributes) {
                element.bind("change", function (changeEvent) {
                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.filereadinfile = loadEvent.target.result;
                        });
                    }
                    scope.imagename = changeEvent.target.files[0].name;
                    scope.imagetype = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);

app.controller('EquipmentFileCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster, equipment, Upload) {
    var MESSAGE_TITLE = 'Equipment File';
    $scope.file = equipment;
    $scope.file.files = [];
    $scope.data = {};
    $scope.data.filename = '';
    $scope.data.filedescription = '';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.uploadFiles = function (files) {
        $scope.file.files = $scope.file.files.concat(files);
    };

    $scope.removeFile = function(f) {
        $scope.file.files = $scope.file.files.filter(item => f != item);
    }

    $scope.AddFile = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        
        request.equip_id = $scope.file.id;
        request.files = $scope.file.files;
        request.filename = $scope.data.filename;
        request.filedescription = $scope.data.filedescription;

        Upload.upload({
                url: '/frontend/equipment/createequipfile',
                data: request
            }).then(function (response) {
                console.log(response);
                $uibModalInstance.close();
            }, function (response) {
                
            }, function (evt) {
                
            });    

    }
});
