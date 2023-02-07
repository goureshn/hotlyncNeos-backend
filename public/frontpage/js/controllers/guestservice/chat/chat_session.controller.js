'use strict';

app.controller('ChatSessionController', function($scope, $rootScope, $http, $interval, $stateParams, $window, $timeout, toaster, AuthService, GuestService, TranslateService, socket) {
    var MESSAGE_TITLE = 'Chat Page';

	$scope.content_height = 'height: ' + ($window.innerHeight - 380) + 'px; overflow-y: auto';

	var profile = AuthService.GetCredentials();

	$scope.content = {};
	$scope.content.chat = '';
	$scope.content.user_id = profile.id;

	$scope.messages = [];
	$scope.room = {};

	$scope.init = function(room) {
		$scope.room = room;		
        $scope.pause = null;
		
		getChatHistory();
	}

    $scope.$watch('room.height', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.room.chat_height = newValue - 100;
    });

    function getChatHistory() {
    	var profile = AuthService.GetCredentials();

        $scope.room.lang_code = 'en';

	    $scope.messages = [];

	    var request = {};

	    request.session_id = $scope.room.id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/chathistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.messages = response.data;

            for(var i = $scope.messages.length - 1; i >= 0; i-- )   // last guest's language
            {
                if( $scope.messages[i].direction == 0 ) // incoming guest message
                {
                    $scope.room.lang_code = $scope.messages[i].language;
                    break;
                }
            } 
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to get Chat History.');
        })
        .finally(function() {

        });
	}

	$scope.onSendMessage = function(message) {
        if( !message )
            return;
        
		var profile = AuthService.GetCredentials();
	    var msg = {};

        msg.session_id = $scope.room.id;
	    msg.guest_id = $scope.room.guest_id;
	    msg.property_id = profile.property_id;
	    msg.agent_id = profile.id;
	    msg.agent_name = profile.first_name + ' ' + profile.last_name;
        msg.lang_code = $scope.room.lang_code;

	    msg.text = message;
	    msg.created_at = moment().format('YYYY-MM-DD HH:mm:ss');
	    msg.direction = 1;  // outgoing

        if( msg.lang_code != 'en')
        {
            TranslateService.translate(msg.text, 'en', msg.lang_code)
            .then(function(response) {
                var data = response.data.data;
                msg.text_trans = data.translations[0].translatedText;
            }).catch(function(response) {               
            })
            .finally(function() {              
                $scope.messages.push(msg);        
                socket.emit('agent_msg', msg);
            });    
        }
        else
        {
            msg.text_trans = '';
            $scope.messages.push(msg);   
            socket.emit('agent_msg', msg);
        }
        
	    $scope.content.chat = '';
  	}

    $scope.onChangeChat = function(message) {
        console.log('input is changed');
        
        var profile = AuthService.GetCredentials();
        
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
        msg.session_id = $scope.room.id;
        msg.agent_id = profile.id;
        msg.guest_id = $scope.room.guest_id;
        msg.typing_flag = typing_flag;  // type start

        socket.emit('agent_typing', msg);        
    }

  	$scope.$on('guest_message', function(event, args){
  		if(args.session_id != $scope.room.id )
            return;

        var message = angular.copy(args);

        console.log(message);

        $scope.room.typing_flag = 0;

  		message.direction = 0; // incoming

        $scope.room.lang_code = message.language;
        
        for(var i = 0; i < $scope.task_list_item.length; i++)
            message.text = message.text.replace($scope.task_list_item[i].task, '<a ng-dblclick="onCreateTask(' + i + ', \'' + $scope.task_list_item[i].task + '\')" ng-sglclick="onSelectTask(' + i + ', \'' + $scope.task_list_item[i].task + '\')">' + $scope.task_list_item[i].task + '</a>');

        $scope.messages.push(message);  
    });

    $scope.$on('guest_typing', function(event, args){
        var message = args;
        if(message.session_id != $scope.room.id )
            return;

        $scope.room.typing_flag = message.typing_flag;
    });

    $scope.$on('guest_status', function(event, args){
  		var data = args;
  		if( data.guest_id == $scope.room.guest_id )
  		{
  			$scope.room.status = data.status;
  		}
    });

    $scope.onCreateTask = function(num, task_name) { 
        $scope.showRequestWithRoomAndTask(1, $scope.room, $scope.task_list_item[num]);        
    }

    $scope.onSelectTask = function(num, task_name) { 
        $scope.showRequestWithRoomAndTask(1, $scope.room, $scope.task_list_item[num]);
    }

});