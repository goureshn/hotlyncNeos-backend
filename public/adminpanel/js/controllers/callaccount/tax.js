define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('TaxCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {
		console.log("TaxCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/tax', name: 'Tax'},
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
		//end///
		$scope.fields = ['ID', 'Name', 'Carrier', 'Value'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/taxnggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'tax.id' },
					{ data: 'name', name: 'tax.name' },
					{ data: 'carrier', name: 'cr.carrier' },
					{ data: 'value', name: 'tax.value' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/carrier').success( function(response) {
				$scope.carriers = response;			
			});		
			
		}
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			$scope.model_data.carrier_id = $scope.carriers[0].id;
			$scope.model_data.name = "";		
			$scope.model_data.value = "";
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/call/wizard/tax/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;										
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
					url: '/backoffice/call/wizard/tax/' + id, 
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
					url: '/backoffice/call/wizard/tax', 
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
				$http.get('/backoffice/call/wizard/tax/' + id)
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
					url: '/backoffice/call/wizard/tax/' + id 								
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