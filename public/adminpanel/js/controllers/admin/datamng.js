define(['app', 'directives/directive', 'file-model'],
    function (app) {
        app.controller('DataManageCtrl', function ($scope, $http, $window) {
            $scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';

            $scope.onDownloadTemplate = function() {
                $window.location.href = '/uploads/template/hotel_data.xlsx';			
            }

            $scope.excel_file = null;    

            $scope.onUploadData = function()
            {
                var fd = new FormData();
                fd.append('myfile', $scope.excel_file);
                
                $http.post('/backoffice/admin/wizard/datamng/upload', fd, {
                    transformRequest: angular.identity,
                    headers: {'Content-Type': undefined}
                })
                .success(function(response){
                    console.log(response);     
                    if( response.code != 200 )
                    {
                        $scope.success_message = '';
                        $scope.error_message = response.message;
                    }
                    else                   
                    {
                        $scope.success_message = 'Data is updated successfully.';
                        $scope.error_message = '';
                    }
                })        
                .error(function(data, status, headers, config){
                    $scope.error_message = status;
                });         
            }   

    
            // Purge Data
            $scope.table_list = [
                {id: "common_room", label: "Rooms"},
                {id: "common_floor", label: "Floors"},
                {id: "call_staff_extn", label: "Admin Extension"},
                {id: "call_guest_extn", label: "Guest Extension"},
                {id: "services_task_list", label: "Task"},
                {id: "common_users", label: "Users"},
                {id: "services_location", label: "Location"},
            ];

            $scope.table_hint = { buttonDefaultText: 'Select Table List' };	
            $scope.selected_table = [];	
            $scope.hint_setting = {
                smartButtonMaxItems: 3,
                smartButtonTextConverter: function (itemText, originalItem) {
                    return itemText;
                }
            };

            $scope.onDeleteData = function() {
                temp = "";
                $scope.selected_table.forEach((element, index) => {
                    if(index > 0)
                        temp += ",";
                    
                    temp += element.id;
                });

                var request = {};
                request.table_list = temp;
                $http({
                    method: 'POST',
                    url: '/backoffice/admin/wizard/datamng/erasetables',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                })
                    .success(function (data, status, headers, config) {
                        if (data) {
                            $scope.success_message = "Tables are erased successfully";
                            $scope.error_message = '';
                        }
                        else {

                        }
                    })
                    .error(function (data, status, headers, config) {
                        console.log(status);
                        $scope.success_message = "";
                        $scope.error_message = status;
                    });

            }
            
        });
    });