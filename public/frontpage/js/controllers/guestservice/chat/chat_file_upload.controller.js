app.controller('ctrlChatFileUpload', function ($scope, $http, AuthService, $uibModalInstance, liveserver, socket, toaster, current_session, profile) {

    $scope.fileName = '';
    $scope.file = null;

    $scope.isLoading = false;
    $scope.isSending = false;

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };

    function getFileNameAndExe(filename) {
        let tempArr = filename.split(".");

        let type = '';

        if (tempArr.length > 1) {
            type = tempArr.pop();

            return {
                name: tempArr.join("."),
                type
            };
        }

        return {
            name: filename,
            type
        };
    }

    function getChatTypeFromFileName(filename) {
        let fileInfo = getFileNameAndExe(filename);

        let result = 'document';
        let fileType = fileInfo.type;
        if (['mp4', 'avi'].includes(fileType)) {
            result = 'video';
        } else if (['mp3', 'ogg', 'wav'].includes(fileType)) {
            result = 'audio';
        } else if (['jpg', 'png', 'svg'].includes(fileType)) {
            result = 'image';
        }

        return result;
    }

    $scope.onSendAttachment = function(data) {

        $scope.isSending = true;

        let msg = {};

        msg.property_id = profile.property_id;
        msg.session_id = current_session.id;
        msg.agent_id = profile.id;
        msg.guest_id = current_session.guest_id;
        msg.lang_code = current_session.lang_code;
        msg.agent_name = profile.first_name + ' ' + profile.last_name;

        msg.text = data.filename;
        msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
        msg.direction = 1;  // outgoing

        // add new infos
        msg.mobile_number = current_session.mobile_number;
        msg.guest_type = current_session.guest_type;

        msg.chat_type= getChatTypeFromFileName(data.filename);
        msg.attachment = data.path;
        msg.room = $scope.current_session.room;
        msg.language_name = $scope.current_session.language_name;

        msg.media_id = data.media_id;

        let fileInfo = getFileNameAndExe(data.filename);
        msg.filename = fileInfo.name;


        $http({
            method: 'POST',
            url: '/frontend/guestservice/sendattachmentfromagent',
            data: msg,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                if (response.data.success == true) {
                    $uibModalInstance.close(msg);
                } else {
                    toaster.pop('error', 'Error', 'There are some error in sending...');
                }

            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
                $scope.isSending = false;
            });
    };

    $scope.onUpload = function() {
        if (!$scope.file) {
            return;
        }

        var headers = {
            "Content-Type":     undefined
        };

        $scope.isUploading = true;

        let uploadAttachmentUrl = liveserver.api + 'sendMedia';
        // upload
        let formData = new FormData();
        formData.append("whatsappfile", $scope.file);
        $http.post(uploadAttachmentUrl, formData, {
            transformRequest: angular.identity,
            accept: '*/*',
            headers: headers
        })
            .success(function(response){
                if (response.success == true) {
                    $scope.onSendAttachment(response.data);
                } else {
                    toaster.pop('error', 'Error', response.error);
                }
            })
            .error(function(error){
                toaster.pop('error', 'Error', error.message);
            })
            .finally(function () {
                $scope.isUploading = false;
            })
        ;
    };

    $scope.uploadFiles = function (files) {
        if (files.length > 0) {
            $scope.file = files[0];
            $scope.fileName = $scope.file.name;
        }
    };
});

