app.controller('MyIssueController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, GuestService, AuthService, DateService, uiGridConstants, liveserver, $httpParamSerializer) {
    var MESSAGE_TITLE = 'Issue';

    $scope.gs = GuestService;

    $scope.tableState = undefined;
    $scope.isLoading = false;

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 190) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';
    $scope.count = 0;

      // Filter
      $scope.filter = {};


    $scope.select_status = [true, false];
    $scope.uploadexcel = {};
    $scope.uploadexcel.src = '';
    $scope.uploadexcel.name = '';
    $scope.uploadexcel.type = '';
    $scope.searchoptions = ['Status', 'Department', 'Location', 'Manufacture', 'Supplier'];
    $scope.searchoption = $scope.searchoptions[0];
    $scope.statuses = [
        { name: 'Pending', level: '1' },
        { name: 'In-Progress', level: '2' },
        { name: 'Resolved', level: '3' },
        { name: 'Closed', level: '4' },
        { name: 'Rejected', level: '5' }
    ];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger').focus();
    }

    $scope.select_status = [true, false, false];
    $scope.onChangeStatus = function (val) {
        switch (val) {
            case 'edit':
                $scope.select_status = [false, true, false];
                break;
            case 'detail':
                $scope.select_status = [false, false, true];
                break;
        }
    }

    $scope.selected_building = {};
    $scope.selected_building.id = 0;
    function getBuildingList() 
    {
        var request = {};
        $http({
            method: 'POST',
            url: '/frontend/it/buildinglist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            $scope.building_list = response.data;
            var alloption = {id: '0', name : 'All Buildings'};
            $scope.building_list.unshift(alloption);
            $scope.selected_building = angular.copy(alloption);
        }).catch(function (response) {
            console.error('Gists error', response.data);
        })
        .finally(function () {
        });
    }
    getBuildingList();

    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = 'height: ' + ($window.innerHeight - 95) + 'px; overflow-y: auto;';

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.ticketlist = [];
    $scope.selectedTicket = [];
    $scope.issue_name = '';

    $scope.issue = {};
    var filter = 'Total';
    $scope.onFilter = function getFilter(param) {
        filter = param;
        $scope.pageChanged();
    }

    
    $scope.onBuildingFilter = function(param) {
        $scope.selected_building = angular.copy(param);   
        $scope.pageChanged();
    }


    $scope.initPageNum = function () {
        $scope.paginationOptions.numberOfPages = 1;

    }

    $scope.onCreateNew = function () {
        $scope.select_status = [true, false];
    }

      // category filter    
      $scope.category_list = [];
      $scope.filter.category_tags = [];
      $http.get('/frontend/it/it_category')
              .then(function(response){
                  $scope.category_list = response.data;
              });
  
      $scope.categoryTagFilter = function(query) {
          return $scope.category_list.filter(function(item) {
              return item.category.toLowerCase().indexOf(query.toLowerCase()) != -1;
          });
      }
  
      // type filter
      $scope.type_list = [];
      $scope.filter.type_tags = [];
      $http.get('/list/typelistit')
          .then(function (response) {
              $scope.type_list = response.data;
          });
  
      $scope.typeTagFilter = function(query) {
          return $scope.type_list.filter(function(item) {
              return item.type.toLowerCase().indexOf(query.toLowerCase()) != -1;
          });
      }
  
       // subcategory filter
       $scope.subcat_list = [];
       $scope.filter.subcategory_tags = [];
       $http.get('/frontend/it/subcatlist')
           .then(function (response) {
               $scope.subcat_list = response.data;
           });
   
       $scope.subcatTagFilter = function(query) {
           return $scope.subcat_list.filter(function(item) {
               return item.sub_cat.toLowerCase().indexOf(query.toLowerCase()) != -1;
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
  
           // user filter
           $scope.user_list = [];
           $scope.filter.user_tags = [];
           $http.get('/list/user')
               .then(function (response) {
                   $scope.user_list = response.data;
               });
       
           $scope.userTagFilter = function(query) {
               return $scope.user_list.filter(function(item) {
                   return item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
               });
           }
  
              // status filter
               $scope.status_list = [];
               $scope.filter.status_tags = [];
               $http.get('/frontend/it/statuslist')
                   .then(function (response) {
                       $scope.status_list = response.data;
                   });
           
               $scope.statusTagFilter = function(query) {
                   return $scope.status_list.filter(function(item) {
                       return item.status.toLowerCase().indexOf(query.toLowerCase()) != -1;
                   });
               }
  
         // building filter
         $scope.building_list = [];
         $scope.filter.building_tags = [];
         $http.get('/list/building')
             .then(function (response) {
                 $scope.building_list = response.data;
             });
     
         $scope.buildingTagFilter = function(query) {
             return $scope.building_list.filter(function(item) {
                 return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
             });
         }


    $scope.pageChanged = function (preserve) {
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
        request.building_id = $scope.selected_building.id;


        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        request.category_ids = $scope.filter.category_tags.map(item => item.category);
        request.sub_cat_ids = $scope.filter.subcategory_tags.map(item => item.sub_cat);
        request.type_ids = $scope.filter.type_tags.map(item => item.id);
        request.dept_ids = $scope.filter.dept_tags.map(item => item.id);
        request.user_ids = $scope.filter.user_tags.map(item => item.id);
        request.building_ids = $scope.filter.building_tags.map(item => item.id);
        request.status_ids = $scope.filter.status_tags.map(item => item.status);

        $scope.filter_apply = $scope.filter.category_tags.length > 0 || 
                                    $scope.filter.subcategory_tags > 0 || 
                                    $scope.filter.dept_tags.length > 0 ||
                                    $scope.filter.status_tags.length > 0 ||
                                    $scope.filter.building_tags.length > 0||
                                    $scope.filter.user_tags.length > 0||
                                    $scope.filter.type_tags.length > 0;


        var url = '/frontend/it/issuelist';


        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            $scope.ticketlist = response.data.datalist;

            $scope.paginationOptions.totalItems = response.data.totalcount;
			$scope.getItrequestHist($scope.issue.id);


            if ($scope.paginationOptions.totalItems < 1)
                $scope.paginationOptions.countOfPages = 0;
            else
                $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
            console.log(response);
        }).catch(function (response) {
            console.error('Gists error', response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    };
    $scope.$on('onpageChanged', function (event, args) {
        $scope.pageChanged();
    });

    $scope.onPrevPage = function () {
        if ($scope.paginationOptions.numberOfPages <= 1)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.pageChanged();
    }

    $scope.onNextPage = function () {
        if ($scope.paginationOptions.totalItems < 1)
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if ($scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.pageChanged();
    }

    $scope.refreshTickets = function () {
        $scope.pageChanged();
    }


    $scope.refreshTickets();

    $scope.getTicketNumber = function (ticket) {
        if (!ticket)
            return 'IT00000';
        return sprintf('IT%05d', ticket.id);
    }

    $scope.refresh = $interval(function() {
        $scope.pageChanged();
    }, 60 * 1000);

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.refresh)) {
            $interval.cancel($scope.refresh);
            $scope.refresh = undefined;
        }
    });

  

    $scope.onSelectTicket = function (ticket, event, type) {
        $scope.select_status = [false, true];
        ticket.type = type;
        $scope.selectedTicket = [];
        $scope.selectedTicket[0] = ticket;
        $scope.selectedNum = 0;

        $scope.issue_name = ticket.subject;
        $scope.issue = ticket;
        if ($scope.issue.upload)
            $scope.issue.sub_download_array = $scope.issue.upload.split("|");
        else
            $scope.issue.sub_download_array = [];
        $scope.getItrequestHist($scope.issue.id);
  
    }
    $scope.getItrequestHist = function (id) {
        $scope.isLoading = true;
        
        $scope.issue.datalist = [];
        $http({
            method: 'POST',
            url: '/frontend/it/requesthist',
            data: {
                id: $scope.issue.id,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.issue.datalist = response.data.datalist;
				//$window.location.reload();

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };
    $scope.checkSelection = function (ticketlist) {
        if (!ticketlist)
            return;
        for (var i = 0; i < ticketlist.length; i++) {
            var index = -1;
            for (var j = 0; j < $scope.selectedTicket.length; j++) {
                if (ticketlist[i].id == $scope.selectedTicket[j].id) {
                    index = j;
                    break;
                }
            }
            ticketlist[i].active = index >= 0 ? true : false;
        }
    }

    $scope.onDownloadExcel = function () {
        var profile = AuthService.GetCredentials();

        var filters = {};

        filters.filter = filter;
        filters.user_id = profile.id;
        filters.report_by = 'IT';
        filters.report_type = 'Detailed';
        filters.report_target = 'it_summary';
        var profile = AuthService.GetCredentials();
        filters.property_id = profile.property_id;
        filters.start_time = $scope.daterange.substring(0, '2016-01-01'.length);
        filters.end_time = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

     
     
        filters.category_ids = $scope.filter.category_tags.map(item => item.category);
        filters.sub_cat_ids = $scope.filter.subcategory_tags.map(item => item.sub_cat);
        filters.type_ids = $scope.filter.type_tags.map(item => item.id);
        filters.dept_ids = $scope.filter.dept_tags.map(item => item.id);
        filters.user_ids = $scope.filter.user_tags.map(item => item.id);
        filters.building_ids = $scope.filter.building_tags.map(item => item.id);
        filters.status_ids = $scope.filter.status_tags.map(item => item.status);
        filters.excel_type = 'excel';

        $window.location.href = '/frontend/report/it_excelreport?' + $httpParamSerializer(filters);
    }


    $scope.editRequestor = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/it/modal/it_requestor.html',
            controller: 'ItRequestorCtrl',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {
              
            }
        });

        modalInstance.result.then(function (row) {
        }, function () {

        });
    }



});

