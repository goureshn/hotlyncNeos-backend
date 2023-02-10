@extends('backoffice.wizard.admin.setting_layout')
@section('setting_content')
<?php
	$method = "post";								
	$create = 'Submit';
	$title = "ADD DEPARTMENT";
	if( !empty($model->id) )
	{
		$method = "put";										
		$title = "model";
		$create = 'Update';
	}
	
	$current_url = '/backoffice/admin/wizard/department';
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
			<div id="data_grid_view" class="grid-view" style="width:90%;height:240px;margin:auto;margin-top:20px">
				<table class="items">
					<thead>
						<tr>
							<th width="1%" class="center cm-no-hide-input">
								<input type="checkbox" name="check_all" value="Y" title="Check / uncheck all" class="checkbox cm-check-items" />
							</th>
							<th class="titlename" width="5%">#</th>
							<th class="titlename" width="15%">Department</th>
							<th class="titlename" width="15%">Property</th>
							<th class="titlename" width="15%">Short Code</th>
							<th class="titlename" width="15%">Guest Service</th>
							<th class="titlename" width="50%">Description</th>
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
									<a href="{{$current_url}}/{{$value['id']}}/edit{{$param}}"><span>{{$value['department']}}</span></a>
								</td>		
								<td >
									{{$value->property['name']}}
								</td>										
								<td >
									{{$value['short_code']}}
								</td>
								<td >
									{{$value['services']}}
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
									<p>No Department List.</p>
								</td>
							</tr>
						@endif
					</tbody>	
				</table>	
			</div>
		</form>
		
		<div style="margin-top:10px;text-align:center">
			<div style="margin:auto; width:90%">
				<span class="delete-button" style="float:left">
					<i class="editbt-icon fa fa-pencil"></i>
					<input type="button" class="arrow-del-button" value="Edit" onclick="return resetForm(this.form);" />
				</span>			
			</div>			
		</div>
		
	
		
		{{ Form::open(array('url' => '/backoffice/admin/wizard/department/' . $model->id, 'method' => $method )) }}		
			<div id="content_general" style="margin-top:30px;clear:left">
				<fieldset>
					<div class="form-field">
						<label for="property" class="cm-required">Property:</label>
						<?php echo Form::select('property_id', $property, $model->property_id, ['style' => 'width:auto']); ?>						
					</div>
					<div class="form-field">
						<label for="department" class="cm-required">Department Name:</label>
						<input type="text" id="department" name="department" class="input-text" size="32" maxlength="50" value="{{$model->department}}" />															
					</div>						
					<div class="form-field">
						<label for="description">Description:</label>
						<input type="text" id="description" name="description" class="input-text" size="50" maxlength="100" value="{{$model->description}}" />											
					</div>		
					<div class="form-field">
						<label for="shortcode">ShortCode:</label>
						<input type="number" id="shortcode" name="short_code" class="input-text" size="50" maxlength="100" value="{{$model->short_code}}" />											
					</div>	
					
					<script type="text/javascript">
						$(function(argument) {
							$('#service').bootstrapSwitch();
						})	
					</script>
					
					<div class="form-field">
						<label for="mobile">Guest Service:</label>
						<div style="float:left;margin-top:-3px;">
							<input type="checkbox"  id="service"/>		
						</div>
					</div>
					
					
				</fieldset>
			</div>
		
			<div class="submit_container" style="margin-top: 30px;">
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
	<!--<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/property/wizard/room';"  value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;-->
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/admin/wizard/common';"  value="  Next  " />
	</span>						
</div>


<script type="text/javascript">

$(function(){
    $("ul li").click(function(){
        $("ul li").removeClass("on");
        $(this).addClass("on"); 
    });
});
	
</script>

@stop

