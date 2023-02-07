    'use strict';
    app.factory('AuthService', function () {
        // Simulate a service
        return {
            getTest: function () {
                return 'This is test for service';
            },
            isAuthenticated : function(rootScope, localStorage) {
                if( rootScope.globals && rootScope.globals.currentUser )
                    return true;

                rootScope.globals = localStorage.globals;
                if( rootScope.globals && rootScope.globals.currentUser )
                    return true;

                return false;
            },
            isValidModule: function (page, service,rootScope,localStorage ) {
                if (service.isAuthenticated(rootScope,localStorage) == false)
                    return false;

                var permission = rootScope.globals.currentUser.permission;
                for (var i = 0; i < permission.length; i++) {
                    if (page == permission[i].name)
                        return true;
                }

                return false;
            }
        };
    });


