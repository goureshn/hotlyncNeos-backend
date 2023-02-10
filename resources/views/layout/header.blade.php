@section('header')
<head>
	<meta charset="UTF-8">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	
	<title>HotLync</title>
	
	<script type='text/javascript' src='/js/jquery.min.js'></script>
	<script src="/js/jquery-migrate-1.2.1.js"></script>
	<script type='text/javascript' src='/assets/js/ie10-viewport-bug-workaround.js'></script>	
	<script src="/js/jquery.dataTables.js"></script>
    <script src="/js/dataTables.bootstrap.js"></script>
	
	<link rel="stylesheet" href="/bootstrap/css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="/bootstrap/css/bootstrap.css">
    <link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.css">
	<link href="/bootstrap/css/simple-sidebar.css" rel="stylesheet">
    <link href="/font-awesome-4.6.1/css/font-awesome.min.css" rel="stylesheet">
	<script type='text/javascript' src="/bootstrap/js/bootstrap.min.js"></script>
	
	
	<script type='text/javascript' src='/js/jquery.validate.min.js'></script>	
    
	
	<!-- Upload -->
	<script type='text/javascript' src='/js/jquery.uploadfile.min.js'></script>
	<link rel="stylesheet" type="text/css" href="/css/uploadfile.css" />
	
	<!-- bootstrap switch-->
	<link href="/css/bootstrap-switch.css" rel="stylesheet">
	<script src="/js/bootstrap-switch.js"></script>
	
	<script type='text/javascript' src='/js/jquery.appear-1.1.1.js'></script>
	<script type='text/javascript' src='/js/core.js'></script>
	<script type='text/javascript' src='/js/ajax.js'></script>
	<script type='text/javascript' src='/js/funcs.js'></script>	
	<script type='text/javascript' src='/js/customize_ui.js'></script>	
	
	
	<script type="text/javascript">
		$.ajaxSetup({
		  headers: {
			'X-CSRF-TOKEN': "{{ csrf_token() }}"
		  }
		});
		
		var changes_warning = 'Y';
        $(function(){
            $.runCart('A');
        });
		
	</script>
</head>
@show
