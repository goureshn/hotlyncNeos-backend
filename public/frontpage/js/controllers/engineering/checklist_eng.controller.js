app.controller('ChecklistEngController', function($scope, $http, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Check List Page';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.work_order_types = [
        'Repairs',
        'Requests',
        'Preventive',
        'Upgrade',
        'New',
    ];
    $scope.getEquipGroupList = function(val) {
        if( val == undefined )
            val = "";
        return promiss = $http.get('/frontend/equipment/grouplist?group_name='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onEquipGroupSelect = function (equipment, $item, $model, $label) {
        var equipments = {};
        $scope.equip_group_id = $item.id;
        $scope.equip_group_name = $item.name;
    };

    var location_list = [];
    function getTotalLocationList() 
    {
        var profile = AuthService.GetCredentials();
        $http.get('/list/locationtotallist?client_id=' + profile.client_id)
            .then(function(response){
                location_list = response.data.map(item => {
                    item.name_type = item.name + '-' + item.type;
                    return item;
                });
            });
    }
    getTotalLocationList();

    $scope.getLocationList = function(query) {
        if( query == undefined )
            query = "";

        return location_list.filter(item => item.name.toLowerCase().includes(query.toLowerCase())).slice(0, 10);                
    };


    $scope.total_room_type = [];
    function initData() {
        $scope.id = 0;
        $scope.check_list_name = '';
        $scope.equip_group_id = 0;
        $scope.equip_group_name = '';
        $scope.work_order_type = $scope.work_order_types[0];
        $scope.location_tags = [];
        $scope.action_button = 'Add';
    }

    initData();

    $scope.getCheckListItemFilter = function(query) {
        if( query == undefined )
            query = "";

        return $scope.check_list_items.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.add = function() {
        var request = {};

        request.id = $scope.id;

        var profile = AuthService.GetCredentials();

        request.property_id = profile.property_id;
        request.name = $scope.check_list_name;
        request.equip_group_id = $scope.equip_group_id;
        request.work_order_type = $scope.work_order_type;

        if( request.name == '' )
            return;
      
        var location_type = [];
        for(var i = 0; i < $scope.location_tags.length; i++) {
            var location = {};
            location = {'id':$scope.id,
                'location_id':$scope.location_tags[i].id
            } ;
            location_type.push(location);
        }

        request.location = JSON.stringify(location_type);

        $http({
            method: 'POST',
            url: '/frontend/equipment/createequipcheckList',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Check list have been created successfully');
                $scope.cancel();
                $scope.getDataList();

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.cancel = function() {
        initData();
    }

    $scope.edit = function(row) {
        $scope.id = row.id;
        $scope.check_list_name = row.name;
        $scope.location_tags = row.locations;
        $scope.equip_group_id = row.equip_group_id;
        $scope.equip_group_name = row.equip_group_name;
        $scope.work_order_type = row.work_order_type;

        $scope.action_button = 'Update';
    }

    $scope.delete = function(row) {
        var request = {};
        request.id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/equipment/deletequipchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Tasks have been deleted successfully');
                $scope.cancel();
                $scope.getDataList();
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


    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        var profile = AuthService.GetCredentials();
        request.dept_id = profile.dept_id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/equipment/getengchecklistnames',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                $scope.check_list_items = response.data.check_list_items;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
 
    $scope.editCheck = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/preventive/checklist_create_dialog.html',
            controller: 'ChecklistEditDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                checklist: function () {
                    return row;
                },                
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                add(data);
            }
        }, function () {

        });
    }
});


app.controller('ChecklistEditDialogCtrl', function($scope, $http, AuthService, $uibModalInstance, $uibModal, checklist, toaster) {
    var MESSAGE_TITLE = 'Preventive Maintenance';

    $scope.checklist = angular.copy(checklist);
    $scope.item_list = [];

    $scope.item = {};

    $scope.type_list = [
        'Yes/No',        
        'Reading',
        'Others',
    ];

    function init()
    {
        $scope.item.id = 0;
        $scope.item.name = '';
        $scope.item.type = $scope.type_list[0];
    }

    init();

    function getCategoryList()
    {
        var request = {};
        request.checklist_id = $scope.checklist.id;

        $http({
            method: 'POST',
            url: '/frontend/equipment/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.category_list = response.data.content;      
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    getCategoryList();

    $scope.onCategorySelect = function($item, $model, $label)
    {
        $scope.item.category_id = $item.id;        
        $scope.item.order_id = $item.order_id;
    }

    function getItemList()
    {
        var request = {};
        request.checklist_id = $scope.checklist.id;

        $http({
            method: 'POST',
            url: '/frontend/equipment/getchecklistitemlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.item_list = response.data.list;      
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    getItemList();
    
    $scope.onAddCategory = function(name)
    {
        var request = {};
        request.checklist_id = $scope.checklist.id;
        request.name = name;
        request.order_id = $scope.item.order_id;

        $http({
            method: 'POST',
            url: '/frontend/equipment/createchecklistcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.category_list = response.data.list;
                $scope.item.category_id = response.data.id;
                $scope.item.category_name = response.data.name;
                $scope.item.noCategoryResults = false;

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    $scope.onCreateCheckListItem = function()
    {
        var request = {};
        request = angular.copy($scope.item);
        request.checklist_id = $scope.checklist.id;
        if( request.category_id < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select category');
            return;
        }

        if( !request.name )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select name');
            return;
        }


        $http({
            method: 'POST',
            url: '/frontend/equipment/createchecklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.item_list = response.data.list;
                $scope.category_list = response.data.category_list;
                init();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }

    $scope.edit = function(row)
    {
        $scope.item = angular.copy(row);
    }    

    $scope.delete = function(row)
    {
        var request = {};
        request = angular.copy(row);
        request.checklist_id = $scope.checklist.id;
        
        $http({
            method: 'POST',
            url: '/frontend/equipment/deletechecklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.item_list = response.data.list;

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                
            });
    }
    

    $scope.ok = function() {
       
        $uibModalInstance.close($scope.checklist);
    }

    $scope.clear = function() {
        init();            
    }

    $scope.cancel = function() {
        $uibModalInstance.dismiss();            
    }
});
