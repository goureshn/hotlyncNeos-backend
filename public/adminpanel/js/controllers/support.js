define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('SupportCtrl', function ($scope, $rootScope, $compile, $timeout, $http, $window, FileUploader   /*$location, $http, initScript */) {
            console.log("PropertyCtrl reporting for duty.");

            $scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
            $scope.model_data = {};

            //request socket to central server

            //socket.emit('support', device_number);
            //socket.emit('support','0000-0011-1587-0224-7099-5471-11');


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
            var profile = $rootScope.globals.currentUser;
            $scope.model_data.user_id = profile.id;
            $scope.model_data.client_id = profile.client_id;

            $http.get('/list/client').success( function(response) {
                $scope.clients = response;
            });

            $http.get('/list/property').success( function(response) {
                $scope.properties = response;     
                $scope.model_data.property_id = $scope.properties[0].id;

                $http.get('/list/module_list?property_id='+$scope.model_data.property_id).success( function(response) {
                    $scope.modules = response;
                    var length = $scope.modules.length;
                    var module = {};
                        module.id = 0;
                        module.name = '---Select Module';
                        module.description = '---Select Module';
                        $scope.modules.push(module);
                        $scope.model_data.module_id = $scope.modules[length].id;                   
                });
            });

            $scope.severities = [
                'High',
                'Medium',
                'Low'
            ];

            $scope.statuses = [
                'Open',
                'Assigned',
                'Pending',
                'Close'
            ];

            $scope.model_data.severity = $scope.severities[0];    
           
   
            initUploader();         
            $timeout( initDomData, 0, false );


            $scope.grid = {};
            $scope.idkey = [];

            $scope.fields = ['ID', 'Property', 'Module', 'Subject', 'Data Created', 'Severity', 'Created By', 'Status'];
            function initDomData() {
                var $grid = $('#table_grid').dataTable( {
                    processing: true,
                    serverSide: true,
                    order: [[ 1, "asc" ]], //column indexes is zero based
                    ajax: '/backoffice/property/wizard/support',
                    "lengthMenu": [[15, 25, 50, 100, -1], [15, 25, 50, 100, "All"]],
                    columns: [                        
                        { data: 'id', name: 'cp.id' },
                        { data: 'cpname', name: 'cp.name' },
                        { data: 'cmname', name: 'cm.name' },
                        { data: 'subject', name: 'cs.subject' },
                        { data: 'created_at', name: 'cs.created_at' },
                        { data: 'severity', name: 'cs.severity' },
                        { data: 'username', name: 'username' },
                        { data: 'status', name: 'cs.status' },
                        { data: 'edit', width: '40px', orderable: false, searchable: false},                        
                    ],
                    "createdRow": function( row, data, dataIndex ) {
                        $compile(row)($scope);
                        $scope.idkey[data.id] = dataIndex;
                    }
                });

                $scope.grid = $grid;
            }

            function initUploader()
            {
                var headers = {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')};
                var uploader = $scope.uploader = new FileUploader({
                    url: '/backoffice/property/wizard/support/uploadattach',
                    alias : 'myfile',
                    headers: headers
                });
                uploader.filters.push({
                    name: 'imageFilter',
                    fn: function(item /*{File|FileLikeObject}*/, options) {
                        var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
                        return '|jpg|png|bmp|'.indexOf(type) !== -1;
                    }
                });
                uploader.onSuccessItem = function(fileItem, response, status, headers) {
                    if($scope.model_data.attach_path !='') $scope.model_data.attach_path +='|';
                    $scope.model_data.attach_path += '/' + response.content;
                };
                uploader.onErrorItem = function(fileItem, response, status, headers) {
                    console.info('onErrorItem', fileItem, response, status, headers);
                };
            }

            $scope.cc_email = true;
            $scope.dbClickEmail = function() {
                if ($scope.cc_email == true )  $scope.cc_email = false;                                   
                else { 
                    $scope.cc_email = true;
                    $scope.onUpdateRow();
                }
            }

            $scope.emailCheck = function(value) {
                var EMAIL_REGEXP = /^[a-z0-9!#$%&'*+/=?^_`{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*$/i;
                return EMAIL_REGEXP.test(value);
            }

            $scope.onShowEditRow = function(id)
            {
                $scope.model_data.id = id;
                $scope.error = '';
                if( id > 0 )	// Update
                {
                    $scope.model_data = loadData(id);     
                    $scope.history(id);               
                }
                else
                {
                    $scope.model_data.property_id = $scope.properties[0].id;
                    $scope.model_data.module_id = $scope.modules[0].id;
                    $scope.model_data.severity = $scope.severities[0];                    
                    $scope.model_data.subject = "";
                    $scope.model_data.from_email = "";
                    $scope.model_data.cc_email = "";
                    $scope.model_data.issue = "";
                    $scope.model_data.attach_path = ""; 
                    $scope.historylist = {};                   
                }
            }


            $scope.onUpdateRow = function()
            {
                var id = $scope.model_data.id;               
                if($scope.model_data.property_id == null ) {
                    $scope.error = 'Please enter prperty.';
                    return ;
                }
                if($scope.model_data.module_id == null) {
                    $scope.error = 'Please enter module.';
                    return;
                }
                if($scope.model_data.subject == null) {
                    $scope.error = 'Please enter subject.';
                    return;
                }
                if($scope.model_data.severity == null) {
                    $scope.error = 'Please enter severity.';
                    return;
                }
                if($scope.model_data.from_email == null) {
                    $scope.error = 'Please enter email.';
                    return;
                }

                var flag_1 = false;    
                var flag_1 = $scope.emailCheck($scope.model_data.from_email.replace(/[\s]/g, ''));
                if(flag_1 == false){
                    $scope.error = 'Email is not right format.';
                    return;
                }  

                if($scope.model_data.cc_email == null) {
                    $scope.error = 'Please enter cc.';
                    return;
                }

                var emails = $scope.model_data.cc_email.split(',');
                var flag = false;
                for(var i =0 ; i < emails.length; i++) {
                    var flag = $scope.emailCheck(emails[i].replace(/[\s]/g, ''));
                    if(flag == false){
                        break;
                    }
                }
                if(flag == false){
                    $scope.error = 'CC is not right email format.';
                    return;
                }
                if($scope.model_data.issue == null) {
                    $scope.error = 'Please enter issue.';
                    return;
                }                
                
                $scope.error = '';
                var data = jQuery.extend(true, {}, $scope.model_data);

                if (id >= 0)	// Update
                {
                    $http({
                        method: 'POST',
                        url: '/backoffice/property/wizard/supportupdate/' + id,
                        data: data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function (data, status, headers, config) {
                            $scope.error = 'Successfully completed. Please click cancel button.';
                            if (data) {
                                refreshCurrentPage();
                            }
                            else {

                            }
                        })
                        .error(function (data, status, headers, config) {
                            console.log(status);
                        });
                }
                else {
                    $http({
                        method: 'POST',
                        url: '/backoffice/property/wizard/supportcreate',
                        data: data,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function (data, status, headers, config) {
                            $scope.error = 'Successfully completed. Please click cancel button.';
                            if (data) {
                                $scope.grid.fnPageChange('last');
                            }
                            else {

                            }
                        })
                        .error(function (data, status, headers, config) {
                            console.log(status);
                        });
                }                
            }

            $scope.historylist = {};

            $scope.history = function(id){
                //get history
                $http({
                    method: 'GET',
                    url: '/backoffice/property/wizard/support/gethistory/' + id
                })
                    .success(function(data, status, headers, config) {
                        if(status == '200') {
                            $scope.historylist = data;
                        }
                    })
                    .error(function(data, status, headers, config) {
                        console.log(status);
                    });
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
                        url: '/backoffice/property/wizard/support/' + id
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
                    delete data.ccname;                    
                    return data;
                }
                var data = {};
                return data;
            }

            $scope.sendMessage = function(){
                var request = $scope.model_data;
                request.client_id = profile.client_id;
                request.user_id = profile.id;
                
                 $http({
                        method: 'POST',
                        url: '/backoffice/property/wizard/supportsendmessage',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .success(function (data, status, headers, config) {
                            $scope.model_data.message_list = data.data_list;

                        })
                        .error(function (data, status, headers, config) {
                            console.log(status);
                        });
                
            }

        });

    });