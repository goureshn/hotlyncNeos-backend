app.controller('TaskCreateController', function ($scope, $http, AuthService, toaster, $uibModalInstance, $uibModal) {

    $scope.taskgroups = [];
    $scope.taskcategories = [];
    $scope.langlist = [];

    $scope.isLoading = false;

    $scope.model_data = {
        taskgroup_id: 0,
        category_id: 0,
        tasklist_name: "",
        type_id: "None",
        cost: 0,
        lang: [],
        status: false
    };

    $scope.taskgroup_name = "";
    $scope.taskcategory_name = "";

    let profile = AuthService.GetCredentials();

    function getTaskGroups() {
        let user_id = profile.id;

        $http.get('/frontend/guestservice/getsettingtaskgroups?user_id=' + user_id)
            .then(function (response) {
                $scope.taskgroups = response.data;
            });
    }

    function getTaskCategories() {
        let user_id = profile.id;
        $http.get('/frontend/guestservice/getsettingtaskcategories')
            .then(function (response) {
                $scope.taskcategories = response.data;
            });
    }

    function getUserLangList() {
        let user_id = profile.id;
        let property_id = profile.property_id;

        $http.get('/frontend/guestservice/getsettinguserlanglist')
            .then(function (response) {
                $scope.model_data.lang = response.data;
            });
    }

    function init() {
        getTaskGroups();
        getTaskCategories();
        getUserLangList();
    }

    init();

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onSave = function () {

        let request = angular.copy($scope.model_data);

        request.lang.forEach(element => {
            delete element.lang;
        });

        $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/addsettingtask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .success(function(data, status, headers, config) {
                if( data) {
                    $uibModalInstance.close('ok');
                }
            })
            .error(function(data, status, headers, config) {
                toaster.pop('error', 'Error', 'Error');
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onSelectTaskgroup = function($item, $model, $label) {
        $scope.model_data.taskgroup_id = $item.id;
    };

    $scope.onSelectTaskCategory = function ($item) {
        $scope.model_data.category_id = $item.id;
    };
});
