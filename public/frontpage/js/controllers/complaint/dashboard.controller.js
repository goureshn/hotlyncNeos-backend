app.controller('ComplaintDashboardController', function ($scope, $rootScope,  $httpParamSerializer, $window, $http, $interval, $uibModal, $timeout, AuthService, ImageUtilsService, toaster) {
    var MESSAGE_TITLE = 'Validation';

    var graph_data = {};
    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(1,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    $scope.filter = {};

    $scope.filter.category_id = 0;

    var graph_panel_height_not_full = $window.innerHeight - 270;
    var graph_panel_height_full = $window.innerHeight - 128;

    var chart_height = graph_panel_height_not_full / 2;

    $scope.property_department_table_body_style = {
        "height": chart_height + 'px'
    };

    $rootScope.fullmode = false;
    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;
        layoutChartGraph(fullmode);
    }

    function getComplaintCategoryList() {
        $scope.category_list = [];
        var profile = AuthService.GetCredentials();

        var request = {};

        request.dept_id = 0;

        $http({
            method: 'POST',
            url: '/frontend/complaint/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.category_list = response.data;
            var alloption = {id: 0, name : '-- All Category --'};
            $scope.category_list.unshift(alloption);               
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    getComplaintCategoryList();

    $scope.$watch('dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getTicketStatistics();
    });

    $scope.$watch('daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getTicketStatistics();
    });

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
                break;
        }

        var param = $httpParamSerializer($scope.filter);

        $scope.loading = true;
        $http.get('/frontend/complaint/statistics?' + param)
            .then(function(response) {
                console.log(response.data);
                $scope.total = response.data.total;
                graph_data = response.data;
                $scope.showStatistics(graph_data);

            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.getTicketStatistics();

    $scope.dateShort = function(period)
    {
        $scope.date_short = period;
        switch(period){
            case 'Today':       var start_time = moment().subtract(1,'d').format('YYYY-MM-DD');
                                var end_time = moment().format('DD-MM-YYYY ');
                                $scope.daterange = start_time + ' - ' + end_time;
                                break;
            
            case 'Weekly':   var start_time = moment().subtract(7,'d').format('YYYY-MM-DD');
                                var end_time = moment().format('DD-MM-YYYY');
                                $scope.daterange = start_time + ' - ' + end_time;
                                break;
           
            case 'Monthly':  var start_time = moment().subtract(30,'d').format('YYYY-MM-DD');
                                var end_time = moment().format('DD-MM-YYYY');
                                $scope.daterange = start_time + ' - ' + end_time;
                                break;
            
            case 'Yearly':   var start_time = moment().subtract(365,'d').format('YYYY-MM-DD');
                                var end_time = moment().format('DD-MM-YYYY');
                                $scope.daterange = start_time + ' - ' + end_time;
                                break;
            case 'Custom Days':   var start_time = moment().subtract(45,'d').format('YYYY-MM-DD');
                                var end_time = moment().format('DD-MM-YYYY');
                                $scope.daterange = start_time + ' - ' + end_time;
                                break;
            default:            var start_time = moment().subtract(1,'d').format('YYYY-MM-DD');
                                var end_time = moment().format('DD-MM-YYYY ');
                                $scope.daterange = start_time + ' - ' + end_time;
                                break;
        }
    }


    $scope.showStatistics = function(data) {
        showSeverityGraph(data);      
        showStatusGraph(data);  
        showGuestTypeGraph(data);
        showPropertyDepartmentGraph(data);
        showCostGraph(data);
    }
    
    ///start dashboard///////
    //servirety
    $scope.severiy_options = {
        chart: {
            type: 'discreteBarChart',
            height: chart_height,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 30
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,
            valueFormat: function(d){
                return Math.round(d);
            },
            duration: 500,
            xAxis: {
                fontSize: 10
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            }
        },
        title: {
            enable: true,
            text: 'Severity',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showSeverityGraph(data) {

        $scope.serveriy_data = [
            {
                key: "Cumulative Return",
                values: [                    
                ]
            }
        ];

        var severity = data.severity;
        var color = ['#23b7e5','#beb411','#ee900a','#FF5722','#f05050'];

        var count = 0;
        for (var key in severity) {
            if( count > color.length )
                count = color.length - 1;
            if (severity.hasOwnProperty(key)) {
                $scope.serveriy_data[0].values.push( {
                    "label" : key ,
                    "value" : Number(severity[key]),
                    color: color[count]
                });
                count++;
            }
        }
    }

    //status
    $scope.status_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: chart_height,
            margin : {
                top: 0,
                right: 20,
                bottom: 40,
                left: 80
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showControls: false,
            showValues: true,
            showLabels: false,
            duration: 500,
            xAxis: {
                fontSize: 11
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },            
            //legendPosition: 'bottom',
            stacked: true
        },

        title: {
            enable: true,
            text: 'Status',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
    };

    function showStatusGraph(data) {
       

        $scope.status_data = [
          
        ];

        var status = data.status;
        var color = ['#23b7e5','#27c24c'];

        var keys = ['Open', 'Closed'];

        for (var i = 0; i < keys.length; i++) {
            var key = keys[i];
            var row = {};
            row.key = key;
            row.values= [];

            for(var j = 0; j < keys.length; j++)
            {
                if( j == i )
                {
                    row.values.push({
                        "label" : key,
                        "value" : Number(status[key]),
                        color: color[i]
                    });    
                }
                else
                {
                    row.values.push({
                        "label" : key,
                        "value" : 0,
                        color: color[i]
                    });       
                }

            }
            
            $scope.status_data.push(row);
        }
    }

    //Guest_type
    $scope.guest_type_options = {
        chart: {
            type: 'pieChart',
            height: chart_height,
            donut:true,
            x: function(d){return d.key;},
            y: function(d){return Math.round(d.y);},
            showLabels: false,
            duration: 500,
            labelThreshold: 0.01,
            labelSunbeamLayout: true,
            valueFormat: function(d){
                return Math.round(d);
            },
            legend: {
                margin: {
                    top: 5,
                    right: 35,
                    bottom: 5,
                    left: 0
                }
            },
            callback: function(chart) {
                chart.pie.dispatch.on('elementClick', function(e){
                    var data = e.data;
                    showGuestTypeCountry(data);                    
                });
            }
        },
        title: {
            enable: true,
            text: 'Guest Type',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showGuestTypeGraph(data) {
        $scope.guest_type_data = [           
        ];

        var guest_type_list = data.guest_type_list;
        for(var i = 0; i < data.guest_type_list.length; i++)
        {
            $scope.guest_type_data.push({
                key: data.guest_type_list[i], 
                y: data.guest_types['gt' + i]
            });
        }
    }

    

    var dept_names = [];

    //property vs department
    $scope.property_department_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: chart_height,
            margin : {
                top: 5,
                right: 20,
                bottom: 40,
                left: 70
            },
            x: function(d){return d.label;},
            y: function(d){return d.value;},
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function(d){
                    return Math.round(d);
                },
                fontSize: 11
            },
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 5
                },
                fontSize: 7
            },
            legendPosition: 'bottom',
            stacked: true,
            callback: function(chart) {
                chart.multibar.dispatch.on('elementClick', function(e){
                    var data = e.data;
                    showLocationTypeCountForDepartment(data)
                });
            }
        },
        title: {
            enable: true,
            text: 'Department/Status',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
    };

    function showPropertyDepartmentGraph(data) {
        $scope.property_department_data = [];
        $scope.complaint_list = [];

        var color = ['#9C27B0', '#00a14d', '#ffa500', '#FF5722', '#ee6757', '#E91E63', '#1565C0'];
        var label = ['Opened', 'Completed', 'Escalated', 'Re-routing', 'Canceled', 'Timeout', 'Re-opened'];
        var key = ['pending_cnt', 'completed_cnt', 'escalated_cnt', 'reassign_cnt', 'canceled_cnt','timeout_cnt', 'reopen_cnt'];

        for(var k = 0; k < key.length; k++)
        {       
            var graph_data = {};
            var cnt_key = key[k];
            graph_data.key = label[k];
            graph_data.color = color[k];

            var list = [];

            for(var i = 0; i < data.property_list.length; i++ )
            {
                for(var j = 0; j < data.dept_data[i].length; j++)
                {
                    var row = data.dept_data[i][j];
                    list.push({label: row['short_code'], value: Number(row[cnt_key])});                    
                }
            }
            graph_data.values = list;

            $scope.property_department_data.push(graph_data);    
        }

        var max_dept_cnt = 0;
        // department list
        for(var i = 0; i < data.property_list.length; i++ )
        {
            for(var j = 0; j < data.dept_data[i].length; j++)
            {
                var row = {};
                row.property_name = data.property_list[i].name;
                row.department = data.dept_data[i][j].department;
                row.complaint_cnt = data.dept_data[i][j].cnt;
                row.completed_cnt = data.dept_data[i][j].completed_cnt

                $scope.complaint_list.push(row);
            }

            if( data.dept_data[i].length > max_dept_cnt )
                max_dept_cnt = data.dept_data[i].length;
        }
    }

    //compensation
    $scope.compensation_options = {
        chart: {
            type: 'discreteBarChart',
            height: chart_height,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 30
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,            
            valueFormat: function(d){
                return 'AED ' + Math.round(d);
            },
            duration: 500,
            xAxis: {
                fontSize: 8
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
            callback: function(chart) {
                chart.discretebar.dispatch.on('elementClick', function(e){
                    var data = e.data;
                    showLocationTypeCompensationForDepartment(data)
                });
            }
        },
        title: {
            enable: true,
            text: 'Compensation',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },        
    };

    function showCostGraph(data) {

        $scope.compensation_data = [
            {
                key: "Cumulative Return",
                values: [                    
                ]
            }
        ];

        for(var i = 0; i < data.property_list.length; i++ )
        {
            for(var j = 0; j < data.dept_data[i].length; j++)
            {
                $scope.compensation_data[0].values.push({
                    "label": data.dept_data[i][j].short_code,
                    "value": Number(data.dept_data[i][j].sub_total_cost)
                });             
            }
        }
    }

    var chart_view_options = [
                                $scope.severiy_options, 
                                $scope.status_options, 
                                $scope.guest_type_options,
                                $scope.property_department_options,
                                $scope.compensation_options,
                            ];

    $scope.expand_flag = [false, false, false, false, false, false];
    $scope.visible_flag = [true, true, true, true, true, true];
    $scope.table_view_visible = [false, false, false, false, false, false];

    $scope.onExpandDashboard = function(num) {
        $scope.expand_flag[num] = !$scope.expand_flag[num];    
        for(var i = 0; i < $scope.expand_flag.length; i++)
        {
            if( $scope.expand_flag[num] == false )
            {
                $scope.visible_flag[i] = true;    
            }
            else
            {
                $scope.visible_flag[i] = i == num;                
            }       
        }

        layoutChartGraph($rootScope.fullmode);
    }

    function layoutChartGraph(fullmode) {
        if( fullmode == true ) 
            chart_height = graph_panel_height_full / 2;
        else
            chart_height = graph_panel_height_not_full / 2;
        
        for(var i = 0; i < $scope.expand_flag.length; i++)
        {
            if( i == 0 )
                continue;

            if( $scope.expand_flag[i] )
            {
                if( fullmode == false )    
                    chart_view_options[i - 1].chart.height = $window.innerHeight-157;
                else
                    chart_view_options[i - 1].chart.height = $window.innerHeight-110;
            }
            else
            {
                if( fullmode == false )    
                    chart_view_options[i - 1].chart.height = chart_height;
                else
                    chart_view_options[i - 1].chart.height = chart_height + 3;

                $scope.table_view_visible[i] = false;
            }
        }

        $timeout(function() {
            // $scope.getTicketStatistics();
            // $scope.guest_type_api.refresh();
            var chart_api = [
                                $scope.severiy_api, 
                                $scope.status_api, 
                                $scope.guest_type_api,
                                $scope.property_department_api,
                                $scope.compensation_api,
                            ];                        

            for(var i = 0; i < chart_api.length; i++)
            {
                chart_api[i].refresh();
            }
        }, 100);
    }

    $scope.onChangeChart = function(num) {
        $timeout(function() {
            // $scope.getTicketStatistics();
            // $scope.guest_type_api.refresh();
            var chart_api = [
                                $scope.severiy_api, 
                                $scope.status_api, 
                                $scope.guest_type_api,
                                $scope.property_department_api,
                                $scope.compensation_api,
                            ];                        

            chart_api[num - 1].refresh();

        }, 100);
    }

    $scope.severity_callback = function(scope) {
        console.log(scope.svg);
    }

    $scope.onChangeSubcomplaintCategory = function() {
        $scope.getTicketStatistics();
    }

    var exported_file_name = [
        '',
        'Severity',
        'Status',
        'Guest Type',
        'Property vs Department',
        'Compensation',        
    ];

    function generateExportedFileName(num) {        
        var start = '';
        var end = '';
        switch($scope.filter.period)
        {
            case 'Today': 
                start = moment().format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');                
                break;
            case 'Weekly':             
                start = moment().subtract(7, "days").format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');                
                break;
            case 'Monthly':
                start = moment().subtract(30, "days").format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');                
                break;
            case 'Yearly':
                start = moment().subtract(1, "years").format('YYYY-MM-DD');
                end = moment().format('YYYY-MM-DD');                
                break;
            case 'Custom Days':
                start = $scope.filter.start_date;
                end = $scope.filter.end_date;                                
                break;
        }

        var filename = start + '_' + end;

        switch(num) {
            case 4: // Property vs Department
                filename += '_';
                if( $scope.filter.category_id == 0 )
                    filename += 'All Category';
                else
                {
                    for(var i = 0; i < $scope.category_list.length; i++)
                    {
                        if( $scope.filter.category_id == $scope.category_list[i].id)
                        {
                            filename += $scope.category_list[i].name;
                            break;
                        }
                    }
                }
                break;
        }

        return filename;
    }

    $scope.onExportToImage = function(num) {
        var selector = '';
        switch(num)
        {
            case 1:
                selector = '#severity';
                break;                
            case 2:
                selector = '#status';
                break;
            case 3:
                selector = '#guest_type';
                break;                    
            case 4:
                selector = '#property_department';
                break;    
            case 5:
                selector = '#compensation';
                break;                    
        }
        var container = jQuery(selector),
        thesvg = container.find('svg');

        var cssStyleText = '';

        cssStyleText = '.tick line{ stroke: #e5e5e5; opacity: 0.2}';
        cssStyleText += 'svg text{ fill: white;}';
        cssStyleText += 'svg{ background-color: #2a2a2a;}';    // panel background color    
        cssStyleText += '.nvd3 .nv-legend text{ font: normal 10px Arial;}';
        cssStyleText += '.nvd3.nv-pie .nv-slice text{ fill: white !important;}';
        cssStyleText += '.nvd3 .nv-discretebar .nv-groups text, .nvd3 .nv-multibarHorizontal .nv-groups text{ fill: rgba(255,255,255,1);stroke: rgba(255,255,255,0);}';
        cssStyleText += '.status-view:hover{ color:#0b2ef4;}';
        cssStyleText += '.nv-background rect{ fill: rgb(42, 42, 42)}';  // for linechart, default is black, so change it with custom color

        var svgString = ImageUtilsService.getSVGString(thesvg[0], cssStyleText);

        var width = $window.innerWidth - 110;
        if( $rootScope.fullmode )
            width = $window.innerWidth - 60;

        var height = chart_view_options[num - 1].chart.height;

        ImageUtilsService.svgString2Image(exported_file_name[num], svgString, width, height, 'png', save ); // passes Blob and filesize String to the callback

        function save( dataBlob, filesize ){
            var filename = exported_file_name[num];

            filename += generateExportedFileName(num);

            filename += '.png';

            saveAs( dataBlob, filename ); // FileSaver.js function
        }
    }   

    function showGuestTypeCountry(data)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/location_type_count_dialog.html',
            controller: 'GuestTypeCountryDialogCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                data: function () {
                    return data;
                },
                graph_data: function () {
                    return graph_data;
                }
            }
        });

        modalInstance.result.then(function (sub) {
            
        }, function () {

        });
    }

    function showLocationTypeCountForDepartment(data)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/location_type_count_dialog.html',
            controller: 'LocationTypeCountDialogCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                data: function () {
                    return data;
                },
                graph_data: function () {
                    return graph_data;
                }
            }
        });

        modalInstance.result.then(function (sub) {
            
        }, function () {

        });
    }

    function showLocationTypeCompensationForDepartment(data)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/location_type_compensation_dialog.html',
            controller: 'LocationTypeCompensationDialogCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                data: function () {
                    return data;
                },
                graph_data: function () {
                    return graph_data;
                }
            }
        });

        modalInstance.result.then(function (sub) {
            
        }, function () {

        });
    }
});


