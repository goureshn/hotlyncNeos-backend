'use strict';

app.controller('GuestLoginController', function($scope, $rootScope, $http, $interval, $stateParams, $state, toaster, AuthService) {
    var MESSAGE_TITLE = 'Guest Login';


    var property_id = $stateParams.property_id;
    $scope.property = {};
    $scope.room_list = [];
    $scope.guest = {};

    $scope.guest.room = '';
    $scope.guest.room_id = 0;
    $scope.guest.guest_name = '';
    $scope.guest.language = 'en';

    $http.get('/guest/roomlist?property_id='+property_id)
         .then(function(response) {
            $scope.property = response.data.property;           
            $scope.room_list = response.data.room_list;           
            if( !$scope.property ) {
                toaster.pop('info', MESSAGE_TITLE, 'Invalid URL');
            }
        });

    $scope.onRoomSelect = function($item, $model, $label) {
        $scope.guest.room = parseInt($item.room);
        $scope.guest.room_id = $item.id;        
    }

    function getLanguageList() {
        $scope.guest.language = 'en';
        $http.get('/list/languagelist')
            .then(function(response) {
                $scope.language_list = response.data;
                $scope.guest.language = 'en';
            });
    }
    getLanguageList();

    $scope.login = function() {
        $http.post('/guest/login', { room_id: $scope.guest.room_id, guest_name: $scope.guest.guest_name, language: $scope.guest.language })
            .then(function (response) {
                console.log(response);
                var data = response.data;

                if( data.code != 200 )
                {
                    toaster.pop('error', MESSAGE_TITLE, data.message);
                    return;
                }

                data.guest.property_id = property_id;
                
                AuthService.SetCredentials(data.guest);   

                $rootScope.$broadcast('success-login', data.guest);

                $state.go('app.first');

            }).catch(function(response) {
            })
            .finally(function() {

            });
    }

    $scope.page = function(param) {
        $rootScope.$broadcast('go-page', param);
    }
});