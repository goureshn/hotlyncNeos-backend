'use strict';

app.controller('PromotionController', function($rootScope, $scope, $http, $state, $interval, $stateParams, $window, $timeout, toaster, AuthService, socket) {
    var MESSAGE_TITLE = 'Promotion Page';

    $scope.pro_condition = "list";
    $scope.promotion = {};
    $scope.datetime = {};
    $scope.enquiry = {};
    $scope.datetime.start_date = new Date();
    $scope.datetime.start_time = '';

    $scope.$watch('datetime.start_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.datetime.start_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
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

    $scope.pageChanged = function() {
        $scope.ticketlist = [];
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        var url = '/guest/promotionlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.datalist;

            for(var i = 0; i < $scope.ticketlist.length ; i++) {
                var path = $scope.ticketlist[i].path;
                var title = $scope.ticketlist[i].title;
                var path_array = path.split('|');
                var images = [];
                for( var j=0 ; j < path_array.length; j++) {
                    var ext ='';
                    if ( /\.(jpe?g|png|gif|bmp)$/i.test(path_array[j]) ) {
                        ext= 'image';
                    }else {
                        ext= 'video';
                    }
                    var image = {src:'/'+path_array[j], title:title, ext:ext};
                    console.log(image);
                    images.push(image);
                }
                $scope.ticketlist[i].images = images;
            }
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };
    $scope.pageChanged();

    $scope.viewPromotion = function(promotion) {
        $scope.promotion = promotion;
        $scope.pro_condition = "view";
        $scope.migration();
    }

    $scope.insertPromotion = function() {
        $scope.pro_condition = "insert";
        var profile = AuthService.GetCredentials();
        $scope.enquiry.name = profile.guest_name;
        $scope.enquiry.room = profile.room;
        $scope.migration();
    }

    $scope.sendEnquiry = function () {
        var request = {};
        if($scope.enquiry.name == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter name.');
            return;
        }
        if($scope.enquiry.room == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter room.');
            return;
        }
        if($scope.enquiry.pax == null) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter pax.');
            return;
        }
        if($scope.enquiry.email == null) {
            $scope.enquiry.email = '';
        }
        if($scope.enquiry.contact_no == null) {
            $scope.enquiry.contact_no = '';
        }
        if($scope.enquiry.comment == null) {
            $scope.enquiry.comment = '';
        }
        request = $scope.enquiry;

        request.disclaimer = $scope.promotion.disclaimer;
        request.start_date = $scope.datetime.start_time;
        request.enquiry_to = $scope.promotion.enquiry_to;
        request.outlet_name = $scope.promotion.outlet_name;
        request.promotion_id = $scope.promotion.id;
        request.title = $scope.promotion.title;
        request.price = $scope.promotion.price;
        request.discnt = $scope.promotion.discnt;
        request.start_date = $scope.promotion.start_date;
        request.end_date = $scope.promotion.end_date;
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        var url = '/guest/sendenquiry';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', MESSAGE_TITLE, 'Email has been send to agent successfully');
            $scope.enquiry.pax = null;
            $scope.enquiry.email = null;
            $scope.enquiry.contact_no = null;
            $scope.enquiry.comment = null;
            $scope.pro_condition = "list";
            $scope.migration();
            console.log(response);
        }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to send email.');
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    //go to
    $scope.migration = function() {
        $rootScope.$broadcast('current_page', 'promotion_'+$scope.pro_condition);
    }
    $scope.$on('before_page', function (val) {
        switch(val) {
            case 'promotion_view' :
                $scope.pro_condition = 'list';
                break;
            case 'promotion_insert' :
                $scope.pro_condition = 'view';
                break;
        }
        $scope.backPage();
    });


    $scope.backPage = function(){
        var cond =  $scope.pro_condition;
        switch(cond) {
            case 'list' :
                $state.go('app.first');
                break;
            case 'view' :
                $scope.pro_condition = 'list';
                break;
            case 'insert' :
                $scope.pro_condition = 'view';
                break;
        }
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
        '<li ng-repeat="image in images" ng-click="toggleStartStop()" ng-swipe-right="sliderBack()" ng-swipe-left="sliderForward()"><img data-ng-src="{{image.src}}" data-ng-alt="{{image.alt}}" ng-class="show($index)"/></li>' +
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

