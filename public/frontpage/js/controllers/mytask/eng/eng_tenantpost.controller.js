'use strict';

app.controller('EngTenantPostController', function($scope, $http, $window, $interval, $timeout, $stateParams, toaster, Upload, $uibModal) {
    var MESSAGE_TITLE = 'Engineering Tenant Post';
    
    var client_id = $stateParams.client_id;
    $scope.create_enable = false;
    $scope.disable_employee_select = false;
    var login_user = {};

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

    $scope.requester = {};
    $scope.repair_request = {};
    $scope.property_list = [];

    $http.get('/list/property')
    .then(function (response) {
        $scope.property_list = response.data;
        if( $scope.property_list.length > 0 )
                {
                    $scope.repair_request.property_id = $scope.property_list[0].id;
                    $scope.onChangedProperty();
                }
    });
    function init()
    {
        $scope.requester = {};
        $scope.repair_request = {};
        $scope.repair_request.priority = $scope.prioritys[0];
        $scope.repair_request.property_id = $scope.property_list[0].id;
        $scope.onChangedProperty();
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


    $scope.onChangedProperty = function() {
        $scope.location = {};
        $http.get('/list/locationlist?property_id=' + $scope.repair_request.property_id)
            .then(function(response){
                $scope.location_list = response.data;
            });
    }

    $scope.flag = 0;
    $scope.show = 0;

    $scope.onRequesterSelect = function (item) {
        $scope.show = 1;

     /*   $scope.requester = $item;
        $scope.requester.wholename = $item.tenant_name;

        $scope.repair_request.property_id = $scope.requester.property_id;
        $scope.repair_request.requestor_id = $item.id;
        $scope.repair_request.requestor_type = $item.type;

    */
        var request = {};
        request.email = item;
        $http({
            method: 'POST',
            url: '/frontend/eng/gettenantlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

            $scope.flag = response.data.flag;
            $scope.requester.tenant_name = response.data.content.tenant_name;
            $scope.repair_request.property_id = response.data.content.property_id;
            $scope.repair_request.requestor_id = response.data.content.id;
            $scope.repair_request.requestor_type = 'Tenant';
      
        }).catch(function(response) {
         
        })
            .finally(function() {

            });
        
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.repair_request.location_id = $item.id;        
        $scope.repair_request.location_type = $item.type;        
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

    $scope.getTenantList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/eng_mytask/repairtenantlist?value=' + val)
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
            $scope.repair_request.files.forEach(row => {
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

        if ($scope.flag == 0){

   

        var request = {};
    
        request.name = $scope.requester.tenant_name;
        request.email = $scope.requester.tenant_email;
        request.contact = '';
        request.user_id = 0;

        $http({
            method: 'POST',
            url: '/frontend/eng/repairrequest_savetenant',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

          $scope.repair_request.requestor_id = response.data.content.id;
          var data = angular.copy($scope.repair_request);

        console.log(data);
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

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
            });
           
        });
     
        }
        else
        {




        var data = angular.copy($scope.repair_request);

        console.log(data);
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
        /*
        if(!data.equipment_id){
            toaster.pop('info', MESSAGE_TITLE, 'Please add Equipment from the List');
            return;
        }
       
        */
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

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
            });
        }
    }

    

    $scope.addrequestor = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/repair_request/modal/repairrequest_requestor.html',
            controller: 'RepairRequestRequestorCtrl',
            scope: $scope,
            resolve: {
                repair_request: function () {
                    return $scope.repair_request;
                },
                tenant_list: function () {
                    return $scope.tenant_list;
                }
            }
        });

        modalInstance.result.then(function (row) {
            $scope.repair_request.wholename = row.tenant_name;
            $scope.repair_request.requestor_id = row.id;
            $scope.repair_request.requestor_type = 'Tenant';
        }, function () {

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

    app.controller('RepairRequestRequestorCtrl', function($scope, $uibModalInstance, $http, toaster, AuthService, repair_request, tenant_list) {
        var MESSAGE_TITLE = 'Add New Requestor';
        $scope.repair_request = repair_request;
        $scope.tenant_list = tenant_list;

    
        $scope.addrequestor = function () {
          //  var profile = AuthService.GetCredentials();
    
            var request = {};
    
            request.name = $scope.repair_request.new_requestor;
            request.email = $scope.repair_request.new_email;
            request.contact = $scope.repair_request.new_contact;
            request.user_id = 0;
        //    request.property_id = profile.property_id;
    
            if( !request.name ){
                toaster.pop('info', MESSAGE_TITLE, 'Please enter Name');
                return;
            }
    
            if( !request.email ){
                toaster.pop('info', MESSAGE_TITLE, ' Please enter Email');
                return;
            }
    
            $http({
                method: 'POST',
                url: '/frontend/eng/repairrequest_savetenant',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {
                console.log(response);
                $scope.repair_request.new_requestor = '';
                $scope.repair_request.new_email = '';
                $scope.repair_request.new_contact = '';
                toaster.pop('success', MESSAGE_TITLE, ' Details added successfully');
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to add Details!');
            })
                .finally(function() {
    
                });
        };
    
    
        $scope.cancel = function () {
            $uibModalInstance.dismiss();
        };
    
        $scope.selectRow = function(row){        
            $uibModalInstance.close(row);
        }
    
    });
    
   



