app.controller('EngEditController', function ($scope, $rootScope, $http, $interval, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload, $uibModal) {
    var MESSAGE_TITLE = 'Engineering Request Edit';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.datetime = {};
    $scope.uploadme = {};
    $scope.location = {};
    $scope.files = [];
   // window.alert(profile.id);
   
    var property_id = profile.property_id;
    
    if($scope.eng.location !=null) {
        $scope.location = $scope.eng.location;
    }
  

    $scope.life_units = [
        'days',
        'months',
        'years',
    ];
    
    $scope.statuses = $http.get('/frontend/eng_mytask/statuslist')
        .then(function(response){
            $scope.statuses = response.data;
        });
        $scope.severity_list = $http.get('/frontend/eng_mytask/severitylist')
        .then(function(response){
            $scope.severity_list = response.data;
        });

    $scope.getDepartmentList = function(val) {
        if( val == undefined )
            val = "";
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        return GuestService.getDepartSearchList(val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    
    $scope.inProgress=function(){
	    $scope.eng.status ="In-Progress";
	   $scope.UpdateEng();
	    $scope.UpdateStatus();
	    
    }
    $scope.onAck = function () {
        $scope.eng.status = "Acknowledge";
        $scope.UpdateEng();
        $scope.UpdateStatus();
    }
         
    $scope.onReject=function(){
	    $scope.eng.status ="Rejected";
	   
         var modalInstance = $uibModal.open({
            templateUrl: 'tpl/mytask/eng/rejectedmodal.html',
            controller: 'RejectedComment',
            resolve: {
               eng: function () {
                    return $scope.eng;
                }
            }
        });
    }
	    
    $scope.onResolve = function(){
		if($scope.eng.resolution)
		{
            $scope.eng.status ="Resolved";
            $scope.UpdateEng();
            $scope.UpdateStatus();
		}
		else
		{
			toaster.pop('info', MESSAGE_TITLE, 'Please enter resolution comment.');
			return;
		}
    }
	
	$scope.onClose=function(){
		$scope.eng.status ="Closed";
		$scope.UpdateEng();
	    $scope.UpdateStatus();
    }

    $scope.$on('UpdateStatus', function(event, args){
        $scope.UpdateStatus();
    });
   
    $scope.UpdateStatus = function(){
        $scope.eng.updated_by = profile.id;
        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updatestatus',
            data: {
                    id: $scope.eng.id,
                    status: $scope.eng.status,
                    updated_by: $scope.eng.updated_by,
                }
,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Status has been updated successfully');
                $scope.pageChanged();
                //$scope.UpdateEng();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
                console.log(response);
            })
            .finally(function() {
            });
    }
 


    $scope.getLocationList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.location = $item;
        $scope.eng.location_group_member_id = $item.id;
    };

    $scope.getCategoryList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/eng_mytask/catlist?category='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    };
     $scope.getSubCategoryList = function(val) {
        if( val == undefined )
            val = "";
            
            var category=$scope.eng.category;
            //window.alert(category);

        return $http.get('/frontend/eng_mytask/subcatlist?sub_cat='+val+ '&category=' + category)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    };
    $scope.getAssigneeList = function (val) {
        if (val == undefined)
            val = "";
        
      //  var assignee = $scope.eng.assignee;
        //window.alert(category);

        return $http.get('/frontend/eng_mytask/assigneelist?asgn=' + val + '&property_id=' + property_id)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });

    };
    
    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.eng.category = $item.category;
        $scope.eng.updated_by = profile.id;


        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updatecategory',
            data: {
                id: $scope.eng.id,
                category: $scope.eng.category,
                updated_by: $scope.eng.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Category has been changed successfully');
                $scope.pageChanged();
                //$scope.UpdateEng();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Category');
                console.log(response);
            })
            .finally(function () {
            });
       
    };
    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.eng.subcategory = $item.sub_cat;
        $scope.eng.updated_by = profile.id;
        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updatesubcategory',
            data: {
                id: $scope.eng.id,
                subcategory: $scope.eng.subcategory,
                updated_by: $scope.eng.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Sub-Category has been changed successfully');
                $scope.pageChanged();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Sub-Category');
                console.log(response);
            })
            .finally(function () {
            }); 
    };
    $scope.onSeveritySelect = function () {
        //$scope.eng.sev;
        $scope.eng.updated_by = profile.id;
        angular.forEach($scope.severity_list, function (value) { 
            if (value.severity == $scope.eng.sev)
                $scope.eng.severity=value.id;
        });
        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updateseverity',
            data: {
                id: $scope.eng.id,
                severity: $scope.eng.severity,
                updated_by: $scope.eng.updated_by,

            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Severity has been changed successfully');
                $scope.pageChanged();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Severity');
                console.log(response);
            })
            .finally(function () {
            }); 
        
    };
    $scope.onAssigneeSelect = function ($item, $model, $label) {
        $scope.eng.assignee = $item.wholename;
        $scope.eng.updated_by = profile.id;


        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updateassignee',
            data: {
                id: $scope.eng.id,
                assignee: $scope.eng.assignee,
                updated_by: $scope.eng.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'This task has been assigned to ' + $scope.eng.assignee+' successfully');
                $scope.pageChanged();
                //$scope.UpdateEng();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change assign task');
                console.log(response);
            })
            .finally(function () {
            });

    };
    $scope.onIPComment = function() {
        //$scope.eng.assignee = $item.wholename;
        $scope.eng.updated_by = profile.id;


        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/savecomment',
            data: {
                id: $scope.eng.id,
                ipcomment: $scope.eng.ipcomment,
                updated_by: $scope.eng.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Comment has been added successfully');
                $scope.pageChanged();
                //$scope.UpdateEng();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to save comment.');
                console.log(response);
            })
            .finally(function () {
            });

    };

    $scope.onSupplierSelect = function ($item, $model, $label) {
        $scope.eng.outside_flag = 1;
        $scope.eng.supplier_id = $item.id;

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updatesupplier',
            data: $scope.eng,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Supplier is changed successfully.');
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change supplier');
                console.log(response);
            })
            .finally(function () {
            });

    };

    $scope.onSupplierEnabled = function () {
        if( $scope.eng.outside_flag == false )
            $scope.eng.supplier = '';

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updatesupplier',
            data: $scope.eng,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Supplier is changed successfully.');
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change supplier');
                console.log(response);
            })
            .finally(function () {
            });

    };


    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);
        var profile = AuthService.GetCredentials();       
        if ($scope.files && $scope.files.length > 0 ) {
            Upload.upload({
                url: '/frontend/eng_mytask/uploadsubfiles',
                data: {
                    id: $scope.eng.id,
                    user_id: profile.id,
                    files: $scope.files
                }
            }).then(function (response) {
                $scope.files = [];
                if( response.data.upload )
                    $scope.eng.sub_download_array = response.data.upload.split("|");
                else
                    $scope.eng.sub_download_array = [];
            }, function (response) {
                $scope.files = [];
                if (response.status > 0) {
                    $scope.errorMsg = response.status + ': ' + response.data;
                }
            }, function (evt) {
                $scope.progress = 
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        }
    };

    $scope.removeFile = function($index) {        
        var request = {};
        request.id = $scope.eng.id;
        request.index = $index;

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/removefiles',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
            if( response.data.upload )
                $scope.eng.sub_download_array = response.data.upload.split("|");
            else
                $scope.eng.sub_download_array = [];
        }).catch(function(response) {
        })
        .finally(function() {

        });
     
    }

    $scope.UpdateEng = function(){
	    $scope.eng.updated_by= profile.id;
        var data = angular.copy($scope.eng);
        //window.alert($scope.eng.severity);
        var currentdate = new Date();
        var datetime = currentdate.getFullYear()+"-"+
            (currentdate.getMonth()+1) +"_"+
            currentdate.getDate() + "_"+
            currentdate.getHours() +"_"+
            currentdate.getMinutes() +"_"+
            currentdate.getSeconds()+"_";
        var url =  datetime + Math.floor((Math.random() * 100) + 1);

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updateeng',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
	            
                
                $scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    $scope.onCreateSupplier = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'supplier_list_modal.html',
            controller: 'SupplierListCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                eng: function () {
                    return $scope.eng;
                },
                supplier_list: function () {
                    return $scope.supplier_list;
                },                
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.setSupplierList = function(list) {
        $scope.supplier_list = list;
    }
});

