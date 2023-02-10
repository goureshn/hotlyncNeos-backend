app.controller('ChatTemplateListController', function ($scope, $rootScope, $http, $aside, $timeout, $interval, $uibModal, $window, $stateParams, toaster, hotkeys, AuthService) {
    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 18,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.selectedRow = null;
    $scope.editRowId = -1;
    $scope.templatelist_height = $window.innerHeight - 110;
    $scope.templatelist = [];

    $scope.selectedTypeList = [];
    $scope.detailTemplate = "";

    let tempTemplate = '';
    let tempChatName = '';
    let tempRoomTypes = [];
    let tempVips = [];

    $scope.filterInfo = {
        typeId: '',
        vipId: '',
        chatName: '',
        searchValue: ''
    };

    $scope.vipLevelList = [];
    $scope.roomTypeList = [];
    $scope.chatNameList = [];


    let getRoomTypeInfo = function (item) {

        let room_type_info = "";
        if (item.room_types.length > 0) {
            room_type_info = item.room_types.map(typeItem => {
                return typeItem.type;
            }).join(', ');

            if (room_type_info.length > 120) {
                room_type_info = room_type_info.substring(0, 117) + "...";
            }

            return room_type_info;
        }

        return room_type_info;
    };

    let getVipInfo = function (item) {

        let vip_info = "";
        if (item.vips.length > 0) {
            vip_info = item.vips.map(vipItem => {
                return vipItem.name;
            }).join(', ');

            if (vip_info.length > 120) {
                vip_info = vip_info.substring(0, 117) + "...";
            }

            return vip_info;
        }

        return vip_info;
    };

    $scope.getInitListForChat = function () {
        $http({
            method: 'GET',
            url: '/frontend/guestservice/getinitinfofortemplate',
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.vipLevelList = response.data.vipLevelList;
                $scope.roomTypeList = response.data.typeList;
                $scope.chatNameList = response.data.chatNameList;
            }).catch(function (response) {
        })
            .finally(function () {
            });
    };


    $scope.getJoinTypes = function (roomTypes) {
        return roomTypes.map(item => {
            return item.room_type;
        }).join(", ");
    };

    $scope.onSelectRoomType = function ($item) {
        $scope.filterInfo.typeId = $item.id;

        $scope.onSearchResult();
    };

    $scope.onSelectVipLevel = function ($item) {
        $scope.filterInfo.vipId = $item.id;

        $scope.onSearchResult();
    };

    $scope.getInitListForChat();

    $scope.onSearchResult = function () {
        $scope.paginationOptions.pageNumber = 0;
        $scope.paginationOptions.totalItems = 0;
        $scope.paginationOptions.numberOfPages = 1;
        $scope.paginationOptions.countOfPages = 1;

        $scope.onSearchTemplates();
    };

    $scope.onEditOrSave = function (selectedRow) {
        if ($scope.editRowId === -1) {
            $scope.editRowId = selectedRow.id;
            tempTemplate = selectedRow.template;
            tempChatName = selectedRow.name;
            tempRoomTypes = angular.copy(selectedRow.room_types);
            tempVips = angular.copy(selectedRow.vips);
        } else {
            // save
            let request = {};
            request.template = selectedRow.template;
            request.name = selectedRow.name;
            request.editId = selectedRow.id;
            request.roomTypeIds = selectedRow.room_types.length > 0 ? selectedRow.room_types.map(item => {
                return item.id;
            }) : [];

            request.vipIds = selectedRow.vips.length > 0 ? selectedRow.vips.map(item => {
                return item.id;
            }) : [];

            $scope.isLoading = true;
            $http({
                method: 'POST',
                url: '/frontend/guestservice/updatetemplaterow',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function (response) {
                    if (response.data.success === true) {

                        toaster.pop('success', 'Notice', 'Successfully updated!');

                        selectedRow.room_type_info = getRoomTypeInfo(selectedRow);
                        selectedRow.vip_info = getVipInfo(selectedRow);

                        $scope.getInitListForChat();

                        $scope.editRowId = -1;
                    } else {
                        toaster.pop('error', 'Error', response.data.message);
                    }
                }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
                .finally(function () {
                    $scope.isLoading = false;
                });
        }
    };

    $scope.onDeleteRow = function (deleteId) {
        let request = {};
        request.deleteId = deleteId;

        $scope.isLoading = true;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/deletetemplaterow',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                if (response.data.success === true) {

                    toaster.pop('success', 'Notice', 'Successfully deleted!');
                    $scope.filterInfo.chatName = '';
                    $scope.filterInfo.vipName = '';
                    $scope.filterInfo.typeName = '';
                    $scope.filterInfo.searchValue = '';

                    $scope.onSearchResult();
                } else {
                    toaster.pop('error', 'Error', response.data.message);
                }
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.onDeleteSelectedRow = function (selectedRow) {
        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat_template/modal/confirm_delete.html',
            backdrop: 'static',
            size: 'sm',
            scope: $scope,
            controller: function ($scope, $uibModalInstance) {
                $scope.onYes = function () {
                    $uibModalInstance.close('Yes');
                };
                $scope.onNo = function () {
                    $uibModalInstance.close('No');
                };
            },
        });

        modalInstance.result.then(function (result) {
            if (result === 'Yes') {
                $scope.editRowId = -1;
                $scope.onDeleteRow(selectedRow.id);
            }
        }, function () {

        });
    };

    $scope.onDeleteOrCancel = function (selectedRow) {
        if ($scope.editRowId === -1) {
            // delete
            let modalInstance = $uibModal.open({
                templateUrl: 'tpl/guestservice/chat_template/modal/confirm_delete.html',
                backdrop: 'static',
                size: 'sm',
                scope: $scope,
                controller: function ($scope, $uibModalInstance) {
                    $scope.onYes = function () {
                        $uibModalInstance.close('Yes');
                    };
                    $scope.onNo = function () {
                        $uibModalInstance.close('No');
                    };
                },
            });

            modalInstance.result.then(function (result) {
                if (result === 'Yes') {
                    $scope.onDeleteRow(selectedRow.id);
                }
            }, function () {

            });
        } else {
            // cancel
            $scope.editRowId = -1;
            $scope.selectedRow.template = tempTemplate;
            $scope.selectedRow.name = tempChatName;
            $scope.selectedRow.room_types = tempRoomTypes;
            $scope.selectedRow.vips = tempVips;
            tempTemplate = '';
        }
    };

    $scope.onSearchByItems = function (item) {
        if (item === 'type') {
            if (!$scope.filterInfo.typeName) {
                $scope.onSearchResult();
            }
        } else if (item === 'vip') {
            if (!$scope.filterInfo.vipName) {
                $scope.onSearchResult();
            }
        }
    };

    $scope.onRoomTypeFilter = function (query) {
        return $scope.roomTypeList.filter(item => item.type.toLowerCase().includes(query.toLowerCase()));
    };

    $scope.onVipLevelFilter = function (query) {
        return $scope.vipLevelList.filter(item => item.name.toLowerCase().includes(query.toLowerCase()));
    };

    $scope.onSearchTemplates = function (tableState) {

        if (tableState !== undefined) {
            $scope.tableState = tableState;
            let pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        let request = {};
        let profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pageSize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        request.typeId = $scope.filterInfo.typeName ? $scope.filterInfo.typeId : 0;
        request.vipId = $scope.filterInfo.vipName ? $scope.filterInfo.vipId : 0;
        request.chatName = $scope.filterInfo.chatName;
        request.searchValue = $scope.filterInfo.searchValue;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getchattemplatelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.templatelist = response.data.templatelist;

                $scope.templatelist = $scope.templatelist.map(item => {

                    item.room_type_info = getRoomTypeInfo(item);
                    item.vip_info = getVipInfo(item);

                    return item;
                });

                $scope.paginationOptions.totalItems = response.data.totalCount;

                if (tableState !== undefined) {
                    if ($scope.paginationOptions.totalItems < 1)
                        tableState.pagination.numberOfPages = 0;
                    else
                        tableState.pagination.numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                    $scope.paginationOptions.countOfPages = tableState.pagination.numberOfPages;
                } else {
                    $scope.paginationOptions.totalItems = response.data.totalCount;
                    $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
                }

                $scope.selectedRow = null;
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.onSelectRow = function (row) {
        if ($scope.editRowId !== -1 && $scope.editRowId !== row.id) {
            toaster.pop('warning', 'Notice', 'Please save or cancel the current row');
        } else {
            $scope.selectedRow = row;
        }
    };

    $scope.onPrevPage = function () {
        if ( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.onSearchTemplates();
    };

    $scope.onNextPage = function () {
        if ($scope.paginationOptions.totalItems < 1)
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = Math.floor(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if ($scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.onSearchTemplates();
    };

    $scope.onAddChatTemplate = function () {

        if ($scope.editRowId !== -1) {
            toaster.pop('warning', 'Notice', 'Please save or cancel the current row');
            return;
        }

        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat_template/modal/add_template.html',
            backdrop: 'static',
            size: 'lg',
            scope: $scope,
            controller: 'AddTemplateController',
        });

        modalInstance.result.then(function (result) {
            if (result == null) {

                toaster.pop('success', 'Notice', 'Successfully added');
                $scope.filterInfo.chatName = '';
                $scope.filterInfo.vipName = '';
                $scope.filterInfo.typeName = '';
                $scope.filterInfo.searchValue = '';

                $scope.onSearchResult();
                $scope.getInitListForChat();

            } else {
                let updatedId = result.id;
                let template = result.template;

                for (let i = 0; i < $scope.templatelist.length; i++) {
                    if ($scope.templatelist[i].id === updatedId) {
                        $scope.templatelist[i].template = template;
                        break;
                    }
                }

                toaster.pop('success', 'Notice', 'Successfully updated');
            }
        }, function () {
        });
    };
});
