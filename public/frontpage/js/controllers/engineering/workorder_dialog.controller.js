app.controller('WorkorderDialogController', function ($scope, $rootScope, $http, $uibModal, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, $uibModalInstance, $uibModal, workorder) {
    var MESSAGE_TITLE = 'Workorder Detail';

    $scope.workorder = angular.copy(workorder);

    getWorkorderDetail();
    getWorkOrderHistory();
    getWorkorderStaffList();

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.supervisor = AuthService.isValidModule('mobile.workorder.supervisor');

    function getWorkorderDetail() {
        var request = {};

        request.id = $scope.workorder.id;
        request.property_id = property_id;
        var url = '/frontend/eng/getworkorderdetail';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.workorder = response.data.content;

            getWorkOrderStaff();
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }


    $scope.ticketlist = [];

    function getWorkOrderHistory() {
        $scope.ticketlist = [];
        var request = {};
        request.id = $scope.workorder.id;

        var url = '/frontend/eng/getworkorderhistorylist';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.content;
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    function changeWorkorderStatus(){
        var data = angular.copy($scope.workorder);
        $http({
            method: 'POST',
            url: '/frontend/eng/changestatus',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                // $scope.pageChanged();
                getWorkOrderHistory();

            }).catch(function(response) {
                //toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    function changeStatusButton(val) {
        switch (val) {
            case 'Pending':
                $scope.pendingcolor='btn-danger';
                $scope.progresscolor='btn-grey';
                $scope.holdcolor='btn-grey';
                $scope.completcolor='btn-grey';
                break;
            case 'In Progress':
                $scope.pendingcolor='btn-grey';
                $scope.progresscolor='btn-progres';
                $scope.holdcolor='btn-grey';
                $scope.completcolor='btn-grey';
                break;
            case 'On Hold':
                $scope.pendingcolor='btn-grey';
                $scope.progresscolor='btn-grey';
                $scope.holdcolor='btn-hold';
                $scope.completcolor='btn-grey';
                break;
            case 'Completed':
                $scope.pendingcolor='btn-grey';
                $scope.progresscolor='btn-grey';
                $scope.holdcolor='btn-grey';
                $scope.completcolor='btn-success';
                break;

        }
        $scope.workorder.status = val;
    }

    changeStatusButton($scope.workorder.status);

    function isValidChangeStatus(status) {
        var currentstatus = $scope.workorder.status;
        if(status == 'Pending') {
            if (currentstatus == 'Pending' || currentstatus == 'Completed') {
                return false;
            } else {
                return true;
            }
        } else if(status === 'In Progress') {
            if( currentstatus == 'In Progress' || currentstatus == 'Completed')
                return false;
            else
                return true;
        } else if(status == 'On Hold') {
            if (currentstatus == 'On Hold' || currentstatus == 'Completed') {
                return false;
            } else {
                return true;
            }
        } else if(status === 'Completed') {

            if ($scope.datalist.length < 1) {
                toaster.pop('Warning', MESSAGE_TITLE, 'Please assign a staff to this Work Order');
                return false;
            }

            if (($scope.workorder.request_flag == 1) && ($scope.workorder.request_id > 0)){

                var modalInstance = $uibModal.open({
                    templateUrl: 'tpl/modal/modal_input.html',
                    controller: 'ModalInputCtrl',
                    scope: $scope,
                    resolve: {
                        title: function () {
                            return 'Please Input The Complete Comment';
                        },
                        min_length: function () {
                            return 0;
                        }
                    }
                });
        
                modalInstance.result
                    .then(function (comment) {
                        $scope.workorder.comment = comment;
                        changeWorkorderStatus();
                    }, function () {
        
                    });

            }



            if( currentstatus != 'Completed')
            {
                if( $scope.workorder.checklist_id > 0 && $scope.workorder.inspected == false )  // check inspection
                {
                    toaster.pop('info', MESSAGE_TITLE, "Please check check list before completion");
                    return false;
                }
                return true;
            }
            else
                return false;
        }

        return false;

    }

    function completecomment(comment) {
    

        var request = {};

        request.comment = comment;

        if( request.comment == undefined || request.comment.length < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please provide the Comment.');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/eng/addcompletecomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Comments added successfully.');
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to add Comments.');
        })
        .finally(function() {

        });
    }



    $scope.changeStatusWorkOrder = function(val) {
        if(isValidChangeStatus(val) == true) {
            changeStatusButton(val);
            changeWorkorderStatus();
        }
    };

    // Update Work Order
    $scope.history_details = {};
    $scope.historydetail_view = '';

    $scope.onSelectHistoryDetail = function(row) {
        $scope.history_details = angular.copy(row);
        $scope.sendbutton = 'Update';
        if(row.status != 'Custom' ) // updated data from workorder detail page
            $scope.historydetail_view = 'true';
        else {
            //manualy updated data from this page
            $scope.historydetail_view = '';
        }
    }

    $scope.onUpdateHistoryDetail = function() {
        var request = {};
        if($scope.history_details.id > 0) {
            request = angular.copy($scope.history_details);
            request.description = $scope.history_details.description;
        }else{
            request.workorder_id = $scope.workorder.id;
            request.description = $scope.history_details.description;
            request.status = 'Custom';
            request.log_kind = 'workorder';
        }

        $http({
            method: 'POST',
            url: '/frontend/eng/updateworkorderhistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            toaster.pop('success', MESSAGE_TITLE, ' Comments are updated successfully');
            $scope.history_details = {};
            getWorkOrderHistory();
        }).catch(function (response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function () {

            });
    }

    $scope.onCancelHistoryDetail = function(){
        $scope.history_details = {};
        $scope.sendbutton = 'Create';
    }
    $scope.onCancelHistoryDetail();

    $scope.$on('onRefreshAfterDeleted', function (event, args) {

    });

    $scope.onDeleteHistoryDetail = function (row) {
        if(row.workorder_id > 0) {
            var modalInstance = $uibModal.open({
                templateUrl: 'workorder_history_delete.html',
                controller: 'WorkorderHistoryDeleteCtrl',
                scope: $scope,
                resolve: {
                    row: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {

            });
        }
    }

    $scope.refreshWorkorderHistory = function() {
        getWorkOrderHistory();
    }

    // work order staff
    $scope.isLoading = false;
    $scope.staffs ={};
    $scope.saffstatus = 'create';
    function getWorkOrderStaff() {
        $scope.isLoading = true;
        var request = {};
        request.workorder_id = $scope.workorder.id;

        var url = '/frontend/eng/getworkorderstafflist';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.datalist = response.data.datalist;
            $scope.workorder.staff_group = response.data.staff_group;
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.refreshWorkorderStaff = function()
    {
        getWorkOrderStaff();
    }

    $scope.onDeleteStaffDetail = function (row) {
        if(row.id > 0) {
            var modalInstance = $uibModal.open({
                templateUrl: 'workorder_staff_delete.html',
                controller: 'WorkorderStaffDeleteCtrl',
                scope: $scope,
                resolve: {
                    row: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {

            });
        }
    }

    $scope.onStaffEdit = function (row) {
        $scope.staffs = {};
        $scope.staffs = angular.copy(row);
        if(row.status == 'Pending')
            $scope.staffs.statff_statuss = ['In Progress','On Hold','Completed'];
        if(row.status == 'In Progress')
            $scope.staffs.statff_statuss = ['On Hold','Completed'];
        if(row.status == 'On Hold')
            $scope.staffs.statff_statuss = ['In Progress','Completed'];

        // if( $scope.staffs.statff_statuss.length > 0 )
        //     $scope.staffs.status = $scope.staffs.statff_statuss[0];


        $scope.saffstatus = 'update';
    }

    $scope.onStaffCancel = function () {
        $scope.staffs = {};
        $scope.saffstatus = 'create';
    }

    $scope.staff_list = [];
    function getWorkorderStaffList() {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return promiss = $http.get('/frontend/eng/getstaffgrouplist?property_id='+property_id)
            .then(function(response){
                $scope.staff_list = response.data.content;
            });
    };

    $scope.onWorkorderStaffSelect = function (staffs, $item, $model, $label) {

        $scope.staffs.staff_id = $item.id;
        $scope.staffs.staff_cost = $item.cost;
        if($scope.staffs.staff_cost == null) $scope.staffs.staff_cost = 0;
        $scope.staffs.staff_type = $item.type;

    };

    $scope.onCreateStaff = function() {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.workorder_id = $scope.workorder.id;
        request.user_id = profile.id;
        request.staff_id = $scope.staffs.staff_id;
        request.staff_type = $scope.staffs.staff_type;
        request.staff_cost = $scope.staffs.staff_cost;
        request.staff_name = $scope.staffs.staff_name;
        request.status = 'Pending';
        $http({
            method: 'POST',
            url: '/frontend/eng/createworkorderstaff',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {

            $scope.history_details = {};
            if( response.data.code == 200 )
                toaster.pop('success', MESSAGE_TITLE, ' Staff of workOrder has been created successfully');
            else
                toaster.pop('info', MESSAGE_TITLE, response.data.message);

            $scope.staffs.staff_name = '';
            $scope.staffs.staff_id = 0;
            getWorkOrderStaff();
        }).catch(function (response) {

            })
            .finally(function () {

            });
    }

    $scope.onUpdateStaff = function() {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.id = $scope.workorder.id;
        request.user_id = profile.id;
        request.staff_id = $scope.staffs.staff_id;
        request.staff_cost = $scope.staffs.staff_cost;
        request.staff_name = $scope.staffs.staff_name;
        request.status = $scope.staffs.status;
        request.source = 'web';
        $http({
            method: 'POST',
            url: '/frontend/eng/updateworkorderstaff',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            if( response.data.code == 200 )
            {
                toaster.pop('success', MESSAGE_TITLE, ' Staff of workOrder has been updated successfully');
                $scope.staffs.staff_name = '';
                $scope.staffs.staff_id = 0;
                $scope.saffstatus = 'create';
            }
            else
                toaster.pop('info', MESSAGE_TITLE, response.data.message);

            $scope.history_details = {};
            getWorkOrderStaff();
        }).catch(function (response) {

            })
            .finally(function () {

            });
    }

    function getCheckListData()
    {
        var request = {};

        request.workorder_id = $scope.workorder.id;

        var url = '/frontend/eng/workorderchecklist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.workorder.inspected = response.data.content.list.filter(item => {
                return item.check_flag == 0;
            }).length == 0;

        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {

            });
    }

    $scope.onViewCheckList = function()
    {
        // find selected ticket
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_checklist.html',
            controller: 'WorkorderChecklistController',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {
                workorder: function() {
                    return $scope.workorder;
                }
            }
        });

        modalInstance.result.then(function (data) {
            getCheckListData();
        }, function () {

        });
    }


    $scope.cancel = function() {
        $uibModalInstance.dismiss();
    }
});


app.controller('WorkorderHistoryDeleteCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster , row) {
    var MESSAGE_TITLE = 'WorkOrder History ';
    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();
        var request = {};
        request = angular.copy(row);
        $http({
            method: 'POST',
            url: '/frontend/eng/deleteworkorderhistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.history_details = {};
            toaster.pop('success', MESSAGE_TITLE, ' WorkOrder History has been deleted successfully');
            $uibModalInstance.close();
            $scope.refreshWorkorderHistory();
        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
    }
});

app.controller('WorkorderStaffDeleteCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster , row) {
    var MESSAGE_TITLE = 'WorkOrder Staff ';
    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();
        var request = {};
        request = angular.copy(row);
        $http({
            method: 'POST',
            url: '/frontend/eng/deleteworkorderstaff',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', MESSAGE_TITLE, ' Staff has been deleted successfully');
            $uibModalInstance.close();
            $scope.refreshWorkorderStaff();

            // send signal to parent to refresh
            $scope.$emit('refresh_workorder_page', '');
        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
    }
});

app.controller('ModalInputCtrl', function($scope, $rootScope, $uibModalInstance, title, min_length) {
    $scope.data = {};

    $scope.title = title;
    $scope.data.comment = '';
    $scope.min_length = 0;
    if( min_length > 0 )
        $scope.min_length = min_length;

    $scope.save = function () {
        $uibModalInstance.close($scope.data.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});