app.controller('MobileTrackController', function ($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, blockUI, $uibModal, AuthService, toaster, liveserver,Upload) {
    var MESSAGE_TITLE = 'Mobile Tracking';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 200) + 'px;';
    $scope.tab_full_list_height = 'height: ' + ($window.innerHeight - 220) + 'px; overflow-y: auto;';
    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.$on('refresh_list', function (event) {
       
        $scope.getDataList();
    });
    $scope.$on('onSyncMobileList', function (event, args) {
        $scope.getDataList();
    });
    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if (tableState != undefined) {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'asc' : 'desc';
        }


        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/gettracklist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if (tableState != undefined)
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                //console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.uploadfile = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/callaccounting/uploadmobilecsv.html',
            controller: 'UploadList',
             scope: $scope,
            // resolve: {
            //     dept_func_id: function () {
            //         return $scope.dept_func_id;
            //     }
            //     ,
            //     casual_staff_list: function () {
            //         return $scope.casual_staff_list;
            //     }
            // }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }
    $scope.sync = function (row) {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.row =row;
        blockUI.start("Please wait while CSV file is being synced."); 
        $http({
            method: 'POST',
            url: '/frontend/callaccount/syncstart',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                blockUI.stop(); 
                $scope.getDataList();
                //console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
    $scope.sendReminder = function (row) {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.row =row;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/sendreminder',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
               
                $scope.getDataList();
                //console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
    $scope.delete = function (row) {
        var request = {};
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.row = row;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/deletetracklist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, response.data.message);
                $scope.getDataList();
               
                // $scope.datalist = response.data.datalist;
                // $scope.paginationOptions.totalItems = response.data.totalcount;

                // var numberOfPages = 0;

                // if ($scope.paginationOptions.totalItems < 1)
                //     numberOfPages = 0;
                // else
                //     numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                // if (tableState != undefined)
                //     tableState.pagination.numberOfPages = numberOfPages;
                // else
                //     $scope.tableState.pagination.numberOfPages = numberOfPages;

                // $scope.paginationOptions.countOfPages = numberOfPages;

                //console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    }
});
app.controller('UploadList', function ($scope, $rootScope, $uibModalInstance,$window, $http,Upload, AuthService) {
    $scope.image = [];
    $scope.staff ={};
    $scope.date = new Date();
    //$scope.staff.type = new Date();
    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker',
       // minMode: 'month',
    };

  
    $scope.createEntry = function () {
       // window.alert($scope.staff.type);
        //$scope.staff.type = $scope.date.format('yyyy-MMMM');
        $scope.load = 1;
        //window.alert("here out:" + $scope.image);
        var profile = AuthService.GetCredentials();
        if ($scope.image && $scope.image.length) {
           // window.alert("here");
            Upload.upload({
                url: '/frontend/callaccount/uploadimage',
                data: {
                    mobile_track: $scope.image,
                    type: $scope.date,
                    user: profile.first_name
                }
            }).then(function (response) {
                $scope.image = [];
                $scope.progress_img = 0;
               
               // $scope.complaint.guest_path = '/' + response.data.path;
                $scope.load = 0;
                 $uibModalInstance.dismiss();
                $rootScope.$broadcast('refresh_list');
            }, function (response) {
                $scope.image = [];
                $scope.progress_img = 0;
               
                if (response.status > 0) {
                    $scope.errorMsg = response.status + ': ' + response.data.files;
                    window.alert("Error:"+ $scope.errorMsg );
                }
                $rootScope.$broadcast('refresh_list');
            }, function (evt) {
                $scope.progress_img =
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        }
       
       
        
    };
    $scope.selectStaff = function (row) {
        $scope.staff = row;
    };


    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    $scope.uploadGuestImg = function (image) {
        $scope.image = $scope.image.concat(image);
       
    };
  

});
