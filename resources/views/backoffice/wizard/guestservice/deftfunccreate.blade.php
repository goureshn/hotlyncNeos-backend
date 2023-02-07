@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "DEPARTMENT FUNCTION";
	if( !empty($model->id) )
	{
		$method = "put";										
		$create = 'Update';
	}
	
	$current_url = '/backoffice/guestservice/wizard/departfunc';
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];	
?>

<div class="item_container" style="margin:auto">
	<div class="items">		
		<span style="float:left;margin-left:10px;margin-top:10px">
		{{$title}}
		</span>

	</div>		
		
		{{ Form::open(array('url' => '/backoffice/guestservice/wizard/departfunc/' . $model->id, 'method' => $method )) }}		
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="department" class="cm-required">Department:</label>
						<?php echo Form::select('dept_id', $department, $model->dept_id, ['style' => 'width:auto']); ?>						
					</div>
					<div class="form-field">
						<label for="function" class="cm-required">Function:</label>
						<input type="text" id="function" name="function" class="input-text" size="32" maxlength="50" value="{{$model->function}}" />															
					</div>						
					<div class="form-field">
						<label for="short_code">Short Code:</label>
						<input type="text" id="short_code" name="short_code" class="input-text" size="10" maxlength="4" value="{{$model->short_code}}" />											
					</div>		
					<div class="form-field">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="{{$model->description}}" />											
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
					<input type="button" class="arrow-button" value="Cancel" onclick="return resetForm(this.form);" />
				</span>
			</div>
		
		{{ Form::close() }}				
			
	</div>	
	
</div>


<div class="bottom-button" style="clear:both;">
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/departfunc';" value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/location';"   value="  Next  " />
	</span>						
</div>

<script type="text/javascript">
	var excelupload = {
        url: "/backoffice/guestservice/wizard/departfunc/upload",		
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
            console.log(data);
			location.reload();
        },
        deleteCallback: function(data, pd)
        {   
			console.log(data);        
        }
    }
	
	$(".excel_upload").uploadFile(excelupload);
	
	
</script>



@stop

