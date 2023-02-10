app.controller('ChecklistCreateModController', function($scope, $rootScope, $http,$httpParamSerializer, $window,$uibModal,  AuthService, toaster) {
    var MESSAGE_TITLE = 'Check List Page';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.property_id = profile.property_id;
 
    $scope.getcategoryList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/frontend/complaint/modcategorylist?value=' + val + '&property_id=' + property_id)
           .then(function(response){
            var list = response.data.slice(0, 10);
            return list.map(function(item){
                return item;
                });
            });
    };

    $scope.onCategorySelect = function ($item, $model, $label) {
        var category = {};
        $scope.category_id = $item.id;
        $scope.category = $item.category;
    };
   
    function initData() {
        $scope.id = 0;
        $scope.check_list_name = '';
        $scope.category = '';
        $scope.tasks = [];
        $scope.action_button = 'Create';
    }
    $scope.tasks = [];
    initData();

    $scope.searchLog = function(value) {
	    $scope.paginationOptions.numberOfPages=2;
	    $scope.onPrevPage();
        $scope.getDataList();
    }

    $scope.clearLog = function()
    {
	    $scope.filter_value = '';
	    $scope.refreshLogs();
    }
   
    $scope.addMainTask = function (){

        var new_task={
            category: $scope.task.category,
            task: ""
        }
        $scope.task = new_task;
    }
    $scope.addTask = function () {
        
        var task = angular.copy($scope.task);
        $scope.tasks.push(task);

        // init main task
        $scope.addMainTask();
       
    }
    
    $scope.removeTask = function (item) {
        $scope.tasks.splice($scope.tasks.indexOf(item), 1);
      
    }

    $scope.add = function() {
        var request = {};

        request.id = $scope.id;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.name = $scope.check_list_name;
        request.category_id = $scope.category_id;
        request.created_by = profile.id;
        request.tasks = $scope.tasks;
      
        if( request.name == '' )
            return;
        if( $scope.category == '' )
            return;


        $http({
            method: 'POST',
            url: '/frontend/complaint/createmodchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Checklist have been created successfully');
                $scope.cancel();
                $scope.getDataList();
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.cancel = function() {
        initData();
    }

    $scope.edit= function (row) {
	    
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/checklist_edit.html',
            controller: 'ChecklistEditCtrl',
            windowClass: 'app-modal-window',
            resolve: {
               row: function () {
                    return row;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    $scope.delete = function(row) {
        var request = {};
        request.id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/complaint/deletemodchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Checklist have been deleted successfully');
                $scope.cancel();
                $scope.getDataList();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.deleteTask = function(row) {
        var request = {};
        request.id = row.checklist_id;
        request.category = row.category;
        request.task = row.task;
       
        $http({
            method: 'DELETE',
            url: '/frontend/complaint/deletemodtask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Task have been deleted successfully');
                $scope.cancel();
                $scope.getDataList();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }


    $scope.isLoading = false;

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 5,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };


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
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter_value = $scope.filter_value;

        $http({
            method: 'POST',
            url: '/frontend/complaint/getmodchecklist',
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
   
    $scope.getDataList();

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.refreshLogs = function(){
        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onClickRow = function(row, index) {
        var request = {};
        request.id = row.id;
        request.room_id = row.room_id;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
       
        $http({
            method: 'POST',
            url: '/frontend/complaint/tasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.detaillist = response.data.datalist;
                for(var i = 0; i < $scope.datalist.length; i++) {
                    if(i == index) $scope.datalist[i].view = true;
                    else $scope.datalist[i].view = false;
                }
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    
});

app.controller('ChecklistEditCtrl', function($scope, $rootScope, $window, $uibModalInstance, row,  AuthService, $http, toaster, $interval) {
	
	var MESSAGE_TITLE = 'Checklist';
 
    $scope.row = row;
    
	$scope.tableState = undefined;
	

    // pip
    $scope.isLoading = false;

    function initData() {
        $scope.row.name = $scope.row.name;
        $scope.row.category = "";
        $scope.row.task = "";
        $scope.tasks = [];
       
    }
    $scope.tasks = [];
    initData();
    $scope.getcategoryList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/frontend/complaint/modcategorylist?value=' + val + '&property_id=' + property_id)
           .then(function(response){
            var list = response.data.slice(0, 10);
            return list.map(function(item){
                return item;
                });
            });
    };

    $scope.onCategorySelect = function ($item, $model, $label) {
        var category = {};
        $scope.category_id = $item.id;
        $scope.category = $item.category;
    };
    

	
    $scope.addMainTask = function (){

        var new_task={
            category: $scope.row.category,
            task: ""
        }
       // $scope.row = new_task;
       $scope.row.category = $scope.row.category;
    $scope.row.task = '';
    }
    $scope.addTask = function () {
        
        var data ={};
        data.category = $scope.row.category;
        data.task = $scope.row.task;
       
        $scope.tasks.push(data);

        // init main task
        $scope.addMainTask();
       
    }
    


    $scope.removeTask = function (item) {
        $scope.tasks.splice($scope.tasks.indexOf(item), 1);
      
    }
    
    
	
     $scope.addTasks = function () {
	    
        var request = {};
          request.id = $scope.row.id;
          var profile = AuthService.GetCredentials();
          request.property_id = profile.property_id;
          request.name = $scope.row.name;
          request.created_by = profile.id;
          request.tasks = $scope.tasks;

	     $http({
            method: 'POST',
            url: '/frontend/complaint/createmodchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
             .then(function(response) {
				
				toaster.pop('Success', MESSAGE_TITLE, 'Checklist have been updated successfully');
				//$window.location.reload();
				$scope.cancel();
				console.log(response);
				
				
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
        //$uibModalInstance.dismiss();
        initData();
    };
});
