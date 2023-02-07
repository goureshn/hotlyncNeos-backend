app.controller('LinenSettingController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Linen Setting Page';

    var profile = AuthService.GetCredentials();

    $scope.filter = {};

    $scope.room_type_list = [];
    $scope.vip_list = [];
    $scope.linen_type_list = [];
    function getRoomTypeList() 
    {
        $http.get('/list/roomtype')
            .then(function (response) {
                $scope.room_type_list = response.data;
                $scope.filter.room_type_id = $scope.room_type_list[0].id;

                getLinenlist();
            });
    }

    function getVipList() 
    {
        $http.get('/list/vips')
            .then(function (response) {
                $scope.vip_list = response.data;
                $scope.filter.vip_id = $scope.vip_list[0].id;

                getLinenlist();
            });
    }

    function getLinenTypeList() 
    {
        $http.get('/list/linentype')
            .then(function (response) {
                $scope.linen_type_list = response.data;
                $scope.filter.linen_type = $scope.linen_type_list[0].id;

                getLinenlist();
            });
    }

    getRoomTypeList();
    getVipList(); 
    getLinenTypeList();

    function getLinenlist() 
    {
        var request = {};

        request = $scope.filter;

        console.log(request);
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/getlinensettinglist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.linen_list = response.data;
            console.log($scope.linen_list);
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

    

    $scope.addLinen = function()
    {
        var request = {};

        request.qty = $scope.filter.qty;
        request.user_id = profile.id;
        
        request.vip_id = $scope.filter.vip_id;
        request.room_type_id = $scope.filter.room_type_id;
        request.linen_type = $scope.filter.linen_type;
        
       
        $http({
            method: 'POST',
            url: '/frontend/hskp/createlinensetting',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                toaster.pop('success', MESSAGE_TITLE, 'Linen Setting added successfully');
                $scope.linen_list = response.data.list;
                getLinenlist();
            }
            else
            {
                toaster.pop('info', 'LinenSettinglist', response.data.message);
            }
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };

    

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onEditLinen = function(row)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/linen_setting_edit.html',
            controller: 'LinenEditController',
            scope: $scope,
            resolve: {
                linenlist: function() {
                    return row;
                }
            }
        });

        modalInstance.result.then(function () {
          getLinenlist();
        }, function () {

        });

    }

    
    $scope.onChangeVip = function()
    {
        getLinenlist();
    }

    $scope.onChangeRoomType = function()
    {
        getLinenlist();
    }
    $scope.onChangeLinenType = function()
    {
        getLinenlist();
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


    $scope.onDeleteLinen = function(row) {
        var request = {};
        request.id = row.id;
        
        $http({
            method: 'DELETE',
            url: '/frontend/hskp/removelinensetting',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Linen Setting deleted successfully');
                getRulelist();         
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

   
    /* $scope.getRoomTypes = function(row) {
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
    } */

    
});


app.controller('LinenEditController', function($scope, $uibModalInstance, $http, AuthService, toaster, linenlist) {
    $scope.model = angular.copy(linenlist);

    var MESSAGE_TITLE = 'Linen Setting Page';


    $scope.update = function () {        
        var request = {};

        request = angular.copy($scope.model);
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updatelinensetting',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', MESSAGE_TITLE, 'Linen Setting updated successfully');
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

