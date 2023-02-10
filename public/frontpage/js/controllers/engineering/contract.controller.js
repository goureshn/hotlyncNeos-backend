app.controller('ContractController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, GuestService, AuthService, DateService, uiGridConstants) {
    var MESSAGE_TITLE = 'Contract';

    $scope.gs = GuestService;
    var profile = AuthService.GetCredentials();

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 190) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';
    $scope.splitdiv_height = 'height: ' + ($window.innerHeight - 160) + 'px; overflow-y: auto';
    $scope.listview_width = 'col-sm-12';
    $scope.detailview_width = 'col-sm-4';
    $scope.detailview = false;
    $scope.full_height = 'height: ' + ($window.innerHeight - 90) + 'px; overflow-y: auto';
    $scope.ticketlist_height = $window.innerHeight - 88;

    $scope.searchoptions = ['Status','Department','Location','Manufacture','Supplier'];
    $scope.searchoption = $scope.searchoptions[0];

    $scope.listmode = false;

    $scope.location_list = [];
    $http.get('/list/locationtotallisteng?client_id=' + profile.client_id + '&user_id=' + profile.id)
            .then(function(response){
                $scope.location_list = response.data; 
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

    $scope.active = 1;
    $scope.select_status = [true, false, false,false,false];
    $scope.onChangeStatus = function (val) {
        switch(val) {
            case 'create':
                $scope.select_status = [true, false, false,false,false]; //create, edit, detail,
                break;
            case 'edit':
                $scope.select_status = [false, true, false,false,false];
                break;
            case 'detail':
                $scope.select_status = [false, false, true, false, false];
                $scope.active = 1;
                break;
            case 'update':
                $scope.select_status = [false, false, false,true,false];
                $scope.active = 3;
                break;
            case 'staff':
                $scope.select_status = [false, false, false,false,true];
                $scope.active = 4;
                break;
        }

        $scope.listview_width = 'col-sm-8';
        $scope.detailview_width = 'col-sm-4';
        $scope.detailview = true;
    }

    $scope.Close = function () {
        $scope.listview_width = 'col-sm-12';
        $scope.detailview_width = 'col-sm-0';
        $scope.detailview = false;
    }

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.ticketlist = [];
    $scope.selectedTicket = {};

    $scope.repair_request = {};

    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
    }

    $scope.pageChanged = function(preserve) {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);

        $scope.ticketlist = [];

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.


        var request = {};
        request.searchoption = $scope.searchoption;
        request.searchtext = $scope.searchtext;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;


        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;
        request.dept_id = profile.dept_id;
        request.job_role_id = profile.job_role_id;


        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var url = '/frontend/eng/contractlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
           $scope.ticketlist = response.data.datalist;

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
        $scope.pageChanged();
    }

    $scope.refreshTickets = function(){
        $scope.pageChanged();
    }

    $scope.$on('create_contract', function(event, args){
        $scope.pageChanged();
    });

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'C00000';
        return sprintf('C%05d', ticket.id);
    }

    $scope.onSelectTicket = function(ticket, event){        
        $scope.selectedTicket = ticket;        
        
        $timeout(function() {
            $scope.onChangeStatus('edit');            
            $scope.$broadcast('init', ticket);
        }, 100);
    }

    $scope.delete = function(row) 
    {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete the contract? Please note that this action cannot be undone.';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');   

                    deleteRow(row);
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });  
        
        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                callback(row);         
        }, function () {

        });   
        
    }

    function deleteRow(row)
    {
        var request = {};
        request.id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/eng/deletecontract',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Contract has been deleted successfully');                
                $scope.pageChanged();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
});

