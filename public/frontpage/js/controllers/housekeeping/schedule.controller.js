app.controller('ScheduleController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Schedule Page';

    $scope.filter = {};
   
    $scope.selected_checklist = {};

    $scope.schedule_list = {};

    getSchedulelist();

    function getSchedulelist() 
    {
        var request = {};

        $http({
            method: 'POST',
            url: '/frontend/hskp/getschedulelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.schedule_list = response.data;
           
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

    
    $scope.addSchedule = function()
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/schedule_create.html',
            controller: 'ScheduleCreateController',
            scope: $scope,
            resolve: {
                
            }
        });

        modalInstance.result.then(function (list) {
            $scope.schedule_list = list.map(item => {
                return item;
            });
        }, function () {

        });

    }

    $scope.onEditScheduleList = function(row)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/schedule_edit.html',
            controller: 'SchedulelistEditController',
            scope: $scope,
            resolve: {
                schedulelist: function() {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (list) {
            $scope.schedule_list = list.map(item => {
                return item;
            });
        }, function () {

        });

    }

  
    $scope.onDeleteSchedule = function(row) {
        var request = {};
        request.id = row.id;
        
        $http({
            method: 'DELETE',
            url: '/frontend/hskp/removeschedule',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Schedule deleted successfully');
                getSchedulelist();         
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }


    $scope.isLoading = false;

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


   
   

   

    

   

    
    $scope.getRoomTypes = function(row) {
        return getValuefromID(row.room_type, $scope.total_room_type, 'type');
    }

    

    function getValuefromID(ids, values, key)
    {
        var ids = JSON.parse(ids);
        var result = '';
        var index = 0;
        for(var i = 0; i < ids.length; i++)
        {
            for( var j = 0; j < values.length; j++)
            {
                if( ids[i] == values[j].id )
                {
                    if( index > 0 )
                        result += ', ';
                    result +=  values[j][key];
                    index++;
                    break;
                }
            }
        }

        return result;
    }

    
});

app.controller('ScheduleCreateController', function($scope, $uibModalInstance, $http, AuthService, toaster) {

    var profile = AuthService.GetCredentials();
    var MESSAGE_TITLE = 'Schedule Page';

    $scope.model = {};

    $scope.code_list = [
        'CG1',
        'CG2',
        'CG3'
    ];

    $scope.model.code = $scope.code_list[0];

    $scope.days_list = [
        {id: 0, label: 'Sunday'},
        {id: 1, label: 'Monday'},
        {id: 2, label: 'Tuesday'},
        {id: 3, label: 'Wednesday'},
        {id: 4, label: 'Thursday'},
        {id: 5, label: 'Friday'},
        {id: 6, label: 'Saturday'},
    ];
    $scope.daylist_hint = {buttonDefaultText: 'Select Days'};
    $scope.daylist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };

    $scope.model.days = [];

    $scope.create = function () {        
       
        var request = {};

        request.name = $scope.model.name;
        request.code = $scope.model.code;
        request.days =  $scope.model.days.map(item => item.label).join(","); 
        request.user_id = profile.id;

        console.log($scope.model.days);
        
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/hskp/createschedulelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                $uibModalInstance.close(response.data.list);
                toaster.pop('success', MESSAGE_TITLE, 'Schedule created successfully');
            }
            else
            {
                toaster.pop('info', 'Schedule', response.data.message);
            }
        }).catch(function(response) {
        })
            .finally(function() {

            });
            
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('SchedulelistEditController', function($scope, $uibModalInstance, $http, AuthService, toaster, schedulelist) {
    $scope.model = angular.copy(schedulelist);
    $scope.days = $scope.model.days;

    $scope.change = false;
    var MESSAGE_TITLE = 'Schedule Page';

    console.log($scope.model);

    $scope.code_list = [
        'CG1',
        'CG2',
        'CG3'
    ];

    $scope.days_list = [
        {id: 0, label: 'Sunday'},
        {id: 1, label: 'Monday'},
        {id: 2, label: 'Tuesday'},
        {id: 3, label: 'Wednesday'},
        {id: 4, label: 'Thursday'},
        {id: 5, label: 'Friday'},
        {id: 6, label: 'Saturday'},
    ];
    $scope.daylist_hint = {buttonDefaultText: 'Select Days'};
    $scope.daylist_hint_setting = {
        smartButtonMaxItems: 3,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };

    $scope.model.new_days = [];

    $scope.update = function () {        
        var request = {};

        request = angular.copy($scope.model);
        if ($scope.model.new_days.length > 0 ){
            request.days =  $scope.model.new_days.map(item => item.label).join(","); 
        }
        else{
            request.days = $scope.model.days;
        }
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updateschedule',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', MESSAGE_TITLE, 'Schedule updated successfully');
            $uibModalInstance.close(response.data.list);            
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

