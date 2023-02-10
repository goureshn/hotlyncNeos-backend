define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('FeedbackTypeCtrl', function ($rootScope, $scope, $compile, $timeout, $http /*$location, $http, initScript */) {

            $scope.model_data = {};

            var profile = $rootScope.globals.currentUser;


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

            $http.get('/list/complaint_datalist?client_id=' + profile.client_id).success(function (response) {
				$scope.category_list = response.category_list;
				$scope.severity_list = response.severity_list;
            });
            
            //end///
            $scope.fields = ['ID', 'Name', /*'Category', 'Severity',*/ 'Default Flag'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/guestservice/wizard/feedbacktype',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        //{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'scft.id' },
                        { data: 'name', name: 'scft.name' },
                        // { data: 'category', searchable: false},
                        // { data: 'severity', searchable: false},
                        { data: 'default_flag', name: 'scft.default_flag' },
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

            function ids2Names(ids, list, name_key, id_key)
            {
                if( ids == null || ids == '' )
                    return [];
                    
                ids = ids.split(',');

                return ids.map(id => {
                    var row = list.find(item => {
                        return parseInt(id) == item[id_key];
                    })

                    return row[name_key];
                });
            }

            function names2Ids(names, list, text_key, id_key)
            {
                if( names == null || names == '' )
                    return '';

                return names.map(name => {
                    var row = list.find(item => {
                        return name.text == item[text_key];
                    })

                    return row[id_key];
                }).join();
            }

            $scope.onShowEditRow = function(id)
            {
                $scope.model_data.id = id;

                if( id > 0 )	// Update
                {
                    $scope.model_data = loadData(id);

                    $scope.category_name_list = ids2Names($scope.model_data.category_ids, $scope.category_list, 'name', 'id');						
                    $scope.severity_name_list = ids2Names($scope.model_data.severity_ids, $scope.severity_list, 'type', 'id');						
                }
                else
                {
                    $scope.model_data.name = "";
                    $scope.category_name_list = [];						
                    $scope.severity_name_list = [];
                }
            }


            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;

                // $scope.model_data.category_ids = names2Ids($scope.category_name_list, $scope.category_list, 'name', 'id');
			    // $scope.model_data.severity_ids = names2Ids($scope.severity_name_list, $scope.severity_list, 'type', 'id');

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/guestservice/wizard/feedbacktype/' + id,
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
                        url: '/backoffice/guestservice/wizard/feedbacktype',
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
                        url: '/backoffice/guestservice/wizard/feedbacktype/' + id
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

                    return data;
                }
                var data = {};
                return data;
            }

            $scope.loadCategoryFilters = function (query) {
                var category_list = $scope.category_list.filter(function (item) {
                    return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;					
                });
        
                return category_list.map(function (tag) { return tag.name; });	
            }

            $scope.loadSeverityFilters = function (query) {
                var severity_list = $scope.severity_list.filter(function (item) {
                    return item.type.toLowerCase().indexOf(query.toLowerCase()) != -1;					
                });
        
                return severity_list.map(function (tag) { return tag.type; });	
            }

        });
    });