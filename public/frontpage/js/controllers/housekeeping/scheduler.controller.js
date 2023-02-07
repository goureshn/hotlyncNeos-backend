app.controller('SchedulerController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Scheduler Page';

    $scope.hskp_role_list = ['Attendant', 'Supervisor'];
    
    $scope.filter = {};
    $scope.filter.hskp_role = $scope.hskp_role_list[0];
    $scope.selected_checklist = {};

    $scope.room_type_list = [];
    function getRoomTypeList() 
    {
        $http.get('/list/roomtype')
            .then(function (response) {
                $scope.room_type_list = response.data;
                $scope.filter.room_type_id = $scope.room_type_list[0].id;

                getChecklist();
            });
    }

    getRoomTypeList();

    function getChecklist() 
    {
        var request = {};

        request = $scope.filter;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/getchecklistlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.checklist_list = response.data.map(item => {
                item.active = item.active == 'true';
                return item;
            });
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

    $scope.onChangeActive = function(row)
    {
        var request = {};

        request = angular.copy(row);
        request.active = row.active ? 'true' : 'false';
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updatechecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.checklist_list = response.data.list.map(item => {
                item.active = item.active == 'true';
                return item;
            });
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }


    $scope.addChecklist = function()
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/checklist_create.html',
            controller: 'ChecklistCreateController',
            scope: $scope,
            resolve: {
                
            }
        });

        modalInstance.result.then(function (list) {
            $scope.checklist_list = list.map(item => {
                item.active = item.active == 'true';
                return item;
            });;
        }, function () {

        });

    }

    $scope.onEditCheckList = function(row)
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/checklist_edit.html',
            controller: 'ChecklistEditController',
            scope: $scope,
            resolve: {
                checklist: function() {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (list) {
            $scope.checklist_list = list.map(item => {
                item.active = item.active == 'true';
                return item;
            });
        }, function () {

        });

    }

    $scope.onEditCheckListItem = function(row)
    {
        $scope.selected_checklist = angular.copy(row);
        getChecklistGroupList();
    }
    
    function getChecklistGroupList()
    {
        var request = {};

        request.checklist_id = $scope.selected_checklist.id;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/getchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.datalist = response.data;
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

    $scope.onChangeHskpRole = function()
    {
        getChecklist();
    }

    $scope.onChangeRoomType = function()
    {
        getChecklist();
    }

    $scope.addGroup = function()
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/group_edit.html',
            controller: 'GroupEditController',
            scope: $scope,
            resolve: {
                checklist: function() {
                    return $scope.selected_checklist;
                }
            }
        });

        modalInstance.result.then(function (list) {
            $scope.datalist = list;
        }, function () {

        });

    }

    function getCheckListItem()
    {
        $scope.check_list_item = [];

        var request = {};

        request = $scope.filter;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/checklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.check_list_items = response.data;
        }).catch(function(response) {
        })
            .finally(function() {

            });    
    }

    getCheckListItem();

   
    $scope.edit = function(row) {
        $scope.id = row.id;
        $scope.check_list_name = row.name;
        $scope.type_tags = getArrayfromID(row.room_type, $scope.total_room_type);

        $scope.job_role = {};
        $scope.job_role.id = row.jr_id;
        $scope.job_role.job_role = row.job_role;

        $scope.onSelectJobrole($scope.job_role, {}, {});

        $scope.action_button = 'Update';
    }

    $scope.delete = function(row) {
        var request = {};
        request.checklist_id = $scope.selected_checklist.id;
        request.group_id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/hskp/deletechecklistwithgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data;
                toaster.pop('success', MESSAGE_TITLE, 'Check list Group is deleted');                
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


    $scope.onClickRow = function(row, index) {
        row.collapse = !row.collapse;
        for(var i = 0; i < $scope.datalist.length; i++)
        {
            if( i == index )
                continue;

            $scope.datalist[i].collapse = false;
        }
    }

    $scope.onItemSelect = function(row, $item, $model, $label)
    {
        row.weight = $item.weight;
    }

    $scope.addCheckListItem = function(row) {
        if( !row.item_name )
            return;

        var exist = false;
        for(var i = 0; i < row.items.length; i++)
        {
            if( row.items[i].name == row.item_name )
            {
                exist = true;
                break;
            }
        }
        if( exist == true )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Duplicated Names');
            return;
        }

        // find check list item with name

        var item_id = 0;
        for(var i = 0; i < $scope.check_list_items.length; i++)
        {
            if( $scope.check_list_items[i].name == row.item_name )
            {
                item_id = $scope.check_list_items[i].id
                break;
            }
        }

        addCheckListItemToGroup(row, item_id, row.item_name, row.id)        
    }

    function addCheckListItemToGroup(row, item_id, item_name, group_id)
    {
        var request = {};

        request.checklist_id = $scope.selected_checklist.id;        
        request.group_id = group_id;
        request.item_id = item_id;
        request.item_name = item_name;
        request.weight = row.weight;

        $http({
            method: 'POST',
            url: '/frontend/hskp/addchecklistitemtogroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                row.items = response.data.item_list;
                $scope.check_list_items = response.data.total_item_list;

                $scope.cancelItem(row);
                toaster.pop('success', MESSAGE_TITLE, 'Check list Item have been changed successfully');
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.onDeleteCheckListItem = function(row, item) {
        var request = {};

        request.checklist_id = $scope.selected_checklist.id;                
        request.group_id = row.id;
        request.item_id = item.id;
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/removechecklistitemfromgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                row.items = response.data.item_list;
                $scope.cancelItem(row);                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.saveItems = function(row) {
        var request = {};

        request.name_id = row.id;
        request.items = row.items;

        $http({
            method: 'POST',
            url: '/frontend/hskp/postchecklistitems',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.cancelItem(row);
                toaster.pop('success', MESSAGE_TITLE, 'Check list Item have been changed successfully');
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.cancelItem = function(row) {
        row.item_name = '';
        row.weight = '';
    }
    $scope.getRoomTypes = function(row) {
        return getValuefromID(row.room_type, $scope.total_room_type, 'type');
    }

    $scope.onSelectJobrole = function($item, $model, $label) {
        console.log($item);
        $scope.type_tags = [];

        var request = $item;
        $http({
            method: 'POST',
            url: '/frontend/hskp/roomtypelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.room_type = response.data;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
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

    function getArrayfromID(ids, values)
    {
        var ids = JSON.parse(ids);
        var result = [];
        for(var i = 0; i < ids.length; i++)
        {
            for( var j = 0; j < values.length; j++)
            {
                if( ids[i] == values[j].id )
                {
                    result.push(values[j]);
                    break;
                }
            }
        }

        return result;
    }

    $scope.onItemDrop = function (row, item, index, pos) {
        if( !item || item.items )
            return;

  
        var pos1 = row.items.findIndex(row1 => row1.id == item.id);   
        row.items.splice(pos1, 1);
        if( index > pos1 )
            row.items.splice(index - 1, 0, item);
        else
            row.items.splice(index, 0, item);

        var request = {};
        request.checklist_id = $scope.selected_checklist.id;                
        request.group_id = row.id;
        request.id_list = row.items.map(row1 => row1.id).join(",");

        $http({
            method: 'POST',
            url: '/frontend/hskp/reorderchecklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });    
    }

    $scope.onGroupDrop = function(item, index, pos)
    {
        if( !item || !item.items)
            return;

        index--;    

        var pos1 = $scope.datalist.findIndex(row1 => row1.id == item.id);   
        $scope.datalist.splice(pos1, 1);
        if( index > pos1 )
            $scope.datalist.splice(index - 1, 0, item);
        else
            $scope.datalist.splice(index, 0, item);

        var request = {};
        request.checklist_id = $scope.selected_checklist.id;                        
        request.group_id_list = $scope.datalist.map(row1 => row1.id).join(",");

        $http({
            method: 'POST',
            url: '/frontend/hskp/reorderchecklistgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });    
    }
});

app.controller('ChecklistCreateController', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    $scope.model = {};

    $scope.model.active = false;

    $scope.create = function () {        
        var request = {};

        request.name = $scope.model.name;
        request.active = $scope.model.active ? 'true' : 'false';
        request.hskp_role = $scope.filter.hskp_role;
        request.room_type_id = $scope.filter.room_type_id;
        
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/hskp/createchecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                $uibModalInstance.close(response.data.list);
            }
            else
            {
                toaster.pop('info', 'Checklist', response.data.message);
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

app.controller('ChecklistEditController', function($scope, $uibModalInstance, $http, AuthService, toaster, checklist) {
    $scope.model = angular.copy(checklist);

    $scope.model.active = false;

    $scope.update = function () {        
        var request = {};

        request = angular.copy($scope.model);
        request.active = $scope.model.active ? 'true' : 'false';
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/updatechecklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
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

app.controller('GroupEditController', function($scope, $uibModalInstance, $http, AuthService, checklist, toaster) {
    $scope.check_group_list = [];
    $scope.model = {};

    function getGroupList()
    {
        var request = {};

        request.checklist_id = checklist.id;

        $http({
            method: 'POST',
            url: '/frontend/hskp/checklistgrouplist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.check_group_list = response.data;            
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

    getGroupList();

    $scope.createGroup = function () {        
        var request = {};

        request.name = $scope.model.new_name;
        request.checklist_id = checklist.id;
        
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/hskp/createchecklistgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                $scope.model.new_name = '';
                $scope.check_group_list = response.data.list;            
            }
            else
            {
                toaster.pop('info', 'Checklist Group', response.data.message);
            }
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.selectRow = function(row)
    {
        var request = {};

        request.group_id = row.id;
        request.checklist_id = $scope.selected_checklist.id;        
        
        $http({
            method: 'POST',
            url: '/frontend/hskp/createchecklistwithgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $uibModalInstance.close(response.data);           
        }).catch(function(response) {
        })
            .finally(function() {

            });
    }

});
