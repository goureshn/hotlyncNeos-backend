app.controller('GRLogComplaintController', function ($scope, $rootScope, $http, $window, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, $aside, liveserver) {
    var MESSAGE_TITLE = 'Guest Feedback List';
   
    //$scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 80;
    $scope.tab_height = $window.innerHeight +10;
    $scope.tab_height1 = $window.innerHeight - 120;
	
	
	$scope.uploadexcel = {};
    $scope.uploadexcel.src = '';
    $scope.uploadexcel.name = '';
    $scope.uploadexcel.type = '';

    $scope.filter_value = '';
    $scope.property_ids = [];


	
    var profile = AuthService.GetCredentials();

    var userlist = [];
    

    $scope.newTickets = [];
    $scope.selectedTickets = [];

    $scope.newTickets[0] = {
        "id" : 0,
        "Number" : 1,
        "groupName" : "Create Wakeup",
    };

    $scope.isLoading = false;
    $scope.datalist = [];

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

    $scope.$on('$destroy', function() {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.onClickDateFilter = function() {
        angular.element('#dateranger').focus();
    }

    $scope.category_list = [
            'Guest Interaction',
			'Courtesy Calls',
            'Room Inspection',
            'In-House Special Attention ', 
            'Escorted to Room',      
        ]; 

    $scope.subcategory_list = [
        'Positive Feedback',
        'Constructive Feedback - refer DM Feedback',    
        ]; 
        
    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        // dateDisabled: disabled,
        class: 'datepicker'
    };

    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.config.opened = true;
    };
    
    $scope.select = function(date) {
        console.log(date);

        $scope.config.opened = false;
    } 
	
    var filter = 'Total';
    $scope.onFilter = function getFilter(param) {
        filter = param;
        $scope.pageChanged();
    }
    $scope.searchComplaint = function(value) {
	    $scope.paginationOptions.numberOfPages=2;
	    $scope.onPrevPage();
        $scope.pageChanged();
    }
    
     $scope.clearComplaint = function()
    {
	    $scope.filter_value = '';
	    $scope.refreshLogs();
    }

    $scope.pageChanged = function() {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        /////////////////////
        var request = {};
        request.page = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
		request.filter = filter;
        //if( request.filter )
           // request.filter.departure_date = moment(request.filter.departure_date).format('YYYY-MM-DD');
        
        request.filter_value = $scope.filter_value;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $scope.datalist = [];
        console.log(request);
        $http({
            method: 'POST',
            url: '/frontend/complaint/fblist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                $scope.datalist = response.data.datalist;
                $scope.datalist.forEach(function(item, index) {
                    item.ticket_no = $scope.getTicketNumber(item);
                    item.created_at_time = moment(item.created_at).format('D MMM YYYY hh:mm a');
                });
				
                $scope.paginationOptions.totalItems = response.data.totalcount;
                $scope.property_ids = response.data.property_ids;
				//$scope.filter_value = '';
                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
 
                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
                console.log(response.data.time);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
	
    $scope.pageChanged();

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.pageChanged();
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
        $scope.pageChanged();
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('DD-MMM-YYYY HH:mm:ss');
    }


    $scope.refreshLogs = function(){
        $scope.isLoading = true;
        $scope.pageChanged();
    }


    $scope.getRowCss = function(row) {
        if( row.active )
            return 'active';
        else
            return '';
    }

    $scope.getWakeupNumber = function(log){
        if( log == undefined )
            return 0;

        return sprintf('%05d', log.id);
    }

    $scope.getDurationInMinute = function(duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    $scope.removeTicket = function() {

    }

    $scope.onSelectTicket = function(ticket){
        // check select ticket
        $timeout(function(){
            var index = -1;
            for(var i = 0; i < $scope.selectedTickets.length; i++)
            {
                if( ticket.id == $scope.selectedTickets[i].id )
                {
                    index = i;
                    break;
                }
            }

            if( index < 0 )    // not selected
            {
                $scope.selectedTickets.push(angular.copy(ticket));
            }
            else {
                $scope.selectedTickets.splice(index, 1);
                //checkSelectStatus();
            }

            $timeout(function() {
                if( index < 0 )
                    $scope.active = 6 + ticket.id;
            }, 10);

        }, 10);
    }
    

    

    
  
    $scope.getTicketNumber = function(ticket) {
        return sprintf('GR%05d', ticket.id);
    }

    $scope.$on('onChangedComplaint', function(event, args){
        $scope.pageChanged();
    });

    $scope.$on('complaint_post', function(event, args){
        $scope.pageChanged();
    });

    
    
    $scope.onDownloadPDF = function(){
        var profile = AuthService.GetCredentials();

        var filters = {};
		
		
        filters.user_id = profile.id;
        filters.report_by = 'Summary';
        filters.report_type = 'Summary';
        filters.report_target = 'feedback_summary';
        var profile = AuthService.GetCredentials();
        filters.property_id = profile.property_id;
        filters.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        filters.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        filters.filter_value = $scope.filter_value;
		filters.filter = filter;
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filters);
    }
	
	$scope.onDownloadExcel = function(){
        var profile = AuthService.GetCredentials();

        var filters = {};
       
        filters.filter=filter;
        filters.user_id = profile.id;
        filters.report_by = 'Summary';
        filters.report_type = 'Summary';
        filters.report_target = 'feedback_summary';
        var profile = AuthService.GetCredentials();
        filters.property_id = profile.property_id;
        filters.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        filters.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        filter.filter_value = $scope.filter_value;

        $window.location.href = '/frontend/report/feedback_excelreport?' + $httpParamSerializer(filters);
    }
});



