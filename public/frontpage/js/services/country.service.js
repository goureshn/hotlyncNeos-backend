'use strict';
app.factory('CountryService',
    function ($http) {
        var service = {};
        service.countrylist = [];

        $http.get('/list/country')
            .then(function(response) {
                for(var i = 0; i < response.data.length; i++ )
                    service.countrylist.push(response.data[i]);
            });


        return service;
    });
