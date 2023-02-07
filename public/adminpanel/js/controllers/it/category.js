define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {

	app.controller('CategoryCtrl', function ($scope, $compile, $timeout, $http, $window,FileUploader /*$location, $http, initScript */) {
		console.log("CategoryCtrl reporting for duty.");
		
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
					{link: '/it/category', name: 'Category'},
				];
		
		
		$http.get('/list/jobrole').success( function(response) {
			$scope.jobroles = response;
		});

		var g_selected_id = 0;
		$scope.selected_level_list = [];
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		$scope.idkey = [];

		$scope.approval_mode_list = [
			'None','Centralized','Decentralized'
		];
		
		$scope.fields = ['ID', 'Category', 'Approval Mode'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/it/wizard/category',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'it.id' },
					{ data: 'category', name: 'it.category' },
					{ data: 'approval_mode', name: 'it.approval_mode' },
					{ data: 'edit', orderable: false, searchable: false},
					{ data: 'delete', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;

					if( g_selected_id > 0 )	// already selected
					{
						if(  data.id == g_selected_id )
						{
							$(row).attr('class', 'selected');	
							showLevelList();
							g_selected_id = 0;							
						}
					}
					else
					{
						if ( dataIndex == 0 )
						{					
							$(row).attr('class', 'selected');	
							$scope.selected_id = data.id;		
							showLevelList();	
						}
					}
				}
			});		
			
			$scope.grid = $grid;

			$grid.on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
				}
				else {
					$scope.grid.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
				}
				
				 /* Get the position of the current data from the node */
				var aPos = $scope.grid.fnGetPosition( this );

				/* Get the data array for this row */
				var aData = $scope.grid.fnGetData( aPos );
				
				$scope.selected_id = aData.id;
				showLevelList();
			} );	
			
			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '350px');
			
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
				$scope.model_data.category = "";
				$scope.model_data.approval_mode = "None";				
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/it/wizard/category/' + id, 
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
					url: '/backoffice/it/wizard/category', 
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
					url: '/backoffice/it/wizard/category/' + id 								
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

		function showLevelList()
		{	
			$scope.selected_row_data = loadData($scope.selected_id);
			var data = {id: $scope.selected_id};			
			
			$http({
				method: 'POST', 
				url: "/backoffice/it/wizard/category/selectitem",
				data: data, 
				headers: {'Content-Type': 'application/json; charset=utf-8'} 
			})
			.success(function(data, status, headers, config) {
				if( data ) {
					$scope.selected_level_list = data;		
					
					addNewLevel();			
				}
				else {
					
				}
			})
			.error(function(data, status, headers, config) {				
				console.log(status);
			});
		}

		function addNewLevel() 
		{
			var row = {};

			row.id = 0;
			row.job_role_id = 0;
			row.job_role = "";
			row.level = 1;
			if( $scope.selected_level_list.length > 0 )
				row.level = $scope.selected_level_list[$scope.selected_level_list.length - 1].level + 1;

			row.category_id = $scope.selected_id;

			$scope.selected_level_list.push(row);
		}

		$scope.onAddLevel = function(row) {
			if( row.job_role_id < 1 )
				return;

			updateEscalationInfo(row);
		}

		$scope.onDeleteLevel = function(row) {
			$http({
				method: 'POST', 
				url: "/backoffice/it/wizard/category/deleteinfo",
				data: row, 
				headers: {'Content-Type': 'application/json; charset=utf-8',
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} 
			})
			.success(function(data, status, headers, config) {				
				refreshCurrentRow();
			})
			.error(function(data, status, headers, config) {				
				console.log(status);
			});  

		}

		function updateEscalationInfo(row) {	
			$http({
				method: 'POST', 
				url: "/backoffice/it/wizard/category/updateinfo",
				data: row, 
				headers: {'Content-Type': 'application/json; charset=utf-8',
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} 
			})
			.success(function(data, status, headers, config) {
				refreshCurrentRow();

			})
			.error(function(data, status, headers, config) {				
				console.log(status);
			});
		}

		$scope.onSelectJobroleForLevel = function(row, $item, $model, $label)
		{
			row.job_role_id = $item.id;

			if( row.id < 1 )
				return;

			updateEscalationInfo(row);			
		}

		function loadData(id)
		{
			if( id >= 0 )
			{
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				delete data.category;
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