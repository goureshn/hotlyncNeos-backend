
app.controller('IssueCreateController', function ($scope, $rootScope, $http, $window, $uibModal, $interval,$timeout, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'ISSUE Status';
    var INCOMP = ' INCOMPLETE';

    
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    $scope.status_list = ['Pending'];

    $scope.guest_list = [{ guest_id: 0, guest_name: 'Select Guest' }];
    $scope.building_list = [];
    $scope.severity_list = [];
    $scope.category_list = [];
    $scope.subcategory_list = [];
    $scope.type_list = [];
    $scope.complaint_tasks = [];
    $scope.count = false;
    $scope.disable_create = 0;

    $scope.requester = {};
    $scope.it = {};

    $scope.includeMobile = false;
    var screenWidth = $window.innerWidth;
    if (screenWidth < 550) {
        $scope.includeMobile = true;
    }


    $scope.cancelIssue = function () {
        $scope.it = {};
        
        $scope.requester = {};
        $scope.it.housecomplaint_id = 0;
        $scope.it.property_id = 0;
        $scope.it.building_id = 0;
        if( $scope.building_list.length > 0 )
            $scope.it.building_id = $scope.building_list[0].id;
        
        $scope.it.initial_response = '';
        $scope.it.status = $scope.status_list[0];
        $scope.it.severity = 1;
        $scope.it.type = 1;
        $scope.complaint_tasks = [];
        $scope.count = false;
        $scope.files = [];

      
    }

    $scope.init = function () {
       

        $http.get('/frontend/it/id')
            .then(function (response) {
                $scope.issue_id = response.data.max_id + 1;
            });

        $http.get('/list/building')
            .then(function(response) {
               $scope.building_list = response.data;           
           });
        

        $http.get('/list/severitylistit')
            .then(function (response) {
                $scope.severity_list = response.data;
            });

        $http.get('/list/typelistit')
            .then(function (response) {
                $scope.type_list = response.data;
            });
        $http.get('/frontend/it/it_category')
            .then(function(response){
                $scope.category_list = response.data;
            });

        $timeout(function () {
            $scope.cancelIssue();

            $scope.timer = $interval(function () {
                $scope.it.request_time = moment().format("HH:mm:ss");
            }, 1000);

        }, 1500);
    }

    $scope.$on('$destroy', function () {
        if ($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });

   

    $scope.getCategoryList = function (val) {
        if (val == undefined)
            val = "";

        return $http.get('/frontend/it/catlist?category=' + val)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });

    };
    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.it.category = $item.category;
        $scope.it.category_id = $item.id;
    };

    $scope.getSubCategoryList = function (val) {
        if (val == undefined)
            val = "";

        var category = $scope.it.category;
   

        return $http.get('/frontend/it/subcatlist?sub_cat=' + val + '&category=' + category)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });

    };
    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.it.subcategory = $item.sub_cat;
    };


   

    $scope.getStaffList = function (val) {
        if (val == undefined)
            val = "";
        
        return $http.get('/frontend/it/stafflist?value=' + val + '&client_id=' + client_id)
            .then(function (response) {
                return response.data.datalist.map(function (item) {
                    return item;
                });
            });
    };
   

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
        $scope.it.property_id = $scope.requester.property_id;
     
    };

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);
    };

  


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if ($view == 'minute') {
            var activeDate = moment().subtract('minute', 5);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() > activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.removeFile = function ($index) {
        $scope.files.splice($index, 1);
    }


    $scope.createIssue = function () {
      //  window.alert("here");
        $scope.disable_create = 1;
        var request = {};

        request.client_id = client_id;
        request.property_id = $scope.it.property_id;
        request.building_id = $scope.it.building_id;
        request.requestor_id = $scope.requester.id;

        if (!(request.requestor_id > 0)) {
            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'You did not select requestor');
            $scope.disable_create = 0;
            return;
        }

        
        request.severity = $scope.it.severity;
        request.type = $scope.it.type;
        request.status = $scope.it.status;
        request.initial_response = $scope.it.initial_response;
        request.housecomplaint_id = $scope.it.housecomplaint_id;
        request.incident_time = $scope.it.incident_time;
        request.category = $scope.it.category;
        request.subcategory = $scope.it.subcategory;



        if (!request.category) {

            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'Please select Category');
            $scope.disable_create = 0;
            return;
        }

        if (!request.initial_response) {

            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'Please enter Subject of Issue');
            $scope.disable_create = 0;
            return;
        }


       

        request.comment = $scope.it.comment;

   
        if (!request.comment) {

            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'You did not enter issue');
            $scope.disable_create = 0;
            return;
        }

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/it/post',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.disable_create = 0;
            toaster.pop('success', MESSAGE_TITLE, response.data.message);

 
            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/it/uploadfiles',
                    data: {
                        id: response.data.id,
                        files: $scope.files
                    }
                }).then(function (response) {
                    $scope.files = [];
                    $scope.progress = 0;
                }, function (response) {
                    $scope.files = [];
                    $scope.progress = 0;
                    if (response.status > 0) {
                        $scope.errorMsg = response.status + ': ' + response.data;
                    }
                }, function (evt) {
                    $scope.progress =
                        Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                });
            }

            $scope.cancelIssue();
            $scope.pageChanged();
            $scope.issue_id = response.data.id + 1;
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Issue.');
           
            $scope.disable_create = 0;
        })
            .finally(function () {

            });
    }

    $scope.createCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/it/modal/it_category.html',
            controller: 'ItCategoryCtrl',
            scope: $scope,
            resolve: {
                it: function () {
                    return $scope.it;
                },
                category_list: function () {
                    return $scope.category_list;
                }
            }
        });

        modalInstance.result.then(function (row) {
            $scope.it.category_id = row.id;
            $scope.it.category = row.category;
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
                    return $scope.it;
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
        $http.get('/frontend/it/it_category?category_id='+$scope.it.category_id)
            .then(function(response){
                $scope.subcategory_list = response.data;
            });
    }

    $scope.setSubCategory = function(row){
        $scope.it.subcategory = row.id;
    };




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


