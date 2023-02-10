define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
	function (app) {
		app.controller('UsergroupCtrl', function ($scope, $compile, $timeout, $http, $localStorage,$sessionStorage /*$location, $http, initScript */)
		{
			console.log("UsergroupCtrl reporting for duty.");


		$scope.model_data = {};
		
		$scope.menus = [
					{link: '/user', name: 'User'},
					{link: '/user/usergroup', name: 'User Group'},
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
			var client_id = $sessionStorage.admin.currentUser.client_id;
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;			
			});
		// $http.get('/list/usergrouptype').success( function(response) {
		// 		$scope.levels = response;			
		// 	});
			$http.get('/list/locationgroups').success(function (response) {
				$scope.loc_grps = response;
				$scope.loc_groups = [];
				for (var i = 0; i < $scope.loc_grps.length; i++) {
					var location = { id: $scope.loc_grps[i].id, label: $scope.loc_grps[i].name };
					$scope.loc_groups.push(location);
				}
			});
			$http.get('/list/vips').success(function (response) {
				$scope.vip = response;
				$scope.vips = [];
				for (var i = 0; i < $scope.vip.length; i++) {
					var guest = { id: $scope.vip[i].id, label: $scope.vip[i].name };
					$scope.vips.push(guest);
				}
			});
			
		$timeout( initDomData, 0, false );
		$scope.loc_group_type = [];
		$scope.vip_type = [];
		$scope.grid = {};
		$scope.idkey = [];
		$scope.group_notify_types = [
			{id: 'email', label: 'email'},
			{id: 'SMS', label: 'SMS'},
			{id: 'Mobile', label: 'Mobile'}
			];
			$scope.fields = ['ID', 'Property', 'Name', 'Location Group', 'VIPS', 'Group Notification', 'Notification Type', 'SMS', 'Email', 'Check-In Notification', 'Check-Out Notification', 'Room Change Notification', 'Complaint Notification', 'Sub-Complaint Notification', 'Roster Based', 'Use For Eng Teams'];
		function initDomData() {

			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: {
					url: '/backoffice/user/wizard/usergroup',
					type: 'GET',	
					"beforeSend": function(xhr){
				            xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
				        }
				},
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ug.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'name', name: 'ug.name' },
					{ data: 'location_group', width: '280px', orderable: false, searchable: false },
					{ data: 'vip', width: '280px', orderable: false, searchable: false },
					{ data: 'group_notification', name: 'ug.group_notification' },
					{ data: 'group_notification_type', name: 'ug.group_notification_type' },
					{ data: 'sms', name: 'ug.sms' },
					{ data: 'email', name: 'ug.email' },
					{ data: 'check_in_notify', name: 'ug.check_in_notify' },
					{ data: 'check_out_notify', name: 'ug.check_out_notify' },
					{ data: 'room_change', name: 'ug.room_change' },
					{ data: 'complaint_notify', name: 'ug.complaint_notify' },
					{ data: 'subcomplaint_notify', name: 'ug.subcomplaint_notify' },
					{ data: 'roster_notify', name: 'ug.roster_notify' },
					{ data: 'use_for_eng_teams', name: 'ug.use_for_eng_teams' },
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

			$scope.loc_groups_hint = { buttonDefaultText: 'Select Location Group' };
			$scope.loc_groups_hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};
			$scope.vip_hint = { buttonDefaultText: 'Select VIP Codes' };
			$scope.vip_hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};
			$scope.group_hint = { buttonDefaultText: 'Select Group Notification Type' };
			$scope.group_hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};

			
		
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
				var location_group = $scope.model_data.location_group.split(',');
				//var usergroup_ids = [];
				$scope.loc_group_type = [];
				for (var i = 0; i < location_group.length; i++) {
					for (var j = 0; j < $scope.loc_groups.length; j++) {
						if (location_group[i] == $scope.loc_groups[j].label) {
							var val = { id: $scope.loc_groups[j].id };
							$scope.loc_group_type.push(val);
						}
					}
				}
				var vips = $scope.model_data.vip.split(',');
				//var usergroup_ids = [];
				$scope.vip_type = [];
				for (var i = 0; i < vips.length; i++) {
					for (var j = 0; j < $scope.vips.length; j++) {
						if (vips[i] == $scope.vips[j].label) {
							var val = { id: $scope.vips[j].id };
							$scope.vip_type.push(val);
						}
					}
				}

				var group_notification_type = $scope.model_data.group_notification_type.split(",");
				var tempNoticationTypes = [];

				for (let i = 0; i < group_notification_type.length; i++) {
					let tempObj = {
						id: group_notification_type[i],
						label: group_notification_type[i]
					};

					tempNoticationTypes.push(tempObj);
				}

				$scope.model_data.group_notification_type = tempNoticationTypes;

			}
			else
			{
				$scope.model_data.property_id = $scope.properties[0].id;
				//$scope.model_data.access_level = $scope.levels['1'];
				$scope.model_data.location_group = $scope.loc_grps[0].id;
				$scope.model_data.vip = $scope.vip[0].id;
				$scope.model_data.name = "";
				$scope.model_data.sms = "";
				$scope.model_data.email = "";
				$scope.group_notify_flag = true;
				$scope.check_in_flag = false;
				$scope.check_out_flag = false;
				$scope.room_change_flag = false;
				$scope.complaint_notify_flag = false;
				$scope.subcomplaint_notify_flag = false;
				$scope.roster_flag = false;
				$scope.use_for_eng_teams = false;
				$scope.model_data.group_notification_type = [];
				//$scope.model_data.loc_group_type = [];			
				//$scope.model_data.vip_type = [];	
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			
			$scope.model_data.group_notification = $scope.group_notify_flag ? 'Y' : 'N';
			$scope.model_data.check_in_notify = $scope.check_in_flag  ? 'Y' : 'N';
			$scope.model_data.check_out_notify = $scope.check_out_flag  ? 'Y' : 'N';
			$scope.model_data.room_change = $scope.room_change_flag ? 'Y' : 'N';
			$scope.model_data.complaint_notify = $scope.complaint_notify_flag ? 'Y' : 'N';
			$scope.model_data.subcomplaint_notify = $scope.subcomplaint_notify_flag ? 'Y' : 'N';
			$scope.model_data.roster_notify = $scope.roster_flag ? 'Y' : 'N';
			$scope.model_data.use_for_eng_teams = $scope.use_for_eng_teams ? 'Y' : 'N';
			// $scope.model_data.group_notification_type = $scope.group_notify_type ? 'SMS' : 'email';
			if ($scope.loc_group_type.length > 0)
			$scope.loc_ids = $scope.loc_group_type[0].id;

			for (var i = 1; i < $scope.loc_group_type.length; i++)
				$scope.loc_ids = $scope.loc_ids +','+$scope.loc_group_type[i].id;

			$scope.model_data.location_group = $scope.loc_ids;	

			if ($scope.vip_type.length > 0)
			$scope.vip_ids = $scope.vip_type[0].id;	

			for (var i = 1; i < $scope.vip_type.length; i++)
				$scope.vip_ids = $scope.vip_ids + ',' +$scope.vip_type[i].id;

			let notificationType = "";
			for (let i = 0; i < $scope.model_data.group_notification_type.length; i++) {
				if (notificationType !== "") {
					notificationType += ",";
				}
				notificationType += $scope.model_data.group_notification_type[i].id;
			}

			var request = $scope.model_data;
			request.group_notification_type = notificationType;

			$scope.model_data.vip = $scope.vip_ids;
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/user/wizard/usergroup/' + id, 
					data: request,
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
					url: '/backoffice/user/wizard/usergroup', 
					data: request,
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
					url: '/backoffice/user/wizard/usergroup/' + id 								
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
				delete data.delete;
				delete data.cpname;
				delete data.lgname;
				delete data.vipname;
				
				if( data.group_notification == 'Y' )
					$scope.group_notify_flag = true;
				else
					$scope.group_notify_flag = false;

				if (data.check_in_notify == 'Y')
					$scope.check_in_flag = true;
				else
					$scope.check_in_flag = false;

				if (data.check_out_notify == 'Y')
					$scope.check_out_flag = true;
				else
					$scope.check_out_flag = false;
				if (data.room_change == 'Y')
					$scope.room_change_flag = true;
				else
					$scope.room_change_flag = false;
				if (data.complaint_notify == 'Y')
					$scope.complaint_notify_flag = true;
				else
					$scope.complaint_notify_flag = false;

				if (data.subcomplaint_notify == 'Y')
					$scope.subcomplaint_notify_flag = true;
				else
					$scope.subcomplaint_notify_flag = false;	

				if( data.group_notification_type == 'SMS' )
					$scope.group_notify_type = true;
				else
					$scope.group_notify_type = false;

				if (data.roster_notify == 'Y')
					$scope.roster_flag = true;
				else
					$scope.roster_flag = false;

				if (data.use_for_eng_teams == 'Y')
					$scope.use_for_eng_teams = true;
				else
					$scope.use_for_eng_teams = false;
				
				return data;
			}
			var data = {};
			return data;
		}
		
	});
});
