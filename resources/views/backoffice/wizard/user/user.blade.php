@extends('backoffice.wizard.user.setting_layout')
@section('setting_content')

<input type="hidden" id="id" value="-1"/>

<div class="container"> 
	<div class="row"> 
		<div class="col-sm-offset-1 col-md-12"> 
			<div class="panel panel-primary"> 
				<div class="panel-heading"> 
					<button type="button" class="btn btn-success btn-xs" style="float:right" onclick="location.href = '/backoffice/user/wizard/user/create'">
						<span class="glyphicon glyphicon-plus"></span>
						<b> Add New </b>
					</button>                             
					Users
				</div>                         
				<div class="panel-body"> 
					<table id="table_grid" class="table table-hover table-bordered" style="text-align: center"> 
						<thead> 
							<tr> 
								<th style="text-align: center">
									<input type="checkbox" id="checkall" />
								</th>
								<th style="text-align: center"><b>ID</b></th>
								<th style="text-align: center"><b>First Name</b></th>
								<th style="text-align: center"><b>Last Name</b></th>
								<th style="text-align: center"><b>Username</b></th>
								<th style="text-align: center"><b>Password</b></th>
								<th style="text-align: center"><b>IVR-Password</b></th>
								<th style="text-align: center"><b>Department</b></th>
								<th style="text-align: center"><b>Mobile</b></th>
								<th style="text-align: center"><b>Email</b></th>
								<th style="text-align: center"><b>Image</b></th>
								<th style="text-align: center"><b>Business Hours</b></th>
								<th style="text-align: center"><b>After Work</b></th>
								<th style="text-align: center">Edit</th>
								<th style="text-align: center">Delete</th>
							</tr>                                     
						</thead>                    
					</table>                             
				</div>
			</div>                     
			
			<div class="col-sm-offset-10">
				<button type="button" class="btn btn-success btn-sm" onclick="location.href = '/backoffice/user/wizard/pmgroup'"><b> Next </b>  
					<span class="glyphicon glyphicon-forward"></span>
				</button>
			</div>
								 
		</div>                 
	</div>   
</div>		
	
<script>

	var $grid = $('#table_grid').dataTable( {
		processing: true,
		serverSide: true,
		ajax: '/backoffice/user/wizard/usergrid/get',
		//"lengthMenu": [[1, 2, 5, -1], [1, 2, 5, "All"]],
		columns: [
			{ data: 'checkbox', orderable: false, searchable: false},
			{ data: 'id', name: 'cu.id'},
			{ data: 'first_name', name: 'cu.first_name' },
			{ data: 'last_name', name: 'cu.last_name'},
			{ data: 'username', name: 'cu.username' },
			{ data: 'password', name: 'cu.password' },
			{ data: 'ivr_password', name: 'cu.ivr_password' },
			{ data: 'department', name: 'cd.department' },
			{ data: 'mobile', name: 'cu.mobile' },
			{ data: 'email', name: 'cu.email' },
			{ data: 'picture', name: 'cu.picture'},
			{ data: 'contact_pref_bus', name: 'cu.contact_pref_bus'},
			{ data: 'contact_pref_nbus', name: 'cu.contact_pref_nbus'},
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