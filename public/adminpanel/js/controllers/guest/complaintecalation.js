define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ComplaintescalationCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            console.log("UsergroupCtrl reporting for duty.");

            $scope.model_data = {};

    		var job_role_list = [];            
            $http.get('/list/jobrole').success( function(response) {
                job_role_list = response;
            });

            $scope.notify_type_list = ['Email', 'SMS'];

            var g_selected_id = 0;
		    $scope.selected_level_list = [];
        
            $timeout( initDomData, 0, false );

            $scope.grid = {};
            $scope.idkey = [];

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
    		$scope.fields = ['ID', 'Status', 'Max Time', 'Levels', 'Job Roles', 'Max times', 'Notify Types'];

            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/guestservice/wizard/complaintescalation',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        { data: 'id', name: 'id' },
                        { data: 'status', name: 'status' },
                        { data: 'max_time', name: 'max_time' },
                        { data: 'levels', name: 'levels', orderable: false, searchable: false },					
                        { data: 'job_roles', name: 'job_roles', orderable: false, searchable: false },					
                        { data: 'maxtimes', name: 'maxtimes', orderable: false, searchable: false },					
                        { data: 'notify_types', name: 'notify_types', orderable: false, searchable: false },		
                        { data: 'edit', width: '40px', orderable: false, searchable: false},
                    ],
                    "createdRow": function( row, data, dataIndex ) {
                        $compile(row)($scope);
                        $scope.idkey[data.id] = dataIndex;

                        if( g_selected_id > 0 )	// already selected
                        {
                            if(  data.id == g_selected_id )
                            {
                                $(row).attr('class', 'selected');	
                                showLevelList();
                                g_selected_id = 0;							
                            }
                        }
                        else 
                        {
                            if ( dataIndex == 0 )	// select first
                            {					
                                $(row).attr('class', 'selected');	
                                $scope.selected_id = data.id;		
                                showLevelList();	
                            }
                        }
                    }
                });

                $scope.grid = $grid;

                $grid.on( 'click', 'tr', function () {
                    if ( $(this).hasClass('selected') ) {
                        // $(this).removeClass('selected');
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
                    showLevelList();
                } );	

    			$('.dataTables_wrapper  > div:nth-child(2)').css('height', '350px');
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
                    
                }
            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/guestservice/wizard/complaintescalation/' + id,
                        data: $scope.model_data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if( data ) {
                                refreshCurrentRow();                                
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
                        url: '/backoffice/guestservice/wizard/complaintescalation',
                        data: $scope.model_data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if(data.code == '400') {
                                alert("Job Role with Tyep and Property can not duplicate.");
                            }else if( data ) {
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

                    return data;
                }
                var data = {};
                return data;
            }

            function showLevelList()
            {	
                var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[$scope.selected_id]));
                
                $http({
                    method: 'POST', 
                    url: "/backoffice/guestservice/wizard/complaintescalation/selectitem",
                    data: data, 
                    headers: {'Content-Type': 'application/json; charset=utf-8'} 
                })
                .success(function(data, status, headers, config) {
                    if( data ) {
                        $scope.selected_level_list = data;		
                        
                        addNewLevel();			
                    }
                    else {
                        
                    }
                })
                .error(function(data, status, headers, config) {				
                    console.log(status);
                });
            }

            function addNewLevel() 
            {
                var row = {};
                row.id = 0;
                row.job_role_list = [];
                var data = jQuery.extend({}, $scope.grid.fnGetData($scope.idkey[$scope.selected_id]));
                row.status = data.status;
                row.level = 1;
                row.notify_type_list = ['Email']
                if( $scope.selected_level_list.length > 0 )
                    row.level = $scope.selected_level_list[$scope.selected_level_list.length - 1].level + 1;
                
                row.max_time = 600;

                $scope.selected_level_list.push(row);
            }

            $scope.onAddLevel = function(row) {
                if( row.job_role_list.length < 1 || row.notify_type_list.length < 1 || row.max_time < 1 )
                    return;

                updateEscalationInfo(row);
            }

            $scope.onDeleteLevel = function(row) {
                $http({
                    method: 'POST', 
                    url: "/backoffice/guestservice/wizard/complaintescalation/deleteinfo",
                    data: row, 
                    headers: {'Content-Type': 'application/json; charset=utf-8',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} 
                })
                .success(function(data, status, headers, config) {
                    refreshCurrentRow();
                })
                .error(function(data, status, headers, config) {				
                    console.log(status);
                });  

            }

            function refreshCurrentRow() {
                g_selected_id = $scope.selected_id;
                refreshCurrentPage();		
            }

            function updateEscalationInfo(row) {
                if( row.job_role_list.length < 1 || row.notify_type_list.length < 1 || row.max_time < 1 )
                    return;

                row.job_role_ids = row.job_role_list.map(item => item.id).join(',');

                row.notify_type = row.notify_type_list.map(name => {				
                    return name['text'];
                }).join();


                $http({
                    method: 'POST', 
                    url: "/backoffice/guestservice/wizard/complaintescalation/updateinfo",
                    data: row, 
                    headers: {'Content-Type': 'application/json; charset=utf-8',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} 
                })
                .success(function(data, status, headers, config) {
                    refreshCurrentRow();
                })
                .error(function(data, status, headers, config) {				
                    console.log(status);
                });
            }

            $scope.onJobRoleChanged = function(row)
            {		
                if( row.id < 1 )
                    return;

                updateEscalationInfo(row);			
            }

            $scope.onChangeMaxtime = function(row)
            {		
                if( row.id < 1 )
                    return;

                updateEscalationInfo(row);			
            }

            $scope.onNotifyChanged = function(row)
            {		
                if( row.id < 1 )
                    return;

                updateEscalationInfo(row);			
            }

            function refreshCurrentPage()
            {
                var oSettings = $scope.grid.fnSettings();
                var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
                $scope.grid.fnPageChange(page);
            }

            $scope.loadJobroleFilter = function(query) {                
                return job_role_list.filter(item => item.job_role.toLowerCase().includes(query.toLowerCase()));
            }

        });
    });