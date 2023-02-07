<!doctype html>
<html lang="en">
<head>
	<title>HotLync Login</title>

	<link rel="stylesheet" href="/frontpage/bower_components/font-awesome/css/font-awesome.min.css" type="text/css" />
	<link rel="stylesheet" href="/frontpage/bower_components/bootstrap/dist/css/bootstrap.css" type="text/css" />

	<!-- Simple Chat -->
	<link rel="stylesheet" href="/frontpage/css/app.css">
	<link rel="stylesheet" href="/frontpage/css/style.css">

	<!-- Dependencies -->
	<script src="/frontpage/bower_components/jquery/dist/jquery.min.js"></script>
	<script src="/frontpage/bower_components/angular/angular.js"></script>

	<script src="/libs/angular/angular-cookies/angular-cookies.js"></script>
	<script src="../libs/angular/ngstorage/ngStorage.js"></script>
	<script src="/frontpage/bower_components/angular-bootstrap/ui-bootstrap-tpls.js"></script>

	<script src="/adminpanel/signin/main.js"></script>
	<script src="/frontpage/js/services/auth.service.js"></script>
	<script src="/adminpanel/signin/login.js"></script>

</head>
<body ng-app = "app" ng-controller="SigninFormController">
<!--
<div class="container w-xxl w-auto-xs">
	<a href class="navbar-brand block m-t">HotLync Admin Panel</a>
	<div class="m-b-lg">
		<div class="wrapper text-center">

		</div>
		<form ng-submit="login()" name="form" class="form-validation">
			<div class="text-danger wrapper text-center" ng-show="authError">
				{{authError}}
			</div>
			<div class="list-group list-group-sm">
				<div class="list-group-item">
					<input type="text" placeholder="Username" class="form-control no-border" ng-model="user.username" required>
				</div>
				<div class="list-group-item">
					<input type="password" placeholder="Password" class="form-control no-border" ng-model="user.password" required>
				</div>
			</div>
			<button type="submit" class="btn btn-lg btn-primary btn-block"  ng-disabled='form.$invalid'>Log in</button>
		</form>
	</div>
</div>
</body> -->


<div class="login-page" style="display: table-cell; vertical-align:middle; text-align: center;">
	<div class="container w-xxl w-auto-xs"  ng-init="app.settings.container = false;">
		<img class="navbar-brand block m-t" src="../frontpage/img/hotlync_.png" alt="" style="width: 330px; height: auto"m align="middle">
	  <!--<a href class="navbar-brand block m-t">{{app.name}}</a>-->
	  <div class="m-b-lg">
		  <label class="navbar-brand block m-t" style="color: #009688">Back Office Login</label>
		<div class="wrapper text-center">

		</div>
		<form name="form" class="form-validation">
		  <div class="text-danger wrapper text-center" ng-show="authError">
			  {{authError}}
		  </div>
		  <div class="list-group list-group-sm">
				<div class="list-group-item">
				  <input type="text" placeholder="Username" class="form-control no-border" ng-model="user.username" required>
				</div>
				<div class="list-group-item">
				   <input type="password" placeholder="Password" class="form-control no-border" ng-model="user.password" required>
				</div>
		  </div>
		  <button type="submit" class="login-button" style="display: block; margin: 0 auto;" ng-click="getCpmpare_flag()" ng-disabled='form.$invalid'>Log in</button>
		  
		  <!--<div class="text-center m-t m-b" style="color:#fff"><a ui-sref="access.forgotpwd">Forgot password?</a></div>-->
		  

		 
		</form>
	  </div>
	  <!--
	  <div class="text-center" ng-include="'tpl/blocks/page_footer.html'">
		{% include 'blocks/page_footer.html' %}
	  </div>-->
	</div>
</div> 




</html>
