@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "DEVICES";
	if( !empty($model->id) )
	{
		$method = "put";										
		$create = 'Update';
	}
	
	$current_url = '/backoffice/guestservice/wizard/device/create';
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
		
		{{ Form::open(array('url' => '/backoffice/guestservice/wizard/device/' . $model->id, 'method' => $method )) }}		
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="deft" class="cm-required">Department Function:</label>
						<?php echo Form::select('dept_id', $deftlist, $model->dept_id, ['style' => 'width:auto']); ?>						
					</div>
					
					<div class="form-field">
						<label for="type" class="cm-required">Type:</label>
						<?php echo Form::select('type_id', $model->getTypeList(), '0', ['style' => 'width:auto', 'onchange' => 'onSelectType()', 'id' => 'type_id']); ?>						
					</div>			
				
					<div class="form-field">
						<label for="description">Mobile No:</label>
						<input type="number" id="number" name="number" class="input-text" size="20" maxlength="20" value="{{$model->number}}" />											
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
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/hskp/create';" value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/alarm';"   value="  Next  " />
	</span>						
</div>

<script type="text/javascript">
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

