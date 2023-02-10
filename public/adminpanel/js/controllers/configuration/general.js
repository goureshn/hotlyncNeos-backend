define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('GeneralCtrl', function ($scope, $compile, $timeout, $http, $localStorage, $location/*, $http, initScript */) {
            $scope.password_compare_flag_check = false;
            $scope.account_security_flag = false;
            $scope.property_id = 0;
            $scope.sound_file = null;

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                $scope.property_id = $scope.properties[0].id;
                $scope.getPasswordSetting('password_setting');
                $scope.getPasswordSetting('account_setting');
                $scope.getPasswordSetting('site_directory');
                $scope.getPasswordSetting('notification');
                $scope.getPasswordSetting('smtp');
                $scope.getPasswordSetting('currency');
                $scope.getPasswordSetting('soundfile');
                $scope.getPasswordSetting('mobileserver');
            });

            $scope.password_type_list = [
                'None',
                'Alphanumeric',
                'Alphanumeric_Special',
            ];

            $scope.getPasswordSetting = function(setting_value)
            {
                var data = {};
                data.setting_group = setting_value ;
                data.property_id =   $scope.property_id;
                if(setting_value == 'site_directory')  data.property_id = 0;
                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/general',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.password_setting ) {
                            $scope.displayPasswordSetting(data);
                        }else if(data.account_setting ){
                            $scope.displayAccountSetting(data);
                        }else if(data.site_directory) {
                            $scope.displaySiteDirectory(data);
                        }else if(data.notification) {
                            $scope.displayNotification(data);
                        }else if(data.smtp) {
                            $scope.displaySmtp(data);
                        }else if(data.currency) {
                            $scope.displayCurrency(data);
                        }else if(data.mobileserver) {
                            $scope.displayMobileServer(data);
                        }else if(data.soundfile) {
                            $scope.displaySoundfile(data);
                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                    });
            }

            $scope.displayPasswordSetting = function (data) {
                $scope.password_minimum_length = data.password_setting.password_minimum_length;
                $scope.password_expire_date = data.password_setting.password_expire_date;
                $scope.last_use_password = data.password_setting.last_use_password;
                for(var i =0 ; i< $scope.password_type_list.length ; i++ ) {
                    if($scope.password_type_list[i] == data.password_setting.password_type) {
                        $scope.password_type = data.password_setting.password_type;
                        break;
                    }
                }

                if(data.password_setting.password_compare_flag == 1) {
                    $scope.password_compare_flag_check = true;
                }else {
                    $scope.password_compare_flag_check = false;
                }

            }

            $scope.displayAccountSetting = function (data) {
                $scope.login_session_timeout = data.account_setting.login_session_timeout;
                $scope.password_lock_attempts = data.account_setting.password_lock_attempts;
                $scope.only_allowed_domain_in_email_flag = data.account_setting.only_allowed_domain_in_email_flag;
                $scope.allowed_domain_in_email = data.account_setting.allowed_domain_in_email;
                $scope.allow_multiple_login = data.account_setting.allow_multiple_login == "1" ? 1 : 0;
            }

            $scope.displaySiteDirectory = function (data) {
                $scope.interface_host = data.site_directory.interface_host;
                $scope.hotlync_host = data.site_directory.hotlync_host;
                $scope.live_host = data.site_directory.live_host;
                $scope.public_live_host = data.site_directory.public_live_host;
                $scope.public_domain = data.site_directory.public_domain;
                $scope.export_server = data.site_directory.export_server;
                $scope.public_url = data.site_directory.public_url;
                $scope.hotlync_internal_host = data.site_directory.hotlync_internal_host;
                $scope.mobile_host = data.site_directory.mobile_host;
                $scope.low_free_size = data.site_directory.low_free_size;
                $scope.low_free_notify = data.site_directory.low_free_notify == "1" ? 1 : 0;
                $scope.low_free_emails = data.site_directory.low_free_emails;
            }

            $scope.displayNotification = function (data) {
                $scope.notification_smtp_server = data.notification.notification_smtp_server;
                $scope.notification_smtp_user = data.notification.notification_smtp_user;
                $scope.notification_smtp_password = data.notification.notification_smtp_password;
                $scope.notification_smtp_sender = data.notification.notification_smtp_sender;
                $scope.notification_smtp_port = data.notification.notification_smtp_port;
                $scope.notification_smtp_tls = data.notification.notification_smtp_tls;
                $scope.sms_gateway_settings = data.notification.sms_gateway_settings;
            }

            $scope.displaySmtp = function (data) {
                $scope.smtp_server = data.smtp.smtp_server;
                $scope.smtp_user = data.smtp.smtp_user;
                $scope.smtp_sender = data.smtp.smtp_sender;
                $scope.smtp_password = data.smtp.smtp_password;
                $scope.smtp_port = data.smtp.smtp_port;
                $scope.smtp_tls = data.smtp.smtp_tls;
            }

            $scope.displayCurrency = function (data) {
                $scope.currency = data.currency.currency;
            }

            $scope.displayMobileServer = function (data) {
                $scope.mobileserver_alarm_to_email = data.mobileserver.mobileserver_alarm_to_email;
            }

            $scope.displaySoundfile = function (data) {
                //$scope.soundfile = $location.protocol() + "://" + $location.host() + ":" + $location.port()+data.soundfile.soundfile;
                $scope.soundfile = data.soundfile.soundfile;
            }

            $scope.saveSound = function(fieldname , value, setting_group) {
                var fd = new FormData();
                fd.append("fieldname", fieldname);
                fd.append("property_id", $scope.property_id);
                fd.append("setting_group", setting_group);
                fd.append('file', $scope.sound_file);
                console.log(fd);
                $http.post('/backoffice/configuration/wizard/savegeneral', fd, {
                        transformRequest: angular.identity,
                        headers: {'Content-Type': undefined}
                    })
                    .success(function(response){
                        console.log(response);
                        if( response.success != 200 )
                        {
                            $scope.message = " Error: Can't connection databse.";
        
                        }
                        else
                        {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                        }
                    })
                    .error(function(data, status, headers, config){
                        console.log(status);
                    });
            }

            $scope.saveGeneral = function(fieldname , value, setting_group) {
                var data= {};
                data.property_id = $scope.property_id;
                data.fieldname = fieldname;
                data.fieldvalue = value;
                data.setting_group = setting_group;
                if(setting_group == 'site_directory')  data.property_id = 0;

                if(fieldname == 'password_compare_flag') {
                    if($scope.password_compare_flag_check == true) {
                        data.fieldvalue = 0;
                    }else {
                        data.fieldvalue = 1;
                    }
                }
                if(fieldname == 'account_security_flag') {
                    if($scope.account_security_flag == true) {
                        $scope.account_security_flag = false;
                    }else{
                        $scope.account_security_flag = true;
                    }
                    return;
                }

                if(fieldname == 'only_allowed_domain_in_email_flag') {
                    $scope.only_allowed_domain_in_email_flag = !$scope.only_allowed_domain_in_email_flag;
                    
                    data.fieldvalue = $scope.only_allowed_domain_in_email_flag ? 1 : 0;                    
                }

                if(fieldname == 'password_minimum_length') {
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 6 && parseInt(value) <= 32) {
                    }else{
                        $scope.message = " Error: Please enter correct number(6-32) format.";
                        return;
                    }
                }

                if(fieldname == "password_expire_date") {
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 0 && parseInt(value) <= 365) {
                    }else{
                        $scope.message = " Error: Please enter correct number(6-32) format.";
                        return;
                    }
                }

                if(fieldname == "last_use_password") {
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 0 && parseInt(value) <= 50) {
                    }else{
                        $scope.message = " Error: Please enter correct number(6-32) format.";
                        return;
                    }
                }

                if(fieldname == "login_session_timeout") {
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 0 && parseInt(value) <= 1440) {
                    }else{
                        $scope.message = " Error: Please enter correct number(6-32) format.";
                        return;
                    }
                }

                if(fieldname == "password_lock_attempts") {
                    var pattern = /^\d+$/;
                    if(pattern.test(value)&& parseInt(value) >= 1 && parseInt(value) <= 10) {
                    }else{
                        $scope.message = " Error: Please enter correct number(6-32) format.";
                        return;
                    }
                }

                $http({
                    method: 'POST',
                    url: '/backoffice/configuration/wizard/savegeneral',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if( data.success == 200 ) {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                            if( config.data.setting_group == 'password_setting' )
                                $scope.getPasswordSetting('password_setting');
                            if( config.data.setting_group == 'account_setting' )
                                $scope.getPasswordSetting('account_setting');
                            if( config.data.setting_group == 'site_directory' )
                                $scope.getPasswordSetting('site_directory');
                            if( config.data.setting_group == 'notification' )
                                $scope.getPasswordSetting('notification');
                            if( config.data.setting_group == 'smtp' )
                                $scope.getPasswordSetting('smtp');
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