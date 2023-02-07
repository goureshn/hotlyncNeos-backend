<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
    <base href="/frontpage/" target="_blank">

    <title>HotLync | EnnovaTech</title>
    <link rel="icon" href="img/favicon.ico" type="image/gif" sizes="16x16">
    <!--  <link rel="stylesheet" href="../libs/assets/animate.css/animate.css" type="text/css" />-->
    <link rel="stylesheet" href="bower_components/components-font-awesome/css/font-awesome.min.css" type="text/css" />
    <link rel="stylesheet" href="../libs/assets/simple-line-icons/css/simple-line-icons.css" type="text/css" />
    <link rel="stylesheet" href="../libs/angular/angular-material/angular-material.css" type="text/css" />
    <link rel="stylesheet" href="../libs/jquery/bootstrap/dist/css/bootstrap.css" type="text/css" />
    <!--  <link href="https://fonts.googleapis.com/css?family=Roboto" rel="stylesheet">-->
    <!--  <link rel="stylesheet" href="bower_components/angular-aside/dist/css/angular-aside.css" type="text/css" />-->
    <link rel="stylesheet" href="bower_components/hint.css/hint.css" type="text/css" />
    <link rel="stylesheet" type="text/css" href="bower_components/jqplot/jquery.jqplot.min.css" />

    <link rel="stylesheet" href="bower_components/angular/angular-csp.css">
    <link rel="stylesheet" href="bower_components/angular-surveys/dist/form-builder-bootstrap.min.css">
    <link rel="stylesheet" href="bower_components/angular-surveys/dist/form-viewer.min.css">

    <!-- build:css css/app.material.css -->
    <link rel="stylesheet" href="css/material-design-icons.css" type="text/css" />
    <link rel="stylesheet" href="css/md.css" type="text/css" />
    <link rel="stylesheet" href="css/font.css" type="text/css" />
    <link rel="stylesheet" href="css/app.css" type="text/css" />
    <link rel="stylesheet" href="css/style.css" type="text/css" />
    <link rel="stylesheet" href="css/tabs.css" type="text/css" />
    <link rel="stylesheet" href="css/tabstyles.css" type="text/css" />
    <link rel="stylesheet" href="css/normalize.css" type="text/css" />
    <link rel='stylesheet' href='bower_components/angular-loading-bar/build/loading-bar.min.css' type='text/css' media='all' />
    <link rel="stylesheet" href="css/ngFader.css" type="text/css" />
    <!-- endbuild -->
    <style>
        #mydiv {
            position:absolute;
            top:0;
            left:0;
            width:100%;
            height:100%;
            z-index:1000;
            background-color:grey;
            opacity: .8;
        }

        .ajax-loader {
            position: absolute;
            left: 50%;
            top: 50%;
            margin-left: -32px; /* -1 * image width / 2 */
            margin-top: -32px;  /* -1 * image height / 2 */
            display: block;
        }

    </style>
</head>
<body ng-app = "mainApp" ng-controller = "DBInstallController" layout="row" style="font-size: inherit;">
<div layout="column" flex ui-view>

    <div class=" panel panel-default" style="border-color: #dee5e7">
        <div class="panel-heading wrapper">
            <div  style="float:right; margin-top: -5px; background: #efefef;padding:5px 10px 5px 10px;color: #868585;font-size: 16px; " >
                <strong></strong>
                <!--<button type="button" class="btn btn-success btn-sm" style="float:right; margin-top: -5px" data-toggle="modal" data-target="#addModal" ng-disabled="job_role!='SuperAdmin'&&edit_flag==0" ng-click="saveMinibar()">
                 <span class="glyphicon glyphicon-plus"></span>
                 <b> Update </b>
             </button> -->
            </div>
            <i class="fa fa-cog"></i>
            <span class="font-bold"> &nbsp;&nbsp;Database Configuration</span>
        </div>
        <div class="" style="padding-top:20px;background-color: #eeeeee">
            <!----------------------left  general --->
            <div class = col-sm-2></div>
            <div class="col-sm-6">
                <!--<div>
                    <h5><strong> Database Configuration</strong></h5>
                </div>-->
                <div style="margin-left: 30px">
                    <div class="form-horizontal" >
                            <div class="form-group">
                                <label class="col-sm-6 text-right">Database Hostname:</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" ng-model = "db.path" required/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 text-right">Database Username:</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" ng-model = "db.username" required/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 text-right">Database Password:</label>
                                <div class="col-sm-6">
                                    <input type="password" class="form-control" ng-model = "db.password" required />
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 text-right">Database Password Confirm:</label>
                                <div class="col-sm-6">
                                    <input type="password" class="form-control" ng-model = "db.password_confirm" required/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 text-right">Database Name:</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" ng-model = "db.dbname" required/>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-6 text-right">Interface Database Name:</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" ng-model = "db.interface_dbname" required />
                                </div>
                            </div>

                            <!--<div class="form-group">
                                <label class="col-sm-6">Database Source File(*.sql)</label>
                                <div class="col-sm-6">
                                    <input type="file" name="file" onchange="angular.element(this).scope().uploadSql(this.files)"/>

                                </div>
                            </div>-->

                            <div class="form-group">
