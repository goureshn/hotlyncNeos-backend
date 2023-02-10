app.controller('ComplaintGRLogValidationController', function ($scope, $rootScope, $http, $httpParamSerializer, $window, $interval, $uibModal, AuthService, GuestService, toaster ,liveserver) {
    var MESSAGE_TITLE = 'Guest Feedback';
		
	$scope.full_height = $window.innerHeight - 125;
	 $scope.tab_height = $window.innerHeight - 100;
		
    $scope.complaint = {};
    $scope.guest_id_editable = false;
    $scope.genders = ['Male', 'Female'];
    $scope.exist_subcomplaint_list = [];
    $scope.guest_history = {};
    $scope.subflag=0;
    $scope.dept="";
    
    var depts_list = [];
    $scope.depts=[];
    $scope.department=[];
    $scope.selDept=[];
    $scope.selected_depts = [];
    $scope.tagsString= [];

    $scope.forward_flag = false;
    $scope.init = function(complaint) {
      
        var profile = AuthService.GetCredentials();
        //console.log("checking");
        

        $scope.can_be_edit = AuthService.isValidModule('app.complaint.complaint_edit');
        $scope.category_editable = AuthService.isValidModule('app.complaint.maincategory_add');
        $scope.disable_all = !$scope.can_be_edit;

        
        $scope.complaint = complaint;
       // window.alert($scope.disable_all);
        
        $scope.complaint.location = complaint.lgm_type + ' - ' + complaint.lgm_name;
        //$scope.guest_id_editable = !(complaint.guest_id > 0);
        //$scope.comment_list = [];
        //$scope.complaint.approval_route_flag = complaint.approval_route_id > 0;
        $scope.comp = {};

        $scope.complaint.guest_is_open = true;
        //$scope.complaint.compensation_comment_is_open = false;
        //$scope.complaint.running_subcomplaint_is_open = false;
        $scope.complaint.complaint_comment_is_open = false;
        $scope.forward_flag = $scope.complaint.lg_property_id == profile.property_id;

        if( complaint.path )
            $scope.complaint.download_array = complaint.path.split("|");
        else
            $scope.complaint.download_array = [];

        if( complaint.gender == null )
            $scope.complaint.gender = $scope.genders[0];

        complaint.modified_by = profile.id;

        /*$http.get('/frontend/complaint/compensationtype?client_id='+profile.client_id)
            .then(function(response) {
                $scope.compensations =  response.data;              
            });*/

		//toaster.pop('info', MESSAGE_TITLE, 'Please select Guest');

        //complaint.latest = 1;
        //complaint.active = true;
        //$scope.$emit('onUpdateComplaint', complaint);    

        
              

        // refresh keywords    
        var comment_highlight = $scope.complaint.comment_highlight + '';
        var response_highlight = $scope.complaint.response_highlight + '';
        $scope.complaint.comment_highlight = undefined;
        $scope.complaint.response_highlight = undefined;

        /*$http.get('/list/severitylist')
            .then(function(response) {
            $scope.complaint.comment_highlight = comment_highlight;
            $scope.complaint.response_highlight = response_highlight;

            $scope.severity_list = response.data; 
        });

        getCommentList(); 
        getComplaintInfo();
        getGuestHistory();
        getComplaintLogs();
        $scope.loadDepts();*/
    }
    
 
     
    

    
    /*$scope.loadFiltersValue = function(value,query) {
	    
     $scope.department = depts_list.filter(function(item) { 
	        			if(item.department.toLowerCase().indexOf(query.toLowerCase()) != -1)
							return item.department; });
							
      $scope.depts = $scope.department.map(function(tag) { return tag.department; });
        
        return $scope.depts;
       }*/
    
 
	
    

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

    $scope.onClose = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'modal_input.html',
            controller: 'ModalInputCtrl',
            scope: $scope,
            resolve: {
                title: function () {
                    return 'Please input resolution';
                },
                min_length: function () {
                    return 0;
                }
            }
        });

        modalInstance.result
            .then(function (comment) {
                closeComplaint(comment);
            }, function () {

            });
    }

/*   
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
            });
        }).catch(function(response) {
            
        })
        .finally(function() {

        });
    }

    

    $scope.viewGuestHistory = function() {
        $scope.complaint.history_view = true;
    }

    $scope.hideGuestHistory = function() {
        $scope.complaint.history_view = false;
    }*/

    $scope.showComplaint = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'complaint_detail.html',
            controller: 'ComplaintDetailCtrl1',
            scope: $scope,
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

    

    

    
   
   

    
    

    $scope.getTicketNumber = function(ticket){
        if(!ticket)
            return 'GR00000';
        return sprintf('GR%05d%s', ticket.parent_id, ticket.sub_label);        
    }

    $scope.getComplaintNumber = function(ticket){
        if(!ticket)
            return 'GR00000';
        return sprintf('GR%05d', ticket.id);        
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

   /* function getComplaintInfo(callback) {
        var profile = AuthService.GetCredentials();
        var property_id = $scope.complaint.lg_property_id;
        
        GuestService.getComplaintItemList(property_id)
            .then(function(response) {
                $scope.complaint_list =  response.data.list;
                $scope.complaint_types =  response.data.types;
                //$scope.complaint.type_id = $scope.complaint_types[0].id;
              //  $scope.complaint_department = response.data.com_dept;
               // $scope.complaint_usergroup = response.data.com_usergroup;
               // $scope.dept_list = response.data.dept;
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to get Complaint Information.');
            })
            .finally(function() {
                if( callback != undefined )
                    callback();
            });    
    }*/

    $scope.updateComplaintList = function(list){
        $scope.complaint_list = list;
    }

    

    $scope.$on('selected_complaint', function(event, args){
        $scope.init(args);        
    });

    

    $scope.createCategory = function () {        
        var modalInstance = $uibModal.open({
            templateUrl: 'complaint_category.html',
            controller: 'ComplaintCategoryCtrl',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
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

   /* $scope.setComplaintCategoryList = function(list) {
        $scope.category_list = list;
    }*/

});





