@extends('backoffice.wizard.property.setting_layout')
@section('setting_content')

<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ADD A CLIENT";
	if( !empty($model->id) )
	{
		$method = "put";										
		$title = "UPDATE A CLIENT";
		$create = 'Submit';
	}

?>

<div class="item_container" style="margin:auto;height:300px">
	<div class="items">		
		<span style="float:left;margin-left:10px;margin-top:10px">
		{{$title}}
		</span>

	</div>		
	
	<div class="form_center">		
		{{ Form::open(array('url' => '/backoffice/property/wizard/client/' . $model->id, 'method' => $method )) }}
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="name" class="cm-required">Name:</label>
						<input type="text" id="name" name="name" class="input-text" size="32" maxlength="50" value="{{$model->name}}" />
						<i class="name-edit fa fa-pencil"></i>
					</div>							
					<div class="form-field">
						<label for="name">Description:</label>
						<input type="text" id="desc" name="description" class="input-text" size="32" maxlength="100" value="{{$model->description}}" />	
						<i class="name-edit fa fa-pencil"></i>
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
		@if (isset($prev))
			<input type="button" class="arrow-button" onclick="location.href = '/backoffice/property/wizard/{{$prev}}';"  value="  Prev  " />
			&nbsp;&nbsp;&nbsp;&nbsp;
		@endif	
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/property/wizard/property';"  value="  Next  " />
	</span>						
</div>
	
@stop