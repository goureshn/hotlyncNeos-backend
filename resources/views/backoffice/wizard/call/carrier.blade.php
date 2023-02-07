@extends('backoffice.wizard.call.setting_layout')
@section('setting_content')

<div class="container">
	<div class="row">
		<div class="col-sm-offset-1 col-md-10">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<button type="button" class="btn btn-success btn-xs" style="float:right" data-toggle="modal" data-target="#addModal" onClick="onShowEditRow(-1)">
						<span class="glyphicon glyphicon-plus"></span>
						<b> Add New </b>
					</button>
					Carrier
				</div>
				<div class="panel-body">
					<table id="carriergrid" class="table table-hover table-bordered" style="text-align: center">
						<thead>
							<tr>
								<th no-sort style="text-align: center">
									<input type="checkbox" id="checkall" />
								</th>
								<th style="text-align: center"><b>ID</b></th>
								<th style="text-align: center"><b>Carrier</b></th>
								<th style="text-align: center"><b>Property</b></th>
								<th style="text-align: center"><b>Description</b></th>
								<th style="text-align: center">Edit</th>
								<th style="text-align: center">Delete</th>
							</tr>
						</thead>                               
					</table>
				</div>
			</div>
			
			<div class="col-sm-offset-10">
				<button type="button" class="btn btn-warning btn-sm"  onclick="location.href = '/backoffice/call/wizard/guest'"> 
					<span class="glyphicon glyphicon-backward"></span><b> Back </b>
				</button>
					<button type="button" class="btn btn-success btn-sm"  onclick="location.href = '/backoffice/call/wizard/dest'"><b> Next </b>  
					<span class="glyphicon glyphicon-forward"></span>
				</button>
			</div>
		</div>
	</div>
</div>


<!-- Modal Dialog -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<input type="hidden" id="id" value="-1"/>	
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h3 class="modal-title" style="color:#2691d9" id="addModalLabel">Add Carrier</h3>
			</div>
			<div class="modal-body">
				<br>
				<form class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-sm-4" for="client">Carrier : </label>
						<div class="col-sm-5">
							<input type="text" class="form-control" id="carrier_name" placeholder="Carrier">
						</div>	
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Property : </label>
						<div class="col-sm-5">
							<?php echo Form::select('property_id', $property, '1', array('class'=>'form-control', 'id' => 'property_id', 'onchange' => 'onSelectProperty()')); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="description">Description : </label>
						<div class="col-sm-5">
							<textarea class="form-control" rows="3" id="description"></textarea>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer " id="createButton">
				<button type="button" class="btn btn-success btn-sm" data-dismiss="modal" onClick="onUpdateRow()">
					<span class="glyphicon glyphicon-ok"></span>
					<b> Save</b>
				</button>
				<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">
					<span class="glyphicon glyphicon-remove"></span>
					<b> Cancel</b>
				</button>
			</div>
			
			<div class="modal-footer " id="updateButton">
				<button type="button" class="btn btn-warning btn-lg" style="width: 100%;"  data-dismiss="modal" onClick="onUpdateRow()">
					<span class="glyphicon glyphicon-ok-sign"></span> Update
				</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
				</button>
				<h3 class="modal-title" style="color:#2691d9" id="Heading">Delete Carrier</h3>
			</div>
			<br>
			<div class="modal-body">
				<div class="alert alert-danger">
					<span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete <span id="delete_item">{Carrier}</span>
					?                   
				</div>
			</div>
			<div class="modal-footer ">
				<div class="modal-footer ">
					<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal" onClick="deleteRow()">
						<span class="glyphicon glyphicon-ok"></span>
						<b> Yes</b>
					</button>
					<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
						<span class="glyphicon glyphicon-remove"></span>
						<b> No</b>
					</button>
				</div>
			</div>
			<!-- /.modal-content -->                     
		</div>
		<!-- /.modal-dialog -->                 
	</div>
</div>	



<script> 
	var $grid = $('#carriergrid').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/call/wizard/carriergrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'cr.id' },
			{ data: 'carrier', name: 'cr.carrier' },
			{ data: 'name', name: 'cp.name' },
			{ data: 'description', name: 'cr.description' },			
			{ data: 'edit', orderable: false, searchable: false},
			{ data: 'delete', orderable: false, searchable: false}
		]
	});	

	function onShowEditRow(id)
	{	
		$("#id").val(id);
		
		$('#carrier_name').val("");
		$('#property_id').val(1);				
		$('#description').val("");
					
		if( id > 0 )	// Update
		{
			$('#createButton').hide();
			$('#updateButton').show();
			$('#addModalLabel').text("Update Carrier");
			
			$.ajax({
				url: "/backoffice/call/wizard/carrier/" + id,
				success:function(data){
					console.log(data);
					$('#carrier_name').val(data.carrier);
					$('#property_id').val(data.prpty_id);				
					$('#description').val(data.description);								
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		else
		{
			$('#addModalLabel').text("Add Carrier");
			$('#createButton').show();
			$('#updateButton').hide();
		}		
	}
	
	function onUpdateRow()
	{
		var id = $("#id").val();
		
		var carrier = $("#carrier_name").val();
		var property_id = $("#property_id").val();
		var description = $("#description").val();
			
		var data = {
			id : id,
			carrier: carrier,
			prpty_id: property_id,				
			description: description
		};
				
		if( id >= 0 )	// Update
		{
			
				
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/carrier/updatedata",
				data: data,
				success:function(data){
					console.log(data);
					$grid.fnDraw();				
				},			
				error:function(request,status,error){
					alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		else			// Add
		{	
			
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/carrier/createdata",
				data: data,
				success:function(data){
					$grid.fnDraw();
				},			
				error:function(request,status,error){
					alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
	}	
	
	function onSelectProperty()
	{
		
	}
	
	function onDeleteRow(id)
	{
		$("#id").val(id);
		
		if( id >= 0 )
		{
			$.ajax({
				url: "/backoffice/call/wizard/carrier/" + id,
				success:function(data){
					$("#delete_item").text("\"" + data.carrier + "\"");		
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		
	}
	
	function  deleteRow()
	{
		var id  = $("#id").val();
		
		if( id >= 0 )
		{
			$.ajax({
				url: "/backoffice/call/wizard/carrier/delete/" + id,
				success:function(data){
					$grid.fnDraw();			
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
	}

	
</script>
        
@stop

