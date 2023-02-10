app.controller('ComplaintDutyCreateController', function ($scope, $rootScope, $http, $interval, $timeout, $httpParamSerializer, $uibModal, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Duty Manager Create';

    var MESSAGE_TITLE = 'Feedback Status';
    var INCOMP = ' INCOMPLETE';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.count= false;
		
	$scope.guest_types = ['In-House', 'Checkout', 'Arrival', 'Walk-in', 'House Complaint'];
    //$scope.guest_types = ['Walk-in', 'Arrival', 'Checkout', 'In-House', 'House Complaint'];

    if( AuthService.isValidModule('app.complaint.complaint_edit') == true )
        $scope.status_list = ['Acknowledge', 'Resolved'];
    else
        $scope.status_list = ['Pending', 'Resolved'];

    $scope.comps = [];
    $scope.property_list = [];
    $scope.guest_list = [{guest_id: 0, guest_name: 'Select Guest'}];
    $scope.severity_list = [];
    $scope.complaint_tasks = [];
    $scope.category_list = [];
    $scope.feedback_type_list = [];
    $scope.feedback_source_list = [];

    var depts_list = [];
    $scope.depts_list = [];
    $scope.selected_depts = [];
    $scope.disable_create=0;
    $scope.category_editable = AuthService.isValidModule('app.complaint.maincategory_add');
    $http.get('/list/department')
        .then(function (response) {
            depts_list = response.data;
            $scope.depts_list = depts_list;
        });

    $scope.complaint_setting = {};
    $http.get('/list/complaintsetting?property_id=' + profile.property_id).success( function(response) {
        $scope.complaint_setting = response;
    });    

    $scope.cancelComplaint = function() {
        $scope.complaint = {};
        $scope.location = {};
        $scope.requester = {};
        $scope.compn = {};
        var profile = AuthService.GetCredentials();
        $scope.requester.id = profile.id;
        $scope.requester.employee_id = profile.employee_id;
        $scope.requester.wholename = profile.first_name + ' ' + profile.last_name;
        $scope.requester.job_role = profile.job_role;

        $scope.complaint.guest_type = $scope.guest_types[0];
        $scope.complaint.guest_id = 0;
        $scope.complaint.profile_id = 0;
        $scope.complaint.housecomplaint_id = 0;
        $scope.complaint.feedback_source_id = 0;
        if( $scope.property_list.length > 0 )
            $scope.complaint.property_id = $scope.property_list[0].id;
        $scope.onChangedProperty();
        if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            $scope.complaint.housecomplaint_id = $scope.housecategory_list[0].id;

        $scope.complaint.new_guest = false;
        $scope.complaint.initial_response = '';
        $scope.complaint.status = $scope.status_list[0];
        $scope.complaint.severity = 1;
        $scope.complaint.category_id = 0;
        $scope.complaint.incident_time = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint_tasks = [];
        $scope.comps = [];

        $scope.selDept = [];
        $scope.count = false;

        $scope.files = [];
        $scope.guest_image = [];

        $scope.onChangeGuestType();
        
        $http.get('/list/complaint_datalist?&client_id=' + client_id)
         .then(function(response) {
            $scope.feedback_type_list = response.data.feedback_type_list; 
        //    $scope.feedback_source_list = response.data.feedback_source_list; 
            console.log(response.data.feedback_source_list);
            $scope.category_list = response.data.category_list;
            var alloption = { id: 0, name: 'Unclassified' };
            $scope.category_list.unshift(alloption); 

            $scope.severity_list = response.data.severity_list; 
            $scope.division_list = response.data.division_list; 

        //    if( $scope.feedback_source_list.length > 0 )
        //        $scope.complaint.feedback_source_id = $scope.feedback_source_list[0].id;

            $scope.complaint.feedback_type_id = 0;
            if( $scope.feedback_type_list.length > 0 )
            {
                var feedback_type = $scope.feedback_type_list.find(item => item.default_flag == 1);
                if( feedback_type )
                {
                    $scope.complaint.feedback_type_id = feedback_type.id;
                }
            }
            $scope.onChangeFeedbackType();
        });

        
    }

    $scope.init = function(ack) {        
        $scope.status_list[0] = ack;
        if( AuthService.isValidModule('app.complaint.complaint_edit') == true )
            $scope.status_list[0] = ack;
        else
            $scope.status_list[0] = 'Pending';

        $http.get('/list/property?client_id='+client_id)
            .then(function(response) {
                $scope.property_list = response.data;           
                if( $scope.property_list.length > 0 )
                {
                    $scope.complaint.property_id = $scope.property_list[0].id;
                    $scope.onChangedProperty();
                }
            });

        $http.get('/frontend/complaint/id')
         .then(function(response) {
            $scope.complaint_id = response.data.max_id + 1;
        });

        $http.get('/list/housecomplaint')
         .then(function(response) {
            $scope.housecategory_list = response.data;
        });

        $http.get('/list/locationtotallist?client_id=' + client_id)
            .then(function(response){
                $scope.location_list = response.data; 
            })

        $http.get('/frontend/complaint/stafflist?&client_id=' + client_id)
            .then(function(response){
                $scope.staff_list = response.data;
            }); 
        
        $http.get('/list/department')
            .then(function (response) {
                depts_list = response.data;
            });

        $http.get('/frontend/complaint/compensationtype?client_id='+client_id)
            .then(function(response) {
                $scope.compensations =  response.data;
            });

            $scope.compn = {};
        

         $timeout( function() {
            $scope.cancelComplaint();

            $scope.timer = $interval(function() {
                $scope.complaint.request_time = moment().format("HH:mm:ss");
             }, 1000);

        }, 1500 ); 
        // $scope.loadDepts();
    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.onChangedProperty = function() {
        $scope.location = {};
        $http.get('/list/roomlist?property_id=' + $scope.complaint.property_id)
            .then(function(response){
                $scope.roomlist = response.data;
            });
    }

    $scope.onChangeGuestType = function() {
        $scope.complaint.room = '';
        $scope.complaint.room_id = 0;
        $scope.complaint.guest_id = 0;
        $scope.complaint.profile_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.departure = '';
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.arrival = '';
        $scope.complaint.departure = '';
        $scope.complaint.nationality = '';
        $scope.complaint.new_guest = false; 
        if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            $scope.complaint.housecomplaint_id = $scope.housecategory_list[0].id;
    }

    $scope.createCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_maincategory.html',
            controller: 'ComplaintCategoryCtrl1',
            size: 'lg',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },
                category_list: function () {
                    return $scope.category_list;
                },                
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.main_subcategory_list = [];
    $scope.createMainSubCategory = function () {
        if( !($scope.complaint.category_id > 0) )
            return;

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_mainsubcategory.html',
            controller: 'ComplaintMainSubCategoryCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },
                main_subcategory_list: function () {
                    return $scope.main_subcategory_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }


    $scope.selected_category_list = [];    
    $scope.onChangedCategory = [];
    $scope.selected_severity_list = [];

    $scope.onChangeFeedbackType = function() {
        $scope.selected_category_list = $scope.category_list;
        $scope.selected_severity_list = $scope.severity_list;  
     
         $http.get('/list/feedbacksourcelist?type_id=' + $scope.complaint.feedback_type_id)
           .then(function(response){
            $scope.feedback_source_list = response.data.feedback_source_list;
           
            if( $scope.feedback_source_list.length > 0 )
            $scope.complaint.feedback_source_id = $scope.feedback_source_list[0].id;

            });
            
        
    }

    $scope.onChangedCategory = function($item, $model, $label) {
        $scope.complaint.category_id = $item.id;

        // set severity
        $scope.category_list.forEach(ele => {
            if( ele.id == $scope.complaint.category_id )
            {
                $scope.complaint.severity = ele.severity;               
            }   
        });

        // category does not severity
        if( !($scope.complaint.severity > 0) )
        {
            // set first severity as default
            $scope.complaint.severity = $scope.severity_list[0].id;
        }

        getMainSubcategoryList();
    }
    
    function getMainSubcategoryList()
    {
        // get sub category list
        var request = $scope.complaint;
        request.category_id = $scope.complaint.category_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/mainsubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.main_subcategory_list = response.data.content;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Severity.');
        })
        .finally(function() {

        });
    }

    $scope.onChangedSubCategory = function($item, $model, $label) {
        $scope.complaint.subcategory_id = $item.id;       
    }

    $scope.onChangedSeverity = function() {
        var request = $scope.complaint;
        request.id = 0;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changeseverity',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            
            // set severity
            $scope.category_list.forEach(ele => {
                if( ele.id == $scope.complaint.category_id )
                {
                    ele.severity = $scope.complaint.severity;               
                }   
            });

        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Severity.');
        })
        .finally(function() {

        });
    }


    $scope.getLocationList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.location = $item;
    };

    $scope.getRoomList = function(val) {
        if( val == undefined )
            val = "";

        var property_id = $scope.complaint.property_id;

        return $http.get('/list/roomlist?room=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.complaint.room_id = $item.id;

        $scope.complaint.guest_id = 0;
        $scope.complaint.profile_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.nationality = '';
        $scope.complaint.new_guest = false; 

        if( $scope.complaint.guest_type == 'In-House')   // check in user            
        {
            // find checkin user
            $http.get('/frontend/complaint/findcheckinguest?room_id=' + $item.id)
                .then(function(response){
                    var data = response.data;

                    if( data.code != 200 )
                    {
                        toaster.pop('info', MESSAGE_TITLE, 'No guest check in and change the type to CO');
                        return;
                    }

                    $scope.complaint.guest_name = data.data.guest_name;
                    $scope.complaint.first_name = data.data.first_name;
                    $scope.complaint.arrival = data.data.arrival;
                    $scope.complaint.departure = data.data.departure;
                    $scope.complaint.guest_id = data.data.guest_id;
                    $scope.complaint.profile_id = data.data.profile_id;
                    $scope.complaint.mobile = data.data.mobile;
                    $scope.complaint.email = data.data.email;
                    $scope.complaint.nationality = data.data.nationality;
                });
        }        
    };


    $scope.loadFiltersValue = function(value,query) {	    
        return depts_list.filter(item =>
            item.department.toLowerCase().indexOf(query.toLowerCase()) != -1                            
        );
    }
    
    $scope.addComplaint = function() {
        var date = new Date();
        //$scope.complaint_id = $scope.complaint_id + 1;

        var new_complaint = {
            guest_type : $scope.guest_types[0],
            guest_id : 0,
            housecomplaint_id : $scope.complaint.housecomplaint_id,
            property_id : $scope.complaint.property_id,
            new_guest: false,
            initial_response : "",
            status : $scope.complaint.status,
            severity: $scope.complaint.severity,
            incident_time  : $scope.complaint.incident_time,
            category_id :0,
            room : "",
            room_id : 0,
            guest_name : "",
            departure : "",
            mobile : "",
            email: "",
            arrival : ""
            //comment: $scope.complaint.comment
       }



        $scope.complaint = new_complaint;
       
    }

    
    
    $scope.addTask = function(message_flag) {
        var task = angular.copy($scope.complaint);
/*
        if( isValidTask(task, message_flag) == false )
            return;
*/

        if(task.room && task.guest_name)
        $scope.complaint_tasks.push(task);
        else if((task.guest_name)== '')
                {
                    toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Enter Guest Name');
                    return; 
                }
       
        
      

        // init main task
        $scope.addComplaint();
         if(!($scope.complaint_tasks.length <= 0))
        $scope.count = true;
    };

    $scope.removeTask = function(item) {
        $scope.complaint_tasks.splice($scope.complaint_tasks.indexOf(item),1);
        if($scope.complaint_tasks.length <= 0)
        $scope.count = false;
    };

    $scope.onChangeNewGuest = function() {
        if( $scope.complaint.new_guest == true )
        {
            $scope.complaint.guest_name = ''
            $scope.complaint.guest_id = 0;
            $scope.complaint.profile_id = 0;
            $scope.complaint.mobile = '';
            $scope.complaint.email = '';
            $scope.complaint.nationality = '';
        }
    }

    $scope.getStaffList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/complaint/stafflist?value=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.getGuestList = function(val) {
        if( $scope.complaint.new_guest == true )
            return;

        if( val == undefined )
            val = "";

        var request = {};        
        var property_id = $scope.complaint.property_id;
        request.client_id = client_id;
        request.property_id = property_id;
        request.value = val;
        request.guest_type = $scope.complaint.guest_type;
        request.room_id = $scope.complaint.room_id;

        return $http({
            method: 'POST',
            url: '/frontend/complaint/searchguestlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
                var list = response.data.content.slice(0, 10);
                return list.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.complaint.guest_name = $item.guest_name;
        $scope.complaint.first_name = $item.first_name;        
        $scope.complaint.guest_id = $item.guest_id;  
        $scope.complaint.profile_id = $item.profile_id;        
        $scope.complaint.mobile = $item.mobile;
        $scope.complaint.email = $item.email;      
        $scope.complaint.arrival = $item.arrival;
        $scope.complaint.departure = $item.departure;
        $scope.complaint.nationality = $item.nationality;
        $scope.complaint.new_guest = false;        
    };

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
    };

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);       
    };

    $scope.uploadGuestImg = function (guest_image) {
        $scope.guest_image = $scope.guest_image.concat(guest_image);
    };


    $scope.$watch('complaint.timepicker', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.complaint.incident_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }
    $scope.removeGuestImg = function ($index) {
        $scope.guest_image.splice($index, 1);
    }


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {

        if ($view == 'day') {
            var activeDate = moment().subtract(1,'days');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        
        else
     if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 5);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() > activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }
    
    $scope.createComplaint = function(){
	    var i = 0;
        $scope.disable_create = 1;
        
	    if( $scope.complaint_tasks.length )
	    {	    
    	    for(i = 0; i < $scope.complaint_tasks.length; i++)
    	    {
    		    $scope.createComplaintIndividial($scope.complaint_tasks[i]);
    		}
		    
		    if($scope.complaint.guest_name != "")
		        $scope.createComplaintIndividial($scope.complaint);
	    }
	    else
	    {
		    $scope.createComplaintIndividial($scope.complaint);
	    }
    }
     
   
    $scope.createComplaintIndividial = function(comp) {
	   
        var request = {};

        request.client_id = client_id;
        request.property_id = comp.property_id;
        request.loc_id = $scope.location.id;

        request.requestor_id = $scope.requester.employee_id;

        if( !(request.requestor_id > 0) ) {
            request.requestor_id = $scope.requester.id;            
        }
        
        if( !(request.requestor_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select requestor');
            $scope.disable_create=0;
            return;
        }

        if( !(request.loc_id > 0) && $scope.complaint_setting.main_complaint_location_mandatory > 0 && comp.feedback_type_id != 1 )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select location');
            $scope.disable_create=0;
            return;
        }

        request.guest_type = comp.guest_type;        
        request.room_id = comp.room_id;
        request.guest_id = comp.guest_id;
        request.profile_id = comp.profile_id;
        request.guest_name = comp.guest_name;
        request.first_name = comp.first_name;
        request.mobile = comp.mobile;
        request.email = comp.email;
        request.severity = comp.severity;
        request.status = comp.status;
        request.nationality = comp.nationality;
        request.initial_response = comp.initial_response;
        request.housecomplaint_id = comp.housecomplaint_id;
        request.category_id = comp.category_id;
        request.subcategory_id = comp.subcategory_id;
        request.feedback_type_id = comp.feedback_type_id;
        request.feedback_source_id = comp.feedback_source_id;
        request.compensation_type = comp.compensation_type;
        request.incident_time = comp.incident_time;
        request.solution = comp.solution;
        $scope.complaint.compensation_total=$scope.complaint.compensation_total+$scope.compn.cost;

        if ($scope.comps.length == 0 && !($scope.compn)){
            
            request.compen_list = [];
        }
        else if ($scope.comps.length == 0 && $scope.compn.id > 0){
            $scope.comps.push($scope.compn);
            request.compen_list = $scope.comps;
        }else{
            request.compen_list = $scope.comps;
        }
       
        if( request.guest_type != 'Walk-in' && request.guest_type != 'Arrival' && request.guest_type != 'House Complaint')   // in-house guest complaint
        {
            if( !(request.room_id > 0) )
            {
                toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select room for In-house complaint');
                $scope.disable_create=0;
                return;       
            }

            if( !(request.guest_id > 0) )
            {
                toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select guest for In-house complaint');
                $scope.disable_create=0;
                return;       
            }

            request.new_guest = 0;
        }
        else if( request.guest_type == 'House Complaint' )
        {

        }
        else    // walk in guest complaint
        {
            request.new_guest = comp.new_guest ? 1 : 0;
             if( (request.new_guest == 0) )
            {
                if( (request.guest_id == 0) )
                {
                toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select guest');
                $scope.disable_create=0;
                return;       
                }
            }    
            else
            {
                if((request.guest_name)== '')
                {
                    toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Enter Name');
                    $scope.disable_create=0;
                    return; 
                }
                else if((request.guest_name!= '')&&((request.mobile == '') && (request.email == '')))
                {
                    toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Enter mobile or email');
                    $scope.disable_create=0;
                    return; 
                }
            }
        }

        request.comment = $scope.complaint.comment;

        //not working
        if( !request.comment  )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not enter complaint');
            $scope.disable_create=0;
            return;
        }
        //window.alert(request.category_id);
        if( request.category_id == 0 && $scope.complaint_setting.main_complaint_maincategory_mandatory > 0 && request.feedback_type_id != 1)
        {            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not enter complaint category');
            $scope.disable_create=0;
            return;
        }

        console.log(request);
       
        if ($scope.selected_depts.length > 6) {
            toaster.pop('error', MESSAGE_TITLE, 'Add a maximum of 6 tags.');
            return;
        }

        request.depts_list = $scope.selected_depts.map(item => item.id);

        $http({
            method: 'POST',
            url: '/frontend/complaint/post',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.disable_create=0;
            toaster.pop('success', MESSAGE_TITLE, response.data.message);

            // upload files
            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/complaint/uploadfiles',
                    data: {
                        id: response.data.id,
                        files: $scope.files
                    }
                }).then(function (response) {
                    $scope.files = [];
                }, function (response) {
                    $scope.files = [];
                    if (response.status > 0) {
                        $scope.errorMsg = response.status + ': ' + response.data;
                    }
                }, function (evt) {
                    $scope.progress = 
                        Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                });
            }
            if ($scope.guest_image && $scope.guest_image.length) {
                Upload.upload({
                    url: '/frontend/complaint/uploadguestimage',
                    data: {
                        id: response.data.id,
                        guest_image: $scope.guest_image
                    }
                }).then(function (response) {
                    $scope.guest_image = [];
                    $scope.progress_img = 0;
                }, function (response) {
                    $scope.guest_image = [];
                    $scope.progress_img = 0;
                    if (response.status > 0) {
                        $scope.errorMsg = response.status + ': ' + response.data.files;
                    }
                }, function (evt) {
                    $scope.progress_img =
                        Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                });
            }
            $scope.cancelComplaint();
            // $scope.pageChanged();
            $scope.complaint_id = response.data.id + 1;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
            $scope.disable_create=0;
        })
        .finally(function() {

        });
    }

    $scope.setComplaintCategoryList = function(list) {
        $scope.category_list = list;
        $scope.complaint.subcategory_id = 0;        
        $scope.complaint.subcategory_name = '';
    }

    $scope.setComplaintMainSubCategoryList = function(list) {
        $scope.main_subcategory_list = list;
    }

    $scope.onSearchCheckoutGuest = function()
    {        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/checkout_guest_dialog.html',
            controller: 'CheckoutGuestListCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                property_id: function () {
                    return $scope.complaint.property_id;
                },
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                $scope.complaint.room = data.room;
                $scope.complaint.room_id = data.room_id;
                $scope.complaint.guest_id = data.guest_id;
                $scope.complaint.profile_id = data.profile_id;
                $scope.complaint.guest_name = data.guest_name;        
                $scope.complaint.mobile = data.mobile;
                $scope.complaint.email = data.email;
                $scope.complaint.new_guest = false; 
                $scope.complaint.first_name = data.first_name;
                $scope.complaint.arrival = data.arrival;
                $scope.complaint.departure = data.departure;
                $scope.complaint.nationality = data.nationality;
            }
        }, function () {

        });  
    }

    $scope.addCompensationType = function(cost) {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.client_id = profile.client_id;
        request.property_id = profile.property_id;
        request.compensation = $scope.compn.compensation;
        request.cost = cost;  

        $http({
            method: 'POST',
            url: '/frontend/complaint/addcompensationtype',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            var data = response.data;
            if( data.code == 200 )
            {
                $scope.compensations =  response.data.list;    

                var item = {};
                item.id = response.data.compensation_id;
                item.cost = 0;
                item.compensation = $scope.compn.compensation;

                $scope.onCompensationSelect(item, null, null);          
                
                toaster.pop('success', MESSAGE_TITLE, 'New compensation type has been added');
            }
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post compensation.');
        })
        .finally(function() {

        }); 

    }

    $scope.onCompensationSelect = function($item, $model, $label) {
        var compensation_id = $item.id;
        $scope.compn = $item;   
    }

    $scope.onDepartmentSelect = function(compn, $item, $model, $label) {
        compn.dept_id = $item.id;        
    }

    $scope.onProviderSelect = function(compn, $item, $model, $label) {
        compn.provider_id = $item.id;            
    }

    $scope.addCompensation = function (message_flag) {
        
        var comp = $scope.compn;
       

        $scope.comps.push(comp);

        // init main task
        $scope.addMainComp();
    }
    $scope.removeCompensation = function (item) {
        $scope.compss.splice($scope.comps.indexOf(item), 1);
    }

    $scope.addMainComp = function () {
        var date = new Date();

        $scope.compn = {
            approval_route_id: 0,
            client_id: 4,
            comment: "",
            cost: "",
            department: "",
            dept_id: 0,
            id: 0,
            name: "",
            property_id: 4,
            provider_id: 0,
            provider_name: "",
            total: 0,
            
        }

     //   $scope.compn = new_comp;

        
    }
    
});

