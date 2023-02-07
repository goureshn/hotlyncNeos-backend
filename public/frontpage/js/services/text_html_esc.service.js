'use strict';
app.filter('htmlEscape', function() {
    return function(input) {
        if (!input) {
            return '';
        }
        return input.
        replace(/&/g, '&amp;').
        replace(/</g, '&lt;').
        replace(/>/g, '&gt;').
        replace(/'/g, '&#39;').
        replace(/"/g, '&quot;')
            ;
    };
});