app.controller('LNFMatchingListController', function ($scope, $rootScope, $http, $window,$sce, $httpParamSerializer, $timeout, $uibModal, AuthService, toaster, $aside, liveserver) {
    var MESSAGE_TITLE = 'Lost&Found Match';

    //$scope.full_height = 'height: ' + ($window.innerHeight - 40) + 'px; overflow-y: auto';
    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
    $scope.full_height = $window.innerHeight - 80;
    $scope.tab_height = $window.innerHeight +10;
    $scope.tab_height1 = $window.innerHeight - 200;

    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;


    $scope.search_tags = [];
    $http.get('/frontend/lnf/getSearchTagsAll?property_id=' + profile.property_id)
        .then(function(response) {
            console.log(response.data);

            var res_search_tags = response.data.datalist;
            for(var i in res_search_tags)
                $scope.search_tags.push(res_search_tags[i]);
        });

    $http.get('/list/user?client_id=' + client_id)
        .then(function(response) {
            $scope.user_list = response.data;
        });

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    var daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    
    $scope.filterTags = [];
    
    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };


    $scope.onClickDateFilter = function() {
        angular.element('#dateranger').focus();
        $scope.dateFilter = angular.element('#dateranger');

        $scope.dateFilter.on('apply.daterangepicker', function(ev, picker) {
            daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
            $scope.pageChanged();
        });
    }


    var daterange1 = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    
    $scope.filterTags1 = [];
    
    $scope.paginationOptions1 = {
        pageNumber: 0,
        pageSize: 10,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.onClickDateFilter1 = function() {
        angular.element('#dateranger1').focus();
        $scope.dateFilter1 = angular.element('#dateranger1');

        $scope.dateFilter1.on('apply.daterangepicker', function(ev, picker) {
            daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
            $scope.pageChanged1();
        });
    }


    $scope.data = {};
    $scope.data.datalist = [];
    $scope.data.datalist1 = [];

    
    $scope.pageChanged = function(tableState) {
        var filter_tags = $scope.filterTags.map(item => {
            return item.text;
        });
        pageChanged(tableState, $scope.paginationOptions, filter_tags, daterange, 'Inquiry');        
    };

    $scope.pageChanged1 = function(tableState) {
        var filter_tags = $scope.filterTags1.map(item => {
            return item.text;
        });
        pageChanged(tableState, $scope.paginationOptions1, filter_tags, daterange, 'Found');        
    };

    $scope.$on('refresh_data', function(event, args){
        // $scope.firstTab();
         $scope.pageChanged();
         $scope.pageChanged1();
     });

    function pageChanged(tableState, paginationOptions, filter_tags, daterange, lnf_type) {
        var request = {};

        if( tableState != undefined )
        {
            paginationOptions.field = tableState.sort.predicate;
            paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }
        request.page = paginationOptions.pageNumber;
        request.pagesize = paginationOptions.pageSize;
        request.field = paginationOptions.field;
        request.sort = paginationOptions.sort;

        request.filter_tags = JSON.stringify(filter_tags);

        var filters = {};
        filters.filtername = '';
        filters.filtervalue = [] ;
        request.filters = JSON.stringify(filters);

        request.start_date = daterange.substring(0, '2016-01-01'.length);
        request.end_date = daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;

        // get selection id for inquiry
        request.selected_inquiry_ids = $scope.data.datalist.filter(item => item.selected).map(item => item.item_id).join(',');
        request.suggest_flag = $scope.data.suggest_flag;
        

        var url = '';
        if( lnf_type == 'Inquiry' )
            url = '/frontend/lnf/inquirylist';
        else    
            url = '/frontend/lnf/availablelist';

        //console.log(request);
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response.data);
                var datalist = response.data.datalist.map(function(item) {
                    if( item.found_by > 0 )
                        item.found_fullname = item.common_firstname + " " + item.common_lastname + " - Common User";
                    else
                        item.found_fullname = item.custom_firstname + " " + item.custom_lastname + " - Custom User";    

                    return item;
                });

                if(!datalist || datalist.length < 1)
                {
                    if( lnf_type == 'Inquiry' )
                        $scope.data.datalist = [];
                    else
                        $scope.data.datalist1 = [];

                    return;
                }

                for(var index = 0; index < datalist.length; index++){
                    var item = datalist[index];

                    var images = item.images;
                    if(images){
                        datalist[index].images_arr =  datalist[index].images.split("|");
                    }   
                    else                 
                        datalist[index].images_arr = [];
                }
                paginationOptions.totalItems = response.data.totalcount;
                if( paginationOptions.totalItems < 1 )
                    paginationOptions.countOfPages = 0;
                else
                    paginationOptions.countOfPages = parseInt((paginationOptions.totalItems - 1) / paginationOptions.pageSize + 1);

                if( paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt((paginationOptions.totalItems - 1) / paginationOptions.pageSize + 1);

                if( lnf_type == 'Inquiry' )
                    $scope.data.datalist = datalist;                
                else    
                    $scope.data.datalist1 = datalist;
                    
                calculateMatchedTag();    
                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    };

    function calculateMatchedTag() {
        // find selected inquiry list item's tag
        var inquiry_tag_list = [];
        $scope.data.datalist.filter(item => item.selected).forEach(item => {
            item.tags.split(',').forEach(item1 => {
                inquiry_tag_list.push(item1);
            });
        });

        if( inquiry_tag_list.length > 0 )
        {
            $scope.data.datalist1.forEach(item => {
                item.matched_tags = item.tags.split(',').filter(item1 => inquiry_tag_list.includes(item1)).join(',');
                item.non_matched_tags = item.tags.split(',').filter(item1 => !inquiry_tag_list.includes(item1)).join(',');
                item.tags = '';
            });
        }
        

        console.log(inquiry_tag_list);
    }


    $scope.onPrevPage = function(paginationOptions, num) {
        if( paginationOptions.numberOfPages <= 1 )
            return;

        paginationOptions.numberOfPages = paginationOptions.numberOfPages - 1;

        paginationOptions.pageNumber = (paginationOptions.numberOfPages - 1) * paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
        if(num == 0)
            $scope.pageChanged();
        if(num == 1)
            $scope.pageChanged1();    
    }

    $scope.onNextPage = function(paginationOptions, num) {
        if( paginationOptions.totalItems < 1 )
            paginationOptions.countOfPages = 0;
        else
            paginationOptions.countOfPages = parseInt((paginationOptions.totalItems - 1) / paginationOptions.pageSize) + 1;

        if( paginationOptions.numberOfPages >= paginationOptions.countOfPages )
            return;

        paginationOptions.numberOfPages = paginationOptions.numberOfPages + 1;
        paginationOptions.pageNumber = (paginationOptions.numberOfPages - 1) * paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
        if(num == 0)
            $scope.pageChanged();
        if(num == 1)
            $scope.pageChanged1();    
    }

    $scope.loadFiltersValue = function(value,query) {

        console.log($scope.search_tags);
        var search_items = [];
        for(var i = 0 ; i < $scope.search_tags.length; i++)
        {
            if($scope.search_tags[i].toLowerCase().indexOf(query.toLowerCase()) != -1)
                search_items.push($scope.search_tags[i]);
        }

        console.log(search_items);
        return search_items;
    }

    $scope.data.suggest_flag = true;
    $scope.onChangeSuggest = function() {
        $scope.pageChanged1();
    }

    $scope.onClickInquiry = function(row) {        
        $scope.pageChanged1();        
    }

    $scope.onClickFound = function(row) {        
        if( row.selected == false )
            return;
        $scope.data.datalist1.forEach(item => {
            if(item.selected == true && item.item_id != row.item_id )
                item.selected = false;
        });
    }

    $scope.onMatchItem = function() {
        var inquired_str = $scope.data.datalist.filter(item => item.selected ).map(item => item.item_id).join(',');
        var found_str = $scope.data.datalist1.filter(item => item.selected ).map(item => item.item_id).join(',');

        if( inquired_str.length < 1 || found_str.length < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select item at least');
            return;
        }

        var request = {};
        request.inquired_str = inquired_str;
        request.found_str = found_str;

        $http({
            method: 'POST',
            url: '/frontend/lnf/matchitems', //getLnf
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code == 200 )
                {
                    $scope.$emit('onCreateNewLnf');                    
                }
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }
});

