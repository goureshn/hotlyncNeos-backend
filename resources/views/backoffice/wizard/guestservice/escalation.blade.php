@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "Escalation";
		
	$current_url = '/backoffice/guestservice/wizard/escalation';
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
					<label for="deft_func" class="cm-required">Department Function:</label>
					<?php echo Form::select('dept_func', $deftlist, $deft_id, ['style' => 'width:auto', 'onchange' => 'onSelectDepartment()', 'id' => 'deft_id']); ?>						
				</div>					
				<div class="form-field">
					<label for="esgroup_name">Group Name:</label>
					<input type="text" id="group_name" name="name" class="input-text" size="20" maxlength="30" value="" />
					<span class="send-button cm-process-items" style="float:left;margin-left:10px;margin-top:1px;">						
						<input type="submit" class="arrow-button" value="Add" onclick="onAddGroup()" />
					</span>
				</div>							
			</fieldset>
		</div>
		
			<div id="content_general" style="margin-top:20px;clear:left">
				<fieldset>
					<div class="form-field">
						<label for="floor" class="cm-required">Escalation Group:</label>
						<?php echo Form::select('', array(), '0', ['style' => 'width:auto', 'id' => 'esgroup_id', 'onchange' => 'onSelectEsGroup()']); ?>						
					</div>										
					
					<div class="form-field">
						<label for="esgroup_name">Default Time(mins):</label>
						<input type="number" id="default_time" name="default_time" class="input-text" size="20" value="360" />						
						<span class="send-button cm-process-items" style="float:left;margin-left:35%;">						
							<input type="submit" class="arrow-button" value="Add Custom Time" onclick="onAddCustomTime()" />
						</span>
					</div>			
					
					<!-- bootstrap-->
					<style scoped>
						@import "/css/multimove.css";
					</style>
					<script src="/js/multiselect.js"></script>
					<script src="/js/jquery-sortable.js"></script>
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
								<?php echo Form::select('to[]', array(), '0', ['class' => 'form-control', 'id' => 'search_to', 'size' => '8', 'onchange' => 'onChangeMember()', 'multiple' => 'multiple']); ?>
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
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/location';" value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/task'"   value="  Next  " />
	</span>						
</div>
<script type="text/javascript">
	var excelupload = {
        url: "/backoffice/guestservice/wizard/escalation/upload",
        dragDrop: false,
        fileName: "myfile",
        multiple: false,
        showCancel: false,
        showAbort: false,
        showDone: false,
        showDelete: false,
        showError: true,
        showStatusAfterSuccess: false,
        showStatusAfterError: false,
        showFileCounter: false,
        allowedTypes: "csv,xlsx,xls",
        maxFileSize: 5120000,
        returnType: "text",
        onSuccess: function(files, data, xhr)
        {
            location.reload();			
        },
        deleteCallback: function(data, pd)
        {   
			console.log(data);        
        }
    }
	
	$(".excel_upload").uploadFile(excelupload);
	
	function onSelectDepartment()
	{
		var deft_id = $('#deft_id').val();
			
		$.ajax({
            url:"/dropdown/deft?deft_id=" + deft_id,
            success:function(data){
        		var model = $('#esgroup_id');
				var count = 0;
				model.empty();

				$.each(data, function(index, element) {									
					model.append("<option value='"+ element.id +"'>" + element.name + "</option>");
					if( count == 0 )
					{
						$('#esgroup_id').val(element.id);
						onSelectEsGroup();
					}
					count++;	
				});
				
				if( count < 1 )
				{
					$('#search').empty();
					$('#search_to').empty();
				}
            }
        });	
	}
	
	onSelectDepartment();
	var maxtime = new Object();
	
	function onAddGroup()
	{
		var group_name = $('#group_name').val();		
		var deft_id = $('#deft_id').val();
		
		var data = {dept_func: deft_id, name: group_name};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/escalation/group_create",
			data: data,
            success:function(data){
                console.log(data);
				$('#group_name').val("");
				var model = $('#esgroup_id');
				model.append("<option value='"+ data.id +"'>" + data.name + "</option>");				
				
				$('#esgroup_id').val(data.id);
				onSelectEsGroup();
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onSelectEsGroup()
	{
		var esgroup_id = $('#esgroup_id').val();
		
		var data = {esgroup_id: esgroup_id};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/escalation/selectesgroup",
			data: data,
            success:function(data){
                console.log(data[0]);
				console.log(data[1]);
				
				var from = $('#search');
				from.empty();

				$.each(data[0], function(index, element) {				
					from.append("<option value='"+ element.id +"'>" + element.username + "</option>");
				});
				
				var to = $('#search_to');
				to.empty();
				var count = 1;
				$.each(data[1], function(index, element) {				
					to.append("<option value='"+ element.id +"'>" + element.username + "(Level " + count + ")" + "</option>");
					count++;
				});
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onChangeMember(val) {
		console.log(val);
	}
	
	
	$('#search').multiselect({
		search: {
			left: '<input type="text" name="q" class="form-control" placeholder="Add Member..." />',
			right: '<input type="text" name="q" class="form-control" placeholder="Selected Member..." />',
		},
		attatch : false, 
		afterMoveToRight: function($left, $right, $options) { 
			rearrangeSelectList();
		},
		afterMoveToLeft: function($left, $right, $options) { 
			rearrangeSelectList();
		},
		beforeMoveToRight : function($left, $right, $options) { 
			assignMaxTime();
			return true;
		}		
	});

	function rearrangeSelectList()
	{
		$("#search option").each(function()
		{
			var text = $(this).text();
			var index = text.indexOf("(Level ") ;
			if( index >= 0 )
				text = text.substring(0,index);
			$(this).text(text);						
		});
		
		var count = 1;
		$("#search_to option").each(function()
		{
			var text = $(this).text();
			var index = text.indexOf("(Level ") ;
			if( index >= 0 )
				text = text.substring(0,index);
			
			$(this).text(text + "(Level " + count + ")");
			
			count++;
		});
	}
	
	function onAddCustomTime()
	{
		var minute = window.prompt("Please input max time", "60");
		
		$("#search_to option:selected").each(function()
		{
			var id = $(this).val();
			maxtime[id] = minute;			
		});		
	}
	
	function assignMaxTime()
	{
		var defaultTime = $('#default_time').val();
		if( defaultTime < 1 )
			return;
		
		$("#search option:selected").each(function()
		{
			var id = $(this).val();
			maxtime[id] = defaultTime;						
		});
		
		console.log(maxtime);		
	}
	
	function onSubmit()
	{
		var esgroup_id = $('#esgroup_id').val();
		
		var select_id = new Object();
		var count = 0;
		$("#search_to option").each(function()
		{
			select_id[count] = $(this).val();
			count++;
		});
		
		var data = {esgroup_id: esgroup_id, select_id: select_id, max_time: maxtime};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/escalation/postescalation",
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

