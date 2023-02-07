app.controller('MySubcomplaintController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, $aside, toaster, AuthService) {
    var MESSAGE_TITLE = 'My Complaint';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.filter_option = {};

    $scope.complaint_filters = [
        {name: 'Approvals', badge: '2'},
        {name: 'Complaints', badge: '2'},
        {name: 'Returned', badge: '2'},
    ];

    $scope.filter_list = [
            'All Tickets',
            'Last 24 Hours',
            'Acknowledged by me',
        ];
	 $scope.filter_compen = [
            'Less than 500',
            'Greater than 500',
			'All'
        ];

    $scope.status_list = [
            'Pending',
            'Resolved',
            'Acknowledge',
            'Closed',
            'Rejected',
            'Flagged',
            'Unresolved',            
        ];  

    $scope.currentFilter = $scope.complaint_filters[1];

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

    $scope.filter = undefined;
    function setFilterPanel(filter) {
        filter.departure_date = moment(filter.departure_date).toDate();
        $scope.filter = filter;
    }

	$scope.onCompenChange=function(flag)
	{		
		if(flag==0)
		{
			$scope.filter.service_recovery="";
		}
		else if(flag==1)
		{
			$scope.filter.service_recovery=$scope.filter_compen[2];
		}
	}
	
    $scope.openFilterPanel = function(position, backdrop) {
        $rootScope.asideState = {
            open: true,
            position: position
        };

        function postClose(filter, mode) {
            $rootScope.asideState.open = false;
            if( filter.mode == 1 ) // complaint
            {
                if( filter.name == undefined )
                    return;

                $scope.currentFilter = filter;
				
            }

            if( filter.mode == 2 ) 
            {

            }
            
            search_option = '';
            $scope.$emit('erase_search');

            $scope.refreshTickets();
        }

        $aside.open({
            templateUrl: 'tpl/toolbar/mycomplaintfilter.aside.html',
            placement: position,
            scope: $scope,
            size: 'sm',
            backdrop: backdrop,
            controller: function($scope, $uibModalInstance) {
				//console.log(filter);
                $scope.ok = function(e) {
                    $uibModalInstance.close();
                    e.stopPropagation();
                };
                $scope.cancel = function(e) {
                    $uibModalInstance.dismiss();
                    e.stopPropagation();
                };
                $scope.onSelectFilter = function(filter) {  // filter sort
                    if( filter.name == 'Complaints' )
                    {
                        $scope.currentFilter.name = 'Complaints';
                        return;
                    }

                    filter.mode = 1;
                    $uibModalInstance.close(filter);
                }

                $scope.saveTicketFilter = function() {
					// filter setting  
                    filter.mode = 2;
                    $uibModalInstance.close({}, 2);
                }   
            },
        }).result.then(postClose, postClose);
    }


    $scope.onSelectItem = function(item) {
        $scope.currentFilter = item;
        filter = 'Total';
        $scope.initPageNum();
        $scope.refreshTickets();
    }

    $scope.detail_view_height = $window.innerHeight - 85;

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

    var filter = 'Total';
    $scope.onFilter = function getFilter(param) {
        filter = param;
        $scope.pageChanged();
    }


    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
    }

    $scope.pageChanged = function(preserve) {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);

        $scope.ticketlist = [];

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        var filtername = '';
        if( $scope.currentFilter != undefined )
            filtername = $scope.currentFilter.name;

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filtername = $scope.currentFilter.name;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;
        request.dept_id = profile.dept_id;
        request.job_role_id = profile.job_role_id;
        // request.filter = filter;
        request.filter = $scope.filter;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
		
        
        var url = '';
        if( $scope.currentFilter.name == 'Returned' )
            url = '/frontend/guestservice/compensationlist';
        else if( $scope.currentFilter.name == 'Approvals' )
            url = '/frontend/complaint/onroutemylist';
        else if( $scope.currentFilter.name == 'Complaints' )
            url = '/frontend/complaint/submylist';
        console.log('filter var');
        console.log(request);
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.datalist;
            console.log($scope.ticketlist);
            $scope.ticketlist.forEach(function(item, index) {
                item.total_comp = item.compensation_total + item.sub_compen_list_all;
                
            });
          
            var type = 1; 

            if( $scope.currentFilter.name == 'Approvals' ||
                $scope.currentFilter.name == 'Returned' )
                type = 2;
            else if( $scope.currentFilter.name == 'Complaints' )
                type = 3;
               
            for(var i = 0; i < $scope.ticketlist.length; i++)
            {
                $scope.ticketlist[i].type = type;
            }

            if( $scope.ticketlist.length > 0 && !(preserve == true) ) {
                $scope.selectedTicket = [];
                $scope.selectedTicket[0] = $scope.ticketlist[0];
               
                $scope.selectedNum = 0;
            }
            $scope.checkSelection($scope.ticketlist);
            $scope.paginationOptions.totalItems = response.data.totalcount;

            if( $scope.paginationOptions.totalItems < 1 )
                $scope.paginationOptions.countOfPages = 0;
            else
                $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

            setFilterPanel(response.data.filter);

            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

        getFilterList();    

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

    $scope.onChangeFlagFilter = function() {
        $scope.pageChanged();           
    }

    var category_list = [];
    var profile = AuthService.GetCredentials();
    function getComplaintCategoryList() {
        category_list = [];
     
        var request = {};
        request.dept_id = profile.dept_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            category_list = response.data;            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    var subcategory_list = [];
    function getComplaintSubcategoryList(category_id) {
        subcategory_list = [];
     
        var request = {};
        request.category_id = 0;
        request.dept_id = profile.dept_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/subcategorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            subcategory_list = response.data;                        
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }
	
	var location_list = [];
	var location_type_list = [];
    //var profile = AuthService.GetCredentials();
    function getComplaintLocationList() {
        location_list = [];
		var profile = AuthService.GetCredentials();
        var request = {};
        request.dept_id = profile.dept_id;
		request.job_role_id = profile.job_role_id;
		request.property_id = profile.property_id;
        request.dispatcher = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/locationlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            location_list = response.data.loc_list;            
            location_type_list = response.data.loc_type_list;            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    getComplaintCategoryList();
    getComplaintSubcategoryList();
	getComplaintLocationList();


    $scope.loadCategoryFilters = function(query) {
        return category_list.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.loadSubcategoryFilters = function(query) {
        return subcategory_list.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };
	
	$scope.loadLocationFilters = function(query) {		
        return location_list.filter(function(type){
			return type.loc_name.toLowerCase().indexOf(query.toLowerCase()) != -1; 
		});
    };

    $scope.loadLocationTypeFilters = function(query) {			  
        if( query == "" )
            return location_type_list;

		return location_type_list.filter(function(type){
			return type.type.toLowerCase().indexOf(query.toLowerCase()) != -1 || type.type.toLowerCase().indexOf(query.toLowerCase()) != -1; 
		});
    };
	
	

    function getFilterList() {
        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;    
        request.dispatcher = profile.id;
        request.job_role_id = profile.job_role_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/myfilterlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.complaint_filters = response.data;
				//console.log($scope.complaint_filters);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });    
    }

    $scope.refreshTickets();

    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;

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

    $scope.onSelectTicket = function(ticket, event, type){
       
        $scope.selectedTicket = [];
        $scope.selectedTicket[0] = ticket;
        $scope.selectedNum = 0;
     
        $scope.checkSelection($scope.ticketlist);
    }


    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'F00000';

        if( ticket.type == 3 )
            return $scope.getSubComplaintTicketNumber(ticket);
        else
            return $scope.getCompensationTicketNumber(ticket);
    }

    $scope.getSubComplaintTicketNumber = function(ticket){
        if(!ticket)
            return 'F00000';

        return sprintf('F%05d%s', ticket.parent_id, ticket.sub_label);      
    }

    $scope.getCompensationTicketNumber = function(ticket){
        if(!ticket)
            return 'F00000';
        return sprintf('F%05d', ticket.task_id);      
    }

    $scope.$on('onChangedSubComplaint', function(event, args){
        if( $scope.currentFilter.name == 'Complaints' ||
            $scope.currentFilter.name == 'Approvals'
            )
            $scope.pageChanged(true);        
    });

    $scope.$on('subcomplaint_assigned', function(event, args){
        if( $scope.currentFilter.name == 'Complaints' )
            $scope.pageChanged(true);        
    });

    $scope.$on('main_complaint_delete', function(event, args){
        if( $scope.currentFilter.name == 'Complaints' )
            $scope.pageChanged(true);        
    });

    $scope.$on('subcomplaint_escalated', function(event, args){
        if( $scope.currentFilter.name == 'Complaints' )
            $scope.pageChanged(true);        
    });


    $scope.$on('compensation_post', function(event, args){
        if( $scope.currentFilter.name == 'Approvals' || $scope.currentFilter.name == 'Returned' )
            $scope.pageChanged(true);        
    });

    $scope.$on('subcomplaint_create', function(event, args){
        var complaint = args.info;

        if( $scope.currentFilter.name == 'Complaints' )
        {
            $scope.initPageNum();
            $scope.pageChanged();
        }
    });

    $scope.$on('sub_complaint_delete', function(event, args){
        $scope.pageChanged();
    });

    $scope.$on('subcomplaint_status_changed', function(event, args){
        var complaint = args.info;

        if( $scope.currentFilter.name == 'Complaints' )
        {
            $scope.ticketlist.forEach(row => {
                if(row.id == complaint.sub.id)
                {
                    $scope.pageChanged(true);
                }
            })
        }
    });

    $scope.$on('subcomplaint_compensation_create', function(event, args){
        var complaint = args.info;

        if( $scope.currentFilter.name == 'Complaints' )
        {
            $scope.ticketlist.forEach(row => {
                if(row.id == complaint.sub_id)
                {
                    row.sub_compen_list_total = complaint.sub_item_total;
                }
            })
        }        
    });

    function confirmDeleteComplaint(row, callback)
    {
        if( row.total < 1 )
        {
            callback(row);
            return;
        }

        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete the Sub Complaint?';

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
    $scope.onDelete = function(row) {
        confirmDeleteComplaint(row, function(row) {
            var modalInstance = $uibModal.open({
                templateUrl: 'tpl/modal/modal_input.html',
                controller: 'ModalInputCtrl',
                scope: $scope,
                resolve: {
                    title: function () {
                        return 'Please input reason to delete the Sub Complaint';
                    },
                    min_length: function () {
                        return 0;
                    }
                }
            });
    
            modalInstance.result
                .then(function (comment) {
                    deleteComplaint(comment,row);
                }, function () {
    
                });
        });
    }

    function deleteComplaint(comment,row) {
        var request = {};

        request.id = row.id;
        console.log(row);
        request.parent_id = row.parent_id;
        request.comment = comment;

        if( request.comment == null )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please enter Comment');
            return;
        }

        
        $http({
            method: 'POST',
            url: '/frontend/complaint/deletesubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('sub_complaint_delete',response.data);  
          //  $scope.pageChanged();
          //  $scope.refreshTickets();
            // $scope.pageChanged();
            toaster.pop('info', MESSAGE_TITLE, 'Sub Complaint deleted successfully.');
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Sub Complaint.');
        })
        .finally(function() {

        });
        
    }
});




