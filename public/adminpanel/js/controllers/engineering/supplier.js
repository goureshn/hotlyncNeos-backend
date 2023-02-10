define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.factory(
            "initScript",
            function( $compile, $timeout ) {
                // Return the public API.
                return({
                    load: load
                });
                // ---
                // PUBLIC METHODS.
                // ---
                // I load the 3rd-party script tag.
                function load() {
                    // Apply the script inject in the next tick of the event loop. This
                    // will give AngularJS time to safely finish its compile and linking.
                    $timeout( loadSync, 0, false );
                }
                // ---
                // PRIVATE METHODS.
                // ---
                // I load the 3rd-party script tag.
                function loadSync() {

                }
            }
        );

        app.controller('SupplierCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {


            $scope.model_data = {};
            $scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
            $scope.menus = [
                {link: '/engineering', name: 'Equipment Group'},
                {link: '/engineering/supplier', name: 'supplier'},
            ];


            $scope.idkey = [];

            $timeout( initDomData, 0, false );

            $scope.grid = {};

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
            //$scope.fields = ['ID', 'Property', 'Name', 'Description','Code'];
            $scope.fields = ['ID', 'Supplier', 'Contact', 'Phone','Email','Url'];

            function initDomData() {

                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/engineering/wizard/suppliernggrid/get',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                       // { data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'id' },
                        { data: 'supplier', name: 'supplier' },
                        { data: 'contact', name: 'contact' },
                        { data: 'phone', name: 'phone' },
                        { data: 'email', name: 'email' },
                        { data: 'url', name: 'url' },
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
                    $scope.model_data.supplier = "";
                    $scope.model_data.contact = '';
                    $scope.model_data.phone = '';
                    $scope.model_data.email = '';
                    $scope.model_data.url = '';
                }

            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;
                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/engineering/wizard/supplier/' + id,
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
                        url: '/backoffice/engineering/wizard/supplier',
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
                    $http.get('/backoffice/engineering/wizard/supplier/' + id)
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
                        url: '/backoffice/engineering/wizard/supplier/' + id
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
                    delete data.edit;
                    delete data.delete;


                    return data;
                }
                var data = {};
                return data;
            }

        });
    });