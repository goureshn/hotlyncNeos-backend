define(['app', 'datatables.net', 'datatables.net-bs', 'multiselect', 'directives/directive'],
    function (app) {
        app.controller('SubcomplaintJobroleDeptCtrl', function ($scope, $compile, $timeout, $http, $localStorage,$sessionStorage /*$location, $http, initScript */) {
            console.log("SubcomplaintJobroleDeptCtrl reporting for duty.");

            $scope.model_data = {};

            $scope.menus = [
                {link: '/complaint', name: 'User'},
                {link: '/complaint/subcomplaint_jobrole_dept', name: 'Sub complaint Job role Department'},
            ];

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
            $scope.fields = ['ID', 'Property', 'Job Role', 'Department', 'Permission Group', 'Manager Flag','Cost'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based                    
                    ajax: {
                        url: '/backoffice/user/wizard/jobrole/list',
                        type: 'GET',
                        "beforeSend": function(xhr){
                                xhr.setRequestHeader("Authorization", $sessionStorage.admin.authdata);
                            }
                    },
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [                        
                        { data: 'id', name: 'cj.id' },
                        { data: 'property_name', name: 'cp.name' },
                        { data: 'job_role', name: 'cj.job_role' },
                        { data: 'department', name: 'cd.department' },
                        { data: 'pgname', name: 'pg.name' },
                        { data: 'manager_flag', name: 'manager_flag' },
                        { data: 'cost', name: 'cost' },                        
                    ],
                    "createdRow": function( row, data, dataIndex ) {
                        $compile(row)($scope);
                        $scope.idkey[data.id] = dataIndex;

                        if ( dataIndex == 0 )
                        {
                            $(row).attr('class', 'selected');
                            $scope.selected_id = data.id;
                            showDepartmentList();
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
                    showDepartmentList();
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

            function refreshCurrentPage()
            {
                var oSettings = $scope.grid.fnSettings();
                var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
                $scope.grid.fnPageChange(page);
            }

            function showDepartmentList()
            {
                var client_id = $sessionStorage.admin.currentUser.client_id;

                var data = {job_role_id: $scope.selected_id, client_id: client_id};

                $http({
                    method: 'POST',
                    url: "/backoffice/user/wizard/jobrole/deptlist",
                    data: data,
                    headers: {'Content-Type': 'application/json; charset=utf-8',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
                })
                .then(function(response) {
                    var data = response.data;

                    console.log(data[0]);
                    console.log(data[1]);

                    var from = $('#search');
                    from.empty();

                    $.each(data[0], function(index, element) {
                        from.append("<option value='"+ element.id +"'>" + element.department + '(' + element.property_name + ')' + "</option>");
                    });

                    var to = $('#search_to');
                    to.empty();
                    var count = 1;
                    $.each(data[1], function(index, element) {
                        to.append("<option value='"+ element.id +"'>" + element.department + '(' + element.property_name + ')' + "</option>");
                        count++;
                    });    
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
                    url: "/backoffice/user/wizard/jobrole/postdeptlist",
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