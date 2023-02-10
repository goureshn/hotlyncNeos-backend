app.controller('AlarmAlamrsController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Alarm Page';
    
    
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    $scope.alarm = {};
    function initData() {
        $scope.alarm.id = 0;
        $scope.alarm.name = '';
        $scope.alarm.properpty = property_id;
        $scope.alarm.description = '';
        $scope.alarm.editable = 0;
        $scope.alarm.enable = 0;
        $scope.action_button = 'Add';
    }
  
    initData();

    $scope.alarmAdd = function() {
        var data = {};
        data.property = property_id;
        data.id = $scope.alarm.id;
        data.name = $scope.alarm.name;
        if($scope.alarm.name == '') return false;
        data.description = $scope.alarm.description;
        if($scope.alarm.description == '') return false;
        data.editable = $scope.alarm.editable;
        data.enable = $scope.alarm.enable;
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/createalarm',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if($scope.alarm.id > 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Alarm has been updated successfully.');
                }else {
                    toaster.pop('success', MESSAGE_TITLE, 'Alarm has been created successfully.');
                }                
                $scope.alarmCancel();
                $scope.getDataList();

                console.log(response);

            }).catch(function(response) {
               // console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.alarmCancel = function() {
        initData();
    }

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
    $scope.onSearch = function(){
        $scope.getDataList();    
    } 
    $scope.getDataList = function getDataList(tableState) {

        $scope.isLoading = true;
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? 'id' : 'id' ;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.property = property_id; 
        request.searchtext = $scope.searchtext;
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getalarm',
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
                console.error('Alarm error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.editItems = function(row) {
        $scope.alarm.id = row.id;
        $scope.alarm.name = row.name;
        $scope.alarm.description = row.description;  
        if(row.editable == '1') $scope.alarm.editable = true;
        else   $scope.alarm.editable   = false;
        if(row.enable == '1') $scope.alarm.enable = true;
        else $scope.alarm.enable     = false;
        $scope.action_button = 'Update';
    }   
});
