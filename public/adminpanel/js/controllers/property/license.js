define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('LicenseCtrl', function ($scope, $compile, $timeout, $window, $http, FileUploader /*$location, $http, initScript */) {
            console.log("PropertyCtrl reporting for duty.");

            $scope.model_data = {};
            $scope.license_file = null;
            $scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
            $scope.menus = [
                {link: '/property', name: 'Property'},
                {link: '/property/license', name: 'License'},
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
            $http.get('/list/client').success( function(response) {
                $scope.clients = response;
            });

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;
            });

            var device_number = "";

            $http.get('/backoffice/property/wizard/license_deviceid').success( function(response) {                
                device_number = response.device_id;
            });

            $timeout( initDomData, 0, false );

            $http.get('/list/module').success( function(response) {
                $scope.module = response;
                $scope.modules = [];
                for(var i = 0; i < $scope.module.length ; i++) {
                    var mo = {id: $scope.module[i].id, label: $scope.module[i].name};
                    $scope.modules.push(mo);
                }
            });

            $scope.modules_hint = {buttonDefaultText: 'Select Module'};
            $scope.modules_hint_setting = {
                smartButtonMaxItems: 3,
                smartButtonTextConverter: function(itemText, originalItem) {
                    return itemText;
                }
            };

            $scope.module_type = [];

            $scope.grid = {};
            $scope.idkey = [];

            $scope.fields = ['ID', 'Client','Property', 'Email', 'Request File','Room Count','Expire'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 0, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/property/wizard/license',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [
                        //{ data: 'checkbox', width: '40px', orderable: false, searchable: false},
                        { data: 'id', name: 'cl.id' },
                        { data: 'ccname', name: 'cc.name' },
                        { data: 'cpname', name: 'cp.name' },
                        { data: 'email', name: 'cp.email' },
                        // { data: 'modules', width: '280px',orderable: false, searchable: false },
                        { data: 'down_csr_path', name: 'cl.csr_path' },
                        { data: 'room_count', name: 'cl.room_count' },
                        { data: 'expiry_date', name: 'cl.expiry_date' },
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
                    $scope.model_data.client_id = $scope.clients[0].id;
                    $scope.model_data.address = "";
                    $scope.model_data.company = "";
                    $scope.model_data.phone = "";
                    $scope.model_data.email = "";
                    $scope.model_data.property_id = $scope.properties[0].id;
                    $scope.model_data.module_type = [];
                    $scope.model_data.user_count = "";
                    $scope.model_data.room_count = "";
                    $scope.model_data.device_number = device_number;
                    $scope.model_data.serial_number = "";
                    $scope.model_data.created_at = "";
                    $scope.model_data.updated_at = "";
                    $scope.model_data.expiry_date = "";                    
                }
            }

            $scope.onUpdateRow = function(condition)
            {
                var id = $scope.model_data.id;
                $scope.model_data.condition = condition;
                var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                var email_confirm = re.test($scope.model_data.email);

                if($scope.model_data.email == '' || email_confirm == false ) {
                    alert("Please enter email or enter right email.");
                    return;
                }
                var data = jQuery.extend(true, {}, $scope.model_data);
                console.log(data);
                if( id >= 0 )	// Update
                {
                    $http({
                        method: 'PUT',
                        url: '/backoffice/property/wizard/license/' + id,
                        data: data,
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
                        url: '/backoffice/property/wizard/license',
                        data: data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function(data, status, headers, config) {
                            if(data.code == '401') {
                                alert(data.message);
                            }
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
                        url: '/backoffice/property/wizard/license/' + id
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
                    delete data.cpname;
                    delete data.ccname;
                    
                    data.device_number = device_number;

                    return data;
                }
                var data = {};
                data.device_number = device_number;
                return data;
            }

            $scope.onSelectFile = function(license_file)
            {
                $scope.license_file = license_file;
                console.log($scope.license_file);
            }

            $scope.onUploadLicense = function(license_file)
            {
                var fd = new FormData();
                fd.append("id", $scope.model_data.id);
                fd.append("property_id", $scope.model_data.property_id);                
                fd.append('file', $scope.license_file);
                
                $http.post('/backoffice/property/wizard/uploadlicense', fd, {
                        transformRequest: angular.identity,
                        headers: {'Content-Type': undefined}
                    })
                    .success(function(response){
                        console.log(response);
                        refreshCurrentPage();

                        if( response.success != 200 )
                        {
                            $scope.message = " Error: Can't connection databse.";
        
                        }
                        else
                        {
                            $scope.message = "The "+config.data.fieldname + ' was kept in database successfully.';
                        }
                    })
                    .error(function(data, status, headers, config){
                        console.log(status);
                    });   
            }

        });
    });