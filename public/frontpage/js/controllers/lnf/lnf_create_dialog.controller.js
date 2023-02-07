app.controller('LnfCreateDialogCtrl', function ($scope, $rootScope, $http, $window, $httpParamSerializer, $timeout, $uibModal, $uibModalInstance, AuthService, toaster, Upload, lnf_type) {
    let profile = AuthService.GetCredentials();
    $scope.body_height = $window.innerHeight - 400;

    let MESSAGE_TITLE = 'Lost/Found Create';

    $scope.lnf_type_list = ['Inquiry', 'Found'];

    $scope.guest_show = 0;

    $scope.lnf = {};
    $scope.lnf_item = {};
    $scope.lnf.lnf_type = lnf_type;

    $scope.cancelItem = function () {
        $scope.lnf_item = {};
        $scope.lnf_item.files = [];
        $scope.lnf_item.thumbnails = [];
        $scope.lnf_item.quantity = 1;

        $scope.lnf_item.store_date = new Date();
        $scope.lnf_item.stored_time = moment().format('YYYY-MM-DD HH:mm:ss');
    };

    function initVariable() {
        $scope.lnf.lnf_date = new Date();
        $scope.lnf.lnf_time = moment().format('YYYY-MM-DD HH:mm:ss');

        $scope.lnf.received_date = new Date();
        $scope.lnf.received_time = moment().format('YYYY-MM-DD HH:mm:ss');

        $scope.lnf.guest_id = 0;
        $scope.lnf.custom_guest = 0;
        $scope.lnf.create_another_flag = true;

        // Found Receive
        $scope.found_by = {};
        $scope.received_by = {};
        $scope.received_by.fullname = profile.first_name + ' ' + profile.last_name;
        $scope.lnf.received_by = profile.id;

        // Location
        $scope.location = {};
        $scope.location.name = '';
        $scope.location.type = 'Room';

        // Item list
        $scope.items = [];

        $scope.cancelItem();
    }

    initVariable();

    function initData() {
        $http.get('/list/locationtotallist?client_id=' + profile.client_id)
            .then(function (response) {
                $scope.location_list = response.data;

                let sortingArr = ["Room", "Property", "Building", "Floor", "Common Area", "Admin Area", "Outdoor"];
                $scope.location_list.sort(function (a, b) {
                    return sortingArr.indexOf(a.type) - sortingArr.indexOf(b.type);
                });
            });

        $http.get('/list/lnf_datalist?client_id=' + profile.client_id)
            .then(function (response) {
                $scope.storedlocation_list = response.data.store_loc;
                $scope.itemcustomuser_list = response.data.item_user;

                for (let i = 0; i < $scope.itemcustomuser_list.length; i++) {
                    let item = $scope.itemcustomuser_list[i];

                    if (item['id'] === profile.id) {
                        $scope.found_by = item;

                        $scope.lnf.found_by = item.id;
                        $scope.lnf.custom_user = item.id;
                        $scope.lnf.user_type = item.user_type;

                        break;
                    }
                }

                $scope.itemcolor_list = response.data.item_color;
                $scope.itembrand_list = response.data.item_brand;
                $scope.itemtype_list = response.data.item_type;
                $scope.tag_list = response.data.item_tag;
                $scope.itemcategory_list = response.data.item_category;
                $scope.jobrole_list = response.data.item_jobrole;
            });

        $http.get('/list/user?client_id=' + profile.client_id)
            .then(function (response) {
                $scope.user_list = response.data.map(item => {
                    item.fullname = "";
                    if (item.first_name)
                        item.fullname = item.first_name;

                    if (item.last_name)
                        item.fullname += item.last_name;

                    return item;
                });
            });
    }


    initData();


    if (lnf_type === 'Found')
        $scope.dialog_title = 'Create Found Item';
    else
        $scope.dialog_title = 'Create Inquiry Item';


    $scope.$watch('lnf.lnf_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        // console.log(newValue);
        $scope.lnf.lnf_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.$watch('lnf.received_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        // console.log(newValue);
        $scope.lnf.received_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if ($view === 'day') {
            let activeDate = moment().subtract(0, 'days');
            for (let i = 0; i < $dates.length; i++) {
                $dates[i].selectable = $dates[i].localDateValue() <= activeDate.valueOf();
            }
        } else if ($view === 'minute') {
            let activeDate = moment().subtract(5, 'minute');
            for (let i = 0; i < $dates.length; i++) {
                $dates[i].selectable = $dates[i].localDateValue() <= activeDate.valueOf();
            }
        }
    };

    // Filter tags
    $scope.loadFilters = function (query) {
        return $scope.tag_list.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onFoundBySelect = function ($item, $model, $label) {
        $scope.found_by = $item;
        $scope.lnf.found_by = $item.id;
        $scope.lnf.custom_user = $item.id;
        $scope.lnf.user_type = $item.user_type;
    };

    // event
    $scope.onReceiverSelect = function ($item, $model, $label) {
        $scope.received_by = $item;
        $scope.lnf.received_by = $item.id;
    };

    $scope.refreshGuestList = function ($item) {
        if (!$item)
            $item = $scope.location;

        if ($item.type === "Room") {
            let request = {};
            request.client_id = profile.client_id;
            request.property_id = profile.property_id;
            request.loc_id = $item.id;
            return $http({
                method: 'POST',
                url: '/frontend/lnf/searchguestlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function (response) {
                // console.log(response);
                $scope.guest_list = response.data.content.slice(0, 10);
            });
        } else {
            let request = {};
            request.client_id = profile.client_id;
            request.loc_id = 0;
            return $http({
                method: 'POST',
                url: '/frontend/lnf/searchguestlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function (response) {
                $scope.guest_list = response.data.content;
            });
        }
    };

    $scope.onLocationSelect = function ($item, $model, $label) {

        if ($item.type === 'Room')
            $scope.guest_show = 1;


        $scope.location = angular.copy($item);
        $scope.lnf.location_id = $item.id;
        $scope.lnf.location_type = $item.type;

        $scope.refreshGuestList($item);
    };

    $scope.createNewGuest = function () {
        $scope.new_guest_input = 1;
        $scope.created_guest = {};
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_newguest.html',
            controller: 'LnfNewGuestCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                created_guest: function () {
                    return $scope.created_guest;
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
        }, function () {

        });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {

        $scope.location.guest_name = $item.guest_name + ' ( ' + $item.arrival + ' - ' + $item.departure + ' )';
        $scope.lnf.guest_id = $item.guest_id;
        $scope.lnf.custom_guest = $item.custom_guest;
        $scope.lnf.guest_type = $item.guest_type;
    };

    // ----------- LNF Item part --------------------------

    // Item Type
    $scope.onItemTypeSelect = function ($item, $model, $label) {
        $scope.lnf_item.type_id = $item.id;
    };

    $scope.setItemTypeList = function (list) {
        $scope.itemtype_list = list;
    };

    $scope.createItemType = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_itemtype.html',
            controller: 'LnfItemTypeCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    // Item Brand
    $scope.onItemBrandSelect = function ($item, $model, $label) {
        $scope.lnf_item.brand_id = $item.id;
    };

    $scope.setItemBrandList = function (list) {
        $scope.itembrand_list = list;
    };

    $scope.createItemBrand = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_itembrand.html',
            controller: 'LnfItemBrandCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itembrand_list: function () {
                    return $scope.itembrand_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    // Item Category
    $scope.onItemCatgorySelect = function ($item, $model, $label) {
        $scope.lnf_item.category_id = $item.id;
    };

    $scope.setItemCategoryList = function (list) {
        $scope.itemcategory_list = list;
    };

    $scope.createItemCategory = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_item_category.html',
            controller: 'LnfItemCategoryCtrl',
            scope: $scope,
            size: 'lg',
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itemcategory_list: function () {
                    return $scope.itemcategory_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    // Store Location
    $scope.onStoredLocationForItemSelect = function ($item, $model, $label) {
        $scope.lnf_item.stored_location_id = $item.id;
    };

    $scope.setStoreLocationList = function (list) {
        $scope.storedlocation_list = list;
    };

    $scope.createStoredLocation = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_storedlocation.html',
            controller: 'LnfStoredLocationCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    $scope.loadTagFilters = function (query) {
        return $scope.tag_list.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    };

    $scope.$watch('lnf_item.stored_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        // console.log(newValue);
        $scope.lnf_item.stored_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.uploadFiles = function (files) {
        if (files.length > 0) {
            $scope.lnf_item.files = $scope.lnf_item.files.concat(files);

            $scope.lnf_item.files.forEach(item => {
                $scope.lnf_item.thumbnails = [];
                let reader = new FileReader();
                reader.onload = function (loadEvent) {
                    $scope.lnf_item.thumbnails.push(loadEvent.target.result);
                };
                reader.readAsDataURL(item);
            });
        }
    };
    $scope.removeFile = function ($index) {
        $scope.lnf_item.files.splice($index, 1);
        $scope.lnf_item.thumbnails.splice($index, 1);
    };

    $scope.addItem = function () {

        if ($scope.lnf_item.item_type && $scope.lnf_item.quantity > 0) {
            let tags = "";
            if ($scope.lnf_item.item_tag)
                tags = $scope.lnf_item.item_tag.map(item => item.text).join(',');
            $scope.lnf_item.tags = tags.replace(/,\s*$/, "");

            $scope.items.push($scope.lnf_item);

            $scope.cancelItem();
        } else {
            toaster.pop('info', "Incomplete", 'You must select values.');
            return 0;
        }
        return 1;
    };

    $scope.clearLnf = function () {
        initVariable();
        $scope.cancelItem();
    };

    $scope.removeItem = function (index) {
        $scope.items.splice(index, 1);
    };

    $scope.editItem = function (item, index) {
        $scope.lnf_item = item;
        $scope.btn_add = "Update";
        $scope.items.splice(index, 1);
    };

    $scope.createLnf = function () {
        if ($scope.items.length < 1) {
            if (!$scope.addItem())
                return;
        }

        if ($scope.items.length < 1) {
            toaster.pop('error', MESSAGE_TITLE, 'No Item');
            return;
        }

        let data = $scope.lnf;
        data.auth_id = profile.id;
        data.items = $scope.items;

        $http({
            method: 'POST',
            url: '/frontend/lnf/create_lnf',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {

            let lnf_request_id = response.data.id;

            $scope.saveLnfItem(lnf_request_id, 0);

        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to created Lost&Found');

        }).finally(function () {

        });
    };

    $scope.saveLnfItem = function (lnf_id, item_count) {
        let data1 = {};

        data1.item = $scope.items[item_count];
        data1.lnf_id = lnf_id;
        data1.lnf_type = $scope.lnf_type;

        $http({
            method: 'POST',
            url: '/frontend/lnf/create_lnf_item',
            data: data1,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {

                // console.log(response);

                let files;
                if ($scope.items[item_count])
                    files = $scope.items[item_count].files;
                // upload files
                if (files && files.length > 0) {
                    Upload.upload({
                        url: '/frontend/lnf/uploadfiles',
                        data: {
                            item_id: response.data.id,
                            files: files
                        }
                    }).then(function (response) {

                        if (item_count == $scope.items.length - 1) {
                            completePostLnfItem(lnf_id);

                        } else {
                            item_count = item_count + 1;
                            $scope.saveLnfItem(lnf_id, item_count);
                        }
                    }, function (response) {
                        if (response.status > 0) {
                            $scope.errorUploadMsg = response.status + ': ' + response.data;
                        }
                    }, function (evt) {
                        $scope.upload_progress =
                            Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                    });
                } else {
                    if (item_count == $scope.items.length - 1) {
                        completePostLnfItem(lnf_id);
                    } else {
                        item_count = item_count + 1;
                        $scope.saveLnfItem(lnf_id, item_count);
                    }
                }
            }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to created Lost&Found');
        })
            .finally(function () {
            });
    };

    function completePostLnfItem(lnf_id) {
        let data = {};
        data.lnf_id = lnf_id;

        $http({
            method: 'POST',
            url: '/frontend/lnf/completepostitem',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, ' Lost&Found has been created successfully');
                $scope.items = [];
                $scope.location = {};

                if ($scope.lnf.create_another_flag === false)
                    $uibModalInstance.dismiss();
                else
                    initVariable();

                // emit onCreateNewLnf
                $scope.$emit('onCreateNewLnf');
            }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to created Lost&Found');
        })
            .finally(function () {
            });
    }

    $scope.setItemCustomUserList = function (list) {
        $scope.itemcustomuser_list = list;
    };

    $scope.createCustomUser = function () {
        $scope.created_user = {};
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_itemcustomuser.html',
            controller: 'LnfCustomUserCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itemcustomuser_list: function () {
                    return $scope.itemcustomuser_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }
});


app.directive('fileDropzone', function () {
    return {
        restrict: 'A',
        scope: {
            file: '=',
            fileName: '='
        },
        link: function (scope, element, attrs) {
            let checkSize,
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

            checkSize = function (size) {
                let _ref;
                if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
                    return true;
                } else {
                    alert("File must be smaller than " + attrs.maxFileSize + " MB");
                    return false;
                }
            };

            isTypeValid = function (type) {
                if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
                    return true;
                } else {
                    alert("Invalid file type.  File must be one of following types " + validMimeTypes);
                    return false;
                }
            };

            element.bind('dragover', processDragOverOrEnter);
            element.bind('dragenter', processDragOverOrEnter);

            return element.bind('drop', function (event) {
                let file, name, reader, size, type;
                if (event != null) {
                    event.preventDefault();
                }
                reader = new FileReader();
                reader.onload = function (evt) {
                    if (checkSize(size) && isTypeValid(type)) {
                        return scope.$apply(function () {
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
                    let reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.fileread = loadEvent.target.result;
                        });
                    };
                    scope.imagename = changeEvent.target.files[0].name;
                    scope.imagetype = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);

app.controller('LnfCustomUserCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, toaster) {
    $scope.lnf = lnf;
    $scope.created_user = {};
    $scope.lnf.new_itemcustomuser_username = "";
    $scope.lnf.new_itemcustomuser_firstname = "";
    $scope.lnf.new_itemcustomuser_lastname = "";
    $scope.department = {};

    let profile = AuthService.GetCredentials();
    let client_id = profile.client_id;
    $http.get('/list/department?client_id=' + client_id)
        .then(function (response) {
            $scope.dep_list = response.data;
        });

    $scope.createItemCustomUser = function () {
        let profile = AuthService.GetCredentials();

        let request = {};

        request.username = $scope.lnf.new_itemcustomuser_username;
        request.first_name = $scope.lnf.new_itemcustomuser_firstname;
        request.last_name = $scope.lnf.new_itemcustomuser_lastname;
        request.department = $scope.department.department;
        request.created_by = profile.id;

        $scope.created_user.username = request.username;
        $scope.created_user.first_name = request.first_name;
        $scope.created_user.last_name = request.last_name;
        $scope.created_user.department = request.department;
        $scope.created_user.fullname = $scope.created_user.first_name + " " + $scope.created_user.last_name + " - " + "Custom User";
        $scope.created_user.user_type = 2;

        if (!request.first_name || !request.department) {
            toaster.pop('info', 'Please input information');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/lnf/createitemcustomuser',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {

            if (response.data.id > 0) {
                $scope.setItemCustomUserList(response.data.list);
                $scope.itemcustomeruser_id = response.data.id;

                $scope.created_user.id = response.data.id;
                $scope.found_by = $scope.created_user;

                toaster.pop('success', 'New user has been created');

                $uibModalInstance.dismiss();
            } else {
                toaster.pop('error', 'User Create Error', 'Duplicated User Name, Department');
            }
        }).catch(function (response) {
        })
            .finally(function () {
            });
    };
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onDepartmentSelect = function ($item, $model, $label) {
        $scope.department = $item;
    }
});

app.controller('LnfNewGuestCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, created_guest) {
    $scope.lnf = lnf;
    $scope.created_guest = {};
    $scope.lnf.guest_firstname = "";
    $scope.lnf.guest_lastname = "";
    $scope.lnf.guest_email = "";
    $scope.lnf.guest_contact_no = "";


    $scope.createGuest = function () {
        let profile = AuthService.GetCredentials();

        let request = {};
        request.first_name = $scope.lnf.guest_firstname;
        request.last_name = $scope.lnf.guest_lastname;
        request.email = $scope.lnf.guest_email;
        request.contact_no = $scope.lnf.guest_contact_no;
        request.created_by = profile.id;

        $scope.created_guest.first_name = request.first_name;
        $scope.created_guest.last_name = request.last_name;
        $scope.created_guest.email = request.email;
        $scope.created_guest.contact_no = request.contact_no;
        $scope.created_guest.first_name = request.first_name;
        $scope.created_guest.guest_type = 2;

        if (!request.first_name)
            return;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createnewguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.created_guest.id = response.data.content.id;

            $scope.lnf.guest_id = 0;
            $scope.lnf.custom_guest = response.data.content.id;
            $scope.lnf.guest_type = 2;
            $scope.location.guest_name = $scope.lnf.guest_firstname + ' ' + $scope.lnf.guest_lastname;

            $scope.refreshGuestList();

            $uibModalInstance.dismiss();
        }).catch(function (response) {
        })
            .finally(function () {
            });
    };
    $scope.cancel = function () {
        $scope.created_guest = {};
        $uibModalInstance.dismiss();
    };

});

