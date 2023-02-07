define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
	function (app) {

		app.controller('DeviceCtrl', function ($scope, $compile, $timeout,$window, $http, FileUploader /*$location, $http, initScript */) {
			console.log("DeviceCtrl reporting for duty.");

			$scope.model_data = {};
			$scope.status_list = ['All', 'Online', 'Offline', 'Disabled'];
			$scope.device_status = 'All';

			$scope.menus = [
				{ link: '/guest', name: 'Guest Services' },
				{ link: '/guest/device', name: 'Device' },
			];

			//edit permission check
			var permission = $scope.globals.currentUser.permission;
			$scope.edit_flag = 0;
			for (var i = 0; i < permission.length; i++) {
				if (permission[i].name == "access.superadmin") {
					$scope.edit_flag = 1;
					break;
				}
			}
			//end///
			var headers = { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') };
			var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/device/upload',
				alias: 'myfile',
				headers: headers
			});
			uploader.filters.push({
				name: 'excelFilter',
				fn: function (item /*{File|FileLikeObject}*/, options) {
					var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
					return '|csv|xls|xlsx|'.indexOf(type) !== -1;
				}
			});
			uploader.onSuccessItem = function (fileItem, response, status, headers) {
				$('#closeButton').trigger('click');
				$scope.grid.fnPageChange('last');
			};
			uploader.onErrorItem = function (fileItem, response, status, headers) {
				console.info('onErrorItem', fileItem, response, status, headers);
			};


			$timeout(initDomData, 0, false);

			$scope.grid = {};

			$scope.fields = ['ID', 'Name', 'Department Function', 'Secondary Department Function', 'Type', 'Number','Status', 'Location Group', 'Secondary Location Group', 'Building', 'Device ID', 'Device User', 'Device Name', 'Device Api Level', 'OS', 'Device Manufacturer', 'Device Model'];
			function initDomData() {
				var status_div = jQuery('#status_id');

				var $grid = $('#table_grid').dataTable({
					processing: true,
					serverSide: true,
					destroy: true,
					order: [[0, "asc"]], //column indexes is zero based
					ajax: '/backoffice/guestservice/wizard/device?status='+$scope.device_status,
					"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
					columns: [
						//{ data: 'checkbox', width: '40px', orderable: false, searchable: false },
						{ data: 'id', name: 'sd.id' },
						{ data: 'name', name: 'sd.name' },
						{ data: 'function', name: 'sd.function', searchable: false},
						{ data: 'sec_function', orderable: false, searchable: false },
						{ data: 'type', name: 'sd.type' },
						{ data: 'number', name: 'sd.number' },
						{ data: 'device_status', name: 'device_status', searchable: false },
						{ data: 'loc_name', name: 'lg.name', searchable: false },
						{ data: 'sec_loc_name', name: 'lg2.name', searchable: false },
						{ data: 'cb_name', name: 'cb.name', searchable: false },
						{ data: 'device_id', name: 'sd.device_id' },
						{ data: 'device_user', name: 'sd.device_user' },
						{ data: 'device_model', name: 'sd.device_model' },
						{ data: 'device_api_level', name: 'sd.device_api_level' },
						{ data: 'device_os', name: 'sd.device_os' },
						{ data: 'device_manufacturer', name: 'sd.device_manufacturer' },
						{ data: 'device_version_release_model', name: 'sd.device_version_release_model' },
						{ data: 'edit', width: '40px', orderable: false, searchable: false },
						{ data: 'delete', width: '40px', orderable: false, searchable: false }
					],
					"createdRow": function (row, data, dataIndex) {
						$compile(row)($scope);
					}
				});

				$scope.grid = $grid;

				var filter_field = jQuery('#table_grid_filter');
				filter_field.append(status_div);
			}

			$scope.onChangeStatus = function() {
				$timeout( initDomData, 0, false );
			}

			function initData()
			{
				$scope.dept_func_list = [];

				$http.get('/list/deptfunc').success(function (response) {
					dept_func_list = response;

					response.forEach((ele) => {
						var item = {
							id: ele.id,
							function: ele.function
						};

						var label = ele.function + " - " + "(";
						switch(ele.gs_device)
						{
							case "0":
								label += "User";
								break;
							case "1":
								label += "Device";
								break;
							case "2":
								label += "Roster";
								break;
						}

						item.label = label + ")";

						$scope.dept_func_list.push(item);
					});
				});

				$http.get('/list/building').success(function (response) {
					$scope.builds = response;
				});

				$scope.locaton_group_list = [];
				$http.get('/list/locationgroups').success(function (response) {
					$scope.loc_grps = response;
					for (var i = 0; i < $scope.loc_grps.length; i++) {
						var item = { id: $scope.loc_grps[i].id, label: $scope.loc_grps[i].name };
						$scope.locaton_group_list.push(item);
					}
				});
				$http.get('/list/phonetype').success(function (response) {
					$scope.phonetypes = response;
				});
				$http.get('/list/devices').success(function (response) {
					$scope.devices = response.map(function(item) {
						return item.device_id;
					});
				});

				// get building  list
				$scope.building_list = [];
				$http.get('/list/building').success(function (response) {
					response.forEach(element => {
						var item = { id: element.id, label: element.name };
						$scope.building_list.push(item);
					});
				});
			}

			initData();

			$scope.hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};

			$scope.dept_func_array_id = [];
			$scope.sec_dept_func = [];
			$scope.deptfunc_hint = { buttonDefaultText: 'Department Function' };

			$scope.loc_grp_array_id = [];
			$scope.sec_loc_grp_array_id = [];
			$scope.location_group_hint = { buttonDefaultText: 'Location Group' };

			$scope.building_ids = [];
			$scope.building_hint = { buttonDefaultText: 'Building' };


			function str2array(str) {
				var ids = [];
				if( str )
				{
					val = str.split(',');
					val.forEach(element => {
						var val = { id: parseInt(element) };
						ids.push(val);
					});
				}

				return ids;
			}

			function array2str(ids)
			{
				temp = "";
				ids.forEach((element, index) => {
					if(index > 0)
						temp += ",";

					temp += element.id;
				});

				return temp;
			}

			$scope.onShowEditRow = function (id) {
				// uploader.clearQueue();
				$http.get('/list/devices').success(function (response) {
					$scope.devices = response.map(function(item) {
						return item.device_id;
					});
				});

				$scope.copy_device_name = '';
				$scope.dept_func_array_id = [];
				$scope.sec_dept_func = [];
				$scope.loc_grp_array_id = [];
				$scope.sec_loc_grp_array_id = [];
				$scope.building_ids = [];
				$scope.model_data.id = id;
				$scope.model_data.name = "";

				$scope.model_data.type = $scope.phonetypes['1'];
				$scope.model_data.number = "";
				$scope.model_data.priority_flag = false;
				$scope.model_data.device_id = "";

				if (id > 0)	// Update
				{
					$http.get('/backoffice/guestservice/wizard/device/' + id)
						.success(function (response) {
							console.log(response);
							$scope.model_data = response;
							// window.alert($scope.model_data.priority_flag);
							if($scope.model_data.priority_flag==1){
								$scope.model_data.priority_flag = true;
							}else{
								$scope.model_data.priority_flag = false;
							}

							$scope.dept_func_array_id = str2array($scope.model_data.dept_func_array_id);
							$scope.sec_dept_func = str2array($scope.model_data.sec_dept_func);
							$scope.loc_grp_array_id = str2array($scope.model_data.loc_grp_array_id);
							$scope.sec_loc_grp_array_id = str2array($scope.model_data.sec_loc_grp_id);
							$scope.building_ids = str2array($scope.model_data.building_ids);
						});
				}
				else {

				}
			}

			$scope.onUpdateRow = function () {
				var id = $scope.model_data.id;

				if($scope.model_data.priority_flag==true)
					$scope.model_data.priority_flag = 1;
				else
					$scope.model_data.priority_flag = 0;

				$scope.model_data.dept_func_array_id = array2str($scope.dept_func_array_id);
				$scope.model_data.sec_dept_func = array2str($scope.sec_dept_func);

				$scope.model_data.loc_grp_array_id = array2str($scope.loc_grp_array_id);
				$scope.model_data.sec_loc_grp_id = array2str($scope.sec_loc_grp_array_id);

				$scope.model_data.building_ids = array2str($scope.building_ids);


				if (id >= 0)	// Update
				{
					$http({
						method: 'PUT',
						url: '/backoffice/guestservice/wizard/device/' + id,
						data: $scope.model_data,
						headers: { 'Content-Type': 'application/json; charset=utf-8' }
					})
						.success(function (data, status, headers, config) {
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
						url: '/backoffice/guestservice/wizard/device/storeng',
						data: $scope.model_data,
						headers: { 'Content-Type': 'application/json; charset=utf-8' }
					})
						.success(function (data, status, headers, config) {
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

			$scope.onDeleteRow = function (id) {
				if (id >= 0) {
					$http.get('/backoffice/guestservice/wizard/device/' + id)
						.success(function (response) {
							console.log(response);
							$scope.model_data = response;
						});
				}

			}

			$scope.deleteRow = function () {
				var id = $scope.model_data.id;

				if (id >= 0) {
					$http({
						method: 'DELETE',
						url: '/backoffice/guestservice/wizard/device/' + id
					})
						.success(function (data, status, headers, config) {
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
			}

			function refreshCurrentPage() {
				var oSettings = $scope.grid.fnSettings();
				var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
				$scope.grid.fnPageChange(page);
			}

			$scope.onDownloadExcel = function() {

				//$window.alert($scope.filter.property_id);
				$window.location.href = '/backoffice/property/wizard/auditdevice_excelreport?';
			}

			$scope.getDeviceList = function(val)
			{
				if( val == undefined )
					val = "";
				return $http.get('/backoffice/guestservice/wizard/devicelist?val='+val)
					.then(function(response){
						return response.data;
					});
			}

			$scope.onSelectDevice = function(item, $item, $model, $label)
			{
				$http.get('/backoffice/guestservice/wizard/device/' + $item.id)
						.success(function (response) {
							console.log(response);
							$scope.dept_func_array_id = str2array(response.dept_func_array_id);
							$scope.sec_dept_func = str2array(response.sec_dept_func);
							$scope.loc_grp_array_id = str2array(response.loc_grp_array_id);
							$scope.sec_loc_grp_array_id = str2array(response.sec_loc_grp_id);
							$scope.building_ids = str2array(response.building_ids);
						});
			}

			$scope.$watch('dept_func_array_id', function(newValue, oldValue) {
				console.log(newValue);
				if(newValue.length < 2 )
					return;

				var request = {};
				request.dept_func_array_id = newValue;
				$http({
					method: 'POST',
					url: '/backoffice/guestservice/wizard/device/checkprimarydeptfunc',
					data: request,
					headers: { 'Content-Type': 'application/json; charset=utf-8' }
				})
					.success(function (data, status, headers, config) {
						if( data.code == 200 )
						{
							if( data.roster_setting_count > 1 )
							{
								$scope.dept_func_array_id = oldValue;
								alert("Device cannot have 2 primary department functions with Roster");
							}
						}
					})
					.error(function (data, status, headers, config) {
						console.log(status);
					});
            }, true);


		});

	});
