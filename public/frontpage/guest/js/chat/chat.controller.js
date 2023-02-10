'use strict';

app.controller('ChatController', function($scope, $http, $interval, $stateParams, $window, $timeout, toaster, AuthService, TranslateService, socket) {
    var MESSAGE_TITLE = 'Chat Page';

    var profile = AuthService.GetCredentials();

    $scope.current_session = {};

    $scope.current_session.id = 0;
    $scope.current_session.agent_id = 0;
    $scope.current_session.status = 'No Status';
    $scope.current_session.language = "en";

    let temp_lang_code = "en";
    let temp_language = "English";

	$scope.content_height = 'height: ' + ($window.innerHeight - 135) + 'px; overflow-y: auto';

	$scope.messages = [];


    function getLanguageList() {
        profile = AuthService.GetCredentials();
        $scope.lang_code = profile.language;

        $http.get('/list/languagelist')
            .then(function(response) {
                $scope.language_list = response.data;
            });
    }

	$scope.init = function() {
	    getLanguageList();
	    $scope.pause = null;
	};

	$scope.logResize = function () {
        console.log('element resized');
    };

	var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
	console.log(iOS);

	$scope.chat_height_style = {'height': '90% !important'};

	if( iOS === false ) {
        $scope.chat_height_style = {};
    }

	$scope.endChat = function() {
	    profile = AuthService.GetCredentials();
	    var request = {};
	    request.session_id = $scope.current_session.id;
	    request.agent_id = $scope.current_session.agent_id;

	    $http({
            method: 'POST',
            url: '/guest/endchatfromguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
	    }).then(function(response) {
	        var data = response.data;
            if( data.code != 200)
            {
                toaster.pop('error', data.message);
                return;
            }
        }).catch(function (response) {
        })
        .finally(function () {

        });
	};

	// function getChatHistory() {
    // 	profile = AuthService.GetCredentials();
    //
	//     var request = {};
	//     request.session_id = $scope.current_session.id;
	//     if( request.session_id < 1 ) {
    //         return;
    //     }
    //
    //     $http({
    //         method: 'POST',
    //         url: '/guest/chathistory',
    //         data: request,
    //         headers: {'Content-Type': 'application/json; charset=utf-8'}
    //     }).then(function(response) {
    //         $scope.messages = response.data;
    //     }).catch(function(response) {
    //         toaster.pop('error', MESSAGE_TITLE, 'Failed to get Chat History.');
    //     })
    //     .finally(function() {
    //
    //     });
	// }

    function sendTypingEvent(typing_flag) {

        profile = AuthService.GetCredentials();
        var msg = {};

        msg.session_id = $scope.current_session.id;
        msg.guest_id = profile.guest_id;
        msg.agent_id = $scope.current_session.agent_id;
        msg.property_id = profile.property_id;
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
  		var message = args;

  		message.direction = 0; // incoming

        let tempMessageInfo = {
            sender: 'other',
            messageContent: message.text,
            timeInfo: moment().format('HH:mm A, MMM D')
        };

        if (message.lang_code != 'en') {
            TranslateService.translate(message.text, 'en', message.lang_code)
                .then(function(response) {
                    var data = response.data.data;
                    tempMessageInfo.messageContent = data.translations[0].translatedText;

                }).catch(function(response) {
            })
                .finally(function() {
                    $scope.chatlist.push(tempMessageInfo);
                });
        } else {
            $scope.chatlist.push(tempMessageInfo);
        }

        // if( $scope.lang_code != 'en' && message.trans && !message.text_trans ){// not translated
        //     TranslateService.translate(message.text, 'en', $scope.lang_code)
        //         .then(function(response) {
        //             var data = response.data.data;
        //             message.text_trans = data.translations[0].translatedText;
        //         }).catch(function(response) {
        //         })
        //         .finally(function() {
        //
        //
        //             $scope.chatlist.push(tempMessageInfo);
        //         });
        // } else {
        //     $scope.chatlist.push(tempMessageInfo);
        // }

        $timeout(function () {
            document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
        }, 100);

        // $scope.current_session.typing_flag = 1;
        // $scope.isTyping = true;
  	});

    $scope.$on('agent_typing', function(event, args){
        var message = args;
        $scope.isTyping = message.typing_flag == 1 ? false : true;
    });

    $scope.$on('guest_message', function(event, args){
  		// var message = args;
  		// $scope.messages.push(message);
    });

    $scope.$on('agent_chat_event', function(event, args){
        var message = args;
        if( message.sub_type == 'accept_chat' ) {
            $scope.current_session = message.data;
        }

        if( message.sub_type == 'end_chat' ) {
            $scope.current_session.id = 0;
            $scope.current_session.agent_id = 0;
            $scope.current_session.status = 'No Status';

            currentChatInfo.template = "";
            currentChatInfo.infoList = [];
        }

        if( message.sub_type == 'logout_chat' ) {
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
    const CHAT_SELECT_LANGUAGE = "select_language";
    const CHAT_WELCOME = "welcome";
    const CHAT_CREATE_QUICK_TASK_QUANTITY = "create_quick_task_quantity";
    const CHAT_QUANTITY_VALID_NUMBER = "quantity_valid_number";
    const CHAT_CREATE_QUICK_TASK_SUCCESS = "create_quick_task_success";
    const CHAT_CREATE_QUICK_TASK_FAILED = "create_quick_task_failed";
    const CHAT_AGENT_CALL = "agent_call";


    // type names
    const TYPE_CHAT = "chat";
    const TYPE_AGENT_CHAT = "agent_chat";
    const TYPE_CREATE_QUICK_TASK = "type_creating_quick_task";

    $scope.bShowChatbot = false;

    $scope.messageContent = "";


    $scope.otherInfo = {
        name: 'Alex Shinde',
        image: '/images/chatIcon.ico'
    };

    $scope.userInfo = {
        name: profile.guest_name,
        image: ''
    };

    $scope.isTyping = false;
    $scope.onToggleChatbot = function () {
        $scope.bShowChatbot = !$scope.bShowChatbot;
    };

    $scope.onTextareaKeypress = function(event) {
        if (event == 10) {
            $scope.onSendChatToServer();
        }
    };

    var selectedQuickTaskInfo = {};

    var currentChatInfo = {
        chatName: "welcome",
        template: "",
        infoList: []
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
            case "":
            case CHAT_WELCOME:
            case CHAT_SELECT_LANGUAGE:
                if(message == "1") {
                    temp_lang_code = "ar";
                    temp_language = "Arabic";

                    result.type = TYPE_CHAT;
                    result.content = CHAT_LANGUAGE_SELECTED;
                } else if (message == "2") {
                    temp_language = "English";
                    temp_lang_code = "en";

                    result.type = TYPE_CHAT;
                    result.content = CHAT_LANGUAGE_SELECTED;
                } else if (message == "3") {
                    temp_lang_code = "ch";
                    temp_language = "Chinese";

                    result.type = TYPE_CHAT;
                    result.content = CHAT_LANGUAGE_SELECTED;

                } else {
                    result.type = TYPE_CHAT;
                    result.content = "";
                }
                break;
            case CHAT_LANGUAGE_SELECTED:
                if (message == "1") {
                    $scope.current_session.language = temp_lang_code;

                    result.type = TYPE_CHAT;
                    result.content = CHAT_HELLO;
                } else {
                    $scope.current_session.language = 'en';
                }
                break;
            case CHAT_WRONG:
            case CHAT_HELLO:
                if (message == "1") {
                    result.type = TYPE_CHAT;
                    result.content = CHAT_CREATE_QUICK_TASK;
                } else if (message == "2") {
                } else if (message == "5") {
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

                    if (content.quantity == true) {
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
            default:
                break;
        }

        return result;
    }

    $scope.sendChatToServer = function(request, filterResult) {
        request.message_content = filterResult.content;

        if (request.message_content === CHAT_CREATE_QUICK_TASK_QUANTITY) {
            request.task_name = filterResult.task_name;
        } else if (request.message_content === CHAT_CREATE_QUICK_TASK_FAILED) {
            request.error = filterResult.error;
        }

        request.prev_chat_name = currentChatInfo.chatName.toLowerCase();

        $scope.isTyping = true;
        $http({
            method: 'POST',
            url: '/guest/getnextchatcontent',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(async function (response) {

            if (response.data.success == true && response.data.result.message_content != undefined) {

                let serverResponse = {
                    sender: 'other',
                    messageContent: response.data.result.message_content,
                    timeInfo: moment().format('HH:mm A, MMM D')
                };



                currentChatInfo.chatName = response.data.result.name;
                currentChatInfo.template = response.data.result.message_content;
                currentChatInfo.infoList = response.data.result.info_list;

                if (currentChatInfo.chatName == CHAT_LANGUAGE_SELECTED) {
                    serverResponse.messageContent = serverResponse.messageContent.replace("{{lang_label}}", temp_language);
                }

                if ($scope.current_session.language != 'en') {
                    TranslateService.translate(serverResponse.messageContent, $scope.current_session.language, 'en')
                        .then(function(transResponse) {
                            var data = transResponse.data.data;
                            serverResponse.messageContent = data.translations[0].translatedText;
                        })
                        .catch(function (error) {

                        })
                        .finally(async function () {
                            let saveResponse = await onSaveChatBotHisotry(serverResponse);

                            if (saveResponse != null && saveResponse.data.success == true) {
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


                            if (response.data.result.is_wrong === true && response.data.result.name != CHAT_WRONG) {

                                if (response.data.result.prev_chat_name == CHAT_WELCOME) {
                                    filterResult.content = CHAT_SELECT_LANGUAGE;
                                } else {
                                    filterResult.content = response.data.result.prev_chat_name;
                                }

                                $scope.sendChatToServer(request, filterResult);
                            }
                        });
                } else {
                    let saveResponse = await onSaveChatBotHisotry(serverResponse);

                    if (saveResponse != null && saveResponse.data.success == true) {
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


                    if (response.data.result.is_wrong === true && response.data.result.name != CHAT_WRONG) {

                        if (response.data.result.prev_chat_name == CHAT_WELCOME) {
                            filterResult.content = CHAT_SELECT_LANGUAGE;
                        } else {
                            filterResult.content = response.data.result.prev_chat_name;
                        }

                        $scope.sendChatToServer(request, filterResult);
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

                    if (saveResponse != null && saveResponse.data.success == true) {
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

    $scope.getRequestInfo = function() {
        let request = {};
        // request.phone_number = '8613321443788';
        request.guest_id = profile.guest_id;
        request.property_id = profile.property_id;
        return request;
    };

    async function onSaveChatBotHisotry(chatInfo) {
        let savePromise = null;
        let request = {};
        request.property_id = profile.property_id;
        request.session_id = $scope.current_session.id;
        request.agent_id = $scope.current_session.agent_id;
        request.guest_id = profile.guest_id;
        request.text = chatInfo.messageContent;
        request.text_trans = '';
        request.language = $scope.current_session.language;

        request.cur_chat_name = currentChatInfo.chatName;

        if (chatInfo.sender == 'me') {
            request.sender = 'guest';
        } else {
            request.sender = 'server';
        }

        savePromise = await $http({
            method: 'POST',
            url: '/guest/savechatbothistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });

        return savePromise;
    }

    $scope.onSendChatToServer = async function (init = false, initMessage = "") {

        if (init == false && $scope.messageContent == "") {
            return;
        }

        let request = $scope.getRequestInfo();

        let tempMessageInfo = {
            sender: 'me',
            messageContent: $scope.messageContent,
            timeInfo: moment().format('HH:mm A, MMM D')
        };

        if ($scope.current_session.status == 'Active') {

            profile = AuthService.GetCredentials();
            var msg = {};

            msg.session_id = $scope.current_session.id;
            msg.guest_name = profile.guest_name;
            msg.guest_id = profile.guest_id;
            msg.agent_id = $scope.current_session.agent_id;
            msg.property_id = profile.property_id;
            msg.room = profile.room;
            msg.text = $scope.messageContent;
            msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
            msg.direction = 1;  // outgoing
            msg.language = $scope.current_session.language;

            if( $scope.current_session.language != 'en') {
                TranslateService.translate(msg.text, $scope.current_session.language, 'en')
                    .then(function(response) {
                        var data = response.data.data;
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

            let filterResult = {};

            if (init == true) {
                filterResult = getFilterMessageContent();
            } else {
                filterResult = getFilterMessageContent($scope.messageContent.trim().toLowerCase());

                $scope.chatlist.push(tempMessageInfo);
                $scope.messageContent = "";

                $timeout(function () {
                    document.getElementById('chatbot-body').scrollTop = document.getElementById('chatbot-body').scrollHeight;
                }, 100);

                let savePromise = await onSaveChatBotHisotry(tempMessageInfo);
            }

            if (filterResult.type === TYPE_CHAT) {
                $scope.sendChatToServer(request, filterResult);
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
                        url: '/guest/addnewquicktask',
                        data: request,
                        headers: {'Content-Type': 'application/json; charset=utf-8'}
                    }).then(function (response) {

                        if (response.data.success == true) {
                            var data = response.data.result.task_info;

                            if (data.department == undefined) {
                                return;
                            }

                            if (data.staff_list.length < 1) {
                                data.staff_list.push({id: 0, user_id: 0, wholename: 'No Staff is on shift'});
                            }

                            var staff_name = data.staff_list[0].wholename;

                            profile = AuthService.GetCredentials();

                            var quicktask_data = {};

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
                            quicktask_data.guest_id = profile.guest_id;
                            quicktask_data.location_id = response.data.result.location_id;

                            var taskList = [];
                            taskList.push(quicktask_data);

                            $scope.isTyping = true;

                            $http({
                                method: 'POST',
                                url: '/guest/createtasklist',
                                data: taskList,
                                headers: {'Content-Type': 'application/json; charset=utf-8'}
                            }).then(function (response) {
                                if (response.data.invalid_task_list.length == 0) {
                                    console.log('success ================ ');

                                    request = $scope.getRequestInfo();
                                    filterResult = {
                                        type: TYPE_CHAT,
                                        content: CHAT_CREATE_QUICK_TASK_SUCCESS
                                    };

                                    $scope.sendChatToServer(request, filterResult);
                                } else {
                                    console.log('failed =================');
                                    request = $scope.getRequestInfo();

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
            url: '/guest/guestservice/prioritylist'
        }).success(function(data, status, headers, config) {
            $scope.prioritylist = data;
        }).error(function(data, status, headers, config) {
        });
    };

    $scope.onLoadedData = function (e) {
        e.preventDefault();

        e.target.scrollTop(e.target.height);
    };

    async function requestChat() {
        profile = AuthService.GetCredentials();
        var request = {};

        request.property_id = profile.property_id;
        request.guest_id = profile.guest_id;
        request.guest_name = profile.guest_name;
        request.room_id = profile.room_id;
        request.language = $scope.current_session.language;

        let response = await $http({
            method: 'POST',
            url: '/guest/requestchat',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });

        return response;
    }

    async function getInitAgentChatHistory() {
        profile = AuthService.GetCredentials();

        $scope.messages = [];

        var request = {};
        request.session_id = $scope.current_session.id;
        if( request.session_id < 1 ) {
            return;
        }

        let response = await $http({
            method: 'POST',
            url: '/guest/chathistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });

        return response;
    }

    async function getInitChatbotHistory() {
        profile = AuthService.GetCredentials();

        var request = {};
        request = $scope.getRequestInfo();

        let response = await $http({
            method: 'POST',
            url: '/guest/chatbothistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        });

        return response;
    }

    $scope.callToAgent = function () {
        var request = {};
        request.session_id = $scope.current_session.id;

        $http({
            method: 'POST',
            url: '/guest/calltoagent',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            var data = response.data;

            if (data.success == true) {
                currentChatInfo.chatName = "hello";
                $scope.current_session.status = "Waiting";
            } else {
                toaster.pop('error', 'Did not call to any agent...');
            }
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    var initial  = async function () {
        let chatHistoryResponse = await getInitChatbotHistory();
        $scope.current_session = chatHistoryResponse.data.session_info;

        if ($scope.current_session.status == 'Waiting') {
            currentChatInfo.chatName = 'hello'
        } else {
            currentChatInfo.chatName = chatHistoryResponse.data.cur_chat_name;
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
            $timeout(function () {
                $scope.onSendChatToServer(true);
            }, 1000);
        }

        $scope.getPriorityList();
    };

    initial();
});
