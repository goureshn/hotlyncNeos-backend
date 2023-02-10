define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
		function (app) {
	app.controller('EmployeeCtrl', function ($scope, $rootScope, $window, $localStorage,$sessionStorage, $compile, $timeout, $http, FileUploader) {
		console.log("EmployeeCtrl reporting for duty.");
		$scope.property_id = 0;

		$scope.agent_id = $sessionStorage.admin.currentUser.id;
		var client_id = $sessionStorage.admin.currentUser.client_id;
		$scope.model_data = {};

		$scope.menus = [
					{link: '/user', name: 'User'},
					{link: '/user/employee', name: 'Employee'},
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
		$scope.sex_list = [
			'Male', 'Female'
		];		

		$scope.mstatus_list = [
			'MARRIED', 'SINGLE'
		];

		$scope.dbtype_list = [
			'MS SQL', 'Mysql'
		];		

		$http.get('/backend_api/multipropertylist?client_id='+client_id).success( function(response) {
				$scope.properties = response;							
			});		

		$scope.national_list = [];
		$scope.country_list = [];
		$http.get('/list/countrylist')
            .then(function(response){
                $scope.national_list = response.data;
                $scope.country_list = response.data;
            });

		function disabled(data) {
		    var date = data.date,
		    mode = data.mode;
		    return mode === 'day' && (date > new Date());		
		}

		$scope.dateOptions = {
		    // dateDisabled: disabled,
		    formatYear: 'yy',
		    maxDate: new Date(),
		    startingDay: 0
		};

	    function getSyncFlag() {
	    	var request = {};

	    	request.client_id = $sessionStorage.admin.currentUser.client_id;

	    	$http({
					method: 'POST', 
					url: '/backoffice/user/wizard/employee/getsyncsetting', 
					data: request, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.then(function(response) {
					$scope.sync_setting = response.data;		
					$scope.sync_setting.auto_sync_employee	= $scope.sync_setting.auto_sync_employee == "1";	
					$scope.viewclass = $scope.sync_setting.auto_sync_employee;
				});	
	    }

	    getSyncFlag();

		$timeout( initDomData, 0, false );

		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = [	
							'ID',
							'Image',
							'Property',  
							'Department',
							'User Name',
							'Record Type',
							'First Name', 
							'Last Name', 
							'Sex',
							'Married Status',
							'Date of Birth',
							'Nationality',
							'Tel',
							'Mobile',
							// 'Address',
							'Country',
							'Divsn',
							'Sub Department',
							// 'Design',
							'Date of Joining',
							'Passport Number',
							'Passport Expiry',
							'Visa Expiry',
							'Has Done',

						];
		function initDomData() {
			
			
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: {
					url: '/backoffice/user/wizard/employee?client_id='+client_id,
					type: 'GET',
					"beforeSend": function(xhr){
				            xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
				        }
				},
				"lengthMenu": [[10, 15, 25, 50, 100, -1], [10, 15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ce.id'},
					{ data: 'image', width: '40px', orderable: false, searchable: false},
					{ data: 'property', name: 'ce.property' },
					{ data: 'dept', name: 'cd.department' },
					{ data: 'wholename', name: 'cu.wholename' , orderable: false, searchable: false},
					{ data: 'rtype', name: 'ce.rtype' },
					{ data: 'fname', name: 'ce.fname' },
					{ data: 'lname', name: 'ce.lname' },
					{ data: 'sex', name: 'ce.sex' },
					{ data: 'mstatus', name: 'ce.mstatus' },
					{ data: 'dob', name: 'ce.dob' },
					{ data: 'nationality', name: 'ce.nationality' },
					{ data: 'tel', name: 'ce.tel' },
					{ data: 'mobile', name: 'ce.mobile' },
					// { data: 'address', name: 'ce.address' },
					{ data: 'country', name: 'ce.country' },
					{ data: 'divsn', name: 'ce.divsn' },
					{ data: 'sdept', name: 'ce.sdept' },
					// { data: 'design', name: 'ce.design' },
					{ data: 'doj', name: 'ce.doj' },
					{ data: 'psnum', name: 'ce.psnum' },
					{ data: 'psexp', name: 'ce.psexp' },
					{ data: 'vsexp', name: 'ce.vsexp' },
					{ data: 'hasdone', name: 'ce.hasdone' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false},
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
				}
			});	
			
			$scope.grid = $grid;
			var height = ($window.innerHeight - 240) + 'px';

			$('.dataTables_wrapper  > div:nth-child(2)').css('height', height);
		}

		function initUploader()
		{			
			var headers = {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
					'Authorization': $sessionStorage.admin.authdata
				};
			var uploader = $scope.uploader = new FileUploader({
					url: '/backoffice/user/wizard/employee/upload',
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

		initUploader();

		$scope.onChangeProperty = function() {
			$scope.dept_list = [];
			$http.get('/backoffice/user/wizard/departmentlist?property_id=' + $scope.model_data.property_id)
				.then(function(response){
					$scope.dept_list = response.data;
				});

			$scope.staff_list = [];
			$http.get('/frontend/complaint/stafflist?&client_id=' + client_id)
            .then(function(response){
                $scope.staff_list = response.data;
            });   

            for(var i = 0; i < $scope.properties.length; i++)
            {
            	if( $scope.model_data.property_id == $scope.properties[i].id )
            	{
            		$scope.model_data.property = $scope.properties[i].name;
            		break;
            	}
            }
		}

		$scope.onSelectDepartment = function (department, $item, $model, $label) {
			$scope.model_data.dept_id = $item.id;
		};

		$scope.onSelectUser = function (user, $item, $model, $label) {
			$scope.model_data.user_id = $item.id;	
			$scope.model_data.fname = $item.first_name;
			$scope.model_data.lname = $item.last_name;		
			$scope.model_data.dept = $item.department;
			$scope.model_data.dept_id = $item.dept_id;
			$scope.model_data.mobile = $item.mobile;
		};

		
		$scope.onMigrate = function() {
			if (confirm('if migrate, original employee data may be lost, do you want to continue to migrate?')) {
			    var request = {};
				request.client_id = client_id;

				$http({
					method: 'POST', 
					url: '/backoffice/user/wizard/employee/migrate', 
					data: request, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						refreshCurrentPage();				
					}
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
				});
			} else {
			    // Do nothing!
			}
		}
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data = {};

			$scope.model_data.id = id;

			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);				
				
				
			}
			else
			{	
				if( $scope.properties.length > 0 )
				{
					$scope.model_data.property_id = $scope.properties[0].id;	
					$scope.model_data.property = $scope.properties[0].name;
				}

				$scope.model_data.client_id = client_id;
				$scope.model_data.dept_id = 0;	
				$scope.model_data.user_id = 0;						
				$scope.wholename = '';
				$scope.model_data.sex = $scope.sex_list[0];
				$scope.model_data.mstatus = $scope.mstatus_list[0];
				// $scope.model_date.dob = '';
				// $scope.model_date.nationality = '';
				// $scope.model_date.tel = '';
				// $scope.model_data.mobile = '';
				// $scope.model_date.country = '';
				// $scope.model_date.divsn = '';

			}		

			$scope.onChangeProperty();
		}


		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/user/wizard/employee/' + id, 
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
					url: '/backoffice/user/wizard/employee', 
					data: $scope.model_data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					if( data ) {
						$scope.grid.fnPageChange('last');					
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
					url: '/backoffice/user/wizard/employee/' + id 								
				})
				.success(function(data, status, headers, config) {
					refreshCurrentPage();						
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
				});
			}
		}

		$scope.onUpdateSetting = function()
		{
			$scope.viewclass = $scope.sync_setting.auto_sync_employee;

			var request = angular.copy($scope.sync_setting);

			request.client_id = client_id;

			$http({
				method: 'POST', 
				url: '/backoffice/user/wizard/employee/updatesyncsetting', 
				data: request, 
				headers: {'Content-Type': 'application/json; charset=utf-8'} 
			})
			.then(function(response) {
				refreshCurrentPage();
			});
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
				
				data.dob = moment(data.dob).toDate();
				data.doj = moment(data.doj).toDate();
				data.psexp = moment(data.psexp).toDate();
				data.vsexp = moment(data.vsexp).toDate();
				delete data.checkbox;
				delete data.edit;
				delete data.delete;
				delete data.image;

				$scope.wholename = data.wholename;
				
				return data;
			}
			var data = {};
			return data;
		}
		
	});

});

