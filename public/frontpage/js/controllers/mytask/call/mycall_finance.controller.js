app.controller('MycallFinanceController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService, uiGridConstants) {
    var MESSAGE_TITLE = 'My Task Finance';

    $scope.table_container_height = 'height: ' + ($window.innerHeight - 340) + 'px; overflow-y: auto';

    $scope.selectDepartNum = undefined;
    $scope.selectRowRow = undefined;
    $scope.selectRow = undefined;
    $scope.filter = {};
    $scope.selectedRow = undefined;
    $scope.approve_types = [
        'Waiting For Approval',
        'Approve',
        'Return',
        'Reject',
        'Closed'
    ];
    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};

        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/departlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data;
                $scope.filter.depart_selected = false ;
                for(var i = 0; i < $scope.datalist.length; i++) {
                    $scope.datalist[i].depart_selected = false;
                }

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.getSelectDetailCallList = function (row, index) {
        $scope.selectDepartNum = index;
        $scope.selectRow = row;
        $scope.getDetailCallList(row);
    }

    $scope.getDetailCallList = function getDataList(row) {


        $scope.isChildLoading = true;
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = 0;
        request.dept_id = row.id;
        
        $http({
            method: 'POST',
            url: '/frontend/callaccount/financedetailcalllist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.detaillist = response.data;
                 for(var i = 0; i < $scope.detaillist.length; i++) {
                    $scope.detaillist[i].selected = false;
                    $scope.detaillist[i].downimage = 'glyphicon glyphicon-arrow-down' ;
                     for(var j = 0; j < $scope.detaillist[i].inform.length; j++) {
                         $scope.detaillist[i].inform[j].selected = false;
                     }
                }
                $scope.filter.total_selected = false;

               // $scope.selected_count = 0;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isChildLoading = false;
            });
    };

    $scope.onChangeSelected = function() {
        var selected_count = 0;

        for(var i = 0; i < $scope.detaillist.length; i++) {
            if( $scope.detaillist[i].selected )
                selected_count++;
            var inform = $scope.detaillist[i].inform;
            for(var j = 0; j < inform.length; j++) {
                inform[j].selected = $scope.detaillist[i].selected;
            }

        }
        $scope.selected_count = selected_count;
    }

    $scope.onChangeTotalSelected = function() {
        for(var i = 0; i < $scope.detaillist.length; i++) {
            $scope.detaillist[i].selected = $scope.filter.total_selected;
        }

        $scope.onChangeSelected();
    }

    $scope.onDepartChangeSelected = function() {
        var selected_count = 1;
        for(var i = 0; i < $scope.datalist.length; i++) {
            if( $scope.datalist[i].depart_selected )
                selected_count++;
        }
        $scope.selected_count = selected_count;
    }

    $scope.onChangeDepartTotalSelected = function() {
        for(var i = 0; i < $scope.datalist.length; i++) {
            $scope.datalist[i].depart_selected = $scope.filter.depart_selected;
        }
        $scope.onDepartChangeSelected();
    }


    $scope.selectedIndex = 0;
    $scope.onClickRow = function(row, index) {
        $scope.selectedRow = row ;
        $scope.selectedIndex = index;
        row.collapse = !row.collapse;
        for(var i = 0; i < $scope.detaillist.length; i++)
        {
            if( i == index )
                continue;

            $scope.detaillist[i].collapse = false;
        }
        if($scope.detaillist[index].collapse == true) row.downimage = 'glyphicon glyphicon-arrow-up';
        else row.downimage = 'glyphicon glyphicon-arrow-down';
    }

    $scope.onFinanceClose = function (part) {
        var kind = part;
        var child = ''
        if(part == 'child' || part == 'child_return') kind = 'agent' ;
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        var depart_ids = [];
        var agent_ids = [] ;
        var calls_ids =[];
        if( kind == 'department') {
            for(var i = 0; i < $scope.datalist.length; i++) {
               if($scope.datalist[i].depart_selected == true){
                   depart_ids.push($scope.datalist[i].id);
               }
            }
        }
        if( kind == 'agent') {
            for(var i = 0; i < $scope.detaillist.length; i++) {
                if($scope.detaillist[i].selected == true)
                    agent_ids.push($scope.detaillist[i].submitter);

                var inform = $scope.detaillist[i].inform;
                for(var j=0; j < inform.length ;j++ ) {
                    if(inform[j].selected == true)
                        calls_ids.push(inform[j].id);
                }
            }
        }
        request.kind = kind;
        request.type = part ;
        depart_ids = JSON.stringify(depart_ids);
        agent_ids = JSON.stringify(agent_ids);
        calls_ids = JSON.stringify(calls_ids);
        request.depart_ids = depart_ids;
        request.agent_ids = agent_ids;
        request.calls_ids = calls_ids;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/financedepartclose',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, ' has been updated successfully');
                $scope.getDetailCallList($scope.selectRow);
                $scope.selectedRow.collapse = !$scope.selectedRow.collapse;

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to update');
            })
            .finally(function() {
                $scope.isChildLoading = false;
                if($scope.selectedRow != undefined)
                    $scope.onClickRow( $scope.selectedRow ,$scope.selectedIndex );
            });

    }
});



