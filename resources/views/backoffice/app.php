<!DOCTYPE html>
<html lang="en" ng-controller="CommonController">
<head>
  <meta charset="utf-8">
	<meta name="csrf-token" content="<?php echo csrf_token() ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">
	<title>HotLync | Back Office</title>

  <!--<link rel="stylesheet" href="../libs/assets/animate.css/animate.css" type="text/css" />-->
  <link rel="stylesheet" href="../libs/assets/font-awesome/css/font-awesome.min.css" type="text/css" />
  <link rel="stylesheet" href="../libs/assets/simple-line-icons/css/simple-line-icons.css" type="text/css" />
  <link rel="stylesheet" href="../libs/jquery/bootstrap/dist/css/bootstrap.css" type="text/css" />
	<link rel="stylesheet" href="../libs/jquery/bootstrap/dist/css/font.css" type="text/css" />
  <link rel="stylesheet" href="../libs/jquery/bootstrap/dist/css/app.css" type="text/css" />
	<link rel="stylesheet" href="/css/mystyle.css">
	<link rel="stylesheet" href="/css/open_sans.css">
			<!------- Bootstrap ------------------------>
	<link rel="stylesheet" href="/bootstrap/css/bootstrap.css">
	<link rel="stylesheet" href="/bootstrap/css/bootstrap-theme.css">
	<link href="/bootstrap/css/simple-sidebar.css" rel="stylesheet">
	<link rel="shortcut icon" href="/assets/global/img/favicon.png" />
			<!------- Datatable ------------------------>
	<link rel="stylesheet" href="/bootstrap/css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="/bootstrap/css/jquery.dataTables.min.css">
	<link rel="stylesheet" href="../frontpage/bower_components/components-font-awesome/css/font-awesome.min.css" type="text/css" />
	<link rel="stylesheet" href="../libs/assets/simple-line-icons/css/simple-line-icons.css" type="text/css" />
  <link rel="stylesheet" href="../frontpage/bower_components/quill/quill.snow.css" type="text/css" />
  <link rel="stylesheet" href="../libs/angular/ng-tag/ng-tags-input.min.css" type="text/css" />
  <script src="../frontpage/bower_components/moment/moment.js"></script>
  <script type="text/javascript" src="../frontpage/bower_components/quill/quill.min.js"></script>
  <script src="../frontpage/js/libs/socket.io-2.2.0.js"></script>

</head>

