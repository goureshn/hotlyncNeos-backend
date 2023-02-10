define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'], 
		function (app) {
	app.controller('SkillgroupCtrl', function ($scope, $compile, $timeout, $http, $window, $interval /*$location, $http, initScript */) {
		console.log("SkillgroupCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
		$scope.menus = [
					{link: '/callcenter', name: 'Call Center'},
					{link: '/callcenter/ivr_call_type', name: 'Channel'},
				];

		$timeout( initDomData, 0, false );

		$scope.grid = {};
		$scope.idkey = [];

		$http.get('/frontend/call/skillgroup').success( function(response) {
			$scope.skillgroup = response;		
		//	var alloption = {id: '0', name : '-- Select Property --'};
		//	$scope.properties.unshift(alloption);		
		});


		//end///
		$scope.fields = ['ID', 'Skill Group', 'Email', 'Duration'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/callcenter/wizard/skillgroup',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'id' },					
					{ data: 'group_name', name: 'group_name' },
					{ data: 'email', name: 'email' },
					{ data: 'duration', name: 'duration' },
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
			if( id > 0 )	// Update
			{
				$scope.model_data = loadData(id);
			}
			else
			{
				$scope.model_data.id = -1;
			//	$scope.model_data.group_name = '';	
				$scope.model_data.skillgroup_id = '0';
				$scope.model_data.email = '';
				$scope.model_data.duration = 0;			
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.skillgroup_id;
			console.log($scope.model_data);
			
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/callcenter/wizard/skillgroup/' + id, 
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
			/*
			else			
			{
				$http({
					method: 'POST', 
					url: '/backoffice/callcenter/wizard/skillgroup', 
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
			*/
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
			var id = $scope.model_data.skillgroup_id;
			
			if( id >= 0 )
			{
				$http({
					method: 'DELETE', 
					url: '/backoffice/callcenter/wizard/skillgroup/' + id 								
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

		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));				
				delete data.checkbox;
			//	delete data.group_name;
			//	delete data.email;
			//	delete data.duration;
				delete data.edit;
				delete data.delete;
				
				return data;
			}
			var data = {};
			return data;
		}
	});
});