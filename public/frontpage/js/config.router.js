'use strict';

/**
 * Config for the router
 */
angular.module('app')
    .run(
        [          '$rootScope', '$state', '$stateParams', 'AuthService',
            function ($rootScope,   $state,   $stateParams, AuthService) {
                $rootScope.$state = $state;
                $rootScope.$stateParams = $stateParams;

                //$rootScope.$on('$locationChangeStart', function (event, next, current) {
                //    // redirect to login page if not logged in
                //    if ($location.path() !== '/login' && !$rootScope.globals.currentUser) {
                //        $location.path('/login');
                //    }
                //});

                $rootScope.$on('$stateChangeStart', function(event, toState, toStateParams, fromState, fromStateParams) {
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

            }
        ]
    )
    .config(
        [          '$stateProvider', '$urlRouterProvider', 'JQ_CONFIG', 'MODULE_CONFIG',
            function ($stateProvider,   $urlRouterProvider, JQ_CONFIG, MODULE_CONFIG) {

                var layout = "tpl/blocks/app.layout.html";
                $urlRouterProvider
                    .otherwise('/access/signin');

                $stateProvider
                    .state('app', {
                        abstract: true,
                        url: '/app',
                        templateUrl: layout
                    })
                    //.state('app.mytask', {
                    //    url: '/mytask',
                    //    template: '<div ui-view></div>',
                    //})
                    .state('access', {
                        url: '/access',
                        template: '<div ui-view class="fade-in-right-big smooth"></div>'
                    })
                    .state('access.signin', {
                        url: '/signin',
                        templateUrl: 'tpl/page_signin.html',
                        resolve: load( [
                            'js/controllers/signin.js',
                        ] )
                    })
                    .state('access.signup', {
                        url: '/signup',
                        templateUrl: 'tpl/page_signup.html',
                        resolve: load( ['js/controllers/signup.js'] )
                    })
                    .state('access.forgotpwd', {
                        url: '/forgotpwd',
                        templateUrl: 'tpl/page_forgotpwd.html',
                        resolve: load( ['js/controllers/forgot.js'] )
                    })
                    .state('access.changepass', {
                        url: '/changepass',
                        templateUrl: 'tpl/page_changepass.html',
                        resolve: load( [
                            'js/controllers/profile/changepass.js',
                        ] )
                    })
                    .state('access.404', {
                        url: '/404',
                        templateUrl: 'tpl/page_404.html'
                    })
                    .state('access.guestanswer', {
                        url: '/guestanswer',
                        templateUrl: 'tpl/guestsurvey/guest_answer.html',
                        resolve: load( [
                            'toaster',
                            'js/controllers/guestsurvey/guest_answer.controller.js',
                        ] )
                    })
                    .state('access.complaint', {
                        url: '/complaint/:client_id',
                        templateUrl: 'tpl/complaint/complaint_post.html',
                        resolve: load( [
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker',
                            'js/services/guestservice.service.js',
                            'js/controllers/complaint/complaint_post.controller.js',
                        ] )
                    })
                    .state('access.helpdesk', {
                        url: '/helpdesk/:client_id',
                        templateUrl: 'tpl/helpdesk/helpdesk.html',
                        resolve: load( [
                            'js/controllers/helpdesk/helpdesk.controller.js',
                        ] )
                    })
                    .state('access.it', {
                        url: '/it/:client_id',
                        templateUrl: 'tpl/it/it_post.html',
                        resolve: load( [
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker',
                            'js/services/guestservice.service.js',
                            'js/controllers/it/it_post.controller.js',
                        ] )
                    })
                    .state('access.eng', {
                        url: '/repair/:client_id',
                        templateUrl: 'tpl/mytask/eng/eng_post.html',
                        resolve: load([
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'smart-table',
                            'js/controllers/modal/login_modal.controller.js',
                            'js/controllers/mytask/eng/eng_post.controller.js',
                        ])
                    })

                    .state('access.engtenant', {
                        url: '/repair_tenant/:client_id',
                        templateUrl: 'tpl/mytask/eng/eng_tenantpost.html',
                        resolve: load([
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'smart-table',

                            'js/controllers/mytask/eng/eng_tenantpost.controller.js',
                        ])
                    })

                    .state('access.enghelpdesk', {
                        url: '/eng/:client_id',
                        templateUrl: 'tpl/mytask/eng/eng_post.html',
                        resolve: load([
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'smart-table',
                            'js/controllers/modal/login_modal.controller.js',
                            'js/controllers/mytask/eng/eng_post.controller.js',
                        ])
                    })
                    /*
                    .state('access.enghelpdesk', {
                        url: '/eng/:client_id',
                        templateUrl: 'tpl/mytask/eng/eng_helpdesk.html',
                        resolve: load([
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'smart-table',

                            'js/controllers/mytask/eng/eng_helpdesk.controller.js',
                        ])
                    })
                    */
                    .state('access.engineering', {
                        url: '/engineering/:client_id',
                        templateUrl: 'tpl/engineering/engineering_post.html',
                        resolve: load( [
                            'toaster', 'ngFileUpload', 'ui.bootstrap.datetimepicker',
                            'js/services/guestservice.service.js',
                            'js/controllers/engineering/engineering_post.controller.js',
                        ] )
                    })
                    .state('app.mytask', {
                        url: '/mytask',
                        template: '<div ui-view></div>',
                    })
                    .state('app.mytask.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/mytask/dashboard.html',
                        resolve: load([
                            'toaster', 'smart-table','nvd3', 'ui.jq',
                            'js/controllers/mytask/dashboard.controller.js',
                            'js/services/imageutils.service.js',
                        ])
                    })
                    .state('app.mytask.guestservice', {
                        url: '/guestservice',
                        templateUrl: 'tpl/mytask/guest/guest.html',
                        resolve: load([
                            'js/controllers/mytask/guest/myguestservice.controller.js',
                            'js/controllers/guestservice/tickets/guestrequest_edit.controller.js',
                            'js/controllers/guestservice/tickets/departmentrequest_edit.controller.js',
                            'js/controllers/mytask/guest/mymanagedtask_edit.controller.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.jq', 'ngAside',
                             'moment'   ])
                    })
                    .state('app.mytask.complaint', {
                        url: '/complaint',
                        templateUrl: 'tpl/mytask/complaint/complaint.html',
                        resolve: load([
                            'js/controllers/mytask/complaint/mysubcomplaint.controller.js',
                            'js/controllers/complaint/subcomplaint_edit.controller.js',
                            'js/controllers/complaint/myapproval_edit.controller.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'ui.jq', 'ngAside', 'ngFileUpload', 'moment', 'angular-highlight', 'ngTagsInput'  ])
                    })
                    .state('app.mytask.it', {
                        url: '/it',
                        templateUrl: 'tpl/it/it.html',
                        resolve: load([
                            'js/controllers/it/myissue.controller.js',
                            'js/controllers/it/issue_edit.controller.js',
                            'js/controllers/it/issue_create.controller.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'ui.jq', 'ngAside', 'ngFileUpload', 'moment', 'angular-highlight','rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'js/services/date.service.js','ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.chart', 'ui.jq', 'ngAside', 'ngTagsInput',
                                'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns'])
                    })
                    .state('app.mytask.eng', {
                        url: '/eng',
                        templateUrl: 'tpl/mytask/eng/eng.html',
                        resolve: load([
                            'js/controllers/mytask/eng/eng.controller.js',
                            'js/controllers/mytask/eng/eng_edit.controller.js',
                            'js/controllers/mytask/eng/eng_create.controller.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'ui.jq', 'ngAside', 'ngFileUpload', 'moment', 'angular-highlight', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'js/services/date.service.js', 'ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.chart', 'ui.jq', 'ngAside',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns'])
                    })
                    .state('app.guestservice', {
                        url: '/guestservice',
                        template: '<div ui-view></div>',
                        resolve: load(['js/controllers/guestservice/guestservice.controller.js'])
                    })
                    .state('app.guestservice.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/guestservice/dashboard.html',
                        resolve: load([
                            'smart-table', 'toaster', 'moment', 'nvd3','ui.jq', 'angularLazyImg',
                            'js/controllers/guestservice/dashboard.controller.js',
                            'js/services/imageutils.service.js',
                            '/libs/svgtoimage/canvas-toBlob.js',
                            '/libs/svgtoimage/FileSaver.min.js',
                            ])
                    })
                    .state('app.guestservice.ticket', {
                        url: '/ticket/:ticket_id',
                        templateUrl: 'tpl/guestservice/ticket.html',
                        //params:      ['ticket_id'],
                        resolve: load(['js/controllers/guestservice/guestservice.controller.js',
                            'js/controllers/guestservice/tickets/guestrequest_new.controller.js',
                            'js/controllers/guestservice/tickets/guestrequest_edit.controller.js',
                            'js/controllers/guestservice/tickets/departmentrequest_new.controller.js',
                            'js/controllers/guestservice/tickets/departmentrequest_edit.controller.js',
                            'js/controllers/complaint/complaint_duty_create.controller.js',
                            'js/controllers/complaint/complaint_duty_validate.controller.js',
                            'js/controllers/guestservice/tickets/managedtask_new.controller.js',
                            'js/controllers/guestservice/tickets/managedtask_edit.controller.js',
                            'js/controllers/guestservice/chat/chat_session.controller.js',
                            'js/services/guestservice.service.js', 'InlineTextEditor',
                            'js/services/translate.service.js', 'ngTagsInput', 'cfp.hotkeys', 'angular-duration-format', 'ngFileUpload',
			                'smart-table', 'toaster', 'moment', 'ngAside', 'luegg.directives',
                            'ui.bootstrap.datetimepicker', 'angularResizable',
                        ])
                    })

                    .state('app.guestservice.facility', {
                        url: '/facility',
                        templateUrl: 'tpl/guestservice/guest_facility.html',
                        resolve: load(['js/controllers/guestservice/guest_facility.controller.js',
                            'js/controllers/guestservice/guest_facility/guest_facility_create.controller.js',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js', 'InlineTextEditor',
                            'js/services/translate.service.js', 'ngTagsInput', 'cfp.hotkeys', 'angular-duration-format', 'ngFileUpload',
			                'smart-table', 'toaster', 'moment', 'ngAside', 'luegg.directives', 'ui.bootstrap.contextMenu',
                            'ui.bootstrap.datetimepicker', 'angularResizable', 'ui.jq', 'ui.select',
                        ])
                    })
                    .state('app.guestservice.reservation', {
                        url: '/reservation',
                        templateUrl: 'tpl/guestservice/reservation.html',
                        resolve: load([
                            'js/controllers/guestservice/reservation.controller.js',
                            'js/controllers/guestservice/reservation/reservation_dashboard.controller.js',
                            'js/controllers/guestservice/reservation/reservation_new.controller.js',
                            'js/controllers/guestservice/reservation/reservation_edit.controller.js',
                            'js/services/guestservice.service.js',
                            'js/services/date.service.js',
                            'smart-table', 'toaster',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns', 'moment'   ])
                    })
                    .state('app.guestservice.shift', {
                        url: '/shifts',
                        templateUrl: 'tpl/guestservice/shift.html',
                        resolve: load(['moment','fullcalendar','ui.calendar', 'toaster',
                            'js/controllers/guestservice/shift.controller.js',
                            'js/services/date.service.js',
                        ])
                    })
                    .state('app.guestservice.alarm', {
                        url: '/alarm',
                        templateUrl: 'tpl/guestservice/alarm.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table','rgkevin.datetimeRangePicker', 'ui.jq', 'ngAside','ui.chart', 'nvd3',
                            'js/controllers/guestservice/alarm.controller.js',
                            'js/controllers/guestservice/alarms/alarm_logs.controller.js',
                            'js/controllers/guestservice/alarms/alarm_main.controller.js'
                        ])
                    })
                    .state('app.guestservice.guestinfo', {
                        url: '/guestinfo',
                        templateUrl: 'tpl/guestservice/guest_info.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'ui.bootstrap.datetimepicker', 'ui.jq',
                            'js/controllers/guestservice/guestinfo.controller.js'
                        ])
                    })
                    .state('app.guestservice.guestreservation', {
                        url: '/guestreservation',
                        templateUrl: 'tpl/guestservice/guest_reservation/guest_reservation.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'ui.bootstrap.datetimepicker', 'ui.jq',
                            'js/controllers/guestservice/guestreservation.controller.js'
                        ])
                    })
                    .state('app.guestservice.guest_sms_template', {
                        url: '/guest_sms_template',
                        templateUrl: 'tpl/guestservice/guest_sms_template.html',
                        resolve: load([
                            'toaster',
                            'js/controllers/guestservice/guest_sms_template.controller.js'
                        ])
                    })
                    .state('app.guestservice.wakeup', {
                        url: '/wakeup',
                        templateUrl: 'tpl/guestservice/wakeup.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'ui.bootstrap.datetimepicker', 'moment',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns','ui.jq',
                            'js/controllers/guestservice/wakeup.controller.js',
                            'js/controllers/guestservice/wakeup/wakeup_create.controller.js',
                            'js/controllers/guestservice/wakeup/wakeup_edit.controller.js',
                            'js/services/guestservice.service.js',
                        ])
                    })
                    .state('app.guestservice.chat', {
                        url: '/chat',
                        templateUrl: 'tpl/guestservice/chat/chatview.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'luegg.directives', 'ui.jq',
                            'ui.bootstrap.datetimepicker', 'bootstrapLightbox', 'EXIF', 'ngFileUpload',
                            'js/recorder.js',
                            'js/services/guestservice.service.js',
                            'js/controllers/guestservice/chat/chatview.controller.js',
                            'js/controllers/guestservice/chat/guestchat.controller.js',
                            'js/controllers/guestservice/chat/chat_recorder.controller.js',
                            'js/controllers/guestservice/chat/chat_file_upload.controller.js',
                            'js/controllers/guestservice/chat/agentchat.controller.js',
                            'js/controllers/guestservice/chat/groupchat.controller.js',
                            'js/controllers/guestservice/chat/chatsetting.controller.js',
                            'js/controllers/guestservice/tickets/guestrequest_new.controller.js',
                            'js/controllers/guestservice/chat/preset_messages.controller.js',
                            'js/controllers/guestservice/chat/send_email.controller.js',

                            'js/services/translate.service.js',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js',
                        ])
                    })
                    .state('app.guestservice.promotion', {
                        url: '/promotion',
                        templateUrl: 'tpl/guestservice/promotion.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'luegg.directives', 'ui.jq',
                            'ui.bootstrap.datetimepicker', 'bootstrapLightbox', 'EXIF', 'ngFileUpload','ngQuill','dndLists',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns',
                            'js/services/guestservice.service.js','js/services/text_html.service.js','js/services/text_html_esc.service.js',
                            'js/controllers/guestservice/promotion.controller.js',
                            'js/controllers/guestservice/promotion/promotion_create.controller.js',
                            'js/controllers/guestservice/promotion/promotion_edit.controller.js',
                        ])
                    })
                    .state('app.guestservice.chat_templates', {
                        url: '/chat-templates',
                        templateUrl: 'tpl/guestservice/chat_template/chat_template.html',
                        resolve: load([
                            'js/controllers/guestservice/chat_template/chat_template.controller.js',
                            'js/controllers/guestservice/chat_template/add_template.controller.js',
                            'js/services/guestservice.service.js',
                            'js/services/translate.service.js', 'ngTagsInput', 'cfp.hotkeys', 'angular-duration-format',
                            'smart-table', 'toaster', 'moment', 'ngAside', 'luegg.directives',
                            'angularResizable',
                        ])
                    })
                    .state('app.guestservice.settings', {
                        url: '/settings',
                        templateUrl:'tpl/guestservice/settings/dashboard.html',
                        resolve: load([
                            'js/controllers/guestservice/settings/dashboard.controller.js',

                            'js/controllers/guestservice/settings/task_group.controller.js',
                            'js/controllers/guestservice/settings/task_group_create.controller.js',
                            'js/controllers/guestservice/settings/task_group_edit.controller.js',

                            'js/controllers/guestservice/settings/task.controller.js',
                            'js/controllers/guestservice/settings/task_create.controller.js',
                            'js/controllers/guestservice/settings/task_edit.controller.js',

                            'js/controllers/guestservice/settings/location_group.controller.js',
                            'js/controllers/guestservice/settings/location_group_create.controller.js',
                            'js/controllers/guestservice/settings/location_group_edit.controller.js',

                            'js/controllers/guestservice/settings/device.controller.js',
                            'js/controllers/guestservice/settings/device_create.controller.js',
                            'js/controllers/guestservice/settings/device_edit.controller.js',

                            'js/controllers/guestservice/settings/user.controller.js',
                            'js/controllers/guestservice/settings/user_create.controller.js',
                            'js/controllers/guestservice/settings/user_edit.controller.js',

                            'js/services/guestservice.service.js',
                            'angularjs-dropdown-multiselect',
                            'ngTagsInput', 'toaster', 'moment', 'smart-table', 'ui.jq','dndLists',
                        ])
                    })
                    .state('app.alarm', {
                        url: '/alarm',
                        template: '<div ui-view></div>'
                    })
                    .state('app.alarm.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/alarm/main_dashboard.html',
                        resolve: load(['toaster', 'ngTagsInput','smart-table', 'ui.jq','dndLists',
                            'js/controllers/alarm/main_dashboard.controller.js',
                            'js/controllers/alarm/main_dashboardsub.controller.js',
                            'js/controllers/alarm/active.controller.js',
                            'js/controllers/alarm/log.controller.js',
                            ])
                    })
                    .state('app.alarm.setting', {
                        url: '/setting',
                        templateUrl: 'tpl/alarm/setting.html',
                        resolve: load(['toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ngTagsInput', 'dndLists', 'colorpicker',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect','ui.select','ngDragDrop',
                            'js/controllers/alarm/setting.controller.js',
                            'js/controllers/alarm/group.controller.js',
                            'js/controllers/alarm/dashboard.controller.js',
                            'js/controllers/alarm/alarm_setting.controller.js',
                            'js/controllers/alarm/alarm_alarms.controller.js',
                            ])
                    })
                    .state('app.complaintmgnt', {
                        url: '/complaintmgnt',
                        template: '<div ui-view></div>',
                    })
                    .state('app.complaintmgnt.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/complaint/dashboard.html',
                        resolve: load([
                            'toaster', 'smart-table','nvd3', 'ui.jq',
                            'js/controllers/complaint/dashboard.controller.js',
                            'js/services/imageutils.service.js',
                        ])
                    })
                    .state('app.complaint', {
                        url: '/complaint',
                        template: '<div ui-view></div>',
                         resolve: load([
                           'angular-highlight',
                        ])
                    })
                    .state('app.complaint.complaint', {
                        url: '/complaint',
                        templateUrl: 'tpl/complaint/complaint_duty.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect',
                            'js/services/guestservice.service.js', 'InlineTextEditor',
                            'js/controllers/complaint/complaint_duty.controller.js',
                            'js/controllers/complaint/complaint_duty_create.controller.js',
                            'js/controllers/complaint/complaint_duty_validate.controller.js',
                        ])
                    })
					 .state('app.complaint.gr_log', {
                        url: '/gr_log',
                        templateUrl: 'tpl/complaint/complaint_gr_log.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect',
                            'js/services/guestservice.service.js',
                            'js/controllers/complaint/complaint_gr_log.controller.js',
                            'js/controllers/complaint/complaint_gr_log_create.controller.js',
                            'js/controllers/complaint/complaint_gr_log_validate.controller.js'
                        ])
                    })

                    .state('app.complaint.create_mod_checklist', {
                        url: '/create_mod_checklist',
                        templateUrl: 'tpl/complaint/complaint_create_mod_checklist.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect',
                            'js/services/guestservice.service.js',
                            'js/controllers/complaint/checklist_mod_create.controller.js'

                        ])
                    })
                    .state('app.complaint.mod_checklist', {
                        url: '/mod_checklist',
                        templateUrl: 'tpl/complaint/complaint_mod_checklist.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect',
                            'js/services/guestservice.service.js',
                            'js/controllers/complaint/checklist_mod.controller.js',


                        ])
                    })

                    .state('app.complaint.briefing_mng', {

                        url: '/briefingmng',
                        templateUrl: 'tpl/complaint/complaint_brief_manager.html',
                        resolve: load([
                            'toaster', 'dndLists', 'ngAside', 'smart-table', 'ui.jq', 'ui.bootstrap.datetimepicker', 'duScroll', 'ngFileUpload',
                            'js/services/guestservice.service.js',
                            'js/controllers/complaint/complaint_brief_manager.controller.js',
                            'js/controllers/complaint/complaint_duty_validate.controller.js'
                        ])
                    })
                    .state('app.complaint.advance_briefing', {
                        url: '/advance_briefing',
                        templateUrl: 'tpl/complaint/complaint_advance_briefing.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'disableAll', 'ui.utils.masks',
                            'js/controllers/complaint/complaint_advance_briefing.controller.js',
                        ])
                    })
                    .state('app.complaint.briefing_view', {
                        url: '/briefingview',
                        templateUrl: 'tpl/complaint/complaint_brief_view.html',
                        resolve: load([
                            'toaster', 'smart-table','nvd3', 'ui.jq',
                            'js/services/guestservice.service.js',
                            'js/controllers/complaint/complaint_briefing_view.controller.js'
                        ])
                    })

                    .state('app.mod', {
                        template: '<div ui-view></div>',
                        resolve: load([

                        ])
                    })


                    .state('app.mod.dashboard', {
                        url: '/mod/dashboard',
                        templateUrl: 'tpl/mod/dashboard.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ngTagsInput', 'ui.jq','ngFileUpload',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect', 'moment',
                            'ui.bootstrap.datetimepicker',
                            'js/controllers/mod/mod.controller.js',
                            'js/controllers/mod/mng_checklist.controller.js',
                            'js/controllers/mod/checklist_task.controller.js',
                            'js/controllers/mod/checklist_result.controller.js',
                        ])
                    })

                    .state('app.marketing', {
                        url: '/marketing',
                        template: '<div ui-view></div>',
                         resolve: load([

                        ])
                    })
                    .state('app.marketing.campaign', {
                        url: '/campaign',
                        templateUrl: 'tpl/marketing/campaign/campaign_manager.html',
                        resolve: load([
                            'toaster', 'smart-table', 'ui.jq', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'ngQuill', 'ui.utils.masks',
                            'js/controllers/marketing/campaign/campaign_manager.controller.js',
                            'js/controllers/marketing/campaign/campaign_create.controller.js',
                            'js/controllers/marketing/campaign/campaign_edit.controller.js'
                        ])
                    })
                    .state('app.complaint.compensation_template', {
                        url: '/compensation_template',
                        templateUrl: 'tpl/complaint/compensation_template.html',
                        resolve: load([
                            'toaster', 'ngQuill',
                            'js/controllers/complaint/compensation_template.controller.js'
                        ])
                    })
                    .state('app.callaccounting', {
                        url: '/callaccounting',
                        template: '<div ui-view></div>',
                        resolve: load(['toaster', 'moment', 'smart-table'])
                    })
                    .state('app.callaccounting.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/callaccounting/callaccounting_dashboard.html',
                        resolve: load([
                            'js/controllers/callaccount/callaccounting_dashboard.controller.js',
                            'moment', 'ui.jq', 'ui.chart', 'nvd3',
                        ])
                    })
                    .state('app.callaccounting.livedata', {
                        url: '/livedata',
                        templateUrl: 'tpl/callaccounting/livedata.html',
                        resolve: load(['js/controllers/callaccount/livecall.controller.js',
                            'js/controllers/callaccount/guest_call.controller.js',
                            'js/controllers/callaccount/admin_call.controller.js',
							'js/controllers/callaccount/bc_call.controller.js',
                            'js/controllers/callaccount/manual_post.controller.js',
                        ])
                    })
                    .state('app.callaccounting.mycalls', {
                        url: '/mycalls',
                        templateUrl: 'tpl/callaccounting/mycalls.html',
                        resolve: load(['js/controllers/callaccount/mycall_classification.controller.js',
                           'js/controllers/callaccount/mycalls_dashboard.controller.js',
                            'js/controllers/callaccount/mycalls.controller.js', 'js/controllers/callaccount/mycall_mobile_classification.controller.js',
                            'js/services/date.service.js',
                            'smart-table', 'toaster', 'rgkevin.datetimeRangePicker', 'ui.jq', 'ngAside', 'moment','ui.chart', 'nvd3',
                        ])
                    })
                    .state('app.callaccounting.myapproval', {
                        url: '/approval',
                        templateUrl: 'tpl/callaccounting/approvals.html',
                        resolve: load(['js/controllers/callaccount/approvals.controller.js',
                            'js/controllers/callaccount/mycall_approval.controller.js',
                            'js/controllers/callaccount/mymobile_approval.controller.js',
                            'js/services/date.service.js',
                            'smart-table', 'toaster'
                        ])
                    })
                    .state('app.callaccounting.finance', {
                        url: '/finance',
                        templateUrl: 'tpl/callaccounting/finance.html',
                        resolve: load(['js/controllers/callaccount/mycall_finance.controller.js',
                            'js/controllers/callaccount/finance.controller.js',
                            'js/controllers/callaccount/mycall_mobile_finance.controller.js',
                            'js/services/date.service.js',
                            'smart-table', 'toaster'
                        ])
                    })
                    .state('app.callaccounting.mobiletrack', {
                        url: '/mobiletrack',
                        templateUrl: 'tpl/callaccounting/mobiletrack.html',
                        resolve: load([
                            'js/services/date.service.js', 'ngFileUpload',
                            'smart-table', 'toaster','ngTagsInput', 'toaster', 'moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'angularjs-dropdown-multiselect', 'ui.jq', 'js/controllers/callaccount/mobiletrack.controller.js'
                        ])
                    })
                    .state('app.minibar', {
                        url: '/minibar',
                        template: '<div ui-view></div>',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ui.jq'])
                    })
                    .state('app.minibar.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/minibar/dashboard.html',
                        resolve: load(['nvd3', 'ngTagsInput','toaster', 'moment', 'smart-table', 'angular-duration-format', 'pdfmake', 'ui.jq', 'html2canvas', 'js/controllers/minibar/dashboard.controller.js'])
                    })
                    .state('app.minibar.logs', {
                        url: '/logs',
                        templateUrl: 'tpl/minibar/logs.html',
                        resolve: load(['js/controllers/minibar/logs.controller.js'])
                    })
                    .state('app.minibar.by_guest', {
                        url:'/guest',
                        templateUrl:'tpl/minibar/guest.html',
                        resolve: load(['js/controllers/minibar/guest.controller.js'])
                    })
					.state('app.minibar.stock', {
                        url:'/stocks',
                        templateUrl:'tpl/minibar/stocks.html',
                        resolve: load(['js/controllers/minibar/stock.controller.js'])
                    })
                    .state('app.minibar.roster', {
                        url: '/roaster',
                        templateUrl: 'tpl/minibar/roster.html',
                        resolve: load([
                            'ngTagsInput', 'ngListSelect','angularjs-dropdown-multiselect',
                            'js/controllers/minibar/roster.controller.js',
                            'js/controllers/minibar/roster_new.controller.js',
                            'js/controllers/minibar/roster_edit.controller.js',
                            'js/controllers/minibar/roster_list.controller.js',
                            'dndLists'
                        ])
                    })
                    .state('app.minibar.post', {
                        url: '/post',
                        templateUrl: 'tpl/minibar/post.html',
                        resolve: load([
                            'ngTagsInput', 'ngListSelect','angularjs-dropdown-multiselect',
                            'js/controllers/minibar/post.controller.js',
                            'dndLists'
                        ])
                    })
                    .state('app.housekeeping', {
                        url: '/housekeeping',
                        template: '<div ui-view></div>',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ui.jq',
                        ])
                    })
                    .state('app.housekeeping.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/housekeeping/dashboard.html',
                        resolve: load(['js/controllers/housekeeping/dashboard.controller.js'])
                    })
                    .state('app.housekeeping.logs', {
                        url: '/logs',
                        templateUrl: 'tpl/housekeeping/logs.html',
                        resolve: load(['js/controllers/housekeeping/logs.controller.js'])
                    })
                    .state('app.housekeeping.workflow', {
                        url: '/workflow',
                        templateUrl: 'tpl/housekeeping/workflow.html',
                        resolve: load([
                            'ngTagsInput',
                            'js/controllers/housekeeping/workflow.controller.js',
                            'js/controllers/housekeeping/notification.controller.js',
                            'js/controllers/housekeeping/checklist.controller.js',
                            'js/controllers/housekeeping/public_area.controller.js',
                            'js/controllers/housekeeping/rules.controller.js',
                            'js/controllers/housekeeping/schedule.controller.js',
                            'js/controllers/housekeeping/linen_setting.controller.js',
                            'js/services/guestservice.service.js', 'dndLists',
                        ])
                    })
                    .state('app.housekeeping.realtime', {
                        url: '/realtime',
                        templateUrl: 'tpl/housekeeping/real_time.html',
                        resolve: load([
                            'ngTagsInput',
                            'js/controllers/housekeeping/real_time.controller.js',
                        ])
                    })
                    .state('app.housekeeping.assignment', {
                        url: '/roomassignment',
                        templateUrl: 'tpl/housekeeping/assignment.html',
                        resolve: load([
                            'ngTagsInput', 'ngListSelect',
                            'js/controllers/housekeeping/assignment.controller.js',
                            'dndLists',
                        ])
                    })
                    .state('app.housekeeping.roster', {
                        url: '/roster',
                        templateUrl: 'tpl/housekeeping/roster.html',
                        resolve: load([
                            'ngTagsInput', 'ngListSelect','angularjs-dropdown-multiselect',
                            'js/controllers/housekeeping/roster.controller.js',
                            'js/controllers/housekeeping/roster_new.controller.js',
                            'js/controllers/housekeeping/roster_edit.controller.js',
                            'js/controllers/housekeeping/roster_list.controller.js',
                            'dndLists',
                        ])
                    })
                    .state('app.housekeeping.turndown_assign', {
                        url: '/turndown_assign',
                        templateUrl: 'tpl/housekeeping/turndown_assign.html',
                        resolve: load([
                            'ngTagsInput', 'ngListSelect',
                            'js/controllers/housekeeping/turndown_assign.controller.js',
                            'dndLists',
                        ])
                    })
                    .state('app.calldistribution', {
                        url: '/calldistribution',
                        template: '<div ui-view></div>',
                    })
                    .state('app.calldistribution.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/calldistribution/dashboard.html',
                        resolve: load(['js/controllers/guestservice/guestservice.controller.js', 'ui.jq'])
                    }).state('app.calls', {
                        url: '/calls',
                        template: '<div ui-view></div>',
                    })
                    .state('app.calls.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/calls/dashboard.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'angular-duration-format', 'pdfmake', 'ui.jq', 'nvd3', 'html2canvas', 'ngTagsInput',
                            'js/controllers/calllogger/dashboard.controller.js'])
                    })
                    .state('app.calls.agentstatus', {
                        url: '/agentstatus',
                        templateUrl: 'tpl/calls/call_agentstatus.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'angular-duration-format', 'ngAside',
                            'js/controllers/calllogger/call_agentstatus.controller.js',
                            'js/controllers/calllogger/call_imageload.controller.js',
                            'js/services/guestservice.service.js',
                            'js/services/translate.service.js', 'ngFileUpload',
                            'js/controllers/guestservice/tickets/guestrequest_new.controller.js',
                            'js/controllers/guestservice/tickets/departmentrequest_new.controller.js',
                            'js/controllers/guestservice/tickets/guestrequest_edit.controller.js',
                            'js/controllers/guestservice/tickets/departmentrequest_edit.controller.js',
                            'js/controllers/guestservice/wakeup/wakeup_create.controller.js',
                            'js/controllers/guestservice/guestinfo.controller.js',
                            'ui.bootstrap.datetimepicker', 'angularResizable',
                            'js/services/country.service.js','cfp.hotkeys'])
                    })
                    .state('app.calls.managecall', {
                        url: '/managecall',
                        templateUrl: 'tpl/calls/call_managecall.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'angular-duration-format',
                            'js/controllers/calllogger/call_managecall.controller.js',
                            'js/services/country.service.js',])
                    })
                    .state('app.calls.logger', {
                        url: '/logger',
                        templateUrl: 'tpl/calls/call_logger.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ui.jq',
                            'js/controllers/calllogger/call_logger.controller.js',
                            'js/controllers/calllogger/log_detail.controller.js',
                        ])
                    })
                    .state('app.calls.aa', {
                        url: '/aa',
                        templateUrl: 'tpl/calls/call_aa.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ui.jq',
                            'js/controllers/calllogger/call_aa.controller.js',
                            'js/controllers/calllogger/log_detail.controller.js',
                        ])
                    })

                    .state('app.calls.settings', {
                        url: '/settings',
                        templateUrl: 'tpl/calls/call_settings.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ngTagsInput',
                            'js/controllers/calllogger/call_skills.controller.js',
                            'js/controllers/calllogger/call_threshold.controller.js',
                        ])
                    })

                    .state('app.calls.timings', {
                        url: '/timings',
                        templateUrl: 'tpl/calls/call_timings.html',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ngTagsInput',
                            'js/controllers/calllogger/call_timings.controller.js',
                        ])
                    })

                    .state('app.engineering', {
                        url: '/engineering',
                        template: '<div ui-view></div>',
                    })
                    .state('app.engineering.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/engineering/dashboard.html',
                        resolve: load(['toaster', 'smart-table','nvd3', 'ui.jq','nvd3',
                                    'js/controllers/guestservice/guestservice.controller.js',
                                    'js/controllers/engineering/dashboard.controller.js' ])
                    })
                    .state('app.engineering.repair_request', {
                        url: '/repair_request',
                        templateUrl: 'tpl/engineering/repair_request/repair_request.html',
                        resolve: load(['moment', 'smart-table', 'ui.bootstrap.datetimepicker',
                            'js/controllers/engineering/repair_request.controller.js',
                            'js/controllers/engineering/repair_request_create.controller.js',
                            'js/controllers/engineering/repair_request_edit.controller.js',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js',  'ngTagsInput', 'ui.jq',
                            'smart-table', 'toaster', 'ui.bootstrap.contextMenu',
                            'moment','ngFileUpload'
                        ])
                    })
                    .state('app.engineering.preventive', {
                        url: '/preventive',
                        templateUrl: 'tpl/engineering/preventive.html',
                        resolve: load([
                            'ngTagsInput','toaster',
                            'js/controllers/engineering/preventive.controller.js',
                            'js/controllers/engineering/preventive_maintainence.controller.js',
                            'js/controllers/engineering/checklist_eng.controller.js',
                            'js/services/guestservice.service.js', 'angularjs-dropdown-multiselect',
                            'smart-table', 'ui.jq','moment','ui.bootstrap.datetimepicker', 'ngTagsInput',
                        ])
                    })
                    .state('app.engineering.equipment', {
                        url: '/equipment',
                        templateUrl: 'tpl/engineering/equipment.html',
                        resolve: load(['moment', 'smart-table', 'ui.bootstrap.datetimepicker',
                                'js/controllers/engineering/equipment.controller.js',
                                'js/controllers/engineering/equipment_create.controller.js',
                                'js/controllers/engineering/equipment_edit.controller.js',
                                'js/controllers/engineering/equipment_detail.controller.js',
                                'js/controllers/engineering/equipment_file.controller.js',
                                'js/controllers/engineering/equipment_workorder.controller.js',
                                'js/services/guestservice.service.js',
                                'js/services/date.service.js', 'ngFileUpload',
                                'ui.jq', 'ngTagsInput',
                                'toaster'  ])
                    })
                    .state('app.engineering.part', {
                        url: '/part',
                        templateUrl: 'tpl/engineering/part.html',
                        resolve: load(['moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'js/controllers/engineering/part.controller.js',
                            'js/controllers/engineering/part_create.controller.js',
                            'js/controllers/engineering/part_edit.controller.js',
                            'js/controllers/engineering/part_detail.controller.js',
                            'js/controllers/engineering/part_workorder.controller.js',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.chart', 'ui.jq', 'ngAside',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns', 'moment'   ])
                    })
                    .state('app.engineering.workorder', {
                        url: '/workorder',
                        templateUrl: 'tpl/engineering/workorder.html',
                        resolve: load(['moment', 'smart-table', 'ui.bootstrap.datetimepicker',
                            'js/controllers/engineering/workorder.controller.js',
                            'js/controllers/engineering/workorder_create.controller.js',
                            'js/controllers/engineering/workorder_edit.controller.js',
                            'js/controllers/engineering/workorder_checklist.controller.js',
                            'js/controllers/engineering/workorder_dialog.controller.js',
                            'fullcalendar','ui.calendar',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js', 'dndLists', 'ngTagsInput',
                            'toaster', 'ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.jq',
                            'ui.select', 'ngFileUpload',
                            ])
                    })
                    .state('app.engineering.checklist', {
                        url: '/checklist',
                        templateUrl: 'tpl/engineering/checklist.html',
                        resolve: load([
                            'ngTagsInput','toaster',
                            'js/controllers/engineering/checklist_eng.controller.js',
                            'js/services/guestservice.service.js'
                        ])
                    })
                    .state('app.engineering.contract', {
                        url: '/contract',
                        templateUrl: 'tpl/engineering/contract/contract.html',
                        resolve: load(['moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'js/controllers/engineering/contract.controller.js',
                            'js/controllers/engineering/contract_create.controller.js',
                            'js/controllers/engineering/contract_edit.controller.js',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.chart', 'ui.jq', 'ngAside',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns', 'moment','ngFileUpload'
                        ])
                    })
                    .state('app.valet', {
                        url: '/valet',
                        template: '<div ui-view></div>',
                    })

                    .state('app.valet.request', {
                        url: '/request',
                        templateUrl: 'tpl/valet/valet.html',
                        resolve: load(['moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'js/controllers/valet/valet.controller.js',
                            'js/controllers/valet/valet_create.controller.js',
                            'js/controllers/valet/valet_edit.controller.js',
                            'js/controllers/valet/valet_detail.controller.js',
                            'js/controllers/valet/valet_update.controller.js',
                            'js/services/date.service.js',
                            'js/services/guestservice.service.js',
                            'smart-table', 'toaster', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.contextMenu', 'cfp.hotkeys', 'ui.chart', 'ui.jq', 'ngAside',
                            'ui.grid', 'ui.grid.edit', 'ui.grid.selection', 'ui.grid.autoResize', 'ui.grid.pagination', 'ui.grid.resizeColumns', 'ui.grid.moveColumns', 'moment'   ])
                    })


                    .state('app.guestsurvey', {
                        url: '/guestsurvey',
                        template: '<div ui-view></div>',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ui.jq'])
                    })
                    .state('app.guestsurvey.dashboard', {
                        url: '/dashboard',
                        templateUrl: 'tpl/guestsurvey/dashboard.html',
                        resolve: load(['js/controllers/guestservice/guestservice.controller.js'])
                    })
                    .state('app.guestsurvey.setting', {
                        url: '/setting',
                        templateUrl: 'tpl/guestsurvey/setting.html',
                        resolve: load([
                            'ngTagsInput',
                            'js/controllers/guestsurvey/setting.controller.js'
                        ])
                    })
                    .state('app.guestsurvey.answer', {
                        url: '/answerlist',
                        templateUrl: 'tpl/guestsurvey/answerlist.html',
                        resolve: load([
                            'ngTagsInput',
                            'js/controllers/guestsurvey/answerlist.controller.js'
                        ])
                    })
                    .state('app.reports', {
                        url: '/reports',
                        templateUrl: 'tpl/reports/dashboard.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'angularjs-dropdown-multiselect', 'ui.jq',
                            'js/controllers/report/report.controller.js',
                            'js/controllers/report/callaccount.controller.js',
                            'js/controllers/report/guestservice.controller.js',
                            'js/controllers/report/minibar.controller.js',
                            'js/controllers/report/engineering.controller.js',
                            'js/controllers/report/hskp.controller.js',
                            'js/controllers/report/callcenter.controller.js',
                            'js/controllers/report/schdule_report_create.controller.js',
                            'js/controllers/report/scheduled_report.controller.js',
                            'js/controllers/report/day_report.controller.js',
                            'js/controllers/report/wakeupcall.controller.js',
                            'js/controllers/report/callclassify.controller.js',
                            'js/controllers/report/audit.controller.js',
                             'js/controllers/report/complaints.controller.js',
                            'js/controllers/report/lnf_report.controller.js',
                        ])
                    })
                    .state('app.forms', {
                        url: '/forms',
                        template: '<div ui-view></div>',
                        resolve: load(['toaster', 'moment', 'smart-table', 'ui.jq'
                      //  templateUrl: 'tpl/forms/dashboard.html',
                       // resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                        /*    'angularjs-dropdown-multiselect', 'ui.jq',
                            'js/controllers/forms/forms.controller.js',
                            'js/controllers/forms/liftusage_form.controller.js',
                            'js/controllers/forms/permit_work.controller.js',*/

                        ])
                    })
                    .state('app.forms.hotwork_permit', {
                        url: '/hotwork_permit',
                        templateUrl: 'tpl/forms/hotworkpermit_form.html',
                        resolve: load(['ngTagsInput','toaster', 'moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'angularjs-dropdown-multiselect', 'ui.jq',
                            'js/controllers/forms/hotwork_permit.controller.js',
                            'js/controllers/forms/hotwork_validate.controller.js',

                        ])
                    })
                    .state('app.forms.liftusage_form', {
                        url: '/liftusage_form',
                        templateUrl: 'tpl/forms/liftusage_form.html',
                        resolve: load(['ngTagsInput','toaster', 'moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'angularjs-dropdown-multiselect', 'ui.jq',
                            'js/controllers/forms/liftusage_form.controller.js',
                            'js/controllers/forms/liftusage_form_validate.controller.js'
                        ])
                    })
                    .state('app.forms.permit_work', {
                        url: '/permit_work',
                        templateUrl: 'tpl/forms/permittowork_form.html',
                        resolve: load(['ngTagsInput', 'toaster', 'moment', 'smart-table', 'rgkevin.datetimeRangePicker', 'ui.bootstrap.datetimepicker',
                            'angularjs-dropdown-multiselect', 'ui.jq',
                            'js/controllers/forms/permit_work.controller.js',
                            'js/controllers/forms/permit_work_validate.controller.js'
                        ])
                    })
                    .state('app.faq', {
                        url: '/faq',
                        templateUrl: 'tpl/faq.html',
                        resolve: load( ['ngQuill', 'toaster',
                            'js/services/text_html.service.js','js/services/text_html_esc.service.js',
                            'js/controllers/faq.controller.js',
                        ] )
                    })
                    .state('app.profile', {
                        url: '/profile',
                        templateUrl: 'tpl/profile/page_profile.html',
                        resolve: load( ['ngImgCrop', 'toaster', 'ui.jq',
                            'js/controllers/profile/page_profile.controller.js',
                        ] )
                    })
                    .state('app.lost_found', {
                        url: '/lost_found',
                        templateUrl: 'tpl/lnf/lnf.html',
                        resolve: load([ 'toaster', 'smart-table', 'ui.jq', 'moment', 'ngFileUpload', 'ui.bootstrap.datetimepicker', 'ngTagsInput',
                            'ngAside', 'disableAll','angularjs-dropdown-multiselect', 'cfp.hotkeys',
                            'js/services/guestservice.service.js',
                            'js/controllers/lnf/lnf.controller.js',
                            'js/controllers/lnf/lnf_status.controller.js',
                            // 'js/controllers/lnf/lnf_create.controller.js',
                            'js/controllers/lnf/lnf_create_dialog.controller.js',
                            'js/controllers/lnf/lnf_detail.controller.js',
                            'js/controllers/lnf/lnf_edit.controller.js',
                            'js/controllers/lnf/lnf_list_inquiry.controller.js',
                            'js/controllers/lnf/lnf_list_matching.controller.js',
                        ])
                    });

                function load(srcs, callback) {
                    return {
                        deps: ['$ocLazyLoad', '$q',
                            function( $ocLazyLoad, $q ){
                                var deferred = $q.defer();
                                var promise  = false;
                                srcs = angular.isArray(srcs) ? srcs : srcs.split(/\s+/);
                                if(!promise){
                                    promise = deferred.promise;
                                }
                                angular.forEach(srcs, function(src) {
                                    promise = promise.then( function(){
                                        if(JQ_CONFIG[src]){
                                            return $ocLazyLoad.load(JQ_CONFIG[src]);
                                        }
                                        angular.forEach(MODULE_CONFIG, function(module) {
                                            if( module.name == src){
                                                name = module.name;
                                            }else{
                                                name = src;
                                            }
                                        });
                                        return $ocLazyLoad.load(name);
                                    } );
                                });
                                deferred.resolve();
                                return callback ? promise.then(function(){ return callback(); }) : promise;
                            }]
                    }
                }


            }
        ]
    );
