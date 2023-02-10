
<table class="table table-hover table-bordered" style="text-align: center">
	<thead>
		<tr>
			<th style="text-align: center">
				<input type="checkbox" id="checkall" />
			</th>
			<th style="text-align: center">
				<b>ID</b>
				<a href="?sort=id"><i class="fa fa-fw fa-sort"></i></a>

			<th style="text-align: center">
				<b>Section</b>
				<a href="?sort=section"><i class="fa fa-fw fa-sort"></i></a>

			<th style="text-align: center">
				<b>Department</b>
				<a href="?sort=department"><i class="fa fa-fw fa-sort"></i></a>

			<th style="text-align: center">
				<b>Manager</b>
				<a href="?sort=manager"><i class="fa fa-fw fa-sort"></i></a>

			<th style="text-align: center">
				<b>Description</b>
				<a href="?sort=description"><i class="fa fa-fw fa-sort"></i></a>
				<th style="text-align: center">Edit</th>
				<th style="text-align: center">Delete</th>
		</tr>
	</thead>
	<tbody>
	<?php 
		$start = ($datalist->currentPage() - 1) * $datalist->perPage() + 1; 
		$end = $start + $datalist->count() - 1; 
		$i = 1; 
	?>	
	@foreach ( $datalist as $value )
		<?php $no = $i + $start - 1; ?>
		<tr>
			<td>
				<input type="checkbox" class="checkthis" />
			</td>
			<td>{{$no}}</td>
			<td>{{$value['section']}}</td>
			<td>{{$value->department['department']}}</td>
			<td>{{$value->manager['username']}}</td>
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
	</tbody>
</table>
<div class="clearfix"></div>
<div style="float:right">
	{{ $datalist->fragment('foo#asc')->links() }}
</div>	

	