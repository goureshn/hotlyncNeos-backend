define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
		function (app) {

	app.filter('capitalize', function() {
	    return function(input) {
	      return (!!input) ? input.charAt(0).toUpperCase() + input.substr(1).toLowerCase() : '';
	    }
	});
		
	app.controller('UserCtrl', function ($scope, $rootScope, $window, $httpParamSerializer, $localStorage,$sessionStorage, $compile, $timeout, $http /*$location, $http, initScript */) {
		console.log("UserCtrl reporting for duty.");
		$scope.image = null;
		$scope.imageFileName = '';
		$scope.uploadme = {};
		$scope.uploadme.src = '';
		$scope.contact_mode_list = ['e-mail', 'SMS', 'Mobile'];
		$scope.status_list = ['All', 'Active', 'Disabled'];
		$scope.active_status = 'All';
		$scope.property_id = 0;

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
		$scope.agent_id = $sessionStorage.admin.currentUser.id;
		var client_id = $sessionStorage.admin.currentUser.client_id;
		$scope.is_super_admin = $sessionStorage.admin.currentUser.job_role == 'SuperAdmin';
		$scope.model_data = {};

		$scope.menus = [
					{link: '/user', name: 'User'},
					{link: '/user/user', name: 'User'},
				];

		$http.get('/backend_api/multipropertylist?client_id='+client_id).success( function(response) {
				$scope.properties = response;
				var alloption = {id: '0', name : '-- Select Property --'};
				$scope.properties.unshift(alloption);				
				if( $scope.properties.length > 0 )
					$scope.property_id = $scope.properties[0].id;
			});		

		$http.get('/list/usergroup').success( function(response) {
			$scope.usergroup = response;
			$scope.usergroups = [];
			for(var i = 0; i < $scope.usergroup.length ; i++) {
				var user = {id: $scope.usergroup[i].id, label: $scope.usergroup[i].name};
				$scope.usergroups.push(user);
			}
		});
		$http.get('/list/userlang').success(function (response) {
				
			$scope.langs = [];
			for (var i = 0; i < response.length; i++) {
				var lang = { id: response[i].id, label: response[i].language };
				$scope.langs.push(lang);
			}
			$scope.langs.push( { id: 0, label: 'English'});
		});
		$scope.getjob_roles = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/user/jobrole?property_id='+$scope.property_id+'&job_role='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};

		$scope.onJobRoleSelect = function (job_role, $item, $model, $label) {
			var job_role = {};
			$scope.model_data.job_role_id = $item.id;
			$scope.job_role = $item.job_role;
		};


		$timeout( initDomData, 0, false );



		$scope.usergroups_hint = {buttonDefaultText: 'Select User Group'};
		$scope.usergroups_hint_setting = {
			smartButtonMaxItems: 3,
			smartButtonTextConverter: function(itemText, originalItem) {
				return itemText;
			}
		};

		$scope.group_type = [];
		

		$scope.grid = null;
		$scope.idkey = [];
		
		//$scope.fields = ['ID', 'Image', 'Building', 'First Name', 'Last Name', 'User Name', 'Employee ID','Login PIN','Shift Group', 'User Group', 'Job Role', 'Access Code', 'IVR-Password', 'Department', 'Mobile', 'Email', 'Business Hours', 'After Work', 'Casual Staff','Reset'];
		$scope.fields = ['ID', 'Image', 'Building', 'First Name', 'Last Name', 'User Name','Language', 'User Group', 'Job Role', 'IVR-Password', 'Department', 'Mobile', 'Email','Disabled', 'Online', 'Reset'];
		function initDomData() {
			var status_div = jQuery('#status_id');

			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				destroy: true,
				order: [[ 0, "desc" ]], //column indexes is zero based
				ajax: {
					url: '/backoffice/user/wizard/user?client_id='+client_id+'&status='+$scope.active_status,
					type: 'GET',
					"beforeSend": function(xhr){
				            xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
				        }
				},
				"lengthMenu": [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, "All"]],
				columns: [					
					{ data: 'id', name: 'cu.id'},
					{ data: 'image', width: '40px', orderable: false, searchable: false},
					{ data: 'cbname', name: 'cb.name', orderable: false, searchable: false},
					{ data: 'first_name', name: 'cu.first_name' },
					{ data: 'last_name', name: 'cu.last_name'},
					{ data: 'username', name: 'cu.username' },
					{ data: 'language', name: 'cul.language' },
					//{ data: 'employee_id', name: 'cu.employee_id' },
					{ data: 'usergroup', name: 'cg.name' },
					//{ data: 'shiftgroup', width: '80px', orderable: false, searchable: false },
					//{ data: 'usergroup', width: '80px',orderable: false, searchable: false },
					{ data: 'job_role', name: 'jr.job_role' },
					//{ data: 'access_code', name: 'cu.access_code' },
					{ data: 'ivr_password', name: 'cu.ivr_password' },
					{ data: 'department', name: 'cd.department' },
					{ data: 'mobile', name: 'cu.mobile' },
					{ data: 'email', name: 'cu.email' },
					{ data: 'disabled_label', name: 'cu.deleted' },
					{ data: 'online_label', name: 'cu.active_status' },
					//{ data: 'picture', name: 'cu.picture'},
					//{ data: 'contact_pref_bus', name: 'cu.contact_pref_bus'},
					//{ data: 'contact_pref_nbus', name: 'cu.contact_pref_nbus'},
					//{ data: 'casual_staff', name: 'cu.casual_staff' },
					{ data: 'reset', width: '40px', orderable: false, searchable: false},
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					//{ data: 'delete', width: '40px', orderable: false, searchable: false},
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
				}
			});	
			
			$scope.grid = $grid;
			var height = ($window.innerHeight - 240) + 'px';

			$('.dataTables_wrapper  > div:nth-child(2)').css('height', height);

			var filter_field = jQuery('#table_grid_filter');
			filter_field.append(status_div);						
		}

		$scope.onChangeStatus = function() {
			$timeout( initDomData, 0, false );
		}

		$scope.changeProperty = function()
		{	
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.property_id).success( function(response) {
				$scope.buildings = response;						
				$scope.building_name_list = [];			
				$scope.changeBuild();
			});	
		}

		$scope.changeBuild = function()
		{			
			if($scope.property_id == 0)
				return;

			$http.get('/backoffice/user/wizard/department?building_id=' + $scope.building_id ).success( function(response) {
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
				//$scope.changeDepartment();	
			});	
		}

		$scope.getdepartment = function(val) {
			if( val == undefined )
				val = "";

			var building_ids = names2Ids($scope.building_name_list, $scope.buildings, 'name', 'id');	
			return $http.get('/backoffice/user/wizard/departmentlist?building_ids=' + building_ids + '&department='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};

		$scope.ondepartment = function (department, $item, $model, $label) {
			var departments = {};
			if ($scope.model_data.dept_id != $item.id)
			{
				$scope.model_data.shift_id=null;
				$scope.shift_name='';
			}
			$scope.model_data.dept_id = $item.id;
			$scope.dept_name = $item.department;
			//window.alert(JSON.stringify($scope.getshift('')));
		};

		function ids2Names(ids, list, name_key, id_key)
		{
			if( ids == null || ids == '' )
				return [];
				
			ids = ids.split(',');

			return ids.map(id => {
				var row = list.find(item => {
					return parseInt(id) == item[id_key];
				})

				return row[name_key];
			});
		}

		function names2Ids(names, list, text_key, id_key)
		{
			if( names == null || names == '' )
				return '';

			return names.map(name => {
				var row = list.find(item => {
					return name.text == item[text_key];
				})

				return row[id_key];
			}).join();
		}


		$scope.getshift = function (val) {
			if (val == undefined)
				val = "";
			if (!$scope.model_data.dept_id)
			{
				alert("Please select Department.");
				return;
			}
			return $http.get('/list/shiftgroup?dept_id=' + $scope.model_data.dept_id).then(function (response) {
				if (!$scope.shift_name)
				{
					for(i=0;i<response.data.length;i++)
					{
						if(response.data[i].name=='Default')
						{
							$scope.model_data.shift_id = response.data[i].id;
							$scope.shift_name = response.data[i].name;
						}
					}
					if (!$scope.model_data.shift_id)
					{
						$scope.model_data.shift_id = response.data[0].id;
						$scope.shift_name = response.data[0].name;
					}
					
				}
				
				return response.data.map(function (item) {
					return item;
				});
			});
		};
		$scope.onshift = function (shift, $item, $model, $label) {
			var shifts = {};
			$scope.model_data.shift_id = $item.id;
			$scope.shift_name = $item.name;
		};

		$scope.changeProperty();
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);			
				history(id);

						
				$scope.shift_name = $scope.model_data.shiftgroup;

				//$scope.model_data.usergroup_ids = usergroup_ids;
				$scope.model_data.agent_id = $scope.agent_id;
				$http.get('/backoffice/user/wizard/user/getimage?image_url='+$scope.model_data.picture).success(function(response) {
					$scope.uploadme.src = response;
					var url = $scope.model_data.picture;
					//$scope.uploadme.imagetype = 'image/' + url.substr(url.lastIndexOf(".") + 1, url.length);
					$scope.uploadme.imagetype = 'image/jpeg';
					$scope.uploadme.imagename = $scope.model_data.picture;
				});	
				
				for(var i = 0 ;i <  $scope.properties.length ; i++) {
					if($scope.cpname == $scope.properties[i].name) {
						$scope.property_id = $scope.properties[i].id;
						
						break;
					}
				}
				
				$scope.building_name_list = ids2Names($scope.model_data.building_ids, $scope.buildings, 'name', 'id');
				
				$scope.job_role = $scope.job_name;

				var usergroup = $scope.model_data.usergroup.split(',');	
				$scope.group_type = [];
				for(var i =0; i < usergroup.length ; i++) {
					for(var j = 0; j< $scope.usergroups.length ; j++) {
						if(usergroup[i] == $scope.usergroups[j].label) {
							var val = {id:$scope.usergroups[j].id };
							$scope.group_type.push(val);
						}
					}
				}	
			}
			else
			{
				$scope.property_id = $scope.properties[0].id;
				$scope.changeProperty();
				$scope.model_data.dept_id = 0;
				$scope.model_data.shift_id = 0;
				$scope.dept_name = '';
				$scope.shift_name = '';
				$scope.model_data.first_name = "";
				$scope.model_data.last_name = "";
				$scope.model_data.username = "";
				// $scope.model_data.usergroup_ids = $scope.usergroups[0].id;
				$scope.model_data.employee_id = "";
				$scope.model_data.login_pin = "";
				$scope.model_data.access_code = null;
				$scope.model_data.password = "";
				$scope.model_data.ivr_password = "";
				$scope.model_data.job_role_id = 0;
				$scope.job_role = '';
				$scope.model_data.mobile = "";
				$scope.model_data.email = "";
				$scope.casual_staff = false;
				$scope.model_data.picture = "";
				$scope.model_data.contact_pref_bus = "Mobile";
				$scope.model_data.contact_pref_nbus = "Mobile";
				$scope.uploadme.src = null;
				$scope.uploadme.imagetype = 'image/jpeg';
				$scope.uploadme.imagename = '';
				$scope.historylist = {};
				$scope.building_name_list = [];
				$scope.model_data.usergroup_ids = [];
			}		
		}


		$scope.onUpdateRow = function()
		{
			//window.alert("here");

			if($scope.property_id == 0) {
				alert("Please enter Property");
				return;
			}
			if($scope.building_name_list.length < 1) {
				alert("Please enter Building");
				return;
			}
			if($scope.model_data.username == undefined) {
				alert(" User Name should not be able to add special character.");
				return;
			}
			/*
			if($scope.model_data.email == undefined) {
				alert("Invalid email address format.");
				return;
			} */
			if($scope.model_data.first_name == "") {
				alert("Please enter First Name");
				return;
			}
			
			if($scope.model_data.username == "") {
				alert("Please enter Username");
				return;
			}
			
			if($scope.model_data.username == "") {
				alert("please enter username");
				return;
			}

			if($scope.job_role == "") {
				alert("Please enter Job Role");
				return;
			}
			if($scope.dept_name == "") {
				alert("Please enter Department");
				return;
			}
			// if(!($scope.shift_name)) {
			// 	alert("Please select Shift");
			// 	return;
			// }
			$scope.model_data.username = $scope.model_data.username.toLowerCase();
			var id = $scope.model_data.id;
			$scope.model_data.agent_id = $scope.agent_id;
			//if(id >= 0 && $scope.model_data.lock == true) $scope.model_data.lock = 'Yes';
			//if(id >= 0 && $scope.model_data.lock == false) $scope.model_data.lock = 'No';
			if(id <= 0) $scope.model_data.lock = 'No'; //add new user
			$scope.model_data.casual_staff = $scope.casual_staff ? 'Y' : 'N';
			if($scope.uploadme.src != null && $scope.uploadme.src != "") {
				var extension = $scope.uploadme.imagename.substr($scope.uploadme.imagename.lastIndexOf("."), $scope.uploadme.imagename.length);
				var imagename = $scope.model_data.username;
				$scope.model_data.picture_src = $scope.uploadme.src;
				$scope.model_data.picture_name = imagename+extension;
			}
			$scope.model_data.usergroup_ids = [];
			for(var i = 0; i < $scope.group_type.length; i++)
				$scope.model_data.usergroup_ids.push($scope.group_type[i].id);

			$scope.model_data.building_ids = names2Ids($scope.building_name_list, $scope.buildings, 'name', 'id');
			

			if( id >= 0 )	// Update
			{
				var valid = true;
				if( $scope.model_data.deleted == true && $scope.model_data.already_deleted != true )
					valid = confirm('Are you sure you want to disable ' + $scope.model_data.first_name + ' ' + $scope.model_data.last_name + '?');

				if( valid == false )
					return;

				$scope.model_data.picture = $scope.model_data.picture_name;

				if ($scope.model_data.login_pin == '')
				{
					$scope.model_data.login_pin = null;
				}
				var request = angular.copy($scope.model_data);
				request.agent_id = $scope.agent_id;

				$http({
					method: 'PUT', 
					url: '/backoffice/user/wizard/user/' + id, 
					data: request, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.then(function(response) {
					var data = response.data;
					if( data.code == 200 ) {						
						refreshCurrentPage();					
						$('#addModal').modal('hide');
					}
					else {	
						if (data.code == 401){
							alert("Duplicate PIN. Please use a different PIN.");
						}	else		
						alert(data.message);	
						
					}
				});
			}
			else			
			{

				
				if ($scope.model_data.login_pin == '')
				{
					$scope.model_data.login_pin = null;
				}
				// console.log($scope.model_data);
				// return;
				var request = angular.copy($scope.model_data);
				request.agent_id = $scope.agent_id;

				$http({
					method: 'POST', 
					url: '/backoffice/user/wizard/user', 
					data: request, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.then(function(response) {
					var data = response.data;
					if( data.code == 200 ) {
						
						$('#addModal').modal('hide');
						$scope.grid.fnPageChange('first');
						
					}
					else {
						if(data.code == 400) {
							alert("Username already exists. Please use a different username.");
						} 
						else if (data.code == 401){
							alert("Duplicate PIN. Please use a different PIN.");
						}
						else if (data.code == 402){
							alert("Duplicate IVR Code. Please use a different IVR Code." + data.message);
						}
						else {
							alert(data.message);				
						}
					}
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
					url: '/backoffice/user/wizard/user/' + id 								
				})
				.success(function(data, status, headers, config) {
					refreshCurrentPage();						
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
				});
			}
		}

		//reset password with default password from property_setting
		$scope.onResetRow = function(id)
		{
			var data = loadData(id);
			if (confirm('Are you sure you want to reset ' + data.first_name + ' ' + data.last_name + '\'s password?')) {
				if( id >= 0 )
				{
					$http({
						method: 'GET',
						url: '/backoffice/user/wizard/user/resetpassword/' + id+'-'+$scope.agent_id
					})
						.success(function(data, status, headers, config) {
							if(status == '200') {
								alert('Password changed to default password (' + data.password + ')');
							}
							refreshCurrentPage();
						})
						.error(function(data, status, headers, config) {
							console.log(status);
						});
				}
			}
		}

		function refreshCurrentPage()
		{
			var oSettings = $scope.grid.fnSettings();
			var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
			$scope.grid.fnPageChange(page);
		}

		$scope.onDownloadExcel = function(type) {

			var filter = {};
			filter.excel_type = type;

			//$window.alert($scope.filter.property_id);
			
			$window.location.href = '/backoffice/property/wizard/audituser_excelreport?'  + $httpParamSerializer(filter);
			
			
		}
		

		function loadData(id)
		{
			if( id >= 0 )
			{
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				if (data.casual_staff == 'Y')
					$scope.casual_staff = true;
				else
					$scope.casual_staff = false;
				if(data.lock == 'Yes') data.lock = true;
				else data.lock = false
				$scope.dept_name = data.department + '';
				$scope.job_name = data.job_role + '';	
				$scope.shift_name = data.shift + '';
				$scope.property_id = data.property_id;
				data.employee_id = parseInt(data.employee_id);
				data.employee_id = parseInt(data.access_code);
				data.already_deleted = data.deleted;
				$scope.cpname = data.cpname;
				$scope.cbname = data.cbname;

				delete data.checkbox;
				delete data.language;
				delete data.property_id;
				delete data.cpname;
				delete data.cbname;
				delete data.edit;
				delete data.delete;
				delete data.department;
				delete data.job_role;
				delete data.shift;
				delete data.shift_id;
				delete data.reset;
				delete data.image;

				return data;
			}
			var data = {};
			return data;
		}

		$scope.historylist = {};

		function history(id){
			//get history
			$http({
				method: 'GET',
				url: '/backoffice/user/wizard/user/gethistory/' + id
			})
				.success(function(data, status, headers, config) {
					if(status == '200') {
						$scope.historylist = data;
					}
				})
				.error(function(data, status, headers, config) {
					console.log(status);
				});
		}

		$scope.onSendCredential = function() {
			var data = {};
			data.username = $sessionStorage.admin.currentUser.first_name+" " +$sessionStorage.admin.currentUser.last_name;
			$http({
				method: 'POST',
				url: '/backoffice/user/wizard/user/sendCredential',
				data: data,
				headers: {'Content-Type': 'application/json; charset=utf-8'}
			})
				.success(function(data, status, headers, config) {
					if(status == '200') {
						alert("successfully send credentail message to all user.");
					}
				})
				.error(function(data, status, headers, config) {
					console.log(status);
				});
		}

		$scope.loadFiltersValue = function (value, query) {

			var building_list = $scope.buildings.filter(function (item) {
				return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;					
			});
	
			return building_list.map(function (item) { return item.name; });	
		}
		
	});

	//**image upload
	app.directive('fileDropzone', function() {
			return {
				restrict: 'A',
				scope: {
					file: '=',
					fileName: '='
				},
				link: function(scope, element, attrs) {
					var checkSize,
						isTypeValid,
						processDragOverOrEnter,
						validMimeTypes;

					processDragOverOrEnter = function (event) {
						if (event != null) {
							event.preventDefault();
						}
						event.dataTransfer.effectAllowed = 'copy';
						return false;
					};

					validMimeTypes = attrs.fileDropzone;

					checkSize = function(size) {
						var _ref;
						if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
							return true;
						} else {
							alert("File must be smaller than " + attrs.maxFileSize + " MB");
							return false;
						}
					};

					isTypeValid = function(type) {
						if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
							return true;
						} else {
							alert("Invalid file type.  File must be one of following types " + validMimeTypes);
							return false;
						}
					};

					element.bind('dragover', processDragOverOrEnter);
					element.bind('dragenter', processDragOverOrEnter);

					return element.bind('drop', function(event) {
						var file, name, reader, size, type;
						if (event != null) {
							event.preventDefault();
						}
						reader = new FileReader();
						reader.onload = function(evt) {
							if (checkSize(size) && isTypeValid(type)) {
								return scope.$apply(function() {
									scope.file = evt.target.result;
									if (angular.isString(scope.fileName)) {
										return scope.fileName = name;
									}
								});
							}
						};
						file = event.dataTransfer.files[0];
						name = file.name;
						type = file.type;
						size = file.size;
						reader.readAsDataURL(file);
						return false;
					});
				}
			};
	})
	.directive("fileread", [function () {
		return {
			scope: {
				fileread: "=",
				imagename: "=",
				imagetype: "="
			},
			link: function (scope, element, attributes) {
				element.bind("change", function (changeEvent) {
					var reader = new FileReader();
					reader.onload = function (loadEvent) {
						scope.$apply(function () {
							scope.fileread = loadEvent.target.result;
						});
					}
					scope.imagename = changeEvent.target.files[0].name;
					scope.imagetype = changeEvent.target.files[0].type;
					reader.readAsDataURL(changeEvent.target.files[0]);
				});
			}
		}
	}]);
	//**image uplaod**/
});

