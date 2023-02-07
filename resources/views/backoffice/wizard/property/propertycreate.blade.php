@extends('backoffice.wizard.property.setting_layout')
@section('setting_content')

<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ADD A PROPERTY";
	if( !empty($model->id) )
	{
		$method = "put";										
		$title = "UPDATE A PROPERTY";
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
		{{ Form::open(array('url' => '/backoffice/property/wizard/property/' . $model->id, 'method' => $method )) }}
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="desc" class="cm-required">Client:</label>
						<?php echo Form::select('client_id', $client, $model->client_id, array('class'=>'select-form')); ?>
					</div>
					<div class="form-field">
						<label for="name" class="cm-required">Property:</label>
						<input type="text" id="name" name="name" class="input-text" size="32" maxlength="50" value="{{$model->name}}" />											
					</div>						
					<div class="form-field">
						<label for="address">Address:</label>
						<input type="text" id="address" name="address" class="input-text" size="50" maxlength="100" value="{{$model->address}}" />											
					</div>			
					<div class="form-field">
						<label for="city">City:</label>
						<input type="text" id="city" name="city" class="input-text" size="32" maxlength="100" value="{{$model->city}}" />											
					</div>					
					<div class="form-field">
						<label for="country">Country:</label>
						<input type="text" id="country" name="country" class="input-text" size="32" maxlength="100" value="{{$model->country}}" />											
					</div>					
					<div class="form-field">
						<label for="contact">Contact Person:</label>
						<input type="text" id="contact" name="contact" class="input-text" size="32" maxlength="100" value="{{$model->contact}}" />											
					</div>	
					<div class="form-field">
						<label for="contact">Mobile:</label>
						<input type="text" id="mobile" name="mobile" class="input-text" size="32" maxlength="100" value="{{$model->mobile}}" />											
					</div>	
					<div class="form-field">
						<label for="email">Email:</label>
						<input type="text" id="email" name="email" class="input-text" size="32" maxlength="100" value="{{$model->email}}" />											
					</div>	
					<div class="form-field">
						<label for="desc" class="cm-required">Select Modules:</label>
						
						<?php echo Form::select('modules_ids[]', $model->getModuleList(), $model->getModules(),  ['multiple']); ?>
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
					<input type="button" class="arrow-button" value="Cancel" onclick="location.href = '/backoffice/property/wizard/property'"/>
				</span>
			</div>
		
		{{ Form::close() }}
	</div>	
</div>

@stop