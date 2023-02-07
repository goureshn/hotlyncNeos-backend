app.controller('LNFController', function ($scope, $rootScope, $http, $window, $sce, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, $aside, liveserver, hotkeys) {
    let MESSAGE_TITLE = 'Lost&Found Status List';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 80;
    $scope.tab_height = $window.innerHeight + 10;
    $scope.tab_height1 = $window.innerHeight - 200;

    let profile = AuthService.GetCredentials();
    let client_id = profile.client_id;

    let dept_list = [];

    $scope.selectedTickets = [];

    $scope.search_tags = [];
    $http.get('/frontend/lnf/getSearchTagsAll?property_id=' + profile.property_id)
        .then(function (response) {
            // console.log(response.data);

            let res_search_tags = response.data.datalist;
            for (let i in res_search_tags)
                $scope.search_tags.push(res_search_tags[i]);

        });

    $http.get('/list/user?client_id=' + client_id)
        .then(function (response) {
            $scope.user_list = response.data;
        });

    $scope.isLoading = false;
    $scope.datalist = [];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.lnf_statuses = [
        'Available',
        'Matched',
        'Returned',
        'Discarded',
        'Disposed',
        'Surrendered',
    ];

    $scope.lnf_status_list = [
        {name: 'All', selected: false},
        {name: 'Available', selected: false},
        {name: 'Matched', selected: false},
        {name: 'Returned', selected: false},
        {name: 'Discarded', selected: false},
        {name: 'Disposed', selected: false},
        {name: 'Surrendered', selected: false},
    ];

    $scope.filters = {filtername: "", filtervalue: null};
    $scope.onChangeStatusFilter = function (status) {
        if (status.name === 'All') {
            let selected = status.selected;
            $scope.lnf_status_list.forEach(item => item.selected = selected);
        }

        $scope.filters.filtername = 'status_name';
        $scope.filters.filtervalue = $scope.lnf_status_list.filter(item => item.selected).map(item => item.name).join(',');

        $scope.pageChanged();
    };

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };
    $scope.data = {};

    angular.element('#dateranger').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger').focus();
        $scope.dateFilter = angular.element('#dateranger');

        $scope.dateFilter.on('apply.daterangepicker', function (ev, picker) {
            $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
            $scope.pageChanged();
        });
    };

    $scope.pageChanged = function (tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;
        /////////////////////
        let request = {};

        if (tableState !== undefined) {
            // $scope.tableState = tableState;
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filters = JSON.stringify($scope.filters);
        request.filter_tags = JSON.stringify($scope.filter_tags);

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        let profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.lnf_type = 'Found';

        //console.log(request);
        $http({
            method: 'POST',
            url: '/frontend/lnf/getLnfAllItems', //getLnf
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                // console.log(response.data);
                $scope.datalist = response.data.datalist.map(function (item) {
                    if (item.found_by > 0)
                        item.found_fullname = item.common_firstname + " " + item.common_lastname + " - Common User";
                    else
                        item.found_fullname = item.custom_firstname + " " + item.custom_lastname + " - Custom User";

                    item.details = [];
                    if (item.status_name === 'Surrendered') {
                        item.details[0] = 'Date : ' + moment(item.surrendered_date).format('YYYY-MM-DD');
                        item.details[1] = 'Department : ' + item.surrendered_department;
                        item.details[2] = 'To : ' + item.surrendered_to;
                        item.details[3] = 'Location : ' + item.surrendered_location;
                    }

                    if (item.status_name === 'Returned') {
                        item.details[0] = 'Date : ' + moment(item.return_date).format('YYYY-MM-DD');
                        item.details[1] = 'Mode : ' + item.return_mode;
                        if (item.return_mode === 'Guest In Person') {
                            item.details[2] = 'Guest Name : ' + item.return_guest_name;
                        }
                        if (item.return_mode === 'Guest Representative') {
                            item.details[2] = 'ID No : ' + item.staff_id_no;
                            item.details[3] = 'Name : ' + item.staff_name;
                            item.details[4] = 'Email : ' + item.staff_email;
                            item.details[5] = 'Contact No : ' + item.staff_contact_no;
                        }

                        if (item.return_mode === 'Courier') {
                            item.details[2] = 'Company : ' + item.courier_company;
                            item.details[3] = 'AWB : ' + item.courier_awb;
                        }
                    }

                    if (item.status_name === 'Discarded' || item.status_name === 'Disposed') {
                        item.details[0] = 'Date : ' + moment(item.discarded_date).format('YYYY-MM-DD');
                        item.details[1] = 'By : ' + item.discarded_by;
                    }

                    item.ticket_no = $scope.getTicketNumber(item);

                    let images = item.images;
                    if (images)
                        item.images_arr = item.images.split("|");
                    else
                        item.images_arr = [];

                    return item;
                });

                if (!$scope.datalist || $scope.datalist.length < 1) {
                    $scope.data.datalist = [];
                    return;
                }

                $scope.paginationOptions.totalItems = response.data.totalcount;
                if ($scope.paginationOptions.totalItems < 1)
                    $scope.paginationOptions.countOfPages = 0;
                else
                    $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                $scope.data.datalist = $scope.datalist;
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    // $scope.pageChanged();

    $scope.loadFiltersValue = function (value, query) {
        let search_items = [];
        for (let i = 0; i < $scope.search_tags.length; i++) {
            if ($scope.search_tags[i].toLowerCase().indexOf(query.toLowerCase()) !== -1)
                search_items.push($scope.search_tags[i]);
        }

        return search_items;
    };

    $scope.filterTags = {text: []};
    $scope.filter_tags = [];
    $scope.searchFilter = function () {
        $scope.filter_tags = [];
        for (let j = 0; j < $scope.filterTags.text.length; j++) {
            $scope.filter_tags.push($scope.filterTags.text[j].text);
        }

        $scope.pageChanged();
    };

    function checkSelectStatus() {
        for (let j = 0; j < $scope.datalist.length; j++) {
            let ticket = $scope.datalist[j];
            let index = -1;
            for (let i = 0; i < $scope.selectedTickets.length; i++) {
                if (ticket.id === $scope.selectedTickets[i].item_id) {
                    index = i;
                    break;
                }
            }
            ticket.active = index >= 0;
        }
    }

    $scope.getProcess = function (row) {
        if (row.total < 1)
            return 0;
        return row.completed * 100 / row.total;
    };

    $scope.onPrevPage = function () {
        if ($scope.paginationOptions.numberOfPages <= 1)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
        $scope.isLoading = true;
        $scope.pageChanged();
    };

    $scope.onNextPage = function () {
        if ($scope.paginationOptions.totalItems < 1)
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if ($scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
        $scope.isLoading = true;
        $scope.pageChanged();
    };

    $scope.onSelectTicket = function (ticket) {
        $timeout(function () {
            let index = -1;
            for (let i = 0; i < $scope.selectedTickets.length; i++) {
                if (ticket.item_id == $scope.selectedTickets[i].item_id) {
                    index = i;
                    break;
                }
            }

            if (index < 0)    // not selected
            {
                $scope.selectedTickets.push(angular.copy(ticket));
            } else {
                $scope.selectedTickets.splice(index, 1);
                checkSelectStatus();
            }

        }, 10);
    };

    $scope.getTicketNumber = function (row) {
        let prefix = 'F';
        if (row.lnf_type === 'Found')
            prefix = 'F';
        if (row.lnf_type === 'Inquiry')
            prefix = 'I';

        return sprintf('%s%05d', prefix, row.item_id)
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

    $scope.$on('onCreateNewLnf', function (event, args) {
        // $scope.firstTab();
        $scope.pageChanged();
        $scope.$broadcast('refresh_data', {});
    });

    $scope.onChangeStatus = function (attr, val, lnf_item) {
        //change status
        if (val === "FOUND" || val === "LOST")
            return;
        if (val === 'Returned') {
            showReturnStatusDialog(val, lnf_item);
            return;
        }

        if (val === 'Matched') {
            showMatchedStatusDialog(val, lnf_item);
            return;
        }

        if (val === 'Surrendered') {
            showSurrenderedStatusDialog(val, lnf_item);
            return;
        }

        if (val === 'Discarded' || val === 'Disposed') {
            showDiscardedStatusDialog(val, lnf_item);
            return;
        }

        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_status_change.html',
            controller: 'LnfStatusChgCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return lnf_item;
                },
                user_list: function () {
                    return $scope.user_list;
                },
                lnf_status: function () {
                    return {name: val};
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    function showReturnStatusDialog(val, lnf_item) {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_status_to_return_change.html',
            controller: 'LnfReturnStatusChgCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return lnf_item;
                },
                user_list: function () {
                    return $scope.user_list;
                },
                guest_list: function () {
                    return $scope.guest_list;
                },
                lnf_status: function () {
                    return {name: val};
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    function showMatchedStatusDialog(val, lnf_item) {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_status_to_matched_change.html',
            controller: 'LnMatchedStatusChgCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return lnf_item;
                },
                user_list: function () {
                    return $scope.user_list;
                },
                guest_list: function () {
                    return $scope.guest_list;
                },
                lnf_status: function () {
                    return {name: val};
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    function showSurrenderedStatusDialog(val, lnf_item) {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_status_to_surrendered_change.html',
            controller: 'LnfSurrenderedStatusChgCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return lnf_item;
                },
                user_list: function () {
                    return $scope.user_list;
                },
                lnf_status: function () {
                    return {name: val};
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    function showDiscardedStatusDialog(val, lnf_item) {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_status_to_discarded_change.html',
            controller: 'LnfDiscardedStatusChgCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return lnf_item;
                },
                user_list: function () {
                    return $scope.user_list;
                },
                lnf_status: function () {
                    return {name: val};
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.filter_lnf_status = function (row) {
        return function (item) {
            if (row.status_name === 'Matched' && (item !== 'Matched' && item !== 'Returned')) {
                return false;
            } else if (row.status_name === 'Available' && (item === 'Matched')) {
                return false;
            }

            return true;
        };
    };

    hotkeys.add({
        combo: 'c',
        description: 'Open Create Dialog',
        callback: function () {
            $scope.onCreateItem();
        }
    });

    $scope.onCreateItem = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_create_found_dialog.html',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                lnf_type: function () {
                    return 'Found';
                },
            },
            controller: 'LnfCreateDialogCtrl'
        });

        modalInstance.result.then(function (selectedItem) {

        }, function () {

        });
    }
});

app.controller('LNFStatusDialogCtrl', function ($scope, $http, AuthService, $uibModalInstance, complaint, userlist) {
    $scope.complaint = complaint;

    if (!$scope.complaint.reminder_time)
        $scope.complaint.reminder_time = moment().format("YYYY-MM-DD HH:mm:ss");

    $scope.complaint.reminder_flag = $scope.complaint.reminder_flag == 1;

    $scope.user_tags = [];
    let request = {};
    request.userids = JSON.parse($scope.complaint.reminder_ids);

    $http({
        method: 'POST',
        url: '/userlistwithids',
        data: request,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    }).then(function (response) {
        // console.log(response);
        $scope.user_tags = response.data;

    }).catch(function (response) {
    })
        .finally(function () {

        });

    $scope.ok = function () {
        let reminder_ids = [];
        for (let i = 0; i < $scope.user_tags.length; i++) {
            reminder_ids.push($scope.user_tags[i].id);
        }

        $scope.complaint.reminder_ids = JSON.stringify(reminder_ids);
        $scope.complaint.reminder_flag = $scope.complaint.reminder_flag ? 1 : 0;

        $uibModalInstance.close($scope.complaint);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.loadUsernameFilters = function (query) {
        return userlist.filter(function (type) {
            return type.wholename.toLowerCase().indexOf(query.toLowerCase()) !== -1;
        });
    };

    $scope.$watch('complaint.datetime', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        // console.log(newValue);
        $scope.complaint.reminder_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        let i;
        if ($view === 'day') {
            let activeDate = moment().subtract('days', 1);
            for (i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        } else if ($view === 'minute') {
            let activeDate = moment().subtract('minute', 5);
            for (i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

});

app.controller('LnfStatusChgCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, AuthService, lnf, user_list, lnf_status) {
    $scope.lnf = lnf;
    $scope.user_list = user_list;
    $scope.lnf_status = lnf_status;
    $scope.custom_action_user = 0;
    $scope.created_user = {};
    for (let i = 0; i < $scope.user_list.length; i++) {
        $scope.user_list[i].fullname = "";
        if ($scope.user_list[i].first_name)
            $scope.user_list[i].fullname = $scope.user_list[i].fullname + $scope.user_list[i].first_name;
        if ($scope.user_list[i].last_name)
            $scope.user_list[i].fullname = $scope.user_list[i].fullname + " " + $scope.user_list[i].last_name;
    }

    $scope.init = function () {
    };

    $scope.saveChangeStatus = function () {
        let profile = AuthService.GetCredentials();
        let request = {};
        request.user_id = profile.id;
        request.custom_user = $scope.custom_action_user;
        request.action_by = $scope.action_by.id;
        request.created_user = $scope.created_user;
        request.item_id = $scope.lnf.item_id;
        request.status = $scope.lnf_status.name;

        // console.log(request);
        if ($scope.action_by.fullname === "" && !request.created_user.first_name)
            return;
        //request.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/lnf/statusChange',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            // console.log(response);
            //$scope.history_items = response.data.datalist;
            //$scope.setHistoryItem($scope.history_items);
            $uibModalInstance.dismiss();
            $scope.pageChanged();
            toaster.pop('info', "Successful", 'Status changed of this item.');
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Status not changed of this item.');
        })
            .finally(function () {
            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
        $scope.$emit('onCreateNewLnf');
    };
    $scope.onUserSelect = function ($item, $model, $label) {
        $scope.action_by = $item;
    };
    $scope.action_by = {fullname: ""};
    $scope.createCustomUser = function () {

        $scope.action_by.fullname = "";
        $scope.custom_action_user = 1;
    };
    $scope.searchUser = function () {
        $scope.custom_action_user = 0;
        $scope.created_user = {};
    };
    $scope.createCustomUserModal = function () {
        $scope.custom_user = 1;
        $scope.created_user = {};
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_itemcustomuser.html',
            controller: 'LnfActionCustomerUserCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                created_user: function () {
                    return $scope.created_user;
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

app.controller('LnfReturnStatusChgCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, AuthService, lnf, user_list, lnf_status, guest_list) {
    $scope.lnf = lnf;
    $scope.user_list = user_list;
    $scope.lnf_status = lnf_status;

    $scope.mode_list = [
        'Guest In Person',
        'Guest Representative',
        'Courier'
    ];

    $scope.model = {};

    $scope.model.item_id = $scope.lnf.item_id;
    $scope.model.return_mode = $scope.mode_list[0];
    $scope.model.return_date = new Date();
    $scope.model.guest_id = 0;
    $scope.model.guest_name = '';
    $scope.model.comment = '';

    function getGuestList() {
        let profile = AuthService.GetCredentials();
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

    getGuestList();


    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.model.opened = true;
    };

    $scope.dateOptions = {
        // formatYear: 'yy',
        startingDay: 1,
        // dateDisabled: disabled,
        class: 'datepicker'
    };

    // function disabled(data) {
    //     let date = data.date;
    //     let sel_date = moment(date).format('YYYY-MM-DD');
    //     let disabled = true;
    //     if (moment().add(1, 'days').format('YYYY-MM-DD') <= sel_date && sel_date <= $scope.guest.departure)
    //         disabled = false;
    //     else
    //         disabled = true;

    //     mode = data.mode;
    //     return mode === 'day' && disabled;
    // }

    $scope.select = function (date) {
        // console.log(date);

        $scope.model.opened = false;
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.model.guest_id = $item.guest_id;
    };

    $scope.saveChangeStatus = function () {

        $scope.model.return_date = moment($scope.model.return_date).format('YYYY-MM-DD');
        $http({
            method: 'POST',
            url: '/frontend/lnf/savereturn',
            data: $scope.model,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            // console.log(response);
            $uibModalInstance.dismiss();
            $scope.$emit('onCreateNewLnf');
            toaster.pop('info', "Successful", 'Status changed of this item.');
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Status not changed of this item.');
        })
            .finally(function () {
            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
        $scope.$emit('onCreateNewLnf');
    };
});

app.controller('LnMatchedStatusChgCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, AuthService, lnf, user_list, lnf_status, guest_list) {
    $scope.lnf = lnf;
    $scope.user_list = user_list;
    $scope.lnf_status = lnf_status;

    $scope.model = {};

    function getNonMatchedItemList() {
        return $http({
            method: 'POST',
            url: '/frontend/lnf/nonmatchitemlist',
            data: $scope.lnf,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.non_matched_list = response.data;
        });
    }

    getNonMatchedItemList();

    $scope.onItemSelect = function ($item, $model, $label) {
        $scope.lnf.matched_id = $item.id;
    };

    $scope.saveChangeStatus = function () {
        $http({
            method: 'POST',
            url: '/frontend/lnf/savematcheditem',
            data: $scope.lnf,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            // console.log(response);
            $uibModalInstance.dismiss();
            $scope.$emit('onCreateNewLnf');
            toaster.pop('info', "Successful", 'Status changed of this item.');
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Status not changed of this item.');
        })
            .finally(function () {
            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
        $scope.$emit('onCreateNewLnf');
    };
});

app.controller('LnfSurrenderedStatusChgCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, AuthService, lnf, user_list, lnf_status) {
    $scope.lnf = lnf;
    $scope.user_list = user_list;
    $scope.lnf_status = lnf_status;

    let department_tag = [];
    let to_tag = [];
    let location_tag = [];

    function getDataList() {
        $http.get('/list/lnf_surrendered_data')
            .then(function (response) {
                department_tag = response.data.department_tag;
                to_tag = response.data.to_tag;
                location_tag = response.data.location_tag;
            });
    }

    getDataList();

    $scope.model = {};

    $scope.model.item_id = $scope.lnf.item_id;
    $scope.model.surrendered_date = moment().format('YYYY-MM-DD HH:mm:ss');
    $scope.model.surrendered_time = moment().format('YYYY-MM-DD HH:mm:ss');
    $scope.model.department = [];
    $scope.model.to = [];
    $scope.model.location = [];
    $scope.model.comment = '';

    $scope.saveChangeStatus = function () {
        let profile = AuthService.GetCredentials();

        let department_tag = $scope.model.department.map(function (item) {
            return item.text;
        }).join(',');

        let to_tag = $scope.model.to.map(function (item) {
            return item.text;
        }).join(',');

        let location_tag = $scope.model.location.map(function (item) {
            return item.text;
        }).join(',');

        // console.log(department_tag);

        let request = {};
        request.item_id = lnf.item_id;
        request.surrendered_date = $scope.model.surrendered_time;
        request.department_tag = department_tag;
        request.to_tag = to_tag;
        request.location_tag = location_tag;
        request.comment = $scope.model.comment;

        $http({
            method: 'POST',
            url: '/frontend/lnf/savesurrendered',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            // console.log(response);
            $uibModalInstance.dismiss();
            $scope.$emit('onCreateNewLnf');
            toaster.pop('info', "Successful", 'Status changed of this item.');
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Status not changed of this item.');
        })
            .finally(function () {
            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
        $scope.$emit('onCreateNewLnf');
    };

    $scope.$watch('model.surrendered_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        // console.log(newValue);
        $scope.model.surrendered_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.loadDepartmentFilter = function (query) {
        return department_tag.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    };

    $scope.loadToFilter = function (query) {
        return to_tag.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    };

    $scope.loadLocationFilter = function (query) {
        return location_tag.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    }
});

app.controller('LnfDiscardedStatusChgCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, AuthService, lnf, user_list, lnf_status) {
    $scope.lnf = lnf;
    $scope.user_list = user_list;
    $scope.lnf_status = lnf_status;

    let profile = AuthService.GetCredentials();

    let user_tag = [];

    function getDataList() {
        $http.get('/list/lnf_discarded_data')
            .then(function (response) {
                user_tag = response.data.user_tag;
            });
    }

    getDataList();

    $scope.model = {};

    $scope.model.item_id = $scope.lnf.item_id;
    $scope.model.discarded_by = [profile.first_name + ' ' + profile.last_name];
    $scope.model.discarded_date = moment().format('YYYY-MM-DD HH:mm:ss');
    $scope.model.discarded_time = moment().format('YYYY-MM-DD HH:mm:ss');
    $scope.model.comment = '';

    $scope.saveChangeStatus = function () {

        let discarded_by_list = $scope.model.discarded_by.map(function (item) {
            return item.text;
        }).join(',');

        let request = {};
        request.item_id = lnf.item_id;
        request.discarded_date = $scope.model.discarded_time;
        request.discarded_by_tag = discarded_by_list;
        request.comment = $scope.model.comment;
        request.status_name = $scope.lnf_status.name;

        $http({
            method: 'POST',
            url: '/frontend/lnf/savediscarded',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            // console.log(response);
            $uibModalInstance.dismiss();
            $scope.$emit('onCreateNewLnf');
            toaster.pop('info', "Successful", 'Status changed of this item.');
        }).catch(function (response) {
            toaster.pop('info', "Failed", 'Status not changed of this item.');
        })
            .finally(function () {
            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
        $scope.$emit('onCreateNewLnf');
    };

    $scope.$watch('model.discarded_date', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        // console.log(newValue);
        $scope.model.discarded_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.loadUserFilter = function (query) {
        return user_tag.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) !== -1)
                return item;
        });
    }
});
