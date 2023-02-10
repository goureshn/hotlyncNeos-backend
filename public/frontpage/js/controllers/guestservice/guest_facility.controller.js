app.controller('GuestFacilityController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, toaster, GuestService, AuthService, $httpParamSerializer) {
    var MESSAGE_TITLE = 'Guest Facility';

    $scope.gs = GuestService;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;

    $scope.ticketlist_height = $window.innerHeight - 88;

    $scope.guest_type_selected = 'All';
    $scope.guest_types = [
        'All',
        'In-House',
        'Walkin',  
    ];

    $scope.breakfast = [
        'Yes',
        'No',
    ];

    


    $scope.listmode = false;

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

 

    $scope.Close = function () {
        $scope.listview_width = 'col-sm-12';
        $scope.detailview_width = 'col-sm-0';
        $scope.detailview = false;
    }

    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = 'height: ' + ($window.innerHeight - 115) + 'px; overflow-y: auto;';

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.ticketlist = [];

    $scope.facility = {};

    $scope.facility.bmeal = $scope.breakfast[1];

    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
    }

    $scope.onSelectGuestType = function(item, model)
    {
        $scope.guest_type_selected = angular.copy(item);
        $scope.pageChanged();
    }

    // Filter
    $scope.filter = {};

    
    // room filter
    $scope.filter.room_tags = [];
    $scope.room_list = [];
    $http.get('/list/roomlist?property_id=' + profile.property_id)
            .then(function(response){
                $scope.room_list = response.data;
            });

    $scope.roomTagFilter = function(query) {
        return $scope.room_list.filter(function(item) {
            return item.room.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.pageChanged = function pageChanged(preserve) {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);

        $scope.ticketlist = [];

        if( preserve )
        {
            $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.field = preserve.sort.predicate;
            $scope.paginationOptions.sort = preserve.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.searchoption = $scope.searchoption;
        request.searchtext = $scope.searchtext;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        if( $scope.guest_type_selected)
            request.guest_type = $scope.guest_type_selected;
        else
            request.guest_type = 'All';

        request.room_ids = $scope.filter.room_tags.map(item => item.id).join(',');
     
        $scope.filter_apply = request.guest_type != 'All' ||
                                    $scope.filter.room_tags.length > 0;

        var url = '/frontend/guestservice/guestfacilitylist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
           $scope.ticketlist = response.data.datalist;

            $scope.ticketlist.forEach(function(item, index) {
                    if (item.guest_type == 'In-House'){
                       if  (item.bmeal == 1) { 
                           item.bmeal = 'Paid';
                        }
                        else {
                           item.bmeal = 'Not Paid';
                        } 
                    }
                    else if (item.guest_type == 'Walkin'){
                        item.bmeal = 'NA';
                    }
            });

            $scope.paginationOptions.totalItems = response.data.totalcount;

            if( $scope.paginationOptions.totalItems < 1 )
                $scope.paginationOptions.countOfPages = 0;
            else
                $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
            console.log(response);
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });
    };


    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = $scope.paginationOptions.numberOfPages - 1;
        $scope.pageChanged();
    };

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = $scope.paginationOptions.numberOfPages - 1;
        $scope.pageChanged();
    }

    $scope.refreshTickets = function(){
        $scope.pageChanged();
    }

    $scope.$on('create_repairrequest', function(event, args){
        $scope.pageChanged();
    });


    $scope.getTicketNumber = function(ticket){
        if(ticket)    
            return sprintf('R%05d', ticket.id);
    }



  
    $scope.onCreate = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/guest_facility/guest_facility_create.html',
            controller: 'GuestFacilityCreateController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {

            }
        });

        modalInstance.result.then(function (selectedItem) {

        }, function () {

        });
    }

    $scope.exit = function (row) {

        var data = angular.copy(row);

        $http({
            method: 'POST',
            url: '/frontend/guestservice/exitguest',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Guest Exit Done');
            $scope.pageChanged();
          
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };

    

    $scope.onExportExcel = function()
    {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.searchoption = $scope.searchoption;
        request.searchtext = $scope.searchtext;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
       
        if( $scope.guest_type_selected)
            request.guest_type = $scope.guest_type_selected;
        else
            request.guest_type = 'All';

        request.room_ids = $scope.filter.room_tags.map(item => item.id).join(',');
  
        request.excel_type = 'excel';

        $window.location.href = '/frontend/guestservice/exportguestfacility?' + $httpParamSerializer(request);
    }

    $scope.$on('refresh_repair_page', function(event, args){
        $scope.pageChanged();
    });
});


