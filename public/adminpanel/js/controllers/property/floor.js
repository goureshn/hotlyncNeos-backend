define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('FloorCtrl', function ($scope, $compile, $timeout, $http, $window, FileUploader /*$location*/) {
		console.log("FloorCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/property', name: 'Property'},
					{link: '/property/floor', name: 'Floor'},
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
		
		$scope.fields = ['ID', 'Property', 'Building', 'Floor', 'Description'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/property/wizard/floor',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cf.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'floor', name: 'cf.floor' },
					{ data: 'description', name: 'cf.description' },
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
			// var data = {};
			// data.property_id = $scope.property_id;
			// if( data.property_id < 1 )
			// {
			// 	alert("Please Select Property!");
			// 	return;
			// }

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
			});	
		}
		$scope.changeProperty();

		$scope.read = '';
		$scope.cpname = '';
		$scope.cbname = '';
		$scope.onShowEditRow = function(id)
		{

			$scope.model_data.id = id;
			$scope.error = '';
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);

				for(var i = 0; i < $scope.properties.length ; i++) {
					if($scope.properties[i].name == $scope.cpname)
						$scope.property_id = $scope.properties[i].id;
				}

				for(var i = 0; i < $scope.buildings.length ; i++) {
					if($scope.buildings[i].name == $scope.cbname)
						$scope.model_data.bldg_id = $scope.buildings[i].id;
				}

				$scope.read = 'readonly';
			}
			else
			{
				$scope.model_data.bldg_id = "";
				$scope.model_data.floor = "";
				$scope.model_data.description = "";
				$scope.read = 'readonly';
				$scope.property_id = $scope.properties[0].id;
				$scope.changeProperty();
			}


		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			var set = true;
			if($scope.property_id ==  0) {
				$scope.error = 'Please select property.';
				set = false;
			}
			if($scope.model_data.bldg_id == 0) {
				$scope.error = 'Please select building.';
				set = false;
			}
			if($scope.model_data.floor == '') {
				$scope.error = 'Please enter floor.';
				set = false;
			}
			if($scope.model_data.description == '') {
				$scope.error = 'Please enter description.';
				set = false;
			}

			if(set == true) {
				if (id >= 0)	// Update
				{
					$http({
						method: 'PUT',
						url: '/backoffice/property/wizard/floor/' + id,
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
						url: '/backoffice/property/wizard/floor',
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
					url: '/backoffice/property/wizard/floor/' + id 								
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
				$scope.cbname = data.cbname;
				$scope.cpname = data.cpname;
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