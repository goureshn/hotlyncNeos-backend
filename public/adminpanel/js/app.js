define(['angularAMD', 'angular-route', 'angular-file', 'sidebar_menu', 'toggle-switch', 'checklist-model', 'cookies', 'ngStorage', 'ui.bootstrap','angularjs-dropdown-multiselect','ngQuill','ngTagsInput','btford.socket-io','base64'], function (angularAMD) {

    var app = angular.module("webapp", ['ngRoute', 'angularFileUpload', 'toggle-switch', 'checklist-model', 'ngCookies', 'ngStorage', 'ui.bootstrap','angularjs-dropdown-multiselect','ngQuill','ngTagsInput','btford.socket-io','base64']);

    app.config(function ($routeProvider) {
		$routeProvider

		.when("/support", angularAMD.route({templateUrl: '/adminpanel/partials/support.html', controller: 'SupportCtrl', controllerUrl: 'controllers/support'}))

		.when("/property/client", angularAMD.route({templateUrl: '/adminpanel/partials/property/client.html', controller: 'ClientCtrl', controllerUrl: 'controllers/property/client'}))
		.when("/property/property", angularAMD.route({templateUrl: '/adminpanel/partials/property/property.html', controller: 'PropertyCtrl', controllerUrl: 'controllers/property/property'}))
		.when("/property/building", angularAMD.route({templateUrl: '/adminpanel/partials/property/building.html', controller: 'BuildingCtrl', controllerUrl: 'controllers/property/building'}))
		.when("/property/license", angularAMD.route({templateUrl: '/adminpanel/partials/property/license.html', controller: 'LicenseCtrl', controllerUrl: 'controllers/property/license'}))
		.when("/property/floor", angularAMD.route({templateUrl: '/adminpanel/partials/property/floor.html', controller: 'FloorCtrl', controllerUrl: 'controllers/property/floor'}))
		.when("/property/roomtype", angularAMD.route({templateUrl: '/adminpanel/partials/property/roomtype.html', controller: 'RoomtypeCtrl', controllerUrl: 'controllers/property/roomtype'}))
		.when("/property/room", angularAMD.route({templateUrl: '/adminpanel/partials/property/room.html', controller: 'RoomCtrl', controllerUrl: 'controllers/property/room'}))
		.when("/property/location", angularAMD.route({templateUrl: '/adminpanel/partials/property/location.html', controller: 'LocationCtrl', controllerUrl: 'controllers/property/location'}))
		.when("/property/locationtype", angularAMD.route({templateUrl: '/adminpanel/partials/property/locationtype.html', controller: 'LocationTypeCtrl', controllerUrl: 'controllers/property/locationtype'}))
		.when("/property/module", angularAMD.route({templateUrl: '/adminpanel/partials/property/module.html', controller: 'ModuleCtrl', controllerUrl: 'controllers/property/module'}))


		.when("/admin/department", angularAMD.route({templateUrl: '/adminpanel/partials/admin/department.html', controller: 'DepartmentCtrl', controllerUrl: 'controllers/admin/department'}))
		.when("/admin/division", angularAMD.route({templateUrl: '/adminpanel/partials/admin/division.html', controller: 'DivisionCtrl', controllerUrl: 'controllers/admin/division'}))
		.when("/admin/commonarea", angularAMD.route({templateUrl: '/adminpanel/partials/admin/commonarea.html', controller: 'CommonareaCtrl', controllerUrl: 'controllers/admin/commonarea'}))
		.when("/admin/adminarea", angularAMD.route({templateUrl: '/adminpanel/partials/admin/adminarea.html', controller: 'AdminareaCtrl', controllerUrl: 'controllers/admin/adminarea'}))
		.when("/admin/datamng", angularAMD.route({templateUrl: '/adminpanel/partials/admin/datamng.html', controller: 'DataManageCtrl', controllerUrl: 'controllers/admin/datamng'}))
		.when("/admin/outdoor", angularAMD.route({templateUrl: '/adminpanel/partials/admin/outdoor.html', controller: 'OutdoorCtrl', controllerUrl: 'controllers/admin/outdoor'}))
		.when("/admin/faq", angularAMD.route({templateUrl: '/adminpanel/partials/admin/faq.html', controller: 'FaqCtrl', controllerUrl: 'controllers/admin/faq'}))

		.when("/user/user", angularAMD.route({templateUrl: '/adminpanel/partials/user/user.html', controller: 'UserCtrl', controllerUrl: 'controllers/user/user'}))
		.when("/user/pmgroup", angularAMD.route({templateUrl: '/adminpanel/partials/user/pmgroup.html', controller: 'PmgroupCtrl', controllerUrl: 'controllers/user/pmgroup'}))
		.when("/user/usergroup", angularAMD.route({templateUrl: '/adminpanel/partials/user/usergroup.html', controller: 'UsergroupCtrl', controllerUrl: 'controllers/user/usergroup'}))
		.when("/user/permission", angularAMD.route({templateUrl: '/adminpanel/partials/user/permission.html', controller: 'PermissionCtrl', controllerUrl: 'controllers/user/permission'}))
		.when("/user/pmmodule", angularAMD.route({templateUrl: '/adminpanel/partials/user/pmmodule.html', controller: 'PmModuleCtrl', controllerUrl: 'controllers/user/pmmodule'}))
		.when("/user/createjob", angularAMD.route({templateUrl: '/adminpanel/partials/user/createjob.html', controller: 'CreatejobCtrl', controllerUrl: 'controllers/user/createjob'}))
		.when("/user/shift", angularAMD.route({templateUrl: '/adminpanel/partials/user/shift.html', controller: 'ShiftCtrl', controllerUrl: 'controllers/user/shift'}))
		.when("/user/employee", angularAMD.route({templateUrl: '/adminpanel/partials/user/employee.html', controller: 'EmployeeCtrl', controllerUrl: 'controllers/user/employee'}))

		.when("/callcenter/extension", angularAMD.route({templateUrl: '/adminpanel/partials/callcenter/extension.html', controller: 'ExtensionCtrl', controllerUrl: 'controllers/callcenter/extension'}))   
		.when("/callcenter/channel", angularAMD.route({templateUrl: '/adminpanel/partials/callcenter/channel.html', controller: 'ChannelCtrl', controllerUrl: 'controllers/callcenter/channel'}))   
		.when("/callcenter/ivr_call_type", angularAMD.route({templateUrl: '/adminpanel/partials/callcenter/ivr_call_type.html', controller: 'IVRCallTypeCtrl', controllerUrl: 'controllers/callcenter/ivr_call_type'}))   
		.when("/callcenter/threshold", angularAMD.route({templateUrl: '/adminpanel/partials/callcenter/threshold.html', controller: 'ThresholdCtrl', controllerUrl: 'controllers/callcenter/threshold'}))
		.when("/callcenter/skill_group", angularAMD.route({templateUrl: '/adminpanel/partials/callcenter/skill_group.html', controller: 'SkillgroupCtrl', controllerUrl: 'controllers/callcenter/skill_group'}))   
		.when("/callcenter/setting", angularAMD.route({templateUrl: "adminpanel/partials/configuration/call_center.html", controller: "CallCenterCtrl", controllerUrl: 'controllers/configuration/call_center'}))


		.when("/call/section", angularAMD.route({templateUrl: '/adminpanel/partials/call/section.html', controller: 'SectionCtrl', controllerUrl: 'controllers/callaccount/section'}))
		.when("/call/adminext", angularAMD.route({templateUrl: '/adminpanel/partials/call/adminext.html', controller: 'AdminextCtrl', controllerUrl: 'controllers/callaccount/adminext'}))
		.when("/call/guestext", angularAMD.route({templateUrl: "adminpanel/partials/call/guestext.html", controller: "GuestextCtrl", controllerUrl: 'controllers/callaccount/guestext'}))
		.when("/call/admintracking", angularAMD.route({templateUrl: "adminpanel/partials/call/admintracking.html", controller: "AdminTrackingCtrl", controllerUrl: 'controllers/callaccount/admintracking'}))
		.when("/call/whitelist", angularAMD.route({templateUrl: "adminpanel/partials/call/whitelist.html", controller: "WhitelistCtrl", controllerUrl: 'controllers/callaccount/whitelist'}))
		.when("/call/dest", angularAMD.route({templateUrl: "adminpanel/partials/call/dest.html", controller: "DestinationCtrl", controllerUrl: 'controllers/callaccount/dest'}))
		.when("/call/carrier", angularAMD.route({templateUrl: "adminpanel/partials/call/carrier.html", controller: "CarrierCtrl", controllerUrl: 'controllers/callaccount/carrier'}))
		.when("/call/carriergroup", angularAMD.route({templateUrl: "adminpanel/partials/call/carriergroup.html", controller: "CarriergroupCtrl", controllerUrl: 'controllers/callaccount/carriergroup'}))
		.when("/call/carriercharge", angularAMD.route({templateUrl: "adminpanel/partials/call/carriercharge.html", controller: "CarrierchargeCtrl", controllerUrl: 'controllers/callaccount/carriercharge'}))
		.when("/call/propertycharge", angularAMD.route({templateUrl: "adminpanel/partials/call/propertycharge.html", controller: "PropertychargeCtrl", controllerUrl: 'controllers/callaccount/propertycharge'}))
		.when("/call/tax", angularAMD.route({templateUrl: "adminpanel/partials/call/tax.html", controller: "TaxCtrl", controllerUrl: 'controllers/callaccount/tax'}))
		.when("/call/allowance", angularAMD.route({templateUrl: "adminpanel/partials/call/allowance.html", controller: "AllowanceCtrl", controllerUrl: 'controllers/callaccount/allowance'}))
		.when("/call/timeslab", angularAMD.route({templateUrl: "adminpanel/partials/call/timeslab.html", controller: "TimeslabCtrl", controllerUrl: 'controllers/callaccount/timeslab'}))
		.when("/call/adminrate", angularAMD.route({templateUrl: "adminpanel/partials/call/adminrate.html", controller: "AdminrateCtrl", controllerUrl: 'controllers/callaccount/adminrate'}))
		.when("/call/guestrate", angularAMD.route({templateUrl: "adminpanel/partials/call/guestrate.html", controller: "GuestrateCtrl", controllerUrl: 'controllers/callaccount/guestrate'}))

		.when("/guest/deptfunc", angularAMD.route({templateUrl: "adminpanel/partials/guest/deptfunc.html", controller: "DeptfuncCtrl", controllerUrl: 'controllers/guest/deptfunc'}))
		.when("/guest/locationgroup", angularAMD.route({templateUrl: "adminpanel/partials/guest/locationgroup.html", controller: "LocationgroupCtrl", controllerUrl: 'controllers/guest/locationgroup'}))
		.when("/guest/escalationgroup", angularAMD.route({templateUrl: "adminpanel/partials/guest/escalationgroup.html", controller: "EscgroupCtrl", controllerUrl: 'controllers/guest/escalationgroup'}))
		.when("/guest/taskgroup", angularAMD.route({templateUrl: "adminpanel/partials/guest/taskgroup.html", controller: "TaskgroupCtrl", controllerUrl: 'controllers/guest/taskgroup'}))
		.when("/guest/taskmain", angularAMD.route({templateUrl: "adminpanel/partials/guest/taskmain.html", controller: "TaskMainCtrl", controllerUrl: 'controllers/guest/taskmain'}))
		.when("/guest/tasklist", angularAMD.route({templateUrl: "adminpanel/partials/guest/tasklist.html", controller: "TasklistCtrl", controllerUrl: 'controllers/guest/tasklist'}))
		.when("/guest/minibar", angularAMD.route({templateUrl: "adminpanel/partials/guest/minibar.html", controller: "MinibarCtrl", controllerUrl: 'controllers/guest/minibar'}))
		.when("/guest/minibaritem", angularAMD.route({templateUrl: "adminpanel/partials/guest/minibaritem.html", controller: "MinibaritemCtrl", controllerUrl: 'controllers/guest/minibaritem'}))
		.when("/guest/hskp", angularAMD.route({templateUrl: "adminpanel/partials/guest/hskp.html", controller: "HousekeepingCtrl", controllerUrl: 'controllers/guest/hskp'}))
		.when("/guest/device", angularAMD.route({templateUrl: "adminpanel/partials/guest/device.html", controller: "DeviceCtrl", controllerUrl: 'controllers/guest/device'}))
		.when("/guest/alarm", angularAMD.route({templateUrl: "adminpanel/partials/guest/alarm.html", controller: "AlarmCtrl", controllerUrl: 'controllers/guest/alarm'}))
		.when("/guest/subcomplaint", angularAMD.route({templateUrl: "adminpanel/partials/guest/subcomplaint.html", controller: "SubcomplaintCtrl", controllerUrl: 'controllers/guest/subcomplaint'}))
		.when("/guest/complainttype", angularAMD.route({templateUrl: "adminpanel/partials/guest/complainttype.html", controller: "ComplainttypeCtrl", controllerUrl: 'controllers/guest/complainttype'}))
		.when("/guest/complaintdeptpivot", angularAMD.route({templateUrl: "adminpanel/partials/guest/complaintdeptpivot.html", controller: "ComplaintdeptpivotCtrl", controllerUrl: 'controllers/guest/complaintdeptpivot'}))
		.when("/guest/complaintecalation", angularAMD.route({templateUrl: "adminpanel/partials/guest/complaintecalation.html", controller: "ComplaintescalationCtrl", controllerUrl: 'controllers/guest/complaintecalation'}))
		.when("/guest/complaintgrouppivot", angularAMD.route({templateUrl: "adminpanel/partials/guest/complaintgrouppivot.html", controller: "ComplaintgrouppivotCtrl", controllerUrl: 'controllers/guest/complaintgrouppivot'}))
		.when("/guest/compensation", angularAMD.route({templateUrl: "adminpanel/partials/guest/compensation.html", controller: "CompensationCtrl", controllerUrl: 'controllers/guest/compensation'}))
		.when("/guest/compapproute", angularAMD.route({templateUrl: "adminpanel/partials/guest/comapproute.html", controller: "ComapprouteCtrl", controllerUrl: 'controllers/guest/comapproute'}))
		.when("/guest/compapproutemem", angularAMD.route({templateUrl: "adminpanel/partials/guest/compapproutemem.html", controller: "ComapproutememCtrl", controllerUrl: 'controllers/guest/compapproutemem'}))
		.when("/guest/deptdefaultass", angularAMD.route({templateUrl: "adminpanel/partials/guest/deptdefaultass.html", controller: "DeptdefaultassCtrl", controllerUrl: 'controllers/guest/deptdefaultass'}))
		.when("/guest/shift", angularAMD.route({templateUrl: "adminpanel/partials/guest/shift.html", controller: "ShiftCtrl", controllerUrl: 'controllers/guest/shift'}))
		.when("/guest/alexa", angularAMD.route({templateUrl: "adminpanel/partials/guest/alexa.html", controller: "AlexaCtrl", controllerUrl: 'controllers/guest/alexa'}))

		.when("/complaint/subcomplaint_jobrole_dept", angularAMD.route({templateUrl: "adminpanel/partials/complaint/subcomplaint_jobrole_dept.html", controller: "SubcomplaintJobroleDeptCtrl", controllerUrl: 'controllers/complaint/subcomplaint_jobrole_dept'}))
		.when("/complaint/feedbacksource", angularAMD.route({templateUrl: "adminpanel/partials/complaint/feedback_source.html", controller: "FeedbackSourceCtrl", controllerUrl: 'controllers/complaint/feedback_source'}))
		.when("/complaint/feedbacktype", angularAMD.route({templateUrl: "adminpanel/partials/complaint/feedback_type.html", controller: "FeedbackTypeCtrl", controllerUrl: 'controllers/complaint/feedback_type'}))
		.when("/complaint/complaintdivisionescalation", angularAMD.route({templateUrl: "adminpanel/partials/complaint/complaintdivisionescalation.html", controller: "ComplaintDivisionEscalationCtrl", controllerUrl: 'controllers/complaint/complaintdivisionescalation'}))
		.when("/complaint/subcomplaintecalation", angularAMD.route({templateUrl: "adminpanel/partials/complaint/subcomplaintecalation.html", controller: "SubcomplaintEscalationCtrl", controllerUrl: 'controllers/complaint/subcomplaintecalation'}))
		.when("/complaint/subcomplaintlocescalation", angularAMD.route({templateUrl: "adminpanel/partials/complaint/subcomplaintlocescalation.html", controller: "SubcomplaintLocEscalationCtrl", controllerUrl: 'controllers/complaint/subcomplaintlocescalation'}))
		.when("/complaint/subcomplaintreopenescalation", angularAMD.route({templateUrl: "adminpanel/partials/complaint/subcomplaintreopenescalation.html", controller: "SubcomplaintReopenEscalationCtrl", controllerUrl: 'controllers/complaint/subcomplaintreopenescalation'}))


		.when("/interface/channel", angularAMD.route({templateUrl: "adminpanel/partials/interface/channel.html", controller: "ChannelCtrl", controllerUrl: 'controllers/interface/channel'}))
		.when("/interface/procotol", angularAMD.route({templateUrl: "adminpanel/partials/interface/protocol.html", controller: "ProtocolCtrl", controllerUrl: 'controllers/interface/protocol'}))
		.when("/interface/parser", angularAMD.route({templateUrl: "adminpanel/partials/interface/parser.html", controller: "ParserCtrl", controllerUrl: 'controllers/interface/parser'}))
		.when("/interface/formatter", angularAMD.route({templateUrl: "adminpanel/partials/interface/formatter.html", controller: "FormatterCtrl", controllerUrl: 'controllers/interface/formatter'}))
		.when("/interface/alarm", angularAMD.route({templateUrl: "adminpanel/partials/interface/alarm.html", controller: "AlarmCtrl", controllerUrl: 'controllers/interface/alarm'}))
		.when("/interface/logs", angularAMD.route({templateUrl: "adminpanel/partials/interface/logs.html", controller: "LogCtrl", controllerUrl: 'controllers/interface/logs'}))

		.when("/services/list", angularAMD.route({templateUrl: "adminpanel/partials/services/services.html", controller: "ServicesCtrl", controllerUrl: 'controllers/services/services'}))

		.when("/configuration/general", angularAMD.route({templateUrl: "adminpanel/partials/configuration/general.html", controller: "GeneralCtrl", controllerUrl: 'controllers/configuration/general'}))
		.when("/configuration/request", angularAMD.route({templateUrl: "adminpanel/partials/configuration/request.html", controller: "RequestCtrl", controllerUrl: 'controllers/configuration/request'}))
		.when("/configuration/chatbot", angularAMD.route({templateUrl: "adminpanel/partials/configuration/chatbot.html", controller: "ChatbotCtrl", controllerUrl: 'controllers/configuration/chatbot'}))

		.when("/configuration/complaint", angularAMD.route({templateUrl: "adminpanel/partials/configuration/complaint.html", controller: "ComplaintCtrl", controllerUrl: 'controllers/configuration/complaint'}))
		.when("/configuration/auto_wakeup", angularAMD.route({templateUrl: "adminpanel/partials/configuration/auto_wakeup.html", controller: "AutoWakeupCtrl", controllerUrl: 'controllers/configuration/auto_wakeup'}))
		.when("/configuration/call_account", angularAMD.route({templateUrl: "adminpanel/partials/configuration/call_account.html", controller: "CallAccountCtrl", controllerUrl: 'controllers/configuration/call_account'}))
		.when("/configuration/minibar", angularAMD.route({templateUrl: "adminpanel/partials/configuration/minibar.html", controller: "MinibarCtrl", controllerUrl: 'controllers/configuration/minibar'}))
		.when("/configuration/report", angularAMD.route({templateUrl: "adminpanel/partials/configuration/report.html", controller: "ReportCtrl", controllerUrl: 'controllers/configuration/report'}))
		.when("/configuration/guestservice", angularAMD.route({templateUrl: "adminpanel/partials/configuration/guestservice.html", controller: "GuestServiceCtrl", controllerUrl: 'controllers/configuration/guestservice'}))
		.when("/configuration/housekeeping", angularAMD.route({templateUrl: "adminpanel/partials/configuration/housekeeping.html", controller: "HskpCtrl", controllerUrl: 'controllers/configuration/housekeeping'}))
		.when("/configuration/call_center", angularAMD.route({templateUrl: "adminpanel/partials/configuration/call_center.html", controller: "CallCenterCtrl", controllerUrl: 'controllers/configuration/call_center'}))
		.when("/configuration/mobile", angularAMD.route({templateUrl: "adminpanel/partials/configuration/mobile.html", controller: "MobileCtrl", controllerUrl: 'controllers/configuration/mobile'}))
		.when("/configuration/engineering", angularAMD.route({templateUrl: "adminpanel/partials/configuration/engineering.html", controller: "EngCtrl", controllerUrl: 'controllers/configuration/engineering'}))
		.when("/configuration/helpdesk", angularAMD.route({templateUrl: "adminpanel/partials/configuration/helpdesk.html", controller: "HelpdeskCtrl", controllerUrl: 'controllers/configuration/helpdesk'}))
		.when("/configuration/lnf", angularAMD.route({templateUrl: "adminpanel/partials/configuration/lnf_config.html", controller: "LnfConfigCtrl", controllerUrl: 'controllers/configuration/lnf_config'}))

		.when("/engineering/partgroup", angularAMD.route({templateUrl: "adminpanel/partials/engineering/partgroup.html", controller: "PartGroupCtrl", controllerUrl: 'controllers/engineering/partgroup'}))
		.when("/engineering/equipgroup", angularAMD.route({templateUrl: "adminpanel/partials/engineering/equipgroup.html", controller: "EquipGroupCtrl", controllerUrl: 'controllers/engineering/equipgroup'}))
		.when("/engineering/category", angularAMD.route({templateUrl: "adminpanel/partials/engineering/category.html", controller: "CategoryCtrl", controllerUrl: 'controllers/engineering/category'}))
		.when("/engineering/subcategory", angularAMD.route({templateUrl: "adminpanel/partials/engineering/subcategory.html", controller: "SubcategoryCtrl", controllerUrl: 'controllers/engineering/subcategory'}))
		.when("/engineering/supplier", angularAMD.route({templateUrl: "adminpanel/partials/engineering/supplier.html", controller: "SupplierCtrl", controllerUrl: 'controllers/engineering/supplier'}))
		.when("/engineering/contract", angularAMD.route({templateUrl: "adminpanel/partials/engineering/contract.html", controller: "ContractCtrl", controllerUrl: 'controllers/engineering/contract'}))
		.when("/engineering/inventory", angularAMD.route({templateUrl: "adminpanel/partials/engineering/inventory.html", controller: "InventoryCtrl", controllerUrl: 'controllers/engineering/inventory'}))
		.when("/backup/daily", angularAMD.route({templateUrl: "adminpanel/partials/backup/daily.html", controller: "CategoryCtrl", controllerUrl: 'controllers/backup/daily'}))
		.when("/backup/weekly", angularAMD.route({templateUrl: "adminpanel/partials/backup/weekly.html", controller: "CategoryCtrl", controllerUrl: 'controllers/backup/weekly'}))
		.when("/backup/monthly", angularAMD.route({templateUrl: "adminpanel/partials/backup/monthly.html", controller: "CategoryCtrl", controllerUrl: 'controllers/backup/monthly'}))

		.when("/it/category", angularAMD.route({templateUrl: "adminpanel/partials/it/category.html", controller: "CategoryCtrl", controllerUrl: 'controllers/it/category'}))
		.when("/it/subcategory", angularAMD.route({templateUrl: "adminpanel/partials/it/subcategory.html", controller: "SubcategoryCtrl", controllerUrl: 'controllers/it/subcategory'}))
		.when("/it/type", angularAMD.route({templateUrl: "adminpanel/partials/it/type.html", controller: "TypeCtrl", controllerUrl: 'controllers/it/type'}))
		.when("/it/centralroute", angularAMD.route({templateUrl: "adminpanel/partials/it/centralroute.html", controller: "CenteralRouteCtrl", controllerUrl: 'controllers/it/centralroute'}))
		.when("/it/decentralroute", angularAMD.route({templateUrl: "adminpanel/partials/it/centralroute.html", controller: "DecenteralRouteCtrl", controllerUrl: 'controllers/it/decentralroute'}))

		.otherwise({redirectTo: "/property/property"});
    });


	app.controller('CommonController', function($scope, $rootScope, $cookieStore, $window, $http, $location, $localStorage,$sessionStorage, interface) {
        if($localStorage.admin)
		{
            $sessionStorage.admin = $localStorage.admin;
            $localStorage.admin = {};
            $localStorage.$reset();
		}

		$rootScope.globals = $sessionStorage.admin;
		$rootScope.job_role = 'Admin';

        $rootScope.$on('$locationChangeStart', function (event, next, current) {
            // redirect to login page if not logged in

            $rootScope.globals = $sessionStorage.admin;
            if ($location.path() !== '/hotlyncBO/signin' && !($rootScope.globals && $rootScope.globals.currentUser)) {
                $window.location.href = '/hotlyncBO/signin';
            }
        });


        $scope.full_height = 'height: ' + ($window.innerHeight - 75) + 'px; overflow-y: auto;';

		//get hard size
		$http({
			method: 'POST',
			url: '/backoffice/property/wizard/gethardsize',
			data: {},
			headers: {'Content-Type': 'application/json; charset=utf-8'}
		}).then(function(response) {
			$scope.total = response.data.total;
			$scope.used = response.data.used;
			$scope.free = response.data.free;

		}).catch(function(response) {

			})
			.finally(function() {

			});
		//


		if( $rootScope.globals && $rootScope.globals.currentUser )
		{
			var user = $rootScope.globals.currentUser;
			$rootScope.job_role = user.job_role;
			$rootScope.wholename = user.wholename;
			//$http.defaults.headers.common['Authorization'] = $localStorage.admin.authdata; // jshint ignore:line
            $http.defaults.headers.common['Authorization'] = $sessionStorage.admin.authdata; // jshint ignore:line

        }
		else
		{
			$window.location.href = '/hotlyncBO/signin';
		}

		 $scope.$on('updateCSS', function(event, args) {
			$scope.stylesheets = args;
		});


        /*$rootScope.$on('$stateChangeStart', function(event, toState, toStateParams, fromState, fromStateParams) {
            if (toState.name.indexOf('access') < 0 && !AuthService.isAuthenticated()) {
                event.preventDefault();
                $state.go('access.signin');
            }
            else
            {
                if( toState.name.indexOf('access') < 0 ) {
                    $rootScope.profile = AuthService.GetCredentials();
                    if (AuthService.isValidModule(toState.name) == false) {
                        event.preventDefault();
                        $state.go('access.signin');
                    }
                }
            }
        });
*/





		$scope.logout = function () {
			console.log('logout');
			$rootScope.globals = {};
			$localStorage.admin = {};
            $sessionStorage.admin = {};
			$http.defaults.headers.common.Authorization = 'Basic ';

			$window.location.href = '/hotlyncBO';
		}
		//permission for page
		$scope.isValidModule = function(page){
			var global = false;
			if( $rootScope.globals && $rootScope.globals.currentUser )
				global = true;

			$rootScope.globals = $sessionStorage.admin;
			if( $rootScope.globals && $rootScope.globals.currentUser )
				global = true;

			if( global == false )
				return false;

			var permission = $rootScope.globals.currentUser.permission;
			for(var i = 0; i < permission.length; i++)
			{
				if( page == permission[i].name )
					return true;
			}

			return false;
		}

		// socket.emit('support', 'This is message');
		// socket.on('support_emit', function(message){
		// 	alert(message);
		// });

	});

	app.config(['$httpProvider', function($httpProvider) {
		  $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
	  }]);

	app.service('interface', function ($http) {
		this.api = "http://localhost:3000";
		var vm = this;

		var request = {property_id: 0};
		$http({
			method: 'POST',
			url: '/project/setting',
			data: request,
			headers: {'Content-Type': 'application/json; charset=utf-8'}
		}).then(function(response) {
			if( response.data ) {
				for(var i = 0; i < response.data.length; i++) {
					if(response.data[i].settings_key == 'interface_host') {
						vm.api = response.data[i].value;
						break;
					}
				}
			}
		}).catch(function(response) {

		})
		.finally(function() {

		});


	});

	app.factory('socket',
		//['socketFactory', '$location', 'Base64', function (socketFactory, $location, Base64) {
		['socketFactory', '$location', '$base64',  function (socketFactory, $location, $base64) {
			var myIoSocket = io.connect('192.168.1.91:8003');

			// var absolute_url = $location.absUrl();
			// var first_part = absolute_url.substring(0, 7);
            //
			// var myIoSocket = null;
            //
			// // get app config.
			// var value = angular.element('#app_config').val();
			// var app_config = {};
			// if(value !=null) {
			// 	value = $base64.decode(value);
			// 	app_config = JSON.parse(value);
			// }else {
			// 	app_config.live_server = 'http://192.168.1.91:8003';
			// }
			// myIoSocket = io.connect(app_config.live_server);

			var mySocket = socketFactory({
				ioSocket: myIoSocket
			});

			return mySocket;
		}]);

	function base64_encode(input){
		var keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
		var output = "";
		var chr1, chr2, chr3 = "";
		var enc1, enc2, enc3, enc4 = "";
		var i = 0;

		do {
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}

			output = output +
					keyStr.charAt(enc1) +
					keyStr.charAt(enc2) +
					keyStr.charAt(enc3) +
					keyStr.charAt(enc4);
			chr1 = chr2 = chr3 = "";
			enc1 = enc2 = enc3 = enc4 = "";
		} while (i < input.length);

		return output;
	}


	return angularAMD.bootstrap(app);
});
