app.controller('GuestChatController', function ($scope, $rootScope, $window, $http, $uibModal, $timeout, $interval, $compile, AuthService, toaster, TranslateService, socket, GuestService, DateService, liveserver) {
    let profile = AuthService.GetCredentials();

    const WAITING = 1;
    const ACTIVE = 2;
    const ENDED = 3;

    const MESSAGE_TITLE = 'Chat page';
    $scope.agent_id = profile.id;

    $scope.status_filter = [
        {
            checked: false,
            value: WAITING,
            label: 'Waiting'
        },
        {
            checked: false,
            value: ACTIVE,
            label: 'Active'
        },
        {
            checked: false,
            value: ENDED,
            label: 'Ended'
        }
    ];

    $scope.presetMessages = [];

    $scope.curSetting = {
        warning_time: 0,
        critical_time: 0,
        job_role_ids: '',
        end_chat: '',
        no_answer: '',
        accept_chat: ''
    };

    $scope.$on("changed_auth_status", function (evt, data) {
        $rootScope.$broadcast('call_transfer_agent', data);
    });

    $scope.is_downloading_pdf = false;

    function getGuestChatSettingInfo() {
        let property_id = profile.property_id;
        $http.get('/frontend/guestservice/getguestchatsettinginfo?property_id=' + property_id)
            .then(function (response) {
                $scope.curSetting.warning_time = parseInt(response.data.warning_time);
                $scope.curSetting.critical_time = parseInt(response.data.critical_time);
                $scope.curSetting.job_role_ids = response.data.job_role_ids;
                $scope.curSetting.end_chat = response.data.end_chat;
                $scope.curSetting.no_answer = response.data.no_answer;
                $scope.curSetting.accept_chat = response.data.accept_chat;
            });
    }

    $rootScope.$on('guestchat_setting_updated', function (event, args) {
        $scope.curSetting = angular.copy(args);
    });

    getGuestChatSettingInfo();

    let cur_index = 0;

    $scope.onFilterReset = function () {
        $scope.dateRangeOption = {
            format: 'YYYY-MM-DD',
            startDate: moment().subtract(1, 'd').format('YYYY-MM-DD'),
            endDate: moment().format('YYYY-MM-DD')
        };

        $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

        $scope.agent_tags = [];
        $scope.room_tags = [];

        $scope.status_filter = [
            {
                checked: false,
                value: WAITING,
                label: 'Waiting'
            },
            {
                checked: false,
                value: ACTIVE,
                label: 'Active'
            },
            {
                checked: false,
                value: ENDED,
                label: 'Ended'
            }
        ];
    };


    function initChatSessionFilter() {

        $scope.filter_is_open = false;

        $scope.onFilterReset();
    }

    $scope.search_text = "";

    initChatSessionFilter();

    function getGuestTaskItemList() {
        let request = {};
        request.property_id = profile.property_id;
        request.type = 1;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/tasklist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.task_list_item = response.data;
        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
            });
    }

    getGuestTaskItemList();


    $scope.refresh = $interval(function () {
        if ($scope.chat_session_list !== undefined) {
            $scope.chat_session_list.forEach(chat_session_info => {
                let diff_time = 0;
                if (chat_session_info.status === 'Waiting') {
                    chat_session_info.duration = '00:00:00';
                    chat_session_info.start_time = '--:--';
                    diff_time = moment() - moment(chat_session_info.updated_at, "YYYY-MM-DD HH:mm:ss");
                    chat_session_info.waiting_time_seconds = diff_time / 1000;
                    chat_session_info.wait_time = moment("2015-01-01").startOf('day')
                        .seconds(chat_session_info.waiting_time_seconds)
                        .format('HH:mm:ss');
                }

                if (chat_session_info.status === 'Active') {
                    diff_time = moment() - moment(chat_session_info.start_time, "YYYY-MM-DD HH:mm:ss");
                    chat_session_info.duration = moment("2015-01-01").startOf('day')
                        .seconds(diff_time / 1000)
                        .format('HH:mm:ss');
                }
            });
        }

    }, 1000);

    $scope.onShowRecorderModal = function (selected_session) {
        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat/modal_chat_recorder.html',
            backdrop: 'static',
            size: 'md',
            scope: $scope,
            resolve: {
                current_session: function () {
                    return selected_session;
                }
            },
            controller: 'ctrlChatRecorder'
        });
    };

    $scope.onShowFileUploadModal = function (current_session) {
        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat/modal_chat_file_upload.html',
            backdrop: 'static',
            size: 'md',
            scope: $scope,
            resolve: {
                current_session: function () {
                    return current_session;
                },
                profile: function () {
                    return profile;
                }

            },
            controller: 'ctrlChatFileUpload'
        });

        modalInstance.result.then(function (msg) {
            if (msg) {
                // send to whatsapp and save to database
                $scope.messages.push(msg);
                socket.emit('agent_msg', msg);
            }
        }, function () {

        });
    };

    $scope.$on('$destroy', function () {
        if (angular.isDefined($scope.refresh)) {
            $interval.cancel($scope.refresh);
            $scope.refresh = undefined;
        }
    });

    $scope.prioritylist = [];
    GuestService.getPriorityList()
        .then(function (response) {
            $scope.prioritylist = response.data;
        });

    function onPlayNewChatRequest() {
        let audio = new Audio('/sound/chat-sound-request.wav');
        audio.play();
    }

    function onPlayGuestReply(messageInfo) {

        let agent_id = messageInfo.agent_id;

        if (agent_id == profile.id) {
            let audio = new Audio('/sound/chat-sound-reply.wav');
            audio.play();
        }
    }

    function onPlayDisconnectedStatus(messageInfo) {
        let agent_id = messageInfo.data.agent_id;

        if (agent_id == profile.id) {
            let audio = new Audio('/sound/chat-sound-ended.wav');
            audio.play();
        }
    }

    function getPresetMessages() {
        let request = {};
        request.agent_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getpresetmessages',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.presetMessages = response.data;

        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
            });
    }

    function getChatSessionList(refresh_flag) {

        let request = {};
        request.property_id = profile.property_id;
        request.status_arr = [];
        request.user_id = profile.id;

        $scope.status_filter.forEach(item => {
            if (item.checked == true) {
                request.status_arr.push(item.value);
            }
        });

        request.search_text = $scope.search_text;
        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);

        request.agent_ids = [];
        for (let i = 0; i < $scope.agent_tags.length; i++)
            request.agent_ids.push($scope.agent_tags[i].id);

        request.room_ids = [];
        for (let i = 0; i < $scope.room_tags.length; i++)
            request.room_ids.push($scope.room_tags[i].id);

        let unread_count = {};

        if ($scope.chat_session_list) {
            for (let i = 0; i < $scope.chat_session_list.length; i++)
                unread_count[$scope.chat_session_list[i].id] = $scope.chat_session_list[i].unread;
        }

        $http({
            method: 'POST',
            url: '/frontend/guestservice/chatsessionlistnew',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.chat_session_list = response.data;

            // set unread count
            for (let i = 0; i < $scope.chat_session_list.length; i++) {
                // update
                if ($scope.chat_session_list[i].status == 1) {
                    $scope.chat_session_list[i].status = 'Waiting';
                } else if ($scope.chat_session_list[i].status == 2) {
                    $scope.chat_session_list[i].status = 'Active';
                } else if ($scope.chat_session_list[i].status == 3) {
                    $scope.chat_session_list[i].status = 'Ended';
                } else if ($scope.chat_session_list[i].status == 4) {
                    $scope.chat_session_list[i].status = 'Transfer';
                }
                if (unread_count[$scope.chat_session_list[i].id])
                    $scope.chat_session_list[i].unread = unread_count[$scope.chat_session_list[i].id];
            }

            if ($scope.chat_session_list.length > 0 && refresh_flag === true) {
                $scope.current_session = $scope.chat_session_list[0];
                getChatHistory();
            } else {
                clearCurrentSession();
            }

            if (refresh_flag === false) {
                let exist_flag = false;
                for (let i = 0; i < $scope.chat_session_list.length; i++) {
                    if ($scope.current_session.id == $scope.chat_session_list[i].id) {
                        $scope.current_session = $scope.chat_session_list[i];
                        exist_flag = true;
                        break;
                    }
                }

                if (exist_flag === false)   // select default one
                {
                    if ($scope.chat_session_list.length > 0) {
                        $scope.current_session = $scope.chat_session_list[0];
                        getChatHistory();
                    } else
                        clearCurrentSession();
                }
            }
        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
            });
    }

    getChatSessionList(true);
    getPresetMessages();

    $scope.onSearch = function () {
        getChatSessionList(true);
    };

    $scope.onKeyDown = function (event) {
        console.log(event);
        alert(event);
    };

    $scope.onShowPresetMessageModal = function() {
        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat/modal_preset_messages.html',
            backdrop: 'static',
            size: 'lg',
            scope: $scope,
            resolve: {
                agent_id: function () {
                    return profile.id;
                },
                presetMessages: function () {
                    return $scope.presetMessages;
                }
            },
            controller: "ctrlPresetMessages"
        });

        modalInstance.result.then(function (data) {
            $scope.presetMessages = angular.copy(data);
        }, function () {

        });
    };

    $scope.onSetPresetMessage = function(msgInfo) {

        if ($scope.current_session.status == "Active") {
            $scope.current_session.chat = msgInfo.message;
        }
    };

    $scope.onTextareaKeypress = function (event) {
        let bFound = false;
        if (event == 13) {
            let guestInput = document.getElementById("guest_chat_input").value;
            $scope.current_session.chat = guestInput;
            $scope.onSendMessage($scope.current_session.chat);
        } else if (event == 38) { // top

            let tempIndex = cur_index;

            for (let i = tempIndex - 1; i >= 0; i--) {
                if ($scope.messages[i].direction == 1) {
                    $scope.current_session.chat = $scope.messages[i].text;
                    cur_index = i;
                    bFound = true;
                    break;
                }
            }

            if (bFound === false) {
                cur_index = 0;
            }

        } else if (event == 40) {
            let tempIndex = cur_index;

            for (let i = tempIndex + 1; i < $scope.messages.length - 1; i++) {
                if ($scope.messages[i].direction == 1) {
                    $scope.current_session.chat = $scope.messages[i].text;
                    bFound = true;
                    cur_index = i;
                    break;
                }
            }

            if (bFound === false) {
                cur_index = $scope.messages.length - 1;
            }
        }
    };

    function clearCurrentSession() {
        $scope.current_session = {};
        $scope.current_session.id = 0;
        $scope.current_session.status = 'Ended';
        $scope.current_session.lang_code = 'en';
    }

    $scope.$on('guest_chat_event', function (event, args) {
        let message = args;

        if (message.sub_type === 'request_chat') {
            //  play effect sound
            onPlayNewChatRequest();
            getChatSessionList(false);
        }

        if (message.sub_type === 'guest_message') {
            $scope.onReceiveMessageFromGuest(args.data);
        }

        if (message.sub_type === 'updated_phonebook_info') {
            getChatSessionList(false);
        }

        if (message.sub_type === 'exit_chat') {
            getChatSessionList(false);
        }

        if (message.sub_type === 'accept_chat')
            getChatSessionList(false);

        if (message.sub_type === 'end_chat') {
            onPlayDisconnectedStatus(args);
            getChatSessionList(false);
        }

        if (message.sub_type === 'logout_chat')
            getChatSessionList(false);

        if (message.sub_type === 'transfer_chat')
            getChatSessionList(false);

        if (message.sub_type === 'cancel_transfer')
            getChatSessionList(false);
    });


    $scope.onSelectChatSession = function (row) {
        $scope.current_session = row;
        getChatHistory();
    };

    $scope.onSelectChatHistorySession = function (row) {

        $scope.guest_history_messages = [];
        $scope.history_chat_session = row;

        let request = {};

        request.session_id = row.id;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/chathistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.guest_history_messages = response.data;
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to get Chat History.');
        })
            .finally(function () {

            });
    };

    $scope.isLoadingSpam = false;

    $scope.onSetMark = function (curSession) {

        $scope.isLoadingSpam = true;

        let request = {};
        request.agent_id = curSession.agent_id;
        request.mobile_number = curSession.mobile_number;
        request.spam_id = curSession.spam_id ? curSession.spam_id : 0;
        request.spam_status = curSession.spam_status ? curSession.spam_status : 0;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/setguestspaminfo',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            let data = response.data;
            if (data.success === true) {
                if (request.spam_id && request.spam_status) {
                    toaster.pop('success', "Success", "Removed from spam list");
                } else {
                    toaster.pop('success', "Success", "Marked as spam");
                }
                getChatSessionList(false);
            } else {
                toaster.pop('error', "Error", 'Error');
            }
        }).catch(function (err) {
            toaster.pop('error', 'Error', err.message);
        }).finally(function () {
            $scope.isLoadingSpam = false;
        });
    };

    $scope.onChangePhonebookInfo = function (curSession) {
        if (curSession.guest_type === 'In-House') {
            return;
        }

        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat/modal_change_phonebook.html',
            backdrop: 'static',
            size: 'md',
            scope: $scope,
            resolve: {
                info: function () {
                    return {
                        mobile_number: curSession.mobile_number,
                        phonebook_id: curSession.phonebook_id ? curSession.phonebook_id : 0,
                        phonebook_name: curSession.phonebook_name ? curSession.phonebook_name : '',
                        agent_id: curSession.agent_id,
                        property_id: profile.property_id
                    }
                }
            },
            controller: function ($scope, $uibModalInstance, $uibModal, $http, toaster, info) {
                $scope.title = info.phonebook_id == 0 ? 'Add To Phonebook' : 'Change Phonebook Information';
                $scope.mobile_number = info.mobile_number;
                $scope.phonebook_name = info.phonebook_name;
                $scope.phonebook_id = info.phonebook_id;
                $scope.onCancel = function () {
                    $uibModalInstance.dismiss();
                };

                $scope.onDelete = function () {
                    let modalInstance = $uibModal.open({
                        templateUrl: 'tpl/guestservice/chat/modal_confirm.html',
                        size: 'sm',
                        controller: function ($scope, $uibModalInstance, $http) {
                            $scope.title = 'Are you sure to delete current information?';

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
                            request.phonebook_id = info.phonebook_id;

                            $http({
                                method: 'POST',
                                url: '/frontend/guestservice/deletephonebookinfo',
                                data: request,
                                headers: {'Content-Type': 'application/json; charset=utf-8'}
                            }).then(function (response) {
                                let data = response.data;
                                if (data.success === true) {
                                    toaster.pop('success', "Success", "Successfully deleted.");
                                    $uibModalInstance.close('ok');
                                } else {
                                    toaster.pop('error', "Error", 'Error');
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

                $scope.onSave = function () {

                    $scope.isLoading = true;
                    let request = {};
                    request.property_id = info.property_id;
                    request.agent_id = info.agent_id;
                    request.mobile_number = info.mobile_number;
                    request.phonebook_id = info.phonebook_id;
                    request.phonebook_name = $scope.phonebook_name;

                    $http({
                        method: 'POST',
                        url: '/frontend/guestservice/savephonebookinfo',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    })
                        .then(function (response) {
                            let data = response.data;
                            if (data.success === true) {
                                toaster.pop('success', 'Notification', 'Successfully saved');
                                $uibModalInstance.close('ok');
                            } else {
                                toaster.pop('error', 'Notification', 'Not saved');
                            }
                        }).catch(function (err) {
                        toaster.pop('error', 'Error', err.message);
                    })
                        .finally(function () {
                            $scope.isLoading = false;
                        });
                }
            }
        });

        modalInstance.result.then(function (res) {
            if (res === 'ok') {
                getChatSessionList(false);
            }
        }, function () {

        });
    };

    $scope.onDownloadPdf = function (session_id, history_messages) {
        if (!$scope.auth_svc.isValidModule('app.guestservice.downloadpdf') || history_messages.length < 1) {
            return;
        }

        let property_id = profile.property_id;

        $window.location.href = liveserver.api + 'downloadguesthistorypdf?session_id=' + session_id + '&property_id=' + property_id;
    };

    $scope.onSendEmail = function (curSession, history_messages) {
        if (!$scope.auth_svc.isValidModule('app.guestservice.sendemail') || history_messages.length < 1) {
            return;
        }

        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/guestservice/chat/modal_add_users.html',
            backdrop: 'static',
            size: 'md',
            scope: $scope,
            resolve: {
                curUserId: function () {
                    return profile.id
                },
                curSessionId: function () {
                    return curSession.id;
                },
                property_id: function () {
                    return profile.property_id;
                },
                mobile_number: function () {
                    return curSession.mobile_number;
                }
            },
            controller: 'SendEmailCtrl'
        });
    };

    $scope.onAcceptChat = function (session) {
        $scope.acceptChat(session);
    };

    $scope.acceptChat = function (session) {
        let request = {};
        request.session_id = session.id;
        request.agent_id = profile.id;

        let accept_chat = $scope.curSetting.accept_chat;
        if (accept_chat !== '') {

            let agentName = profile.first_name + ' ' + profile.last_name;
            if (agentName !== '') {
                accept_chat = accept_chat.replace('__', agentName).replace("{", '').replace("}", '');
            } else {
                // remove {{ }}
                let firstIndex = accept_chat.indexOf('{');
                let lastIndex = accept_chat.indexOf('}') + 1;
                let tempString = accept_chat.slice(firstIndex, lastIndex);
                accept_chat = accept_chat.replace(tempString, '');
            }
        }

        request.accept_chat = accept_chat;
        request.language_name = $scope.current_session.language_name;
        request.room = $scope.current_session.room;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/acceptchat',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                let data = response.data;

                if (data.code != 200) {
                    toaster.pop('info', data.message);
                }
            }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    $scope.onEndChat = function (session) {
        $scope.endChat(session);
    };

    $scope.endChat = function (session) {
        let request = {};

        session.unread = 0;

        request.session_id = session.id;
        request.agent_id = profile.id;

        request.end_chat = $scope.curSetting.end_chat;
        request.language_name = $scope.current_session.language_name;
        request.room = $scope.current_session.room;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/endchatfromagent',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                let data = response.data;

                if (data.code != 200) {
                    toaster.pop('error', data.message);
                }
            }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    $scope.onTransferRequest = function (session) {
        let modalInstance = $uibModal.open({
            templateUrl: 'transfer_select_dialog.html',
            controller: 'TransferSelectDialogCtrl',
            resolve: {}
        });

        modalInstance.result.then(function (agent) {
            transferChatToOtherAgent(agent);
        }, function () {

        });
    };

    function transferChatToOtherAgent(agent) {
        let request = {};

        request.session_id = $scope.current_session.id;
        request.origin_agent_id = profile.id;
        request.new_agent_id = agent.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/transferchat',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                let data = response.data;

                if (data.code != 200) {
                    toaster.pop('error', data.message);
                }
            }).catch(function (response) {
        })
            .finally(function () {

            });
    }

    $scope.onTransferCancel = function (session) {
        let request = {};

        request.session_id = $scope.current_session.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/canceltransfer',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {
                let data = response.data;

                if (data.code != 200) {
                    toaster.pop('error', data.message);
                }
            }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    $scope.onSendMessage = function (message) {
        if (!message)
            return;
        let msg = {};

        msg.property_id = profile.property_id;
        msg.session_id = $scope.current_session.id;
        msg.agent_id = profile.id;
        msg.guest_id = $scope.current_session.guest_id;
        msg.lang_code = $scope.current_session.lang_code;
        msg.agent_name = profile.first_name + ' ' + profile.last_name;

        msg.text = message;
        msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
        msg.direction = 1;  // outgoing

        // add new infos
        msg.mobile_number = $scope.current_session.mobile_number;
        msg.guest_type = $scope.current_session.guest_type;

        msg.chat_type = 'text';
        msg.attachment = '';
        msg.room = $scope.current_session.room;
        msg.language_name = $scope.current_session.language_name;

        $scope.messages.push(msg);
        socket.emit('agent_msg', msg);

        $http({
            method: 'POST',
            url: '/frontend/guestservice/sendmessagefromagent',
            data: msg,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {

        }).catch(function (response) {
        })
            .finally(function () {
            });

        $scope.current_session.chat = '';
    };

    $scope.onChangeChat = function (message) {
        console.log('input is changed');

        if (!$scope.pause) {
            console.log('typing is started');

            sendTypingEvent(0);
        }

        $timeout.cancel($scope.pause);

        cur_index = 0;
        if ($scope.messages.length > 0) {
            cur_index = $scope.messages.length - 1;
        }

        $scope.pause = $timeout(function () {
            console.log('typing is ended');
            $scope.pause = null;

            sendTypingEvent(1);

        }, 2000);
    };

    function sendTypingEvent(typing_flag) {

        let msg = {};

        msg.property_id = profile.property_id;
        msg.session_id = $scope.current_session.id;
        msg.agent_id = profile.id;
        msg.guest_id = $scope.current_session.guest_id;
        msg.typing_flag = typing_flag;  // type start

        socket.emit('agent_typing', msg);
    }

    $scope.onSelectMessage = function (row) {
        row.unread = 0;

        cur_index = 0;
        if ($scope.messages.length > 0) {
            cur_index = $scope.messages.length - 1;
        }
    };

    $scope.onShowFullImage = function (imageSrc) {
        $scope.$emit('show_full_image', imageSrc);
    };

    $scope.onReceiveMessageFromGuest = function (args) {

        if (args.agent_id == profile.id) {
            onPlayGuestReply(args);

            for (let i = 0; i < $scope.chat_session_list.length; i++) {
                if ($scope.chat_session_list[i].id == args.session_id && profile.id == args.agent_id) {
                    $scope.chat_session_list[i].unread++;
                    break;
                }
            }
        }

        if (args.session_id == $scope.current_session.id) {
            let message = angular.copy(args);

            $scope.current_session.typing_flag = 1;

            message.direction = 0; // incoming

            $scope.current_session.lang_code = message.language;

            for (let i = 0; i < $scope.task_list_item.length; i++)
                message.text = message.text.replace($scope.task_list_item[i].task, '<a ng-dblclick="onCreateTask(' + i + ', \'' + $scope.task_list_item[i].task + '\')" ng-sglclick="onSelectTask(' + i + ', \'' + $scope.task_list_item[i].task + '\')">' + $scope.task_list_item[i].task + '</a>');

            // $compile(message.text)($scope);
            $scope.messages.push(message);
        }
    };

    $scope.$on('guest_message', function (event, args) {

        onPlayGuestReply(args);

        for (let i = 0; i < $scope.chat_session_list.length; i++) {
            if ($scope.chat_session_list[i].id == args.session_id && profile.id == args.agent_id) {
                $scope.chat_session_list[i].unread++;
                break;
            }
        }

        if (args.session_id == $scope.current_session.id) {
            let message = angular.copy(args);

            $scope.current_session.typing_flag = 1;

            message.direction = 0; // incoming

            $scope.current_session.lang_code = message.language;

            for (let i = 0; i < $scope.task_list_item.length; i++)
                message.text = message.text.replace($scope.task_list_item[i].task, '<a ng-dblclick="onCreateTask(' + i + ', \'' + $scope.task_list_item[i].task + '\')" ng-sglclick="onSelectTask(' + i + ', \'' + $scope.task_list_item[i].task + '\')">' + $scope.task_list_item[i].task + '</a>');

            // $compile(message.text)($scope);
            $scope.messages.push(message);
        }
    });

    $scope.$on('guest_typing', function (event, args) {
        let message = args;
        if (message.session_id != $scope.current_session.id)
            return;

        $scope.current_session.typing_flag = message.typing_flag;
    });

    $scope.$on('agent_message', function (event, args) {
        let message = args;
        if ((message.session_id != $scope.current_session.id) || (message.agent_id == $scope.agent_id)) {
            // current session's guest message
            return;
        }

        $scope.messages.push(message);
    });

    function getChatHistory() {

        $scope.messages = [];

        let request = {};

        request.session_id = $scope.current_session.id;
        request.property_id = profile.property_id;

        if (!(request.session_id > 0))
            return;

        $scope.current_session.lang_code = $scope.current_session.language;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/chathistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.messages = response.data;
            for (let i = $scope.messages.length - 1; i >= 0; i--) {
                if ($scope.messages[i].direction == 0) // incoming guest message
                {
                    $scope.current_session.lang_code = $scope.messages[i].language;
                    break;
                }
            }
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to get Chat History.');
        })
            .finally(function () {

            });

        $scope.$broadcast('room_selected', $scope.current_session);
        getChatSessionHistory();
    }

    function getChatSessionHistory() {

        $scope.chat_session_history_list = [];

        $scope.chat_table_style = {};

        let request = {};

        request.mobile_number = $scope.current_session.mobile_number;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/chatsessionhistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            $scope.chat_session_history_list = response.data;
            let chat_table_height = ($window.innerHeight - 410);
            let limit = chat_table_height / 28;
            if (response.data.length > limit) {
                $scope.chat_table_style = {
                    'height': chat_table_height + 'px',
                    'overflow-y': 'auto'
                };
            } else {
                $scope.chat_table_style = {};
            }
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to get Chat History.');
        })
            .finally(function () {

            });
    }

    $scope.loadAgentFilters = function (query) {

        let request = {};

        request.property_id = profile.property_id;
        request.filter = query;
        request.agent_id = 0;

        return $http({
            method: 'POST',
            url: '/frontend/chat/agentlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });
    };


    $scope.$on('close_filter_dropdown', function (evt, data) {
        $scope.onFilterClose();
    });

    // end chat action
    $scope.$on('end_chat_action', function (evt, data) {
        if ($scope.current_session.status === 'Active') {
            $scope.endChat($scope.current_session);
        }
    });

    // waiting chat
    $scope.$on('accept_waiting_chat', function (evt, data) {
        if ($scope.current_session.status === 'Waiting') {
            $scope.acceptChat($scope.current_session);
        }
    });

    $scope.$on('no_answer_action', function (evt, data) {
        if ($scope.current_session.status === 'Active') {

            let no_answer_chat = $scope.curSetting.no_answer;
            if (no_answer_chat !== '') {
                $scope.onSendMessage(no_answer_chat);
            }
        }
    });

    $scope.loadRoomFilters = function (query) {
        let request = {};

        request.property_id = profile.property_id;
        request.room = query;

        return $http.get('/list/roomlist?property_id=' + profile.property_id + '&room=' + query);
    };

    $scope.onFilterClose = function () {
        $scope.filter_is_open = false;
    };


    $scope.onFilterSearch = function () {
        $scope.onFilterClose();

        getChatSessionList(true);
    };

    $scope.onCreateTask = function (num, task_name) {
        $scope.$broadcast('create_new_task', $scope.task_list_item[num]);
    };

    $scope.onSelectTask = function (num, task_name) {
        $scope.$broadcast('select_new_task', $scope.task_list_item[num]);
    }
});

app.controller('TransferSelectDialogCtrl', function ($scope, $rootScope, $http, AuthService, $uibModalInstance) {
    $scope.selected_agent = {};

    function getChatActiveGgentList() {
        let profile = AuthService.GetCredentials();

        let request = {};


        request.property_id = profile.property_id;
        request.agent_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/chatactiveagentlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function (response) {
            console.log(response);
            $scope.agent_list = response.data;

        }).catch(function (response) {
        })
            .finally(function () {

            });
    }

    getChatActiveGgentList();

    $rootScope.$on("call_transfer_agent", function (evt, data) {
        getChatActiveGgentList();
    });

    $scope.onSelectAgent = function (row) {
        $scope.selected_agent = row;
    };

    $scope.ok = function () {
        if ($scope.selected_agent.id > 0)
            $uibModalInstance.close($scope.selected_agent);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };


});



app.filter("trustUrl", ['$sce', function ($sce) {
    return function (recordingUrl) {
        return $sce.trustAsResourceUrl(recordingUrl);
    }
}]);

