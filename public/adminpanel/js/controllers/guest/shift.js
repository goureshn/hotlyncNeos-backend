define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
	function (app) {

		app.controller('ShiftCtrl', function ($scope, $compile, $timeout,$window, $http, FileUploader /*$location, $http, initScript */) {
			console.log("ShiftCtrl reporting for duty.");

			$scope.model_data = {};

			$scope.menus = [
				{ link: '/guest', name: 'Guest Services' },
				{ link: '/guest/shift', name: 'Shift' },
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
				url: '/backoffice/guestservice/wizard/shift/upload',
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

			$scope.fields = ['ID', 'Name', 'Department Function', 'Task Group', 'Location Group', 'Building'];
			function initDomData() {
				var $grid = $('#table_grid').dataTable({
					processing: true,
					serverSide: true,
					order: [[0, "asc"]], //column indexes is zero based
					ajax: '/backoffice/guestservice/wizard/shift',
					"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
					columns: [
						//{ data: 'checkbox', width: '40px', orderable: false, searchable: false },
						{ data: 'id', name: 'su.id' },
						{ data: 'wholename', name: 'cu.first_name' },
						{ data: 'dept_func_list', name: 'df.function'},
					//	{ data: 'location_group_list', name: 'lg.name'},
					//	{ data: 'task_group_list', name: 'tg.name'},
					//	{ data: 'building_list', name: 'cb.name'},
					//	{ data: 'dept_func_list', name: 'su.dept_func_list' ,searchable: false},
						{ data: 'task_group_list', name: 'su.task_group_list', searchable: false },
						{ data: 'location_group_list', name: 'su.location_group_list',searchable: false },
						{ data: 'building_list', name: 'su.building_list',searchable: false },
						{ data: 'edit', width: '40px', orderable: false, searchable: false },
						{ data: 'delete', width: '40px', orderable: false, searchable: false }
					],
					"createdRow": function (row, data, dataIndex) {
						$compile(row)($scope);
					}
				});

				$scope.grid = $grid;

				$scope.shift_username = "";
				
				// get user list	
				$scope.userlist = [];
				$http.get('/list/userlist').success(function (response) {
					$scope.userlist = response;					
				});	

				// get department function list	
				$scope.dept_func_list = [];
				$http.get('/list/deptfunc').success(function (response) {
					response.forEach(element => {
						var item = { id: element.id, label: element.function };
						$scope.dept_func_list.push(item);
					});					
				});

				// get task group list	
				$scope.task_group_list = [];
				$http.get('/list/taskgroup').success(function (response) {
					response.forEach(element => {
						var item = { id: element.id, label: element.name };
						$scope.task_group_list.push(item);
					});					
				});
				
				// get location group list	
				$scope.locaton_group_list = [];
				$http.get('/list/locationgroups').success(function (response) {
					response.forEach(element => {
						var item = { id: element.id, label: element.name };
						$scope.locaton_group_list.push(item);
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

			$scope.hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};


			$scope.dept_func_ids = [];
			$scope.sec_dept_func_ids = [];
			$scope.deptfunc_hint = { buttonDefaultText: 'Dept Functions' };
			
			$scope.task_group_ids = [];
			$scope.taskgroup_hint = { buttonDefaultText: 'Task Groups' };

			$scope.location_group_ids = [];
			$scope.sec_location_group_ids = [];
			$scope.locationgroup_hint = { buttonDefaultText: 'Location Groups' };

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
				$scope.model_data.id = id;				
				$scope.dept_func_ids = [];
				$scope.sec_dept_func_ids = [];
				$scope.task_group_ids = [];
				$scope.location_group_ids = [];
				$scope.sec_location_group_ids = [];
				$scope.building_ids = [];
				$scope.shift_username = "";
				
				if (id > 0)	// Update
				{
					$http.get('/backoffice/guestservice/wizard/shift/' + id)
						.success(function (response) {
							console.log(response);
							$scope.model_data = response;							

							var shift_user = $scope.userlist.find( ele => {
								return ele.id == $scope.model_data.user_id;								
							});
							if( shift_user)
								$scope.shift_username = shift_user.first_name + (shift_user.last_name ? " " + shift_user.last_name : '');
							
							$scope.dept_func_ids = str2array($scope.model_data.dept_func_ids);
							$scope.sec_dept_func_ids = str2array($scope.model_data.sec_dept_func_ids);
							$scope.task_group_ids = str2array($scope.model_data.task_group_ids);
							$scope.location_group_ids = str2array($scope.model_data.location_group_ids);
							$scope.sec_location_group_ids = str2array($scope.model_data.sec_location_group_ids);
							$scope.building_ids = str2array($scope.model_data.building_ids);
						});
				}
				else {

				}
			}

			$scope.onUpdateRow = function () {
				var id = $scope.model_data.id;				

				$scope.model_data.dept_func_ids = array2str($scope.dept_func_ids);
				$scope.model_data.sec_dept_func_ids = array2str($scope.sec_dept_func_ids);
				$scope.model_data.task_group_ids = array2str($scope.task_group_ids);
				$scope.model_data.location_group_ids = array2str($scope.location_group_ids);
				$scope.model_data.sec_location_group_ids = array2str($scope.sec_location_group_ids);
				$scope.model_data.building_ids = array2str($scope.building_ids);

				if (id >= 0)	// Update
				{
					$http({
						method: 'PUT',
						url: '/backoffice/guestservice/wizard/shift/' + id,
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
						url: '/backoffice/guestservice/wizard/shift',
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
					$http.get('/backoffice/guestservice/wizard/shift/' + id)
						.success(function (response) {
							console.log(response);
							$scope.model_data = response;
							var shift_user = $scope.userlist.find( ele => {
								return ele.id == $scope.model_data.user_id;								
							});
							if( shift_user)
								$scope.shift_username = shift_user.first_name + (shift_user.last_name ? " " + shift_user.last_name : '');
						});
				}

			}

			$scope.deleteRow = function () {
				var id = $scope.model_data.id;

				if (id >= 0) {
					$http({
						method: 'DELETE',
						url: '/backoffice/guestservice/wizard/shift/' + id
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
				$window.location.href = '/backoffice/property/wizard/auditdevice_excelreport?';
			}

			$scope.onSelectUser = function(userlist, $item, $model, $label)
			{
				$scope.model_data.user_id = $item.id;
			}
		});

	});	
