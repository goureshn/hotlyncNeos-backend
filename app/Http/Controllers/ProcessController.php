<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

use App\Models\User;
use App\Models\TempStage;
use App\Models\Comment;
use App\Models\Stage;
use App\Models\History;
use App\Models\Advertisement;
use App\Models\Contacts;
use App\Models\Favorite;
use App\Models\ReceivePoint;
use App\Models\SendPoint;
use App\Models\CommentStar;
header('Content-type: application/json; charset=utf-8');
//use Request;

define("SUCCESS", "200"); 			// successfully

define("MISSING_PARAMETER", "100"); // Parameter missing
define("INVALID_PARAMETER", "101"); // Parameter is invacheckUserValiditylid

define("USER_EXIST", "201");		// user already exist
define("NO_VERIFIED", "202"); 		// not verified user
define("STATUS_INACTIVE", "203"); 	// status inactive

define("NO_USER_EXIST", "301"); 	// user not exist
define("INVALID_PASSWORD", "302");	// user or password is not valid
define("INVALID_VCODE", "303");		// verify code is invalid
define("NO_PERMISSIONS", "304"); 	// no permissions
define("EXPIRED_VCODE", "305");		// verify code is expired
define("CONTACT_EXIST", "306");		// verify code is expired
define("INVALID_TOKEN", "307");		// verify code is expired
define("CONTACT_NO_EXIST", "308");		// verify code is expired
define("DATA_NO_EXIST", "310");		// verify code is expired
define("INVALID_VERIFY", "311");		// verify code is expired
define("INVALID_EMAIL", "312");		// verify code is expired
define("INVALID_AMOUNT", "313");		// verify code is expired
define("FILE_ERROR", "309");		// verify code is expired
define("SERVER_INTERNAL_ERROR", "401"); // server process error
define("CHAT_SERVER_ERROR", "402"); // chat server down


define("DEVICE_IPHONE", "iphone");	// device type iPhone
define("DEVICE_ANDROID", "android");// device type Android
define("LIMITNUM", "10");	// device type iPhone


function isNullOrEmptyString($question)
{
    return (!isset($question) || trim($question)==='');
}

function generateVerifyCode()
{
	$code = rand(1000, 9999); 
	return $code;
}
class ProcessController extends Controller
{

