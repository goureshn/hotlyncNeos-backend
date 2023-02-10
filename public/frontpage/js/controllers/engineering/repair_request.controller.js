app.controller('RepairRequestController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, toaster, GuestService, AuthService, $httpParamSerializer) {
    var MESSAGE_TITLE = 'Work Request';

    $scope.gs = GuestService;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;

    $scope.ticketlist_height = $window.innerHeight - 88;

    $scope.prioritys = [
        'Low',
        'Medium',
        'High',
        'Urgent'
    ];

    $scope.repair_request_cofig = {};

    $scope.getRepairRequest = function()
    {
        var data = {};
        data.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/backoffice/configuration/wizard/getrepairrequest',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .success(function (data, status, headers, config) {
                $scope.repair_request_cofig = data;
                $scope.repair_request_cofig.repair_auth_on = data.repair_auth_on == "1";
                $scope.repair_request_cofig.create_workorder_flag = data.create_workorder_flag == "1";
                $scope.repair_request_cofig.repair_request_equipment_status = data.repair_request_equipment_status == "1";
            })
            .error(function (data, status, headers, config) {
                console.log(status);
            });
    };

    $scope.getRepairRequest();

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
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.ticketlist = [];
    $scope.selectedTicket = [];

    $scope.repair_request = {};

    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
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


        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;
        request.dept_id = profile.dept_id;
        request.job_role_id = profile.job_role_id;
        request.priority = $scope.priority;


        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        request.assigne_ids = $scope.filter.staff_tags.map(item => item.id).join(',');
        request.status_names = $scope.filter.status_names;

        console.log(request.status_names);
        request.category_ids = $scope.filter.category_tags.map(item => item.id);
        request.dept_ids = $scope.filter.dept_tags.map(item => item.id);
        request.location_ids = $scope.filter.location_tags.map(item => item.id);
        request.equipment_ids = $scope.filter.equipment_tags.map(item => item.id);
        request.equip_ids = $scope.filter.equip_id_tags.map(item => item.equip_id);

        $scope.filter_apply = $scope.filter.staff_tags.length > 0 ||
                                    $scope.filter.status_names.length > 0 ||
                                    $scope.filter.category_tags.length > 0 ||
                                    $scope.filter.dept_tags.length > 0 ||
                                    $scope.filter.location_tags.length > 0 ||
                                    $scope.filter.equipment_tags.length > 0||
                                    $scope.filter.equip_id_tags.length > 0;

        var url = '/frontend/eng/repairrequestlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
           $scope.ticketlist = response.data.datalist;

            $scope.ticketlist.forEach(function(item, index) {
                item.onlytime = moment(item.due_date).format('HH:mm A');
                item.onlydate = moment(item.due_date).format('DD MMM YYYY');
                item.browser_time=moment();
                item.start_time = moment(item.start_date).format('HH:mm A');
                item.end_time = moment(item.end_date).format('HH:mm A');
                if (item.requestor_type == 'User')
                    item.wholename = item.wholename;
                if (item.requestor_type == 'Leasor')
                    item.wholename = item.leasor;
                if (item.requestor_type == 'Tenant')
                    item.wholename = item.tenant_name;
                // window.alert(item.browser_time);

                $http.get('/frontend/eng/getrepaircomment?id=' + item.id)
                .then(function(response){
                    item.comment_list = response.data.content;

                });

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
        if(!ticket)
            return 'W' + moment().format('RYYYYMMDD00');
        return 'W' + moment(ticket.created_at).format('RYYYYMMDD') + sprintf('%02d', ticket.daily_id);
    }

    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;
        $timeout(function() {
            $scope.onChangeStatus('edit');

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

    // Filter
    $scope.filter = {};

    // assignee filter
    $scope.filter.staff_tags = [];

    $scope.staff_list = [];
    $http.get('/frontend/eng/requestorlist')
                .then(function(response){
                    $scope.staff_list = response.data.content;
                });


    $scope.staffTagFilter = function(query) {
        return $scope.staff_list.filter(function(item) {
            return item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };



    // Staff Filter
    $scope.staff_group_list = [];

    function getStaffGroupList()
    {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        $http.get('/frontend/eng/getstaffgrouplist?property_id='+property_id  + '&user_id=' + profile.id)
            .then(function(response){
                $scope.staff_group_list = response.data.content.map(item => {
                    item.text = item.name + '-' + item.label;
                    return item;
                });
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    getStaffGroupList();

    $scope.staffGroupTagFilter = function(query) {
        return $scope.staff_group_list.filter(function(item) {
            return item.text.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // repair request status list filter
    $scope.filter.status_names = [];

    $scope.status_list = [
        { id: 1, name: 'Pending' },
        { id: 2, name: 'Assigned'},
        { id: 3, name: 'In Progress'},
        { id: 4, name: 'Completed'},
        { id: 5, name: 'Rejected'},
        { id: 6, name: 'Closed'},
        { id: 7, name: 'Pre-Approved'}
    ];

    function getInitDataFromStorage() {
        let tempStatusData = window.localStorage.getItem('temp_status_request_data');
        if (tempStatusData != undefined && tempStatusData != null) {
            window.localStorage.removeItem('temp_status_request_data');
            let realData = JSON.parse(tempStatusData);

            if (realData.status) {
                $scope.filter.status_names = $scope.status_list.filter(item => {
                    if (item.name === realData.status) {
                        return true;
                    }
                    return false;
                });
            }

            if ((realData.status == 'In Progress') && (realData.flag == 1)){
                var alloption = {id: '2', name : 'Assigned'};
                $scope.filter.status_names.unshift(alloption);
            }

            console.log($scope.filter.status_names);

            $scope.daterange = realData.daterange;
            $scope.dateRangeOption = realData.dateRangeOption;
            $scope.priority = realData.priority;
        }
    }

    getInitDataFromStorage();

    // $scope.refreshTickets();

    $scope.statusNameFilter = function(query) {
        return $scope.status_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // category filter
    $scope.category_list = [];
    $scope.filter.category_tags = [];
    $http.get('/frontend/eng/repairrequest_getcategory_list?user_id='+ profile.id)
            .then(function(response){
                $scope.category_list = response.data.content;
            });

    $scope.categoryTagFilter = function(query) {
        return $scope.category_list.filter(function(item) {
            return item.category_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

      // department filter
      $scope.dept_list = [];
      $scope.filter.dept_tags = [];
      $http.get('/list/department')
          .then(function (response) {
              $scope.dept_list = response.data;
          });

      $scope.deptTagFilter = function(query) {
          return $scope.dept_list.filter(function(item) {
              return item.department.toLowerCase().indexOf(query.toLowerCase()) != -1;
          });
      }

    // location filter
    $scope.filter.location_tags = [];
    $scope.location_list = [];
    $http.get('/list/locationtotallisteng?client_id=' + profile.client_id + '&user_id=' + profile.id)
            .then(function(response){
                $scope.location_list = response.data;
            });

    $scope.locationTagFilter = function(query) {
        return $scope.location_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // equipment filter
    $scope.filter.equipment_tags = [];
    $scope.equipment_list = [];
    $http.get('/list/equipmentlist?property_id=' + profile.property_id)
            .then(function(response){
                $scope.equipment_list = response.data;
            });

    $scope.equipmentTagFilter = function(query) {
        return $scope.equipment_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

       // equipment id list
       $scope.filter.equip_id_tags = [];
       equip_id_list = [];
       $http.get('/frontend/equipment/idlist?property_id=' + profile.property_id)
           .then(function(response){
               equip_id_list = response.data;
           });

       $scope.equipIdTagFilter = function(query) {
           return equip_id_list.filter(function(item) {
             return item.equip_id.toLowerCase().indexOf(query.toLowerCase()) != -1;
           });
       }


    // supplier list

    $http.get('/list/suppliers')
        .then(function(response){
            $scope.supplier_list = response.data;
        });

    $scope.onCreateRequest = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/repair_request_create.html',
            controller: 'RepairRequestCreateController',
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

    $scope.onSelectTicket = function(ticket){
        $scope.repair_request = ticket;
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/repair_request_edit.html',
            controller: 'RepairRequestEditController',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                repair_request: function () {
                    return ticket;
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {

        }, function () {

        });
    }

    $scope.onExportExcel = function()
    {
        var request = {};
        request.searchoption = $scope.searchoption;
        request.searchtext = $scope.searchtext;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;
        request.dept_id = profile.dept_id;
        request.job_role_id = profile.job_role_id;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        request.assigne_ids = $scope.filter.staff_tags.map(item => item.id).join(',');
    //    request.status_names = $scope.filter.status_names.map(item => item.name);

        var status_tags = [];
        for(var i = 0; i < $scope.filter.status_names.length; i++)
            status_tags.push($scope.filter.status_names[i].name);

        request.status_names = JSON.stringify(status_tags);

        $scope.filter.status_tags = JSON.stringify(status_tags);
        request.category_ids = $scope.filter.category_tags.map(item => item.id);
        request.dept_ids = $scope.filter.dept_tags.map(item => item.id);
        request.location_ids = $scope.filter.location_tags.map(item => item.id);
        request.equipment_ids = $scope.filter.equipment_tags.map(item => item.id);
        request.equip_ids = $scope.filter.equip_id_tags.map(item => item.id);
        request.excel_type = 'excel';

        $window.location.href = '/frontend/eng/exportrepairrequest?' + $httpParamSerializer(request);
    }

    $scope.$on('refresh_repair_page', function(event, args){
        $scope.pageChanged();
    });
});


