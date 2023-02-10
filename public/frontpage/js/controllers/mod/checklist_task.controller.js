app.controller('ChecklistTaskController', function($scope, $rootScope, $http, $timeout,  $uibModal, $window,  toaster, liveserver, $httpParamSerializer, AuthService) {
    var MESSAGE_TITLE = 'Checklist Logs';

    $scope.filter = {};
    $scope.filter.user_tags = [];
    $scope.filter.name_tags = [];
    $scope.filter.status_tags = [];
    $scope.auth_svc = AuthService;

    // get job role list
    var user_list = [];
    var name_list = [];
    function getDataList()
    {
        var profile = AuthService.GetCredentials();

        $http.get('/list/userlist?property_id=' + profile.property_id)
            .then(function (response) {
                user_list = response.data;
            });

        $http.get('/list/checklist?property_id=' + profile.property_id)
            .then(function (response) {
                name_list = response.data;
            });

        $http.get('/list/locationlist?property_id=' + profile.property_id)
            .then(function (response) {
                $scope.location_list = response.data;
            });
    }

    $scope.$on('checklisttask_created', function(message) {
       $scope.getDataList();
    });

    $scope.$on('checklisttask_updated', function(event, message) {
        $scope.getDataList();
    });

    $scope.$on('checklisttask_updatestatus', function (event, message) {
        $scope.getDataList();
    });

    $rootScope.$on('callGetDataList', function(event) {
        $scope.getDataList();
    });

    $scope.userTagFilter = function(query) {
        return user_list.filter(function(item) {
            return item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.nameTagFilter = function(query) {
        return name_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    getDataList();

    var status_list = [
        'Pending',
        'In Progress',
        'Done',
    ];

    $scope.statusTagFilter = function(query) {
        return status_list.filter(function(item) {
            return item.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };


    $scope.onShowAddChecklistModal = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/mod/checklist_add_dialog.html',
            controller: 'ChecklistAddDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                        location_list: function() {
                            if (!$scope.auth_svc.isValidModule('mobile.dutymanager.edit')) {
                                return [];
                            } else {
                                return $scope.location_list;
                            }
                        }
                    }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                $scope.getDataListForAdd();
            }
        }, function () {

        });
    };

    $scope.onShowDateRangePicker = function(event) {
        let buttonOffSet = $('#daterange').offset();
        let buttonWidth = $('#daterange').width();

        let windowWidth = $(document).width();

        let remainRight = windowWidth - (buttonOffSet.left + buttonWidth / 2);
        let calendarComponents = $('.daterangepicker.opensright');

        if (remainRight < 666) {
            for (let i = 0; i < calendarComponents.length; i++) {
                let calendar = calendarComponents[i];
                $(calendar).addClass('before_after_hide');
            }

        } else {
            for (let i = 0; i < calendarComponents.length; i++) {
                let calendar = calendarComponents[i];
                $(calendar).removeClass('before_after_hide');
            }
        }
    };

    //datr filter option
    $scope.dateRangeOptions = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };
    $scope.daterange = $scope.dateRangeOptions.startDate + ' - ' + $scope.dateRangeOptions.endDate;

    angular.element('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.start_time =  picker.startDate.format('YYYY-MM-DD HH:mm:ss');
        $scope.end_time = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
        $scope.time_range = $scope.start_time + ' - ' + $scope.end_time;
        $scope.getDataList();
    });


    $scope.tableState = {};
    $scope.tableState.pagination = {};
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.onSearch = function() {
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }

    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
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
        var profile = AuthService.GetCredentials();
        request.attendant = profile.id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
    //    request.start_date = $scope.dateRangeOptions.startDate;
    //    request.end_date = $scope.dateRangeOptions.endDate;
        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        request.searchtext = $scope.searchtext;

        request.completed_by_ids = $scope.filter.user_tags.map(item => item.id).join(",");
        request.checklist_name = $scope.filter.name_tags.map(item => item.name).join(",");
        request.status_array = $scope.filter.status_tags.map(item => item.text).join(",");

        $scope.filter_apply = $scope.filter.user_tags.length > 0 ||  $scope.filter.name_tags.length > 0 ||
                                    $scope.filter.status_tags.length > 0;


        $http({
            method: 'POST',
            url: '/frontend/mod/getchecklisttask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.content;

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

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'C00000';
        return sprintf('C%05d', ticket.id);
    }


    $scope.editCheck = function(row) {

        $rootScope.$emit("addSelectedLogs", row);

        //
        //
        // // find selected ticket
        // var modalInstance = $uibModal.open({
        //     templateUrl: 'tpl/mod/checklist_result.html',
        //     controller: 'ModChecklistResultController',
        //     scope: $scope,
        //     size: 'lg',
        //     backdrop: 'static',
        //     resolve: {
        //         task: function() {
        //             return row;
        //         }
        //     }
        // });
        //
        // modalInstance.result.then(function (data) {
        //     console.log(data);
        // }, function () {
        //
        // });
    }

    $scope.deleteCheck = function(row)
    {

        var request = {};
        request.id = row.id;
        var url = '/frontend/mod/deletechecklisttask';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('warning', MESSAGE_TITLE, "Checklist had been deleted Successfully");
            $scope.getDataList();
            console.log(response);
        }).catch(function(response) {
            console.error('Gists error', response.data);
            toaster.pop('danger', MESSAGE_TITLE, "Failed to delete Checklist");
        })
            .finally(function() {

            });
    }

    $scope.exportPDF = function(row) {
        var profile = AuthService.GetCredentials();

        var filter = {};
        filter.user_id = profile.id;
        filter.task_id = row.id;
        filter.report_target = 'mod_checklist';

        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }

});

