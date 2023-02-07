<!DOCTYPE html>
<html ng-app="app">
<head>
	<meta charset="UTF-8">
	<link rel="shortcut icon" href="/frontpanel/img/logo.png" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="description" content="upwork test">
	<meta name="keywords" content="test">
	<title>Test</title>
	<!-- build:css css/vendor.min.css -->
	<!-- bower:css -->
	<link rel="stylesheet" href="bower_components/normalize-css/normalize.css" />
	<link rel="stylesheet" href="bower_components/hint.css/hint.min.css">
	<link rel="stylesheet" href="bower_components/angular-ui-grid/ui-grid.min.css">
	<link rel="stylesheet" href="bower_components/angular-ui/build/angular-ui.min.css">
	<link rel="stylesheet" href="bower_components/angular-native-datepicker/build/themes/default.css">
	<link rel="stylesheet" href="bower_components/angular-native-datepicker/build/themes/classic.date.css">
	<link rel="stylesheet" href="bower_components/angular-native-datepicker/build/themes/default.date.css">
	<link rel="stylesheet" href="bower_components/angular-native-datepicker/build/themes/classic.time.css">
	<link rel="stylesheet" href="bower_components/angularjs-datetime-picker/angularjs-datetime-picker.css">
	<link rel="stylesheet" href="bower_components/angular-native-datepicker/build/themes/rtl.css">
	<!-- endbower -->
	<!-- endbuild -->
	<!-- build:css css/style.min.css -->
	<link rel="stylesheet" href="/frontpanel/css/style.css" />
	<link rel="stylesheet" href="/frontpanel/css/font-awesome.min.css" />
	<!-- endbuild -->
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery.ui.all.css" />
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js"></script>
</head>
<body>
	<main>
		<header class="header">
			<div class="header__logo">
				<a href="#/"><img src="/frontpanel/img/hotlync.png" alt="logo"></a>
			</div>
			<form action="/search" class="header__search">
				<button class="btn--inactive"><i class="fa fa-search"></i></button>
				<input type="text" placeholder="Search">
				<button class="btn--submit">
					<i class="fa fa-clock-o"></i>
				</button>
				<span class="btn--submit" id="hellouser">
					<i class="fa fa-bell"></i>
					<div class="notifbox">
						<ul>
							<li>asjlf</li>
							<li>sdf</li>
							<li>sdfsd</li>
						</ul>
					</div>
				</span>
			</form>
			<ul class="header__nav" ng-controller="filterController">
				<button class="changeview hint  hint--bottom" data-hint="Change View">
					<img src="/frontpanel/img/1column.png" alt="">
				</button>
				<div class="account-img"><i class="fa fa-user"></i></div>
				<div class="account-menu">
					<div>Hello, User	</div>

					<div class="submenu">
						<li class="nav-item"><a href=""><i class="fa fa-cog"></i><span>Preferences</span></a></li>
						<li class="nav-item"><a href=""><i class="fa fa-question-circle"></i><span>Support</span></a></li>
						<li class="nav-item"><a href=""><i class="fa fa-question-circle"></i><span>Logout</span></a></li>
					</div>
				</div>
			</ul>
		</header>
		<section class="container">

			<aside class="aside" ng-include="'frontpanel/pages/menu.html'"></aside>
			<div class="view" ng-view></div>
		</section>
		<script src="/frontpanel/js/main.js"></script>
	</main>
	<script src="bower_components/angular/angular.min.js"></script>
	<script src="bower_components/angular-route/angular-route.min.js"></script>
	<script src="bower_components/angular-ui/build/angular-ui.min.js"></script>
	<script src="bower_components/angular-ui/modules/directives/sortable/sortable.js"></script>
	<script src="bower_components/angular-ui-grid/ui-grid.min.js"></script>
	<script src="bower_components/angular-datetime-picker/js/angular-datetime-picker.js"></script>
	<script src="bower_components/angularjs-datetime-picker/angularjs-datetime-picker.js"></script>
	<script src="bower_components/angular-native-datepicker/build/angular-datepicker.js"></script>
	<script src="bower_components/angular-hotkeys/build/hotkeys.min.js"></script>
	<script src="/frontpanel/js/app.js"></script>
	<script src="/frontpanel/js/config/config.js"></script>
	<script src="/frontpanel/js/controllers/index.controller.js"></script>
	<script src="/frontpanel/js/controllers/callaccounting.controller.js"></script>
	<script src="/frontpanel/js/controllers/minibar.controller.js"></script>
	<script src="/frontpanel/js/controllers/housekeeping.controller.js"></script>
	<script src="/frontpanel/js/controllers/calldistribution.controller.js"></script>
	<script src="/frontpanel/js/controllers/calls.controller.js"></script>
	<script src="/frontpanel/js/controllers/engeneering.controller.js"></script>
	<script src="/frontpanel/js/controllers/guestsurvey.controller.js"></script>
	<script src="/frontpanel/js/main.js"></script>
</body>
</html>