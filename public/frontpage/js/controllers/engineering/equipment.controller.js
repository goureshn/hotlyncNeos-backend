app.controller('EquipmentController', function ($scope, $rootScope, $http, $uibModal, $window, toaster, AuthService) {
    var MESSAGE_TITLE = 'Equipment';

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 190) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.location_tags = [];
    $scope.department_tags = [];
    $scope.status_tags = [];
    $scope.equip_group_tags = [];
    $scope.equip_id_tags = [];

    var profile = AuthService.GetCredentials();


    // location list
    var location_list = [];
    $http.get('/list/locationlist?&property_id=' + profile.property_id)
        .then(function(response){
            location_list = response.data;
        });
     
    $scope.locationTagFilter = function(query) {
        return location_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    // deparment list
    var department_list = [];
    $http.get('/frontend/guestservice/departlist?property_id=' + profile.property_id)
        .then(function(response){
            department_list = response.data.departlist;
        });

    $scope.departmentTagFilter = function(query) {
        return department_list.filter(function(item) {
            return item.department.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }
        
    // equipment status list
    var status_list = [];
    $http.get('/list/equipstatuslist')
        .then(function(response){
            status_list = response.data;
        });

    $scope.statusTagFilter = function(query) {
        return status_list.filter(function(item) {
            return item.status.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }    

    // equipment group list
    equip_group_list = [];
    $http.get('/frontend/equipment/grouplist')
        .then(function(response){
            equip_group_list = response.data;                              
        });

    $scope.equipGroupTagFilter = function(query) {
        return equip_group_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

      // equipment id list
      equip_id_list = [];
      $http.get('/frontend/equipment/idlist?property_id=' + profile.property_id)
          .then(function(response){
              equip_id_list = response.data;                              
          });
  
      $scope.equipIdTagFilter = function(query) {
          return equip_id_list.filter(function(item) {
            return item.equip_id.toLowerCase().indexOf(query.toLowerCase()) != -1;
          });
      }

    $scope.uploadexcel = {};
    $scope.uploadexcel.src = '';
    $scope.uploadexcel.name = '';
    $scope.uploadexcel.type = '';
    $scope.searchoptions = ['Status','Department','Location','Manufacture','Supplier'];
    $scope.searchoption = $scope.searchoptions[0];
    $scope.statuses = [
        {name: 'Due', level: '1'},
        {name: 'OK', level: '2'},
        {name: 'Retired', level: '3'},
        {name: 'Faulty', level: '4'},
        {name: 'Break Down', level: '5'},
        {name: 'Over Due', level: '6'},
        {name: 'All', level: '7'},
    ];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.select_status = [true, false, false];
    $scope.onChangeStatus = function (val) {
        switch(val) {
            case 'create':
                $scope.select_status = [true, false, false];
                break;
            case 'edit':
                $scope.select_status = [false, true, false];
                break;
            case 'detail':
                $scope.select_status = [false, false, true];
                break;
        }
    }



    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = 'height: ' + ($window.innerHeight - 115) + 'px; overflow-y: auto;';

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.ticketlist = [];
    $scope.selectedTicket = [];
    $scope.equipment_name = '';

    $scope.equipment = {};


    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
    }

    $scope.pageChanged = function(preserve) {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);       
        
        $scope.ticketlist = [];

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        var profile = AuthService.GetCredentials();
        
        
        var request = {};
        request.property_id = profile.property_id;        
        request.searchtext = $scope.searchtext;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        request.location_ids = $scope.location_tags.map(item => item.id).join(',');
        request.department_ids = $scope.department_tags.map(item => item.id).join(',');
        request.status_ids = $scope.status_tags.map(item => item.id).join(',');
        request.equip_group_ids = $scope.equip_group_tags.map(item => item.id).join(',');
        request.equip_ids = $scope.equip_id_tags.map(item => item.equip_id).join(',');
 
        $scope.filter_apply = $scope.location_tags.length > 0 ||             
            $scope.department_tags.length > 0 ||            
            $scope.status_tags.length > 0 ||
            $scope.equip_id_tags.length > 0 ||
            $scope.equip_group_tags.length > 0;    

        var url = '/frontend/equipment/equipmentlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.ticketlist = response.data.datalist;

            $scope.paginationOptions.totalItems = response.data.totalcount;

            if( $scope.paginationOptions.totalItems < 1 )
                $scope.paginationOptions.countOfPages = 0;
            else
                $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.pageChanged();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.pageChanged();
    }

    $scope.refreshTickets = function(){
        $scope.pageChanged();
    }

  
    $scope.refreshTickets();

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'A00000';
        return sprintf('A%05d', ticket.id);
    }

    $scope.onSelectTicket = function(ticket, event, type){
        ticket.type = type;
        $scope.selectedTicket = [];
        $scope.selectedTicket[0] = ticket;
        $scope.selectedNum = 0;
        $scope.equipment_name = ticket.name;
        $scope.equipment = ticket;
        if(ticket.critical_flag == 1) $scope.equipment.critical_flag = true;
        if(ticket.critical_flag == 0) $scope.equipment.critical_flag = false;
        if(ticket.external_maintenance == 1) $scope.equipment.external_maintenance = true;
        if(ticket.external_maintenance == 0) $scope.equipment.external_maintenance = false;
        var request = {};
        request.equip_id = ticket.id;
        $http({
            method: 'POST',
            url: '/frontend/equipment/equipmentinformlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if(response.data.filelist != null) $scope.equipment.filelist = response.data.filelist;
            $scope.checkSelection($scope.ticketlist);
            //console.log(response);
            $rootScope.$broadcast('equipment_workorder');
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
    $scope.checkSelection = function(ticketlist){
        if( !ticketlist )
            return;
        $scope.onChangeStatus('detail');
        for(var i = 0; i < ticketlist.length; i++)
        {
            var index = -1;
            for(var j = 0; j < $scope.selectedTicket.length; j++ )
            {
                if( ticketlist[i].id == $scope.selectedTicket[j].id)
                {
                    index = j;
                    break;
                }
            }
            ticketlist[i].active = index >= 0 ? true : false;
        }
    }

    $scope.Delete = function () {
        if($scope.equipment.id > 0) {
            var modalInstance = $uibModal.open({
                templateUrl: 'equipment_delete.html',
                controller: 'EquipmentDeleteCtrl',
                scope: $scope,
                resolve: {
                    equipment: function () {
                        return $scope.eqipment;
                    }
                }
            });

            modalInstance.result.then(function (selectedItem) {
                $scope.selected = selectedItem;
            }, function () {

            });
        }
    }

    $scope.onImportExcel = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'equipment_excel.html',
            controller: 'EquipmentExcelCtrl',
            scope: $scope,
            resolve: {
                name: function () {
                    return $scope.name;
                }

            }
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

});

app.controller('EquipmentDeleteCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Equipment';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.id = $scope.equipment.id;

        $http({
            method: 'POST',
            url: '/frontend/equipment/equipmentdelete',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.equipment = {};
            toaster.pop('success', MESSAGE_TITLE, ' Equipment has been deleted successfully');
            $uibModalInstance.close();
            $scope.pageChanged();
        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
    }
});

app.directive('excelDropzone', function() {
        return {
            restrict: 'A',
            scope: {
                file: '=',
                fileName: '='
            },
            link: function(scope, element, attrs) {
                var checkSize,
                    isTypeValid,
                    processDragOverOrEnter,
                    validMimeTypes;

                processDragOverOrEnter = function (event) {
                    if (event != null) {
                        event.preventDefault();
                    }
                    event.dataTransfer.effectAllowed = 'copy';
                    return false;
                };

                validMimeTypes = attrs.fileDropzone;

                checkSize = function(size) {
                    var _ref;
                    if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
                        return true;
                    } else {
                        alert("File must be smaller than " + attrs.maxFileSize + " MB");
                        return false;
                    }
                };

                isTypeValid = function(type) {
                    if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
                        return true;
                    } else {
                        alert("Invalid file type.  File must be one of following types " + validMimeTypes);
                        return false;
                    }
                };

                element.bind('dragover', processDragOverOrEnter);
                element.bind('dragenter', processDragOverOrEnter);

                return element.bind('drop', function(event) {
                    var file, name, reader, size, type;
                    if (event != null) {
                        event.preventDefault();
                    }
                    reader = new FileReader();
                    reader.onload = function(evt) {
                        if (checkSize(size) && isTypeValid(type)) {
                            return scope.$apply(function() {
                                scope.file = evt.target.result;
                                if (angular.isString(scope.fileName)) {
                                    return scope.fileName = name;
                                }
                            });
                        }
                    };
                    file = event.dataTransfer.files[0];
                    name = file.name;
                    type = file.type;
                    size = file.size;
                    reader.readAsDataURL(file);
                    return false;
                });
            }
        };
    })


    .directive("excelfileread", [function () {
        return {
            scope: {
                excelfileread: "=",
                excelname: "=",
                exceltype: "="
            },
            link: function (scope, element, attributes) {
                element.bind("change", function (changeEvent) {
                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.excelfileread = loadEvent.target.result;
                        });
                    }
                    scope.excelname = changeEvent.target.files[0].name;
                    scope.exceltype = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);

app.controller('EquipmentExcelCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Equipment';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.createExcel = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.name = $scope.uploadexcel.name;
        request.exceltype = $scope.uploadexcel.type;
        request.src = $scope.uploadexcel.src;
        
        if(request.src !=  '' && request.src != null ) {
            $http({
                method: 'POST',
                url: '/frontend/equipment/importexcel',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function (response) {
                if(response.data == 1062) {
                    toaster.pop('error', MESSAGE_TITLE, 'Equipment names are duplicated.');
                }else if(response.status == '200') {
                    $scope.pageChanged();
                    toaster.pop('success', MESSAGE_TITLE, 'Eqipment list of ' + response.data + ' has been imported  successfully');
                    $uibModalInstance.close();
                }

            }).catch(function (response) {
                //consol.log(response);
                    // CASE 3: NO Asignee Found on shift : Default Asignee
                    toaster.pop('error', MESSAGE_TITLE, 'Connection error.');
                })
                .finally(function () {

                });
        }
    }
});






