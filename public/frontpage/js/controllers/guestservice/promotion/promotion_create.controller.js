app.controller('PromotionCreateController', function ($scope, $rootScope, $http, $interval, $httpParamSerializer, AuthService, GuestService, toaster,Upload) {
    var MESSAGE_TITLE = 'Promotion Create';

    $scope.promotion = {};
    $scope.datetime = {};
    $scope.datetime.start_date = new Date();
    $scope.datetime.end_date = new Date();
    $scope.datetime.start_time = '';
    $scope.datetime.end_time = '';

    $scope.files = [];

    $scope.init = function(promotion) {
        $scope.promotion = promotion;
    }

     $scope.status_list = [
        'Active',
        'Disable',
        'Enabled',
        'Expired',
        'Cancel',
        'Extended',
        'Scheduled',
    ];

    $scope.promotion.status = $scope.status_list[0];

    $scope.$watch('datetime.start_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.datetime.start_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.$watch('datetime.end_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        $scope.datetime.end_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });


    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if( $view == 'day' )
        {
            var activeDate = moment().subtract('days', 1);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        else if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 0);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.emailCheck = function(value) {
        var EMAIL_REGEXP = /^[a-z0-9!#$%&'*+/=?^_`{|}~.-]+@[a-z0-9-]+(\.[a-z0-9-]+)*$/i;
        return EMAIL_REGEXP.test(value);
    }


    $scope.createPromotion = function() {
        var request = {};
        var confirm_val = $scope.confirm();
        if(confirm_val == false) {
            return ;
        }
        request = $scope.promotion;
        var profile = AuthService.GetCredentials();


        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.start_date = $scope.datetime.start_time;
        request.end_date = $scope.datetime.end_time;
        

        $http({
            method: 'POST',
            url: '/frontend/guest/promotion/create',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {

            if( response.status != 200 )
            {
                toaster.pop('error', MESSAGE_TITLE, 'This data can not save. please try it.');
                return;
            }

            if ($scope.files && $scope.files.length) {
                Upload.upload({
                    url: '/frontend/guest/promotion/uploadfiles',
                    data: {
                        id: response.data.id,
                        files: $scope.files
                    }
                }).then(function (response) {
                    $scope.files = [];
                    //$scope.datetime.date = new Date();
                    $scope.promotion = {};
                    $scope.datetime.start_time = '';
                    $scope.datetime.end_time = '';

                    $scope.$emit('ChangedGuestPromotion', response.data);
                    toaster.pop('success', MESSAGE_TITLE, 'Guest service Promotion have been successed to create');
                }, function (response) {
                    $scope.files = [];
                    if (response.status > 0) {
                        $scope.errorMsg = response.status + ': ' + response.data;
                    }
                }, function (evt) {
                    $scope.progress = 
                        Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                });
            }

        }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Promotion have been failed to create');
            })
            .finally(function() {

            });

    }

    $scope.cancelPromotion = function() {
        $scope.promotion = {};
        $scope.datetime.start_time = '';
        $scope.datetime.end_time = '';

    }

    $scope.confirm = function() {
        var confirm_val= true;
        if($scope.promotion.outlet_name == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter outlet name.');
            confirm_val = false;
        }
        if($scope.promotion.title == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter title.');
            confirm_val = false;
        }
        if($scope.promotion.price == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter price.');
            confirm_val = false;
        }
        if($scope.promotion.discnt == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter discount.');
            confirm_val = false;
        }
        if($scope.datetime.start_time == '' || !$scope.datetime.start_time ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter start date.');
            confirm_val = false;
        }
        if($scope.datetime.end_time == '' || !$scope.datetime.end_time ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter end date.');
            confirm_val = false;
        }
        if($scope.promotion.highlight == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter highlight.');
            confirm_val = false;
        }
        
        if($scope.promotion.enquiry_to == null ) {
            toaster.pop('error', MESSAGE_TITLE, 'Please enter send enquiry to.');
            confirm_val = false;
        }

        var emails = $scope.promotion.enquiry_to.split(',');
        var flag = false;
        for(var i =0 ; i < emails.length; i++) {
            var flag = $scope.emailCheck(emails[i].replace(/[\s]/g, ''));
            if(flag == false){
                break;
            }
        }
        if(flag == false){
            toaster.pop('error', MESSAGE_TITLE, 'Enquiry to is not right email format.');
            confirm_val = false;
        }

        return confirm_val;

    }

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);
    };

    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }

    //log history
     var paginationOptions = {
        pageNumber: 1,
        pageSize: 20,
        sort: 'asc',
        field: 'id',
    };

    var columns = [
        {
            field : 'id',
            displayName : "ID",
            enableCellEdit: false,
        },
        {
            field : 'action',
            displayName : "Action",
            enableCellEdit: false,
        },
        {
            field : 'created_at',
            displayName : "Date&Time",
            enableCellEdit: false,
        },
        {
            field : 'user',
            displayName : "User",
            enableCellEdit: false,
        },
    ];

    $scope.gridOptions =
    {
        enableGridMenu: false,
        enableRowHeaderSelection: false,
        enableColumnResizing: true,
        paginationPageSizes: [10, 20, 30, 40],
        paginationPageSize: 10,
        useExternalPagination: true,
        useExternalSorting: true,
        columnDefs: columns,
    };

    $scope.gridOptions.onRegisterApi = function( gridApi ) {
        $scope.gridApi = gridApi;
        
        gridApi.core.on.sortChanged($scope, function(grid, sortColumns) {
            if (sortColumns.length == 0) {
                paginationOptions.sort = 'asc';
                paginationOptions.field = 'id';
            } else {
                paginationOptions.sort = sortColumns[0].sort.direction;
                paginationOptions.field = sortColumns[0].name;
            }
            getPromotionHistory();
        });
        gridApi.pagination.on.paginationChanged($scope, function (newPage, pageSize) {
            paginationOptions.pageNumber = newPage;
            paginationOptions.pageSize = pageSize;
            getPromotionHistory();
        });
    };

    var getPromotionHistory = function() {
        var request = {};

        request.id = $scope.wakeup.id;
        request.page = paginationOptions.pageNumber;
        request.pagesize = paginationOptions.pageSize;
        request.field = paginationOptions.field;
        request.sort = paginationOptions.sort;

        $http({
            method: 'POST',
            url: '/frontend/guest/promotioin/logs',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.gridOptions.totalItems = response.data.totalcount;
            $scope.gridOptions.data = response.data.datalist;
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {

            });
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

