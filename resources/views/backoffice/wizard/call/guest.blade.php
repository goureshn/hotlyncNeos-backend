@extends('backoffice.wizard.call.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ADMIN";
		
	$current_url = '/backoffice/guestservice/wizard/alarm';
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];	
?>
<div class="container">
	<div class="row">
		<div class="col-sm-offset-1 col-md-10">
			<div class="panel panel-primary">
				<div class="panel-heading">
					<button type="button" class="btn btn-success btn-xs" style="float:right" data-toggle="modal" data-target="#addModal" onClick="onShowAddRow()">
					   <span class="glyphicon glyphicon-plus"></span><b> Add New </b>
					</button>
					Guest Extension
				</div>
				<div class="panel-body">
					<table id="guestextn" class="table table-hover table-bordered" style="text-align: center">
						<thead>
							<tr>
								<th class="no-sort" style="text-align: center">
									<input type="checkbox" id="checkall" />
								</th>
								<th style="text-align: center">
									<b>ID</b>
								</th>
								<th style="text-align: center">
									<b>Building</b>
								 </th>   

								<th style="text-align: center">
									<b>Room</b>
								 </th>   
								<th style="text-align: center">
									<b>Extension</b>
								</th> 
								<th style="text-align: center">Edit</th>
								<th style="text-align: center">Delete</th>
							</tr>
						</thead>						
					</table>
				</div>
			</div>
				<div class="col-sm-offset-10">
					<button type="button" class="btn btn-warning btn-sm" onclick="location.href = '/backoffice/call/wizard/admin'"> 
						<span class="glyphicon glyphicon-backward"></span><b> Back </b>
					</button>
					<button type="button" class="btn btn-success btn-sm" onclick="location.href = '/backoffice/call/wizard/carrier'"><b> Next </b>  
						<span class="glyphicon glyphicon-forward"></span>
					</button>
				</div>
		</div>
	</div>
</div>
       

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<input type="hidden" id="id" value="-1"/>
	<input type="hidden" id="roomselect_id" value="-1"/>
	<div class="modal-dialog" role="document">

		<div class="modal-content">
		
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h3 class="modal-title" style="color:#2691d9" id="myModalLabel">Add Guest Extension</h3>
			</div>
			<div class="modal-body"> 
				<button type="button" style="float:right" class="btn btn-success" data-title="Multi Upload">
					<i class="fa fa-upload" aria-hidden="true"></i>
				</button>
				<br>
				<form class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-sm-4" for="building_id">Building : </label>
						<div class="col-sm-5">
							<?php echo Form::select('building_id', $building, '1', array('class'=>'form-control', 'id' => 'build_id', 'onchange' => 'onSelectBuild()')); ?>							
						</div>
					</div>
					<div class="form-group">
					
						<label class="control-label col-sm-4" for="room_id">Room : </label>
						<div class="col-sm-5">
							<select class="form-control" id="room_id">								
							</select>
						</div>
					</div>
					
					<div class="form-group">
						<label class="control-label col-sm-4" for="pextn">Primary Extn : </label>
						<div class="col-sm-5">
							<input type="text" class="form-control" id="pextn">
							<button type="button" id="addextension" class="btn btn-success btn-xs" style="float:right">
								<span class="glyphicon glyphicon-plus"></span>
							</button>
						</div>						
					</div>
						
					<div class="form-group">
						<label class="control-label col-sm-4" for="pextn">Description : </label>
						<div class="col-sm-5">
							<textarea class="form-control" id="pextndesc"  rows="5">
							</textarea>
						</div>
					</div>
				</form>
			</div>
			
			<div class="modal-footer ">
				<button type="button" class="btn btn-success btn-sm" data-dismiss="modal" onClick="onUpdateRow()">
					<span class="glyphicon glyphicon-ok"></span><b> Save</b>
				</button>
				<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">
					<span class="glyphicon glyphicon-remove"></span><b> Cancel
					</b>
				</button>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
				</button>
				<h3 class="modal-title" style="color:#2691d9" id="Heading">Edit Guest Extension</h3>
			</div>
			<br>
			<div class="modal-body">
				<br>
				<form class="form-horizontal">
				<div class="form-group">
						<label class="control-label col-sm-4" for="building_id_edit">Building : </label>
						<div class="col-sm-5">
							<?php echo Form::select('building_id', $building, '1', array('class'=>'form-control', 'id' => 'buildupdate_id', 'onchange' => 'onSelectBuildUpdate()' )); ?>							
						</div>
					</div>
					<div class="form-group">
						<label class="control-label col-sm-4" for="room_id_edit">Room:</label>
						<div class="col-sm-5">
							<select class="form-control" id="roomupdate_id">								
							</select>
						</div>
					</div>
				   
					<div class="form-group">
						<label class="control-label col-sm-4" for="client"> Primary Extension: </label>
						<div class="col-sm-5">
							<input type="text" class="form-control" id="pextnupdate">
						</div>
					</div>
					
					<div class="form-group">
						<label class="control-label col-sm-4" for="pextn">Description : </label>
						<div class="col-sm-5">
							<textarea class="form-control" id="pextndescupdate"  rows="5">
							</textarea>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer ">
				<button type="button" class="btn btn-warning btn-lg" style="width: 100%;"  data-dismiss="modal" onClick="onUpdateRow()">
					<span class="glyphicon glyphicon-ok-sign"></span> Update
				</button>
			</div>
		</div>
		<!-- /.modal-content -->                 
	</div>
	<!-- /.modal-dialog -->             
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
					<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
				</button>
				<h3 class="modal-title" style="color:#2691d9" id="Heading">Delete Guest Extension</h3>
			</div>
			<br>
			<div class="modal-body">
				<div class="alert alert-danger">
					<span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete <span id="delete_item">{Guest Extension}</span>
					?                   
				</div>
			</div>
			<div class="modal-footer ">
				<button type="button" class="btn btn-danger btn-sm data-dismiss="modal" onClick="deleteRow()">
					<span class="glyphicon glyphicon-ok"></span><b> Yes</b>
				</button>
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">
					<span class="glyphicon glyphicon-remove"></span><b> No</b>
				</button>
			</div>
		</div>
		<!-- /.modal-content -->                 
	</div>
	<!-- /.modal-dialog -->             
