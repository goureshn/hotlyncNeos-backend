define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {

	app.controller('EscgroupCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		console.log("EscgroupCtrl reporting for duty.");
		
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
					{link: '/guest/escalationgroup', name: 'Escalation Groups'},
				];
				
		$scope.maxtime = [];
		$scope.default_time = 360;
		$scope.locationgroups = null;
		
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/escalation/upload',
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
		
		
		$timeout( initDomData, 0, false );
		$scope.grid = {};

		$scope.avaiable_jobrole_list = [];
		$scope.selected_level_list = [];

		$scope.gs_device_types = [
			{id: 0, name: "User"},
			{id: 1, name: "Device"},
			{id: 2, name: "Roster"},
		]
		
		$scope.fields = ['ID', 'Department function', 'Group Name'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/escalation',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'eg.id' },
					{ data: 'function', name: 'sdf.function' },
					{ data: 'name', name: 'eg.name' },				
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
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

			var detailRows = [];
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
		
		$scope.$on('$includeContentLoaded', function(event,url) {
			if( url.indexOf('multimove.html') > -1 )
			{
				$('#search').multiselect({
					search: {
						left: '<input type="text" name="q" class="form-control" placeholder="Add Job Role..." />',
						right: '<input type="text" name="q" class="form-control" placeholder="Selected Job Role..." />',
					},
					attatch : false, 
					afterMoveToRight: function($left, $right, $options) { 
						rearrangeSelectList();
					},
					afterMoveToLeft: function($left, $right, $options) { 
						rearrangeSelectList();
					},
					beforeMoveToRight : function($left, $right, $options) { 
						$scope.assignMaxTime();
						return true;
					}		
				});	
			}
		});
	
		$http.get('/list/deptfunc').success( function(response) {
				$scope.deptfuncs = response;			
			});

		$scope.getdepartmentfunc = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/guestservice/wizard/deptfunclist?deptfunc='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};
		$scope.ondepartmentfunc = function (dept_function, $item, $model, $label) {
			var dept_function = {};
			$scope.model_data.dept_func = $item.id;
			$scope.deptfunc = $item.function;
		};

		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			$scope.model_data.dept_func = $scope.deptfuncs[0].id;
			$scope.model_data.name = "";
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/escalation/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;
						for(var i =0 ; i < $scope.deptfuncs.length;i++) {
							if($scope.model_data.dept_func == $scope.deptfuncs[i].id )
								$scope.deptfunc = $scope.deptfuncs[i].function;
						}
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
					url: '/backoffice/guestservice/wizard/escalation/' + id, 
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
					url: '/backoffice/guestservice/wizard/escalation', 
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
				$http.get('/backoffice/guestservice/wizard/escalation/' + id)
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
					url: '/backoffice/guestservice/wizard/escalation/' + id 								
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
		

			
		function showLevelList()
		{	

			var data = {esgroup_id: $scope.selected_id};			
			
			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/escalation/selectesgroup",
				data: data, 
				headers: {'Content-Type': 'application/json; charset=utf-8'} 
			})
			.success(function(data, status, headers, config) {
				if( data ) {
					$scope.available_jobrole_list = data[0];
					$scope.selected_level_list = data[1];		
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
				addNewLevel();	
			})
			.error(function(data, status, headers, config) {				
				console.log(status);
			});  

		}

		function updateEscalationInfo(row) {
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
					addNewLevel();	
				}
			})
			.error(function(data, status, headers, config) {				
				console.log(status);
			});
		}

		$scope.onSelectJobrole = function(row, $item, $model, $label)
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
		
	});
	
});