define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('PmModuleCtrl', function ($scope, $compile, $timeout, $http, $localStorage,$sessionStorage /*$location, $http, initScript */) {
            console.log("PmModule reporting for duty.");

            $scope.model_data = {};

            // $scope.menus = [
            //     {link: '/user/user', name: 'User'},
            //     {link: '/user/permission', name: 'Permission'},
            // ];
            //
            //
            // $http.get('/list/prgroup').success( function(response) {
            //     $scope.perm_groups = response;
            // });

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
            $http.get('/list/module').success( function(response) {
                $scope.modules = response;
                var module_data= {
                    id: 10000,
                    name: 'All Module',
                    description: 'All permission mdoule'
                };
                $scope.modules.push(module_data);
            });

            $timeout( initDomData, 0, false );

            $scope.grid = {};
            $scope.idkey = [];

            $scope.fields = ['ID', 'Page Route Name', 'Description', 'Module Name'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: {
                        url: '/backoffice/user/wizard/pmmodule',
                        type: 'GET',
                        "beforeSend": function(xhr){
                            xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
                        }
                    },
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        //{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'cpr.id' },
                        { data: 'name', name: 'cpr.name' },
                        { data: 'description', name: 'cpr.description' },
                        { data: 'mname', name: 'cm.name' },
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
                    $scope.model_data.perm_group_id = $scope.perm_groups[0].id;
                    $scope.model_data.name = "";
                }
            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/user/wizard/pmmodule/' + id,
                        data: $scope.model_data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if( data ) {
                                //alert("Updated Successfully");
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
                        url: '/backoffice/user/wizard/pmmodule',
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
                        url: '/backoffice/user/wizard/pmmodule/' + id
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
                    delete data.mname;

                    return data;
                }
                var data = {};
                return data;
            }

        });
    });