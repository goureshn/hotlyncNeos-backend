app.controller('DeviceSettingController', function ($scope, $window, $http, $uibModal, $timeout, $interval, $compile, AuthService, toaster, GuestService) {
    var profile = AuthService.GetCredentials();

    $scope.deviceProfileInfo = {
        tableState: {
            pagination: {}
        },
        paginationOptions: {
            pageNumber: 0,
            pageSize: 15,
            sort: 'asc',
            field: 'sdp.id',
            totalItems: 0,
            numberOfPages : 1,
            countOfPages: 1
        },
        oldPaginationOptions: {
            pageNumber: 0,
            pageSize: 15,
            sort: 'asc',
            field: 'sdp.id',
            totalItems: 0,
            numberOfPages : 1,
            countOfPages: 1
        },
        searchText: "",
        isLoading: false
    };

    $scope.deviceInfo = {
        tableState: {
            pagination: {}
        },
        paginationOptions: {
            pageNumber: 0,
            pageSize: 15,
            sort: 'asc',
            field: 'lg.id',
            totalItems: 0,
            numberOfPages : 1,
            countOfPages: 1
        },
        oldPaginationOptions: {
            pageNumber: 0,
            pageSize: 15,
            sort: 'asc',
            field: 'lg.id',
            totalItems: 0,
            numberOfPages : 1,
            countOfPages: 1
        },
        searchText: "",
        isLoading: false
    };

    $scope.deviceProfileList = [];
    $scope.selected_row = null;

    $scope.status_list = ['All', 'Online', 'Offline', 'Disabled'];
    $scope.device_status = 'All';

    $scope.getLocationgroupList = function (tableState) {
        $scope.deviceProfileInfo.isLoading = true;

        if( tableState != undefined )
        {
            $scope.deviceProfileInfo.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.deviceProfileInfo.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.deviceProfileInfo.paginationOptions.pageSize = pagination.number || $scope.deviceProfileInfo.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.deviceProfileInfo.paginationOptions.field = tableState.sort.predicate;
            $scope.deviceProfileInfo.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        request.device_status = $scope.device_status;
        request.page = $scope.deviceProfileInfo.paginationOptions.pageNumber;
        request.pagesize = $scope.deviceProfileInfo.paginationOptions.pageSize;
        request.field = $scope.deviceProfileInfo.paginationOptions.field;
        request.sort = $scope.deviceProfileInfo.paginationOptions.sort;

        request.searchText = $scope.deviceProfileInfo.searchText;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getsettingdeviceprofilelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.deviceProfileList = response.data.content;

                $scope.deviceProfileInfo.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.deviceProfileInfo.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.deviceProfileInfo.paginationOptions.totalItems - 1) / $scope.deviceProfileInfo.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.deviceProfileInfo.tableState.pagination.numberOfPages = numberOfPages;

                $scope.deviceProfileInfo.paginationOptions.countOfPages = numberOfPages;

                $scope.selected_row = null;

            }).catch(function(response) {
                console.error('taskgroups error', response.status, response.data);
            })
            .finally(function() {
                $scope.deviceProfileInfo.isLoading = false;
            });
    };

    $scope.onRefreshLocationGroup = function() {
        $scope.deviceProfileInfo.searchText = "";
        $scope.deviceProfileInfo.paginationOptions = angular.copy($scope.deviceProfileInfo.oldPaginationOptions);

        $scope.getLocationgroupList();
    };

    $scope.onLocationGroupRow = function(row) {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_delete.html',
            scope: $scope,
            resolve: {
                name: function () {
                    return row.name;
                }
            },
            controller: function ($scope, $uibModalInstance, name) {
                $scope.title = "Device Profile";
                $scope.name = name;
                $scope.onOk = function (e) {
                    $uibModalInstance.close('ok');
                };
                $scope.onCancel = function (e) {
                    $uibModalInstance.dismiss();
                };
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' ) {
                $scope.isLoading = false;

                let request = {};
                request.delete_id = row.id;

                $http({
                    method: 'POST',
                    url: '/frontend/guestservice/deletesettingdeviceprofilerow',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
                        $scope.getLocationgroupList();
                    }).catch(function(response) {
                    console.error('taskgroups error', response.status, response.data);
                })
                    .finally(function() {
                        $scope.isLoading = false;
                    });
            }
        }, function () {

        });
    };

    $scope.onAddProfile = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_deviceprofile_create.html',
            controller: 'DeviceSettingGreateController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
            }
        });

        modalInstance.result.then(function (ret) {
            if (ret == 'ok') {
                $scope.getLocationgroupList();
            }
        }, function () {
        });
    };

    $scope.editGroupRow = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_locationgroup_edit.html',
            controller: 'LocationgroupEditController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                row: function () {
                    return $scope.selected_row;
                }
            }
        });

        modalInstance.result.then(function (ret) {
            if (ret == 'ok') {
                $scope.getLocationgroupList();
            }
        }, function () {
        });
    };

    $scope.onEditGroupRow = function (row) {

        if ($scope.selected_row == null || $scope.selected_row.id != row.id) {
            $scope.onSelectGroupRow(row, true);
        } else {
            $scope.editGroupRow();
        }

    };



    $scope.onSelectGroupRow = function(row, bEdit = false) {
        $scope.selected_row = angular.copy(row);
        // let request = {};
        // request.group_id = row.id;
        //
        // $http({
        //     method: 'POST',
        //     url: '/frontend/guestservice/getsettinglocationgroupdetaillist',
        //     data: request,
        //     headers: {'Content-Type': 'application/json; charset=utf-8'}
        // })
        //     .success(function(data, status, headers, config) {
        //         $scope.selected_row.detail_list = data;
        //
        //         $scope.selected_row.detail_list.forEach(item => {
        //             let str_val = item.locations.selected_member.map(sub_item => {
        //                 return sub_item.name;
        //             }).join(", ");
        //
        //             item.locations.str_selected_member = str_val.length > 250 ? str_val.slice(0, 247) + "..." : str_val;
        //         });
        //
        //         if (bEdit == true) {
        //             $scope.editGroupRow();
        //         }
        //     })
        //     .error(function(data, status, headers, config) {
        //         toaster.pop('error', 'Error', 'Error');
        //     })
        //     .finally(function() {
        //         $scope.isLoading = false;
        //     });
    };
});


