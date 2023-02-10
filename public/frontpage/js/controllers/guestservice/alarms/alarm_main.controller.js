app.controller('AlarmMainController', function($scope, $http, $window, $timeout, toaster, AuthService,$uibModal) {
    var MESSAGE_TITLE = 'Alarms';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;

    $scope.tags = [
        { text: 'Tag1' },
        { text: 'Tag2' },
        { text: 'Tag3' }
    ];

    // pip
    $scope.isLoading = false;
    $scope.ticketlist = [];
    $scope.alarm_ten=[];
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
    
      $scope.cancelAlarm = function() {
	    
        $scope.data = {};
        $scope.alarm_group = {};
        $scope.alarm_group.id=" ";
       $scope.users = [];
       $scope.alarm_group_name='';
       //data.notification_group={};
       // $scope.alarmlist={};

        var profile = AuthService.GetCredentials();
          var property_id = profile.property_id;
          $scope.comment='';
          val='';
          }
    getAlarmListTen()
    function getAlarmListTen() {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $http({
                method: 'POST',
                url: '/frontend/guestservice/alarmlistten',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.alarmlist = response.data.alarmlist;
               
                

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
    $scope.onClick=function(alarm_group)
    {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/modal/alarm.html',
            controller: 'AlarmTenController',
            windowClass: 'app-modal-window',
            resolve: {
                alarm_group: function () {
                    return alarm_group;
                }
            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.onClickCustom=function()
    {

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/modal/alarm.html',
            controller: 'AlarmCustomController',
            windowClass: 'app-modal-window',
            
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    $scope.getDate = function(row) {
        return moment(row.time).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.time).format('hh:mm:ss a');
    }

    $scope.getAlarmGroupList = function(val) {
        if( val == undefined )
            val = '';
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/frontend/guestservice/alarmgroup?val=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGroupSelect = function ($item, $model, $label) {
        $scope.alarm_group = $item;

        $http.get("/backoffice/guestservice/wizard/alarmgroup/userlist?alarm_id=" + $scope.alarm_group.id)
            .then(function(response){
                console.log(response.data);

                $scope.users = [];
                for(var i = 0; i < response.data[1].length; i++)
                    $scope.users.push({text: response.data[1][i].name});
            });
    };

    $scope.onSendAlarm = function() {
        var data = {};
        if( $scope.alarm_group == undefined || $scope.alarm_group.id == undefined )
            return;

        data.notification_group = $scope.alarm_group.id;

        var profile = AuthService.GetCredentials();
        data.user_id = profile.id;
        data.message = $scope.comment;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/sendalarm',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code == 200 )
                    toaster.pop('success', MESSAGE_TITLE, 'Alarm message has been sent successfully');
                else
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);

                $scope.paginationOptions.pageNumber = 0;
                $scope.getAlarmList();
                $scope.cancelAlarm();
            }).catch(function(response) {

            })
            .finally(function() {

            });
    }


});

app.controller('AlarmTenController', function ($scope,$http, $uibModalInstance, toaster,AuthService, alarm_group, $filter) {
    $scope.alarm_group = alarm_group;
    var MESSAGE_TITLE = 'Alarms';
    
        $http.get("/backoffice/guestservice/wizard/alarmgroup/userlist?alarm_id=" + $scope.alarm_group.id)
            .then(function(response){
                console.log(response.data);

                $scope.users = [];
                for(var i = 0; i < response.data[1].length; i++)
                    $scope.users.push({text: response.data[1][i].name});
            });
            $scope.onSendAlarm = function() {
                var data = {};
                if( $scope.alarm_group == undefined || $scope.alarm_group.id == undefined )
                    return;
        
                data.notification_group = $scope.alarm_group.id;
        
                var profile = AuthService.GetCredentials();
                data.user_id = profile.id;
                data.message = $scope.comment;
        
                $http({
                    method: 'POST',
                    url: '/frontend/guestservice/sendalarm',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
                        if( response.data.code == 200 )
                            toaster.pop('success', MESSAGE_TITLE, 'Alarm message has been sent successfully');
                        else
                            toaster.pop('error', MESSAGE_TITLE, response.data.message);
        
                            $uibModalInstance.dismiss('cancel');
                    }).catch(function(response) {
        
                    })
                    .finally(function() {
        
                    });
            }
  
    $scope.save = function () {
        $uibModalInstance.close($scope.ticket.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});
app.controller('AlarmCustomController', function ($scope,$http, $uibModalInstance, toaster,AuthService,  $filter) {
    $scope.alarm_group = {};
    $scope.alarm_group.id=0
    $scope.alarm_group.description=''
    var MESSAGE_TITLE = 'Alarms';
    
        $http.get("/backoffice/guestservice/wizard/alarmgroup/userlist?alarm_id=" +  $scope.alarm_group.id)
            .then(function(response){
                console.log(response.data);

                $scope.users = [];
                for(var i = 0; i < response.data[0].length; i++)
                    $scope.users.push({text: response.data[0][i].name});
            });
            $scope.onSendAlarm = function() {
                var data = {};
                if( $scope.alarm_group == undefined || $scope.alarm_group.id == undefined )
                    return;
        
                data.notification_group = $scope.alarm_group.id;
        
                var profile = AuthService.GetCredentials();
                data.user_id = profile.id;
                data.message = $scope.comment;
        
                $http({
                    method: 'POST',
                    url: '/frontend/guestservice/sendalarm',
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .then(function(response) {
                        if( response.data.code == 200 )
                            toaster.pop('success', MESSAGE_TITLE, 'Alarm message has been sent successfully');
                        else
                            toaster.pop('error', MESSAGE_TITLE, response.data.message);
        
                            $uibModalInstance.dismiss('cancel');
                    }).catch(function(response) {
        
                    })
                    .finally(function() {
        
                    });
            }
  
    $scope.save = function () {
        $uibModalInstance.close($scope.ticket.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});