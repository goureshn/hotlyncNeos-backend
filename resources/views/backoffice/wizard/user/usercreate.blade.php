@extends('backoffice.wizard.user.setting_layout')
@section('setting_content')

<?php
	$method = "post";								
	$create = 'Submit';
	$title = "CREATE USER";
	if( !empty($model->id) )
	{
		$method = "put";										
		$title = "UPDATE USER";
		$create = 'Update';
	}
	
	$yesno = [
        '1' => 'Yes',
        '2' => 'No'
    ];
?>

<div class="item_container" style="margin:auto">
	<div class="items">		
		<span style="float:left;margin-left:10px;margin-top:10px">
		{{$title}}
		</span>
	</div>		
	
	<div class="form_center">		
		{{ Form::open(array('url' => '/backoffice/user/wizard/user/' . $model->id, 'method' => $method )) }}
			<div id="content_general" style="margin-top:30px;height:450px;overflow-y:scroll;">
				<fieldset>
					<div class="form-field">
						<label for="firstname" class="cm-required">First Name:</label>
						<input type="text" id="firstname" name="first_name" class="input-text" size="32" maxlength="50" value="{{$model->first_name}}" />											
					</div>			
					<div class="form-field">
						<label for="secondname" class="cm-required">Last Name:</label>
						<input type="text" id="last_name" name="last_name" class="input-text" size="32" maxlength="50" value="{{$model->last_name}}" />											
					</div>		
					<div class="form-field">
						<label for="username" class="cm-required">User Name:</label>
						<input type="text" id="username" name="username" class="input-text" size="32" maxlength="50" value="{{$model->username}}" />											
					</div>		
					<div class="form-field">
						<label for="password" class="cm-required">Password:</label>
						<input type="text" id="password" name="password" class="input-text" size="32" maxlength="50" value="{{$model->password}}" />											
					</div>	
					<div class="form-field">
						<label for="ivr_password" class="cm-required">IVR-Password:</label>
						<input type="text" id="ivr_password" name="ivr_password" class="input-text" size="32" maxlength="50" value="{{$model->password}}" />											
					</div>							
					<div class="form-field">
						<label for="department" class="cm-required">Department:</label>
						<?php echo Form::select('dept_id', $department, $model->dept_id, array('class'=>'select-form')); ?>
					</div>
					<div class="form-field">
						<label for="email" class="cm-required">Email:</label>
						<input type="text" id="email" name="email" class="input-text" size="32" maxlength="50" value="{{$model->email}}" />											
					</div>						
					<div class="form-field">
						<label for="mobile">Mobile:</label>
						<input type="text" id="mobile" name="mobile" class="input-text" size="50" maxlength="100" value="{{$model->mobile}}" />											
					</div>
					<div class="form-field">
						<label for="mobile">Enable "Call to notify":</label>
						<input type="checkbox" class="input-text" style="margin-left:0px"  id="notify"/>											
					</div>					
					<div class="form-field">
						<label for="picture">Upload an image:</label>
						<div class="form_item" style="margin-top:0px">							
							<input type="text" class="file" style="float:left" id="picture" name="picture" value="{{$model->picture}}" READONLY />
							<div class="picture_upload">Select Picture..</div>							
						</div>									
					</div>	
					<div class="form-field">
						<label for="yesno" class="cm-required">Notify during working hours:</label>
						<?php echo Form::select('', $yesno, '0', array('class'=>'select-form')); ?>
					</div>					
					<div class="form-field">
						<label for="yesno" class="cm-required">Notify after working hours:</label>
						<?php echo Form::select('', $yesno, '0', array('class'=>'select-form')); ?>
					</div>		

					<div class="form-field">
						<label for="mobile">Upload multiple users?:</label>
						<input type="checkbox" class="input-text" style="margin-left:0px"  id="notify"/>											
					</div>					
					<div class="form-field">
						<label for="picture">Upload .csv file:</label>
						<div class="form_item" style="margin-top:0px">							
							<input type="text" class="file" style="float:left" id="excelupload" value="" READONLY />
							<div class="excel_upload">Upload</div>							
						</div>									
					</div>						
				</fieldset>				
				
			</div>
			<div class="submit_container">
				<span class="send-button cm-process-items">
					<i class="sendbt-icon fa fa-check"></i>
					<input type="submit" class="arrow-button" value="{{$create}}" onclick="onSubmit()" />
				</span>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<span class="cancel-button cm-process-items">
					<i class="cancelbt-icon fa fa-times"></i>
					<input type="button" class="arrow-button" value="Cancel" onclick="location.href = '/backoffice/user/wizard/user'"/>
				</span>
			</div>
		
		{{ Form::close() }}
	</div>	

</div>

<script type="text/javascript">
	var pictureupload = {
        url: "/uploadpicture",
        dragDrop: false,
        fileName: "myfile",
        multiple: false,
        showCancel: false,
        showAbort: false,
        showDone: false,
        showDelete: false,
        showError: true,
        showStatusAfterSuccess: false,
        showStatusAfterError: false,
        showFileCounter: false,
        allowedTypes: "jpg,png,jpeg",
        maxFileSize: 5120000,
        returnType: "text",
        onSuccess: function(files, data, xhr)
        {
            $('#picture').val(data);
        },
        deleteCallback: function(data, pd)
        {   
			console.log(data);        
        }
    }
	
	$(".picture_upload").uploadFile(pictureupload);
	
	var excelupload = {
        url: "/backoffice/user/wizard/user/upload",
        dragDrop: false,
        fileName: "myfile",
        multiple: false,
        showCancel: false,
        showAbort: false,
        showDone: false,
        showDelete: false,
        showError: true,
        showStatusAfterSuccess: false,
        showStatusAfterError: false,
        showFileCounter: false,
        allowedTypes: "csv,xlsx,xls",
        maxFileSize: 5120000,
        returnType: "text",
        onSuccess: function(files, data, xhr)
        {
            
        },
        deleteCallback: function(data, pd)
        {   
			console.log(data);        
        }
    }
	
	$(".excel_upload").uploadFile(excelupload);
	
	
</script>

@stop