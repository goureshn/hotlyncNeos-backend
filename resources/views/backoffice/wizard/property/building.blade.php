@extends('backoffice.wizard.property.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "BUILDING";
	if( !empty($model->id) )
	{
		$method = "put";												
		$create = 'Update';
	}
	
	$current_url = '/backoffice/property/wizard/building';
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
			<div id="data_grid_view" class="grid-view" style="width:70%;height:200px;">
				<table class="items">
					<thead>
						<tr>
							<th width="1%" class="center cm-no-hide-input">
								<input type="checkbox" name="check_all" value="Y" title="Check / uncheck all" class="checkbox cm-check-items" />
							</th>
							<th width="10%">#</th>
							<th width="10%">Building</th>
							<th width="65%">Description</th>
							<th class="button-column" id="data_grid_view_c8">
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
									<p>No Building List.</p>
								</td>
							</tr>
						@endif
					</tbody>	
				</table>	
			</div>
		</form>
		
		<div style="margin-top:10px;text-align:center">
			<div style="text-align:left;margin:auto; width:70%">
				<span class="delete-button" onclick="onDelete()" >
					<i class="editbt-icon fa fa-pencil"></i>
					<input type="button" class="arrow-del-button" value="Delete" onclick="onDelete()" />
				</span>
			</div>
		</div>
		
		{{ Form::open(array('url' => '/backoffice/property/wizard/building/' . $model->id, 'method' => $method )) }}		
			<div id="content_general" style="margin-top:30px">
				<fieldset>
					<div class="form-field">
						<label for="desc" class="cm-required">Property:</label>
						<?php echo Form::select('property_id', $property, $model->property_id, ['style' => 'width:auto']); ?>
					</div>
					<div class="form-field">
						<label for="name" class="cm-required">Building:</label>
						<input type="text" id="name" name="name" class="input-text" size="32" maxlength="50" value="{{$model->name}}" />															
					</div>						
					<div class="form-field">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description" class="input-text" size="32" maxlength="100" value="{{$model->description}}" />											
					</div>		
				</fieldset>
			</div>
		
			<div class="submit_container">
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
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/property/wizard/property';"  value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/property/wizard/floor';"  value="  Next  " />
	</span>						
</div>

@stop

