define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect','ngTagsInput', 'directives/directive'], 
		function (app) {
			
	app.controller('TaskgroupCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		console.log("TaskgroupCtrl reporting for duty.");
		
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
					{link: '/guest/taskgroup', name: 'Task Group'},
				];
				
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/task/upload',
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
		
		$scope.fields = ['ID', 'Department Function', 'Group Name', 'User Group Name', 'Duration', 'Request Reminder', 'Escalate', 'By Guest'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/task',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'tg.id' },
					{ data: 'function', name: 'df.function' },
					{ data: 'name', name: 'tg.name' },
					{ data: 'ugname', name: 'tg.name' },
					{ data: 'max_time', name: 'tg.max_time' },
					{ data: 'request_reminder', name: 'tg.request_reminder' },
					{ data: 'escalation', name: 'tg.escalation' },
					{ data: 'by_guest_flag', name: 'tg.by_guest_flag' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/deptfunc').success( function(response) {
				$scope.deptfuncs = response;			
			});
		
			$http.get('/list/usergroup').success(function (response) {
				$scope.usergroups = response;
			});

			$http.get('/list/userlist').success(function (response) {
				$scope.userlist = response;
			});

			$http.get('/list/jobrole').success(function (response) {
				$scope.jobrole_list = response;
			});
			
			
		}

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
			$scope.model_data.dept_function = $item.id;
			$scope.deptfunc = $item.function;
		};

		$scope.getusergroup = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/guestservice/wizard/usergrouplist?usergroup='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};
		$scope.onusergroup = function (user_group, $item, $model, $label) {
			$scope.model_data.user_group = $item.id;
			$scope.usergroup = $item.name;
		};

		function ids2Names(ids, list, key)
		{
			if( ids == null || ids == '' )
				return '';
				
			ids = ids.split(',');

			return ids.map(id => {
				var row = list.find(item => {
					return parseInt(id) == item.id;
				})

				return row[key];
			});
		}

		function names2Ids(names, list, key)
		{
			if( names == null || names == '' )
				return '';

			return names.map(name => {
				var row = list.find(item => {
					return name.text == item.job_role;
				})

				return row[key];
			}).join();
		}

		$scope.onShowEditRow = function(id)
		{	
			// uploader.clearQueue();
			
			$scope.model_data.id = id;
			$scope.freq_job_role_name_list = [];
			$scope.hold_job_role_name_list = [];

			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/task/' + id)
					.success( function(response) {
						console.log(response);					
						if( response != "" )
							$scope.model_data = response;
						for(var i = 0 ; i < $scope.deptfuncs.length ; i ++ ) {
							if($scope.model_data.dept_function == $scope.deptfuncs[i].id)
								$scope.deptfunc = $scope.deptfuncs[i].function;
						}

						for(var i = 0 ;i < $scope.usergroups.length; i ++) {
							if($scope.model_data.user_group == $scope.usergroups[i].id)
								$scope.usergroup = $scope.usergroups[i].name;
						}

						$scope.freq_job_role_name_list = ids2Names($scope.model_data.frequency_job_role_ids, $scope.jobrole_list, 'job_role');						
						$scope.hold_job_role_name_list = ids2Names($scope.model_data.hold_job_role_ids, $scope.jobrole_list, 'job_role');						
						
						if($scope.model_data.escalation == 'Yes' ) $scope.escalation_flag = true;
						if($scope.model_data.escalation == 'No' ) $scope.escalation_flag = false;
						

					});		
			}
			else
			{
				$scope.deptfunc = '';
				$scope.model_data.dept_function = $scope.deptfuncs[0].id;
				$scope.model_data.user_group = 0;
				$scope.usergroup = '';
				$scope.model_data.name = "";
				$scope.model_data.max_time = 0;
				$scope.model_data.request_reminder = "50,80";
				$scope.model_data.escalation = "Yes";
				$scope.model_data.period = 0;

				$scope.model_data.by_guest_flag = 0;
				$scope.model_data.frequency_notification_flag = 0;
				$scope.model_data.hold_notification_flag = 0;
				$scope.model_data.unassigne_flag = 0;
				$scope.model_data.start_duration = 0;
				
				$scope.model_data.frequency = 0;
				$scope.model_data.cost_flag = 0;
				
			}		
		}
		
		$scope.onUpdateRow = function()
		{			
			$scope.model_data.frequency_job_role_ids = names2Ids($scope.freq_job_role_name_list, $scope.jobrole_list, 'id');
			$scope.model_data.hold_job_role_ids = names2Ids($scope.hold_job_role_name_list, $scope.jobrole_list, 'id');
			var id = $scope.model_data.id;

			if( $scope.model_data.unassigne_flag == 1 )
			{
				if( !($scope.model_data.user_group > 0) )
				{
					alert('Please select User Group for Unassigned Task Group');
					return;
				}

				if( !($scope.model_data.start_duration > 0) )
				{
					alert('Please set start duration for Unassigned Task Group');
					return;
				}
			}
			
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/guestservice/wizard/task/' + id, 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {					
					if( data) {
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
					url: '/backoffice/guestservice/wizard/task', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data) {
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
				$http.get('/backoffice/guestservice/wizard/task/' + id)
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
					url: '/backoffice/guestservice/wizard/task/' + id 								
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

		$scope.loadFiltersValue = function (value, query) {

			var job_role_list = $scope.jobrole_list.filter(function (item) {
				return item.job_role.toLowerCase().indexOf(query.toLowerCase()) != -1;					
			});
	
			return job_role_list.map(function (tag) { return tag.job_role; });	
		}
		
		
	});
});	