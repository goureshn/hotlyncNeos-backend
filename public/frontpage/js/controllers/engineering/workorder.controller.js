app.controller('WorkorderController', function ($scope, $http, $uibModal, $window, toaster, AuthService, $timeout, $httpParamSerializer, liveserver, $state, $stateParams, blockUI) {
    var MESSAGE_TITLE = 'Work Order';
    $scope.listmode = true;

    $scope.splitdiv_height = 'height: ' + ($window.innerHeight - 160) + 'px; overflow-y: auto';

    var profile = AuthService.GetCredentials();

    var staff_list = [];
    var location_list = [];
    $scope.staff_tags = [];
    $scope.location_tags = [];
    $scope.equip_tags = [];
    $scope.wrid_tags = [];

    $scope.equipment_list = [];
    $scope.location_list = [];

    if( AuthService.isValidModule('mobile.workorder.supervisor') == true )
    {
        $scope.contextMenuOptions = [
            ['Edit', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                editWorkOrder($itemScope.row);
            }],
            ['Delete', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                deleteWorkOrder($itemScope.row);
            }],
            null,
            ['CheckList', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                showChecklist($itemScope.row);
            }]
        ];
    }
    else
    {
        $scope.contextMenuOptions = [
            ['Start', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                startWorkOrder($itemScope.row);
            }],
            ['On Hold', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                holdWorkOrder($itemScope.row);
            }],
            ['Resume', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                resumeWorkOrder($itemScope.row);
            }],
            ['Finish', function ($itemScope, $event) {
                selected_ticket = $itemScope.row;
                finishWorkOrder($itemScope.row);
            }],
        ];
    }

    $scope.menuOptions = function (item) {
        if( AuthService.isValidModule('mobile.workorder.supervisor') )
        {
            return [
                ['Edit', function ($itemScope, $event) {
                    selected_ticket = $itemScope.row;
                    editWorkOrder($itemScope.row);
                }],
                ['Delete', function ($itemScope, $event) {
                    selected_ticket = $itemScope.row;
                    deleteWorkOrder($itemScope.row);
                }],
                null,
                ['CheckList', function ($itemScope, $event) {
                    selected_ticket = $itemScope.row;
                    showChecklist($itemScope.row);
                }]
            ];
        }
        else
        {
            if( item.status == 'Completed')
                return ['CheckList', function ($itemScope, $event) {
                    selected_ticket = $itemScope.row;
                    showChecklist($itemScope.row);
                }];
            else if( item.staff_status == 'Not Assigned' )
                return [];
            else if( item.staff_status == 'Pending' )
            {
                return [
                    ['Start', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        startWorkOrder($itemScope.row);
                    }],
                    null,
                    ['CheckList', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        showChecklist($itemScope.row);
                    }]
                ];
            }
            else if( item.staff_status == 'In Progress')
            {
                return [
                    ['On Hold', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        holdWorkOrder($itemScope.row);
                    }],
                    ['Finish', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        finishWorkOrder($itemScope.row);
                    }],
                    null,
                    ['CheckList', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        showChecklist($itemScope.row);
                    }]
                ];
            }
            else if( item.staff_status == 'On Hold')
            {
                return [
                    ['Resume', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        resumeWorkOrder($itemScope.row);
                    }],
                    null,
                    ['CheckList', function ($itemScope, $event) {
                        selected_ticket = $itemScope.row;
                        showChecklist($itemScope.row);
                    }],
                ];
            }
            else
                return [];
        }
    };

    function getInitDataFromStorage(isInit = false) {
        let tempTaskData = window.localStorage.getItem('temp_task_request_data');
        if (tempTaskData != undefined && tempTaskData != null) {
            if (isInit == false) {
                window.localStorage.removeItem('temp_task_request_data');
            }

            let realData = JSON.parse(tempTaskData);

            if (isInit == true) {
                $scope.staff_tags = realData.staff_tags;
            } else {
                if (realData.staff_id) {
                    $scope.staff_tags = staff_list.filter(item => {
                        if (item.id == realData.staff_id) {
                            return true;
                        }

                        return false;
                    })
                }
            }
            $scope.status = realData.status;
            $scope.daterange = realData.daterange;
            $scope.dateRangeOption = realData.dateRangeOption;
        }
    }


    var selected_ticket = undefined;

    function initData()
    {
        $scope.staff_selected = undefined;

        // filter

        // staff list
        $http.get('/list/userlist?&client_id=' + profile.client_id)
            .then(function(response){
                staff_list = response.data;
                getInitDataFromStorage();
            });

        // location list
        $scope.location_selected = undefined;
        $http.get('/list/locationlist?&property_id=' + profile.property_id)
            .then(function(response){
                location_list = response.data;
                $scope.location_list = location_list;
            });

        // equipment group list
        $scope.equipment_selected = undefined;
        $http.get('/frontend/eng/getequipmentorgrouplist')
            .then(function(response){
                equip_list = response.data;
            });

            // wr id list
        $http.get('/frontend/eng/getwridlist')
            .then(function(response){
                wrid_list = response.data;
            });

        // equipment list
        $http.get('/list/equipmentlist?property_id=' + profile.property_id)
            .then(function(response){
                $scope.equipment_list = response.data;
            });

        // priority list
        $scope.priority_selected = undefined;
        $scope.priority_list = ['All', 'Low', 'Medium', 'High', 'Urgent'];

        // type list
        $scope.type_selected = undefined;
        $scope.type_list = [
            {id: 0, name: 'All'},
            {id: 1, name: 'Repairs'},
            {id: 2, name: 'Preventive'},
            {id: 3, name: 'Requests'},
            {id: 4, name: 'Upgrade'},
            {id: 5, name: 'New'},
        ];

         // equipment group list
         $scope.equipment_selected = undefined;
         $http.get('/frontend/eng/getequipmentorgrouplist')
             .then(function(response){
                 equip_list = response.data;
             });
    }

    initData();

    $scope.dateRangeOption = {
        format: 'YYYY-MM-DD',
        startDate: moment().subtract(45,'d').format('YYYY-MM-DD'),
        endDate: moment().format('YYYY-MM-DD')
    };

    $scope.daterange = $scope.dateRangeOption.startDate + ' - ' + $scope.dateRangeOption.endDate;

    getInitDataFromStorage(true);

    angular.element('#dateranger').on('apply.daterangepicker', function(ev, picker) {
        $scope.daterange = picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD');
        $scope.pageChanged();
    });

    $scope.staffTagFilter = function(query) {
        return staff_list.filter(function(item) {
            return item.wholename.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.locationTagFilter = function(query) {
        return location_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.equipTagFilter = function(query) {
        return equip_list.filter(function(item) {
            return item.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.wrIDTagFilter = function(query) {
        return wrid_list.filter(function(item) {
            return item.ref_id.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    }

    $scope.onSelectEquipment = function(item, model)
    {
        $scope.equipment_selected = angular.copy(item);
        $scope.pageChanged();
    }

    $scope.onSelectPriority = function(item, model)
    {
        $scope.priority_selected = angular.copy(item);
        $scope.pageChanged();
    }

    $scope.onSelectType = function(item, model)
    {
        $scope.type_selected = angular.copy(item);
        $scope.pageChanged();
    }

    $scope.onCreateWorkorder = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_create.html',
            controller: 'WorkorderCreateController',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {
            }
        });

        modalInstance.result.then(function (data) {
            console.log();
        }, function () {

        });
    }

    $scope.onEditWorkorder = function() {
        editWorkOrder(ticket);
    }

    function editWorkOrder(ticket)
    {
        // find selected ticket
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_edit.html',
            controller: 'WorkorderEditController',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {
                workorder: function() {
                    return ticket;
                }
            }
        });

        modalInstance.result.then(function (data) {
            console.log(data);
        }, function () {

        });
    }

    function showChecklist(ticket)
    {
        // find selected ticket
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_checklist.html',
            controller: 'WorkorderChecklistController',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {
                workorder: function() {
                    return ticket;
                }
            }
        });

        modalInstance.result.then(function (data) {
            console.log(data);
        }, function () {

        });
    }


    $scope.list_view_height = 'height: ' + ($window.innerHeight - 140) + 'px; overflow-y: auto;';
    $scope.detail_view_height = 'height: ' + ($window.innerHeight - 115) + 'px; overflow-y: auto;';

    $scope.totalDisplayed = [10, 10, 10, 10];

    $scope.onLoadMore = function(num) {
        $scope.totalDisplayed[num] += 10;
    }

    $scope.pending_list = [];
    $scope.in_progress_list = [];
    $scope.on_hold_list = [];
    $scope.completed_list = [];

    $scope.workorder_name = '';
    $scope.ref_id = '';

    $scope.workorder = {};

    $scope.pageChanged = function(preserve) {

        var request = {};

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        request.dispatcher = profile.id;
        applyFilter(request);

        var url = '/frontend/eng/workorderlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            var ticketlist = response.data.datalist;

            if ($scope.status != undefined){
            if ($scope.status == 'Pending'){

                $scope.pending_list = ticketlist.filter(item => item.status == 'Pending');
                $scope.in_progress_list = [];
                $scope.on_hold_list = [];
                $scope.completed_list = [];
            
            }
            else if($scope.status == 'In Progress'){
                $scope.pending_list = [];
                $scope.in_progress_list = ticketlist.filter(item => item.status == 'In Progress');
                $scope.on_hold_list = [];
                $scope.completed_list = [];

            }
            else if ($scope.status == 'On Hold'){
                $scope.pending_list = [];
                $scope.in_progress_list = [];
                $scope.on_hold_list = ticketlist.filter(item => item.status == 'On Hold');
                $scope.completed_list = [];
            } else{
                $scope.pending_list = [];
                $scope.in_progress_list = [];
                $scope.on_hold_list = [];
                $scope.completed_list = ticketlist.filter(item => item.status == 'Completed');
            }
            }
            else{
                $scope.pending_list = ticketlist.filter(item => item.status == 'Pending');
                $scope.in_progress_list = ticketlist.filter(item => item.status == 'In Progress');
                $scope.on_hold_list = ticketlist.filter(item => item.status == 'On Hold');
                $scope.completed_list = ticketlist.filter(item => item.status == 'Completed');
            }

            $scope.totalDisplayed = [10, 10, 10, 10];

            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

        if( $scope.listmode == false )
            $('.calendar').fullCalendar( 'refetchEvents' );
    };


    $scope.refreshTickets = function(){
        $scope.pageChanged();
    };

    $scope.$on('create_workorder', function(event, args){
        $scope.pageChanged();
    });

    $scope.refreshTickets();

    $scope.getTicketNumber = function(ticket){
        return ticket.ticket_id;
    }

    $scope.onSelectTicket = function(ticket, event, type){
        ticket.type = type;
        $scope.selectedNum = 0;
        $scope.workorder_name = ticket.name;
        $scope.ref_id = ticket.ref_id;

        $scope.workorder = angular.copy(ticket);
        console.log($scope.workorder);
        if(ticket.critical_flag == 1) $scope.workorder.critical_flag = true;
        if(ticket.critical_flag == 0) $scope.workorder.critical_flag = false;

        $scope.pending_list.forEach(item => item.active = item.id == $scope.workorder.id);
        $scope.in_progress_list.forEach(item => item.active = item.id == $scope.workorder.id);
        $scope.on_hold_list.forEach(item => item.active = item.id == $scope.workorder.id);
        $scope.completed_list.forEach(item => item.active = item.id == $scope.workorder.id);

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_dialog.html',
            controller: 'WorkorderDialogController',
            scope: $scope,
            size: 'lg',
            backdrop: 'static',
            resolve: {
                workorder: function () {
                    return ticket;
                }
            }
        });

        modalInstance.result.then(function (data) {
            console.log();
        }, function () {

        });
    }

    $scope.onClickFlag = function(row)
    {
        var request = {};

        request.id = row.id;
        request.favorite_flag = 1 - row.favorite_flag;

        $http({
            method: 'POST',
            url: '/frontend/eng/flagworkorder',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            row.favorite_flag = request.favorite_flag;
            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.onClickItem = function(row)
    {
        if(row.selected == true )
            return;

        selected_ticket = angular.copy(row);
    }

    $scope.onUpdateInspected = function(row)
    {
        $scope.pending_list.forEach(item => {
            if( item.id == row.id )
                item.inspected = row.inspected;
        });

        $scope.in_progress_list.forEach(item => {
            if( item.id == row.id )
                item.inspected = row.inspected;
        });

        $scope.on_hold_list.forEach(item => {
            if( item.id == row.id )
                item.inspected = row.inspected;
        });
    }

    $scope.onDeleteWorkOrder = function () {
        deleteWorkOrder(selected_ticket);
    }

    function deleteWorkOrder(ticket)
    {
        if( !ticket || ticket.id < 1 )
            return;

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/engineering/workorder/workorder_delete.html',
            controller: 'WorkorderDeleteCtrl',
            scope: $scope,
            resolve: {
                workorder: function () {
                    return ticket;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    function removeItemFromSourceList(items)
    {
        if( items.length < 1 )
            return;

        var item = items[0];

        if( item.status == 'Pending' )
            $scope.pending_list = $scope.pending_list.filter(row =>
                items.filter(row1 => row1.id == row.id).length == 0
            );

        if( item.status == 'In Progress' )
            $scope.in_progress_list = $scope.in_progress_list.filter(row =>
                items.filter(row1 => row1.id == row.id).length == 0
            );

        if( item.status == 'On Hold' )
            $scope.on_hold_list = $scope.on_hold_list.filter(row =>
                items.filter(row1 => row1.id == row.id).length == 0
            );

        if( item.status == 'Completed' )
            $scope.completed_list = $scope.completed_list.filter(row =>
                items.filter(row1 => row1.id == row.id).length == 0
            );
    }

    function updateWorkorderStatus(item, status)
    {
        item.status = status;
        var data = angular.copy(item);
        $http({
            method: 'POST',
            url: '/frontend/eng/changestatus',
            data: data,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.$broadcast('workorder_status_change', item);

                var workorder = response.data.content;
                item.status = workorder.status;
                item.time_spent = workorder.time_spent;
                item.duration = workorder.duration;
            }).catch(function(response) {
                //toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    function updateWorkorderListStatus(items, status)
    {
        items.forEach(item => {
            item.selected = false;
            updateWorkorderStatus(item, status);
        });
    }

    function changeWorkOrder(workorder, url)
    {
        $http({
            method: 'POST',
            url: url,
            data: workorder,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code == 200 )
                    $scope.pageChanged();
                else
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
            }).catch(function(response) {
                //toaster.pop('error', MESSAGE_TITLE, 'Failed to created notification');
            })
            .finally(function() {
            });
    }

    function startWorkOrder(workorder)
    {
        var url = '/frontend/workorder/start';
        changeWorkOrder(workorder, url);
    }

    function holdWorkOrder(workorder)
    {
        var url = '/frontend/workorder/hold';
        changeWorkOrder(workorder, url);
    }

    function resumeWorkOrder(workorder)
    {
        var url = '/frontend/workorder/resume';
        changeWorkOrder(workorder, url);
    }

    function finishWorkOrder(workorder)
    {
        var url = '/frontend/workorder/finish';
        changeWorkOrder(workorder, url);
    }

    $scope.getSelectedItemsIncluding = function(list, item) {
        item.selected = true;
        return list.filter(function(row) { return row.selected; });
    };

    $scope.onPendingDrop = function (list, items, index, pos) {
        if( items.length < 1 )
            return;

        var item = items[0];

        if( item.status == 'Pending' || item.status == 'Completed')
            return;

        removeItemFromSourceList(items);
        $scope.in_progress_list.splice(index, 0, ...items);

        updateWorkorderListStatus(items, 'Pending');
    };

    $scope.onInProgressDrop = function (list, items, index, pos) {
        // if( AuthService.isValidModule('mobile.workorder.supervisor') == false )
        //     return;

        if( items.length < 1 )
            return;

        var item = items[0];

        if( item.status == 'In Progress' || item.status == 'Completed' )
            return;

        removeItemFromSourceList(items);
        $scope.in_progress_list.splice(index, 0, ...items);

        updateWorkorderListStatus(items, 'In Progress');
    };

    $scope.onOnHoldDrop = function (list, items, index, pos) {
        // if( AuthService.isValidModule('mobile.workorder.supervisor') == false )
        //     return;

        if( items.length < 1 )
            return;

        var item = items[0];

        if( item.status == 'On Hold' || item.status == 'Completed')
            return;

        removeItemFromSourceList(items);
        $scope.on_hold_list.splice(index, 0, ...items);

        updateWorkorderListStatus(items, 'On Hold');
    };

    $scope.onCompletedDrop = function (list, items, index, pos) {


        if( AuthService.isValidModule('mobile.workorder.supervisor') == false )
            return;

        var item = items[0];
        if (item.assigne_list.length < 1) {
            toaster.pop('warning', MESSAGE_TITLE, 'Please assign a staff to this Work Order');
            return;
        }

        if( item.status == 'Completed' )
            return;

        var inspected_flag = true;
        items.forEach(item => {
            if(item.checklist_id > 0 && item.inspected == false )   // not inspected
            {
                inspected_flag = false;
                return;
            }
        });

        if( inspected_flag == false )
        {
            toaster.pop('info', MESSAGE_TITLE, "Please check check list before completion");
            return;
        }

        removeItemFromSourceList(items);
        $scope.completed_list.splice(index, 0, ...items);

        updateWorkorderListStatus(items, 'Completed');
    };

    $scope.openModalImage = function (src, filename) {
        var extension = filename.substr((filename.lastIndexOf('.') +1));
        if( extension == 'pdf')
        {
            $window.location.href = '/' + src;
            return;
        }

        var modalInstance = $uibModal.open({
            templateUrl: "tpl/lnf/modalImage.html",
            resolve: {
                imageSrcToUse: function () {
                    return src;
                },
                imageDescriptionToUse: function () {
                    return filename;
                }
            },
            controller: [
                "$scope", "imageSrcToUse", "imageDescriptionToUse",
                function ($scope, imageSrcToUse, imageDescriptionToUse) {
                    $scope.ImageSrc = '/' + imageSrcToUse;
                    return $scope.ImageDescription = imageDescriptionToUse;
                }
            ]
        });
        modalInstance.result.then(function (selectedItem) {

        }, function () {

        });
    }

    // calendar view
    $scope.onClickViewMode = function()
    {
        $scope.listmode = !$scope.listmode;
        if( $scope.listmode == false )
        {
            $timeout(function() {
                $scope.today();
            }, 1000);
        }
    }

    function applyFilter(request)
    {
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        // filter
        request.searchtext = $scope.searchtext;

        request.assignee_ids = $scope.staff_tags.map(item => item.id).join(',');
        request.location_ids = $scope.location_tags.map(item => item.id).join(',');
        request.equip_list = $scope.equip_tags;
        request.wr_ids = $scope.wrid_tags.map(item => item.ref_id).join(',');

        if( $scope.priority_selected)
            request.priority = $scope.priority_selected;
        else
            request.priority = 'All';

        if( $scope.type_selected )
            request.work_order_type = $scope.type_selected.name;
        else
            request.work_order_type = 'All';

        $scope.filter_apply = $scope.staff_tags.length > 0 ||
            $scope.wrid_tags.length > 0 ||
            $scope.location_tags.length > 0 ||
            $scope.equip_tags.length > 0 ||
            request.priority!= 'All' ||
            request.work_order_type != 'All';
    }

    $scope.events = function(start, end, timezone, callback) {
        var request = {};

        request.start_date = moment(start).format('YYYY-MM-DD');
        request.end_date = moment(end).format('YYYY-MM-DD');
        request.date_flag = 1;
        request.dispatcher = profile.id;

        applyFilter(request);

        var url = '/frontend/eng/workorderlist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            events = [];
            response.data.datalist.forEach(row => {
                var event = {};
                event.start = moment(row.schedule_date).format('YYYY-MM-DD');
                event.end = moment(row.schedule_date).format('YYYY-MM-DD');
                event.title = $scope.getTicketNumber(row) + " - " + row.name;
                switch(row.status)
                {
                    case 'Pending':
                        event.className = ['b-l b-r b-4x b-pending'];
                        break;
                    case 'In Progress':
                        event.className = ['b-l b-r b-4x b-in-progress'];
                        break;
                    case 'On Hold':
                        event.className = ['b-l b-r b-4x b-on-hold'];
                        break;
                    case 'Completed':
                        event.className = ['b-l b-r b-4x b-completed'];
                        break;
                }

                // event.startEditable = row.status == 'Pending';
                event.editable = row.status == 'Pending';

                event.workorder = row;

                events.push(event); // push a copy of day
            });

            callback(events);

            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });

    }

    function changeWorkorderDate(workorder, start_date, end_date)
    {
        var request = {};
        request.id = workorder.id;
        request.schedule_date = start_date;
        $http({
            method: 'POST',
            url: '/frontend/eng/changeworkdate',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('error', MESSAGE_TITLE, 'Work order cannot be updated');
                    return;
                }
                workorder.schedule_date = start_date;
            }).catch(function(response) {

            })
            .finally(function() {
            });
    }

    /* alert on Drop */
    $scope.onEventDrop = function(event, delta, revertFunc, jsEvent, ui, view){
        var workorder = event.workorder;
        var start_date = moment(workorder.schedule_date).add(delta).format('YYYY-MM-DD');
        var end_date = moment(workorder.schedule_date).add(delta).format('YYYY-MM-DD');

        changeWorkorderDate(workorder, start_date, end_date);
    };

    /* alert on Resize */
    $scope.onEventResize = function(event, delta, revertFunc, jsEvent, ui, view){
        var workorder = event.workorder;
        var start_date = moment(workorder.schedule_date).format('YYYY-MM-DD');
        var end_date = moment(workorder.schedule_date).add(delta).format('YYYY-MM-DD');

        changeWorkorderDate(workorder, start_date, end_date);
    };

    $scope.overlay = $('.fc-overlay');
    $scope.alertOnMouseOver = function( event, jsEvent, view ){
        $scope.event = event;
    }

    $scope.onDayClick = function(event, jsEvent, view) {

    }

    $scope.onEventClick = function( date, jsEvent, view){
        console.log(date, jsEvent, view);
        var workorder = date.workorder;
        // editWorkOrder(workorder);
        $scope.onSelectTicket(workorder, null, 1);
    };

    /* config object */
    $scope.uiConfig = {
        calendar:{
            height: 750,
            editable: true,
            eventDurationEditable: false,
            header:{
                left: 'prev',
                center: 'title',
                right: 'next'
            },
            dayClick: $scope.onDayClick,
            eventClick: $scope.onEventClick,
            eventDrop: $scope.onEventDrop,
            // eventResize: $scope.onEventResize,
            eventMouseover: $scope.alertOnMouseOver
        }
    };

    /* Change View */
    $scope.changeView = function(view, calendar) {
        $('.calendar').fullCalendar('changeView', view);
    };

    $scope.today = function(calendar) {
        $('.calendar').fullCalendar('today');
    };

    ///* event sources array*/
    $scope.eventSources = [$scope.events];

    $scope.onDownloadChecklist = function(row)
    {
        var filter = {};
        filter.id = row.id;

        var profile = AuthService.GetCredentials();
        filter.creator_id = profile.id;
        filter.timestamp = new Date().getTime();

        filter.report_target = 'workorder_checklist';
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);

        $scope.generateDownloadChecker(filter);
    }

    $scope.$on('$destroy', function() {
        clearDownloadChecker();
    });

    var filter_param = undefined;

    $scope.generateDownloadChecker = function(filter){
        filter_param = filter;

        // Block the user interface
        blockUI.start("Please wait while the report is being generated.");
    }

    function clearDownloadChecker() {
        // Unblock the user interface
        blockUI.stop();
    }

    $scope.$on('pdf_export_finished', function(event, args){
        if( filter_param && args == filter_param.timestamp )
            clearDownloadChecker();
    });

    $scope.onExportExcel = function()
    {
        var request = {};
        request.searchoption = $scope.searchoption;
        request.searchtext = $scope.searchtext;

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        request.dispatcher = profile.id;

        request.start_date = $scope.daterange.substring(0, '2016-01-01'.length);
        request.end_date = $scope.daterange.substring('2016-01-01 - '.length, '2016-01-01 - 2016-01-01'.length);
        request.assigne_ids = $scope.staff_tags.map(item => item.id).join(',');
        request.wr_ids = $scope.wrid_tags.map(item => item.ref_id).join(',');
        if( $scope.priority_selected)
            request.priority = $scope.priority_selected;
        else
            request.priority = 'All';

        if( $scope.type_selected )
            request.work_order_type = $scope.type_selected.name;
        else
            request.work_order_type = 'All';

        request.location_ids = $scope.location_tags.map(item => item.id).join(',');
        request.equip_list = $scope.equip_tags.map(item => item.id).join(',');
        request.excel_type = 'excel';



        $window.location.href = '/frontend/eng/exportworkorder?' + $httpParamSerializer(request);
    }

    $scope.$on('refresh_workorder_page', function(event, args){
        $scope.pageChanged();
    });
});

app.controller('WorkorderDeleteCtrl', function($scope, $uibModalInstance, $http, workorder, AuthService, toaster) {
    var MESSAGE_TITLE = 'Work Order';

    $scope.workorder = angular.copy(workorder);

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.deleterow = function() {
        var profile = AuthService.GetCredentials();

        var request = {};
        request = angular.copy($scope.workorder);
        $http({
            method: 'POST',
            url: '/frontend/eng/deleteworkorder',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.workorder = {};
            toaster.pop('success', MESSAGE_TITLE, ' WorkOrder has been deleted successfully');
            $uibModalInstance.close();
            $scope.pageChanged();
        }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
    }
});
