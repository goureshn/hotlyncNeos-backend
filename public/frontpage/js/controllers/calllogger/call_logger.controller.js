app.controller('LoggerController', function ($scope, $rootScope, $http, $httpParamSerializer, $timeout, $uibModal, $window, AuthService, toaster) {
    var MESSAGE_TITLE = 'Call Logger';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 180) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.grid_height = 'height: ' + ($window.innerHeight - 224) + 'px; overflow-y: auto';

    var search_option = '';
    var profile = AuthService.GetCredentials();

    $scope.durationlist = [
        'All',
        '=',
        '>',
        '>=',
        '<',
        '<=',
    ]
    $scope.call_duration = $scope.durationlist[0];
    $scope.call_duration_time = 0;
    $scope.tta = $scope.durationlist[0];
    $scope.tta_time = 0;

    var filterlist = [];
    var all = {};
    all.id = 0;
    all.label = 'All';

    $http.get('/frontend/call/agentlist?property_id=' + profile.property_id)
        .then(function(response) {
            $scope.agentlist = response.data;
            $scope.agentlist.unshift(all);

            $scope.agent_filter = {};
            for( var i = 0; i < $scope.agentlist.length; i++) {
                filterlist[i] = $scope.agentlist[i].id;
                $scope.agent_filter[$scope.agentlist[i].id] = true;
            }
        });

    $http.get('/frontend/call/typelist?property_id=' + profile.property_id)
        .then(function(response) {
            $scope.typelist = response.data;
            for( var i = 0; i < $scope.typelist.length; i++) {
                $scope.typelist[i].id = i+1;
            }
            $scope.typelist.unshift(all);

            $scope.type_filter = {};
            for( var i = 0; i < $scope.typelist.length; i++)
                $scope.type_filter[$scope.typelist[i].id] = true;
        });
        
        $http.get('/frontend/call/channellist?property_id=' + profile.property_id)
        .then(function(response) {
            $scope.channellist = response.data;
            for( var i = 0; i < $scope.channellist.length; i++) {
                $scope.channellist[i].id = i+1;
            }
            $scope.channellist.unshift(all);

            $scope.channel_filter = {};
            for( var i = 0; i < $scope.channellist.length; i++)
                $scope.channel_filter[$scope.channellist[i].id] = true;
                
               
        });
    $scope.isLoading = false;
    $scope.datalist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.showStatistics = function(data) {
        $scope.total_queue_count = data.total_queue_count;
        $scope.total_answered_count = data.total_answered_count;
        $scope.total_abandoned_count = data.total_abandoned_count;
        $scope.total_count = data.total_count;
        $scope.total_callback_count = data.total_callback_count;
        $scope.total_missed_count = data.total_missed_count;
        $scope.total_follow_count = data.total_follow_count;
        $scope.total_outgoing_count = data.total_outgoing_count;
        $scope.total_dropped_count = data.total_dropped_count;
        $scope.agentdatalist = data.agent_list;
    }

    $scope.$watch('dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getDataList();
    });

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getDataList();
    });

    $scope.$on('$destroy', function() {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.filter = 'Answered';
    $scope.onFilter = function getFilter(param) {
        $scope.filter =param;
        $scope.getDataList();
    }

    //var intiDatalist = [];
    $scope.dateFilter = 'Today';
    $scope.filters = {};
    $scope.filters.filtername = 'agent';
    $scope.filters.filtervalue = filterlist;

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        /////////////////////
        var profile = AuthService.GetCredentials();
        $scope.filters.property_id = profile.property_id;
        $scope.filters.period = $scope.dateFilter;
        

        switch($scope.filters.period)
        {
            case 'Today':
                break;
            case 'Weekly':
                $scope.filters.during = 7;
                $scope.filters.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Monthly':
                $scope.filters.during = 30;
                $scope.filters.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                $scope.filters.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Custom Days':
                $scope.filters.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
                $scope.filters.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
                $scope.filters.during = b.diff(a, 'days');

                if( $scope.filters.during > 45 )
                {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 45 days");
                    return;
                }
                break;
        }

        ///////////////

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter = $scope.filter;
        request.filters = JSON.stringify($scope.filters) ;
        request.searchoption = search_option;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dept_id = profile.dept_id;

        $http({
            method: 'POST',
            url: '/frontend/call/logs',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                $scope.showStatistics(response.data);

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getDataList();
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
        $scope.getDataList();
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.refreshLogs = function(){
        $scope.getDataList(null);
    }

    $scope.selectedLogs = []; 
    $scope.onSelectLog = function(log){
        console.log(log);
        // check select log
        var index = -1;
        for(var i = 0; i < $scope.selectedLogs.length; i++)
        {
            if( log.id == $scope.selectedLogs[i].id )
            {
                index = i;
                break;
            }
        }

        if( index < 0 )    // not selected
        {
            $scope.selectedLogs.push(angular.copy(log));
        }
        else {
            $scope.selectedLogs.splice(index, 1);
        }

        $timeout(function(){
            if( index < 0 )
                $scope.active = log.id;
        }, 100);
    }

    $scope.removeSelectLog = function(item, $index) {
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

            $scope.selectedLogs.splice($index, 1);
        }, 100);
    }

    $scope.getRowCss = function(row) {
        if( row.active )
            return 'active';
        else
            return '';
    }

    $scope.getLogNumber = function(log){
        if( log == undefined )
            return 0;

        return sprintf('%05d', log.id);
    }

    $scope.getDurationInMinute = function(duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    $scope.$on('call_event', function(event, args) {
        var profile = AuthService.GetCredentials();
        if(args.user_id != profile.id )
            return;

        console.log(args);
        $scope.getDataList();
    });

    $scope.onChangeAgentlist = function(agent) {
        if( agent.label == 'All' ) {
            for( var i = 0; i < $scope.agentlist.length; i++) {
                $scope.agent_filter[$scope.agentlist[i].id] = $scope.agent_filter[0];
            }
        }
        filterlist = [];
        var j = 0;
        for( var i = 0; i < $scope.agentlist.length; i++) {
           if($scope.agent_filter[$scope.agentlist[i].id] == true) {
               if($scope.agentlist[i].label !='All'||$scope.agentlist[i].label != null){
                   filterlist[j] = $scope.agentlist[i].id;
                   j++;
               }
           }
        }
        $scope.filters.filtername = 'agent';
        $scope.filters.filtervalue = filterlist ;
        $scope.getDataList();
    }

    $scope.onChangeTypelist = function(type) {
        if( type.label == 'All' ) {
            for( var i = 0; i < $scope.typelist.length; i++) {
                $scope.type_filter[$scope.typelist[i].id] = $scope.type_filter[0];
            }
        }
        filterlist = [];
        var j = 0;
        for( var i = 0; i < $scope.typelist.length; i++) {
            if($scope.type_filter[$scope.typelist[i].id] == true) {
                if ($scope.agentlist[i].label != 'All' || $scope.typelist[i].label != null) {
                    filterlist[j] = $scope.typelist[i].label;
                    j++;
                }
            }
        }
        $scope.filters.filtername = 'type';
        $scope.filters.filtervalue = filterlist ;
        $scope.getDataList();
    }

    $scope.onChangeChannellist = function(channel) {
        if( channel.label == 'All' ) {
            for( var i = 0; i < $scope.channellist.length; i++) {
                $scope.channel_filter[$scope.channellist[i].id] = $scope.channel_filter[0];
            }
        }
        filterlist = [];
        var j = 0;
        for( var i = 0; i < $scope.channellist.length; i++) {
            if($scope.channel_filter[$scope.channellist[i].id] == true) {
                if ($scope.agentlist[i].label != 'All' || $scope.channellist[i].label != null) {
                    filterlist[j] = $scope.channellist[i].label;
                    j++;
                }
            }
        }
        $scope.filters.filtername = 'channel';
        $scope.filters.filtervalue = filterlist ;
        $scope.getDataList();
    }

    $scope.onChangeDurationlist = function(keyEvent, cond, cond_time) {
        if (keyEvent.which != 13) return;
        var condition = cond;
        var condition_time = cond_time;
        var j = 0;
        filterlist = [];
        filterlist[0] =condition;
        filterlist[1] =condition_time;
        $scope.filters.filtername = 'duration';
        $scope.filters.filtervalue = filterlist ;
        $scope.getDataList();
    }

    $scope.onChangeTTAlist = function(keyEvent, cond, cond_time) {
        if (keyEvent.which != 13) return;
        var condition = cond;
        var condition_time = cond_time;
        var agentlist = [] ;
        var j = 0;
        filterlist = [];
        filterlist[0] =condition;
        filterlist[1] =condition_time;
        $scope.filters.filtername = 'tta';
        $scope.filters.filtervalue = filterlist ;
        $scope.getDataList();
    }
    /*
    function getTimetoSec(time) {
        var times = time.split(':');
        var sectime = (+times[0]) * 60 * 60 + (+times[1]) * 60 + (+times[2]);
        return sectime;
    }*/
    /*
    function setfilter(category){
        var agentlist = [];
        var j = 0 ;
        for(var i=0; i < intiDatalist.length ;i++) {
           if(category == 'agent') {
               if (isName(intiDatalist[i].wholename) == true) {
                   agentlist[j] = intiDatalist[i];
                   j++;
               }
           }
           if(category == 'type') {
               if (isName(intiDatalist[i].type) == true) {
                   agentlist[j] = intiDatalist[i];
                   j++;
               }
           }
        }
        $scope.datalist = agentlist ;
    }

    function isName(name) {
        var value = false;
           for(var i= 0; i <filterlist.length; i++) {
               if(filterlist[i] == name) {
                   value = true ;
                   break;
               }
           }
        return value;
    } */
    
    /////////////////////////




    $scope.getTicketStatistics = function() {
        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.dateFilter;

        switch($scope.filter.period)
        {
            case 'Today':
                break;
            case 'Weekly':
                $scope.filter.during = 7;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Monthly':
                $scope.filter.during = 30;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Custom Days':
                $scope.filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
                $scope.filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
                $scope.filter.during = b.diff(a, 'days');

                if( $scope.filter.during > 45 )
                {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 45 days");
                    return;
                }
                break;
        }

        var param = $httpParamSerializer($scope.filter);

        $scope.loading = true;
        $http.get('/frontend/call/statistics?' + param)
            .then(function(response) {
                console.log(response.data);
                switch($scope.filter.period)
                {
                    case 'Today':
                        $scope.showStatistics(response.data);
                        break;
                    case 'Weekly':
                    case 'Monthly':
                    case 'Yearly':
                    case 'Custom Days':
                        $scope.showStatistics(response.data);
                        break;
                }

                //$scope.avg_time_answer = [{ data: average_time, label: 'Gouresh', points: { show: true, radius: 6}, lines: { show: true, tension: 0.45, lineWidth: 3, fill: 0 } }];

            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.getDataList();
    }

});
