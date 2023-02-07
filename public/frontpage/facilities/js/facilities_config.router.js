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

                //$rootScope.$on('$locationChangeStart', function (event, next, current) {
                //    // redirect to login page if not logged in
                //    if ($location.path() !== '/login' && !$rootScope.globals.currentUser) {
                //        $location.path('/login');
                //    }
                //});

                $rootScope.$on('$stateChangeStart', function(event, toState, toStateParams, fromState, fromStateParams) {
                    if (toState.name.indexOf('access') < 0 && !AuthService.isAuthenticated()) {
                        event.preventDefault();
                        $state.go('access.signin');
                    }
                    else
                    {
                        if( toState.name.indexOf('access') < 0 ) {
                            $rootScope.profile = AuthService.GetCredentials();
                            if (AuthService.isValidModule(toState.name) == false) {
                                event.preventDefault();
                                $state.go('access.signin');
                            }
                        }
                    }
                });

            }
        ]
    )
    .config(
        [          '$stateProvider', '$urlRouterProvider', 'JQ_CONFIG', 'MODULE_CONFIG',
            function ($stateProvider,   $urlRouterProvider, JQ_CONFIG, MODULE_CONFIG) {

                var layout = "facilities/tpl/facilities_app.layout.html";
                $urlRouterProvider
                    .otherwise('/app/guestservice/guestinfo');

                $stateProvider
                    .state('app', {
                        abstract: true,
                        url: '/app',
                        templateUrl: layout
                    })       
                    .state('access', {
                        url: '/access',
                        template: '<div ui-view class="fade-in-right-big smooth"></div>'
                    })
                    .state('access.signin', {
                        url: '/signin',
                        templateUrl: 'facilities/tpl/page_signin.html',
                        resolve: load( [
                            'facilities/js/facilities_signin.js',
                        ] )
                    })
                    .state('access.signup', {
                        url: '/signup',
                        templateUrl: 'tpl/page_signup.html',
                        resolve: load( ['js/controllers/signup.js'] )
                    })
                    .state('access.forgotpwd', {
                        url: '/forgotpwd',
                        templateUrl: 'tpl/page_forgotpwd.html',
                        resolve: load( ['js/controllers/forgot.js'] )
                    })
                    .state('access.changepass', {
                        url: '/changepass',
                        templateUrl: 'tpl/page_changepass.html',
                        resolve: load( [
                            'js/controllers/profile/changepass.js',
                        ] )
                    })
                    .state('access.404', {
                        url: '/404',
                        templateUrl: 'tpl/page_404.html'
                    })
                     .state('app.guestservice', {
                        url: '/guestservice',
                        template: '<div ui-view></div>',
                        resolve: load(['js/controllers/guestservice/guestservice.controller.js'])
                    })
                     .state('app.guestservice.guestinfo', {
                        url: '/guestinfo',
                        templateUrl: 'tpl/guestservice/guest_info.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'ui.bootstrap.datetimepicker',
                            'js/controllers/guestservice/guestinfo.controller.js'
                        ])
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