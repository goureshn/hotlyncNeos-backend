app.controller('AlarmGroupController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Alarm Group Setting Page';
    //ChecklistController
    $scope.total_room_type = [];
    $scope.userlist = [];
    
    
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;

    function initData() {
        $scope.group_id = 0;
        $scope.group_name = '';
        $scope.group_description = '';
        $scope.user_name_list =[];
        $scope.alarm_name_list = [];
        $scope.group_notification =[];    
        $scope.group_flag = $scope.flags[0]; 
        $scope.group_permission = $scope.permissions[0];   
        $scope.group_status = [];   
        $scope.max_duration = 10;
        $scope.action_button = 'Add';
    }
  
    $scope.flags = [
        {id:'1', name: 'Enable'},
        {id:'0', name: 'Disable'}];
    $scope.group_flag = $scope.flags[0];    
    
    $scope.permissions = [
        {id:'0', name: 'Read'},
        {id:'1', name: 'Read & Write'}];
    $scope.group_permission = $scope.permissions[0];        

    initData();

    $scope.groupAdd = function() {
        var group_data = {};
        group_data.property = property_id;
        group_data.id = $scope.group_id;
        group_data.name = $scope.group_name;
        if($scope.group_name == '') return false;
        group_data.description = $scope.group_description;
        if($scope.group_description == '') return false;
        group_data.default_notifi = $scope.group_notification;
        if($scope.group_notification == '')  {
            $scope.default_notifi_err = "Select default notification.";
            return false;
        }else {
            $scope.default_notifi_err = "";
        }
        group_data.flag = $scope.group_flag.id;
        group_data.permission = $scope.group_permission.id;        
        group_data.status = $scope.group_status;      
        var user_ids = [];
        for(var i =0; i < $scope.user_name_list.length;i++) {
            user_ids.push($scope.user_name_list[i].id);
        }
        group_data.user_ids = user_ids; 
        var alarm_ids = [];
        for(var i =0; i < $scope.alarm_name_list.length;i++) {
            alarm_ids.push($scope.alarm_name_list[i].id);
        }
        group_data.alarm_ids = alarm_ids;  
        group_data.max_duration = $scope.max_duration;       
       
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/creategroup',
            data: group_data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if($scope.group_id > 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Group list has been updated successfully.');
                }else {
                    toaster.pop('success', MESSAGE_TITLE, 'Group list has been created successfully.');
                }                
                $scope.groupCancel();
                $scope.getDataList();

                console.log(response);

            }).catch(function(response) {
               // console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.groupCancel = function() {
        initData();
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
            url: '/frontend/alarm/setting/getgroup',
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
               // console.error('Alarm error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.editItems = function(row) {
        $scope.group_id = row.id;
        $scope.group_name = row.name;
        $scope.group_description = row.description;  
        $scope.max_duration = row.max_duration;
        
        for(var i =0 ; i <$scope.flags.length ; i++) {
            if($scope.flags[i].id == row.flag)
                $scope.group_flag = $scope.flags[i]; 
        }
        
        for(var i =0 ; i <$scope.permissions.length ; i++) {
            if($scope.permissions[i].id == row.permission)
                $scope.group_permission = $scope.permissions[i]; 
        }

        var status_val = row.status;
        $scope.group_status = status_val.split(',');
        var notifi_val = row.default_notifi;
        $scope.group_notification = notifi_val.split(',');               
        $scope.action_button = 'Update';
        //get users and alarm
        var request = {};
        request.group_id = row.id;  
        request.alarm_id = row.alarm_id;      
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getusers_alarms',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.user_name_list = response.data.users;
                $scope.alarm_name_list =  response.data.alarms;                
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
            url: '/frontend/alarm/setting/deletegroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Group has been deleted successfully');
                $scope.groupCancel();
                $scope.getDataList();

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    
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
    

    /*$scope.alarm = {};
    $scope.onAlarmSelect = function ($item, $model, $label) {
        $scope.alarm = $item;
    };*/
   
});

app.filter('statusname', function() {
    return function(input) {
      input = input || '';
      var out = '';
      var split_ =  input.split(",");      
      for (var i = 0; i < split_.length; i++) {
          if(split_[i] =='1' ) out += 'Acknowledge';
          if(split_[i] =='2' ) out += 'Check';
          if(split_[i] =='3' ) out += 'Clear';
          if(split_.length > (i+1)) out += ', ';
      }
      return out;
    };
  })