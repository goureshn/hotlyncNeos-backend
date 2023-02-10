
app.controller('EngCreateController', function ($scope, $rootScope, $http, $window, $interval,$timeout, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Engineering Status';
    var INCOMP = ' INCOMPLETE';

    
        var profile = AuthService.GetCredentials();
        var client_id = profile.client_id;
    $scope.status_list = ['Pending'];

    $scope.guest_list = [{ guest_id: 0, guest_name: 'Select Guest' }];
    $scope.severity_list = [];
    $scope.complaint_tasks = [];
    $scope.count = false;
    $scope.disable_create = 0;

    $scope.includeMobile = false;
    var screenWidth = $window.innerWidth;
    if (screenWidth < 550) {
        $scope.includeMobile = true;
    }


    $scope.cancelEng = function () {
        $scope.eng = {};
        
        $scope.requester = {};
        $scope.eng.housecomplaint_id = 0;
        $scope.eng.property_id = 0;

        $scope.eng.initial_response = '';
        $scope.eng.status = $scope.status_list[0];
        $scope.eng.severity = 1;
        $scope.complaint_tasks = [];
        $scope.count = false;
        $scope.files = [];

      
    }

    $scope.init = function () {
       

        $http.get('/frontend/eng_mytask/id')
            .then(function (response) {
                $scope.eng_id = response.data.max_id + 1;
            });

        

        $http.get('/list/severitylisteng')
            .then(function (response) {
                $scope.severity_list = response.data;
            });

        $timeout(function () {
            $scope.cancelEng();

            $scope.timer = $interval(function () {
                $scope.eng.request_time = moment().format("HH:mm:ss");
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

        return $http.get('/frontend/eng_mytask/catlist?category=' + val)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });

    };
    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.eng.category = $item.category;
    };

    $scope.getSubCategoryList = function (val) {
        if (val == undefined)
            val = "";

        var category = $scope.eng.category;
   

        return $http.get('/frontend/eng_mytask/subcatlist?sub_cat=' + val + '&category=' + category)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });

    };
    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.eng.subcategory = $item.sub_cat;
    };


   

    $scope.getStaffList = function (val) {
        if (val == undefined)
            val = "";
        
        return $http.get('/frontend/eng_mytask/stafflist?value=' + val + '&client_id=' + client_id)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };
   

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
        $scope.eng.property_id = $scope.requester.property_id;
     
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


    $scope.getLocationList = function (val) {
        if (val == undefined)
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function (response) {
                return response.data.map(function (item) {
                    return item;
                });
            });
    };



    $scope.onLocationSelect = function ($item, $model, $label) {
       // window.alert(JSON.stringify($item));
        $scope.eng.location=$item.name;
          if($item.type=='Room')
    {
            $scope.eng.location_item = $item.type + ' ' + $item.name;
    }
     else  
      $scope.eng.location_item = $item.name;
    };


    $scope.createEng = function () {
      //  window.alert("here");
        $scope.disable_create = 1;
        var request = {};

        request.client_id = client_id;
        request.property_id = $scope.eng.property_id;

        request.requestor_id = $scope.requester.id;

        if (!(request.requestor_id > 0)) {
            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'You did not select requestor');
            $scope.disable_create = 0;
            return;
        }

        
        request.severity = $scope.eng.severity;
        request.status = $scope.eng.status;
        request.initial_response = $scope.eng.initial_response;
        request.housecomplaint_id = $scope.eng.housecomplaint_id;
        request.incident_time = $scope.eng.incident_time;
        request.category = $scope.eng.category;
        request.subcategory = $scope.eng.subcategory;
        request.location = $scope.eng.location_item;
        request.id=$scope.eng_id


        if (!request.category) {

            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'Please select Category');
            $scope.disable_create = 0;
            return;
        }

        if (!request.initial_response) {

            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'Please enter Subject of Request');
            $scope.disable_create = 0;
            return;
        }


       

        request.comment = $scope.eng.comment;

   
        if (!request.comment) {

            toaster.pop('info', MESSAGE_TITLE + INCOMP, 'You did not enter engineering request');
            $scope.disable_create = 0;
            return;
        }

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/eng_mytask/post',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.disable_create = 0;
            toaster.pop('success', MESSAGE_TITLE, response.data.message);

 
            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/eng_mytask/uploadfiles',
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

            $scope.cancelEng();
            $scope.pageChanged();
            $scope.eng_id = response.data.id + 1;
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Engineering Request.');
           
            $scope.disable_create = 0;
        })
            .finally(function () {

            });
    }





});
