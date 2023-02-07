app.controller('CallaccountReportController', function($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, toaster, liveserver, blockUI) {
    var MESSAGE_TITLE = 'Schedule Report Page';

    $scope.$on('$destroy', function() {
        if (angular.isDefined($scope.checkdownload)) {
            $interval.cancel($scope.checkdownload);
            $scope.checkdownload = undefined;
        }
    });

    var iframe_style1 = {"height": ($window.innerHeight - 450) + 'px'};
    var iframe_style2 = {"height": ($window.innerHeight - 160) + 'px'};

    $scope.data = {isHidden: false};    
    $scope.data.iframe_style =  iframe_style1;
    $scope.onGetFrameHeight = function() {
        if(!$scope.data.isHidden)  $scope.data.iframe_style =  iframe_style1;
        else  $scope.data.iframe_style =  iframe_style2;
    }

    $scope.tableState = undefined;
    $scope.param = '';
    var profile = AuthService.GetCredentials();

    $scope.calltypes = [
        {id: 0, label: 'Internal'},
        {id: 1, label: 'Received'},
        {id: 2, label: 'Local'},
        {id: 3, label: 'Mobile'},
        {id: 4, label: 'National'},
        {id: 5, label: 'International'},
        {id: 6, label: 'Missed'},
        {id: 7, label: 'Received_I'},
        {id: 8, label: 'Toll Free'},
    ];
    $scope.calltypes_hint = {buttonDefaultText: 'Select Call Type'};
    $scope.calltypes_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };

    $scope.call_type = [];
    $scope.building = [];

    $http.get('/frontend/buildsomelist?building_ids=' + profile.building_ids)
        .success(function(response) {
            $scope.buildingdetail = response;
            $scope.buildings = [];
            for(var i = 0; i < $scope.buildingdetail.length ; i++) {
                var build = {id: $scope.buildingdetail[i].id, label: $scope.buildingdetail[i].name};
                $scope.buildings.push(build);
            }

        //    $scope.building = angular.copy($scope.buildings);
        });

    $scope.buildings_hint = {buttonDefaultText: 'Select Building'};
    $scope.buildings_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };

    var permission = $scope.globals.currentUser.permission;
    $scope.extentionsorts = [               
        'Admin Call',
    ];

    for(var i = 0; i < permission.length; i++)
	{
		if( permission[i].name == "app.reports.admincallaccount" ) {
            $scope.extentionsorts = [
               
                'Admin Call',
               
            ];
			break;
        }else
        if( permission[i].name == "app.reports.guestcallaccount" ) {
            $scope.extentionsorts = [
               
                'Guest Call',
            ];
			break;
        }
       else
	if( permission[i].name == "app.reports.allcallaccount" ){
            $scope.extentionsorts = [
                'All',
                'Admin Call',
                'Business Centre',
                'Guest Call',
            ];
			break;
        }
        
	}
	//e
    $scope.call_sort = [$scope.extentionsorts[0]];

    // $scope.call_sort = [];

    $scope.reporttypes = [
        'Detailed',
        'Summary',
    ];
    $scope.report_type = $scope.reporttypes[0];

    $scope.reportby_list = [
        'Property',
        'Call Date',
        'Room',
        'Department',
        'Extension',
        'Destination',
        'Access Code',
        'Called Number',
        'Plain CSV',
        'Hour Status',
        'Frequency'
    ];

    $scope.report_by = $scope.reportby_list[0];

    $scope.call_durations = [
        '=',
        '>',
        '>=',
        '<',
        '<=',
    ]
    $scope.call_duration = $scope.call_durations[0];

    $scope.reportby_year_month_list = [
        'None',
        'Month End',
        'Year End',
    ];

    $scope.report_year_month_by = $scope.reportby_year_month_list[0];

    $scope.callcharge_flag = true;
    $scope.transfer_flag = false;

    // pip
    $scope.isLoading = false;
    $scope.ticketlist = [];

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

    $scope.filter = {};

    $scope.report_tags = [];
    $scope.valid_flag = [true, true, true, true, true, true,true];
    $scope.onChangeReportBy = function () {
        switch($scope.report_by) {
            case 'Property':
                $scope.valid_flag = [true, false, false, false, false, false,false,false];
                break;
            case 'Call Date':
                $scope.valid_flag = [true, true, true, true, true, true, false,false];
                break;
            case 'Room':
                $scope.valid_flag = [true, false, true, false,true, true,false,false];
                break;
            case 'Department':
                $scope.valid_flag = [true, true, false, true,true, true, false,false];
                break;
            case 'Extension':
                $scope.valid_flag = [true, true, false, true,true,true, false,false];
                break;
            case 'Destination':
                $scope.valid_flag = [true, true, true, true, true,true, false,false];
                break;
			case 'Access Code':
                $scope.valid_flag = [true, true, false, false, true,true,true,false];
                break;
            case 'Called Number':
                $scope.valid_flag = [true, false, false, false, false,false,false,true];
                break;
            case 'Plain CSV':
                $scope.valid_flag = [true, false, false, false, false, false,false,false];
                break;
            case 'Hour Status':
                $scope.valid_flag = [true, true, false, true,false, false, false,false];
                break;
            case 'Frequency':
                    $scope.valid_flag = [true, false, false, true,false, false, false,true];
                    break;
        }

        $scope.extension_tags = [];
		$scope.accesscode_tags = [];
        $scope.room_tags = [];
        $scope.calledno_tags = [];
    }

    $scope.onChangeReportBy();

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.loadFilters = function(query, filter_name) {
        var filter = {};

        var profile = AuthService.GetCredentials();

        filter.property_id = profile.property_id;
        filter.filter_name = filter_name;
        filter.filter = query;

        var confirm =  false ;
        if($scope.report_by == 'Call Date' ) confirm = true;
        if($scope.report_by == 'Department' ) confirm = true;
        if($scope.report_by == 'Extension' ) confirm = true;
        if($scope.report_by == 'Destination' ) confirm = true;
        if($scope.report_by == 'Access Code' ) confirm = true;
        if($scope.report_by == 'Called Number' ) confirm = true;
        if($scope.report_by == 'Hour Status' ) confirm = true;
        if($scope.report_by == 'Frequency' ) confirm = true;
        if(confirm == true) {
            if (filter_name == 'Section' || filter_name == 'Extension') {
                var departments = $scope.department_tags;
                if (departments != null && departments.length > 0) {
                    filter.filter_department = generateFilters(departments);
                }
            }
        }

        var param = $httpParamSerializer(filter);
        console.log(param);
        return $http.get('/frontend/report/filterlist?' + param);
    };

    $scope.onChangeRoom = function() {
        console.log($scope.room_tags);
    }
    $scope.dateShort = function(period)
    {
        $scope.date_short = period;
        switch(period){
            case 'Today':       var start_time = moment().format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'Yesterday':   var start_time = moment().subtract(1, 'days').format('DD-MM-YYYY 00:00');
                                var end_time = moment().subtract(1, 'days').format('DD-MM-YYYY 23:59');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'This Week':   var start_time = moment().startOf('week').format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'Last Week':  var start_time = moment().startOf('week').subtract(7, 'days').format('DD-MM-YYYY 00:00');
                                var end_time = moment().startOf('week').subtract(1, 'days').format('DD-MM-YYYY 23:59');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'This Month':  var start_time = moment().startOf('month').format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'Last Month':  var start_time = moment().subtract(1,'months').startOf('month').format('DD-MM-YYYY 00:00');
                                var end_time = moment().startOf('month').subtract(1, 'days').format('DD-MM-YYYY 23:59');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            case 'This Year':   var start_time = moment().startOf('year').format('DD-MM-YYYY 00:00');
                                var end_time = moment().format('DD-MM-YYYY HH:mm');
                                $scope.time_range = start_time + ' - ' + end_time;
                                break;
            default:
            
        }
    }
    function generateFilter() {
        var profile = AuthService.GetCredentials();

        $scope.filter.creator_id = profile.id;
        $scope.filter.timestamp = new Date().getTime();
        $scope.filter.property_id = profile.property_id;
        // $scope.filter.building_id = $scope.building_id;

        console.log($scope.buildings);
        console.log($scope.building);
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
        var calltypelist = [];
        console.log($scope.call_type);
        console.log($scope.calltypes);
        for(var i = 0; i < $scope.call_type.length; i++)
            calltypelist.push($scope.calltypes[$scope.call_type[i].id].label);
        $scope.filter.call_type = JSON.stringify(calltypelist);
        //window.alert($scope.filter.call_type);

        $scope.filter.call_sort = JSON.stringify($scope.call_sort);
        $scope.filter.report_by = $scope.report_by;
        if($scope.report_year_month_by == 'Year End') {
            $scope.start_time = $scope.datetime.time+"-01-01 00:00:00";
            $scope.end_time = $scope.datetime.time+"-12-31 23:59:59";
        }
        if($scope.report_year_month_by == 'Month End') {
            $scope.start_time = $scope.datetime.time+"-01 00:00:00";
            var date = new Date($scope.datetime.time), y = date.getFullYear(), m = date.getMonth();
            var lastDay = new Date(y, m + 1, 0);
            $scope.end_time =lastDay.getFullYear()+"-"+(lastDay.getMonth() + 1)+"-"+lastDay.getDate()+" "+"23:59:59";
        }
        $scope.startTime =  moment($scope.start_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'DD-MM-YYYY HH:mm').format('YYYY-MM-DD HH:mm');
        if(($scope.report_year_month_by == 'Month End') || ($scope.report_year_month_by == 'Year End')){
        $scope.startTime =  moment($scope.start_time, 'YYYY-MM-DD HH:mm').format('YYYY-MM-DD HH:mm');
        $scope.endTime = moment($scope.end_time, 'YYYY-MM-DD HH:mm').format('YYYY-MM-DD HH:mm');
        }
        //window.alert($scope.start_time);
        //window.alert($scope.end_time);
        //window.alert($scope.startTime);
        //window.alert($scope.endTime);
        $scope.filter.start_time = $scope.startTime;
        $scope.filter.end_time = $scope.endTime;
        $scope.filter.call_date = $scope.call_date;
        $scope.filter.callcharge = $scope.callcharge_flag;
        $scope.filter.transfer = $scope.transfer_flag;
        $scope.filter.call_duration = $scope.call_duration;
        $scope.filter.call_duration_time = $scope.call_duration_time;
        
        if( $scope.valid_flag[1] )
            $scope.filter.department_tags = generateFilters($scope.department_tags);
        else
            $scope.filter.department_tags = "[]";

        if( $scope.valid_flag[2] )
            $scope.filter.room_tags = generateFilters($scope.room_tags);
        else
            $scope.filter.room_tags = "[]";

        if( $scope.valid_flag[3] )
            $scope.filter.extension_tags = generateFilters($scope.extension_tags);
        else
            $scope.filter.extension_tags = "[]";

        if( $scope.valid_flag[4] )
            $scope.filter.destination_tags = generateFilters($scope.destination_tags);
        else
            $scope.filter.destination_tags = "[]";

        if( $scope.valid_flag[5] )
            $scope.filter.section_tags = generateFilters($scope.section_tags);
        else
            $scope.filter.section_tags = "[]";
		
		if( $scope.valid_flag[6] )
            $scope.filter.accesscode_tags = generateFilters($scope.accesscode_tags);
        else
            $scope.filter.accesscode_tags = "[]";
        
        if( $scope.valid_flag[7] )
            $scope.filter.calledno_tags = generateFilters($scope.calledno_tags);
        else
            $scope.filter.calledno_tags = "[]";

        return $scope.filter;
    }

    function removeBlockUI(){
        const interval = setInterval(function() {
            //console.log('params::::'+$scope.param);
            //var temp = document.getElementById('');
            //console.log($scope.hasIframe);

          }, 2000);
    }

    $scope.onGenerateReport = function() {
        //alert('test');
        //removeBlockUI();
        blockUI.stop();
        blockUI.start("Please wait while the report is being generated."); 
        
        var filter = generateFilter();
        console.log(JSON.stringify(filter));
        console.log($httpParamSerializer(filter));
        $scope.param = '/frontend/report/callaccount_generatereport?' + $httpParamSerializer(filter);
        $scope.hasIframe = true;
        

    }


    $scope.onShowSchedule = function() {
        var size = 'lg';
        var filter = generateFilter();
        var modalInstance = $uibModal.open({
            templateUrl: 'scheduleReportModal.html',
            controller: 'ScheduleReportController',
            size: size,
            resolve: {
                filter: function () {
                    return filter;
                }
            }
        });

        modalInstance.result.then(function (schedule) {
            if(schedule == undefined)
                return;
            var data = angular.copy(schedule);
            data.report_type = 'callaccount';
            var profile = AuthService.GetCredentials();
            data.property_id = profile.property_id;
            data.submitter = profile.id;
            $http({
                method: 'POST',
                url: '/frontend/report/createschedulereport',
                data: data,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
                .then(function(response) {
                    console.log(response.data);
                    toaster.pop('success', MESSAGE_TITLE, 'Schedule report has been added successfully');
                }).catch(function(response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to add Schedule report');
                })
                .finally(function() {
                });

        }, function () {
           
        });
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

    function getBase64FromImageUrl(url) {
        var img = new Image();

        img.setAttribute('crossOrigin', 'anonymous');

        img.onload = function () {
            var canvas = document.createElement("canvas");
            canvas.width =this.width;
            canvas.height =this.height;

            var ctx = canvas.getContext("2d");
            ctx.drawImage(this, 0, 0);

            var dataURL = canvas.toDataURL("image/png");

            console.log(dataURL);
        };

        img.src = url;
    }

    $scope.onDownloadPDF = function() {
        var filter = generateFilter();
        filter.report_target = 'callaccount';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
        
        $scope.generateDownloadChecker(filter);
    }

    $scope.onDownloadExcel = function(type) {
        var filter = generateFilter();
        filter.excel_type = type;

        $window.location.href = '/frontend/report/callaccount_excelreport?' + $httpParamSerializer(filter);       
        
        $scope.generateDownloadChecker(filter);
    }

    function initTimeRanger() {
        var start_time = moment().format('DD-MM-YYYY 00:00');
        var end_time = moment().format('DD-MM-YYYY HH:mm');

        $scope.dateRangeOption = {
            timePicker: true,
            timePickerIncrement: 5,
            format: 'DD-MM-YYYY HH:mm',
            startDate: start_time,
            endDate: end_time
        };

        $scope.time_range = start_time + ' - ' + end_time;

        getTimeRange();

        $scope.$watch('time_range', function(newValue, oldValue) {
            if( newValue == oldValue )
                return;

            getTimeRange();
        });
    }

    
    function getTimeRange() {
        $scope.start_time = $scope.time_range.substring(0, '01-01-2016 00:00'.length);
        $scope.end_time = $scope.time_range.substring('01-01-2016 00:00 - '.length, '01-01-2016 00:00 - 01-01-2016 00:00'.length);
    }

    initTimeRanger();

    $scope.yearview = false;
    $scope.monthview = false;
    $scope.dateview = true;
    $scope.$watch('datetime.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.datetime.time = moment(newValue).format('YYYY-MM');
    });

    $scope.$watch('datetime.year', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.datetime.time = moment(newValue).format('YYYY');
    });


    $scope.periodView = function(val){
        if($scope.datetime != null) $scope.datetime = "";
        if(val == 'Year End'){
            $scope.yearview = true;
            $scope.monthview = false;
            $scope.dateview = false;
        }
        if(val == 'Month End'){
            $scope.yearview = false;
            $scope.monthview = true;
            $scope.dateview = false;
        }
        if(val == 'None') {
            $scope.yearview = false;
            $scope.monthview = false;
            $scope.dateview = true;
        }
    }


});



