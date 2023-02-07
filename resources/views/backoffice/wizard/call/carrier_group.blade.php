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
					</button>          Carrier Group
				</div>
				<div class="panel-body">
					<table id="carriergroup_grid" class="table table-hover table-bordered" style="text-align: center">
						<thead>
							<tr>
								<th class="no-sort" style="text-align: center">
									<input type="checkbox" id="checkall" />
								</th>
								<th style="text-align: center"><b>ID</b></th>
								<th style="text-align: center"><b>Carrier Group</b></th>
								<th style="text-align: center"><b>Carrier</b></th>
								<th style="text-align: center"><b>Call Type</b></th>
								<th class="no-sort" style="text-align: center">Edit</th>
								<th class="no-sort" style="text-align: center">Delete</th>
							</tr>
						</thead>                              
					</table>
				</div>
			</div>
			
			<div class="col-sm-offset-10">
				<button type="button" class="btn btn-warning btn-sm" onclick="location.href = '/backoffice/call/wizard/dest'"> 
					<span class="glyphicon glyphicon-backward"></span><b> Back </b>
				</button>
					<button type="button" class="btn btn-success btn-sm" onclick="location.href = '/backoffice/call/wizard/carriercharge'"><b> Next </b>  
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
				<h3 class="modal-title" style="color:#2691d9" id="addModalLabel">Add Carrier Group</h3>
			</div>
			<div class="modal-body">
				<br>
				<form class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-sm-4" for="client">Group Name : </label>
						<div class="col-sm-5">
							<input type="text" class="form-control" id="name" placeholder="Group">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Carrier : </label>
						<div class="col-sm-5">
							<?php echo Form::select('carrier_id', $carrier, '1', array('class'=>'form-control', 'id' => 'carrier_id')); ?>                                  
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Call Type : </label>
						<div class="col-sm-5">
							<?php echo Form::select('calltype_id', $carrier_group->getTypeList(), '1', array('class'=>'form-control', 'id' => 'calltype_id')); ?>                                    
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
				<h3 class="modal-title" style="color:#2691d9" id="Heading">Delete Destination</h3>
			</div>
			<br>
			<div class="modal-body">
				<div class="alert alert-danger">
					<span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete <span id="delete_item">{Destination}</span>
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

	var $grid = $('#carriergroup_grid').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/call/wizard/carriergroupgrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'ccg.id' },
			{ data: 'name', name: 'ccg.name' },
			{ data: 'carrier', name: 'cr.carrier' },
			{ data: 'call_type', name: 'ccg.call_type' },		
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
		
		$('#name').val("");
		$('#carrier_id').val('1');				
		$('#calltype_id').val("1");
		
		if( id > 0 )	// Update
		{
			$('#createButton').hide();
			$('#updateButton').show();
			$('#addModalLabel').text("Update Carrier Group");
			
			$.ajax({
				url: "/backoffice/call/wizard/carriergroup/" + id,
				success:function(data){
					console.log(data);
					$('#name').val(data.name);
					$('#carrier_id').val(data.carrier_id);				
					$('#calltype_id').val(data.calltype_id);								
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		else
		{
			$('#addModalLabel').text("Add Carrier Group");
			$('#createButton').show();
			$('#updateButton').hide();
		}		
	}
	
	function onUpdateRow()
	{
		var id = $("#id").val();
		
		var name = $("#name").val();
		var carrier_id = $("#carrier_id").val();
		var calltype = $("#calltype_id option:selected").text();
			
		var data = {
			id : id,
			name: name,
			carrier_id: carrier_id,							
			call_type: calltype
		};
				
		if( id >= 0 )	// Update
		{
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/carriergroup/updatedata",
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
				url: "/backoffice/call/wizard/carriergroup/createdata",
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
				url: "/backoffice/call/wizard/carriergroup/" + id,
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
				url: "/backoffice/call/wizard/carriergroup/delete/" + id,
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
