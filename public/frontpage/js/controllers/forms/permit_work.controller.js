app.controller('PermittoWorkFormController', function($scope,$http, AuthService, $window, $timeout,$uibModal,toaster, liveserver) {
    var MESSAGE_TITLE = 'Permit to Work Form';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 80;
    $scope.tab_height = $window.innerHeight +10;
    $scope.tab_height1 = $window.innerHeight - 120;

    $scope.isLoading = false;
    $scope.datalist = [];
   
    $scope.filter_value = '';
    $scope.selectedTickets = [];

   
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

    $timeout(function(){
        $scope.active = 1;
    }, 100);

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
    $scope.searchForm = function(value) {
	    $scope.paginationOptions.numberOfPages=2;
	    $scope.onPrevPage();
        $scope.pageChanged();
    }
    
     $scope.clearForm = function()
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
            url: '/frontend/forms/permitformlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                $scope.datalist = response.data.datalist;
               
                $scope.datalist.forEach(function(item, index) {
                    item.ticket_no = $scope.getTicketNumber(item);
                    item.created_at_time = moment(item.requested_on).format('D MMM YYYY hh:mm a');
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
                checkSelectStatus();
                console.log(response);
                //console.log(response.data.time);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
	
    $scope.pageChanged();

    function checkSelectStatus() {
        for(var j = 0; j < $scope.datalist.length; j++)
        {
            var ticket = $scope.datalist[j];
            var index = -1;
            for(var i = 0; i < $scope.selectedTickets.length; i++)
            {
                if( ticket.id == $scope.selectedTickets[i].id )
                {
                    index = i;
                    break;
                }
            }    
            ticket.active = index >= 0;            
        }        
    }


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
                checkSelectStatus();
            }

            $timeout(function() {
                if( index < 0 )
                    $scope.active = ticket.id;
            }, 10);

        }, 10);
    }
    
    $scope.removeSelectticket = function(item, $index) {
        if( !$scope.datalist )
            return;

        $timeout(function(){
            var index = -1;
            for(var i = 0; i < $scope.datalist.length; i++)
            {
                if( item.id == $scope.datalist[i].id )
                {
                    index = i;
                    $scope.datalist[i].active = false;
                }
            }

            $scope.selectedTickets.splice($index, 1);
        }, 100);
    }
    

    
  
    $scope.getTicketNumber = function(ticket) {
        return sprintf('F%05d', ticket.id);
    }

    $scope.$on('onChangedPermitForm', function(event, args){
        $scope.pageChanged();
    });

    
/*

    $scope.onCreateForm = function () {
	    
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/forms/modal/permit_work_create.html',
            controller: 'PermitWorkCreateCtrl',
            windowClass: 'app-modal-window'
            
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
*/

});

/*
    app.controller('PermitWorkCreateCtrl', function($scope, $rootScope, $window, $uibModalInstance, AuthService, $http, toaster, $interval) {
    
        var MESSAGE_TITLE = 'Permit to Work Form';

    function initData() {
    
        $scope.request_company = '';
        $scope.request_name = '';
        $scope.request_floor = '';
        $scope.request_no = '';
        $scope.request_signature = 0;

        $scope.authorize_name = '';
        $scope.worker = '';
        $scope.contact = '';
        $scope.final_name = '';
       

       
        $scope.request_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.third_date = moment().format('YYYY-MM-DD HH:mm:ss');
        $scope.authorizing_date = moment().format('YYYY-MM-DD HH:mm:ss');
      

    }

   

    $scope.tasks = [];
    initData();

    $scope.cancel = function() {
        initData();
        $uibModalInstance.dismiss();
    }

    $scope.$watch('requestdatepicker', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        console.log(newValue);
        $scope.request_date = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
       
    });
    

    $scope.submit = function() {
        var request = {};

        request.id = $scope.id;
        var profile = AuthService.GetCredentials();
        request.request_by = profile.id;
        request.property_id = profile.property_id;
        request.request_company = $scope.request_company;
        request.request_name = $scope.request_name;
        request.request_floor = $scope.request_floor;
        request.request_no = $scope.request_no;
        request.request_date = $scope.request_date;

        request.authorize_name = $scope.authorize_name;
        request.worker = $scope.worker;
        request.contact = $scope.contact;
        request.authorizing_date = $scope.authorizing_date;
        request.final_name = $scope.final_name;

        if ($scope.request_signature == 0)
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please sign the form before submission.');
					return;
        }
 
 

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/forms/createpermittoworkrequest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Request have been submitted successfully');
                $scope.cancel();
                //$scope.getDataList();
                $window.location.reload();

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }

});
*/