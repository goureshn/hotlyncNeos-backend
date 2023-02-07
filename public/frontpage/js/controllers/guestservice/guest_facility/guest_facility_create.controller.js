app.controller('GuestFacilityCreateController', function ($scope, $http, $uibModal, $uibModalInstance, AuthService, toaster, Upload) {
    var MESSAGE_TITLE = 'Guest Facility Create';

    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;

    $scope.isLoadingCreate = false;

    function init()
    {
        $scope.repair_request = {};
        $scope.facility = {};
        $scope.facility.guest_type = 'In-House';
        $scope.facility.bmeal = $scope.breakfast[1];
       
    }

    init();

    $http.get('/list/roomlist?property_id=' + property_id)
            .then(function(response){
                $scope.roomlist = response.data;
            });


    $scope.onSelectGuestType = function ($item) {
        $scope.facility.guest_name = '';     
        $scope.facility.adult = '';
        $scope.facility.child = '';      
    };

    

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.facility.room_id = $item.id;

        $scope.facility.guest_id = 0;
        $scope.facility.profile_id = 0;
        $scope.facility.guest_name = '';        
        $scope.facility.mobile = '';
        $scope.facility.email = '';
        $scope.facility.nationality = '';
        $scope.facility.vip = 0;
        $scope.facility.adult = 0;
        $scope.facility.child = 0;
        $scope.facility.new_guest = false; 

        console.log($scope.facility.guest_type);

        if( $scope.facility.guest_type == 'In-House')   // check in user            
        {
            // find checkin user
            $http.get('/frontend/complaint/findcheckinguest?room_id=' + $item.id)
                .then(function(response){
                    var data = response.data;

                    if( data.code != 200 )
                    {
                        toaster.pop('info', MESSAGE_TITLE, 'No Checkin Guest');
                        return;
                    }

                    $scope.facility.guest_name = data.data.guest_name;
                    $scope.facility.first_name = data.data.first_name;
                    $scope.facility.arrival = data.data.arrival;
                    $scope.facility.departure = data.data.departure;
                    $scope.facility.guest_id = data.data.guest_id;
                    $scope.facility.profile_id = data.data.profile_id;
                    $scope.facility.mobile = data.data.mobile;
                    $scope.facility.email = data.data.email;
                    $scope.facility.nationality = data.data.nationality;
                    $scope.facility.vip = data.data.vip;
                    $scope.facility.adult = data.data.adult;
                    $scope.facility.child = data.data.chld;
                });
        }        
    };
      
    $scope.createFacility = function(){
        var data = angular.copy($scope.facility);

        if ($scope.facility.bmeal == 'No')
             $scope.facility.bmeal = 0;
        else
            $scope.facility.bmeal = 1;

        if (!(data.guest_name)  ){
            toaster.pop('error', MESSAGE_TITLE, 'Please input Guest Name');
            return;
        }

        if (!(data.adult)  ){
            toaster.pop('error', MESSAGE_TITLE, 'Please input no of Adults');
            return;
        }

        if (!(data.table)  ){
            toaster.pop('error', MESSAGE_TITLE, 'Please select Table Number');
            return;
        }

    
        
        $http({
            method: 'POST',
            url: '/frontend/guestservice/createguestfacility',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);

                if (response.data.code == '203'){
                    toaster.pop('error', MESSAGE_TITLE, response.data.message);
                }else{
                   
                    toaster.pop('success', MESSAGE_TITLE, ' Details has been added successfully');

                    $uibModalInstance.close();
                    
                }
              
                $scope.pageChanged();

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to add Details!');
            })
            .finally(function() {
            });
    }
    

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }
});



app.directive('myEsc', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 27) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEsc);
                });

                event.preventDefault();
            }
        });
    };
});


