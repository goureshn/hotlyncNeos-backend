define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('RoomCtrl', function ($scope, $compile, $timeout,$httpParamSerializer, $window, $http /*$location*/) {
		console.log("RoomCtrl reporting for duty.");
		
		$scope.model_data = {};
		
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/property', name: 'Property'},
					{link: '/property/room', name: 'Room'},
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
		
		$scope.fields = ['ID', 'Property', 'Building', 'Room Type', 'Floor', 'Housekeeping', 'Room', 'Credits', 'Vacant', 'Departure', 'Pickup','Description', 'Enable'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/property/wizard/room',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cr.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'type', name: 'rt.type' },
					{ data: 'floor', name: 'cf.floor' },
					{ data: 'status', name: 'hskp.status' },
					{ data: 'room', name: 'cr.room' },
					{ data: 'credits', name: 'cr.credits' },
					{ data: 'vacant', name: 'cr.vacant' },
					{ data: 'depart', name: 'cr.depart' },
					{ data: 'pickup', name: 'cr.pickup' },
					{ data: 'description', name: 'cr.description' },
					{ data: 'renable', searchable: false, orderable: false, },
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
		
		$scope.changeProperty = function()
		{	
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.buildings = response;		
				var alloption = {id: '0', name : '-- Select Building --'};
				$scope.buildings.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.buildings.length > 0 )
						$scope.building_id = $scope.buildings[0].id;				
					else
						$scope.building_id = 0;					
				}
				
				
				$scope.changeBuild();
			});	
		}
		
		$scope.changeBuild = function()
		{	
			console.log($scope.building_id);
			$http.get('/backoffice/property/wizard/roomlist/assist?build_id=' + $scope.model_data.bldg_id).success( function(response) {
				$scope.floors = response.floor;	
				$scope.roomtypes = response.roomtype;	
				$scope.hskps = response.hskp;	
				
				// if( $scope.model_data.id < 1 )
				{
					if($scope.roomtypes.length > 0 )
						$scope.model_data.type_id = $scope.roomtypes[0].id;
					else
						$scope.model_data.type_id = 0;
					
					if($scope.floors.length > 0 )
						$scope.model_data.flr_id = $scope.floors[0].id;
					else
						$scope.model_data.flr_id = 0;
					
				
					if($scope.hskps.length > 0 )
						$scope.model_data.hskp_status_id = $scope.hskps[0].id;
					else
						$scope.model_data.hskp_status_id = 0;					
				}
			});	
		}
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
				$scope.property_id = $scope.model_data.property_id;
				$scope.building_id = $scope.model_data.bldg_id;

				$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.property_id).success( function(response) {
					$scope.buildings = response;
					var alloption = {id: '0', name : '-- Select Building --'};
					$scope.buildings.unshift(alloption);
				});

				$http.get('/backoffice/property/wizard/roomlist/assist?build_id=' + $scope.building_id).success( function(response) {
					$scope.floors = response.floor;
					$scope.roomtypes = response.roomtype;
					$scope.hskps = response.hskp;
				});
			}
			else
			{
				$scope.model_data.room = "";
				$scope.model_data.credits = "";
				$scope.model_data.vacant = "";
				$scope.model_data.depart = "";
				$scope.model_data.pickup = "";
				$scope.model_data.description = "";
				$scope.model_data.enable = 0;
				$scope.property_id = $scope.properties[0].id;
				$scope.changeProperty();
			}
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;

			delete $scope.model_data.property_id;
			delete $scope.model_data.bldg_id;
			
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/property/wizard/room/' + id, 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data == 1 ) {
						alert('Total Rooms Exceeded');
						//refreshCurrentPage();		
					}
					else {
						refreshCurrentPage();		
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
					url: '/backoffice/property/wizard/room', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data == 1 ) {
						alert('Total Rooms Exceeded');
						//$scope.grid.fnPageChange( 'last' );
					}
					else {
						$scope.grid.fnPageChange( 'last' );
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
					url: '/backoffice/property/wizard/room/' + id 								
				})
				.success(function(data, status, headers, config) {
					refreshCurrentPage();						
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
			$window.location.href = '/backoffice/property/wizard/auditroom_excelreport?';			
		}
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				
				delete data.checkbox;
				delete data.edit;
				delete data.delete;
				delete data.type;
				delete data.floor;
				delete data.status;
				delete data.cbname;
				delete data.cpname;
				delete data.cbid;
				delete data.cpid;
				delete data.renable;


				return data;
			}
			var data = {};
			return data;
		}
		
	});
	
});