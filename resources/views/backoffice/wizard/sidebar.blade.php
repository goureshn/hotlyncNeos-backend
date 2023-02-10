@section('sidebar')
<div class="left_menu">
<?php
	$select_option1 = "";
	$select_option2 = "";
	$select_option3 = "";
	$select_option4 = "";
	$select_option5 = "";
	$select_option6 = "";
	$confirm = explode('backoffice/property/wizard', URL::to('/').'/'.Route::getCurrentRoute()->getPath());	
	if(count($confirm) > 1){
		$select_option1 = "#999";
	}
	$confirm = "";
	$confirm = explode('backoffice/admin/wizard', URL::to('/').'/'.Route::getCurrentRoute()->getPath());	
	if(count($confirm) > 1){
		$select_option2 = "#999";
	}
	
	$confirm = "";
	$confirm = explode('backoffice/user/wizard', URL::to('/').'/'.Route::getCurrentRoute()->getPath());	
	if(count($confirm) > 1){
		$select_option3 = "#999";
	}
	$confirm = "";
	$confirm = explode('backoffice/call/wizard', URL::to('/').'/'.Route::getCurrentRoute()->getPath());	
	if(count($confirm) > 1){
		$select_option4 = "#999";
	}
	
	$confirm = "";
	$confirm = explode('backoffice/guestservice/wizard', URL::to('/').'/'.Route::getCurrentRoute()->getPath());	
	if(count($confirm) > 1){
		$select_option5 = "#999";
	}
?>	

	
	<div class="eachmenu" style="margin-top:47px;margin-left:0px;">		
		<p id="setup_wizard"><i class="fa fa-gear" style="width:25px;font-size:25px"></i><span style="margin-top:-5px;font-size:15px;font-weight:bold;">&nbsp;&nbsp;SETUP WIZARD<span></p>
	</div>
	<div class="eachmenu" style="margin-top:37px;">
		<img src="/images/menu_icon_1.png" width="11px" style="margin-left:3px;"/>
		<a href="/backoffice/property/wizard/client"><p style="padding-left:2px;color:<?php echo $select_option1;?>">Property Setup</p></a>
	</div>
	<div class="eachmenu">
		<img src="/images/admin_setup_icon.png" width="17px"/>
		<a href="/backoffice/admin/wizard/department"><p style="color:<?php echo $select_option2;?>">Admin Setup</p></a>
	</div>
	<div class="eachmenu">
		<img src="/images/menu_icon_2.png"/>
		<a href="/backoffice/user/wizard/user"><p style="color:<?php echo $select_option3;?>">User Setup</p></a>
	</div>
	<div class="eachmenu">
		<img src="/images/menu_icon_3.png"/>
		<a href="/backoffice/call/wizard/section"><p style="color:<?php echo $select_option4;?>">Call Accounting Setup</p></a>
	</div>
	<div class="eachmenu">
		<img src="/images/menu_icon_4.png"/>
		<a href="/backoffice/guestservice/wizard/departfunc/create"><p style="color:<?php echo $select_option5;?>">Guest Services Setup</p></a>
	</div>
	<div class="eachmenu">
		<img src="/images/menu_icon_5.png"/>
		<p style="color:<?php echo $select_option6;?>">System Level Setup</p>
	</div>
</div>
@show