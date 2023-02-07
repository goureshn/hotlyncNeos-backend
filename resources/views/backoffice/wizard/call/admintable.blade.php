<table id="adminextn" class="table table-striped table-bordered" style="text-align: center" cellspacing="0" width="100%"> 
	<thead>
		<tr>
			<th class="no-sort" style="text-align: center">
				<input type="checkbox" id="checkall" />
			</th>
			<th style="text-align: center">
				<b>ID</b>
			 </th>   
			<th style="text-align: center">
				<b>Section</b>
			  </th>  
			<th style="text-align: center">
				<b>Extension</b>
			  </th>  
			<th style="text-align: center">
				<b>User</b>
			  </th>  
			<th style="text-align: center">
				<b>User Group</b>
			</th>
			<th style="text-align: center">Edit</th>
			<th style="text-align: center">Delete</th>
		</tr>
	</thead>
	<tbody>
	<?php
		$i = 1; 
		// for( $k = 0; $k < 100; $k++ ) { 
	?>
	@foreach ( $datalist as $value )
		<tr>
			<td>
				<input type="checkbox" class="checkthis" />
			</td>
			<td>{{$i}}</td>
			<td>{{$value->section['section']}}</td>
			<td>{{$value['extension']}}</td>
			<td>{{$value->user['username']}}</td>
			<td>{{$value['description']}}</td>
			<td><p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#myModaladdsection"  onClick="onShowSection({{$value['id']}})">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p></td>
			<td><p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deletesection" onClick="onDeleteSection({{$value['id']}})">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p></td>
		</tr>
		
		<?php $i++; ?>
	@endforeach
	<?php  	?>
	</tbody>
</table>