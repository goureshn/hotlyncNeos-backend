app.controller('MycallClassificationController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService, uiGridConstants) {
    var MESSAGE_TITLE = 'My Task';

    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';

    $scope.$watch('vm.dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getDataList();
    });

    $scope.$watch('vm.daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

         $scope.getDataList();
    });

    $scope.vm.dateFilter = 'Today';
    $scope.filters = {};
    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;



    $scope.tableState = undefined;

    // pip
    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.filter = {};

    $scope.cur_date = new Date();
    $scope.filter.call_date = moment().format('YYYY-MM-DD');
    $scope.filter.total_selected = false;

    $scope.calltypes = [
        'All',
        'Internal',
        'Received',
        'Local',
        'Mobile',
        'National',
        'International',
    ];

    $scope.call_filter = {};
    for( var i = 0; i < $scope.calltypes.length; i++)
        $scope.call_filter[$scope.calltypes[i]] = true;

    $scope.classify_filter_types = [
        'All',
        'Business',
        'Personal',
        'Unclassified',
    ];

    $scope.classify_types = [
        'Business',
        'Personal',
        'Unclassified',
    ];

    $scope.classify_filter = {};

    for( var i = 0; i < $scope.classify_filter_types.length; i++)
        $scope.classify_filter[$scope.classify_filter_types[i]] = true;

    $scope.approve_states = [
        'All',
        'Waiting For Approval',
        'Approved',
        'Returned',
        'Rejected',
        'Closed'
    ];

    $scope.approve_filter_name = '';

    $scope.approve_filter = {};
    for( var i = 0; i < $scope.approve_states.length; i++)
        $scope.approve_filter[$scope.approve_states[i]] = true;

    $scope.filter.search = "";
    $scope.filter.extensions = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 15,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.myDatetimeRange =
    {
        date: {
            from: moment().subtract('month', 1), // start date ( Date object )
            to: moment() // end date ( Date object )
        },
        time: {
            from: 480, // default start time (in minutes)
            to: 1020, // default end time (in minutes)
            step: 15, // step width
            minRange: 15, // min range
            hours24: false // true = 00:00:00 | false = 00:00 am/pm
        },
        "hasDatePickers": true,
        "hasTimeSliders": true
    }
    $scope.myDatetimeLabels =
    {
        date: {
            from: 'Start date',
            to: 'End date'
        }
    }

    $scope.time_range = '';

    $scope.$watch('myDatetimeRange.date.from', function(newValue, oldValue) {
        if (newValue === oldValue)
            return;
        $scope.getTimeRange();
        $scope.getDataList();
    });
    $scope.$watch('myDatetimeRange.date.to', function(newValue, oldValue) {
        if (newValue === oldValue)
            return;
        $scope.getTimeRange();
        $scope.getDataList();
    });
    $scope.$watch('myDatetimeRange.time.from', function(newValue, oldValue) {
        if (newValue === oldValue)
            return;
        $scope.getTimeRange();
        $scope.getDataList();
    });
    $scope.$watch('myDatetimeRange.time.to', function(newValue, oldValue) {
        if (newValue === oldValue)
            return;
        $scope.getTimeRange();
        $scope.getDataList();
    });

    $scope.getTimeRange = function() {
        var start_time = moment($scope.myDatetimeRange.date.from)
            .set({
                'hour' : 0,
                'minute'  : 0,
                'second' : 0
            })
            .add('minute', $scope.myDatetimeRange.time.from)
            .format('YYYY-MM-DD HH:mm:ss');

        var end_time = moment($scope.myDatetimeRange.date.to)
            .set({
                'hour' : 0,
                'minute'  : 0,
                'second' : 0
            })
            .add('minute', $scope.myDatetimeRange.time.to)
            .format('YYYY-MM-DD HH:mm:ss');

        $scope.start_time = start_time;
        $scope.end_time = end_time;
        $scope.time_range = start_time + ' - ' + end_time;
    }

    $scope.getTimeRange();

    getExtensionList();
    getDestinationList();

    $scope.subcount = {};
    $scope.subcount.unmarked = 0;
    $scope.subcount.rejected = 0;
    $scope.subcount.awaiting = 0;
    $scope.subcount.approved = 0;
    $scope.subcount.personal = 0;
    $scope.subcount.business = 0;

    $scope.origin_filter = '';
    $scope.getDataList = function(tableState) {
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

        //////////
        $scope.filters.period = $scope.vm.dateFilter;


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
        //////////

        var request = {};
        if( $scope.origin_filter != filter_name) $scope.paginationOptions.pageNumber = 0;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.search = $scope.filter.search;
        request.start_time = $scope.start_time;
        request.end_time = $scope.end_time;
        request.filter = filter_name;
        $scope.origin_filter = filter_name;
        request.filters = JSON.stringify($scope.filters) ;

        request.call_type = [];
        for (var key in $scope.call_filter) {
            if( $scope.call_filter[key] == true && key != 'All' ) {
                request.call_type.push(key);
            }
        }

        request.extensions = [];
        for(var i = 0; i < $scope.filter.extensions.length; i++) {
            if ($scope.filter.extensions[i].selected == true)
                request.extensions.push($scope.filter.extensions[i].id);
        }

        request.classify = [];
        for (var key in $scope.classify_filter) {
            if( $scope.classify_filter[key] == true && key != 'All' ) {
                request.classify.push(key);
            }
        }

        request.approval = [];
        for (var key in $scope.approve_filter) {
            if( $scope.approve_filter[key] == true && key != 'All' ) {
                request.approval.push(key);
            }
        }

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.agent_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/myadmincall',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.subcount = response.data.subcount;
                if($scope.subcount.unmarked == null) $scope.subcount.unmarked = 0;
                if($scope.subcount.rejected == null) $scope.subcount.rejected = 0;
                if($scope.subcount.awaiting == null) $scope.subcount.awaiting = 0;
                if($scope.subcount.approved == null) $scope.subcount.approved = 0;
                if($scope.subcount.personal == null) $scope.subcount.personal = 0;
                if($scope.subcount.business == null) $scope.subcount.business = 0;
                for(var i = 0; i < $scope.datalist.length; i++) {
                    $scope.datalist[i].classify_temp = $scope.datalist[i].classify + '';
                    $scope.datalist[i].approval_temp = $scope.datalist[i].approval + '';
                    $scope.datalist[i].selected = false;
                }

                $scope.filter.total_selected = false;
                $scope.filter.classify_temp = 'Unclassified';
                $scope.selected_count = 0;

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

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onChangeExtension = function(ext) {
        if( ext.extension == 'All' )
        {
            for( var i = 1; i < $scope.filter.extensions.length; i++)
                $scope.filter.extensions[i].selected = ext.selected;
        }

        $scope.getDataList();
    }

    $scope.onChangeSelected = function() {
        var selected_count = 0;
        for(var i = 0; i < $scope.datalist.length; i++) {
            if( $scope.datalist[i].selected )
                selected_count++;
        }

        $scope.selected_count = selected_count;
    }

    $scope.onChangeTotalSelected = function() {
        for(var i = 0; i < $scope.datalist.length; i++) {
            $scope.datalist[i].selected = $scope.filter.total_selected;
        }

        $scope.onChangeSelected();
    }

    $scope.onChangeClassify = function(row) {
        if( row.classify_temp == 'Business' )
        {
            var size = '';
            var modalInstance = $uibModal.open({
                templateUrl: 'classifyReasonModal.html',
                controller: 'ClassifyReasonController',
                size: size,
                resolve: {
                    call: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (comment) {
                if( comment == undefined || comment.length < 1 )
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Please set reason' );
                    return;
                }

                row.comment = comment;
            }, function () {

            });
        }
    }

    $scope.onChangeTotalClassify = function() {
        var row = {};
        if( $scope.filter.classify_temp == 'Business' )
        {
            var size = '';
            var modalInstance = $uibModal.open({
                templateUrl: 'classifyReasonModal.html',
                controller: 'ClassifyReasonController',
                size: size,
                resolve: {
                    call: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (comment) {
                for(var i = 0; i < $scope.datalist.length; i++) {
                    if( $scope.datalist[i].selected &&
                        $scope.datalist[i].classify == 'Unclassified' &&
                        ($scope.datalist[i].approval == 'Waiting For Approval' || $scope.datalist[i].approval == 'Returned')) {
                        $scope.datalist[i].classify_temp = $scope.filter.classify_temp;
                        $scope.datalist[i].comment = comment + "";
                    }
                }
            }, function () {

            });
        }
        else
        {
            for(var i = 0; i < $scope.datalist.length; i++) {
                if ($scope.datalist[i].selected &&
                    $scope.datalist[i].classify == 'Unclassified' &&
                    ($scope.datalist[i].approval == 'Waiting For Approval' || $scope.datalist[i].approval == 'Returned')) {
                    $scope.datalist[i].classify_temp = $scope.filter.classify_temp;
                    $scope.datalist[i].comment = "";
                }
            }
        }
    }

    $scope.sendApproval = function() {
        var request = {};

        request.calls = [];

        var profile = AuthService.GetCredentials();

        for(var i = 0; i < $scope.datalist.length; i++ )
        {
            var call = $scope.datalist[i];
            if( call.classify == call.classify_temp && call.approval == call.approval_temp )
                continue;

            var data = {};
            data.id = call.id;
            data.submitter = profile.id;
            data.classify = call.classify_temp;
            if( data.classify == 'Business' ) {
                data.comment = call.comment;
                data.approval = 'Waiting For Approval';
            }
            else {
                data.comment = '';
                data.approval = 'Closed';
            }
            request.calls.push(data);
        }

        $http({
            method: 'POST',
            url: '/frontend/callaccount/submitapproval',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getDataList();
                $scope.$emit('refreshCall', '');
            }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    $scope.sendFinance = function() {

    }

    $scope.onClickApproval = function(row) {
        if( row.approval == 'Returned')
        {
            var size = 'lg';
            var modalInstance = $uibModal.open({
                templateUrl: 'returnReplyModal.html',
                controller: 'ReturnReplyController',
                size: size,
                resolve: {
                    call: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (comment) {
                if( comment == undefined || comment.length < 1 )
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Please set reason' );
                    return;
                }

                row.comment = comment;
                row.approval_temp = 'Waiting For Approval';
                //submitComment(row, comment);
            }, function () {

            });
        }
    }

    var filter_name = 'Total';
    $scope.onFilter = function(filter) {
        filter_name = filter;
        $scope.getDataList();
    }

    function submitComment(call, comment) {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.call_id = call.id;
        request.submitter = profile.id;
        request.comment = comment;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/submitcomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
        }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function(duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    function getExtensionList()
    {
        // get extension list
        var request = {};

        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/myextlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.filter.extensions = response.data;
            for(var i = 0; i < $scope.filter.extensions.length; i++)
                $scope.filter.extensions[i].selected = true;

            var all = {};
            all.id = 0;
            all.extension = 'All';
            all.selected = true;
            $scope.filter.extensions.unshift(all);

            console.log(response);
        }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    function getDestinationList()
    {
        // get extension list
        var request = {};

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/destlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.dest_list = response.data;
            for(var i = 0; i < $scope.dest_list.length; i++)
                $scope.dest_list[i].selected = true;

            var all = {};
            all.id = 0;
            all.country = 'All';
            all.selected = true;
            $scope.dest_list.unshift(all);

            console.log(response);
        }).catch(function(response) {

            })
            .finally(function() {

            });
    }

});

app.controller('ClassifyReasonController', function ($scope, $uibModalInstance, toaster, call) {
    $scope.call = call;
    $scope.save = function () {
        $uibModalInstance.close($scope.call.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});

app.controller('ReturnReplyController', function ($scope, $uibModalInstance, $http, call) {
    $scope.call = call;
    $scope.call.comment_content = 'Please input reason';

    $scope.send = function () {
        $uibModalInstance.close($scope.call.comment_content);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function(duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    var request = {};
    request.call_id = call.id;

    $scope.comment_list = [];
    $http({
        method: 'POST',
        url: '/frontend/callaccount/commentlist',
        data: request,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    })
        .then(function(response) {
            $scope.comment_list = response.data;
        }).catch(function(response) {

        })
        .finally(function() {

        });
});

app.controller('CallTypeController', function ($scope) {
    $scope.onChangeCallType = function(type) {
        if( type == 'All' )
        {
            for( var i = 0; i < $scope.calltypes.length; i++)
                $scope.call_filter[$scope.calltypes[i]] = $scope.call_filter['All'];
        }

        $scope.getDataList();

    }
});

app.controller('ClassifyController', function ($scope) {
    $scope.onChangeClassify = function(type) {
        if( type == 'All' )
        {
            for( var i = 0; i < $scope.classify_types.length; i++)
                $scope.classify_filter[$scope.classify_types[i]] = $scope.classify_filter['All'];
        }

        $scope.getDataList();

    }
});

app.controller('ApprovalController', function ($scope) {
    $scope.onChangeApproval = function(type) {
        if( type == 'All' )
        {
            for( var i = 0; i < $scope.approve_states.length; i++)
                $scope.approve_filter[$scope.approve_states[i]] = $scope.approve_filter['All'];
        }

        $scope.getDataList();

    }
});




