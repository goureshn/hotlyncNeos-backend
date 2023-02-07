@extends('backoffice.wizard.guestservice.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "LOACTION GROUP";
		
	$current_url = '/backoffice/guestservice/wizard/location';
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
					<label for="property" class="cm-required">Client:</label>
					<?php echo Form::select('client_id', $clientlist, $client_id, ['style' => 'width:auto', 'id' => 'client_id', 'onchange' => 'onSelectClient()']); ?>						
				</div>					
				<div class="form-field">
					<label for="name">Name:</label>
					<input type="text" id="name" name="name" class="input-text" size="20" maxlength="30" value="" />
				</div>							
				<div class="form-field">
					<label for="description">Description:</label>
					<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="" />
					<span class="send-button cm-process-items" style="float:left;margin-left:10px;margin-top:0px;">						
						<input type="submit" class="arrow-button" value="Add" onclick="onAddLocationGroup()" />
					</span>
				</div>							
			</fieldset>
		</div>
		
		<div id="content_general" style="margin-top:20px;clear:left">
			<fieldset>
				<div class="form-field">
					<label for="location_group" class="cm-required">Location Group:</label>
					<?php echo Form::select('ltgroup_id', array(), '0', ['style' => 'width:auto', 'id' => 'ltgroup_id', 'onchange' => 'onSelectLocationGroup()']); ?>						
				</div>										
				<div class="form-field">
					<label for="type" class="cm-required">Type:</label>
					<?php echo Form::select('type_id', $ltgroupmember->getTypeList(), '0', ['style' => 'width:auto', 'onchange' => 'onSelectType()', 'id' => 'type_id']); ?>						
				</div>					
				
				<!-- bootstrap-->
				<script src="/js/multiselect.js"></script>
				<script src="/js/jquery-sortable.js"></script>
				<style scoped>
					@import "/css/multimove.css";
				</style>
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
							<?php echo Form::select('to[]', array(), '0', ['class' => 'form-control', 'id' => 'search_to', 'size' => '8', 'multiple' => 'multiple']); ?>
						</div>
					</div>	
				</div>
				
				
				<div class="form-field">
					<label for="excelfile">Upload .csv file:</label>
					<div class="form_item" style="margin-top:0px">							
						<input type="text" class="file" style="float:left" id="excelupload" value="" READONLY />
						<div class="excel_upload" >Upload</div>							
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
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/departfunc/create';" value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/guestservice/wizard/escalation';"   value="  Next  " />
	</span>						
</div>
<script type="text/javascript">
	var excelupload = {
        url: "/backoffice/guestservice/wizard/location/upload",
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
	
	onSelectClient();
		
	function onSelectClient()
	{
		var client_id = $('#client_id').val();
			
		$.ajax({
            url: "/backoffice/guestservice/wizard/locationgroup/list?client_id=" + client_id,
            success:function(data){
        		var model = $('#ltgroup_id');
				var count = 0;
				model.empty();

				$.each(data, function(index, element) {									
					model.append("<option value='"+ element.id +"'>" + element.name + "</option>");
					if( count == 0 )
					{
						$('#ltgroup_id').val(element.id);
						onSelectLocationGroup();
					}
					count++;	
				});
            }
        });	
	}
	function onAddLocationGroup()
	{
		var client_id = $('#client_id').val();
		var name = $('#name').val();		
		var description = $('#description').val();
		
		var data = {
			client_id: client_id,
			name: name,
			description: description
			};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/location/creategroup",
			data: data,
            success:function(data){
                console.log(data);
				$('#name').val("");
				$('#description').val("");
				
				var model = $('#ltgroup_id');
				model.append("<option value='"+ data.id +"'>" + data.name + "</option>");				
				
				$('#ltgroup_id').val(data.id);
				onSelectLocationGroup();
            },			
			error:function(request,status,error){
				alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	function onSelectLocationGroup()
	{
		getLocationList();
	}
	
	function onSelectType()
	{
		getLocationList();		
	}
	
	function getLocationList()
	{
		var ltgroup_id = $('#ltgroup_id').val();
		var type_id = $('#type_id').val();
		
		var data = {ltgroup_id: ltgroup_id, type_id: type_id};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/location/list",
			data: data,
            success:function(data){
                // console.log(data[0]);
				// console.log(data[1]);
				
				var from = $('#search');
				from.empty();

				$.each(data[0], function(index, element) {				
					from.append("<option value='"+ element.id +"'>" + element.name + "</option>");
				});
				
				var to = $('#search_to');
				to.empty();
				var count = 1;
				$.each(data[1], function(index, element) {				
					to.append("<option value='"+ element.id +"'>" + element.name + "</option>");
					count++;
				});
            },			
			error:function(request,status,error){
				//alert("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			}
        });	
	}
	
	$('#search').multiselect({
		search: {
			left: '<input type="text" name="q" class="form-control" placeholder="Add Location..." />',
			right: '<input type="text" name="q" class="form-control" placeholder="Selected Location..." />',
		},
		attatch : true
	});

		
	function onSubmit()
	{
		var ltgroup_id = $('#ltgroup_id').val();
		var type_id = $('#type_id').val();
		
		var select_id = new Object();
		var count = 0;
		$("#search_to option").each(function()
		{
			select_id[count] = $(this).val();
			count++;
		});
		
		var data = {ltgroup_id: ltgroup_id, type_id: type_id, select_id: select_id};
		
		$.ajax({
			type: "POST",
            url: "/backoffice/guestservice/wizard/location/postlocation",
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

