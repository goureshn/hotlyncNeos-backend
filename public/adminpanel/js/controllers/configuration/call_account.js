define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('CallAccountCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            $scope.duty_manager_notify_check = false;
            $scope.awu_record_flag_check = false;
            $scope.property_id = 0;

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getCallAccount('call_account');
                $scope.getCallAccount('night_audit');
            });

            $scope.call_end_setting_list = [
                'S',
                'E',
                'SE'
            ];
            $scope.call_reminder_day_list = [
                1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31
            ]

            $scope.night_audit_report_type_list = [
                'Detailed report by Room',
                'Detailed report by Extension',
                'Detailed report by Property',
                'Detailed report by Call Date'
            ]

            $scope.night_audit_report_extensions_list = [
                'guest',
                'admin',
                'both'
            ]

            $scope.night_audit_file_type_list = [
                'PDF',
                'Excel'
            ]

            
            $scope.getCallAccount = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/callaccount',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.call_account) {
                            $scope.displayCallAccount(data);
                        }
                        if(data.night_audit) {
                            $scope.displayNightAudit(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }


            $scope.displayCallAccount = function (data) {
                for(var i=0; i < $scope.call_end_setting_list.length ; i++) {
                    if($scope.call_end_setting_list[i] == data.call_account.call_end_setting) {
                       $scope.call_end_setting = $scope.call_end_setting_list[i];
                        break;
                    }
                }
                var call_reminder_day = data.call_account.call_reminder_date.split(" ");
                $scope.call_reminder_time = call_reminder_day[1];
                for(var i = 0; i<$scope.call_reminder_day_list.length ; i++) {
                    if($scope.call_reminder_day_list[i] == call_reminder_day[0]) {
                        $scope.call_reminder_day = $scope.call_reminder_day_list[i];
                    }
                }
                $scope.min_approval_duration =  data.call_account.min_approval_duration;
                $scope.pre_approved_call_types = data.call_account.pre_approved_call_types;
                $scope.min_approval_amount =  data.call_account.min_approval_amount;
                $scope.max_unmarked_count =  data.call_account.max_unmarked_count;
                $scope.max_approver_notify =  data.call_account.max_approver_notify;
                $scope.max_close_notify =  data.call_account.max_close_notify;
                $scope.call_reminder_date =  data.call_account.call_reminder_date;
                $scope.check_call_classification_time =  data.call_account.check_call_classification_time;
                $scope.max_unmarked_cost =  data.call_account.max_unmarked_cost;
            }

            $scope.displayNightAudit = function (data) {
                for(var i=0; i < $scope.night_audit_report_type_list.length ; i++) {
                    if($scope.night_audit_report_type_list[i] == data.night_audit.night_audit_report_type) {
                        $scope.night_audit_report_type = $scope.night_audit_report_type_list[i];
                        break;
                    }
                }

                for(var i = 0; i<$scope.night_audit_report_extensions_list.length ; i++) {
                    if($scope.night_audit_report_extensions_list[i] ==  data.night_audit.night_audit_report_extensions) {
                        $scope.night_audit_report_extensions = $scope.night_audit_report_extensions_list[i];
                        break;
                    }
                }
                
                if(data.night_audit.night_audit_email_flag == 'YES') {
                    $scope.night_audit_email_flag_check = true;
                }else if(data.night_audit.night_audit_email_flag == 'NO') {
                    $scope.night_audit_email_flag_check = false;
                }

                $scope.night_audit_recipients =  data.night_audit.night_audit_recipients;
                $scope.night_audit_report_subject =  data.night_audit.night_audit_report_subject;
                $scope.night_audit_admin_report_subject =  data.night_audit.night_audit_admin_report_subject;
                $scope.night_audit_guest_report_subject =  data.night_audit.night_audit_guest_report_subject;
                $scope.last_night_audit =  data.night_audit.last_night_audit;

                if(data.night_audit.night_audit_include_mb == 'true') {
                    $scope.night_audit_include_mb = true;
                }else {
                    $scope.night_audit_include_mb = false;
                }
                if(data.night_audit.complaint_in_nightaudit == 1) {
                    $scope.complaint_in_nightaudit = true;
                }else {
                    $scope.complaint_in_nightaudit = false;
                }

                for(var i = 0; i<$scope.night_audit_file_type_list.length ; i++) {
                    if($scope.night_audit_file_type_list[i] ==  data.night_audit.night_audit_file_type) {
                        $scope.night_audit_file_type = $scope.night_audit_file_type_list[i];
                        break;
                    }
                }
            }

            $scope.saveCallAccount = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
                if(fieldname == 'min_approval_duration'){
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 0 && parseInt(value) <= 60) {
                    }else{
                        $scope.message = " Error: Please enter correct number format.";
                        return;
                    }
                }
                if(fieldname == "min_approval_amount") {
                    var pattern = /^\d{0,3}$/;
                    if(!pattern.test(value)) {
                        $scope.message = "Error: Please correct number format.";
                        return;
                    }
                }

                if(fieldname == 'max_unmarked_count' || fieldname == 'max_unmarked_cost' || fieldname == 'max_approver_notify' || fieldname == 'max_close_notify'){
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 0 && parseInt(value) <= 1000) {
                    }else{
                        $scope.message = " Error: Please enter correct number format.";
                        return;
                    }
                }


                if(fieldname == 'check_call_classification_time') {
                    var pattern = /^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
                    if(!pattern.test(value)) {
                        $scope.message = "Error: Time format is not correct.";
                        return;
                    }
                }

                if(fieldname == 'call_reminder_time') {
                    var pattern = /^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/;
                    if(!pattern.test(value)) {
                        $scope.message = "Error: Time format is not correct.";
                        return;
                    }else {
                        data.fieldname = 'call_reminder_date';
                        data.fieldvalue = $scope.call_reminder_day +" "+value;
                    }
                }

                if(fieldname == 'call_reminder_day') {
                    data.fieldname = 'call_reminder_date';
                    data.fieldvalue = value+" "+$scope.call_reminder_time;
                }

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savecallaccount',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'call_account' )
                                $scope.getCallAccount('call_account');
                        }
                        else {
                            $scope.message = " Error: Can't connection databse.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

            $scope.saveNightAudit = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
                if(fieldname == 'night_audit_email_flag') {
                    if($scope.night_audit_email_flag_check == true) {
                        data.fieldvalue = 'NO';
                    }else if($scope.night_audit_email_flag_check == false) {
                        data.fieldvalue = 'YES';
                    }
                }

                if(fieldname == "night_audit_include_mb") {
                    if($scope.night_audit_include_mb == true) {
                        data.fieldvalue = 'true';
                    }else {
                        data.fieldvalue = 'false';
                    }
                }
                if(fieldname == "complaint_in_nightaudit") {
                    if($scope.complaint_in_nightaudit == true) {
                        data.fieldvalue = '1';
                    }else {
                        data.fieldvalue = '0';
                    }
                }

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savecallaccount',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 || status == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'night_audit' )
                                $scope.getCallAccount('night_audit');
                        }
                        else {
                            $scope.message = " Error: Can't connection databse.";
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });

            }

        });
    });