app.controller('LnfItemTypeCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, toaster) {
    $scope.lnf = lnf;

    $scope.createItemType = function () {

        let request = {};

        request.type = $scope.lnf.new_itemtype;

        if (!request.type)
            return;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createitemtype',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {

            if (response.data.id > 0) {
                $scope.lnf.new_itemtype = '';
                $scope.setItemTypeList(response.data.list);
            } else {
                toaster.pop('error', 'Item Create Error', 'Duplicated Item Name');
            }

        }).catch(function (response) {
        }).finally(function () {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.setType = function (row) {
        $scope.lnf_item.item_type = row.type;
        $scope.lnf_item.type_id = row.id;
        $uibModalInstance.dismiss();
    }
});


app.controller('LnfItemBrandCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, toaster) {
    $scope.lnf = lnf;

    $scope.createItemBrand = function () {

        let request = {};
        request.brand = $scope.lnf.new_itembrand;

        if (!request.brand)
            return;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createitembrand',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            if (response.data.id > 0) {
                $scope.lnf.new_itembrand = '';
                $scope.setItemBrandList(response.data.list);
            } else {
                toaster.pop('error', 'Brand Creation Error', 'Duplicated Brand Name');
            }

        }).catch(function (response) {
        }).finally(function () {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.setBrand = function (brand) {
        $scope.lnf_item.brand_id = brand.id;
        $scope.lnf_item.brand = brand.brand;
        $uibModalInstance.dismiss();
    }
});

