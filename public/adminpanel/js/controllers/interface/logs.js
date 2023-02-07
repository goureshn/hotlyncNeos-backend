define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('LogCtrl', function ($scope, $compile, $timeout, $http, interface /*$location, $http, initScript */) {
		console.log("LogCtrl reporting for duty.");

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
		//end///
		$scope.menus = [
					{link: '/property', name: 'Interface'},
					{link: '/property/building', name: 'Logs'},
				];
		
		$timeout( initDomData, 0, false );

		
		$scope.fields = ['Time', 'Level', 'Message'];
		
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "desc" ]], //column indexes is zero based
				//ajax: '/interface/log?start_date=2016-10-27',
				ajax: '/interface/log',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},					
					{ data: 'timestamp', width: '120px', name: 'timestamp' },
					{ data: 'level', width: '40px', name: 'level' },					
					{ data: 'message', name: 'message' },										
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});

		}

	});
});