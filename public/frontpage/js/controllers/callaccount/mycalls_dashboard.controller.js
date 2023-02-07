app.controller('MyCallsDashboardController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, $interval, AuthService, toaster) {
    var MESSAGE_TITLE = 'My Calls Dashboard';
    $scope.filter = {};

    $scope.full_height = 'height: ' + ($window.innerHeight - 50) + 'px; overflow-y: auto;';
    var profile = AuthService.GetCredentials();
    var data = {};
    data.setting_group = 'currency';
    data.property_id = profile.property_id;
    $http({
        method: 'POST',
        url: '/backoffice/configuration/wizard/general',
        data: data,
        headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
        .success(function (data, status, headers, config) {
            $scope.currency = data.currency.currency;
        })
        .error(function (data, status, headers, config) {
            console.log(status);
        });
    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.three_height = ($window.innerHeight - 55 - 52 - 4 - 109 - 10 - 20) / 2;

    // $scope.xaxis = {font: { color: '#ccc' }};
    // $scope.yaxis = {font: { color: '#ccc' }};

    $scope.fullScreen = function (fullmode) {
        $rootScope.fullmode = fullmode;

        if (fullmode == true) {
            $scope.three_height = ($window.innerHeight - 70) / 2;
        }
        else {
            $scope.three_height = ($window.innerHeight - 109) / 2;
        }
        $scope.admincall_options.chart.height = $scope.three_height - 85;
        $scope.guestcall_options.chart.height = $scope.three_height - 85;
        $scope.duration_admin_options.chart.height = $scope.three_height - 85;
        $scope.cost_admin_options.chart.height = $scope.three_height - 85;
    }


    $scope.filter.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.filter.dateFilter = 'Today';

    $scope.subcount = {};
    $scope.subcount.international = 0;
    $scope.subcount.national = 0;
    $scope.subcount.mobile = 0;
    $scope.subcount.local = 0;
    $scope.subcount.incoming = 0;
    $scope.subcount.internal = 0;


    $scope.$watch('filter.dateFilter', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        $scope.getCallStatistics();
    });

    $scope.$watch('filter.daterange', function (newValue, oldValue) {
        if (newValue == oldValue)
            return;

        $scope.getCallStatistics();
    });
    /////////////////////////////////////////////////
    // $scope.admin_stats_option={
    //         chart: {
    //             type: 'multiBarHorizontalChart',
    //             height: $scope.three_height,
    //             margin: {
    //                 top: 10,
    //                 right: 0,
    //                 bottom: 0,
    //                 left: 0
    //             },
    //             x: function (d) { return d.key; },
    //             y: function (d) { return d.y; },
    //             showControls: false,
    //             showValues: true,
    //             duration: 500,
    //             xAxis: {
    //                 showMaxMin: false,
    //                 fontSize: 11
    //             },
    //             yAxis: {
    //                 tickFormat: function (d) {
    //                     return Math.round(d);
    //                 },
    //                 fontSize: 11
    //             },
    //             legend: {
    //                 rightAlign: false,
    //                 margin: {
    //                     left: 50,
    //                     top: 5
    //                 }
    //             },
    //             //legendPosition: 'bottom',
    //             stacked: true,
    //         },

    //         // title: {
    //         //     enable: true,
    //         //     text: 'Top Called Destinations(Guest Call)',
    //         //     css: {
    //         //         color: 'white',
    //         //         'font-size': 'small',
    //         //         'margin-top': 6
    //         //     }
    //         // },
    //     };
    // $scope.adminStats = function (datalist) {
    //     var requested = [], escalated = [], timeout = [];
    //     /////////
    //     var count_info = datalist.subcount_admin;
    //     for (var i = 0; i < count_info.length; i++) {
    //         requested.push({ label: count_info[i].country, value: Math.round(Number(count_info[i].cnt)) });
    //     }

    //     $scope.admin_stats_data = [
    //         {
    //             "key": " Call Destination",
    //             "color": "#2ebf5b",
    //             "values": requested
    //         }];
    // };
    $scope.onDownloadPDF = function () {
        // var profile = AuthService.GetCredentials();

        // var filter = {};
        // filter.user_id = profile.id;
        // filter.report_by = 'Summary';
        // filter.report_type = 'Summary';
        // filter.report_target = 'complaint_summary';
        // var profile = AuthService.GetCredentials();
        // filter.property_id = profile.property_id;
        // filter.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        // filter.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        // filter.filter_value = $scope.filter_value;

        // $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }
    $scope.admin_stats_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.three_height,
            margin: {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
            },
            x: function (d) { return d.label; },
            y: function (d) { return d.value; },
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function (d) {
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
            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(JSON.stringify(d));
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Admin Stats',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.dept_count_list = [];

    $scope.adminStats = function (datalist) {
        var subcount_admin = datalist.subcount_admin;
        var personal = [], business = [], unclassified = [];
        /////////

        for (var i = 0; i < subcount_admin.length; i++) {
            personal.push({ label: subcount_admin[i].call_type, value: Math.round(Number(subcount_admin[i].personal)) });
            business.push({ label: subcount_admin[i].call_type, value: Math.round(Number(subcount_admin[i].business)) });
            unclassified.push({ label: subcount_admin[i].call_type, value: Math.round(Number(subcount_admin[i].unclassified)) });

        }

        $scope.admin_stats_data = [
            {
                "key": "Personal",
                "color": "#00a14d",
                "values": personal
            },
            {
                "key": "Business",
                "color": "#FB8C00",
                "values": business
            },
            {
                "key": "Unclassified",
                "color": "#ee6757",
                "values": unclassified
            }
        ]
    }

    //Mobile Stats
    $scope.mobile_stats_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.three_height,
            margin: {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
            },
            x: function (d) { return d.label; },
            y: function (d) { return d.value; },
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function (d) {
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
            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
            //legendPosition: 'bottom',
            stacked: true,
        },

        title: {
            enable: true,
            text: 'Mobile Stats',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.dept_count_list = [];

    $scope.mobileStats = function (datalist) {
        //window.alert(JSON.stringify(datalist.subcount_mobile));
        var subcount_mobile = datalist.subcount_mobile;
        var personal = [], business = [], unclassified = [];
        /////////

        for (var i = 0; i < subcount_mobile.length; i++) {
            personal.push({ label: subcount_mobile[i].call_type, value: Math.round(Number(subcount_mobile[i].personal)) });
            business.push({ label: subcount_mobile[i].call_type, value: Math.round(Number(subcount_mobile[i].business)) });
            unclassified.push({ label: subcount_mobile[i].call_type, value: Math.round(Number(subcount_mobile[i].unclassified)) });

        }

        $scope.mobile_stats_data = [
            {
                "key": "Personal",
                "color": "#00a14d",
                "values": personal
            },
            {
                "key": "Business",
                "color": "#FB8C00",
                "values": business
            },
            {
                "key": "Unclassified",
                "color": "#ee6757",
                "values": unclassified
            }
        ]
       // window.alert(JSON.stringify($scope.mobile_stats_data));
    }

    $scope.admin_total_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },

            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },
            


            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Calls(Admin Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };
    $scope.adminTotal = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.admin_total_data = [];
        var count_info = datalist.by_admin_total_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.admin_total_data.push({ key: count_info[i].classify, y: Math.round(Number(count_info[i].cnt)) });
        }

        // $scope.admincall_data = [
        //     {
        //         "key": " Call Destination",
        //         "color": "#b32ebf",
        //         "values": requested
        //     }];
    };
    //total mobile
    $scope.mobile_total_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },

            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Calls(Moble Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };
    $scope.mobileTotal = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.mobile_total_data = [];
        var count_info = datalist.by_mobile_total_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.mobile_total_data.push({ key: count_info[i].classify, y: Math.round(Number(count_info[i].cnt)) });
        }

        // $scope.admincall_data = [
        //     {
        //         "key": " Call Destination",
        //         "color": "#b32ebf",
        //         "values": requested
        //     }];
    };
    $scope.admincall_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },

            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Called Destinations(Admin Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.adminCallTask = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.admincall_data=[];
        var count_info = datalist.by_admin_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.admincall_data.push({ key: count_info[i].country, y: Math.round(Number(count_info[i].cnt)) });
        }

        // $scope.admincall_data = [
        //     {
        //         "key": " Call Destination",
        //         "color": "#b32ebf",
        //         "values": requested
        //     }];
    };
    //Mobile Call Destinations

    $scope.mobilecall_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },

            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Called Destinations(Mobile Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.mobileCallTask = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.mobilecall_data = [];
        var count_info = datalist.by_mobile_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.mobilecall_data.push({ key: count_info[i].country, y: Math.round(Number(count_info[i].cnt)) });
        }

        // $scope.admincall_data = [
        //     {
        //         "key": " Call Destination",
        //         "color": "#b32ebf",
        //         "values": requested
        //     }];
    };
    //////////////guest call/////
    $scope.guestcall_options = {
        chart: {
            type: 'multiBarHorizontalChart',
            height: $scope.three_height,
            margin: {
                top: 0,
                right: 20,
                bottom: 40,
                left: 100
            },
            x: function (d) { return d.label; },
            y: function (d) { return d.value; },
            showControls: false,
            showValues: true,
            duration: 500,
            xAxis: {
                showMaxMin: false,
                fontSize: 11
            },
            yAxis: {
                tickFormat: function (d) {
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
        },

        title: {
            enable: true,
            text: 'Top Called Destinations(Guest Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.guestCallTask = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        var count_info = datalist.by_guest_cnt;
        for (var i = 0; i < count_info.length; i++) {
            requested.push({ label: count_info[i].country, value: Math.round(Number(count_info[i].cnt)) });
        }

        $scope.guestcall_data = [
            {
                "key": " Call Destination",
                "color": "#2ebf5b",
                "values": requested
            }];
    };

    //duration Admin
    $scope.duration_admin_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },

            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;minutes&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Called Duration(Admin Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };
    $scope.adminDuration = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.duration_admin_data = [];
        var count_info = datalist.by_duration_admin_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.duration_admin_data.push({ key: count_info[i].classify, y: Math.round(Number(count_info[i].cnt/60)) });
        }

        // $scope.duration_admin_data = [
        //     {
        //         "key": " Call Duration",
        //         "color": "#2651f1",
        //         "values": requested
        //     }];
    };
    

    // mobile duration
    $scope.duration_mobile_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },

            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;minutes&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Called Duration(Mobile Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };
    $scope.mobileDuration = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.duration_mobile_data = [];
        var count_info = datalist.by_duration_mobile_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.duration_mobile_data.push({ key: count_info[i].classify, y: Math.round(Number(count_info[i].cnt/60)) });
        }

        // $scope.duration_admin_data = [
        //     {
        //         "key": " Call Duration",
        //         "color": "#2651f1",
        //         "values": requested
        //     }];
    };

    
    //cost admin
    $scope.cost_admin_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },
            z: function (d) { return d.curr; },
            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;'+d.data.curr+'&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Called Cost(Admin Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.adminCost = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.cost_admin_data = [];
        var count_info = datalist.by_cost_admin_cnt;
        for (var i = 0; i < count_info.length; i++) {
            $scope.cost_admin_data.push({ key: count_info[i].classify, y: Math.round(Number(count_info[i].cnt)), curr: $scope.currency });
        }

        // $scope.cost_admin_data = [
        //     {
        //         "key": " Call Cost",
        //         "color": "#e3a613",
        //         "values": requested
        //     }];
    };

    //cost mobile
    $scope.cost_mobile_options = {
        chart: {
            type: 'pieChart',
            height: 250,
            x: function (d) { return d.key; },
            y: function (d) { return d.y; },
            z: function (d) { return d.curr; },
            duration: 500,
            labelThreshold: 1.0,
            labelSunbeamLayout: false,
            donutLabelsOutside: true,
            growOnHover: true,
            legend: {
                margin: {
                    top: 10,
                    right: 0,
                    bottom: 0,
                    left: 0
                }
            },



            callback: function (chart) {
                console.log("!!! lineChart callback !!!");
                var tooltip = chart.tooltip;
                chart.tooltip.distance(-15);
                //chart.tooltip.duration(2000);
                //chart.tooltip.hidden(false);
                chart.tooltip.hideDelay(2000)
                chart.tooltip.fixedTop(100);

                //chart.tooltip.hidden(true);
                //contentGenerator(function (d){
                tooltip.contentGenerator(function (d) {
                    //console.log(d.data.y);
                    var html =

                        '<div class="btn-group-vertical" style="color:' + d.color + ';background-color:black;">' +

                        '<div class="inline"><label class="control-label font-bold">' + '&nbsp;&nbsp;' + d.data.key + ':&nbsp;&nbsp;' + d.data.y +
                        '&nbsp;' + d.data.curr + '&nbsp;&nbsp;</label></pre></div>' +
                        '<br/>' +
                        '</div>';
                    return html;
                });

                return chart;
            },
        },

        title: {
            enable: true,
            text: 'Total Called Cost(Mobile Call)',
            css: {
                color: 'white',
                'font-size': 'small',
                'margin-top': 6
            }
        },
    };

    $scope.mobileCost = function (datalist) {
        var requested = [], escalated = [], timeout = [];
        /////////
        $scope.cost_mobile_data = [];
        var count_info = datalist.by_cost_mobile_cnt;
        for (var i = 0; i < count_info.length; i++) {
            if (Math.round(Number(count_info[i].cnt))>0)
            $scope.cost_mobile_data.push({ key: count_info[i].classify, y: Math.round(Number(count_info[i].cnt)), curr: $scope.currency });
        }

        // $scope.cost_admin_data = [
        //     {
        //         "key": " Call Cost",
        //         "color": "#e3a613",
        //         "values": requested
        //     }];
    };

    ////////////////////////////////////////////////////
    //
    //var s1 = [];
    //var s2 = [];
    //$scope.ticks = [];
    //
    //$scope.admin_call_data = [s1];

    // $scope.admin_call_option = {
    //     title: 'Top Called Destinations(Admin Call)',
    //     seriesDefaults: {
    //         renderer:$.jqplot.BarRenderer,
    //         pointLabels: { show: true },
    //         rendererOptions: {
    //             // Set varyBarColor to tru to use the custom colors on the bars.
    //             varyBarColor: true
    //         }
    //     },
    //     axes: {
    //         xaxis: {
    //             renderer: $.jqplot.CategoryAxisRenderer,
    //             ticks: $scope.ticks
    //         },
    //         yaxis: {
    //             labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
    //             min: 0 ,
    //             tickOptions: {
    //                 formatString: '%.0f'
    //             }
    //
    //         }
    //     },
    //     legend:{
    //         renderer: $.jqplot.EnhancedLegendRenderer,
    //         show:true,
    //     },
    //     series:[
    //         {label : 'Admin Call'},
    //     ],
    //     highlighter: {
    //         show: true,
    //         sizeAdjust: 1,
    //         tooltipOffset: 9,
    //         tooltipContentEditor:tooltipContentEditor
    //     },
    //     cursor: {
    //         show: false
    //     }
    // };
    //
    // $scope.guest_call_option = angular.copy($scope.admin_call_option);
    // $scope.guest_call_option.title = 'Top Called Destinations(Guest Call)';
    // $scope.guest_call_option.series = [
    //     {label : 'Guest Call'},
    // ];

    // function tooltipContentEditor(str, seriesIndex, pointIndex, plot) {
    //     // display series_label, x-axis_tick, y-axis value
    //     return plot.series[seriesIndex]["label"] + ", " + plot.data[seriesIndex][pointIndex];
    // }

    $scope.getCallStatistics = function () {
        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.filter.dateFilter;
        $scope.filter.user_id = profile.id;

        switch ($scope.filter.period) {
            case 'Weekly':
                $scope.filter.during = 7;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Monthly':
                $scope.filter.during = 30;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                $scope.filter.during = 365;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Custom Days':
                $scope.filter.start_date = $scope.filter.daterange.substring(0, '2016-01-01'.length);
                $scope.filter.end_date = $scope.filter.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
                var a = moment($scope.filter.start_date);
                var b = moment($scope.filter.end_date);
                $scope.filter.during = b.diff(a, 'days');

                var ticks = [];
                for (var i = 0; i < $scope.filter.during; i++) {
                    var time = moment().subtract($scope.filter.during - 1 - i, 'd').format('MM-DD');
                    ticks.push(time);
                }

                if ($scope.filter.during > 94) {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 94 days");
                    return;
                }
                break;
        }


        $http({
            method: 'POST',
            url: '/frontend/callaccount/mycallstats',
            data: $scope.filter,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response.data);
            $scope.adminStats(response.data);
            $scope.mobileStats(response.data);
            $scope.adminTotal(response.data);
            $scope.mobileTotal(response.data);
            $scope.adminCallTask(response.data);
            $scope.mobileCallTask(response.data);
            //$scope.guestCallTask(response.data);
            $scope.adminDuration(response.data);
            $scope.mobileDuration(response.data);
            $scope.adminCost(response.data);
            $scope.mobileCost(response.data);

        }).catch(function (response) {

        })
            .finally(function () {

            });
    }

    $scope.showStatistics = function (data) {
        // var s1 = [];
        // var s2 = [];
        // var ticks = [];
        //
        // var count_info = data.by_admin_cnt;
        // for(var i = 0; i < count_info.length; i++)
        // {
        //     s1[i] = count_info[i].cnt;
        //     ticks.push(count_info[i].country);
        // }
        // if( ticks.length < 1 ) {
        //     s1.push(0);
        //     ticks.push('No Data');
        // }
        //
        // $scope.admin_call_option.axes.xaxis.ticks = ticks;
        //
        // $scope.admin_call_data = [s1];
        //
        // var guest_ticks = [];
        //
        // var count_info = data.by_guest_cnt;
        // for(var i = 0; i < count_info.length; i++)
        // {
        //     s2[i] = count_info[i].cnt;
        //     guest_ticks.push(count_info[i].country);
        // }
        // if( guest_ticks.length < 1 ) {
        //     s2.push(0);
        //     guest_ticks.push('No Data');
        // }
        //
        // $scope.guest_call_option.axes.xaxis.ticks = guest_ticks;
        //
        // $scope.guest_call_data = [s2];

        $scope.subcount.international = parseInt(data.subcount_admin.international) + parseInt(data.subcount_guest.international);
        $scope.subcount.national = parseInt(data.subcount_admin.national) + parseInt(data.subcount_guest.national);
        $scope.subcount.mobile = parseInt(data.subcount_admin.mobile) + parseInt(data.subcount_guest.mobile);
        $scope.subcount.local = parseInt(data.subcount_admin.local) + parseInt(data.subcount_guest.local);
        $scope.subcount.incoming = parseInt(data.subcount_admin.incoming) + parseInt(data.subcount_guest.incoming);
        $scope.subcount.internal = parseInt(data.subcount_admin.internal) + parseInt(data.subcount_guest.internal);
    }

    $scope.getCallStatistics();
});