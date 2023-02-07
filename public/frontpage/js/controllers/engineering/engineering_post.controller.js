'use strict';

app.controller('EngineeringPostController', function($scope, $http, $window, $interval, $timeout, $stateParams, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Engineering Status';
    var INCOMP = ' INCOMPLETE';

    var client_id = $stateParams.client_id;
    $scope.priority_list = ['Low', 'Medium','High','Urgent'];

    $scope.count= false;
    $scope.disable_create=0;

    $scope.includeMobile = false;
    var screenWidth = $window.innerWidth;
    if (screenWidth < 550){
        $scope.includeMobile = true;
    }


    $scope.cancelEng = function() {
        $scope.eng = {};
        $scope.location = {};
        $scope.category_name = '';
        $scope.sub_category_name = '';
        $scope.requester = {};
        if( $scope.property_list && $scope.property_list.length > 0 )
            $scope.eng.property_id = $scope.property_list[0].id;
        $scope.eng.priority = $scope.priority_list[0];

        $scope.count = false;
        $scope.files = [];


    }

    $scope.ticketNumer = function(number) {
        return sprintf('E%05d', number);
    }

    $scope.init = function() {
        $http.get('/list/property?client_id='+client_id)
            .then(function(response) {
                $scope.property_list = response.data;
            });

        $http.get('/frontend/eng/id')
            .then(function(response) {
                $scope.eng_id = $scope.ticketNumer(response.data.max_id + 1);
            });

        $http.get('/list/employeelist?client_id=' + client_id)
            .then(function(response){
                $scope.employee_list = response.data;
            });

        $timeout( function() {
            $scope.cancelEng();

            $scope.timer = $interval(function() {
                $scope.eng.request_time = moment().format("HH:mm:ss");
            }, 1000);

        }, 1500 );
    }

    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
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
    };

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.location = $item;
    };


    $scope.getCategoryList = function(val) {
        if( val == undefined )
            val = "";
        var property_id = $scope.eng.property_id;
        return $http.get('/list/engcategorylist?category=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.eng.category_id = $item.id;
        $scope.eng.category_name = $item.name;
    };

    $scope.getSubCategoryList = function(val) {
        if( val == undefined )
            val = "";
        var property_id = $scope.eng.property_id;
        var category_id = $scope.eng.category_id;
        return $http.get('/list/engsubcategorylist?subcategory=' + val + '&category_id=' + category_id+'&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onSubCategorySelect = function ($item, $model, $label) {
        $scope.eng.sub_category_id = $item.id;
        $scope.eng.sub_category_name = $item.name;
    };




    // $scope.getStaffList = function(val) {
    //     if( val == undefined )
    //         val = "";
    //     return $http.get('/frontend/complaint/stafflist?value=' + val + '&client_id=' + client_id)
    //         .then(function(response){
    //             return response.data.map(function(item){
    //                 return item;
    //             });
    //         });
    // };

    $scope.onRequesterSelect = function ($item, $model, $label) {
        $scope.requester = $item;
    };

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);
    };

    $scope.createEng = function(){
	    $scope.disable_create=1;
         $scope.createEngIndividial($scope.eng);
    }


    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }

    $scope.createEngIndividial = function(eng) {
        var request = {};

        request.client_id = client_id;
        request.property_id = eng.property_id;
        request.loc_id = $scope.location.id;
        request.priority = eng.priority;
        request.requestor_id = $scope.requester.id;

        if( !(request.requestor_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select requestor');
            $scope.disable_create=0;
            return;
        }

        if( !(request.loc_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select location');
            $scope.disable_create=0;
            return;
        }

        request.category_id = eng.category_id;
        if( !(request.category_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select category');
            $scope.disable_create=0;
            return;
        }

        request.sub_category_id = eng.sub_category_id;
        if( !(request.sub_category_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select sub-category');
            $scope.disable_create=0;
            return;
        }
        request.subject = eng.subject;
        if(request.subject == '')
        {
            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not select Subject');
            $scope.disable_create=0;
            return;
        }


        request.comment = $scope.eng.comment;
        //not working
        if( !request.comment  )
        {

            toaster.pop('info', MESSAGE_TITLE+INCOMP, 'You did not enter issue');
            $scope.disable_create=0;
            return;
        }

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/eng/post',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.disable_create=0;
            toaster.pop('success', MESSAGE_TITLE, response.data.message);

            // upload files
            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/eng/uploadfiles',
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
            $scope.eng_id = $scope.ticketNumer(response.data.id + 1);
        }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to post Engineering request.');
                $scope.disable_create=0;
            })
            .finally(function() {

            });
    }

});