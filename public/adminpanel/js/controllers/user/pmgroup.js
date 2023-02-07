//define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive','services/auth'],
//		function (app) {
//			app.controller('PmgroupCtrl', function ($scope,$rootScope, $localStorage, $compile, $timeout, $http, AuthService /*$location, $http, initScript */) {
//				console.log("PmgroupCtrl reporting for duty.");

//				$scope.viewclass = AuthService.isValidModule('bo.users.permissiongroup.view', AuthService, $rootScope, $localStorage);
//				if($rootScope.globals.currentUser.job_role == "SuperAdmin" ) $scope.viewclass = false;

//				$scope.model_data = {};
define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
		function (app) {
			app.controller('PmgroupCtrl', function ($scope, $window, $compile, $timeout, $http, $localStorage,$sessionStorage /*$location, $http, initScript */) {
				console.log("PmgroupCtrl reporting for duty.");

				$scope.model_data = {};

				$scope.menus = [
					{link: '/user/user', name: 'User'},
					{link: '/user/pmgroup', name: 'Permission Group'},
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
				$http.get('/list/property').success( function(response) {
					$scope.properties = response;
				});

				$http.get('/list/pageroute').success( function(response) {
					$scope.pageroutes = response;
				});

				$timeout( initDomData, 0, false );

				$scope.grid = {};
				$scope.idkey = [];

				$scope.fields = ['ID', 'Property', 'Name', 'Home Page', 'Description'];
				function initDomData() {
					var $grid = $('#table_grid').dataTable( {
						processing: true,
						serverSide: true,
						order: [[ 0, "asc" ]], //column indexes is zero based						
						ajax: {
							url: '/backoffice/user/wizard/pmgroup',
							type: 'GET',
							"beforeSend": function(xhr){
						            xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
						        }
						},
						"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
						columns: [
							//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
							{ data: 'id', name: 'pg.id' },
							{ data: 'cpname', name: 'cp.name' },
							{ data: 'name', name: 'pg.name' },
							{ data: 'prname', name: 'pr.name' },
							{ data: 'prdescription', name: 'pr.description' },
							{ data: 'edit', width: '40px', orderable: false, searchable: false},
							{ data: 'copy', width: '40px', orderable: false, searchable: false},
							{ data: 'delete', width: '40px', orderable: false, searchable: false}
						],
						"createdRow": function( row, data, dataIndex ) {
							$compile(row)($scope);
							$scope.idkey[data.id] = dataIndex;

							if ( dataIndex == 0 )
							{
								$(row).attr('class', 'selected');
								$scope.selected_id = data.id;
								$scope.property_id = data.property_id;
								showPageRouteList();
							}
						}
					});

					$scope.grid = $grid;

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
						$scope.property_id = aData.property_id;
						showPageRouteList();
					} );

					$('.dataTables_wrapper  > div:nth-child(2)').css('height', '400px');
				}

				$scope.$on('$includeContentLoaded', function(event,url) {
					if( url.indexOf('multimove.html') > -1 )
					{
						$('#search').multiselect({
							search: {
								left: '<input type="text" name="q" class="form-control" placeholder="Add Page Route..." />',
								right: '<input type="text" name="q" class="form-control" placeholder="Selected Page Route..." />',
							},
							attatch : true
						});
					}
				});

				$scope.onShowEditRow = function(id)
				{
					$scope.model_data.id = id;

					if( id > 0 )	// Update
					{
						$scope.model_data = loadData(id);
					}
					else
					{
						$scope.model_data.property_id = $scope.properties[0].id;
						$scope.model_data.home_route_id = 0;
						$scope.model_data.name = "";
						$scope.model_data.description = "";
					}
				}

				$scope.onShowCopyRow = function(id)
				{
					$scope.model_data.id = id;
					//$window.alert($scope.model_data.id);

						$scope.model_data.property_id = $scope.properties[0].id;
						$scope.model_data.home_route_id = 0;
						$scope.model_data.name = "";
						$scope.model_data.description = "";
					
				}


				$scope.onUpdateRow = function()
				{
					var id = $scope.model_data.id;
					

					if( id >= 0 )	// Update
					{
						$http({
							method: 'PUT',
							url: '/backoffice/user/wizard/pmgroup/' + id,
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
							url: '/backoffice/user/wizard/pmgroup',
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

				$scope.onCopyRow = function()
				{
					var id = $scope.model_data.id;

				
						$http({
							method: 'POST',
							url: '/backoffice/user/wizard/pmgroup/copy',
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
							url: '/backoffice/user/wizard/pmgroup/' + id
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
						delete data.copy;
						delete data.delete;
						delete data.delete;
						delete data.cpname;						

						return data;
					}
					var data = {};
					return data;
				}

				$scope.onHomeRouteSelect = function(items, $item, $model, $label) {
					$scope.model_data.home_route_id = $item.id;
				}

				function showPageRouteList()
				{
					var data = {id: $scope.selected_id, property_id: $scope.property_id};
					$http({
						method: 'POST',
						url: "/backoffice/user/wizard/pmgroup/pagelist",
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
					var select_id = new Object();
					var count = 0;
					$("#search_to option").each(function()
					{
						select_id[count] = $(this).val();
						count++;
					});

					var data = {pmgroup_id: $scope.selected_id, select_id: select_id};

					$http({
						method: 'POST',
						url: "/backoffice/user/wizard/pmgroup/postpagelist",
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