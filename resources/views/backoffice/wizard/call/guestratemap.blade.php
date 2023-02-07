@extends('backoffice.wizard.call.setting_layout')
@section('setting_content')

<input type="hidden" id="id" value="-1"/>

<div class="container"> 
	<div class="row"> 
		<div class="col-sm-offset-1 col-md-10"> 
			<div class="panel panel-primary"> 
				<div class="panel-heading"> 
					<button type="button" class="btn btn-success btn-xs" style="float:right" data-toggle="modal" data-target="#addModal" onClick="onShowEditRow(-1)">
						<span class="glyphicon glyphicon-plus"></span>
						<b> Add New </b>
					</button>                             
					Guest Rate Map
				</div>                         
				<div class="panel-body"> 
					<table id="ratemap_grid" class="table table-hover table-bordered" style="text-align: center"> 
						<thead> 
							<tr> 
								<th class="no-sort" style="text-align: center">
									<input type="checkbox" id="checkall" />
								</th>
								<th style="text-align: center">
									<b>ID</b>
								</th>

								<th style="text-align: center">
									<b>Carrier Group</b>
								</th>

								<th style="text-align: center">
									<b>Rate Map Name</b>
								</th>
								
								<th style="text-align: center">
									<b>Carrier Charge</b>
								</th>	

								<th style="text-align: center">
									<b>Hotel Charge</b>
								</th>	
								
								<th style="text-align: center">
									<b>Tax</b>
								</th>	
								
								<th style="text-align: center">
									<b>Allowance</b>
								</th>									
								
								<th style="text-align: center">
									<b>Time Slab</b>
								</th>								
								
								<th class="no-sort" style="text-align: center">Edit</th>
								<th class="no-sort" style="text-align: center">Delete</th>
							</tr>                                     
						</thead>                    
					</table>                             
				</div>
			</div>                     
			
			<div class="col-sm-offset-10">
				<button type="button" class="btn btn-warning btn-sm" onclick="location.href = '/backoffice/call/wizard/adminrate'"> 
					<span class="glyphicon glyphicon-backward"></span><b> Back </b>
				</button>
					<button type="button" class="btn btn-success btn-sm" onclick="location.href = '/backoffice/call/wizard/guestrate'"><b> Next </b>  
					<span class="glyphicon glyphicon-forward"></span>
				</button>
			</div>
								 
		</div>                 
	</div>   
</div>		
	
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"> 
	<div class="modal-dialog" role="document"> 
		<div class="modal-content"> 
			<div class="modal-header"> 
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
					<span aria-hidden="true">&times;</span> 
				</button>                             
				<h3 class="modal-title" style="color:#2691d9" id="addModalLabel">Add Guest Rate Map</h3> 
			</div>                         
			<div class="modal-body"> 
				<br> 
				<form class="form-horizontal"> 
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Carrier Group: </label>
						<div class="col-sm-5">
							<?php echo Form::select('carriergroup_id', $carriergroup, '1', array('class'=>'form-control', 'id' => 'carriergroup_id')); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="client">Rate Map Name: </label>
						<div class="col-sm-5">
							<input type="text" class="form-control" id="name" placeholder="Name">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Carrier Charge: </label>
						<div class="col-sm-5">
							<?php echo Form::select('carriercharge_id', $carriercharge, '1', array('class'=>'form-control', 'id' => 'carriercharge_id')); ?>							
						</div>
					</div>	
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Hotel Charge: </label>
						<div class="col-sm-5">
							<?php echo Form::select('hotelcharge_id', $hotelcharge, '1', array('class'=>'form-control', 'id' => 'hotelcharge_id')); ?>							
						</div>
					</div>	
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Tax: </label>
						<div class="col-sm-5">
							<?php echo Form::select('tax_id', $tax, '1', array('class'=>'form-control', 'id' => 'tax_id')); ?>							
						</div>
					</div>	
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Carrier Charge: </label>
						<div class="col-sm-5">
							<?php echo Form::select('carriercharge_id', $carriercharge, '1', array('class'=>'form-control', 'id' => 'carriercharge_id')); ?>							
						</div>
					</div>	
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Allowance: </label>
						<div class="col-sm-5">
							<?php echo Form::select('allowance_id', $allowance, '1', array('class'=>'form-control', 'id' => 'allowance_id')); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Time Slab: </label>
						<div class="col-sm-5">
							<?php echo Form::select('timeslab_id', $timeslab, '1', array('class'=>'form-control', 'id' => 'timeslab_id')); ?>							
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
					<span class="glyphicon glyphicon-ok-sign"></span> Update
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
				<h3 class="modal-title" style="color:#2691d9" id="Heading">Delete Rate Map</h3>
			</div>
			<br>
			<div class="modal-body">
				<div class="alert alert-danger">
					<span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete <span id="delete_item">{Fetching...}</span>
					?                   
				</div>
			</div>
			<div class="modal-footer ">
				<div class="modal-footer ">
					<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal" onClick="deleteRow()">
						<span class="glyphicon glyphicon-ok"></span>
						<b> Yes</b>
					</button>
					<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
						<span class="glyphicon glyphicon-remove"></span>
						<b> No</b>
					</button>
				</div>
			</div>
			<!-- /.modal-content -->                     
		</div>
		<!-- /.modal-dialog -->                 
	</div>
