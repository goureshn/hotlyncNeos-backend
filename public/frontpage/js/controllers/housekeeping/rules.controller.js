app.controller('RulesController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Rules Page';

    var profile = AuthService.GetCredentials();

    
    
    $scope.filter = {};
    
    $scope.selected_checklist = {};

    $scope.room_type_list = [];
    $scope.vip_list = [];
    function getRoomTypeList() 
    {
        $http.get('/list/roomtype')
            .then(function (response) {
                $scope.room_type_list = response.data;
                $scope.filter.room_type_id = $scope.room_type_list[0].id;

                getRulelist();
            });
    }

    function getVipList() 
    {
        $http.get('/list/vips')
            .then(function (response) {
                $scope.vip_list = response.data;
                $scope.filter.vip_id = $scope.vip_list[0].id;

                getRulelist();
            });
    }

    getRoomTypeList();
    getVipList(); 

    function getRulelist() 
    {
        var request = {};

        request = $scope.filter;

        console.log(request);
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/getrulelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.rule_list = response.data;
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

    

    $scope.addRule = function()
    {
        var request = {};

        request.days = $scope.filter.days;
        request.user_id = profile.id;
        
        request.vip_id = $scope.filter.vip_id;
        request.room_type_id = $scope.filter.room_type_id;
        
       
        $http({
            method: 'POST',
            url: '/frontend/hskp/createrulelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                toaster.pop('success', MESSAGE_TITLE, 'Rule created successfully');
                $scope.rule_list = response.data.list;
                getRulelist();
            }
            else
            {
                toaster.pop('info', 'Rulelist', response.data.message);
            }
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };

    

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onEditRule = function(row)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/rule_edit.html',
            controller: 'RuleEditController',
            scope: $scope,
            resolve: {
                rulelist: function() {
                    return row;
                }
            }
        });

        modalInstance.result.then(function () {
          getrulelist();
        }, function () {

        });

    }

    
    $scope.onChangeVip = function()
    {
        getRulelist();
    }

    $scope.onChangeRoomType = function()
    {
        getRulelist();
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


    $scope.onDeleteRule = function(row) {
        var request = {};
        request.id = row.id;

        request.vip_id = row.vip_id;
        request.room_type_id = row.room_type_id;
        
        $http({
            method: 'DELETE',
            url: '/frontend/hskp/removerule',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Rule deleted successfully');
                getRulelist();         
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

   
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


app.controller('RuleEditController', function($scope, $uibModalInstance, $http, AuthService, toaster, rulelist) {
    $scope.model = angular.copy(rulelist);

    var MESSAGE_TITLE = 'Rules Page';


    $scope.update = function () {        
        var request = {};

        request = angular.copy($scope.model);
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updaterule',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', MESSAGE_TITLE, 'Rule updated successfully');
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

