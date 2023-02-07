app.controller('LiftFormValidationController', function ($scope, $rootScope, $http, $httpParamSerializer, $window, $interval, $uibModal, AuthService, toaster ,liveserver) {
    var MESSAGE_TITLE = 'Lift Usage Form';
		
	$scope.full_height = $window.innerHeight - 125;
	 $scope.tab_height = $window.innerHeight - 100;
		
    $scope.complaint = {};
    
    
    $scope.forward_flag = false;
    $scope.init = function(complaint) {
      
        var profile = AuthService.GetCredentials();
        //console.log("checking");
        
        $scope.complaint = complaint;
       
     

      
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
    $scope.prptylist = function prptylist(tableState) {
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
                url: '/frontend/forms/prptylist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
				
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
 
	
       $scope.approve=function(){
	    $scope.complaint.status ="Approved Level ";
	 
	    $scope.UpdateStatus();
	    
    }

    $scope.UpdateStatus = function(){

        
        var profile = AuthService.GetCredentials();
        
    $scope.complaint.property_id = profile.property_id;
       $scope.complaint.updated_by = profile.id;
       $scope.complaint.form_id = 1;
        $http({
            method: 'POST',
            url: '/frontend/forms/updatestatus',
            data: {
                    id: $scope.complaint.id,
                    status: $scope.complaint.status,
                    updated_by: $scope.complaint.updated_by,
                    property_id: $scope.complaint.property_id,
                    form_id:  $scope.complaint.form_id,
                }
,
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

 
    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'F00000';
        return sprintf('F%05d%s', ticket.parent_id, ticket.sub_label);        
    }

    $scope.getComplaintNumber = function(ticket){
        if(!ticket)
            return 'F00000';
        return sprintf('F%05d', ticket.id);        
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

    

    

});





