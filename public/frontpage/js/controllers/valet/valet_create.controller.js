app.controller('ValetCreateController', function ($scope, $rootScope, $http, $interval,$timeout,$uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster) {
    var MESSAGE_TITLE = 'Valet Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    var property_id = profile.property_id;
   
    $scope.valet = {};
    $scope.datetime = {};

    $scope.datetime.date = new Date();
    $scope.valet.start_date = moment($scope.datetime.date).format('YYYY-MM-DD');
    $scope.valet.end_date = moment($scope.datetime.date).format('YYYY-MM-DD');


    $scope.prioritys = [
        'Low',
        'Medium',
        'High',
        'Urgent'
    ];
    
     $scope.statuses = [
        'Pending',
        'In-Progress',
        'Parked',
        'Fetched',
        'Waiting',
        'Handed Over',
        'Rejected'
    ];
    $scope.valet.requestor_id = profile.id;
    $scope.guest_types = ['In-House CI', 'In-House CO', 'Arrival', 'Walk-in'];
    $scope.guest_list = [{guest_id: 0, guest_name: 'Select Guest'}];
    $scope.valet.priority = $scope.prioritys[0];
    $scope.valet.status = $scope.statuses[0];
    $scope.valet.guest_type = $scope.guest_types[0];
    $scope.timer = $interval(function() {
                $scope.valet.incident_time = moment().format("HH:mm:ss");
             }, 1000);
             
    $scope.$on('$destroy', function() {
        if($scope.timer != undefined) {
            $interval.cancel($scope.timer);
            $scope.timer = undefined;
        }
    });         
    

    $scope.getStaffList = function(val) {
         if( val == undefined )
            val = "";

        return $http.get('/frontend/valet/stafflist?value=' + val + '&client_id=' + client_id + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
     $scope.getSpotList = function(val) {
         if( val == undefined )
            val = "";

        return $http.get('/frontend/valet/spotlist?value=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };
    
     $scope.onSpotSelect = function ($item, $model, $label) {
      
         $scope.valet.parkspot_id = $item.id;
        // window.alert($scope.valet.staff_id);
    };
    
  
    
    $scope.onChangeGuestType = function() {
        $scope.valet.room = '';
        $scope.valet.room_id = 0;
        $scope.valet.guest_id = 0;
        $scope.valet.guest_name = '';        
        $scope.valet.departure = '';
        $scope.valet.mobile = '';
        $scope.valet.email = '';
        $scope.valet.arrival = '';
        $scope.valet.departure = '';
        $scope.valet.new_guest = false; 
      
    };
     $scope.onChangeNewGuest = function() {
        if( $scope.valet.new_guest == true )
        {
            $scope.valet.guest_name = ''
            $scope.valet.guest_id = 0;
            $scope.valet.mobile = '';
            $scope.valet.email = '';
        }
    }
    
     $scope.getGuestList = function(val) {
        if( $scope.valet.new_guest == true )
            return;

        if( val == undefined )
            val = "";

        var request = {};        
        var property_id = $scope.valet.property_id;
        request.client_id = client_id;
        request.property_id = property_id;
        request.value = val;
        request.guest_type = $scope.valet.guest_type;
        request.room_id = $scope.valet.room_id;

        return $http({
            method: 'POST',
            url: '/frontend/valet/searchguestlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
                var list = response.data.slice(0, 10);
                return list.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.valet.guest_name = $item.guest_name;    
        $scope.valet.first_name = $item.first_name;    
        $scope.valet.guest_id = $item.guest_id;        
        $scope.valet.mobile = $item.mobile;
        $scope.valet.email = $item.email;      
        $scope.valet.arrival = $item.arrival;
        $scope.valet.departure = $item.departure;
        $scope.valet.new_guest = false;        
    };
    
    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.valet.room_id = $item.id;

        $scope.valet.guest_id = 0;
        $scope.valet.guest_name = '';        
        $scope.valet.mobile = '';
        $scope.valet.email = '';
        $scope.valet.new_guest = false; 

        if( $scope.valet.guest_type == 'In-House CI')   // check in user            
        {
            // find checkin user
            $http.get('/frontend/valet/findcheckinguest?room_id=' + $item.id)
                .then(function(response){
                    var data = response.data;

                    if( data.code != 200 )
                    {
                        toaster.pop('info', MESSAGE_TITLE, 'No guest check in and change the type to CO');
                        return;
                    }

                    $scope.valet.guest_name = data.data.guest_name;
                    $scope.valet.first_name = data.data.first_name;
                    $scope.valet.arrival = data.data.arrival;
                    $scope.valet.departure = data.data.departure;
                    $scope.valet.guest_id = data.data.guest_id;
                });
        }        
    };
     $scope.getRoomList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/roomlist?room=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };



    $scope.onStaffSelect = function ($item, $model, $label) {
        $scope.valet.staff = $item.wholename;
         $scope.valet.staff_id = $item.id;
        // window.alert($scope.valet.staff_id);
    };


    $scope.createValet = function(){
        var data = angular.copy($scope.valet);
        data.property_id = profile.property_id;
        data.user_id = profile.id;
        
        $http({
            method: 'POST',
            url: '/frontend/valet/createvalet',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Valet has been created successfully');
                $scope.pageChanged();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to created valet entry');
            })
            .finally(function() {
            });
    }

    $scope.cancelValet = function(){
        $scope.valet = {};
    }
    
   

});

