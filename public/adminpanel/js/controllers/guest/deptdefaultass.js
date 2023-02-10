define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('DeptdefaultassCtrl', function ($scope, $compile, $timeout, $http ,$sessionStorage) {


            $scope.model_data = {};


            $http.get('/list/department').success( function(response) {
                $scope.departments = response;
            });

            $http.get('/list/user').success( function(response) {
                $scope.users = response;
            });
            $http.get('/list/usergroup').success( function(response) {
                $scope.usergroups = response;
            });

            var property_id = $sessionStorage.admin.currentUser.property_id;
            var location_list = [];
            $http.get('/list/locationlist?property_id=' + property_id).success( function(response) {
                location_list = response.map(item => {
                    item.location_type_name = item.name + ' - ' + item.type;
                    return item;
                });
                locaton_list 
            });

            var location_type_list = [];
            $http.get('/list/locationtype').success( function(response) {
                location_type_list = response;
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

            $scope.fields = ['Department', 'User', 'User Group', 'Max Time'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/guestservice/wizard/deptdefaultass',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        //{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'department', name: 'cd.department' },
                        { data: 'first_name', name: 'cu.first_name' },
						 { data: 'group_name', name: 'cg.name' },
						 { data: 'max_time', name: 'sa.max_time' },
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
                    $scope.model_data.dept_id = $scope.departments[0].id;
                    $scope.model_data.user_id = $scope.users[0].id;
                    $scope.model_data.user_group = $scope.usergroups[0].id;
					$scope.model_data.location_list = [];
					$scope.model_data.location_type_list = [];
                    
                }
            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                if( id >= 0 )	// Update
                {
                    // $scope.model_data.id = $scope.model_data.dept_id;
                    // delete $scope.model_data.dept_id;
                    delete $scope.model_data.first_name;
                    delete $scope.model_data.last_name;
					delete $scope.model_data.group_name;
                    $http({
                        method: 'PUT',
                        url: '/backoffice/guestservice/wizard/deptdefaultass/' + id,
                        data: $scope.model_data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if(data.code == '400') {
                                alert('Department can not duplicate.');
                            }else if( data ) {
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
                    $scope.model_data.id = $scope.model_data.dept_id;
                    delete $scope.model_data.dept_id;
                    delete $scope.model_data.first_name;
                    delete $scope.model_data.last_name;
					delete $scope.model_data.group_name;
                    $http({
                        method: 'POST',
                        url: '/backoffice/guestservice/wizard/deptdefaultass',
                        data: $scope.model_data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if(data.code == '400') {
                                alert('Department can not duplicate.');
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
                        url: '/backoffice/guestservice/wizard/deptdefaultass/' + id
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
                    delete data.department;
                    delete data.wholename;
					delete data.group_name;
                    data.dept_id = data.id;
                    return data;
                }
                var data = {};
                data.location_list = [];
                data.location_type_list = [];
                return data;
            }

            $scope.loadLocationFilter = function(query) {                
                return location_list.filter(item => item.location_type_name.toLowerCase().includes(query.toLowerCase()));
            }

            $scope.loadLocationTypeFilter = function(query) {                
                return location_type_list.filter(item => item.type.toLowerCase().includes(query.toLowerCase()));
            }

        });
    });