app.controller('GuestTypeCountryDialogCtrl', function($scope, $http, $uibModalInstance, $uibModal, $window, graph_data, data) {
    console.log(data);

    chart_height = $window.innerHeight / 2;
    //property vs department
    $scope.location_type_options = {
        chart: {
            type: 'multiBarChart',
            height: chart_height,
            margin : {
                top: 5,
                right: 20,
                bottom: 40,
                left: 70
            },
            x: function(d){return d.label;},
            y: function(d){return d.value;},
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function(d){
                    return Math.round(d);
                },
                fontSize: 11
            },
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 5
                },
                fontSize: 7
            },
            legendPosition: 'bottom',
            stacked: true,
            reduceXTicks: false,
            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table><thead><tr><td class=value colspan=3><h1 style="font-size:15px; float: left;font-weight:bold; margin-top:0px; margin-bottom:0px">' + obj.data.country_name + '</h1></td></tr></thead>' +                        
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.key + '</td><td class=value>' + obj.data.y + '</td></tr>' +
                        '</tbody></table>'
                });

                return chart;
            },
        },
        title: {
            enable: true,
            text: 'Top 10 Complaints By Country',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
        subtitle: {
            enable: true,
            text: 'Count/Guest Type',
            css: {
                color: 'white',
                'font-size': 'small',
                // 'margin-top': 6
            }
        },
    };

    $scope.location_type_count_data = [];

    var key = 0;
    graph_data.guest_type_list.forEach(row => {
        var graph_data1 = {};
        var cnt_key = 'gt' + key;
        graph_data1.key = row;

        var list = [];
        graph_data.guest_type_country.forEach(row1 => {
            list.push({label: row1['country_code'], value: Number(row1[cnt_key]), country_name: row1['country_name']});
        });

        graph_data1.values = list;

        $scope.location_type_count_data.push(graph_data1);    
        key++;
    });
});