<body>
<div class="app app-header-fixed">
  <header id="header" class="app-header navbar" role="menu" style="border-left: 0px">
      <div class="navbar-header bg-dark">
        <button class="pull-right visible-xs dk" ui-toggle-class="show" target=".navbar-collapse">
          <i class="glyphicon glyphicon-cog"></i>
        </button>
        <button class="pull-right visible-xs" ui-toggle-class="off-screen" target=".app-aside" ui-scroll="app">
          <i class="glyphicon glyphicon-align-justify"></i>
        </button>
        <a href="#/" class="navbar-brand text-lt" >
	        <div ng-show="show">
						<img src="../frontpage/img/ets.png" width="40px" style="padding-left: 10px; margin: 15px 15px 0px 0px"/>
          </div>
	        <img src="../frontpage/img/backend_header_logo.png" class="hidden-folded m-l-xs" width="150px" style="padding-left: 10px; margin: 15px 15px 0px 0px"/>
        </a>
      </div>

      <div class="collapse pos-rlt navbar-collapse box-shadow bg-white-only" style="height: 49px !important">
        <div class="nav navbar-nav hidden-xs" style="padding-right: 20px; margin: 0px">
          <a href="#" class="btn no-shadow navbar-btn" ng-click="show = !show" ui-toggle-class="app-aside-folded" target=".app">
            <i class="fa fa-dedent fa-fw text"></i>
            <i class="fa fa-indent fa-fw text-active"></i>
          </a>
        </div>

        <ul class="nav navbar-nav" style="padding: 0px; margin: 0px">
					<!--<li class="">
						<a href="#"><span><b><i class="fa fa-home"></i>Home</b></span></a>
					</li>-->
					<li class="">
						<a href="/hotlync?token=<?php echo csrf_token(); ?>#/access/signin" target="_blank">
							<span class="text-xs">
							<i class="fa fa-laptop"></i><b>&nbsp;&nbsp;Front Office</b>
							</span>
						</a>
					</li>
				</ul>

				<ul class="nav navbar-nav navbar-right" style="padding: 0px; margin: 0px">
					<li>
						<a href="#/support"" class="btn-lot">
							<span class="text-xs">
								<i class="fa fa-life-ring"></i>
                <b>&nbsp;&nbsp;Support</b>
							</span>
						</a>
					</li>
					<li>
						<a href="#" class="btn-lot" ng-click="logout()">
							<span class="text-xs">
							<i class="fa fa-sign-out"></i><b>&nbsp;&nbsp;Log Out</b>
							</span>
						</a>
					</li>
					<li>
            <a href="#" class="btn-lot">
              <span class="font-bold">
                &nbsp;&nbsp;{{wholename}} : {{job_role}}
              </span>
            </a>
          </li>
				</ul>
      </div>
  </header>

    <!-- aside -->
  <aside id="aside" class="app-aside hidden-xs bg-dark" style="position: absolute; bottom: 0; top: 0; z-index: 10; overflow-x: hidden;">
      <div class="aside-wrap">
        <div class="navi-wrap">

          <nav ui-nav class="navi clearfix">
            <ul class="nav">
	            <br><br><br><br>
	            <li class="hidden-folded padder m-t m-b-sm text-muted text-xs">
	            	<i class="fa fa-database text-success"></i>
                <span>Data Management</span>
              </li>

	            <li ng-if="isValidModule('bo.property')">
                <a href class="auto">
	                <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-bank"></i>
                  <span>Property</span>
                </a>
                <ul class="nav nav-sub dk">
                    <li ng-if="isValidModule('bo.property.module')"><a href="#/property/module"><span>Module</span></a></li>
                    <li ng-if="isValidModule('bo.property.client')"><a href="#/property/client"><span>Client</span></a></li>
                    <li ng-if="isValidModule('bo.property.property')"><a href="#/property/property"><span>Property</span></a></li>
                    <li ng-if="isValidModule('bo.property.license')"><a href="#/property/license"><span>License</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.admin')" >
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-id-badge"></i>
                  <span>Admin</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Admin</span>
                    </a>
                  </li>
                    <li ng-if="isValidModule('bo.admin.department')"><a href="#/admin/department"><span>Department</span></a></li>
                    <li ng-if="isValidModule('bo.admin.department')"><a href="#/admin/division"><span>Division</span></a></li>
                    <li ng-if="isValidModule('bo.admin.admin_area')"><a href="#/admin/adminarea"><span>Admin Area</span></a></li>
                    <li ng-if="isValidModule('bo.admin.common_area')"><a href="#/admin/commonarea"><span>Common Area</span></a></li>
                    <li ng-if="isValidModule('bo.admin.outdoor')"><a href="#/admin/outdoor"><span>Outdoor</span></a></li>
                    <li ><a href="#/admin/datamng"><span>Data Managment</span></a></li>
                    <li ng-if="isValidModule('bo.admin.faq')"><a href="#/admin/faq"><span>FAQ</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.property')">
                <a href class="auto">
	                <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-building"></i>
                  <span>Locations</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li ng-if="isValidModule('bo.property.building')"><a href="#/property/building"><span>Buildings</span></a></li>
                  <li ng-if="isValidModule('bo.property.floors')"><a href="#/property/floor"><span>Floors</span></a></li>
                  <li ng-if="isValidModule('bo.property.room_type')"><a href="#/property/roomtype"><span>Room Type</a></li>
                  <li ng-if="isValidModule('bo.property.room')"><a href="#/property/room"><span>Room</span></a></li>
                  <li ng-if="isValidModule('bo.property.room')"><a href="#/property/locationtype"><span>Location Type</span></a></li>
                  <li ng-if="isValidModule('bo.property.room')"><a href="#/property/location"><span>Location</span></a></li>
                  <li ng-if="isValidModule('bo.guestservices.location_group')"><a href="#/guest/locationgroup"><span>Location Groups</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.callcenter')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-mobile"></i>
                  <span>Call Center</span>
                </a>
                <ul class="nav nav-sub dk">
                    <li ng-if="isValidModule('bo.callcenter.extension')" ><a href="#/callcenter/extension"><span>Extension</span></a></li>
                    <li ng-if="isValidModule('bo.callcenter.channel')" ><a href="#/callcenter/channel"><span>Channel</span></a></li>
                    <li ng-if="isValidModule('bo.callcenter.ivr_call_type')" ><a href="#/callcenter/ivr_call_type"><span>Types</span></a></li>
                    <li ng-if="isValidModule('bo.callcenter.threshold')" ><a href="#/callcenter/threshold"><span>Threshold</span></a></li>
                    <li ng-if="isValidModule('bo.callcenter.threshold')" ><a href="#/callcenter/skill_group"><span>Skill Group</span></a></li>
                    <li ng-if="isValidModule('bo.callcenter.setting')" ><a href="#/callcenter/setting"><span>Setting</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.callaccounting')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-phone"></i>
                  <span>Call Accounting</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Call Accounting</span>
                    </a>
                  </li>
                    <li ng-if="isValidModule('bo.callaccounting.section')" ><a href="#/call/section"><span>Section</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.admin_extension')" ><a href="#/call/adminext"><span>Admin Extensions</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.guest_extension')" ><a href="#/call/guestext"><span>Guest Extensions</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.admin_tracking')" ><a href="#/call/admintracking"><span>Call Admin Tracking</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.whitelist')" ><a href="#/call/whitelist"><span>Whitelist</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.destination')" ><a href="#/call/dest"><span>Destination</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.carrier')" ><a href="#/call/carrier"><span>Carrier</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.carrier_group')" ><a href="#/call/carriergroup"><span>Carrier Groups</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.carrier_charge')" ><a href="#/call/carriercharge"><span>Carrier Charge</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.property_charge')" ><a href="#/call/propertycharge"><span>Property Charge</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.tax')" ><a href="#/call/tax"><span>Tax</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.allowance')" ><a href="#/call/allowance"><span>Allowance</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.time_slabs')" ><a href="#/call/timeslab"><span>Time Slabs</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.admin_rate_mapping')" ><a href="#/call/adminrate"><span>Admin Rate Mapping</span></a></li>
                    <li ng-if="isValidModule('bo.callaccounting.guest_rate_mapping')" ><a href="#/call/guestrate"><span>Guest Rate Mapping</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.guestservices')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-street-view"></i>
                  <span>Guest Services</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Guest Services</span>
                    </a>
                  </li>
                        <li ng-if="isValidModule('bo.guestservices.department_function')"><a href="#/guest/deptfunc"><span>Department Functions</span></a></li>

                        <li class="hide" ng-if="isValidModule('bo.guestservices.escalation_group')"><a href="#/guest/escalationgroup"><span>Escalation Groups</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.tasks')"><a href="#/guest/tasklist"><span>Tasks</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.task_group')"><a href="#/guest/taskgroup"><span>Task Groups</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.tasks')"><a href="#/guest/taskmain"><span>Main Tasks</span></a></li>
                        <li><a href="#/guest/shift"><span>Shifts</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.housekeeping')"><a href="#/guest/hskp"><span>Housekeeping</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.device')"><a href="#/guest/device"><span>Device</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.minibar')"><a href="#/guest/minibar"><span>Minibar</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.minibar_item')"><a href="#/guest/minibaritem"><span>Minibar Items</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.alarm')"><a href="#/guest/alarm"><span>Alarm</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.alexa')"><a href="#/guest/alexa"><span>Alexa</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.complaints')">
                    <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                        <i class="fa fa-comment"></i>
                        <span>Complaints</span>
                    </a>
                    <ul class="nav nav-sub dk">
                        <li class="nav-sub-header">
                            <a href>
                                <span>Complaints</span>
                            </a>
                        </li>
                        <li ng-if="isValidModule('bo.guestservices.compensation')"><a href="#/guest/compensation"><span>Compensation</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.compensation_approval_route')"><a href="#/guest/compapproute"><span>Compensation Approval Route</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.compensation_approval_ro_mem')"><a href="#/guest/compapproutemem"><span>Compensation Approval Route Member</span></a></li>
                        <li ng-if="isValidModule('bo.guestservices.department_default_assignee')"><a href="#/guest/deptdefaultass"><span>Department Default Assignee & Location</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.sub_complaint')"><a href="#/guest/subcomplaint"><span>Sub Complaints</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_type')"><a href="#/guest/complainttype"><span>Complaint Type</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_type')"><a href="#/complaint/feedbacksource"><span>Feedback Source</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_type')"><a href="#/complaint/feedbacktype"><span>Feedback Type</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_department_pivot')"><a href="#/guest/complaintdeptpivot"><span>Complaint Department Pivot</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_escalation')"><a href="#/guest/complaintecalation"><span>Complaint Escalation</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_escalation')"><a href="#/complaint/complaintdivisionescalation"><span>Complaint Division Escalation</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.subcomplaint_escalation')"><a href="#/complaint/subcomplaintecalation"><span>Sub Complaint Escalation</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.subcomplaint_loc_escalation')"><a href="#/complaint/subcomplaintlocescalation"><span>Sub Complaint Location Escalation</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.subcomplaint_reopen_escalation')"><a href="#/complaint/subcomplaintreopenescalation"><span>Sub Complaint Reopen Escalation</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.complaint_user_group_pivot')"><a href="#/guest/comgrouppivotplaint"><span>Complaint User Group Pivot</span></a></li>
                        <li ng-if="isValidModule('bo.complaints.sub_complaint')"><a href="#/complaint/subcomplaint_jobrole_dept"><span>Sub Complaint Job Role Department</span></a></li>
                    </ul>
                </li>

              <li ng-if="isValidModule('bo.users')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-user-circle-o"></i>
                  <span>Users</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Users</span>
                    </a>
                  </li>
                  <li ng-if="isValidModule('bo.users.permissionmodule')"><a href="#/user/pmmodule"><span>Permission Module</span></a></li>
                  <li ng-if="isValidModule('bo.users.permission')"><a href="#/user/permission"><span>Permission</span></a></li>
                  <li ng-if="isValidModule('bo.users.permissiongroup')" ><a href="#/user/pmgroup"><span>Persmission Groups</span></a></li>
                  <li ng-if="isValidModule('bo.users.user')" ><a href="#/user/user"><span>User</span></a></li>
                  <li ng-if="isValidModule('bo.users.usergroup')"><a href="#/user/usergroup"><span>User Groups</span></a></li>
                  <li ng-if="isValidModule('bo.users.jobrole')"><a href="#/user/createjob"><span>Job Role</span></a></li>
                  <li ng-if="isValidModule('bo.users.shift')"><a href="#/user/shift"><span>Shifts</span></a></li>
                  <li ng-if="isValidModule('bo.users.employee')" ><a href="#/user/employee"><span>Employee</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.ivr')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-th"></i>
                  <span>IVR</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>IVR</span>
                    </a>
                  </li>
                      <li class="disabled" ><a href=""><span>Asterisk</span></a></li>
                      <li class="disabled"><a href=""><span>Voicemail</span></a></li>
                      <li class="disabled"><a href=""><span>Recording</span></a></li>
                      <li class="disabled"><a href=""><span>User Groups</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.interface')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-cubes"></i>
                  <span>Interface</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Interface</span>
                    </a>
                  </li>
                    <li ng-if="isValidModule('bo.interface.channel')" ><a href="#/interface/channel"><span>Channel</span></a></li>
                    <li class="disabled"><a href=""><span>Message</span></a></li>
                    <li ng-if="isValidModule('bo.interface.protocol')" ><a href="#/interface/procotol"><span>Protocol</span></a></li>
                    <li ng-if="isValidModule('bo.interface.parsers')" ><a href="#/interface/parser"><span>Parsers</span></a></li>
                    <li ng-if="isValidModule('bo.interface.formatters')" ><a href="#/interface/formatter"><span>Formatters</span></a></li>
                    <li ng-if="isValidModule('bo.interface.alarm')" ><a href="#/interface/alarm"><span>Alarm</span></a></li>
                </ul>
              </li>
                <li ng-if="isValidModule('bo.services')">
                    <a href="#/services/list" class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                        <i class="fa fa-server"></i>
                        <span>Services</span>
                    </a>

                </li>

              <li ng-if="isValidModule('bo.engineering')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-wrench"></i>
                  <span>Engineering</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Engineering</span>
                    </a>
                  </li>
                        <li ng-if="isValidModule('bo.engineering.partgroup')" ><a href="#/engineering/partgroup"><span>Parts Group</span></a></li>
                        <li ng-if="isValidModule('bo.engineering.equipgroup')" ><a href="#/engineering/equipgroup"><span>Equipments Group</span></a></li>
                        <li ng-if="isValidModule('bo.engineering.category')" ><a href="#/engineering/category"><span>Category</span></a></li>
                        <li ng-if="isValidModule('bo.engineering.subcategory')" ><a href="#/engineering/subcategory"><span>Sub Category</span></a></li>
                        <li ng-if="isValidModule('bo.engineering.inventory')" ><a href="#/engineering/inventory"><span>Inventory</span></a></li>
                        <li class="disabled"><a href=""><span>Preventive Maintenance</span></a></li>
                        <li class="disabled"><a href=""><span>Work Order</span></a></li>
                        <li class="disabled"><a href=""><span>Scheduling</span></a></li>
                        <li class="disabled"><a href=""><span>Equipment</span></a></li>
                        <li ng-if="isValidModule('bo.engineering.supplier')" ><a href="#/engineering/supplier"><span>Suppliers</span></a></li>
                        <li ng-if="isValidModule('bo.engineering.contract')" ><a href="#/engineering/contract"><span>Contract</span></a></li>

                </ul>
              </li>

              <li ng-if="isValidModule('bo.it')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-wrench"></i>
                  <span>IT</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>IT</span>
                    </a>
                  </li>
                  <li ng-if="isValidModule('bo.it.category')" ><a href="#/it/category"><span>Category</span></a></li>
                  <li ng-if="isValidModule('bo.it.subcategory')" ><a href="#/it/subcategory"><span>Sub Category</span></a></li>
                  <li ng-if="isValidModule('bo.it.type')" ><a href="#/it/type"><span>Type</span></a></li>
                  <li ng-if="isValidModule('bo.it.subcategory')" ><a href="#/it/centralroute"><span>Centeralized Route</span></a></li>
                  <li ng-if="isValidModule('bo.it.subcategory')" ><a href="#/it/decentralroute"><span>Decenteralized Route</span></a></li>
                </ul>
              </li>

              <li ng-if="isValidModule('bo.backup')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-laptop"></i>
                  <span>Backup</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span>Backup</span>
                    </a>
                  </li>
                  <li ng-if="isValidModule('bo.backup.daily')" ><a href="#/backup/daily"><span>Daily</span></a></li>
                  <li ng-if="isValidModule('bo.backup.weekly')" ><a href="#/backup/weekly"><span>Weekly</span></a></li>
                  <li ng-if="isValidModule('bo.backup.monthly')" ><a href="#/backup/monthly"><span>Monthly</span></a></li>

                </ul>
              </li>
              <li class="line dk"></li>

              <li class="hidden-folded padder m-t m-b-sm text-muted text-xs">
              	<i class="fa fa-cogs text-primary-lter"></i>
                <span>Property Settings</span>
              </li>

              <li ng-if="isValidModule('bo.configuration')">
                <a href class="auto">
                  <span class="pull-right text-muted">
                    <i class="fa fa-fw fa-angle-right text"></i>
                    <i class="fa fa-fw fa-angle-down text-active"></i>
                  </span>
                  <i class="fa fa-cog"></i>
                  <span>Configuration</span>
                </a>
                <ul class="nav nav-sub dk">
                  <li class="nav-sub-header">
                    <a href>
                      <span class="font-bold">Configuration</span>
                    </a>
                  </li>
                  <li ng-if="isValidModule('bo.configuration.general')" ><a href="#/configuration/general"><span>General</span></a></li>
                    <li><a href="#/configuration/request"><span>Request</span></a></li>
                    <li><a href="#/configuration/chatbot"><span>Chatbot</span></a></li>

                    <li ng-if="isValidModule('bo.configuration.complaint')"><a href="#/configuration/complaint"><span>Complaint</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.auto_wakeup')"><a href="#/configuration/auto_wakeup"><span>Auto Wakeup</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.call_accounting')"><a href="#/configuration/call_account"><span>Call Accounting</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.call_centre')"><a href="#/configuration/call_center"><span>Call Centre</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.engineering')"><a href="#/configuration/engineering"><span>Engineering</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.lnf')"><a href="#/configuration/lnf"><span>Lost&Found</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.helpdesk')"><a href="#/configuration/helpdesk"><span>Helpdesk</span></a></li>
               <!--   <li class="disabled"><a href=""><span>Guest Services</span></a></li>
                  <li class="disabled"><a href=""><span>Housekeeping</span></a></li>
