<!DOCTYPE html>
<html lang="en" data-ng-app="app">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
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
  <!--tab scroll css-->
  <link rel="stylesheet" href="../libs/tabscroll/scrolling-tabs.css">

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
  <link rel='stylesheet' href='bower_components/angular-block-ui/dist/angular-block-ui.min.css' type='text/css' media='all' />
  <link rel="stylesheet" href="css/ngFader.css" type="text/css" />
  
  <!-- Mobile Drag and Drop -->
  <link rel="stylesheet" href="bower_components/mobile-drag-drop/release/default.css">

  <!-- endbuild -->
</head>
<body ng-controller="AppCtrl" layout="row" ng-init="init('<?php echo base64_encode(json_encode($_REQUEST))?>')">
  <input type="hidden" id="app_config" value="'<?php echo base64_encode(json_encode($app_config))?>'">
  <div layout="column" flex ui-view></div>

<!-- build:js js/app.material.js --> 
  <script type="text/javascript" src="dist/frontend.min.js"></script>

  <!-- App -->

  <script src="js/app.js"></script>
  <script src="js/config.js"></script>
  <script src="js/config.lazyload.js"></script>
  <script src="js/config.router.js"></script>
  <script src="js/main.js"></script>
  <script src="js/services/socket.service.js"></script>
  <script src="js/services/auth.service.js"></script>
  <script src="js/controllers/modal/modal_input.ctrl.js"></script>
  <script src="js/services/httpresponseinterceptor.factory.js"></script>
  <script src="js/services/ui-load.js"></script>
  <script src="js/filters/myfilter.js"></script>
<!--  <script src="js/controllers/calllogger/call_event.controller.js"></script>-->  
<!--  <script src="js/directives/setnganimate.js"></script>-->
<!--  <script src="js/directives/ui-butterbar.js"></script>-->
<!--  <script src="js/directives/ui-focus.js"></script>-->
  <script src="js/directives/ui-fullscreen.js"></script>
<!--  <script src="js/directives/ui-jq.js"></script>-->
  <script src="js/directives/ui-module.js"></script>
  <script src="js/directives/mydirective.js"></script>  
<!--  <script src="js/directives/ui-nav.js"></script>-->
<!--  <script src="js/directives/ui-scroll.js"></script>-->
<!--  <script src="js/directives/ui-shift.js"></script>-->
<!--  <script src="js/directives/ui-toggleclass.js"></script>-->
<!--  <script src="js/directives/ui-contextmenu.js"></script>-->

<!--  <script src="js/controllers/aside/ticketfilter.controller.js"></script>-->

  <script>
    // Initialize Firebase
//    var config = {
//      apiKey: "AIzaSyAdscqAvI81Y9LTlQvCRzV1URInMTVwo64",
//      authDomain: "hotlync-d73c6.firebaseapp.com",
//      databaseURL: "https://hotlync-d73c6.firebaseio.com",
//      storageBucket: "hotlync-d73c6.appspot.com",
//    };
//    firebase.initializeApp(config);

    MobileDragDrop.polyfill({
        // use this to make use of the scroll behaviour
        dragImageTranslateOverride: MobileDragDrop.scrollBehaviourDragImageTranslateOverride
    });
  </script>
</body>
</html>
