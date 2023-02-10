@extends('backoffice.wizard.admin.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ADD COMMON AREA";
	if( !empty($model->id) )
	{
		$method = "put";												
		$create = 'Update';
	}
	
	$current_url = '/backoffice/admin/wizard/common';
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
		<?php 
			$start = ($datalist->currentPage() - 1) * $datalist->perPage() + 1; 
			$end = $start + $datalist->count() - 1 
		?>	
		<form action="{{$current_url}}" method="GET">
			<div id="data_grid_view" class="grid-view" style="width:90%;height:240px;margin:auto;margin-top:30px">
				<table class="items">
					<thead>
						<tr>
							<th width="1%" class="center cm-no-hide-input">
								<input type="checkbox" name="check_all" value="Y" title="Check / uncheck all" class="checkbox cm-check-items" />
							</th>
							<th width="5%">#</th>
							<th width="15%">Common Area</th>
							<th width="15%">Building</th>
							<th width="15%">Floor</th>
							<th width="50%">Description</th>
							<th>
								<?php echo Form::select('pagesize', array('10' => '10', '20' => '20', '50' => '50', '100' => '100'), $pagesize, array('onchange' => 'this.form.submit()', 'style' => 'width:auto', 'class'=>'select-form')); ?>									
							</th>
						</tr>
					</thead>	
					<tbody>
						<?php $i = 1; ?>	
						@foreach ( $datalist as $value )	
							<tr class="odd">
								<td class="center cm-no-hide-input">
									<input type="checkbox" name="ids[]" value={{$value['id']}}" class="checkbox cm-item" />
								</td>
								<?php $no = $i + $start - 1; ?>
								<td> {{$no}}</td>
								<td >
									<a href="{{$current_url}}/{{$value['id']}}/edit{{$param}}"><span>{{$value['name']}}</span></a>
								</td>		
								<td >
									@if( !empty($value->floor) )
									{{ $value->floor->building['name'] }}
									@endif
								</td>										
								<td >
									{{$value->floor['floor']}}
								</td>
								<td >
									{{$value->description}}
								</td>				
								<td class="nowrap">
									<a class="tool-link " href="{{$current_url}}/{{$value['id']}}/edit{{$param}}" >Edit</a>
									&nbsp;&nbsp;|
									<ul class="cm-tools-list tools-list">
										<li><a class="cm-confirm" href="{{$current_url}}/delete/{{$value['id']}}{{$param}}">Delete</a></li>
									</ul>
								</td>
							</tr>
							<?php $i++; ?>
						@endforeach
						
						@if ($datalist->total() === 0)
							<tr class="no-items">
								<td class="center cm-no-hide-input" colspan="7">
									<p>No Common Area List.</p>
								</td>
							</tr>
						@endif
					</tbody>	
				</table>	
			</div>
		</form>
		
		<div style="margin-top:20px;text-align:center">
			<div style="margin:auto; width:90%">
				<span  class="delete-button" style="float:left">
					<i class="editbt-icon fa fa-pencil"></i>
					<input type="button" class="arrow-del-button" value="Edit" onclick="return resetForm(this.form);" />
				</span>
				
				<div class="form_item" style="float:left">							
					<input type="text" class="file" style="float:left;margin-left:50px" id="roomfile" name="roomfile" value="" READONLY />
					<div class="excel_upload">Choose file..</div>							
				</div>	
			</div>			
		</div>
		
		
		
			<div id="content_general" style="margin-top:60px;clear:left">
				<fieldset>
					<?php 
						$selectedbuild_id = $model->floor['bldg_id'];
						if( empty($selectedbuild_id) )
							$selectedbuild_id = $build_id;
					?>
					<div class="form-field">
						<label for="building" class="cm-required">Building:</label>
						<?php echo Form::select('build_id', $building, $selectedbuild_id, ['style' => 'width:auto', 'onchange' => 'onSelectBuilding()', 'id' => 'build_id']); ?>						
					</div>					
					{{ Form::open(array('url' => '/backoffice/admin/wizard/common/' . $model->id, 'method' => $method )) }}		
					<div class="form-field">
						<label for="floor" class="cm-required">Floor:</label>
						<?php echo Form::select('floor_id', $floor, $model->floor['id'], ['style' => 'width:auto', 'id' => 'floor_id']); ?>						
					</div>
					<div class="form-field">
						<label for="name" class="cm-required">Name(should be unique):</label>
						<input type="text" id="name" name="name" class="input-text" size="32" maxlength="50" value="{{$model->name}}" />															
					</div>						
					<div class="form-field">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="{{$model->description}}" />											
					</div>					
					
				</fieldset>
			</div>
					
			<div class="submit_container" >
				<span class="send-button cm-process-items">
					<i class="sendbt-icon fa fa-check"></i>
					<input type="submit" class="arrow-button" value="{{$create}}" onclick="onSubmit()" />
				</span>
				&nbsp;&nbsp;&nbsp;&nbsp;
				<span class="cancel-button cm-process-items">
					<i class="cancelbt-icon fa fa-times"></i>
					<input type="button" class="arrow-button" value="Cancel" onclick="return resetForm(this.form);" />
				</span>
			</div>
		
		{{ Form::close() }}				
			
	</div>	
	
</div>



<div class="bottom-button" style="clear:both;">
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/admin/wizard/department';" value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/admin/wizard/admin';"   value="  Next  " />
	</span>						
</div>
<script type="text/javascript">
	var excelupload = {
        url: "/backoffice/admin/wizard/common/upload",
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
	
	function onSelectBuilding()
	{
		var build_id = $('#build_id').val();
			
		$.ajax({
            url:"/dropdown/floor?build_id=" + build_id,
            success:function(data){
                console.log(data);
				var model = $('#floor_id');
				model.empty();

				$.each(data, function(index, element) {				
					model.append("<option value='"+ element.id +"'>" + element.floor + "</option>");
				});
            }
        });	
	}
	
</script>

@stop

