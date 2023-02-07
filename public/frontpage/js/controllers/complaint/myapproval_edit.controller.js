app.controller('MyApprovalEditController', function ($scope, $rootScope, $http, $interval, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Validation';

    $scope.complaint = {};
    $scope.guest_id_editable = false;
    $scope.genders = ['Male', 'Female'];
    $scope.subcomplaint_list = [];
    $scope.exist_subcomplaint_list = [];
    $scope.subcomment_list = [];
    var original_dept_id = 0;

    $scope.init = function(complaint) {
        $scope.complaint = complaint;
        getCommentList();
    }

    function getCommentList(sub) {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.comp_id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getcompensationcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.subcomment_list = response.data;
            for(var i = 0; i < $scope.subcomment_list.length; i++)
            {
                $scope.subcomment_list[i].comment = $scope.subcomment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.onApprove = function() {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;
        request.user_id = profile.id;
        request.comment = $scope.complaint.approve_comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/approve',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('success', MESSAGE_TITLE, 'Compensation is approved');        
            $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onReject = function() {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;
        request.user_id = profile.id;
        request.comment = $scope.complaint.approve_comment;

        if( !request.comment )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please input comment for reject.');
            return;
        }
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/rejectcompensation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('info', MESSAGE_TITLE, 'Compensation is rejected');        
            $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onReturn = function() {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;
        request.user_id = profile.id;
        request.comment = $scope.complaint.approve_comment;

        if( !request.comment )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please input comment for return.');
            return;
        }
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/returncompensation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('info', MESSAGE_TITLE, 'Compensation is returned');        
            $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

   

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

});

