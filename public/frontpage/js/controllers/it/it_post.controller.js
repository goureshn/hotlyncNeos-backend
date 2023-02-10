'use strict';

app.controller('ITPostController', function($scope, $http, $window, $interval, $timeout, $stateParams, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'ISSUE Status';
    var INCOMP = ' INCOMPLETE';

    var client_id = $stateParams.client_id;
	$scope.status_list = ['Pending'];

    $scope.guest_list = [{guest_id: 0, guest_name: 'Select Guest'}];
    $scope.severity_list = [];
    $scope.type_list = [];
    $scope.complaint_tasks = [];
    $scope.count= false;
    $scope.disable_create=0;
    $scope.requester = {};
    $scope.it = {};
    
    $scope.includeMobile = false; 
		var screenWidth = $window.innerWidth;
		if (screenWidth < 550){
		    $scope.includeMobile = true;
		}

    
     $scope.cancelIssue = function() {
        $scope.it = {};
        $scope.requester = {};
        $scope.it.housecomplaint_id = 0;
        $scope.it.property_id=0;
        if( $scope.building_list.length > 0 )
            $scope.it.building_id = $scope.building_list[1].id;

        $scope.it.initial_response = '';
        $scope.it.status = $scope.status_list[0];
        $scope.it.severity = 1;
        $scope.it.type = 1;
        $scope.complaint_tasks = [];
        $scope.count = false;
        $scope.files = [];
    }

    $scope.init = function() {        
        $http.get('/frontend/it/id')
         .then(function(response) {
            $scope.issue_id = response.data.max_id + 1;
        });

        $http.get('/list/building')
            .then(function(response) {
                $scope.building_list = response.data;           
        });

        $http.get('/list/severitylistit')
         .then(function(response) {
            $scope.severity_list = response.data; 
        });

        $http.get('/list/typelistit')
        .then(function(response) {
           $scope.type_list = response.data; 
       });

        $timeout( function() {
            $scope.cancelIssue();

            $scope.timer = $interval(function() {
                $scope.it.request_time = moment().format("HH:mm:ss");
             }, 1000);

        }, 1500 ); 
    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });


    $scope.getCategoryList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/it/catlist?category='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    };
     $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.it.category = $item.category;
    };
    
    $scope.getSubCategoryList = function(val) {
        if( val == undefined )
            val = "";
            
            var category=$scope.it.category;
            //window.alert(category);

        return $http.get('/frontend/it/subcatlist?sub_cat='+val+ '&category=' + category)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    };
    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.it.subcategory = $item.sub_cat;
    };
 

    $scope.getStaffList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/it/stafflist?value=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.datalist.map(function(item){
                    return item;
                });
            });
    };
    $scope.requester = {};
    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;        
		$scope.it.property_id = $scope.requester.property_id;
		//window.alert(prop_id);
    };

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);  
    };


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
    
    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }


    $scope.createIssue = function() {
	    $scope.disable_create=1;
        var request = {};

        request.client_id = client_id;
        request.property_id = $scope.it.property_id;
        request.building_id = $scope.it.building_id;

        request.requestor_id = $scope.requester.id;
        
        if( !(request.requestor_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select requestor');
            $scope.disable_create=0;
            return;
        }

        request.severity = $scope.it.severity;
        request.type = $scope.it.type;
        request.status = $scope.it.status;
        request.initial_response = $scope.it.initial_response;
        request.housecomplaint_id = $scope.it.housecomplaint_id;
        request.incident_time = $scope.it.incident_time;
        request.category = $scope.it.category;
        request.subcategory = $scope.it.subcategory;
        
       
        
         if( !request.category )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please select Category');
            $scope.disable_create=0;
            return;
        }
        
           if( !request.initial_response )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'Please enter Subject of Issue');
            $scope.disable_create=0;
            return;
        }

        request.comment = $scope.it.comment;

        //not working
        if( !request.comment  )
        {
            
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not enter issue');
            $scope.disable_create=0;
            return;
        }

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/it/post',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.disable_create=0;
            toaster.pop('success', MESSAGE_TITLE, response.data.message);

            // upload files
            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/it/uploadfiles',
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

            $scope.cancelIssue();
            $scope.issue_id = response.data.id + 1;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Issue.');
           // console.log(response);
            $scope.disable_create=0;
        })
        .finally(function() {

        });
    }



   

});
