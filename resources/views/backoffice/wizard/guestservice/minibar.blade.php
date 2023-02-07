@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ALARMS";
		
	$current_url = '/backoffice/guestservice/wizard/minibar';
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];	
?>

<div class="col-sm-offset-1 col-md-10">

	<div class="panel panel-primary">
		<div class="panel-heading">Minibar</div>
		<div class="panel-body">
			<br>
            <form class="form-horizontal">
				<div class="form-group">
					<label class="control-label col-xs-3" for="itemGroup">Item Group:</label>
					<div class="col-xs-3">
						<?php echo Form::select('rsg_id', $rsglist, $rsg_id, ['id' => 'rsg_id', 'class' => 'form-control', 'onchange' => 'onSelectRSG()']); ?>						
						<button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#myModaItmgrp">
                                Add
						</button>
					</div>
				</div>

				<br>
                        
				<div class="form-group">
					<label class="control-label col-xs-3" for="itemGroup">Item Group:</label>
					<div class="col-xs-4">
						<select multiple class="form-control" id="rsi_id">							
						</select>
						<button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#myModaItm">
							Add
						</button>
					</div>

				</div>    
				
			</form>
		</div>
	</div>
	<div class="bottom-button" style="clear:both;">
		<button type="submit" style="float: right;" class="btn btn-primary" onclick="location.href = '/backoffice/guestservice/wizard/hskp/create';">Next >></button>                        
		<button type="submit" style="float: right; margin-right:10px" class="btn btn-primary" onclick="location.href = '/backoffice/guestservice/wizard/task';">Previous</button>
	</div>
</div>	
	
	
<div class="modal fade" id="myModaItmgrp" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="myModalLabel">Item Group</h4>
			</div>
			<div class="modal-body">
				<fieldset class="form-group">
					<label class="control-label col-xs-3" for="exampleSelect2">Building</label>
					<div class="col-xs-4">
						<?php echo Form::select('build_id', $buildlist, '1', ['id' => 'build_id', 'class' => 'form-control']); ?>
					</div>												<br>
				</fieldset>
				<fieldset class="form-group">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">Item Group: </label>	
					<div class="col-xs-4">           
						<input type="text" class="form-control" id="item_group" placeholder="Item Group">
						<small class="text-muted">Enter Group Name</small>
					</div>
					<br>
				</fieldset>
				<fieldset class="form-group">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">SO# : </label>
					<div class="col-xs-2">
						<input type="text" class="form-control" id="sales_outlet" placeholder="SO">
						<small class="text-muted">Sales Outlet</small>
					</div>												
					<br>
				</fieldset>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary"  data-dismiss="modal" onClick="onAddRSG()">Save changes</button>
			</div>
		</div>
	</div>
</div>	

<div class="modal fade" id="myModaItm" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="myModalLabel">Item</h4>
			</div>
			<div class="modal-body">
				<fieldset class="form-group">
					<label class="control-label col-xs-3" for="exampleSelect2">Item : </label>
					<div class="col-xs-4">
						<input type="text" class="form-control" id="item_name" placeholder="Item">
						<small class="text-muted">Minibar Item</small>
					</div>												<br>
				</fieldset>
				<fieldset class="form-group" style="display:none">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">Price : </label>
					<div class="col-xs-3">
						<input type="email" class="form-control" id="exampleInputEmail1" placeholder="AED">
					</div>												<br>
				</fieldset>
				<fieldset class="form-group">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">Quantity : </label>
					<div class="col-xs-3">
						<input type="text" class="form-control" id="quality" placeholder="Qty">
						<small class="text-muted">Max Qty allowed</small>
					</div>												<br>
				</fieldset>
				<fieldset class="form-group">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">Price : </label>
					<div class="col-xs-3">
						<input type="text" class="form-control" id="charge" placeholder="AED">
					</div>												<br>
				</fieldset>
				<fieldset class="form-group">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">IVR Code : </label>
					<div class="col-xs-3">
						<input type="number" class="form-control" id="ivr_code" placeholder="Code">
					</div>												<br>
				</fieldset>
				<fieldset class="form-group">
					<label class="control-label col-xs-3"  for="exampleInputEmail1">PMS Code : </label>
					<div class="col-xs-3">
						<input type="number" class="form-control" id="pms_code" placeholder="Code">
					</div>												<br>
				</fieldset>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-primary" data-dismiss="modal" onClick="onAddRSItem()">Save changes</button>
			</div>
		</div>
	</div>
</div>


<script type="text/javascript">	
	onSelectRSG();
	
	function onSelectRSG()
	{
		var rsg_id = $('#rsg_id').val();
				
		$.ajax({
			url: "/backoffice/guestservice/wizard/minibargroup/list?rsg_id=" + rsg_id,
			success:function(data){
                // console.log(data[0]);
				// console.log(data[1]);
				
				var model = $('#rsi_id');
				model.empty();

				$.each(data, function(index, element) {				
					model.append("<option value='"+ element.id +"'>" + element.item_name + "</option>");
				});				
			
            },			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onAddRSG()
	{
		var build_id = $('#build_id').val();
		var rsg_name = $('#item_group').val();
		var sales_outlet = $('#sales_outlet').val();
		
		var data = {
			building_id: build_id,
			name: rsg_name,
			sales_outlet: sales_outlet
			};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/minibar/creategroup",
			data: data,
            success:function(data){
         		$('#item_group').val("");
				$('#sales_outlet').val("");
				
				var model = $('#rsg_id');
				model.append("<option value='"+ data.id +"'>" + data.name + "</option>");				
				
				$('#rsg_id').val(data.id);
				onSelectRSG();
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onAddRSItem()
	{
		var rsg_id = $('#rsg_id').val();
		var item_name = $('#item_name').val();
		var quality = $('#quality').val();
		var charge = $('#charge').val();
		var ivr_code = $('#ivr_code').val();
		var pms_code = $('#pms_code').val();
		
		var data = {
			room_service_group: rsg_id,
			item_name: item_name,
			max_quantity: quality,
			charge: charge,
			ivr_code: ivr_code,
			pms_code: pms_code
			};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/minibar/createlist",
			data: data,
            success:function(data){
         		$('#item_name').val("");
				$('#quality').val("");
				$('#charge').val("");
				$('#ivr_code').val("");
				$('#pms_code').val("");
				
				var model = $('#rsi_id');
				model.append("<option value='"+ data.id +"'>" + data.item_name + "</option>");				
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
</script>


	
@stop

