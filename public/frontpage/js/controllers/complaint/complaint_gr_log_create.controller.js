app.controller('ComplaintGRLogCreateController', function ($scope, $rootScope, $http,$window, $interval, $timeout, $httpParamSerializer, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'GR Log Create';

    var MESSAGE_TITLE = 'Guest Feedback';
    var INCOMP = ' INCOMPLETE';
    var profile = AuthService.GetCredentials();
	var client_id = profile.client_id;
      
    $scope.guest_list = [{guest_id: 0, guest_name: 'Select Guest'}];
    $scope.disable_create=0;
    
	
     $scope.cancelComplaint = function() {
        $scope.complaint = {};
        $scope.location = {};
        $scope.occasion = {};
        $scope.requester = {};
        var profile = AuthService.GetCredentials();
        $scope.requester.id = profile.id;
        $scope.requester.employee_id = profile.employee_id;
        $scope.requester.wholename = profile.first_name + ' ' + profile.last_name;
        $scope.requester.job_role = profile.job_role;
        $scope.complaint.guest_id = 0;
        $scope.complaint.new_guest = false;
        $scope.complaint.category = $scope.category_list[0];
        $scope.onChangeCategoryType();
        $scope.complaint.sub_category = $scope.subcategory_list[0];
        $scope.count = false;
        

    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });
	
	 $scope.init = function(ack) {        
       
       
        $http.get('/list/property?client_id='+client_id)
         .then(function(response) {
            $scope.property_list = response.data;           
        });
		$http.get('/frontend/complaint/feedbackid')
         .then(function(response) {
            $scope.complaint_id = response.data.maxf_id + 1;
        }); 

        $http.get('/list/locationtotallist?client_id=' + client_id)
            .then(function(response){
                $scope.location_list = response.data; 
            });

         $timeout( function() {
            $scope.cancelComplaint();

            $scope.timer = $interval(function() {
                $scope.complaint.request_time = moment().format("HH:mm:ss");
             }, 1000);

        }, 1500 ); 
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
		 var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/list/roomlist?room=' + val + '&property_id=' + property_id)
           .then(function(response){
                var list = response.data.slice(0, 10);
                return list.map(function(item){
                    return item;
                });
            });
    };

    $scope.getOccasionList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/frontend/complaint/occasionlist?value=' + val + '&property_id=' + property_id)
           .then(function(response){
            var list = response.data.slice(0, 10);
            return list.map(function(item){
                return item;
                });
            });
    };

    $scope.onOccasionSelect = function ($item, $model, $label) {
        $scope.occasion = $item;
    };

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.complaint.room_id = $item.id;
		$scope.complaint.guest_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.new_guest = false; 
		
        
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
            url: '/frontend/complaint/searchcoguestlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
                var list = response.data.slice(0, 10);
                return list.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.complaint.guest_name = $item.guest_name;
		$scope.complaint.first_name = $item.first_name;        
        $scope.complaint.guest_id = $item.guest_id;         
        $scope.complaint.arrival = $item.arrival;
        $scope.complaint.departure = $item.departure;
        $scope.complaint.new_guest = false;        
    };

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
    };

   

    $scope.$watch('complaint.timepicker', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.complaint.incident_time = moment(newValue).format('HH:mm:ss');
    });

    
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
    

    $scope.onChangeCategoryType = function(){
/*
        if($scope.complaint.category == 'Guest Interaction') {
            $scope.subcategory_list = [
                'Positive Feedback',
                'Constructive Feedback - refer DM Feedback', 
            ]; 
       }
*/
       if($scope.complaint.category == 'Courtesy Calls'){
        $scope.subcategory_list = [
            'Guest is fine',
            'Minor Issues - Resolved', 
            'Major Issues - refer DM Feedback',   
        ]; 
       }
       else if($scope.complaint.category == 'Room Inspection'){
        $scope.subcategory_list = [
            'No Issues',
            'Issues found and shared with HK',    
        ]; 
       }
       else
       {
        $scope.subcategory_list = [
            'Positive Feedback',
            'Constructive Feedback - refer DM Feedback', 
        ]; 
       }
    }
    
    $scope.createFeedback = function(){
	    var i=0;
	    $scope.disable_create=1;
	   
		    if($scope.complaint.guest_name != "")
		        $scope.createFeedbackIndividial($scope.complaint);
	        else
	    {
		    $scope.createFeedbackIndividial($scope.complaint);
        }
    }
     
   
    $scope.createFeedbackIndividial = function(comp) {
	   
        var request = {};

        request.client_id = client_id;
        request.property_id = profile.property_id;
        request.requestor_id = $scope.requester.employee_id;
      
        if( !(request.requestor_id > 0) ) {
            request.requestor_id = $scope.requester.id;            
        }
        request.loc_id = $scope.location.id;
        request.room_id = comp.room_id;
        request.guest_id = comp.guest_id;
        request.guest_name = comp.guest_name;
        request.first_name = comp.first_name;
        request.category = comp.category;

        if( (request.category == 'In-House Special Attention ') || (request.category == 'Escorted to Room'))
        {
            request.sub_category = '';
        }
        else{
            request.sub_category = comp.sub_category;
        }
	    /*
        if (request.sub_category == null){

            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select Sub-Category');
            $scope.disable_create=0;
            return;  
        }
	*/
        request.occasion_id = $scope.occasion.id;
        if( !(request.room_id > 0) && !(request.category == 'Room Inspection') )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select Room');
            $scope.disable_create=0;
            return;       
        }

        if( !(request.guest_id > 0) && !(request.category == 'Room Inspection') )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You must select Guest');
            $scope.disable_create=0;
            return;       
        }

        if( !(request.loc_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select location');
            $scope.disable_create=0;
            return;
        }

        if( request.category == 'In-House Special Attention ')
        {
            if( !(request.occasion_id > 0) )
            {
                toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select Occasion');
                $scope.disable_create=0;
                return;
            }
        }
         
        request.comment = $scope.complaint.comment;
        
        //not working

        if( !request.comment  )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not enter Feedback');
            $scope.disable_create=0;
            return;
        }

        console.log(request);
	
       $http({
            method: 'POST',
            url: '/frontend/complaint/postfeedback',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.disable_create=0;
            toaster.pop('success', MESSAGE_TITLE, 'Guest Feedback has been posted Successfully.',response.data.message);
			
            $scope.cancelComplaint();
			$scope.pageChanged();
			$scope.complaint_id = response.data.maxfb_id + 1;
           
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Feedback.');
            $scope.disable_create=0;
        })
        .finally(function() {

        });
    }

});

