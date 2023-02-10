app.controller('MycallDashboardController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService, uiGridConstants) {
    var MESSAGE_TITLE = 'My Task';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.filter = {};

    var s1 = [];
    var s2 = [];
    $scope.ticks = [];

    $scope.data = [s1, s2];

    $scope.chartOptions = {
        seriesDefaults: {
            renderer:$.jqplot.BarRenderer,
            pointLabels: { show: true },
        },
        axes: {
            xaxis: {
                renderer: $.jqplot.CategoryAxisRenderer,
                ticks: $scope.ticks
            },
            yaxis: {
                labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                min: 0 ,
                tickOptions: {
                    formatString: '%.0f'
                }

            }
        },
        legend:{
            renderer: $.jqplot.EnhancedLegendRenderer,
            show:true,
        },
        series:[
            {label : 'Business'},
            {label : 'Personal'}
        ],
        highlighter: {
            show: true,
            sizeAdjust: 1,
            tooltipOffset: 9,
            tooltipContentEditor:tooltipContentEditor
        },
        cursor: {
            show: false
        }
    };

    $scope.by_count_option = {
        title:'MOSTLY DIALED',
        seriesDefaults:{
            renderer:$.jqplot.DonutRenderer,
            trendline:{ show: true },
            rendererOptions: { showDataLabels: true, dataLabels: 'value', totalLabel: true }
        },
        legend:{
            show: true,
            location: 'ne',
            rendererOptions: {numberColumns: 2}
        },
        cursor: {
            show: false
        }
    };

    $scope.by_cost_option = {
        title:'BY COST',
        seriesDefaults:{
            renderer:$.jqplot.DonutRenderer,
            trendline:{ show: true },
            rendererOptions: { showDataLabels: true, dataLabels: 'value', totalLabel: true }
        },
        legend:{ show: true,
            location: 'ne',
            rendererOptions: {numberColumns: 2}
        },
        cursor: {
            show: false
        }
    };

    function tooltipContentEditor(str, seriesIndex, pointIndex, plot) {
        // display series_label, x-axis_tick, y-axis value
        return plot.series[seriesIndex]["label"] + ", " + plot.data[seriesIndex][pointIndex];
    }


    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.filter.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.filter.dateFilter = 'Today';

    $scope.$watch('filter.dateFilter', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getCallStatistics();
    });

    $scope.$watch('filter.daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.getCallStatistics();
    });

    $scope.getCallStatistics = function() {
        var profile = AuthService.GetCredentials();
        $scope.filter.property_id = profile.property_id;
        $scope.filter.period = $scope.filter.dateFilter;
        $scope.filter.user_id = profile.id;
        $scope.filter.job_role = profile.job_role;
        $scope.filter.dept_id = profile.dept_id;

        switch($scope.filter.period)
        {
            case 'Today':
                var ticks = [];
                for(var i = 0; i < 12; i++) {
                    var time = sprintf('%02d:00', i * 2);
                    ticks.push(time);
                }
                break;
            case 'Weekly':
                $scope.filter.during = 7;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');

                var ticks = [];
                for(var i = 0; i < 7; i++) {
                    var time = moment().subtract(6-i,'d').format('dddd');
                    ticks.push(time);
                }

                break;
            case 'Monthly':
                var ticks = [];
                for(var i = 0; i < 30; i++) {
                    var time = moment().subtract(29-i,'d').format('MM-DD');
                    ticks.push(time);
                }
                $scope.filter.during = 30;
                $scope.filter.end_date = moment().format('YYYY-MM-DD');
                break;
            case 'Yearly':
                var ticks = [];
                for(var i = 0; i < 12; i++) {
                    var time = moment().subtract(11-i,'month').format('YYYY-MM');
                    ticks.push(time);
                }

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
                for(var i = 0; i < $scope.filter.during; i++) {
                    var time = moment().subtract($scope.filter.during - 1 - i,'d').format('MM-DD');
                    ticks.push(time);
                }

                if( $scope.filter.during > 45 )
                {
                    toaster.pop('error', MESSAGE_TITLE, "You cannot select days longer than 45 days");
                    return;
                }
                break;
        }

        $scope.chartOptions.axes.xaxis.ticks = ticks;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/statistics',
            data: $scope.filter,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            $scope.showTodayStatistics(response.data);
        }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    $scope.showTodayStatistics = function(data) {
        var s1 = [];
        var s2 = [];
        var ticks = [];

        var count_info = data.count_info;
        for(var i = 0; i < count_info.length; i++)
        {
            s1[i] = count_info[i].ticket_count.business;
            s2[i] = count_info[i].ticket_count.personal;
        }

        $scope.data = [s1, s2];

        var by_count = data.by_called_cnt;
        $scope.by_count = [[]];
        for(var i = 0; i < by_count.length; i++)
            $scope.by_count[0].push([by_count[i].called_no, by_count[i].cnt]);

        if( $scope.by_count[0].length == 0 )
            $scope.by_count[0].push(['No Data', 0]);

        var by_cost = data.by_cost;
        $scope.by_cost = [[]];
        for(var i = 0; i < by_cost.length; i++)
            $scope.by_cost[0].push([by_cost[i].called_no, by_cost[i].cost]);

        if( $scope.by_cost[0].length == 0 )
            $scope.by_cost[0].push(['No Data', 0]);
    }

    $scope.getCallStatistics();

});




