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

        app.controller('CategoryCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {


            $scope.model_data = {};
            $scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
            $scope.menus = [
                {link: '/monthly', name: 'Equipment Group'},
                {link: '/backup/monthly', name: 'Backup Monthly'},
            ];

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
                var alloption = {id: '0', name : '-- Select Property --'};
                $scope.properties.unshift(alloption);
            });

            $scope.idkey = [];

            $timeout( initDomData, 0, false );

            $scope.grid = {};

            //$scope.fields = ['ID', 'Property', 'Name', 'Description','Code'];
            $scope.fields = ['No', 'File Name', 'Backup Date'];

            function initDomData() {

                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/backoffice/backup/wizard/monthly/get',
                        dataSrc: ''
                    },
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        { data: 'id' },
                        { data: 'filename'},
                        { data: 'date' }
                    ],
                    "createdRow": function( row, data, dataIndex ) {
                        $compile(row)($scope);
                        $scope.idkey[data.id] = dataIndex;
                    }
                });

                $scope.grid = $grid;

            }

            /*$scope.onShowEditRow = function(id)
             {
             $scope.model_data.id = id;

             if( id > 0 )	// Update
             {
             $scope.model_data = loadData(id);
             for(var i = 0 ;i <  $scope.properties.length ; i++) {
             if($scope.cpname == $scope.properties[i].name) {
             $scope.model_data.property_id = $scope.properties[i].id;
             break;
             }
             }
             }
             else
             {
             $scope.model_data.name = "";
             $scope.model_data.property_id = $scope.properties[0].id;
             }

             }

             $scope.onUpdateRow = function()
             {
             var id = $scope.model_data.id;
             if( id >= 0 )	// Update
             {
             $http({
             method: 'PUT',
             url: '/backoffice/engineering/wizard/category/' + id,
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
             url: '/backoffice/engineering/wizard/category',
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
             $http.get('/backoffice/engineering/wizard/category/' + id)
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
             url: '/backoffice/engineering/wizard/category/' + id
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
             //$scope.cpname = data.cpname;
             delete data.checkbox;
             delete data.edit;
             delete data.delete;
             delete data.cpname;

             return data;
             }
             var data = {};
             return data;
             }*/

        });
    });