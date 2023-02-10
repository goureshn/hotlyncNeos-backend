app.controller('ValetController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, GuestService, AuthService, DateService, uiGridConstants) {
    var MESSAGE_TITLE = 'Valet';

    $scope.gs = GuestService;

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 190) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';
    $scope.splitdiv_height = 'height: ' + ($window.innerHeight - 160) + 'px; overflow-y: auto';
    $scope.listview_width = 'col-sm-12';
    $scope.detailview_width = 'col-sm-3';
    $scope.detailview = false;

    $scope.uploadexcel = {};
    $scope.uploadexcel.src = '';
    $scope.searchoptions = ['Status','Department','Location','Manufacture','Supplier'];
    $scope.searchoption = $scope.searchoptions[0];
    

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
        }

        $scope.listview_width = 'col-sm-9';
        $scope.detailview_width = 'col-sm-3';
        $scope.detailview = true;
    }
    
    $scope.Close = function () {
        $scope.listview_width = 'col-sm-12';
        $scope.detailview_width = 'col-sm-0';
        $scope.detailview = false;
    }



    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = 'height: ' + ($window.innerHeight - 115) + 'px; overflow-y: auto;';

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
    $scope.selectedTicket = [];
    $scope.valet_name = '';

    $scope.valet = {};


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

        var url = '/frontend/valet/valetlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            for(var i= 0 ; i < response.data.datalist.length; i++) {
                if(response.data.datalist[i].part_group.length>0) {
                    //var part_group = JSON.parse(response.data.datalist[i].part_group);
                    var part_group = response.data.datalist[i].part_group;
                    response.data.datalist[i].part_group = part_group;
                }
            }
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

    $scope.$on('create_valet', function(event, args){
        $scope.pageChanged();
    });

    $scope.refreshTickets();

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'W00000';
        return sprintf('W%05d', ticket.id);
    }

    $scope.onSelectTicket = function(ticket, event, type){
        ticket.type = type;
        $scope.selectedTicket = [];
        $scope.selectedTicket[0] = ticket;
        $scope.selectedNum = 0;
        $scope.valet_name = ticket.name;
        $scope.valet = ticket;
        if(ticket.critical_flag == 1) $scope.valet.critical_flag = true;
        if(ticket.critical_flag == 0) $scope.valet.critical_flag = false;
        $scope.checkSelection($scope.ticketlist);
    }
    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;
        $timeout(function() {
            $scope.onChangeStatus('detail');
         
            for(var i = 0; i < ticketlist.length; i++)
            {
                var index = -1;
                for(var j = 0; j < $scope.selectedTicket.length; j++ )
                {
                    if( ticketlist[i].id == $scope.selectedTicket[j].id)
                    {
                        index = j;
                        break;
                    }
                }
                ticketlist[i].active = index >= 0 ? true : false;
            }
        }, 100);

    }

    $scope.Delete = function () {
        if($scope.valet.id > 0) {
            var modalInstance = $uibModal.open({
                templateUrl: '/frontpage/tpl/valet/valet_delete.html',
                controller: 'ValetDeleteCtrl',
                scope: $scope,
                resolve: {
                    valet: function () {
                        return $scope.valet;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {

            });
        }
    }

});

app.controller('ValetDeleteCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Valet';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request = angular.copy($scope.valet);
        $http({
            method: 'POST',
            url: '/frontend/valet/deletevalet',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.valet = {};
            toaster.pop('success', MESSAGE_TITLE, ' Valet Entry has been deleted successfully');
            $uibModalInstance.close();
            $scope.pageChanged();
        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
    }
});
