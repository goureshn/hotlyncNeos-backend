define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ComplaintdeptpivotCtrl', function ($scope, $compile, $timeout, $http /*$location, $http, initScript */) {
            console.log("UsergroupCtrl reporting for duty.");

            $scope.model_data = {};

            $http.get('/list/department').success( function(response) {
                $scope.departments = response;
            });
            $http.get('/list/complaint').success( function(response) {
                $scope.complaints = response;
            });

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
            $scope.fields = ['ID', 'Complaint', 'Department', 'Max Time'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/guestservice/wizard/complaintdeptpivot',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        //{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'sp.id' },
                        { data: 'complaint', name: 'sc.complaint' },
                        { data: 'department', name: 'cd.department' },
                        { data: 'max_time', name: 'sp.max_time' },
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
                    $scope.model_data.complaint_id = $scope.complaints[0].id;
                    $scope.model_data.dept_id = $scope.departments[0].id;
                    $scope.model_data.max_time = '';
                }
            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/guestservice/wizard/complaintdeptpivot/' + id,
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
                        url: '/backoffice/guestservice/wizard/complaintdeptpivot',
                        data: $scope.model_data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if(data.code == '400') {
                                alert("Complaint can not duplicate.");
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
                        url: '/backoffice/guestservice/wizard/complaintdeptpivot/' + id
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
                    delete data.complaint;
                    delete data.pgname;
                    delete data.department;

                    return data;
                }
                var data = {};
                return data;
            }

        });
    });