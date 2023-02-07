app.controller('AlarmSettingController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Alarm Setting Page';
    
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    $scope.set = {};
    function initData() {
        $scope.id = 0;        
        $scope.show_alarm = false;
        $scope.show_alarm_list = true;
        $scope.alarm_name = '';
        $scope.alarm_id = 0;
        $scope.trigger1_action = 'IVR';
        $scope.set.percent = '60';
        $scope.set.trigger1_time = 1; //1 min
        $scope.set.trigger1_loop = 1 ;
        $scope.set.trigger1_duration = 1;//6s
        $scope.trigger1_flag = true; 
        $scope.trigger2_action = 'IVR';
        $scope.set.trigger2_time = 5; // 5 min
        $scope.trigger2_flag = false; 
        $scope.action_button = 'Add';  
        $scope.trigger1_sg_flag = false;
        $scope.trigger1_unack_flag = false;
        $scope.user_name_list =[];
        $scope.trigger1_retry_flag = true;
    }
  
    initData();

    var user_list = [];
    $http.get('/list/user')
        .then(function (response) {
            user_list = response.data;
        });

    $scope.user_name_list =[];
    $scope.loadFiltersUser = function (query) {       
        $scope.wholename = user_list.filter(function (item) {
            if (item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1)
                return item.wholename;
        });      
        $scope.users = $scope.wholename.map(function (tag) { 
            return tag;
        });
        return $scope.users;
    }
    
    $scope.trigger1_action_types = [];
    $scope.trigger2_action_types = [];
    $scope.actiontypes = [
        {id: 'SMS', label: 'SMS'},
        {id: 'Mobile', label: 'Mobile'},
        {id: 'Email', label: 'Email'},
        {id: 'IVR', label: 'IVR'},
        {id: 'Webpush', label: 'Webpush'},
        {id: 'Desktop', label: 'Desktop'},
        {id: 'WhatsApp', label: 'WhatsApp'},      
    ];
        

    $scope.actiontypes_hint = {buttonDefaultText: 'Select Type'};
    $scope.actiontypes_hint_setting = {
        smartButtonMaxItems: 1,
        smartButtonTextConverter: function(itemText, originalItem) {
            return itemText;
        }
    };    

    $scope.getAlarmGroupList = function(val) {
        if( val == undefined ) val = '';
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return $http.get('/frontend/alarm/setting/getalarmgrouplist?val=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.viewTrigger = function(option){
        if(option == '1') {
            $scope.trigger1_flag = true;
            $scope.trigger2_flag = false;          
        }
        if(option == '2') {
            $scope.trigger2_flag = true;
            $scope.trigger1_flag = false;            
        }
    }

    $scope.viewSameGroup = function() {
        if($scope.trigger1_sg_flag == true) {
            $scope.trigger1_sg_flag = false;
            //$scope.trigger1_unack_flag = false;
        }else {
            $scope.trigger1_sg_flag = true;
            $scope.user_name_list =[];
        }
    }

    $scope.viewSameUnack = function() {
        if($scope.trigger1_unack_flag == true) {
            $scope.trigger1_unack_flag = false;
            //$scope.trigger1_unack_flag = false;
        }else {
            $scope.trigger1_unack_flag = true;         
        }
    }

    $scope.onGroupSelect = function (item, $model, $label) {
        $scope.alarm_group = item;
        $scope.alarm_id = item.id;
    };

    var alarm_list = [];
    $http.get('/list/alarmgroup?property_id='+property_id)
        .then(function (response) {
            alarm_list = response.data;
        });
    
    $scope.alarm_name_list =[];
    $scope.loadFiltersAlarm = function (query) {
        
        $scope.alarmname = alarm_list.filter(function (item) {
            if (item.name.toLowerCase().indexOf(query.toLowerCase()) != -1)
                return item.name;
        });
        
        $scope.alarms = $scope.alarmname.map(function (tag) { 
            return tag;
        });
        return $scope.alarms;
    }
    
  
    $scope.getDataList = function getDataList(tableState) {

        $scope.isLoading = true;
        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? 'id' : 'id' ;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getsettinglist',
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

                console.log(response);
            }).catch(function(response) {
                console.error('Alarm error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.alarmSettingAdd = function() {
        console.log($scope.action_types);
        var data = {};
        data.property_id = property_id;
        data.id = $scope.id;       
        data.alarm_id = $scope.alarm_id;        
        data.trigger1_action = $scope.trigger1_action;
        var t1_action_types = [];
        for(var i =0; i < $scope.trigger1_action_types.length;i++) {
            t1_action_types.push($scope.trigger1_action_types[i].id);
        }
        data.trigger1_action_types = t1_action_types;
        data.percent = $scope.set.percent;
        data.trigger1_time = $scope.set.trigger1_time;        
        data.trigger1_flag = $scope.trigger1_flag;
        data.trigger1_sg_flag = $scope.trigger1_sg_flag;
        data.trigger1_unack_flag = $scope.trigger1_unack_flag;
        var user_ids = [];
        for(var i =0; i < $scope.user_name_list.length;i++) {
            user_ids.push($scope.user_name_list[i].id);
        }   
        data.user_ids = user_ids; 
        data.trigger1_retry_flag = $scope.trigger1_retry_flag;  
        data.trigger1_duration = $scope.set.trigger1_duration;
        data.trigger1_loop = $scope.set.trigger1_loop;         
        data.trigger2_action = $scope.trigger2_action;   
        var t2_action_types = [];
        for(var i =0; i < $scope.trigger2_action_types.length;i++) {
            t2_action_types.push($scope.trigger2_action_types[i].id);
        }
        data.trigger2_action_types = t2_action_types;     
        data.trigger2_time = $scope.set.trigger2_time;        
        data.trigger2_flag = $scope.trigger2_flag;      
        var alarm_ids = [];
        for(var i =0; i < $scope.alarm_name_list.length;i++) {
            alarm_ids.push($scope.alarm_name_list[i].id);
        }
        data.alarm_ids = alarm_ids;  

        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/createalarmsetting',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if(response.data.code == '200') {
                    if($scope.id > 0) {
                        toaster.pop('success', MESSAGE_TITLE, 'Alarm setting has been updated successfully.');
                        $scope.id = 0 ;
                    }else {
                        toaster.pop('success', MESSAGE_TITLE, 'Alarm setting has been created successfully.');
                    }
                    $scope.alarmCancel();
                    $scope.getDataList();
                }
                if(response.data.code == '201') {
                    toaster.pop('error', MESSAGE_TITLE, 'This alarm is already registered.');
                }
                console.log(response);

            }).catch(function(response) {
               // console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.alarmCancel = function() {
        initData();
    }

    $scope.editItems = function(row) {
        $scope.trigger1_action_types = [];
        $scope.trigger2_action_types = [];
        $scope.id = row.id;
        $scope.show_alarm = true;
        $scope.show_alarm_list = false;
        $scope.alarm_id = row.alarm_id;
        $scope.alarm_name = row.alarm_name;      
        $scope.trigger1_action = row.trigger1_action;  
        var trigger1_action = $scope.trigger1_action.split(",");
        for(var i =0; i < trigger1_action.length;i++) {
            var obj = {'id': trigger1_action[i]};
            $scope.trigger1_action_types.push(obj);
        }
        $scope.set.percent = row.percent;
        $scope.set.trigger1_time = row.trigger1_time;
        var trigger1_flag = false ;
        if(row.trigger1_flag == 1) trigger1_flag = true;   
        $scope.trigger1_flag = trigger1_flag; 
        $scope.set.trigger1_duration = row.trigger1_duration;
        $scope.set.trigger1_loop = row.trigger1_loop;
        var trigger1_sg_flag = false;  
        if(row.trigger1_sg_flag == 1) trigger1_sg_flag = true;   
        $scope.trigger1_sg_flag = trigger1_sg_flag; 
        var trigger1_unack_flag = false;  
        if(row.trigger1_unack_flag == 1) trigger1_unack_flag = true;   
        $scope.trigger1_unack_flag = trigger1_unack_flag; 
        var trigger1_retry_flag = false;  
        if(row.trigger1_retry_flag == 1) trigger1_retry_flag = true;   
        $scope.trigger1_retry_flag = trigger1_retry_flag; 
        
        $scope.trigger2_action = row.trigger2_action;
        var trigger2_action = $scope.trigger2_action.split(",");
        for(var i =0; i < trigger2_action.length;i++) {
            var obj = {'id': trigger2_action[i]};
            $scope.trigger2_action_types.push(obj);
        }
        $scope.set.trigger2_time = row.trigger2_time;
        var trigger2_flag = false ;
        if(row.trigger2_flag == 1) trigger2_flag = true;    
        $scope.trigger2_flag = trigger2_flag;         
        $scope.action_button = 'Update';  
        
        if(row.trigger1_sg_users == '') return;
        var request = {};
        request.user_ids = row.trigger1_sg_users;          
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getsamegroupusers',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.user_name_list = response.data.users;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }


    $scope.deleteItem = function(row) {
        var request = {};
        request.id = row.id;  
        request.property = property_id;      
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/deletealarmsetting',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Alarm Setting have been deleted successfully');
                $scope.alarmCancel();
                $scope.getDataList();

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    /*
    $scope.viewAction = function(name) {
        var alarm_name = '';
        if(name == 'IVR') alarm_name = "IVR";
        if(name == 'GROUP') alarm_name = "Different group";
        if(name == 'REPEAT') alarm_name = "Repeat message";
        return alarm_name ;
    }
    */

});
