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
					Admin Extension
				</div>                         
				<div class="panel-body"> 
					<table id="adminextn" class="table table-hover table-bordered" style="text-align: center"> 
						<thead> 
							<tr> 
								<th style="text-align: center">
									<input type="checkbox" id="checkall" />
								</th>
								<th style="text-align: center">
									<b>ID</b>
								</th>
								
								<th style="text-align: center">
									<b>Section</b>
								</th>

								<th style="text-align: center">
									<b>Extension</b>
								</th>

								<th style="text-align: center">
									<b>User</b>
								</th>
								<th style="text-align: center">
									<b>User Group</b>
								</th>
								<th style="text-align: center">
									<b>Description</b>
								</th>
								<th style="text-align: center">Edit</th>
								<th style="text-align: center">Delete</th>
							</tr>                                     
						</thead>                    
					</table>                             
				</div>
			</div>                     
			
			<div class="col-sm-offset-10">
				<button type="button" class="btn btn-warning btn-sm" onclick="location.href = '/backoffice/call/wizard/section'"> 
					<span class="glyphicon glyphicon-backward"></span><b> Back </b>
				</button>
				<button type="button" class="btn btn-success btn-sm" onclick="location.href = '/backoffice/call/wizard/guest'"><b> Next </b>  
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
				<h3 class="modal-title" style="color:#2691d9" id="addModalLabel">Add Admin Extension</h3> 
			</div>                         
			<div class="modal-body"> 
				<br> 
				<form class="form-horizontal"> 
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Section:</label>
						<div class="col-sm-5">
							<?php echo Form::select('section_id', $section, '1', array('class'=>'form-control', 'id' => 'section_id')); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="description">Extension : </label>
						<div class="col-sm-5">
							<textarea class="form-control" rows="3" id="extension"></textarea>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">User:</label>
						<div class="col-sm-5">
							<?php echo Form::select('user_id', $users, '1', array('class'=>'form-control', 'id' => 'user_id')); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">User Group:</label>
						<div class="col-sm-5">
							<?php echo Form::select('usergroup_id', $usergroup, '1', array('class'=>'form-control', 'id' => 'usergroup_id')); ?>							
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
				<h3 class="modal-title" style="color:#2691d9" id="Heading">Delete Time Slab</h3>
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
	
	var $grid = $('#adminextn').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/call/wizard/admingrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'cse.id' },
			{ data: 'section', name: 'cs.section' },
			{ data: 'extension', name: 'cse.extension' },
			{ data: 'username', name: 'cu.username' },
			{ data: 'name', name: 'cug.name' },
			{ data: 'description', name: 'cse.description' },
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
		
		$('#user_id').val("1");
		$('#usergroup_id').val("1");
		$('#section_id').val("1");
		$("#description").val("");
		$("#extension").val("");
		
		if( id > 0 )	// Update
		{
			$('#createButton').hide();
			$('#updateButton').show();
			$('#addModalLabel').text("Update Admin Extension");
			
			$.ajax({
				url: "/backoffice/call/wizard/admin/" + id,
				success:function(data){
					console.log(data);
					$('#user_id').val(data.user_id);
					$('#usergroup_id').val(data.user_group_id);
					$('#section_id').val(data.section_id);
					$("#description").val(data.description);
					$("#extension").val(data.extension);
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		else
		{
			$('#addModalLabel').text("Add Admin Extension");
			$('#createButton').show();
			$('#updateButton').hide();
		}		
	}
	
	function onUpdateRow()
	{
		var id = $("#id").val();
		
		var data = {
			id : id,
			user_id: $('#user_id').val(),
			user_group_id: $('#usergroup_id').val(),
			section_id: $("#section_id").val(),
			description: $("#description").val(),
			extension: $("#extension").val()
			};

					
		if( id >= 0 )	// Update
		{
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/admin/updatedata",
				data: data,
				success:function(data){
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
				url: "/backoffice/call/wizard/admin/createdata",
				data: data,
				success:function(data){
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
				url: "/backoffice/call/wizard/admin/" + id,
				success:function(data){
					$("#delete_item").text("\"" + data.section + "\"");		
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
				url: "/backoffice/call/wizard/admin/delete/" + id,
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