app.controller('LocationTypeCountDialogCtrl', function($scope, $http, $uibModalInstance, $uibModal, $window, graph_data, data) {
    console.log(data);

    chart_height = $window.innerHeight / 2;
    //property vs department
    $scope.location_type_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: chart_height,
            margin : {
                top: 5,
                right: 20,
                bottom: 40,
                left: 70
            },
            x: function(d){return d.label;},
            y: function(d){return d.value;},
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function(d){
                    return Math.round(d);
                },
                fontSize: 11
            },
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 5
                },
                fontSize: 7
            },
            legendPosition: 'bottom',
            stacked: true,
            callback: function (chart) {
                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table><thead><tr><td class=value colspan=3><h1 style="font-size:15px; float: left;font-weight:bold; margin-top:0px; margin-bottom:0px">' + obj.data.loc_type_name + '</h1></td></tr></thead>' +                        
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.key + '</td><td class=value>' + obj.data.y + '</td></tr>' +
                        '</tbody></table>'
                });

                return chart;
            },
        },
        title: {
            enable: true,
            text: 'Location Type/Status',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
    };

    // find dept location data
    var item = undefined;
    for(var i = 0; i < graph_data.property_list.length; i++ )
    {
        for(var j = 0; j < graph_data.dept_data[i].length; j++)
        {
            var row = graph_data.dept_data[i][j];
            if( row.short_code == data.label )
                item = row;                       
        }
    }

    if( !item )
        return;

    $scope.location_type_options.title.text = item.department + ': Location Type';

    $scope.location_type_count_data = [];

    var color = ['#9C27B0', '#ffa500', '#00a14d', '#FF5722', '#ee6757', '#E91E63', '#1565C0'];
    var label = ['Opened', 'Completed', 'Escalated', 'Re-routing', 'Canceled', 'Timeout', 'Re-opened'];
    var key = ['pending_cnt', 'completed_cnt', 'escalated_cnt', 'reassign_cnt', 'canceled_cnt','timeout_cnt', 'reopen_cnt'];

    for(var k = 0; k < key.length; k++)
    {       
        var graph_data = {};
        var cnt_key = key[k];
        graph_data.key = label[k];
        graph_data.color = color[k];

        var list = [];

        item.loc_type_count_data.forEach(row => {
            if( !row['short_code'] )
            {
                row['short_code'] = 'ULT';
                row['type'] = 'Unknown';
            }
            list.push({label: row['short_code'], value: Number(row[cnt_key]), loc_type_name: row['type']});      
        });

        graph_data.values = list;

        $scope.location_type_count_data.push(graph_data);    
    }

});

