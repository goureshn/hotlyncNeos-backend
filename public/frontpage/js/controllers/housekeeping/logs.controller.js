app.controller('HousekeepLogController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, AuthService, $httpParamSerializer, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Guest Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;

    // pip
    $scope.isLoading = false;
    $scope.alarmlist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };
    $scope.searchLog = function(value) {	    
        $scope.getDataList();
    }
    $scope.clearLog = function()
    {
	    $scope.filter_value = '';
	    $scope.refreshLogs();
    }
  //  $scope.pageChanged = function(){
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
        request.filter_value = $scope.filter_value;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
                method: 'POST',
                url: '/frontend/hskp/logs',
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
 //   };

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

    $scope.getDate = function(row) {
        return moment(row.created_at).format('DD-MM-YYYY');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.refreshLogs = function(){
        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onViewChecklist = function(row)
    {
        var filter = {};
        filter.check_num = row.check_num;
        filter.room_id = row.room_id;
        filter.room = row.room;
        filter.id = row.id;
        filter.hskp_role = row.state == 2 ? 'Attendant' : 'Supervisor';

        var profile = AuthService.GetCredentials();
        filter.creator_id = profile.id;
        filter.timestamp = new Date().getTime();

        filter.report_target = 'hskp_checklist';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.$on('$destroy', function() {
        clearDownloadChecker();
    });

    var filter_param = undefined;

    $scope.generateDownloadChecker = function(filter){
        filter_param = filter;
        
        // Block the user interface        
        blockUI.start("Please wait while the report is being generated."); 
    }

    function clearDownloadChecker() {
        // Unblock the user interface
        blockUI.stop(); 
    }

    $scope.$on('pdf_export_finished', function(event, args){
        if( filter_param && args == filter_param.timestamp )
            clearDownloadChecker();
    });

});
