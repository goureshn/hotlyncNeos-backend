define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('AlarmCtrl', function ($scope, $compile, $timeout, $http, interface /*$location, $http, initScript */) {
		console.log("AlarmCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.protocol_data = {};
		
		$scope.menus = [
					{link: '/property', name: 'Interface'},
					{link: '/property/building', name: 'Alarm'},
				];

		$http.get('/list/property').success( function(response) {
			$scope.properties = response;
		});

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
		$timeout( initDomData, 0, false );
		
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Property', 'Email', 'SMTP Server', 'SMTP Port', 'Auth', 'SSL', 'SMS Host', 'SMS Username', 'SMS Password', 'SMS From'];
		
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/interface/alarm',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},					
					{ data: 'id', name: 'id' },
					{ data: 'property', name: 'property', orderable: false, searchable: false },
					{ data: 'email', name: 'email' },
					{ data: 'smtp_server', name: 'smtp_server' },
					{ data: 'smtp_port', name: 'smtp_port' },
					{ data: 'auth', name: 'auth' },
					{ data: 'ssl', name: 'ssl' },
					{ data: 'sms_host', name: 'sms_host' },
					{ data: 'sms_username', name: 'sms_username' },
					{ data: 'sms_password', name: 'sms_password' },
					{ data: 'sms_from', name: 'sms_from' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
				}
			});	
			
			$scope.grid = $grid;			
		}

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
				$scope.model_data.email = "alarms@ennovatech.ae";
				$scope.model_data.password = "EnnovaTech2@16";
				$scope.model_data.smtp_server = "send.one.com";
				$scope.model_data.smtp_port = "465";
				$scope.chk_auth = true;
				$scope.chk_ssl = true;
				$scope.model_data.sms_host = "https://api.infobip.com/sms/1/text/single";
				$scope.model_data.sms_username = "Ennovatech";
				$scope.model_data.sms_password = "123456En";
				$scope.model_data.sms_from = "Ennovatech";
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			
			$scope.model_data.auth = $scope.chk_auth ? 'Yes' : 'No';
			$scope.model_data.ssl = $scope.chk_ssl ? 'true' : 'false';

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/interface/alarm/' + id, 
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
					url: '/interface/alarm', 
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
					url: '/interface/alarm/' + id 								
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
				delete data.delete;
				delete data.property;

				if( data.auth == 'Yes' )
					$scope.chk_auth = true;
				else
					$scope.chk_auth = false;

				if( data.ssl == 'true' )
					$scope.chk_ssl = true;
				else
					$scope.chk_ssl = false;
				
				return data;
			}
			var data = {};
			return data;
		}

	});
});