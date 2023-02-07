app.controller('MytaskDashboardController', function ($scope, $rootScope,  $httpParamSerializer, $window, $http, $interval, $uibModal, $timeout, AuthService, ImageUtilsService, toaster) {
    var MESSAGE_TITLE = 'Validation';

    var graph_data = {};

    $scope.complaint = {};
  
    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    $scope.filter = {};

    $scope.filter.category_id = 0;

    var chart_height = ($window.innerHeight - 155) / 2;

    $scope.category_table_body_style = {
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
        $scope.filter.dept_id = profile.dept_id;

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
        $http.get('/frontend/complaint/mystatistics?' + param)
            .then(function(response) {
                console.log(response.data);
                graph_data = response.data;
                $scope.showStatistics(response.data);

            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.getTicketStatistics();

    $scope.showStatistics = function(data) {
        showCategoryGraph(data);        
        showSeverityTypeGraph(data);
        showResolvedGraph(data);
    }
    
    $scope.resolve_options = {
        chart: {
            type: 'discreteBarChart',
            height: chart_height,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 60
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,
            valueFormat: function(d){                
                return moment("2015-01-01").startOf('day')
                    .seconds(d)
                    .format('HH:mm:ss');
            },
            duration: 500,
            xAxis: {
                fontSize: 10
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return moment("2015-01-01").startOf('day')
                        .seconds(d)
                        .format('HH:mm:ss');
                }
            },
            callback: function(chart) {
                chart.discretebar.dispatch.on('elementClick', function(e){
                    var data = e.data;
                    showLocationTypeResolveForDepartment(data)
                });

                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table>' + 
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.dept_name + '</td></tr>' +
                        '</tbody></table>'
                });
            }
        },
        title: {
            enable: true,
            text: 'Average Closure by Department',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showResolvedGraph(data) {

        $scope.resolve_data = [
            {
                key: "Cumulative Return",
                values: [                    
                ]
            }
        ];

        var dept_resolve_time = data.dept_resolve_time;
        dept_resolve_time.forEach(item => {
            var value = 0;
            if( Number(item.cnt) > 0 )
                value = Math.round(Number(item.total_time) / Number(item.cnt));
            $scope.resolve_data[0].values.push( {
                "label" : item.short_code ,
                "value" : value,     
                "dept_name" : item.department,                
            });            
        });
    }

    // Severity_type
    /*
    $scope.severity_type_options = {
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
            }
        },
        title: {
            enable: true,
            text: 'Severity Type',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showSeverityTypeGraph(data) {
        $scope.severity_type_data = [           
        ];

        var severity_type = data.severity_type;

        for (var key in severity_type) {
            if (severity_type.hasOwnProperty(key)) {
                $scope.severity_type_data.push({
                    key: key, 
                    y: severity_type[key]
                });
            }
        }
    }
*/

$scope.severiy_options = {
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
                showLocationTypeSeverityCountForDepartment(data)
            });
        }
    },
    title: {
        enable: true,
        text: 'Severity/department',
        css: {
            color: 'white',
            'font-size': 'medium',
            'margin-top': 6
        }
    },
};

