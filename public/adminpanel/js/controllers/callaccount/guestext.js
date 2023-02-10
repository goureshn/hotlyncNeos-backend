define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('GuestextCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {
		console.log("Guest Ctrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/guestext', name: 'Guest Extension'},
				];
		
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;		
				var alloption = {id: '0', name : '-- Select Property --'};
				$scope.properties.unshift(alloption);				
			});

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
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Property', 'Building', 'Room', 'Primary Extension', 'Extension', 'Enable'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/guestnggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ge.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'name', name: 'b.name' },
					{ data: 'room', name: 'r.room' },
					{ data: 'primary_extn', name: 'ge.primary_extn' },
					{ data: 'extension', name: 'ge.extension' },
					{ data: 'genable', searchable: false, orderable: false, },				
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					//{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					
					$compile(row)($scope);
					
					if( data.id >= 0 )
						
						$scope.idkey[data.id] = dataIndex;
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/building').success( function(response) {
				$scope.buildings = response;	
				$http.get('/room/list?build_id=' + $scope.buildings[0].id).success( function(response) {
					$scope.rooms = response;			
				});			
			});
							
		}
		
		$scope.changeProperty = function()
		{	
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.buildings = response;		
				var alloption = {id: '0', name : '-- Select Building --'};
				$scope.buildings.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.buildings.length > 0 )
						$scope.model_data.bldg_id = $scope.buildings[0].id;				
					else
						$scope.model_data.bldg_id = '0';					
				}
				$scope.changeBuild();
			});				
		}
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.sub_extesnions = [];
				$scope.values = [];
				$scope.model_data = loadData(id);
				for(var i = 0 ;i <  $scope.properties.length ; i++) {
					if($scope.cpname == $scope.properties[i].name) {
						$scope.property_id = $scope.properties[i].id;
						break;
					}
				}
				$http.get('/room/list?build_id=' + $scope.model_data.bldg_id).success( function(response) {
					$scope.rooms = response;
				});
			}
			else
			{
				$scope.model_data.primary_extn = 'Y';
				$scope.model_data.extension = "";
				$scope.model_data.description = "";
				$scope.model_data.enable = 1;
				$scope.sub_extesnions = [];
				$scope.values = [];
				$scope.property_id = $scope.properties[0].id;
				$scope.changeProperty();
			}	

		}
		
		$scope.changeBuild = function()
		{	
			$http.get('/room/list?build_id=' + $scope.model_data.bldg_id).success( function(response) {
				$scope.rooms = response;	
				if( $scope.rooms.length > 0 )
					$scope.model_data.room_id = $scope.rooms[0].id;			
				else
					$scope.model_data.room_id = 0;				
				
			});
		}
		
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;

			if($scope.values.length !=0) {
				$scope.model_data.sub_exten = $scope.values;
			}

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/call/wizard/guest/' + id, 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if(data == 1062) {
						alert("Can 't save this data because Room ID is duplicated!");
					}else if( data == 1 ) {
						
						alert("Error!! Extension already enabled for Admin");	
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
					url: '/backoffice/call/wizard/guest', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if (data == 1)
						{
						alert("Error!! Extension already enabled for Admin");
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
				$http.get('/backoffice/call/wizard/guest/' + id)
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
					url: '/backoffice/call/wizard/guest/' + id 								
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
			
			$window.location.href = '/backoffice/property/wizard/auditguestext_excelreport?';
			
			
		}
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				$scope.cpname = data.cpname;
				delete data.checkbox;
				delete data.edit;
				delete data.delete;
				delete data.name;
				delete data.room;
				delete data.cpname;
				delete data.genable;
				
				return data;
			}
			var data = {};
			return data;
		}

		$scope.sub_extesnions = [];
		$scope.values = [];
		$scope.addSubexten = function () {
			$scope.sub_extesnions.push('');
		}

		$scope.minusSubexten = function (val) {
			for(var i = 0 ; i < $scope.values.length ; i ++) {
				if($scope.values[i] == val)
					$scope.values.splice(i, 1);
					$scope.sub_extesnions.splice(i,1);
			}
		}
		
	});
});	