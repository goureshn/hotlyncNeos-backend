app.controller('HotWorkFormController', function($scope,$http, $window,AuthService, $timeout,$uibModal,toaster, liveserver) {
    var MESSAGE_TITLE = 'Hotwork Permit Form';

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
            url: '/frontend/forms/hotworkformlist',
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
               // console.log(response.data.time);
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

    $scope.$on('onChangedForm', function(event, args){
        $scope.pageChanged();
    });

   
    


    $scope.onCreateForm = function () {
	    
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/forms/modal/hotwork_form_create.html',
            controller: 'HotworkCreateCtrl',
            windowClass: 'app-modal-window'
            
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };


});

    app.controller('HotworkCreateCtrl', function($scope, $rootScope, $window, $uibModalInstance, AuthService, $http, toaster, $interval) {
    
        var MESSAGE_TITLE = 'Hotwork Permit Form';
    function initData() {
    
        $scope.request_company = '';
        $scope.request_permit = 0;
        $scope.request_location = '';
        $scope.hzrd_desc = '';
        $scope.cont_name = '';
        $scope.completed_by = '';
        $scope.position = '';
        $scope.personnel = 0;
        $scope.loc_watch = '';
        $scope.first_aid = '';
        $scope.precaution = '';
        $scope.worker_name = '';
        $scope.worker_pos = '';
        $scope.less_hazard = 0;
        $scope.project = 0;
        $scope.in_house = 0;
        $scope.contractor = 0;
        $scope.verify = 0;
        $scope.permit_area = 0;
        $scope.sprinkler = 0;
        $scope.equipment = 0;
        $scope.flammable = 0;
        $scope.combustible = 0;
        $scope.cleaning = 0;
        $scope.floor_protect = 0;
        $scope.enclose = 0;
        $scope.ventilation = 0;
        $scope.clean_flame = 0;
        $scope.vapours = 0;
        $scope.detector = 0;
        $scope.thirty_min = 0;
        $scope.sixty_min = 0;
        $scope.exceed = 0;
        $scope.briefed = 0;
        $scope.count = false;
        $scope.request_date = moment().format('YYYY-MM-DD HH:mm:ss');
       

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
   
    $scope.submit = function() {
       	
        var request = {};

       // request.id = $scope.id;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.request_by = profile.id;
        request.request_company = $scope.request_company;
        request.request_permit = $scope.request_permit;
        request.request_location = $scope.request_location;
        request.hzrd_desc = $scope.hzrd_desc;
        request.cont_name = $scope.cont_name;
        request.completed_by = $scope.completed_by;
        request.position = $scope.position;
        request.personnel = $scope.personnel;
        request.loc_watch = $scope.loc_watch;
        request.first_aid = $scope.first_aid;
        request.precaution = $scope.precaution;
        request.worker_name = $scope.worker_name;
        request.worker_pos = $scope.worker_pos;
        request.request_date = $scope.request_date;
        

        request.less_hazard = $scope.less_hazard ? 1 : 0;
        request.project = $scope.project ? 1 : 0;
        request.in_house = $scope.in_house ? 1 : 0;
        request.contractor = $scope.contractor ? 1 : 0;
        request.verify = $scope.verify ? 1 : 0;
        request.permit_area = $scope.permit_area ? 1 : 0;
        request.sprinkler = $scope.sprinkler ? 1 : 0;
        request.equipment = $scope.equipment ? 1 : 0;
        request.flammable = $scope.flammable ? 1 : 0;
        request.combustible = $scope.combustible ? 1 : 0;
        request.cleaning = $scope.cleaning ? 1 : 0;
        request.floor_protect = $scope.floor_protect ? 1 : 0;
        request.enclose = $scope.enclose ? 1 : 0;
        request.ventilation = $scope.ventilation ? 1 : 0;
        request.clean_flame = $scope.clean_flame ? 1 : 0;
        request.vapours = $scope.vapours ? 1 : 0;
        request.detector = $scope.detector ? 1 : 0;
        request.thirty_min = $scope.thirty_min ? 1 : 0;
        request.sixty_min = $scope.sixty_min ? 1 : 0;
        request.exceed = $scope.exceed ? 1 : 0;
        request.briefed = $scope.briefed ? 1 : 0;
        /*
        if ($scope.signature == 0)
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please sign the form before submission.');
					return;
        }
 */
        
        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/forms/createhotworkrequest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Request have been submitted successfully');
                $scope.cancel();
                
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
