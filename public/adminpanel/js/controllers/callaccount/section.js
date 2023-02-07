define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.factory(
		"initScript",
		function( $compile, $timeout ) {
			// Return the public API.
			return({
				load: load
			});
			// ---
			// PUBLIC METHODS.
			// ---
			// I load the 3rd-party script tag.
			function load() {
				// Apply the script inject in the next tick of the event loop. This
				// will give AngularJS time to safely finish its compile and linking.
				$timeout( loadSync, 0, false );
			}
			// ---
			// PRIVATE METHODS.
			// ---
			// I load the 3rd-party script tag.
			function loadSync() {
				
			}
		}
	);

	app.controller('SectionCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {
		console.log("Section Ctrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/section', name: 'Section'},
				];
				
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;		
				var alloption = {id: '0', name : '-- Select Property --'};
				$scope.properties.unshift(alloption);				
			});

		$scope.model_data.dept_id = "2";
		$scope.idkey = [];

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
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		
		$scope.fields = ['ID', 'Property', 'Section', 'Department', 'Manager', 'Description'];
		
		function initDomData() {		
			
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/sectionnggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cs.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'section', name: 'cs.section' },
					{ data: 'department', name: 'cd.department' },
					{ data: 'username', name: 'cu.username' },
					{ data: 'description', name: 'cs.description' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
				}
			});	
			
			$scope.grid = $grid;
			
			$http.get('/list/manager').success( function(response) {
				$scope.manager = response;			
			});
		}
		
		$scope.changeProperty = function()
		{	
			$http.get('/backoffice/admin/wizard/departmentlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.department = response;		
				var alloption = {id: '0', department : '-- Select Department --'};
				$scope.department.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.department.length > 0 )
						$scope.model_data.dept_id = $scope.department[0].id;				
					else
						$scope.model_data.dept_id = 0;					
				}				
			});	
		}

		$scope.getdepartment = function(val) {
			if( val == undefined )
				val = "";
			if( $scope.property_id == 0) {
				alert('Please select property');
				return;
			}
			return $http.get('/backoffice/admin/wizard/departmentlist?property_id='+ $scope.property_id+'&department='+val)
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
		
		$scope.changeProperty();
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
				for(var i = 0 ;i <  $scope.properties.length ; i++) {
					if($scope.cpname == $scope.properties[i].name) {
						$scope.property_id = $scope.properties[i].id;
						break;
					}
				}
			}			
			else
			{
				$scope.model_data.section = "";
				$scope.model_data.description = "";
				if( $scope.manager.length > 0 )
					$scope.model_data.manager_id = $scope.manager[0].id;
				else
					$scope.model_data.manager_id = 0;
				$scope.property_id = $scope.properties[0].id;
				$scope.changeProperty();
			}		

		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/call/wizard/section/' + id, 
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
					url: '/backoffice/call/wizard/section', 
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
				$http.get('/backoffice/call/wizard/section/' + id)
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
					url: '/backoffice/call/wizard/section/' + id 								
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
			
			$window.location.href = '/backoffice/property/wizard/auditsection_excelreport?';
			
			
		}
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				$scope.cpname = data.cpname;
				$scope.dept_name = data.department;
				delete data.checkbox;
				delete data.edit;
				delete data.delete;
				delete data.username;
				delete data.department;
				delete data.cpname;
				
				return data;
			}
			var data = {};
			return data;
		}
		
	});
});	