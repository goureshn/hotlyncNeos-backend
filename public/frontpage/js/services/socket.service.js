'use strict';
app.factory('socket',
   ['socketFactory', '$location', 'Base64', function (socketFactory, $location, Base64) {
       	//var myIoSocket = io.connect('52.165.34.85:8001');

       	var absolute_url = $location.absUrl();
    		var first_part = absolute_url.substring(0, 7);

    		var myIoSocket = null;

        // get app config.
        var value = angular.element('#app_config').val();
        value = Base64.decode(value);
        var app_config = JSON.parse(value);

    		myIoSocket = io.connect(app_config.live_server);		    
		
        var mySocket = socketFactory({
           ioSocket: myIoSocket
        });

        return mySocket;
   }]);
