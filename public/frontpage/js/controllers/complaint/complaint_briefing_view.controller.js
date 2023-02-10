app.controller('ComplaintBriefingViewController', function ($scope, $rootScope,  $httpParamSerializer, $window, $http, $interval, $uibModal, AuthService, GuestService, toaster, socket) {
    var MESSAGE_TITLE = 'Validation';

    $scope.complaint = {};
    $scope.exist_subcomplaint_list = [];
    $scope.dept_tag_list = [];

    var profile = AuthService.GetCredentials();
    $scope.property_list = profile.property_list;
    $scope.current_property_id = profile.property_id;

    function getCurrentBriefing() {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = $scope.current_property_id;
        request.user_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/currentbriefing',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    $scope.complaint = {};                    
                    return;
                }

                $scope.briefing_num = response.data.briefing_num;
                $scope.briefing_count = response.data.briefing_count;

                $scope.init(response.data.datalist[0]);
            }).catch(function(response) {
               // console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    getCurrentBriefing();

    $scope.init = function(complaint) {
        $scope.complaint = complaint;
        $scope.complaint.location = complaint.lgm_type + ' - ' + complaint.lgm_name;
        $scope.comment_list = [];
        $scope.complaint.compensation = {};
        $scope.complaint.compensation.cost = complaint.cost;

        $scope.complaint.guest_is_open = true;
        $scope.complaint.compensation_comment_is_open = false;
        $scope.complaint.running_subcomplaint_is_open = false;
        $scope.complaint.complaint_comment_is_open = false;

        if( !$scope.complaint.compensation_name )
            $scope.complaint.compensation_name = '';

        var comment_highlight = $scope.complaint.comment_highlight + '';
        var response_highlight = $scope.complaint.response_highlight + '';

        $scope.complaint.comment_highlight = undefined;
        $scope.complaint.response_highlight = undefined;

        $http.get('/frontend/complaint/getcomplaintinfo?id=' + complaint.id)
            .then(function(response) {
                $scope.complaint.comment_highlight = comment_highlight;
                $scope.complaint.response_highlight = response_highlight;
                
                $scope.exist_subcomplaint_list =  response.data.content.sublist;
                getDepartmentTags(response.data.content.sublist);
            });    

        getCommentList(); 
        getCompensationCommentList();   
    }

    $scope.$on('$destroy', function() {
        leaveFromBreifing($scope.current_property_id);
    });

    function joinToBriefing(property_id)
    {
        var profile = AuthService.GetCredentials();

        if( property_id > 0 && property_id == profile.property_id )
            return;

        var config = {};
        config.source = 'web';
        config.user_id = profile.id;
        config.property_id = property_id;
        socket.emit('briefing_view_join', config);
    }

    function leaveFromBreifing(property_id) {
        var profile = AuthService.GetCredentials();

        if( property_id > 0 && property_id == profile.property_id )
            return;

        var config = {};
        config.source = 'web';
        config.user_id = profile.id;
        config.property_id = property_id;
        socket.emit('briefing_view_leave', config);   
    }

    $scope.onChangedProperty = function() {
        joinToBriefing($scope.current_property_id);

        getCurrentBriefing();
    }

    function getDepartmentTags(subcomplaint_list) {
        var profile = AuthService.GetCredentials();
        var dept_list = [];
        for(var i = 0; i < subcomplaint_list.length; i++)
        {
            var dept = {};
            dept.id = subcomplaint_list[i].dept_id;
            dept.short_code = subcomplaint_list[i].short_code;

            if( dept.id == profile.dept_id) {
                dept_list.push(dept);
                break;
            }
        }

        $scope.dept_tag_list = dept_list;
    }

    function getCommentList(sub) {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.comment_list = response.data;
            for(var i = 0; i < $scope.comment_list.length; i++)
            {
                $scope.comment_list[i].comment = $scope.comment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }


    function getCompensationCommentList() {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getcompensationcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.compensationcomment_list = response.data;
            for(var i = 0; i < $scope.compensationcomment_list.length; i++)
            {
                $scope.compensationcomment_list[i].comment = $scope.compensationcomment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.commitComment = function(comment) {
        console.log(comment);

        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.sub_id = $scope.complaint.id;
        request.parent_id = 0;
        request.user_id = $scope.profile.id;        
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/addcomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                

            $scope.comment_list = response.data;
            $scope.complaint.sub_comment = ''; 
            for(var i = 0; i < $scope.comment_list.length; i++)
            {
                $scope.comment_list[i].comment = $scope.comment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.flagComplaint = function() {
        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.complaint_id = $scope.complaint.id;
        request.user_id = profile.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/flag',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.complaint.flag = response.data.flag;  
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.makeNote = function(comment) {
        var profile = AuthService.GetCredentials();
        
        var request = {};        
        request.complaint_id = $scope.complaint.id;
        request.user_id = profile.id;
        request.comment = comment;
        if( !comment )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please input note comment.');
            return;
        }
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/makenote',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('success', MESSAGE_TITLE, 'Note is made successfully');
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.getMainTicketNumber = function(){
        if( !$scope.complaint || !$scope.complaint.id )
            return 'Not Started';

        return sprintf('C%05d', $scope.complaint.id);        
    }

    $scope.getTicketNumber = function(sub){
        if( !sub || !sub.parent_id )
            return 'Not Started';

        return sprintf('C%05d%s', sub.parent_id, sub.sub_label);        

        return sprintf('C%05d', $scope.complaint.id);        
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

    $scope.$on('briefing', function(event, args){
        if( args.property_id != $scope.current_property_id )
            return;

        getCurrentBriefing();    
    });

    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    $scope.filter = {};

    $scope.full_height = 'height: ' + ($window.innerHeight-20) + 'px;';
    $scope.chart_height = ($window.innerHeight - 195) / 2;

    $rootScope.fullmode = false;
    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;
        if( fullmode == true ) {
            $scope.full_height = 'height: ' + ($window.innerHeight) + 'px;';
            $scope.chart_height = ($window.innerHeight-162) / 2;
        }
        else
        {
            $scope.chart_height = ($window.innerHeight - 195) / 2;
        }

        $scope.severiy_options.chart.height = $scope.chart_height+5;
        $scope.status_options.chart.height = $scope.chart_height+5;
        $scope.guest_type_options.chart.height = $scope.chart_height+5;
        $scope.property_department_options.chart.height = $scope.chart_height+5;
        $scope.compensation_options.chart.height = $scope.chart_height+5;
    }


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
                $scope.showStatistics(response.data);

            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.getTicketStatistics();

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
            height: $scope.chart_height,
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
        var color = ['#9C27B0', '#3F51B5', '#FF5722', '#009688', '#ee900a'];

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
            height: $scope.chart_height,
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
            stacked: true,
        },

        title: {
            enable: true,
            text: 'Status',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    function showStatusGraph(data) {
        $scope.status_data = [
            {
                key: "Cumulative Return",
                values: [                    
                ]
            }
        ];

        var status = data.status;
        var color = ['#9C27B0', '#3F51B5', '#FF5722', '#009688', '#ee900a'];

        var count = 0;
        for (var key in status) {
            if( count > color.length )
                count = color.length - 1;
            if (status.hasOwnProperty(key)) {
                $scope.status_data[0].values.push( {
                    "label" : key ,
                    "value" : Number(status[key]),
                    color: color[count]
                });
                count++;
            }
        }
    }

    //Guest_type
    $scope.guest_type_options = {
        chart: {
            type: 'pieChart',
            height: $scope.chart_height,
            donut:true,
            x: function(d){return d.key;},
            y: function(d){return d.y;},
            showLabels: false,
            duration: 500,
            labelThreshold: 0.01,
            labelSunbeamLayout: true,
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
            type: 'lineChart',
            height: $scope.chart_height,
            margin : {
                top: 5,
                right: 20,
                bottom: 40,
                left: 70
            },
            x: function(d){ 
                return d.x; 
            },
            y: function(d){ return d.y; },
            xAxis: {
                ticks: [6],
                axisLabel :"DEPARTMENT",
                tickFormat: function(d) {
                    return dept_names[d];
                }
            },
            yAxis: {
                ticks: [5],
                axisLabel :"COMPLAINTS",
                tickFormat: function(d) {
                    return Math.round(d);
                }
            },
            //forceY: value_max,
            legend: {
                rightAlign: false,
                margin: {
                    left: 50,
                    top: 10
                }
            },
            //legendPosition: 'bottom',
            useInteractiveGuideline: true,
            dispatch: {
                stateChange: function(e){ console.log("stateChange"); },
                changeState: function(e){ console.log("changeState"); },
                tooltipShow: function(e){ console.log("tooltipShow"); },
                tooltipHide: function(e){ console.log("tooltipHide"); }
            },
            callback: function(chart){
                console.log("!!! lineChart callback !!!");
            },
            legendPosition: 'bottom',
        },
        title: {
            enable: true,
            text: 'Property vs Department',
            css: {
                color: 'white',
                'font-size': 'medium',
                'margin-top': 6
            }
        },
    };

    function showPropertyDepartmentGraph(data) {
        dept_names = [];
        $scope.property_department_data = [];

        var color = ['#9C27B0', '#3F51B5', '#FF5722', '#009688', '#ee900a'];

        var max_dept_cnt = 0;
        for(var i = 0; i < data.property_list.length; i++ )
        {
            dept_names = [];
            var graph_data = {};

            graph_data.key = data.property_list[i].name;
            graph_data.color = color[i];
            graph_data.values = [];

            for(var j = 0; j < data.dept_data[i].length; j++)
            {
                dept_names.push(data.dept_data[i][j].department);
                graph_data.values.push({x:j, y:Number(data.dept_data[i][j].cnt)});
            }

            if( data.dept_data[i].length > max_dept_cnt )
                max_dept_cnt = data.dept_data[i].length;

            $scope.property_department_data.push(graph_data);
        }

        $scope.property_department_options.chart.xAxis.ticks = max_dept_cnt;

    }

    //compensation
    $scope.compensation_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.chart_height,
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
                fontSize: 8
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

        var cost_data = data.cost_data;
        var color = ['#9C27B0', '#3F51B5', '#FF5722', '#009688', '#ee900a'];

        for(var i = 0; i < data.property_list.length; i++ )
        {
            if(cost_data[i] != null) {
                $scope.compensation_data[0].values.push({
                    "label": data.property_list[i].name,
                    "value": Number(cost_data[i].cost),
                    color: color[i]
                });
            }
        }
    }
   
});
