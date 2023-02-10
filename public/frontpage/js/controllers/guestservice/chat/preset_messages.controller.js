app.controller('ctrlPresetMessages', function ($scope, $http, AuthService, $uibModalInstance, $timeout, toaster, agent_id, presetMessages) {

    $scope.isSending = false;

    $scope.msgInfoList = angular.copy(presetMessages);

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };


    $scope.onSaveOrEditRow = function (row) {
        if (row.bEdit) {
            row.tempMessage = row.message;
            row.bEdit = false;
        } else {
            row.tempMessage = row.message;
            row.bEdit = true;
        }
    };

    $scope.onDeleteOrCancel = function (row, index) {
        if (row.bEdit) { // cancel
            if (!row.tempMessage) {
                // delete
                $scope.msgInfoList.splice(index, 1);
            } else {
                // show delete modal
                row.message = row.tempMessage ? row.tempMessage : '';
                row.bEdit = false;
            }
        } else { // delete
            if (!row.tempMessage) {
                // delete
                $scope.msgInfoList.splice(index, 1);
            } else {
                // show delete modal
                alert('show delete confirm modal');
            }
        }
    };

    $scope.onAddMessage = function () {
        let tempRow = {
            id: 0,
            message: '',
            bEdit: true
        };

        $scope.msgInfoList.push(tempRow);

        $timeout(function () {
            document.getElementById('preset-table-wrapper').scrollTop = document.getElementById('preset-table-wrapper').scrollHeight;
        }, 100);
    };

    $scope.onSave = function () {
        $scope.msgInfoList = $scope.msgInfoList.map(msgInfo => {
            return {
                id: msgInfo.id,
                message: msgInfo.message
            };
        });

        let request = {
            agent_id,
            preset_messages: $scope.msgInfoList
        };

        $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/savepresetmessages',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            toaster.pop('success', 'Notification', 'Successfully saved!');
            $uibModalInstance.close(response.data);
        }).catch(function (response) {
            toaster.pop('error', 'Error', 'Failed to get Chat History.');
        })
            .finally(function () {
                $scope.isLoading = true;
            });
    };

});

