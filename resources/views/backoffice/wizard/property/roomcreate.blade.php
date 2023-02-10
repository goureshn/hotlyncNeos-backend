@extends('backoffice.wizard.property.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "FLOOR";
	if( !empty($model->id) )
	{
		$method = "put";										
		$title = "model";
		$create = 'Update';
	}
	
?>

<div class="item_container" style="margin:auto">
	<div class="items">		
		<span style="float:left;margin-left:30px;margin-top:10px">
		{{$title}}
		</span>

	</div>		
		
	<div class="form_center">		
		
		
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="bldg_id" class="cm-required">Building:</label>
						<?php echo Form::select('bldg_id', $building, $model->bldg_id, ['style' => 'width:200px', 'onchange' => 'onSelectBuilding()', 'id' => 'build_id']); ?>						
					</div>
					{{ Form::open(array('url' => '/backoffice/property/wizard/room/' . $model->id, 'method' => $method )) }}							
					<div class="form-field">
						<label for="flr_id" class="cm-required">Floor:</label>
						<?php echo Form::select('flr_id', $floor, $model->flr_id, ['style' => 'width:200px']); ?>						
					</div>
					<div class="form-field">
						<label for="type_id" class="cm-required">Type:</label>
						<?php echo Form::select('type_id', $roomtype, $model->type_id, ['style' => 'width:200px']); ?>
					</div>
					<div class="form-field">
						<label for="hskp_status_id" class="cm-required">Housekeeping Status:</label>
						<?php echo Form::select('hskp_status_id', $hsks, $model->hskp_status_id, ['style' => 'width:200px']); ?>
					</div>
					<div class="form-field">
						<label for="room" class="cm-required">Room:</label>
						<input type="text" id="room" name="room" class="input-text" size="32" maxlength="50" value="{{$model->room}}" />															
					</div>						
					<div class="form-field">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="{{$model->description}}" />											
					</div>		
									
				</fieldset>
			</div>
		
			<div class="submit_container">
				<span class="cm-button-main ">
					<input type="submit" value="{{$create}}"/>
				</span>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<span class="cm-button-main cm-process-items">
					<input type="button" value="Cancel" onclick="location.href = '/backoffice/property/wizard/room'" />
				</span>
			</div>
		
		{{ Form::close() }}				
			
	</div>	
	
</div>

<script type="text/javascript">
	var roomupload = {
        url: "/upload",		
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
            $("#floorfile").val(data);
			console.log(data);
        },
        deleteCallback: function(data, pd)
        {   
			console.log(data);        
        }
    }
	
	$(".room_upload").uploadFile(roomupload);
	
	function onSelectBuilding()
	{
		var build_id = $('#build_id').val();
		console.log(build_id);
		location.href="/backoffice/property/wizard/room/create?bldg_id=" + build_id;
	}
	
</script>


@stop