app.controller('ComplaintCategoryCtrl1', function ($scope, $uibModalInstance, $http, AuthService, complaint, category_list) {
    $scope.complaint = complaint;
    $scope.cateory_list = category_list;
    $scope.complaint.new_severity = $scope.severity_list[0].id;
    $scope.complaint.new_division = $scope.division_list[0].id;
    $scope.selected_category_id = 0;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.complaint.category_new_name;
        request.severity = $scope.complaint.new_severity;
        request.division_id = $scope.complaint.new_division;
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        if (!request.name)
            return;

        $http({
            method: 'POST',
            url: '/frontend/complaint/createmaincategory',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.complaint.category_new_name = '';
            $scope.category_list = response.data;

            $scope.setComplaintCategoryList($scope.category_list);
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onClickUpdate = function(row) {    
        $scope.selected_category_id = row.id;    
        $scope.complaint.category_new_name = row.name;
        $scope.complaint.new_severity = row.severity;
        $scope.complaint.new_division = row.division_id;
    }

    $scope.onClickDelete = function(row) {
        var request = {};
        request.id = row.id;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/deletemaincategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);        
            $scope.complaint.category_new_name = '';        
            $scope.category_list = response.data;       

            var alloption = {id: 0, name : 'Unclassified'};
            $scope.category_list.unshift(alloption);

            $scope.setComplaintCategoryList($scope.category_list);    
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

});

app.controller('ComplaintMainSubCategoryCtrl', function ($scope, $uibModalInstance, $http, AuthService, complaint, main_subcategory_list) {
    $scope.complaint = complaint;
    $scope.main_subcategory_list = main_subcategory_list;
    $scope.selected_category_id = 0;

    $scope.createSubCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.complaint.category_new_name;
        request.category_id = $scope.complaint.category_id;
        
        if (!request.name)
            return;

        $http({
            method: 'POST',
            url: '/frontend/complaint/createmainsubcategory',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.complaint.category_new_name = '';
            $scope.main_subcategory_list = response.data.content;

            $scope.setComplaintMainSubCategoryList($scope.main_subcategory_list);
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onClickUpdate = function(row) {   
        $scope.selected_category_id = row.id;     
        $scope.complaint.category_new_name = row.name;        
    }

    $scope.onClickDelete = function(row) {
        var request = {};
        request.id = row.id;
    
        $http({
            method: 'POST',
            url: '/frontend/complaint/deletemainsubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);        
            $scope.complaint.category_new_name = '';        
            $scope.main_subcategory_list = response.data.content;       

            $scope.setComplaintMainSubCategoryList($scope.main_subcategory_list);    
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

});

app.controller('CheckoutGuestListCtrl', function ($scope, $uibModalInstance, $http, AuthService, property_id) {
    $scope.filter = {};
    $scope.guest_list = [];
    
    var profile = AuthService.GetCredentials();
    if( !(property_id > 0) )
        property_id = profile.property_id;

    var PAGE_SIZE = 20;
    $scope.totalGuestCount = PAGE_SIZE;

    $scope.onSearchCheckoutGuest = function () {

        var request = $scope.filter;
        request.property_id = property_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/searchcheckoutguest',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.guest_list = response.data;            
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    
    $scope.onRoomSelect1 = function ($item, $model, $label) {
        $scope.filter.room_id = $item.id;
    };

    $scope.onLoadMoreGuestHistory = function() {
        $scope.totalGuestCount += PAGE_SIZE; 
    }

    $scope.onSelectGuest = function(row)
    {
        $uibModalInstance.close(row);    
    }


    $scope.clear = function() {
        $scope.filter = {};
    }

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});


app.directive('myEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEnter);
                });

                event.preventDefault();
            }
        });
    };
});

app.directive('myEsc', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 27) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEsc);
                });

                event.preventDefault();
            }
        });
    };
});
