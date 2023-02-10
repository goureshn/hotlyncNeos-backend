
$(document).ready(function() {
    var enableChat = true;
    var timer = setInterval(function() {
        /*if (checkOfficeTime()) {
            enableChat = true;
            $("#officetime").html("<b>&nbsp;&nbsp;&nbsp;Operating Hours</b><br>&nbsp;&nbsp;&nbsp;Available daily from 9:00am to 10:00pm")
        }
        else {
            enableChat = false;
            $("#officetime").html("<b>&nbsp;&nbsp;&nbsp;Sorry, Chat service is currently unavailable.<br>&nbsp;&nbsp;&nbsp;Do contact us again during our Operating Hours.</b>")
        }*/
		enableChat = true;
    }, 3000);
    
    var isWrite = false;
    if($('#is_write').val() == "1") {
        isWrite = true;
    }

    $(function() {
        $("#selectable").selectable({
            stop: function() {
                $(".ui-selected", this).each(function() {
                    var usernameId = "#" + $(this).val() + "_username";
                    var username = $(usernameId).val();
                    removeMsgCount(username);
                    displayUserInfo(username);
                });
            }
        });
    });

    var selusername = "#" + $("#to_uid").val();
    $(selusername).addClass("ui-selected");

    $.ionSound({
        sounds: [
            "beer_can_opening",
            "bell_ring",
            "branch_break",
            "receive"
        ],
        path: "",
        multiPlay: true,
        volume: "1.0"
    });

    /**
     * Appends a chat message with the specified username, the specified
     * datetime and the specified text to the chatlog.
     *
     * @param {string} username The username of the user who wrote the message.
     * @param {string} datetime The datetime of the message.
     * @param {string} text     The text of the message.
     *
     * @returns {void}
     */
    function addChatLineToChatlog(username, datetime, text) {
        if (datetime == "now") {
            var now = new Date();
            datetime = [[AddZero(now.getDate()), AddZero(now.getMonth() + 1), now.getFullYear()].join("/"), [AddZero(now.getHours() >= 12 ? (now.getHours() - 12) : now.getHours()), AddZero(now.getMinutes()), AddZero(now.getSeconds())].join(":"), now.getHours() >= 12 ? "PM" : "AM"].join(" ");
            //datetime = now.getDate() + "/" + (now.getMonth() + 1) + "/" +now.getFullYear() + " ";
            //datetime += (now.getHours() >=12?(12-now.getHours()):now.getHours())
        }

        addLineToChatlog(
                '<span class="uid">' + username + '</span>&nbsp;&nbsp;&nbsp;<span class="ts">'
                + datetime + '</span><br>' + text,
                'message'
                );
    }

    function replaceURL(text) {
        var resultText = '';
        var httpRegExp = /\b((?:[a-z][\w-]+:(?:\/{1,3}|[a-z0-9%])|www\d{0,3}[.]|[a-z0-9.-]+[.][a-z]{2,4}\/)(?:(?:[^\s()<>.]+[.]?)+|((?:[^\s()<>]+|(?:([^\s()<>]+)))))+(?:((?:[^\s()<>]+|(?:([^\s()<>]+))))|[^\s`!()[]{};:'".,<>?«»“”‘’]))/gi;
        var emailRegExp = /([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;

        var wordArray = text.split(' ');

        var str = '';
        var index = -1;
        for (var i = 0; i < wordArray.length; i++) {
            if (httpRegExp.test(wordArray[i])) {
                str = wordArray[i];
                index = str.indexOf('http');

                if (index == -1) {
                    str = 'http://' + str;
                }

//                if (index > 0) {
//                    str = str.substr(0, index) + 'http://' + str;
//                }

                str = "<a target=_blank href='" + str + "'>" + wordArray[i] + "</a>";

                resultText += (resultText == '' ? str : (' ' + str));
            }
            else if (emailRegExp.test(wordArray[i])) {
                str = "<a target=_blank href='mailto:" + wordArray[i] + "'>" + wordArray[i] + "</a>";
                resultText += (resultText == '' ? str : (' ' + str));
            }
            else {
                str = wordArray[i];
                resultText += (resultText == '' ? str : (' ' + str));
            }
        }

        return resultText;
    }

    /**
     * Appends a line (HTML list item <li> element) with a specified text and
     * a specified type to the chatlog.
     *
     * @param {string} text The text to append.
     * @param {string} type The output type ('info', 'warning' or 'error').
     *
     * @returns {void}
     */
    function addLineToChatlog(text, type) {
        $('#chatlog').append('<li class="' + type + '">' + text + '</li>');
        //$('.message').css('background-color', 'red');
        // auto scroll
        var objDiv = document.getElementById("message-thread12");
        objDiv.scrollTop = objDiv.scrollHeight;
    }

    /**
     * Refreshes the state of the form user interface (UI) elements.
     *
     * @param {WebSocketConnection} ws The WebSocketConnection.
     *
     * @returns {void}
     */
    function refreshUserInterface(ws) {
        var disabled = ws.isClosed();
//		alert(disabled);
        //$('#uid').attr('disabled', !disabled);
        //$('#connect').attr('disabled', !disabled);
        //$('#disconnect').attr('disabled', disabled);
        $('#msg').attr('disabled', disabled);
        $('#send').attr('disabled', disabled);

        $(".selectable").selectable({disabled: disabled});

        if (disabled) {
            $('.ajax-file-upload').css('visibility', 'hidden');
        }
        else {
            $('.ajax-file-upload').css('visibility', 'show');
        }

//        var is_write = $('#is_write').val();
//        if (is_write == "0") {
//            $('.ajax-file-upload').css('visibility', 'hidden');
//            $('#msg').attr('disabled', true);
//            $('#send').attr('disabled', true);
//        }
//        else {
//            $('.ajax-file-upload').css('visibility','show');
//        }
    }

    function sendPush(username, msg) {
        // Increse badge number
        badge = badge + 1;

        var sendData = {
            username: username,
            msg: msg,
            badge: badge
        };

        $.ajax({
            type: "POST",
            cache: false,
            url: 'send_push.php',
            data: sendData,
            async: true,
            dataType: 'text',
            success: function(response) {
                if (response == "The message do not sent by push.") {
                    badge = badge - 1;
                }
                else {
                    // increase bage
                    var badgeId = "#badge_" + username;
                    var old_b = $(badgeId).val();
                    var new_b = parseInt(old_b, 10);
                    if ($.isNumeric(new_b)) {
                        new_b++;
                    }
                    else {
                        new_b = 1;
                    }
                    // set new badge
                    $(badgeId).val("" + new_b);
                }
                addLineToChatlog(response, 'info');
            },
            error: function(response) {
                badge = badge - 1;
                addLineToChatlog('The message do not sent by push.', 'info');
            }
        });
    }

    function getChatLog(fromuid, tosuername) {
        var sendData = {
            from: fromuid,
            to: tosuername
        };

        $.ajax({
            type: "POST",
            cache: false,
            url: 'get_chat_log.php',
            data: sendData,
            async: true,
            dataType: 'json',
            success: function(response) {
                /////////////////
//                var response = $.parseJSON(response1);
//                for (i = 0; i < response.length; i++) {
//                    alert(dummy_url_decode(response[i].date));
//                }
                ////////////////            

                response.sort(function(a, b) {
                    var a1 = a.id, b1 = b.id;
                    if (a1 == b1)
                        return 0;
                    return a1 > b1 ? 1 : -1;
                });

                var type = 1;
                var i = 0;
                var message = '';
                var path = '';
                for (i = 0; i < response.length; i++) {
                    type = response[i].type;
                    if (type == 1) {
                        message = replaceURL(decodeURL(response[i].content));
                        addChatLineToChatlog(response[i].from, decodeURL(response[i].date), message);
                    }
                    else {
                        path = decodeURL(response[i].content);//.split('¥').join('');alert(path);
                        message = "<a href='" + path + "' target='_blank'><img src='" + path + "' width='50px' height='50px'></a>";
                        addChatLineToChatlog(response[i].from, decodeURL(response[i].date), message);
                    }
                }
                /*
                 
                 */
            },
            error: function(response) {
                addLineToChatlog('Do not show chat logs.', 'info');
            }
        });
    }

    function decodeURL(url) {
        // fixed -- + char decodes to space char
        var o = url;
        var binVal, t, b;
        var r = /(%[^%]{2}|\+)/;
        while ((m = r.exec(o)) != null && m.length > 1 && m[1] != '') {
            if (m[1] == '+') {
                t = ' ';
            } else {
                b = parseInt(m[1].substr(1), 16);
                t = String.fromCharCode(b);
            }
            o = o.replace(m[1], t);
        }
        return o;
    }

    /**
     * The JSON configuration.
     *
     * * config.host      The name of the host to connect to.
     * * config.resource  The name of the resource to connect to.
     * * config.port      The port to connect to.
     * * config.secure    Whether to use a secure connection.
     * * config.protocols One or more sub-protocols that the server must support
     *                    for the connection to be successful.
     */
    var config = null;
    var badge = 0;

    $.ajax({
        url: 'config.json',
        async: false,
        dataType: 'json',
        success: function(response) {
            config = response;
        },
        error: function(response) {
            $('#chatclient').fadeOut('slow');
            $('<p>Unable to read the JSON configuration file.</p>')
                    .appendTo('[role="main"]');
        }
    });

//    addLineToChatlog('The chat client has been loaded.', 'info');
	
    if (!('WebSocket' in window)) {
        $('#chatclient').fadeOut('slow');
        $('<p>The web browser does not support the WebSocket protocol.</p>')
                .prependTo('[role="main"]');
				
    }
    else {
	
        // The web browser does support the WebSocket protocol.
        var ws = new WebSocketConnection(
                config.host, config.port, config.resource, config.secure,
                config.protocols
                );
//		alert(config.host+":"+config.port+":"+config.resource+":"+config.secure+":"+config.protocols+":"+ws.uri);
        //$('<p>' . ws.uri . '</p>').appendTo('[role="main"]');
        refreshUserInterface(ws);

        // Event handler

        /**
         * Create a new WebSocket if the "Connect" button is clicked.
         *
         * @event
         */
//        $('#connect').click(function() {
        if (ws.isOpened()) {
            addLineToChatlog('The connection is already open.', 'warning');
            return;
        }

        var username = $('#from_uid').val();
        if ('' == username) {
            addLineToChatlog('Please enter an username.', 'warning');
            return;
        }

//        addLineToChatlog('Trying to connect to "' + ws.uri + '"...', 'info');
        addLineToChatlog('Trying to connect to server ...', 'info');

        try {
            // Triggers event onopen on success.
            ws.open();

            // WebSockets is an event-driven API.

            /**
             * The WebSocket `onopen` event.
             *
             * @event
             */
            ws.socket.onopen = function() {
                refreshUserInterface(ws);
                addLineToChatlog('The connection has been opened.', 'info');

                // Send the username to authenticate the chat client at the chat server.					
                var authMsg = {
                    action: 'AUTH',
                    userid: username,
                    password: username
                };

                var message = JSON.stringify(authMsg);
                ws.send(message);
            }

            /**
             * The WebSocket `onmessage` event.
             *
             * @event
             */
            ws.socket.onmessage = function(msg) {
                var json = $.parseJSON(msg.data);

                var action = json.action;

                if (action == "AUTH") {
                    var code = json.code;
                    if (code == '200') {
                        addLineToChatlog('You logged in.', 'info');

                        // check online status
                        var status = $('#is_online').val();
                        if (status == "0") {
                            addLineToChatlog('User is "offline" and may not reply immediatly.', 'error');
                        }

                        // sho msg logs
                        var from = $('#from_uid').val();
                        var to = $('#to_uid').val();
                        getChatLog(from, to);

                        var msgInputField = $('#msg');
                        // Set focus
                        msgInputField.focus();
                    }
                    else if (code == '330') {
                        addLineToChatlog('Another user already logged on with this user.', 'error');

                        //////////////////////////
                        try {
                            ws.close();
                        } catch (ex) {
                            addLineToChatlog('Exception: ' + ex, 'error');
                        }

                        refreshUserInterface(ws);
                        /////////////////////////

                        return;
                    }
                    else {
                        addLineToChatlog('Failed login. Try again!', 'error');
                        return;
                    }
                }
                else if (action == "JOIN") {
                    var code = json.code;
                    var joinUser = json.to;
                    //var joinUserId = json.uid;
                    //var joinDisplayname = json.displayname;

                    // If new user joined
                    if (code == '100') {
                        // if user is current user, set to online
                        var to = $('#to_uid').val();
                        if (to == joinUser) {
                            $('#is_online').val("1");
                            badge = 0;
                        }
                        else {
                            var statusId = "#" + joinUser + "_status";
                            $(statusId).val("1");
                        }

                        // set status image to online
                        var statusImgId = "#" + joinUser + "_status_photo";
                        $(statusImgId).attr('src', $("#online_link").val());

                        // init bage
                        var badgeId = "#badge_" + joinUser;
                        $(badgeId).val(0);

                        addLineToChatlog('User "' + joinUser + '" joined', 'warning');
                    }
                    // If exist user go out
                    else if (code == '150') {
                        // if user is current user, set to offline
                        var to = $('#to_uid').val();
                        if (to == joinUser) {
                            $('#is_online').val("0");
                        }
                        else {
                            var statusId = "#" + joinUser + "_status";
                            $(statusId).val("0");
                        }

                        // set status image to online
                        var statusImgId = "#" + joinUser + "_status_photo";
                        $(statusImgId).attr('src', $("#offline_link").val());

                        addLineToChatlog('User "' + joinUser + '" log out.', 'warning');
                    }
                }
                else if (action == "MSG") {
                    var from = json.from;
                    var message = json.message;

                    // if user is current user, show message
                    var to = $('#to_uid').val();
                    if (to == from) {
                        addChatLineToChatlog(from, 'now', message);

                    }
                    else {
                        // set message count
                        increseMsgCount(from);
                    }

                    //////////////
                    $.ionSound.play("receive");
                    //////////////
                }
                else if (action == "SHAREFILE") {
                    var from = json.from;
                    var path = json.path;
                    path = path.split('¥').join('')
                    var message = "<a href='" + path + "' target='_blank'><img src='" + path + "' width='50px' height='50px'></a>";

                    // if user is current user, show message
                    var to = $('#to_uid').val();
                    if (to == from) {
                        addChatLineToChatlog(from, 'now', message);

                    }
                    else {
                        // set message count
                        increseMsgCount(from);
                    }

                    //////////////
                    $.ionSound.play("receive");
                    //////////////
                }
                else if (action == "RESPONSE") {
                    var code = json.code;
                    // If server error
                    if (code == "403") {
                        addLineToChatlog('Failed from server.', 'info');
                    }
                    // No connected user
                    else if (code == "503") {
                        var to = json.to;
                        //addLineToChatlog('User ' + to + ' no connected. Message sent by push.', 'info');
                    }
                }
            }

            /**
             * The WebSocket `onclose` event.
             *
             * @event
             */
            ws.socket.onclose = function() {
                refreshUserInterface(ws);
                addLineToChatlog('The connection has been closed.', 'info');
            }
        } catch (ex) {
            addLineToChatlog('Exception: ' + ex, 'error');
        }
//        });

        /**
         * Close the WebSocket if the "Disconnect" button is clicked.
         *
         * @event
         */
        $('#disconnect').click(function() {
            if (ws.isClosed()) {
                addLineToChatlog('The connection is not opened.', 'warning');
                return;
            }

            try {
                ws.close();
            } catch (ex) {
                addLineToChatlog('Exception: ' + ex, 'error');
            }

            refreshUserInterface(ws);
        });

        /**
         * Sets the state of the "Send" button in dependency of the value of the
         * "Message" input text field.
         *
         * @event
         * @todo This is extremely slow, hence commented. Find out why.
         */
        //$('#msg').change(function() {
        //    $('#send').attr('disabled', !$(this).val());
        //});

        /**
         * Send data via the WebSocket if the "Send" button is clicked.
         *
         * @event
         */
        $('#send').click(function() {
            handleSend();

            var msgInputField = $('#msg');
            var objDiv = document.getElementById("message-thread12");

            // Set focus
            msgInputField.focus();

            // auto scroll            
            objDiv.scrollTop = objDiv.scrollHeight;
        });

        /**
         * Send data via the WebSocket if the "Return" keyboard key is pressed.
         *
         * @event
         */
        $('#msg').keypress(function(event) {
            if (event.keyCode == '13') {
                handleSend();

                var msgInputField = $('#msg');
                var objDiv = document.getElementById("message-thread12");

                // Set focus
                msgInputField.focus();

                // auto scroll            
                objDiv.scrollTop = objDiv.scrollHeight;
            }
        });

        /**
         * Sends data via the specified WebSocket and sets the correct state of
         * the user interface.
         *
         * @param {WebSocket} socket The WebSocket to send data with.
         *
         * @returns {void}
         * @function
         */
        function handleSend() {
            if (ws.isClosed()) {
                addLineToChatlog('Establish a connection first.', 'warning');
                return;
            }

            var msgInputField = $('#msg');
            var msg = msgInputField.val();
            if ('' == msg) {
                //addLineToChatlog('Please enter a message.', 'warning');
                return;
            }

            if (!enableChat) {
                alert('Sorry, Chat service is currently unavailable. \r\nDo contact us again during our Operating Hours.');
                return;
            }
            
            if (!isWrite) {
                alert('Sorry, You do not have permission to chat.');
                return;
            }

            try {
                var from = $('#from_uid').val();
                var to = $('#to_uid').val();
                var status = $('#is_online').val() + "";

                // Display owner message
                addChatLineToChatlog(from, 'now', replaceURL(msg));

                // if user is offline, sent by push
                if (status == "0") {
                    sendPush(to, msg);
                }
                else {
                    badge = 0;
                }

                // Send the message to client.					
                var sendMsg = {
                    action: 'MSG',
                    from: from,
                    to: to,
                    message: msg
                };

                var message = JSON.stringify(sendMsg);
                ws.send(message);

                //////////////
                $.ionSound.play("receive");
                //////////////

                // Init text box
                msgInputField.val('');
            } catch (ex) {
                addLineToChatlog('Exception: ' + ex, 'error');
            }
        }

        function sendFile(path) {
            try {
                var from = $('#from_uid').val();
                var to = $('#to_uid').val();

                var message = "<a href='" + path + "' target='_blank'><img src='" + path + "' width='50px' height='50px'></a>";
                addChatLineToChatlog(from, 'now', message);

                // Send the file to client.					
                var sendMsg = {
                    action: 'SHAREFILE',
                    from: from,
                    to: to,
                    path: path
                };

                var message = JSON.stringify(sendMsg);
                ws.send(message);
            } catch (ex) {
                addLineToChatlog('Exception: ' + ex, 'error');
            }
        }

        var settings = {
            url: "../upload_chat_file.php",
            dragDrop: false,
            fileName: "myfile",
            formData: {where: "web", userid: adminId},
            multiple: false,
            autoSubmit: true,
            showCancel: false,
            showAbort: false,
            showDone: false,
            showDelete: false,
            showError: true,
            showStatusAfterSuccess: false,
            showStatusAfterError: false,
            showFileCounter: false,
            allowedTypes: "jpg,jpeg,bmp,png",
            returnType: "json",
            onSuccess: function(files, data, xhr)
            {
                sendFile(data[0]);
                // auto scroll
                var objDiv = document.getElementById("message-thread12");
                objDiv.scrollTop = objDiv.scrollHeight;
            },
            deleteCallback: function(data, pd)
            {
                for (var i = 0; i < data.length; i++)
                {
                    $.post("file_delete.php", {op: "delete", name: data[i]},
                    function(resp, textStatus, jqXHR)
                    {
                        //Show Message  
                        $("#status").html("<div>File Deleted</div>");
                    });
                }
                pd.statusbar.hide(); //You choice to hide/not.

            }
        }
        var uploadObj = $("#mulitplefileuploader").uploadFile(settings);

    }

    //Pad given value to the left with "0"
    function AddZero(num) {
        return (num >= 0 && num < 10) ? "0" + num : num + "";
    }

    function displayUserInfo(username) {
        var sendData = {
            username: username
        };

        $.ajax({
            type: "POST",
            cache: false,
            url: 'get_userinfo.php',
            data: sendData,
            async: true,
            dataType: 'text',
            success: function(response) {
                var json = $.parseJSON(response);
                var user_name = json.user_name;
                var display_name = json.display_name;
                var company = json.company;
                var photo = json.photo;
                var status = json.status;

                $('#to_uid').val(user_name);
                $('#sel_user_photo').attr('src', photo);
                $('#display_name').text(display_name);
                $('#company').text(company);
                $('#is_online').val(status);
                $('#badge').val(0);

                $('#chatlog').empty();

                // sho msg logs
                var from = $('#from_uid').val();
                var to = $('#to_uid').val();
                getChatLog(from, to);
            },
            error: function(response) {
                addLineToChatlog('The message do not sent by push.', 'info');
            }
        });
    }

    function increseMsgCount(username) {
        var joinedUser = "#unread_" + username;
        var unreadCountId = "#unread_" + username + "_count";
        var unreadCount = parseInt($(unreadCountId).text(), 10);
        if ($.isNumeric(unreadCount)) {
            unreadCount++;
        }
        else {
            unreadCount = 1;
        }

        $(joinedUser).html('&nbsp;<small id="unread_' + username + '_count" class="unread">' + unreadCount + '</small>');

        sercheSort('#selectable', 'small');
    }

    function removeMsgCount(username) {
        // get badge
        var badgeId = "#badge_" + username;
        var curr_b = $(badgeId).val();
        var new_b = parseInt(curr_b, 10);
        if ($.isNumeric(new_b)) {
            badge = new_b;
        }
        else {
            badge = 0;
        }

        var joinedUser = "#unread_" + username;
        $(joinedUser).empty();

        sercheSort('#selectable', 'small');
    }

    function sercheSort(id, SortItem) {
        var SortId = $(id);
        var SortListItem = SortId.children('li').get();
        var SortTypeValue = 'desc';

        SortListItem.sort(function(a, b) {
            var AItem = parseInt($(a).find(SortItem).text().toUpperCase(), 10);
            var BItem = parseInt($(b).find(SortItem).text().toUpperCase(), 10);
            if (SortTypeValue == 'desc') {
                return (BItem < AItem) ? -1 : (BItem > AItem) ? 1 : 0;
            } else {
                return (AItem < BItem) ? -1 : (AItem > BItem) ? 1 : 0;
            }
        });

        $(SortId).append(SortListItem);
    }

    /*
     * Check if chat is available.
     * 
     * @returns {undefined}
     */
    function checkOfficeTime() {
        var today = new Date();
        var year = today.getFullYear();
        var month = today.getMonth();
        var day = today.getDate();
        var hour = today.getHours();
        var min = today.getMinutes();
        var sec = today.getSeconds();

        var currTime = new Date(year, month, day, hour, min, sec);
        //var startTime = new Date(year, month, day, 9, 0, 0);
		var startTime = new Date(year, month, day, 0, 0, 1);
        var endTime = new Date(year, month, day, 21, 59, 60);

        //if (startTime <= currTime && endTime >= currTime) {
            return true;
        //}

        //return false;
    }

});
