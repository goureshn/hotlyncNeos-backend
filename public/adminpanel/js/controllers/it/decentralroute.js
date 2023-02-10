define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {

	app.controller('DecenteralRouteCtrl', function ($scope, $compile, $timeout, $http, $window,FileUploader /*$location, $http, initScript */) {
		console.log("DecenteralRouteCtrl reporting for duty.");
		
		$scope.model_data = {};

		//end///
		$scope.menus = [
					{link: '/it', name: 'IT'},
					{link: '/it/centralroute', name: 'Central Route'},
				];

		var job_role_list = [];
		
		$http.get('/list/jobrole').success( function(response) {
			job_role_list = response;
		});

		$scope.notify_type_list = ['Email', 'SMS', 'Mobile'];

		var g_subcategory_selected_id = 0;
		var g_dept_selected_id = 0;
		$scope.selected_level_list = [];
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		
		$scope.fields = ['ID', 'Category', 'Sub Category', 'Department', 'Levels', 'Job Roles', 'Notify Types'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/it/wizard/approvallist?central_mode=0',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'sub.id' },
					{ data: 'category', name: 'ca.category' },
					{ data: 'sub_cat', name: 'sub.sub_cat' },
					{ data: 'department', name: 'cd.department' },
					{ data: 'levels', name: 'levels', orderable: false, searchable: false },					
					{ data: 'job_roles', name: 'job_roles', orderable: false, searchable: false },										
					{ data: 'notify_types', name: 'notify_types', orderable: false, searchable: false },			
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					
					if( g_subcategory_selected_id > 0 )	// already selected
					{
						if(  data.id == g_subcategory_selected_id && data.dept_id == g_dept_selected_id )
						{
							$(row).attr('class', 'selected');	
							showLevelList();
							g_subcategory_selected_id = 0;				
							g_dept_selected_id = 0;			
						}
					}
					else 
					{
						if ( dataIndex == 0 )	// select first
						{					
							$(row).attr('class', 'selected');	
							$scope.subcategory_selected_id = data.id;		
							$scope.dept_selected_id = data.dept_id;		
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

				$scope.subcategory_selected_id = aData.id;
				$scope.dept_selected_id = aData.dept_id;
				
				$scope.selected_id = aData.id;
				showLevelList();
			} );	

			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '350px');

		}

		function showLevelList()
		{	
			var data = {subcategory_id: $scope.subcategory_selected_id, dept_id: $scope.dept_selected_id};			
			
			$http({
				method: 'POST', 
				url: "/backoffice/it/wizard/selectitem",
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
			row.job_role_list = [];
			row.subcategory_id = $scope.subcategory_selected_id;
			row.dept_id = $scope.dept_selected_id;
			row.level = 0;
			row.notify_type_list = ['Email']
			if( $scope.selected_level_list.length > 0 )
				row.level = $scope.selected_level_list[$scope.selected_level_list.length - 1].level + 1;
			
			$scope.selected_level_list.push(row);
		}

		$scope.onAddLevel = function(row) {
			if( row.job_role_list.length < 1 || row.notify_type_list.length < 1 )
				return;

			updateEscalationInfo(row);
		}

		$scope.onDeleteLevel = function(row) {
			$http({
				method: 'POST', 
				url: "/backoffice/it/wizard/deleteinfo",
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

		function refreshCurrentRow() {
			g_subcategory_selected_id = $scope.subcategory_selected_id;
			g_dept_selected_id = $scope.dept_selected_id;
			refreshCurrentPage();		
		}

		function updateEscalationInfo(row) {
			if( row.job_role_list.length < 1 || row.notify_type_list.length < 1 || row.max_time < 1 )
				return;

			row.job_role_ids = row.job_role_list.map(item => item.id).join(',');

			row.notify_type = row.notify_type_list.map(name => {				
				return name['text'];
			}).join();


			$http({
				method: 'POST', 
				url: "/backoffice/it/wizard/updateinfo",
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

		$scope.onJobRoleChanged = function(row)
		{		
			if( row.id < 1 )
				return;

			updateEscalationInfo(row);			
		}

		$scope.onChangeMaxtime = function(row)
		{		
			if( row.id < 1 )
				return;

			updateEscalationInfo(row);			
		}

		$scope.onNotifyChanged = function(row)
		{		
			if( row.id < 1 )
				return;

			updateEscalationInfo(row);			
		}

		function refreshCurrentPage()
		{
			var oSettings = $scope.grid.fnSettings();
			var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
			$scope.grid.fnPageChange(page);
		}

		$scope.loadJobroleFilter = function(query) {
			return job_role_list.filter(item => item.job_role.toLowerCase().includes(query.toLowerCase()));
		}

	});
});