</div>	

<script>

	var $grid = $('#ratemap_grid').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/call/wizard/guestrategrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'ccm.id' },
			{ data: 'cgname', name: 'cg.name' },
			{ data: 'name', name: 'ccm.name' },
			{ data: 'ccname', name: 'cc.charge' },
			{ data: 'hcname', name: 'hc.name' },
			{ data: 'taxname', name: 'tax.name' },
			{ data: 'caname', name: 'ca.Name' },
			{ data: 'tsname', name: 'ts.name' },
			{ data: 'edit', orderable: false, searchable: false},
			{ data: 'delete', orderable: false, searchable: false}
		]
	});	
	
	function refreshCurrentPage()
	{
		var oSettings = $grid.fnSettings();
		var page = Math.ceil(oSettings._iDisplayStart / oSettings._iDisplayLength);
		$grid.fnPageChange(page);
	}
	
	function onShowEditRow(id)
	{	
		$("#id").val(id);
		
		$('#carrier_id').val('1');				
		$('#name').val("");
		$('#value').val("");
		
		if( id > 0 )	// Update
		{
			$('#createButton').hide();
			$('#updateButton').show();
			$('#addModalLabel').text("Update Guest Rate Map");
			
			$.ajax({
				url: "/backoffice/call/wizard/guestrate/" + id,
				success:function(data){
					console.log(data);
					$('#carriergroup_id').val(data.group_id);				
					$('#name').val(data.name);
					$('#timeslab_id').val(data.time_slab);
					$('#carriercharge_id').val(data.carrier_charges);
					$('#allowance_id').val(data.call_allowance);
					$('#hotelcharge_id').val(data.hotel_charges);
					$('#tax_id').val(data.tax);
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		else
		{
			$('#addModalLabel').text("Add Guest Rate Map");
			$('#createButton').show();
			$('#updateButton').hide();
		}		
	}
	
	function onUpdateRow()
	{
		var id = $("#id").val();
		
		var carrier_id = $("#carrier_id").val();
		var name = $("#name").val();
		var value = $("#value").val();
			
		var data = {
			id : id,
			name: $("#name").val(),
			group_id: $('#carriergroup_id').val(),
			time_slab: $('#timeslab_id').val(),
			carrier_charges: $('#carriercharge_id').val(),	
			call_allowance: $('#allowance_id').val(),
			hotel_charges: $('#hotelcharge_id').val(),
			tax: $('#tax_id').val()
		};
				
		if( id >= 0 )	// Update
		{
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/guestrate/updatedata",
				data: data,
				success:function(data){
					// $grid.fnPageChange('next');																		
					refreshCurrentPage();
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
				url: "/backoffice/call/wizard/guestrate/createdata",
				data: data,
				success:function(data){
					// $grid.fnDraw();
					$grid.fnPageChange( 'last' );
				},			
				error:function(request,status,error){
					alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
	}	
	
	function onDeleteRow(id)
	{
		$("#id").val(id);
		
		if( id >= 0 )
		{
			$.ajax({
				url: "/backoffice/call/wizard/guestrate/" + id,
				success:function(data){
					$("#delete_item").text("\"" + data.name + "\"");		
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
				url: "/backoffice/call/wizard/guestrate/delete/" + id,
				success:function(data){					
					refreshCurrentPage();					
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
	}
			
</script>    


@stop	