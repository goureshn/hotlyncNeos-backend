define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('LocationCtrl', function ($scope, $compile, $timeout, $http, $window, FileUploader /*$location*/) {
		console.log("LocationCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/property', name: 'Property'},
					{link: '/property/location', name: 'Location'},
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
		initUploader();
		
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;		
				var alloption = {id: '0', name : '-- Select Property --'};
				$scope.properties.unshift(alloption);				
			});
		
		
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Name', 'Type', 'Property', 'Building', 'Floor', 'Room', 'Description','Disable'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/property/wizard/location',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cf.id' },
					{ data: 'name', name: 'sl.name' },
					{ data: 'type', name: 'lt.type' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'floor', name: 'cf.floor' },
					{ data: 'room', name: 'cr.room' },
					{ data: 'desc', name: 'sl.desc' },
					{ data: 'lenable', searchable: false, orderable: false, },
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
					url: '/backoffice/property/wizard/floor/upload',
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
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.model_data.property_id).success( function(response) {
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
			});	
		}
		
		$scope.changeBuilding = function()
		{	
			console.log($scope.building_id);
			$http.get('/backoffice/property/wizard/roomlist/assist?build_id=' + $scope.model_data.building_id).success( function(response) {
				$scope.floors = response.floor;	

				var alloption = {id: 0, floor : '-- Select Floor --'};
				$scope.floors.unshift(alloption);
				
				if($scope.floors.length > 0 )
					$scope.model_data.floor_id = $scope.floors[0].id;
				else
					$scope.model_data.floor_id = 0;				
			});	
		}

		$scope.changeProperty();
		$scope.changeBuilding();

		$scope.onShowEditRow = function(id)
		{

			$scope.model_data.id = id;
			$scope.error = '';
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
			}
			else
			{
				$scope.model_data.property_id = 0;
				$scope.model_data.building_id = 0;
				$scope.model_data.floor_id = 0;
				$scope.model_data.room_id = 0;
				$scope.model_data.type_id = 0;
				$scope.model_data.type = "";
				$scope.model_data.name = "";
				$scope.model_data.desc = "";
				$scope.model_data.property_id = $scope.properties[0].id;
				$scope.model_data.disable = 0;
				$scope.changeProperty();
			}
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			var set = true;
			if($scope.model_data.property_id ==  0) {
				$scope.error = 'Please select property.';
				set = false;
			}

			if($scope.model_data.name == '') {
				$scope.error = 'Please enter name.';
				set = false;
			}

			if($scope.model_data.desc == '') {
				$scope.error = 'Please enter description.';
				set = false;
			}

			if(set == true) {
				if (id >= 0)	// Update
				{
					$http({
						method: 'PUT',
						url: '/backoffice/property/wizard/location/' + id,
						data: $scope.model_data,
						headers: {'Content-Type': 'application/json; charset=utf-8'}
					})
						.success(function (data, status, headers, config) {
							//$scope.error = 'Successfully completed. Please click cancel button.';
							if (data) {
								refreshCurrentPage();
							}
							else {

							}
						})
						.error(function (data, status, headers, config) {
							console.log(status);
						});
				}
				else {
					$http({
						method: 'POST',
						url: '/backoffice/property/wizard/location',
						data: $scope.model_data,
						headers: {'Content-Type': 'application/json; charset=utf-8'}
					})
						.success(function (data, status, headers, config) {
							//$scope.error = 'Successfully completed. Please click cancel button.';
							if (data) {
								$scope.grid.fnPageChange('last');
							}
							else {

							}
						})
						.error(function (data, status, headers, config) {
							console.log(status);
						});
				}
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
					url: '/backoffice/property/wizard/location/' + id 								
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
				delete data.lenable;

				return data;
			}
			var data = {};
			return data;
		}

		$scope.getTypeList = function(val) {
			return $http.get('/backoffice/property/wizard/location_type?val='+val)
				.then(function(response){
					return response.data;
				});
		};

		$scope.onSelectType = function(model_data, $item, $model, $label)
		{
			$scope.model_data.type_id = $item.id;
		}

		$scope.onAddType = function(type)
		{
			if( type == '' )
				return;

			var request = {};
			request.type = type;

			$http({
				method: 'POST',
				url: '/backoffice/property/wizard/location/createtype',
				data: request,
				headers: {'Content-Type': 'application/json; charset=utf-8'}
			})
				.success(function (data, status, headers, config) {
					//$scope.error = 'Successfully completed. Please click cancel button.';
					if (data) {
						$scope.model_data.type_id = response.id;
					}
					else {

					}
				})
				.error(function (data, status, headers, config) {
					console.log(status);
				});
		}
	});
});