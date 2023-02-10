//define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive','services/auth'],
//    function (app) {
//        app.controller('CreatejobCtrl', function ($scope, $rootScope, $localStorage, $compile, $timeout, $http, AuthService /*$location, $http, initScript */) {
//            console.log("UsergroupCtrl reporting for duty.");
//					$scope.viewclass = AuthService.isValidModule('bo.users.jobrole.view', AuthService, $rootScope, $localStorage);
//            if($rootScope.globals.currentUser.job_role == "SuperAdmin" ) $scope.viewclass = false;
            
define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
    function (app) {
        app.controller('CreatejobCtrl', function ($scope, $compile, $window, $timeout, $http, $localStorage,$sessionStorage /*$location, $http, initScript */) {
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
            $http.get('/list/property').success( function(response) {
                $scope.properties = response;                
            });
            $http.get('/list/usergrouptype').success( function(response) {
                $scope.levels = response;
            });
            $http.get('/list/prgroup').success( function(response) {
                $scope.perm_groups = response;
            });

            $http.get('/list/department').success( function(response) {
                $scope.departments = response;
            });

            $scope.hskp_role_list = [
                'None',
                'Attendant',
                'Supervisor',
            ];

            $timeout( initDomData, 0, false );

            $scope.grid = {};
            $scope.idkey = [];

            $scope.fields = ['ID', 'Property', 'Job Role', 'Department', 'Permission Group', 'Manager Flag','Cost', 'Housekeeping Role'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based                    
                    ajax: {
                        url: '/backoffice/user/wizard/createjob',
                        type: 'GET',
                        "beforeSend": function(xhr){
                                xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
                            }
                    },
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        //{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'cj.id' },
                        { data: 'property_name', name: 'cp.name'},
                        { data: 'job_role', name: 'cj.job_role' },
                        { data: 'department', name: 'cd.department'},
                        { data: 'pgname', name: 'pg.name'},
                        { data: 'manager_flag', name: 'cj.manager_flag'},
                        { data: 'cost', name: 'cj.cost'},
                        { data: 'hskp_role', name: 'cj.hskp_role' },			
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

            $scope.changeProperty = function(reset_flag)
            {   
                $http.get('/list/deptpermissiongroup?property_id=' + $scope.model_data.property_id)
                    .success( function(response) {
                        $scope.departments = response.departments;
                        $scope.perm_groups = response.perm_groups;

                        var alloption = {id: '0', department : 'Whole Property'};
                        $scope.departments.unshift(alloption);   

                        if( reset_flag )
                        {
                            $scope.model_data.dept_id = $scope.departments[0].id;

                            $scope.model_data.permission_group_id = 0;

                            if( $scope.perm_groups.length > 0 )
                                $scope.model_data.permission_group_id = $scope.perm_groups[0].id;    
                        }
                        
                    }); 
            }

            $scope.onShowEditRow = function(id)
            {
                $scope.model_data.id = id;

                if( id > 0 )	// Update
                {
                    $scope.model_data = loadData(id);
                    $scope.changeProperty(false);
                }
                else
                {
                    $scope.model_data.property_id = $scope.properties[0].id;                    
                    $scope.model_data.job_role = "";
                    $scope.model_data.manager_flag = false;
                    $scope.model_data.cost = "0";
                    $scope.changeProperty(true);
                }
            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                $scope.model_data.manager_flag = $scope.manager_flag ? '1' : '0';

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/user/wizard/createjob/' + id,
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
                        url: '/backoffice/user/wizard/createjob',
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
                        url: '/backoffice/user/wizard/createjob/' + id
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
                
                $window.location.href = '/backoffice/property/wizard/auditjob_excelreport?';
                
                
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
                    delete data.department;
                    delete data.pgname;
                    delete data.property_name;

                    if( data.manager_flag == '1' )
                        $scope.manager_flag = true;
                    else
                        $scope.manager_flag = false;

                    return data;
                }
                var data = {};
                return data;
            }

            function showPropertyList()
            {
                $http.get("/backoffice/user/wizard/createjob/propertylist/" + $scope.selected_id)
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

                var data = {job_role_id: $scope.selected_id, select_id: select_id};

                $http({
                    method: 'POST',
                    url: "/backoffice/user/wizard/createjob/postpropertylist",
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
