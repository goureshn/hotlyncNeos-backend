app.controller('SendEmailCtrl', function ($scope, $uibModalInstance, $uibModal, $http, toaster, curUserId, curSessionId, property_id, mobile_number) {
    $scope.isLoading = false;

    $scope.activeUsers = [];
    $scope.selectedUsers = [];

    $scope.active_users_hint = {buttonDefaultText: 'Select Users'};

    $scope.acitve_users_events = {
        onDeselectAll: function () {
        },
        onSelectAll: function () {
        },
        onItemSelect: function () {
        },
        onItemDeselect: function () {
        }
    };

    $scope.active_users_setting = {
        keyboardControls: true,
        scrollable: true,
        scrollableHeight: 400,
        enableSearch: true,
        checkBoxes: true,
        smartButtonTextConverter: function (itemText) {
            return itemText;
        }
    };

    function intGetActiveUsers() {

        $scope.isLoading = false;

        let request = {};
        request.cur_user_id = curUserId;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getactiveusers',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                $scope.activeUsers = response.data;

                if ($scope.activeUsers < 1) {
                    toaster.pop('warning', 'Warning', 'There is no users.');
                }
            }).catch(function (err) {
            toaster.pop('error', "Error", err.message)
        })
            .finally(function () {
                $scope.isLoading = false;
            });
    }

    $scope.onSendEmailToUsers = function () {

        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat/modal_confirm.html',
            size: 'sm',
            resolve: {
                mobile_number: function () {
                    return mobile_number
                }
            },
            controller: function ($scope, $uibModalInstance, $http, mobile_number) {
                $scope.title = 'Are you sure to send ' + mobile_number + ' chat transript?';

                $scope.onYes = function () {
                    $uibModalInstance.close('yes');
                };

                $scope.onNo = function () {
                    $uibModalInstance.dismiss();
                }
            }
        });

        modalInstance.result.then(function (res) {
            if (res === 'yes') {
                $scope.isLoading = true;
                let request = {};
                request.users = $scope.selectedUsers;
                request.session_id = curSessionId;
                request.property_id = property_id;

                $http({
                    method: 'POST',
                    url: '/frontend/guestservice/sendemailtousers',
                    data: request,
                    headers: {'Content-Type': 'application/json; charset=utf-8'}
                }).then(function (response) {
                    let data = response.data;
                    if (data.success == true) {
                        toaster.pop('success', "Success", "Successfully sent to users.");
                        $uibModalInstance.dismiss();
                    } else {
                        toaster.pop('error', "Error", data.message);
                    }
                }).catch(function (err) {
                    toaster.pop('error', 'Error', err.message);
                }).finally(function () {
                    $scope.isLoading = false;
                });
            }
        }, function () {

        });
    };

    $scope.onSend = function () {
        if ($scope.selectedUsers.length < 1) {
            toaster.pop('warning', 'Notification', 'Please select users.');
            return;
        }

        $scope.onSendEmailToUsers();
    };
    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };

    intGetActiveUsers();
});
