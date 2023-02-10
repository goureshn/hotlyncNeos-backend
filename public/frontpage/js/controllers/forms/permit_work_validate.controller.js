app.controller('PermitWorkValidationController', function ($scope, $rootScope, $http, $httpParamSerializer, $window, $interval, $uibModal, AuthService, toaster ,liveserver) {
    var MESSAGE_TITLE = 'Permit to Work Form';
		
	$scope.full_height = $window.innerHeight - 125;
	 $scope.tab_height = $window.innerHeight - 100;
		
    $scope.complaint = {};
    

    $scope.init = function(complaint) {
      
        var profile = AuthService.GetCredentials(); 
        $scope.complaint = complaint;
        $scope.comp = {};  
        $scope.complaint.lease_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.third_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.authorizing_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.start_time = moment().format('HH:mm:ss');
        $scope.complaint.end_time = moment().format('HH:mm:ss');
        
    }
    
    $scope.approve=function(){
	    $scope.complaint.status ="Approved Level ";
	 
	    $scope.UpdateStatus();
	    
    }

    $scope.UpdateStatus = function(){

        
    var profile = AuthService.GetCredentials();    
    $scope.complaint.property_id = profile.property_id;
    $scope.complaint.updated_by = profile.id;
    $scope.complaint.form_id = 2;
        $http({
            method: 'POST',
            url: '/frontend/forms/updatestatus',
            data: {
                    id: $scope.complaint.id,
                    status: $scope.complaint.status,
                    updated_by: $scope.complaint.updated_by,
                    property_id: $scope.complaint.property_id,
                    form_id: $scope.complaint.form_id,
                },
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                
                console.log(response);
                if ((response.data.error == 0) || (response.data.error == 1))
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Not Authorized to Approve');
                }
                else{

                

                toaster.pop('success', MESSAGE_TITLE, 'Status has been updated successfully');
                $scope.pageChanged();
                //$scope.UpdateIssue();
                }
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Status');
                console.log(response);
            })
            .finally(function() {
            });
    }
   

    $scope.$watch('complaint.leasedatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.lease_date =  moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
    });

    $scope.$watch('complaint.thirddatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.third_date =  moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
    });
   
    $scope.$watch('complaint.starttimepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.start_time =  moment(newValue).format('HH:mm');
       
    });
    $scope.$watch('complaint.endtimepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.end_time =  moment(newValue).format('HH:mm');
       
    });


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if ($view == 'day') {
            var activeDate = moment().subtract(1,'days');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        
        else if ($view == 'minute') {
            var activeDate = moment().subtract(5,'minute');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.beforeRender1 = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        
        if ($view == 'minute') {
            var activeDate = moment().subtract(5,'minute');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    
    $scope.save = function(){
        
    var profile = AuthService.GetCredentials();
    var request = {};
    request.property_id = profile.property_id;
    request.id = $scope.complaint.id;
    request = $scope.complaint;
    request.updated_by = profile.id;

        if ($scope.complaint.lease_signature == undefined)
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please sign the form before submission.');
					return;
        }

        $http({
            method: 'POST',
            url: '/frontend/forms/updatelease',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
                //$scope.pageChanged();
                $window.location.reload();
                $scope.$emit('onChangedPermitForm', response.data);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                console.log(response);
            })
            .finally(function() {
            });
    }  

    $scope.update_auth = function(){
        
        var profile = AuthService.GetCredentials();
        var request = {};
        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        request = $scope.complaint;
        request.updated_by = profile.id;
            if ($scope.complaint.auth_sign == undefined)
            {
                toaster.pop('info', MESSAGE_TITLE, 'Please sign the form before submission.');
                        return;
            }
    
            $http({
                method: 'POST',
                url: '/frontend/forms/updateauthr',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
                    $window.location.reload();
                    $scope.$emit('onChangedPermitForm', response.data);
                }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                    console.log(response);
                })
                .finally(function() {
                });
        }  
    

    $scope.update = function(){
        
        var profile = AuthService.GetCredentials();

        var request = {};
        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        request = $scope.complaint;
        request.updated_by = profile.id;
        
        if ($scope.complaint.goggles == 0 && $scope.complaint.safetyglasses == 0 && $scope.complaint.faceshield == 0 && $scope.complaint.gloves == 0 && $scope.complaint.welding == 0 && $scope.complaint.respirator == 0 
        && $scope.complaint.hearingprotection == 0 && $scope.complaint.apron == 0 && $scope.complaint.showereyewash == 0 && $scope.complaint.fallarrest == 0 && $scope.complaint.other == 0 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select "SAFETY AND PERSONAL PROTECTION EQUIPMENT REQUIREMENT"  before submission.');
					return;
        }

        if ($scope.complaint.permit_require == 'Yes')
        {
            var modalInstance = $uibModal.open({
                templateUrl: 'tpl/forms/modal/hotwork_form_create.html',
                controller: 'HotworkCreateCtrl',
                windowClass: 'app-modal-window'
              
                
            });
            modalInstance.result.then(function () {
               
            }, function () {

    
            });
        }

        app.controller('HotworkCreateCtrl', function($scope, $rootScope, $window, $uibModalInstance, AuthService, $http, toaster, $interval) {
      
            var MESSAGE_TITLE = 'Hotwork Permit Form';
            function initData() {
        
            $scope.request_company = '';
            $scope.request_permit = 0;
            $scope.request_location = '';
            $scope.hzrd_desc = '';
            $scope.cont_name = '';
            $scope.completed_by = '';
            $scope.position = '';
            $scope.personnel = 0;
            $scope.loc_watch = '';
            $scope.first_aid = '';
            $scope.precaution = '';
            $scope.worker_name = '';
            $scope.worker_pos = '';
            $scope.less_hazard = 0;
            $scope.project = 0;
            $scope.in_house = 0;
            $scope.contractor = 0;
            $scope.verify = 0;
            $scope.permit_area = 0;
            $scope.sprinkler = 0;
            $scope.equipment = 0;
            $scope.flammable = 0;
            $scope.combustible = 0;
            $scope.cleaning = 0;
            $scope.floor_protect = 0;
            $scope.enclose = 0;
            $scope.ventilation = 0;
            $scope.clean_flame = 0;
            $scope.vapours = 0;
            $scope.detector = 0;
            $scope.thirty_min = 0;
            $scope.sixty_min = 0;
            $scope.exceed = 0;
            $scope.briefed = 0;
            $scope.count = false;
            $scope.request_date = moment().format('YYYY-MM-DD HH:mm:ss');
           
    
        }
    
       
    
        $scope.tasks = [];
        initData();
    
        $scope.cancel = function() {
            initData();
            $uibModalInstance.dismiss();
        }
    
        $scope.$watch('requestdatepicker', function (newValue, oldValue) {
            if (newValue == oldValue)
                return;
    
            console.log(newValue);
            $scope.request_date = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
           
        });
        
    
        
        $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
            if ($view == 'day') {
                var activeDate = moment().subtract(1,'days');
                for (var i = 0; i < $dates.length; i++) {
                    if ($dates[i].localDateValue() < activeDate.valueOf())
                        $dates[i].selectable = false;
                }
            }
            
            else if ($view == 'minute') {
                var activeDate = moment().subtract(5,'minute');
                for (var i = 0; i < $dates.length; i++) {
                    if ($dates[i].localDateValue() < activeDate.valueOf())
                        $dates[i].selectable = false;
                }
            }
        }
       
        $scope.submit = function() {
               
            var request = {};
    
         
            var profile = AuthService.GetCredentials();
            request.property_id = profile.property_id;
           // request.permit_id = $scope.complaint.id;
            request.request_by = profile.id;
            request.request_company = $scope.request_company;
            request.request_permit = $scope.request_permit;
            request.request_location = $scope.request_location;
            request.hzrd_desc = $scope.hzrd_desc;
            request.cont_name = $scope.cont_name;
            request.completed_by = $scope.completed_by;
            request.position = $scope.position;
            request.personnel = $scope.personnel;
            request.loc_watch = $scope.loc_watch;
            request.first_aid = $scope.first_aid;
            request.precaution = $scope.precaution;
            request.worker_name = $scope.worker_name;
            request.worker_pos = $scope.worker_pos;
            request.request_date = $scope.request_date;
            
    
            request.less_hazard = $scope.less_hazard ? 1 : 0;
            request.project = $scope.project ? 1 : 0;
            request.in_house = $scope.in_house ? 1 : 0;
            request.contractor = $scope.contractor ? 1 : 0;
            request.verify = $scope.verify ? 1 : 0;
            request.permit_area = $scope.permit_area ? 1 : 0;
            request.sprinkler = $scope.sprinkler ? 1 : 0;
            request.equipment = $scope.equipment ? 1 : 0;
            request.flammable = $scope.flammable ? 1 : 0;
            request.combustible = $scope.combustible ? 1 : 0;
            request.cleaning = $scope.cleaning ? 1 : 0;
            request.floor_protect = $scope.floor_protect ? 1 : 0;
            request.enclose = $scope.enclose ? 1 : 0;
            request.ventilation = $scope.ventilation ? 1 : 0;
            request.clean_flame = $scope.clean_flame ? 1 : 0;
            request.vapours = $scope.vapours ? 1 : 0;
            request.detector = $scope.detector ? 1 : 0;
            request.thirty_min = $scope.thirty_min ? 1 : 0;
            request.sixty_min = $scope.sixty_min ? 1 : 0;
            request.exceed = $scope.exceed ? 1 : 0;
            request.briefed = $scope.briefed ? 1 : 0;
            /*
            if ($scope.signature == 0)
            {
                toaster.pop('info', MESSAGE_TITLE, 'Please sign the form before submission.');
                        return;
            }
     */
            
            console.log(request);
    
            $http({
                method: 'POST',
                url: '/frontend/forms/createhotworkrequest',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('success', MESSAGE_TITLE, 'Request have been submitted successfully');
                    $scope.cancel();
                    $window.location.reload();
                    console.log(response);
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                    $scope.isLoading = false;
                });
    
        }
    });

            $http({
                method: 'POST',
                url: '/frontend/forms/updatethird',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
                    //$window.location.reload();
                    $scope.$emit('onChangedPermitForm', response.data);
                }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                    console.log(response);
                })
                .finally(function() {
                });
        }  

   
});





