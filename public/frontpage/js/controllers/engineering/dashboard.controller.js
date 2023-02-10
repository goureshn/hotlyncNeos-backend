app.controller('EngineeringDashboardController', function($scope, $rootScope, $http, $httpParamSerializer, $window, AuthService, toaster, $timeout, $uibModal, $state, $stateParams, $timeout, toaster) {
    var MESSAGE_TITLE = 'Guest Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';

    $scope.three_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 25 - 20) / 2;

    $scope.xaxis = {font: { color: '#ccc' }};
    $scope.yaxis = {font: { color: '#ccc' }};

    $scope.fullScreen = function(fullmode) {
        $rootScope.fullmode = fullmode;
        if( fullmode == true ) {
            $scope.two_height = ($window.innerHeight - 52 - 4 - 109 - 10 - 10) / 2;
            $scope.three_height = ($window.innerHeight - 70) / 2;
        }
        else
        {
            $scope.two_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 10) / 2;
            $scope.three_height = ($window.innerHeight - 55  - 4 - 109 - 10 - 20) / 2;
        }

        $scope.number_of_workorder_options.chart.height = $scope.three_height - 20;
        $scope.cost_of_workorder_options.chart.height = $scope.three_height - 20;
    }

    /***number of workorder****/
    $scope.number_of_workorder_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.three_height,
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
            },
            discretebar: {
                dispatch: {
                    //chartClick: function(e) {console.log("! chart Click !")},
                    elementClick: function(e) {
                    let request = {};
                    request.status = e.data.label;
                    request.staff_id = 0;
                    request.staff_tags = [
                        
                    ];
                    request.daterange  = $scope.daterange;
                    request.dateRangeOption = angular.copy($scope.dateRangeOption);

                    let startDate = '';
                    let endDate = moment().format('YYYY-MM-DD');

                    if ($scope.dateFilter == 'Today') {
                        startDate = endDate;
                    } else if ($scope.dateFilter == 'Weekly') {
                        startDate = moment().subtract(7, 'days').format('YYYY-MM-DD');
                    } else if ($scope.dateFilter == 'Monthly') {
                        startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
                    } else if ($scope.dateFilter == 'Yearly') {
                        startDate = moment().subtract(1, 'years').format('YYYY-MM-DD');
                    }else if ($scope.dateFilter == 'Custom Days'){
                        startDate = $scope.daterange.substring(0, '2016-01-01'.length);
                        endDate = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                   }

                    request.dateRangeOption.startDate = startDate;
                    request.dateRangeOption.endDate = endDate;

                    request.daterange = startDate + ' - ' + endDate;

                    // save on localstorage for temp
                    window.localStorage.setItem('temp_task_request_data', JSON.stringify(request));
                    $state.go('app.engineering.workorder');
                    }
                }
            }
        },
        title: {
            enable: true,
            text: 'Work Order',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.numberOfWorkorder = function(data) {

        $scope.number_of_workorder_data = [
            {
                key: "Cumulative Return",
                values: [
                ]
            }
        ];

        var color = ['#9C27B0', '#3F51B5', '#FF5722', '#009688'];

        var pending = data.pending;
        var hold = data.hold;
        var progress = data.progress;
        var completed = data.completed;

        $scope.number_of_workorder_data[0].values.push( {"label" : "Pending" , "value" : Number(pending), color: color[0]});
        $scope.number_of_workorder_data[0].values.push( {"label" : "In Progress" , "value" : Number(progress), color: color[1]});
        $scope.number_of_workorder_data[0].values.push( {"label" : "On Hold" , "value" : Number(hold), color: color[2]});
        $scope.number_of_workorder_data[0].values.push( {"label" : "Completed" , "value" : Number(completed), color: color[3]});

    }

    /***cost of workorder****/
    $scope.cost_of_workorder_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.three_height,
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
            text: 'Cost',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.costOfWorkorder = function(data) {

        $scope.cost_of_workorder_data = [
            {
                key: "Cumulative Return",
                values: [
                ]
            }
        ];

        var color = ['#9C27B0', '#FF5722', '#009688','#ee900a'];

        var repair = data.repair;
    //    var requests = data.requests;
        var preventive = data.preventive;
        var upgrade = data.upgrade;
        var new_ = data.new;
        $scope.cost_of_workorder_data[0].values.push( {"label" : "Work Request" , "value" : Number(repair), color: color[0]});
    //    $scope.cost_of_workorder_data[0].values.push( {"label" : "Requests" , "value" : Number(requests), color: color[1]});
        $scope.cost_of_workorder_data[0].values.push( {"label" : "Preventive" , "value" : Number(preventive), color: color[2]});
        $scope.cost_of_workorder_data[0].values.push( {"label" : "Upgrade" , "value" : Number(upgrade), color: color[3]});
        $scope.cost_of_workorder_data[0].values.push( {"label" : "New" , "value" : Number(new_), color: color[4]});
    }
    /***********number of workorder**********/
    $scope.number_of_workorder_completed_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.three_height,
            margin : {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
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
                }
            },
            //legendPosition: 'bottom',
            stacked: true,
            callback: function(chart) {
                chart.multibar.dispatch.on('elementClick', function(e){
                    let request = {};
                    request.staff_id = e.data.user_id;
                    request.staff_tags = [
                        {id: e.data.user_id}
                    ];
                    request.daterange  = $scope.daterange;
                    request.dateRangeOption = angular.copy($scope.dateRangeOption);

                    let startDate = '';
                    let endDate = moment().format('YYYY-MM-DD');

                    if ($scope.dateFilter == 'Today') {
                        startDate = endDate;
                    } else if ($scope.dateFilter == 'Weekly') {
                        startDate = moment().subtract(7, 'days').format('YYYY-MM-DD');
                    } else if ($scope.dateFilter == 'Monthly') {
                        startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
                    } else if ($scope.dateFilter == 'Yearly') {
                        startDate = moment().subtract(1, 'years').format('YYYY-MM-DD');
                    } else if ($scope.dateFilter == 'Custom Days'){
                        startDate = $scope.daterange.substring(0, '2016-01-01'.length);
                        endDate = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                    }

                    request.dateRangeOption.startDate = startDate;
                    request.dateRangeOption.endDate = endDate;

                    request.daterange = startDate + ' - ' + endDate;

                    // save on localstorage for temp
                    window.localStorage.setItem('temp_task_request_data', JSON.stringify(request));
                    $state.go('app.engineering.workorder');
                });
            }

        },

        title: {
            enable: true,
            text: 'Task',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.number_of_workorder_completed = function (datalist) {
        var pending = [], hold = [], progress = [], completed = [];
        /////////
        for (var i = 0; i < datalist.length; i++) {
            pending.push({label: datalist[i].user, value: Math.round(Number(datalist[i].pending)), user_id: datalist[i].user_id});
            hold.push({label: datalist[i].user, value: Math.round(Number(datalist[i].hold)), user_id: datalist[i].user_id});
            progress.push({label: datalist[i].user, value: Math.round(Number(datalist[i].progress)), user_id: datalist[i].user_id});
            completed.push({label: datalist[i].user, value: Math.round(Number(datalist[i].completed)), user_id: datalist[i].user_id});
        }

        $scope.number_of_workorder_completed_data = [
            {
                "key": "Pending",
                "color": "#b535bd",
                "values": pending
            },
            {
                "key": "On Hold",
                "color": "#6254b2",
                "values": hold
            },
            {
                "key": "In Progress",
                "color": "#3F51B5",
                "values": progress
            },
            {
                "key": "Completed",
                "color": "#27c24c",
                "values": completed
            }];
    };

    /***priority***/
    $scope.priority_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.three_height,
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
            },

            discretebar: {
                dispatch: {
                    //chartClick: function(e) {console.log("! chart Click !")},
                    elementClick: function(e) {
                        let request = {};
                        request.priority = e.data.label;
                        request.period = $scope.dateFilter;
                        request.daterange  = $scope.daterange;
                        request.dateRangeOption = angular.copy($scope.dateRangeOption);

                        let startDate = '';
                        let endDate = moment().format('YYYY-MM-DD');

                        if ($scope.dateFilter == 'Today') {
                            startDate = endDate;
                        } else if ($scope.dateFilter == 'Weekly') {
                            startDate = moment().subtract(7, 'days').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Monthly') {
                            startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Yearly') {
                            startDate = moment().subtract(1, 'years').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Custom Days'){
                            startDate = $scope.daterange.substring(0, '2016-01-01'.length);
                            endDate = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                        }

                        request.dateRangeOption.startDate = startDate;
                        request.dateRangeOption.endDate = endDate;

                        request.daterange = startDate + ' - ' + endDate;

                        // save on localstorage for temp
                        window.localStorage.setItem('temp_status_request_data', JSON.stringify(request));
                        $state.go('app.engineering.repair_request');
                    }
                }
            }
        },
        title: {
            enable: true,
            text: 'Priority',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.priority = function(data) {

        $scope.priority_data = [
            {
                key: "Cumulative Return",
                values: [
                ]
            }
        ];

        var color = ['#9C27B0', '#3F51B5', '#FF5722', '#009688'];

        var low = data.low;
        var medium = data.medium;
        var high = data.high;
        var urgent = data.urgent;
        $scope.priority_data[0].values.push( {"label" : "Low" , "value" : Number(low), color: color[0]});
        $scope.priority_data[0].values.push( {"label" : "Medium" , "value" : Number(medium), color: color[1]});
        $scope.priority_data[0].values.push( {"label" : "High" , "value" : Number(high), color: color[2]});
        $scope.priority_data[0].values.push( {"label" : "Urgent" , "value" : Number(urgent), color: color[3]});
    }

    /***status****/
    $scope.status_options = {
        chart: {
            type: 'discreteBarChart',
            height: $scope.three_height,
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
            },
            discretebar: {
                dispatch: {
                    //chartClick: function(e) {console.log("! chart Click !")},
                    elementClick: function(e) {
                        let request = {};
                        request.status = e.data.label;
                        request.flag = 0;
                        request.period = $scope.dateFilter;
                        request.daterange  = $scope.daterange;
                        request.dateRangeOption = angular.copy($scope.dateRangeOption);

                        let startDate = '';
                        let endDate = moment().format('YYYY-MM-DD');

                        if ($scope.dateFilter == 'Today') {
                            startDate = endDate;
                        } else if ($scope.dateFilter == 'Weekly') {
                            startDate = moment().subtract(7, 'days').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Monthly') {
                            startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Yearly') {
                            startDate = moment().subtract(1, 'years').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Custom Days'){
                            startDate = $scope.daterange.substring(0, '2016-01-01'.length);
                            endDate = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                        }

                        request.dateRangeOption.startDate = startDate;
                        request.dateRangeOption.endDate = endDate;

                        request.daterange = startDate + ' - ' + endDate;

                        // save on localstorage for temp
                        window.localStorage.setItem('temp_status_request_data', JSON.stringify(request));
                        $state.go('app.engineering.repair_request');
                    }
                }
            }
        },
        title: {
            enable: true,
            text: 'Work Request',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.status = function(data) {

        $scope.status_data = [
            {
                key: "Cumulative Return",
                values: [
                ]
            }
        ];

        var color = ['#9C27B0', '#FF5722', '#009688', '#0047AB','#666666', '#D81B60', '#00BFFF'];

        var pending = data.pending;
        var assigned = data.assigned;
        var completed = data.completed;
        var in_progress = data.in_progress;
        var closed = data.closed;
        var rejected = data.rejected;
        var pre_approved = data.pre_approved;
        $scope.status_data[0].values.push( {"label" : "Pending" , "value" : Number(pending), color: color[0]});
        $scope.status_data[0].values.push( {"label" : "Assigned" , "value" : Number(assigned), color: color[1]});
        $scope.status_data[0].values.push( {"label" : "In Progress" , "value" : Number(in_progress), color: color[3]});
        $scope.status_data[0].values.push( {"label" : "Completed" , "value" : Number(completed), color: color[2]});
        $scope.status_data[0].values.push( {"label" : "Closed" , "value" : Number(closed), color: color[4]});
        $scope.status_data[0].values.push( {"label" : "Rejected" , "value" : Number(rejected), color: color[5]});
        $scope.status_data[0].values.push( {"label" : "Pre-Approved" , "value" : Number(pre_approved), color: color[6]});
    }
    /*********************/
    $scope.loading = true;

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

    $scope.dateFilter = 'Today';

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.filter = {};

    $scope.getTicketStatistics = function() {

        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.dateFilter;
        $scope.filter.user_id = profile.id;

        switch($scope.filter.period)
        {
            case 'Today':
                break;
            case 'Weekly':
                $scope.filter.during = 7;
                $scope.filter.end_date = moment().format('YYYY-MM-DD HH:mm:ss');
                break;
            case 'Monthly':
                $scope.filter.during = 30;
                $scope.filter.end_date = moment().format('YYYY-MM-DD HH:mm:ss');
                break;
            case 'Yearly':
                $scope.xaxis.ticks = [];
                $scope.filter.end_date = moment().format('YYYY-MM-DD HH:mm:ss');
                break;
            case 'Custom Days':
                $scope.filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
                $scope.filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
                $scope.filter.during = b.diff(a, 'days') + 1;
                /*
                if( $scope.filter.during > 45 )
                {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 45 days");
                    return;
                }
                */
                break;
        }

        var param = $httpParamSerializer($scope.filter);

        $scope.loading = true;
        $http.get('/frontend/equipment/statistics?' + param)
            .then(function(response) {
                console.log(response.data);

                $scope.total_out_of_stock = response.data.total_out_of_stock;
                $scope.total_low_stock = response.data.total_low_stock;
                $scope.total_pending = response.data.total_pending;
                $scope.total_inprogress = response.data.total_inprogress;
                $scope.total_closed = response.data.total_closed;
                $scope.total_completed = response.data.total_completed;

                $scope.numberOfWorkorder(response.data.number_of_workorder);
                $scope.costOfWorkorder(response.data.cost_of_workorder);
                $scope.number_of_workorder_completed(response.data.number_of_workorder_completed);
                $scope.priority(response.data.priority);
                $scope.status(response.data.status);


            }).catch(function(response) {

            })
            .finally(function() {
                $scope.loading = false;
            });
    }

    $scope.getTicketStatistics();

    $scope.onChangeFilterItem = function(item) {
                        let request = {};
                        request.status = item;
                        request.flag = 1;
                        request.period = $scope.dateFilter;
                        request.daterange  = $scope.daterange;
                        request.dateRangeOption = angular.copy($scope.dateRangeOption);

                        let startDate = '';
                        let endDate = moment().format('YYYY-MM-DD');

                        if ($scope.dateFilter == 'Today') {
                            startDate = endDate;
                        } else if ($scope.dateFilter == 'Weekly') {
                            startDate = moment().subtract(7, 'days').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Monthly') {
                            startDate = moment().subtract(30, 'days').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Yearly') {
                            startDate = moment().subtract(1, 'years').format('YYYY-MM-DD');
                        } else if ($scope.dateFilter == 'Custom Days'){
                            startDate = $scope.daterange.substring(0, '2016-01-01'.length);
                            endDate = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                        }

                        request.dateRangeOption.startDate = startDate;
                        request.dateRangeOption.endDate = endDate;

                        request.daterange = startDate + ' - ' + endDate;

                        // save on localstorage for temp
                        window.localStorage.setItem('temp_status_request_data', JSON.stringify(request));
                        $state.go('app.engineering.repair_request');

    };
});
