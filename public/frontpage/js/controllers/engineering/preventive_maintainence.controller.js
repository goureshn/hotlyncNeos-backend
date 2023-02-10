app.controller('preventiveMaintainenceController', function($scope, $rootScope, $http,  $timeout,  $uibModal,  $window, $interval, $httpParamSerializer, toaster, AuthService, GuestService) {

    var MESSAGE_TITLE = 'Preventive Maintenance';
    $scope.$watch('vm.daterange', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;
        $scope.getDataList();
    });

    $scope.preventive = {};

    $scope.isLoading = false;

    $scope.auth_svc = AuthService;

    $scope.total_selected = false;

    $scope.preventive_types = ['Major','Minor','Check'];
    $scope.preventive_frequency_units = [
        'Days',
        'Weeks',
        'Months',
        'Years'
    ];    

    $scope.start_mode_list = [
        'Due Date',
        'Beginning of Week',
        'Beginning of Month',
    ];

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'PM00000';
        return sprintf('PM%05d', ticket.id);
    }


    // $scope.getEquipmentOrGroupList = function (val) {
    //     if( val == undefined )
    //         val = "";        

    //     return promiss = $http.get('/frontend/eng/getequipmentorgrouplist?name='+val)
    //         .then(function(response){
    //             return response.data;
    //         });
    // }

    //datr filter option
    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };
    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD ') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.start_time =  picker.startDate.format('YYYY-MM-DD HH:mm:ss');
        $scope.end_time = picker.endDate.format('YYYY-MM-DD HH:mm:ss');
        $scope.time_range = $scope.start_time + ' - ' + $scope.end_time;
        $scope.getDataList();
    });
    //  pagination

    $scope.tableState = {};
    $scope.tableState.pagination = {};
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
        request.attendant = profile.id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        if($scope.start_time == undefined) $scope.start_time = $scope.dateRangeOption.startDate;
        if($scope.end_time == undefined) $scope.end_time = $scope.dateRangeOption.endDate;
        request.start_date = $scope.start_time;
        request.end_date = $scope.end_time;
        request.searchtext = $scope.searchtext;
        request.type_array = $scope.filter.type_tags.map(item => item.text);
        request.equip_group_array = $scope.filter.equipment_tags;
        request.staff_array = $scope.filter.staff_tags;
        request.status_array = $scope.filter.status_tags.map(item => item.id);
        request.inspection = $scope.filter.inspection;

        $scope.filter_apply = $scope.filter.type_tags.length > 0 ||                                     
                                    $scope.filter.equipment_tags.length > 0 ||
                                    $scope.filter.staff_tags.length > 0 ||
                                    $scope.filter.status_tags.length > 0 || 
                                    $scope.filter.inspection > 0;


        $http({
            method: 'POST',
            url: '/frontend/eng/getpreventivemaintenancelist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist.map(row => {
                    row.part_names = row.parts.map(item => item.name + '_' + item.quantity).join(',');
                    row.staff_names = row.staffs.map(item => item.name).join(',');
                    return row;
                });

                $scope.total_selected = false;
                $scope.selected_count = 0;

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

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onChangeSelected = function() {
        var selected_count = 0;
        for(var i = 0; i < $scope.datalist.length; i++) {
            if( $scope.datalist[i].selected )
                selected_count++;
        }

        $scope.selected_count = selected_count;
    }

    $scope.onChangeTotalSelected = function() {
        for(var i = 0; i < $scope.datalist.length; i++) {
            $scope.datalist[i].selected = $scope.total_selected;
        }

        $scope.onChangeSelected();
    }

    function add(data) {
        var request = {};
        request.id = data.id;
        if(data.id == undefined) 
            request.id = 0;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.approver = profile.id;

        request.type = data.type;
        request.name = data.name;
        request.equip_id = data.equip_id;
        request.equip_type = data.equip_type;
        request.checklist_id = data.checklist_id;
        request.description = data.description;

        request.parts = data.parts;

        if(data.staffs)
            request.staffs = data.staffs;

        request.start_mode = data.start_mode;
        request.frequency = data.frequency;
        request.frequency_unit = data.frequency_unit;
        request.start_date = data.start_date;

        request.inspection = data.inspection ? 1 : 0;        
        request.user_group_ids = data.user_group_tags.map(item => item.id).join(",");
        request.sms = data.sms ? 1 : 0;
        request.email = data.email ? 1 : 0;
        request.reminder = data.reminder;

        $http({
            method: 'POST',
            url: '/frontend/eng/createpreventivemaintenance',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {

                if(response.data.id == 0)
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Failed!');
                }
                else if($scope.action_button == 'Update')
                    toaster.pop('success', MESSAGE_TITLE, 'Tasks have been updated successfully');
                else
                    toaster.pop('success', MESSAGE_TITLE, 'Tasks have been created successfully');
                
                $scope.getDataList();
                getPartGroupList();

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.delete = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete this entry? Please note that this action cannot be undone.';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');   

                    deleteRow(row);
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });  
        
        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                callback(row);         
        }, function () {

        });   
        
    }

    function deleteRow(row)
    {
        var request = {};
        request.id = row.id;

        $http({
            method: 'DELETE',
            url: '/frontend/eng/deletepreventivemaintenance',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Tasks have been deleted successfully');                
                $scope.getDataList();
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.edit = function(row) {        
        $scope.onShowEditDialog(row);
    }

    $scope.searchtext = '';
    $scope.onSearch = function() {
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getDataList();
    }

    $scope.onShowEditDialog = function(data) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/preventive/preventive_create_dialog.html',
            controller: 'PreventiveEditDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                preventive: function () {
                    return data;
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

    //-----------------------------------------------------------------------//

   
    $scope.part_group_list = [];
    function getPartGroupList()
    {
        $http.get('/frontend/equipment/partgrouplist')
            .then(function(response){
                $scope.part_group_list = response.data;
            });
    }

    getPartGroupList();
   
   
    $scope.filter = {};
    $scope.filter.type_tags = [];    

    $scope.type_list = [
        'Check',
        'Major',
        'Minor',
    ];

    $scope.typeTagFilter = function(query) {
        return $scope.type_list.filter(function(item) {
            return item.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // Equipment Filter
    $scope.equip_group_list = [];

    function getEquipmentOrGroupList() {
        $http.get('/frontend/eng/getequipmentorgrouplist')
            .then(function(response){
                $scope.equip_group_list = response.data.map(item => {
                    item.label = item.name + '-' + item.type;
                    return item;
                });
            });
    }

    getEquipmentOrGroupList();

    $scope.filter.equipment_tags = [];    
    $scope.equipmentTagFilter = function(query) {
        return $scope.equip_group_list.filter(function(item) {
            return item.label.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }    

    // Staff Filter
    $scope.staff_list = [];
    
    function getStaffList() 
    {      
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        $http.get('/frontend/eng/getstaffgrouplist?property_id='+property_id)
            .then(function(response){
                $scope.staff_list = response.data.content.map(item => {
                    item.text = item.name + '-' + item.label;
                    return item;
                });             
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    };

    getStaffList();

    $scope.filter.staff_tags = [];    
    $scope.staffTagFilter = function(query) {
        return $scope.staff_list.filter(function(item) {
            return item.text.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // status filter   
    $scope.preventive_status_list = [];
    $http({
        method: 'GET',
        url: '/frontend/eng/getPreventivestatusList',
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    })
        .then(function(response) {
            console.log(response);
            $scope.preventive_status_list = response.data.datalist;

        }).catch(function(response) {

    })
        .finally(function() {
        });

        
    $scope.filter.status_tags = [];    
    $scope.statusTagFilter = function(query) {
        return $scope.preventive_status_list.filter(function(item) {
            return item.status_name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }    

    // User group

    function getUserGroupList() 
    {      
        var profile = AuthService.GetCredentials();
        $http.get('/list/usergroup?property_id=' + profile.property_id)
                .then(function (response) {
                    $scope.user_group_list = response.data;
                });    
    };

    getUserGroupList();

    $scope.usergroupTagFilter = function(query) {
        return $scope.user_group_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // inspection filter
    $scope.inspection_list = [
        {id: 0, name: 'All'},
        {id: 1, name: 'Yes'},
        {id: 2, name: 'No'},        
    ];

    $scope.filter.inspection = 0;    

    $scope.selected = [];
    $scope.onGenerateWO = function () {
      
        var selected_arr = [];
        $scope.selected = $scope.datalist.filter(function (item) {
            return item.selected;
        });
        selected_arr = $scope.selected;
        //window.alert(JSON.stringify(selected));

        $scope.createMultipleWO(selected_arr);
    }
    $scope.createMultipleWO = function (selected) {
        var request = {};
        request.pms = [];
        $scope.CurrentDate = new Date();
        var profile = AuthService.GetCredentials();

        for (var i = 0; i < selected.length; i++) {
            var pm = selected[i];
          
            var data = {};
            data.id = pm.id;
           
           
            request.pms.push(data);
        }
 
        $http({
            method: 'POST',
            url: '/frontend/eng/createworkordermanual',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.getDataList();
                toaster.pop('success', MESSAGE_TITLE, 'Work Orders have been generated successfully');
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {

            });
    }

    
});

app.controller('PreventiveEditDialogCtrl', function($scope, $http, AuthService, $uibModalInstance, $uibModal, preventive, toaster) {
    var MESSAGE_TITLE = 'Preventive Maintenance';

    $scope.datetime = {};
    
    if( !preventive || !(preventive.id > 0) )
    {
        $scope.preventive = {};
        $scope.preventive.type = $scope.preventive_types[0];
        $scope.preventive.name = '';
        $scope.preventive.equip_id = 0;
        $scope.preventive.equip_name = '';
        $scope.preventive.equip_type = 'group';
        $scope.preventive.checklist_id = 0;
        $scope.preventive.checklist_name = '';
        $scope.preventive.description = '';
        $scope.preventive.start_mode = $scope.start_mode_list[0];
        $scope.preventive.frequency = 0;
        $scope.preventive.frequency_unit = $scope.preventive_frequency_units[0];

        $scope.preventive.start_date = moment().format('YYYY-MM-DD');

        $scope.preventive.inspection = true;
        $scope.preventive.sms = false;
        $scope.preventive.email = false;
        $scope.action_button = 'Add';        
        $scope.preventive.parts = [];
        $scope.preventive.staffs = [];
        $scope.preventive.user_group_ids = '';
        $scope.preventive.user_group_tags = [];
        $scope.preventive.reminder = 0;
    }
    else
    {
        $scope.preventive = angular.copy(preventive);        

        $scope.preventive.inspection = preventive.inspection == 1 ? true : false;
        $scope.preventive.sms = preventive.sms == 1 ? true : false;
        $scope.preventive.email = preventive.email == 1 ? true : false;
    }

    
    $scope.$watch('datetime.start_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.preventive.start_date = moment(newValue).format('YYYY-MM-DD');
    });
  

    $scope.onEquipmentOrGroupSelect = function (equipment, $item, $model, $label) {
        $scope.preventive.equip_id = $item.id;
        $scope.preventive.equip_name = $item.name;
        $scope.preventive.equip_type = $item.type;
    };

    $scope.getCheckListFromPreventive = function (val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        var id = $scope.preventive.equip_id;
        var type = $scope.preventive.equip_type;
        return promiss = $http.get('/frontend/eng/getchecklistfrompreventive?name='+val+
                    "&property_id="+property_id+"&id="+id+"&type="+type)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    }
    
    $scope.onChecklistFromPreventiveSelect = function (equipment, $item, $model, $label) {
        $scope.preventive.checklist_id = $item.id;
        $scope.preventive.checklist_name = $item.name;
    };    

    $scope.onPartGroupSelect = function (equipment, $item, $model, $label) {
        $scope.part_id =  $item.id;
        $scope.part_name = $item.name;
        $scope.part_stock = $item.quantity;
        $scope.part_type = $item.type;
    };

   
    $scope.part_init = function(){
        $scope.part_id = 0;
        $scope.part_name = '';
        $scope.part_quantity = 0;
        $scope.part_stock = 0;
        $scope.part_type = '';
    }
    
    $scope.part_init();

    $scope.createParts = function () {
        if($scope.part_quantity > $scope.part_stock) {
            toaster.pop('error', MESSAGE_TITLE, 'A quantity of part can not big than the stock' );
            return;
        }
        if($scope.part_quantity == 0) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter quantity of part.' );
            return;
        }
        var part = {};
        part.id = $scope.part_id;
        part.name = $scope.part_name;
        part.quantity = $scope.part_quantity;
        part.type = $scope.part_type;
        $scope.preventive.parts.push(part);
        $scope.part_init();
    }

    $scope.removeParts = function(row) {
        for( var i= 0 ; i< $scope.preventive.parts.length ; i++) {
            if(row.id ==  $scope.preventive.parts[i].id) {
                $scope.preventive.parts.splice(i, 1);
                break;
            }
        }
    }
    
    $scope.ok = function() {
        if( $scope.preventive.name.length < 1 || $scope.preventive.equip_type.length < 1 )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please set preventive name' );
            return;
        }

        if($scope.preventive.equip_id == 0) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter equipment' );
            return;
        }
  
        if($scope.preventive.parts == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter correct part' );
            return;
        }

        $uibModalInstance.close($scope.preventive);
    }

    $scope.cancel = function() {
        $uibModalInstance.dismiss();        
    }
});