function showSeverityTypeGraph(data) {
    $scope.serveriy_data = [];

    data.severity_name_list.forEach(row => {
        var graph_data = {};
        var cnt_key = row.type;
        graph_data.key = row.type;

        var list = [];
        data.subcomplaint_severity.forEach(item => {
            list.push({label: item['short_code'], value: Number(item[cnt_key])});
        });

        graph_data.values = list;

        $scope.serveriy_data.push(graph_data);    
    });
}

    //servirety
    $scope.category_options = {
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
            callback: function(chart) {
                chart.multibar.dispatch.on('elementClick', function(e){
                    var data = e.data;
                    showLocationTypeCountForDepartment(data)
                });

                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table><thead><tr><td class=value colspan=3><h1 style="font-size:15px; float: left;font-weight:bold; margin-top:0px; margin-bottom:0px">' + obj.data.loc_type_name + '</h1></td></tr></thead>' +                        
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.key + '</td><td class=value>' + obj.data.y + '</td></tr>' +
                        '</tbody></table>'
                });

                return chart;

            }
        },
        title: {
            enable: true,
            text: 'Department/Category',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
    };

    function showCategoryGraph(data) {
        $scope.category_data = [];

        var category_list = data.category_list;
        
        category_list.forEach(item => {
            var graph_data = {};

            var cnt_key = item.category_name;
            graph_data.key = item.category_name;      

            var list = [];

            data.dept_category_list.forEach(row => {
                list.push({label: row.short_code, value: Number(row[cnt_key])});  
                if( !row['short_code'] )
                {
                    row['short_code'] = 'ULT';
                    row['department'] = 'Unknown';
                }
    
                list.push({label: row['short_code'], value: Number(row[cnt_key]), loc_type_name: row['department']});
          
           
            });

            graph_data.values = list;

            $scope.category_data.push(graph_data);    
        });
    }

    var chart_view_options = [
                                $scope.category_options,                                
                                $scope.severity_type_options,
                                $scope.resolve_options, 
                            ];

    var table_view_style = [
                                $scope.category_table_body_style
                            ];                        

    $scope.expand_flag = [false, false, false, false];
    $scope.visible_flag = [true, true, true, true];
    $scope.table_view_visible = [false, false, false, false];

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
            chart_height = ($window.innerHeight-120) / 2;
        else
            chart_height = ($window.innerHeight - 155) / 2;
        
        for(var i = 0; i < $scope.expand_flag.length; i++)
        {
            if( i == 0 )
                continue;

            if( $scope.expand_flag[i] )
            {
                if( fullmode == false )    
                    chart_view_options[i - 1].chart.height = $window.innerHeight-152;
                else
                    chart_view_options[i - 1].chart.height = $window.innerHeight-116;
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

        for(var i = 0; i < table_view_style.length; i++)
        {           
             if( fullmode == false )    
                table_view_style[i].height = $window.innerHeight-138;
            else
                table_view_style[i].height = $window.innerHeight-102;            
        }

        $timeout(function() {
             var chart_api = [
                                $scope.category_api,                                
                                $scope.severiy_api,
                                $scope.resolve_api,
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
                $scope.category_api,                                
                $scope.severiy_api,
                $scope.resolve_api,
            ];                        

            chart_api[num - 1].refresh();

        }, 100);
    }

    $scope.onChangeSubcomplaintCategory = function() {
        $scope.getTicketStatistics();
    }

    var exported_file_name = [
        'Department-Category',
        'Severity',
        'Average Resolved',        
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
                selector = '#category';
                break;                
            case 2:
                selector = '#severity_type';
                break;
            case 3:
                selector = '#resolve_time';
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
    
    function showLocationTypeSeverityCountForDepartment(data)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/location_type_count_dialog.html',
            controller: 'LocationTypeSeverityCountDialogCtrl',
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
            controller: 'LocationTypeCountDialogCtrl1',
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

    function showLocationTypeResolveForDepartment(data)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/location_type_resolve_dialog.html',
            controller: 'LocationTypeResolveDialogCtrl',
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

app.controller('LocationTypeCountDialogCtrl1', function($scope, $http, $uibModalInstance, $uibModal, $window, graph_data, data, $timeout) {
    console.log(data);

    chart_height = $window.innerHeight / 2;

    $scope.sub_category_view = false;

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
            callback: function(chart) {
                chart.multibar.dispatch.on('elementClick', function(e){
                    var data = e.data;                    
                    showSubcategoryByLocType(data);
                });

                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table><thead><tr><td class=value colspan=3><h1 style="font-size:15px; float: left;font-weight:bold; margin-top:0px; margin-bottom:0px">' + obj.data.loc_type_name + '</h1></td></tr></thead>' +                        
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.key + '</td><td class=value>' + obj.data.y + '</td></tr>' +
                        '</tbody></table>'
                });

            }
           
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
    var loc_data = undefined;
    for(var i = 0; i < graph_data.dept_category_list.length; i++ )
    {
        var row = graph_data.dept_category_list[i];
        if( row.short_code == data.label )
            loc_data = row;           
    }

    if( !loc_data )
        return;

    $scope.location_type_options.title.text = loc_data.department + ': Location Type / Category';

    $scope.location_type_count_data = [];

    var category_list = graph_data.category_list;
        
    category_list.forEach(item => {
        var graph_data1 = {};

        var cnt_key = item.category_name;
        graph_data1.key = item.category_name;      

        var list = [];

        loc_data.loc_category_list.forEach(row => {
            list.push({label: row.short_code, value: Number(row[cnt_key]), loc_type_name: row['type'], loc_type_id: row.type_id, category_id: item.category_id});  
        });

        graph_data1.values = list;

        $scope.location_type_count_data.push(graph_data1);    
    });
    
    $scope.onBack = function()
    {
        $scope.sub_category_view = false;
    }

    $scope.location_type_subcategory_options = {
        chart: {
            type: 'discreteBarChart',
            height: chart_height,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 60
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},            
            showValues: true,            
            valueFormat: function(d){
                return Math.round(d);
            },
            duration: 500,
            xAxis: {
                fontSize: 11
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d){
                    return Math.round(d);
                }
            }
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

    function showSubcategoryByLocType(data) {
        $scope.sub_category_view = true;

        var category_name = data.key;

        $scope.location_type_subcategory_options.title.text = category_name + ' / ' + data.loc_type_name  + ': Sub Category';

        var key = '';
        if( data.loc_type_id )
            key += data.loc_type_id;

        key += '_' + data.category_id;    

        var datalist = loc_data.dept_loc_category_subcategory_list[key];

        var list = [];
        datalist.forEach(row => {
            var value = Number(row.cnt);            
            list.push({label: row['subcategory_name'], value: value});                  
        });

        $scope.location_type_subcategory_count_data = [
            {
                key: "Cumulative Return",
                values: list
            }
        ];

    }

    
});

