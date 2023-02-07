app.controller('EngController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, GuestService, AuthService, DateService, uiGridConstants, liveserver,  $httpParamSerializer) {
    var MESSAGE_TITLE = 'Engineering';

    $scope.gs = GuestService;

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 190) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';
    $scope.count=0;

    $scope.select_status = [true, false];
    $scope.uploadexcel = {};
    $scope.uploadexcel.src = '';
    $scope.uploadexcel.name = '';
    $scope.uploadexcel.type = '';
    $scope.searchoptions = ['Status','Department','Location','Manufacture','Supplier'];
    $scope.searchoption = $scope.searchoptions[0];
    $scope.statuses = [
        {name: 'Pending', level: '1'},
        {name: 'In-Progress', level: '2'},
        {name: 'Resolved', level: '3'},
        {name: 'Closed', level: '4'},
        {name: 'Rejected', level: '5'}
    ];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $http.get('/list/suppliers')
         .then(function(response) {
            $scope.supplier_list = response.data;           
        });

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
      $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
      $scope.pageChanged();
    });

    $scope.onClickDateFilter = function() {
        angular.element('#dateranger').focus();
    }

    $scope.select_status = [true, false, false];
    $scope.onChangeStatus = function (val) {
        switch(val) {
            case 'edit':
                $scope.select_status = [false, true, false];
                break;
            case 'detail':
                $scope.select_status = [false, false, true];
                break;
        }
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
    $scope.eng_name = '';

    $scope.eng = {};
/*
       $scope.init = function(eng){
	       $scope.eng=eng;
	       //$scope.getTicketNumber(ticketlist[0]);
	       window.alert(ticketlist[0].id+"yes");
	       
       }
*/
 var filter = 'Total';
    $scope.onFilter = function getFilter(param) {
        filter = param;
        $scope.pageChanged();
    }


    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
       
    }

    $scope.onCreateNew =function() {
        $scope.select_status = [true,false];
    }


    $scope.pageChanged = function(preserve) {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);
        $scope.count++;       
        
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
        request.filter = filter;


        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        

        var url = '/frontend/eng_mytask/englist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.datalist;
            //window.alert($scope.ticketlist[0].id);
            // if($scope.count==1)
            // $scope.onSelectTicket($scope.ticketlist[0], event, 1);
            //window.alert("YES");

            $scope.paginationOptions.totalItems = response.data.totalcount;
            
           $scope.getEngrequestHist($scope.eng.id);

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
    $scope.$on('onpageChanged', function(event, args){
        //toaster.pop('error', 'Balls');

        $scope.pageChanged();;
        //toaster.pop('error', 'Refreshed');
    });

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

  
    $scope.refreshTickets();

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'EN00000';
        return sprintf('EN%05d', ticket.id);
    }
    
   


    $scope.onSelectTicket = function(ticket, event, type){
        $scope.select_status = [false, true];
        ticket.type = type;
        $scope.selectedTicket = [];
        $scope.selectedTicket[0] = ticket;
        $scope.selectedNum = 0;
        //window.alert(event.name);
           // window.alert("Yes");
        
        $scope.eng_name = ticket.subject;
        //window.alert(ticket.subject);
        $scope.eng = ticket;
        //window.alert($scope.eng.upload);
         if( $scope.eng.upload )
            $scope.eng.sub_download_array = $scope.eng.upload.split("|");
        else
            $scope.eng.sub_download_array = [];


        $scope.eng.outside_flag = $scope.eng.outside_flag == 1;    
        //window.alert($scope.eng.status);
		 $scope.getEngrequestHist($scope.eng.id);

        var request = {};
        request.eng_id = ticket.id;
        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/enginformlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data.filelist != null) $scope.eng.filelist = response.data.filelist;
            $scope.checkSelection($scope.ticketlist);
            //console.log(response);
           // $rootScope.$broadcast('equipment_workorder');
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
	$scope.getEngrequestHist = function (id) {
		
        //toaster.pop('success', MESSAGE_TITLE, $scope.issue.id);
        $scope.isLoading = true;
        //window.alert("here");
        
        $scope.eng.datalist = [];
        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/requesthist',
            data: {
                id: $scope.eng.id,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.eng.datalist = response.data.datalist;
				//$window.location.reload();

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };
    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;
        //$scope.onChangeStatus('detail');
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
    }
	
	$scope.onDownloadExcel = function(){
        var profile = AuthService.GetCredentials();

        var filters = {};
       
        filters.filter=filter;
        filters.user_id = profile.id;
        filters.report_by = 'ENG';
        filters.report_type = 'Detailed';
        filters.report_target = 'eng_summary';
        var profile = AuthService.GetCredentials();
        filters.property_id = profile.property_id;
        filters.start_time = $scope.daterange.substring(0, '2016-01-01'.length);
        filters.end_time = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        //filter.filter_value = $scope.filter_value;

        $window.location.href = '/frontend/report/eng_mytask_excelreport?' + $httpParamSerializer(filters);
    }

   
});

