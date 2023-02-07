app.controller('ContractCreateController', function ($scope, $rootScope, $http, $interval, $uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Contract Request Create';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    $scope.contract = {};
    $scope.contract.added_members = [];
    $scope.added_member = {};
    $scope.datetime1 = {};
    $scope.datetime1.date = new Date();
    $scope.datetime2 = {};
    $scope.datetime2.date = new Date();
    $scope.contract.start_date = moment($scope.datetime1.date).format('YYYY-MM-DD');
    $scope.contract.end_date = moment($scope.datetime2.date).format('YYYY-MM-DD');

    $scope.$watch('datetime1.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.contract.start_date = moment(newValue).format('YYYY-MM-DD');
    });
    $scope.$watch('datetime2.date', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.contract.end_date = moment(newValue).format('YYYY-MM-DD');
    });

    $scope.onLocationSelect = function(item, model, label)
    {
        $scope.contract.apartment_no = item.id;
    }

    $scope.createContract = function(){
        var data = angular.copy($scope.contract);

        if(!data.leasor)
        {
            toaster.pop('error', MESSAGE_TITLE, ' You must input leasor name.');
            return;
        }

        if(!data.apartment_no)
        {
            toaster.pop('error', MESSAGE_TITLE, ' You must select Apartment.');
            return;
        }
        if(!data.leasor_email)
        {
            toaster.pop('error', MESSAGE_TITLE, ' You must input leasor email.');
            return;
        }

        data.property_id = profile.property_id;
        data.additional_members = JSON.stringify($scope.contract.added_members);

        console.log(JSON.stringify(data));

        $http({
            method: 'POST',
            url: '/frontend/eng/createcontract',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);

                toaster.pop('success', MESSAGE_TITLE, ' Contract has been created successfully');
                $scope.pageChanged();

            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Contract!');
            })
            .finally(function() {
            });
    }

    $scope.cancelContract = function(){
        $scope.contract = {};
    }

    $scope.addmember = function(){

        if( $scope.contract.added_members.indexOf($scope.added_member)  == -1)
        {
            if($scope.added_member.name)
            {
                $scope.contract.added_members.push($scope.added_member);
                $scope.added_member = {};
            }

        }

    }
    $scope.removeMember = function(index){

            $scope.contract.added_members.splice(index , 1);

    }

});

app.directive('myEnter', function () {
    return function (scope, element, attrs) {
        element.bind("keydown keypress", function (event) {
            if(event.which === 13) {
                scope.$apply(function (){
                    scope.$eval(attrs.myEnter);
                });

                event.preventDefault();
            }
        });
    };
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
