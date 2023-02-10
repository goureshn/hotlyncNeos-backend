define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
		function (app) {
	console.log("TasklistCtrl reporting for duty.");
app.controller('TasklistCtrl', function ($scope, $compile, $timeout, $http,$window, FileUploader /*$location, $http, initScript */) {
		$scope.cost_flag = 0;
		$scope.select_flag = "";
		$scope.model_data = {};

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
		$scope.menus = [
					{link: '/guest', name: 'Guest Services'},
					{link: '/guest/tasklist', name: 'Tasks'},
				];

		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/tasklist/upload',
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


		$timeout( initDomData, 0, false );

		$scope.grid = {};
		$scope.typefields = ['None','Amenities'];
		$scope.fields = ['ID', 'Task Group Name', 'Task Name', 'Status', 'Languages'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/tasklist',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'tl.id' },
					{ data: 'tgname', name: 'tg.name' },
					{ data: 'task', name: 'tl.task' },
					{ data: 'chkstatus', name: 'tl.chkstatus' },
					{ data: 'lang', width: '280px', orderable: false, searchable: false },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});

			$scope.grid = $grid;

			$http.get('/list/taskgroup').success( function(response) {
				$scope.taskgroups = response;
			});
			$http.get('/frontend/guestservice/gettaskcategory').success( function(response) {
				$scope.taskcategorys = response;
			});
			$http.get('/list/userlang').success(function (response) {

				$scope.langs = [];
				for (var i = 0; i < response.length; i++) {
					var lang = { id: response[i].id, lang: response[i].language };
					$scope.langs.push(lang);
				}
			});
		}

		$scope.gettaskgroups = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/guestservice/wizard/gettaskgrouplist?taskgroup='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};
		$scope.ontaskgroups = function (department, $item, $model, $label) {
			var taskgroups = {};
			$scope.model_data.taskgroup_id = $item.id;
			$scope.taskgroup_name = $item.name;
		};
		$scope.gettaskcategorys = function(val) {
			if( val == undefined )
				val = "";
			return $http.get('/backoffice/guestservice/wizard/gettaskcategorylist?taskcategory='+val)
				.then(function(response){
					return response.data.map(function(item){
						return item;
					});
				});
		};
		$scope.ontaskcategorys = function (department, $item, $model, $label) {
			var taskcategorys = {};
			$scope.model_data.category_id = $item.id;
			$scope.taskcategory_name = $item.name;
		};
		$scope.onSelectType = function(type){
			if(type == "Amenities"){
				$scope.model_data.type_id = "Amenities";
				$scope.cost_flag = 1;
			}else{
				$scope.model_data.type_id = "None";
				$scope.cost_flag = 0;
			}
		}

		$scope.onShowEditRow = function(id)
		{
			// uploader.clearQueue();

			$scope.model_data.id = id;

            $scope.model_data.taskgroup_id = 0;
            $scope.model_data.category_id = 0;
            if($scope.taskgroups.length > 0)
				$scope.model_data.taskgroup_id = $scope.taskgroups[0].id;
            if($scope.taskcategorys.length > 0)
				$scope.model_data.category_id = $scope.taskcategorys[0].id;

            $scope.model_data.category_id = 0;
			$scope.taskcategory_name =  "";
			$scope.taskgroup_name =  "";
			$scope.model_data.tasklist_name = "";
			$scope.model_data.cost = "0";
			$scope.model_data.status = true;
			$scope.cost_flag = 0;
			$scope.select_flag = "";
			$scope.model_data.type_id = "";
			$scope.model_data.lang=[];
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/tasklist/' + id)
					.success( function(response) {
						console.log(response);

						$scope.model_data.id = response.id;
						$scope.model_data.tasklist_name = response.task;
						$scope.model_data.category_id = response.category_id;
						$scope.model_data.cost = response.cost;

						if(response.lang)
						var lang=(JSON.parse(response.lang));
						// window.alert('Lang'+lang);
						for(i=0;i<$scope.langs.length;i++)
						{

							if(lang)
								$scope.model_data.lang.push({'id':$scope.langs[i].id, 'lang':$scope.langs[i].lang, 'text': lang[i].text });
							else
								$scope.model_data.lang.push({'id':$scope.langs[i].id, 'lang':$scope.langs[i].lang, 'text': '' });
						}
						// window.alert(JSON.stringify($scope.model_data.lang));
						if(response.cost != 0){
							$scope.cost_flag = 1;
							$scope.model_data.type_id = "Amenities";
						}else{
							$scope.cost_flag = 0;
							$scope.model_data.type_id = "None";
						}
					    if(response.status){
							$scope.model_data.status = true;
					    }else{
							$scope.model_data.status = false;
						}
						if( response.taskgroup.length > 0 ) {
							$scope.model_data.taskgroup_id = response.taskgroup[0].id;
							$scope.taskgroup_name = response.taskgroup[0].name;
						}else {
							$scope.model_data.taskgroup_id = 0; // $scope.taskgroups[0].id;
							$scope.taskgroup_name = ""; //response.taskgroups[0].name;
						}



						if (response.category_id == null){
							$scope.taskcategory_name = '';
						}else{
							$http.get('/backoffice/guestservice/wizard/categoryname?category_id=' + response.category_id)
							.then( function(response) {
								$scope.taskcategory_name = response.data;
							});
						}

					});
			}
			else
			{
				for(i=0;i<$scope.langs.length;i++)
						{
							$scope.model_data.lang.push({'id':$scope.langs[i].id, 'lang':$scope.langs[i].lang, 'text': '' });
						}
			}
		}

		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			$scope.model_data.lang.forEach(element => {
				delete element.lang;
			});
			// delete $scope.model_data.lang['lang'];
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT',
					url: '/backoffice/guestservice/wizard/tasklist/' + id,
					data: $scope.model_data,
					headers: {'Content-Type': 'application/json; charset=utf-8'}
				})
				.success(function(data, status, headers, config) {
					console.log(data);
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
					url: '/backoffice/guestservice/wizard/task/createlist',
					data: $scope.model_data,
					headers: {'Content-Type': 'application/json; charset=utf-8'}
				})
				.success(function(data, status, headers, config) {
					console.log(data);
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
				$http.get('/backoffice/guestservice/wizard/tasklist/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data.id = response.id;
						$scope.model_data.tasklist_name = response.task;
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
					url: '/backoffice/guestservice/wizard/tasklist/' + id
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


			$window.location.href = '/backoffice/property/wizard/audittask_excelreport?';


		}

	});
});