//return;
//$rootScope.myPromise = $http.get('/frontend/report/callaccount_pdfreport?' + $httpParamSerializer($scope.filter))
//    .then(function(response) {
//        var data = response.data;
//
//        $window.location.href = data.upload_path;
//
//        return;
//        var docDefinition = {
//            content: [
//                {
//                    columns: [
//                        {
//                            image: 'data:image/png;base64,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',
//                            width: 150
//                        },
//                        {
//                            width: '*',
//                            text: ''
//                        },
//                        {
//                            width: 200,
//                            text: [
//                                'Date Generated : ' + moment().format('YYYY-MM-DD') + '\n\n',
//                                'Property : ' + data.property.name + '\n\n',
//                                'Building : ' + data.building + '\n\n',
//                                'Extension Type : ' + data.extenstion_type,
//                            ],
//                            style: 'desc'
//                        }
//                    ],
//                },
//                {
//                    text: 'Guest Call',
//                    style: 'header',
//                    alignment: 'center'
//                }
//            ],
//            pageSize: 'A4',
//            pageOrientation: 'landscape',
//            pageMargins: [ 40, 60, 40, 60 ],
//            styles: {
//                header: {
//                    fontSize: 13,
//                    bold: true,
//                    margin: [0, 20, 0, 20]
//                },
//                desc: {
//                    fontSize: 10
//                },
//                subheader: {
//                    fontSize: 16,
//                    bold: true,
//                    margin: [0, 10, 0, 5]
//                },
//                tableExample: {
//                    width: '100%',
//                    margin: [0, 5, 0, 15]
//                },
//                tableHeader: {
//                    bold: true,
//                    fillColor: '#c2dbec'
//                },
//                tableSum: {
//                    bold: true,
//                    alignment: 'right'
//                }
//            },
//        };
//
//        for (var key in data.guest_call_list) {
//            if (data.guest_call_list.hasOwnProperty(key)) {
//                var data_group = data.guest_call_list[key];
//
//                var total_carrier = 0;
//                var total_hotel = 0;
//                var total_tax = 0;
//                var total_total = 0;
//
//                var table = {
//                    style: 'tableExample',
//                    table: {
//                        widths: [50, 60, 40, 85, 50, 70, 60, '*', '*', '*', '*' ],
//                        body: [
//                            [
//                                {style: 'tableHeader', text:'Time'},
//                                {style: 'tableHeader', text:'Extension'},
//                                {style: 'tableHeader', text:'Room'},
//                                {style: 'tableHeader', text:'Called No'},
//                                {style: 'tableHeader', text:'Duration'},
//                                {style: 'tableHeader', text:'Call Type'},
//                                {style: 'tableHeader', text:'Destination'},
//                                {style: 'tableHeader', text:'Carrier Charges'},
//                                {style: 'tableHeader', text:'Hotel charges'},
//                                {style: 'tableHeader', text:'Tax'},
//                                {style: 'tableHeader', text:'Total charges'},
//                            ],
//                        ]
//                    }
//                };
//
//                for(var i = 0; i < data_group.length; i++)
//                {
//                    var row = data_group[i];
//                    table.table.body.push(
//                        [
//                            row.start_time,
//                            row.extension + '',
//                            row.room,
//                            row.called_no,
//                            moment.utc(row.duration * 1000).format("HH:mm:ss"),
//                            row.call_type,
//                            row.country ? row.country : '',
//                            row.carrier_charges + '',
//                            row.hotel_charges + '',
//                            row.tax + '',
//                            row.total_charges + '',
//                        ]);
//                    total_carrier += row.carrier_charges;
//                    total_hotel += row.hotel_charges;
//                    total_tax += row.tax;
//                    total_total += row.total_charges;
//                }
//
//                table.table.body.push(
//                    [
//                        '',
//                        '',
//                        '',
//                        '',
//                        '',
//                        '',
//                        {style: 'tableSum', text:'Total'},
//                        {style: 'tableSum', text:parseFloat(Math.round(total_carrier * 100) / 100).toFixed(2) + ''},
//                        {style: 'tableSum', text:parseFloat(Math.round(total_hotel * 100) / 100).toFixed(2) + ''},
//                        {style: 'tableSum', text:parseFloat(Math.round(total_tax * 100) / 100).toFixed(2) + ''},
//                        {style: 'tableSum', text:parseFloat(Math.round(total_total * 100) / 100).toFixed(2) + ''},
//                    ]);
//
//                docDefinition.content.push(data.report_by_guest_call + ' : ' + key);
//                docDefinition.content.push(table);
//            }
//        }
//
//        docDefinition.content.push({
//            text: 'Admin Call',
//            style: 'header',
//            alignment: 'center'
//        });
//
//        for (var key in data.admin_call_list) {
//            if (data.admin_call_list.hasOwnProperty(key)) {
//                var data_group = data.admin_call_list[key];
//
//                var total_carrier = 0;
//
//                var table = {
//                    style: 'tableExample',
//                    table: {
//                        widths: [50, 60, '*', 85, 50, 70, 60, '*' ],
//                        body: [
//                            [
//                                {style: 'tableHeader', text:'Time'},
//                                {style: 'tableHeader', text:'Extension'},
//                                {style: 'tableHeader', text:'User Name'},
//                                {style: 'tableHeader', text:'Called No'},
//                                {style: 'tableHeader', text:'Duration'},
//                                {style: 'tableHeader', text:'Call Type'},
//                                {style: 'tableHeader', text:'Destination'},
//                                {style: 'tableHeader', text:'Carrier Charges'},
//                            ],
//                        ]
//                    }
//                };
//
//                for(var i = 0; i < data_group.length; i++)
//                {
//                    var row = data_group[i];
//                    table.table.body.push(
//                        [
//                            row.start_time,
//                            row.extension,
//                            row.wholename,
//                            row.called_no,
//                            moment.utc(row.duration * 1000).format("HH:mm:ss"),
//                            row.call_type,
//                            row.country ? row.country : '',
//                            row.carrier_charges + '',
//                        ]);
//                    total_carrier += row.carrier_charges;
//                }
//
//                table.table.body.push(
//                    [
//                        '',
//                        '',
//                        '',
//                        '',
//                        '',
//                        '',
//                        {style: 'tableSum', text:'Total'},
//                        {style: 'tableSum', text:total_carrier + ''},
//                    ]);
//
//                //
//                docDefinition.content.push(data.report_by_admin_call + ' : ' + key);
//                docDefinition.content.push(table);
//            }
//        }
//
//        var filename = 'Detail_Report_By_' + data.report_by + '_' + moment().format('MM_DD_YYYY_HH_SS') + '.pdf';
//
//        var pdf = pdfMake.createPdf(docDefinition);
//
//        pdf.download(filename, function() {
//        });
//    });

// var app = angular.module('plunker', []);
//
// app.controller('MainCtrl', function($scope) {
//     $scope.var1 = '07-2013';
// });

