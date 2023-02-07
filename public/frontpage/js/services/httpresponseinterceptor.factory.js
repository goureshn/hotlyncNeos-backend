'use strict';
app.factory('myHttpInterceptor', function ($q, $location, $rootScope, $localStorage, $sessionStorage) {
    var $storage = $sessionStorage;
    return {
        // request: function(config) {
        //     // same as above            
        //     var method = config.method;
        //     var key = '';
        //     if( method == 'POST' )
        //         key = method + config.url + JSON.stringify(config.data);
            
        //     if( method == 'GET' )
        //         key = method + config.url;
            
        //     key = sha1(key);
        //     var hash_key = key + '_result_hash';

        //     var saved_data = $localStorage[key];
        //     if( saved_data )            
        //     {                
        //         var hash = $localStorage[hash_key];

        //         if( method == 'POST' )
        //             config.data.hash = hash;
        //         if( method == 'GET' && (config.url.includes('/frontend') || config.url.includes('/list') ) )
        //         {
        //             if( config.url.includes('?') )
        //                 config.url = config.url + "&hash=" + hash;    
        //             else    
        //                 config.url = config.url + "?hash=" + hash;    
        //         }
        //     }

        //     return config;
        // },
        response: function (response) {
            //// do something on success
            //if(response.headers()['content-type'] === "application/json"){
            //    // Validate response, if not ok reject
            //    return $q.reject(response);
            //}

            // var config = response.config;
            // var method = config.method;
            // var key = '';
            // if( method == 'POST' )
            // {
            //     config.data.hash = undefined;
            //     key = method + config.url + JSON.stringify(config.data);
            // }
            
            // if( method == 'GET' && (config.url.includes('/frontend') || config.url.includes('/list') ) )
            // {
            //     key = method + config.url;

            //     var key_list = [];

            //     if( config.url.includes('?hash=') )
            //         key_list = key.split('?hash=');
            //     else    
            //         key_list = key.split('&hash=');

            //     if( key_list.length > 0 )                
            //         key = key_list[0];
            // }
            
            // key = sha1(key);
            // var hash_key = key + '_result_hash';
            
            // if( response.data.sync == 1)
            // {
            //     try {
            //         response.data = JSON.parse($localStorage[key]);
            //     } catch(e) {
            //         response.data = {};
            //         $localStorage[key] = undefined;
            //         $localStorage[hash_key] = "";
            //     }
            // }
            // else
            // {
            //     if( method == 'POST' || method == 'GET' && (config.url.includes('/frontend') || config.url.includes('/list') ))
            //     {
            //         $localStorage[key] = JSON.stringify(response.data);
            //         $localStorage[hash_key] = response.headers()['result_hash'];
            //     }
            // }

            return response;
        },
        responseError: function (response) {
            // do something on error
            if( response.status == 401 ) {
                event.preventDefault();
                // clear login info
                $rootScope.globals = {};
                delete $storage.globals;

                console.log(response.data);

                $location.path('/access/signin');
                setTimeout(function() {
                    alert(response.data);
                }, 1000);
                
            }
            return $q.reject(response);            
        }
    };
});
app.config(function ($httpProvider) {
    $httpProvider.interceptors.push('myHttpInterceptor');
});