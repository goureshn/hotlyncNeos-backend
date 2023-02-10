@if( !empty($data['chart_graph_flag']) && $data['chart_graph_flag'] == 'true' )
	<br/><br/><br/><br/>
	<div class="mychart" style="text-align: center">
		<img src="data:image/png;base64,{{ $data['graph1']}}" {{$data['graph1_style']}}>
	</div>
@endif