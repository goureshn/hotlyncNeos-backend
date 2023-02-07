define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {

	app.controller('DeptfuncCtrl', function ($scope, $compile, $timeout, $http, $window,FileUploader /*$location, $http, initScript */) {
		console.log("DeptfuncCtrl reporting for duty.");
		
		$scope.model_data = {};

		//edit permission check
		$scope.gs_device_types = [
			{id: '0', name: 'User'},
			{id: '1', name: 'Device'},
			{id: '2', name: 'Roster'},
		];

		$scope.device_types = [
			{id: 0, name: 'User'},
			{id: 1, name: 'Device'},
			{id: 2, name: 'Roster'},
		];

		$scope.hskp_role_list = [
			'None',
			'Attendant',
			'Supervisor',
		];

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
					{link: '/guest/deptfunc', name: 'Department Function'},
				];
		
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/departfunc/upload',
				alias : 'myfile',
				headers: headers
			});
		uploader.filters.push({
				name: 'excelFilter',
				fn: function(item /*{File|FileLikeObject}*/, options) {
					var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
					return '|csv|xls|xlsx|'.indexOf(type) !== -1;
				}
			});
		uploader.onSuccessItem = function(fileItem, response, status, headers) {
				$('#closeButton').trigger('click');
				$scope.grid.fnPageChange( 'last' );
			};	
		uploader.onErrorItem = function(fileItem, response, status, headers) {
			console.info('onErrorItem', fileItem, response, status, headers);
		};

		$http.get('/list/jobrole').success( function(response) {
			$scope.jobroles = response;
		});

		$http.get('/list/department').success( function(response) {
			$scope.departments = response;			
		});

		$scope.avaiable_jobrole_list = [];
		$scope.selected_level_list = [];

		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		
		$scope.fields = ['ID', 'Department', 'Function', 'Short Code', 'Description', 'Device Setting', 'All Departments Setting', 'Escalation Setting', 'Housekeeping Role'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/departfunc',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'df.id' },
					{ data: 'department', name: 'cd.department' },
					{ data: 'function', name: 'df.function' },
					{ data: 'short_code', name: 'df.short_code' },
					{ data: 'description', name: 'df.description' },
					{ data: 'device', name: 'df.gs_device' },	
					{ data: 'all_dept_setting', name: 'df.all_dept_setting' },	
					{ data: 'escalation_setting', name: 'df.escalation_setting' },			
					{ data: 'hskp_role', name: 'df.hskp_role' },			
					{ data: 'edit', orderable: false, searchable: false},
					{ data: 'delete', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					if ( dataIndex == 0 )
					{					
						$(row).attr('class', 'selected');	
						$scope.selected_id = data.id;		
						showLevelList();	
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

		$scope.getdepartment = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/admin/wizard/departmentlist?department='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};
		$scope.ondepartment = function (department, $item, $model, $label) {
			var departments = {};
			$scope.model_data.dept_id = $item.id;
			$scope.dept_name = $item.department;
		};


		$scope.onShowEditRow = function(id)
		{	
			// uploader.clearQueue();
			
			$scope.model_data.id = id;
			
			$scope.model_data.dept_id = $scope.departments[0].id;
			$scope.model_data.function = "";
			$scope.model_data.short_code = "";
			$scope.model_data.description = "";
			$scope.model_data.gs_device = '0';
			$scope.all_dept_setting = false;
			$scope.escalation_setting = false;
			$scope.model_data.all_dept_setting = '0';
			$scope.model_data.escalation_setting= '0';
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/departfunc/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;	
						$scope.all_dept_setting =($scope.model_data.all_dept_setting=='1')  ? true : false;
						$scope.escalation_setting = ($scope.model_data.escalation_setting=='1')  ? true : false;													
					});		
			}
			else
			{
				
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			$scope.model_data.all_dept_setting =$scope.all_dept_setting  ? '1' : '0';
			$scope.model_data.escalation_setting = $scope.escalation_setting  ? '1' : '0';
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/guestservice/wizard/departfunc/' + id, 
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
					url: '/backoffice/guestservice/wizard/departfunc', 
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
				$http.get('/backoffice/guestservice/wizard/departfunc/' + id)
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
					url: '/backoffice/guestservice/wizard/departfunc/' + id 								
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

		$scope.onDownloadExcel = function() {

			//$window.alert($scope.filter.property_id);
			
			$window.location.href = '/backoffice/property/wizard/auditdeptfunc_excelreport?';
			
			
		}

		$scope.onSelectJobrole = function(row, $item, $model, $label)
		{
			if( !($item.id > 0) )
				return;

			$scope.model_data.job_role_id = $item.id;
		}

		$scope.onSelectSecJobrole = function(row, $item, $model, $label)
		{
			if( !($item.id > 0) )
				return;

			$scope.model_data.sec_job_role_id = $item.id;
		}

		function setNotifyList() 
		{
			$scope.selected_level_list.forEach(row => {
				if( row.notify_type.length > 0  )
					row.notify_list = row.notify_type.split(',');
				else	
					row.notify_list = [];
			});
		}

		function showLevelList()
		{	
			var data = {esgroup_id: $scope.selected_id};			
			
			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/escalation/selectitem",
				data: data, 
				headers: {'Content-Type': 'application/json; charset=utf-8'} 
			})
			.success(function(data, status, headers, config) {
				if( data ) {
					$scope.available_jobrole_list = data[0];
					$scope.selected_level_list = data[1];		
					
					setNotifyList();
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
			row.escalation_group = $scope.selected_id;
			row.level = 1;
			row.notify_list = ['Email']
			row.device_type = -1;
			if( $scope.selected_level_list.length > 0 )
				row.level = $scope.selected_level_list[$scope.selected_level_list.length - 1].level + 1;
			
			row.max_time = 600;

			$scope.selected_level_list.push(row);
		}

		$scope.onAddLevel = function(row) {
			if( row.job_role_id < 1 || row.device_type < 0 )
				return;

			updateEscalationInfo(row);
		}

		$scope.onDeleteLevel = function(row) {
			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/escalation/deleteinfo",
				data: row, 
				headers: {'Content-Type': 'application/json; charset=utf-8',
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} 
			})
			.success(function(data, status, headers, config) {
				$scope.selected_level_list = data;		
				setNotifyList();
				addNewLevel();	
			})
			.error(function(data, status, headers, config) {				
				console.log(status);
			});  

		}

		function updateEscalationInfo(row) {
			row.notify_type = row.notify_list.map(name => {				
				return name['text'];
			}).join();

			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/escalation/updateinfo",
				data: row, 
				headers: {'Content-Type': 'application/json; charset=utf-8',
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} 
			})
			.success(function(data, status, headers, config) {
				if( row.id < 1 )
				{
					$scope.selected_level_list = data;		
					setNotifyList();
					addNewLevel();	
				}
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

		$scope.onChangeDeviceType = function(row)
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

		$scope.loadFiltersNotify = function (value, query) {
			return ['Email', 'SMS', 'Mobile'];			
		}
	});
});