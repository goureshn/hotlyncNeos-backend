
app.controller('AddTemplateController', function ($scope, $rootScope, $http, AuthService, GuestService, $interval, toaster, $timeout, $uibModal, $uibModalInstance) {
    /*
               $scope.ok = function () {
            $uibModalInstance.close($scope.sub);
            };
    */

    var select_pos = {};
    select_pos.index = 0;
    select_pos.length = 0;

    $scope.profile = AuthService.GetCredentials();
    $scope.vipLevelList = [];
    $scope.roomTypeList = [];
    $scope.chatNameList = [];

    $scope.isLoading = false;

    $scope.selectInfo = {
        chatName: '',
        roomTypes : [],
        vipLevels: [],
        template: ''
    };

    $scope.getInitListForChat = function() {
        $http({
            method: 'GET',
            url: '/frontend/guestservice/getinitinfofortemplate',
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.chatNameList = response.data.chatNameList;
                $scope.roomTypeList = response.data.typeList;
                $scope.vipLevelList = response.data.vipLevelList;
            }).catch(function(response) {
        })
            .finally(function() {
            });
    };

    var getInitLoad = function() {
        $scope.getInitListForChat();
    };

    getInitLoad();

    $scope.onCancel = function () {
        // window.alert($scope.feedback.choice);
        $uibModalInstance.dismiss();
    };

    $scope.saveTemplateData = function() {
        var request = {};
        request.property_id = $scope.profile.property_id;
        request.vipLevelIds = $scope.selectInfo.vipLevels.map(item => {
            return item.id;
        });
        request.roomTypeIds = $scope.selectInfo.roomTypes.map(item => {
            return item.id;
        });

        request.chatName = $scope.selectInfo.chatName;

        request.template = $scope.selectInfo.template;

        $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/savetemplatedata',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if (response.data.success == true) {
                    $uibModalInstance.close(null);
                } else {
                    toaster.pop('error', "Notice", 'There are some errors in adding...');
                }
            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onSave = function () {

        if (!$scope.chatNameList.includes($scope.selectInfo.chatName)) {
            let modalInstance = $uibModal.open({
                templateUrl: 'tpl/guestservice/chat_template/modal/confirm_create.html',
                backdrop: 'static',
                size: 'sm',
                scope: $scope,
                resolve: {
                    chatName: function () {
                        return $scope.selectInfo.chatName;
                    }
                },
                controller: function ($scope, $uibModalInstance, chatName) {

                    $scope.chatName = chatName;

                    $scope.onYes = function () {
                        $uibModalInstance.close('Yes');
                    };
                    $scope.onNo = function () {
                        $uibModalInstance.close('No');
                    };
                },
            });

            modalInstance.result.then(function (result) {
                if (result === 'Yes') {
                    $scope.saveTemplateData();
                } else {
                    $scope.selectInfo.chatName = '';
                    toaster.pop('warning', "Notice", 'Please select chat name.');
                    return;
                }
            }, function () {

            });
        } else {
            $scope.saveTemplateData();
        }
    };

    $scope.onRoomTypeFilter = function(query) {
        return $scope.roomTypeList.filter(item => item.type.toLowerCase().includes(query.toLowerCase()));
    };

    $scope.onVipLevelFilter = function(query) {
        return $scope.vipLevelList.filter(item => item.name.toLowerCase().includes(query.toLowerCase()));
    };

    $scope.onRoomTypeChanged = function(type) {
        if (type === 'add') {
            $scope.selectInfo.roomTypes = $scope.selectInfo.roomTypes.filter((item) => {
                return item.id ? true : false;
            });
        }
    };

    $scope.onVipLevelChanged = function(type) {
        if (type === 'add') {
            $scope.selectInfo.vipLevels = $scope.selectInfo.vipLevels.filter((item) => {
                return item.id ? true : false;
            });
        }
    };

    $scope.reduceVipLevels = function() {
        let curVipIds = $scope.vipLevelList.map((item) => {
            return item.id;
        });

        $scope.selectInfo.vipLevels = $scope.selectInfo.vipLevels.filter(item => {
            if (curVipIds.includes(item.id)) {
                return true;
            } else {
                return false;
            }
        });
    };


    $scope.onSelectRoomType = function ($item, $model, $label) {
        $scope.selectInfo.roomType = $item;
    };

    $scope.loadVipLevelFilter = function (query) {
        return $scope.vipLevelList.filter(item => item.name.toLowerCase().includes(query.toLowerCase()));
    };

    $scope.loadChatNameFilter = function (query) {
        return $scope.chatNameList.filter(item => item.name.toLowerCase().includes(query.toLowerCase()));
    };
});

