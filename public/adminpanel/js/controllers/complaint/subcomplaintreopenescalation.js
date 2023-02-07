define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {

	app.controller('SubcomplaintReopenEscalationCtrl', function ($scope, $compile, $timeout, $http, $window,FileUploader /*$location, $http, initScript */) {
		console.log("SubcomplaintReopenEscalationCtrl reporting for duty.");
		
		//end///
		$scope.menus = [
					{link: '/complaint', name: 'Complaint'},
					{link: '/complaint/subcomplaintreopenescalation', name: 'Sub Complaint Reopen Escalation'},
				];
		
		var job_role_list = [];
		
		$http.get('/list/jobrole').success( function(response) {
			job_role_list = response;
		});

		$scope.notify_type_list = ['Email', 'SMS'];

		var g_severity_selected_id = 0;
		$scope.selected_level_list = [];
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		
		$scope.fields = ['ID', 'Severity', 'Levels', 'Job Roles', 'Max times', 'Notify Types'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/subcomplaintreopenescalation',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'sct.id' },					
					{ data: 'severity', name: 'sct.type' },
					{ data: 'levels', name: 'levels', orderable: false, searchable: false },					
					{ data: 'job_roles', name: 'job_roles', orderable: false, searchable: false },					
					{ data: 'maxtimes', name: 'maxtimes', orderable: false, searchable: false },					
					{ data: 'notify_types', name: 'notify_types', orderable: false, searchable: false },					
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					
					if( g_severity_selected_id > 0 )	// already selected
					{
						if(  data.severity_id == g_severity_selected_id )
						{
							$(row).attr('class', 'selected');	
							showLevelList();
							g_severity_selected_id = 0;			
						}
					}
					else 
					{
						if ( dataIndex == 0 )	// select first
						{					
							$(row).attr('class', 'selected');								
							$scope.severity_selected_id = data.severity_id;		
							showLevelList();	
						}
					}
				}
			});		
			
			$scope.grid = $grid;

			$grid.on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					// $(this).removeClass('selected');
				}
				else {
					$scope.grid.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
				}
				
				 /* Get the position of the current data from the node */
				var aPos = $scope.grid.fnGetPosition( this );

				/* Get the data array for this row */
				var aData = $scope.grid.fnGetData( aPos );
				
				$scope.severity_selected_id = aData.severity_id;
				showLevelList();
			} );	
			
			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '350px');
			
		}

		function showLevelList()
		{	
			var data = {severity_id: $scope.severity_selected_id};			
			
			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/subcomplaintreopenescalation/selectitem",
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
			row.severity_id = $scope.severity_selected_id;
			row.level = 0;
			row.notify_type_list = ['Email']
			if( $scope.selected_level_list.length > 0 )
				row.level = $scope.selected_level_list[$scope.selected_level_list.length - 1].level + 1;
			
			row.max_time = 600;

			$scope.selected_level_list.push(row);
		}

		$scope.onAddLevel = function(row) {
			if( row.job_role_list.length < 1 || row.notify_type_list.length < 1 || row.max_time < 1 )
				return;

			updateEscalationInfo(row);
		}

		$scope.onDeleteLevel = function(row) {
			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/subcomplaintreopenescalation/deleteinfo",
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
			g_severity_selected_id = $scope.severity_selected_id;
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
				url: "/backoffice/guestservice/wizard/subcomplaintreopenescalation/updateinfo",
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