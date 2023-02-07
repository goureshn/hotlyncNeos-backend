define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('RoomtypeCtrl', function ($scope, $compile, $timeout, $window, $http, FileUploader /*$location*/) {
		console.log("RoomtypeCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		initUploader();
		
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;		
				var alloption = {id: 0, name : '-- Select Property --'};
				$scope.properties.unshift(alloption);				
			});
			
		$scope.menus = [
					{link: '/property', name: 'Property'},
					{link: '/property/roomtype', name: 'Room Type'},
				];

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
		
		$scope.fields = ['ID', 'Property', 'Building', 'Room Type', 'Max Time', 'Due Out', 'Check in', 'Turn Down', 'Linen Change', 'Description'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/property/wizard/roomtype',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'rt.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'type', name: 'rt.type' },
					{ data: 'max_time', name: 'rt.max_time' },
					{ data: 'due_out', name: 'rt.due_out' },
					{ data: 'checkin', name: 'rt.checkin' },
					{ data: 'turn_down', name: 'rt.turn_down' },
					{ data: 'linen_change', name: 'rt.linen_change' },
					{ data: 'description', name: 'rt.description' },
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
		
		function initUploader()
		{
			var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
			var uploader = $scope.uploader = new FileUploader({
					url: '/backoffice/property/wizard/roomtype/upload',
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
		}
		
		$scope.changeProperty = function()
		{	
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.buildings = response;		
				var alloption = {id: 0, name : '-- Select Building --'};
				$scope.buildings.unshift(alloption);
				
				if( $scope.property_id < 1 )
				{
					$scope.model_data.bldg_id = 0;								
				}
				else
				{
					
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
			}
			else
			{
				$scope.model_data.type = "";	
				$scope.model_data.max_time = 0;
				$scope.model_data.due_out = 0;
				$scope.model_data.checkin = 0;
				$scope.model_data.turn_down = 0;							
				$scope.model_data.description = "";
				$scope.property_id = 0;
			}

			$scope.changeProperty();			
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			delete $scope.model_data.property_id;
			
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/property/wizard/roomtype/' + id, 
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
					url: '/backoffice/property/wizard/roomtype', 
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
					url: '/backoffice/property/wizard/roomtype/' + id 								
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
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				delete data.checkbox;
				delete data.edit;
				delete data.delete;
				delete data.delete;
				delete data.cbname;
				delete data.cpname;

				return data;
			}
			var data = {};
			return data;
		}
		
	});
});