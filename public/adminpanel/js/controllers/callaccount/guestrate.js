define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('GuestrateCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {
		console.log("GuestrateCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/call', name: 'Call Accounting'},
					{link: '/call/guestrate', name: 'Guest Rate Mapping'},
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
		$timeout( initDomData, 0, false );
		$scope.grid = {};
		$scope.vip_type = [];
		$scope.room_type = [];
		$scope.fields = ['ID', 'Carrier Group', 'Rate Map Name', 'Carrier Charge', 'Hotel Charge', 'Tax', 'Allowance','Time Slab','Room Types','VIP Statuses'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/call/wizard/guestratenggrid/get',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'ccm.id' },
					{ data: 'cgname', name: 'cg.name' },
					{ data: 'name', name: 'ccm.name' },
					{ data: 'ccname', name: 'cc.charge' },
					{ data: 'hcname', name: 'hc.name' },
					{ data: 'taxname', name: 'tax.name' },
					{ data: 'caname', name: 'ca.Name' },
					{ data: 'tsname', name: 'ts.name' },
					{ data: 'roomtype', width: '280px', orderable: false, searchable: false },
					{ data: 'vip', width: '280px', orderable: false, searchable: false },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;
			
			$http.get('/list/carriergroup').success( function(response) {
				$scope.carriergroups = response;			
			});		
			$http.get('/list/carriercharge').success( function(response) {
				$scope.carriercharges = response;			
			});	
			$http.get('/list/allowance').success( function(response) {
				$scope.allowances = response;			
			});	
			$http.get('/list/timeslab').success( function(response) {
				$scope.timeslabs = response;			
			});	
			$http.get('/list/hotelcharge').success( function(response) {
				$scope.propertycharges = response;			
			});	
			$http.get('/list/tax').success( function(response) {
				$scope.taxes = response;			
			});
			$http.get('/list/vips').success(function (response) {
				$scope.vip = response;
				$scope.vips = [];
				for (var i = 0; i < $scope.vip.length; i++) {
					var guest = { id: $scope.vip[i].id, label: $scope.vip[i].name };
					$scope.vips.push(guest);
				}
			});
			$http.get('/list/roomtype').success(function (response) {
				$scope.roomtype = response;
				$scope.room_types = [];
				for (var i = 0; i < $scope.roomtype.length; i++) {
					var guest = { id: $scope.roomtype[i].id, label: $scope.roomtype[i].type };
					$scope.room_types.push(guest);
				}
			});	
		}
		$scope.vip_type = [];
		$scope.vip_hint = { buttonDefaultText: 'Select VIP Codes' };
		$scope.vip_hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};
		$scope.room_type = [];
		$scope.room_type_hint = { buttonDefaultText: 'Select Room Type' };
		$scope.room_type_hint_setting = {
				smartButtonMaxItems: 3,
				smartButtonTextConverter: function (itemText, originalItem) {
					return itemText;
				}
			};
		$scope.onShowEditRow = function(id)
		{	
			$scope.model_data.id = id;
			$scope.vip_type = [];
			$scope.room_type = [];
			$scope.model_data.carrier_group_id = $scope.carriergroups[0].id;
			$scope.model_data.carrier_charges = $scope.carriercharges[0].id;
			$scope.model_data.call_allowance = $scope.allowances[0].id;
			$scope.model_data.time_slab = $scope.timeslabs[0].id;
			$scope.model_data.tax = $scope.taxes[0].id;
			$scope.model_data.hotel_charges = $scope.propertycharges[0].id;
			
			$scope.model_data.name = "";		
			
			if( id > 0 )	// Update
			{
				$http.get('/backoffice/call/wizard/guestrate/' + id)
					.success( function(response) {
				
						$scope.model_data = response;	
						
						$scope.room_type = str2array($scope.model_data.room_type_ids);	
						$scope.vip_type = str2array($scope.model_data.vip_ids);								
					});	
		
			}
			else
			{
				$scope.model_data.vip = $scope.vip[0].id;
				$scope.model_data.room_type = $scope.room_type[0].id;
			}		
		}

		function str2array(str) {
			var ids = [];
			if (str) {
				val = str.split(',');
				val.forEach(element => {
					var val = { id: parseInt(element) };
					ids.push(val);
				});
			}

			return ids;
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			
			$scope.model_data.room_type_ids = array2str($scope.room_type);
			$scope.model_data.vip_ids = array2str($scope.vip_type);

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/call/wizard/guestrate/' + id, 
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
					url: '/backoffice/call/wizard/guestrate', 
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
		function array2str(ids) {
			temp = "";
			ids.forEach((element, index) => {
				if (index > 0)
					temp += ",";

				temp += element.id;
			});

			return temp;
		}
		$scope.onDeleteRow = function(id)
		{
			if( id >= 0 )
			{
				$http.get('/backoffice/call/wizard/guestrate/' + id)
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
					url: '/backoffice/call/wizard/guestrate/' + id 								
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
			
			$window.location.href = '/backoffice/property/wizard/auditguestrate_excelreport?';
			
			
		}
		
	});
});	