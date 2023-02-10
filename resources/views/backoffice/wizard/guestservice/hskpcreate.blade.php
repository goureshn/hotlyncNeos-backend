@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "HOUSEKEEPING";
	if( !empty($model->id) )
	{
		$method = "put";										
		$create = 'Update';
	}
	
	$current_url = '/backoffice/guestservice/wizard/hskp/create';
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
		
		{{ Form::open(array('url' => '/backoffice/guestservice/wizard/hskp/' . $model->id, 'method' => $method )) }}		
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="building" class="cm-required">Building:</label>
						<?php echo Form::select('bldg_id', $buildlist, $model->bldg_id, ['style' => 'width:auto']); ?>						
					</div>
					<div class="form-field">
						<label for="status" class="cm-required">Status:</label>
						<input type="text" id="status" name="status" class="input-text" size="32" maxlength="50" value="{{$model->status}}" />															
					</div>						
					<div class="form-field">
						<label for="short_code">PMS Code:</label>
						<input type="number" id="pms_code" name="pms_code" class="input-text" size="6" maxlength="4" value="{{$model->pms_code}}" />											
					</div>
					
					<script type="text/javascript">
						$(function(argument) {
							$('#chk_ivr').bootstrapSwitch();
						})	
					</script>
					
					<input type="hidden" id="chk_ivr_flag" name="chk_ivr_flag" value="0" />
					
					<div class="form-field">
						<label for="mobile">Use different IVR code:</label>
						<div style="float:left;margin-top:-3px;">
							<input type="checkbox"  id="chk_ivr"/>		
						</div>
						
						<label for="short_code" style="float:left;margin-left:50px">IVR Code:</label>
						<input type="number" id="ivr_code" name="ivr_code" class="input-text" size="6" maxlength="4" value="{{$model->ivr_code}}" />											
					</div>
					
					<div class="form-field">
						<label for="type" class="cm-required">Type:</label>
						<?php echo Form::select('type_id', $model->getTypeList(), '0', ['style' => 'width:auto', 'onchange' => 'onSelectType()', 'id' => 'type_id']); ?>						
					</div>			
				
					<div class="form-field">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="{{$model->description}}" />											
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
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/device/create';"   value="  Next  " />
	</span>						
</div>

<script type="text/javascript">
	$('#chk_ivr').on('switchChange.bootstrapSwitch', function(event, state) {
		console.log(this); // DOM element
		console.log(event); // jQuery event
		console.log(state); // true | false
		
		if (state == false)
		{	
			$("#ivr_code").attr("disabled", "disabled");
			$("#chk_ivr_flag").val(0);
		}
		else
		{
			$("#ivr_code").removeAttr("disabled");
			$("#chk_ivr_flag").val(1);
		}
	});
	
	$("#ivr_code").attr("disabled", "disabled");
	
	
</script>



@stop

