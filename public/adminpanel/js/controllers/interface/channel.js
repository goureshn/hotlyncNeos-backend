define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
		function (app) {
	app.controller('ChannelCtrl', function ($scope, $compile, $timeout, $http, $interval, interface /*$location, $http, initScript */) {
		console.log("ChannelCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.protocol_data = {};

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
					{link: '/property', name: 'Interface'},
					{link: '/property/building', name: 'Channel'},
				];
						
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;			
			});
		$http.get('/list/building').success( function(response) {
			$scope.buildings = response;

		var all = {};
		all.id = 0;
		all.name = 'All';
		$scope.buildings.unshift(all);

		});
		$http.get('/list/externaltype').success( function(response) {
				$scope.types = response;
			});
		$http.get('/list/commodetype').success( function(response) {
				$scope.comtypes = response;			
			});	
		$http.get('/list/protocol').success( function(response) {
				$scope.protocols = response;
				for(var i = 0; i < response.length; i++ )
				{
					$scope.protocol_data[response[i].id] = response[i];
				}
			});

		$scope.data_end_type_list = [
			'None',
			'stx_etx',
			'line_feed',
			'line_feed_38',			
		];				
		
		$timeout( initDomData, 0, false );
		$interval(checkChannelState, 5000);
		
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Name', 'Property', 'Active', 'Alarm', 'Last Data', 'Duration', 'Log Level'];
		
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/interface/channel',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},					
					{ data: 'id', name: 'cn.id' },
					{ data: 'name', name: 'cn.name' },
					{ data: 'property', name: 'property', orderable: false, searchable: false },
					//{ data: 'type', name: 'type' },
					{ data: 'active', name: 'cn.active' },
					{ data: 'alarm', name: 'cn.alarm' },
					{ data: 'last_data', name: 'last_data', orderable: false, searchable: false  },
					{ data: 'duration', name: 'cn.duration' },
					{ data: 'alarm_level', name: 'cn.alarm_level' },
					{ data: 'view_log', width: '40px', orderable: false, searchable: false},
					{ data: 'conn_status', width: '40px', orderable: false, searchable: false},
					{ data: 'live_data', width: '40px', orderable: false, searchable: false},
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;
					updateLiveState(data);
					if ( dataIndex == 0 )
					{
						$(row).attr('class', 'selected');
						$scope.selected_id = data.id;
						showAcceptBuildingList();
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
				showAcceptBuildingList();
			} );

			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '400px');
		}

		$scope.$on('$includeContentLoaded', function(event,url) {
			if( url.indexOf('multimove.html') > -1 )
			{
				$('#search').multiselect({
					search: {
						left: '<input type="text" name="q" class="form-control" placeholder="Add Accept Building..." />',
						right: '<input type="text" name="q" class="form-control" placeholder="Selected Accept Building..." />',
					},
					attatch : true
				});
			}
		});

		function checkChannelState() {
			var oSettings = $scope.grid.fnSettings();
			if( !oSettings || oSettings._iDisplayStart == undefined )
				return;

			var first = oSettings._iDisplayStart;
			var last = oSettings._iDisplayStart + oSettings._iDisplayLength;
			if( last > oSettings._iRecordsTotal )
				last = oSettings._iRecordsTotal;
			for(var i = first; i < last; i++ )
			{
				var data = $scope.grid.fnGetData(i);
				updateLiveState(data)
			}
		}

		function updateLiveState(channel)
		{
			$http({
				method: 'POST',
				url: interface.api + 'process/checklive',
				data: channel,
				headers: {'Content-Type': 'application/json; charset=utf-8'}
				})
				.success(function(data, status, headers, config) {
					console.log(data);

					var liveid = '#livedata' + data.id;
					var iconid = '#livedata' + data.id + '>span';
					var dotid = '.status' + data.id + '>.dot';
					var pulseid = '.status' + data.id + '>.pulse';
					var lastdataid = '#lastdata' + data.id + '';

					var currentTime = Date.now();
					
					if( data )
					{
						if( data.live == 'live' )
						{
							$(liveid).addClass('btn-danger');
							$(liveid).removeClass('btn-success');
							$(iconid).addClass('fa-stop');
							$(iconid).removeClass('fa-play');
						}
						else
						{
							$(liveid).removeClass('btn-danger');
							$(liveid).addClass('btn-success');
							$(iconid).removeClass('fa-stop');
							$(iconid).addClass('fa-play');
						}

						if( data.live == 'live' )
						{
							if( data.lasttime > 0 && currentTime - data.lasttime < channel.duration * 60 * 1000 )
							{
								$(dotid).css("background-color", "green");
								$(dotid).css("border", "green");
								$(pulseid).css("background-color", "green");
								$(pulseid).css("border", "green");
							}
							else
							{
								$(dotid).css("background-color", "yellow");
								$(dotid).css("border", "yellow");
								$(pulseid).css("background-color", "yellow");
								$(pulseid).css("border", "yellow");
							}
							if( data.lasttime > 0)
							{
								$(lastdataid).text(parseInt((currentTime - data.lasttime) / 1000 / 60) + 'min');
							}
							else
							{
								$(lastdataid).text('No Data');
							}

						}
						else
						{
							$(dotid).css("background-color", "red");
							$(dotid).css("border", "red");
							$(pulseid).css("background-color", "red");
							$(pulseid).css("border", "red");
							$(lastdataid).text('No Data');
						}
					}
				})
				.error(function(data, status, headers, config) {
					console.log(status);
					var liveid = '#livedata' + channel.id;
					var iconid = '#livedata' + channel.id + '>span';
					var dotid = '.status' + channel.id + '>.dot';
					var pulseid = '.status' + channel.id + '>.pulse';
					var lastdataid = '#lastdata' + channel.id + '';

					$(liveid).removeClass('btn-danger');
					$(liveid).addClass('btn-success');
					$(iconid).removeClass('fa-stop');
					$(iconid).addClass('fa-play');

					$(dotid).css("background-color", "red");
					$(dotid).css("border", "red");
					$(pulseid).css("background-color", "red");
					$(pulseid).css("border", "red");
					$(lastdataid).text('No Data');

				});
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
				$scope.model_data.src_build_id = 0;
				$scope.model_data.name = "";
				//$scope.model_data.type = $scope.types[1];
				$scope.chk_active = true;
				$scope.chk_alarm = true;	
				$scope.chk_sanity = true;	
				$scope.model_data.email = "";
				$scope.model_data.mobile = "";
				$scope.model_data.com_mode = $scope.comtypes[1];
				$scope.model_data.data_end = $scope.data_end_type_list[0];
				$scope.model_data.tcpip = "";
				$scope.model_data.tcpport = "";
				$scope.model_data.protocol_id = $scope.protocols[0].id;
				$scope.model_data.duration = 10;
				$scope.model_data.param = '';
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			var id = $scope.model_data.id;
			
			$scope.model_data.active = $scope.chk_active ? 'Yes' : 'No';
			$scope.model_data.alarm = $scope.chk_alarm ? 'Yes' : 'No';
			$scope.model_data.sanity_check = $scope.chk_sanity ? 'Yes' : 'No';

			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/interface/channel/' + id, 
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
					url: '/interface/channel', 
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
					url: '/interface/channel/' + id 								
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
				delete data.cpname;
				delete data.last_data;
				delete data.view_log;
				delete data.conn_status;
				delete data.live_data;
				delete data.cptype;

				if( data.active == 'Yes' )
					$scope.chk_active = true;
				else
					$scope.chk_active = false;

				if( data.alarm == 'Yes' )
					$scope.chk_alarm = true;
				else
					$scope.chk_alarm = false;

				if( data.sanity_check == 'Yes' )
					$scope.chk_sanity = true;
				else
					$scope.chk_sanity = false;
				
				return data;
			}
			var data = {};
			return data;
		}
		
		$scope.onTestConnection = function()
		{
			var data = {};
			data.tcpip = $scope.model_data.tcpip;
			data.tcpport = $scope.model_data.tcpport;
			data.com_mode = $scope.model_data.com_mode;
			data.param = $scope.model_data.param;
			
			$http({
					method: 'POST', 
					url: interface.api + 'process/testconnection',
					data: data, 
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					console.log(data);
					if( data.code == 200 )
						alert('Server is alive');
					else
						alert('Server is die');
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
					alert('Server is die');
				});
		}
		
		$scope.onActiveConnection = function(id)
		{
			var data = loadData(id);
			data.complete_flag = data.data_end == 'None' ? 1 : 0;
			var data1 = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
			var req = jQuery.extend({}, data);
			req.protocol = $scope.protocol_data[data.protocol_id];
			req.property_name = data1.property;
			req.protocol_type = data1.cptype;

			var protocol_type = data1.cptype;
			if( protocol_type == 'HOTLYNC' )
			{
				$http({
					method: 'POST',
					url: '/interfaceprocess/setchannel',
					data: req,
					headers: {'Content-Type': 'application/json; charset=utf-8'}
				})
				.success(function(data, status, headers, config) {
					console.log(data);
				})
				.error(function(data, status, headers, config) {

				});
			}

			$http({
					method: 'POST', 
					url: interface.api + 'process/activeconnection',
					data: req,
					headers: {'Content-Type': 'application/json; charset=utf-8'} 
				})
				.success(function(data, status, headers, config) {
					updateLiveState(data1);
				})
				.error(function(data, status, headers, config) {				
					console.log(status);
					alert('Server is die');
				});
		}

		function showAcceptBuildingList()
		{
			$http.get("/interface/buildlist?id=" + $scope.selected_id)
					.success( function(data) {
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
					});

		}

		$scope.onSubmitSelect = function() {
			var select_id = "";
			var count = 0;
			$("#search_to option").each(function()
			{
				if( count > 0 )
					select_id += ",";
				select_id += $(this).val();
				count++;
			});

			var data = {id: $scope.selected_id, select_id: select_id};

			$http({
				method: 'POST',
				url: "/interface/postbuildlist",
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