-->
                  <li ng-if="isValidModule('bo.configuration.guestservice')"><a href="#/configuration/guestservice"><span>Guest Services</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.housekeeping')"><a href="#/configuration/housekeeping"><span>Housekeeping</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.minibar')"><a href="#/configuration/minibar"><span>Minibar</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.report')"><a href="#/configuration/report"><span>Automated Reports</span></a></li>
                  <li ng-if="isValidModule('bo.configuration.minibar')"><a href="#/configuration/mobile"><span>Mobile App</span></a></li>
                </ul>
              </li>
              <li></br></li>

            </ul>
          </nav>
        </div>
      </div>
    </aside>

  <!-- / aside -->
  <div id="content" class="app-content" role="main" style="{{full_height}}">
  	<div class="app-content-body" style="padding: 0px;">
	    <div class="hbox hbox-auto-xs hbox-auto-sm">
				<div class="col">
					<div ng-view id="page-content-wrapper" style="overflow: hidden; padding: 0px">

					</div>
	  		</div>
  		</div>
		</div>
  </div>
	<footer id="footer" class="app-footer" role="footer" style="position: fixed">
    <div class="wrapper b-t bg-light font-bold">
      <span class="pull-right">
        <label class="text-danger">Total HDD SIZE : <?php echo $data['size_gb'] ?> GB  &nbsp;&nbsp; Used : <?php echo $data['used_gb'] ?> GB  &nbsp;&nbsp; Free : <?php echo $data['free_gb'] ?> GB </label> &nbsp;&nbsp; v3.0.2</span>
      &copy; Copyright <?php echo date("Y"); ?>. EnnovaTech Solutions FZ LLC. All Rights Reserved.
    </div>
  </footer>
</div>

<script data-main="adminpanel/js/main" src="/lib/require/require.js"></script>
<script src="../libs/jquery/jquery/dist/jquery.js"></script>
<!--<script src="../libs/jquery/bootstrap/dist/js/bootstrap.js"></script>-->
<script src="../js/ui-load.js"></script>
<script src="../js/ui-jp.config.js"></script>
<script src="../js/ui-jp.js"></script>
<script src="../js/ui-nav.js"></script>
<script src="../js/ui-toggle.js"></script>
<script src="../js/ui-client.js"></script>


</body>
</html>
