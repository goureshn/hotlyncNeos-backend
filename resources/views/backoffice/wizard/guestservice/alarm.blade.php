@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ALARMS";
		
	$current_url = '/backoffice/guestservice/wizard/alarm';
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
		
	<div class="form_center">		
		<div id="content_general" style="margin-top:40px;clear:left">
			<fieldset>
				<div class="form-field">
					<label for="property" class="cm-required">Property:</label>
					<?php echo Form::select('property_id', $propertylist, $property_id, ['style' => 'width:auto', 'id' => 'property_id', 'onchange' => 'onSelectProperty()']); ?>						
				</div>					
				<div class="form-field">
					<label for="name">Name:</label>
					<input type="text" id="name" name="name" class="input-text" size="20" maxlength="30" value="" />
				</div>							
				<div class="form-field">
					<label for="description">Description:</label>
					<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="" />
					<span class="send-button cm-process-items" style="float:left;margin-left:10px;margin-top:0px;">						
						<input type="submit" class="arrow-button" value="Add" onclick="onAddAlarm()" />
					</span>
				</div>							
			</fieldset>
		</div>
		
		<div id="content_general" style="margin-top:20px;clear:left">
			<fieldset>
				<div class="form-field">
					<label for="location_group" class="cm-required">Alarm Group:</label>
					<?php echo Form::select('alarm_id', array(), '0', ['style' => 'width:auto', 'id' => 'alarm_id', 'onchange' => 'onSelectAlarmGroup()']); ?>						
				</div>										
				
				<!-- bootstrap-->
				<script src="/js/multiselect.js"></script>
				<script src="/js/jquery-sortable.js"></script>
				<style scoped>
					@import "/css/multimove.css";
				</style>
				<div class="form-field multimove">
					<div class="row">
						<div class="col-xs-5">
							<?php echo Form::select('from[]', array(), '0', ['class' => 'form-control', 'id' => 'search', 'size' => '8', 'multiple' => 'multiple']); ?>						
						</div>
						
						<div class="col-xs-2">
							<button type="button" id="search_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>
							<button type="button" id="search_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>
							<button type="button" id="search_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>
							<button type="button" id="search_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>
						</div>
						
						<div class="col-xs-5">
							<?php echo Form::select('to[]', array(), '0', ['class' => 'form-control', 'id' => 'search_to', 'size' => '8', 'multiple' => 'multiple']); ?>
						</div>
					</div>	
				</div>
				
			</fieldset>
		</div>
		
		<div class="submit_container" >
			<span class="send-button cm-process-items">
				<i class="sendbt-icon fa fa-check"></i>
				<input type="button" class="arrow-button" value="{{$create}}" onclick="onSubmit()" />
			</span>
			&nbsp;&nbsp;&nbsp;&nbsp;
			<span class="cancel-button cm-process-items">
				<i class="cancelbt-icon fa fa-times"></i>
				<input type="button" class="arrow-button" value="Cancel" onclick="return resetForm(this.form);" />
			</span>
		</div>
		
			
	</div>	
	
</div>



<div class="bottom-button" style="clear:both;">
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/device/create';" value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '#';"   value="  Next  " />
	</span>						
</div>
<script type="text/javascript">
	onSelectProperty();
		
	function onSelectProperty()
	{
		var property_id = $('#property_id').val();
			
		$.ajax({
            url: "/backoffice/guestservice/wizard/alarmgroup/list?property_id=" + property_id,
            success:function(data){
        		var model = $('#alarm_id');
				var count = 0;
				model.empty();

				$.each(data, function(index, element) {									
					model.append("<option value='"+ element.id +"'>" + element.name + "</option>");
					if( count == 0 )
					{
						$('#alarm_id').val(element.id);
						onSelectAlarmGroup();
					}
					count++;	
				});
            }
        });	
	}
	function onAddAlarm()
	{
		var property_id = $('#property_id').val();
		var name = $('#name').val();		
		var description = $('#description').val();
		
		if( name == "" )
			return;
		
		var data = {
			property: property_id,
			name: name,
			description: description
			};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/alarm/creategroup",
			data: data,
            success:function(data){
                console.log(data);
				$('#name').val("");
				$('#description').val("");
				
				var model = $('#alarm_id');
				model.append("<option value='"+ data.id +"'>" + data.name + "</option>");				
				
				$('#alarm_id').val(data.id);
				onSelectAlarmGroup();
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onSelectAlarmGroup()
	{
		var alarm_id = $('#alarm_id').val();
		
		
		$.ajax({
	        url: "/backoffice/guestservice/wizard/alarmgroup/userlist?alarm_id=" + alarm_id,
	        success:function(data){
                // console.log(data[0]);
				// console.log(data[1]);
				
				var from = $('#search');
				from.empty();

				$.each(data[0], function(index, element) {				
					from.append("<option value='"+ element.id +"'>" + element.username + "</option>");
				});
				
				var to = $('#search_to');
				to.empty();
				var count = 1;
				$.each(data[1], function(index, element) {				
					to.append("<option value='"+ element.id +"'>" + element.username + "</option>");
					count++;
				});
            },			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
		
	$('#search').multiselect({
		search: {
			left: '<input type="text" name="q" class="form-control" placeholder="Add User..." />',
			right: '<input type="text" name="q" class="form-control" placeholder="Selected Users..." />',
		},
		attatch : true
	});

		
	function onSubmit()
	{
		var alarm_id = $('#alarm_id').val();
		
		var select_id = new Object();
		var count = 0;
		$("#search_to option").each(function()
		{
			select_id[count] = $(this).val();
			count++;
		});
		
		var data = {alarm_id: alarm_id, select_id: select_id};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/alarmgroup/postalarm",
			data: data,
            success:function(data){
                alert(data);
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
</script>

@stop

