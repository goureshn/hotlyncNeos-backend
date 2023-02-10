app.controller('IssueEditController', function ($scope, $rootScope, $http, $interval, $uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload, $uibModal) {
    var MESSAGE_TITLE = 'Issue Edit';

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.datetime = {};
    $scope.uploadme = {};
    $scope.location = {};
    $scope.files = [];
//    $scope.category_list = [];
    $scope.subcategory_list = [];
   // window.alert(profile.id);
   
	$scope.tableState = undefined;
    $scope.isLoading = false;
    
    if($scope.issue.location !=null) {
        $scope.location = $scope.issue.location;
    }
	$scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.life_units = [
        'days',
        'months',
        'years',
    ];
    
    $scope.statuses = $http.get('/frontend/it/statuslist')
        .then(function(response){
            $scope.statuses = response.data;
        });
    $scope.severity_list = $http.get('/frontend/it/severitylist')
        .then(function(response){
            $scope.severity_list = response.data;
        });
    $scope.type_list = $http.get('/frontend/it/typelist')
        .then(function(response){
            $scope.type_list = response.data;
        });
    $scope.category_list = $http.get('/frontend/it/it_category')
        .then(function(response){
            $scope.category_list = response.data;
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
    }
     //$scope.issue.sendflag=0;

    $scope.onApprove=function(){
     
        var request = {};

        request.id = $scope.issue.id;        
        $http({
            method: 'POST',
            url: '/frontend/it/approver',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, response.data.message);
                $scope.pageChanged();
                $scope.issue.status = response.data.status;
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
                console.log(response);
            })
            .finally(function() {
            });
	    
    }
    
    $scope.inProgress=function(){
        $scope.issue.status ="In-Progress";
        $scope.issue.assignee = profile.id;
        
	    $scope.UpdateIssue();	    
    }
    $scope.onReject=function(){
	    $scope.issue.status ="Rejected";
	   
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/it/rejectedmodal.html',
            controller: 'RejectedComment',
            resolve: {
                issue: function () {
                    return $scope.issue;
                }
            }
        });        
    }
	    
    
    $scope.onResolve=function(){
		if($scope.issue.resolution)
		{
            $scope.issue.status ="Resolved";
            $scope.UpdateIssue();
		}
		else
		{
			toaster.pop('info', MESSAGE_TITLE, 'Please enter resolution comment.');
			return;
		}
    }

    $scope.onHold=function(){
		if($scope.issue.resolution)
		{
            $scope.issue.status ="On-Hold";
            $scope.UpdateIssue();
		}
		else
		{
			toaster.pop('info', MESSAGE_TITLE, 'Please enter hold comment.');
			return;
		}
    }
    
    $scope.onReopen=function() {	    
		$scope.issue.status ="Re-Opened";
		$scope.UpdateIssue();
    }

	$scope.onClose=function() {	    
		$scope.issue.status ="Closed";
		$scope.UpdateIssue();
    }

    $scope.$on('UpdateStatus', function(event, args){
        $scope.UpdateIssue();
    });
   
    
    $scope.getLocationList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    }

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.location = $item;
        $scope.issue.location_group_member_id = $item.id;
    }

    $scope.getCategoryList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/frontend/it/catlist?category='+val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    }



    $scope.getSubCategoryList = function(val) {
        if( val == undefined )
            val = "";
            
        var category=$scope.issue.category;
        //window.alert(category);

        return $http.get('/frontend/it/subcatlist?sub_cat='+val+ '&category=' + category)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });

    }

    $scope.getAssigneeList = function(val) {
        if( val == undefined )
            val = "";

       return $http.get('/frontend/it/assigneelist?assignee=' + val + '&dept_id=' + profile.dept_id)
    //    return $http.get('/frontend/it/assigneelist')
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    }

    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.issue.category = $item.category;
        $scope.issue.category_id = $item.id;
        $scope.issue.updated_by = profile.id;


        $http({
            method: 'POST',
            url: '/frontend/it/updatecategory',
            data: {
                id: $scope.issue.id,
                category: $scope.issue.category,
                updated_by: $scope.issue.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Category has been changed successfully');
                $scope.pageChanged();
                //$scope.UpdateIssue();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Category');
                console.log(response);
            })
            .finally(function () {
            });       
    }

    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.issue.subcategory = $item.sub_cat;
        $scope.issue.updated_by = profile.id;
        $http({
            method: 'POST',
            url: '/frontend/it/updatesubcategory',
            data: {
                id: $scope.issue.id,
                subcategory: $scope.issue.subcategory,
                updated_by: $scope.issue.updated_by,
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
    }

    $scope.onAssigneeSelect = function ($item, $model, $label) {
        $scope.issue.assignee_id = $item.id;
        $scope.issue.updated_by = profile.id;


        $http({
            method: 'POST',
            url: '/frontend/it/updateassignee',
            data: {
                id: $scope.issue.id,
                assignee_id: $scope.issue.assignee_id,
                updated_by: $scope.issue.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Assignee has been changed successfully');
                $scope.pageChanged();
                //$scope.UpdateIssue();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Assignee');
                console.log(response);
            })
            .finally(function () {
            });       
    }

    $scope.onSeveritySelect = function () {
        //$scope.issue.sev;
        $scope.issue.updated_by = profile.id;
        angular.forEach($scope.severity_list, function (value) { 
            if (value.severity == $scope.issue.sev)
                $scope.issue.severity=value.id;
        });
        $http({
            method: 'POST',
            url: '/frontend/it/updateseverity',
            data: {
                id: $scope.issue.id,
                severity: $scope.issue.severity,
                updated_by: $scope.issue.updated_by,

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
        
    }

    $scope.onTypeSelect = function () {
        //$scope.issue.sev;
        $scope.issue.updated_by = profile.id;
        angular.forEach($scope.type_list, function (value) { 
            if (value.type == $scope.issue.type_it)
                $scope.issue.type=value.id;
        });
        $http({
            method: 'POST',
            url: '/frontend/it/updatetype',
            data: {
                id: $scope.issue.id,
                type: $scope.issue.type,
                updated_by: $scope.issue.updated_by,

            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Type has been changed successfully');
                $scope.pageChanged();
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to change Type');
                console.log(response);
            })
            .finally(function () {
            }); 
        
    }
	
	$scope.onIPComment = function() {
        $scope.issue.updated_by = profile.id;


        $http({
            method: 'POST',
            url: '/frontend/it/savecomment',
            data: {
                id: $scope.issue.id,
                ipcomment: $scope.issue.ipcomment,
                updated_by: $scope.issue.updated_by,
            }
            ,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, 'Comment has been added successfully');
                $scope.pageChanged();
                
            }).catch(function (response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to save comment.');
                console.log(response);
            })
            .finally(function () {
            });

    }

	$scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);
        var profile = AuthService.GetCredentials();       
        if ($scope.files && $scope.files.length > 0 ) {
            Upload.upload({
                url: '/frontend/it/uploadsubfiles',
                data: {
                    id: $scope.issue.id,
                    user_id: profile.id,
                    files: $scope.files
                }
            }).then(function (response) {
                $scope.files = [];
                if( response.data.upload )
                    $scope.issue.sub_download_array = response.data.upload.split("|");
                else
                    $scope.issue.sub_download_array = [];
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
    }

    $scope.removeFile = function($index) {        
        var request = {};
        request.id = $scope.issue.id;
        request.index = $index;

        $http({
            method: 'POST',
            url: '/frontend/it/removefiles',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
            if( response.data.upload )
                $scope.issue.sub_download_array = response.data.upload.split("|");
            else
                $scope.issue.sub_download_array = [];
        }).catch(function(response) {
        })
        .finally(function() {

        });
     
    }

    $scope.UpdateIssue = function() {
	    $scope.issue.updated_by = profile.id;
        var data = angular.copy($scope.issue);
      
        $http({
            method: 'POST',
            url: '/frontend/it/updateissue',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.pageChanged();
                toaster.pop('success', MESSAGE_TITLE, 'Status has been updated successfully');
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    $scope.createCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/it/modal/it_category.html',
            controller: 'ItCategoryCtrl',
            scope: $scope,
            resolve: {
                it: function () {
                    return $scope.issue;
                },
                category_list: function () {
                    return $scope.category_list;
                }
            }
        });

        modalInstance.result.then(function (row) {
            $scope.issue.category_id = row.id;
            $scope.issue.category = row.category;
            $scope.selectMainCategory();
        }, function () {

        });
    }

    $scope.createSubCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/it/modal/it_subcategory.html',
            controller: 'ItSubCategoryCtrl',
            scope: $scope,
            resolve: {
                it: function () {
                    return $scope.issue;
                },
                subcategory_list: function () {
                    return $scope.subcategory_list;
                },
                category_list: function () {
                    return $scope.category_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem,row) {
            $scope.selected = selectedItem;
            console.log(row);
        }, function () {

        });
    }

    
    $scope.setItCategoryList = function(list){
        $scope.category_list = list;
    }

    $scope.setItSubCategoryList = function(list){
        $scope.subcategory_list = list;
    }

    $scope.selectMainCategory = function()
    {
        $http.get('/frontend/it/it_category?category_id='+$scope.issue.category_id)
            .then(function(response){
                $scope.subcategory_list = response.data;
            });
    }

    $scope.setSubCategory = function(row){
        $scope.issue.subcategory = row.sub_cat;
    };

	
});

app.controller('RejectedComment', function($scope, $rootScope, $http, AuthService, GuestService, $interval, toaster, $timeout, $uibModalInstance, issue) {
    var MESSAGE_TITLE = 'Issue Edit';
    $scope.reject = '';
    var profile = AuthService.GetCredentials();

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    
    $scope.onSend=function(){
        issue.sendflag = 1;
        $scope.UpdateIssue();        
    }
    
    $scope.UpdateIssue = function() {    
        issue.reject = $scope.reject;
        issue.updated_by = profile.id;
        var data = angular.copy(issue);

        $http({
            method: 'POST',
            url: '/frontend/it/updateissue',
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

app.controller('ItCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, it, category_list) {
    $scope.it = it;
    $scope.cateory_list = category_list;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.it.category_new_name;
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/it/it_savecategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.it.category_new_name = '';
            $scope.category_list = response.data;
            $scope.setItCategoryList($scope.category_list);
        }).catch(function(response) {
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


app.controller('ItSubCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, it, subcategory_list, category_list) {
    $scope.it = it;
    $scope.subcategory_list = subcategory_list;
    $scope.category_list = category_list;
    $scope.category = {};
    for(var i = 0 ; i < $scope.category_list.length;i++)
    {
        if($scope.category_list[i].id == $scope.it.category_id)
        {
            $scope.category = $scope.category_list[i];
        }
    }

    $scope.createSubCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.it.subcategory_new_name;
        request.user_id = profile.id;
        request.property_id = profile.property_id;
        request.category_id = $scope.it.category_id;
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/frontend/it/it_savesubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.it.subcategory_new_name = '';
            $scope.subcategory_list = response.data;

            //var alloption = {id: 0, name : 'Unclassified'};
            //$scope.category_list.unshift(alloption);

            $scope.setItSubCategoryList($scope.subcategory_list);
        }).catch(function(response) {
        })
            .finally(function() {

            });
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    $scope.selectRow = function(row){
        $scope.setSubCategory(row);
        $uibModalInstance.dismiss();
    }
});



