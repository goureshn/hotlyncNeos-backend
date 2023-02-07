define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ShiftCtrl', function ($scope, $compile, $timeout, $http, $localStorage,$sessionStorage) {
            console.log("ShiftCtrl reporting for duty.");

            $scope.model_data = {};

            $scope.menus = [
                {link: '/user', name: 'User'},
                {link: '/user/shift', name: 'Shift'},
            ];

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;                
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
            var profile = $sessionStorage.admin.currentUser;

            $timeout( initDomData, 0, false );

            $scope.grid = {};
            $scope.idkey = [];

            $scope.fields = ['ID', 'Property', 'Name', 'Start Time', 'End Time'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based                    
                    ajax: {
                    url: '/backoffice/user/wizard/shift',
                        type: 'GET',    
                        "beforeSend": function(xhr){
                                xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
                            }
                    },
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                       // { data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'sh.id' },
                        { data: 'property', name: 'cp.property' },
                        { data: 'name', name: 'sh.name' },
                        { data: 'start_time', name: 'sh.start_time' },
                        { data: 'end_time', name: 'sh.end_time' },
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
                    $scope.model_data.property_id = $scope.properties[0].id;
                    $scope.model_data.name = '';
                    $scope.model_data.start_time = '08:00';   
                    $scope.model_data.end_time = '16:00';   
                }
            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                if( $scope.model_data.start_time > $scope.model_data.end_time )
                {
                    alert("End time must be less than Start Time");
                    return;
                }

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/user/wizard/shift/' + id,
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
                        url: '/backoffice/user/wizard/shift',
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
                        url: '/backoffice/user/wizard/shift/' + id
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
                    
                    return data;
                }
                var data = {};
                return data;
            }

        });
    });