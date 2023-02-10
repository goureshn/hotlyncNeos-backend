app.controller('MycallClassificationController', function ($scope, $rootScope, $http, $timeout, $aside, $uibModal, $window, $interval, toaster, AuthService) {
    var MESSAGE_TITLE = 'My Task';

    $scope.$watch('vm.daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

         $scope.getDataList();
    });
    $scope.time_period = [
        'Custom Days'
    ];
    $scope.time_filter = {};
    for (var i = 0; i < $scope.time_period.length; i++) {
        
            if (i == 0) $scope.time_filter = $scope.time_period[i];
        
    }
    $scope.tableState = undefined;

    // pip
    $scope.config = {};
    $scope.config.opened = false;

    

    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.config.opened = true;
    };

    $scope.select = function (date) {
        console.log(date);

        $scope.config.opened = false;
    }    
    $scope.isLoading = false;
    $scope.datalist = [];
    $scope.filter = {};
    var search_option = '';
    var profile = AuthService.GetCredentials();
        var data = {};
        data.setting_group = 'currency' ;
        data.property_id =   profile.property_id;
        $http({
            method: 'POST',
            url: '/backoffice/configuration/wizard/general',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .success(function (data, status, headers, config) {
                $scope.currency = data.currency.currency;
            })
            .error(function (data, status, headers, config) {
                console.log(status);
            });
    $scope.cur_date = new Date();
    $scope.filter.call_date = moment().format('YYYY-MM-DD');
    $scope.filter.total_selected = false;

    $scope.calltypes = [
      //  'All',
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
        //'All',
        'Business',
        'Personal',
        'Unclassified',
       // 'No Classify'
    ];

    $scope.classify_types = [
        'Business',
        'Personal',
        'Unclassified',
       // 'No Classify',
    ];

    $scope.classify_filter = {};

    for( var i = 0; i < $scope.classify_filter_types.length; i++) {
     if(i==2 || i==0)   $scope.classify_filter[$scope.classify_filter_types[i]] = true;
    }

    $scope.approve_states = [
       // 'All',
       // 'No Approval',
        'Unclassified',
        'Waiting For Approval',
        'Approved',
        'Returned',
       // 'Rejected',
        'Pre-Approved',
        'Closed'
    ];

    $scope.approve_filter = {};
    for( var i = 0; i < $scope.approve_states.length; i++){
      if(i==0 || i==1 || i==3)  $scope.approve_filter[$scope.approve_states[i]] = true;
    }


    $scope.filter.search = "";
    $scope.filter.extensions = [];


    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(31,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    }; 
    $scope.start_time = $scope.dateRangeOption.startDate;
    $scope.end_time = $scope.dateRangeOption.endDate;
    $scope.time_range = $scope.start_time + ' - ' + $scope.end_time;
    $scope.daterange2 = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    $scope.daterange = $scope.daterange2;
    // $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    // angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
    //     $scope.daterange = picker.startDate.format('YYYY-MM-DD ') + ' - ' + picker.endDate.format('YYYY-MM-DD');
    //     $scope.start_time =  picker.startDate.format('YYYY-MM-DD HH:mm:ss');
    //     $scope.end_time = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
    //     $scope.time_range = $scope.start_time + ' - ' + $scope.end_time;
    //     $scope.getDataList();
    // });
    $scope.$watch('dateFilter', function (newValue, oldValue) {
        console.log(newValue);
        // window.alert("here2");
        if (newValue == oldValue)
            return;

        //$scope.getTicketStatistics();
    });
    $scope.fetchDateBetn = function (event) {
        // window.alert("here");
        //console.log("Fetch:" + JSON.stringify(event));

        $scope.start_time = event.daterange2.substring(0, '2016-01-01'.length);
        $scope.end_time = event.daterange2.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
       
        var a = moment($scope.start_time);
        var b = moment($scope.end_time);
        //window.alert("here1");
        $scope.during = b.diff(a, 'days');
        if ($scope.during > 94) {
            toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 94 days");
            event.daterange2 = $scope.daterange2;
            $scope.start_time = $scope.dateRangeOption.startDate;
            $scope.end_time = $scope.dateRangeOption.endDate;
            return;
        }
        $scope.dateRangeOption.startDate = $scope.start_time;
        $scope.dateRangeOption.endDate = $scope.end_time;
        $scope.datarange = event.daterange2;
        $scope.daterange2 = event.daterange2;
        $scope.time_range = $scope.daterange2;
    }
    $scope.$watch('daterange2', function (newValue, oldValue) {
        //console.log("aaaa:" + newValue);
        if (newValue == oldValue)
            return;
        //$scope.getTicketStatistics();
    });

    $scope.refreshTickets = function(){
        for( var i = 0; i < $scope.classify_filter_types.length; i++) {
            if(i==3 || i==1)   $scope.classify_filter[$scope.classify_filter_types[i]] = true;
            else $scope.classify_filter[$scope.classify_filter_types[i]] = false;
        }
        for( var i = 0; i < $scope.approve_states.length; i++){
            if(i==1 || i==2 || i==4)   $scope.approve_filter[$scope.approve_states[i]] = true;
           // if(i==5)   $scope.approve_filter[$scope.approve_states[i]] = true;
            else $scope.approve_filter[$scope.approve_states[i]] = false;
        }
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getDataList();
    }
    $scope.onPrevPage = function () {
        if ($scope.paginationOptions.numberOfPages <= 1)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;

        $scope.isLoading = true;
        $scope.getDataList();
    }
    $scope.onNextPage = function () {
        if ($scope.paginationOptions.totalItems < 1)
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if ($scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.isLoading = true;
        $scope.getDataList();
    }

    $scope.onFilterShort = function(val){
        if(val == 'Personal') {
            for( var i = 0; i < $scope.classify_filter_types.length; i++) {
                if(i==2)   $scope.classify_filter[$scope.classify_filter_types[i]] = true;
                else $scope.classify_filter[$scope.classify_filter_types[i]] = false;
            }

            for( var i = 0; i < $scope.approve_states.length; i++){
                  $scope.approve_filter[$scope.approve_states[i]] = true;
            }
        }
        if(val == 'Approved' || val == 'Waiting For Approval' || val == 'Returned' || val == 'Closed') {
            for( var i = 0; i < $scope.approve_states.length; i++){
                if($scope.approve_states[i]==val)   
                    $scope.approve_filter[$scope.approve_states[i]] = true;
                else   
                    $scope.approve_filter[$scope.approve_states[i]] = false;
            }

            for( var i = 0; i < $scope.classify_filter_types.length; i++) {
                 $scope.classify_filter[$scope.classify_filter_types[i]] = true;
            }
        }
        
        if(val == 'Total') {
            for( var i = 0; i < $scope.classify_filter_types.length; i++) {
                 $scope.classify_filter[$scope.classify_filter_types[i]] = true;
            }
            for( var i = 0; i < $scope.approve_states.length; i++){
                  $scope.approve_filter[$scope.approve_states[i]] = true;
            }
        }
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getDataList();
    }

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

   

    // $scope.myDatetimeRange =
    // {
    //     date: {
    //         from: moment().subtract(6, "months"), // start date ( Date object )
    //         to: moment() // end date ( Date object )
    //     },
    //     time: {
    //         from: 0, // default start time (in minutes)
    //         to: 24 * 60, // default end time (in minutes)
    //         step: 15, // step width
    //         minRange: 15, // min range
    //         hours24: false // true = 00:00:00 | false = 00:00 am/pm
    //     },
    //     "hasDatePickers": true,
    //     "hasTimeSliders": true
    // }
    $scope.myDatetimeLabels =
    {
        date: {
            from: 'Start date',
            to: 'End date'
        }
    }

    $scope.time_range = '';

    // $scope.$watch('myDatetimeRange.date.from', function(newValue, oldValue) {
    //     if (newValue === oldValue)
    //         return;
    //     //$scope.getTimeRange();
    //     $scope.getDataList();
    // });
    // $scope.$watch('myDatetimeRange.date.to', function(newValue, oldValue) {
    //     if (newValue === oldValue)
    //         return;
    //    // $scope.getTimeRange();
    //     $scope.getDataList();
    // });
    // $scope.$watch('myDatetimeRange.time.from', function(newValue, oldValue) {
    //     if (newValue === oldValue)
    //         return;
    //     //$scope.getTimeRange();
    //     $scope.getDataList();
    // });
    // $scope.$watch('myDatetimeRange.time.to', function(newValue, oldValue) {
    //     if (newValue === oldValue)
    //         return;
    //    // $scope.getTimeRange();
    //     $scope.getDataList();
    // });

    // $scope.getTimeRange = function() {
    //     var start_time = moment($scope.myDatetimeRange.date.from)
    //         .set({
    //             'hour' : 0,
    //             'minute'  : 0,
    //             'second' : 0
    //         })
    //         .add('minute', $scope.myDatetimeRange.time.from)
    //         .format('YYYY-MM-DD HH:mm:ss');

    //     var end_time = moment($scope.myDatetimeRange.date.to)
    //         .set({
    //             'hour' : 0,
    //             'minute'  : 0,
    //             'second' : 0
    //         })
    //         .add('minute', $scope.myDatetimeRange.time.to)
    //         .format('YYYY-MM-DD HH:mm:ss');

    //     $scope.start_time = start_time;
    //     $scope.end_time = end_time;
    //     $scope.time_range = start_time + ' - ' + end_time;
    // }

   // $scope.getTimeRange();

    getExtensionList();
    getDestinationList();

    $scope.subcount = {};
    $scope.subcount.unmarked = 0;
    $scope.subcount.rejected = 0;
    $scope.subcount.awaiting = 0;
    $scope.subcount.approved = 0;
    $scope.subcount.personal = 0;
    $scope.subcount.business = 0;

    $scope.getDataList = function(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize; 
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }


        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.search = $scope.filter.search;
        request.start_time = $scope.start_time;
        request.end_time = $scope.end_time;
        request.searchoption = search_option;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        request.call_type = [];
        for (var key in $scope.call_filter) {
            if( $scope.call_filter[key] == true && key != 'All' ) {
                request.call_type.push(key);
            }
        }
        if( request.call_type.length < 6) 
            $scope.calltypecolor = '#f2a30a';
        else 
            $scope.calltypecolor = '#fff';

        request.extensions = [];
        for(var i = 0; i < $scope.filter.extensions.length; i++) {
            if ($scope.filter.extensions[i].selected == true)
                request.extensions.push($scope.filter.extensions[i].id);
        }
        if($scope.filter.extensions.length != request.extensions.length )
            $scope.extensioncolor = '#f2a30a';
        else
            $scope.extensioncolor = '#fff';


        request.classify = [];
        for (var key in $scope.classify_filter) {
            if( $scope.classify_filter[key] == true && key != 'All' ) {
                request.classify.push(key);
            }
        }
        if( request.classify.length < 3) 
            $scope.classifycolor = '#f2a30a';
        else 
            $scope.classifycolor = '#fff';

        request.approval = [];
        for (var key in $scope.approve_filter) {
            if( $scope.approve_filter[key] == true && key != 'All' ) {
                if($scope.approve_filter['All'] == true && $scope.approve_filter[key] == true && key == 'No Approval')
                                continue;
                request.approval.push(key);
            }
        }
        if( request.approval.length < 6) 
            $scope.approvalcolor = '#f2a30a';
        else 
            $scope.approvalcolor = '#fff';


        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.agent_id = profile.id;
        request.data_flag = 1;

        $scope.datalist = [];

        // only get data
        $http({
            method: 'POST',
            url: '/frontend/callaccount/myadmincall',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                
                for(var i = 0; i < $scope.datalist.length; i++) {
                    $scope.datalist[i].classify_temp = $scope.datalist[i].classify + '';
                    $scope.datalist[i].approval_temp = $scope.datalist[i].approval + '';
                    $scope.datalist[i].selected = false;
                    if($scope.datalist[i].classify != 'Unclassified') $scope.datalist[i].classifyhide = true;
                    else $scope.datalist[i].classifyhide = false;
                }

                $scope.filter.total_selected = false;
                $scope.filter.classify_temp = 'Unclassified';
                $scope.selected_count = 0;

                console.log(response);
                console.log(response.data.time);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

        // only get count    

        var count_request = angular.copy(request);
        count_request.data_flag = 0;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/myadmincall',
            data: count_request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {               
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

                console.log('count time: ' + response.data.time);                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });    
    };
    $scope.phonebook = function (call) {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/callaccounting/modal/phonebook.html',
            controller: 'PhonebookAdminCtrl',
            windowClass: 'app-modal-window',
            resolve: {
                call: function () {
                    return call;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.selected = [];
    $scope.onChangeClassifyMultiple = function (classify_temp) {
        var selected_arr = [];
        $scope.selected = $scope.datalist.filter(function (item) {
            return item.selected;
        });
        selected_arr = $scope.selected;
        //window.alert(JSON.stringify(selected));

        if (classify_temp == 'Business') {
            var size = '';
            var modalInstance = $uibModal.open({
                templateUrl: 'classifyReasonModal.html',
                controller: 'ClassifyReasonController',
                size: size,
                resolve: {
                    call: function () {
                        return selected_arr;
                    }
                }
            });

            modalInstance.result.then(function (comment) {
                if (comment == undefined || comment.length < 1) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                    return;
                }
                $scope.sendMultipleApproval(comment, selected_arr, classify_temp);

            }, function () {

            });
        } else if (classify_temp == 'Personal') {
            var size = '';
            var modalInstance = $uibModal.open({
                templateUrl: 'classifyPersonalModal.html',
                controller: 'ClassifyAdminPersonalController',
                size: size,
                resolve: {
                    call: function () {
                        return selected_arr;
                    }
                }
            });

            modalInstance.result.then(function (choice) {
                if (choice == 1) {

                     $scope.sendMultipleApproval('', selected_arr, classify_temp);
            //         // });
                 }
            //     // else
            //     //     row.classify_temp = 'Unclassified';
             }, function () {
            //     // row.classify_temp = 'Unclassified';
             });
            //$scope.sendMultipleApproval('', selected_arr, classify_temp);
        }
    }
    $scope.sendMultipleApproval = function (comment, selected, classify) {
        var request = {};
//window.alert(JSON.stringify(selected));
        request.calls = [];
        $scope.CurrentDate = new Date();
        var profile = AuthService.GetCredentials();

        for (var i = 0; i < selected.length; i++) {
            var call = selected[i];
            // if (call.classify == call.classify_temp && call.approval == call.approval_temp)
            //     continue;

            var data = {};
            data.id = call.id;
            data.submitter = profile.id;
            data.classify = classify;
            if (data.classify == 'Business') {
                data.comment = comment;
                data.approval = 'Waiting For Approval';
                data.classify_date = $scope.CurrentDate;
            }
            else {
                data.comment = '';
                data.approval = 'Closed';
                data.classify_date = $scope.CurrentDate;
            }
            request.calls.push(data);
        }
 
        $http({
            method: 'POST',
            url: '/frontend/callaccount/submitapproval',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.getDataList();
                $scope.$emit('refreshCall', '');
            }).catch(function (response) {

            })
            .finally(function () {

            });
    }

    $scope.onChangeClassifyView = function (ticket) {
        ticket.classifyhide = false;
    }
    $scope.onChangeExtension = function(exten) {
        if( exten.extension == 'All' )
        {
            if($scope.filter.extensions[0].selected == false ) {
                for(var i = 1; i < $scope.filter.extensions.length; i++) {
                    $scope.filter.extensions[i].selected = false;
                }
            }else {
                for(var i = 1; i < $scope.filter.extensions.length; i++) {
                    $scope.filter.extensions[i].selected = true;
                }
            }
        }
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
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
                $scope.sendApproval();
            }, function () {

            });
        } else if (row.classify_temp == 'Personal') {
            var size = '';
            var modalInstance = $uibModal.open({
                templateUrl: 'classifyPersonalModal.html',
                controller: 'ClassifyAdminPersonalController',
                size: size,
                resolve: {
                    call: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (choice) {
                if (choice == 1)
                    $scope.sendApproval();
                else
                    row.classify_temp = 'Unclassified';
            }, function () {
                row.classify_temp = 'Unclassified';
            });
           // $scope.sendApproval();
        }
    }

     

    $scope.sendApproval = function() {
        var request = {};

        request.calls = [];
        $scope.CurrentDate = new Date();
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
                data.classify_date = $scope.CurrentDate;
            }
            else {
                data.comment = '';
                data.approval = 'Closed';
                data.classify_date = $scope.CurrentDate;

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
                if( comment.comment_content == undefined || comment.comment_content.length < 1 )
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Please set reason' );
                    return;
                }

                row.comment = comment.comment_content;
                row.classify = comment.classify_temp;
                if(row.classify == 'Business') row.approval = 'Waiting For Approval';
                if(row.classify == 'Personal') row.approval = 'Closed';
                submitComment(row, comment);
            }, function () {

            });
        }
    }

    $scope.onMoreInfo = function (row) {
        var size = '';
        var modalInstance = $uibModal.open({
            templateUrl: 'MoreInfoModal.html',
            controller: 'ReturnReplyController',
            size: size,
            resolve: {
                call: function () {
                    return row;
                }
            }
        });
    }

    function submitComment(call, comment) {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.call_id = call.id;
        request.submitter = profile.id;
        request.comment = comment.comment_content;
        var currentdate = new Date();
        var datetime = currentdate.getFullYear()+"-"+
            (currentdate.getMonth()+1) +"_"+
            currentdate.getDate() + "_"+
            currentdate.getHours() +"_"+
            currentdate.getMinutes() +"_"+
            currentdate.getSeconds()+"_";
        var url =  datetime + Math.floor((Math.random() * 100) + 1);
        var imagetype = comment.imagetype;
        var imagename = comment.imagename;
        if(imagetype != undefined) {
            var extension = imagetype.substr(imagetype.indexOf("/") + 1, imagetype.length);
            request.image_url = url + "." + extension;
            if(comment.src == '') request.image_url = '';
        }
        request.image_src = comment.src;
        request.approval = call.approval;
        request.classify = call.classify;
        
        $http({
            method: 'POST',
            url: '/frontend/callaccount/submitcomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.getDataList();
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
    $scope.openFilterPanel = function (position, backdrop) {
        $scope.config.opened = false;
        $rootScope.asideState = {
            open: true,
            position: position
        };

        function postClose() {
            $rootScope.asideState.open = false;
            // window.alert(JSON.stringify($scope.filter.all_flag));
            // window.alert(JSON.stringify($scope.classify_filter));
            if ($scope.filter.all_flag) {
                for (var i = 0; i < $scope.classify_filter_types.length; i++) {
                    $scope.classify_filter[$scope.classify_filter_types[i]] = true;
                }
                for (var i = 0; i < $scope.calltypes.length; i++) {
                    $scope.call_filter[$scope.calltypes[i]] = true;
                }
                for (var i = 0; i < $scope.approve_states.length; i++) {
                    $scope.approve_filter[$scope.approve_states[i]] = true;
                }
            }
            $scope.paginationOptions.pageNumber = 0;
            $scope.tableState.pagination.start = 0;
            $scope.getDataList();
        }

        $aside.open({
            templateUrl: 'tpl/toolbar/callsfilter.aside.html',
            placement: position,
            scope: $scope,
            size: 'sm',
            backdrop: backdrop,
            controller: function ($scope, $uibModalInstance) {
                $scope.ok = function (e) {
                    $uibModalInstance.close();
                    e.stopPropagation();
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();
                    e.stopPropagation();
                };
                $scope.onChangeradio = function (time) {
                    $scope.time_filter = time;
                }

                $scope.saveTicketFilter = function () {
                    // window.alert(JSON.stringify($scope.filter.all_flag));
                    $uibModalInstance.close();
                }
            },
        }).result.then(postClose, postClose);
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

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getDataList();
    }

});

app.directive('dropzoneclassify', function() {
        return {
            restrict: 'A',
            scope: {
                file: '=',
                fileName: '='
            },
            link: function(scope, element, attrs) {
                var checkSize,
                    isTypeValid,
                    processDragOverOrEnter,
                    validMimeTypes;

                processDragOverOrEnter = function (event) {
                    if (event != null) {
                        event.preventDefault();
                    }
                    event.dataTransfer.effectAllowed = 'copy';
                    return false;
                };

                validMimeTypes = attrs.fileDropzone;

                checkSize = function(size) {
                    var _ref;
                    if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
                        return true;
                    } else {
                        alert("File must be smaller than " + attrs.maxFileSize + " MB");
                        return false;
                    }
                };

                isTypeValid = function(type) {
                    if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
                        return true;
                    } else {
                        alert("Invalid file type.  File must be one of following types " + validMimeTypes);
                        return false;
                    }
                };

                element.bind('dragover', processDragOverOrEnter);
                element.bind('dragenter', processDragOverOrEnter);

                return element.bind('drop', function(event) {
                    var file, name, reader, size, type;
                    if (event != null) {
                        event.preventDefault();
                    }
                    reader = new FileReader();
                    reader.onload = function(evt) {
                        if (checkSize(size) && isTypeValid(type)) {
                            return scope.$apply(function() {
                                scope.file = evt.target.result;
                                if (angular.isString(scope.fileName)) {
                                    return scope.fileName = name;
                                }
                            });
                        }
                    };
                    file = event.dataTransfer.files[0];
                    name = file.name;
                    type = file.type;
                    size = file.size;
                    reader.readAsDataURL(file);
                    return false;
                });
            }
        };
    })


    .directive("filereadclassify", [function () {
        return {
            scope: {
                filereadclassify: "=",
                imagenameclassify: "=",
                imagetypeclassify: "="
            },
            link: function (scope, element, attributes) {
                element.bind("change", function (changeEvent) {
                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.filereadclassify = loadEvent.target.result;
                        });
                    }
                    scope.imagenameclassify = changeEvent.target.files[0].name;
                    scope.imagetypeclassify = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);



app.controller('ClassifyReasonController', function ($scope, $uibModalInstance, toaster, call) {
    $scope.call = call;
    var MESSAGE_TITLE = 'Classify Reason';
    $scope.call.regex = /^[_A-z0-9]*((-|\s)*[_A-z0-9.])*(?:\s*)$/;
    $scope.save = function () {

        if ($scope.call.comment == undefined) {
            toaster.pop('error', MESSAGE_TITLE, 'Please do not use special characters.');
            return;
        }
        if ($scope.call.comment.length < 10) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter more than 10 characters.');
            return;
        }
        $uibModalInstance.close($scope.call.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});

app.controller('ReturnReplyController', function ($scope, $uibModalInstance, $http, call) {
    $scope.call = call;
    $scope.call.src = '';
    $scope.call.imagename = '';
    $scope.call.imagetype = '';
    $scope.call.comment_content = '';

    $scope.send = function () {
        $uibModalInstance.close($scope.call);
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
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
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

        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
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
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getDataList();

    }
});
app.controller('ClassifyAdminPersonalController', function ($scope, $uibModalInstance, $http, AuthService, toaster, call) {
    // $scope.call = call;
    
    var temp, flag = 0;
    for (i = 0; i < call.length; i++) {
        if (!temp)
            temp = call[i].called_no;

        if (temp != call[i].called_no) {
            flag = 1;
            break;
        }
    }
    $scope.multiple = ((call.length > 1) && flag == 1) ? 1 : 0;
    if (((call.length > 1) && flag == 0) || (Array.isArray(call) && flag == 0))
        $scope.call = call[0];
    else if (call)
        $scope.call = call;

    if ($scope.call) {
        $scope.call.contact_no = $scope.call.called_no;
        $scope.call.type = "Personal";
        $scope.call.auto_classify = true;
    }

    // $scope.save = function () {
    //     $uibModalInstance.close($scope.call.comment);
    // };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
    var MESSAGE_TITLE = 'Phone Book';
    $scope.save = function () {
        var request = {};

        var profile = AuthService.GetCredentials();
        request.user_id = profile.id;
        request.contact_name = $scope.call.contact_name;
        request.contact_no = $scope.call.contact_no;
        request.auto_classify = $scope.call.auto_classify ? 1 : 0;
        request.classify_comment = $scope.call.classify_comment;
        request.type = $scope.call.type;
        /*
          if ((preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', request.contact_no)))
          {
              toaster.pop('Success', MESSAGE_TITLE, 'Error');
          }
      */


        $http({
            method: 'POST',
            url: '/frontend/callaccount/addphonebook',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                
                if(response.data.message)
                toaster.pop('error', MESSAGE_TITLE, response.data.message);
                else
                toaster.pop('success', MESSAGE_TITLE, 'Entry has been added successfully');
                //$window.location.reload();
                $scope.cancel();
                console.log(response);

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });


        $uibModalInstance.close(1);
    };
    $scope.ok = function () {
        //     var request = {};

        //     var profile = AuthService.GetCredentials();
        //     request.user_id = profile.id;
        //     request.contact_name = $scope.call.contact_name;
        //     request.contact_no = $scope.call.contact_no;
        //     request.auto_classify = $scope.call.auto_classify ? 1 : 0;
        //     request.classify_comment = $scope.call.classify_comment;
        //     request.type = $scope.call.type;
        //     /*
        //       if ((preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', request.contact_no)))
        //       {
        //           toaster.pop('Success', MESSAGE_TITLE, 'Error');
        //       }
        //   */


        //     $http({
        //         method: 'POST',
        //         url: '/frontend/callaccount/addphonebook',
        //         data: request,
        //         headers: { 'Content-Type': 'application/json; charset=utf-8' }
        //     })
        //         .then(function (response) {

        //             toaster.pop('success', MESSAGE_TITLE, 'Entry has been added successfully');
        //             //$window.location.reload();
        //             $scope.cancel();
        //             console.log(response);

        //         }).catch(function (response) {
        //             console.error('Gists error', response.status, response.data);
        //         })
        //         .finally(function () {
        //             $scope.isLoading = false;
        //         });


        $uibModalInstance.close(1);
    };

    $scope.cancel = function () {
        $uibModalInstance.close(0);
    };

});
app.controller('PhonebookAdminCtrl', function ($scope, $rootScope, $window, call, $uibModalInstance, AuthService, $http, toaster, $interval) {

    var MESSAGE_TITLE = 'Phonebook';
    $scope.call = {};

    $scope.call.contact_name = '';
    $scope.call.contact_no = '';
    $scope.call.auto_classify = 1;
    $scope.call.classify_comment = '';
    $scope.call.type = 'Personal';
    $scope.call.auto_classify = true;
    $scope.text = '';


    var profile = AuthService.GetCredentials();

    $scope.tableState = undefined;


    // pip
    $scope.isLoading = false;
    $scope.datalist = [];


    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.onSearch = function (txt) {
        $scope.searchtext = txt;
        $scope.getHist();
    }
    $scope.filter = function (type) {
        $scope.type = type;
        $scope.getHist();
    }
    $scope.refreshPhonebook = function () {
       $scope.type='';
        $scope.searchtext='';
        
        $scope.getHist();
    }
    $scope.getHist = function getHist(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;


        if (tableState != undefined) {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;
            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
      
        request.searchtext=$scope.searchtext;
        request.type = $scope.type;
        $scope.datalist = [];



        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/phonelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {

                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else

                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if (tableState != undefined)
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });


    };


    $scope.addEntry = function () {
        if(!$scope.call.contact_name || $scope.call.contact_name==' ')
        {
                toaster.pop('error', MESSAGE_TITLE, 'Please enter the contact name.');
                return;
            
        }
        if (!$scope.call.contact_no || $scope.call.contact_no==' ') {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter the contact number');
            return;
        }
        var request = {};

        var profile = AuthService.GetCredentials();
        request.user_id = profile.id;
        request.contact_name = $scope.call.contact_name;
        request.contact_no = $scope.call.contact_no;
        request.auto_classify = $scope.call.auto_classify ? 1 : 0;
        request.classify_comment = $scope.call.classify_comment;
        request.type = $scope.call.type;
        /*
          if ((preg_match('/[\'^£$%&*()}{@#~?><>,|=_+¬-]/', request.contact_no)))
          {
              toaster.pop('Success', MESSAGE_TITLE, 'Error');
          }
      */


        $http({
            method: 'POST',
            url: '/frontend/callaccount/addphonebook',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
               
                if(response.data.message)
                toaster.pop('error', MESSAGE_TITLE, response.data.message);
                else
                toaster.pop('success', MESSAGE_TITLE, 'Entry has been added successfully');
                //$window.location.reload();
                $scope.cancel();
                console.log(response);

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });

    };
    $scope.detailDisplay = false;
    $scope.detailDisplayauto = false;
    $scope.onFieldHidden = function (count, row) {
        if (count == 'Name') {
            row.label = false;
            row.input = true;
        }
        if (count == 'Number') {
            row.label_no = false;
            row.input_no = true;
        }
        if (count == 'Comment') {
            row.label_comment = false;
            row.input_comment = true;
        }
       
    }
    $scope.onKeySave = function (count, row) {
        if (count == 'Name') {
            row.label = true;
            row.input = false;
            SaveField(count, row.id, row.contact_name);
        }
        if (count == 'Number') {
            row.label_no = true;
            row.input_no = false;
            SaveField(count, row.id, row.contact_no);
        }
        if (count == 'Comment') {
            row.label_comment = true;
            row.input_comment = false;
            SaveField(count, row.id, row.classify_comment);
        }
       
    }
    $scope.selectRow = {};
    $scope.onClickType = function(row) {
        $scope.detailDisplay = true ;
        $scope.selectRow = row;
        $scope.call = angular.copy(row);
        $scope.call.type = row.type;
       
        console.log($scope.call);
    }
    $scope.onSaveType = function() {
        $scope.selectRow.type = $scope.call.type;
        SaveField('Type',$scope.call.id ,$scope.call);
      
        $scope.getHist();
    }
    $scope.selectRow1 = {};
    $scope.onClickAuto = function(row) {
        $scope.detailDisplayauto = true ;
        $scope.selectRow1 = row;
        $scope.call = angular.copy(row);
        if (row.auto_classify == 1) $scope.call.auto_classify = true;
        if (row.auto_classify == 0) $scope.call.auto_classify = false;
       
        console.log($scope.call);
    }
    $scope.onSaveAutoclassify = function() {
        $scope.selectRow1.auto_classify = $scope.call.auto_classify;
        SaveField('Classify',$scope.call.id ,$scope.call);
        $scope.getHist();
        $scope.getHist();
    }

    function SaveField(fieldName, rowId, rowValue) {
        var data = {};
        data.id = rowId;
        if (fieldName == 'Name') {
            data.contact_name = rowValue;
        }
        if (fieldName == 'Number') {
            data.contact_no = rowValue;
        }
        if (fieldName == 'Comment') {
            data.classify_comment = rowValue;
        }
        if(fieldName == 'Type') {
            data.type = rowValue.type;
         }
        if(fieldName == 'Classify') {
            if(rowValue.auto_classify == true) data.auto_classify = 1;
            if(rowValue.auto_classify == false) data.auto_classify = 0;
          
         }

        console.log(data);
        $http({
            method: 'POST',
            url: '/frontend/callaccount/updatephonebook',
            data: data,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                //$window.location.reload();
                console.log(response.data);
                toaster.pop('success', MESSAGE_TITLE, fieldName + ' has been updated successfully');

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to edit field.');
            })
            .finally(function () {
            });

    }



    $scope.onDelete = function (row) {
        var data = {};
        data.id = row.id;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/deletephonebook',
            data: data,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                console.log(response.data);
                $scope.getHist();
                toaster.pop('success', MESSAGE_TITLE, 'Entry has been deleted successfully');

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Entry');
            })
            .finally(function () {
            });
    }

    $scope.status = {
        isFirstOpen: true,
        isFirstDisabled: false
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});