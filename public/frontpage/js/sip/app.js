/* globals SIP,user,moment, Stopwatch */

var etssip;


$(document).ready(function() {




     // $('.digit').click(function(event) {
     //     alert($('#ws_url').val()+":::"+$('#sip_ip').val()+"::"+$('#display_name').val()+":"+$('#sip_user').val()+":"+$('#sip_pass').val());
     //     if (typeof(user) === 'undefined') {
     //         user = JSON.parse(localStorage.getItem('SIPCreds'));
     //     }
     //     user.Pass = $('#sip_user').val();
     //     user.Display = $('#display_name').val();
     //     user.User = $('#sip_user').val();
     //     user.Realm = $('#sip_ip').val();
     //     user.WSServer = $('#ws_url').val();
     // });


    ///////////////////////////////////

        if (typeof(user) === 'undefined') {
            user = JSON.parse(localStorage.getItem('SIPCreds'));
        }

    etssip = {

        config : {
            password        : user.Pass,
            displayName     : user.Display,
            uri             : 'sip:'+user.User+'@'+user.Realm,
            wsServers       : user.WSServer,
            // stunServers : [],
            // usePreloadedRoute : true,
            registerExpires : 30,
			hackIpInContact: true,
			hackWssInTransport: true,
 rtcpMuxPolicy: 'negotiate',
            traceSip        : true,
            log             : {
                level : 3,
            }
        },
        ringtone     : document.getElementById('ringtone'),
        ringbacktone : document.getElementById('ringbacktone'),
        dtmfTone     : document.getElementById('dtmfTone'),

        Sessions     : [],
        callTimers   : {},
        callActiveID : null,
        callVolume   : 1,
        Stream       : null,

        /**
         * Parses a SIP uri and returns a formatted US phone number.
         *
         * @param  {string} phone number or uri to format
         * @return {string}       formatted number
         */
        formatPhone : function(phone) {

            var num;

            if (phone.indexOf('@')) {
                num =  phone.split('@')[0];
            } else {
                num = phone;
            }

            num = num.toString().replace(/[^0-9]/g, '');

            if (num.length === 10) {
                return '(' + num.substr(0, 3) + ') ' + num.substr(3, 3) + '-' + num.substr(6,4);
            } else if (num.length === 11) {
                return '(' + num.substr(1, 3) + ') ' + num.substr(4, 3) + '-' + num.substr(7,4);
            } else {
                return num;
            }
        },

        // Sound methods
        startRingTone : function() {
            try { etssip.ringtone.play(); } catch (e) { }
        },

        stopRingTone : function() {
            try { etssip.ringtone.pause(); } catch (e) { }
        },

        startRingbackTone : function() {
            try { etssip.ringbacktone.play(); } catch (e) { }
        },

        stopRingbackTone : function() {
            try { etssip.ringbacktone.pause(); } catch (e) { }
        },

        // Genereates a rendom string to ID a call
        getUniqueID : function() {
            return Math.random().toString(36).substr(2, 9);
        },

        newSession : function(newSess) {

            newSess.displayName = newSess.remoteIdentity.displayName || newSess.remoteIdentity.uri.user;
            newSess.etsid       = etssip.getUniqueID();

            var status;

            if (newSess.direction === 'incoming') {
                status = "Incoming: "+ newSess.displayName;
                etssip.startRingTone();
            } else {
                status = "Trying: "+ newSess.displayName;
                etssip.startRingbackTone();
            }

            etssip.logCall(newSess, 'ringing');

            etssip.setCallSessionStatus(status);

            // EVENT CALLBACKS

            newSess.on('progress',function(e) {
                if (e.direction === 'outgoing') {
                    etssip.setCallSessionStatus('Calling...');
                }
            });

            newSess.on('connecting',function(e) {
                if (e.direction === 'outgoing') {
                    etssip.setCallSessionStatus('Connecting...');
                }
            });

            newSess.on('accepted',function(e) {
                // If there is another active call, hold it
                if (etssip.callActiveID && etssip.callActiveID !== newSess.etsid) {
                    etssip.phoneHoldButtonPressed(etssip.callActiveID);
                }

                etssip.stopRingbackTone();
                etssip.stopRingTone();
                etssip.setCallSessionStatus('Answered');
                etssip.logCall(newSess, 'answered');
                etssip.callActiveID = newSess.etsid;
            });

            newSess.on('hold', function(e) {
                etssip.callActiveID = null;
                etssip.logCall(newSess, 'holding');
            });

            newSess.on('unhold', function(e) {
                etssip.logCall(newSess, 'resumed');
                etssip.callActiveID = newSess.etsid;
            });

            newSess.on('muted', function(e) {
                etssip.Sessions[newSess.etsid].isMuted = true;
                etssip.setCallSessionStatus("Muted");
            });

            newSess.on('unmuted', function(e) {
                etssip.Sessions[newSess.etsid].isMuted = false;
                etssip.setCallSessionStatus("Answered");
            });

            newSess.on('cancel', function(e) {
                etssip.stopRingTone();
                etssip.stopRingbackTone();
                etssip.setCallSessionStatus("Canceled");
                if (this.direction === 'outgoing') {
                    etssip.callActiveID = null;
                    newSess             = null;
                    etssip.logCall(this, 'ended');
                }
            });

            newSess.on('bye', function(e) {
                etssip.stopRingTone();
                etssip.stopRingbackTone();
                etssip.setCallSessionStatus("");
                etssip.logCall(newSess, 'ended');
                etssip.callActiveID = null;
                newSess             = null;
            });

            newSess.on('failed',function(e) {
                etssip.stopRingTone();
                etssip.stopRingbackTone();
                etssip.setCallSessionStatus('Terminated');
            });

            newSess.on('rejected',function(e) {
                etssip.stopRingTone();
                etssip.stopRingbackTone();
                etssip.setCallSessionStatus('Rejected');
                etssip.callActiveID = null;
                etssip.logCall(this, 'ended');
                newSess             = null;
            });

            etssip.Sessions[newSess.etsid] = newSess;

        },

        // getUser media request refused or device was not present
        getUserMediaFailure : function(e) {
            window.console.error('getUserMedia failed:', e);
            etssip.setError(true, 'Media Error.', 'You must allow access to your microphone.  Check the address bar.', true);
        },

        getUserMediaSuccess : function(stream) {
             etssip.Stream = stream;
        },

        /**
         * sets the ui call status field
         *
         * @param {string} status
         */
        setCallSessionStatus : function(status) {
            $('#txtCallStatus').html(status);
        },

        /**
         * sets the ui connection status field
         *
         * @param {string} status
         */
        setStatus : function(status) {
            $("#txtRegStatus").html('<i class="fa fa-signal"></i> '+status);
        },

        /**
         * logs a call to localstorage
         *
         * @param  {object} session
         * @param  {string} status Enum 'ringing', 'answered', 'ended', 'holding', 'resumed'
         */
        logCall : function(session, status) {

            var log = {
                    clid : session.displayName,
                    uri  : session.remoteIdentity.uri.toString(),
                    id   : session.etsid,
                    time : new Date().getTime()
                },
                calllog = JSON.parse(localStorage.getItem('sipCalls'));

            if (!calllog) { calllog = {}; }

            if (!calllog.hasOwnProperty(session.etsid)) {
                calllog[log.id] = {
                    id    : log.id,
                    clid  : log.clid,
                    uri   : log.uri,
                    start : log.time,
                    flow  : session.direction
                };
            }

            if (status === 'ended') {
                calllog[log.id].stop = log.time;
            }

            if (status === 'ended' && calllog[log.id].status === 'ringing') {
                calllog[log.id].status = 'missed';
            } else {
                calllog[log.id].status = status;
            }

            localStorage.setItem('sipCalls', JSON.stringify(calllog));
            etssip.logShow();
        },

        /**
         * adds a ui item to the call log
         *
         * @param  {object} item log item
         */
        logItem : function(item) {

            var callActive = (item.status !== 'ended' && item.status !== 'missed'),
                callLength = (item.status !== 'ended')? '<span id="'+item.id+'"></span>': moment.duration(item.stop - item.start).humanize(),
                callClass  = '',
                callIcon,
                i;

            switch (item.status) {
                case 'ringing'  :
                    callClass = 'list-group-item-success';
                    callIcon  = 'fa-bell';
                    break;

                case 'missed'   :
                    callClass = 'list-group-item-danger';
                    if (item.flow === "incoming") { callIcon = 'fa-chevron-left'; }
                    if (item.flow === "outgoing") { callIcon = 'fa-chevron-right'; }
                    break;

                case 'holding'  :
                    callClass = 'list-group-item-warning';
                    callIcon  = 'fa-pause';
                    break;

                case 'answered' :
                case 'resumed'  :
                    callClass = 'list-group-item-info';
                    callIcon  = 'fa-phone-square';
                    break;

                case 'ended'  :
                    if (item.flow === "incoming") { callIcon = 'fa-chevron-left'; }
                    if (item.flow === "outgoing") { callIcon = 'fa-chevron-right'; }
                    break;
            }


            i  = '<div class="list-group-item sip-logitem clearfix '+callClass+'" data-uri="'+item.uri+'" data-sessionid="'+item.id+'" title="Double Click to Call">';
            i += '<div class="clearfix"><div class="pull-left">';
            i += '<i class="fa fa-fw '+callIcon+' fa-fw"></i> <strong>'+etssip.formatPhone(item.uri)+'</strong><br><small>'+moment(item.start).format('MM/DD hh:mm:ss a')+'</small>';
            i += '</div>';
            i += '<div class="pull-right text-right"><em>'+item.clid+'</em><br>' + callLength+'</div></div>';

            if (callActive) {
                i += '<div class="btn-group btn-group-xs pull-right">';
                if (item.status === 'ringing' && item.flow === 'incoming') {
                    i += '<button class="btn btn-xs btn-success btnCall" title="Call"><i class="fa fa-phone"></i></button>';
                } else {
                    i += '<button class="btn btn-xs btn-primary btnHoldResume" title="Hold"><i class="fa fa-pause"></i></button>';
                    i += '<button class="btn btn-xs btn-info btnTransfer" title="Transfer"><i class="fa fa-random"></i></button>';
                    i += '<button class="btn btn-xs btn-warning btnMute" title="Mute"><i class="fa fa-fw fa-microphone"></i></button>';
                }
                i += '<button class="btn btn-xs btn-danger btnHangUp" title="Hangup"><i class="fa fa-stop"></i></button>';
                i += '</div>';
            }
            i += '</div>';

            $('#sip-logitems').append(i);


            // Start call timer on answer
            if (item.status === 'answered') {
                var tEle = document.getElementById(item.id);
                etssip.callTimers[item.id] = new Stopwatch(tEle);
                etssip.callTimers[item.id].start();
            }

            if (callActive && item.status !== 'ringing') {
                etssip.callTimers[item.id].start({startTime : item.start});
            }

            $('#sip-logitems').scrollTop(0);
        },

        /**
         * updates the call log ui
         */
        logShow : function() {

            var calllog = JSON.parse(localStorage.getItem('sipCalls')),
                x       = [];

            if (calllog !== null) {

                $('#sip-splash').addClass('hide');
                $('#sip-log').removeClass('hide');

                // empty existing logs
                $('#sip-logitems').empty();

                // JS doesn't guarantee property order so
                // create an array with the start time as
                // the key and sort by that.

                // Add start time to array
                $.each(calllog, function(k,v) {
                    x.push(v);
                });

                // sort descending
                x.sort(function(a, b) {
                    return b.start - a.start;
                });

                $.each(x, function(k, v) {
                    etssip.logItem(v);
                });

            } else {
                $('#sip-splash').removeClass('hide');
                $('#sip-log').addClass('hide');
            }
        },

        /**
         * removes log items from localstorage and updates the UI
         */
        logClear : function() {

            localStorage.removeItem('sipCalls');
            etssip.logShow();
        },

        sipCall : function(target) {

            try {
                var s = etssip.phone.invite(target, {
                    media : {
                        stream      : etssip.Stream,
                        constraints : { audio : true, video : false },
                        render      : {
                            remote : $('#audioRemote').get()[0]
                        },
                        // RTCConstraints : { "optional": [{ 'DtlsSrtpKeyAgreement': 'true'} ]}
                    }
                });
                s.direction = 'outgoing';
                etssip.newSession(s);

            } catch(e) {
                throw(e);
            }
        },

        sipTransfer : function(sessionid) {

            var s      = etssip.Sessions[sessionid],
                target = window.prompt('Enter destination number', '');

            etssip.setCallSessionStatus('<i>Transfering the call...</i>');
            s.refer(target);
        },

        sipHangUp : function(sessionid) {

            var s = etssip.Sessions[sessionid];
            // s.terminate();
            if (!s) {
                return;
            } else if (s.startTime) {
                s.bye();
            } else if (s.reject) {
                s.reject();
            } else if (s.cancel) {
                s.cancel();
            }

        },

        sipSendDTMF : function(digit) {

            try { etssip.dtmfTone.play(); } catch(e) { }

            var a = etssip.callActiveID;
            if (a) {
                var s = etssip.Sessions[a];
                s.dtmf(digit);
            }
        },

        phoneCallButtonPressed : function(sessionid) {

            var s      = etssip.Sessions[sessionid],
                target = $("#numDisplay").val();

            if (!s) {

                $("#numDisplay").val("");
                etssip.sipCall(target);

            } else if (s.accept && !s.startTime) {

                s.accept({
                    media : {
                        stream      : etssip.Stream,
                        constraints : { audio : true, video : false },
                        render      : {
                            remote : document.getElementById('audioRemote')
                        },
                        RTCConstraints : { "optional": [{ 'DtlsSrtpKeyAgreement': 'true'} ]}
                    }
                });
            }
        },

        phoneMuteButtonPressed : function (sessionid) {

            var s = etssip.Sessions[sessionid];

            if (!s.isMuted) {
                s.mute();
            } else {
                s.unmute();
            }
        },

        phoneHoldButtonPressed : function(sessionid) {

            var s = etssip.Sessions[sessionid];

            if (s.isOnHold().local === true) {
                s.unhold();
            } else {
                s.hold();
            }
        },


        setError : function(err, title, msg, closable) {

            // Show modal if err = true
            if (err === true) {
                $("#mdlError p").html(msg);
                //$("#mdlError").modal('show');
                $("#mdlError").modal('hide');

                if (closable) {
                    var b = '<button type="button" class="close" data-dismiss="modal">&times;</button>';
                    $("#mdlError .modal-header").prepend(b);
                    $("#mdlError .modal-title").html(title);
                    $("#mdlError").modal({ keyboard : true });
                } else {
                    $("#mdlError .modal-header").find('button').remove();
                    $("#mdlError .modal-title").html(title);
                    $("#mdlError").modal({ keyboard : false });
                }
                $('#numDisplay').prop('disabled', 'disabled');
            } else {
                $('#numDisplay').removeProp('disabled');
                $("#mdlError").modal('hide');
            }
        },

        /**
         * Tests for a capable browser, return bool, and shows an
         * error modal on fail.
         */
        hasWebRTC : function() {

            if (navigator.webkitGetUserMedia) {
                return true;
            } else if (navigator.mozGetUserMedia) {
                return true;
            } else if (navigator.getUserMedia) {
                return true;
            } else {
                etssip.setError(true, 'Unsupported Browser.', 'Your browser does not support the features required for this phone.');
                window.console.error("WebRTC support not found");
                return false;
            }
        }
    };


    // Throw an error if the browser can't hack it.
    if (!etssip.hasWebRTC()) {
        return true;
    }

    etssip.phone = new SIP.UA(etssip.config);

    etssip.phone.on('connected', function(e) {
        etssip.setStatus("Connected");
    });

    etssip.phone.on('disconnected', function(e) {
        etssip.setStatus("Disconnected");

        // disable phone
        etssip.setError(true, 'Websocket Disconnected.', 'An Error occurred connecting to the websocket.');

        // remove existing sessions
        $("#sessions > .session").each(function(i, session) {
            etssip.removeSession(session, 500);
        });
    });

    etssip.phone.on('registered', function(e) {

        var closeEditorWarning = function() {
            return 'If you close this window, you will not be able to make or receive calls from your browser.';
        };

        var closePhone = function() {
            // stop the phone on unload
            localStorage.removeItem('etsPhone');
            etssip.phone.stop();
        };

        window.onbeforeunload = closeEditorWarning;
        window.onunload       = closePhone;

        // This key is set to prevent multiple windows.
        localStorage.setItem('etsPhone', 'true');

        $("#mldError").modal('hide');
        etssip.setStatus("Ready");

        // Get the userMedia and cache the stream
        if (SIP.WebRTC.isSupported()) {
            SIP.WebRTC.getUserMedia({ audio : true, video : false }, etssip.getUserMediaSuccess, etssip.getUserMediaFailure);
        }
    });

    etssip.phone.on('registrationFailed', function(e) {
        etssip.setError(true, 'Registration Error.', 'An Error occurred registering your phone. Check your settings.');
        etssip.setStatus("Error: Registration Failed");
    });

    etssip.phone.on('unregistered', function(e) {
        etssip.setError(true, 'Registration Error.', 'An Error occurred registering your phone. Check your settings.');
        etssip.setStatus("Error: Registration Failed");
    });

    etssip.phone.on('invite', function (incomingSession) {

        var s = incomingSession;

        s.direction = 'incoming';
        etssip.newSession(s);
    });

    // Auto-focus number input on backspace.
    $('#sipClient').keydown(function(event) {
        if (event.which === 8) {
            $('#numDisplay').focus();
        }
    });

    $('#numDisplay').keypress(function(e) {
        // Enter pressed? so Dial.
        if (e.which === 13) {
            etssip.phoneCallButtonPressed();
        }
    });

    $('.digit').click(function(event) {
        event.preventDefault();
        var num = $('#numDisplay').val(),
            dig = $(this).data('digit');

        $('#numDisplay').val(num+dig);

        etssip.sipSendDTMF(dig);
        return false;
    });

    $('#phoneUI .dropdown-menu').click(function(e) {
        e.preventDefault();
    });

    $('#phoneUI').delegate('.btnCall', 'click', function(event) {
        etssip.phoneCallButtonPressed();
        // to close the dropdown
        return true;
    });

    $('.sipLogClear').click(function(event) {
        event.preventDefault();
        etssip.logClear();
    });

    $('#sip-logitems').delegate('.sip-logitem .btnCall', 'click', function(event) {
        var sessionid = $(this).closest('.sip-logitem').data('sessionid');
        etssip.phoneCallButtonPressed(sessionid);
        return false;
    });

    $('#sip-logitems').delegate('.sip-logitem .btnHoldResume', 'click', function(event) {
        var sessionid = $(this).closest('.sip-logitem').data('sessionid');
        etssip.phoneHoldButtonPressed(sessionid);
        return false;
    });

    $('#sip-logitems').delegate('.sip-logitem .btnHangUp', 'click', function(event) {
        var sessionid = $(this).closest('.sip-logitem').data('sessionid');
        etssip.sipHangUp(sessionid);
        return false;
    });

    $('#sip-logitems').delegate('.sip-logitem .btnTransfer', 'click', function(event) {
        var sessionid = $(this).closest('.sip-logitem').data('sessionid');
        etssip.sipTransfer(sessionid);
        return false;
    });

    $('#sip-logitems').delegate('.sip-logitem .btnMute', 'click', function(event) {
        var sessionid = $(this).closest('.sip-logitem').data('sessionid');
        etssip.phoneMuteButtonPressed(sessionid);
        return false;
    });

    $('#sip-logitems').delegate('.sip-logitem', 'dblclick', function(event) {
        event.preventDefault();

        var uri = $(this).data('uri');
        $('#numDisplay').val(uri);
        etssip.phoneCallButtonPressed();
    });

    $('#sldVolume').on('change', function() {

        var v      = $(this).val() / 100,
            // player = $('audio').get()[0],
            btn    = $('#btnVol'),
            icon   = $('#btnVol').find('i'),
            active = etssip.callActiveID;

        // Set the object and media stream volumes
        if (etssip.Sessions[active]) {
            etssip.Sessions[active].player.volume = v;
            etssip.callVolume                     = v;
        }

        // Set the others
        $('audio').each(function() {
            $(this).get()[0].volume = v;
        });

        if (v < 0.1) {
            btn.removeClass(function (index, css) {
                   return (css.match (/(^|\s)btn\S+/g) || []).join(' ');
                })
                .addClass('btn btn-sm btn-danger');
            icon.removeClass().addClass('fa fa-fw fa-volume-off');
        } else if (v < 0.8) {
            btn.removeClass(function (index, css) {
                   return (css.match (/(^|\s)btn\S+/g) || []).join(' ');
               }).addClass('btn btn-sm btn-info');
            icon.removeClass().addClass('fa fa-fw fa-volume-down');
        } else {
            btn.removeClass(function (index, css) {
                   return (css.match (/(^|\s)btn\S+/g) || []).join(' ');
               }).addClass('btn btn-sm btn-primary');
            icon.removeClass().addClass('fa fa-fw fa-volume-up');
        }
        return false;
    });

    // Hide the spalsh after 3 secs.
    setTimeout(function() {
        etssip.logShow();
    }, 3000);


    /**
     * Stopwatch object used for call timers
     *
     * @param {dom element} elem
     * @param {[object]} options
     */
    var Stopwatch = function(elem, options) {

        // private functions
        function createTimer() {
            return document.createElement("span");
        }

        var timer = createTimer(),
            offset,
            clock,
            interval;

        // default options
        options           = options || {};
        options.delay     = options.delay || 1000;
        options.startTime = options.startTime || Date.now();

        // append elements
        elem.appendChild(timer);

        function start() {
            if (!interval) {
                offset   = options.startTime;
                interval = setInterval(update, options.delay);
            }
        }

        function stop() {
            if (interval) {
                clearInterval(interval);
                interval = null;
            }
        }

        function reset() {
            clock = 0;
            render();
        }

        function update() {
            clock += delta();
            render();
        }

        function render() {
            timer.innerHTML = moment(clock).format('mm:ss');
        }

        function delta() {
            var now = Date.now(),
                d   = now - offset;

            offset = now;
            return d;
        }

        // initialize
        reset();

        // public API
        this.start = start; //function() { start; }
        this.stop  = stop; //function() { stop; }
    };

});
