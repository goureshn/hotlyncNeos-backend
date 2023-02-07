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
					Section
				</div>                         
				<div class="panel-body"> 
					<table id="section_grid" class="table table-hover table-bordered" style="text-align: center"> 
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
									<b>Department</b>
								</th>

								<th style="text-align: center">
									<b>Manager</b>
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
				<button type="button" class="btn btn-success btn-sm" onclick="location.href = '/backoffice/call/wizard/admin'"><b> Next </b>  
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
				<h3 class="modal-title" style="color:#2691d9" id="addModalLabel">Add Time slab</h3> 
			</div>                         
			<div class="modal-body"> 
				<br> 
				<form class="form-horizontal"> 
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Department:</label>
						<div class="col-sm-5">
							<?php echo Form::select('dept_id', $department, '1', array('class'=>'form-control', 'id' => 'dept_id')); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="client">Section Name : </label>
						<div class="col-sm-5">
							<input type="text" class="form-control" id="call_section" placeholder="Section" value="">
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="description">Description : </label>
						<div class="col-sm-5">
							<textarea class="form-control" rows="3" id="description"></textarea>
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="Property">Manager:</label>
						<div class="col-sm-5">
							<?php echo Form::select('user_id', $users, '1', array('class'=>'form-control', 'id' => 'user_id')); ?>							
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

	var $grid = $('#section_grid').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/call/wizard/sectiongrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'cs.id' },
			{ data: 'section', name: 'cs.section' },
			{ data: 'department', name: 'cd.department' },
			{ data: 'username', name: 'cu.username' },
			{ data: 'description', name: 'cs.description' },
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
		
		$('#dept_id').val('1');				
		$('#call_section').val("");
		$("#description").val("");
		$("#user_id").val("1");
		
		if( id > 0 )	// Update
		{
			$('#createButton').hide();
			$('#updateButton').show();
			$('#addModalLabel').text("Update Section");
			
			$.ajax({
				url: "/backoffice/call/wizard/section/" + id,
				success:function(data){
					console.log(data);
					$('#dept_id').val(data.dept_id);
					$('#call_section').val(data.section);
					$('#description').val(data.description);
					$('#user_id').val(data.manager_id);					
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
		else
		{
			$('#addModalLabel').text("Add Section");
			$('#createButton').show();
			$('#updateButton').hide();
		}		
	}
	
	function onUpdateRow()
	{
		var id = $("#id").val();
		
		var deft_id = $("#dept_id").val();
		var manager_id = $("#user_id").val();
		var section = $("#call_section").val();
		var description = $("#description").val();
		
		var data = {
			id : id,
			dept_id: deft_id,
			manager_id: manager_id,
			section: section,
			description: description
			};

					
		if( id >= 0 )	// Update
		{
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/section/updatedata",
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
				url: "/backoffice/call/wizard/section/createdata",
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
				url: "/backoffice/call/wizard/section/" + id,
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
				url: "/backoffice/call/wizard/section/delete/" + id,
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