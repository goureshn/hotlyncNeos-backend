'use strict';
app.filter('textToHtml',
    ['$sce', 'htmlEscapeFilter', function($sce, htmlEscapeFilter) {
        return function(input) {
            if (!input) {
                return '';
            }
            //input = htmlEscapeFilter(input);
            var output = '';
            $.each(input.split("\n\n"), function(key, paragraph) {
                output += '<p>' + paragraph + '</p>';
            });

            return $sce.trustAsHtml(output);
        };
    }])