app.controller('RejectedComment', function($scope, $rootScope, $http, AuthService, GuestService, $interval, toaster, $timeout, $uibModalInstance, eng) {
    var MESSAGE_TITLE = 'Engineering Request Edit';
    $scope.reject='';
    var profile = AuthService.GetCredentials();

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
        
    $scope.onSend=function(){
	    eng.sendflag = 1;
	    $scope.UpdateEng();
	    $rootScope.$broadcast('UpdateStatus');
    }
	   
    $scope.UpdateEng = function(){
		eng.reject=$scope.reject;
        var currentdate = new Date();
        var datetime = currentdate.getFullYear()+"-"+
            (currentdate.getMonth()+1) +"_"+
            currentdate.getDate() + "_"+
            currentdate.getHours() +"_"+
            currentdate.getMinutes() +"_"+
            currentdate.getSeconds()+"_";
        var url =  datetime + Math.floor((Math.random() * 100) + 1);
        eng.reject=$scope.reject;
        eng.updated_by= profile.id;
        var data = angular.copy(eng);

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/updateeng',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
	            //toaster.pop('success', MESSAGE_TITLE, 'Status has been updated successfully');
                $rootScope.$broadcast('onpageChanged');
                $uibModalInstance.dismiss();
                //$scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

});


app.controller('SupplierListCtrl', function ($scope, $uibModalInstance, $http, AuthService, eng) {
    var profile = AuthService.GetCredentials();
    $scope.new_eng = {};

    $scope.createSupplier = function () {

        var request = {};

        request = $scope.new_eng;
        
        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/createsupplier',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.supplier_list = response.data;
            $scope.setSupplierList(response.data);
            if( !($scope.new_eng.id > 0) )
                $scope.new_eng = {};
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onClickUpdate = function(row) {
        $scope.new_eng = angular.copy(row);
    }

    $scope.onClickDelete = function(row) {
        var request = {};
        request.id = row.id;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/deletesupplier',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.supplier_list = response.data;
            $scope.setSupplierList(response.data);
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

});