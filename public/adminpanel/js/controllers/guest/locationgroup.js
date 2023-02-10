define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
		function (app) {
	app.controller('LocationgroupCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		console.log("LocationgroupCtrl reporting for duty.");

		$scope.model_data = {};

		$scope.menus = [
					{link: '/guest', name: 'Guest Services'},
					{link: '/guest/locationgroup', name: 'Location Groups'},
				];

		$scope.locationgroups = null;

		var permission = $scope.globals.currentUser.permission;
		$scope.edit_flag = 0;
		for(var i = 0; i < permission.length; i++)
		{
			if( permission[i].name == "access.superadmin" ) {
				$scope.edit_flag = 1;
				break;
			}
		}
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/location/upload',
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

		$scope.fields = ['ID', 'Client', 'Location Group Name', 'Description'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/location',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'lg.id' },
					{ data: 'ccname', name: 'cc.name' },
					{ data: 'name', name: 'lg.name' },
					{ data: 'description', name: 'lg.description' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					if ( dataIndex == 0 )
					{
						$(row).attr('class', 'selected');
						$scope.selected_id = data.id;
						showLocationList();
					}
				}
			});

			$scope.grid = $grid;

			var detailRows = [];
			$grid.on( 'click', 'tr', function () {
				if ( $(this).hasClass('selected') ) {
					$(this).removeClass('selected');
				}
				else {
					$scope.grid.$('tr.selected').removeClass('selected');
					$(this).addClass('selected');
				}

				 /* Get the position of the current data from the node */
				var aPos = $scope.grid.fnGetPosition( this );

				/* Get the data array for this row */
				var aData = $scope.grid.fnGetData( aPos );

				$scope.selected_id = aData.id;
				showLocationList();
			} );

			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '400px');
		}

		$scope.$on('$includeContentLoaded', function(event,url) {
			if( url.indexOf('multimove.html') > -1 )
			{
				$('#search').multiselect({
					search: {
						left: '<input type="text" name="q" class="form-control" placeholder="Add Location..." />',
						right: '<input type="text" name="q" class="form-control" placeholder="Selected Location..." />',
					},
					attatch : true
				});
			}
		});

		$http.get('/list/client').success( function(response) {
				$scope.clients = response;
			});


		$scope.onShowEditRow = function(id)
		{
			$scope.model_data.id = id;

			$scope.model_data.client_id = $scope.clients[0].id;
			$scope.model_data.name = "";
			$scope.model_data.description = "";

			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/location/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;
					});
			}
			else
			{

			}
		}

		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT',
					url: '/backoffice/guestservice/wizard/location/' + id,
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
					url: '/backoffice/guestservice/wizard/location',
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
				$http.get('/backoffice/guestservice/wizard/location/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;
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
					url: '/backoffice/guestservice/wizard/location/' + id
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

		function showLocationList()
		{
			if( $scope.locationgroups != null )
			{
				$scope.locationtype = $scope.locationgroups[0].id;
				$scope.changeLocationType();

				return;
			}

			$http.get('/list/locationgroup').success( function(response) {
				$scope.locationgroups = response;
				showLocationList();
			});
		}

		$scope.changeLocationType = function()
		{
			console.log($scope.locationtype);

			var type_id = $scope.locationtype;

			var data = {ltgroup_id: $scope.selected_id, type_id: type_id};
			$http({
				method: 'POST',
				url: "/backoffice/guestservice/wizard/location/list",
				data: data,
				headers: {'Content-Type': 'application/json; charset=utf-8'}
			})
			.success(function(data, status, headers, config) {
				if( data ) {
					console.log(data[0]);
					console.log(data[1]);

					var from = $('#search');
					from.empty();

					$.each(data[0], function(index, element) {
						from.append("<option value='"+ element.id +"'>" + element.name + "</option>");
					});

					var to = $('#search_to');
					to.empty();
					var count = 1;
					$.each(data[1], function(index, element) {
						to.append("<option value='"+ element.id +"'>" + element.name + "</option>");
						count++;
					});
				}
				else {

				}
			})
			.error(function(data, status, headers, config) {
				console.log(status);
			});
		}

		$scope.onSubmitSelect = function() {
			var ltgroup_id = $scope.selected_id;
			var type_id = $scope.locationtype;

			var select_id = new Object();
			var count = 0;
			$("#search_to option").each(function()
			{
				select_id[count] = $(this).val();
				count++;
			});

			var data = {ltgroup_id: ltgroup_id, type_id: type_id, select_id: select_id};

			$http({
				method: 'POST',
				url: "/backoffice/guestservice/wizard/location/postlocation",
				data: data,
				headers: {'Content-Type': 'application/json; charset=utf-8',
						'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
			})
			.success(function(data, status, headers, config) {
				if( data ) {
					alert(data);
				}
				else {

				}
			})
			.error(function(data, status, headers, config) {
				console.log(status);
			});
		}

	});
});
