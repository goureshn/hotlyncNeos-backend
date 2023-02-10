app.controller('ComplaintController', function ($scope, $rootScope, $http, $interval, $timeout, toaster, GuestService, AuthService) {
    var MESSAGE_TITLE = 'Create Complaint Ticket';

    $scope.guest = {};
    $scope.alert = {};
    $scope.complaint = {};
    $scope.complaint_list = [];
    $scope.complaint_types = [];
    $scope.complaint.complaint = '';
    $scope.complaint.compensation = {};
    

    $scope.selected_room = {};

    var date = new Date();
    $scope.guest.request_time = date.format("HH:mm:ss");
    $scope.timer = $interval(function() {
        var date = new Date();
        $scope.guest.request_time = date.format("HH:mm:ss");
    }, 1000);

    $scope.$on('$destroy', function() {
        $interval.cancel($scope.timer);
        $scope.timer = undefined;
    });

    $http.get('/list/servicedepartment')
        .then(function(response) {
            $scope.departments =  response.data;
        });

    $http.get('/frontend/guestservice/compensationtype')
        .then(function(response) {
            $scope.compensations =  response.data;
        });

    GuestService.getMaxTicketNo()
        .then(function(response) {
            $scope.max_ticket_no = response.data.max_ticket_no + $scope.newTickets.length - 1;
            $scope.ticket_id = sprintf('C%05d', $scope.max_ticket_no + 1);
        });

    $scope.getRoomList = function(val) {
        if( val == undefined )
            val = "";

        return GuestService.getRoomList(val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.selected_room = $item;

        GuestService.getGuestName($item)
            .then(function(response){
                if( response.data )
                    $scope.guest = response.data;
                else
                    $scope.guest.guest_name = 'Admin task';

                var date = new Date();
                $scope.guest.request_time = date.format("HH:mm:ss");
            });

        GuestService.getLocationGroupFromRoom($item.id)
            .then(function(response){
                $scope.selected_room.location_group = response.data;
            });
    };


    var secretEmptyKey = '[$empty$]'
    $scope.stateComparator = function (state, viewValue) {
        return viewValue === secretEmptyKey || (''+state).toLowerCase().indexOf((''+viewValue).toLowerCase()) > -1;
    };

    GuestService.getComplaintItemList(0)
        .then(function(response) {
            $scope.complaint_list =  response.data.list;
            $scope.complaint_types =  response.data.types;
            $scope.complaint.type_id = $scope.complaint_types[0].id;
        });

    $scope.onComplaintSelect = function ($item, $model, $label) {
        $scope.onChangeComplaintType();

        $scope.complaint = $item;
        $scope.complaint.complaint_name = $item.complaint;
        $scope.complaint.dept_id = $scope.departments[0].id;
        $scope.complaint.compensation = $scope.compensations[0];
        $scope.complaint.compensation_id = $scope.compensations[0].id;
        $scope.complaint.compensation.approve_flag = $scope.complaint.compensation.approval_route_id > 0;

    };

    $scope.addComplaintItem = function() {
        var data = {};
        data.complaint = $scope.complaint.complaint_name;
        data.type_id = $scope.complaint.type_id;

        GuestService.createComplaintItem(data)
            .then(function(response) {
                $scope.complaint_list =  response.data;
                $scope.complaint = {};
                var item = {};
                for(var i =0 ; i < $scope.complaint_list.length; i++) {
                    var complaint = $scope.complaint_list[i].complaint;
                    if(complaint == data.complaint) {
                        item.complaint =  $scope.complaint_list[i].complaint;
                        item.id =  $scope.complaint_list[i].id;
                        item.type_id =  $scope.complaint_list[i].type_id;
                        break;
                    }
                }
                $scope.selected_room.noResults = false;
                $scope.onComplaintSelect(item, data.complaint, data.complaint);
            });
    }

    $scope.onFocus = function (e) {
        $timeout(function () {
            $(e.target).trigger('input');
            $(e.target).trigger('change'); // for IE
        });
    };

    $scope.onChangeComplaintType = function() {
        $http.get('/frontend/guestservice/assignee?type_id=' + $scope.complaint.type_id)
            .then(function(response) {
                $scope.assignee =  response.data;
                if($scope.assignee == undefined) {
                    $scope.assignee = {id: 0, wholename: 'No Staff is on shift'}
                    toaster.pop('error', 'Task error', 'No Staff is on shift');
                }
                else
                    $scope.assignee.wholename = $scope.assignee.first_name + ' ' + $scope.assignee.last_name;
            });
    }

    $scope.onChangeCompensationType = function() {
        var compensation_id = $scope.complaint.compensation_id;
        for(var i = 0; i < $scope.compensations.length; i++ )
        {
            if( $scope.compensations[i].id == compensation_id )
            {
                $scope.complaint.compensation = $scope.compensations[i];
                $scope.complaint.compensation.approve_flag = $scope.complaint.compensation.approval_route_id > 0;
                break;
            }
        }
    }

    $scope.createComplaint = function (flag) {  // 0: only create, 1: Create and another for same room, 2: Create and another for diff room
        var tasklist = [];

        if( !($scope.selected_room.id > 0 && $scope.guest.guest_name) )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select room and guest');
            return;
        }

        if( !($scope.complaint.complaint.length > 0 && $scope.complaint.type_id > 0 && $scope.selected_room.noResults == false) )
        {
            toaster.pop('error', MESSAGE_TITLE, 'Please select complaint type');
            return;
        }

        var profile = AuthService.GetCredentials();

        var data = {};

        data.type = 3;
        data.priority = $scope.complaint.type_id;

        var date = new Date();

        data.property_id = profile.property_id;
        data.start_date_time = date.format("yyyy-MM-dd HH:mm:ss");
        data.status_id = 1;
        data.running = 1;
        data.end_date_time = '0000-00-00 00:00:00';

        data.dispatcher = $scope.assignee.id;
        data.attendant = profile.id;
        data.room = $scope.guest.room_id;
        data.complaint_list = $scope.complaint.id;
        data.department_id = $scope.complaint.dept_id;
        data.max_time = $scope.assignee.max_time;
        data.custom_message = $scope.complaint.custom_message;
        data.guest_id = $scope.guest.id;
        data.location_id = $scope.selected_room.location_group.id;

        data.compensation_status = 1;   // On route
        
        if( $scope.complaint.compensation_enable )
        {
            data.compensation_id = $scope.complaint.compensation.id;
            data.compensation_comment = $scope.complaint.compensation.comment;
        }
        else {
            data.compensation_id = 0;
            data.compensation_comment = '';
        }

        $rootScope.myPromise = GuestService.createComplaint(data);
        $rootScope.myPromise.then(function(response) {
            console.log(response);
            $scope.complaint = {};
            $scope.max_ticket_no = response.data.max_ticket_no;
            $scope.ticket_id = sprintf('C%05d', $scope.max_ticket_no + 1);

            $scope.$emit('onTicketChange', tasklist);

            if( flag == 0 ) // Create
            {
                $scope.selected_room = {};
                $scope.room_num = '';
                $scope.guest = {};
                $scope.$emit('onTicketCreateFinished', 1);      // Guest Request
            }
            if( flag == 1 ) // Create Create & add another for same room
            {
                // refresh quick task list
                $scope.onRoomSelect($scope.selected_room);
            }

            if( flag == 2 ) // Create Create & add another for another room
            {
                $scope.selected_room = {};
                $scope.room_num = '';
                $scope.guest = {};
            }

            toaster.pop('success', MESSAGE_TITLE, 'Complaint have been created successfully');
        }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Complaint have been failed to create');
            })
            .finally(function() {

            });
    }

});

