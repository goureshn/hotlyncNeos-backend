define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('FaqCtrl', function ($scope, $rootScope, $compile, $httpParamSerializer, $timeout, $http, FileUploader /*$location*/) {
            console.log("OutdoorCtrl reporting for duty.");

            $scope.model_data = {};

            $http.get('/backoffice/module/getmodulelist').success( function(response) {
                $scope.modules = response;           
                if( $scope.modules.length > 0 )
                    $scope.model_data.module_id = $scope.modules[0].id;
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

            $scope.fields = ['ID', 'Title', 'Module', 'Category', 'Created By', 'Date Created', 'Last Edited'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/admin/wizard/faq',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                       // { data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'cf.id' },
                        { data: 'title', name: 'cf.title' },
                        { data: 'module', name: 'cm.name' },
                        { data: 'category', name: 'cc.name' },
                        { data: 'username', name: 'cu.first_name' },
                        { data: 'created_at', name: 'cf.created_at' },
                        { data: 'updated_at', name: 'cf.updated_at' },
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
                    
                    $scope.model_data.title = "";
                    $scope.model_data.category = "";
                    $scope.model_data.tags = "";
                    $scope.model_data.content = "";
                    $scope.model_data.excerpt = "";
                }

            }

            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;
                $scope.model_data.user_id = $rootScope.globals.currentUser.id;

                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/admin/wizard/faq/' + id,
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
                        url: '/backoffice/admin/wizard/faq',
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
                        url: '/backoffice/admin/wizard/faq/' + id
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

            /*category list and add*/
             $scope.getCategoryList = function(val) {
                if( val == undefined )
                    val = "";
                return $http.get('/backoffice/faq/category?category='+val)
                    .then(function(response){
                        if(response.data.length == 0) $scope.taskbtnview = true;
                        if(response.data.length > 0) {
                            return response.data.filter(function(item, index, attr){
                                return index < 10;
                            });
                        }
                    });
            };

            $scope.onCategorySelect = function (category, $item, $model, $label) {
                var category = {};
                $scope.model_data.category_id = $item.id;
                $scope.model_data.category = $item.name;
            };

            $scope.onAddCategory = function(){
                var category = $scope.model_data.category;
               
                var request = {};
                request.category = category;                
                
                $http({
                    method: 'POST',
                    url: '/backoffice/admin/wizard/faq/addcategory',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                }).then(function(response) {
                    $scope.model_data.category_id = response.data.list.id;
                    $scope.model_data.category = response.data.list.name;      
                    
                }).catch(function(response) {
                })
                .finally(function() {

                });
            }

            $scope.loadFilters = function(query) {
                console.log($scope.faq_tags); 
                var request = {};                
                
                request.filter = query;

                var param = $httpParamSerializer(request);

                return $http.get('/backoffice/faq/gettaglist?' + param);               
                
            };

             //end log history
            $scope.editorCreated = function (editor) {
                console.log(editor)
            }
            $scope.contentChanged = function (editor, html, text, delta, oldDelta, source) {
                console.log('delta: ', delta, 'oldDelta:', oldDelta);
            }
            $scope.selectionChanged = function (editor, range, oldRange, source) {
                console.log('editor: ', editor, 'range: ', range, 'oldRange:', oldRange, 'source:', source)
            }
            
        });
        
    });