<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
		<meta name="csrf-token" content="<?php echo csrf_token() ?>">
		
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">
        <title>HotLync by Ennovatech</title>
        
		<!------- Bootstrap ------------------------->
		<link rel="stylesheet" href="/bootstrap/css/bootstrap.css">
		<link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.css">
		<link href="/bootstrap/css/simple-sidebar.css" rel="stylesheet">
		<link href="/font-awesome-4.6.1/css/font-awesome.min.css" rel="stylesheet">
		
		<!------- Datatable ------------------------>
		<link rel="stylesheet" href="/bootstrap/css/dataTables.bootstrap.min.css">
		<link rel="stylesheet" href="/bootstrap/css/jquery.dataTables.min.css">
		
		<!-- Upload -->
		<link rel="stylesheet" type="text/css" href="/css/uploadfile.css" />
		
		<!-- bootstrap switch-->
		<link href="/css/bootstrap-switch.css" rel="stylesheet">
		
		<!-- Angular JS -->
		<script src="app/libs/angular/angular-1.5.5.min.js"></script>
		<script src="app/libs/angular/angular-route-1.2.18.min.js"></script>
    </head>
	
<body ng-app="backoffice">
	<nav class="navbar navbar-inverse no-margin">
		<!-- Brand and toggle get grouped for better mobile display -->
		<div class="navbar-header fixed-brand">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" id="menu-toggle">
				<span class="glyphicon glyphicon-th-large" aria-hidden="true"></span>
			</button>
			<a class="navbar-brand" style="color:#2691d9" href="#"><strong>HOTLYNC</strong></a>
		</div>
		<!-- navbar-header-->
		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
			<ul class="nav navbar-nav">
				<li class="active">
					<button class="navbar-toggle collapse in" data-toggle="collapse" id="menu-toggle-2"> 
						<span class="glyphicon glyphicon-th-large" aria-hidden="true"></span>
					</button>
				</li>
				<li>
					<a href="#">Home <span class="sr-only">(current)</span></a>
				</li>
				<li>
					<a href="#">Front Office</a>
				</li>
				<form class="navbar-form navbar-left" role="search">
					<input type="text" class="form-control" placeholder="Search">
					<button type="submit" class="btn btn-default btn-sm">Submit</button>
				</form>
			</ul>
			<ul class="nav navbar-nav navbar-right">
				<li>
					<a href="#" class="btn btn- btn-xs"><span class="glyphicon glyphicon-log-out"></span> Log out</a>
				</li>
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-earphone"></span> Support <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li>
							<a href="#">Live Chat</a>
						</li>
						<li>
							<a href="#">Another action</a>
						</li>
						<li>
							<a href="#">Something else here</a>
						</li>
						<li role="separator" class="divider"></li>
						<li>
							<a href="#">Separated link</a>
						</li>
					</ul>
				</li>
			</ul>
		</div>
		<!-- bs-example-navbar-collapse-1 -->
	</nav>
    <div id="wrapper">           
		<div id="sidebar-wrapper">
			<ul class="sidebar-nav nav-pills nav-stacked" id="menu">
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-dashboard fa-stack-1x "></i></span> Dashboard</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#">Interface Statistics</a>
						</li>
						<li>
							<a href="#">System Errors</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-hotel fa-stack-1x "></i></span> Property</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#/property/client"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Client</a>
						</li>
						<li>
							<a href="#/property/property"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Property</a>
						</li>
						<li>
							<a href="#building"><span class="fa-stack fa-lg pull-left"><i class="fa fa-building fa-stack-1x "></i></span>Building</a>
						</li>
						<li>
							<a href="#floors"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Floors</a>
						</li>
						<li>
							<a href="#roomtype"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Room Type</a>
						</li>
						<li>
							<a href="#room"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Room</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-cloud-download fa-stack-1x "></i></span>Admin</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Department</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Admin Area</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Common Area</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Outdoor</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-calculator fa-stack-1x "></i></span>Call Accounting</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#/call/section"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Section</a>
						</li>
						<li>
							<a href="#/call/adminext"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Admin Extensions</a>
						</li>
						<li>
							<a href="#/call/guestext"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Guest Extensions</a>
						</li>
						<li>
							<a href="#/call/dest"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Destination</a>
						</li>
						<li>
							<a href="#/call/carrier"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Carrier</a>
						</li>
						<li>
							<a href="#/call/carriergroup"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Carrier Groups</a>
						</li>
						<li>
							<a href="#/call/carriercharge"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Carrier Charge</a>
						</li>
						<li>
							<a href="#/call/propertycharge"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Property Charge</a>
						</li>
						<li>
							<a href="#/call/tax"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Tax</a>
						</li>
						<li>
							<a href="#/call/allowance"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Allowance</a>
						</li>
						<li>
							<a href="#/call/timeslab"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Time Slabs</a>
						</li>
						<li>
							<a href="#/call/adminrate"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Admin Rate Mapping</a>
						</li>
						<li>
							<a href="#/call/guestrate"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Guest Rate Mapping</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-bell fa-stack-1x "></i></span> Guest Services</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#/guest/deptfunc"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Department Functions</a>
						</li>
						<li>
							<a href="#/guest/locationgroup"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Location Groups</a>
						</li>
						<li>
							<a href="#/guest/escalationgroup"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Escalation Groups</a>
						</li>
						<li>
							<a href="#/guest/tasklist"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Tasks</a>
						</li>
						<li>
							<a href="#/guest/taskgroup"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Task Groups</a>
						</li>
						<li>
							<a href="#/guest/"><span class="fa-stack fa-lg pull-left"><i class="fa fa-calendar fa-stack-1x "></i></span>Shifts</a>
						</li>
						<li>
							<a href="#/guest/hskp"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Houskeeping</a>
						</li>
						<li>
							<a href="#/guest/device"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Device</a>
						</li>
						<li>
							<a href="#/guest/minibar"><span class="fa-stack fa-lg pull-left"><i class="fa fa-glass fa-stack-1x "></i></span>Minibar</a>
						</li>
						<li>
							<a href="#/guest/minibaritem"><span class="fa-stack fa-lg pull-left"><i class="fa fa-glass fa-stack-1x "></i></span>Minibar Items</a>
						</li>
						<li>
							<a href="#/guest/alarm"><span class="fa-stack fa-lg pull-left"><i class="fa fa-glass fa-stack-1x "></i></span>Alarm</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-wrench fa-stack-1x "></i></span>Engineering</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Preventive Maintainance</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Inventory</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Work Order</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Scheduling</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Equipment</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Suppliers</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-users fa-stack-1x "></i></span>User</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>Permission</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-user fa-stack-1x "></i></span>Persmission Groups</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>User</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>User Groups</a>
						</li>
					</ul>
				</li>
				<li>
					<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-volume-control-phone fa-stack-1x "></i></span>IVR</a>
					<ul class="nav-pills nav-stacked" style="list-style-type:none;">
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-asterisk fa-stack-1x "></i></span>Asterisk</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-envelope fa-stack-1x "></i></span>Voicemail</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-microphone fa-stack-1x "></i></span>Recording</a>
						</li>
						<li>
							<a href="#"><span class="fa-stack fa-lg pull-left"><i class="fa fa-flag fa-stack-1x "></i></span>User Groups</a>
						</li>
					</ul>
				</li>
			</ul>
        </div>
        <!-- #sidebar-wrapper -->
		
		<!-- Page Content -->
		<div ng-view id="page-content-wrapper"></div>
		<!-- /#page-content-wrapper -->
    </div>    
        
	
	<!-- /#wrapper -->
	<!------- jquery ------------------------->
	<script type='text/javascript' src='/js/jquery.min.js'></script>
	<script src="/js/jquery-migrate-1.2.1.js"></script>
	<script type='text/javascript' src='/assets/js/ie10-viewport-bug-workaround.js'></script>	
	<script type='text/javascript' src='/js/jquery.validate.min.js'></script>	
	
	<!------- Bootstrap ------------------------->
	<script type='text/javascript' src="/bootstrap/js/bootstrap.min.js"></script>
	<script src="/bootstrap/js/sidebar_menu.js"></script>
	
	
	
	<!------- Datatable ------------------------>
	<script src="/js/jquery.dataTables.js"></script>
	<script src="/js/dataTables.bootstrap.js"></script>
	
	<!-- Upload -->
	<script type='text/javascript' src='/js/jquery.uploadfile.min.js'></script>
	
	<!-- bootstrap switch-->
	<script src="/js/bootstrap-switch.js"></script>
	
	<!------ Custom js ------------------->
	<script type='text/javascript' src='/js/jquery.appear-1.1.1.js'></script>
	
	<!-- Angular Uploader -->
	<script type='text/javascript' src='app/libs/angular/angular-file-upload.min.js'></script>
	<script type='text/javascript' src='app/libs/angular/angular-toggle-switch.min.js'></script>
	
	<!-- Our Website Javascripts -->
	<script src="app/js/backoffice.js"></script>
	<script src="app/js/factories/factory.js"></script>
	<script src="app/js/directives/directive.js"></script>
	<script src="app/js/controllers/callaccount/section.js"></script>
	<script src="app/js/controllers/callaccount/adminext.js"></script>
	<script src="app/js/controllers/callaccount/guestext.js"></script>
	<script src="app/js/controllers/callaccount/dest.js"></script>
	<script src="app/js/controllers/callaccount/carrier.js"></script>
	<script src="app/js/controllers/callaccount/carriergroup.js"></script>
	<script src="app/js/controllers/callaccount/carriercharge.js"></script>
	<script src="app/js/controllers/callaccount/propertycharge.js"></script>
	<script src="app/js/controllers/callaccount/tax.js"></script>
	<script src="app/js/controllers/callaccount/allowance.js"></script>
	<script src="app/js/controllers/callaccount/timeslab.js"></script>
	<script src="app/js/controllers/callaccount/adminrate.js"></script>
	<script src="app/js/controllers/callaccount/guestrate.js"></script>
	
	<script src="app/js/controllers/guest/deptfunc.js"></script>
	<script src="app/js/controllers/guest/locationgroup.js"></script>
	<script src="app/js/controllers/guest/escalationgroup.js"></script>
	<script src="app/js/controllers/guest/taskgroup.js"></script>
	<script src="app/js/controllers/guest/tasklist.js"></script>
	<script src="app/js/controllers/guest/minibar.js"></script>
	<script src="app/js/controllers/guest/minibaritem.js"></script>
	<script src="app/js/controllers/guest/hskp.js"></script>
	<script src="app/js/controllers/guest/device.js"></script>
	<script src="app/js/controllers/guest/alarm.js"></script>
	
	<!-- Multi Select-->
	<script src="/js/multiselect.js"></script>
	<script src="/js/jquery-sortable.js"></script>
	<style scoped>
		@import "/css/multimove.css";
	</style>
	
	<script type="text/javascript">
		$.ajaxSetup({
		  headers: {
			'X-CSRF-TOKEN': "{{ csrf_token() }}"
		  }
		});
		
		
	</script>
</body>
</html>
