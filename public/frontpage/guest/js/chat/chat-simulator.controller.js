'use strict';

app.controller('ChatSimulatorController', function($rootScope, $scope, $http, $interval, $stateParams, $window, $timeout, toaster, AuthService, TranslateService, $state, socket) {

    $scope.current_session = {};

    $scope.current_session.id = 0;
    $scope.current_session.agent_id = 0;
    $scope.current_session.status = 'No Status';
    $scope.current_session.language = "en";


    $scope.guest_info = {};

    let tempLanguage = "English";
    let infoList = [];

    let phone_number = '';

	$scope.content_height = 'height: ' + ($window.innerHeight - 135) + 'px; overflow-y: auto';

	let tempPhoneNumber = localStorage.getItem('simulatorPhoneNumber');
	if (tempPhoneNumber == null) {
        $state.go('app/first-simulator');
    } else {
        phone_number = tempPhoneNumber;
    }

    function sendTypingEvent(typing_flag) {

        let msg = {};

        msg.session_id = $scope.current_session.id;
        msg.guest_id = $scope.guest_info.guest_id;
        msg.agent_id = $scope.current_session.agent_id;
        msg.property_id = $scope.guest_info.property_id;
        msg.typing_flag = typing_flag;  // type end

        socket.emit('guest_typing', msg);
    }

    $scope.onChangeChat = function() {
        console.log('input is changed');

        if( !$scope.pause ) {
            console.log('typing is started');
            sendTypingEvent(0);
        }

        $timeout.cancel($scope.pause);
        $scope.pause = $timeout(function() {
            console.log('typing is ended');
            $scope.pause = null;
            sendTypingEvent(1);
        }, 2000);
    };

  	$scope.$on('agent_message', function(event, args){
  		let message = args;

  		message.direction = 0; // incoming

        let tempMessageInfo = {
            sender: 'other',
            messageContent: message.text,
            timeInfo: moment().format('HH:mm A, MMM D')
        };

        if (message.lang_code !== undefined && message.lang_code !== 'en') {
            TranslateService.translate(message.text, 'en', message.lang_code)
                .then(function(response) {
                    let data = response.data.data;
                    tempMessageInfo.messageContent = data.translations[0].translatedText;

                }).catch(function(response) {
            })
                .finally(function() {
                    $scope.chatlist.push(tempMessageInfo);
                });
        } else {
            $scope.chatlist.push(tempMessageInfo);
        }

        $timeout(function () {
            document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
        }, 100);
  	});

    $scope.$on('agent_typing', function(event, args){
        let message = args;
        $scope.isTyping = !(message.typing_flag === 1 || message.typing_flag === '1');
    });

    $scope.$on('agent_chat_event', function(event, args){
        let message = args;
        if( message.sub_type === 'accept_chat' ) {
            $scope.current_session = message.data;
        }

        if( message.sub_type === 'end_chat' ) {
            $scope.current_session.id = 0;
            $scope.current_session.agent_id = 0;
            $scope.current_session.status = 'No Status';

            currentChatInfo.template = "";
        }

        if( message.sub_type === 'logout_chat' ) {
            $scope.current_session = message.data;
        }
    });

    $scope.prioritylist = [];

    $scope.chatlist = [
    ];
//    chat bot part

    // names for chat template (content and chat name)
    const CHAT_HELLO = "hello";
    const CHAT_WRONG = "wrong";
    const CHAT_CREATE_QUICK_TASK = "create_quick_task";
    const CHAT_LANGUAGE_SELECTED = "language_selected";

    const CHAT_WELCOME = "welcome";
    const CHAT_CREATE_QUICK_TASK_QUANTITY = "create_quick_task_quantity";
    const CHAT_QUANTITY_VALID_NUMBER = "quantity_valid_number";
    const CHAT_AGENT_CALL = "agent_call";
    const CHAT_QUESTION_ROOM = "question_room";
    const CHAT_QUESTION_GUEST_NAME = "question_guestname";

    // type names
    const TYPE_CHAT = "chat";
    const TYPE_CREATE_QUICK_TASK = "type_creating_quick_task";

    $scope.bShowChatbot = false;

    $scope.messageContent = "";


    $scope.otherInfo = {
        name: 'Alex Shinde',
        image: '/images/chatIcon.ico'
    };

    $scope.userInfo = {
        name: $scope.guest_info.guest_name,
        image: ''
    };

    $scope.isTyping = false;
    $scope.onToggleChatbot = function () {
        $scope.bShowChatbot = !$scope.bShowChatbot;
    };

    $scope.onTextareaKeypress = async function (event) {
        if (event === 10) {
            await $scope.onSendChatToServer();
        }
    };

    let selectedQuickTaskInfo = {};

    let currentChatInfo = {
        chatName: "",
        template: ""
    };

    function getFilterMessageContent(message = CHAT_WELCOME) {
        let result = {
            type: TYPE_CHAT,
            content: message
        };

        let messageArr = ["", CHAT_WELCOME, CHAT_HELLO];
        if (messageArr.includes(message.toLowerCase())) {
            result.type = TYPE_CHAT;
            result.content = message;
            return result;
        }

        switch (currentChatInfo.chatName.toLowerCase()) {
            case CHAT_QUESTION_ROOM:
                $scope.guest_info.room = message;
                result.type = TYPE_CHAT;
                break;
            case CHAT_QUESTION_GUEST_NAME:
                $scope.guest_info.guest_name = message;
                result.type = TYPE_CHAT;
                break;
            case CHAT_LANGUAGE_SELECTED:
                if (message === "1") {
                    $scope.current_session.language = tempLanguage;

                    result.type = TYPE_CHAT;
                    result.content = CHAT_HELLO;
                } else {
                    $scope.current_session.language = 'en';
                }
                break;
            case CHAT_WRONG:
            case CHAT_HELLO:
                if (message === "1") {
                    result.type = TYPE_CHAT;
                    result.content = CHAT_CREATE_QUICK_TASK;
                } else if (message === "2") {
                } else if (message === "5") {
                    result.type = TYPE_CHAT;
                    result.content = CHAT_AGENT_CALL;
                }
                break;
            case CHAT_CREATE_QUICK_TASK:
                let content = currentChatInfo.infoList[message];

                if (content === undefined) {
                    // result.type = TYPE_CREATE_QUICK_TASK;
                    // result.content = content;
                    result.type = TYPE_CHAT;
                    result.content = message;
                } else {
                    selectedQuickTaskInfo = angular.copy(content);

                    if (content.quantity === true) {
                        result.type = TYPE_CHAT;
                        result.content = CHAT_CREATE_QUICK_TASK_QUANTITY;
                        result.task_name = content.name;
                    } else {
                        result.type = TYPE_CREATE_QUICK_TASK;
                        result.content = content;
                    }

                    result.is_wrong = false;
                }

                break;
            case CHAT_CREATE_QUICK_TASK_QUANTITY:
            case CHAT_QUANTITY_VALID_NUMBER:
                if (message.match(/^[0-9]+$/) != null) {
                    result.type = TYPE_CREATE_QUICK_TASK;
                    result.content = selectedQuickTaskInfo;
                    result.quantity_count = parseInt(message);
                } else {
                    // not only number
                    result.type = TYPE_CHAT;
                    result.content = CHAT_QUANTITY_VALID_NUMBER;
                }
                break;
        }

        return result;
    }

    $scope.sendChatToServer = function(messageInfo) {

        let request = $scope.getRequestInfo(messageInfo);

        $scope.isTyping = true;
        $http({
            method: 'POST',
            url: '/getnextchatcontent-simulator',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(async function (response) {

            if (response.data.success === true && response.data.result.message_content !== undefined) {

                let serverResponse = {
                    sender: 'other',
                    messageContent: response.data.result.message_content,
                    timeInfo: moment().format('HH:mm A, MMM D')
                };

                currentChatInfo.chatName = response.data.result.name;
                currentChatInfo.template = response.data.result.message_content;

                $scope.guest_info.guest_id = response.data.result.guest_id;
                $scope.guest_info.guest_name = response.data.result.guest_name;
                $scope.guest_info.guest_type = response.data.result.guest_type;
                $scope.guest_info.room = response.data.result.room;

                $scope.current_session.language = response.data.result.lang_code;
                tempLanguage = response.data.result.language;
                infoList = response.data.result.info_list;

                if (response.data.join_external) {
                    let sendGuestInfo = angular.copy($scope.guest_info);
                    sendGuestInfo.guest_id = 'external_' + phone_number.replace('+', '');
                    $rootScope.$broadcast('success-login', sendGuestInfo);
                }

                if (response.data.join_in_house) {
                    $rootScope.$broadcast('success-login', $scope.guest_info);
                }

                if (response.data.disable_chat) {
                    if ($scope.current_session.status !== 'Active') {
                        $scope.current_session.status = 'Waiting';
                    }
                }

                if ($scope.current_session.language !== undefined && $scope.current_session.language !== 'en') {
                    TranslateService.translate(serverResponse.messageContent, 'en', $scope.current_session.language)
                        .then(function(transResponse) {
                            let data = transResponse.data.data;
                            serverResponse.messageContent = data.translations[0].translatedText;
                        })
                        .catch(function (error) {

                        })
                        .finally(async function () {
                            let saveResponse = await onSaveChatBotHisotry(serverResponse);

                            if (saveResponse != null && saveResponse.data.success === true) {
                                $scope.chatlist.push(serverResponse);


                                if (response.data.result.name === CHAT_AGENT_CALL) {
                                    let reqChatResponse = await requestChat();
                                    $scope.current_session = reqChatResponse.data;

                                    $scope.callToAgent();
                                }

                                $timeout(function () {
                                    document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
                                    $scope.isTyping = false;
                                }, 100);
                            }


                            if (response.data.result.is_wrong === true) {

                                let sendMessageInfo = angular.copy(messageInfo);
                                sendMessageInfo.messageContent = '';
                                $scope.sendChatToServer(sendMessageInfo);
                            }
                        });
                } else {
                    let saveResponse = await onSaveChatBotHisotry(serverResponse);

                    if (saveResponse != null && saveResponse.data.success === true) {
                        $scope.chatlist.push(serverResponse);

                        $timeout(function () {
                            document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
                            $scope.isTyping = false;
                        }, 100);
                    }

                    if (response.data.result.is_wrong === true) {
                        let sendMessageInfo = angular.copy(messageInfo);
                        sendMessageInfo.messageContent = '';
                        $scope.sendChatToServer(sendMessageInfo);
                    }
                }

            } else {
                if (currentChatInfo.chatName !== "") {
                    let serverResponse = {
                        sender: 'other',
                        messageContent: currentChatInfo.template,
                        timeInfo: moment().format('HH:mm A, MMM D')
                    };

                    let saveResponse = await onSaveChatBotHisotry(serverResponse);

                    if (saveResponse != null && saveResponse.data.success === true) {
                        $scope.chatlist.push(serverResponse);

                        $timeout(function () {
                            document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
                            $scope.isTyping = false;
                        }, 100);
                    }
                }
            }
        }).catch(function (response) {

        }).finally(function () {
            $scope.isTyping = false;
        });
    };

    $scope.getRequestInfo = function(messageInfo) {
        let request = {};
        request.phone_number = phone_number;
        request.message_content = messageInfo.messageContent;

        request.prev_chat_name = currentChatInfo.chatName;

        request.guest_id = $scope.guest_info.guest_id;
        request.room = $scope.guest_info.room;
        request.guest_name = $scope.guest_info.guest_name;
        request.guest_type = $scope.guest_info.guest_type;
        request.lang_code = $scope.current_session.language;
        request.language = tempLanguage;
        request.infoList = infoList;
        return request;
    };

    async function onSaveChatBotHisotry(chatInfo) {
        let request = {};
        request.property_id = $scope.guest_info.property_id;
        request.session_id = $scope.current_session.id;
        request.agent_id = $scope.current_session.agent_id;
        request.guest_id = $scope.guest_info.guest_id;
        request.phone_number = phone_number;
        request.text = chatInfo.messageContent;
        request.text_trans = '';
        request.language = $scope.current_session.language;

        request.cur_chat_name = currentChatInfo.chatName;

        if (chatInfo.sender === 'me') {
            request.sender = 'guest';
        } else {
            request.sender = 'server';
        }

        return await $http({
            method: 'POST',
            url: '/savechatbothistory-simulator',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });
    }

    $scope.onSendChatToServer = async function (init = false) {

        if (init === false && $scope.messageContent === "") {
            return;
        }

        let tempMessageInfo = {
            sender: 'me',
            messageContent: $scope.messageContent,
            timeInfo: moment().format('HH:mm A, MMM D')
        };

        if ($scope.current_session.status === 'Active') {

            let msg = {};

            msg.session_id = $scope.current_session.id;
            msg.guest_name = $scope.guest_info.guest_name;
            msg.guest_id = $scope.guest_info.guest_id;
            msg.agent_id = $scope.current_session.agent_id;
            msg.property_id = $scope.guest_info.property_id;
            msg.room = $scope.guest_info.room;
            msg.text = $scope.messageContent;
            msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
            msg.direction = 1;  // outgoing
            msg.language = $scope.current_session.language;

            if( $scope.current_session.language !== undefined && $scope.current_session.language !== 'en') {
                TranslateService.translate(msg.text, $scope.current_session.language, 'en')
                    .then(function(response) {
                        let data = response.data.data;
                        msg.text_trans = data.translations[0].translatedText;
                    }).catch(function(response) {
                })
                    .finally(function() {
                        $scope.chatlist.push(tempMessageInfo);
                        socket.emit('guest_msg', msg);
                    });
            }
            else {
                msg.text_trans = '';

                $scope.chatlist.push(tempMessageInfo);
                socket.emit('guest_msg', msg);
            }

            $scope.messageContent = '';

            $timeout(function () {
                document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
            }, 100);
        } else {
            if (init !== true) {
                $scope.chatlist.push(tempMessageInfo);
                $scope.messageContent = "";

                $timeout(function () {
                    document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
                }, 100);

                await onSaveChatBotHisotry(tempMessageInfo);
            }

            $scope.sendChatToServer(tempMessageInfo);

            if (filterResult.type === TYPE_CHAT) {
                $scope.sendChatToServer(request, tempMessageInfo);
            } else if (filterResult.type === TYPE_CREATE_QUICK_TASK) {
                if (filterResult.content == undefined || filterResult.content == null) {
                    $scope.isTyping = true;

                    let serverResponse = {
                        sender: 'other',
                        messageContent: currentChatInfo.template,
                        timeInfo: moment().format('HH:mm A, MMM D')
                    };

                    let savePromise = await onSaveChatBotHisotry(serverResponse);

                    if (savePromise != null && savePromise.data.success == true) {
                        $scope.chatlist.push(serverResponse);

                        if ($scope.chatlist.length > 40) {
                            $scope.chatlist.splice(0, $scope.chatlist.length - 40);
                        }

                        $timeout(function () {
                            document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
                            $scope.isTyping = false;
                        }, 100);
                    }
                } else {

                    //    create new request(quick task)
                    request.task_id = filterResult.content.id;

                    $scope.isTyping = true;
                    $http({
                        method: 'POST',
                        url: '/addnewquicktask',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    }).then(function (response) {

                        if (response.data.success == true) {
                            let data = response.data.result.task_info;

                            if (data.department == undefined) {
                                return;
                            }

                            if (data.staff_list.length < 1) {
                                data.staff_list.push({id: 0, user_id: 0, wholename: 'No Staff is on shift'});
                            }

                            let staff_name = data.staff_list[0].wholename;

                            let quicktask_data = {};

                            quicktask_data.property_id = response.data.result.property_id;
                            quicktask_data.dept_func = data.deptfunc.id;
                            quicktask_data.department_id = data.department.id;
                            quicktask_data.type = 1;
                            quicktask_data.priority = $scope.prioritylist[0].id;
                            quicktask_data.start_date_time = moment().format("YYYY-MM-DD HH:mm:ss");
                            quicktask_data.created_time = moment().format("YYYY-MM-DD HH:mm:ss");
                            quicktask_data.end_date_time = '0000-00-00 00:00:00';
                            quicktask_data.dispatcher = data.staff_list[0].user_id;
                            quicktask_data.feedback_flag = false;

                            quicktask_data.attendant = 0;
                            quicktask_data.room = response.data.result.room_id;
                            quicktask_data.task_list = response.data.result.task_id;
                            quicktask_data.max_time = data.taskgroup.max_time;
                            quicktask_data.quantity = filterResult.quantity_count == undefined ? 1 : filterResult.quantity_count;
                            quicktask_data.custom_message = '';
                            quicktask_data.status_id = 1;
                            quicktask_data.guest_id = $scope.guest_info.guest_id;
                            quicktask_data.location_id = response.data.result.location_id;

                            let taskList = [];
                            taskList.push(quicktask_data);

                            $scope.isTyping = true;

                            $http({
                                method: 'POST',
                                url: '/createtasklist',
                                data: taskList,
                                headers: {'Content-Type': 'application/json; charset=utf-8'}
                            }).then(function (response) {
                                if (response.data.invalid_task_list.length == 0) {
                                    console.log('success ================ ');

                                    request = $scope.getInitRequestInfo();
                                    filterResult = {
                                        type: TYPE_CHAT,
                                        content: CHAT_CREATE_QUICK_TASK_SUCCESS
                                    };

                                    $scope.sendChatToServer(request, filterResult);
                                } else {
                                    console.log('failed =================');
                                    request = $scope.getInitRequestInfo();

                                    for (let i = 0; i < response.data.invalid_task_list.length; i++) {
                                        filterResult = {
                                            type: TYPE_CHAT,
                                            content: CHAT_CREATE_QUICK_TASK_FAILED,
                                            error: response.data.invalid_task_list[0].message
                                        };

                                        $scope.sendChatToServer(request, filterResult);
                                    }
                                    console.log(response.data.invalid_task_list[0].message);
                                }
                            }).catch(function (response) {
                            }).finally(function () {
                                $scope.isTyping = false;
                            });
                        }
                    }).catch(function (response) {
                    }).finally(function () {
                        $scope.isTyping = false;
                    });
                }
            }
        }
    };

    $scope.getPriorityList = function() {
        $http({
            method: 'GET',
            url: '/prioritylist'
        }).success(function(data) {
            $scope.prioritylist = data;
        }).error(function(data, status, headers, config) {
        });
    };

    $scope.onLoadedData = function (e) {
        e.preventDefault();

        e.target.scrollTop(e.target.height);
    };

    async function requestChat() {
        let request = {};

        request.property_id = $scope.guest_info.property_id;
        request.guest_id = $scope.guest_info.guest_id;
        request.language = $scope.current_session.language;
        request.mobile_number = phone_number;
        request.guest_type = $scope.guest_info.guest_type;

        return await $http({
            method: 'POST',
            url: '/requestchat-simulator',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });
    }

    async function getInitChatbotHistory() {
        let request = {};
        request.phone_number = phone_number;

        return await $http({
            method: 'POST',
            url: '/chatbothistory-simulator',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });
    }

    $scope.callToAgent = function () {
        let request = {};
        request.session_id = $scope.current_session.id;

        $http({
            method: 'POST',
            url: '/calltoagent',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            let data = response.data;

            if (data.success === true) {
                currentChatInfo.chatName = "agent_call";
                $scope.current_session.status = "Waiting";
            } else {
                toaster.pop('error', 'Did not call to any agent...');
            }
        }).catch(function (response) {

        }).finally(function () {

        });
    };

    let initial  = async function () {
        let chatHistoryResponse = await getInitChatbotHistory();
        $scope.current_session = chatHistoryResponse.data.session_info;

        $scope.guest_info = chatHistoryResponse.data.guest_info;

        if ($scope.guest_info && $scope.guest_info.guest_id > 0) {
            $rootScope.$broadcast('success-login', $scope.guest_info);
        }

        if (chatHistoryResponse.data.chat_list.length > 0) {
            $scope.chatlist = chatHistoryResponse.data.chat_list;
        }

        $timeout(function () {
            $scope.bShowChatbot = true;

            $timeout(function () {
                document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
            }, 200);
        }, 1000);

        if ($scope.chatlist.length < 1 || currentChatInfo.chatName === 'welcome') {
            $timeout(async function () {
                await $scope.onSendChatToServer(true);
            }, 1000);
        }

        $scope.getPriorityList();
    };

    initial();
});
