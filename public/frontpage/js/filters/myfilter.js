'use strict';

/* Filters */
// need load the moment.js to use this filter. 
angular.module('app')
  	.filter('mydate', function() {
	    return function(date, format) {
	    	if( !format )
	    		format = 'DD MMM YYYY';

	    	if( !date || date == '0000-00-00 00:00:00' )
	    		return '--:--';

	      	return moment(date).format(format);
	    }
 	})
 	.filter('mydatetime', function() {
	    return function(date, format) {
	    	if( !format )
	    		format = 'DD MMM YYYY HH:mm:ss';

	    	if( !date || date == '0000-00-00 00:00:00' )
	    		return '--:--';
	    	
	      	return moment(date).format(format);
	    }
	 })
	 .filter('mydateminute', function() {
	    return function(date, format) {
	    	if( !format )
	    		format = 'DD MMM YYYY HH:mm';

	    	if( !date || date == '0000-00-00 00:00:00' )
	    		return '--:--';
	    	
	      	return moment(date).format(format);
	    }
 	})
 	.filter('mytime', function() {
	    return function(date, format) {
	    	if( !format )
	    		format = 'HH:mm:ss';

	    	if( !date || date == '0000-00-00 00:00:00' )
	    		return '--:--';
	    	
	      	return moment(date).format(format);
		}
	});