app.controller('LnfItemColorCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf) {
    $scope.lnf = lnf;

    $scope.createItemColor = function () {

        let request = {};

        request.color = $scope.lnf.new_itemcolor;

        if (!request.color)
            return;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createitemcolor',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {

            $scope.lnf.new_itemcolor = '';
            $scope.itemcolor_list = response.data.list;

            let alloption = {id: 0, color: 'Unclassified'};
            $scope.itemcolor_list.unshift(alloption);
        }).catch(function (response) {
        }).finally(function () {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('LnfItemCategoryCtrl', function ($scope, $uibModalInstance, $http, AuthService, toaster) {
    $scope.list = $scope.itemcategory_list;

    let profile = AuthService.GetCredentials();

    $scope.category = {};
    $scope.category.property_id = profile.property_id;
    $scope.category.status_name = $scope.lnf_statuses[0];
    $scope.category.notify_flag = true;

    $scope.createItem = function () {
        if (!$scope.category.name) {
            toaster.pop('info', 'Please select Category Name.');
            return;
        }

        if ($scope.category.notify_flag == 1) {
            if (!($scope.category.notify_job_role_id > 0)) {
                toaster.pop('info', 'Please select Notify User.');
                return;
            }

            if ($scope.category.notify_type.length < 1) {
                toaster.pop('info', 'Please select at least a notify type.');
                return;
            }
        }

        let request = angular.copy($scope.category);
        request.notify_flag = request.notify_flag ? 1 : 0;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createitemcategory',
            data: $scope.category,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            if (response.data.id > 0) {
                $scope.list = response.data.list;
                $scope.setItemCategoryList(response.data.list);

                $scope.category = {};
            } else {
                toaster.pop('error', 'Category Creation Error', 'Duplicated Category Name');
            }
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.setItem = function (row) {
        $scope.lnf_item.category_id = row.id;
        $scope.lnf_item.category = row.name;

        $uibModalInstance.dismiss();
    };

    $scope.onJobRoleSelect = function ($item, $model, $label) {
        $scope.category.notify_job_role_id = $item.id;
    };

    let notify_type_list = [
        'Email', 'SMS', 'Mobile'
    ];

    $scope.loadNotifyTypeFilter = function (query) {
        return notify_type_list.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    };

    $scope.onClickUpdate = function (row) {
        $scope.category = angular.copy(row);
        $scope.category.notify_type = row.notify_type.split(",").map(item => {
            return {'text': item}
        });
        $scope.category.notify_flag = $scope.category.notify_flag == 1;
    };

    $scope.onClickDelete = function (row) {
        let request = {};
        request.id = row.id;

        $http({
            method: 'POST',
            url: '/frontend/lnf/deleteitemcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.list = response.data;
            $scope.itemcategory_list = response.data;
        }).catch(function (response) {
        }).finally(function () {

        });
    }
});

app.controller('LnfStoredLocationCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, toaster) {
    $scope.lnf = lnf;

    $scope.createStoredLocation = function () {
        let request = {};
        request.stored_loc = $scope.lnf.new_stored_location;

        if (!request.stored_loc)
            return;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createstoredlocation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            if (response.data.content.id > 0) {
                $scope.lnf.new_stored_location = '';
                $scope.setStoreLocationList(response.data.content.list);
            } else {
                toaster.pop('error', 'Store Create Error', 'Duplicated Store Name');
            }

        }).catch(function (response) {
        }).finally(function () {

        });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.select_storedlocation = function (row) {
        $scope.lnf_item.stored_location_id = row.id;
        $scope.lnf_item.stored_loc = row.store_loc;
        $uibModalInstance.dismiss();
    }
});
