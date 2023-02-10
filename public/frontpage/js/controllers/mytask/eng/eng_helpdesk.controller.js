'use strict';

app.controller('EngPostController', function($scope, $http, $window, $interval, $timeout, $stateParams, toaster, Upload) {
    var MESSAGE_TITLE = 'Engineering Status';
    var INCOMP = ' INCOMPLETE';

    var client_id = $stateParams.client_id;
	//var prop_id;

    //$scope.guest_types = ['In-House CI', 'In-House CO', 'Arrival', 'Walk-in', 'House Complaint'];
    $scope.status_list = ['Pending'];

    $scope.guest_list = [{guest_id: 0, guest_name: 'Select Guest'}];
    $scope.severity_list = [];
    $scope.complaint_tasks = [];
    $scope.count= false;
    $scope.disable_create=0;
    
    $scope.includeMobile = false; 
		var screenWidth = $window.innerWidth;
		if (screenWidth < 550){
		    $scope.includeMobile = true;
		}

    
     $scope.cancelEng = function() {
        $scope.eng = {};
        //$scope.location = {};
        $scope.requester = {};
       // $scope.eng.guest_type = $scope.guest_types[0];
       // $scope.complaint.guest_id = 0;
        $scope.eng.housecomplaint_id = 0;
		$scope.eng.property_id=0;

        // if( $scope.property_list && $scope.property_list.length > 0 )
            // $scope.eng.property_id = $scope.property_list[0].id;

        // if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            // $scope.eng.housecomplaint_id = $scope.housecategory_list[0].id;

        //$scope.complaint.new_guest = false;
        $scope.eng.initial_response = '';
        $scope.eng.status = $scope.status_list[0];
        $scope.eng.severity = 1;
        //$scope.complaint.incident_time = moment().format('HH:mm:ss');
        $scope.complaint_tasks = [];
        $scope.count = false;
        $scope.files = [];

        //$scope.onChangeGuestType();
    }

    $scope.init = function() {        
    	// $http.get('/list/property?client_id='+client_id)
    	 // .then(function(response) {
            // $scope.property_list = response.data;
				//$scope.eng.property_id = $scope.property_list[0].id;
        // });

        $http.get('/frontend/eng_mytask/id')
         .then(function(response) {
            $scope.eng_id = response.data.max_id + 1;
        });

        // $http.get('/list/housecomplaint')
         // .then(function(response) {
            // $scope.housecategory_list = response.data;
        // });

        $http.get('/list/severitylistit')
         .then(function(response) {
            $scope.severity_list = response.data; 
        });

        $timeout( function() {
            $scope.cancelEng();

            $scope.timer = $interval(function() {
                $scope.eng.request_time = moment().format("HH:mm:ss");
             }, 1000);

        }, 1500 ); 
    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

/*
    $scope.onChangedProperty = function() {
        $scope.location = {};
    }
*/

/*
    $scope.onChangeGuestType = function() {
        $scope.complaint.room = '';
        $scope.complaint.room_id = 0;
        $scope.complaint.guest_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.departure = '';
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.arrival = '';
        $scope.complaint.departure = '';
        $scope.complaint.new_guest = false; 
        if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            $scope.complaint.housecomplaint_id = $scope.housecategory_list[0].id;
    }
*/


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
       // window.alert(JSON.stringify($item));
        $scope.eng.location=$item.name;

        if($item.type=='Room')
    {
            $scope.eng.location_item = $item.type + ' ' + $item.name;
    }
     else  
      $scope.eng.location_item = $item.name;
    };


    $scope.getCategoryList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/eng_mytask/catlist?category='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    };
     $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.eng.category = $item.category;
    };
    
    $scope.getSubCategoryList = function(val) {
        if( val == undefined )
            val = "";
            
            var category=$scope.eng.category;
            //window.alert(category);

        return $http.get('/frontend/eng_mytask/subcatlist?sub_cat='+val+ '&category=' + category)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    };
    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.eng.subcategory = $item.sub_cat;
    };
 

/*
    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.complaint.room_id = $item.id;
        $scope.complaint.guest_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.new_guest = false; 
        if( $scope.complaint.guest_type == 'In-House CI')   // check in user            
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
                    $scope.complaint.arrival = data.data.arrival;
                    $scope.complaint.departure = data.data.departure;
                    $scope.complaint.guest_id = data.data.guest_id;
                });
        }        
    };
*/
/*
    
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
*/
/*
        if( isValidTask(task, message_flag) == false )
            return;
*/

/*
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
*/

