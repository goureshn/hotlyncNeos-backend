define(['app', 'datatables.net',
		'datatables.net-bs', 'directives/directive'], function (app) {
	app.controller('CarrierCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
		console.log("CarrierCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/carrier', name: 'Carrier'},
				];
				
		$scope.model_data.prpty_id = "1";
		
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
		//end///
		$scope.fields = ['ID', 'Carrier', 'Property', 'Description'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/carriernggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cr.id' },
					{ data: 'carrier', name: 'cr.carrier' },
					{ data: 'name', name: 'cp.name' },
					{ data: 'description', name: 'cr.description' },			
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/property').success( function(response) {
				$scope.properties = response;			
			});
		}
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			$scope.model_data.prpty_id = $scope.properties[0].id;
			$scope.model_data.carrier = "";
			$scope.model_data.description = "";
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/call/wizard/carrier/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;										
						//$scope.model_data.dept_id = response.dept_id;
					});		
			}
			else
			{
				
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/call/wizard/carrier/' + id, 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						refreshCurrentPage();		
					}
					else {
						
					}
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
				});
			}
			else			
			{
				$http({
					method: 'POST', 
					url: '/backoffice/call/wizard/carrier', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						$scope.grid.fnPageChange( 'last' );
					}
					else {
						
					}
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
				});
			}
		}	
		
		$scope.onDeleteRow = function(id)
		{
			if( id >= 0 )
			{
				$http.get('/backoffice/call/wizard/carrier/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;															
					});			
			}
			
		}
		
		$scope.deleteRow = function()
		{
			var id = $scope.model_data.id;
			
			if( id >= 0 )
			{
				$http({
					method: 'DELETE', 
					url: '/backoffice/call/wizard/carrier/' + id 								
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						refreshCurrentPage();	
					}
					else {
						
					}
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
				});
			}
		}
		
		function refreshCurrentPage()
		{
			var oSettings = $scope.grid.fnSettings();
			var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
			$scope.grid.fnPageChange(page);
		}
		
		
	});
});	