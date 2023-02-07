app.controller('CampaignEditController', function ($scope, $rootScope, $http, $interval, $timeout, $httpParamSerializer, AuthService, toaster, Upload) {
    var MESSAGE_TITLE = 'Campaign Create';

    var MESSAGE_TITLE = 'Complaint Status';
    var INCOMP = ' INCOMPLETE';

    var profile = AuthService.GetCredentials();    
    	
	$scope.type_list = ['Birthday', 'Anniversary', 'Holiday', 'Other'];
    $scope.send_to_list = ['Address Book', 'Upload Excelsheet', 'Manually'];
    $scope.periodic_list = ['Pre Deliver', 'Immediately', 'Periodic'];
    
    $scope.init = function(campaign) {        
        initCampaign(campaign);
        getAddressbookList();
        getUserList();
        getReceipientList();
    }

    $scope.$on('$destroy', function() {
        
    });

    $scope.guest_error_list = [];

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().format('YYYY-MM-DD'),
        endDate: moment().add(45,'d').format('YYYY-MM-DD')
    };

    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();

        $scope.opened = true;
    };

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };


    $scope.select = function(date) {
        console.log(date);

        $scope.opened = false;
    }

    $scope.onChangeType = function() {
        switch($scope.campaign.type) {
            case 'Birthday':
            case 'Anniversary':
                $scope.send_to_list = ['Address Book', 'Upload Excelsheet'];                
                break;
            case 'Holiday':
            case 'Other':
                $scope.send_to_list = ['Address Book', 'Upload Excelsheet', 'Manually'];                                
                break;
        }       

        $scope.campaign.send_to = $scope.send_to_list[0];

        switch($scope.campaign.type) {
            case 'Birthday':
            case 'Anniversary':                
            case 'Holiday':
                $scope.periodic_list = ['Pre Deliver'];            
                break;            
            case 'Other':
                $scope.periodic_list = ['Immediately', 'Periodic'];
                break;
        }       

        $scope.campaign.periodic = $scope.periodic_list[0];     
    }
    
    function initCampaign(campaign) {
        $scope.campaign = angular.copy(campaign);

        $scope.campaign.active = $scope.campaign.active == 'true';
        $scope.campaign.sms_flag = $scope.campaign.sms_flag == 'true';
        $scope.campaign.email_flag = $scope.campaign.email_flag == 'true';
        $scope.campaign.reject_flag = $scope.campaign.reject_flag == 'true';
        $scope.campaign.holiday = moment($scope.campaign.holiday).toDate();
        
        $scope.files = [];
        $scope.campaign.file_name = '';
        $scope.campaign.total_guest_selected = false;
        $scope.campaign.book_id = '0';
        $scope.campaign.use_html = false;

        $scope.campaign.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;
    }

    var addressbook_list = [];    
    $scope.addressbook_list = [];
    function getAddressbookList() {
        $http.get('/list/addressbook?client_id=' + profile.client_id)
            .then(function(response) {
                addressbook_list = response.data; 

                $scope.addressbook_list = angular.copy(addressbook_list);
                var create_option = {id: '0', name : '-- Create Address Book --'};
                $scope.addressbook_list.unshift(create_option);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {            
            });       
    }

    $scope.loadAddressbookFilters = function(query) {        
        return addressbook_list.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    var userlist = [];
    function getUserList() {
        var request = {};

        request.client_id = profile.client_id;

        $http({
            method: 'POST',
            url: '/frontend/addressbook/userlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            userlist = response.data;
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.loadUserFilters = function(query) {        
        return userlist.filter(function(type) {
            return type.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.uploadFiles = function (files) {
        $scope.files = files;       
        console.log(files);
        if(files && files.length > 0)
            $scope.campaign.file_name = files[0].name;
    };

    $scope.updateCampaign = function() {
        var request = angular.copy($scope.campaign);

        request.start_date = $scope.campaign.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.campaign.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        request.holiday = moment($scope.campaign.holiday).format('YYYY-MM-DD');

        $http({
            method: 'POST',
            url: '/frontend/campaign/update',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

            var data = response.data;
            if( data.code != 200 )
                return;

            var campaign = data.campaign;            
            uploadAddressbookExcel(campaign);            

        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });   
    }

    function uploadAddressbookExcel(campaign) {        
        // upload files
        if ($scope.files && $scope.files.length) {
            $scope.guest_error_list = [];

            Upload.upload({
                url: '/frontend/campaign/uploadaddressexcel',
                data: {
                    id: campaign.id,
                    book_id: $scope.campaign.book_id,
                    name: $scope.campaign.addressbook_name,
                    user_id: profile.id,
                    client_id: profile.client_id,
                    periodic: $scope.campaign.periodic,
                    files: $scope.files
                }
            }).then(function (response) {
                $scope.files = [];
                $scope.campaign.file_name = '';
                $scope.guest_error_list = response.data.guest_error_list;
                $scope.campaign.book_id = parseInt(response.data.book_id);

                $scope.pageChanged();

                getAddressbookList();
                getUserList();       

                $scope.campaign.book_tags = response.data.book_tags;         
            }, function (response) {                
                if (response.status > 0) {
                    $scope.errorMsg = response.status + ': ' + response.data;
                }
            }, function (evt) {
                $scope.progress = 
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        }
        else
            $scope.pageChanged();
    }

    $scope.onChangeGuestTotalChange = function() {
        for(var i = 0; i < $scope.guest_error_list.length; i++)
        {
            $scope.guest_error_list[i].selected = $scope.campaign.total_guest_selected;   
        }
    }

    $scope.onChangeGuest = function(row, modify_flag) {
        var request = angular.copy($scope.campaign);

        request.error_list = [];

        var error_guest = {};
        error_guest.guest_id = row.id;
        error_guest.modify_flag = modify_flag;
        error_guest.new_guest = row.new_guest;

        request.error_list.push(error_guest);        
        
        $http({
            method: 'POST',
            url: '/frontend/campaign/changeguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

            var data = response.data;
            if( data.code != 200 )
                return;

            row.error_type = 0;
            row.is_open = false;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });   
    }

    $scope.onTotalChangeGuest = function(modify_flag) {
        var request = angular.copy($scope.campaign);

        request.error_list = [];

        for(var i = 0; i < $scope.guest_error_list.length; i++)
        {
            var row = $scope.guest_error_list[i];

            if( row.selected == false )
                continue;

            if( row.error_type != 2 )
                continue;

            var error_guest = {};
            error_guest.guest_id = row.id;
            error_guest.modify_flag = modify_flag;
            error_guest.new_guest = row.new_guest;

            request.error_list.push(error_guest);        
        }
        
        $http({
            method: 'POST',
            url: '/frontend/campaign/changeguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);

            var data = response.data;
            if( data.code != 200 )
                return;

            for(var i = 0; i < $scope.guest_error_list.length; i++)
            {
                var row = $scope.guest_error_list[i];

                if( row.selected == false )
                    continue;

                if( row.error_type != 2 )
                    continue;

                row.error_type = 0;
            }

            $scope.campaign.total_is_open = false;

        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });  
    }

    $scope.cancelCampaign = function() {
        // initCampaign();
    }

    $scope.receipient_list = [];
    function getReceipientList() {
        var request = {};

        request.id = $scope.campaign.id;

        $http({
            method: 'POST',
            url: '/frontend/campaign/receipientlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.receipient_list = response.data.datalist;            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to get Receipients List.');
        })
        .finally(function() {

        });
    }

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

