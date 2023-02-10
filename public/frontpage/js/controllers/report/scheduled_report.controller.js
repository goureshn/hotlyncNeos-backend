app.controller('ScheduledReportController', function($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver) {
    var MESSAGE_TITLE = 'Schedule Report List Page';
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
    //console.log('start schedule');
    $scope.$on('refresh_list', function (args) {
        $scope.getDataList();
    });

    $scope.format_list = [
        'pdf', 'excel'
    ];

    /* apply report detail from report list*/
    $scope.applyDetail = function applyDetail(row) {
        var frequency = row.frequency;
        var day = row.day;
        var date = row.date;
        var time = row.time;
        var detail = '';
        if(frequency == 'Daily') detail = 'Every '+time;
        if(frequency == 'Hourly') detail = 'Every Hour';
        if(frequency == 'Weekly') detail = 'Every '+day+' at '+time;
        if(frequency == 'Monthly') detail = 'Every '+date+' at '+time ;
        if(frequency == 'Custom Days') detail = 'Every ' + time ;
        return detail;
    }
    $scope.getDataList = function getDataList(tableState) {
        //console.log('start schedule');
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


        var request = {};
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/report/scheduledreportlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                for( var i=0; i < $scope.datalist.length;i++) {
                    $scope.datalist[i].label = true ;
                    $scope.datalist[i].input = false ;
                    $scope.datalist[i].label_rec = true ;
                    $scope.datalist[i].input_rec = false ;
                    if($scope.datalist[i].repeat_flag == 1) $scope.datalist[i].repeat = true;
                    if($scope.datalist[i].repeat_flag == 0) $scope.datalist[i].repeat = false;
                }
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

                //console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    var select_row = {};

    //callaccount var
    $scope.reportby_list = [];
    //callacount var
    $scope.callcharge_flag = true;

    //callaccount var
    $scope.calltypes = [];

    $scope.calltypes_hint = {buttonDefaultText: 'Select Call Type'};
    $scope.calltypes_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.call_type = [];
    //callaccount var
   // $scope.buildings = [];

    $scope.buildings_hint = {buttonDefaultText: 'Select Building'};
    $scope.buildings_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.building = [];
    

    //guest service variable
    $scope.status_tags = [];
    $scope.escalated_flag;
    $scope.status_flag;
    $scope.status_tags_hint = {buttonDefaultText: 'Select Status'};
    $scope.status_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.status = [];

    $scope.category_hint = {buttonDefaultText: 'Select Category'};
    $scope.category_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.category_ids = [];

    //guest service vaiable
    $scope.ticket_type_tags = [];
    $scope.ticket_type_tags_hint = {buttonDefaultText: 'Select Ticket type'};
    $scope.ticket_type_tags_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.ticket_type = [];

    //call center var
    $scope.agentlist = [];
    $scope.agentlist_hint = {buttonDefaultText: 'Select Agents'};
    $scope.agentlist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.agent_ids = [];

    //call center var
    $scope.call_types = [];
    $scope.call_type_ids = [];
    $scope.call_type_hint = {buttonDefaultText: 'Select Call Types'};
    $scope.call_type_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    //call center
    $scope.follow_ups = [];
    $scope.follow_up = 'All';

    $scope.call_durations = [];
    $scope.call_duration = '=';

    $scope.time_in_queues = [];
    $scope.time_in_queue = '=';

    $scope.statuses = [];

    //callaccount var
    $scope.extentionsorts = [];

    //callaccout var
    $scope.reporttypes = [
        'Detailed',
        'Summary',
    ];
    //callclassify
    $scope.stafflist = [];
    var profile = AuthService.GetCredentials();

    $http.get('/frontend/call/stafflist?property_id=' + profile.property_id)
        .then(function(response) {
            for(var i = 0; i < response.data.length; i++) {
                var user = {};
                user.id = response.data[i].id;
                user.label = response.data[i].wholename;
                $scope.stafflist.push(user);
            }
        });

    var email_list = [];
    $http.get('/list/emails?property_id=' + profile.property_id)
        .then(function(response) {
            email_list = response.data.map(function(item) {
                return {'text': item};
            });
        });

    $scope.stafflist_hint = {buttonDefaultText: 'Select User'};
    $scope.stafflist_hint_setting = {
        smartButtonMaxItems: 3,
        scrollable: true,
        scrollableHeight: '250px',
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };
    $scope.staff_ids = [];

    //callaccount var
    $scope.report_tags = [];
    $scope.onChangeVal = function (attr, val) {
        //callaccount
        if(attr == 'report_type')   $scope.report_type = val;
        //callaccount
        if(attr == 'building_id') {
            for(var i =0 ; i < $scope.building_list.length ; i++) {
                if(val == $scope.building_list[i].id)  $scope.building_id = val;
            }
        }
        //callaccount
        if(attr == 'call_sort') {
            $scope.call_sort = [];
            for(var i = 0; i < val.length; i++)
                $scope.call_sort.push(val[i]);
        }
        //callcenter
        if(attr == 'follow_up') $scope.follow_up = val;
        //callcenter
        if(attr == 'status') $scope.status = val;
        //callcenter
        if(attr == 'call_duration') $scope.call_duration = val;
        //callcenter
        if(attr == 'time_in_queue_time') $scope.time_in_queue_time = val;
        //callcenter
        if(attr == 'call_time_start') $scope.call_time_start =  val;
        //callcenter
        if(attr == 'call_time_end')
            $scope.call_time_end =  val;
        //callcenter
        if(attr == 'call_duration_time') $scope.call_duration_time =  val;
        //callcenter
        if(attr == 'time_in_queue_time') $scope.time_in_queue_time =  val;
        //callclassify
        if(attr == 'classify') $scope.classify =  val;
        //callclassify
        if(attr == 'approval') $scope.approval =  val;

    }


    $scope.valid_flag = [true, true, true, true, true, true,true];
    $scope.onChangeReportBy = function (val) {
        $scope.report_by = val;
        switch($scope.report_by) {
            case 'Property':
                $scope.valid_flag = [true, false, false, false, false, false,false,false];
                break;
            case 'Call Date':
                $scope.valid_flag = [true, true, true, true, true, true,false,false];
                break;
            case 'Room':
                $scope.valid_flag = [true, false, true, false, true, true,false,false];
                break;
            case 'Department':
                $scope.valid_flag = [true, true, false, true, true, true,false,false];
                break;
            case 'Extension':
                $scope.valid_flag = [true, true, false, true, true, true,false,false];
                break;
            case 'Destination':
                $scope.valid_flag = [true, true, true, true, true, true,false,false];
                break;
			case 'Access Code':
                $scope.valid_flag =[true, true, false, false, true,true,true,false];
                break;
            case 'Called Number':
                $scope.valid_flag =[true, false, false, false, false,false,false,true];
                break;
        }
    }

    $scope.detailDisplay = false;
    $scope.section = '';
    
    $scope.onCancel = function () {
        $scope.section = '';
    }
    
    
    $scope.report_by = [];    
    $scope.callaccountControl = function(row) {
        select_row = row;
        var filter = JSON.parse(row.filter);
        $scope.reportby_list = [
            'Property',
            'Call Date',
            'Room',
            'Department',
            'Extension',
            'Destination',
            'Access Code',
            'Called Number',
        ];
        $scope.extentionsorts = [
            'All',
            'Admin Call',
            'Business Centre',
            'Guest Call',
        ];
        $scope.calltypes = [
            {id: 0, label: 'Internal'},
            {id: 1, label: 'Received'},
            {id: 2, label: 'Local'},
            {id: 3, label: 'Mobile'},
            {id: 4, label: 'National'},
            {id: 5, label: 'International'},
            {id: 6, label: 'Missed'},
            {id: 7, label: 'Received_I'},
            {id: 7, label: 'Toll Free'},
        ];

        $http.get('/build/list?property_id=' + profile.property_id)
        .success(function(response) {
        $scope.buildingdetail = response;
        $scope.buildings = [];
			for(var i = 0; i < $scope.buildingdetail.length ; i++) {
				var build = {id: $scope.buildingdetail[i].id, label: $scope.buildingdetail[i].name};
				$scope.buildings.push(build);
            }
        });
       
        $scope.property_id = filter.property_id;
      
      // $scope.building_id = JSON.stringify(filter.building_id);
        $scope.report_type = filter.report_type;
        if(filter.call_sort != undefined)
            $scope.call_sort = generateTagSelect(filter.call_sort);
        if(filter.call_type != undefined)
            $scope.call_type = callTypeList( filter.call_type, 'calltypes');

        $scope.report_by = filter.report_by;

        $scope.start_time = filter.start_time;
        $scope.end_time = filter.end_time;
        $scope.callcharge = filter.callcharge;
        $scope.transfer = filter.transfer;
        $scope.call_duration = filter.call_duration;
        $scope.call_duration_time = filter.call_duration_time;

        if(filter.building_id != undefined)
        $scope.building = callTypeList( filter.building_id, 'buildings');
        if(filter.department_tags != undefined)
            $scope.department_tags = generateTagObject(filter.department_tags);
        if(filter.room_tags != undefined)
            $scope.room_tags = generateTagObject(filter.room_tags);
        if(filter.extension_tags != undefined)
            $scope.extension_tags = generateTagObject(filter.extension_tags);
        if(filter.destination_tags != undefined)
            $scope.destination_tags = generateTagObject(filter.destination_tags);
        if(filter.section_tags != undefined)
            $scope.section_tags = generateTagObject(filter.section_tags);
		if(filter.accesscode_tags != undefined)
            $scope.accesscode_tags = generateTagObject(filter.accesscode_tags);
        if(filter.calledno_tags != undefined)
            $scope.calledno_tags = generateTagObject(filter.calledno_tags);

        $scope.onChangeReportBy($scope.report_by);
    }

    $scope.guestserviceControl = function(row) {
        select_row = row;
        var filter = JSON.parse(row.filter);
        $scope.reportby_list = [
            'Date',
            'Status',
            'Department',
            'Ticket Type',
            'Item',
            'Location',
            'Service Category'
        ];
        $scope.status_tags = [
            {id: 0, label: 'Completed'},
            {id: 1, label: 'Open'},
            {id: 2, label: 'Escalated'},
            {id: 3, label: 'Timeout'},
            {id: 4, label: 'Canceled'},
            {id: 5, label: 'Scheduled'},
        ];

        $http.get('/frontend/guestservice/gettaskcategory')
    .success(function(response) {
        $scope.categorydetail = response;
        console.log(response);
        $scope.categorys = [];
        for(var i = 0; i < $scope.categorydetail.length ; i++) {
            var cat = {id: $scope.categorydetail[i].id, label: $scope.categorydetail[i].name};
            $scope.categorys.push(cat);
        }
    
    });

        $scope.ticket_type_tags = [
            {id: 1, label : 'Guest Request'},
            {id: 2, label : 'Department Request'},
            //{id: 3, label : 'Complaints'},
            {id: 4, label : 'Managed Task'},
        ];
      
        $scope.property_id = filter.property_id;
        $scope.report_type = filter.report_type;
        $scope.report_by = filter.report_by;
        $scope.escalated_flag=filter.escalated_flag;
        //$scope.status_flag=filter.status_flag;
        
         
        if(filter.escalated_flag==false)
         $scope.status_flag = false;
        
          else
        {
	        $scope.status_flag = true;
	        //$scope.status = [{id: 2, label: 'Escalated'}];
	        
        }
       //window.alert("Yes "+$scope.status_flag);
       if(filter.category_tags != undefined)
            $scope.category_ids = callTypeList( filter.category_tags, 'category_tags');
        if(filter.status_tags != undefined)
            $scope.status = callTypeList( filter.status_tags, 'status_tags');

        if(filter.ticket_type_tags != undefined)
            $scope.ticket_type = callTypeList( filter.ticket_type_tags, 'ticket_type_tags');

        if(filter.department_tags != undefined)
            $scope.department_tags = generateTagObject(filter.department_tags);

        if(filter.department_function_tags != undefined)
            $scope.department_function_tags = generateTagObject(filter.department_function_tags);

        if(filter.location_tags != undefined)
            $scope.location_tags = generateTagObject(filter.location_tags);

        if(filter.item_tags != undefined)
            $scope.item_tags = generateTagObject(filter.item_tags);

        $scope.chart_graph_flag = filter.chart_graph_flag;


    }

    $scope.callcenterControl = function(row) {
        select_row = row;
        var filter = JSON.parse(row.filter);
        $scope.reportby_list = [
            'Agent',
            'Call Status',
            'Date',
            'Origin',
            'Per Hour',
            'Call Type',
            'Agent Status'
        ];

        $scope.agentlist = [
            {id: 0, label: 'Internal'},
            {id: 1, label: 'Received'},
            {id: 2, label: 'Local'},
            {id: 3, label: 'Mobile'},
            {id: 4, label: 'National'},
            {id: 5, label: 'International'},
        ];

        $scope.call_types = [
            {id: 0, label: 'Booking'},
            {id: 1, label: 'Inquiry'},
            {id: 2, label: 'Other'},
        ];

        $scope.follow_ups = [
            'All',
            'Yes',
            'No',
        ];

        $scope.follow_up = filter.follow_up;

        $scope.call_durations = [
            '=',
            '>',
            '>=',
            '<',
            '<=',
        ]
        $scope.call_duration = filter.call_duration;

        $scope.time_in_queues = [
            '=',
            '>',
            '>=',
            '<',
            '<=',
        ]
        $scope.time_in_queue = filter.time_in_queue;

        $scope.statuses = [
            'All',
            'Answered',
            'Abandoned',
            'Missed',
            'Call Back',
        ];
        $scope.status = filter.status;

        $scope.property_id = filter.property_id;
        $scope.report_type = filter.report_type;
        $scope.report_by = filter.report_by;
        if(filter.agent_tags != undefined)
            $scope.agent_ids = callTypeList( filter.agent_tags, 'agent_tags');
        if(filter.origin_tags != undefined)
            $scope.origin_tags = generateTagObject(filter.origin_tags);
        if(filter.call_type_ids != undefined)
            $scope.call_type_ids = callTypeList( filter.call_type_ids, 'call_type_ids');

        $scope.follow_up = filter.follow_up;
        $scope.status = filter.status;
        $scope.call_time_start = new Date (new Date().toDateString() + ' ' + filter.call_time_start);
        $scope.call_time_end =  new Date (new Date().toDateString() + ' ' + filter.call_time_end);
        $scope.call_duration = filter.call_duration;
        $scope.call_duration_time = filter.call_duration_time;
        $scope.time_in_queue = filter.time_in_queue;
        $scope.time_in_queue_time = filter.time_in_queue_time;
        $scope.chart_graph_flag = filter.chart_graph_flag;
    }

    $scope.callclassifyControl = function(row) {
        select_row = row;
        var filter = JSON.parse(row.filter);
        $scope.reportby_list = [
            'Call Date',
            'Call Status',
            'Department',
            'Destination',
            'Extension',
            'User',
            'Mobile',
        ];

        $scope.calltypes = [
            {id: 0, label: 'Local'},
            {id: 1, label: 'Mobile'},
            {id: 2, label: 'National'},
            {id: 3, label: 'International'},
        ];
        $scope.approvals = [
            'All',
            'Waiting For Approval',
            'Pre-Approved',
            'Approved',
            'Rejected',
            'Returned',
            'Closed',
        ];

        $scope.classifys = [
            'All',
            'Business',
            'Personal',
            'Unclassified',
        ];

        $scope.property_id = filter.property_id;
        $scope.report_type = filter.report_type;
        $scope.report_by = filter.report_by;
         if(filter.approval != undefined)
             $scope.approval = JSON.parse(filter.approval)[0];
        if(filter.call_type != undefined)
            $scope.call_type = callTypeList( filter.call_type, 'call_type');
        if(filter.classify != undefined)
            $scope.classify =  filter.classify;
        if(filter.section_tags != undefined)
            $scope.section_tags = generateTagObject(filter.section_tags);
        if(filter.user_id != undefined)
            $scope.staff_ids = callTypeList( filter.user_id, 'user_id');


    }

    function callTypeList(data, section) {
        var tag=[];
        if(data.length == 0) { return tag; }
        else {
            var list = JSON.parse(data);
            for (var i = 0; i < list.length; i++) {
                var label = list[i];
                var id = -1;
                //callaccount
                if(section == 'calltypes') {
                    for (var j = 0; j < $scope.calltypes.length; j++) {
                        var item = $scope.calltypes[j];
                        if (label == item.label) {
                            id = item.label;
                            break;
                        }
                    }
                }
                if(section == 'buildings') {
                    for (var j = 0; j < $scope.buildings.length; j++) {
                        var item = $scope.buildings[j];
                        if (label == item.label) {
                            id = item.id;
                            break;
                        }
                    }
                }
                //guestservice
                if(section == 'status_tags') {
                    for (var j = 0; j < $scope.status_tags.length; j++) {
                        var item = $scope.status_tags[j];
                        if (label == item.id) {
                            id = item.id;
                            break;
                        }
                    }
                }
                if(section == 'category_tags') {
                    for (var j = 0; j < $scope.categorys.length; j++) {
                        var item = $scope.categorys[j];
                        if (label == item.label) {
                            id = item.id;
                            break;
                        }
                    }
                }
                //guest service
                if(section == 'ticket_type_tags') {
                    for (var j = 0; j < $scope.ticket_type_tags.length; j++) {
                        var item = $scope.ticket_type_tags[j];
                        if (label == item.id) {
                            id = item.id;
                            break;
                        }
                    }
                }
                //callcenter
                if(section == 'agent_tags') {
                    for (var j = 0; j < $scope.agentlist.length; j++) {
                        var item = $scope.agentlist[j];
                        if (label == item.id) {
                            id = item.id;
                            break;
                        }
                    }
                }
                //callcenter
                if(section == 'call_type_ids') {
                    for (var j = 0; j < $scope.call_types.length; j++) {
                        var item = $scope.call_types[j];
                        if (label == item.label) {
                            id = item.id;
                            break;
                        }
                    }
                }

                //callclassify
                if(section == 'call_type') {
                    for (var j = 0; j < $scope.calltypes.length; j++) {
                        var item = $scope.calltypes[j];
                        if (label == item.label) {
                            id = item.id;
                            break;
                        }
                    }
                }

                //callclassify
                if(section == 'user_id') {
                    for (var j = 0; j < $scope.stafflist.length; j++) {
                        var item = $scope.stafflist[j];
                        if (label == item.id) {
                            id = item.id;
                            break;
                        }
                    }
                }

                if (id >= 0) {
                    var ele = {};
                    ele.id = id;
                    tag.push(ele);
                }
            }

            return tag;
        }
    }
    function generateTagObject(data) {
        var tags = [];
        if(data.length == 0) { return tags;}
        else {
            var list = JSON.parse(data);
            for (var i = 0; i < list.length; i++) {
                var ele = {};
                ele.text = list[i];
                tags.push(ele);
            }
            return tags;
        }
    }
    function generateTagSelect(data) {
        var tags = [];
        if( JSON.parse(data).length == 0) { return tags;}
        else {
            var list = JSON.parse(data);
            for (var i = 0; i < list.length; i++) {
                tags.push(list[i]);
            }
            return tags;
        }
    }
    function generateFilters(tags) {
        var report_tags = [];
        if( tags )
        {
            for(var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].text);
        }

        return JSON.stringify(report_tags);
    }


    var profile = AuthService.GetCredentials();
/*
    $http.get('/build/list?property_id=' + profile.property_id)
        .then(function(response) {
            $scope.building_list = response.data;
            var all = {};
            all.id = 0;
            all.name = 'All';
            $scope.building_list.unshift(all);

            $scope.building_id = $scope.building_list[0].id;
        });
*/
   

        $scope.category = [];      
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.loadFilters = function(query, filter_name) {
	    
        var filter = {};

        var profile = AuthService.GetCredentials();

        filter.property_id = profile.property_id;
        filter.filter_name = filter_name;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
    };

    $scope.onChangeRoom = function() {
        console.log($scope.room_tags);
    }

    $scope.filter = {};
    function generateFilter() {
        var profile = AuthService.GetCredentials();
        if($scope.section == 'callaccount') {
            $scope.filter.creator_id = profile.id;
            $scope.filter.timestamp = new Date().getTime();
            $scope.filter.property_id = profile.property_id;
            var buildinglist = [];
            for(var i = 0; i < $scope.building.length; i++){
                for(var j = 0; j < $scope.buildings.length; j++){
                    if($scope.building[i].id == $scope.buildings[j].id){
                             buildinglist.push($scope.buildings[j].label);
                    }
                 }
            }
            $scope.filter.building_id = JSON.stringify(buildinglist);
           // window.alert($scope.filter.building_id);

            $scope.filter.report_type = $scope.report_type;
            $scope.filter.start_time = moment(new Date()).format('YYYY-MM-DD 00:00')
            $scope.filter.end_time = moment(new Date()).format('YYYY-MM-DD HH:mm')
            var calltypelist = [];
            for (var i = 0; i < $scope.call_type.length; i++)
                calltypelist.push($scope.calltypes[$scope.call_type[i].id].label);
            $scope.filter.call_type = JSON.stringify(calltypelist);
            var callsort = [];
            for (var i = 0; i < $scope.call_sort.length; i++)
                callsort.push($scope.call_sort[i]);
            $scope.filter.call_sort = JSON.stringify(callsort);
            $scope.filter.report_by = $scope.report_by;
            $scope.filter.callcharge = $scope.callcharge;
            $scope.filter.transfer = $scope.transfer;
            $scope.filter.call_duration = $scope.call_duration;
            $scope.filter.call_duration_time = $scope.call_duration_time;
            

            if ($scope.valid_flag[1])
                $scope.filter.department_tags = generateFilters($scope.department_tags);
            else
                $scope.filter.department_tags = [];

            if ($scope.valid_flag[2])
                $scope.filter.room_tags = generateFilters($scope.room_tags);
            else
                $scope.filter.room_tags = [];

            if ($scope.valid_flag[3])
                $scope.filter.extension_tags = generateFilters($scope.extension_tags);
            else
                $scope.filter.extension_tags = [];

            if ($scope.valid_flag[4])
                $scope.filter.destination_tags = generateFilters($scope.destination_tags);
            else
                $scope.filter.destination_tags = [];

            if ($scope.valid_flag[5])
                $scope.filter.section_tags = generateFilters($scope.section_tags);
            else
                $scope.filter.section_tags = [];
			if ($scope.valid_flag[6])
                $scope.filter.accesscode_tags = generateFilters($scope.accesscode_tags);
            else
                $scope.filter.accesscode_tags = [];
            if ($scope.valid_flag[7])
                $scope.filter.calledno_tags = generateFilters($scope.calledno_tags);
            else
                $scope.filter.calledno_tags = [];
        }

        if($scope.section == 'guestservice') {
            $scope.filter.creator_id = profile.id;
            $scope.filter.timestamp = new Date().getTime();
            $scope.filter.property_id = profile.property_id;
            $scope.filter.report_type = $scope.report_type;
            $scope.filter.start_time = moment(new Date()).format('YYYY-MM-DD 00:00')
            $scope.filter.end_time = moment(new Date()).format('YYYY-MM-DD HH:mm')
            var category = [];
            for(var i=0;i<$scope.category_ids.length;i++){
                for(var j = 0; j < $scope.categorys.length; j++){
                    if($scope.category_ids[i].id == $scope.categorys[j].id){
                        category.push($scope.categorys[j].label);
                    }
                }
            }
            $scope.filter.category_tags = JSON.stringify(category);
            var statuslist = [];
             if($scope.status_flag == true)
          {
	        $scope.status = [{id: 2, label: 'Escalated'}];
	        
          }
         
            for(var i = 0; i < $scope.status.length; i++)
                statuslist.push($scope.status_tags[$scope.status[i].id].id);
            $scope.filter.status_tags = JSON.stringify(statuslist);

            var ticket_typelist = [];
            for(var i = 0; i < $scope.ticket_type.length; i++)
                ticket_typelist.push($scope.ticket_type[i].id);
            $scope.filter.ticket_type_tags = JSON.stringify(ticket_typelist);

            $scope.filter.report_by = $scope.report_by;

            $scope.filter.location_tags = JSON.stringify($scope.location_tags);
            $scope.filter.item_tags = generateFilters($scope.item_tags);
            $scope.filter.department_tags = generateFilters($scope.department_tags);
            $scope.filter.department_function_tags = generateFilters($scope.department_function_tags);
            $scope.filter.id= $scope.profile.id;
            $scope.filter.escalated_flag = $scope.escalated_flag;
            $scope.filter.chart_graph_flag = $scope.chart_graph_flag;
        }

        if($scope.section == 'callcenter') {
            $scope.filter.creator_id = profile.id;
            $scope.filter.timestamp = new Date().getTime();
            $scope.filter.property_id = profile.property_id;
            $scope.filter.report_type = $scope.report_type;
            $scope.filter.start_time = moment(new Date()).format('YYYY-MM-DD 00:00')
            $scope.filter.end_time = moment(new Date()).format('YYYY-MM-DD HH:mm')
            var agentlist = [];
            for(var i = 0; i < $scope.agent_ids.length; i++)
                agentlist.push($scope.agent_ids[i].id);
            $scope.filter.agent_tags = JSON.stringify(agentlist);
            $scope.filter.report_by = $scope.report_by;

            var origin_tags = [];
            if($scope.origin_tags != null) {
                for (var i = 0; i < $scope.origin_tags.length; i++)
                    origin_tags.push($scope.origin_tags[i].text);
            }
            $scope.filter.origin_tags = JSON.stringify(origin_tags);

            var call_type_ids = [];
            for(var i=0;i<$scope.call_type_ids.length;i++)
                call_type_ids.push($scope.call_types[$scope.call_type_ids[i].id].label);
            $scope.filter.call_type_ids = JSON.stringify(call_type_ids);

            $scope.filter.follow_up = $scope.follow_up;
            $scope.filter.status = $scope.status;
            $scope.filter.call_time_start = moment($scope.call_time_start).format('HH:mm:ss');
            $scope.filter.call_time_end = moment($scope.call_time_end).format('HH:mm:ss');
            $scope.filter.call_duration = $scope.call_duration;
            $scope.filter.call_duration_time = $scope.call_duration_time;
            $scope.filter.time_in_queue = $scope.time_in_queue;
            $scope.filter.time_in_queue_time = $scope.time_in_queue_time;

            $scope.filter.chart_graph_flag = $scope.chart_graph_flag;
        }

        if($scope.section == 'callclassify') {
            $scope.filter.creator_id = profile.id;
            $scope.filter.timestamp = new Date().getTime();
            $scope.filter.property_id = profile.property_id;
            $scope.filter.report_type = $scope.report_type;
            $scope.filter.start_time = moment(new Date()).format('YYYY-MM-DD 00:00')
            $scope.filter.end_time = moment(new Date()).format('YYYY-MM-DD HH:mm')
            var calltypelist = [];
            for(var i = 0; i < $scope.call_type.length; i++)
                calltypelist.push($scope.calltypes[$scope.call_type[i].id].label);
            $scope.filter.call_type = JSON.stringify(calltypelist);
            $scope.filter.classify = $scope.classify;

            var approvals =[];
            if($scope.approval != 'All')  approvals.push($scope.approval);

            if($scope.classify == 'Unclassified') approvals = [];

            $scope.filter.approval = JSON.stringify(approvals);


            var stafflist = [];
            for(var i = 0; i < $scope.staff_ids.length; i++)
                stafflist.push($scope.staff_ids[i].id);
            $scope.filter.user_id = JSON.stringify(stafflist);
            $scope.filter.report_by = $scope.report_by;
            $scope.filter.start_time = $scope.start_time;
            $scope.filter.end_time = $scope.end_time;

            if( $scope.valid_flag[0] )
                $scope.filter.section_tags = generateFilters($scope.section_tags);
            else
                $scope.filter.section_tags = [];

        }
        return $scope.filter;
    }

    $scope.onGenerateReport = function() {
        var filter = generateFilter();
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/callaccount_generatereport?' + $httpParamSerializer(filter);
    }

    $scope.onSave = function() {
            var data = {};
            data.id = select_row.id ;
            data.filter =  JSON.stringify(generateFilter());
            console.log(data);
            $http({
                method: 'POST',
                url: '/frontend/report/updatechedulereport',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    console.log(response.data);
                    $scope.getDataList();
                    toaster.pop('success', MESSAGE_TITLE, 'Schedule report has been updated successfully');

                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Shedule report');
                })
                .finally(function() {
                });

    }

    $scope.onDelete = function(row){
            var data = {};
            data.id = row.id ;
            $http({
                method: 'POST',
                url: '/frontend/report/deleteschedulereport',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    console.log(response.data);
                    $scope.getDataList();
                    toaster.pop('success', MESSAGE_TITLE, 'Schedule report has been deleted successfully');

                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Shedule report');
                })
                .finally(function() {
                });
    }
    /* hidden and display , update field of row in schedule report list*/
    $scope.onFieldHidden = function(count, row) {
        if(count == 'name') {
            row.label = false;
            row.input = true;
        }
        if(count == 'recipient') {
            row.label_rec = false;
            row.input_rec = true;
        }
    }
    $scope.onKeySave = function(count, row) {
        if(count == 'name') {
            row.label = true;
            row.input = false;
            SaveField(count,row.id ,row.name);
        }
        if(count == 'recipient') {
            row.label_rec = true;
            row.input_rec = false;
            SaveField(count,row.id ,row.recipient);
        }
    }

    function SaveField(fieldName, rowId , rowValue) {
        var data = {};
        data.id = rowId ;

        if(fieldName != 'detail') {
            data[fieldName] = rowValue;
        }
        else
        {
            data.frequency = rowValue.frequency;    
            data.interval = rowValue.interval;    
            data.freq_unit = rowValue.freq_unit;    
            data.day = rowValue.sel_day;
            data.date = moment(rowValue.date).format("YYYY-MM-DD");
            data.time = moment(rowValue.time).format('HH:mm:ss');
            if(rowValue.repeat == true) data.repeat_flag = 1;
            if(rowValue.repeat == false) data.repeat_flag = 0;
        }

        console.log(data);
        $http({
            method: 'POST',
            url: '/frontend/report/updatechedulereport',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response.data);
                toaster.pop('success', MESSAGE_TITLE, fieldName+' has been updated successfully');

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to edit field.');
            })
            .finally(function() {
            });

    }

    $scope.frequency_units = [
        'Days',
        'Weeks',
        'Months',
        'Years'
    ];   

    /* day, date, update*/
    $scope.selectRow = {};
    $scope.onClickSchedule = function(row) {
        $scope.detailDisplay = true ;
        $scope.selectRow = row;
        $scope.schedule = angular.copy(row);
        $scope.schedule.frequency = row.frequency;
        $scope.schedule.sel_day = row.day;
        $scope.schedule.date = new Date(row.date);
        $scope.schedule.time = moment(row.date + ' ' + row.time).toDate();
        $scope.schedule.start_time = moment(row.date + ' ' + row.start_time).toDate();
        $scope.schedule.end_time = moment(row.date + ' ' + row.end_time).toDate();
        $scope.schedule.interval = row.interval;
        $scope.schedule.freq_unit = row.freq_unit;
        
        console.log($scope.schedule);
    }

    $scope.onClickRecipient = function(row) {
        $scope.detailDisplay = true ;
        $scope.selectRow = row;
        $scope.schedule = angular.copy(row);
        row.recipient_flag = true;
        if( row.recipient )
            $scope.schedule.recipient = row.recipient.split(";").map(function(item) {
                return {"text": item};
            });
        else
            $scope.schedule.recipient = [];

        console.log($scope.schedule);
    }

    $scope.days = [
        "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    ];

    //$scope.schedule.sel_day = $scope.days[0];
    
    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.open = function($event) {
        $scope.schedule.opened = true;
    };

    $scope.onSaveSchedule = function() {
        $scope.selectRow.frequency = $scope.schedule.frequency;        
        // $scope.applyDetail($scope.schedule);
        SaveField('detail',$scope.schedule.id ,$scope.schedule);
        $scope.schedule.isopen = false;

        $scope.getDataList();
    }

    $scope.onCancelSchedule = function() {
        $scope.schedule.isopen = false;
    }

    $scope.emailFilter = function(query) {
        return email_list.filter(function(item) {
            return item.text.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    

    $scope.onSaveRecipient = function() {
        $scope.selectRow.recipient = $scope.schedule.recipient.map(function(item) {
            return item.text;
        }).join(";");
        // $scope.applyDetail($scope.schedule);
        SaveField('recipient',$scope.schedule.id , $scope.selectRow.recipient);
        $scope.schedule.isopen = false;
        $scope.schedule.recipient_flag = false;

        $scope.getDataList();
    }

    $scope.onCancelRecipient = function() {
        $scope.selectRow.recipient_flag = false;
    }

    $scope.onChangeFormat = function(row) {
        SaveField('format', row.id , row.format);
    }

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };

    //guest service vairables
    $scope.department_tags = [];
    
     $scope.loadFiltersValues = function(query, val) {
	    // window.alert("This is"+val);
    if(val == true)
	    {$scope.escalated_flag = true;
		    $scope.status_flag = true;}
	    else
	     {
		$scope.escalated_flag = false;
	    $scope.status_flag = false;
		}
	    
	    $scope.loadFiltersValue(query,'0');
	    };
	    
    $scope.loadFiltersValue = function(query, value) {

	    
        var filter = {};

        var profile = AuthService.GetCredentials();
        filter.property_id = profile.property_id;
        filter.filter_name = value;
        filter.filter = query;

        if(value == 'Department Function') {
            filter.filter_department = generateFilters($scope.department_tags);
        }
        var param = $httpParamSerializer(filter);
        return $http.get('/frontend/report/filterlist?' + param);

    };

});
app.directive('ngDropdownMultiselectDisabled', function() {
  return {
    restrict: 'A',
    controller: function($scope, $element, $attrs) {
      var $btn;
      $btn = $element.find('button');
      return $scope.$watch($attrs.ngDropdownMultiselectDisabled, function(newVal) {
        return $btn.attr('disabled', newVal);
      });
    }
  };
});

