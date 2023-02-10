app.controller('ChecklistModController', function($scope, $rootScope, $http,$httpParamSerializer, $window,$uibModal,  AuthService, toaster, liveserver) {
  
    var MESSAGE_TITLE = 'Manager on Duty';

    $scope.tableState = undefined;

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.property_id = profile.property_id;
 
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

    $scope.edit= function (row) {
	    
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/checklist_task_edit.html',
            controller: 'ChecklistTaskEditCtrl',
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

    $scope.isLoading = false;

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

    $rootScope.$on('onChangedCheckList', function(event, args){
        $scope.getDataList();
    });

    $scope.onDownloadPDF = function(row) {
        var filter = {};
        filter.report_target = 'mod_checklist';
        filter.id = row.id;
        filter.name = row.name;
        var profile = AuthService.GetCredentials();
        filter.property_id = profile.property_id;
        filter.generated_by = profile.id;
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }

    
});

app.controller('ChecklistTaskEditCtrl', function($scope, $rootScope, $window, $uibModalInstance, row,  AuthService, $http, toaster, $interval) {
	
	var MESSAGE_TITLE = 'Checklist Task';
 
    $scope.row = row;
    
	$scope.tableState = undefined;
    $scope.task = {};

    $scope.complete = 0;

    // pip
    $scope.isLoading = false;

    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };
    $scope.getDataList1 = function getDataList1(tableState) {
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
        request.id = $scope.row.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/tasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.detaillist = response.data.datalist;
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
    $scope.detailDisplayyes = false;
    $scope.detailDisplayno = false;
    $scope.detailDisplayna = false;
    $scope.onFieldHidden = function (row) {
            row.label_comment = false;
            row.input_comment = true;
    }
    $scope.onKeySave = function (row) {  
            row.label_comment = true;
            row.input_comment = false;
            SaveField('Comment', row.checklist_id, row.category, row.task, row.comment); 
    }
 
    $scope.selectRow = {};
    $scope.onClick = function(row, count) {
    
        $scope.selectRow = row;
        $scope.task = angular.copy(row);
        if (count == 'Yes'){
            $scope.detailDisplayyes = true ;
            $scope.task.yes = row.yes;
        }
        if (count == 'No'){
            $scope.detailDisplayno = true ;
            $scope.task.no = row.no;
        }
        if (count == 'N/A'){
            $scope.detailDisplayna = true ;
            $scope.task.na = row.na;
        }
        console.log($scope.task);
    }
    $scope.onSaveTask = function(count) {
        
        if (count == 'Yes'){
            $scope.selectRow.yes = $scope.task.yes;
            SaveField('Yes', $scope.task.checklist_id, $scope.task.category, $scope.task.task, $scope.task.yes);
        }
        if (count == 'No'){
            $scope.selectRow.no = $scope.task.no;
            SaveField('No', $scope.task.checklist_id, $scope.task.category, $scope.task.task, $scope.task.no);
        }
        if (count == 'N/A'){
            $scope.selectRow.na = $scope.task.na;
            SaveField('N/A', $scope.task.checklist_id, $scope.task.category, $scope.task.task, $scope.task.na);
        }
        $scope.getDataList1();
    }
     function SaveField(fieldName, rowId, rowCategory, rowTask, rowValue) {
	    
        var request = {};
          request.id = rowId;
          var profile = AuthService.GetCredentials();
          
          request.category = rowCategory;
          request.task = rowTask;
          if(fieldName == 'Yes') {
            if(rowValue == true)  request.yes = 1;
            if(rowValue == false)  request.yes = 0;
         }
          if(fieldName == 'No') {
            if(rowValue == true)  request.no = 1;
            if(rowValue == false)  request.no = 0;
         }
         if(fieldName == 'N/A') {
            if(rowValue == true)  request.na = 1;
            if(rowValue == false)  request.na = 0;
         }
          if (fieldName == 'Comment') {
            request.comment = rowValue;
         }
        console.log(request);
	     $http({
            method: 'POST',
            url: '/frontend/complaint/updatetasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
             .then(function(response) {
				
                toaster.pop('success', MESSAGE_TITLE, 'Task has been updated successfully');
				console.log(response);
				
				
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to edit field.');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
	     
    };
	
    $scope.status = {
        isFirstOpen: true,
        isFirstDisabled: false
    };

    $scope.onComplete = function (row) {
       
        var request = {};
       $scope.complete = 1;
       request.id = row.id;
       request.complete = $scope.complete;
       request.name = row.name;
       var profile = AuthService.GetCredentials();
       request.property_id = profile.property_id;
       request.generated_by = profile.id;
       request.completed_by = profile.id;

       $http({
        method: 'POST',
        url: '/frontend/complaint/updatecomplete',
        data: request,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    })
         .then(function(response) {
           
            console.log(response);
           
            $uibModalInstance.dismiss();
            $scope.$emit('onChangedCheckList') ;
           // $window.location.reload();
        
            
            
        }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
            toaster.pop('error', MESSAGE_TITLE, 'Failed to edit field.');
        })
        .finally(function() {
            
        });

       
    };
});
