app.controller('HotWorkValidationController', function ($scope, $rootScope, $http, $httpParamSerializer, $window, $interval, $uibModal, AuthService, toaster ,liveserver) {
    var MESSAGE_TITLE = ' Hotwork Permit Form';
		
	$scope.full_height = $window.innerHeight - 125;
	 $scope.tab_height = $window.innerHeight - 100;
		
    $scope.complaint = {};
    $scope.inspection = {};
   
    $scope.init = function(complaint) {
      
        var profile = AuthService.GetCredentials(); 
        $scope.complaint = complaint;
        $scope.complaint.auth_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.final_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.work_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.close_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.complaint.start_time = moment().format('HH:mm:ss');
        $scope.complaint.end_time = moment().format('HH:mm:ss');
        $scope.complaint.final_time = moment().format('HH:mm:ss');
        $scope.complaint.ended_time = moment().format('HH:mm:ss');
        $scope.complaint.close_time = moment().format('HH:mm:ss');
        $scope.inspection.inspect_time = moment().format('HH:mm:ss');
        $scope.tasks = [];
    }
    
    
    $scope.$watch('complaint.workdatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.work_date =  moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
    });

    $scope.$watch('complaint.authdatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.auth_date =  moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
    });
    $scope.$watch('complaint.finaldatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.final_date =  moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
    });
    $scope.$watch('complaint.closedatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.close_date =  moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
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
    $scope.$watch('complaint.endedtimepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.ended_time =  moment(newValue).format('HH:mm');
       
    });
    $scope.$watch('complaint.finaltimepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.final_time =  moment(newValue).format('HH:mm');
       
    });
    $scope.$watch('complaint.closetimepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.complaint.close_time =  moment(newValue).format('HH:mm');
       
    });

    $scope.$watch('inspection.inspecttimepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.inspection.inspect_time =  moment(newValue).format('HH:mm');
       
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
        request.auth_by = profile.id;

        if ($scope.complaint.auth_sign == undefined)
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please sign the form before submission.');
					return;
        }
        $http({
            method: 'POST',
            url: '/frontend/forms/updateauth',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
               // $scope.pageChanged();
               // $window.location.reload();
               $scope.$emit('onChangedForm', response.data);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                console.log(response);
            })
            .finally(function() {
            });
    }  

    $scope.tasks = [];
    $scope.addMainTask = function (){

        var new_task={
            inspect_time: moment().format('HH:mm:ss'),
            work_in: ""
        }
        $scope.inspection = new_task;
    }
    $scope.addInspectTime = function () {
        
        var task = angular.copy($scope.inspection);
        $scope.tasks.push(task);

        // init main task
        $scope.addMainTask();
       
    }


    $scope.removeTask = function (item) {
        $scope.tasks.splice($scope.tasks.indexOf(item), 1);
      
    }

    $scope.savelist = function(){
        var profile = AuthService.GetCredentials();    
        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        request.tasks = $scope.tasks;
        request.inspect_by = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/forms/updatelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.inspect_by = response.data.inspect_by;
                toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
               // $scope.pageChanged();
               // $window.location.reload();
               $scope.$emit('onChangedForm', response.data);

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                console.log(response);
            })
            .finally(function() {
            });
    }

    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.tableState = undefined;
	

    // pip
    $scope.isLoading = false;
    $scope.inspectlist = function inspectlist(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;
		
			
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;
			$scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        $scope.datalist = [];
        request.id = $scope.complaint.id;
	   
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
    
        $http({
                method: 'POST',
                url: '/frontend/forms/inspectlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.inspect_by = response.data.inspect_by;	
                $scope.datalist = response.data.datalist;	
               
                $scope.paginationOptions.totalItems = response.data.totalcount;
				
                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
					
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
					
				if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
				console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
			
	
    }; 


    $scope.update = function(){
        
        var profile = AuthService.GetCredentials();    
        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        request = $scope.complaint;
        request.final_by = profile.id;

            $http({
                method: 'POST',
                url: '/frontend/forms/updatefinal',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
    
                    toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
                   // $scope.pageChanged();
                   // $window.location.reload();
                   $scope.$emit('onChangedForm', response.data);
                }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                    console.log(response);
                })
                .finally(function() {
                });
        }  

        $scope.complete = function(){
        
            var profile = AuthService.GetCredentials();    
            var request = {};
    
            request.property_id = profile.property_id;
            request.id = $scope.complaint.id;
            request = $scope.complaint;
            request.close_by = profile.id;
           
            //$scope.pageChanged();
                $http({
                    method: 'POST',
                    url: '/frontend/forms/updateclose',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
        
                        toaster.pop('success', MESSAGE_TITLE, 'Form has been updated successfully');
                        $scope.$emit('onChangedForm', response.data);
                       
                        //$window.location.reload();
                    }).catch(function(response) {
                        toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Form');
                        console.log(response);
                    })
                    .finally(function() {
                    });
            }  

   
});





