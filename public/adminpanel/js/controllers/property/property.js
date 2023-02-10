define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('PropertyCtrl', function ($scope, $compile, $timeout, $http, $window, FileUploader  /*$location, $http, initScript */) {
		console.log("PropertyCtrl reporting for duty.");
		
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.model_data = {};

		initUploader();

		$scope.menus = [
					{link: '/property', name: 'Property'},
					{link: '/property/property', name: 'Property'},
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
		$http.get('/list/client').success( function(response) {
				$scope.clients = response;			
			});
		// $http.get('/list/modules').success( function(response) {
		// 		$scope.modules = response;
		// 	});
		
		$timeout( initDomData, 0, false );

		$http.get('/list/module').success( function(response) {
			$scope.module = response;
			$scope.modules = [];
			for(var i = 0; i < $scope.module.length ; i++) {
				var mo = {id: $scope.module[i].id, label: $scope.module[i].name};
				$scope.modules.push(mo);
			}
		});

		$scope.modules_hint = {buttonDefaultText: 'Select Module'};
		$scope.modules_hint_setting = {
			smartButtonMaxItems: 3,
			smartButtonTextConverter: function(itemText, originalItem) {
				return itemText;
			}
		};

		$scope.module_type = [];
		
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Property', 'Client', 'Address', 'City', 'Country', 'Contact Person', 'Mobile Number', 'Modules','Short Code'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/property/wizard/property',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cp.id' },
					{ data: 'name', name: 'cp.name' },
					{ data: 'ccname', name: 'cc.name' },
					{ data: 'address', name: 'cp.address' },
					{ data: 'city', name: 'cp.city' },
					{ data: 'country', name: 'cp.country' },
					{ data: 'contact', name: 'cp.contact' },
					{ data: 'mobile', name: 'cp.mobile' },					
					// { data: 'modules', name: 'cm.modules' },
					{ data: 'modules', width: '280px',orderable: false, searchable: false },
					{ data: 'shortcode', name: 'cp.shortcode' },					
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
				url: '/backoffice/property/wizard/property/uploadlogo',
				alias : 'myfile',
				headers: headers
			});
			uploader.filters.push({
				name: 'imageFilter',
				fn: function(item /*{File|FileLikeObject}*/, options) {
					var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
					return '|jpg|png|bmp|'.indexOf(type) !== -1;
				}
			});
			uploader.onSuccessItem = function(fileItem, response, status, headers) {
				$scope.model_data.logo_path = '/' + response.content;
			};
			uploader.onErrorItem = function(fileItem, response, status, headers) {
				console.info('onErrorItem', fileItem, response, status, headers);
			};
		}

		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			$scope.error = '';
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);

				var module = $scope.model_data.modules;
				$scope.module_type = [];
				for(var i =0; i < module.length ; i++) {
					for(var j = 0; j< $scope.modules.length ; j++) {
						if(module[i] == $scope.modules[j].label) {
							var val = {id:$scope.modules[j].id };
							$scope.module_type.push(val);
						}
					}
				}
			}
			else
			{
				$scope.model_data.client_id = $scope.clients[0].id;
				//$scope.model_data.modules = [];
				$scope.model_data.name = "";
				$scope.model_data.description = "";
				$scope.model_data.contact = "";
				$scope.model_data.mobile = "";
				$scope.model_data.email = "";
				$scope.model_data.city = "";
				$scope.model_data.country = "";
				$scope.model_data.address = "";
				$scope.model_data.logo_path = "";
				//$scope.model_data.module_type = [];
				$scope.model_data.shortcode = "";
			}		
		}


		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			var set = true;
			if($scope.model_data.name ==  '') {
				$scope.error = 'Please enter property.';
				set = false;
			}
			if($scope.model_data.address ==  '') {
				$scope.error = 'Please enter address.';
				set = false;
			}
			if($scope.model_data.city ==  '') {
				$scope.error = 'Please enter city.';
				set = false;
			}
			if($scope.model_data.country ==  '') {
				$scope.error = 'Please enter country.';
				set = false;
			}
			if($scope.model_data.contact ==  '') {
				$scope.error = 'Please enter contact.';
				set = false;
			}
			if($scope.model_data.mobile ==  '') {
				$scope.error = 'Please enter mobile.';
				set = false;
			}
			if($scope.model_data.email ==  '') {
				$scope.error = 'Please enter email.';
				set = false;
			}
			$scope.model_data.modules_ids = [];
			for(var i = 0; i < $scope.module_type.length; i++)
				$scope.model_data.modules_ids.push($scope.module_type[i].id);

			if(set == true) {
				// var modules = "";
				// $.each($scope.model_data.modules, function (i, e) {
				// 	if (i == 0)
				// 		modules += e;
				// 	else
				// 		modules += "," + e;
				// });

				var data = jQuery.extend(true, {}, $scope.model_data);

				//data.modules = modules;


				if (id >= 0)	// Update
				{
					$http({
						method: 'POST',
						url: '/backoffice/property/wizard/propertyupdate/' + id,
						data: data,
						headers: {'Content-Type': 'application/json; charset=utf-8'}
					})
						.success(function (data, status, headers, config) {
							$scope.error = 'Successfully completed. Please click cancel button.';
							if (data) {
								refreshCurrentPage();
								$('#addModal').modal('hide');
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
						url: '/backoffice/property/wizard/propertycreate',
						data: data,
						headers: {'Content-Type': 'application/json; charset=utf-8'}
					})
						.success(function (data, status, headers, config) {
							$scope.error = 'Successfully completed. Please click cancel button.';
							if (data) {
								$scope.grid.fnPageChange('last');
								$('#addModal').modal('hide');
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
					url: '/backoffice/property/wizard/property/' + id 								
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
				delete data.ccname;
				delete data.edit;
				data.modules = data.modules.split(",");
				
				return data;
			}
			var data = {};
			return data;
		}
		
	});
});