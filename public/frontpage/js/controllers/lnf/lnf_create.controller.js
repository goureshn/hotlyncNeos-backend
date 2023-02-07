app.controller('LNFCreateController', function ($scope, $window, $rootScope, $location, $timeout, $http, $interval, $uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload) {
    let MESSAGE_TITLE = 'Lost/Found Create';
    let profile = AuthService.GetCredentials();
    let client_id = profile.client_id;

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 80;
    $scope.tab_height = $window.innerHeight + 10;
    $scope.tab_height1 = $window.innerHeight - 120;


    $scope.activeUser = profile.first_name + ' ' + profile.last_name;

    $scope.btn_add = "Add";
    $scope.lnf_type_list = ['Inquiry', 'Found'];

    $scope.guest_types = ['In-House CI', 'Walk-in'];
    //$scope.guest_types = ['In-House CI', 'In-House CO', 'Arrival', 'Walk-in', 'House Complaint'];

    $scope.lnf = {};
    $scope.items = [];
    $scope.lnf_item = {};
    $scope.lnf_item.files = [];
    $scope.lnf_item.thumbnails = [];
    $scope.upload_progress = 0;
    $scope.errorUploadMsg = "";

    $scope.location = {};
    $scope.found_by = {};
    $scope.found_by.id = profile.id;
    $scope.found_by.user_type = 1;  // Hotel user

    $scope.created_user = {};
    $scope.created_guest = {};
    $scope.storedlocation = {};

    $scope.received_by = {};

    $scope.image = null;
    $scope.imageFileName = '';
    $scope.uploadme = {};
    $scope.uploadme.src = '';
    $scope.new_guest_input = 0;

    $scope.init = function () {
        $http.get('/list/locationtotallist?client_id=' + client_id)
            .then(function (response) {
                $scope.location_list = response.data;
                let sortingArr = ["Room", "Property", "Building", "Floor", "Common Area", "Admin Area", "Outdoor"];

                $scope.location_list.sort(function (a, b) {
                    return sortingArr.indexOf(a.type) - sortingArr.indexOf(b.type);
                });

            });

        $http.get('/list/lnf_datalist?client_id=' + client_id)
            .then(function (response) {
                $scope.storedlocation_list = response.data.store_loc;
                $scope.itemcustomuser_list = response.data.item_user;
                $scope.itemcolor_list = response.data.item_color;
                $scope.itembrand_list = response.data.item_brand;
                $scope.itemtype_list = response.data.item_type;
                $scope.tag_list = response.data.item_tag;
                $scope.itemcategory_list = response.data.item_category;
                $scope.jobrole_list = response.data.item_jobrole;
            });


        $http.get('/list/user?client_id=' + client_id)
            .then(function (response) {
                $scope.user_list = response.data;
                for (let i = 0; i < $scope.user_list.length; i++) {
                    $scope.user_list[i].fullname = "";
                    if ($scope.user_list[i].first_name)
                        $scope.user_list[i].fullname = $scope.user_list[i].fullname + $scope.user_list[i].first_name;
                    if ($scope.user_list[i].last_name)
                        $scope.user_list[i].fullname = $scope.user_list[i].fullname + " " + $scope.user_list[i].last_name;
                }
            });


        //$scope.found_by = profile.id;
    };


    $scope.onChangeLNFType = function () {
        $scope.location = {};
        $scope.found_by = {};
        $scope.received_by = {};
        $scope.created_user = {};
        $scope.storedlocation = {};

    };
    $scope.getLocationList = function (val) {
        if (val === undefined)
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };
    $scope.guest_type_select = "Walk-in";
    $scope.onLocationSelect = function ($item, $model, $label) {
        let profile = AuthService.GetCredentials();

        $scope.guest = {};
        $scope.selected_guest = {};

        $scope.location = angular.copy($item);
        if ($item.type === "Room") {
            let request = {};
            request.client_id = client_id;
            request.property_id = profile.property_id;
            request.loc_id = $item.id;
            return $http({
                method: 'POST',
                url: '/frontend/lnf/searchguestlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function (response) {
                console.log(response);
                $scope.guest_list = response.data.content.slice(0, 10);
                $scope.guest = {};
            });
        } else {
            //$scope.getGuest($item.name);
            let request = {};
            request.client_id = client_id;
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

    $scope.onUserSelect = function ($item, $model, $label) {
        $scope.found_by = $item;
    };

    $scope.onReceiverSelect = function ($item, $model, $label) {
        $scope.received_by = $item;
    };

    $scope.onStoredLocationSelect = function ($item, $model, $label) {
        $scope.storedlocation = $item;
    };
    $scope.onItemTypeSelect = function ($item, $model, $label) {
        $scope.lnf_item.itemtype = $item;
    };
    $scope.onItemBrandSelect = function ($item, $model, $label) {
        $scope.lnf_item.brand = $item;
    };

    $scope.onStoredLocationForItemSelect = function ($item, $model, $label) {
        $scope.lnf_item.stored_location_id = $item.id;
    };

    $scope.getGuest = function (val) {
        if (val === undefined)
            val = "";
        let property_id = profile.property_id;
        return $http.get('/frontend/guestservice/getguestinfo?room=' + val + '&property_id=' + property_id)
            .then(function (response) {
                $scope.guest = response.data.guestlist;
                let cur_time = new Date();
                if ($scope.guest.checkout_flag === "checkin")
                    $scope.guest.guest_type = "In-House CI";
                else if ($scope.guest.checkout_flag === "checkout") {
                    $scope.guest.guest_type = "In-House CO";
                } else if (new Date($scope.guest.arrival) >= cur_time) {
                    $scope.guest.guest_type = "Arrival";
                } else if (new Date($scope.guest.arrival) <= cur_time) {
                    $scope.guest.guest_type = "Walk-in";
                } else {
                    $scope.guest.guest_type = "House Complaint";
                }

                console.log($scope.guest);
            });
    };


    $scope.getGuestList = function (room_id, val) {
        if (val === undefined)
            val = "";
        let request = {};
        request.client_id = client_id;
        request.value = val;
        request.room_id = room_id; //$scope.complaint.room_id;
        return $http({
            method: 'POST',
            url: '/frontend/lnf/searchguestlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            let list = response.data.content.slice(0, 10);
            return list.map(function (item) {
                return item;
            });
        });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.guest = $item;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };

    $scope.datetime = {};
    $scope.datetime.date = new Date();
    $scope.datetime.time = moment().format('YYYY-MM-DD HH:mm:ss');

    $scope.datetime.received_date = new Date();
    $scope.datetime.received_time = moment().format('YYYY-MM-DD HH:mm:ss');

    $scope.$watch('datetime.date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.datetime.time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.$watch('datetime.received_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.datetime.received_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.$watch('lnf_item.stored_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.lnf_item.stored_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
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
    $scope.createStoredLocation = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_storedlocation.html',
            controller: 'LnfStoredLocationCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                storedlocation_list: function () {
                    return $scope.storedlocation_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.searchUser = function () {
        $scope.created_user = {};
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
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.searchGuest = function () {
        $scope.new_guest_input = 0;
        $scope.created_guest = {};
        $scope.guest = {};
    };

    $scope.createItemType = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_itemtype.html',
            controller: 'LnfItemTypeCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itemtype_list: function () {
                    return $scope.itemtype_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.createItemColor = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_itemcolor.html',
            controller: 'LnfItemColorCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itemcolor_list: function () {
                    return $scope.itemcolor_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
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

    $scope.createItemCategory = function () {
        // let audio = new Audio('/uploads/ringing.mp3');
        // audio.play();

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

    $scope.setStoredLocationList = function (list) {
        $scope.storedlocation_list = list;
    };
    $scope.setStoredLocation = function (row) {
        $scope.storedlocation = row;
    };
    $scope.setItemTypeList = function (list) {
        $scope.itemtype_list = list;
    };
    $scope.setItemCustomUserList = function (list) {
        $scope.itemcustomuser_list = list;
    };

    $scope.setItemColorList = function (list) {
        $scope.itemcolor_list = list;
    };

    $scope.setItemBrandList = function (list) {
        $scope.itembrand_list = list;
    };

    $scope.setItemCategoryList = function (list) {
        $scope.itemcategory_list = list;
    };

    $scope.setItemBrand = function (row) {
        $scope.lnf_item.brand = row;
    };
    $scope.setItemType = function (row) {
        $scope.lnf_item.itemtype = row;
    };
    $scope.setCreatedGuest = function (guest) {
        if (guest.id)
            $scope.created_guest = guest;
        else
            $scope.new_guest_input = 0;

    };
    $scope.setCreatedUser = function (user) {
        $scope.found_by = angular.copy(user);
    };

    $scope.setItemCategory = function (row) {
        $scope.lnf_item.category_id = row.id;
        $scope.lnf_item.category = row.name;
    };

    $scope.onItemCatgorySelect = function ($item, $model, $label) {
        $scope.lnf_item.category_id = $item.id;
    };

    $scope.uploadFiles = function (files) {
        if (files.length > 0) {
            $scope.lnf_item.files = $scope.lnf_item.files.concat(files);
            let reader = new FileReader();
            reader.onload = function (loadEvent) {
                $scope.lnf_item.thumbnails.push(loadEvent.target.result);
            };
            reader.readAsDataURL(files[0]);
        }

    };
    $scope.removeFile = function ($index) {
        $scope.lnf_item.files.splice($index, 1);
        $scope.lnf_item.thumbnails.splice($index, 1);
    };
    $scope.openModalImage = function (imageSrc, imageDescription) {
        let modalInstance = $uibModal.open({
            templateUrl: "modalImage.html",
            resolve: {
                imageSrcToUse: function () {
                    return imageSrc;
                },
                imageDescriptionToUse: function () {
                    return imageDescription;
                }
            },
            controller: [
                "$scope", "imageSrcToUse", "imageDescriptionToUse",
                function ($scope, imageSrcToUse, imageDescriptionToUse) {
                    $scope.ImageSrc = imageSrcToUse;
                    return $scope.ImageDescription = imageDescriptionToUse;
                }
            ]
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.getDimensionsByFilter = function (id, list) {
        return list.filter(x => x.id === id);
    };

    $scope.addItem = function () {

        if ($scope.lnf_item.itemtype && $scope.lnf_item.quantity) {
            let tags = "";
            if ($scope.lnf_item.item_tag) {
                tags = $scope.lnf_item.item_tag.map(item => item.text).join(',');
            }

            $scope.lnf_item.tags = tags.replace(/,\s*$/, "");

            console.log($scope.lnf_item);

            if ($scope.lnf_item.brand === undefined) {
                $scope.lnf_item.brand = "";
            }

            $scope.items.push($scope.lnf_item);

            $scope.lnf_item = {};
            $scope.lnf_item.quantity = 1;
            $scope.lnf_item.files = [];
            $scope.lnf_item.thumbnails = [];
            if ($scope.btn_add === "Update")
                $scope.btn_add = "Add";
        } else {
            toaster.pop('info', "Incomplete", 'You must select values.');
            return 0;
        }
        return 1;
    };
    $scope.cancelItem = function () {
        $scope.lnf_item = {};
        $scope.lnf_item.files = [];
        $scope.lnf_item.thumbnails = [];
        $scope.lnf_item.quantity = 1;
    };

    $scope.clearLnf = function () {
        $scope.lnf = {};
        $scope.location = {};
        $scope.guest = {};
        $scope.created_guest = {};
        $scope.found_by = {};
        $scope.storedlocation = {};
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
        let data = {};
        data.property_id = profile.property_id;
        data.auth_id = profile.id;
        data.lnf_type = $scope.lnf_type;
        data.location = $scope.location;

        data.lnf_time = $scope.datetime.time;
        //data.items = $scope.items;

        if ($scope.lnf_type === "Inquiry") {

        } else if ($scope.lnf_type === "Found") {
            data.stored_location_id = $scope.storedlocation.id;

            if ($scope.found_by.user_type == 1) {
                data.found_by = $scope.found_by.id;
                data.custom_user = 0;
            } else {
                data.found_by = 0;
                data.custom_user = $scope.found_by.id;
            }
        }

        data.custom_guest = $scope.new_guest_input;
        if ($scope.new_guest_input == 0) {
            if ($scope.guest)
                if ($scope.guest.id)
                    data.guest_id = $scope.guest.id;

        } else {
            data.guest_id = $scope.created_guest.id;
        }

        data.received_by = $scope.received_by.id;
        data.received_time = $scope.datetime.received_time;

        //data.createdUser = $scope.created_user.id;

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

                console.log(response);

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
                            toaster.pop('success', MESSAGE_TITLE, ' Lost&Found has been created successfully');
                            $scope.items = [];
                            $scope.location = {};
                            // emit onCreateNewLnf
                            $scope.$emit('onCreateNewLnf');
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
                        toaster.pop('success', MESSAGE_TITLE, ' Lost&Found has been created successfully');
                        $scope.items = [];
                        $scope.location = {};
                        // emit onCreateNewLnf
                        $scope.$emit('onCreateNewLnf');
                    } else {
                        item_count = item_count + 1;
                        $scope.saveLnfItem(lnf_id, item_count);
                    }
                }
            }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to created Lost&Found');
        }).finally(function () {
        });
    };

    $scope.loadFilters = function (query) {
        return $scope.tag_list.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
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

app.controller('LnfStoredLocationCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, storedlocation_list, toaster) {
    $scope.lnf = lnf;
    $scope.storedlocation_list = storedlocation_list;

    $scope.createStoredLocation = function () {

        let request = {};

        request.stored_loc = $scope.lnf.new_stored_location;
        //request.user_id = profile.id;
        //request.property_id = profile.property_id;

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
                $scope.storedlocation_list = response.data.content.list;
                $scope.setStoredLocationList($scope.storedlocation_list);
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
        $scope.setStoredLocation(row);
        $uibModalInstance.dismiss();
    }

});
app.controller('LnfItemTypeCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, itemtype_list, toaster) {
    $scope.lnf = lnf;
    $scope.itemtype_list = itemtype_list;

    $scope.createItemType = function () {

        let request = {};

        request.type = $scope.lnf.new_itemtype;
        //request.user_id = profile.id;
        //request.property_id = profile.property_id;

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
                $scope.itemtype_list = response.data.list;
                $scope.setItemTypeList($scope.itemtype_list);
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

    $scope.setType = function (type) {
        $scope.setItemType(type);
        $uibModalInstance.dismiss();
    }
});
app.controller('LnfCustomUserCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, itemcustomuser_list, toaster) {
    $scope.lnf = lnf;
    $scope.itemcustomuser_list = itemcustomuser_list;
    $scope.customuser_table = 0;
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
                $scope.itemcustomuser_list = response.data.list;
                $scope.itemcustomeruser_id = response.data.id;
                $scope.setItemCustomUserList($scope.itemcustomuser_list);

                $scope.created_user.id = response.data.id;
                $scope.setCreatedUser($scope.created_user);

                $uibModalInstance.dismiss();
            } else {
                toaster.pop('error', 'User Create Error', 'Duplicated User Name, Department');
            }
        }).catch(function (response) {
        }).finally(function () {
        });
    };
    $scope.cancel = function () {
        // $scope.created_user = {};
        // $scope.setCreatedUser($scope.created_user);
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
        $scope.created_guest.fullname = $scope.created_guest.first_name + " " + $scope.created_guest.last_name;
        //request.property_id = profile.property_id;
        if (!request.first_name)
            return;

        $http({
            method: 'POST',
            url: '/frontend/lnf/createnewguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.created_guest.id = response.data.content.id;
            $scope.setCreatedGuest($scope.created_guest);
            $uibModalInstance.dismiss();
        }).catch(function (response) {
        }).finally(function () {
        });
    };
    $scope.cancel = function () {
        $scope.created_guest = {};
        $scope.setCreatedGuest($scope.created_guest);
        $uibModalInstance.dismiss();
    };

});
app.controller('LnfItemBrandCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, itembrand_list, toaster) {
    $scope.lnf = lnf;
    $scope.itembrand_list = itembrand_list;

    $scope.createItemBrand = function () {
        let profile = AuthService.GetCredentials();

        let request = {};

        request.brand = $scope.lnf.new_itembrand;
        //request.user_id = profile.id;
        //request.property_id = profile.property_id;

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
                $scope.itembrand_list = response.data.list;
                $scope.setItemBrandList($scope.itembrand_list);
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
        $scope.setItemBrand(brand);
        $uibModalInstance.dismiss();
    }
});
app.controller('LnfItemColorCtrl', function ($scope, $uibModalInstance, $http, AuthService, lnf, itemcolor_list) {
    $scope.lnf = lnf;
    $scope.itemcolor_list = itemcolor_list;

    $scope.createItemColor = function () {
        let profile = AuthService.GetCredentials();

        let request = {};

        request.color = $scope.lnf.new_itemcolor;
        //request.user_id = profile.id;
        //request.property_id = profile.property_id;

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

            $scope.setItemColorList($scope.itemcolor_list);
        }).catch(function (response) {
        }).finally(function () {

        });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('LnfItemCategoryCtrl', function ($scope, $uibModalInstance, $http, AuthService, itemcategory_list, toaster) {
    $scope.list = itemcategory_list;

    let profile = AuthService.GetCredentials();

    $scope.category = {};
    $scope.category.property_id = profile.property_id;


    $scope.createItem = function () {
        if (!$scope.category.name) {
            toaster.pop('info', 'Please select Category Name.');
            return;
        }

        if (!($scope.category.notify_job_role_id > 0)) {
            toaster.pop('info', 'Please select Notify User.');
            return;
        }

        if ($scope.category.notify_type.length < 1) {
            toaster.pop('info', 'Please select at least a notify type.');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/lnf/createitemcategory',
            data: $scope.category,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            if (response.data.id > 0) {
                $scope.list = response.data.list;
                $scope.category = {};
                $scope.setItemCategoryList(response.data.list);
            } else {
                toaster.pop('error', 'Category Creation Error', 'Duplicated Category Name');
            }
        }).catch(function (response) {
        }).finally(function () {

        });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.setItem = function (row) {
        $scope.setItemCategory(row);
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
            $scope.supplier_list = response.data;
            $scope.setSupplierList(response.data);
        }).catch(function (response) {
        }).finally(function () {

        });
    }
});
