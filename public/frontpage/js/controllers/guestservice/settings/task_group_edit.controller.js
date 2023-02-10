app.controller('TaskgroupEditController', function ($scope, $http, AuthService, toaster, $uibModalInstance, $uibModal, row) {

    $scope.deptfunclist = [];
    $scope.usergrouplist = [];
    $scope.jobrolelist = [];

    $scope.isLoading = false;

    $scope.model_data = {
        dept_function: row.dept_function,
        name: row.name,
        escalation: row.escalation == 'Yes' ? true : false,
        by_guest_flag: row.by_guest_flag == 1 ? true : false,
        user_group: row.user_group,
        max_time: row.max_time,
        request_reminder: row.request_reminder,
        frequency_notification_flag: row.frequency_notification_flag == 1 ? true : false,
        freq_job_role_name_list: [],
        frequency: row.frequency,
        period: row.period,
        hold_notification_flag: row.hold_notification_flag == 1 ? true : false,
        hold_job_role_name_list: [],
        hold_timeout: row.hold_timeout,
        unassigne_flag: row.unassigne_flag,
        start_duration: row.start_duration
    };

    $scope.deptFunc = row.function;
    $scope.usergroup = row.ugname;

    let profile = AuthService.GetCredentials();

    function getDeptFunclist() {
        let user_id = profile.id;

        $http.get('/frontend/guestservice/getsettingdeftfunclist?user_id=' + user_id)
            .then(function (response) {
                $scope.deptfunclist = response.data;
            });
    }

    function getUsergrouList() {
        $http.get('/frontend/guestservice/getsettingusergrouplist')
            .then(function (response) {
                $scope.usergrouplist = response.data;
            });
    }

    function getJobRoleList() {
        let user_id = profile.id;
        let property_id = profile.property_id;

        $http.get('/frontend/guestservice/getsettingjobrolelist?user_id' + user_id + '&property_id=' + property_id)
            .then(function (response) {
                $scope.jobrolelist = response.data;

                let fregJobList = row.frequency_job_role_ids.split(",");

                $scope.model_data.freq_job_role_name_list = fregJobList.map(item => {
                    let res = null;

                    for (let i = 0; i < $scope.jobrolelist.length; i++) {
                        if ($scope.jobrolelist[i].id == parseInt(item)) {
                            res = $scope.jobrolelist[i];
                            break;
                        }
                    }

                    if (res != null) {
                        return res;
                    }
                });

                let holdJobList = row.hold_job_role_ids.split(",");
                $scope.model_data.hold_job_role_name_list = holdJobList.map(item => {
                    let res = null;

                    for (let i = 0; i < $scope.jobrolelist.length; i++) {
                        if ($scope.jobrolelist[i].id == parseInt(item)) {
                            res = $scope.jobrolelist[i];
                            break;
                        }
                    }

                    if (res != null) {
                        return res;
                    }
                });
            });
    }

    function init() {
        getDeptFunclist();
        getUsergrouList();
        getJobRoleList();
    }

    init();

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onSave = function () {

        let request = angular.copy($scope.model_data);

        request.frequency_job_role_ids = request.freq_job_role_name_list.map(item => {
            return item.id;
        }).join();

        request.hold_job_role_ids = request.hold_job_role_name_list.map(item => {
            return item.id;
        }).join();

        request.escalation = request.escalation === true ? 'Yes' : 'No';

        delete request.freq_job_role_name_list;
        delete request.hold_job_role_name_list;

        if (request.unassigne_flag == 1) {
            if( request.user_group == 0 )
            {
                toaster.pop('warning', 'Notification', 'Please select User Group for Unassigned Task Group');
                return;
            }

            if( !(request.start_duration > 0) )
            {
                toaster.pop('warning', 'Notification', 'Please set start duration for Unassigned Task Group');
                return;
            }
        }

        request.id = row.id;

        $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/editsettingtaskgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .success(function(data, status, headers, config) {
                if( data) {
                    toaster.pop('success', 'Notification!', 'Updated Successfully!');
                    $uibModalInstance.close('ok');
                }
            })
            .error(function(data, status, headers, config) {
                toaster.pop('error', 'Error', 'Error');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onSelectDeptFunc = function($item, $model, $label) {
        $scope.model_data.dept_function = $item.id;
    };

    $scope.onSelectUserGroup = function ($item) {
        $scope.model_data.user_group = $item.id;
    };

    $scope.onJobRoleFilter = function (query) {
        return $scope.jobrolelist.filter(item => item.job_role.toLowerCase().includes(query.toLowerCase()));
    };

    $scope.onFreqJobRoleChanged = function() {
        $scope.model_data.freq_job_role_name_list = $scope.model_data.freq_job_role_name_list.filter((item) => {
            return item.id ? true : false;
        });
    };

    $scope.onHoldJobRoleChanged = function() {
        $scope.model_data.hold_job_role_name_list = $scope.model_data.hold_job_role_name_list.filter((item) => {
            return item.id ? true : false;
        });
    };

});
