define(['app', 'datatables.net', 'datatables.net-bs', 'directives/directive'],
    function (app) {
        app.controller('ContractCtrl', function ($scope, $compile, $timeout, $window, $http /*$location, $http, initScript */) {

            $scope.model_data = {};
            $scope.full_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';

            $timeout( initDomData, 0, false );

            //edit permission check
            var permission = $scope.globals.currentUser.permission;
            $scope.edit_flag = 0;
            for(var i = 0; i < permission.length; i++)
            {
                if( permission[i].name == "access.superadmin" ) {
                    $scope.edit_flag = 1;
                    break;
                }
            }
            //end///
            //$scope.fields = ['ID', 'Property', 'Name', 'Description','Code'];
            $scope.fields = ['ID', 'Supplier', 'Contact', 'Phone','Email','Url'];

            function initDomData() {

            }


        });
    });