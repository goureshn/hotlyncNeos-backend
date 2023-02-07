app.controller('ContractEditController', function ($scope,$window, $rootScope, $http, $interval, $uibModal, $stateParams, $httpParamSerializer, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Contract Edit';

    //var client_id = $stateParams.client_id;
    var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;
    var client_id = profile.client_id;


    $scope.contract = {};
    $scope.contract.added_members = [];
    $scope.added_member = {};
    $scope.datetime1 = {};
    $scope.datetime1.date = new Date();
    $scope.datetime2 = {};
    $scope.datetime2.date = new Date();
    $scope.contract.start_date = moment($scope.datetime1.date).format('YYYY-MM-DD');
    $scope.contract.end_date = moment($scope.datetime2.date).format('YYYY-MM-DD');

    $scope.apartment_list = [];

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

    $scope.init = function(row)
    {
        $scope.contract = row;
        $scope.contract.apartment = {};
        $scope.contract.contract_value *= 1;
        if($scope.contract.reminder == 1)
        {
            $scope.contract.reminder = true;
        }else{
            $scope.contract.reminder = false;
        }
        if(row.additional_members)
            $scope.contract.added_members = JSON.parse(row.additional_members);


        var start_date = moment($scope.contract.start_date).format('YYYY-MM-DD');
        $scope.contract.start_date = start_date;
        var end_date = moment($scope.contract.end_date).format('YYYY-MM-DD');
        $scope.contract.end_date = end_date;
    }

    $scope.$on('init', function(event, args) {
        $scope.init(args);        
    });

    $scope.onLocationSelect = function(item, model, label)
    {
        $scope.contract.apartment_no = item.id;
    }


    $scope.updateContract = function(){
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

        data.property_id = profile.property_id;        
        data.additional_members = JSON.stringify($scope.contract.added_members);

        $http({
            method: 'POST',
            url: '/frontend/eng/updatecontract',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);

                toaster.pop('success', MESSAGE_TITLE, ' Contract has been updated successfully');
                $scope.pageChanged();

            }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Contract!');
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
