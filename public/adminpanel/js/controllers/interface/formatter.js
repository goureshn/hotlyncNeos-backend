define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('FormatterCtrl', function ($scope, $compile, $timeout, $http, interface /*$location, $http, initScript */) {
		console.log("FormatterCtrl reporting for duty.");
		
		$scope.model_data = {};

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
			{link: '/property/building', name: 'Formatter'},
		];

		$http.get('/list/protocol').success( function(response) {
			$scope.protocols = response;
		});

		$scope.modes = {1 : 'GET', 2 : 'POST', 3 : 'PUT', 4: 'DELETE'};

		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Protocol', 'Name', 'Formatter', 'URL', 'Mode', 'Verify', 'Keys'];
		
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/interface/formatter',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},					
					{ data: 'id', width: '20px', name: 'fm.id' },
					{ data: 'prname', name: 'pr.name' },
					{ data: 'name', name: 'fm.name' },
					{ data: 'formatter', name: 'fm.formatter' },
					{ data: 'url', name: 'fm.url' },
					{ data: 'mode', name: 'fm.mode' },
					{ data: 'verify', name: 'fm.verify' },
					{ data: 'keys', name: 'fm.keys' },
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
				$scope.model_data.protocol_id = $scope.protocols[0].id;
				$scope.model_data.name = "";
				$scope.model_data.formatter = "";
				$scope.model_data.url = "";
				$scope.model_data.mode = $scope.modes[1];
				$scope.model_data.verify = "";
				$scope.model_data.keys = "";
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/interface/formatter/' + id, 
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
					url: '/interface/formatter', 
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
					url: '/interface/formatter/' + id 								
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
				delete data.prname;

				return data;
			}
			var data = {};
			return data;
		}

	});
});