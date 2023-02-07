define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'], 
		function (app) {
	app.controller('DepartmentCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {
		console.log("DepartmentCtrl reporting for duty.");
		
		$scope.model_data = {};
		$scope.menus = [
					{link: '/admin', name: 'Admin'},
					{link: '/admin/building', name: 'Department'},
				];
				
		$http.get('/list/property').success( function(response) {
				$scope.properties = response;		
				var alloption = {id: '0', name : '-- Select Property --'};
				$scope.properties.unshift(alloption);		
			});

		$http.get('/list/division').success( function(response) {
			$scope.division_list = response;		
			var alloption = {id: 0, division : '-- Select Division --'};
			$scope.division_list.unshift(alloption);		
		});
		
		$timeout( initDomData, 0, false );

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
		$scope.grid = {};
		$scope.idkey = [];
		
		$scope.fields = ['ID', 'Property','Building', 'Department', 'Short Code', 'Services', 'Description', 'Division'];
		function initDomData() {
			var $grid = $('#table_grid').dataTable( {
				processing: true,
				serverSide: true,
				order: [[ 0, "asc" ]], //column indexes is zero based
				ajax: '/backoffice/admin/wizard/department',
				"lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
				columns: [
					//{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
					{ data: 'id', name: 'cd.id' },
					{ data: 'cpname', name: 'cp.name' },
					{ data: 'cbname', name: 'cb.name' },
					{ data: 'department', name: 'cd.department' },
					{ data: 'short_code', name: 'cd.short_code' },
					{ data: 'services', name: 'cd.services' },
					{ data: 'description', name: 'cd.description' },
					{ data: 'division', name: 'ci.division' },
					{ data: 'edit', width: '40px', orderable: false, searchable: false},
					{ data: 'delete', width: '40px', orderable: false, searchable: false}
				],
				"createdRow": function( row, data, dataIndex ) {
					$compile(row)($scope);
					$scope.idkey[data.id] = dataIndex;

					if ( dataIndex == 0 )
                    {
                        $(row).attr('class', 'selected');
                        $scope.selected_id = data.id;
                        showPropertyList();
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
                showPropertyList();
            } );

            $('.dataTables_wrapper  > div:nth-child(2)').css('height', '300px');
		}

		$scope.$on('$includeContentLoaded', function(event,url) {
                if( url.indexOf('multimove.html') > -1 )
                {
                    $('#search').multiselect({
                        search: {
                            left: '<input type="text" name="q" class="form-control" placeholder="Add Property..." />',
                            right: '<input type="text" name="q" class="form-control" placeholder="Selected Property..." />',
                        },
                        attatch : true
                    });
                }
			});
			
		$scope.changeProperty = function()
		{	
			$http.get('/backoffice/property/wizard/buildlist?property_id=' + $scope.model_data.property_id).success( function(response) {
				$scope.buildings = response;		
				var alloption = {id: '0', name : '-- Select Building --'};
				$scope.buildings.unshift(alloption);
				
				// if( $scope.model_data.id < 1 )
				{
					if( $scope.buildings.length > 0 )
						$scope.model_data.building_id = $scope.buildings[0].id;				
					else
						$scope.model_data.building_id = 0;					
				}
			});	
		}

		$scope.changeProperty();

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
				$scope.model_data.division_id = $scope.division_list[0].id;
				$scope.changeProperty();
				$scope.model_data.department = "";
				$scope.model_data.short_code = "";
				$scope.model_data.services = "N";
				$scope.model_data.description = "";
				$scope.service_flag = false;
			}		
		}
		
		$scope.onUpdateRow = function()
		{
			if( !($scope.model_data.property_id > 0) )
			{
				alert('Please select Property');
				return;
			}

			if( !($scope.model_data.building_id > 0) )
			{
				alert('Please select Building');
				return;
			}

			if( !$scope.model_data.department )
			{
				alert('Please input department');
				return;
			}

			var id = $scope.model_data.id;
			
			$scope.model_data.services = $scope.service_flag ? 'Y' : 'N';
			
			if( id >= 0 )	// Update
			{
				$http({
					method: 'PUT', 
					url: '/backoffice/admin/wizard/department/' + id, 
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
					url: '/backoffice/admin/wizard/department', 
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
					url: '/backoffice/admin/wizard/department/' + id 								
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

		$scope.onDownloadExcel = function() {

			//$window.alert($scope.filter.property_id);
			
			$window.location.href = '/backoffice/property/wizard/auditdpt_excelreport?';
			
			
		}
		
		
		function loadData(id)
		{
			if( id >= 0 )
			{				
				var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[id]));
				$scope.cpname = data.cpname;
				$scope.cbname = data.cbname;
				delete data.checkbox;
				delete data.edit;
				delete data.delete;
				delete data.delete;
				delete data.cpname;
				delete data.cbname;
				delete data.division;
				
				if( data.services == 'Y' )
					$scope.service_flag = true;
				else
					$scope.service_flag = false;

				return data;
			}
			var data = {};
			return data;
		}

		function showPropertyList()
        {
            $http.get("/backoffice/admin/wizard/department/propertylist/" + $scope.selected_id)
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
            var select_id = new Object();
            var count = 0;
            $("#search_to option").each(function()
            {
                select_id[count] = $(this).val();
                count++;
            });

            var data = {dept_id: $scope.selected_id, select_id: select_id};

            $http({
                method: 'POST',
                url: "/backoffice/admin/wizard/department/postpropertylist",
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
