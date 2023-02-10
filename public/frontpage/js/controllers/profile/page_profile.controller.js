app.controller('PageProfileController', function($scope, $http, $httpParamSerializer,  AuthService, toaster, $uibModal, $rootScope, $timeout) {
    var MESSAGE_TITLE = 'Profile Page';

    // TODO
    $scope.authError = '';
    $scope.agent = {};
    $scope.agent.viewimg = false;
    $scope.auth_svc = AuthService;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    $scope.agent.firstname = profile.first_name;
    $scope.agent.lastname = profile.last_name;
    if(profile.notify_status == 1){
        $scope.agent.notifystatus = true;
    }else{
        $scope.agent.notifystatus = false;
    }
    if(profile.wakeupnoti_status == 1){
        $scope.agent.wakeupnotifystatus = true;
    }else{
        $scope.agent.wakeupnotifystatus = false;
    }
    if(profile.callaccountingnoti_status == 1){
        $scope.agent.callaccountingnotifystatus = true;
    }else{
        $scope.agent.callaccountingnotifystatus = false;
    }
    $scope.agent.jobrole = profile.job_role;
    $scope.agent.email = profile.email;
    $scope.agent.mobile = profile.mobile;
    $scope.agent.emp_id = profile.employee_id;
    //window.alert(profile.mobile_edit_disable);
    $scope.agent.mobile_disable = profile.mobile_edit_disable;
    $scope.agent.department = profile.department;
    $scope.agent.picture = profile.picture;
    if( profile.shift_info )
    {
        $scope.agent.vacation_start = profile.shift_info.vaca_start_date;
        $scope.agent.vacation_end = profile.shift_info.vaca_end_date;
        $scope.agent.delegated_user_id = profile.shift_info.delegated_user_id;
        $scope.agent.delegated_user = profile.shift_info.delegated_user;        
    }
    else
    {
        $scope.agent.vacation_start = moment().format('YYYY-MM-DD');
        $scope.agent.vacation_end = moment().format('YYYY-MM-DD');
        $scope.agent.delegated_user_id = 0;
        $scope.agent.delegated_user = '';           
    }
    
    $scope.userlist = [];

    function getDelegateUserList() {
        var request = {};

        request.property_id = property_id;
        request.vacation_start = $scope.agent.vacation_start;
        request.vacation_end = $scope.agent.vacation_end;        

        $http({
                method: 'POST',
                url: '/frontend/user/delegatelist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.userlist = response.data;
            }).catch(function(response) {
                
            })
            .finally(function() {
                
            });    
    }    

    getDelegateUserList();

    $scope.myImage='';
    $scope.myCroppedImage='';
    $scope.cropType="circle";

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',        
        startDate: moment($scope.agent.vacation_start).format('YYYY-MM-DD'),
        endDate: moment($scope.agent.vacation_end).format('YYYY-MM-DD'),
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.agent.vacation_start = $scope.daterange.substring(0, '2016-01-01'.length);
        $scope.agent.vacation_end = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);        
    });

    //notification flag from user profile
    $scope.complaint_setting = profile.complaint_setting;
    $scope.change_notifi = function(param,val) {
        switch(param) {
            case 'notifystatus' :
                $scope.agent.notifystatus = val;
                break;
            case 'wakeupnotifystatus' :
                $scope.agent.wakeupnotifystatus = val;
                break;
            case 'callaccountingnotifystatus' :
                $scope.agent.callaccountingnotifystatus = val;
                break;
            case 'compensation_change' :
                $scope.complaint_setting.compensation_change = val;
                break;
            case 'complaint_create' :
                $scope.complaint_setting.complaint_create = val;
                break;
            case 'complaint_notify' :
                $scope.complaint_setting.complaint_notify = val;
                break;
            case 'subcomplaint_complete' :
                $scope.complaint_setting.subcomplaint_complete = val;
                break;
            case 'subcomplaint_create' :
                $scope.complaint_setting.subcomplaint_create = val;
                break;
            case 'Informational' :
                $scope.complaint_setting.severity_filter.Informational = val;
                break;
            case 'Major' :
                $scope.complaint_setting.severity_filter.Major = val;
                break;
            case 'Minor' :
                $scope.complaint_setting.severity_filter.Minor = val;
                break;
            case 'Moderate' :
                $scope.complaint_setting.severity_filter.Moderate = val;
                break;
            case 'Serious' :
                $scope.complaint_setting.severity_filter.Serious = val;
                break;
        }
        $scope.set_notification();
    }

    $scope.change_notifi1 = function(param,val) {
        switch(param) {
            case 'notifystatus' :
                $scope.agent.notifystatus = val;
                $scope.set_notification1();
                break;
            case 'wakeupnotifystatus' :
                $scope.agent.wakeupnotifystatus = val;
                $scope.set_wakeupnotification();
                break;
            case 'callaccountingnotifystatus' :
                $scope.agent.callaccountingnotifystatus = val;
                $scope.set_callaccountingnotification();
                break;
        }
    }

    $scope.set_notification = function() {
        var user_id = profile.id;
        var request = {};
        request.user_id = user_id;
        request.meta_key = 'complaint_setting';
        request.met_value = $scope.complaint_setting;

        $http({
            method: 'POST',
            url: '/frontend/user/setnotification',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.complaint_setting = response.data;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.set_notification1 = function() {
        var user_id = profile.id;
        var request = {};
        request.user_id = user_id;
        request.meta_key = 'notify_status';
        request.met_value = $scope.agent.notifystatus;
        $http({
            method: 'POST',
            url: '/frontend/user/setguestnotification',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
            }).catch(function(response) {
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.set_wakeupnotification = function() {
        var user_id = profile.id;
        var request = {};
        request.user_id = user_id;
        request.meta_key = 'wakeupnoti_status';
        request.met_value = $scope.agent.wakeupnotifystatus;
        $http({
            method: 'POST',
            url: '/frontend/user/setguestwakeupnotification',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
            }).catch(function(response) {
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
    $scope.set_callaccountingnotification = function() {
        var user_id = profile.id;
        var request = {};
        request.user_id = user_id;
        request.meta_key = 'callaccountingnoti_status';
        request.met_value = $scope.agent.callaccountingnotifystatus;
        $http({
            method: 'POST',
            url: '/frontend/user/setguestcallaccountingnotification',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
            }).catch(function(response) {
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
    // get permission
    var permission = profile.permission;
    $scope.complaint_setting_show = false;
    $scope.call_center_show = false;
    for(var i = 0 ; i < permission.length ; i ++ ) {
        var name = permission[i].name.substr(0, permission[i].name.lastIndexOf('.'));
        if( name == 'app.complaint') $scope.complaint_setting_show = true;
        if( name == 'app.calls') $scope.call_center_show = true;
    }

    $scope.$watch('complaint_notification', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;
        var user_id = profile.id;
        var request = {};
        request.user_id = user_id;
        request.method = 'complaint';
        request.notifi_flag = $scope.complaint_notification;

        $http({
            method: 'POST',
            url: '/frontend/user/notification',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    });

    $scope.onUpdateProfile = function () {
        var request = {};
        request.email = $scope.agent.email;
        request.mobile = $scope.agent.mobile;
        request.vacation_start = $scope.agent.vacation_start;
        request.vacation_end = $scope.agent.vacation_end;
        request.delegated_user_id = $scope.agent.delegated_user_id;        

        $http({
            method: 'POST',
            url: '/frontend/user/updateprofile',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {                
                console.log(response);

                if( response.data.code == 200 )
                    toaster.pop('success', MESSAGE_TITLE, response.data.message);
                else
                {
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);    
                }
                
                var profile = AuthService.GetCredentials();

                profile.email = $scope.agent.email;
                profile.mobile = $scope.agent.mobile;                
                if( profile.shift_info == undefined )
                {
                    profile.shift_info = {};
                }

                profile.shift_info.vaca_start_date = $scope.agent.vacation_start;
                profile.shift_info.vaca_end_date = $scope.agent.vacation_end;
                profile.shift_info.delegated_user_id = $scope.agent.delegated_user_id;
                profile.shift_info.delegated_user = $scope.agent.delegated_user;

                AuthService.SetCredentials(profile);

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.onUserSelect = function($item, $model, $label) {
        $scope.agent.delegated_user_id = $item.id;
    }

    $scope.onShowImage_modal = function () {
        var modalInstance = $uibModal.open({
            restrict: 'EA',
            templateUrl: 'agent_image.html',
            controller: 'ImageProfileCtrl',
            scope: $scope,
            resolve: {
                agent: function () {
                    return $scope.agent;
                }
            }
        });

        modalInstance.result.then(function (imgSrc) {
            $scope.agent.picture = imgSrc;
            angular.element(document.querySelector('#agentimg')).attr( 'src', imgSrc);

        }, function () {

        });
    };

});

app.controller('ImageProfileCtrl', function($scope, $uibModalInstance, agent ,$http, $httpParamSerializer,  AuthService, toaster, $timeout) {

    $scope.cropType="circle";
    var profile = AuthService.GetCredentials();

    $scope.myImage = profile.picture;

    $scope.image = {};
    $scope.image.myCroppedImage = '';

    $scope.onType = function(type){
        $scope.cropType = type;
    }

    var handleFileSelect=function(evt) {
        var file=evt.currentTarget.files[0];
        var reader = new FileReader();
        reader.onload = function (evt) {
            $scope.$apply(function($scope){
                $scope.myImage=evt.target.result;
            });
        };
        reader.readAsDataURL(file);
    };


    $uibModalInstance.opened.then(
        $timeout(function() {
            console.log('hello');
            angular.element(document.querySelector('#fileInput_1')).on('change',handleFileSelect);
        }, 500));

    $scope.onSaveImage = function (param) {

        var imgName = profile.first_name+"_"+profile.last_name+"_"+profile.id+".png";
        var imgSrc = $scope.image.myCroppedImage;
        var request = {};
        request.image = imgSrc;
        request.image_name = imgName;

        $http({
            method: 'POST',
            url: '/frontend/user/uploadimage',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {                
                AuthService.SetCredentials(profile);
                $uibModalInstance.close(imgSrc);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});
