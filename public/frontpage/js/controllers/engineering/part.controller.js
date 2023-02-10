app.controller('PartController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, hotkeys, $interval, $aside, toaster, GuestService, AuthService, DateService, uiGridConstants) {
    var MESSAGE_TITLE = 'Part';

    $scope.gs = GuestService;

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 210) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';


    $scope.uploadexcel = {};
    $scope.uploadexcel.src = '';
    $scope.uploadexcel.name = '';
    $scope.uploadexcel.type = '';
    $scope.searchoptions = ['Manufacture','Supplier'];
    $scope.searchoption = $scope.searchoptions[0];

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
    $scope.part_name = '';

    $scope.part = {};


    $scope.initPageNum = function(){
        $scope.paginationOptions.numberOfPages = 1;
    }

    $scope.pageChanged = function(preserve) {
        console.log('Page changed to: ' + $scope.paginationOptions.numberOfPages);

        $scope.ticketlist = [];

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.


        var request = {};
        request.searchoption = $scope.searchoption;
        request.searchtext = $scope.searchtext;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;


        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;
        request.dept_id = profile.dept_id;
        request.job_role_id = profile.job_role_id;


        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var url = '/frontend/part/partlist';

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
            return 'C00000';
        return sprintf('C%05d', ticket.id);
    }

    $scope.getDescription = function(row){
        if(row.description.length > 60)
            return row.description.substr(0, 60) + "...";
        else
            return row.description ;
    }

    $scope.getQuantity = function(row){
        if(row.quantity <= row.minquantity && row.quantity != 0)
            return "Low Stock";
        else if(row.quantity == 0) 
            return "Out of Stock";
        else
            return row.quantity ;
    }

    $scope.onSelectTicket = function(ticket, event, type){
        ticket.type = type;
        $scope.selectedTicket = [];
        $scope.selectedTicket[0] = ticket;
        $scope.selectedNum = 0;
        $scope.part_name = ticket.name;
        $scope.part = ticket;
        if(ticket.critical_flag == 1) $scope.part.critical_flag = true;
        if(ticket.critical_flag == 0) $scope.part.critical_flag = false;
        if(ticket.external_maintenance == 1) $scope.part.external_maintenance = true;
        if(ticket.external_maintenance == 0) $scope.part.external_maintenance = false;
        $scope.checkSelection($scope.ticketlist);
        $rootScope.$broadcast('part_workorder');
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
        if($scope.part.id > 0) {
            var modalInstance = $uibModal.open({
                templateUrl: 'part_delete.html',
                controller: 'PartDeleteCtrl',
                scope: $scope,
                resolve: {
                    part: function () {
                        return $scope.part;
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
            templateUrl: 'part_excel.html',
            controller: 'PartExcelCtrl',
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

app.controller('PartDeleteCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Part';

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request.id = $scope.part.id;

        $http({
            method: 'POST',
            url: '/frontend/part/partdelete',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.part = {};
            toaster.pop('success', MESSAGE_TITLE, ' Part has been deleted successfully');
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

app.controller('PartExcelCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Part';

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
                url: '/frontend/part/importexcelpart',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function (response) {
                if(response.data == 1062) {
                    toaster.pop('error', MESSAGE_TITLE, 'Part names are duplicated.');
                }else if(response.status == '200') {
                    //console.log(response);
                    $scope.pageChanged();
                    toaster.pop('success', MESSAGE_TITLE, 'Part list of ' + response.data + ' has been imported  successfully');
                    $uibModalInstance.close();
                }

            }).catch(function (response) {
                    //console.log(response);
                    // CASE 3: NO Asignee Found on shift : Default Asignee
                    toaster.pop('error', MESSAGE_TITLE, 'Connection error.');
                })
                .finally(function () {

                });
        }
    }
});






