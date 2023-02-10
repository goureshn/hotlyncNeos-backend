define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], function (app) {
	app.controller('AdminextCtrl', function ($scope, $compile, $timeout, $http,$window /*$location, $http, initScript */) {
		console.log("AdminextCtrl reporting for duty.");


		// $scope.viewclass = AuthService.isValidModule('bo.callaccount.admin_extension', AuthService, $rootScope, $localStorage);
		// if($rootScope.globals.currentUser.job_role == "SuperAdmin" ) $scope.viewclass = false;

		$scope.model_data = {};
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/adminext', name: 'Admin Extension'},
				];
				
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;		
				var alloption = {id: '0', name : '-- Select Property --'};
				$scope.properties.unshift(alloption);				
			});		
				
		$scope.model_data.section_id = "1";
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
		
		$scope.fields = ['ID', 'Property', 'Building', 'Section', 'Extension', 'User', 'User Group', 'Description', 'Enable'];


		$scope.grouplist_hint = {buttonDefaultText: 'Select Groups'};
		$scope.grouplist_hint_setting = {
			smartButtonMaxItems: 5,
			smartButtonTextConverter: function(itemText, originalItem) {
				return itemText;
			}
		};

		$scope.group_ids = [];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				enableFiltering: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/adminnggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cse.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'cbname', filter: 'agTextColumnFilter', name: 'cb.name',orderable: false},
					//{ data: 'department', name: 'cd.department' },
					{ data: 'section', name: 'cs.section' },
					{ data: 'extension', name: 'cse.extension' },
					{ data: 'username', name: 'cu.username' },
					{ data: 'user_group_name', name: 'cse.user_group_name' },
					{ data: 'description', name: 'cse.description' },
					{ data: 'adminenable', searchable: false, orderable: false },	
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					//{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
				}

			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/section').success( function(response) {
				$scope.sections = response;			
			});
			
			$http.get('/list/usergroup').success( function(response) {
				$scope.usergroups = response;
				for(var i = 0; i < $scope.usergroups.length; i++){
					$scope.usergroups[i].label = $scope.usergroups[i].name;
				}
			});
			
			$http.get('/list/user').success( function(response) {
				$scope.users = response;			
			});
		}
		
		$scope.changeProperty = function()
		{
			if($scope.property_id == 0)
				return;
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.buildings = response;		
				var alloption = {id: '0', name : '-- Select Building --'};
				$scope.buildings.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.buildings.length > 0 )
						$scope.model_data.building_id = $scope.buildings[0].id;				
					else
						$scope.model_data.building_id = 0;					
				}
				
				
				$scope.changeBuild();
			});
/*
			$http.get('/backoffice/admin/wizard/departmentlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.department = response;		
				var alloption = {id: '0', department : '-- Select Department --'};
				$scope.department.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.department.length > 0 )
						$scope.dept_id = $scope.department[0].id;				
					else
						$scope.dept_id = 0;					
				}			
				$scope.changeDepartment();	
			});	
*/
			//$scope.dept_id = 0;
		}
		$scope.changeBuild = function()
			{
				if($scope.property_id == 0)
					return;
				if($scope.model_data.building_id == 0)
					return;
			$http.get('/backoffice/call/wizard/sectionlist?building_id=' + $scope.model_data.building_id).success( function(response) {
				$scope.sections = response;	
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.sections.length > 0 )
						$scope.model_data.section_id = $scope.sections[0].id;				
					else
						$scope.model_data.section_id = 0;					
				}				
			});	
		}
