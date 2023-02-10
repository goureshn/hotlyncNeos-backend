app.controller('ctrlChatRecorder', function ($scope, $http, AuthService, $uibModalInstance, toaster, current_session) {

    let AudioContext = window.AudioContext || window.webkitAudioContext;
    let audioContext;

    let gumStream; 						//stream from getUserMedia()
    let rec; 							//Recorder.js object
    let input; 							//MediaStreamAudioSourceNode we'll be recording

    $scope.button_label = 'Start';
    $scope.time = "00:00";
    $scope.status = 'Ready'; // 'Paused' //

    $scope.url = '';

    $scope.onCancel = function () {
        $uibModalInstance.dismiss();
    };

    let timer_count = 0;

    let timer = null;

    function getRealTime(count) {
        let seconds = count % 60;

        if (seconds < 10) {
            seconds = '0' + seconds;
        }

        let minutes = Math.floor(count / 60);

        if (minutes < 10) {
            minutes = '0' + minutes;
        }

        return [minutes, seconds].join(':');
    }

    function onRecording() {
        let constraints = { audio: true, video: false };

        if (!navigator.mediaDevices) {
            toaster.pop('warning', 'Notification', 'There is no media devices...');
            return;
        }

        navigator.mediaDevices.getUserMedia(constraints)
            .then(function (stream) {
                $scope.status = 'Recording';
                $scope.button_label = 'Pause';

                audioContext = new AudioContext();

                /*  assign to gumStream for later use  */
                gumStream = stream;

                /* use the stream */
                input = audioContext.createMediaStreamSource(stream);

                rec = new Recorder(input, {numChannels: 1});

                rec.record();

                $scope.url = '';

                timer_count = 0;
                $scope.time = '00:00';
                timer = setInterval(() => {
                    if ($scope.status === 'Recording') {
                        timer_count++;
                        $scope.time = getRealTime(timer_count);
                    }
                }, 1000);
        })
            .catch(function (error) {
                toaster.pop('error', 'Notification', error.message);
                $scope.status = 'Ready';
                timer_count = 0;
                $scope.time = '00:00';
                if (timer) {
                    clearInterval(timer);
                }
            })
    }

    $scope.onStartOrResume = function () {
        if ($scope.status === 'Ready' || $scope.status === 'Ended') {
            onRecording();
        } else if ($scope.status === 'Recording') {
            rec.stop();
            $scope.status = 'Paused';
            $scope.button_label = 'Resume';
        } else if ($scope.status === 'Paused') {

            rec.record();
            $scope.url = '';
            $scope.status = 'Recording';
            $scope.button_label = 'Pause';
        }
    };

    function createDownloadLink(blob) {

        $scope.url = URL.createObjectURL(blob);

        // var li = document.createElement('li');
        // var link = document.createElement('a');
        //
        // //name of .wav file to use during upload and download (without extendion)
        // var filename = new Date().toISOString();
        //
        // //add controls to the <audio> element
        // au.controls = true;
        // au.src = url;
        //
        // //save to disk link
        // link.href = url;
        // link.download = filename+".wav"; //download forces the browser to donwload the file using the  filename
        // link.innerHTML = "Save to disk";
        //
        // //add the new audio element to li
        // li.appendChild(au);
        //
        // //add the filename to the li
        // li.appendChild(document.createTextNode(filename+".wav "))
        //
        // //add the save to disk link to li
        // li.appendChild(link);
        //
        // //upload link
        // var upload = document.createElement('a');
        // upload.href="#";
        // upload.innerHTML = "Upload";
        // upload.addEventListener("click", function(event){
        //     var xhr=new XMLHttpRequest();
        //     xhr.onload=function(e) {
        //         if(this.readyState === 4) {
        //             console.log("Server returned: ",e.target.responseText);
        //         }
        //     };
        //     var fd=new FormData();
        //     fd.append("audio_data",blob, filename);
        //     xhr.open("POST","upload.php",true);
        //     xhr.send(fd);
        // })
        // li.appendChild(document.createTextNode (" "))//add a space in between
        // li.appendChild(upload)//add the upload link to li
        //
        // //add the li element to the ol
        // recordingsList.appendChild(li);
    }

    $scope.onStop = function () {

        rec.stop();
        gumStream.getAudioTracks()[0].stop();
        rec.exportWAV(createDownloadLink);
        $scope.status = 'Ended';
        $scope.button_label = 'Start';

        if (timer) {
            clearInterval(timer);
        }
    }
});