</div>

<script> 
	
	var $grid = $('#guestextn').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/call/wizard/guestgrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'ge.id' },
			{ data: 'name', name: 'b.name' },
			{ data: 'room', name: 'r.room' },
			{ data: 'extension', name: 'ge.extension' },			
			{ data: 'edit', orderable: false, searchable: false},
			{ data: 'delete', orderable: false, searchable: false}
		]
	});	

	function onShowAddRow()
	{
		$("#id").val(-1);
		onSelectBuild();
	}
	
	function onShowEditRow(id)
	{		
		$("#id").val(id);
		
		$.ajax({
			url: "/backoffice/call/wizard/guest/" + id,
			success:function(data){
				console.log(data);
				$('#buildupdate_id').val(data.bldg_id);
				$('#roomselect_id').val(data.room_id);				
				$('#pextnupdate').val(data.extension);				
				$('#pextndescupdate').val(data.description);	

				onSelectBuildUpdate();				
			},			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
		});	
	}
	
	function onSelectBuild()
	{
		var build_id = $("#build_id").val();
		
		$.ajax({
			url: "/room/list?build_id=" + build_id,
			success:function(data){
				console.log(data);
				var model = $('#room_id');
				model.empty();

				$.each(data, function(index, element) {				
					model.append("<option value='"+ element.id +"'>" + element.room + "</option>");
				});
			},			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
		});	
	}
	
	function onSelectBuildUpdate()
	{
		var build_id = $("#buildupdate_id").val();
		
		$.ajax({
			url: "/room/list?build_id=" + build_id,
			success:function(data){
				console.log(data);
				var model = $('#roomupdate_id');
				model.empty();

				$.each(data, function(index, element) {				
					model.append("<option value='"+ element.id +"'>" + element.room + "</option>");
				});
				$('#roomupdate_id').val($('#roomselect_id').val());
			},			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
		});	
	}
	
	function onUpdateRow()
	{
		var id = $("#id").val();
		
		if( id >= 0 )	// Update
		{
			var build_id = $("#buildupdate_id").val();
			var room_id = $("#roomupdate_id").val();
			var extension = $("#pextnupdate").val();
			var description = $("#pextndescupdate").val();
			
			var data = {
				id : id,
				bldg_id: build_id,
				room_id: room_id,				
				extension: extension,
				description: description
				};
				
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/guest/updatedata",
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
			var build_id = $("#build_id").val();
			var room_id = $("#room_id").val();
			var extension = $("#pextn").val();
			var description = $("#pextndesc").val();
			
			var data = {
				bldg_id: build_id,
				room_id: room_id,
				primary_extn: 'Y',
				extension: extension,
				description: description
				};
			
			$.ajax({
				type: "POST",
				url: "/backoffice/call/wizard/guest/createdata",
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
	
	function onDeleteRow(id)
	{
		$("#id").val(id);
		
		if( id >= 0 )
		{
			$.ajax({
				url: "/backoffice/call/wizard/guest/" + id,
				success:function(data){
					$("#delete_item").text("\"" + data.extension + "\"");		
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
				url: "/backoffice/call/wizard/guest/delete/" + id,
				success:function(data){
					$grid.fnDraw();			
				},			
				error:function(request,status,error){
					//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
				}
			});	
		}
	}

	$(document).on('click', '.btn-add', function(e)
		{
			e.preventDefault();

			var controlForm = $('.controls form:first'),
				currentEntry = $(this).parents('.entry:first'),
				newEntry = $(currentEntry.clone()).appendTo(controlForm);

			newEntry.find('input').val('');
			controlForm.find('.entry:not(:last) .btn-add')
				.removeClass('btn-add').addClass('btn-remove')
				.removeClass('btn-success').addClass('btn-danger')
				.html('<span class="glyphicon glyphicon-minus"></span>');
		}).on('click', '.btn-remove', function(e)
		{
			$(this).parents('.entry:first').remove();

			e.preventDefault();
			return false;
		});	

</script>          
						
@stop

