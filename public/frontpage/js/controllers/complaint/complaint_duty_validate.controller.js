app.controller('ComplaintDutyValidationController', function ($scope, $rootScope, $http, $httpParamSerializer, $window, $timeout, $uibModal, AuthService, GuestService, toaster ,liveserver,Upload) {
    var MESSAGE_TITLE = 'Duty Manager';
		
	$scope.full_height = $window.innerHeight - 125;
	 $scope.tab_height = $window.innerHeight - 100;
		
    $scope.complaint = {};
    $scope.guest_id_editable = false;
    $scope.genders = ['Male', 'Female'];
    $scope.exist_subcomplaint_list = [];
    $scope.guest_history = {};
    $scope.subflag=0;
    $scope.dept="";
    $scope.auth_svc = AuthService;
    
    var depts_list = [];
    $scope.depts_list = [];
    $scope.selected_depts = [];
    
    $scope.gs = GuestService;
    $scope.forward_flag = false;
    $scope.property_list = [];
    $scope.guest_types = ['In-House', 'Checkout', 'Arrival', 'Walk-in', 'House Complaint'];
    
    $scope.feedback_type_list = [];
    $scope.feedback_source_list = [];


    $scope.files = [];

    $scope.getLocationList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/locationtotallist?location=' + val + '&client_id=' + client_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onMainLocationSelect = function ($item, $model, $label) {
        $scope.complaint.lgm_name = $item.name;
        $scope.complaint.loc_id = $item.id;
        $scope.complaint.lgm_type = $item.type;
        $scope.complaint.location = $scope.complaint.lgm_type + ' - ' + $scope.complaint.lgm_name;

        var request = $scope.complaint;

      
        $http({
            method: 'POST',
            url: '/frontend/complaint/changelocation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // $scope.$emit('onChangedComplaint', response.data);
            toaster.pop('success', MESSAGE_TITLE, 'Location has been updated.');
            setNonEditState();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Location.');
        })
        .finally(function() {

        });
    };

    function getAdditionalSubComplaint()
    {
        $scope.exist_subcomplaint_list.forEach(function(item, index) {
            item.ticket_no = $scope.getTicketNumber(item);
            item.created_at_time = moment(item.created_at).format('D MMM YYYY hh:mm a');
            item.comment_list.forEach(function(row, index1){
                row.time = $scope.getTime(row);    
            });
            item.age_days = $scope.getTimeNow(item);
            item.log_list.forEach(function(row, index1){
                row.created_at_time = moment(row.created_at).format('DD MMM YYYY HH:mm:ss');    
            });                  
            
            if( item.path )
                item.sub_download_array = item.path.split("|");
            else
                item.sub_download_array = [];
            
            item.sub_pdf_type_flag = item.sub_download_array.map(row => {
                var extension = row.substr((row.lastIndexOf('.') +1));
                return extension == 'pdf' || extension == 'eml' || extension == 'msg';                    
            });    

            item.sub_icon_class = item.sub_download_array.map(row1 => {
                var extension = row1.substr((row1.lastIndexOf('.') +1));
                if( extension == 'pdf' )
                    return 'fa-file-pdf-o';
                if( extension == 'eml' )
                    return 'fa-envelope';
                if( extension == 'msg' )
                    return 'fa-envelope';

                return '';
            });    

            $http.get('/frontend/complaint/getdeptloclist?dept_id=' + item.dept_id)
            .then( function(response) {
                $scope.dept_loc_list = response.data;
        }); 

            item.comp = {};   
            getSubcomplaintCompensationList(item) 
        });
    }   

    $scope.init = function(complaint) {      
        var profile = AuthService.GetCredentials();
        console.log("checking");
        complaint.guest_path = '/'+complaint.guest_path;

        $scope.can_be_edit = AuthService.isValidModule('app.complaint.complaint_edit');        
        $scope.category_editable = AuthService.isValidModule('app.complaint.maincategory_add');
        $scope.category_change = AuthService.isValidModule('app.complaint.mytask_ctgry_change');
        $scope.disable_all = !$scope.can_be_edit;

        $http.get('/frontend/complaint/compensationtype?client_id='+profile.client_id)
            .then(function(response) {
                $scope.compensations =  response.data;
            });

        $http.get('/list/locationtotallist?client_id=' + profile.client_id)
            .then(function(response){
                $scope.location_list = response.data; 
            });

        $http.get('/list/userlist?property_id=' + profile.property_id + '&dept_id=0')
            .then(function(response) {
                $scope.staff_list = response.data;                
        });  

        $http.get('/list/property?client_id=' + profile.client_id)
            .then(function(response) {
                $scope.property_list = response.data;                
        });    

        $http.get('/list/housecomplaint')
            .then(function(response) {
               $scope.housecategory_list = response.data;
           });

        $http.get('/list/complaint_datalist?&client_id=' + profile.client_id)
           .then(function(response) {
              $scope.feedback_type_list = response.data.feedback_type_list; 
              $scope.feedback_source_list = response.data.feedback_source_list;
              
        });
       
       
           getComplaintCategoryList();

        $scope.complaint = complaint;
       // window.alert($scope.disable_all);
        
        $scope.complaint.location = complaint.lgm_type + ' - ' + complaint.lgm_name;
        $scope.guest_id_editable = !(complaint.guest_id > 0);
        $scope.comment_list = [];
        $scope.guest_image = [];
        $scope.complaint.approval_route_flag = complaint.approval_route_id > 0;
        $scope.comp = {};

        $scope.complaint.guest_is_open = true;
        $scope.complaint.compensation_comment_is_open = false;
        $scope.complaint.running_subcomplaint_is_open = false;
        $scope.complaint.complaint_comment_is_open = false;
        $scope.complaint.feedback_type = complaint.feedback_type;
        $scope.comp.provider_id = 0;

        $scope.forward_flag = $scope.complaint.lg_property_id == profile.property_id;

        if( complaint.path )
            $scope.complaint.download_array = complaint.path.split("|");
        else
            $scope.complaint.download_array = [];

        $scope.complaint.pdf_type_flag = $scope.complaint.download_array.map(row => {
                var extension = row.substr((row.lastIndexOf('.') +1));
                return extension == 'pdf' || extension == 'eml' || extension == 'msg';                    
            });    

            $scope.complaint.icon_class = $scope.complaint.download_array.map(row1 => {
                var extension = row1.substr((row1.lastIndexOf('.') +1));
                if( extension == 'pdf' )
                    return 'fa-file-pdf-o';
                if( extension == 'eml' )
                    return 'fa-envelope';
                if( extension == 'msg' )
                    return 'fa-envelope';

                return '';
            });    

        if( complaint.gender == null )
            $scope.complaint.gender = $scope.genders[0];

        complaint.modified_by = profile.id;

        GuestService.getPreviousHistoryList(complaint.gs_guest_id)
            .then(function (response) {
                $scope.historylist = response.data.datalist;
            }).catch(function (response) {

            });

        complaint.latest = 1;
        complaint.active = true;
        $scope.$emit('onUpdateComplaint', complaint);    

        $scope.comp_list = [];
        $http.get('/frontend/complaint/getcomplaintinfo?id=' + complaint.id + '&property_id=' + profile.property_id)
            .then(function(response) {
                $scope.exist_subcomplaint_list =  response.data.content.sublist;
                getAdditionalSubComplaint();

                $scope.comp_list =  response.data.content.comp_list;
                $scope.category_list = response.data.content.category_list;

                var alloption = {id: 0, name : 'Unclassified'};
                $scope.category_list.unshift(alloption);
            });    

        var profile = AuthService.GetCredentials();
        var client_id = profile.client_id;
        $http.get('/list/complaint_datalist?&client_id=' + client_id)
         .then(function(response) {
            $scope.division_list = response.data.division_list; 
        });

        // refresh keywords    
        var comment_highlight = $scope.complaint.comment_highlight + '';
        var response_highlight = $scope.complaint.response_highlight + '';
        $scope.complaint.comment_highlight = undefined;
        $scope.complaint.response_highlight = undefined;

        $http.get('/list/severitylist')
            .then(function(response) {
            $scope.complaint.comment_highlight = comment_highlight;
            $scope.complaint.response_highlight = response_highlight;

            $scope.severity_list = response.data; 
        });

        getCommentList(); 
        getComplaintInfo();
        getGuestHistory();
        getComplaintLogs();
        getMainSubcategoryList();
        $scope.loadDepts();
    }
    
    var profile = AuthService.GetCredentials();
    $scope.complaint_setting = {};
    $http.get('/list/complaintsetting?property_id=' + profile.property_id).success( function(response) {
        $scope.complaint_setting = response;
    });  
     
    $http.get('/list/department')
        .then(function(response){
            depts_list = response.data;
            $scope.depts_list = depts_list;
        });
    $scope.uploadGuestImg = function (guest_image) {
        $scope.guest_image = $scope.guest_image.concat(guest_image);
        $scope.load=1;
        if ($scope.guest_image && $scope.guest_image.length) {
            Upload.upload({
                url: '/frontend/complaint/uploadguestimage',
                data: {
                    id: $scope.complaint.id,
                    guest_image: $scope.guest_image
                }
            }).then(function (response) {
                $scope.guest_image = [];
                $scope.progress_img = 0;
                $scope.complaint.guest_path = '/' + response.data.path;
                $scope.load=0;
            }, function (response) {
                $scope.guest_image = [];
                $scope.progress_img = 0;
               
                if (response.status > 0) {
                    $scope.errorMsg = response.status + ': ' + response.data.files;
                }
            }, function (evt) {
                $scope.progress_img =
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        }
    };
    $scope.removeGuestImg = function ($index) {
        $scope.guest_image.splice($index, 1);
    }
        
    $scope.loadDepts = function(){
	    var request = $scope.complaint;
        $http({
            method: 'POST',
            url: '/frontend/complaint/deptlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.selected_depts = response.data.deptlist;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to fetch departments.');
        })
        .finally(function() {

        });
       
    } 
 
    $scope.loadFiltersValue = function(value,query) {	    
        return depts_list.filter(item =>
            item.department.toLowerCase().indexOf(query.toLowerCase()) != -1                            
        );
    }

    $scope.onDepartmentSend = function () {
        if($scope.selected_depts.length > 6)
        {
            toaster.pop('error', MESSAGE_TITLE, 'Add a maximum of 6 tags.');
            return;            
        }

        $scope.complaint.depts_list = $scope.selected_depts.map(function(tag) { return tag.id; });
        
        if($scope.complaint.depts_list.length>0)
            $scope.complaint.send_flag = 1;            
        else
            $scope.complaint.send_flag = 0;

        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/senddept',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to send departments.');
        })
        .finally(function() {

        });

    
  };

    $scope.onDownloadPDF = function(){
        var filter = {};
        filter.report_by = 'complaint';
        filter.report_type = 'Detailed';
        filter.report_target = 'complaint';
        var profile = AuthService.GetCredentials();
        filter.property_id = profile.property_id;
        filter.id = $scope.complaint.id;
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }

    $scope.onGenerateCompensationPDF = function(){
        var filter = {};
        filter.report_by = 'complaint';
        filter.report_type = 'Detailed';
        filter.report_target = 'compensation';
        var profile = AuthService.GetCredentials();
        filter.property_id = profile.property_id;
        filter.id = $scope.complaint.id;
        filter.user_id = profile.id;
        $window.location.href = liveserver.api + 'pdfreport?' + $httpParamSerializer(filter);
    }

    $scope.onAckMainComplaint = function($event) {
        $event.preventDefault();
    	$scope.complaint.status = 'Acknowledge';

        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/ack',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onReject = function($event) {
        $event.preventDefault();

        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input reason';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                rejectComplaint(comment);
            }, function () {

            });
    }

    function rejectComplaint(comment) {
        var request = {};
        request.id = $scope.complaint.id;
        request.comment = comment;

        $http({
            method: 'POST',
            url: '/frontend/complaint/reject',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.complaint.status = 'Rejected';
            toaster.pop('info', MESSAGE_TITLE, 'Complaint is rejected');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onResolve = function($event) {
        $event.preventDefault();

        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Resolution to Guest';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                resolve(comment);
            }, function () {

            });
    }

    function resolve(comment) {
        $scope.complaint.status = 'Acknowledge';
        $scope.complaint.solution = comment;

        var request = $scope.complaint;

        if( request.solution == undefined || request.solution.length < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please provide primary resolution.');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/complaint/resolve',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // $scope.$emit('onChangedComplaint', response.data);
            toaster.pop('success', MESSAGE_TITLE, 'Complaint resolved successfully.');
            // $scope.complaint.status = 'Resolved';
            // getComplaintLogs();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onClose = function() 
    {
        // check sub complaint
        if ($scope.complaint_setting.close_maincomplaint_without_subcomplaint == 0 &&
            $scope.exist_subcomplaint_list.length < 1 )     // check if there is no sub complaint
        {
            toaster.pop('info', MESSAGE_TITLE, "There is no sub-complaint created. Please create a sub-complaint and assign it to a specific department.");
            return;
        }

        if ($scope.complaint_setting.close_comment == 1)
        {
            var modalInstance = $uibModal.open({
                templateUrl: 'modal_input.html',
                controller: 'ModalInputCtrl',
                scope: $scope,
                resolve: {
                    title: function () {
                        return 'Root Cause';
                    },
                    min_length: function () {
                        return;
                    }
                }
            });

            modalInstance.result
                .then(function (comment) {
                    closeComplaint(comment);
                }, function () {

                });
        }
        else
        {
            var message = {};

            message.title = 'Confirm Dialog';
            message.content = 'Are you sure you want to close the complaint?';

            var modalInstance = $uibModal.open({
                templateUrl: 'tpl/modal/modal_confirm.html',            
                resolve: {
                    message: function () {
                        return message;
                    }
                },            
                controller: function ($scope, $uibModalInstance) {
                    $scope.message = message;
                    $scope.ok = function (e) {
                        $uibModalInstance.close('ok');                    
                    };
                    $scope.cancel = function (e) {
                        $uibModalInstance.dismiss();                    
                    };                
                },
            });
            modalInstance.result.then(function (ret) {
                if( ret == 'ok' )
                    closeComplaint();       
            }, function () {

            });   
        }
    }

    function closeComplaint(comment) {
        var request = {};

        request.id = $scope.complaint.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/close',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);
            $scope.complaint.closed_flag = 1;
            toaster.pop('success', MESSAGE_TITLE, 'Complaint closed successfully.');
            getComplaintLogs();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onReopen = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input comment';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                reopenComplaint(comment);
            }, function () {

            });
    }

    function reopenComplaint(comment) {
        var request = {};

        request.id = $scope.complaint.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/reopen',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);
            $scope.complaint.closed_flag = 0;
            toaster.pop('success', MESSAGE_TITLE, 'Complaint is opened successfully.');
            getComplaintLogs();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }
   

    $scope.onRepending = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input comment';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                rependingComplaint(comment);
            }, function () {

            });
    }

    function rependingComplaint(comment) {
        var request = {};

        request.id = $scope.complaint.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/repending',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);
            $scope.complaint.status = 'Acknowledge';
            toaster.pop('success', MESSAGE_TITLE, 'Complaint is reverted successfully.');
            getComplaintLogs();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onUnresolve = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input reason';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                unresolveComplaint(comment);
            }, function () {

            });
    }

    function unresolveComplaint(comment) {
        var request = {};

        request.id = $scope.complaint.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/unresolve',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);
            $scope.complaint.closed_flag = 1;
            $scope.complaint.status = 'Unresolved';
            toaster.pop('success', MESSAGE_TITLE, 'Complaint closed successfully.');
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.forwardComplaint = function() {
        if($scope.exist_subcomplaint_list.length > 0 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Subcomplaint already created');
            return;
        }

        if( $scope.compenstion_id > 0 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Compensation already created');
            return;
        }

        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/forward',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);
            toaster.pop('info', MESSAGE_TITLE, 'Complaint has been forwarded.');            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to forward Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onDelete = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input reason to delete the Complaint';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                deleteComplaint(comment);
            }, function () {

            });
    }

    function deleteComplaint(comment) {
        var request = {};

        request.id = $scope.complaint.id;
        request.comment = comment;

        if( request.comment == null )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please enter Comment');
            return;
        }

        
        $http({
            method: 'POST',
            url: '/frontend/complaint/delete',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);            
            $scope.$emit('onChangedComplaint', response.data);
            $scope.onSelectTicket($scope.complaint);
            toaster.pop('success', MESSAGE_TITLE, 'Complaint deleted successfully.');
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.onChangedSeverity = function() {
        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changeseverity',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // $scope.$emit('onChangedComplaint', response.data);
            toaster.pop('success', MESSAGE_TITLE, 'Severity has been updated.');
            setNonEditState();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Severity.');
        })
        .finally(function() {

        });
    }

    $scope.onChangedCategory = function($item, $model, $label) {
        // set severity
        $scope.category_list.forEach(ele => {
            if( ele.id == $scope.complaint.category_id )
            {
                $scope.complaint.severity = ele.severity; 
                $scope.complaint.category_name = ele.name;              
            }   
        });

        // category does not severity
        if( !($scope.complaint.severity > 0) )
        {
            // set first severity as default
            $scope.complaint.severity = $scope.severity_list[0].id;
        }

        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changemaincategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // $scope.$emit('onUpdateComplaint', $scope.complaint);            
            toaster.pop('success', MESSAGE_TITLE, 'Category has been updated.');
            setNonEditState();
            getComplaintLogs();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Category.');
        })
        .finally(function() {

        });

        getMainSubcategoryList();
        $scope.complaint.subcategory_id = 0;
        $scope.complaint.subcategory_name = '';
    }

    function getMainSubcategoryList()
    {
        // get sub category list
        var request = $scope.complaint;
        request.category_id = $scope.complaint.category_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/mainsubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.main_subcategory_list = response.data.content;
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Severity.');
        })
        .finally(function() {

        });
    }

    $scope.onChangedSubCategory = function() {
        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changemainsubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // $scope.$emit('onUpdateComplaint', $scope.complaint);            
            setNonEditState();
            toaster.pop('success', MESSAGE_TITLE, 'Sub Category has been updated.');
            getComplaintLogs();
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to change Category.');
        })
        .finally(function() {

        });
    }


    $scope.getGuestList = function(val) {
        if( val == undefined )
            val = "";
        
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        return $http.get('/frontend/complaint/guestlist?value=' + val + '&property_id=' + property_id)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
    	$scope.complaint.guest_id = $item.guest_id;
        $scope.complaint.guest_name = $item.guest_name;
    };

    $scope.getCountryList = function(val) {
        if( val == undefined )
            val = "";
       
        return $http.get('/list/countrylist?value=' + val)
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onCountrySelect = function ($item, $model, $label) {
        $scope.complaint.nationality = $item.id;
        $scope.complaint.nationality_name = $item.name;
    };

    $scope.saveGuestProfile = function() {
    	if( !($scope.complaint.guest_id > 0) )
    	{
    		toaster.pop('info', MESSAGE_TITLE, 'Please select Guest');
    		return;
    	}

        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/saveguestprofile',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Guest Profile has been saved');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    $scope.saveGuestFlag = function() {
        var request = $scope.complaint;

        if( !request.guest_comment )
        {
            toaster.pop('info', 'Please input comment');
            return;
        }

        if( !request.pref )
        {
            toaster.pop('info', 'Please input preference');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/complaint/flagguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Guest is flaged');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Complaint.');
        })
        .finally(function() {

        });
    }

    function getGuestHistory() {
        var request = {};
        request.complaint_id = $scope.complaint.id;
        request.guest_id = $scope.complaint.guest_id; 
        if( request.guest_id < 1 )
        {
            $scope.guest_history = [];
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/complaint/guesthistory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.guest_history = response.data.datalist;
            $scope.guest_history.forEach(function(item, index) {
                item.ticket_no = $scope.getComplaintNumber(item);  
                item.created_at_time = moment(item.created_at).format('D MMM YYYY hh:mm a')              ;
                item.total_comp = item.compensation_total + item.sub_compen_list_all;
            });
        }).catch(function(response) {
            
        })
        .finally(function() {

        });
    }

    function getComplaintLogs() {
        var request = {};
        request.complaint_id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/logs',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.log_list = response.data.datalist;
            $scope.log_list.forEach(function(item, index) {
                    item.created_at_time = moment(item.created_at).format('D MMM YYYY hh:mm a');            
                });

        }).catch(function(response) {
            
        })
        .finally(function() {

        });
    }
     $scope.onSelectPrev = function (){
        $scope.history_view = false;
    }
    $scope.history_view = false;
    $scope.historylist = [];
    $scope.history = {};
    $scope.showGSHistory = function () {
        $scope.history_view = true;
        $scope.history.limit = 10;
    }

    $scope.getGSHistoryType = function (type_id) {
        if (type_id == 1) return 'Guest';
        if (type_id == 3) return 'Complaints';
    }

    $scope.hideGSHistory = function () {
        $scope.history_view = false;
        $scope.history.limit = 0;
    }
    $scope.loadMoreGSHistory = function () {
        $scope.history.limit += 10;
    }


    $scope.viewGuestHistory = function() {
        $scope.complaint.history_view = true;
    }

    $scope.hideGuestHistory = function() {
        $scope.complaint.history_view = false;
    }

    $scope.showComplaint = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'complaint_detail.html',
            controller: 'ComplaintDetailCtrl1',
            scope: $scope,
            size: 'lg',
            resolve: {
                complaint: function () {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    function getCommentList(sub) {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.exist_subcomplaint_list.forEach(function(item, index) {
                item.time = $scope.getTime(item);            
            });
            
            $scope.comment_list = response.data;
            $scope.comment_list.forEach(function(row, index1){
                row.comment = row.comment.replace(/\r?\n/g,'<br/>');
                row.time = $scope.getTime(row);    
            });    
            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.onCommitComment = function($event) {
        $event.preventDefault();

        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input comment';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                commitComment(comment);
            }, function () {

            });
    }

    function commitComment(comment) {
        console.log(comment);

        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.sub_id = $scope.complaint.id;
        request.parent_id = 0;
        request.user_id = $scope.profile.id;        
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/addcomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.onAddFiles = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/complaint_add_files_dialog.html',
            controller: 'ComplaintFileUploadDialogCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },                
            }
        });

        modalInstance.result.then(function () {
            
        }, function () {

        });
    }

    $scope.onClickServiceRecovery = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/service_recovery_dialog.html',
            controller: 'ComplaintServiceRecoveryDialogCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },                
            }
        });

        modalInstance.result.then(function () {
            
        }, function () {

        });
    }

    $scope.postReply = function(sub, sub_reply) {
        console.log(sub_reply);

        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.sub_id = sub.id;
        request.parent_id = 0;
        request.user_id = profile.id;        
        request.comment = sub_reply;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/addsubcomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                

            // sub.comment_list = response.data;
            sub.sub_reply = ''; 
            // for(var i = 0; i < sub.comment_list.length; i++)
            // {
            //     sub.comment_list[i].comment = sub.comment_list[i].comment.replace(/\r?\n/g,'<br/>');
            // }
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onCompensationSelect = function($item, $model, $label) {
        var compensation_id = $item.id;
        $scope.comp = $item;
        $scope.comp.approve_flag = $item.approval_route_id > 0;   
    }


    $scope.onProviderSelect = function($item, $model, $label) {
        $scope.comp.provider_id = $item.id;        
    }

    $scope.addCompensationType = function(cost) {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.client_id = profile.client_id;
        request.property_id = profile.property_id;
        request.compensation = $scope.comp.compensation;
        request.cost = cost;  

        $http({
            method: 'POST',
            url: '/frontend/complaint/addcompensationtype',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            var data = response.data;
            if( data.code == 200 )
            {
                $scope.compensations =  response.data.list;    

                var item = {};
                item.id = response.data.compensation_id;
                item.approval_route_id = 0;
                item.cost = 0;
                item.compensation = $scope.comp.compensation;

                $scope.onCompensationSelect(item, null, null);          
                
                toaster.pop('success', MESSAGE_TITLE, 'New compensation type has been added');
            }
            
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post compensation.');
        })
        .finally(function() {

        }); 

    }

    $scope.onDepartmentSelect = function(comp, $item, $model, $label) {
        comp.dept_id = $item.id;        
    }


    $scope.onProviderSelect = function(comp, $item, $model, $label) {
        comp.provider_id = $item.id;            
    }

    $scope.postCompensation = function() {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.id = $scope.complaint.id;
        request.compensation_id = $scope.comp.id;
        request.comment = $scope.comp.comment;
       
        request.cost = $scope.comp.cost;
        request.dept_id = $scope.comp.dept_id;
        request.provider_id = $scope.comp.provider_id;

        if( request.cost < $scope.complaint_setting.minimum_compensation)
        {            
            toaster.pop('info', MESSAGE_TITLE, 'Please enter a valid amount. Minimum Compensation is AED'+ $scope.complaint_setting.minimum_compensation);
            return;
        }
        $scope.complaint.compensation_total=$scope.complaint.compensation_total+$scope.comp.cost;
        request.total = $scope.complaint.compensation_total;

        if( !(request.compensation_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select compensation');
            return;            
        }

        if( !request.comment )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please input comment');
            return;            
        }

        if( !(request.provider_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Provider');
            return;            
        }

        $http({
            method: 'POST',
            url: '/frontend/complaint/postcompensation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            var data = response.data;

            if( data.code != 200 )
                toaster.pop('error', MESSAGE_TITLE, data.message);    
            else
            {
                // $scope.comp_list = response.data.comp_list;                
                // $scope.$emit('onChangedComplaint', response.data);
                $scope.cancelCompensation();
            }

        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to post Compensation.');
        })
        .finally(function() {

        });        
    }

    $scope.cancelCompensation = function() {
        $scope.comp = {};        
    }

    $scope.deleteMainCompensation = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete the compensation?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                   $uibModalInstance.close('ok');   
                   deleteMainCompensation1(row);                 
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });  
        
        modalInstance.result.then(function (ret) {
            
        }, function () {

        });   
    }

    function deleteMainCompensation1(row) {
        $http({
            method: 'POST',
            url: '/frontend/complaint/deletemaincompensation',
            data: row,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('info', MESSAGE_TITLE, 'Compensation deleted successfully'); 
         
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Compensation');
        })
        .finally(function() {

        });        
    }

   

    $scope.showCompensationDetail = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'compensation_detail.html',
            controller: 'CompensationDetailCtrl',
            scope: $scope,
            resolve: {
                comp: function () {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'F00000';
        return sprintf('F%05d%s', ticket.parent_id, ticket.sub_label);        
    }

    $scope.getComplaintNumber = function(ticket){
        if(!ticket)
            return 'F00000';
        return sprintf('F%05d', ticket.id);        
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

    function getComplaintInfo(callback) {
        var profile = AuthService.GetCredentials();
        var property_id = $scope.complaint.property_id;
        
        GuestService.getComplaintItemList(property_id)
            .then(function(response) {
                $scope.complaint_list =  response.data.list;
                $scope.complaint_types =  response.data.types;
                $scope.complaint.type_id = $scope.complaint_types[0].id;
                $scope.complaint_department = response.data.com_dept;
                $scope.complaint_usergroup = response.data.com_usergroup;
                $scope.dept_list = response.data.dept;
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to get Complaint Information.');
            })
            .finally(function() {
                if( callback != undefined )
                    callback();
            });    
    }

    $scope.updateComplaintList = function(list){
        $scope.complaint_list = list;
    }

    $scope.addSubComplaint = function(sub, callback) {
	   $scope.subflag=1;
        var request = {};

        // request.complaint_id = sub.complaint_id;
        request.complaint_id = 0;
        request.dept_id = sub.dept_id;            
        
        var assginee_id = sub.assignee_id;

        if( !(request.dept_id > 0) ) // department is not selected      
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Department');
            return;
        }

        if( !(assginee_id > 0) ) // assignee is not selected      
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Assignee');
            return;
        }
       
        // save dept id with complaint id
        $http({
            method: 'POST',
            url: '/frontend/complaint/savecomplaintdept',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);     
            $scope.complaint_department = response.data.com_dept;                 
            if( callback != undefined )
                callback();                    
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {
            
        });         
    }
    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);       
    };

    $scope.removeFile = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete the Image?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                   $uibModalInstance.close('ok');   
                   removeFiles1(row);                 
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });  
        
        modalInstance.result.then(function (ret) {
            
        }, function () {

        });   
    }

    function removeFiles1($index) {
        $scope.complaint.download_array.splice($index, 1);
        var path = $scope.complaint.download_array.join("|");

        var request = {};
        request.id = $scope.complaint.id;
        request.path = path;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/updatefilepath',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.$emit('onChangedComplaint', $scope.complaint);
            console.log(response);     
            toaster.pop('info', MESSAGE_TITLE, 'Image deleted successfully'); 
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
            toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Image successfully'); 
        })
        .finally(function() {
            
        });         
    }

    $scope.upload = function() {

        if ($scope.files && $scope.files.length) {
            Upload.upload({
                url: '/frontend/complaint/uploadfiles',
                data: {
                    id: $scope.complaint.id,
                    files: $scope.files
                }
            }).then(function (response) {
                $scope.files = [];
                $scope.complaint.path = response.data.content;

                if( $scope.complaint.path )
                    $scope.complaint.download_array = $scope.complaint.path.split("|");
                else
                    $scope.complaint.download_array = [];

                $scope.$emit('onChangedComplaint', $scope.complaint);
                toaster.pop('info', MESSAGE_TITLE, 'Files Attached Successfully');
                $scope.init($scope.complaint);
            }, function (response) {
                $scope.files = [];
                if (response.status > 0) {
                    $scope.errorMsg = response.status + ': ' + response.data;
                }
            }, function (evt) {
                $scope.progress = 
                    Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
            });
        }

    }


    $scope.assignSubComplaint = function(sub) {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;
        var subcomplaint_list = [];

        subcomplaint_list.push(sub);

        var request = {};

        request.property_id = property_id;
        request.user_id = profile.id;
        request.id = $scope.complaint.id;
        request.subcomplaints = subcomplaint_list;
        
        $scope.addSubComplaint(sub, function() {
            $http({
                method: 'POST',
                url: '/frontend/complaint/assignsubcomplaint',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {
                console.log(response);     
                 $scope.$emit('onChangedComplaint', $scope.complaint);
                // $scope.exist_subcomplaint_list =  response.data;
                // getAdditionalSubComplaint();
                $window.location.reload();
                toaster.pop('success', MESSAGE_TITLE, 'Created Successfully');

                var sub1 = response.data.list[0];
                sub1.files = sub.files;

                uploadFilesForSubcomplaint(sub1);

                sub1.compensation_list = sub.compensation_list;
                addSubcomplaintCompensation(sub1);
            }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
                toaster.pop('error', MESSAGE_TITLE, 'Failed');
            })
            .finally(function() {
            });
        });
        
    }

    function uploadFilesForSubcomplaint(sub)
    {
        if (sub.files && sub.files.length > 0 ) {
            var profile = AuthService.GetCredentials();

            Upload.upload({
                url: '/frontend/complaint/uploadsubfiles',
                data: {
                    id: sub.id,
                    user_id: profile.id,
                    files: sub.files
                }
            }).then(function (response) {
                // if( response.data.path )
                //     sub.sub_download_array = response.data.path.split("|");
                // else
                //     sub.complaint.sub_download_array = [];

                // sub.sub_pdf_type_flag = sub.sub_download_array.map(row => {
                //     var extension = row.substr((row.lastIndexOf('.') +1));
                //     return extension == 'pdf';                    
                // });    

            }, function (response) {
                
            }, function (evt) {
                
            });
        }
    }

    function addSubcomplaintCompensation(sub)
    {
        if (sub.compensation_list && sub.compensation_list.length > 0 ) 
        {
            var request = {};
            request.sub_id = sub.id;
            request.comp_list = sub.compensation_list;

            $http({
                method: 'POST',
                url: '/frontend/complaint/addcompensationlistforsubcomplaint',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {
            //    sub.compensation_list = response.data;
            }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {
                
            });         
        }
    }

    // sub complaint
    $scope.viewSubcomplaint = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'subcomplaint_dialog.html',
            controller: 'SubcomplaintDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                complaint: function () {

                    return $scope.complaint;
                },
                complaint_list: function () {
                    return $scope.complaint_list;
                },
                severity_list: function () {
                    return $scope.severity_list;
                },
                dept_list: function () {
                    return $scope.dept_list;
                },
                complaint_department : function() {
                    return $scope.complaint_department;
                },
                complaint_usergroup : function() {
                    return $scope.complaint_usergroup;
                },
                severity: function() {
                    return $scope.complaint.severity;
                }
            }
        });

        modalInstance.result.then(function (sub) {
            if(sub) {
                $scope.assignSubComplaint(sub);
            }
        }, function () {

        });
    }

    function getHighlightText($text) {
        var html = $text;
        
        html = html.replace('<span class="angular-highlight">', '');
        html = html.replace('</span>', '');
        html = html.trim();        
        html = html.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");        
        html = html.replace('&amp;', '&');

        try {
            var regex = new RegExp(html, 'gmi');

            return html;
        } catch(e) {

        }    

        return '';
    }

    $scope.comment_highlight = function($text) {        
        var html = getHighlightText($text);
        
        updateHighlight($scope.complaint.comment_highlight, html, 0);
    }

    $scope.clearCommentHighlight = function() {        
        $scope.complaint.comment_highlight = '';                  
    }

    $scope.response_highlight = function($text) {        
        var html = getHighlightText($text);
        
        updateHighlight($scope.complaint.response_highlight, html, 1);
    }

    $scope.clearResponseHighlight = function() {        
        $scope.complaint.response_highlight = '';                  
    }

    function updateHighlight(highlight, html, mode) {
        if( !html || html.length < 1 )
            return;

        if( !highlight || highlight.length < 1 )
            highlight = html;
        else
            highlight += '&&' + html;

        if( mode == 0 )
            $scope.complaint.comment_highlight = highlight;
        if( mode == 1 )
            $scope.complaint.response_highlight = highlight;

        var request = $scope.complaint;
        request.mode = mode;

        $http({
                method: 'POST',
                url: '/frontend/complaint/highlight',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {
                console.log(response);                     
                $scope.$emit('onUpdateComplaint', $scope.complaint);

                getComplaintLogs();
            }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
                toaster.pop('error', MESSAGE_TITLE, 'Failed');

            })
            .finally(function() {
            });
    }

    $scope.createCategory = function () {        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_maincategory.html',
            controller: 'ComplaintCategoryCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },
                category_list: function () {
                    return $scope.category_list;
                },
                
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.setComplaintCategoryList = function(list) {
        $scope.category_list = list;
    }

    $scope.setComplaintMainSubCategoryList = function(list) {
        $scope.main_subcategory_list = list;
    }

    $scope.main_subcategory_list = [];
    $scope.createMainSubCategory = function () {
        if( !($scope.complaint.category_id > 0) )
            return;

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_mainsubcategory.html',
            controller: 'ComplaintMainSubCategoryCtrl',
            size: 'lg',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },
                main_subcategory_list: function () {
                    return $scope.main_subcategory_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.onSubCompensationSelect = function(sub, $item, $model, $label) {        
        sub.comp.id = $item.id;        
        sub.comp.cost = $item.cost;
    }

    $scope.onSubProviderSelect = function(sub, $item, $model, $label) {
        sub.comp.sub_provider_id = $item.id;            
    }

    $scope.onAddSubCompensation = function(sub)
    {   
        if( sub.comp.cost < $scope.complaint_setting.minimum_compensation)
        {            
            toaster.pop('info', MESSAGE_TITLE, 'Please enter a valid amount. Minimum Compensation is AED' + $scope.complaint_setting.minimum_compensation);
            return;
        }   
        if( !(sub.comp.id > 0) )
            return;

        if( !(sub.comp.sub_provider_id > 0) ) 
            return;
     

        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure want to add a new service recovery?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');                    
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                addCompensation(sub);            
        }, function () {

        });    
    }

    function addCompensation(sub)
    {
        var request = {};
        request = angular.copy(sub.comp);
        request.sub_id = sub.id;    
        $http({
            method: 'POST',
            url: '/frontend/complaint/addcompensationforsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // getSubcomplaintCompensationList(sub);
            sub.comp = {};
        }).catch(function(response) {
            
        })
        .finally(function() {

        });    
    }

    $scope.onDeleteSubCompensation = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete the compensation?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');      
                    onDeleteSubCompensation1(row);                
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });    
    }

    function onDeleteSubCompensation1(row)
    {
        var request = row;
        $http({
            method: 'POST',
            url: '/frontend/complaint/deletecompensationforsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);     
            toaster.pop('info', MESSAGE_TITLE, 'Compensation deleted successfully');        
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Compensation'); 
            
        })
        .finally(function() {

        });    
    }


    function getSubcomplaintCompensationList(sub) {
        var request = {};        
        request.sub_id = sub.id;    

        $http({
            method: 'POST',
            url: '/frontend/complaint/getcompensationlistforsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            sub.compensation_list = response.data;     
            var sum = 0;
            var no = 0;
            sub.compensation_list.map(item => {
                sum += item.cost;
                no++;

                item.no = no;
            });
            sub.compensation_total = sum;
           
        }).catch(function(response) {
            
        })
        .finally(function() {

        });
    }

    $scope.openModalImage = function (imageSrc, imageDescription) {
        var startIndex = (imageSrc.indexOf('\\') >= 0 ? imageSrc.lastIndexOf('\\') : imageSrc.lastIndexOf('/'));
        var filename = imageSrc.substring(startIndex + 1);

        var modalInstance = $uibModal.open({
            templateUrl: "tpl/lnf/modalImage.html",
            resolve: {
                imageSrcToUse: function () {
                    return imageSrc;
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
            $scope.selected = selectedItem;
        }, function () {

        });
    };

    $scope.downloadFile = function(url) {
        $window.location.href = '/' + url;
    }

    $scope.onAck = function(sub) {
        var request = {};
        request.id = sub.id;        
        $http({
            method: 'POST',
            url: '/frontend/complaint/acksubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            sub.ack = 1;            
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onInprogress = function(sub) {
        var request = {};

        request.id = sub.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/inprogresssubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            sub.in_progress = 1;            
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }


    function checkCompensationForSubcomplaint(sub, callback)
    {
        if( sub.compensation_list.length > 0 )
        {
            callback(sub);
            return;
        }

        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to complete the sub-complaint without adding Compensation?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');                                        
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                                        
                };                
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                    callback(sub);
        }, function () {

        });    
    }
    
    $scope.onComplete = function(sub) {
        if( !(sub.category_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Category');
            return;
        }

        if( !(sub.subcategory_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Sub Category');
            return;
        }

       

        checkCompensationForSubcomplaint(sub, function(sub1) {
            if (sub.status != 7){
            var modalInstance = $uibModal.open({
                templateUrl: 'modal_input.html',
                controller: 'ModalInputCtrl',
                scope: $scope,
                resolve: {
                    title: function () {
                        return 'Please input comment';
                    },
                    min_length: function () {
                        return;
                    }
                }
            });
    
            modalInstance.result
                .then(function (comment) {
                    if( comment.length )
                        completeSubComplaint(sub, comment);
                }, function () {
    
                });
            }
            else{
                completeSubComplaint(sub, sub.resolution);
            }
        });   
          
    }

    function completeSubComplaint(sub, comment) {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = sub.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/completesubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
            toaster.pop('success', MESSAGE_TITLE, 'Sub complaint is completed.');        
            sub.in_progress = 0;
            sub.status = 2;     
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onCancel = function(sub) {
        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input reason';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                cancelSubComplaint(sub, comment);
            }, function () {

            });
    }

    function cancelSubComplaint(sub, comment) {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = sub.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/cancelsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('success', MESSAGE_TITLE, 'Sub complaint is canceled.');        
            sub.in_progress = 0;
            sub.status = 5;            
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onReopensub = function(sub) {
        var request = {};

        request.id = sub.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/reopensubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            sub.status = 7;            
            toaster.pop('success', MESSAGE_TITLE, 'Sub Complaint is Reopened successfully.');   
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.deletefileForSubcomplaint = function(sub, $index)
    {
        var request = {};
        request.id = sub.id;
        request.index = $index;

        $http({
            method: 'POST',
            url: '/frontend/complaint/removesubfiles',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                           
           

        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.onChangeGuest = function() 
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/guest_change_dialog.html',
            controller: 'GuestChangeDialogCtrl',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },
          
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                updateGuestInfo(data);
            }
        }, function () {

        });
    }

    function updateGuestInfo(data) {
        var request = {};
        request = data;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/updateguest',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', MESSAGE_TITLE, 'Guest Information is updated successfully.');            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.$on('selected_complaint', function(event, args){
        $scope.init(args);        
    });

    $scope.$on('main_category_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.complaint.category_id = complaint.category_id;
        $scope.complaint.category_name = complaint.category_name;
    });

    $scope.$on('main_sub_category_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;
        
        $scope.complaint.subcategory_id = complaint.subcategory_id;
        $scope.complaint.subcategory_name = complaint.subcategory_name;
    });

    $scope.$on('main_severity_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.complaint.severity = complaint.severity;
        $scope.complaint.severity_name = complaint.severity_name;       

         // set severity
         $scope.category_list.forEach(ele => {
            if( ele.id == $scope.complaint.category_id )
            {
                ele.severity = $scope.complaint.severity;               
            }   
        });

        getComplaintLogs();
    });

    $scope.$on('maincomplaint_dept_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.selected_depts = complaint.selected_ids;
    });

    $scope.$on('maincomplaint_comment_added', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.comment_list = complaint.comment_list;        
        $scope.comment_list.forEach(function(row, index1){
            row.comment = row.comment.replace(/\r?\n/g,'<br/>');
            row.time = $scope.getTime(row);    
        });    
    });

    $scope.$on('maincomplaint_status_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.complaint.status = complaint.status;
        $scope.complaint.timeout_flag = complaint.timeout_flag;
        $scope.complaint.solution = complaint.solution;
        $scope.complaint.closed_flag = complaint.closed_flag;               
        $scope.complaint.closed_comment = complaint.closed_comment;               
        $scope.complaint.closed_time = complaint.closed_time;                  
    });

    $scope.$on('maincomplaint_guest_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.complaint.guest_type = complaint.guest_type;                                   
        $scope.complaint.room_id = complaint.room_id;
        $scope.complaint.guest_id = complaint.guest_id;
        $scope.complaint.guest_name = complaint.guest_name;
        $scope.complaint.housecomplaint_id = complaint.housecomplaint_id;    
        $scope.complaint.house_complaint_name = complaint.house_complaint_name;

        $scope.complaint.gs_guest_id =  complaint.profile.guest_id;

        GuestService.getPreviousHistoryList(complaint.profile.guest_id)
            .then(function (response) {
                $scope.historylist = response.data.datalist;
            }).catch(function (response) {

            });
    });

    $scope.$on('maincomplaint_compensation_create', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.comp_list = complaint.comp_list;        
    });

    $scope.$on('subcomplaint_create', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $timeout(function() {
            $scope.init($scope.complaint);
        }, 1000);    
    });

    $scope.$on('subcomplaint_status_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.init($scope.complaint);
    });

    $scope.$on('subcomplaint_files_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.exist_subcomplaint_list.forEach(row => {
            if( row.id == complaint.sub.id )
            {
                if( complaint.sub.path )
                    row.sub_download_array = complaint.sub.path.split("|");
                else
                    row.sub_download_array = [];

                row.sub_pdf_type_flag = row.sub_download_array.map(row1 => {
                    var extension = row1.substr((row1.lastIndexOf('.') +1));
                    return extension == 'pdf' || extension == 'eml' || extension == 'msg';                    
                });    

                row.sub_icon_class = row.sub_download_array.map(row1 => {
                    var extension = row1.substr((row1.lastIndexOf('.') +1));
                    if( extension == 'pdf' )
                        return 'fa-file-pdf-o';
                    if( extension == 'eml' )
                        return 'fa-envelope';
                    if( extension == 'msg' )
                        return 'fa-envelope';

                    return '';
                });    
            }
        });
    });

    $scope.$on('subcomplaint_compensation_create', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.exist_subcomplaint_list.forEach(row => {
            if( row.id == complaint.sub_id )
            {
                row.compensation_list = complaint.sub_comp_list;
                var sum = 0;
                row.compensation_list.forEach(item => {
                    sum += item.cost;
                });
                row.compensation_total = sum;
            }
        });
    });

    $scope.$on('subcomplaint_comment_added', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.id )
            return;

        $scope.exist_subcomplaint_list.forEach(row => {
            if( row.id == complaint.sub.id )
            {
                row.comment_list = complaint.sub.comment_list;
                row.comment_list.forEach(function(row1, index1){
                    row1.comment = row1.comment.replace(/\r?\n/g,'<br/>');                
                    row1.time = $scope.getTime(row1);    
                });
            }
        });
    });


    function getComplaintCategoryList() {
        $scope.sub_category_list = [];
        $scope.sub_subcategory_list = [];

        var profile = AuthService.GetCredentials();

        var request = {};

   //     request.dept_id = profile.dept_id;
        request.dept_id = 0;

        $http({
            method: 'POST',
            url: '/frontend/complaint/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.sub_category_list = response.data;   
         //   getComplaintSubcategoryList($scope.sub_category_list., $scope.sub);      
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    function setNonEditSubState(sub) 
    {
        sub.subc_category_edit_flag = false;
        sub.subc_subcategory_edit_flag = false;
        sub.subc_location_edit_flag = false;      
        sub.comment_edit_flag = false;
        sub.resolution_edit_flag = false;
    }

    $scope.onClickSubcomplaintPanel = function($event, sub) {
        // if( $event.target.classList && 
        //     $event.target.classList.value && 
        //     $event.target.classList.value.includes("non-edit") )
            setNonEditSubState(sub);
    }

    $scope.onEditSubcomplaintCategory = function(sub) {
        setNonEditSubState(sub);
        if( $scope.category_change == true && $scope.complaint.closed_flag != 1 )
        {
            sub.subc_category_edit_flag = true;
        }
    }

    $scope.onsubcompCategorySelect = function ($item, $model, $label, sub) {
        console.log($item);
        $scope.sub = sub;
        $scope.sub.category_id = $item.id;
        
        getComplaintSubcategoryList($item.id , sub);

        var request = {};
        request.id = $scope.sub.id;
        request.category_id = $item.id;
        $http({
            method: 'POST',
            url: '/frontend/complaint/savecategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);     
            sub.subc_category_edit_flag = false;
            sub.category_name = response.data.category_name;
            toaster.pop('info', MESSAGE_TITLE, 'Sub Complaint Category changed successfully.');    
            $scope.$emit('onChangedComplaint', response.data);                   
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

  //  getComplaintSubcategoryList($scope.sub, $scope.sub);

    $scope.onEditSubcomplaintSubCategory = function(sub) {
        setNonEditSubState(sub);
        if( $scope.category_change == true && $scope.complaint.closed_flag != 1 )
        {
            sub.subc_subcategory_edit_flag = true;            
        }
    }

    function getComplaintSubcategoryList(category_id, sub) {
       $scope.sub_subcategory_list = [];
        $scope.sub = sub;
        $scope.sub.subcategory_name = '';
        $scope.sub.subcategory_id = 0;

        var request = {};
        request.category_id = category_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/subcategorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.sub_subcategory_list = response.data;            
            if($scope.sub_subcategory_list.length > 0)
            {
                $scope.sub.subcategory_name = $scope.sub_subcategory_list[0].name;
                $scope.sub.subcategory_id = $scope.sub_subcategory_list[0].id;
                return;
            }
            else
            {
                $scope.sub.subcategory_name = '';
                $scope.sub.subcategory_id = 0;
            } 
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.onsubSubcategorySelect = function ($item, $model, $label,sub) {
        $scope.sub = sub;
        $scope.sub.subcategory_id = $item.id; 

        var request = {};
        request.id = $scope.sub.id;
        request.subcategory_id = $item.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/savesubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);    
            sub.subc_subcategory_edit_flag = false;
            toaster.pop('info', MESSAGE_TITLE, 'Sub Complaint Subcategory changed successfully.');    
            $scope.$emit('onChangedComplaint', response.data);                     
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.editSubcomment = function(sub) {
        setNonEditSubState(sub);
        if( $scope.closed_flag == 1 )
            return;

        sub.comment_edit_flag = true;
        sub.original_comment = sub.comment + '';        
    }

    $scope.cancelSubComment = function(sub) {
        sub.comment_edit_flag = false;
        sub.comment = sub.original_comment + '';        
    }

    $scope.changesubComment = function (sub) {
        var request = sub;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changesubcomment',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            sub.comment_edit_flag = false;
            toaster.pop('success', MESSAGE_TITLE, 'Comment has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Comment.');
        })
            .finally(function () {

            });
    }

    $scope.editSubResolution = function(sub) {
        setNonEditSubState(sub);
        if( $scope.complaint.closed_flag == 1 )
            return;

        sub.resolution_edit_flag = true;
        sub.original_resolution = sub.resolution + '';        
    }

    $scope.cancelSubResolution = function(sub) {
        sub.resolution_edit_flag = false;
        sub.resolution = sub.original_resolution + '';        
    }

    $scope.changesubSolution = function (sub) {
        var request = sub;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changesubsolution',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            sub.resolution_edit_flag = false;
            toaster.pop('success', MESSAGE_TITLE, 'Resolution has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Resolution.');
        })
            .finally(function () {

            });
    }

    $scope.onEditSubcomplaintLocation = function(sub) {
        setNonEditSubState(sub);
        if( $scope.category_change == true && $scope.complaint.closed_flag != 1 )
        {
            sub.subc_location_edit_flag = true;            
        }
    }

    $scope.onsubLocationSelect = function ($item, $model, $label,sub) {
        $scope.sub = sub;
        $scope.sub.location_name = $item.name;
        $scope.sub.location_id = $item.id;
        $scope.sub.location_type = $item.type;


        var request = {};
        request.id = $scope.sub.id;
        request.location_id = $item.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/savelocation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);   
            sub.subc_location_edit_flag = false;              
            toaster.pop('success', MESSAGE_TITLE, 'Location has been Updated Successfully.');    
            $scope.$emit('onChangedComplaint', response.data);                     
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    function confirmDeletesubComplaint(row, callback)
    {
        if( row.total < 1 )
        {
            callback(row);
            return;
        }

        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure you want to delete the Sub Complaint?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');                    
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                callback(row);         
        }, function () {

        });    
    }

    $scope.onDeletesub = function(row) {
        confirmDeletesubComplaint(row, function(row) {
            var modalInstance = $uibModal.open({
                templateUrl: 'tpl/modal/modal_input.html',
                controller: 'ModalInputCtrl',
                scope: $scope,
                resolve: {
                    title: function () {
                        return 'Please input reason to delete the Sub Complaint';
                    },
                    min_length: function () {
                        return 0;
                    }
                }
            });
    
            modalInstance.result
                .then(function (comment) {
                    deletesubComplaint(comment,row);
                }, function () {
    
                });
        });
    }

    function deletesubComplaint(comment,row) {
        var request = {};

        request.id = row.id;
        console.log(row);
        request.parent_id = row.parent_id;
        request.comment = comment;

        if( request.comment == null )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please enter Comment');
            return;
        }

        
        $http({
            method: 'POST',
            url: '/frontend/complaint/deletesubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.$emit('onChangedComplaint', response.data);         
            toaster.pop('info', MESSAGE_TITLE, 'Sub Complaint deleted successfully.');
        }).catch(function(response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to delete Sub Complaint.');
        })
        .finally(function() {

        });
        
    }

    function setNonEditState() 
    {
        $scope.feedback_edit_flag = false;
        $scope.initial_response_edit_flag = false;
        $scope.solution_edit_flag = false;
        $scope.feedback_type_edit_flag = false;
        $scope.feedback_source_edit_flag = false;        
        $scope.incident_time_edit_flag = false;
        $scope.category_edit_flag = false;
        $scope.subcategory_edit_flag = false;
        $scope.severity_edit_flag = false;
        $scope.location_edit_flag = false;
    }

    $scope.onClickFeedbackDetail = function($event) {   
        if( $event.target.classList && 
            $event.target.classList.value && 
            $event.target.classList.value.includes("non-edit") )
            return;

        setNonEditState();
    }

    $scope.feedback_edit_flag = false;
    var feedback_text = '';

    $scope.editFeedback = function(complaint) {     
        setNonEditState();   
        if( $scope.can_be_edit == false )
            return;

        feedback_text = complaint.comment + '';
        $scope.feedback_edit_flag = true;
    }

    $scope.cancelFeedback = function(complaint) {
        setNonEditState();
        complaint.comment = feedback_text + '';;
        $scope.feedback_edit_flag = false;
    }

    $scope.changeFeedback = function (complaint) {
        var request = complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changefeedback',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.feedback_edit_flag = false;            
            toaster.pop('success', MESSAGE_TITLE, 'Feedback has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Feedback.');
        })
            .finally(function () {

            });
    }

    $scope.initial_response_edit_flag = false;
    var initial_response_text = '';

    $scope.editInitialResponse = function(complaint) {
        setNonEditState();
        if( $scope.can_be_edit == false )
            return;

        initial_response_text = complaint.initial_response + '';
        $scope.initial_response_edit_flag = true;
    }

    $scope.cancelInitialResponse = function(complaint) {
        complaint.initial_response = initial_response_text + '';;
        $scope.initial_response_edit_flag = false;
    }

    $scope.changeInitialResponse = function (complaint) {
        var request = complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changeinitresponse',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.initial_response_edit_flag = false;
            toaster.pop('success', MESSAGE_TITLE, 'Initial Response has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Initial Response.');
        })
            .finally(function () {

            });
    }

    $scope.onFeedbackTypeSelect = function(complaint) {
        var request = complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changefeedbacktype',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.complaint.feedback_type = response.data.feedback_type;
            toaster.pop('success', MESSAGE_TITLE, 'Feedback Type has been Updated Successfully.');
            setNonEditState();
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Feedback Type.');
        })
            .finally(function () {

            });
    }

    $scope.onFeedbackSourceSelect = function(complaint) {
        var request = complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changefeedbacksource',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.complaint.feedback_source = response.data.feedback_source;
            toaster.pop('success', MESSAGE_TITLE, 'Feedback Source has been Updated Successfully.');
            setNonEditState();
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Feedback Source.');
        })
            .finally(function () {

            });
    }

    $scope.solution_edit_flag = false;
    var resolution_text = '';

    $scope.editResolution = function(complaint) {
        setNonEditState();
        if( complaint.status != "Resolved" )
            return;

        if( $scope.can_be_edit == false )
            return;    

        resolution_text = complaint.solution + '';
        $scope.solution_edit_flag = true;
    }

    $scope.cancelResolution = function(complaint) {
        complaint.solution = resolution_text + '';;
        $scope.solution_edit_flag = false;
    }

    $scope.changeResolution = function(complaint) {
        var request = complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changeresolution',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.solution_edit_flag = false;
            toaster.pop('success', MESSAGE_TITLE, 'Resolution has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Resolution.');
        })
            .finally(function () {

            });
    }

    $scope.feedback_type_edit_flag = false;
    $scope.onEditFeedbackType = function()
    {
        setNonEditState();
        if( $scope.can_be_edit && $scope.complaint.closed_flag != 1 )
            $scope.feedback_type_edit_flag = true;
    }

    $scope.feedback_source_edit_flag = false;
    $scope.onEditFeedbackSource = function()
    {
        setNonEditState();
        if( $scope.can_be_edit )
            $scope.feedback_source_edit_flag = true;
    }

    $scope.incident_time_edit_flag = false;
    $scope.onEditIncidentTime = function()
    {
        setNonEditState();
        if( $scope.can_be_edit && $scope.complaint.closed_flag != 1 )        
            $scope.incident_time_edit_flag = true;
    }

    $scope.category_edit_flag = false;
    $scope.onEditCategory = function()
    {
        setNonEditState();
        if( $scope.can_be_edit )        
            $scope.category_edit_flag = true;
    }

    $scope.subcategory_edit_flag = false;
    $scope.onEditSubCategory = function()
    {
        setNonEditState();
        if( $scope.can_be_edit )        
            $scope.subcategory_edit_flag = true;
    }


    $scope.severity_edit_flag = false;
    $scope.onEditSeverity = function()
    {
        setNonEditState();
        if( $scope.can_be_edit && $scope.complaint.closed_flag != 1 )
            $scope.severity_edit_flag = true;
    }

    $scope.location_edit_flag = false;
    $scope.onEditLocation = function()
    {
        setNonEditState();
        if( $scope.can_be_edit && $scope.complaint.closed_flag != 1 )
            $scope.location_edit_flag = true;
    }


    $scope.$watch('complaint.timepicker', function(newValue, oldValue) {
        if( newValue == oldValue )
            return;

        console.log(newValue);
        $scope.complaint.incident_time = moment(newValue).format('YYYY-MM-DD HH:mm:ss');

        var request = $scope.complaint;

        $http({
            method: 'POST',
            url: '/frontend/complaint/changeincidentime',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Incident Time has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Incident Time.');
        })
            .finally(function () {

            });

    });

    $scope.beforeRender = function ($view, $dates, $leftDate, $upDate, $rightDate) {

        if ($view == 'day') {
            var activeDate = moment().subtract(1,'days');
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() < activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
        
        else
     if( $view == 'minute' )
        {
            var activeDate = moment().subtract('minute', 5);
            for (var i = 0; i < $dates.length; i++) {
                if ($dates[i].localDateValue() > activeDate.valueOf())
                    $dates[i].selectable = false;
            }
        }
    }

    $scope.tab_index = 1;
    $scope.onSelectServiceRecovery = function()
    {
        $scope.tab_index = 1;
    }

    $scope.onSelectSubcomplaint = function()
    {
        $scope.tab_index = 2;
    }

    $scope.onSelectComments = function()
    {
        $scope.tab_index = 3;
    }

    $scope.onSelectHistory = function()
    {
        $scope.tab_index = 4;
    }

    $scope.onEditComment = function(row)
    {        
        $scope.comment_list.map(function(item) {            
            item.edit_flag = false;
        });

        row.edit_flag = true;
        row.original_comment = row.comment + '';
    }

    $scope.onDeleteComment = function(row)
    {
        var request = row;        

        $http({
            method: 'POST',
            url: '/frontend/complaint/deletecomment',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            $scope.comment_list = $scope.comment_list.filter( item => item.id != row.id);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Incident Time.');
        })
            .finally(function () {

            });
    }

    $scope.onSaveComment = function(row)
    {
        var request = row;        

        $http({
            method: 'POST',
            url: '/frontend/complaint/updatecomment',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            row.edit_flag = false;            
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Incident Time.');
        })
            .finally(function () {

            });

    }

    $scope.onCancelComment = function(row)
    {
        row.edit_flag = false;
        row.comment = row.original_comment + '';
    }

});