app.controller('ChecklistAddConfirmDialogCtrl', function($scope, $uibModal, $uibModalInstance, toaster, location_list) {
    $scope.location_list = location_list;
    $scope.location_id = 0;
    $scope.onLocationSelectForAdd = function($item) {
        $scope.location_id = $item.id;
    }

    $scope.onOk = function() {
        if ($scope.location_list.length > 0 && $scope.location_id == 0) {
            toaster.pop('warning', 'Notification', 'Please select location');
            return;
        }
        $uibModalInstance.close($scope.location_id);
    }

    $scope.onCancel = function() {
        $uibModalInstance.dismiss();
    }
});

app.controller('ChecklistAddDialogCtrl', function ($scope, $rootScope, $uibModal, $uibModalInstance, $timeout, $http, AuthService, toaster, location_list) {
    var MESSAGE_TITLE = 'Add Checklist';

    $scope.location_list = location_list;
    $scope.isLoadingForAdd = false;
    $scope.dataListForAdd = [];
    $scope.searchTextForAdd = "";

    $scope.selectedIndex = -1;

    $scope.tableStateForAdd = {};
    $scope.tableStateForAdd.pagination = {};
    $scope.isLoadingForSubmit = false;
    //datr filter option
    $scope.dateRangeOptionForAdd = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };
    $scope.paginationOptionsForAdd = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.onSelectAndAdd = function(row) {
        $scope.selectedIndex = row.id;

        $scope.onAddChecklistTask();
    };

    $scope.onSelectRow = function(row) {
        $scope.selectedIndex = row.id;
    };

    $scope.onSearch = function() {
        $scope.paginationOptionsForAdd.pageNumber = 0;
        $scope.getDataListForAdd();
    };

    $scope.getDataListForAdd = function(tableState) {
        $scope.isLoadingForAdd = true;

        if( tableState != undefined )
        {
            $scope.tableStateForAdd = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptionsForAdd.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptionsForAdd.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptionsForAdd.field = tableState.sort.predicate;
            $scope.paginationOptionsForAdd.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }


        var request = {};
        var profile = AuthService.GetCredentials();
        request.attendant = profile.id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptionsForAdd.pageNumber;
        request.pagesize = $scope.paginationOptionsForAdd.pageSize;
        request.field = $scope.paginationOptionsForAdd.field;
        request.sort = $scope.paginationOptionsForAdd.sort;
        request.start_date = $scope.dateRangeOptionForAdd.startDate;
        request.end_date = $scope.dateRangeOptionForAdd.endDate;
        request.searchtext = $scope.searchTextForAdd;

        $http({
            method: 'POST',
            url: '/frontend/mod/getchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.dataListForAdd = response.data.content.map(row => {
                    return row;
                });

                $scope.paginationOptionsForAdd.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptionsForAdd.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableStateForAdd.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptionsForAdd.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {
                $scope.isLoadingForAdd = false;
            });
    };

    $scope.onAddChecklistTask = function () {
        if ($scope.selectedIndex < 0) {
            toaster.pop('warning', MESSAGE_TITLE, "Please select Checklist item");
            return;
        }

        var modalInstance = $uibModal.open({
            templateUrl: "tpl/mod/add_confirm_dialog.html",
            controller: 'ChecklistAddConfirmDialogCtrl',
            size: 'sm',
            scope: $scope,
            resolve: {
                location_list: function() {
                    return $scope.location_list;
                }
            },
        });

        modalInstance.result.then(function(location_id) {
            var request = {};

            var profile = AuthService.GetCredentials();
            request.id = $scope.selectedIndex;
            request.user_id = profile.id;

            request.location_id = location_id;

            var url = '/frontend/mod/createchecklisttask';

            $scope.isLoadingForSubmit = true;

            $http({
                method: 'POST',
                url: url,
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {

                toaster.pop('success', MESSAGE_TITLE, "Successfully Added!");

                $rootScope.$emit('callGetDataList', {});

                $timeout(function () {
                    $uibModalInstance.dismiss();
                }, 100);
            }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoadingForSubmit = false;
            });
        }, function() {

        });
    };

    $scope.clear = function() {
        init();
    }

    $scope.cancel = function() {
        $uibModalInstance.dismiss();
    }
});
