app.controller('AgentChatController', function ($scope, $rootScope, $window, $http, $uibModal, $timeout, $interval, $compile, AuthService, toaster, TranslateService, socket, Base64, Lightbox, Upload) {
    var value = angular.element('#app_config').val();
    value = Base64.decode(value);
    var app_config = JSON.parse(value);

    var site_url = app_config.site_url;

    $rootScope.unread_chat_cnt = 0;                    

	var profile = AuthService.GetCredentials();
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
    $scope.filter.agent_name = '';

    var old_agent = angular.copy($scope.agent);


    function getAgentList(select_flag) {
        var request = {};
        var profile = AuthService.GetCredentials();

        request.property_id = profile.property_id;
        request.agent_id = profile.id;
        request.online_flag = $scope.filter.online_flag;
        request.filter = $scope.filter.agent_name;

        $http({
            method: 'POST',
            url: '/frontend/chat/agentlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function (response) {
            $scope.agent_list = response.data.content;
            if( select_flag && $scope.agent_list.length > 0 )
            {
                $scope.agent = angular.copy($scope.agent_list[0]);
                showCurrentChatSession();
            }
        }).catch(function (response) {
        })
        .finally(function () {
            
        });
    }

    function getConversationList() {
        var request = {};
        var profile = AuthService.GetCredentials();

        request.from_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/chat/agentconversationhistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function (response) {
            $scope.conversation_list = response.data.list;
        }).catch(function (response) {
        })
        .finally(function () {
            
        });
    }

    getAgentList(true);
    getConversationList();

    $scope.onChangeOnline = function(online_flag) {
        getAgentList(true);            
    }

    $scope.onSearchAgent = function() {
        getAgentList(true);            
    }

    $scope.onSelectAgent = function(row) {
        old_agent = angular.copy($scope.agent);

        $scope.agent = angular.copy(row);        
        showCurrentChatSession();
    }

    $scope.onSelectConversation = function(row) {
        old_agent = angular.copy($scope.agent);

        $scope.agent.id = row.to_id;
        $scope.agent.agent_name = row.to_name;    
        $scope.agent.online_status = row.online_status;
        $scope.agent.to_picture = row.to_picture;    

        showCurrentChatSession();
    }

    $scope.messages = [];

    function getChatHistory(agent_id, last_id, new_flag) {
        if( new_flag )
            $scope.agent.last_id = 0;
        if($scope.agent.last_id < 0)
            return;

        var request = {};
        var profile = AuthService.GetCredentials();

        request.from_id = profile.id;
        request.to_id = agent_id;
        request.pageSize = 20;
        request.base_url = site_url;
        request.last_id = last_id;

        $http({
            method: 'POST',
            url: '/frontend/chat/agentchathistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
        .then(function (response) {
            $scope.agent.last_id = response.data.content.last_id;

            var list = response.data.content.list;            
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

        }).catch(function (response) {
        })
        .finally(function () {
            
        });
    }



    $scope.loadMore = function() {
        console.log("Load More");
        getChatHistory($scope.agent.id, $scope.agent.last_id, false);
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

    function showCurrentChatSession() {
        $scope.current_session = {};
        $scope.current_session.chat = '';
        $scope.current_session.typing_flag = 1;
        $scope.agent.last_id = 0;

        setReadFlag(old_agent);
        setUnreadCount($scope.agent.id, 0);
        getChatHistory($scope.agent.id, $scope.agent.last_id, true);
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

    $scope.onSendMessage = function(message) {
        if( !message )
            return;

        var profile = AuthService.GetCredentials();
        var msg = {};

        msg.property_id = profile.property_id;
        msg.from_id = profile.id;
        msg.to_id = $scope.agent.id;
        msg.type = 1; // text
        msg.text = message;
        msg.from_name = profile.wholename;
        msg.to_name = $scope.agent.agent_name;
        msg.from_picture = site_url + profile.picture;
        msg.to_picture = site_url + $scope.agent.picture;
        msg.direction = 1;  // outgoing
        msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');

        $scope.messages.push(msg);        

        socket.emit('agent_agent_msg', msg, function(confirmation){                          
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
            msg.to_id = $scope.agent.id;
            if( ext == 'pdf' )
                msg.type = 3; // image
            else
                msg.type = 2; // pdf
            
            msg.text = $file.name;
            msg.path = $file.name;
            msg.from_name = profile.wholename;
            msg.to_name = $scope.agent.agent_name;
            msg.from_picture = site_url + profile.picture;
            msg.to_picture = site_url + $scope.agent.picture;
            msg.direction = 1;  // outgoing
            msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');

            $scope.messages.push(msg);        

            socket.emit('agent_agent_msg', msg, function(confirmation){                          
                console.log(confirmation);
                var data = JSON.parse(confirmation);
                uploadChatfile(data, $file, msg);    
            });
        }
    }

    function uploadChatfile(data, $file, msg) {
        data.myfile = $file;
        Upload.upload({
                url: '/frontend/chat/uploadfile',
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
        msg.to_id = $scope.agent.id;
        msg.typing_flag = typing_flag;  // type flag

        socket.emit('agent_agent_typing', msg);
    }

    $scope.$on('agent_agent_msg', function(event, args){
        if( args.from_id != $scope.agent.id)  { // self message
            updateConversationList(args, args.unread_cnt);
            return;
        }

        var message = angular.copy(args);

        $scope.current_session.typing_flag = 1;

        message.direction = 0; // incoming
        message.to_picture = args.from_picture;
        message.from_picture = args.to_picture;
        message.text = args.text.replace(/\r?\n/g,'<br/>');

        $scope.messages.push(message);  
        updateConversationList(args, 0);
       
    });

    $scope.$on('agent_agent_typing', function(event, args){
        var message = args;
        if(message.from_id != $scope.agent.id )
            return;

        $scope.current_session.typing_flag = message.typing_flag;
    });

    $scope.$on('agent_chat_event', function(event, args){
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


});

