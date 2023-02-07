'use strict';

app.controller('EngPostController', function($scope, $http, $window, $interval, $timeout, $stateParams, toaster, Upload, $uibModal) {
    var MESSAGE_TITLE = 'Engineering Post';
    
    var client_id = $stateParams.client_id;
    var property_id = $stateParams.property_id;
    $scope.create_enable = false;
    $scope.disable_employee_select = false;
    var login_user = {};

    $scope.isLoadingCreate = false;

    $scope.prioritys = [
        'Low',
        'Medium',
        'High',
        'Urgent'
    ];

    function checkAuth()
    {
        var data = {};
        data.client_id = client_id;
        
        $http({
            method: 'POST',
            url: '/frontend/eng/checkauth',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                
                if( response.data.code == 200 && response.data.auth_on == 1 )
                {
                    showLoginPopupWindow();
                    $scope.create_enable = false;
                    $scope.disable_employee_select = true;
                }
                else
                {
                    $scope.create_enable = true;
                    $scope.disable_employee_select = false;
                }

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to Check Auth!');
            })
            .finally(function() {
            });
    }

    function showLoginPopupWindow()
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/login_modal.html',
            controller: 'LoginModalController',            
            scope: $scope,
            backdrop: 'static',
            resolve: {
                client_id: function () {
                    return client_id;
                },
            }
        });

        modalInstance.result.then(function (item) {
            login_user = item.user;
            $scope.onRequesterSelect(login_user);
            $scope.create_enable = true;
        }, function () {

        }); 
    }

    $scope.eng_setting = {};
    $http.get('/list/engsetting?property_id=' + property_id).success( function(response) {
                $scope.eng_setting = response;
            });


    $scope.requester = {};
    $scope.repair_request = {};
    function init()
    {
        $scope.requester = {};
        $scope.repair_request = {};
        $scope.repair_request.priority = $scope.prioritys[0];
        $scope.repair_request.files = [];
        $scope.repair_request.thumbnails = [];

        $http.get('/frontend/eng/maxrepairrequestid')
            .then(function(response) {
                $scope.eng_id = response.data.max_id + 1;
            });

        if( $scope.disable_employee_select )    
        {
            $scope.onRequesterSelect(login_user);
        }
    }
    
    $scope.cancelEng = function() {
        init();
    }
