@extends('backoffice.wizard.user.setting_layout')
@section('setting_content')
<?php	
	$title = "USER GROUP";	
	
	$current_url = '/backoffice/user/wizard/usergroup';	
	$param = "";
	if( !empty($_SERVER["QUERY_STRING"]) )
		$param = '?' . $_SERVER["QUERY_STRING"];
?>
<div class="item_container" style="margin:auto;height:600px">
	<div class="items">		
		<span style="float:left;margin-left:10px;margin-top:10px">
		{{$title}}
		</span>
		
		<span class="delete-button"  style="float:right;margin-top:7px;margin-right:2px;background:#648CA9; border-radius:3px;border:1px solid;border-color:#ccc;">
			<i class="editbt-icon fa fa-pencil"></i>
			<input type="button" class="arrow-del-button" value="Add" onclick="location.href = '/backoffice/user/wizard/usergroup/create'" />
		</span>
	</div>		
		
	<div class="form_center">		
		<?php 
			$start = ($datalist->currentPage() - 1) * $datalist->perPage() + 1; 
			$end = $start + $datalist->count() - 1 
		?>	
		<form action="{{$current_url}}" method="GET">
			<div id="data_grid_view" class="grid-view" style="width:99%;height:450px;margin-left:3px;margin-right:6px;margin-top:30px">
				<table class="items">
					<thead>
						<tr>
							<th width="1%" class="center cm-no-hide-input">
								<input type="checkbox" name="check_all" value="Y" title="Check / uncheck all" class="checkbox cm-check-items" />
							</th>
							<th width="3%">#</th>
							<th width="10%">Property</th>
							<th width="10%">Name</th>
							<th width="10%">Permission Group</th>
							<th width="10%">Group Notification</th>
							<th width="10%">Type</th>
							<th width="10%">E-mail</th>
							<th width="10%">Mobile no.</th>
							<th width="10%">Permission</th>							
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
									<a href="{{$current_url}}/{{$value['id']}}/edit"><span>{{$value->property['name']}}</span></a>
								</td>										
								<td >
									{{$value['name']}}
								</td>	
								<td >
									{{$value->pmgroup['name']}}
								</td>
								<td >
									{{$value['group_notification']}}
								</td>
								<td >
									{{$value['group_notification_type']}}
								</td>
								<td >
									{{$value['email']}}
								</td>
								<td >
									{{$value['sms']}}
								</td>
								<td >
									
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
									<p>No User List.</p>
								</td>
							</tr>
						@endif
					</tbody>	
				</table>	
			</div>
		</form>
		
		<div style="margin-top:10px;text-align:center">
			<div style="margin:auto; width:99%">
				<span class="delete-button" style="float:left">
					<i class="editbt-icon fa fa-pencil"></i>
					<input type="button" class="arrow-del-button" value="Edit" onclick="return resetForm(this.form);" />
				</span>			
			</div>			
		</div>
		
	</div>	
	
</div>

<div class="bottom-button" style="clear:both;">
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/user/wizard/client';"  value="  Prev  " />
	</span>	&nbsp;&nbsp;&nbsp;&nbsp;
	<span class="button-style cm-process-items" style="width:90%;text-align:right;margin:auto;margin-top:10px;">
		<input type="button" class="arrow-button" onclick="location.href = '/backoffice/user/wizard/pmgroup';"  value="  Next  " />
	</span>						
</div>


<script type="text/javascript">
	
	
	
</script>
@stop