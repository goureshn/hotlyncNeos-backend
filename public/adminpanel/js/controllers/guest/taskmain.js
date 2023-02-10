define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {

	app.controller('TaskMainCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		console.log("TaskMainCtrl");
		
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
					{link: '/guest/taskmain', name: 'Main TAsk'},
				];
		
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		
		$timeout( initDomData, 0, false );
		$scope.grid = {};

		$scope.selected_task_list = [];

		
		$scope.fields = ['id','Task','Edit', 'delete'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/taskmain',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'tm.id' },
					{ data: 'task', name: 'tlm.task' },
					// { data: 'task_name', name: 'task_name' },				
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);					
				}
			});		
			
			$scope.grid = $grid;	
		}
		
		$scope.task_list = [];
		$http.get('/list/tasklist').success( function(response) {
			$scope.task_list = response;
		});

		function str2array(str) {
			var list = [];
			if( str )
			{
				val = str.split(',');
				val.forEach(element => {
					var row = $scope.task_list.find(ele => {
						return ele.id == parseInt(element);
					});
					list.push(row);
				});
			}

			return list;
		}

		function onlyUnique(value, index, self) { 
			return self.indexOf(value) === index;
		}

		function array2str(ids) 
		{
			temp = "";
			count = 0;

			ids = ids.map(ele => {
				return ele.id;
			});

			ids = ids.filter( onlyUnique );

			ids.forEach((element, index) => {
				if( element > 0 )	
				{
					if(count > 0)
						temp += ",";

					temp += element;	
					count++;	
				}					
			});

			return temp;
		}

		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			console.log(id);
			//$scope.model_data.alias = "";
			$scope.selected_task_list = [];
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/taskmain/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;
						$scope.selected_task_list = str2array($scope.model_data.task_ids);
						$scope.onAddTask();
					});		
			}
			else
			{
				$scope.model_data = {};
				$scope.model_data.id = '-1';
				$scope.onAddTask();

			}		
		}
		
		$scope.onUpdateRow = function()
		{
			//$scope.model_data.task_ids = array2str($scope.selected_task_list);
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/guestservice/wizard/taskmain/' + id, 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						console.log(data);
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
					url: '/backoffice/guestservice/wizard/taskmain', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						console.log(data);
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
				$http.get('/backoffice/guestservice/wizard/taskmain/' + id)
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
					url: '/backoffice/guestservice/wizard/taskmain/' + id 								
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

		$scope.onAddTask = function() 
		{
			var row = {};			
			row.id = 0;
			row.task = "";			
			if( $scope.selected_task_list.length > 0 && $scope.selected_task_list[$scope.selected_task_list.length - 1].id == 0 )
				return;

			$scope.selected_task_list.push(row);
		}

		$scope.onSelectTask = function(row, $item, $model, $label)
		{	
			row.id = $item.id;	
		}

		$scope.onDeleteTask = function(row) {
			$scope.selected_task_list = $scope.selected_task_list.filter(ele => {
				return ele.id != row.id;
			});
		}

		$scope.gettaskgroups = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/guestservice/wizard/gettaskgrouplist?taskgroup='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};
		$scope.ontaskgroups = function (department, $item, $model, $label) {
			var taskgroups = {};
			$scope.model_data.taskgroup_id = $item.id;
			$scope.taskgroup_name = $item.name;
		};
	});
	
});