    function process(Request $request, $action){
		switch($action)
		{
			case 'register';
				$this->register($request);				
				break;
			case 'fbregister';
				$this->fbregister($request);				
				break;
			case 'update';
				$this->update($request);				
				break;
			case 'login';
				$this->login($request);				
				break;
			case 'getprofile';
				$this->getProfile($request);				
				break;
			case 'changepassword';
				$this->changePassword($request);				
				break;
			case 'forgotpassword';
				$this->forgotPassword($request);				
				break;
			case 'addcontact';
				$this->addContacts($request);				
				break;
			case 'delcontact';
				$this->delContacts($request);				
				break;
			case 'getcontacts';
				$this->getContacts($request);				
				break;
			case 'userphoto';
				$this->userPhoto($request);				
				break;
			case 'stagephoto';
				$this->stagePhoto($request);				
				break;
			case 'stagethumb';
				$this->stagethumb($request);				
				break;
			case 'addtempstage';
				$this->stagePhoto($request);				
				break;
			case 'deletetempstage';
				$this->deletetempStage($request);				
				break;
			case 'gettempstage';
				$this->gettempStage($request);				
				break;
			case 'addhistory';
				$this->addHistory($request);				
				break;
			case 'getstage';
				$this->getStage($request);				
				break;
			case 'getcomment';
				$this->getComment($request);				
				break;
			case 'addcomment';
				$this->addComment($request);				
				break;
			case 'deletecomment';
				$this->deleteComment($request);				
				break;
			case 'sendpoint';
				$this->sendPoint($request);				
				break;
			case 'addpoint';
				$this->addPoint($request);				
				break;
			case 'addfavorite';
				$this->addFavorite($request);				
				break;
			case 'gethistorybyuser';
				$this->getHistorybyUser($request);				
				break;
			case 'getownhistory';
				$this->getOwnHistory($request);				
				break;
			case 'getrecenthistory';
				$this->getRecentHistory($request);				
				break;
			case 'getprerecenthistory';
				$this->getPreRecentHistory($request);				
				break;
			case 'getsearchhistory';
				$this->getSearchHistory($request);				
				break;
			case 'gethighpointusers';
				$this->getHighPointUsers($request);				
				break;
			case 'getreceivenumusers';
				$this->getReceivenumUsers($request);				
				break;
			case 'getsendnumusers';
				$this->getSendnumUsers($request);				
				break;
			case 'deletehistory';
				$this->deleteHistory($request);				
				break;
			case 'getadvertisement';
				$this->getAdvertisement($request);				
				break;
			case 'getverify';
				$this->getVerify($request);				
				break;
			case 'extrapoint';
				$this->extraPoint($request);				
				break;
				
		}
		
	}		
	//$fullname, $name, $pwd, $email, $phone, $thumb, $birth, $addr, $supportnum, $pointnum, $commentnum, $friendnum
	private function register($request)
	{
		
		if( $request->has('pwd') == false  ||
			$request->has('email') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$pwd = $request->input('pwd', '');
		$email = $request->input('email', '');
		
		$user = User::registerUser($pwd, $email);
		
		if(empty($user)){
			$this->outputResult(USER_EXIST);
			return;
		}else{
			return $this->outputResult(SUCCESS, $user);
		}
	}	
	
	private function getVerify($request)
	{
		if($request->has('email') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$email = $request->input('email', '');
		
		$value = User::confirmEmail($email);
		
		if($value){
			// send mail
			$vcode = generateVerifyCode();
			$sendok = 1;//$this->sendMail($email, $vcode);
			if($sendok){
				try{
					$ch = curl_init();

					// set URL and other appropriate options
					curl_setopt($ch, CURLOPT_URL, "http://everyday.chaodachaoda.com/mail/test/testemail.php?email=".$email."&vcode=".$vcode);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					
					// grab URL and pass it to the browser
					curl_exec($ch);
					
					// close cURL resource, and free up system resources
					curl_close($ch);
					User::saveVerify($email, $vcode);					

				}catch(Exception $e){
					return $this->outputResult(INVALID_EMAIL);
				}
				return $this->outputResult(SUCCESS, $vcode);
			}else{
				return $this->outputResult(INVALID_EMAIL);
			}
		}else{
			return $this->outputResult(NO_USER_EXIST);
		}
	}
	private function fbregister($request)
	{
		
		if( $request->has('fullname') == false  ||
			$request->has('pwd') == false  ||
			$request->has('email') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$username = $request->input('fullname', '');
		$pwd = $request->input('pwd', '');
		$email = $request->input('email', '');
		
		$user = User::fbregisterUser($username, $pwd, $email);
		
		if(empty($user)){
			$this->outputResult(USER_EXIST);
			return;
		}else{
			return $this->outputResult(SUCCESS, $user);
		}
	}	
	//$fullname, $name, $pwd, $email, $phone, $thumb, $birth, $addr, $supportnum, $pointnum, $commentnum, $friendnum
	private function update($request)
	{
		
		if( $request->has('userno') == false  ||
			//$request->has('fullname') == false ||
			$request->has('username') == false  ||
			$request->has('email') == false  ||
			//$request->has('phone') == false  ||
			//$request->has('birth') == false  ||
			$request->has('token') == false  ||
			$request->has('addr') == false)
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		//$fullname = $request->input('fullname', '');
		$username = $request->input('username', '');
		$email = $request->input('email', '');
		//$phone = $request->input('phone', '');
		//$birth = $request->input('birth', '');
		$addr = $request->input('addr', '');
		$token = $request->input('token', '');
		
		$user = User::updateUser($userno, $username, $email, $addr, $token);
		
		if(empty($user)){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else{
			return $this->outputResult(SUCCESS, $user);
		}
	}
	private function login($request)
	{
		if( $request->has('email') == false  ||
			$request->has('pwd') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$email = $request->input('email', '');
		$pwd = $request->input('pwd', '');
		
		$user = User::login($email, $pwd);
		
		if(empty($user)){
			$this->outputResult(INVALID_PASSWORD);
			return;
		}else{
			return $this->outputResult(SUCCESS, $user);
		}
	}
	private function getProfile($request)
	{
		
		if( $request->has('userno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			$user = User::where('id', '=', $userno)->first();
			return $this->outputResult(SUCCESS, $user);
		}
	}

	
	private function changePassword($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('oldpwd') == false  ||
			$request->has('token') == false  ||
			$request->has('newpwd') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');		
		$oldpwd = $request->input('oldpwd', '');		
		$newpwd = $request->input('newpwd', '');
		$token = $request->input('token','');
		$user = User::changewd($userno, $oldpwd, $newpwd, $token);
		
		if(empty($user)){
			$this->outputResult(INVALID_PASSWORD);
			return;
		}else{
			return $this->outputResult(SUCCESS, $user);
		}
	}
	private function forgotPassword($request)
	{
		if( $request->has('email') == false  ||
			$request->has('vcode') == false  ||
			$request->has('newpwd') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$newpwd = $request->input('newpwd', '');
		$vcode = $request->input('vcode', '');
		$email = $request->input('email','');
		$confirm = User::confirmEmail($email);
		if($confirm){
			$confirm = User::confirmVerify($email, $vcode);
			if($confirm){
				$user = User::forgotpassword($email, $newpwd);
		
				if(empty($user)){
					$this->outputResult(INVALID_PASSWORD);
					return;
				}else{
					return $this->outputResult(SUCCESS, $user);
				}
			}else{
				return $this->outputResult(INVALID_VERIFY);
			}
		}else{
			return $this->outputResult(NO_USER_EXIST);
		}
		
	}
	private function addContacts($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('contactno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$contactno = $request->input('contactno', '');
		$token = $request->input('token','');
		$id = User::addcontact($userno, $contactno, $token);
		
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == 0){
			$this->outputResult(CONTACT_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			return $this->outputResult(SUCCESS, $id);
		}
	}
	private function delContacts($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('contactno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$contactno = $request->input('contactno', '');
		$token = $request->input('token','');
		$id = User::delcontact($userno, $contactno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == 0){
			$this->outputResult(CONTACT_NO_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			return $this->outputResult(SUCCESS, $id);
		}
	}
	private function getContacts($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			$users = Contacts::getContacts($userno, $pagenum * LIMITNUM, LIMITNUM);
			return $this->outputResult(SUCCESS, $users);
		}
	}
	private function deletetempStage($request)
	{
		if( $request->has('userno') == false  ||
		    $request->has('sno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$sno = $request->input('sno', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $id = TempStage::deleteStage($sno, $userno);
		
			if($id == -1){
				$this->outputResult(DATA_NO_EXIST);
				return;
			}else{
				return $this->outputResult(SUCCESS, $id);
			}
		}
	}
	private function gettempStage($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $stages = TempStage::getStages($userno, $pagenum * LIMITNUM, LIMITNUM);			
			 return $this->outputResult(SUCCESS, $stages);
		} 
	}
	private function addHistory($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('content') == false  ||
		    $request->has('levelnum') == false  ||
		    $request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$content = $request->input('content', '');
		$levelnum = $request->input('levelnum', '');
        $token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $id = TempStage::addHistory($userno,$content);	
			 User::sumLevelNum($userno, $levelnum);
			 return $this->outputResult(SUCCESS, $id);
		} 
	}
	private function getStage($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('hno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$hno = $request->input('hno', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $stages = Stage::getStages($userno, $hno, 0, 1000);			
			 return $this->outputResult(SUCCESS, $stages);
		} 
	}
	private function deleteComment($request)
	{
		if( $request->has('userno') == false  ||
		    $request->has('cno') == false  ||
		    $request->has('hno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$cno = $request->input('cno', '');	
		$hno = $request->input('hno', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $id = Comment::deleteComment($cno, $hno);
			if($id == -1){
				$this->outputResult(DATA_NO_EXIST);
				return;
			}else{
				return $this->outputResult(SUCCESS, $id);
			}
		}
	}
	private function deleteHistory($request)
	{
		if( $request->has('userno') == false  ||
		    $request->has('hno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$hno = $request->input('hno', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $id = History::deleteHistory($hno, $userno);
			if($id == -1){
				$this->outputResult(DATA_NO_EXIST);
				return;
			}else{
				return $this->outputResult(SUCCESS, $id);
			}
		}
	}
	private function getComment($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('hno') == false  ||
		    $request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$hno = $request->input('hno', '');	
		$pagenum = $request->input('pagenum', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $stages = Comment::getComments($hno, $pagenum * LIMITNUM, LIMITNUM);			
			 return $this->outputResult(SUCCESS, $stages);
		} 
	}
	private function addComment($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('hno') == false  ||
		    $request->has('content') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$hno = $request->input('hno', '');
		$content = $request->input('content', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
		     $id = Comment::addComment($hno, $userno, $content);
			 //CommentStar::addCommentNum($userno, $hno);
			 
			 return $this->outputResult(SUCCESS, $id);
		} 
	}
	private function sendPoint($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('huserno') == false  ||
		    $request->has('hno') == false  ||
		    $request->has('amount') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$huserno = $request->input('huserno', '');	
		$hno = $request->input('hno', '');
		$amount = $request->input('amount', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		$numflag = User::calPointNum($userno, $amount);
		if($numflag){
			 $id = SendPoint::addSendPoint($userno, $huserno, $hno, $amount);		
			 User::sumSendNum($userno, $amount);	
			 User::sumReceiveNum($huserno, $amount);
		}else{
			return $this->outputResult(INVALID_AMOUNT);
		}
		 return $this->outputResult(SUCCESS, $id);
		 
	}
	private function addPoint($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('amount') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$amount = $request->input('amount', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $id = User::sumPointNum($userno, $amount);
		 return $this->outputResult(SUCCESS, $id);
		 
	}
	private function extraPoint($request)
	{
		if( $request->has('userno') == false  ||
		    $request->has('optionnum') == false ||
		    $request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$optionnum = $request->input('optionnum', '');
		$token = $request->input('token','');
		
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		
		$user = User::sumPointOptionNum($userno, $optionnum);
		 //return $this->outputResult(SUCCESS, $id);
		if(empty($user)){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else{
			return $this->outputResult(SUCCESS, $user);
		}
		 
	}
	private function addFavorite($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
		    $request->has('hno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$hno = $request->input('hno', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $id = Favorite::addFavorite($hno, $userno);
		 return $this->outputResult(SUCCESS, $id);
		 
	}
	private function getAdvertisement($request)
	{  
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		
		 $Ads = Advertisement::getAdvertisement($userno, $pagenum * LIMITNUM, LIMITNUM);
		 $levelnum = 1;
		 User::sumLevelNum($userno, $levelnum);
		 return $this->outputResult(SUCCESS, $Ads);		 
	}
	private function getRecentHistory($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $histories = History::getRecentHistory($userno, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $histories);		 
	}
	private function getPreRecentHistory($request)
	{
		$hno = 0;$uno = 0;
		
		if($request->has('pagenum') == false)
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$pagenum = $request->input('pagenum', '');
		
		 $histories = History::getPreRecentHistory($pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $histories);		 
	}
	private function getSearchHistory($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		if( $request->has('searchkey') )
			$searchkey = $request->input('searchkey','');
		else
			$searchkey = "";
		
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $histories = History::getSearchHistory($userno, $searchkey, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $histories);		 
	}
	private function getHistorybyUser($request)
	{
		$hno = 0;$uno = 0;
		if( $request->has('userno') == false  ||
			$request->has('huserno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$huserno = $request->input('huserno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $histories = History::getHistorybyUser($userno, $huserno, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $histories);		 
	}
	private function getOwnHistory($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $histories = History::getOwnHistory($userno, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $histories);		 
	}
	private function getHighPointUsers($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $highusers = History::getHighUsers($userno, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $highusers);		 
	}
	private function getReceivenumUsers($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $highusers = History::getReceivenumUsers($userno, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $highusers);		 
	}
	private function getSendnumUsers($request)
	{
		$hno = 0;$uno = 0;
		
		if( $request->has('userno') == false  ||
			$request->has('pagenum') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$pagenum = $request->input('pagenum', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}
		 $highusers = History::getSendnumUsers($userno, $pagenum * LIMITNUM, LIMITNUM);
		 return $this->outputResult(SUCCESS, $highusers);		 
	}
	
	private function outputResult( $retcode, $content = '', $error_msg = null )
	{
	    if( $error_msg == null )
		{
			switch ($retcode)
			{
			case SUCCESS:
				$error_msg = '';
				break;
			case MISSING_PARAMETER:
				$error_msg = 'Parameter is missing. || 参数消失.';
				break;
			case INVALID_PARAMETER:
				$error_msg = 'Parameter is invalid. || 参数无效.';
				break;
			case USER_EXIST:
				$error_msg = 'User is already exist. || 用户已存在.';
				break;
			case NO_USER_EXIST:
				$error_msg = 'User is not exist. || 用户尚无存在.';
				break;
			case INVALID_PASSWORD:
				$error_msg = 'Your input password is not correct. || 用户输入密码不正确.';
				break;
			case INVALID_VCODE:
				$error_msg = 'Verification code is invalid. || 验证码无效.';
				break;
			case EXPIRED_VCODE:
				$error_msg = 'Verification code is expired. || 验证码期满.';
				break;
			case CONTACT_EXIST:
				$error_msg = 'Contact is already exist. || 接触已存在.';
				break;
			case CONTACT_NO_EXIST:
				$error_msg = 'Contact is not exist. || 接触尚无存在.';
				break;
			case INVALID_TOKEN:
				$error_msg = 'You didnt authorization. || 用户没法认证.';
				break;
			case FILE_ERROR:
				$error_msg = 'There are no file. || 文件不存在.';
				break;
			case STATUS_INACTIVE:
				$error_msg = 'You can not login, you are disabled by administrator. || 用户没法登录，您被管理者禁止.';
				break;
			case DATA_NO_EXIST:
				$error_msg = 'There are no data. || 资料不存在.';
				break;
			case NO_VERIFIED:
				$error_msg = 'You are not verified yet. || 用户未认证.';
				break;
			case NO_PERMISSIONS:
				$error_msg = 'You have no permission. || 用户没有许可.';
				break;
			case SERVER_INTERNAL_ERROR:
				$error_msg = 'Server internal process error. || 服务器内部处理错误.';
				break;
			case CHAT_SERVER_ERROR:
				$error_msg = 'Chat server is not responding. || 聊天服务器没有反应.';
				break;
			case INVALID_VERIFY:
				$error_msg = 'Your verify code is not match. || 无效的验证码.';
				break;
			case INVALID_EMAIL:
				$error_msg = "Your email address is not exist. || 无效的邮箱";
				break;
			case INVALID_AMOUNT:
			    $error_msg = "You dont have enough point. Please purchase your point. || 用户所有的星星数不够，请购买星星数.";
				break;
			default :
				$error_msg = '';
				break;
			}
		}

		$response = array( 'retcode'=>$retcode, 'content'=>$content, 'error_msg'=>$error_msg );
		
		echo json_encode($response, JSON_UNESCAPED_UNICODE);		
	}
	private function userPhoto($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			if (isset($_FILES["myfile"]))
			{
				$error = $_FILES["myfile"]["error"];

				if ($error === 0)
				{
					//If Any browser does not support serializing of multiple files using FormData()
					if (!is_array($_FILES["myfile"]["name"])) //single file
					{
						$ext1 = explode('.',$_FILES['myfile']['name']);
						$file_ext=strtolower(end($ext1));
						$fileName = $userno . "_".time().".".$file_ext;
						$output_dir = "uploads/photos/";
						if (file_exists($output_dir . $fileName))
						{
							unlink($output_dir . $fileName);
						}
						move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir . $fileName);
						$id = User::updatethumb($userno, $fileName);
						if($id == -1){
							$this->outputResult(NO_USER_EXIST);
							return;
						}else{
							return $this->outputResult(SUCCESS, $fileName);
						}
						
					}else{
						return $this->outputResult(FILE_ERROR);
					}
		//			else  //Multiple files, file[]
		//			{
		//				$fileCount = count($_FILES["myfile"]["name"]);
		//				for ($i = 0; $i < $fileCount; $i++)
		//				{
		//					$fileName = $username . "_photo_" . time() . ".png";   //$_FILES["myfile"]["name"][$i];
		//					move_uploaded_file($_FILES["myfile"]["tmp_name"][$i], $output_dir . $fileName);
		//
		//					updatePhoto($username, $fileName);
		//					$ret = SUCCESS;
		//				}
		//			}
				}else{
					return $this->outputResult(FILE_ERROR);
				}
			}else{
				return $this->outputResult(FILE_ERROR);
			}
		}
	}
	private function stagePhoto($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('content') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$content = $request->input('content', '');	
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			if (isset($_FILES["myfile"]))
			{
				$error = $_FILES["myfile"]["error"];

				if ($error === 0)
				{
					//If Any browser does not support serializing of multiple files using FormData()
					if (!is_array($_FILES["myfile"]["name"])) //single file
					{
						$ext1 = explode('.',$_FILES['myfile']['name']);
						$file_ext=strtolower(end($ext1));
						$prefile = $userno . "_".time();
						$fileName = $prefile.".".$file_ext;
						$output_dir = "uploads/images/";
						if (file_exists($output_dir . $fileName))
						{
							unlink($output_dir . $fileName);
						}
						move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir . $fileName);
						TempStage::addStage($userno, $fileName, $content);
						
						return $this->outputResult(SUCCESS, $prefile);
						
					}else{
						return $this->outputResult(FILE_ERROR);
					}
		//			else  //Multiple files, file[]
		//			{
		//				$fileCount = count($_FILES["myfile"]["name"]);
		//				for ($i = 0; $i < $fileCount; $i++)
		//				{
		//					$fileName = $username . "_photo_" . time() . ".png";   //$_FILES["myfile"]["name"][$i];
		//					move_uploaded_file($_FILES["myfile"]["tmp_name"][$i], $output_dir . $fileName);
		//
		//					updatePhoto($username, $fileName);
		//					$ret = SUCCESS;
		//				}
		//			}
				}else{
					return $this->outputResult(FILE_ERROR);
				}
			}else{
				return $this->outputResult(FILE_ERROR);
			}
		}
	}
	
	private function stagethumb($request)
	{
		if( $request->has('userno') == false  ||
			$request->has('token') == false )
		{ 
			$this->outputResult(MISSING_PARAMETER);
			return;
		}
		$userno = $request->input('userno', '');
		$token = $request->input('token','');
		$id = User::confirmUser($userno, $token);
		if($id == -1){
			$this->outputResult(NO_USER_EXIST);
			return;
		}else if($id == -2){
			$this->outputResult(INVALID_TOKEN);
			return;
		}else{
			if (isset($_FILES["myfile"]))
			{
				$error = $_FILES["myfile"]["error"];
				if ($error === 0)
				{
					//If Any browser does not support serializing of multiple files using FormData()
					if (!is_array($_FILES["myfile"]["name"])) //single file
					{
						
						$fileName = $_FILES['myfile']['name'];
						$output_dir = "uploads/images/";
						if (file_exists($output_dir . $fileName))
						{
							unlink($output_dir . $fileName);
						}
						move_uploaded_file($_FILES["myfile"]["tmp_name"], $output_dir . $fileName);	
						$id = 1;			
						return $this->outputResult(SUCCESS, $id);
						
					}else{
						return $this->outputResult(FILE_ERROR);
					}
				}else{
					return $this->outputResult(FILE_ERROR);
				}
			}else{
				return $this->outputResult(FILE_ERROR);
			}
		}
	}
	private function sendMail($email, $vcode){
				
		require_once 'sendmail/class.phpmailer.php';
		try {
			$mail = new PHPMailer(true); //New instance, with exceptions enabled

			$body             = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html>  <head>    
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">  </head>  <body>
				<p style=""><b>Confirm your new email address for everyday.</b></p>  <br>
				
				<p>Dear.</p> 
				<p>Your verify code is <font color="blue"><b>'.$vcode.'</b></font>. Please verify on app.</p>  
				<p>Regards.</p> 
				</body>
				</html>';
			$body             = preg_replace('/\\\\/','', $body); //Strip backslashes

			$mail->IsSMTP();                           // tell the class to use SMTP
			$mail->SMTPAuth   = true;                  // enable SMTP authentication
			$mail->Port       = 26;                    // set the SMTP server port
			$mail->Host       = "mail.chaodachaoda.com"; // SMTP server
			$mail->Username   = "no-reply@chaodachaoda.com";     // SMTP server username
			$mail->Password   = "R=Ci[AyC5MU#ei";            // SMTP server password

			$mail->IsSendmail();  // tell the class to use Sendmail

			$mail->AddReplyTo("no-reply@chaodachaoda.com","Support");

			$mail->From       = "no-reply@chaodachaoda.com";
			$mail->FromName   = "Support";

			$to = $email;

			$mail->AddAddress($to);

			$mail->Subject  = "Verify code";

			$mail->WordWrap   = 80; // set word wrap
			$mail->MsgHTML($body);

			$mail->IsHTML(true); // send as HTML

			$mail->Send();
			//echo 'Message has been sent.';
			return 1;
		} catch (phpmailerException $e) {
			//echo $e->errorMessage();
			return 0;
		}
	}
}