/*
    $scope.onChangeNewGuest = function() {
        if( $scope.complaint.new_guest == true )
        {
            $scope.complaint.guest_name = ''
            $scope.complaint.guest_id = 0;
            $scope.complaint.mobile = '';
            $scope.complaint.email = '';
        }
    }
*/

    $scope.getStaffList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/eng_mytask/stafflist?value=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
/*
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
                return response.data.map(function(item){
                    return item;
                });
            });
    };
*/

/*
    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.complaint.guest_name = $item.guest_name;        
        $scope.complaint.guest_id = $item.guest_id;        
        $scope.complaint.mobile = $item.mobile;
        $scope.complaint.email = $item.email;      
        $scope.complaint.arrival = $item.arrival;
        $scope.complaint.departure = $item.departure;
        $scope.complaint.new_guest = false;        
    };
*/

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
		$scope.eng.property_id = $scope.requester.property_id;
		//window.alert(prop_id);
    };

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);  
    };

/*
    $scope.$watch('complaint.timepicker', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;
        console.log(newValue);
        $scope.complaint.incident_time = moment(newValue).format('HH:mm:ss');
    });
*/


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
     if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 5);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() > activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }
    
/*
    $scope.createEng = function(){
	    var i=0;
	    
	    if(($scope.complaint_tasks.length))
	    {
	    
    	    for(i=0; i<($scope.complaint_tasks.length);i++)
    	    {
    		    $scope.createEngIndividial($scope.complaint_tasks[i]);
    		}
		    
		    if($scope.complaint.guest_name != "")
		        $scope.createComplaintIndividial($scope.complaint);
	    }
	    else
	    {
		    $scope.createComplaintIndividial($scope.complaint);
	    }
    }
*/
    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }


    $scope.createEng = function() {
	    $scope.disable_create=1;
        var request = {};

        request.client_id = client_id;
		//window.alert($scope.eng.property_id);
        request.property_id = $scope.eng.property_id;
       // request.loc_id = $scope.location.id;

        request.requestor_id = $scope.requester.id;
        
        if( !(request.requestor_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select requestor');
            $scope.disable_create=0;
            return;
        }

/*
        if( !(request.loc_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select location');
            return;
        }
*/

       // request.guest_type = comp.guest_type;        
        //request.room_id = comp.room_id;
       // request.guest_id = comp.guest_id;
       // request.guest_name = comp.guest_name;
       // request.mobile = comp.mobile;
       // request.email = comp.email;
        request.severity = $scope.eng.severity;
        request.status = $scope.eng.status;
        request.initial_response = $scope.eng.initial_response;
        request.housecomplaint_id = $scope.eng.housecomplaint_id;
        request.incident_time = $scope.eng.incident_time;
        request.category = $scope.eng.category;
        request.subcategory = $scope.eng.subcategory;
        request.location = $scope.eng.location_item;
        request.id = $scope.eng_id
        
       
        
         if( !request.category )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please select Category');
            $scope.disable_create=0;
            return;
        }
        
           if( !request.initial_response )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please enter Subject of Request');
            $scope.disable_create=0;
            return;
        }


/*
        if( request.guest_type != 'Walk-in' && request.guest_type != 'Arrival' && request.guest_type != 'House Complaint')   // in-house guest complaint
        {
            if( !(request.room_id > 0) )
            {
                toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select room for In-house complaint');
                return;       
            }
            if( !(request.guest_id > 0) )
            {
                toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select guest for In-house complaint');
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
                return;       
                }
            }    
            else
            {
                if((request.guest_name)== '')
                {
                    toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Enter Name');
                    return; 
                }
                else if((request.guest_name!= '')&&((request.mobile == '') && (request.email == '')))
                {
                    toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Enter mobile or email');
                    return; 
                }
            }
        }
*/

        request.comment = $scope.eng.comment;

        //not working
        if( !request.comment  )
        {
            
            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'You did not enter engineering request');
            $scope.disable_create=0;
            return;
        }

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/post',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.disable_create=0;
            toaster.pop('success', MESSAGE_TITLE, response.data.message);

            // upload files
            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/eng_mytask/uploadfiles',
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
                        $scope.errorMsg = response.status + ': ' + response.data;
                    }
                }, function (evt) {
                    $scope.progress = 
                        Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                });
            }

            $scope.cancelEng();
            $scope.eng_id = response.data.id + 1;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Engineering Request.');
           // console.log(response);
            $scope.disable_create=0;
        })
        .finally(function() {

        });
    }



   

});