app.controller('LocationTypeCompensationDialogCtrl', function($scope, $http, $uibModalInstance, $uibModal, $window, graph_data, data) {
    console.log(data);

    chart_height = $window.innerHeight / 2;

    $scope.compensation_options = {
        chart: {
            type: 'discreteBarChart',
            height: chart_height,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 30
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,            
            valueFormat: function(d){
                return 'AED ' + Math.round(d);
            },
            duration: 500,
            xAxis: {
                fontSize: 8
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
            callback: function (chart) {
                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table>' + 
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.loc_type_name + '</td><td class=value>' + obj.data.value + '</td></tr>' +
                        '</tbody></table>'
                });

                return chart;
            },
        },
        title: {
            enable: true,
            text: 'Compensation',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },        
    };

    // find dept location data
    var item = undefined;
    for(var i = 0; i < graph_data.property_list.length; i++ )
    {
        for(var j = 0; j < graph_data.dept_data[i].length; j++)
        {
            var row = graph_data.dept_data[i][j];
            if( row.short_code == data.label )
                item = row;                       
        }
    }

    if( !item )
        return;

    $scope.compensation_options.title.text = item.department + ': Location Type vs Compensation';    

    var list = [];
    item.loc_type_comp_data.forEach(row => {
        if( !row['short_code'] )
        {
            row['short_code'] = 'ULT';
            row['type'] = 'Unknown';
        }
        list.push({label: row['short_code'], value: Number(row['cost']), loc_type_name: row['type']});                  
    });

    $scope.location_type_comp_data = [
        {
            key: "Cumulative Return",
            values: list
        }
    ];
});
