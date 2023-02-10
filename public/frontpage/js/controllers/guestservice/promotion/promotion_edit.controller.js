app.controller('PromotionEditController', function ($scope, $rootScope, $http, $interval, AuthService, GuestService, toaster,Upload) {
    var MESSAGE_TITLE = 'Promotion Edit';
    $scope.datetime = {};
    $scope.disable = true
    $scope.status_list = [];
    $scope.edit_promotion = {};
    $scope.pre_promotion = {};

    $scope.datetime.start_date = new Date();
    $scope.datetime.end_date = new Date();
    $scope.datetime.start_time = '';
    $scope.datetime.end_time = '';

    $scope.init = function(promotion) {
       
        $scope.files = [];
        $scope.disable = true;
        $scope.preview = false;

        $scope.edit_promotion = promotion;
        $scope.datetime.start_time = promotion.start_date;
        $scope.datetime.end_time = promotion.end_date;

        $scope.readonlystatus(); //change input  readonly status    
        if(promotion.status== 'Enabled') $scope.edit_promotion.status = 'Active';

        switch($scope.edit_promotion.status) {
            case 'Active' :                
                 $scope.status_list = [
                    'Active',                    
                    'Disable',                    
                    'Cancel',                    
                ];
                break;
            case 'Disable' :                
                 $scope.status_list = [                    
                    'Disable',
                    'Enabled',                    
                    'Cancel',
                ];
                break;
            case 'Enabled' :                
                 $scope.status_list = [ 
                    'Enabled',                   
                    'Disable',                    
                    'Cancel',                    
                ];
                break;
            case 'Expired' :                
                 $scope.status_list = [                    
                    'Expired',
                    'Extended',                    
                ];
                break;
            case 'Cancel' :                
                 $scope.status_list = [                    
                    'Cancel',
                ];
                break;
            case 'Extended' :                
                 $scope.status_list = [
                    'Extended',
                    'Active',
                    'Disable',                                        
                    'Cancel',
                ];
                break;
            case 'Scheduled' :                
                 $scope.status_list = [
                    'Scheduled',
                    'Active',                    
                ];
                break;                                            
        }
                
    

        var path = promotion.path;
        var path_array = path.split('|');
        var profile = AuthService.GetCredentials();
        var server_ip = profile.server_ip;

        for(var i =0; i < path_array.length ; i++ ) {
            var url = server_ip+path_array[i];    
            console.log(url);         

            Upload.urlToBlob(url).then(function(blob) {
                blob.lastModifiedDate = new Date();
                var file_names = blob.name.split('_');
                var name = file_names[file_names.length-1];
                blob.name = name;
                $scope.files.push(blob);
            });            
        }        
        getPromotionHistory();
    }

    $scope.$watch('datetime.start_date', function(newValue, oldValue) {
        
        if( newValue == oldValue )
            return;

        $scope.datetime.start_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.$watch('datetime.end_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.datetime.end_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });
    
    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {        
        if( $view == 'day' )
        {
            var activeDate = moment().subtract('days', 1);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        else if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 0);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.emailCheck = function(value) {
        var EMAIL_REGEXP = /^[a-z0-9!#$%&'*+/=?^_`{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*$/i;
        return EMAIL_REGEXP.test(value);
    }

    $scope.updatePromotion = function() {
        if($scope.disable == false) {
            var request = {};
            var confirm_val = $scope.confirm();
            if(confirm_val == false) {
                return ;
            }
            request = $scope.edit_promotion;
            var profile = AuthService.GetCredentials();

            request.property_id = profile.property_id;
            request.user_id = profile.id;
            request.start_date = $scope.datetime.start_time;
            request.end_date = $scope.datetime.end_time;

            $http({
                method: 'POST',
                url: '/frontend/guest/promotion/update',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {

                if( response.status != 200 )
                {
                    toaster.pop('error', MESSAGE_TITLE, 'This data can not save. please try it.');
                    return;
                }

                if ($scope.files && $scope.files.length) {
                    Upload.upload({
                        url: '/frontend/guest/promotion/uploadfiles',
                        data: {
                            id: response.data.id,
                            files: $scope.files
                        }
                    }).then(function (response) {
                        //$scope.files = [];
                        //$scope.promotion.path = response.data.path;
                        //$scope.init($scope.promotion);
                        $scope.cancelPromotion();
                    }, function (response) {
                        //$scope.files = [];
                        if (response.status > 0) {
                            $scope.errorMsg = response.status + ': ' + response.data;
                        }
                    }, function (evt) {
                        $scope.progress =
                            Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                    });
                }

                $scope.disable = true;
                $scope.readonlystatus();

                $scope.$emit('ChangedGuestPromotion', response.data);
                //toaster.pop('success', MESSAGE_TITLE, 'Guest service Promotion have been successed to create');

            }).catch(function(response) {
                    toaster.pop('error', MESSAGE_TITLE, 'Promotion have been failed to create');
                })
                .finally(function() {

                });

        }
    }

    $scope.cancelPromotion = function() {
        $scope.disable = true;
    }

    var paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'asc',
        field: 'id',
    };

    var columns = [
        {
            field : 'id',
            displayName : "ID",
            enableCellEdit: false,
        },
        {
            field : 'status',
            displayName : "Action",
            enableCellEdit: false,
        },
        {
            field : 'created_at',
            displayName : "Date&Time",
            enableCellEdit: false,
        },
        {
            field : 'user',
            displayName : "User",
            enableCellEdit: false,
        },
    ];

    $scope.gridOptions =
    {
        enableGridMenu: false,
        enableRowHeaderSelection: false,
        enableColumnResizing: true,
        paginationPageSizes: [10, 20, 30, 40],
        paginationPageSize: 10,
        useExternalPagination: true,
        useExternalSorting: true,
        columnDefs: columns,
    };

    $scope.gridOptions.onRegisterApi = function( gridApi ) {
        $scope.gridApi = gridApi;
        
        gridApi.core.on.sortChanged($scope, function(grid, sortColumns) {
            if (sortColumns.length == 0) {
                paginationOptions.sort = 'asc';
                paginationOptions.field = 'id';
            } else {
                paginationOptions.sort = sortColumns[0].sort.direction;
                paginationOptions.field = sortColumns[0].name;
            }
            getPromotionHistory();
        });
        gridApi.pagination.on.paginationChanged($scope, function (newPage, pageSize) {
            paginationOptions.pageNumber = newPage;
            paginationOptions.pageSize = pageSize;
            getPromotionHistory();
        });
    };

    var getPromotionHistory = function() {
        var request = {};

        request.id = $scope.edit_promotion.id;
        request.page = paginationOptions.pageNumber;
        request.pagesize = paginationOptions.pageSize;
        request.field = paginationOptions.field;
        request.sort = paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/guest/promotion/logs',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.gridOptions.totalItems = response.data.totalcount;
            $scope.gridOptions.data = response.data.datalist;
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
    };

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);       
    };

    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }

    $scope.removeCurrentRequest = function() {
        $scope.onSelectTicket($scope.promotion);
    }

    // $scope.$on('ChangedGuestPromotion', function(event, args){
    //     if( $scope.promotion.id != args.id )
    //         return;

    //     $scope.init(args);
    // });
     $scope.readstatus = {};
    $scope.readonlystatus = function(){
        $scope.readstatus.outlet_name = true;
        $scope.readstatus.status = true; 
        $scope.readstatus.title = true; 
        $scope.readstatus.price = true;     
        $scope.readstatus.discount = true;
        $scope.readstatus.start_date = true;
        $scope.readstatus.end_date = true;
        $scope.readstatus.disclaimer = true;  
        $scope.readstatus.send_enquiry_to = true;
        $scope.readstatus.description = true;
        $scope.readstatus.highlight = true;
        $scope.readstatus.condition = true;
        $scope.readstatus.image = true;                
    }

   
    $scope.editPromotion = function(val) {
        $scope.disable = false;
        $scope.preview = false;
        switch(val) {
            case 'outlet_name' :                
                 $scope.readstatus.outlet_name = false;            
                break;
            case 'status' :                
                 $scope.readstatus.status = false;            
                break;    
            case 'title' :                
                 $scope.readstatus.title = false;            
                break; 
            case 'price' :                
                 $scope.readstatus.price = false;            
                break; 
            case 'discount' :                
                 $scope.readstatus.discount = false;            
                break; 
            case 'start_date' :                
                 $scope.readstatus.start_date = false;            
                break;
            case 'end_date' :                
                 $scope.readstatus.end_date = false;            
                break;
            case 'disclaimer' :                
                 $scope.readstatus.disclaimer = false;            
                break;
            case 'send_enquiry_to' :                
                 $scope.readstatus.send_enquiry_to = false;            
                break; 
            case 'description' :                
                 $scope.readstatus.description = false;            
                break;
            case 'highlight' :                
                 $scope.readstatus.highlight = false;            
                break;
            case 'condition' :                
                 $scope.readstatus.condition = false;            
                break;     
            case 'image' :                
                 $scope.readstatus.image = false;            
                break;                                                                                                       
           }    
    }


    $scope.previewPromotion = function(){

        if($scope.preview == true) {
            $scope.preview = false;
        }else {
            $scope.preview = true;
        }
        if($scope.preview == true) {
            $scope.pre_promotion =  angular.copy($scope.edit_promotion);
            var path = $scope.pre_promotion.path;
            var title = $scope.pre_promotion.title;
            var path_array = path.split('|');
            var images = [];
            for (var j = 0; j < path_array.length; j++) {
                var ext = '';
                if (/\.(jpe?g|png|gif|bmp)$/i.test(path_array[j])) {
                    ext = 'image';
                } else {
                    ext = 'video';
                }
                var image = {src: '/' + path_array[j], title: title, ext: ext};
                console.log(image);
                images.push(image);
            }
            $scope.pre_promotion.images = images;
        }
    }

    $scope.confirm = function() {
        var confirm_val= true;
        if($scope.edit_promotion.outlet_name == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter outlet name.');
            confirm_val = false;
        }
        if($scope.edit_promotion.title == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter title.');
            confirm_val = false;
        }
        if($scope.edit_promotion.price == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter price.');
            confirm_val = false;
        }
        if($scope.edit_promotion.discnt == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter discount.');
            confirm_val = false;
        }
        if($scope.datetime.start_time == '' || !$scope.datetime.start_time ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter start date.');
            confirm_val = false;
        }
        if($scope.datetime.end_time == '' || !$scope.datetime.end_time ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter end date.');
            confirm_val = false;
        }
        if($scope.edit_promotion.highlight == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter highlight.');
            confirm_val = false;
        }

        if($scope.edit_promotion.enquiry_to == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter send enquiry to.');
            confirm_val = false;
        }

        var emails = $scope.edit_promotion.enquiry_to.split(',');
        var flag = false;
        for(var i =0 ; i < emails.length; i++) {
            var flag = $scope.emailCheck(emails[i].replace(/[\s]/g, ''));
            if(flag == false){
                break;
            }
        }
        if(flag == false){
            toaster.pop('error', MESSAGE_TITLE, 'Enquiry to is not right email format.');
            confirm_val = false;
        }

        return confirm_val;

    }

});

app.directive('ngFader', function($interval) {

    function link(scope){

        //Set your interval time. 4000 = 4 seconds
        scope.setTime = 4000;

        //List your images here.
        // scope.images = [{
        //     src: 'img/fader-images/1.png',
        //     alt: 'The Beach'
        // }, {
        //     src: 'img/fader-images/2.png',
        //     alt: 'The Beach'
        // }, {
        //     src: 'img/fader-images/3.png',
        //     alt: 'The Beach'
        // }, {
        //     src: 'img/fader-images/4.png',
        //     alt: 'The Beach'
        // }, {
        //     src: 'img/fader-images/5.png',
        //     alt: 'The Beach'
        // }, {
        //     src: 'img/fader-images/6.png',
        //     alt: 'The Beach'
        // }];

        /*****************************************************
         STOP! NO FURTHER CODE SHOULD HAVE TO BE EDITED
         ******************************************************/

        //Pagination dots - gets number of images
        scope.numberOfImages = scope.images.length;
        scope.dots = function(num) {
            return new Array(num);
        };

        //Pagination - click on dots and change image
        scope.selectedImage = 0;
        scope.setSelected = function (idx) {
            scope.stopSlider();
            scope.selectedImage = idx;
        };

        //Slideshow controls
        scope.sliderBack = function() {
            scope.stopSlider();
            scope.selectedImage === 0 ? scope.selectedImage = scope.numberOfImages - 1 : scope.selectedImage--;
        };

        scope.sliderForward = function() {
            scope.stopSlider();
            scope.autoSlider();
        };

        scope.autoSlider = function (){
            scope.selectedImage < scope.numberOfImages - 1 ? scope.selectedImage++ : scope.selectedImage = 0;
        };

        scope.stopSlider = function() {
            $interval.cancel(scope.intervalPromise);
            scope.activePause = true;
            scope.activeStart = false;
        };

        scope.toggleStartStop = function() {
            if(scope.activeStart) {
                scope.stopSlider();
            } else {
                scope.startSlider();
            }
        };

        scope.startSlider = function(){
            scope.intervalPromise = $interval(scope.autoSlider, scope.setTime);
            scope.activeStart = true;
            scope.activePause = false;
        };
        scope.startSlider();

        scope.show = function(idx){
            if (scope.selectedImage==idx) {
                return "show";
            }
        };

    }

    return {
        restrict: 'AE',
        scope:{
            images: '='
        },
        template: '<div class="ng-fader">'+
        //images will render here
        '<ul>' +
        '<li ng-repeat="image in images"  ng-click="toggleStartStop()" ng-swipe-right="sliderBack()" ng-swipe-left="sliderForward()">' +
        ' <img data-ng-src="{{image.src}}" data-ng-alt="{{image.alt}}" ng-class="show($index)"/></li>' +
        '</ul>' +
        //pagination dots will render here
        '<div class="ng-fader-pagination">' +
        '<ul>' +
        '<li ng-repeat="i in dots(numberOfImages) track by $index" ng-class="{current: selectedImage==$index}" ng-click="setSelected($index)"></li>' +
        '</ul>' +
        '</div>' +
        //controls are here
        // '<div class="ng-fader-controls">' +
        // '<ul>' +
        // '<li ng-click="sliderBack()">' +
        // '<i class="ngfader-back"></i>' +
        // '</li>' +
        // '<li ng-click="stopSlider()">' +
        // '<i class="ngfader-pause" ng-class="{\'active\': activePause}"></i>' +
        // '</li>' +
        // '<li ng-click="startSlider()">' +
        // '<i class="ngfader-play"  ng-class="{\'active\': activeStart}"></i>' +
        // '</li>' +
        // '<li ng-click="sliderForward()">' +
        // '<i class="ngfader-forward"></i>' +
        // '</li>' +
        // '</ul>' +
        // '</div>' +
        '</div>',
        link: link
    };
});



