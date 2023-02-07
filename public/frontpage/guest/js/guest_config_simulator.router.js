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

                // $rootScope.$on('$stateChangeStart', function(event, toState, toStateParams, fromState, fromStateParams) {
                //     if (toState.name.indexOf('access') < 0 && !AuthService.isAuthenticated()) {
                //         event.preventDefault();
                //         $state.go('access.login');
                //     }
                // });

            }
        ]
    )
    .config(
        [          '$stateProvider', '$urlRouterProvider', 'JQ_CONFIG', 'MODULE_CONFIG',
            function ($stateProvider,   $urlRouterProvider, JQ_CONFIG, MODULE_CONFIG) {

                var layout = "guest/tpl/layout/app.layout.html";
                $urlRouterProvider
                    .otherwise('/app/first-simulator');

                $stateProvider
                    .state('app', {
                        abstract: true,
                        url: '/app',
                        templateUrl: layout
                    })
                    .state('app.chat-simulator', {
                        url: '/chat-simulator',
                        templateUrl: 'guest/tpl/chat/chat-simulator.html',
                        resolve: load( [
                            'toaster', 'luegg.directives',
                            'js/services/translate.service.js',
                            'guest/js/chat/chat-simulator.controller.js',
                        ] )
                    })
                    .state('app.first-simulator', {
                        url: '/first-simulator',
                        templateUrl: 'guest/tpl/first/first-simulator.html',
                        resolve: load( [
                            'toaster',
                            'guest/js/first/first-simulator.controller.js',
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
