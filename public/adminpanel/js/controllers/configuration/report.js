define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ReportCtrl', function ($scope, $http, $httpParamSerializer, $timeout) {
            $scope.property_id = 0;
           
            var dataReady = false;
           
            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getReport('report');
            });

            $scope.guest_fac_report_recipients = [];

            $scope.loadFilters = function(query) {
                var request = {};                
                
                request.filter = query;

                var param = $httpParamSerializer(request);

                return $http.get('/backoffice/configuration/wizard/userlist?' + param);        
            };


            $scope.getReport = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/report',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.report) {
                            $scope.displayReport(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.displayReport = function (data) {

                $scope.guest_fac_report_recipients = data.report.guest_fac_report_recipients;
                $scope.guest_fac_report_time_start = data.report.guest_fac_report_time_start;
                $scope.guest_fac_report_time_interval = data.report.guest_fac_report_time_interval;
                $scope.guest_feedback_report_recipients = data.report.guest_feedback_report_recipients;
                $scope.guest_feedback_report_time_start = data.report.guest_feedback_report_time_start;
                $scope.guest_feedback_report_interval = data.report.guest_feedback_report_interval;
                $scope.complaint_report_recipients = data.report.complaint_report_recipients;
                $scope.complaint_report_time_start = data.report.complaint_report_time_start;
                $scope.complaint_report_time_interval = data.report.complaint_report_time_interval;

                $timeout(function() {
                    dataReady = true;
                }, 5000);                

            }

            $scope.saveReport = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savereport',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            // if( config.data.setting_group == 'report' )
                            //     $scope.getReport('report');
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.saveUserSetting = function(fieldname, value, setting_group) {
                var value_list = value.map(ele => {
                    return ele.text;
                });

                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value_list.join();
                data.setting_group = setting_group;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/saveusersetting',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            // if( config.data.setting_group == 'report' )
                            //     $scope.getReport('report');
                        }
                        else {
                            $scope.message = " Error: Cannot connect database.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

            $scope.$watchCollection('guest_fac_report_recipients', function () {
                if( dataReady == false )
                    return;

                $scope.saveUserSetting('guest_fac_report_recipients' , $scope.guest_fac_report_recipients, 'report');
            });

            $scope.$watchCollection('guest_feedback_report_recipients', function () {
                if( dataReady == false )
                    return;

                $scope.saveUserSetting('guest_feedback_report_recipients' , $scope.guest_feedback_report_recipients, 'report');
            });

            $scope.$watchCollection('complaint_report_recipients', function () {
                if( dataReady == false )
                    return;

                $scope.saveUserSetting('complaint_report_recipients' , $scope.complaint_report_recipients, 'report');
            });
        });
    });