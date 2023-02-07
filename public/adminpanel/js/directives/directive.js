define(['app'], function (app) {
    app.directive('textItem', function() {
	   return {
			restrict: 'E',
			scope: {
				modelname: '=',
				viewclass: '='
			},
			link: function(scope, element, attrs) {
				scope.formlabel = attrs.formlabel;
				scope.texthint = attrs.texthint;
			},
			templateUrl: 'adminpanel/js/directives/template/text-item.html'
		}
	});


	app.directive('dateItem', function() {
	   return {
			restrict: 'E',
			scope: {
				modelname: '=',				
				viewclass: '='
			},
			link: function(scope, element, attrs) {
				scope.formlabel = attrs.formlabel;				
				scope.dateoptions = JSON.parse(attrs.dateoptions);
			},
			templateUrl: 'adminpanel/js/directives/template/date-item.html'
		}
	});

	app.directive('textareaItem', function() {
	   return {
			restrict: 'E',
			scope: {
				modelname: '='
			},
			link: function(scope, element, attrs) {
				scope.formlabel = attrs.formlabel;
				scope.texthint = attrs.texthint;			
			},
			templateUrl: 'adminpanel/js/directives/template/textarea-item.html'
		}
	});

	app.directive('submitForm', function($compile) {
	   return {
			restrict: 'E',
			terminal: true,
			scope: {           
				id: '=',
				updatedata: '&'
			},
			link: function(scope, element, attrs) {
				
			},
			templateUrl: 'adminpanel/js/directives/template/submit-form.html'
		}
	});

	app.directive('submitFormNoDismiss', function($compile) {
	   return {
			restrict: 'E',
			terminal: true,
			scope: {           
				id: '=',
				updatedata: '&'
			},
			link: function(scope, element, attrs) {
				
			},
			templateUrl: 'adminpanel/js/directives/template/submit-form-no-dismiss.html'
		}
	});

	app.directive('tableHeader', function($compile) {
	   return {
			restrict: 'E',
			scope: {
				fields: "="
			},
			link: function(scope, element, attrs) {
				
			},
			templateUrl: 'adminpanel/js/directives/template/table-header.html'
		}
	});

	app.directive('dialogDelete', function($compile) {
	   return {
			restrict: 'E',
			terminal: true,
			scope: {
				modelname: '=',
				deletedata: '&'
			},
			link: function(scope, element, attrs) {
				scope.title = attrs.title;	
			},
			templateUrl: 'adminpanel/js/directives/template/delete-dialog.html'
		}
	});


	app.directive('selectItem', function() {
	   return {
			restrict: 'E',
			scope: {
				modelname: '='
			},
			link: function(scope, element, attrs) {
				scope.formlabel = attrs.formlabel;
				scope.rows = attrs.rows;			
				// scope.carriers = scope.parent.carriers;
			},
			templateUrl: 'adminpanel/js/directives/template/select-item.html'
		}
	});

}); 