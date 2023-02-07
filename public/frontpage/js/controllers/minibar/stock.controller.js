app.controller('MinibarStockController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;

    
    // pip
    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.minibar_item = {};

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.loadMinibarItems = function(datalist) {
		
        $scope.minibar_item = {};
        datalist.forEach( function(value, index, ar) {
            $scope.minibar_item[value.id] = value;
        });
    }

    

	
    $scope.getDataList = function getDataList(tableState) {
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

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.searchtext = $scope.searchtext;


        $http({
                method: 'POST',
                url: '/frontend/minibar/stocks',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.loadMinibarItems(response.data.minibar_item_list);

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
	
	$scope.editStock= function (item) {
	    
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/minibar/modal/stock_quantity.html',
            controller: 'MinibarStockCtrl',
            windowClass: 'app-modal-window',
            resolve: {
               item: function () {
                    return item;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
	
	$scope.status = {
        isFirstOpen: true,
        isFirstDisabled: false
    };
   
    
    $scope.onSearch = function() {
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }
});

app.controller('MinibarStockCtrl', function($scope, $rootScope, $window, $uibModalInstance, item, AuthService, $http, toaster, $interval) {
	
	var MESSAGE_TITLE = 'Stock Page';
 
	$scope.item = item;
	$scope.tableState = undefined;
	

    // pip
    $scope.isLoading = false;
    //$scope.datalist = [];
    $scope.minibar_item = {};

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };
	
	
    $scope.getItemHist = function getItemHist(tableState) {
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
	    request = $scope.item;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
    
        $http({
                method: 'POST',
                url: '/frontend/minibar/historystocks',
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
    
	
     $scope.addEntry = function () {
	     var request = {};
		  $scope.datalist = [];
	      request = $scope.item;

	     $http({
            method: 'POST',
            url: '/frontend/minibar/addstock',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
             .then(function(response) {
				
				toaster.pop('Success', MESSAGE_TITLE, 'Quantity have been updated successfully');
				$window.location.reload();
				$scope.cancel();
				console.log(response);
				
				
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
	     
    };
	
	$scope.rmvEntry = function () {
	     var request = {};
		  $scope.datalist = [];
	      request = $scope.item;
		

	     $http({
            method: 'POST',
            url: '/frontend/minibar/rmvstock',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
             .then(function(response) {
			 
			 if( (request.item_stock - request.quantity_1) <= 0 )
				{
					toaster.pop('info', MESSAGE_TITLE, 'Quantity is more than the current stock. Please try again.');
					return;
				}
			 else
			 {
                
				$window.location.reload();
                $scope.cancel();
				toaster.pop('success', MESSAGE_TITLE, 'Quantity have been updated successfully');
			    console.log(response);
			 }
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
	     
    };
	
	

    $scope.status = {
        isFirstOpen: true,
        isFirstDisabled: false
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});
