app.controller('GroupChatController', function ($scope, $rootScope, $window, $http, $uibModal, $timeout, $interval, $compile, AuthService, toaster, TranslateService, socket, Base64, Lightbox, Upload) {
    var value = angular.element('#app_config').val();
    value = Base64.decode(value);
    var app_config = JSON.parse(value);

    var site_url = app_config.site_url;
    var MESSAGE_TITLE = "Group Chat";
    $rootScope.unread_chat_cnt = 0;                    

	var profile = AuthService.GetCredentials();
    var property_id = profile.property_id;

    $scope.selected_md = 0;
	$scope.agent_id = profile.id;
    $scope.agent_chat_active = 0;

    $scope.agent_list = [];
    $scope.conversation_list = [];
    $scope.agent = {};

    $scope.agent.id = 0;
    $scope.agent.agent_name = {};
    $scope.agent.acitve_status = 0;
    $scope.agent.last_id = 0;

    $scope.filter = {};
    $scope.filter.online_flag = true;
    $scope.filter.group_name = '';

    $scope.groupchat = {};

    $scope.current_session  = {};

    $scope.participant = {};
    $scope.participantListNewGroup = [];

    $scope.groupchat_list = [];
    $scope.selected_groupchat = {};
    $scope.selected_groupchat.last_id = 0;

    var old_group = angular.copy($scope.selected_groupchat);

    $scope.participant_detail = {};
    $scope.participantListDetailGroup = [];

    $http.get('/list/userlist?property_id=' + property_id)
        .then(function (response) {
            $scope.user_list = response.data;
        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function () {
        });

    $http.get('/frontend/chat/group/list')
        .then(function (response) {
            $scope.groupchat_list = response.data.group_list;
            if($scope.groupchat_list.length > 0)
                $scope.onSelectGroup($scope.groupchat_list[0]);
        }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function () {
        });



    $scope.onChangeOnline = function(online_flag) {
        getAgentList(true);            
    }

    $scope.onSearchGroup = function() {
        if(!$scope.filter.group_name || $scope.filter.group_name == '')
            return;

        $http.get('/frontend/chat/group/list?group_name='+$scope.filter.group_name)
            .then(function (response) {
                $scope.groupchat_list = response.data.group_list;
                if($scope.groupchat_list.length > 0)
                    $scope.onSelectGroup($scope.groupchat_list[0]);
            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
            });
    }


    $scope.onSelectGroup = function(row) {
        old_group = angular.copy($scope.selected_groupchat);
        $scope.selected_groupchat = angular.copy(row);

        showCurrentGroupChatSession();
        $http.get('/frontend/chat/group/detail?group_id='+$scope.selected_groupchat.id)
            .then(function (response) {
                $scope.participantListDetailGroup = response.data.list;
                var part_arr = [];
                for(var i = 0 ; i < $scope.participantListDetailGroup.length ; i++)
                {
                    part_arr.push($scope.participantListDetailGroup[i].user_id);
                }
                $scope.selected_groupchat.part_id_list = part_arr;

            }).catch(function (response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function () {
            });
    }

    $scope.onParticipantSelect = function(row) {

        for(var i = 0 ; i < $scope.participantListNewGroup.length ; i++)
        {
            if($scope.participantListNewGroup[i].id == row.id)
            {
                $scope.participant = {};
                return;
            }

        }
        $scope.participant = angular.copy(row);
    }
    $scope.onParticipantDetailSelect = function(row) {
        for(var i = 0 ; i < $scope.participantListDetailGroup.length ; i++)
        {
            if($scope.participantListDetailGroup[i].user_id == row.id)
            {
                $scope.participant_detail = {};
                return;
            }
        }
        $scope.participant_detail = angular.copy(row);
    }

    $scope.addNewParticipant = function(){

        $scope.participantListNewGroup.push($scope.participant);
        $scope.participant = {};
    }

    $scope.addNewParticipantDetail = function(){
        $scope.participant_detail.user_id = $scope.participant_detail.id;
        $scope.participantListDetailGroup.push($scope.participant_detail);
        $scope.participant_detail = {};
    }

    $scope.removeNewParticipant = function(row){

        $scope.participantListNewGroup.splice($scope.participantListNewGroup.indexOf(row),1);
    }
    $scope.removeNewParticipantDetail = function(row){

        $scope.participantListDetailGroup.splice($scope.participantListDetailGroup.indexOf(row),1);
    }

    $scope.messages = [];

    function getGroupChatHistory(group_id, last_id, new_flag) {

        if(!group_id)
            return;
        if( new_flag )
            $scope.selected_groupchat.last_id = 0;
        if($scope.selected_groupchat.last_id < 0)
            return;

        var request = {};
        var profile = AuthService.GetCredentials();

        request.user_id = profile.id;
        request.group_id = group_id;
        request.pageSize = 20;
        request.base_url = site_url;
        request.last_id = last_id;

        $http({
            method: 'POST',
            url: '/frontend/chat/groupchathistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function (response) {

                console.log(response.data);
                $scope.selected_groupchat.last_id = response.data.last_id;
                var list = response.data.list;
                for(var i = 0; i < list.length; i++ )
                    list[i].text = list[i].text.replace(/\r?\n/g,'<br/>');

                if( new_flag )
                    $scope.messages = list.reverse();
                else
                {
                    if( !$scope.messages )
                        $scope.messages = [];
                    $scope.messages = list.reverse().concat($scope.messages);
                }
                console.log($scope.messages);

            }).catch(function (response) {
        })
            .finally(function () {

            });
    }

    $scope.loadMore = function() {
        console.log("Load More");
        getGroupChatHistory($scope.selected_groupchat.id, $scope.selected_groupchat.last_id, false);
    }

    function setUnreadCount(agent_id, cnt) {
        for(var i = 0; i < $scope.conversation_list.length;i++)
        {
            if( $scope.conversation_list[i].to_id == agent_id )
            {
                $scope.conversation_list[i].unread_cnt = 0;
                break;     
            }
        }
    }

    function updateConversationList(msg, cnt) {
        var selected_num = -1;
        for(var i = 0; i < $scope.conversation_list.length;i++)
        {
            if( $scope.conversation_list[i].to_id == msg.from_id )
            {
                selected_num = i;
                $scope.conversation_list[i].unread_cnt = cnt;
                break;     
            }
        }

        if( selected_num >= 0 ) // exist
        {
            var first = [angular.copy($scope.conversation_list[selected_num])];
            first[0].text = msg.text;
            $scope.conversation_list.splice(selected_num, 1);
            $scope.conversation_list = first.concat($scope.conversation_list);
        }
        else // not exist
        {
            getConversationList();
        }
    }


    function showCurrentGroupChatSession() {

        $scope.current_session = {};
        $scope.current_session.chat = '';
        $scope.current_session.typing_flag = 1;

        $scope.selected_groupchat.last_id = 0;

        //setReadFlag(old_agent);
        //setUnreadCount($scope.agent.id, 0);
        getGroupChatHistory($scope.selected_groupchat.id, $scope.selected_groupchat.last_id, true);
    }

    $scope.$on('$destroy', function() {
        setReadFlag($scope.agent);
    });

    function setReadFlag(agent) {
        var request = {};
        var profile = AuthService.GetCredentials();

        request.from_id = profile.id;
        request.to_id = agent.id;
        request.last_id = agent.last_id;

        $http({
            method: 'POST',
            url: '/frontend/chat/setreadflag',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function (response) {
            
        }).catch(function (response) {
        })
        .finally(function () {
            
        });
    }

    $scope.onSendMessageGroup = function(message) {
        if( !message )
            return;

        var profile = AuthService.GetCredentials();
        var msg = {};

        msg.property_id = profile.property_id;
        msg.from_id = profile.id;

        var index = $scope.selected_groupchat.part_id_list.indexOf(profile.id);
        if (index !== -1) $scope.selected_groupchat.part_id_list.splice(index, 1);

        msg.to_ids = $scope.selected_groupchat.part_id_list;
        msg.group_id = $scope.selected_groupchat.id;
        msg.type = 1; // text
        msg.text = message;
        msg.from_name = profile.wholename;
        msg.from_picture = site_url + profile.picture;
        msg.direction = 1;  // outgoing
        msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');

        $scope.messages.push(msg);        

        socket.emit('group_msg', msg, function(confirmation){
                console.log(confirmation);
            });
        
        $scope.current_session.chat = '';
    }

    $scope.onClickAttach = function($file) {
        if ($file) {

            var profile = AuthService.GetCredentials();

            var msg = {};

            var ext = $file.name.split('.').pop().toLowerCase();

            msg.property_id = profile.property_id;
            msg.from_id = profile.id;
            msg.group_id = $scope.selected_groupchat.id;
            msg.to_ids = $scope.selected_groupchat.part_id_list;
            if( ext == 'pdf' )
                msg.type = 3; // image
            else
                msg.type = 2; // pdf
            
            msg.text = $file.name;
            msg.path = $file.name;
            msg.from_name = profile.wholename;
            msg.from_picture = site_url + profile.picture;
            msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');

            $scope.messages.push(msg);        

            socket.emit('group_msg', msg, function(confirmation){
                console.log(confirmation);
                var data = JSON.parse(confirmation);
                uploadChatfile(data, $file, msg);    
            });
        }
    }

    function uploadChatfile(data, $file, msg) {
        data.myfile = $file;
        Upload.upload({
                url: '/frontend/chat/uploadfilegroup',
                data: data
            }).then(function (response) {
               msg.path = site_url + response.data.content; 
            }, function (response) {
               
            }, function (evt) {
                $scope.progress = 
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
    }

    $scope.onChangeChat = function(message) {
        console.log('input is changed');
        
        if( !$scope.pause )
        {
            console.log('typing is started'); 

            sendTypingEvent(0);
        }

        $timeout.cancel($scope.pause);
        $scope.pause = $timeout(function() {        
            console.log('typing is ended');   
            $scope.pause = null;

            sendTypingEvent(1);
        }, 6000);
    }

    function sendTypingEvent(typing_flag) {
        var profile = AuthService.GetCredentials();

        var msg = {};

        msg.property_id = profile.property_id;
        msg.from_id = profile.id;
        msg.group_id = $scope.selected_groupchat.id;
        var index = $scope.selected_groupchat.part_id_list.indexOf(profile.id);
        if (index !== -1) $scope.selected_groupchat.part_id_list.splice(index, 1);

        msg.to_ids = $scope.selected_groupchat.part_id_list;
        msg.typing_flag = typing_flag;  // type flag

        socket.emit('group_typing', msg);
    }

    $scope.$on('group_msg', function(event, args){

        if( args.from_id == $scope.agent_id)  { // self message
            return;
        }

        var message = angular.copy(args);

        $scope.current_session.typing_flag = 1;
        message.from_picture = args.from_picture;
        message.text = args.text.replace(/\r?\n/g,'<br/>');

        console.log(message);
        $scope.messages.push(message);

    });

    $scope.$on('group_typing', function(event, args){
        var message = args;
        if($scope.selected_groupchat.part_id_list.indexOf(message.from_id) == -1 )
            return;

        $scope.current_session.typing_flag = message.typing_flag;
    });

    $scope.$on('group_chat_event', function(event, args){

        var type = args.type;
        var sub_type = args.sub_type;
        var message = args.data;
        if( sub_type == 'uploaded_file_chat' )
        {
            for(var i = $scope.messages.length - 1; i >= 0; i--)
            {
                if( message.id == $scope.messages[i].id )
                {
                    $scope.messages[i].path = message.path;
                    break;
                }
            }
        }
    });

    $scope.$on('agent_status_event', function(event, args){
        var agent = args.data;        

        // update agent list
        for(var i = 0; i < $scope.agent_list.length;i++)
        {
            if( $scope.agent_list[i].id == agent.id )
            {
                $scope.agent_list[i].online_status = agent.online_status;
                break;     
            }
        }

        // update conversation list
        for(var i = 0; i < $scope.conversation_list.length;i++)
        {
            if( $scope.conversation_list[i].to_id == agent.id )
            {
                $scope.conversation_list[i].online_status = agent.online_status;
                break;     
            }
        }

        // update cuurent agent status
        if( agent.id == $scope.agent.id )
            $scope.agent.online_status = agent.online_status;
    });

    $scope.onShowImage = function(msg) {
        $scope.image_series = [];
        var index = -1;
        var count = 0;
        for(var i = 0; i < $scope.messages.length; i++ )
        {
            if( $scope.messages[i].type == 2 ) // image
            {
                var slide = {};
                slide.url = $scope.messages[i].path;
                slide.caption = $scope.messages[i].text;
                $scope.image_series.push(slide);
                if( msg.id == $scope.messages[i].id )    
                    index = count;                    
                
                count++;
            }
        }    

        Lightbox.openModal($scope.image_series, index);    
    }

    $scope.uploadFiles = function (files) {
        if(files.length > 0)
        {
            $scope.groupchat.profile_picture = files[0];
            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                $scope.groupchat.profile_picture_thumbnail = loadEvent.target.result;
            }
            reader.readAsDataURL(files[0]);
        }

    }
    $scope.removeFile = function() {
        $scope.groupchat.profile_picture = {};
        $scope.groupchat.profile_picture_thumbnail = {};
    }
    $scope.isSelectedPicture = 0;
    $scope.uploadFilesDetail = function (files) {
        if(files.length > 0)
        {
            $scope.selected_groupchat.profile_picture = files[0];
            var reader = new FileReader();
            reader.onload = function (loadEvent) {
                $scope.selected_groupchat.profile_picture_thumbnail = loadEvent.target.result;
            }
            reader.readAsDataURL(files[0]);
        }
        $scope.isSelectedPicture = 1;

    }
    $scope.removeFileDetail = function() {
        $scope.selected_groupchat.profile_picture = {};
        $scope.selected_groupchat.profile_picture_thumbnail = {};
        $scope.isSelectedPicture = 0;
    }
    $scope.openModalImage = function (imageSrc, imageDescription) {
        var modalInstance = $uibModal.open({
            templateUrl: "modalImage.html",
            resolve: {
                imageSrcToUse: function () {
                    return imageSrc;
                },
                imageDescriptionToUse: function () {
                    return imageDescription;
                }
            },
            controller: [
                "$scope", "imageSrcToUse", "imageDescriptionToUse",
                function ($scope, imageSrcToUse, imageDescriptionToUse) {
                    $scope.ImageSrc = imageSrcToUse;
                    return $scope.ImageDescription = imageDescriptionToUse;
                }
            ]
        });
        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.createGroup = function()
    {
        if($scope.groupchat.group_name == "" || !$scope.groupchat.group_name){
            toaster.pop('error', MESSAGE_TITLE, "You must input group name.");
            return;
        }
        if($scope.participantListNewGroup.length < 1){
            toaster.pop('error', MESSAGE_TITLE, "You must add participants of group.");
            return;
        }

        var request = angular.copy($scope.groupchat);
        var part_ids = [];
        for(var i = 0 ; i< $scope.participantListNewGroup.length;i++)
        {
            part_ids.push($scope.participantListNewGroup[i]["id"]);
        }
        request.participants = part_ids;

        request.user_id = profile.id;
        $http.post('/frontend/chat/group/create', request)
            .then(function (response) {

                if(response.data.group_id)
                {
                    var added_group_id = response.data.group_id;

                    if($scope.groupchat.profile_picture)
                    {
                        Upload.upload({
                            url: '/frontend/chat/group/uploadprofileimage',
                            data: {
                                group_id: added_group_id,
                                files: $scope.groupchat.profile_picture
                            }
                        }).then(function (response) {
                            toaster.pop('info', MESSAGE_TITLE, "Group created successfully.");
                            $scope.cancelGroup();
                            $scope.groupchat_list = response.data.group_list;

                            for(var i = 0 ; i < $scope.groupchat_list.length ; i++)
                            {
                                if($scope.groupchat_list[i].id == added_group_id)
                                {
                                    $scope.onSelectGroup($scope.groupchat_list[i]);
                                    break;
                                }
                            }

                            $scope.selected_md = 0;
                        }, function (response) {
                            console.error('Gists error', response.status, response.data);
                        }, function (evt) {

                        });
                    }
                    else{

                        toaster.pop('info', MESSAGE_TITLE, "Group created successfully.");
                        $scope.cancelGroup();
                        $scope.groupchat_list = response.data.group_list;

                        for(var i = 0 ; i < $scope.groupchat_list.length ; i++)
                        {
                            if($scope.groupchat_list[i].id == added_group_id)
                            {
                                $scope.onSelectGroup($scope.groupchat_list[i]);
                                break;
                            }
                        }

                        $scope.selected_md = 0;
                    }
                }else{
                    toaster.pop('error', MESSAGE_TITLE, "Group Chat with same name has already been created. Please use different name");
                    $scope.groupchat.group_name = "";
                }
            }
            ).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
            });

    }
    $scope.cancelGroup = function()
    {
        $scope.removeFile();
        $scope.groupchat = {};
        $scope.participantListNewGroup = [];
    }

    $scope.updateGroup = function()
    {
        if($scope.selected_groupchat.groupchat_name == "" || !$scope.selected_groupchat.groupchat_name){
            toaster.pop('error', MESSAGE_TITLE, "You must input group name.");
            return;
        }
        if($scope.participantListDetailGroup.length < 1){
            toaster.pop('error', MESSAGE_TITLE, "You must add participants of group.");
            return;
        }

        var request = angular.copy($scope.selected_groupchat);
        var part_ids = [];
        for(var i = 0 ; i< $scope.participantListDetailGroup.length;i++)
        {
            part_ids.push($scope.participantListDetailGroup[i]["user_id"]);
        }

        console.log($scope.participantListDetailGroup);
        console.log(part_ids);

        request.participants = part_ids;
        request.user_id = profile.id;
        $http.post('/frontend/chat/group/update', request)
            .then(function (response) {
                console.log(response);

                if(response.data.group_id) {
                    if($scope.isSelectedPicture == 1)
                    {
                        Upload.upload({
                            url: '/frontend/chat/group/uploadprofileimage',
                            data: {
                                group_id: response.data.group_id,
                                files: $scope.selected_groupchat.profile_picture
                            }
                        }).then(function (response) {
                            toaster.pop('info', MESSAGE_TITLE, "Group updated successfully.");

                            $scope.groupchat_list = response.data.group_list;

                            for(var i = 0 ; i < $scope.group_list.length ; i++)
                            {
                                if($scope.group_list[i].id == $scope.selected_groupchat.id)
                                    $scope.onSelectGroup($scope.group_list[i]);
                            }

                        }, function (response) {
                            console.error('Gists error', response.status, response.data);
                        }, function (evt) {

                        });
                    }else {
                        toaster.pop('info', MESSAGE_TITLE, "Group updated successfully.");
                        $scope.group_list = response.data.group_list;

                        for(var i = 0 ; i < $scope.group_list.length ; i++)
                        {
                            if($scope.group_list[i].id == $scope.selected_groupchat.id)
                                $scope.onSelectGroup($scope.group_list[i]);
                        }

                    }
                }else{

                    toaster.pop('error', MESSAGE_TITLE, "Group Chat with same name has already been created. Please use different name");
                    $scope.selected_groupchat.groupchat_name = "";
                }

            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {

            });

    }
    $scope.deleteGroup = function() {
        if (confirm("Really delete this group?")) {
            $http.get('/frontend/chat/group/delete?group_id=' + $scope.selected_groupchat.id)
                .then(function (response) {

                    toaster.pop('info', MESSAGE_TITLE, "Group deleted successfully.");
                    $scope.groupchat_list = response.data.group_list;
                    if($scope.groupchat_list.length > 0)
                        $scope.onSelectGroup($scope.groupchat_list[0]);
                }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
                .finally(function () {

                });
        }
    }

});

