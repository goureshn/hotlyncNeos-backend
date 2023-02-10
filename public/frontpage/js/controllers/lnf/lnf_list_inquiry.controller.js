app.controller('LNFInquiryListController', function ($scope, $rootScope, $http, $window, $sce, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, $aside, liveserver) {
    let MESSAGE_TITLE = 'Lost&Found Status List';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 80;
    $scope.tab_height = $window.innerHeight + 10;
    $scope.tab_height1 = $window.innerHeight - 200;

    let profile = AuthService.GetCredentials();
    let client_id = profile.client_id;

    $scope.property_ids = [];

    $scope.lnf_inquiry_status = [
        {name: 'All', value: 'All', selected: false},
        {name: 'Inquired', selected: false},
        {name: 'Matched', selected: false},
        {name: 'Completed', selected: false},
        {name: 'Closed', selected: false},
    ];

    $scope.filters = {filtername: "", filtervalue: null};
    $scope.onChangeStatusFilter = function (status) {
        if (status.name === 'All') {
            let selected = status.selected;
            $scope.lnf_inquiry_status.forEach(item => item.selected = selected);
        }

        $scope.filters.filtername = 'status_name';
        $scope.filters.filtervalue = $scope.lnf_inquiry_status.filter(item => item.selected).map(item => item.name).join(',');

        $scope.pageChanged();
    };


    $scope.search_tags = [];
    $http.get('/frontend/lnf/getSearchTagsAll?property_id=' + profile.property_id)
        .then(function (response) {
            console.log(response.data);

            let res_search_tags = response.data.datalist;
            for (let i in res_search_tags) {
                $scope.search_tags.push(res_search_tags[i]);
            }

        });

    $http.get('/list/user?client_id=' + client_id)
        .then(function (response) {
            $scope.user_list = response.data;
        });

    $scope.isLoading = false;

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.data = {};

    angular.element('#dateranger').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger').focus();
        $scope.dateFilter = angular.element('#dateranger');

        $scope.dateFilter.on('apply.daterangepicker', function (ev, picker) {
            $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
            $scope.pageChanged();
        });
    };

    $scope.pageChanged = function (tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;
        /////////////////////
        let request = {};

        if (tableState !== undefined) {
            $scope.tableState = tableState;
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filters = JSON.stringify($scope.filters);
        request.filter_tags = JSON.stringify($scope.filter_tags);

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        let profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;
        request.lnf_type = 'Inquiry';

        //console.log(request);
        $http({
            method: 'POST',
            url: '/frontend/lnf/getLnfAllItems', //getLnf
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                console.log(response.data);
                let datalist = response.data.datalist.map(function (item) {
                    if (item.found_by > 0)
                        item.found_fullname = item.common_firstname + " " + item.common_lastname + " - Common User";
                    else
                        item.found_fullname = item.custom_firstname + " " + item.custom_lastname + " - Custom User";

                    return item;
                });

                if (!datalist || datalist.length < 1) {
                    $scope.data.datalist = [];
                    return;
                }

                for (let index = 0; index < datalist.length; index++) {
                    let item = datalist[index];

                    let images = item.images;
                    if (images) {
                        datalist[index].images_arr = datalist[index].images.split("|");
                    } else
                        datalist[index].images_arr = [];
                }
                $scope.property_ids = response.data.property_ids;
                //$scope.filter_value = '';
                let numberOfPages = 0;
                $scope.paginationOptions.totalItems = response.data.totalcount;
                if ($scope.paginationOptions.totalItems < 1)
                    $scope.paginationOptions.countOfPages = 0;
                else
                    $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);


                $scope.data.datalist = datalist;
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    // $scope.pageChanged();

    $scope.loadFiltersValue = function (value, query) {

        console.log($scope.search_tags);
        let search_items = [];
        for (let i = 0; i < $scope.search_tags.length; i++) {
            if ($scope.search_tags[i].toLowerCase().indexOf(query.toLowerCase()) !== -1)
                search_items.push($scope.search_tags[i]);
        }

        console.log(search_items);
        return search_items;
    };

    $scope.filterTags = {text: []};
    $scope.filter_tags = [];
    $scope.searchFilter = function () {
        $scope.filter_tags = [];
        for (let j = 0; j < $scope.filterTags.text.length; j++) {
            $scope.filter_tags.push($scope.filterTags.text[j].text);
        }

        $scope.pageChanged();
    };

    $scope.onPrevPage = function () {
        if ($scope.paginationOptions.numberOfPages <= 1)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;

        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
        $scope.isLoading = true;
        $scope.pageChanged();
    };

    $scope.onNextPage = function () {
        if ($scope.paginationOptions.totalItems < 1)
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if ($scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages)
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
        $scope.isLoading = true;
        $scope.pageChanged();
    };

    $scope.$on('refresh_data', function (event, args) {
        $scope.pageChanged();
    });

    $scope.onCloseItem = function (row) {
        let size = '';
        let modalInstance = $uibModal.open({
            templateUrl: 'closeReasonModal.html',
            controller: 'ReasonController',
            size: size,
            resolve: {
                ticket: function () {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (comment) {
            row.closed_comment = comment;
            closeItem(row);
        }, function () {

        });

    };

    function closeItem(row) {
        $http({
            method: 'POST',
            url: '/frontend/lnf/closeitem',
            data: row,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            console.log(response);
            row.status_name = 'Closed';
            $scope.$emit('onCreateNewLnf');
        }).catch(function (response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
            .finally(function () {

            });
    }

    $scope.onCreateItem = function () {
        let modalInstance = $uibModal.open({
            templateUrl: 'lnf_create_inquiry_dialog.html',
            controller: 'LnfCreateDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                lnf_type: function () {
                    return 'Inquiry';
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {

        }, function () {

        });
    }
});

app.controller('ReasonController', function ($scope, $uibModalInstance, toaster, ticket, $filter) {
    $scope.ticket = ticket;
    $scope.save = function () {
        if ($scope.ticket.closed_comment === undefined || $scope.ticket.closed_comment === '') {
            toaster.pop('info', MESSAGE_TITLE, 'Please input closed reason.');
            return;
        }

        $uibModalInstance.close($scope.ticket.closed_comment);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
});
