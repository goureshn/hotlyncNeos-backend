app.controller('LNFDetailController', function ($scope,$location,$anchorScroll, $http, $uibModal, $window, $httpParamSerializer, AuthService, toaster, Upload, liveserver, ) {
    var MESSAGE_TITLE = 'Lost/Found Detail';
    var profile = AuthService.GetCredentials();
    

    $scope.lnf_type_list = ['Inquiry','Found'];
    $scope.lnf_statuses = [
        'Available',
        'Matched',        
        'Returned',
        'Discarded',
        'Disposed',        
        'Surrendered',        
    ];

    $scope.can_be_edit = 1;
    $scope.init = function(lnf) {
        $scope.lnf = angular.copy(lnf);
        $scope.lnf_item = angular.copy(lnf);
        
        console.log($scope.lnf);

        $http.get('/list/locationtotallist?client_id=' + profile.client_id)
            .then(function(response){
                $scope.location_list = response.data;      

                var sortingArr = ["Room","Property","Building","Floor","Common Area","Admin Area","Outdoor"];
                $scope.location_list.sort(function(a, b){
                    return sortingArr.indexOf(a.type) - sortingArr.indexOf(b.type);
                });       
            }); 

        $http.get('/list/lnf_datalist?client_id=' + profile.client_id)
            .then(function(response) {
                $scope.storedlocation_list = response.data.store_loc;
                $scope.itemcustomuser_list = response.data.item_user;
                $scope.itemcolor_list = response.data.item_color;
                $scope.itembrand_list = response.data.item_brand;
                $scope.itemtype_list = response.data.item_type;
                $scope.tag_list = response.data.item_tag;
                $scope.itemcategory_list = response.data.item_category;
                $scope.jobrole_list = response.data.item_jobrole;
            });    

        $http.get('/list/user?client_id=' + profile.client_id)
            .then(function(response) {
                $scope.user_list = response.data.map(item => {
                    item.fullname = "";
                    if( item.first_name )
                        item.fullname = item.first_name;

                    if( item.last_name )
                        item.fullname += item.last_name;    

                    return item;      
                });                
            });    
       
        $http.get('/frontend/lnf/getLnfItems?client_id='+profile.client_id+'&lnf_id='+$scope.lnf.lnf_id)
            .then(function(response) {
                $scope.items = response.data.datalist;
            });

        $http.get('/frontend/lnf/getLnfItemHistory?client_id='+profile.client_id+'&lnf_id='+$scope.lnf.item_id)
            .then(function(response) {
                $scope.history_items = response.data.datalist;
            });
        $http.get('/frontend/lnf/getLnfItemComment?client_id='+profile.client_id+'&lnf_id='+$scope.lnf.item_id)
            .then(function(response) {
                $scope.item_comments = response.data.datalist;
            });

        initVariable();
    };

    function initVariable() {
        $scope.lnf.lnf_date = new Date();

        $scope.lnf.received_date = new Date();

        // Found Receive
        $scope.found_by = {};
        $scope.lnf.common_lastname = $scope.lnf.common_lastname ? $scope.lnf.common_lastname : '';
        $scope.lnf.custom_lastname = $scope.lnf.custom_lastname ? $scope.lnf.custom_lastname : '';
        $scope.lnf.receiver_lastname = $scope.lnf.receiver_lastname ? $scope.lnf.receiver_lastname : '';
        

        if( $scope.lnf.found_by > 0 )
            $scope.found_by.fullname = $scope.lnf.common_firstname + ' ' + $scope.lnf.common_lastname;
        else
            $scope.found_by.fullname = $scope.lnf.custom_firstname + ' ' + $scope.lnf.custom_lastname;

        $scope.received_by = {};
        $scope.received_by.fullname = $scope.lnf.receiver_firstname + ' ' + $scope.lnf.receiver_lastname;

        // Location
        var location = {};
        location.id = $scope.lnf.location_id;
        location.name = $scope.lnf.location_name;
        location.type = $scope.lnf.location_type;

        $scope.onLocationSelect(location);

        $scope.guest = angular.copy($scope.lnf);
        if( $scope.lnf.guest_type == 2 )
            $scope.guest.guest_name = $scope.lnf.customguest_firstname + ' ' + $scope.lnf.customguest_lastname;

        $scope.lnf_item.item_tag = $scope.lnf_item.tags.split(",").filter(item => item != '');    
        $scope.lnf_item.image_url_list = [];
        if( $scope.lnf_item.images)
            $scope.lnf_item.image_url_list = $scope.lnf_item.images.split("|").filter(item => item != '').map(item => '/' + item);    

        $scope.lnf_item.files = [];    

        getMatchedList();
    }

    function getMatchedList() 
    {
        if( $scope.lnf.lnf_type == 'Found' )
            return;

        var request = {};

        request.page = 0;
        request.pagesize = 100;
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.user_id = profile.id;

        // get selection id for inquiry
        request.selected_inquiry_ids = $scope.lnf_item.item_id;
        request.suggest_flag = true;
        request.end_date = moment().format('YYYY-MM-DD');
        
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
                var datalist = response.data.datalist;
                for(var index = 0; index < datalist.length; index++){
                    var item = datalist[index];

                    var images = item.images;
                    if(images){
                        datalist[index].images_arr =  datalist[index].images.split("|");
                    }   
                    else                 
                        datalist[index].images_arr = [];
                }
                $scope.matched_list = datalist;
                    
                calculateMatchedTag();    
                
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });
    }

    function calculateMatchedTag() {
        // find selected inquiry list item's tag
        var inquiry_tag_list = [];
        $scope.lnf_item.tags.split(',').forEach(item1 => {
            inquiry_tag_list.push(item1);
        });
    
        if( inquiry_tag_list.length > 0 )
        {
            $scope.matched_list.forEach(item => {
                item.matched_tags = item.tags.split(',').filter(item1 => inquiry_tag_list.includes(item1)).join(',');
                item.non_matched_tags = item.tags.split(',').filter(item1 => !inquiry_tag_list.includes(item1)).join(',');
                item.tags = '';
            });
        }
        
        console.log(inquiry_tag_list);
    }

    $scope.$watch('lnf.lnf_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.lnf.lnf_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });
    
    $scope.$watch('lnf.received_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.lnf.received_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
        if( $view == 'day' )
        {
            var activeDate = moment().subtract(0,'days');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() <= activeDate.valueOf())
                    $dates[i].selectable = true;
                else
                    $dates[i].selectable = false;
            }
        }
        else if( $view == 'minute' )
        {
            var activeDate = moment().subtract( 5,'minute');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() <= activeDate.valueOf())
                    $dates[i].selectable = true;
                else
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.onFoundBySelect = function ($item, $model, $label) {
        $scope.found_by = $item;
        $scope.lnf.found_by = $item.id;
        $scope.lnf.custom_user = $item.id;
        $scope.lnf.user_type = $item.user_type;
    };

    // event
    $scope.onReceiverSelect = function ($item, $model, $label) {
        $scope.received_by = $item;
        $scope.lnf.received_by = $item.id;
    };


    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.guest = {};

        $scope.location = angular.copy($item);
        $scope.lnf.location_id = $item.id;
        $scope.lnf.location_type = $item.type;


        if($item.type == "Room")
        {
            var request = {};
            request.client_id = profile.client_id;
            request.property_id = profile.property_id;
            request.loc_id = $item.id;
            return $http({
                method: 'POST',
                url: '/frontend/lnf/searchguestlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response){
                console.log(response);
                var list = response.data.content.slice(0, 10);
                $scope.guest_list = list;
                if($model)
                    $scope.guest = {};  
            });
        }
        else {
            var request = {};
            request.client_id = profile.client_id;
            request.loc_id = 0;            
            return $http({
                method: 'POST',
                url: '/frontend/lnf/searchguestlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response){
                var list = response.data.content;
                $scope.guest_list = list;
            });
        }
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.guest = $item;
        $scope.lnf.guest_id = $item.guest_id;
        $scope.lnf.custom_guest = $item.id;
        $scope.lnf.guest_type = 1;
    };


    // ----------- LNF Item part --------------------------

    // Item Type
    $scope.onItemTypeSelect = function ($item, $model, $label) {
        $scope.lnf_item.type_id = $item.id;
    };

    $scope.createItemType = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'lnf_itemtype.html',
            controller: 'LnfItemTypeCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    // Item Brand
    $scope.onItemBrandSelect = function ($item, $model, $label) {
        $scope.lnf_item.brand_id = $item.id;
    };

    $scope.createItemBrand = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'lnf_itembrand.html',
            controller: 'LnfItemBrandCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itembrand_list: function () {
                    return $scope.itembrand_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    // Item Category
    $scope.onItemCatgorySelect = function($item, $model, $label) {
        $scope.lnf_item.category_id = $item.id;        
    }

    $scope.createItemCategory = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'lnf_item_category.html',
            controller: 'LnfItemCategoryCtrl',
            scope: $scope,
            size: 'lg',
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                },
                itemcategory_list: function () {
                    return $scope.itemcategory_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    // Store Location
    $scope.onStoredLocationForItemSelect = function ($item, $model, $label) {
        $scope.lnf_item.stored_location_id = $item.id;
    };

    $scope.createStoredLocation = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'lnf_storedlocation.html',
            controller: 'LnfStoredLocationCtrl',
            scope: $scope,
            resolve: {
                lnf: function () {
                    return $scope.lnf;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.loadTagFilters = function (query) {
        return $scope.tag_list.filter(function (item) {
            if (item.toLowerCase().indexOf(query.toLowerCase()) != -1)
                return item;
        });        
    }

    $scope.$watch('lnf_item.stored_date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.lnf_item.stored_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');
    });

    $scope.uploadFiles = function (files) {
        if(files.length > 0)
        {
            $scope.lnf_item.files = $scope.lnf_item.files.concat(files);
            
            $scope.lnf_item.files.forEach(item => {
                $scope.lnf_item.thumbnails = [];
                var reader = new FileReader();
                reader.onload = function (loadEvent) {
                    $scope.lnf_item.thumbnails.push(loadEvent.target.result);
                }
                reader.readAsDataURL(item);
            });
        }
    }

    $scope.removeFile = function($index) {
        $scope.lnf_item.files.splice($index, 1);
        $scope.lnf_item.thumbnails.splice($index, 1);
    }

    $scope.removeUrl = function($index) {
        $scope.lnf_item.image_url_list.splice($index, 1);
    }

    $scope.submitComment = function() {
        var data = {};
        data.user_id = profile.id;
        data.comment = $scope.comment.submit_comment;
        data.lnf_item_id = $scope.lnf_item.item_id;
        if(data.comment == undefined || data.comment == "" || data.comment == null )
        {
            toaster.pop('error', "Comment", ' Input the comment');
            return;
        }
        $http({
            method: 'POST',
            url: '/frontend/lnf/submit_comment',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.item_comments = response.data.list;

                $scope.comment.submit_comment = "";
            });
    };

    $scope.setHistoryItem = function(list)
    {
        $scope.history_items = list;
    }

    $scope.updateLnfItem = function(){

        var data1 = {};

        if($scope.lnf_item.item_tag)
            tags =  $scope.lnf_item.item_tag.map(item => item.text).join(','); 

        $scope.lnf_item.tags = tags.replace(/,\s*$/, "");

        if($scope.lnf_item.image_url_list)
            $scope.lnf_item.images =  $scope.lnf_item.image_url_list.map(item => item.substring(1)).join('|'); 

        data1.lnf = $scope.lnf;
        data1.lnf_item = $scope.lnf_item;

       
        $http({
            method: 'POST',
            url: '/frontend/lnf/update_lnf_item',
            data: data1,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {

                console.log(response.data);
                $scope.history_items = response.data.datalist;
                //$scope.setHistoryItem($scope.history_items);

                toaster.pop('info', MESSAGE_TITLE, 'Success to update Lost&Inquiry');
                $scope.$emit('onCreateNewLnf');

                uploadFiles($scope.lnf_item.item_id);

            }).catch(function (response) {
            $scope.$emit('onCreateNewLnf');
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Lost&Inquiry');
        })
            .finally(function() {
            });
    }

    function uploadFiles(item_id) {
        // upload files
        var files = $scope.lnf_item.files;
        if (files && files.length > 0) {
            Upload.upload({
                url: '/frontend/lnf/uploadfiles',
                data: {
                    item_id: item_id,
                    files: files
                }
            }).then(function (response) {
                    $scope.$emit('onCreateNewLnf');
              
            }, function (response) {
                if (response.status > 0) {
                    $scope.errorUploadMsg = response.status + ': ' + response.data;
                }
            }, function (evt) {
                $scope.upload_progress =
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        }
    }


    $scope.scrollTo = function(id) {
        $location.hash(id);
        $anchorScroll();
    }

    $scope.onDownloadPDF = function() {
        var profile = AuthService.GetCredentials();

        var filter = {};
        filter.user_id = profile.id;
        filter.id = $scope.lnf.id;
        filter.report_target = 'lnf_detail_report';        
        
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

    }
});
