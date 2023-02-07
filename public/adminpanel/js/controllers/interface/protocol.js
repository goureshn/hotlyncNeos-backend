define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('ProtocolCtrl', function ($scope, $compile, $timeout, $http, interface /*$location, $http, initScript */) {
		console.log("ProtocolCtrl reporting for duty.");

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
		$scope.model_data = {};
		$scope.protocol_data = {};
		
		$http.get('/list/externaltype').success( function(response) {
				$scope.types = response;
			});
		$http.get('/list/checksumtype').success( function(response) {
			$scope.checksum_types = response;
		});
		
		$scope.menus = [
			{link: '/property', name: 'Interface'},
			{link: '/property/building', name: 'Protocol'},
		];
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Name', 'Type', 'CheckSum', 'Checksum Type', 'Checksum Position'];
		
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/interface/protocol',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},					
					{ data: 'id', width: '20px', name: 'id' },
					{ data: 'name', name: 'name' },
					{ data: 'type', name: 'type' },
					{ data: 'checksum_flag', name: 'checksum_flag' },
					{ data: 'checksum_type', name: 'checksum_type' },
					{ data: 'checksum_pos', name: 'checksum_pos' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
				}
			});	
			
			$scope.grid = $grid;			
		}

		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);				
			}
			else
			{
				$scope.model_data.name = "";
				$scope.model_data.type = $scope.types[1];
				$scope.model_data.checksum_type = $scope.checksum_types[1];
				$scope.chk_active = false;
				$scope.checksum_pos = 0;
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			$scope.model_data.checksum_flag = $scope.chk_active ? 'Yes' : 'No';
			
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/interface/protocol/' + id, 
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
					url: '/interface/protocol', 
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
				$scope.model_data = loadData(id);
			}
			
		}
		
		$scope.deleteRow = function()
		{
			var id = $scope.model_data.id;
			
			if( id >= 0 )
			{
				$http({
					method: 'DELETE', 
					url: '/interface/protocol/' + id 								
				})
				.success(function(data, status, headers, config) {
					refreshCurrentPage();						
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
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				delete data.checkbox;
				delete data.edit;
				delete data.delete;

				if( data.checksum_flag == 'Yes' )
					$scope.chk_active = true;
				else
					$scope.chk_active = false;

				return data;
			}
			var data = {};
			return data;
		}

	});
});