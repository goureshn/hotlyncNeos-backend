app.controller('SettingTaskController', function ($scope, $window, $http, $uibModal, $timeout, $interval, $compile, AuthService, toaster, GuestService) {
    var profile = AuthService.GetCredentials();

    $scope.tableState = {};
    $scope.tableState.pagination = {};
    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 15,
        sort: 'asc',
        field: 'tl.id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.oldPaginationOptions = angular.copy($scope.paginationOptions);

    $scope.searchText = "";

    $scope.isLoading = "";

    $scope.taskList = [];

    $scope.getTaskList = function (tableState) {
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        request.searchText = $scope.searchText;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getsettingtasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.taskList = response.data.content;

                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

            }).catch(function(response) {
            console.error('taskgroups error', response.status, response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });


    };

    $scope.onRefresh = function() {
        $scope.searchText = "";
        $scope.paginationOptions = angular.copy($scope.oldPaginationOptions);

        $scope.getTaskList();
    };

    $scope.onDeleteRow = function(row) {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_delete.html',
            scope: $scope,
            resolve: {
                name: function () {
                    return row.task;
                }
            },
            controller: function ($scope, $uibModalInstance, name) {
                $scope.title = "Task";
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
                    url: '/frontend/guestservice/deletesettingtaskrow',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
                        $scope.onRefresh();
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

    $scope.onAddNew = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_task_create.html',
            controller: 'TaskCreateController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
            }
        });

        modalInstance.result.then(function (ret) {
            if (ret == 'ok') {
                $scope.getTaskList();
            }
        }, function () {
        });
    };

    $scope.onExportFile = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_export.html',
            scope: $scope,
            resolve: {
            },
            controller: function ($scope, $uibModalInstance) {
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

                let user_id = profile.id;

                $window.location.href = '/frontend/report/downloadauditexcelreporttask?user_id=' + user_id;
            }
        }, function () {

        });
    };

    $scope.onEditRow = function (row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/settings/modals/modal_task_edit.html',
            controller: 'TaskEditController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                row: function () {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (ret) {
            if (ret == 'ok') {
                $scope.getTaskList();
            }
        }, function () {
        });
    };
});
