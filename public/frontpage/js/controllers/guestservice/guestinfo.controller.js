app.controller('GuestinfoController', function ($scope, $rootScope, $http, $window, $uibModal, $timeout, AuthService, toaster, $location, $httpParamSerializer) {
    var MESSAGE_TITLE = 'Guest Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 160) + 'px; overflow-y: auto;';
    $scope.tableState = undefined;
    var search_option = '';
    $scope.checkout_states = [
        'All',
        'Checkin',
        'Checkout',
    ];
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.guest_logs = [];
    
    
    $http.get('/backoffice/property/wizard/buildlist?property_id='+property_id)
        .success(function(response){            
            $scope.buildings = response;            

            if($scope.buildings.length>1)
                $scope.multiple_flag=1;
            else
                $scope.multiple_flag=0;

            $scope.buildings.unshift({id:0, property_id:property_id, name:"Select Building","description":""});
        });
    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45, 'd').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    angular.element('#dateranger').on('apply.daterangepicker', function (ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        onDownloadExcel();
    });

    $scope.onClickDateFilter = function () {
        angular.element('#dateranger').focus();
    }
    $scope.checkout_flag = $scope.checkout_states[0];
    function onDownloadExcel () {
        var profile = AuthService.GetCredentials();

        var filters = {};

        filters.filter = filter;
        filters.user_id = profile.id;
        filters.report_by = 'Facilities';
        filters.report_type = 'Detailed';
       // filters.report_target = 'it_summary';
        var profile = AuthService.GetCredentials();
        filters.property_id = profile.property_id;
        filters.start_time = $scope.daterange.substring(0, '2016-01-01'.length);
        filters.end_time = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        //filter.filter_value = $scope.filter_value;

        $window.location.href = '/frontend/report/guestfacilities_excelreport?' + $httpParamSerializer(filters);
    }

	 function isSmartDevice( $window )
    {
        // Adapted from http://www.detectmobilebrowsers.com
        var ua = $window['navigator']['userAgent'] || $window['navigator']['vendor'] || $window['opera'];
        // Checks for iOs, Android, Blackberry, Opera Mini, and Windows mobile devices
        return (/iPhone|iPod|iPad|Silk|Android|BlackBerry|Opera Mini|IEMobile/).test(ua);
    }
   
    

    $scope.mobile_flag = isSmartDevice( $window );
    $scope.standalone= function() {
	    if($location.absUrl().search('/hotlyncfacilities')==1)
	    {
		    //window.alert($location.absUrl());
		    return 1;
	    }
	    return 0;
	    
	    };
	 $scope.stand_flag= $scope.standalone();
	 //window.alert($scope.stand_flag);
    // pip
    $scope.isLoading = false;
    $scope.alarmlist = [];

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 22,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.viewLogs = function(guest) {

        var modalInstance = $uibModal.open({
            templateUrl: 'guest_logs.html',
            controller: 'GuestLogsCtrl',
            windowClass: 'custom-modal-window',
            resolve: {
                guest: function () {
                    return guest;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    var filter = 'Total';
    $scope.onFilter = function getFilter(param) {
        filter = param;
        $scope.getGuestList();
    }
    
    
    
    $scope.getGuestList = function getAlarmList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }


        var request = {};
        request.page = $scope.paginationOptions.pageNumber;//($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter = filter;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.checkout_flag = $scope.checkout_flag;
        request.searchoption = search_option;

        $http({
                method: 'POST',
                url: '/frontend/guestservice/guestlist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.guestlist = response.data.guestlist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
/*
                else if( tableState == undefined )
                    $scope.tableState.pagination.numberOfPages = numberOfPages;
*/
                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    //confirm SMS after send sms
    $scope.$on('guestdetail', function(event, args) {
        console.log(args);
        for(var i = 0; i < $scope.guestlist .length; i++ )
        {
            if( $scope.guestlist[i].id == args.notify_id)
            {
                //if ack is 2 on first time when connect new guest from opera.
                if($scope.guestlist[i].ack != 2) {
                    $scope.guestlist[i].ack = args.ack;
                }
                break;
            }
        }
    });
    
        $scope.onPrevPage = function() {
        if( $scope.paginationOptions.numberOfPages <= 1 )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages - 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getGuestList();
    }

    $scope.onNextPage = function() {
        if( $scope.paginationOptions.totalItems < 1 )
            $scope.paginationOptions.countOfPages = 0;
        else
            $scope.paginationOptions.countOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize) + 1;

        if( $scope.paginationOptions.numberOfPages >= $scope.paginationOptions.countOfPages )
            return;

        $scope.paginationOptions.numberOfPages = $scope.paginationOptions.numberOfPages + 1;
        $scope.paginationOptions.pageNumber = ($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.

        $scope.isLoading = true;
        $scope.getGuestList();
    }
    
    $scope.getDate = function(row) {
        return moment(row.time).format('YYYY-MM-DD');
    }

    $scope.getTime = function(row) {
        return moment(row.time).format('h:mm::ss a');
    }

    $scope.onChangeCheckout = function() {
        $scope.getGuestList();
    }

    $scope.viewDetail = function (guest) {
        var modalInstance = $uibModal.open({
            templateUrl: 'guest_detail.html',
            controller: 'GuestDetailCtrl',
            size: 'lg',
            windowClass: 'app-modal-window',
            resolve: {
                guest: function () {
                    return guest;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };
    $scope.addUtil= function (guest) {
	    
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/modal/guest_util_create.html',
            controller: 'GuestUtilCtrl',
            windowClass: 'app-modal-window',
            resolve: {
                guest: function () {
                    return guest;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    };


    $scope.onSwapDatabase = function() {
        var message = {};

        message.title = 'Database swap';
        message.content = 'Do you want to swap database?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/modal/dbswap_modal.html',
            controller: 'DBSwapConfirmCtrl',
            resolve: {
                buildings: function () {
                    return $scope.buildings;
                }      
            }
        });

        modalInstance.result.then(function (ret) {
            $scope.swapDatabase(ret);            
        }, function () {

        });
    }

    $scope.swapDatabase = function(building_id) {
        if( !(building_id > 0) )
            return;

        var data = {};

        var profile = AuthService.GetCredentials();
        data.property_id = profile.property_id;
        data.building_id = building_id;

        $rootScope.myPromise = $http({
            method: 'POST',
            url: '/interface/process/databaseswapfromhotlync',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
     $scope.$on('guestList', function(){
        $scope.getGuestList();
        });

    $scope.searchtext = '';
    $scope.onSearch = function() {
        search_option = $scope.searchtext;
        $scope.paginationOptions.pageNumber = 0;
        $scope.tableState.pagination.start = 0;
        $scope.getGuestList();
    }

    var roomlist = [];

    function getRoomList() {
        var profile = AuthService.GetCredentials();
        $http.get('/list/roomlist?property_id=' + profile.property_id)
            .then(function(response){
                roomlist = response.data;
            });
    }
    getRoomList();

    $scope.onShowManualPosting = function (guest) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/modal/manual_posting.html',
            controller: 'ManualPostingCtrl',
            windowClass: 'app-modal-window',
            resolve: {         
                roomlist: function () {
                    return roomlist;
                },
                buildings: function () {
                    return $scope.buildings;
                }      
            }
        });

        modalInstance.result.then(function () {
            $scope.getGuestList();
        }, function () {

        });
    };

});

app.controller('GuestLogsCtrl', function($scope, $uibModalInstance, guest, AuthService, $http, toaster) {
    $scope.guest = guest;

    $scope.guest_logs = [];
    $scope.isLoading = false;
    $scope.tableState = undefined;
    var filter = "Total";

    var search_option = '';

    var profile = AuthService.GetCredentials();

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 22,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.onSearchLog = function() {
        search_option = $('#log_search_text').val();
        $scope.paginationOptions.pageNumber = 1;//($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;

        $scope.getGuestLogs();
    };

    $scope.onFilterLog = function(param) {
        filter = param;
        $scope.getGuestLogs();
    }


    $scope.getGuestLogs = function(tableState) {

       $scope.isLoading = true;
       $scope.guest_logs = [];

        if( tableState != undefined )
        {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }


        var request = {};
        request.page = $scope.paginationOptions.pageNumber;//($scope.paginationOptions.numberOfPages - 1) * $scope.paginationOptions.pageSize;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.filter = filter;

        request.property_id = profile.property_id;
        request.checkout_flag = $scope.checkout_flag;
        request.searchoption = search_option;
        request.guest_id = guest.guest_id;

        $http({
                method: 'POST',
                url: '/frontend/guestservice/getguestloglist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            })
            .then(function(response) {
                $scope.guest_logs = response.data.guestlist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
/*
                else if( tableState == undefined )
                    $scope.tableState.pagination.numberOfPages = numberOfPages;
*/
                $scope.paginationOptions.countOfPages = numberOfPages;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.ok = function() {
        $uibModalInstance.close();
    }

    $scope.getGuestLogs();

});


app.controller('GuestDetailCtrl', function($scope, $uibModalInstance, guest, AuthService, $http, toaster) {
    $scope.guest = guest;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    $scope.history = {};

    $scope.sendSMS = function () {
        var request = {};
        request = angular.copy(guest);
        request.user_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/sendguestsms',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if(response.data == '200') {
                    toaster.pop('success', "Send SMS", 'SMS sent to guest successfully.');
                }else {
                    toaster.pop('error', "Send SMS", 'SMS flag option is OFF.' );
                }
                $scope.getSMSHistory();
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }


    $scope.getSMSHistory = function () {
        var request = {};
        request = angular.copy(guest);
        request.user_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getguestsmshistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.history = response.data.history;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.getSMSHistory();

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});


app.controller('GuestUtilCtrl', function($scope, $rootScope, $uibModalInstance, guest, AuthService, $http, toaster, $interval) {
    $scope.guest = guest;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
     var client_id = profile.client_id;
     
     $scope.guest_history = {};
     
     
     $scope.init = function(){
	     
	    var request = {};
        request = angular.copy(guest);

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getguestloghistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.guest_history = response.data.history;
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
            
     }
     
      $scope.exit = function (id) { 
	      //window.alert(id);
	      var request = {};
        request.id = id;
        $http({
            method: 'POST',
            url: '/frontend/guestservice/guestexit',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
               $scope.init();
               $rootScope.$broadcast('guestList');
               
            }).catch(function(response) {
                toaster.pop('error', 'Facility log exit unsuccessful.');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
        //window.alert(guest.location);
    };
   
     //$scope.guest={};
     
    //$scope.history = {};
     $scope.guest.entry_time = moment().format('HH:mm:ss');
     //$scope.guest.exit_time = moment().format('HH:mm:ss');
     
    $http.get('/list/facilitytotallist?client_id=' + client_id)
            .then(function(response){
                $scope.location_list = response.data; 
            })
            
    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.guest.location = $item.name;
        //window.alert(guest.location);
    };

  $scope.$watch('guest.timepicker', function(newValue, oldValue) {
	  //window.alert($scope.guest.entry_time);
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.guest.entry_time = moment(newValue).format('HH:mm:ss');
    });
    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {
	     //window.alert($scope.guest.entry_time);
     if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 5);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() > activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }
    
     $scope.createEntry = function () {
	     var request = {};
	     //window.alert($scope.guest)
	     request = $scope.guest;
	     request.user_id = profile.id;
	     $http({
            method: 'POST',
            url: '/frontend/guestservice/facilitylog',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                
                    toaster.pop('success', 'Facility log successful.');
                
                $rootScope.$broadcast('guestList');
                console.log(response);
                $scope.guest.location='';
                $scope.guest.quantity_1=0;
                $scope.guest.quantity_2=0;
                $scope.guest.quantity_3=0;
                $scope.guest.comment='';
                $scope.guest.entry_time=moment().format('HH:mm:ss');
                $uibModalInstance.close();
            }).catch(function(response) {
                
                    toaster.pop('error', 'Facility log unsuccessful.' );
            })
            .finally(function() {
                $scope.isLoading = false;
            });
	   
	     
    };

    $scope.ok = function () {
	    
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});

app.controller('DBSwapConfirmCtrl', function($scope, $uibModalInstance, buildings) {    
    $scope.buildings = buildings;
    $scope.model = {};
    $scope.model.building_id = 0;
    $scope.onDBWSwap = function () {        
        $uibModalInstance.close($scope.model.building_id);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('close');
    };
});

app.controller('ManualPostingCtrl', function($scope, $uibModalInstance, $http, AuthService, toaster, roomlist,buildings) {
    $scope.guest = {};
    $scope.roomlist = roomlist;
    $scope.buildings = buildings;
    
    var profile = AuthService.GetCredentials();

    $scope.pbx_action_type_list = [
        'Checkin',
        'Checkout',
        'Guest Change',
        'Message Lamp',
        'Class Of Service',
        'Do Not Disturb',
    ];

    $scope.value_list = [
        'ON',
        'OFF',
    ];

    $scope.pms_action_type_list = [
        'Call Charge',
        'Room Status',        
    ];

    $scope.call_type_list = [
        { id: 0, name: 'Incoming'},
        { id: 1, name: 'Internal'},
        { id: 2, name: 'Missed'},
        { id: 3, name: 'Outgoing'},        
    ];

    $scope.guest.action = 'Checkin';
    $scope.guest.property_id = profile.property_id;
    $scope.guest.call_type = $scope.call_type_list[0].id;
    $scope.guest.building=$scope.buildings[0];
    $scope.guest.selected = false;
    $scope.guest.select_id = [];
    $scope.guest.value = 'ON';
    $scope.guest.profile_id = 0; 
    //$scope.guest.building_id = $scope.building.id;

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.guest.room_id = $item.id;
    }
    $scope.onSelectBuilding = function() {
        
        
        //window.alert(JSON.stringify($scope.guest.building));
        
        $scope.roomlist = roomlist.filter(function (item) {
            if (item.bldg_id == $scope.guest.building.id)
                return item;
        });
       // window.alert(JSON.stringify($scope.roomlist));
        // $scope.multiple_flag=0;
        // $scope.building=building;
    }

    $scope.getguestgroupList = function(val) {
        if( val == undefined )
            val = "";

            return $http.get('/frontend/wakeup/guestgroups?guest_group='+ val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGroupSelect = function ($item, $model, $label) {
        $scope.guest_groups = $item.guest_group;
        var data = {guest_group: $scope.guest_groups};
        $http({
            method: 'POST',
            url: '/frontend/wakeup/roomlist',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .success(function(response) {
            console.log(response);
            var from = $('#search');
                    from.empty();

                    $.each(response, function(index, element) {
                        from.append("<option value='"+ element.room +"'>" + element.room + "</option>");
                    });
        
            
        }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function() {
          
                });
        

    };

    function postPBX(save_flag)
    {
        $scope.guest.select_id = new Object();
        $scope.guest.save_flag = save_flag;
        var count = 0;
        $("#search option").each(function()
        {
            $scope.guest.select_id[count] = $(this).val();
            count++;
        });

        if($scope.guest.building.id==0)
        {
            toaster.pop('error', 'Manual Posting', "Please select a building.");
            return
        }
        
        var request = $scope.guest;
        if($scope.guest.value == 'ON') request.value = 3;
        if($scope.guest.value == 'OFF') request.value = 0;

        console.log(request);

        $http({
            method: 'POST',
            url: '/frontend/guestservice/manualpost',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function(response) {
            if( response.data.code == 200 )
            {
                toaster.pop('success', 'Manual Posting', response.data.message);
                $uibModalInstance.close('close');
            }
            else
                toaster.pop('error', 'Manual Posting', response.data.message);

            console.log(response);
        }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function() {
            
        });
    }

    $scope.onPostPBX = function () {
        postPBX(false);
    };

    $scope.onPostPBXInternal = function () {
        postPBX(true);
    };

    $scope.cancel = function () {       
        $uibModalInstance.dismiss();       
    };

    $scope.status_list = ['arrived', 'seated', 'check req', 'paid'];

    $scope.testLoading = false;
    $scope.tablecheck = {};
    $scope.tablecheck.testshopref = '5fd1bd793b3cbc0023b28952';
    $scope.tablecheck.testdate = new Date();
    $scope.tablecheck.testAuthorization = '9QNYROG1SLG98YERBM9OUZ56IDTP5D55UD1PG9BW';
    $scope.tablecheck.testrefid = '60c1d05a8be2db003c591331';
    $scope.tablecheck.status = 'arrived';

    $scope.open = function ($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        dateDisabled: disabled,
        class: 'datepicker'
    };

    function disabled(data) {
        var date = data.date;
        var sel_date = moment(date).format('YYYY-MM-DD');
        var disabled = true;
        if (moment().add(1, 'days').format('YYYY-MM-DD') <= sel_date)
            disabled = false;
        else
            disabled = true;

        mode = data.mode;
        return mode === 'day' && disabled;
    }

    $scope.select = function (date) {
        console.log(date);

        $scope.opened = false;
    }



    $scope.onLoadTableCheck = function () {


        $scope.tablecheck.testdate = moment($scope.tablecheck.testdate).format("YYYY-MM-DD");

        var data = {};

        data = $scope.tablecheck;

        $scope.testLoading = true;

        console.log(data);


        $http({
            method: 'GET',
            url: '/frontend/guestservice/tablecheck',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
    
        }).then(function(response) {
            console.log(response);
            $scope.response = response.data;

        }).catch(function(response) {
            console.log(response);
        })
            .finally(function() {
                $scope.testLoading = false;
        });
    };

    $scope.onUpdateTableCheck = function () {  

        var data = {};

        data.testrefid = $scope.tablecheck.testrefid;
        data.status = $scope.tablecheck.status;

        console.log(data);

        $scope.testLoading = true;


        $http({
            method: 'PUT',
            url: '/frontend/guestservice/tablecheckupdate',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
    
        }).then(function(response) {
            console.log(response);
            $scope.response_update = response.data;

        }).catch(function(response) {
            console.log(response);
        })
            .finally(function() {
                $scope.testLoading = false;
        });
    };

    $scope.onWalkin = function () {  

        var data = {};

        data.testadultno = $scope.tablecheck.testadultno;
        data.testduration = $scope.tablecheck.testduration;
        data.testtabname = $scope.tablecheck.testtabname;
        data.status = $scope.tablecheck.status;

        console.log(data);

        $scope.testLoading = true;


        $http({
            method: 'PUT',
            url: '/frontend/guestservice/tablecheckwalkin',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
    
        }).then(function(response) {
            console.log(response);
            $scope.response_walkin = response.data;

        }).catch(function(response) {
            console.log(response);
        })
            .finally(function() {
                $scope.testLoading = false;
        });
    };

});
