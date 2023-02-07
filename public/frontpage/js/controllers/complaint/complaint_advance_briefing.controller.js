app.controller('ComplaintAdvanceBriefingController', function ($scope, $rootScope, $http, $window, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, $aside, liveserver) {
    var MESSAGE_TITLE = 'Advance Briefing List';
   
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 100;
    $scope.tab_height = $window.innerHeight - 125;
    $scope.ticket_select_mode = 0;

    $scope.filter_value = '';
    $scope.property_ids = [];

    var profile = AuthService.GetCredentials();

    $scope.agent_id = profile.id;

    $scope.isLoading = false;
    $scope.datalist = [];

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

    $scope.filter_list = [
            'All Tickets',
            'Last 24 Hours',
            'Tickets created by me',
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


    $scope.config = {};
    $scope.config.opened = false;    

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

    $scope.filter = undefined;
    function setFilterPanel(filter) {
        $scope.filter = filter;        
    }

    $scope.openFilterPanel = function(position, backdrop) {
        $scope.config.opened = false;
        $rootScope.asideState = {
            open: true,
            position: position
        };

        function postClose() {
            $rootScope.asideState.open = false;
            $scope.pageChanged();
        }

        $aside.open({
            templateUrl: 'tpl/toolbar/complaintfilter.aside.html',
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

                $scope.saveTicketFilter = function() {                    
                    $uibModalInstance.close();
                }        
            },
        }).result.then(postClose, postClose);
    }
    
    $scope.searchBriefing = function(value) {
        $scope.pageChanged();
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
        request.join_flag = 0;

        request.filter = $scope.filter;
        if( request.filter )
            request.filter.departure_date = moment(request.filter.departure_date).format('YYYY-MM-DD');
        
        request.filter_value = $scope.filter_value;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $scope.datalist = [];

        $http({
            method: 'POST',
            url: '/frontend/complaint/briefingroomlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.datalist.forEach(function(item, index) {                    
                    item.created_at_time = moment(item.created_at).format('D MMM YYYY hh:mm a');
                    item.discuss_end_time_at = moment(item.discuss_end_time).format('DD MMM YYYY');
                    if( item.free_join_flag == 1 )
                        item.attendees = 'Free Join';
                    else
                        item.attendees = item.participant_count + '/' + item.attendant_list.length;

                    if( item.status == 'Scheduled' )
                        item.status_text = 'Scheduled at ' + moment(item.start_at).format('D MMM HH:mm');
                    else
                        item.status_text = item.status;

                });

                $scope.paginationOptions.totalItems = response.data.totalcount;
                $scope.filter_value = '';

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
 
                $scope.paginationOptions.countOfPages = numberOfPages;

                setFilterPanel(response.data.filter);

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.pageChanged();

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

    $scope.refreshLogs = function(){
        $scope.isLoading = true;
        $scope.pageChanged();
    }

    $scope.calcDuration = function(row) {
        var duration = '00:00:00';
        switch(row.status) {
            case 'Waiting':
                break;
            case 'Active':
                duration = moment() - moment(row.start_at, "YYYY-MM-DD HH:mm:ss") + 0;
                duration = moment.utc(duration).format('HH:mm:ss');
                break;
            case 'Cancelled':
                break;    
            case 'Ended':
                duration = moment(row.end_at, "YYYY-MM-DD HH:mm:ss") - moment(row.start_at, "YYYY-MM-DD HH:mm:ss") + 0;
                duration = moment.utc(duration).format('HH:mm:ss');
                break;
            case 'Scheduled':                
                break;            
        }

        return duration;
    }

    $scope.$on('update_briefing_room', function(event, args){
        $scope.pageChanged();
    });


    $scope.onCreateRoom = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_advance_briefing_create.html',
            controller: 'ComplaintAdvanceBriefingCreateController',
            scope: $scope,
            resolve: {
                         
            }
        });

        modalInstance.result.then(function (briefing_room) {
            createRoom(briefing_room);
        }, function () {

        });
    }

    function createRoom(data) {
        $http({
            method: 'POST',
            url: '/frontend/complaint/createbriefingroom',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {    
                var briefing_room = response.data.data;                
                $scope.pageChanged();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

});

app.controller('ComplaintAdvanceBriefingCreateController', function ($scope, AuthService, $http, $uibModalInstance, toaster) {
    var MESSAGE_TITLE = 'Advance Briefing Create';
    
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;   

    var attendent_list = [];


    function initBriefingData() {
        $scope.attendent_list = attendent_list;
        $scope.selected_list = [];
        $scope.attendent = {};
        $scope.briefing = {};

        $scope.briefing.free_join_flag = 1;
        $scope.briefing.schedule_flag = false;
        $scope.briefing.start_at_date = moment().format('YYYY-MM-DD');
        $scope.briefing.start_at_time = moment().format('HH:mm');

        $scope.briefing.email_link_flag = false;
    }

    initBriefingData();

    function getAttendentList() {
        var request = {};

        request.client_id = client_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/attendantlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {    
                attendent_list = response.data;                           
                $scope.attendent_list = attendent_list;                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }   

    getAttendentList(); 

    $scope.onUserSelect = function ($item, $model, $label) {
        $scope.attendent = angular.copy($item);
    };

    function filterAttendantList() {
        $scope.attendent_list = attendent_list.filter(function(item, index, arr) {
            var exist = false;
            for(var i = 0; i < $scope.selected_list.length; i++)
            {
                if( item.id == $scope.selected_list[i].id )
                {
                    exist = true;
                    break;
                }
            }

            return !exist;
        });
    }

    $scope.addAttendee = function() {
        if( !($scope.attendent.id > 0) )
            return;

        $scope.selected_list.push($scope.attendent);
        $scope.attendent = {};      
        filterAttendantList();
    }

    $scope.onDeleteUser = function(row) {
        $scope.selected_list = $scope.selected_list.filter(function(item, index, arr) {
            if( item.id == row.id )
                return false;
            return true;
        });

        filterAttendantList();
    }

    $scope.createRoom = function() {
        var request = {};

        request.client_id = client_id;
        request.property_id = profile.property_id;
        request.free_join_flag = $scope.briefing.free_join_flag;
        request.schedule_flag = $scope.briefing.schedule_flag;
        request.start_at = $scope.briefing.start_at_date + ' '  + $scope.briefing.start_at_time + ':00';
        request.email_link_flag = $scope.briefing.email_link_flag;
        request.attendant_list = [];
        $scope.selected_list.forEach(function(item, index) {
            request.attendant_list.push(item.id);
        });

        if( request.free_join_flag == false && request.attendant_list.length < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select attendant');
            return;
        }

        $uibModalInstance.close(request);        
    }

    $scope.cancelRoom = function() {
        $uibModalInstance.dismiss();
    }
});



