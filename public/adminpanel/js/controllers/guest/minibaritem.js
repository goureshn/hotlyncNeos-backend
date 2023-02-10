define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive', 'file-model'], 
		function (app) {

	app.controller('MinibaritemCtrl', function ($rootScope, $scope, $compile, $timeout, $window, $http, FileUploader /*$location, $http, initScript */) {
		console.log("MinibaritemCtrl reporting for duty.");
		
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
					{link: '/guest/minibaritem', name: 'Minibar Item'},
				];
				
		var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
		var uploader = $scope.uploader = new FileUploader({
				url: '/backoffice/guestservice/wizard/minibaritem/upload',
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
		$scope.img_file = null;
		
		$scope.fields = ['ID', 'Item Name', 'Charge', 'PMS Code', 'IVR Code', 'Max Item', 'Thumbnail','Item Status'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/guestservice/wizard/minibaritem',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'rsi.id' },
					{ data: 'item_name', name: 'rsi.item_name' },
					{ data: 'charge', name: 'rsi.charge' },
					{ data: 'pms_code', name: 'rsi.pms_code' },
					{ data: 'ivr_code', name: 'rsi.ivr_code' },
					{ data: 'max_qty', name: 'rsi.max_qty' },
					{ data: 'thumbnail', searchable: false,orderable: false, },
					{ data: 'itemstatus', searchable: false, orderable: false, },					
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
				}
			});		
			
			$scope.grid = $grid;

			//$http.get('/list/roomservicegroup').success( function(response) {
			//	$scope.roomservicegroups = response;
			//});
		}
		
		$scope.onShowEditRow = function(id)
		{	
			// uploader.clearQueue();
			
			$scope.model_data.id = id;
			
			//$scope.model_data.room_service_group = $scope.roomservicegroups[0].id;
			$scope.model_data.item_name = "";
			$scope.model_data.max_qty = 0;
			$scope.model_data.charge = 0;
			$scope.model_data.pms_code = "";
			$scope.model_data.ivr_code = "";
			$scope.img_file = null;

			$scope.myImage='';
		    $scope.myCroppedImage='';
			$scope.model_data.user_id = $rootScope.globals.currentUser.id;

			if( id > 0 )	// Update
			{
				history(id);
				$http.get('/backoffice/guestservice/wizard/minibaritem/' + id)
					.success( function(response) {
						console.log(response);					
						if( response != "" )
							$scope.model_data = response;
						var path = $scope.model_data.img_path;
						if( path != null || path != '') {
							var request = {};
							request.path = path;
							var blob = null;
							var xhr = new XMLHttpRequest();
							xhr.open("GET", path);
							xhr.responseType = "blob";//force the HTTP response, response-type header to be blob
							xhr.onload = function()
							{
								$scope.img_file = xhr.response;//xhr.response is now a blob object

							}
							xhr.send();
						}
					});		
			}
			else
			{
				
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			
			var fd = new FormData();
            fd.append('myfile', $scope.img_file);
        
            $http.post('/uploadpicture', fd, {
              transformRequest: angular.identity,
              headers: {'Content-Type': undefined}
            })
            .success(function(response){
            	console.log(response);
            	postData(id, "/" + response.content);
            })        
            .error(function(data, status, headers, config){
            });			
		}	

		function postData(id, img_path) {
			var request = angular.copy($scope.model_data);
			request.img_path = img_path;

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/guestservice/wizard/minibaritem/' + id, 
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
					url: '/backoffice/guestservice/wizard/minibaritem', 
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
				$http.get('/backoffice/guestservice/wizard/minibaritem/' + id)
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
					url: '/backoffice/guestservice/wizard/minibaritem/' + id 								
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
			
			$window.location.href = '/backoffice/property/wizard/auditminibar_excelreport?';
			
			
		}

		$scope.historylist = {};

		function history(id){
			//get history
			$http({
				method: 'GET',
				url: '/backoffice/guestservice/wizard/minibaritem/gethistory/' + id
			})
				.success(function(data, status, headers, config) {
					if(status == '200') {
						$scope.historylist = data.datalist;
					}
				})
				.error(function(data, status, headers, config) {
					console.log(status);
				});
		}
		
	});
});	