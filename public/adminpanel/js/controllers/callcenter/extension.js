define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('ExtensionCtrl', function ($scope, $compile, $timeout, $http, $window, $interval /*$location, $http, initScript */) {
		console.log("ExtensionCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/callcenter', name: 'Call Center'},
					{link: '/callcenter/extension', name: 'Extension'},
				];

		$http.get('/list/property').success( function(response) {
			$scope.properties = response;		
			var alloption = {id: '0', name : '-- Select Property --'};
			$scope.properties.unshift(alloption);		
		});
				
		$timeout( initDomData, 0, false );

		$scope.grid = {};
		$scope.idkey = [];


		//end///
		$scope.fields = ['ID', 'Property', 'Department', 'User', 'Extension', 'Password'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/callcenter/wizard/extension',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ce.id' },
					{ data: 'property_name', name: 'cp.name' },
					{ data: 'department', name: 'cd.department' },
					{ data: 'user_name', name: 'cu.user_name'},
					{ data: 'extension', name: 'ce.extension' },
					{ data: 'password', name: 'ce.password' },
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

		$scope.getuser = function(val) {
			if( val == undefined )
				val = "";

			if ($scope.model_data.dept_id == 0) {
				return;
			}
			return $http.get('/backoffice/admin/wizard/userlist?department_id='+$scope.model_data.dept_id + '&user_name'+val)
				.then(function(response){
					return response.data.map(function(item){
						return {
							id: item.id,
							user_name: item.first_name + " " + item.last_name
						};
					});
				});
		};

		$scope.getdepartment = function(val) {
			if( val == undefined )
				val = "";
			if( $scope.model_data.property_id == 0) {
				alert('Please select property');
				return;
			}
			return $http.get('/backoffice/admin/wizard/departmentlist?property_id='+ $scope.model_data.property_id+'&department='+val)
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

		$scope.onUser = function(user, $item, $model, $label) {
			$scope.model_data.user_id = $item.id;
			$scope.user_name = $item.user_name;
		}

		$scope.onShowEditRow = function(id)
		{	
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
			}
			else
			{
				$scope.model_data.id = -1;
				$scope.model_data.property_id = '0';
				$scope.model_data.extension = '';
				$scope.model_data.password = '';
				$scope.model_data.dept_id = '0';
				$scope.model_data.user_id = '0';
				$scope.dept_name = '';
				$scope.user_name = '';
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/callcenter/wizard/extension/' + id, 
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
					url: '/backoffice/callcenter/wizard/extension', 
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
					url: '/backoffice/callcenter/wizard/extension/' + id 								
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

		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				$scope.dept_name = data.department;
				$scope.user_name = data.user_name;
				delete data.property_name;
				delete data.department;
				delete data.user_name;
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