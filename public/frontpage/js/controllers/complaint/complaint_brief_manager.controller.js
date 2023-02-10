app.controller('ComplaintBriefManagerController', function ($scope, $rootScope, $http, $httpParamSerializer, $timeout, $uibModal, $window, $document, $aside, AuthService, toaster) {
    var MESSAGE_TITLE = 'Briefing';
    
    var isIE = !!navigator.userAgent.match(/MSIE/i);
    $scope.mobflag = 0;
    $scope.moveflag = 0;
    var index =0;
    var index2 =0;
    $scope.index=0;
    $scope.nav=0;
    $scope.btn = {};
    $scope.btn.pending = 0;
    $scope.btn.resolved = 0;
    $scope.btn.rejected = 0;
    $scope.btn.acknowledge = 0;
    $scope.btn.discussed = 0;
    $scope.btn.flagged = 0;
    $scope.btn.total = 1;

    $scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';

    $scope.models = [
        {listName: "Source", items: [], dragging: false},
        {listName: "Target", items: [], dragging: false}
    ];

    $scope.participant_list = [];

    var profile = AuthService.GetCredentials();

    $scope.briefing_ticket = {};
    $scope.discuss_started = false;

    $scope.isLoading = false;
    $scope.collapsed = false;

    $scope.$watch('discuss_started', function(newValue, oldValue) {
        // if( $scope.mobflag != 1 )
        //     return;

        $scope.collapsed = newValue;
    });

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

    $scope.filter = 'Total';
    $scope.filter_value = '';
    $scope.onFilter = function getFilter(param) {
	    if($scope.filter != param)
	    $scope.checkbtn(param);
        $scope.filter = param;
        $scope.pageChanged();
    }
    
    $scope.checkbtn = function(value){
	//$scope.btn = 0;
	
    $scope.btn.resolved = 0;
    $scope.btn.pending = 0;
    $scope.btn.acknowledge = 0;
    $scope.btn.discussed = 0;
    $scope.btn.flagged = 0;
    $scope.btn.total = 0; 
    switch(value){
	    case "Pending": $scope.btn.pending = 1;
	                    break;
	    case "Resolved": $scope.btn.resolved = 1;
	                    break;
	    case "Acknowledge": $scope.btn.acknowledge = 1;
	                    break;
	    case "Discussed": $scope.btn.discussed = 1;
	                    break;
	    case "Flagged": $scope.btn.flagged = 1;
	                    break;
	    case "Total": $scope.btn.total = 1;
	                    break;                                                
	                    
    }
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
    
    $scope.init = function(){
	    if(isIE)
        { 
    	   angular.element($window.document.body).addClass('ie');
    	}

        if(isSmartDevice( $window ) )
        { 
    	    angular.element($window.document.body).addClass('smart');
            //window.alert("Wohoo");
            $scope.mobflag = 1;
         
    	}
    	
    	if($scope.mobflag==1)
    	{
	    	openNav();
    	}
    }
    $scope.init();

    $scope.pageChanged = function() {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var selected_ids = [];
        var category_ids = [];
        for(var i = 0; i < $scope.models[1].items.length; i++ )
        {
            selected_ids.push($scope.models[1].items[i].id);
            if( $scope.models[1].items[i].category_id > 0 )
                category_ids.push($scope.models[1].items[i].category_id);
        }

        /////////////////////
        var request = {};
        request.page = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter = $scope.filter;
        request.filter_value = $scope.filter_value;
        request.selected_ids = selected_ids;
        request.category_ids = category_ids;
        
        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/briefingsrclist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.models[0].items = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;
               // $scope.filter_value = '';

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
 
                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
    
    function isSmartDevice( $window )
            {
                // Adapted from http://www.detectmobilebrowsers.com
                var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
                // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
                return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
            }

    function getProgressList() {
        $scope.discuss_started = false;

        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/briefingprogresslist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                setProgressList(response.data);
                
                $scope.pageChanged();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    function selectBreifing(brief_id)
    {
        $scope.selected_brief_num = 0;
        for(var i = 0; i < $scope.models[1].items.length; i++ )
        {
            if( brief_id == $scope.models[1].items[i].brief_id )
            {
                $scope.briefing_ticket = $scope.models[1].items[i];
                $scope.models[1].items[i].selected = true;
                // $scope.models[1].items[i].dis_flag = 2;
                $rootScope.$broadcast('selected_complaint', $scope.briefing_ticket);
                $scope.selected_brief_num = i;
            }   
            else 
            {
                $scope.models[1].items[i].selected = false;
            }         
        }    
    }

    function setProgressList(data) {
        $scope.participant_list = data.participant_list;

        $scope.models[1].items = data.datalist;
        if( $scope.models[1].items.length > 0 )
        {
            if( data.current_brief_id >= 0 )
            {
                selectBreifing(data.current_brief_id);                
                $scope.discuss_started = true;    
            }
            // else
            // {
            //     $scope.briefing_ticket = $scope.models[1].items[0];
            //     $scope.models[1].items[0].selected = true;    

            //     $rootScope.$broadcast('selected_complaint', $scope.briefing_ticket);
            // }            
        }

    }

    getProgressList();

    $scope.getProcess = function(row) {
        if( row.total < 1 )
            return 0;
        return row.completed * 100 / row.total;
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

    $scope.onSelectTicket = function(ticket){
        if( $scope.discuss_started == false )
        {
            ticket.selected = !ticket.selected;
            return;
        }       
    }
    //$scope.countdown
   $scope.counter = 10;
   
    $scope.onTimeout = function(){
        $scope.counter--;
        mytimeout = $timeout($scope.onTimeout,1000);
    }
    var mytimeout = $timeout($scope.onTimeout,1000);
    
    

    $scope.getTicketNumber = function(ticket) {
	    
        if( ticket == undefined || ticket.id == undefined )
            return 'Waiting...';
            
        $scope.index=(($scope.selected_brief_num + 1)/($scope.models[1].items.length))-1;    
		$scope.tabsSwipeCtrlFn($scope.index);
        return sprintf('C%05d', ticket.id);
    }
    $scope.tabsSwipeCtrlFn = function (init_index) {
    
        $scope.ngIncludeTemplates = [{ index:init_index, url: 'tpl/complaint/complaint_view.html' }];
        $scope.selectPage = selectPage;

        /**
        * Initialize with the first page opened
        */
        $scope.ngIncludeSelected = $scope.ngIncludeTemplates[0];

        /**
        * @name selectPage
        * @desc The function that includes the page of the indexSelected
        * @param indexSelected the index of the page to be included
        */
        function selectPage(indexSelected,dir,main_index) {

    	        $scope.ngIncludeTemplates.push({ index: main_index , url: 'tpl/complaint/complaint_view.html' });
                $scope.moveToLeft = true;
                if(dir=='left')
                {
    	            if(indexSelected==-1)
    	            indexSelected= $scope.models[1].items.length-1;
                $scope.onPrevBreifing();
                
    	            
                }
                else
                {
    	       
                if(indexSelected==($scope.models[1].items.length))    
                indexSelected=0;
                $scope.onNextBreifing(); 
                }
            $scope.index=indexSelected;
            $scope.ngIncludeSelected = $scope.ngIncludeTemplates[indexSelected];
        }
    }

    $scope.toggleDiscuss = function() {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        if( $scope.discuss_started == false )   // start discussing
        {
            if( $scope.models[1].items.length < 1 )
            {
                toaster.pop('info', MESSAGE_TITLE, 'Please select at least one complaint for briefing')
                return;
            }

            var selected_ids = [];
            for(var i = 0; i < $scope.models[1].items.length; i++ )
                selected_ids.push($scope.models[1].items[i].id);
            
            request.selected_ids = selected_ids;

            $http({
                method: 'POST',
                url: '/frontend/complaint/startbriefing',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    setProgressList(response.data);
                    toaster.pop('success', MESSAGE_TITLE, 'Briefing Started');
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                });
        }
        else  // end discussing
        {           
            $http({
                method: 'POST',
                url: '/frontend/complaint/endbriefing',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    toaster.pop('warning', MESSAGE_TITLE, 'Briefing Finished');

                    $scope.models[1].items = [];
                    $scope.pageChanged();
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                })
                .finally(function() {
                });
        }

        clearSelection();

        $scope.discuss_started = !$scope.discuss_started;
    }
    
    

    function clearSelection() {
        for(var j = 0; j < $scope.models.length; j++ )
        {
            $scope.selectedTickets = [];
            for(var i = 0; i < $scope.models[j].items.length; i++)
            {
                $scope.models[j].items[i].selected = false;                   
            }
            $scope.models[j].dragging = false;
        }
    }
    function openNav() {
	    //window.alert('yes');
	    var myEl = angular.element( document.querySelector( '#myNav' ) );
        
    myEl.css({height: '100%'});
    $scope.nav=1;
    $timeout(function(){
	    $scope.closeNav();
	    },10000);
    
    }

$scope.closeNav=function() {
	//window.alert("uesnbasd");
	$scope.nav=0;
    angular.element( document.querySelector( '#myNav' ) ).css({height:'0%'});
}

    function moveNextComplaint(direction, discussed_flag) {
        var ret = 0;

        var ticket = $scope.briefing_ticket;

        var current_num = -1;

        // find current selected
        for(var i = 0; i < $scope.models[1].items.length; i++ )
        {
            if( ticket.id == $scope.models[1].items[i].id )
            {
                current_num = i;
            }            
        }

        // find next ticket
        var next_num = -1;

        if( direction == 1 )
        {
            if( current_num == -1 )
                next_num = 0;
            else if( current_num < $scope.models[1].items.length - 1 )
                next_num = current_num + 1;

            if( next_num >= 0 )
            {
                for(var i = 0; i < $scope.models[1].items.length; i++ )
                {
                    if( i == next_num )
                        $scope.models[1].items[i].selected = true;               
                    else
                        $scope.models[1].items[i].selected = false;   
                }    
            }            
        }

        if( next_num >= 0 ) // exist next ticket
        {
            $scope.briefing_ticket = $scope.models[1].items[next_num];
            $rootScope.$broadcast('selected_complaint', $scope.briefing_ticket);
        }
        else // no exist
        {
            // stop briefing
            if( discussed_flag == true )
            {
                $scope.discuss_started = true;
                $scope.toggleDiscuss();    
            }
            else
            {
                ret = -1;
            }
        }

        return ret;
    }

    $scope.onDiscussed = function() {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.brief_id = $scope.briefing_ticket.brief_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/discussnextbriefing',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);                
            })
            .finally(function() {
            });
    }

    $scope.onPrevBreifing = function() {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.brief_id = $scope.briefing_ticket.brief_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/moveprevbriefing',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);                
            })
            .finally(function() {                
            });
    }

    $scope.onNextBreifing = function() {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.brief_id = $scope.briefing_ticket.brief_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/movenextbriefing',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);                
            })
            .finally(function() {
            });
    }

    $scope.viewParticipants = function(position, backdrop) {
        $rootScope.asideState = {
            open: true,
            position: position
        };

        function postClose(filter) {
            $rootScope.asideState.open = false;            
        }

        $aside.open({
            templateUrl: 'tpl/toolbar/participants.aside.html',
            placement: position,
            scope: $scope,
            size: 'sm',
            backdrop: backdrop,
            controller: function($scope, $uibModalInstance) {
                $scope.ok = function(e) {
                    $uibModalInstance.close();
                    e.stopPropagation();
                };
                $scope.cancel = function(e) {
                    $uibModalInstance.dismiss();
                    e.stopPropagation();
                };                
            },
        }).result.then(postClose, postClose);
    }

    $scope.$on('briefing_selected', function(event, args){
        var brief_id = args.brief_id;

        if( brief_id >= 0 )
            selectBreifing(brief_id);
    });

    $scope.viewDetail = function (row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_detail.html',
            controller: 'ComplaintDetailCtrl1',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return row;
                },                
            }
        });

        modalInstance.result.then(function (selectedItem) {
            
        }, function () {

        });
    }

    $scope.onGroupBy = function (group_by) {        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/itemsort.html',
            controller: 'ItemSortModal',
            windowClass: 'app-modal-window',
            resolve: {         
                group_by: function () {
                    return group_by;
                },     
                ticket_list: function () {
                    return $scope.models[1].items;
                }       
            }
        });

        modalInstance.result.then(function (data) {
            reorderTicket(data);
        }, function () {

        });
    };

    function reorderTicket(data) {         
        var order_by = data.order_by;
        var order_list = data.order_list;
        var list = $scope.models[1].items;

        if( order_by != 'Department' )
        {            
            $scope.models[1].items.sort(function(a, b) {
                var val_a = 0;
                var val_b = 0;

                switch(order_by) {
                    case 'Department':

                        break;
                    case 'Severity':
                        val_a = a.severity;
                        val_b = b.severity;
                        break;
                    case 'Status':
                        val_a = a.status;
                        val_b = b.status;
                        break;
                }

                // find pos in order list
                var pos_a = 0;
                var pos_b = 0;

                for(var i = 0; i < order_list.length; i++ )
                {
                    if( order_by == 'Status' )
                    {
                        if( val_a == order_list[i].name )
                            pos_a = i;
                        if( val_b == order_list[i].name )
                            pos_b = i;                    
                    }

                    if( order_by == 'Severity' )
                    {
                        if( val_a == order_list[i].id )
                            pos_a = i;
                        if( val_b == order_list[i].id )
                            pos_b = i;                    
                    } 
                }

                if( pos_a > pos_b )
                    return 1;
                else
                    return -1;
            });
        }

        if( order_by == 'Department' )
        {
            var ticket_list = angular.copy($scope.models[1].items);

            var new_ticket_list = [];

            angular.forEach(order_list, function(row){
                var temp_ticket_list = [];
                angular.forEach(ticket_list, function(item) {
                    var exists_array = item.dept_list.filter(function(row1) {
                        return row.id == row1.id;
                    });  
                    var exists_array_2 = item.selected_ids.filter(function(row1) {
                        return row.id == row1.id;
                    });      

                    if( (exists_array.length > 0)|| (exists_array_2.length > 0))
                        new_ticket_list.push(item);
                    else
                        temp_ticket_list.push(item);    
                });

                ticket_list = temp_ticket_list;
            });

            $scope.models[1].items = new_ticket_list.concat(ticket_list);
        }

    }

    function updateBreifingStatus(briefing)
    {
        for(var i = 0; i < $scope.models[1].items.length; i++ )
        {
            if( briefing.id == $scope.models[1].items[i].brief_id )
            {
                $scope.models[1].items[i].dis_flag = briefing.discussed_flag;
                break;
            }   
        }    
    }

    $scope.$on('briefing_status', function(event, args){
        var briefing = args.briefing;
        updateBreifingStatus(briefing);
    });

    $scope.$on('briefing_ended', function(event, args){
        $scope.discuss_started = true;
        $scope.toggleDiscuss();    
    });

    $scope.$on('participant_added', function(event, args){
        $scope.participant_list = args.participant_list;
    });


    var left_container = angular.element(document.getElementById('list_ticket'));
    var left_scroll_top = 0; 
    $scope.onScrollUpLeftPanel = function() {   
        left_scroll_top = left_container.scrollTop() - 40;
        left_container.scrollTop(left_scroll_top, 1000);
    }

    $scope.onScrollDownLeftPanel = function() {        
        left_scroll_top = left_container.scrollTop() + 40;        
        left_container.scrollTop(left_scroll_top, 1000);
    }

    var right_container = angular.element(document.getElementById('list_ticket_right'));
    var right_scroll_top = 0; 
    $scope.onScrollUpRightPanel = function() {   
        right_scroll_top = right_container.scrollTop() - 40;
        right_container.scrollTop(right_scroll_top, 1000);
    }

    $scope.onScrollDownRightPanel = function() {        
        right_scroll_top = right_container.scrollTop() + 40;
        right_container.scrollTop(right_scroll_top, 1000);
    }

    left_container.on('scroll', function() {
        console.log('Container scrolled to ', left_container.scrollLeft(), left_container.scrollTop());        
    });
    

    /**
     * dnd-dragging determines what data gets serialized and send to the receiver
     * of the drop. While we usually just send a single object, we send the array
     * of all selected items here.
     */
    $scope.getSelectedItemsIncluding = function(list, item) {
        if( $scope.discuss_started )
            return false;
      item.selected = true;
      return list.items.filter(function(item) { return item.selected; });
    };
    
    $scope.getSelectedItems = function(list, item,loc) {
	    var sel;
	   
        if( $scope.discuss_started )
            return false;
    
        sel = list.items.filter(function(item) { 
            return item.selected; 
        });
     
        if(list.listName=='Source')
        {  
	        item.selected = true;
	        var i=0,count=0;
	        var index=[];
           
         
	        angular.forEach(sel,function(item){
		       index[i++]=list.items.indexOf(item);		       
	        });
	       	 
	        if($scope.onDrop($scope.models[1], sel , index))
	        {		      
		      	angular.forEach(index,function(ind){
    			    if(ind==0)
    			    {
        			    list.items.splice(ind,1);
        			    count++;
    			    }
    			    else if(count!=0){
    			        list.items.splice(ind-count,1); 
    			        count++;
    			    }
    			     else
    			    {
    				    list.items.splice(ind,1);  
    				    count++;				     
    			    }
    			       
    	        });
    		     
    	        $scope.onMoved($scope.models[0]);
	        }

	        $scope.moveflag = 1;	       
	    }	       
	    else if(list.listName=='Target' && (loc=='src'))
        {
	        item.selected = true;
	        var i=0,count=0;
	        var index2=[];
           
	        angular.forEach(sel,function(item){
		       index2[i++]=list.items.indexOf(item);
		       
	        });
			 
	        
	       
		    if($scope.onDrop($scope.models[0], sel , index2))
	        {
		     
	            angular.forEach(index2,function(ind){
                    if(ind==0)
                    {
                        list.items.splice(ind,1);
                        count++;
                    }
                    else if(count!=0){
                        list.items.splice(ind-count,1); 
                        count++;
                    }
                    else
                    {
                        list.items.splice(ind,1);  
                        count++;                            
                    }                      
	            });
	           $scope.onMoved($scope.models[1]);
	        }
	        $scope.moveflag=0;
		       
	    }
	       
	    else if(loc=='trgt1')
        {  
	        if($scope.onDroploc($scope.models[1],'up'))
	        {
		      
	            $scope.onMovedloc($scope.models[1]);
	        }	       
	    }
	    else if(loc=='trgt2')
        { 
            if($scope.onDroploc($scope.models[1],'down'))
	        {
	            $scope.onMovedloc($scope.models[1]);
	        }
	    }       
    };



    /**
     * We set the list into dragging state, meaning the items that are being
     * dragged are hidden. We also use the HTML5 API directly to set a custom
     * image, since otherwise only the one item that the user actually dragged
     * would be shown as drag image.
     */
    $scope.onDragstart = function(list, event) {
        if( $scope.discuss_started )
            return;

       list.dragging = true;
       if (event.dataTransfer.setDragImage) {
         var img = new Image();
         img.src = '/frontpage/img/ic_content_copy_black_24dp_2x.png';
         event.dataTransfer.setDragImage(img, 0, 0);
       }
    };

    /**
     * In the dnd-drop callback, we now have to handle the data array that we
     * sent above. We handle the insertion into the list ourselves. By returning
     * true, the dnd-list directive won't do the insertion itself.
     */
    $scope.onDrop = function(list, items, index) {
        if( $scope.discuss_started )
            return false;
        angular.forEach(items, function(item) { item.selected = false; });
        list.items = list.items.slice(0, index)
                  .concat(items)
                  .concat(list.items.slice(index));
      return true;
    }
    
         $scope.onDroploc = function(list,loc) {
	    
        if( $scope.discuss_started )
            return false;
    
            var sel=list.items.filter(function(item) { return item.selected; });
            var index = list.items.indexOf(sel[sel.length-1]);
            
            if( sel.length>1 )
            {
                toaster.pop('info', MESSAGE_TITLE, 'Please select only one complaint')
                return;
            }
            else if( sel.length==0 )
            {
                toaster.pop('info', MESSAGE_TITLE, 'Please select at least one complaint')
                return;
            }
           
        if((loc=='up')&&((index!=0)&&(sel[0].selected!=false)))
        {
	         angular.forEach(sel, function(item) { item.selected = false; });
	        
	        
	        var prev_index=index-1;
	        list.items.splice(list.items.indexOf(sel[sel.length-1]),1);
	        list.items =list.items.slice(0,prev_index)
	        			.concat(sel)
	        			.concat(list.items.slice(prev_index));


	        			}
	        
        
         else if((loc=='down')&&((index!=(list.items.length-1))&&(sel[0].selected!=false)))
            {
	                 angular.forEach(sel, function(item) { item.selected = false; });
	                  var prev_index=index+1;
	                  list.items.splice(list.items.indexOf(sel[sel.length-1]),1);
	                  list.items = list.items.slice(0, prev_index)
                  .concat(sel)
                  .concat(list.items.slice(prev_index));
                  
                  }
                  else
                  return false;
                  
                  angular.forEach(sel, function(item) { item.selected = true; });
                  
                    
      return true;
    }

    /**
     * Last but not least, we have to remove the previously dragged items in the
     * dnd-moved callback.
     */
    $scope.onMoved = function(list) {
        if( $scope.discuss_started )
            return;
        list.items = list.items.filter(function(item) { return !item.selected; });
    };
    
    $scope.onMovedloc = function(list) {
        if( $scope.discuss_started )
            return;
        //list.items = list.items.filter(function(item) { return !item.selected; });
    };
});

