define(['app', 'datatables.net',
		'datatables.net-bs', 'directives/directive'], function (app) {
	app.controller('AdminrateCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
		console.log("AdminrateCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/adminrate', name: 'Admin Rate Mapping'},
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
		$scope.fields = ['ID', 'Carrier Group', 'Rate Map Name', 'Carrier Charge', 'Allowance', 'Time Slab'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/adminratenggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ccm.id' },
					{ data: 'cgname', name: 'cg.name' },
					{ data: 'name', name: 'ccm.name' },
					{ data: 'ccname', name: 'cc.charge' },
					{ data: 'caname', name: 'ca.Name' },
					{ data: 'tsname', name: 'ts.name' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/carriergroup').success( function(response) {
				$scope.carriergroups = response;			
			});		
			$http.get('/list/carriercharge').success( function(response) {
				$scope.carriercharges = response;			
			});	
			$http.get('/list/allowance').success( function(response) {
				$scope.allowances = response;			
			});	
			$http.get('/list/timeslab').success( function(response) {
				$scope.timeslabs = response;			
			});	
		}
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			$scope.model_data.carrier_group_id = $scope.carriergroups[0].id;
			$scope.model_data.carrier_charges = $scope.carriercharges[0].id;
			$scope.model_data.call_allowance = $scope.allowances[0].id;
			$scope.model_data.time_slab_group = $scope.timeslabs[0].id;
			
			$scope.model_data.name = "";		
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/call/wizard/adminrate/' + id)
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
					url: '/backoffice/call/wizard/adminrate/' + id, 
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
					url: '/backoffice/call/wizard/adminrate', 
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
				$http.get('/backoffice/call/wizard/adminrate/' + id)
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
					url: '/backoffice/call/wizard/adminrate/' + id 								
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
	