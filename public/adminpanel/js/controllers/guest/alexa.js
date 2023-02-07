define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {
	console.log("AlexaCtrl reporting for duty.");
	app.controller('AlexaCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		
		$scope.model_data = {};
		
		$scope.menus = [
					{link: '/guest', name: 'Guest Services'},
					{link: '/guest/alexa', name: 'Alexa'},
				];
		
		$timeout( initDomData, 0, false );
		$scope.grid = {};

		//edit permission check
		var permission = $scope.globals.currentUser.permission;
		$scope.edit_flag = 0;
		for(var i = 0; i < permission.length; i++)
		{
			if( permission[i].name == "access.superadmin" ) {
				$scope.edit_flag = 1;
				break;
			}
		}


		



		
	
	
		
	
		
	});
});	