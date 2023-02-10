'use strict';

/**
 * Config for the router
 */
angular.module('app')
    .run(
        [          '$rootScope', '$state', '$stateParams', 'AuthService',
            function ($rootScope,   $state,   $stateParams, AuthService) {
                $rootScope.$state = $state;
                $rootScope.$stateParams = $stateParams;

                $rootScope.$on('$stateChangeStart', function(event, toState, toStateParams, fromState, fromStateParams) {
                    if (toState.name.indexOf('access') < 0 && !AuthService.isAuthenticated()) {
                        event.preventDefault();
                        $state.go('access.login');
                    }
                });

            }
        ]
    )
    .config(
        [          '$stateProvider', '$urlRouterProvider', 'JQ_CONFIG', 'MODULE_CONFIG',
            function ($stateProvider,   $urlRouterProvider, JQ_CONFIG, MODULE_CONFIG) {

                var layout = "guest/tpl/layout/app.layout.html";
                $urlRouterProvider
                    .otherwise('/access/login/4');

                $stateProvider
                    .state('access', {
                        url: '/access',
                        template: '<div ui-view class="fade-in-right-big smooth"></div>'
                    })
                    .state('access.login', {
                        url: '/login/:property_id',
                        templateUrl: 'guest/tpl/guest_login.html',
                        resolve: load( [
                            'toaster',
                            'guest/js/auth/guest_login.controller.js',
                        ] )
                    })
                    .state('app', {
                        abstract: true,
                        url: '/app',
                        templateUrl: layout
                    })
                    .state('app.chat', {
                        url: '/chat',
                        templateUrl: 'guest/tpl/chat/chat.html',
                        resolve: load( [
                            'toaster', 'luegg.directives',
                            'js/services/translate.service.js',
                            'guest/js/chat/chat.controller.js',
                        ] )
                    })
					.state('app.feedback', {
                        url: '/feedback',
                        templateUrl: 'guest/tpl/feedback/feedback.html',
                        resolve: load( [
                           'luegg.directives',
							'toaster','moment',  'ui.bootstrap.datetimepicker', 'bootstrapLightbox',
                            'guest/services/text_html.service.js',  'guest/services/text_html_esc.service.js',
                            'guest/js/feedback/feedback.controller.js',
                        ] )
                    })
                    .state('app.first', {
                        url: '/first',
                        templateUrl: 'guest/tpl/first/first.html',
                        resolve: load( [
                            'toaster',
                            'guest/js/first/first.controller.js',
                        ] )
                    })
                    .state('app.first-phone', {
                        url: '/simulator-first',
                        templateUrl: 'guest/tpl/first/first-simulator.html',
                        resolve: load( [
                            'toaster',
                            'guest/js/first/first-simulator.controller.js',
                        ] )
                    })
                    .state('app.promotion', {
                        url: '/promotion',
                        templateUrl: 'guest/tpl/promotion/promotion.html',
                        resolve: load( [
                            'toaster','moment',  'ui.bootstrap.datetimepicker', 'bootstrapLightbox',
                            'guest/services/text_html.service.js',  'guest/services/text_html_esc.service.js',
                            'guest/js/promotion/promotion.controller.js',
                        ] )
                    })
                    .state('app.request', {
                        url: '/request',
                        templateUrl: 'guest/tpl/request/request.html',
                        resolve: load( [
                            'toaster','moment', 'bootstrapLightbox','ui.bootstrap.datetimepicker',
                            'guest/js/request/request.controller.js',
                            'guest/services/guestservice.service.js',
                        ] )
                    })
                    .state('access.404', {
                        url: '/404',
                        templateUrl: 'tpl/page_404.html'
                    });


                function load(srcs, callback) {
                    return {
                        deps: ['$ocLazyLoad', '$q',
                            function( $ocLazyLoad, $q ){
                                var deferred = $q.defer();
                                var promise  = false;
                                srcs = angular.isArray(srcs) ? srcs : srcs.split(/\s+/);
                                if(!promise){
                                    promise = deferred.promise;
                                }
                                angular.forEach(srcs, function(src) {
                                    promise = promise.then( function(){
                                        if(JQ_CONFIG[src]){
                                            return $ocLazyLoad.load(JQ_CONFIG[src]);
                                        }
                                        angular.forEach(MODULE_CONFIG, function(module) {
                                            if( module.name == src){
                                                name = module.name;
                                            }else{
                                                name = src;
                                            }
                                        });
                                        return $ocLazyLoad.load(name);
                                    } );
                                });
                                deferred.resolve();
                                return callback ? promise.then(function(){ return callback(); }) : promise;
                            }]
                    }
                }


            }
        ]
    );