/*		
		$scope.changeDepartment = function()
		{
			if($scope.property_id == 0)
				return;
			if($scope.dept_id == 0)
				return;
			$http.get('/backoffice/call/wizard/sectionlist/' + $scope.dept_id).success( function(response) {
				$scope.sections = response;		
				var alloption = {id: '0', section : '-- Select Extension --'};
				$scope.sections.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.sections.length > 0 )
						$scope.model_data.section_id = $scope.sections[0].id;				
					else
						$scope.model_data.section_id = 0;					
				}				
			});	
		}
*/
		$scope.getsection = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/call/wizard/sectionlistofkey?building_id='+ $scope.model_data.building_id+'&section='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};

		$scope.onsection = function (department, $item, $model, $label) {
			var departments = {};
			$scope.model_data.section_id = $item.id;
			$scope.section = $item.section;
		};

		$scope.getuser = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/call/wizard/adminnggrid/userlist?user_name='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};

		$scope.onuser = function (department, $item, $model, $label) {
			var departments = {};
			$scope.model_data.user_id = $item.id;
			$scope.username = $item.username;
		};

		$scope.changeProperty();
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
				$scope.group_ids = [];
				var ids = JSON.parse($scope.model_data.user_group_id);
				for(var i = 0; i < ids.length; i++){
					for(var j = 0; j < $scope.usergroups.length; j++)
						if($scope.usergroups[j].id == ids[i]){
							$scope.group_ids.push($scope.usergroups[i]);
						}
				}
				for(var i = 0 ;i <  $scope.properties.length ; i++) {
					if($scope.cpname == $scope.properties[i].name) {
						$scope.property_id = $scope.properties[i].id;
						
						break;
					}
				}
				
				for(var i = 0 ; i < $scope.buildings.length; i ++ ) {
					if($scope.cbname == $scope.buildings[i].name) {
						$scope.model_data.building_id = $scope.buildings[i].id;
						break;
					}
				}
				
				//window.alert($scope.model_data.building_id);
				for(var j = 0 ; j < $scope.users.length ; j++)
				{
					if($scope.username == $scope.users[j].username) {
						$scope.model_data.user_id = $scope.users[j].id;
						break;
					}
				}

			}
			else
			{
				$scope.group_ids = [];
				
				$scope.model_data.user_id = null;
				$scope.model_data.user_group_id = $scope.usergroups[0].id;
				$scope.model_data.extension = "";
				$scope.model_data.description = "";				
				$scope.model_data.bc_flag = 0;
				$scope.model_data.enable = 1;
				$scope.section = '';
				$scope.property_id = $scope.properties[0].id;
				$scope.changeProperty();
			}

		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			var glist = [];
			for(var i = 0; i < $scope.group_ids.length; i++)
				glist.push($scope.group_ids[i].id);
			$scope.model_data.user_group_id = JSON.stringify(glist);
			$scope.model_data.user_group_name = glist;
			if ($scope.username == '' || $scope.username ==null)
				$scope.model_data.user_id=null;
			if( id >= 0 )	// Update
			{ 
				$http({
					method: 'PUT', 
					url: '/backoffice/call/wizard/admin/' + id,
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data == 1 ) {
						
						alert("Error!! Extension already enabled for Guest");	
						refreshCurrentPage();	
					}
					else if (data == 3)
						{
						alert("Error!! Extension already enabled for Another Staff");
						refreshCurrentPage();
						}
					else if( data ) {
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
					url: '/backoffice/call/wizard/admin', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if (data == 1)
						{
						alert("Error!! Extension already enabled for Guest");
						}
					else if (data == 3)
						{
						alert("Error!! Extension already enabled for Another Staff");
						}
					else if( data ) {
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
				$http.get('/backoffice/call/wizard/admin/' + id)
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
					url: '/backoffice/call/wizard/admin/' + id 								
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
			
			$window.location.href = '/backoffice/property/wizard/auditadminext_excelreport?';
			
			
		}
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				$scope.section = data.section;
				$scope.cpname = data.cpname;
				$scope.cbname = data.cbname;
				//$scope.dept_name = data.department;
				$scope.username = data.username;
				//$scope.enable = data.enable;
				//$scope.group_ids = data.user_group_id;
				delete data.checkbox;
				delete data.edit;
				delete data.adminenable;
				delete data.delete;
				delete data.section;
				delete data.username;
				delete data.name;
				//delete data.department;
				delete data.cpname;
				delete data.cbname;
				
				return data;
			}
			var data = {};
			return data;
		}
		
	});
});	
