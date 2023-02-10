@extends('backoffice.wizard.property.setting_layout')
@section('setting_content')

<?php
	$method = "post";								
	$create = 'Submit';
	$title = "PERMISSION GROUP";
	if( !empty($model->id) )
	{
		$method = "put";										
		$create = 'Update';
	}
?>

<div class="item_container" style="margin:auto">
	<div class="items">		
		<span style="float:left;margin-left:10px;margin-top:10px">
		{{$title}}
		</span>
	</div>		
	
	<div class="form_center">		
		{{ Form::open(array('url' => '/backoffice/user/wizard/pmgroup/' . $model->id, 'method' => $method )) }}
			<div id="content_general" style="margin-top:30px;height:450px;overflow-y:scroll;">
				<fieldset>
					<div class="form-field">
						<label for="property" class="cm-required">Property:</label>
						<?php echo Form::select('property_id', $property, $model->property_id, array('class'=>'select-form')); ?>
					</div>
					<div class="form-field">
						<label for="firstname" class="cm-required">Name:</label>
						<input type="text" id="name" name="name" class="input-text" size="32" maxlength="50" value="{{$model->name}}" />											
					</div>		
					<div class="form-field">
						<label for="email" class="cm-required">Email:</label>
						<input type="text" id="email" name="email" class="input-text" size="32" maxlength="50" value="{{$model->email}}" />											
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
					<input type="button" class="arrow-button" value="Cancel" onclick="location.href = '/backoffice/user/wizard/pmgroup'"/>
				</span>
			</div>
		
		{{ Form::close() }}
	</div>	

</div>

<script type="text/javascript">
	
	
</script>

@stop