app.controller('AlarmDashboardController', function ($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    var MESSAGE_TITLE = 'Alarm Dashboard Page';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
    var user_id = profile.id;

    $scope.model_data = {};
    $scope.image = null;
    $scope.imageFileName = '';
    $scope.uploadme = {};
    $scope.uploadme.src = '';

    $scope.targrt_models = {
        selected: null,
        lists: {"key1": [], "key2": [], "key3": [],"key4": [],"key5": [],"key6": [],
                "key7": [], "key8": [], "key9": [],"key10": [],"key11": [],"key12": [],
                "key13": [], "key14": [], "key15": [],"key16": [],"key17": [],"key18": [],
                "key19": [], "key20": [], "key21": [],"key22": [],"key23": [],"key24": []}
    };

    $scope.$on('reloadDashboard', function (e) {
        setTimeout(function () {
            initData();
            $scope.$apply();
        }, 1000);
    });

    function initData() {
        $scope.dash_id = 0;
        $scope.dash_name = '';
        $scope.dash_description = '';
        $scope.group_list = [];
        $scope.user_name_list =[];
        $scope.alarm_list = [];
        $scope.group_name_list = [];
        $scope.alarmlist_target = [];
        $scope.targrt_models.lists={"key1": [], "key2": [], "key3": [],"key4": [],"key5": [],"key6": [],
        "key7": [], "key8": [], "key9": [],"key10": [],"key11": [],"key12": [],
        "key13": [], "key14": [], "key15": [],"key16": [],"key17": [],"key18": [],
        "key19": [], "key20": [], "key21": [],"key22": [],"key23": [],"key24": []};
        $scope.action_button = 'Add';
        //getUserGroup();
        getAlarmGroup();
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
   
    function getAlarmGroup() {
        $http.get('/list/alarmgroup?property_id=' + property_id)
            .then(function (response) {
                for (var i = 0; i < response.data.length; i++) {
                    response.data[i].backcolor = '#008fff';
                }
                $scope.alarmlist = response.data;
            });
    }

    $scope.alarmlist_target = [];

    // Limit items to be dropped in list1
    $scope.optionsListCond = {
        accept: function (dragEl) {
            // if ($scope.alarmlist_target.length >= 2) {
            //   return false;
            // } else {
            //   return true;
            // }
        }
    };

    $scope.restoreAlarm = function (alarm) {
        var id = alarm.id;
        $scope.alarmlist_target;
        /*for (var i = 0; i < $scope.alarmlist_target.length; i++) {
            if (id == $scope.alarmlist_target[i].id) {
                $scope.alarmlist_target.splice(i, 1);
                $scope.alarmlist.push(alarm);
                return;
            }
        }*/        
        angular.forEach($scope.targrt_models.lists, function (value, key) { 
            var item = value[0];
            if(item) {
                if(id == item.id) {
                    $scope.targrt_models.lists[key] = [];
                    $scope.alarmlist.push(alarm);
                } 
            }
        }); 
    }

    $scope.changeAlarmName = function () {
        var alarm_name = $scope.alarm_name;
        $http.get('/list/alarmgroup?property_id=' + property_id + '&alarm_name=' + alarm_name)
            .then(function (response) {
                for (var i = 0; i < response.data.length; i++) {
                    response.data[i].backcolor = '#008fff';
                }
                $scope.alarmlist = response.data;
            });
    }

    $scope.dashAdd = function () {
        var request = {};
        request.property = property_id;
        request.id = $scope.dash_id;
        request.name = $scope.dash_name;
        $scope.dash_name_err = '';
        if($scope.dash_name == ''){
            $scope.dash_name_err = 'Enter dashboard title.';
            return false;
        } 
        request.description = $scope.dash_description;
        $scope.dash_description_err = '';
        if($scope.dash_description == ''){
            $scope.dash_description_err = 'Enter dashboard description';
            return false;
        }
        /*var group_ids = [];
        for (var i = 0; i < $scope.group_name_list.length; i++) {
            group_ids.push($scope.group_name_list[i].id);
        }
        request.group_ids = group_ids;*/
        var user_ids = [];
        for(var i =0; i < $scope.user_name_list.length;i++) {
            user_ids.push($scope.user_name_list[i].id);
        }
        $scope.user_list_err = '';
        if(user_ids.length <= 0){
            $scope.user_list_err = 'Select user list';
            return false;
        }
        request.user_ids = user_ids;
        request.alarm_ids = $scope.alarmlist_target;
        $scope.alarm_list_err = '';
        /*if(request.alarm_ids.length <= 0){
            $scope.alarm_list_err = 'Choose and move to right side.';
            return false;
        }*/
        request.target_alarms = JSON.stringify($scope.targrt_models.lists);

        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/createdash',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if($scope.dash_id > 0) {
                    toaster.pop('success', MESSAGE_TITLE, 'Dashboard has been updated successfully.');
                }else {
                    toaster.pop('success', MESSAGE_TITLE, 'Dashboard has been created successfully.');
                }
                $scope.dashCancel();
                $scope.getDashList();

                console.log(response);

            }).catch(function (response) {
                // console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.dashCancel = function () {
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
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.getDashList = function getDataList(tableState) {

        $scope.isLoading = true;
        if (tableState != undefined) {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate ? 'id' : 'id';
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getdash',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if (tableState != undefined)
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function (response) {
              //  console.error('Alarm error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.editItems = function (row) {
        $scope.dash_id = row.id;
        $scope.dash_name = row.name;
        $scope.dash_description = row.description;
        $scope.alarmlist = [];
        $scope.action_button = 'Update';
        //get users and alarm
        var request = {};
        request.dash_id = row.id;

        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/getgroups_alarms',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.alarmlist_target = response.data.alarms;
                $scope.user_name_list = response.data.users;                   
                $scope.alarmlist =  response.data.origin_alarms ; 
                if(response.data.target_alarms != '' && response.data.target_alarms != null) {
                    $scope.targrt_models.lists = JSON.parse(response.data.target_alarms); 
                }else {
                    $scope.targrt_models.lists={"key1": [], "key2": [], "key3": [],"key4": [],"key5": [],"key6": [],
                    "key7": [], "key8": [], "key9": [],"key10": [],"key11": [],"key12": [],
                    "key13": [], "key14": [], "key15": [],"key16": [],"key17": [],"key18": [],
                    "key19": [], "key20": [], "key21": [],"key22": [],"key23": [],"key24": []};
                } 
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.deleteItem = function(row) {
        var request = {};
        request.id = row.id;  
        request.property = property_id;      
        $http({
            method: 'POST',
            url: '/frontend/alarm/setting/deletedash',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Dashboard has been deleted successfully');
                $scope.dashCancel();
                $scope.getDashList();

            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.onChangeImage=function(alarm_group) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/alarm/modal/change_image.html',
            controller: 'DashChangeImgController',
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
            getAlarmGroup();
        });
    };

    //=============dnd list==========
    // $scope.models = {
    //     selected: null,
    //     lists: {"A": []}
    // };
   
     // Generate initial model
    // for (var i = 1; i <= 3; ++i) {
    //     $scope.models.lists.A.push({label: "Item A" + i});       
    // }
   
    // Model to JSON for demo purpose
    $scope.$watch('targrt_models', function(model) {
        $scope.modelAsJson = angular.toJson(model, true);
    }, true);
});

app.controller('DashChangeImgController', function ($scope,$http, $rootScope, $uibModalInstance, toaster,AuthService, alarm_group, $filter) {
    $scope.alarm_group = alarm_group;
    var MESSAGE_TITLE = 'Alarm Image';  
        $scope.image = null;
		$scope.imageFileName = '';
		$scope.uploadme = {};
        $scope.uploadme.src = '';        
        
        $scope.onSaveAlarmImage = function() {
            var data = {};
            if( $scope.alarm_group == undefined || $scope.alarm_group.id == undefined )
                return;
    
            data.id = $scope.alarm_group.id;    
            var profile = AuthService.GetCredentials();
            if($scope.uploadme.src != null) {
				var extension = $scope.uploadme.imagename.substr($scope.uploadme.imagename.lastIndexOf("."), $scope.uploadme.imagename.length);
				data.picture_src = $scope.uploadme.src;
				data.extension = extension;
			}
		
            $http({
                method: 'POST', 
                url: '/frontend/alarm/setting/changeimageofalarm', 
                data: data, 
                headers: {'Content-Type': 'application/json; charset=utf-8'} 
            })
            .success(function(data, status, headers, config) {
                $scope.alarm_group.icon = data.icon;
                $uibModalInstance.dismiss('cancel');			
            })
            .error(function(data, status, headers, config) {				
                console.log(status);
            });			
        }
  
    $scope.save = function () {
        $uibModalInstance.close($scope.comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});

//**image upload
app.directive('fileDropzone', function() {
    return {
        restrict: 'A',
        scope: {
            file: '=',
            fileName: '='
        },
        link: function(scope, element, attrs) {
            var checkSize,
                isTypeValid,
                processDragOverOrEnter,
                validMimeTypes;

            processDragOverOrEnter = function (event) {
                if (event != null) {
                    event.preventDefault();
                }
                event.dataTransfer.effectAllowed = 'copy';
                return false;
            };

            validMimeTypes = attrs.fileDropzone;

            checkSize = function(size) {
                var _ref;
                if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
                    return true;
                } else {
                    alert("File must be smaller than " + attrs.maxFileSize + " MB");
                    return false;
                }
            };

            isTypeValid = function(type) {
                if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
                    return true;
                } else {
                    alert("Invalid file type.  File must be one of following types " + validMimeTypes);
                    return false;
                }
            };

            element.bind('dragover', processDragOverOrEnter);
            element.bind('dragenter', processDragOverOrEnter);

            return element.bind('drop', function(event) {
                var file, name, reader, size, type;
                if (event != null) {
                    event.preventDefault();
                }
                reader = new FileReader();
                reader.onload = function(evt) {
                    if (checkSize(size) && isTypeValid(type)) {
                        return scope.$apply(function() {
                            scope.file = evt.target.result;
                            if (angular.isString(scope.fileName)) {
                                return scope.fileName = name;
                            }
                        });
                    }
                };
                file = event.dataTransfer.files[0];
                name = file.name;
                type = file.type;
                size = file.size;
                reader.readAsDataURL(file);
                return false;
            });
        }
    };
})
.directive("fileread", [function () {
    return {
        scope: {
            fileread: "=",
            imagename: "=",
            imagetype: "="
        },
        link: function (scope, element, attributes) {
            element.bind("change", function (changeEvent) {
                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    scope.$apply(function () {
                        scope.fileread = loadEvent.target.result;
                    });
                }
                scope.imagename = changeEvent.target.files[0].name;
                scope.imagetype = changeEvent.target.files[0].type;
                reader.readAsDataURL(changeEvent.target.files[0]);
            });
        }
    }
}]);
//**image uplaod**/
