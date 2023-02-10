@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ALARMS";
		
	$current_url = '/backoffice/guestservice/wizard/task';
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];	
?>

	<div class="col-sm-offset-1 col-md-10">
		<div class="panel panel-primary">
			<div class="panel-heading">Task</div>
			<div class="panel-body">
				<br>
				<form class="form-horizontal">
					<div class="form-group">
						<label class="control-label col-xs-4" for="taskgroup">Task Group</label>
						
						<div class="col-xs-5">
							<?php echo Form::select('taskgroup_id', $taskgrouplist, $taskgroup_id, ['id' => 'taskgroup_id', 'class' => 'form-control', 'onchange' => 'onSelectTaskGroup()']); ?>
							
							<button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#taskgroupModal">
								Add
							</button>
							<small class="text-muted">Select a Task Group or click Add New.</small>
						</div>
					</div>
					<br>
					<div class="form-group">
						<label class="control-label col-xs-4" for="task">Task</label>
						<div class="col-xs-5">
							<select multiple class="form-control" id="tasklist">							
							</select>
							<!-- Button trigger modal -->
							<button type="button" class="btn btn-primary btn-xs" data-toggle="modal" data-target="#tasklistModal">
								Add
							</button>
							<!-- Modal -->
							<small class="text-muted">Select Task or Add New</small>
						</div>
						<br>
						<br>
					</div>                         
					<div class="col-sm-offset-10">
						<div class="form-group">
							<button type="button" class="btn btn-success btn-sm">
								<span class="glyphicon glyphicon-ok-sign"></span> Save
							</button>
							<button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">
								<span class="glyphicon glyphicon-remove"></span> Cancel
							</button>
						</div>
					</div>
				</form>
			</div>                 
		</div>
		<div class="bottom-button" style="clear:both;">
			<button type="submit" style="float: right;" class="btn btn-primary" onclick="location.href = '/backoffice/guestservice/wizard/minibar';">Next >></button>                        
			<button type="submit" style="float: right; margin-right:10px" class="btn btn-primary" onclick="location.href = '/backoffice/guestservice/wizard/escalation';">Previous</button>
		</div>
	</div>
	<div class="modal fade" id="taskgroupModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title" id="myModalLabel">Task Group</h4>
				</div>
				<div class="modal-body">
					<fieldset class="form-group">
						<label for="exampleSelect2">Select Department Function</label>
						<?php echo Form::select('deft_id', $deftlist, '1', ['id' => 'deft_id', 'class' => 'form-control']); ?>					
					</fieldset>
					<fieldset class="form-group">
						<label for="exampleInputEmail1">Task Group</label>
						<input type="text" class="form-control" id="taskgroupname" placeholder="Enter Task Name">
						<small class="text-muted">Enter Task Group Name</small>
					</fieldset>
					<div class="checkbox">
						<label>
							<input type="checkbox" id="escalation_flag"> Escalate
						</label>
					</div>
					<fieldset class="form-group">
						<label for="exampleInputEmail1">Select Escalation Group</label>
						<?php echo Form::select('escpgroup_id', $escplist, '1', ['id' => 'escpgroup_id', 'class' => 'form-control']); ?>						
					</fieldset>
					<fieldset class="form-group">
						<label for="exampleInputEmail1">Duration(mins) : </label>
						<input type="number" class="form-control" id="maxtime" placeholder="minutes">
					</fieldset>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" data-dismiss="modal" onClick="onAddTaskGroup()">Save changes</button>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="tasklistModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel1" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title" id="myModalLabel1">Create New Task</h4>
				</div>
				<div class="modal-body">
					<label for="exampleInputEmail1">Task : </label>
					<form id="defaultForm" method="post" class="form-horizontal" action="target.php">
						<div class="form-group">
							<input class="form-control" type="text" id="tasklist_name" name="textbox[]" placeholder="Task" />
							<button type="button" class="btn btn-primary btn-xs addButton" data-template="textbox">Add Another</button>
						</div>
						<div class="form-group hide" id="textboxTemplate">
							<input class="form-control" type="text" name="textbox[]" placeholder="Task" />
							<button type="button" class="btn btn-primary btn-xs removeButton">Remove</button>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" data-dismiss="modal" onClick="onAddTaskList()">Save changes</button>
				</div>
			</div>
		</div>
	</div>
	
<script type="text/javascript">	

	function onAddTaskGroup()
	{
		var deft_id = $('#deft_id').val();
		var escalation_id = $('#escpgroup_id').val();
		var taskgroup_name = $('#taskgroupname').val();
		var max_time = $('#maxtime').val();
		var escalation_flag = $('#escalation_flag').val();
		
		var flag = 'N';
		if( $('#escalation_flag').is(':checked') == true )
			flag = 'Y';
		
		var data = {
			dept_function: deft_id,
			escalation_group: escalation_id,
			name: taskgroup_name,
			max_time: max_time,
			escalation: flag
			};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/task/creategroup",
			data: data,
            success:function(data){
         		$('#taskgroupname').val("");
				$('#maxtime').val("");
				
				var model = $('#taskgroup_id');
				model.append("<option value='"+ data.id +"'>" + data.name + "</option>");				
				
				$('#taskgroup_id').val(data.id);
				onSelectTaskGroup();
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	onSelectTaskGroup();
	
	function onSelectTaskGroup()
	{
		var taskgroup_id = $('#taskgroup_id').val();
				
		$.ajax({
			url: "/backoffice/guestservice/wizard/taskgroup/list?taskgroup_id=" + taskgroup_id,
			success:function(data){
                // console.log(data[0]);
				// console.log(data[1]);
				
				var model = $('#tasklist');
				model.empty();

				$.each(data, function(index, element) {				
					model.append("<option value='"+ element.id +"'>" + element.task + "</option>");
				});				
			
            },			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onAddTaskList()
	{
		var taskgroup_id = $('#taskgroup_id').val();
		var tasklist_name = $('#tasklist_name').val();
		
		var data = {
			taskgroup_id: taskgroup_id,
			tasklist_name: tasklist_name,
			};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/task/createlist",
			data: data,
            success:function(data){
         		$('#tasklist_name').val("");
				
				var model = $('#tasklist');
				model.append("<option value='"+ data.id +"'>" + data.task + "</option>");				
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
</script>

@stop
