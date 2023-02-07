define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {
	console.log("AlarmCtrl reporting for duty.");
	app.controller('AlarmCtrl', function ($scope, $compile, $timeout, $http, FileUploader /*$location, $http, initScript */) {
		
		$scope.model_data = {};
		
		$scope.menus = [
					{link: '/guest', name: 'Guest Services'},
					{link: '/guest/alarm', name: 'Alarm'},
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
		$scope.fields = ['ID', 'Pref', 'Property', 'Group Name', 'Description', 'Enable'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/alarm',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ag.id' },
					{ data: 'pref', name: 'ag.pref' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'name', name: 'ag.name' },
					{ data: 'description', name: 'ag.description' },
					{ data: 'enable', width: '40px', orderable: false, searchable: false},				
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					if ( dataIndex == 0 )
					{					
						$(row).attr('class', 'selected');	
						$scope.selected_id = data.id;		
						showUserList();	
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
				showUserList();	
			} );
			
			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '450px');			
		}
		
		$scope.$on('$includeContentLoaded', function(event,url) {
			if( url.indexOf('multimove.html') > -1 )
			{
				$('#search').multiselect({
					search: {
						left: '<input type="text" name="q" class="form-control" placeholder="Add User Group..." />',
						right: '<input type="text" name="q" class="form-control" placeholder="Selected User Group..." />',
					},
					attatch : true
				});		
			}
		});

		
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;			
			});
			
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			$scope.model_data.property = $scope.properties[0].id;
			$scope.model_data.name = "";
			$scope.model_data.description = "";
			$scope.model_data.pref = null;
			$scope.model_data.enable = 0;
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/guestservice/wizard/alarm/' + id)
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
					url: '/backoffice/guestservice/wizard/alarm/' + id, 
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
					url: '/backoffice/guestservice/wizard/alarm', 
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
				$http.get('/backoffice/guestservice/wizard/alarm/' + id)
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
					url: '/backoffice/guestservice/wizard/alarm/' + id 								
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
		
		function showUserList()
		{	
			$http.get("/backoffice/guestservice/wizard/alarmgroup/userlist?alarm_id=" + $scope.selected_id)
				.success( function(data) {
					if( data ) {
						console.log(data[0]);
						console.log(data[1]);
						
						var from = $('#search');
						from.empty();

						$.each(data[0], function(index, element) {				
							from.append("<option value='"+ element.id +"'>" + element.wholename + "</option>");
						});
						
						var to = $('#search_to');
						to.empty();
						var count = 1;
						$.each(data[1], function(index, element) {				
							to.append("<option value='"+ element.id +"'>" + element.wholename + "</option>");
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
			
			var data = {alarm_id: $scope.selected_id, select_id: select_id};
			
			$http({
				method: 'POST', 
				url: "/backoffice/guestservice/wizard/alarmgroup/postalarm",
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