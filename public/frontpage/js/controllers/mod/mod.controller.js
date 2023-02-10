app.controller('ModController', function ($scope, $window, AuthService, $http, $rootScope, $timeout, blockUI ) {

    $scope.tab_full_height = 'height: ' + ($window.innerHeight - 108) + 'px; overflow-y: auto';
    $scope.table_container_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto';

    $scope.active = 1;
//      var ua = $window.navigator.appName;
    $scope.flags=0;
    $scope.selectedItems = [];

    $scope.auth_svc = AuthService;

    var filter_param = undefined;

    $rootScope.$on("addSelectedLogs", function (event, row) {
        $scope.addItemToSelectedLogs(row);
    });

    $rootScope.$on("removeItemFromSelectedLogs", function (event, $removeId) {

        $scope.removeItem($removeId);
    });

    if(navigator.appVersion.indexOf('Trident') === -1)
    {
        if(navigator.appVersion.indexOf('Edge') != -1)
        {
            //alert("Edge");
            $scope.flags=1;
        }
    }
    else
    {
        //alert("IE 11");
        $scope.flags=1;
    }

    $scope.indexname = 'mng_checklist';
    var permission = $rootScope.globals.currentUser.permission;
    for(var i = 0; i < permission.length; i++)
    {
        if( (permission[i].name).indexOf('app.reports.') != -1) {
            var cur_name =permission[i].name;
            var start_length = 'app.reports'.length+1;
            var last_length = cur_name.length;
            var name = permission[i].name.substring(start_length, last_length);
            $scope.indexname = name;
            break;
        }
    }

    $scope.getLogsNumber = function(row) {
        return sprintf('C%05d', row.id);
    };

    $scope.checkExistStatus = function(row) {
        let bResult = false;
        for (let i = 0; i < $scope.selectedItems.length; i++) {
            if (row.id === $scope.selectedItems[i].id) {
                bResult = true;
                break;
            }
        }

        return bResult;
    }

    $scope.addItemToSelectedLogs = function(row) {

        $timeout(function () {
            if ($scope.checkExistStatus(row)) {
                return;
            } else {
                $scope.selectedItems.push(angular.copy(row));
            }
        }, 100);
    };

    $scope.removeItem = function(selectLogId) {

        $timeout(function () {
            let i = 0;
            while (i < $scope.selectedItems.length) {
                if ($scope.selectedItems[i].id === selectLogId) {
                    $scope.selectedItems.splice(i, 1);
                } else {
                    i++;
                }
            }
        }, 10);
    };

    $scope.loadContent = function(indexname) {
        $scope.indexname = indexname;
    }
});






