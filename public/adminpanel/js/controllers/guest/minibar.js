define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {

	app.controller('MinibarCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		console.log("MinibarCtrl reporting for duty.");
		
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
					{link: '/guest/minibar', name: 'Minibar'},
				];
				
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/minibar/upload',
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
		
		$scope.fields = ['ID', 'Building', 'Group Name', 'Sales Outlet', 'Room Type'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/minibar',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'rsg.id' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'name', name: 'rsg.name' },
					{ data: 'sales_outlet', name: 'rsg.sales_outlet' },
					{ data: 'room_type', name: 'rt.type' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					if ( dataIndex == 0 )
					{
						$(row).attr('class', 'selected');
						$scope.selected_id = data.id;
						showMinibarGroupList();
					}
				}
			});		
			
			$scope.grid = $grid;

			$http.get('/list/building').success( function(response) {
				$scope.buildings = response;
			});

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
				showMinibarGroupList();
			} );


			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '450px');
		}

		$scope.$on('$includeContentLoaded', function(event,url) {
			if( url.indexOf('multimove.html') > -1 )
			{
				$('#search').multiselect({
					search: {
						left: '<input type="text" name="q" class="form-control" placeholder="Add Minibar Group..." />',
						right: '<input type="text" name="q" class="form-control" placeholder="Selected Minibar Group..." />',
					},
					attatch : true
				});
			}
		});
		
		$scope.onShowEditRow = function(id)
		{	
			// uploader.clearQueue();
			
			$scope.model_data.id = id;
			
			$scope.model_data.building_id = $scope.buildings[0].id;
			$scope.model_data.name = "";
			$scope.model_data.sales_outlet = "";
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/minibar/' + id)
					.success( function(response) {
						console.log(response);					
						if( response != "" ) {
							$scope.model_data = response;
						}
					});		
			}
			else
			{
				$scope.onChangeBuilding($scope.model_data.building_id);
			}		
		}

		$scope.onChangeBuilding = function(build_id) {
			var request = {};
			request.build_id = build_id;

			$http({
				method: 'POST',
				url: '/backoffice/guestservice/wizard/minibar/roomtypelist',
				data: request,
				headers: {'Content-Type': 'application/json; charset=utf-8'}
			})
				.success(function(data, status, headers, config) {
					$scope.room_types = data;
					if( data && data.length > 0 )
						$scope.model_data.room_type_id = data[0].id;
				})
				.error(function(data, status, headers, config) {
					console.log(status);
				});
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/guestservice/wizard/minibar/' + id, 
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
					url: '/backoffice/guestservice/wizard/minibar', 
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
				$http.get('/backoffice/guestservice/wizard/minibar/' + id)
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
					url: '/backoffice/guestservice/wizard/minibar/' + id 								
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

		function showMinibarGroupList()
		{
			$http.get("/backoffice/guestservice/wizard/minibargroup/grouplist?group_id=" + $scope.selected_id)
					.success( function(data) {
						if( data ) {
							console.log(data[0]);
							console.log(data[1]);

							var from = $('#search');
							from.empty();

							$.each(data[0], function(index, element) {
								from.append("<option value='"+ element.id +"'>" + element.item_name + "</option>");
							});

							var to = $('#search_to');
							to.empty();
							var count = 1;
							$.each(data[1], function(index, element) {
								to.append("<option value='"+ element.id +"'>" + element.item_name + "</option>");
								count++;
							});
						}
						else {

						}
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

			var data = {group_id: $scope.selected_id, select_id: select_id};

			$http({
				method: 'POST',
				url: "/backoffice/guestservice/wizard/minibargroup/postgroup",
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