app.controller('ManageChecklistController', function($scope, $http,  $uibModal,  toaster, AuthService) {
    var MESSAGE_TITLE = 'Checklist Manage';

    $scope.frequency_units = [
        'Minutes',
        'Hours',
        'Days',
        'Weeks',
        'Months',
        'Years'
    ];

    $scope.location_mode_list = [
        'User',
        'Admin',
        'None',
    ];

    // get job role list
    function getJobroleUserGroupList()
    {
        var profile = AuthService.GetCredentials();

        $http.get('/list/jobrole?property_id=' + profile.property_id)
            .then(function (response) {
                $scope.job_role_list = response.data;
            });

        $http.get('/list/usergroup?property_id=' + profile.property_id)
            .then(function (response) {
                $scope.user_group_list = response.data;
            });

        $http.get('/list/locationlist?property_id=' + profile.property_id)
            .then(function (response) {
                $scope.location_list = response.data;
            });
    }

    getJobroleUserGroupList();

    //datr filter option
    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };
    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD ') + ' - ' + picker.endDate.format('YYYY-MM-DD');
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
        request.start_date = $scope.dateRangeOption.startDate;
        request.end_date = $scope.dateRangeOption.endDate;
        request.searchtext = $scope.searchtext;


        $http({
            method: 'POST',
            url: '/frontend/mod/getchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.content.map(row => {

                    if (row.report_completor == 1)
                        row.report_completor = true;

                        
                    return row;
                });

                console.log($scope.datalist);

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


    $scope.onShowEditDialog = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/mod/checklist_edit_dialog.html',
            controller: 'ChecklistEditDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                model: function () {
                    return row;
                },
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                $scope.getDataList();
            }
        }, function () {

        });
    }

    $scope.onActive = function(row)
    {
        var request = {};
        request.id = row.id;
        request.disabled = 1 - row.disabled;

        $http({
            method: 'DELETE',
            url: '/frontend/mod/activechecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                row.disabled = request.disabled;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.delete = function(row)
    {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete this entry? Please note that this action cannot be undone.';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',
            resolve: {
                message: function () {
                    return message;
                }
            },
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');

                    deleteRow(row);
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();
                };
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                callback(row);
        }, function () {

        });

    }

    function deleteRow(row)
    {
        var request = {};
        request.id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/mod/deletechecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Tasks have been deleted successfully');
                $scope.getDataList();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.editCheck = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/mod/checklist_item_dialog.html',
            controller: 'ChecklistItemEditDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                checklist: function () {
                    return row;
                },
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                add(data);
            }
        }, function () {

        });
    }

});

app.controller('ChecklistEditDialogCtrl', function($scope, $http, AuthService, $uibModalInstance, $uibModal, toaster, model) {
    var MESSAGE_TITLE = 'Edit Checklist';

    console.log(model);

    if( !model )
    {
        $scope.model = {};
        $scope.model.location_tags = [];
        $scope.model.start_date = new Date(moment().add(-1, 'days'));
    }
    else
    {
        $scope.model = angular.copy(model);
        $scope.model.start_date = new Date(moment(model.start_date));
    }

//    $scope.model.job_role_tags = [];
 //   $scope.model.user_group_tags = [];
//    $scope.model.notify_group_tags = [];
 //   $scope.model.location_tags = [];

    $scope.jobroleTagFilter = function(query) {
        return $scope.job_role_list.filter(function(item) {
            return item.job_role.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.usergroupTagFilter = function(query) {
        return $scope.user_group_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.locationTagFilter = function(query) {
        return $scope.location_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.open = function($event) {
        $scope.start_date_opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker',
    };

    $scope.ok = function() {
        var request = angular.copy($scope.model);

        request.job_role_ids = $scope.model.job_role_tags.map(item => item.id).join(",");
        request.user_group_ids = $scope.model.user_group_tags.map(item => item.id).join(",");
        request.notify_group_ids = $scope.model.notify_group_tags.map(item => item.id).join(",");
        request.location_ids = $scope.model.location_tags.map(item => item.id).join(",");
        request.start_date = moment($scope.model.start_date).format('YYYY-MM-DD');

        $http({
            method: 'POST',
            url: '/frontend/mod/createchecklist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if( response.data.code == 200 )
                {
                    toaster.pop('success', MESSAGE_TITLE, 'Check list is updated successfully');
                    $uibModalInstance.close($scope.model);
                }
                else
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });

    }

    $scope.cancel = function() {
        $uibModalInstance.dismiss();
    }
});

app.controller('ChecklistItemEditDialogCtrl', function($scope, $rootScope, $http, AuthService, $uibModalInstance, $uibModal, checklist, toaster) {
    var MESSAGE_TITLE = 'Managuer On Duty Checklist';

    $scope.checklist = angular.copy(checklist);
    $scope.item_list = [];

    $scope.item = {};

    $scope.type_list = [
        'Yes/No',
        'Comment',
    ];

    function init()
    {
        $scope.item.id = 0;
        $scope.item.name = '';
        $scope.item.type = $scope.type_list[0];
    }

    init();

    function getCategoryList()
    {
        var request = {};
        request.checklist_id = $scope.checklist.id;

        $http({
            method: 'POST',
            url: '/frontend/mod/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.category_list = response.data.content;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    }

    getCategoryList();

    $scope.onCategorySelect = function($item, $model, $label)
    {
        $scope.item.category_id = $item.id;
        $scope.item.order_id = $item.order_id;
    }

    function getItemList()
    {
        var request = {};
        request.checklist_id = $scope.checklist.id;

        $http({
            method: 'POST',
            url: '/frontend/mod/getchecklistitemlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.item_list = response.data.list;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    }

    getItemList();

    $scope.onAddCategory = function(name)
    {
        var request = {};
        request.checklist_id = $scope.checklist.id;
        request.name = name;
        request.order_id = $scope.item.order_id;

        $http({
            method: 'POST',
            url: '/frontend/mod/createchecklistcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.category_list = response.data.list;
                $scope.item.category_id = response.data.id;
                $scope.item.category_name = response.data.name;
                $scope.item.noCategoryResults = false;

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    }

    $scope.onCreateCheckListItem = function()
    {
        var request = {};
        request = angular.copy($scope.item);
        request.checklist_id = $scope.checklist.id;
        if( request.category_id < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select category');
            return;
        }

        if( !request.name )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select name');
            return;
        }


        $http({
            method: 'POST',
            url: '/frontend/mod/createchecklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.item_list = response.data.list;
                $scope.category_list = response.data.category_list;

                $scope.$emit('callGetDataList', {});
                init();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    }

    $scope.edit = function(row)
    {
        $scope.item = angular.copy(row);
    }

    $scope.delete = function(row)
    {
        var request = {};
        request = angular.copy(row);
        request.checklist_id = $scope.checklist.id;

        $http({
            method: 'POST',
            url: '/frontend/mod/deletechecklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.item_list = response.data.list;

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    }


    $scope.ok = function() {

        $uibModalInstance.close($scope.checklist);
    }

    $scope.clear = function() {
        init();
    }

    $scope.cancel = function() {
        $uibModalInstance.dismiss();
    }
});
