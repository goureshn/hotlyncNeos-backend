define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {

	app.controller('HousekeepingCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		console.log("HousekeepingCtrl reporting for duty.");
		
		$scope.$emit('updateCSS', ['/lib/angular/angular-toggle-switch-bootstrap-3.css']);
		
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
					{link: '/guest', name: 'Guest Services'},
					{link: '/guest/hskp', name: 'Housekeeping'},
				];
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		
		$scope.fields = ['ID', 'Building', 'Status', 'IVR Code', 'PMS Code', 'Type', 'Description'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/hskp',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'hskp.id' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'status', name: 'hskp.status' },
					{ data: 'ivr_code', name: 'hskp.ivr_code' },
					{ data: 'pms_code', name: 'hskp.pms_code' },
					{ data: 'type', name: 'hskp.type' },
					{ data: 'description', name: 'df.description' },				
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/building').success( function(response) {
				$scope.buildings = response;			
			});
			$http.get('/list/hskptype').success( function(response) {
				$scope.hskptypes = response;			
			});
			
			
		}
		
		$scope.onShowEditRow = function(id)
		{	
			// uploader.clearQueue();
			
			$scope.model_data.id = id;
			
			$scope.model_data.bldg_id = $scope.buildings[0].id;
			$scope.model_data.type = $scope.hskptypes['1'];
			$scope.model_data.status = "";
			$scope.model_data.pms_code = "";
			$scope.model_data.ivr_code = "";
			$scope.model_data.description = "";
			$scope.chk_ivr_flag = false;
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/hskp/' + id)
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
			if( $scope.chk_ivr_flag == false )
			{
				$scope.model_data.ivr_code = $scope.model_data.pms_code;
			}
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/guestservice/wizard/hskp/' + id, 
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
					url: '/backoffice/guestservice/wizard/hskp/storeng', 
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
				$http.get('/backoffice/guestservice/wizard/hskp/' + id)
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
					url: '/backoffice/guestservice/wizard/hskp/' + id 								
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