define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('RequestCtrl', function ($scope, $compile, $timeout, $http, $localStorage, $location/*, $http, initScript */) {
            $scope.property_id = -1;

            $scope.message = "";
            $scope.bChanged = false;
            $scope.isLoading = false;

            $scope.tempAllDailyTime = "";
            $scope.tempAllWeeklyTime = "";
            $scope.tempAllMonthlyTime = "";
            $scope.tempAllYearlyTime = "";

            $scope.tempIndividualDailyTime = "";
            $scope.tempIndividualWeeklyTime = "";
            $scope.tempIndividualMonthlyTime = "";
            $scope.tempIndividualYearlyTime = "";

            $scope.addDepartmentFlag = false;

            $scope.departmentAllData = [];
            $scope.departmentArr = []; // selected

            $scope.jobRoleDataForAll = [];
            $scope.joblistArr = []; // selected for all

            $scope.jobRoleDataForIndividual = {};

            $scope.weekly_options = [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
            ];

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                getRequestSettingInfo();
            });

            $scope.request_data = {
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
                },
                individual_department: {
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
                        monthly_time: "",
                        yearly: false,
                        yearly_time: ""
                    }
                }
            };

            $scope.onRemoveJobItem = function(index, item) {
                let data = {
                    id: item.id,
                    department: item.department
                };

                $scope.departmentAllData.push(data);
                $scope.request_data.individual_department.job_list.splice(index, 1);

                $scope.checkChanged();

            };

            $scope.changeTime = function(id) {

                let realTime = $('#' + id).val();

                if (id === 'all_daily_time') {
                    $scope.request_data.all_department.frequency.daily_time = realTime;
                } else if(id === 'all_weekly_time') {
                    $scope.request_data.all_department.frequency.weekly_time = realTime;
                } else if(id === 'all_monthly_time') {
                    $scope.request_data.all_department.frequency.monthly_time = realTime;
                } else if (id === 'all_yearly_time') {
                    $scope.request_data.all_department.frequency.yearly_time = realTime;
                } else if (id === 'individual_daily_time') {
                    $scope.request_data.individual_department.frequency.daily_time = realTime;
                } else if(id === 'individual_weekly_time') {
                    $scope.request_data.individual_department.frequency.weekly_time = realTime;
                } else if(id === 'individual_monthly_time') {
                    $scope.request_data.individual_department.frequency.monthly_time = realTime;
                } else if (id === 'individual_yearly_time') {
                    $scope.request_data.individual_department.frequency.yearly_time = realTime;
                }

                $scope.checkChanged();
            };

            $scope.request_old_data = angular.copy($scope.request_data);

            $scope.saveDepartment = function (status) {
                $scope.checkChanged();
            };

            $scope.jobRoleFilter = function(query, dept_id) {
                if ($scope.jobRoleDataForIndividual[dept_id]) {
                    return $scope.jobRoleDataForIndividual[dept_id].filter(function(item) {
                        return item.job_role.toLowerCase().indexOf(query.toLowerCase()) != -1;
                    });
                }
            };

            $scope.joblistFilter = function(query) {
                return $scope.jobRoleDataForAll.filter(function(item) {
                    return item.job_role.toLowerCase().indexOf(query.toLowerCase()) != -1;
                });
            };


            $scope.departmentsFilter = function(query) {
                return $scope.departmentAllData.filter(function(item) {
                    return item.department.toLowerCase().indexOf(query.toLowerCase()) != -1;
                });
            };

            var removeDepartmentsFromAll = function(removeArr) {
                let keys = [];
                removeArr.filter(function (item) {
                    keys.push(item.id);
                });

                $scope.departmentAllData = $scope.departmentAllData.filter(function (item) {
                    if (!keys.includes(item.id)) {
                        return true;
                    } else {
                        return false;
                    }
                });

                $scope.departmentArr = [];
            };

            $scope.onAddDepartment = function(){
                let data = angular.copy($scope.departmentArr);

                for(let i = 0; i < data.length; i++) {
                    data[i].roles = [];
                    data[i].enable = true;
                    let resultArr = $scope.jobRoleDataForIndividual[data[i].id];
                    if (resultArr === undefined) {
                        setJobRoleList(data[i].id);
                    }
                }

                $scope.request_data.individual_department.job_list = $scope.request_data.individual_department.job_list.concat(data);

                $scope.addDepartmentFlag = false;

                removeDepartmentsFromAll($scope.departmentArr);

                $scope.checkChanged();
            };


            $scope.checkChanged = function () {
                let request_info = JSON.stringify($scope.request_data);
                let request_old_info = JSON.stringify($scope.request_old_data);
                if (request_info === request_old_info) {
                    $scope.bChanged = false;
                } else {
                    $scope.bChanged = true;
                }
            };

            $scope.onReset = function () {
                $scope.request_data = angular.copy($scope.request_old_data);

                $scope.checkChanged();
            };

            $scope.saveRequestSettingInfo = function() {

                if ($scope.property_id === undefined || $scope.property_id === -1) {
                    $scope.message = "There is no property_id!";
                    $timeout(function() {
                        $scope.message = "";
                    }, 3000);

                    return;
                }

                var request = {};
                request.property_id = $scope.property_id;


                request.request_data = $scope.request_data;

                $scope.isLoading = true;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saverequest',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        $scope.message = "Successfully Updated!";
                        $timeout(function() {
                            $scope.message = "";
                        }, 2000);

                        if (data) {
                            $scope.request_data = data;
                            $scope.request_old_data = angular.copy(data);
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

            var setJobRoleList = function (dept_id) {
                var request = {};
                request.property_id = $scope.property_id;

                $http.get('/backoffice/configuration/wizard/jobrolelist?property_id=' + $scope.property_id + '&dept_id=' + dept_id)
                    .then(function(response){
                        $scope.jobRoleDataForIndividual[dept_id] = response.data;
                    });
            };


            var getJoblistForAll = function () {
                var request = {};
                request.property_id = $scope.property_id;

                $http.get('/backoffice/configuration/wizard/joblistforall?property_id=' + $scope.property_id)
                    .then(function(response){
                        $scope.jobRoleDataForAll = response.data;
                    });
            }

            var getDepartmentList = function () {
                var request = {};
                request.property_id = $scope.property_id;

                $http.get('/backoffice/configuration/wizard/departmentlist?property_id=' + $scope.property_id)
                    .then(function(response){
                        $scope.departmentAllData = response.data;
                        let list = $scope.request_data.individual_department.job_list;

                        list.map(function (item) {
                           if ($scope.jobRoleDataForIndividual[item.id] === undefined) {
                               setJobRoleList(item.id);
                           }
                        });
                        removeDepartmentsFromAll(list);
                    });
            };

            var getRequestSettingInfo = function() {
                var request = {};
                request.property_id = $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/request',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if (data) {
                            $scope.request_data = data;
                            $scope.request_old_data = angular.copy(data);
                        }
                        getDepartmentList();
                        getJoblistForAll();

                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }
        });
    });