<!--                                <label ng-show="loading"> <span class="glyphicon glyphicon-refresh spinning"></span> Loading...</label>
-->                                <button class="col-sm-2 pull-right" ng-click = "saveDB()" style="margin-right: 15px;padding:5px;color:white;background-color:rgb(85, 46, 110);" > Save</button>
                            </div>

                        <div ng-show="loading" id="mydiv">
                            <img src="\assets\admin\layout2\img\ajax-loading.gif" class="ajax-loader"/>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>



</div>

<!-- build:js js/app.material.js -->
<!-- jQuery -->
<script src="../libs/jquery/jquery/dist/jquery-2.2.4.min.js"></script>
<script src="../libs/jquery/jquery/dist/jquery-ui.min.js"></script>

<!-- Angular -->
<script src="../libs/angular/angular/angular.min.js"></script>
<script src="../libs/angular/angular-animate/angular-animate.min.js"></script>

<script src="../libs/angular/angular-cookies/angular-cookies.js"></script>
<script src="../libs/angular/angular-messages/angular-messages.js"></script>
<script src="../libs/angular/angular-resource/angular-resource.js"></script>
<script src="../libs/angular/angular-sanitize/angular-sanitize.js"></script>
<script src="../libs/angular/angular-touch/angular-touch.js"></script>
<!-- ngMaterial -->

<script src="../libs/angular/angular-ui-router/release/angular-ui-router.js"></script>
<script src="../libs/angular/ngstorage/ngStorage.js"></script>

<!-- bootstrap -->
<script src="../libs/jquery/bootstrap/dist/js/bootstrap.js"></script>
<script src="bower_components/angular-bootstrap/ui-bootstrap-tpls.js"></script>

<!-- lazyload -->
<script src="../libs/angular/oclazyload/dist/ocLazyLoad.min.js"></script>
<!-- translate -->
<script src="../libs/angular/angular-translate/angular-translate.js"></script>
<script src="../libs/angular/angular-translate-loader-static-files/angular-translate-loader-static-files.js"></script>
<script src="../libs/angular/angular-translate-storage-cookie/angular-translate-storage-cookie.js"></script>
<script src="../libs/angular/angular-translate-storage-local/angular-translate-storage-local.js"></script>
<script type='text/javascript' src='bower_components/angular-loading-bar/build/loading-bar.min.js'></script>
<script src="bower_components/angular-scroll-glue/src/scrollglue.js"></script>
<script src="bower_components/sprintf/src/sprintf.js"></script>
<script src="bower_components/sprintf/src/angular-sprintf.js"></script>
<script src="bower_components/moment/moment.js"></script>
<script src="js/libs/socket.io-1.4.5.js"></script>
<script src="bower_components/angular-socket-io/socket.js"></script>


<script src='bower_components/angular-duration-format/dist/angular-duration-format.js'></script>
<script>

    var mainApp = angular.module("mainApp", []);
    mainApp.controller('DBInstallController', function($scope,$http,$location,$window) {
        var request = {};
        $scope.db = {path:"",dbname:"",username:"",password:"",password_confirm:"",interface_dbname:""};
        $scope.loading = false;
        $scope.saveDB = function() {

            if($scope.db.password !== $scope.db.password_confirm)
            {
                alert("Please Confirm Password!");
                return;
            }

            request.DB_HOST=$scope.db.path;
            request.DB_DATABASE=$scope.db.dbname;
            request.DB_USERNAME=$scope.db.username;
            request.DB_PASSWORD=$scope.db.password;
            request.DB_INTERFACE_DATABASE = $scope.db.interface_dbname;

            $scope.loading = true;
            $http.post('/dbinstall', request)
                .then(function (response) {
                    console.log(response);
                    if(response.data == "Success"){
                        $window.location.reload();
                    }
                    else{
                        alert("Failed!");
                    }
                }).catch(function(response) {
            })
                .finally(function() {
                    $scope.loading = false;
                });
        }

        $scope.uploadSql = function(){
            var fd = new FormData();
            //Take the first selected file
            fd.append("file", files[0]);
            $scope.db.sql_file = fd;

        }
    });


</script>
<!-- App -->
<!--<script src="guest/js/guest_app.js"></script>
<script src="js/config.js"></script>
<script src="js/config.lazyload.js"></script>
<script src="guest/js/guest_config.router.js"></script>
<script src="guest/js/guest_main.js"></script>
<script src="js/services/socket.service.js"></script>
<script src="js/controllers/modal/modal_input.ctrl.js"></script>
<script src="guest/services/guest_httpresponseinterceptor.factory.js"></script>
<script src="js/filters/myfilter.js"></script>
<script src="js/directives/mydirective.js"></script>
<script src="guest/services/auth.service.js"></script>-->
</body>
</html>
