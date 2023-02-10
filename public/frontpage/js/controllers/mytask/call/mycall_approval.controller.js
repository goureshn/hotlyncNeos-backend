app.controller('MycallApprovalController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService, uiGridConstants) {
    var MESSAGE_TITLE = 'My Task';

    $scope.table_container_height = 'height: ' + ($window.innerHeight - 200) + 'px; overflow-y: auto;width: '+($window.innerWidth - 260)+ 'px';



    $scope.tableState = undefined;
    $scope.user_info = undefined;

    $scope.filter = {};
    $scope.filter.approval_temp = 'Waiting For Approval';

    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};

        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/approvallist',
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

    $scope.getDetailCallList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isChildLoading = true;

        var request = {};

        var profile = AuthService.GetCredentials();

        if( $scope.user_info )
            request.agent_id = $scope.user_info.id;
        else {
            request.agent_id = 0;
            request.dept_id = profile.dept_id;
        }

        $http({
            method: 'POST',
            url: '/frontend/callaccount/detailcalllist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.detaillist = response.data;
                for(var i = 0; i < $scope.detaillist.length; i++) {
                    $scope.detaillist[i].approval_temp = $scope.detaillist[i].approval + '';
                    $scope.detaillist[i].selected = false;
                }

              //  $scope.filter.approval_temp = 'Waiting For Approval';
                $scope.filter.total_selected = false;
                $scope.selected_count = 0;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isChildLoading = false;
            });
    };

    $scope.approve_types = [
        'Waiting For Approval',
        'Approve',
        'Return',
        'Reject',
        'Closed'
    ];

    $scope.showUserCallList = function(user) {
        console.log(user);
        $scope.user_info = user;
        $scope.getDetailCallList();
    }

    $scope.$on('onSelectUser', function(event, args){
        $scope.showUserCallList(args);
    });

    $scope.getDate = function(row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function(duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    $scope.onChangeApproval = function(row) {
        if( row.approval_temp == 'Return' || row.approval_temp == 'Reject' )
        {
            var size = 'lg';
            var modalInstance = $uibModal.open({
                templateUrl: 'returnReasonModal.html',
                controller: 'ReturnReasonController',
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

            }, function () {

            });
        }
    }

    $scope.onChangeTotalApproval = function() {
        if( $scope.filter.approval_temp == 'Return' || $scope.filter.approval_temp == 'Reject' )
        {
            var row = {};
            var size = 'lg';
            var modalInstance = $uibModal.open({
                templateUrl: 'returnReasonModal.html',
                controller: 'ReturnReasonController',
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

                for(var i = 0; i < $scope.detaillist.length; i++) {
                    if( $scope.detaillist[i].selected &&
                        $scope.detaillist[i].approval == 'Waiting For Approval' ) {
                        $scope.detaillist[i].approval_temp = $scope.filter.approval_temp;
                        $scope.detaillist[i].comment = comment + "";
                    }
                }

            }, function () {

            });
        }
        else
        {
            for(var i = 0; i < $scope.detaillist.length; i++) {
                if( $scope.detaillist[i].selected &&
                    $scope.detaillist[i].approval == 'Waiting For Approval' ) {
                    $scope.detaillist[i].approval_temp = $scope.filter.approval_temp;
                }
            }
        }
    }

    $scope.approveCalls = function() {
        var request = {};

        request.calls = [];

        var profile = AuthService.GetCredentials();

        for (var i = 0; i < $scope.detaillist.length; i++) {
            var call = $scope.detaillist[i];
            if (call.approval == call.approval_temp)
                continue;

            var data = {};
            data.id = call.id;
            data.approver = profile.id;

            if( call.approval_temp == 'Waiting For Approval' )
                data.approval = 'Waiting For Approval';
            if( call.approval_temp == 'Approve' )
                data.approval = 'Approved';
            if( call.approval_temp == 'Return' )
                data.approval = 'Returned';
            if( call.approval_temp == 'Reject' )
                data.approval = 'Rejected';
            if( call.approval_temp == 'Closed' )
                data.approval = 'Close';

            if( data.approval == 'Return' )
                data.classify = 'Unclassified';
            else
                data.classify = call.classify;

            data.comment = call.comment;

            request.calls.push(data);
        }

        $http({
            method: 'POST',
            url: '/frontend/callaccount/approvecall',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.getDataList();
                $scope.getDetailCallList();
                $scope.$emit('refreshCall', '');
            }).catch(function (response) {

            })
            .finally(function () {

            });
    }
    $scope.onChangeSelected = function() {
        var selected_count = 0;
        for(var i = 0; i < $scope.detaillist.length; i++) {
            if( $scope.detaillist[i].selected )
                selected_count++;
        }

        $scope.selected_count = selected_count;
    }

    $scope.onChangeTotalSelected = function() {
        for(var i = 0; i < $scope.detaillist.length; i++) {
            $scope.detaillist[i].selected = $scope.filter.total_selected;
        }

        $scope.onChangeSelected();
    }

    $scope.onChangeDepartTotalSelected = function() {
        for(var i = 0; i < $scope.datalist.length; i++) {
            $scope.datalist[i].depart_selected = $scope.filter.depart_selected;
        }
    }

    $scope.onApproveReject = function (part,type) {
        var part = part;
        var type = type;
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        var submitter_ids = [] ;
        var call_ids = [];
        if( part == 'agent') {
            for(var i = 0; i < $scope.datalist.length; i++) {
                if($scope.datalist[i].depart_selected == true){
                    submitter_ids.push($scope.datalist[i].id);
                }
            }
            if(submitter_ids.length < 1) return ;
        }
        if(part == 'date') {
            for(var i = 0; i < $scope.detaillist.length; i++) {
                if($scope.detaillist[i].selected == true){
                    call_ids.push($scope.detaillist[i].id);
                }
            }
            if(call_ids.length < 1) return ;
        }
        request.part = part;
        request.type = type;
        submitter_ids = JSON.stringify(submitter_ids);
        request.submitter_ids = submitter_ids;
        call_ids = JSON.stringify(call_ids);
        request.call_ids = call_ids;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/approvalapprove',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getDetailCallList();
                toaster.pop('success', MESSAGE_TITLE, ' has been updated successfully');
                // $scope.selected_count = 0;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to update');
            })
            .finally(function() {
                $scope.isChildLoading = false;
            });
    }

});

app.controller('ReturnReasonController', function ($scope, $uibModalInstance, $http, call) {
    $scope.call = call;
    $scope.call.comment_content = '';

    $scope.send = function () {
        $uibModalInstance.close($scope.call.comment_content);
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