app.service('TranslateService', function ($http, $httpParamSerializer) {
    this.translate = function(text, from, to) {
    	var request = {
	            key:"AIzaSyBXzVNjgOdra7iyK6rHeN2nJv6maIptE1Y",
	            source: from,
	            target: to,
	            q: text
            };

        var param = $httpParamSerializer(request);

        return $http.get('https://www.googleapis.com/language/translate/v2?' + param);
    }
});
