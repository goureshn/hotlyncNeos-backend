define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {

	app.controller('TypeCtrl', function ($scope, $compile, $timeout, $http, $window,FileUploader /*$location, $http, initScript */) {
		console.log("TypeCtrl reporting for duty.");
		
		$scope.model_data = {};

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
					{link: '/it', name: 'IT'},
					{link: '/it/type', name: 'Type'},
				];
		
		
		$http.get('/list/jobrole').success( function(response) {
			$scope.jobroles = response;
		});

		var g_selected_id = 0;
		$scope.selected_level_list = [];
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		$scope.idkey = [];

		
		
		$scope.fields = ['ID', 'Type'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/it/wizard/type',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'it.id' },
					{ data: 'type', name: 'it.type' },
				
					{ data: 'edit', width: '40px',orderable: false, searchable: false},
					{ data: 'delete', width: '40px',orderable: false, searchable: false}
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
				$scope.model_data = loadData(id);
			}
			else
			{
				$scope.model_data.type = "";
								
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/it/wizard/type/' + id, 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						refreshCurrentRow();                                						
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
					url: '/backoffice/it/wizard/type', 
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
			if( id > 0 )
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
					url: '/backoffice/it/wizard/type/' + id 								
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
		
		function refreshCurrentRow() {
			g_selected_id = $scope.selected_id;
			refreshCurrentPage();		
		}

		function refreshCurrentPage()
		{
			var oSettings = $scope.grid.fnSettings();
			var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
			$scope.grid.fnPageChange(page);
		}
		
		$scope.selected_row_data = {};

		

		function loadData(id)
		{
			if( id >= 0 )
			{
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				delete data.checkbox;
				delete data.edit;
				delete data.delete;

				return data;
			}
			var data = {};
			return data;
		}

	});
});