app.controller('SubcomplaintDialogCtrl', function($scope, $http, AuthService, $uibModalInstance, $uibModal, complaint, complaint_list, severity_list, dept_list, complaint_department, complaint_usergroup, severity) {
    $scope.complaint = complaint;
    $scope.complaint_list = complaint_list;
    $scope.severity_list = severity_list;
    $scope.dept_list = dept_list;
    console.log($scope.dept_list);
    $scope.complaint_department = complaint_department;
    $scope.complaint_usergroup = complaint_usergroup;
    $scope.staff_list = {};

    $scope.category_list = [];
    $scope.subcategory_list = [];

    $scope.sub = {};
    $scope.sub.compensation_id = 0;
    $scope.sub.sub_provider_id = 0;
    $scope.sub.location_id = 0;
    $scope.sub.complaint_id = 0;
    $scope.sub.complaint_name = '';
    $scope.sub.dept_selectable = true;
    $scope.sub.cost = 0;
    $scope.sub.comment = '';
    $scope.sub.init_response = '';
    $scope.sub.files = [];
    $scope.sub.compensation_list = [];
    $scope.sub.comp = {};
    $scope.sub.severity = severity;
    $scope.dept_loc_list = [];

    if( $scope.dept_list.length == 1 )  // only one department
    {
        $scope.sub.dept_id = $scope.dept_list[0].id;
        $scope.sub.department = $scope.dept_list[0].department;
        selectAssignee($scope.sub);
    }

    $scope.ok = function () {
        $uibModalInstance.close($scope.sub);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    function getSubComplaintCategoryList() {
        $scope.category_list = [];
        $scope.subcategory_list = [];

        var request = {};

        request.dept_id = 0;

        $http({
            method: 'POST',
            url: '/frontend/complaint/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.category_list = response.data;            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    getSubComplaintCategoryList();

    function getStaffList(sub)
    {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        $http.get('/list/userlist?property_id=' + property_id + '&dept_id=0')
            .then(function(response) {
                $scope.staff_list = response.data;                
        });
    }

    getStaffList();


    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.sub.category_id = $item.id;
        getSubComplaintSubcategoryList($item.id);
    }

    $scope.onSubcategorySelect = function ($item, $model, $label) {
        $scope.sub.subcategory_id = $item.id;
      //  getSubComplaintSubcategoryList($item.id);
    }

    $scope.setSubComplaintCategoryList = function(list) {
        $scope.category_list = list;
    } 

    $scope.createCategory = function () {        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_category.html',
            controller: 'SubComplaintCategoryCtrl',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.sub;
                },
                category_list: function () {
                    return $scope.category_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }


    function getSubComplaintSubcategoryList(category_id) {
        $scope.subcategory_list = [];
        $scope.sub.subcategory_name = '';
        $scope.sub.subcategory_id = 0;

        var request = {};
        request.category_id = category_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/subcategorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.subcategory_list = response.data;            
            if($scope.subcategory_list.length > 0)
            {
                $scope.sub.subcategory_name = $scope.subcategory_list[0].name;
                $scope.sub.subcategory_id = $scope.subcategory_list[0].id;
                return;
            }
            else
            {
                $scope.sub.subcategory_name = '';
                $scope.sub.subcategory_id = 0;
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.setComplaintSubcategoryList = function(list) {
        $scope.subcategory_list = list;
    }

    $scope.createSubcategory = function () {        
        if( !($scope.sub.category_id > 0) )
           return;

       var modalInstance = $uibModal.open({
           templateUrl: 'tpl/complaint/complaint_subcategory.html',
           controller: 'SubComplaintSubcategoryCtrl',
           scope: $scope,
           resolve: {
               complaint: function () {
                   return $scope.sub;
               },
               subcategory_list: function () {
                   return $scope.subcategory_list;
               }
           }
       });

       modalInstance.result.then(function (selectedItem) {
       }, function () {

       });
    }

    $scope.onSelectDepartment = function(sub, $item, $model, $label) {
        sub.dept_id = $item.id;
        selectAssignee(sub);              
    }

    function getDeptLocationList(sub)
    {        
        // find assignee
        $http({
            method: 'POST',
            url: '/frontend/complaint/getdeptloclist',
            data: sub,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.dept_loc_list = response.data;        
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }
    function selectAssignee(sub) {
        getDeptLocationList(sub);

        var assignee_id = -1;
        var assignee_name = '';

        // select Default Asignee
        if( sub.dept_id > 0 )
        {
            for(var i = 0; i < $scope.dept_list.length; i++)
            {
                if( sub.dept_id == $scope.dept_list[i].id )
                {
                    assignee_id = $scope.dept_list[i].user_id;
                    assignee_name = $scope.dept_list[i].wholename;
                    break;
                }
            }
        }

        if( assignee_id > 0 )
        {
            sub.assignee_id = assignee_id;
            sub.assignee_name = assignee_name;
        }
        else
        {
            sub.assignee_id = 0;
            if( sub.dept_selectable == true  )
                sub.assignee_name = '';
            else
                sub.assignee_name = 'No default Assignee';
        }

        // find user group id
        var usergroup_id = -1;
        for(var i = 0; i < $scope.complaint_usergroup.length; i++)
        {
            if( $scope.complaint_usergroup[i].complaint_id == sub.complaint_id )
            {
                usergroup_id = $scope.complaint_usergroup[i].usergroup_id;
                break;
            }
        }

        if( usergroup_id > 0 )        
        {        
            var profile = AuthService.GetCredentials();
            var property_id = profile.property_id;

            var request = {};

            request.property_id = property_id;
            request.complaint_id = sub.complaint_id;
            request.usergroup_id = usergroup_id;
            request.dept_id = sub.dept_id;
            request.loc_id = $scope.complaint.loc_id;

            // find assignee
            $http({
                method: 'POST',
                url: '/frontend/complaint/selectassignee',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {
                console.log(response);                
                if( response.data.length > 0 )  // CASE 4: Exist Complaint : System choose asignee
                {                   
                    sub.assignee_id = response.data[0].user_id;
                    sub.assignee_name = response.data[0].wholename;
                }                
            
            }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
        }
    }

    $scope.onComplaintSelect = function (sub, $item, $model, $label) {
        sub.complaint_id = $item.id;
        sub.severity = $item.type_id;   
        sub.comment = '';     
    };

    $scope.updateComplaintTypeList = function(list){
        $scope.complaint_list = list;
    }

    $scope.createComplaintType = function () {        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/complaint_type_list.html',
            controller: 'SubComplaintTypeCtrl',
            scope: $scope,
            resolve: {               
                complaint_type_list: function () {
                    return $scope.complaint_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

    $scope.onCompensationSelect = function(sub, $item, $model, $label) {
        sub.comp.compensation_id = $item.id;    
        sub.comp.cost = $item.cost;    
    }

    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.sub.location_name = $item.name;
        $scope.sub.location_id = $item.id;
    };

   $scope.onProviderSelect = function(sub, $item, $model, $label) {
       sub.comp.sub_provider_id = $item.id;        
   }

    $scope.uploadFiles = function (files) {
        $scope.sub.files = $scope.sub.files.concat(files);
    };

    
    $scope.onAddCompensation = function() {
        if( !($scope.sub.comp.compensation_id > 0) )
            return;

        if( !($scope.sub.comp.sub_provider_id > 0) )
            return;

        $scope.sub.compensation_list.push(angular.copy($scope.sub.comp));

        $scope.sub.comp = {};
    }

    $scope.removeFile = function($index)
    {
        $scope.sub.files.splice($index, 1);
    }
});

app.controller('ComplaintDetailCtrl1', function($scope, $uibModalInstance, $http, AuthService, complaint) {
    $scope.complaint = complaint;
    $scope.comment_list = [];

    function getCommentList() {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.comment_list = response.data;
            for(var i = 0; i < $scope.comment_list.length; i++)
            {
                $scope.comment_list[i].comment = $scope.comment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    getCommentList();

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.getTicketNumber = function (ticket) {
        if(!ticket)
            return 'F00000';

        return sprintf('F%05d', ticket.id);
    };

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    
});

app.controller('CompensationDetailCtrl', function($scope, $uibModalInstance, $http, toaster, AuthService, comp) {
    $scope.comp = comp;
    $scope.comment_list = [];

    function getCommentList() {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.comp.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getcompensationcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.comment_list = response.data;
            for(var i = 0; i < $scope.comment_list.length; i++)
            {
                $scope.comment_list[i].comment = $scope.comment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    getCommentList();

    $scope.addCommentToReturn = function(comment) {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.comp.id;
        request.comment = comment;
        request.user_id = profile.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/addcommentoreturn',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            var data = response.data;

            if( data.code != 200 )
                toaster.pop('error', 'Duty Manager', data.message);    
            else
            {
                $scope.comment_list = response.data.comment_list;
                for(var i = 0; i < $scope.comment_list.length; i++)
                {
                    $scope.comment_list[i].comment = $scope.comment_list[i].comment.replace(/\r?\n/g,'<br/>');
                }
            }
        }).catch(function(response) {
            toaster.pop('error', 'Duty Manager', 'Failed to post Compensation.');
        })
        .finally(function() {

        });        
    }

   
    $scope.getTicketNumber = function (ticket) {
        if(!ticket)
            return 'C00000';

        return sprintf('C%05d', ticket.id);
    };

    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    
});


app.controller('ComplaintCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint, category_list) {
    var profile = AuthService.GetCredentials();

    $scope.complaint = complaint;
    $scope.cateory_list = category_list;    
    $scope.complaint.new_severity = $scope.severity_list[0].id;
    $scope.complaint.new_division = $scope.division_list[0].id;
    
    $scope.selected_category_id = 0;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.id = $scope.selected_category_id;
        request.name = $scope.complaint.category_new_name;        
        request.severity = $scope.complaint.new_severity;
        request.division_id = $scope.complaint.new_division;
        request.user_id = profile.id;
        request.property_id = profile.property_id;

        if( !request.name )
            return;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/createmaincategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);        
            $scope.selected_category_id = 0;        
            $scope.complaint.category_new_name = '';        
            $scope.category_list = response.data;       

            var alloption = {id: 0, name : 'Unclassified'};
            $scope.category_list.unshift(alloption);

            $scope.setComplaintCategoryList($scope.category_list);    
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.onClickUpdate = function(row) {
        $scope.selected_category_id = row.id;
        $scope.complaint.category_new_name = row.name;
        $scope.complaint.new_severity = row.severity;
        $scope.complaint.new_division = row.division_id;
    }

    $scope.onClickDelete = function(row) {
        var request = {};
        request.id = row.id;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/deletemaincategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);        
            $scope.complaint.category_new_name = '';        
            $scope.category_list = response.data;       

            var alloption = {id: 0, name : 'Unclassified'};
            $scope.category_list.unshift(alloption);

            $scope.setComplaintCategoryList($scope.category_list);    
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

});


app.controller('SubComplaintCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint, category_list) {
    $scope.complaint = complaint;
    $scope.cateory_list = category_list;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.complaint.category_new_name;
        request.dept_id = complaint.dept_id;
        request.user_id = profile.id;

        if( !request.name )
            return;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/createcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);        
            $scope.complaint.category_new_name = '';        
            $scope.category_list = response.data;        
            $scope.setSubComplaintCategoryList($scope.category_list);    
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('SubComplaintSubcategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint, subcategory_list) {
    $scope.complaint = complaint;
    $scope.subcategory_list = subcategory_list;

    $scope.createSubcategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.complaint.subcategory_new_name;
        request.category_id = $scope.complaint.category_id;
        request.user_id = profile.id;

        if( !request.name )
            return;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/createsubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);        
            $scope.complaint.subcategory_new_name = '';        
            $scope.subcategory_list = response.data;  
            $scope.setComplaintSubcategoryList($scope.subcategory_list);          
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('SubComplaintTypeCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint_type_list) {
    $scope.sub = {};
    $scope.complaint_type_list = complaint_type_list;

    $scope.createType = function () {
        if( !$scope.sub.type_new_name || $scope.sub.type_new_name == '' )
            return;

        // check exist complaint name
        var exist_flag = false;
        for(var i = 0; i < $scope.complaint_type_list.length; i++)
        {
            if($scope.sub.type_new_name == $scope.complaint_type_list[i].complaint )
            {
                exist_flag = true;
                break;
            }
        }

        if( exist_flag == false )
        {
            var request = {};
            request.complaint = $scope.sub.type_new_name;
            request.type_id = 1;

            // post new complaint
            $http({
                method: 'POST',
                url: '/frontend/complaint/createcomplaintitem',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response) {
                console.log(response);      
                $scope.complaint_type_list =  response.data.list;
                $scope.updateComplaintList(response.data.list);   
                $scope.updateComplaintTypeList(response.data.list);
            }).catch(function(response) {
                // CASE 3: NO Asignee Found on shift : Default Asignee
            })
            .finally(function() {

            });
        }
    };

    
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});


app.controller('GuestChangeDialogCtrl', function($scope, $http, AuthService, $uibModalInstance, $uibModal, toaster, complaint) {
    $scope.complaint = angular.copy(complaint);    
    var profile = AuthService.GetCredentials();
    var client_id = profile.client_id;
    
    $scope.onChangedProperty = function() {
        $scope.location = {};
        $http.get('/list/roomlist?property_id=' + $scope.complaint.property_id)
            .then(function(response){
                $scope.roomlist = response.data;
            });
    }

    $scope.onChangedProperty();

    $scope.onChangeGuestType = function() {
        $scope.complaint.room = '';
        $scope.complaint.room_id = 0;
        $scope.complaint.guest_id = 0;
        $scope.complaint.profile_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.departure = '';
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.arrival = '';
        $scope.complaint.departure = '';
        $scope.complaint.nationality = '';
        $scope.complaint.new_guest = false; 
        if( $scope.housecategory_list && $scope.housecategory_list.length > 0 )
            $scope.complaint.housecomplaint_id = $scope.housecategory_list[0].id;
    }

    $scope.onSearchCheckoutGuest = function()
    {        
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/complaint/modal/checkout_guest_dialog.html',
            controller: 'CheckoutGuestListCtrl1',
            size: 'lg',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                property_id: function () {
                    return $scope.complaint.property_id;
                },
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                $scope.complaint.room = data.room;
                $scope.complaint.room_id = data.room_id;
                $scope.complaint.guest_id = data.guest_id;
                $scope.complaint.profile_id = data.profile_id;
                $scope.complaint.guest_name = data.guest_name;        
                $scope.complaint.mobile = data.mobile;
                $scope.complaint.email = data.email;
                $scope.complaint.new_guest = false; 
                $scope.complaint.first_name = data.first_name;
                $scope.complaint.arrival = data.arrival;
                $scope.complaint.departure = data.departure;
                $scope.complaint.nationality = data.nationality;
            }
        }, function () {

        });  
    }

    $scope.onRoomSelect = function ($item, $model, $label) {
        $scope.complaint.room_id = $item.id;

        $scope.complaint.guest_id = 0;
        $scope.complaint.profile_id = 0;
        $scope.complaint.guest_name = '';        
        $scope.complaint.mobile = '';
        $scope.complaint.email = '';
        $scope.complaint.nationality = '';
        $scope.complaint.new_guest = false; 

        if( $scope.complaint.guest_type == 'In-House')   // check in user            
        {
            // find checkin user
            $http.get('/frontend/complaint/findcheckinguest?room_id=' + $item.id)
                .then(function(response){
                    var data = response.data;

                    if( data.code != 200 )
                    {
                        toaster.pop('info', MESSAGE_TITLE, 'No guest check in and change the type to CO');
                        return;
                    }

                    $scope.complaint.guest_name = data.data.guest_name;
                    $scope.complaint.first_name = data.data.first_name;
                    $scope.complaint.arrival = data.data.arrival;
                    $scope.complaint.departure = data.data.departure;
                    $scope.complaint.guest_id = data.data.guest_id;
                    $scope.complaint.profile_id = data.data.profile_id;
                    $scope.complaint.mobile = data.data.mobile;
                    $scope.complaint.email = data.data.email;
                    $scope.complaint.nationality = data.data.nationality;
                });
        }        
    };

    $scope.onChangeNewGuest = function() {
        if( $scope.complaint.new_guest == true )
        {
            $scope.complaint.guest_name = ''
            $scope.complaint.guest_id = 0;
            $scope.complaint.profile_id = 0;
            $scope.complaint.mobile = '';
            $scope.complaint.email = '';
            $scope.complaint.nationality = '';
        }
    }

    $scope.getGuestList = function(val) {
        if( $scope.complaint.new_guest == true )
            return;

        if( val == undefined )
            val = "";

        var request = {};        
        var property_id = $scope.complaint.property_id;
        request.client_id = client_id;
        request.property_id = property_id;
        request.value = val;
        request.guest_type = $scope.complaint.guest_type;
        request.room_id = $scope.complaint.room_id;

        return $http({
            method: 'POST',
            url: '/frontend/complaint/searchguestlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response){
                var list = response.data.content.slice(0, 10);
                return list.map(function(item){
                    return item;
                });
            });
    };

    $scope.onGuestSelect = function ($item, $model, $label) {
        $scope.complaint.guest_name = $item.guest_name;
        $scope.complaint.first_name = $item.first_name;        
        $scope.complaint.guest_id = $item.guest_id;  
        $scope.complaint.profile_id = $item.profile_id;        
        $scope.complaint.mobile = $item.mobile;
        $scope.complaint.email = $item.email;      
        $scope.complaint.arrival = $item.arrival;
        $scope.complaint.departure = $item.departure;
        $scope.complaint.nationality = $item.nationality;
        $scope.complaint.new_guest = false;        
    };


    $scope.ok = function () {
        $uibModalInstance.close($scope.complaint);            
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
});

app.controller('CheckoutGuestListCtrl1', function ($scope, $uibModalInstance, $http, AuthService, property_id) {
    $scope.filter = {};
    $scope.guest_list = [];
    
    var profile = AuthService.GetCredentials();
    if( !(property_id > 0) )
        property_id = profile.property_id;

    var PAGE_SIZE = 20;
    $scope.totalGuestCount = PAGE_SIZE;

    $scope.onSearchCheckoutGuest = function () {

        var request = $scope.filter;
        request.property_id = property_id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/searchcheckoutguest',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        }).then(function (response) {
            console.log(response);
            $scope.guest_list = response.data;
        }).catch(function (response) {
        })
            .finally(function () {

            });
    };

    $scope.onSelectGuest = function(row)
    {
        $uibModalInstance.close(row);    
    }

    $scope.onLoadMoreGuestHistory = function() {
        $scope.totalGuestCount += PAGE_SIZE; 
    }

    $scope.onRoomSelect1 = function ($item, $model, $label) {
        $scope.filter.room_id = $item.id;
    };

    $scope.clear = function() {
        $scope.filter = {};
    }

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('ComplaintFileUploadDialogCtrl', function ($scope, $uibModalInstance, $http, AuthService, complaint) {
    $scope.onUploadFiles = function() {
        $scope.upload();
        $uibModalInstance.close();   
    }

    $scope.removeFile = function($index) {
        $scope.files.splice($index, 1);
    }

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('ComplaintServiceRecoveryDialogCtrl', function ($scope, $uibModalInstance, $http, AuthService, complaint) {
    $scope.onPostCompensation = function() {
        $scope.postCompensation();
        $uibModalInstance.close(); 
    }

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});