//    $scope.location_list = [];
    $scope.init = function() {        
       $http.get('/frontend/eng/repairrequest_getcategory_list_access')
            .then(function(response){
                $scope.category_list = response.data.content;
            });    

        $scope.location_list = [];
        $http.get('/list/locationtotallist?client_id=' + client_id)
                .then(function(response){
                    $scope.location_list = response.data; 
                });            

        $timeout( function() {
            checkAuth();
            init();

            $scope.timer = $interval(function() {
                $scope.repair_request.request_time = moment().format("HH:mm:ss");
                
             }, 1000);

        }, 1500 ); 
    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
        $scope.requester.wholename = $item.wholename;

        $scope.repair_request.property_id = $scope.requester.property_id;
        $scope.repair_request.requestor_id = $item.id;
        $scope.repair_request.requestor_type = $item.type;
        
        $scope.equipment_list = [];
        $http.get('/list/equipmentlist?property_id=' + $scope.repair_request.property_id)
            .then(function(response){
                $scope.equipment_list = response.data;
            });    
            
        $http.get('/frontend/eng/repairrequest_getcategory_list_access?user_id=' + $item.id)
            .then(function(response){
                $scope.category_list = response.data.content;
            });    

      
        $http.get('/list/locationtotallisteng?client_id=' + client_id + '&user_id=' + $item.id)
                .then(function(response){
                    $scope.location_list = response.data; 
                });     
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.repair_request.location_id = $item.id;        
        $scope.repair_request.location_type = $item.type;        
    };

    $scope.onEquipmentSelect = function (repair_request, $item, $model, $label) {
        $scope.repair_request.equipment_id = $item.id;
        $scope.repair_request.equipment_location = $item.location;
        $scope.repair_request.equip_id = $item.equip_id; 

        if($item.loc_id != 0){
            $scope.repair_request.location_id = $item.loc_id;  
            $scope.repair_request.location_name = $item.location_name;
            $scope.repair_request.location_type = $item.location_type;   
        }  
    };

    $scope.prevHist = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/modal/repairrequest_history.html',
            controller: 'RepairRequestHistoryController',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                repair_request: function () {
                    return $scope.repair_request;
                }
                
            }
        });

        modalInstance.result.then(function (selectedItem) {

        }, function () {

        }); 
    }   

    $scope.getStaffList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/eng_mytask/repairstafflist?value=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data;
            });
    };

    $scope.selectMainCategory = function()
    {
        $http.get('/frontend/eng/repairrequest_getsubcategory_list?category_id='+$scope.repair_request.category)
            .then(function(response){
                $scope.subcategory_list = response.data.content;
            });
    }

    $scope.uploadFiles = function (files) {
        if(files.length > 0)
        {
            $scope.repair_request.files = $scope.repair_request.files.concat(files);
            files.forEach(row => {
                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    $scope.repair_request.thumbnails.push(loadEvent.target.result);
                }
                reader.readAsDataURL(row);
            });
        }
    }
    $scope.removeFile = function($index) {
        $scope.repair_request.files.splice($index, 1);
        $scope.repair_request.thumbnails.splice($index, 1);
    }


    $scope.createRepairRequest = function()
    {
        if( $scope.create_enable == false )
        {
            checkAuth();
            return;
        }
        console.log($scope.eng_setting.eng_equip_mandatory);
        console.log($scope.eng_setting.eng_category_mandatory);
        var data = angular.copy($scope.repair_request);
        data.user_id = $scope.repair_request.requestor_id;
       
        if(!data.repair){
            toaster.pop('info', MESSAGE_TITLE, 'Please add Summary');
            return;
        }

        if(!data.description){
            toaster.pop('info', MESSAGE_TITLE, 'Please add Description');
            return;
        }
        if(!data.location_name){
            toaster.pop('info', MESSAGE_TITLE, 'Please add Location');
            return;
        }
        if (!(data.equipment_id) && $scope.eng_setting.eng_equip_mandatory > 0 ){
            toaster.pop('error', MESSAGE_TITLE, 'Please select Equipment from List');
            return;
        }
        
        if (!(data.category) && $scope.eng_setting.eng_category_mandatory > 0 ){
            toaster.pop('error', MESSAGE_TITLE, 'Please select Category from List');
            return;
        }
       
        $scope.isLoadingCreate = true;
        
        $http({
            method: 'POST',
            url: '/frontend/eng/createrepairrequest',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                var files = $scope.repair_request.files;

                if(files && files.length > 0)
                {
                    Upload.upload({
                        url: '/frontend/eng/upload_repair_attach',
                        data: {
                            id: response.data.id,
                            files: files
                        }
                    }).then(function (response) {
                        init();
                        
                        console.log(response);
                    }, function (response) {
                        if (response.status > 0) {
                            $scope.errorUploadMsg = response.status + ': ' + response.data;
                        }
                    }, function (evt) {
                        $scope.upload_progress = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                    });

                }
                else
                {
                    init();
                }

                toaster.pop('success', MESSAGE_TITLE, ' Work request has been created successfully');
                $scope.isLoadingCreate = false;

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
                $scope.isLoadingCreate = false;
            })
            .finally(function() {
            });
    }

});
    app.controller('RepairRequestHistoryController', function($scope, $window, $uibModalInstance, $http, toaster,AuthService, repair_request) {
        $scope.repair_request = repair_request;
        $scope.tableState = undefined;

        $scope.isLoading = false;
        $scope.paginationOptions = {
            pageNumber: 1,
            pageSize: 10,
            sort: 'desc',
            field: 'id',
            totalItems: 0,
            numberOfPages : 1,
            countOfPages: 1
        };
        
    
        $scope.histlist = [];
       
        
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
  
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.user_id = $scope.repair_request.requestor_id;
        request.property_id = $scope.repair_request.property_id;
    
        
        $http({
            method: 'POST',
            url: '/frontend/eng/repairrequesthistlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
           $scope.histlist = response.data.datalist;
         
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
            console.error('Gists error', response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });
       
        
        };
        
        $scope.getTicketNumber = function(ticket){
                if(!ticket)
                    return moment().format('RYYYYMMDD00');
                return moment(ticket.created_at).format('RYYYYMMDD') + sprintf('%02d', ticket.daily_id);
            }
    
    
        $scope.cancel = function () {
            $uibModalInstance.dismiss();
        };
        
    });
    
   



