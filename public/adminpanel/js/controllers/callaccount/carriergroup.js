define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
		function (app) {
	app.controller('CarriergroupCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {
		console.log("CarriergroupCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/carriergroup', name: 'Carrier Group'},
				];
		
		$timeout( initDomData, 0, false );
		$scope.grid = {};

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
		$scope.fields = ['ID', 'Carrier Group', 'Carrier', 'Call Type'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/carriergroupnggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ccg.id' },
					{ data: 'name', name: 'ccg.name' },
					{ data: 'carrier', name: 'cr.carrier' },
					{ data: 'call_type', name: 'ccg.call_type' },		
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					if ( dataIndex == 0 )
					{
						$(row).attr('class', 'selected');
						$scope.selected_id = data.id;
						showDestinationList();
					}
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/carrier').success( function(response) {
				$scope.carriers = response;			
			});
			
			$http.get('/list/calltype').success( function(response) {
				$scope.calltypes = response;			
			});

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
				showDestinationList();
			} );

			//$('.dataTables_wrapper  > div:nth-child(2)').css('height', '450px');
		}

		$scope.$on('$includeContentLoaded', function(event,url) {
			if( url.indexOf('multimove.html') > -1 )
			{
				$('#search').multiselect({
					search: {
						left: '<input type="text" name="q" class="form-control" placeholder="Add Destination..." />',
						right: '<input type="text" name="q" class="form-control" placeholder="Selected Destination..." />',
					},
					attatch : true
				});
			}
		});

		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			$scope.model_data.carrier_id = $scope.carriers[0].id;
			$scope.model_data.call_type = $scope.calltypes['1'];
			$scope.model_data.name = "";		
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/call/wizard/carriergroup/' + id)
					.success( function(response) {
						console.log(response);
						$scope.model_data = response;										
						//$scope.model_data.dept_id = response.dept_id;
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
					url: '/backoffice/call/wizard/carriergroup/' + id, 
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
					url: '/backoffice/call/wizard/carriergroup', 
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
				$http.get('/backoffice/call/wizard/carriergroup/' + id)
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
					url: '/backoffice/call/wizard/carriergroup/' + id 								
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

		function showDestinationList()
		{
			$http.get("/backoffice/call/wizard/carriergroupdest/list?id=" + $scope.selected_id)
					.success( function(data) {
						if( data ) {
							console.log(data[0]);
							console.log(data[1]);

							var from = $('#search');
							from.empty();

							$.each(data[0], function(index, element) {
								from.append("<option value='"+ element.id +"'>" + element.country + "</option>");
							});

							var to = $('#search_to');
							to.empty();
							var count = 1;
							$.each(data[1], function(index, element) {
								to.append("<option value='"+ element.id +"'>" + element.country + "</option>");
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

			var data = {id: $scope.selected_id, select_id: select_id};

			$http({
				method: 'POST',
				url: "/backoffice/call/wizard/carriergroup/postdestlist",
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