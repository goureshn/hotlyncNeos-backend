define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ComplaintCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            let getComplaintDataForEmail = function() {
                let request = {};
                request.property_id = $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/getcomlaintdataforemail',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if (data) {
                            $scope.complaint_data_for_email = data;
                            $scope.complaint_data_for_email_old = angular.copy(data);
                        }
                        getJoblistForAll();
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            };
            $scope.property_id = 0;

            $scope.setting = {};

            $scope.message = "";
            $scope.bChanged = false;
            $scope.isLoading = false;

            $scope.tempAllDailyTime = "";
            $scope.tempAllWeeklyTime = "";
            $scope.tempAllMonthlyTime = "";
            $scope.tempAllYearlyTime = "";

            $scope.jobRoleDataForAll = [];
            $scope.joblistArr = []; // selected for all

            $scope.weekly_options = [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
            ];

            $scope.complaint_data_for_email = {
                all_department :{
                    enable_flag: false,
                    job_role: false,
                    job_list: [],
                    frequency: {
                        daily: false,
                        daily_time : "",
                        weekly: false,
                        weekly_day: "Monday",
                        weekly_time: "",
                        monthly: false,
                        monthly_time: '',
                        yearly: false,
                        yearly_time: ""
                    }
                }
            };

            $scope.changeTime = function(id) {

                let realTime = $('#' + id).val();

                if (id === 'all_daily_time') {
                    $scope.complaint_data_for_email.all_department.frequency.daily_time = realTime;
                } else if(id === 'all_weekly_time') {
                    $scope.complaint_data_for_email.all_department.frequency.weekly_time = realTime;
                } else if(id === 'all_monthly_time') {
                    $scope.complaint_data_for_email.all_department.frequency.monthly_time = realTime;
                } else if (id === 'all_yearly_time') {
                    $scope.complaint_data_for_email.all_department.frequency.yearly_time = realTime;
                }

                $scope.checkChanged();
            };

            $scope.complaint_data_for_email_old = angular.copy($scope.complaint_data_for_email);

            $scope.saveDepartment = function () {
                $scope.checkChanged();
            };

            $scope.joblistFilter = function(query) {
                return $scope.jobRoleDataForAll.filter(function(item) {
                    return item.job_role.toLowerCase().indexOf(query.toLowerCase()) !== -1;
                });
            };

            $scope.checkChanged = function () {
                let request_info = JSON.stringify($scope.complaint_data_for_email);
                let request_old_info = JSON.stringify($scope.complaint_data_for_email_old);

                $scope.bChanged = request_info !== request_old_info;
            };

            $scope.onReset = function () {
                $scope.complaint_data_for_email = angular.copy($scope.complaint_data_for_email_old);

                $scope.checkChanged();
            };

            $scope.saveComplaintDataInfo = function() {

                if ($scope.property_id === undefined || $scope.property_id === -1) {
                    $scope.message = "There is no property_id!";
                    $timeout(function() {
                        $scope.message = "";
                    }, 3000);

                    return;
                }

                let request = {};
                request.property_id = $scope.property_id;

                request.complaint_data_for_email = $scope.complaint_data_for_email;

                $scope.isLoading = true;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savecomplaintdataforemail',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        $scope.message = "Successfully Updated!";
                        $timeout(function() {
                            $scope.message = "";
                        }, 2000);

                        if (data) {
                            $scope.complaint_data_for_email = data;
                            $scope.complaint_data_for_email_old = angular.copy(data);
                        }

                        $scope.checkChanged();
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    })
                    .finally(function () {
                        $scope.isLoading = false;
                    });
            };

            let getJoblistForAll = function () {
                let request = {};
                request.property_id = $scope.property_id;

                $http.get('/backoffice/configuration/wizard/joblistforall?property_id=' + $scope.property_id)
                    .then(function(response){
                        $scope.jobRoleDataForAll = response.data;
                    });
            };

            let getComplaintSettingInfo = function () {
                let data = {};
                data.property_id = $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/complaint',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        $scope.setting = data;
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            };

            function initialize() {
                $http.get('/list/property').success( function(response) {
                    $scope.properties = response;
                    $scope.property_id = $scope.properties[0].id;

                    getComplaintSettingInfo();
                    getComplaintDataForEmail();
                });
            }

            initialize();
        });
    });
