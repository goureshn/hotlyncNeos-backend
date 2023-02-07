<?php
$sound_name = 'speech_test';
$sound_text ='This is default message if alarm.';
$alarm_name = 'No alarm';
$desc       = 'this is alarm description';
$location   = 'no location';
$comment    = 'no message';

if(isset($_POST['sound_name'])){
    $sound_name= $_POST['sound_name'];
};
if(isset($_GET['sound_name'])){
    $sound_name= $_GET['sound_name'];
};

if(isset($_POST['alarm_name'])){
    $alarm_name= $_POST['alarm_name'];
};
if(isset($_GET['alarm_name'])){
    $alarm_name= $_GET['alarm_name'];
};

if(isset($_POST['location'])){
    $location = $_POST['location'];
};
if(isset($_GET['location'])){
    $location = $_GET['location'];
};

if(isset($_POST['comment'])){
    $comment = $_POST['comment'];
};
if(isset($_GET['comment'])){
    $comment = $_GET['comment'];
};

if(isset($_POST['desc'])){
    $desc = $_POST['desc'];
};
if(isset($_GET['desc'])){
    $desc = $_GET['desc'];
};

$sound_text=$alarm_name.' activated at '.$location.' '.$comment;

$ssml ='<speak>'.$alarm_name.' <break time="0.5s"/>';
$ssml .= $desc.' <break time="0.5s"/>';
$ssml .='Location  <break time="0.5s"/>';
$ssml .=$location.'  <break time="0.5s"/>';
$ssml .=$comment.'  <break time="0.5s"/>';
$ssml .='</speak>';

$path = __DIR__ ; 
$path_index = strrpos($path, 'public');
$path = substr($path,0,$path_index);

require $path . '/vendor/autoload.php';

// Imports the Cloud Client Library
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;


//putenv('GOOGLE_APPLICATION_CREDENTIALS=D:\work\new_project\Gouresh_Shen\hotel2\CMS\public\text_speech_api.json');
putenv('GOOGLE_APPLICATION_CREDENTIALS='.$path.'/public/text_speech_api.json');

// instantiates a client
$client = new TextToSpeechClient();

// sets text to be synthesised
//$synthesisInputText = (new SynthesisInput())
//    ->setText($sound_text);
$synthesisInputText = (new SynthesisInput())
    ->setSsml($ssml);

// build the voice request, select the language code ("en-US") and the ssml
// voice gender
$voice = (new VoiceSelectionParams())
    ->setLanguageCode('en-US')
    ->setSsmlGender(SsmlVoiceGender::FEMALE);

// Effects profile
$effectsProfileId = "telephony-class-application";

// select the type of audio file you want returned
$audioConfig = (new AudioConfig())
    //->setAudioEncoding(AudioEncoding::MP3)
    ->setAudioEncoding(AudioEncoding::LINEAR16)
    ->setSampleRateHertz(8000)
    ->setEffectsProfileId(array($effectsProfileId));

// perform text-to-speech request on the text input with selected voice
// parameters and audio file type
$response = $client->synthesizeSpeech($synthesisInputText, $voice, $audioConfig);
$audioContent = $response->getAudioContent();
$file = '/usr/share/asterisk/sounds/en_US_f_Allison/'.$sound_name.'.wav';
// the response's audioContent is binary
//file_put_contents('sound/'.$sound_name.'.mp3', $audioContent);
file_put_contents($file, $audioContent);
chmod($file,0777);
sleep(1);
//echo 'Audio content written to "output.mp3"' . PHP_EOL;
?>