app.controller('ItemSortModal', function($scope, $uibModalInstance, $http, AuthService, toaster, group_by, ticket_list) {
    $scope.group_by = group_by;


    $scope.item_list = { items: [], dragging: false};

    if( group_by == 'Department' )
    {
        angular.forEach(ticket_list, function(item) { 
            angular.forEach(item.dept_list, function(row) {
                var data = {};
                data.id = row.id;
                data.name = row.department;

                var exists_array = $scope.item_list.items.filter(function(row1) {
                    return data.id == row1.id;
                });

                if( !exists_array || exists_array.length < 1 )
                    $scope.item_list.items.push(data);
            });
            angular.forEach(item.selected_ids, function(row) {
                var data = {};
                data.id = row.id;
                data.name = row.department;

                var exists_array = $scope.item_list.items.filter(function(row1) {
                    return data.id == row1.id;
                });

                if( !exists_array || exists_array.length < 1 )
                    $scope.item_list.items.push(data);
            });
        });
    }

    if( group_by == 'Severity' )
    {
        $scope.item_list.items = [
            {id: 1, name: 'Informational'},
            {id: 2, name: 'Minor'},
            {id: 3, name: 'Moderate'},
            {id: 4, name: 'Major'},
            {id: 5, name: 'Serious'},
        ];    
    }

    if( group_by == 'Status' )
    {
        $scope.item_list.items = [
            {id: 1, name: 'Pending'},
            {id: 2, name: 'Acknowledge'},
            {id: 3, name: 'Resolved'},
            {id: 4, name: 'Rejected'},
            {id: 5, name: 'Closed'},
            {id: 6, name: 'Unresolved'},
            {id: 7, name: 'Forwarded'},
            {id: 8, name: 'Escalated'},
            {id: 9, name: 'Unresolved'},
        ]    
    }
    
    $scope.onOrderApply = function() {
        var data = {};
        data.order_by = group_by;
        data.order_list = $scope.item_list.items;
        $uibModalInstance.close(data);
    }   
    
    $scope.cancel = function () {
        $uibModalInstance.dismiss('close');
    };

    $scope.getSelectedItemsIncluding1 = function(list, item) {
        item.selected = true;        
        return list.items.filter(function(item) { return item.selected; });
    };

    $scope.onDragstart1 = function(list, event) {
        list.dragging = true;
        if (event.dataTransfer.setDragImage) {
            var img = new Image();
            img.src = '/frontpage/img/ic_content_copy_black_24dp_2x.png';
            event.dataTransfer.setDragImage(img, 0, 0);
        }
    }

    $scope.onMoved1 = function(list) {        
        list.items = list.items.filter(function(item) { return !item.selected; });        
    };

    $scope.onDrop1 = function(list, items, index) {
        angular.forEach(items, function(item) { item.selected = false; });
        list.items = list.items.slice(0, index)
                  .concat(items)
                  .concat(list.items.slice(index));
          
        return true;
    }
});