app.controller('LocationTypeResolveDialogCtrl', function($scope, $http, $uibModalInstance, $uibModal, $window, graph_data, data) {
    console.log(data);

    chart_height = $window.innerHeight / 2;

    $scope.resolve_options = {
        chart: {
            type: 'discreteBarChart',
            height: chart_height,
            margin : {
                top: 20,
                right: 20,
                bottom: 14,
                left: 60
            },
            x: function(d){return d.label;},
            y: function(d){return Math.round(d.value);},
            showValues: true,            
            valueFormat: function(d){
                return moment("2015-01-01").startOf('day')
                    .seconds(d)
                    .format('HH:mm:ss');
            },
            duration: 500,
            xAxis: {
                fontSize: 8
            },
            yAxis: {
                fontSize: 11,
                tickFormat: function(d) {
                    return moment("2015-01-01").startOf('day')
                        .seconds(d)
                        .format('HH:mm:ss');
                }
            },
            callback: function (chart) {
                var tooltip = chart.tooltip;             
                tooltip.contentGenerator(function (obj) {
                    return '<table>' + 
                        '<tbody>' +                         
                        '<tr><td class=key><div class="btn-group-vertical" style="width: 15px;height:15px;border-color:gray;border-width:1px;border-style: solid;background-color:' + obj.color + ';"></div></td><td class=value>' + obj.data.loc_type_name + '</td></tr>' +
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
                'font-size': 'small',
                'margin-top': 6
            }
        },        
    };

    // find dept location data
    var loc_data = undefined;
    for(var i = 0; i < graph_data.dept_resolve_time.length; i++ )
    {
        var row = graph_data.dept_resolve_time[i];
        if( row.short_code == data.label )
           loc_data = row;           
    }

    if( !loc_data )
        return;

    $scope.resolve_options.title.text = loc_data.department + ': Location Type vs Average Resolve Time';    

    var list = [];
    loc_data.loc_resolve_list.forEach(row => {
        var value = 0;
        if( Number(row.cnt) > 0 )
            value = Math.round(Number(row.total_time) / Number(row.cnt));

        list.push({label: row['short_code'], value: value, loc_type_name: row.location_type});                  
    });

    $scope.location_type_resolve_data = [
        {
            key: "Cumulative Return",
            values: list
        }
    ];
});

app.controller('LocationTypeSeverityCountDialogCtrl', function($scope, $http, $uibModalInstance, $uibModal, $window, graph_data, data) {
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
            text: 'Location Type/Severity',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
    };

    // find dept location data
    var item = undefined;
    graph_data.subcomplaint_severity.forEach(row => {
        if( row.short_code == data.label )
            item = row;                       
    });
    
    if( !item )
        return;

    $scope.location_type_options.title.text = item.department + ': Location Type';

    $scope.location_type_count_data = [];

    graph_data.severity_name_list.forEach(row => {
        var graph_data = {};
        var cnt_key = row.type;
        graph_data.key = row.type;

        var list = [];
        item.severity_loc_type.forEach(row1 => {
            if( !row1['short_code'] )
            {
                row1['short_code'] = 'ULT';
                row1['type'] = 'Unknown';
            }

            list.push({label: row1['short_code'], value: Number(row1[cnt_key]), loc_type_name: row1['type']});
        });

        graph_data.values = list;

        $scope.location_type_count_data.push(graph_data);    
    });
});
