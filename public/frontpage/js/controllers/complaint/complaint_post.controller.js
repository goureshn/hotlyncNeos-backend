'use strict';

app.controller('ComplaintPostController', function($scope, $http, $window, $interval, $timeout, $stateParams, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Complaint Status';
    var INCOMP = ' INCOMPLETE';

    var client_id = $stateParams.client_id;

    $scope.guest_types = ['In-House', 'Checkout', 'Arrival', 'Walk-in', 'House Complaint'];
    $scope.status_list = ['Pending', 'Resolved'];

    $scope.guest_list = [{guest_id: 0, guest_name: 'Select Guest'}];
    $scope.severity_list = [];
    $scope.complaint_tasks = [];
    $scope.count= false;
    $scope.compen_list = [];
    $scope.comp = {};
	$scope.comp_list = [];
	$scope.total=0;
	$scope.compensations =[];
    $scope.disable_create=0;
    $scope.feedback_type_list = [];
    $scope.feedback_source_list = [];
	

    $scope.includeMobile = false; 
		var screenWidth = $window.innerWidth;
		if (screenWidth < 550){
		    $scope.includeMobile = true;
		}

    
     $scope.cancelComplaint = function() {
        $scope.complaint = {};
        $scope.location = {};
        $scope.requester = {};
        $scope.complaint.guest_type = $scope.guest_types[0];
        $scope.complaint.guest_id = 0;
        $scope.complaint.housecomplaint_id = 0;
        $scope.complaint.feedback_source_id = 0;
        if( $scope.property_list && $scope.property_list.length > 0 )
        {
            $scope.complaint.property_id = $scope.property_list[0].id;
            $scope.onChangedProperty();
        }

        if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            $scope.complaint.housecomplaint_id = $scope.housecategory_list[0].id;

        $scope.complaint.new_guest = false;
        $scope.complaint.initial_response = '';
        $scope.complaint.status = $scope.status_list[0];
        $scope.complaint.severity = 1;
        $scope.complaint.category_id = 0;
        $scope.complaint.incident_time = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint_tasks = [];
        $scope.count = false;
        $scope.files = [];
         $scope.guest_image = [];
         $scope.recover_is_open = false;

        $scope.onChangeGuestType();
        $http.get('/list/complaint_datalist?&client_id=' + client_id)
        .then(function(response) {
           $scope.feedback_type_list = response.data.feedback_type_list; 
           $scope.feedback_source_list = response.data.feedback_source_list; 

           $scope.category_list = response.data.category_list;
           var alloption = { id: 0, name: 'Unclassified' };
           $scope.category_list.unshift(alloption); 

           $scope.severity_list = response.data.severity_list; 

           if( $scope.feedback_source_list.length > 0 )
               $scope.complaint.feedback_source_id = $scope.feedback_source_list[0].id;

           $scope.complaint.feedback_type_id = 0;
           if( $scope.feedback_type_list.length > 0 )
           {
               var feedback_type = $scope.feedback_type_list.find(function(item) { return item.default_flag == 1 });
               if( feedback_type )
               {
                   $scope.complaint.feedback_type_id = feedback_type.id;
               }
           }
           $scope.onChangeFeedbackType();
       });
    }

    $scope.init = function() { 
	  
	  
       
    	$http.get('/list/property?client_id='+client_id)
    	 .then(function(response) {
            $scope.property_list = response.data;	
        });

        $http.get('/frontend/complaint/id')
         .then(function(response) {
            $scope.complaint_id = response.data.max_id + 1;
        });

        $http.get('/list/housecomplaint')
         .then(function(response) {
            $scope.housecategory_list = response.data;
        });

        $http.get('/list/severitylist')
         .then(function(response) {
            $scope.severity_list = response.data; 
        });

        $http.get('/list/severitylist')
         .then(function(response) {
            $scope.severity_list = response.data; 
        });

        $http.get('/list/locationtotallist?client_id=' + client_id)
            .then(function(response){
                $scope.location_list = response.data; 
            })

        $http.get('/list/employeelist?client_id=' + client_id)
            .then(function(response){
                $scope.employee_list = response.data;
            });   

        $http.get('/frontend/complaint/maincategorylist?client_id=' + client_id)
            .then(function (response) {
                $scope.category_list = response.data;
                var alloption = { id: 0, name: 'Unclassified' };
                $scope.category_list.unshift(alloption); 
            }); 
        $http.get('/list/userlist?property_id=' + $scope.property_id + '&dept_id=0')
            .then(function(response) {
                $scope.staff_list = response.data;                
        }); 

            
            $scope.complaint_recovery_is_open = false;
            $scope.recover_is_open = false;
        $timeout( function() {
            $scope.cancelComplaint();

            $scope.timer = $interval(function() {
                $scope.complaint.request_time = moment().format("HH:mm:ss");
             }, 1000);

        }, 1500 ); 
    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });
    
    $scope.onSameLoc = function(){
	    
	    if($scope.complaint.same_loc)
	    {
		    if($scope.complaint.room)
		    {
			     //$scope.location.name=$scope.complaint.room;
			     $scope.locations = $scope.location_list.filter(function(item) { 
	        			if((item.name)== ($scope.complaint.room)){
		        			//window.alert(item.name);
	        			
							return true;} });
							$scope.location=$scope.locations[0];
					 //window.alert($scope.location.name);		
		    }
		   
		    else
		    {
			    toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please enter guest room first');
			    $scope.complaint.same_loc=0;
				return
		    }
	    }
	    else
	    {
		    
		 	$scope.location={};   
		 	return;
	    }
	    
	    
	}
    
    
    
    $scope.fetchComp = function(){

        $scope.recover_is_open = true;
	      
	    $http.get('/frontend/complaint/compensationtype?client_id='+client_id)
            .then(function(response) {
                $scope.compensations =  response.data;              
            });
	    
    }
    
         
     $scope.onCompensationSelect = function($item, $model, $label) {
        var compensation_id = $item.id;
        $scope.comp = $item;
        //window.alert($scope.comp.compensation);
        $scope.comp.approve_flag = $item.approval_route_id > 0;   
    }

    $scope.onProviderSelect = function(comp, $item, $model, $label) {
        comp.provider_id = $item.id;            
    }
    
    $scope.addCompensationType = function(cost) {
       // var profile = AuthService.GetCredentials();
       if(!($scope.requester.property_id))
       {
        toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please enter employee details');
        return
        }

        var request = {};

        request.client_id = client_id;
        request.property_id = $scope.requester.property_id;
        request.compensation = $scope.comp.compensation;
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
                item.approval_route_id = 0;
                item.cost = 0;
                item.compensation = $scope.comp.compensation;

                $scope.onCompensationSelect(item, null, null);          
                
                toaster.pop('success', MESSAGE_TITLE, 'New compensation type has been added');
            }
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post compensation.');
        })
        .finally(function() {

        }); 

    }
    $scope.cancelCompensation = function() {
        $scope.comp = {};
        
              
    }
    $scope.cancelCompList=function(){
	    	$scope.cancelCompensation();
            $scope.compen_list=[];
            $scope.total=0;
            
            }
    
     $scope.addComp = function() {
	     
	     //$scope.comp.total=$scope.total;
/* 
	     $scope.compen={}
	      //request.id = $scope.complaint.id;
        $scope.compen.compensation_id = $scope.comp.id;
        $scope.compen.comment = $scope.comp.comment;
        //request.user_id = $scope.requester.id;
        $scope.compen.cost = $scope.comp.cost;
        */
		

        var task = angular.copy($scope.comp);
/*
        if( isValidTask(task, message_flag) == false )
            return;
*/

        if(task.id  && task.cost && task.compensation)
        {
	    $scope.total=$scope.comp.cost+$scope.total;    
        $scope.compen_list.push(task);
       
        }
       else
            {
	             if(task.cost==0)
	             toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please enter cost more than 0');
	             else
	             toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please check all fields');
                    return;
	            
            }    
       
        
      

        // init main task
        $scope.cancelCompensation();
         
    };

    $scope.removeComp = function(item) {
	     $scope.total=$scope.total-item.cost;
        $scope.compen_list.splice($scope.compen_list.indexOf(item),1);
       
    };
    

    
    $scope.selected_category_list = [];
    $scope.selected_severity_list = [];

    $scope.onChangeFeedbackType = function() {
        $scope.selected_category_list = $scope.category_list;
        $scope.selected_severity_list = $scope.severity_list;        
    }
    
    

    $scope.onChangedProperty = function() {
        $scope.location = {};
        $scope.complaint_tasks = [];
        $http.get('/list/roomlist?property_id=' + $scope.complaint.property_id)
            .then(function(response){
                $scope.roomlist = response.data;
            });
    }

    $scope.onChangeGuestType = function() {
        $scope.complaint.room = '';
        $scope.complaint_tasks = [];
        $scope.complaint.room_id = 0;
        $scope.complaint.guest_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.departure = '';
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.arrival = '';
        $scope.complaint.departure = '';
        $scope.complaint.new_guest = false; 
        if($scope.complaint.same_loc==1)
        {
	     $scope.complaint.same_loc=0;
	     $scope.location={};   
        }
        if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            $scope.complaint.housecomplaint_id = $scope.housecategory_list[0].id;
    }

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
        $scope.complaint.guest_name = '';        
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
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
                });
        }        
    };
    
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
        {
        $scope.complaint_tasks.push(task);
	        if($scope.complaint.same_loc)
	        {
		        $scope.complaint.same_loc=0;
	        	$scope.location={};
	        }
        }
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
            $scope.complaint.mobile = '';
            $scope.complaint.email = '';
        }
    }

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
        $scope.complaint.mobile = $item.mobile;
        $scope.complaint.email = $item.email;      
        $scope.complaint.arrival = $item.arrival;
        $scope.complaint.departure = $item.departure;
        $scope.complaint.new_guest = false;        
    };

    $scope.onRequesterSelect = function ($item, $model, $label) {
	    
        $scope.requester = $item;
        //window.alert($scope.requester.id);
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
	    var i=0;
	    
	    $scope.disable_create=1;	    
	    if(($scope.complaint_tasks.length))
	    {
	    
    	    for(i=0; i<($scope.complaint_tasks.length);i++)
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

    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }
    $scope.removeGuestImg = function ($index) {
        $scope.guest_image.splice($index, 1);
    }

    $scope.createComplaintIndividial = function(comp) {
	   
        var request = {};

        request.client_id = client_id;
        request.property_id = comp.property_id;
        request.loc_id = $scope.location.id;

        request.requestor_id = $scope.requester.id;
        
        if( !(request.requestor_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select requestor');
            $scope.disable_create=0;
            return;
        }

        if( !(request.loc_id > 0) && comp.feedback_type_id != 1)
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select location');
            $scope.disable_create=0;
            return;
        }

        if( request.category_id == 0 && comp.feedback_type_id != 1)
        {            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not enter complaint category');
            $scope.disable_create=0;
            return;
        }

        request.guest_type = comp.guest_type;        
        request.room_id = comp.room_id;
        request.guest_id = comp.guest_id;
        request.guest_name = comp.guest_name;
        request.first_name = comp.first_name;
        request.mobile = comp.mobile;
        request.email = comp.email;
        request.severity = comp.severity;
        request.status = comp.status;
        request.initial_response = comp.initial_response;
        request.housecomplaint_id = comp.housecomplaint_id;
        request.category_id = comp.category_id;
        request.feedback_type_id = comp.feedback_type_id;
        request.feedback_source_id = comp.feedback_source_id;
        request.incident_time = comp.incident_time;
        request.solution = comp.solution;

          if($scope.compen_list.length)
            {
	            request.compen_list = $scope.compen_list;
	            console.log(request.compen_list);
            }
            request.total=$scope.total;

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

        console.log(request);

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
                    $scope.progress = 0;
                }, function (response) {
                    $scope.files = [];
                    $scope.progress = 0;
                    if (response.status > 0) {
                        $scope.errorMsg = response.status + ': ' + response.data.files;
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
                        $scope.errorMsg = response.status + ': ' + response.data;
                    }
                }, function (evt) {
                    $scope.progress_img =
                        Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                });
            }
          

            $scope.cancelComplaint();
            $scope.cancelCompList();
            $scope.complaint_id = response.data.id + 1;
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
             $scope.disable_create=0;
        })
        .finally(function() {

        });
    }



   

});