/*
app.controller('ItRequestorCtrl', function($scope, $uibModalInstance, $http, AuthService, it, requestor_list) {
    $scope.it = it;
    $scope.requestor_list = requestor_list;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.it.category_new_name;
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/it/it_savecategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.it.category_new_name = '';
            $scope.category_list = response.data;
            $scope.setItCategoryList($scope.category_list);
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.selectRow = function(row){        
        $uibModalInstance.close(row);
    }

});
*/

app.controller('ItRequestorCtrl', function($scope, $http, AuthService, $uibModalInstance, $uibModal, toaster) {
    var MESSAGE_TITLE = 'Requestor List';

  
    $scope.requestor_list = [];

    $scope.requester = {};

    $scope.field = 0;

    $scope.create_flag = 0;

    function init()
    {

        
        $http.get('/list/department')
             .then(function (response) {
                 $scope.dept_list = response.data;
             });
        $http.get('/list/jobrole')
             .then(function (response) {
                 $scope.job_list = response.data;
             });
    }

    init();

    $scope.onSearch = function() {
        
        getRequestorList();
    }

    $scope.onDepartmentSelect = function($item, $model, $label)
    {
        $scope.requester.dept_id = $item.id;        
       
    }

    $scope.onJobRoleSelect = function($item, $model, $label)
    {
        $scope.requester.job_role_id = $item.id;        
       
    }

    getRequestorList();
    

    function getRequestorList()
    {
        var request = {};
        request.searchtext = $scope.requester.searchtext;

        $http({
            method: 'POST',
            url: '/frontend/it/stafflist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.requestor_list = response.data.datalist;  
                console.log($scope.requestor_list);    
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    
  
    $scope.onEditRequestor = function(row)
    {
        var request = {};
        request = angular.copy($scope.requester);

        request.ids = row.id;

        console.log(request);
      
        $http({
            method: 'POST',
            url: '/frontend/it/updaterequestor',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.requestor_list = response.data;
                init();
                toaster.pop('success', MESSAGE_TITLE, 'Requestor Details have been updated successfully');
                getRequestorList();
                $scope.field = 0;
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Update Requestor Details');
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    $scope.edit = function(row)
    {
        $scope.field = 1;
        $scope.create_flag = 0;
        $scope.requester = angular.copy(row);
        
    }    

    $scope.createRequestor = function(row)
    {
        $scope.field = 1;
        $scope.create_flag = 1;
        $scope.requester = {};
        
    } 

    $scope.onCreateRequestor = function(row)
    {
        var request = {};
        request = angular.copy($scope.requester);

        console.log(row);
      
        $http({
            method: 'POST',
            url: '/frontend/it/createrequestor',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.requestor_list = response.data;
                init();
                toaster.pop('success', MESSAGE_TITLE, 'Requestor Details have been created successfully');
                getRequestorList();
                $scope.field = 0;
                $scope.create_flag = 0;
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Create Requestor Details');
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
        
    }

    $scope.delete = function(row)
    {
        var request = {};
        request = angular.copy(row);
       
        
        $http({
            method: 'POST',
            url: '/frontend/it/deleterequestor',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.requestor_list = response.data;
                getRequestorList();
                toaster.pop('success', MESSAGE_TITLE, 'Requestor Details have been deleted successfully');
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Delete Requestor Details');
            })
            .finally(function() {
                
            });
    }
    

    $scope.ok = function() {
       
        $uibModalInstance.close();
    }

    

    $scope.cancel = function() {
        $uibModalInstance.dismiss();            
    }
    
});

