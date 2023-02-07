app.controller('SubcomplaintEditController', function ($scope, $rootScope, $http, $interval, $window, $uibModal, AuthService, GuestService, toaster, Upload) {
    var MESSAGE_TITLE = 'Validation';
    $scope.body_height = $window.innerHeight - 300;
    $scope.ticketlist_height = $window.innerHeight - 88;

    $scope.complaint = {};
    $scope.guest_id_editable = false;
    $scope.genders = ['Male', 'Female'];
    $scope.subcomplaint_list = [];
    $scope.exist_subcomplaint_list = [];
    $scope.subcomment_list = [];
    $scope.category_editable = AuthService.isValidModule('app.department.head');
    $scope.category_change = AuthService.isValidModule('app.complaint.mytask_ctgry_change');
    $scope.dept_loc_list = [];

    var original_dept_id = 0;

    $scope.init = function(complaint) {
        if( !complaint.category_name  )
            complaint.category_name = '';

        $scope.complaint = complaint;
        $scope.complaint.location = complaint.lgm_type + ' - ' + complaint.lgm_name;
        $scope.guest_id_editable = !(complaint.guest_id > 0);
        if( complaint.gender == null )
            $scope.complaint.gender = $scope.genders[0];
        if( complaint.path )
            $scope.complaint.sub_download_array = complaint.path.split("|");
        else
            $scope.complaint.sub_download_array = [];

        $scope.complaint.sub_pdf_type_flag = $scope.complaint.sub_download_array.map(row => {
            var extension = row.substr((row.lastIndexOf('.') +1));
            return extension == 'pdf' || extension == 'eml' || extension == 'msg';                    
        });    

        $scope.complaint.sub_icon_class = $scope.complaint.sub_download_array.map(row1 => {
            var extension = row1.substr((row1.lastIndexOf('.') +1));
            if( extension == 'pdf' )
                return 'fa-file-pdf-o';
            if( extension == 'eml' )
                return 'fa-envelope';
            if( extension == 'msg' )
                return 'fa-envelope';
            return '';
        });    


        if( complaint.main_path )
            $scope.complaint.download_array = complaint.main_path.split("|");
        else
            $scope.complaint.download_array = [];

         // find staff list
        var profile = AuthService.GetCredentials();

        $http.get('/list/userlist?property_id=' + profile.property_id + '&dept_id=0')
            .then(function(response) {
                $scope.staff_list = response.data;                
        });    

        $http.get('/frontend/complaint/getdeptloclist?dept_id=' + complaint.dept_id)
            .then( function(response) {
                $scope.dept_loc_list = response.data;
        });  

        getComplaintInfo();
        getMainCommentList();
        getCommentList();
        getComplaintCategoryList();
        getSubcomplaintLogs();
        getSubcomplaintCompensationList();
    }

    var profile = AuthService.GetCredentials();
    $scope.complaint_setting = {};
    $http.get('/list/complaintsetting?property_id=' + profile.property_id).success( function(response) {
        $scope.complaint_setting = response;
    });  
     
    $scope.onLocationSelect = function ($item, $model, $label) {
        $scope.complaint.location_name = $item.name;
        $scope.complaint.location_id = $item.id;

        var request = {};
        request.id = $scope.complaint.id;
        request.location_id = $item.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/savelocation',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };
    function getComplaintInfo(callback) {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        GuestService.getComplaintItemList(property_id)
            .then(function(response) {
                $scope.complaint_list =  response.data.list;
                $scope.complaint_types =  response.data.types;
                $scope.complaint.type_id = $scope.complaint_types[0].id;
                $scope.complaint_department = response.data.com_dept;
                $scope.complaint_usergroup = response.data.com_usergroup;
                $scope.dept_list = response.data.dept;
                original_dept_id = $scope.complaint.dept_id;
                $scope.files = [];

                selectDepartment($scope.complaint);
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Fail to get Complaint Information.');
            })
            .finally(function() {
                if( callback != undefined )
                    callback();
            });    

        $http.get('/frontend/complaint/compensationtype?client_id=' + profile.client_id)
            .then(function(response) {
                $scope.compensations =  response.data;
            });    
    }

    function selectDepartment(sub) {
        // find department id
        var dept_id = -1;
        var max_time = 0;
        for(var i = 0; i < $scope.complaint_department.length; i++)
        {
            if( $scope.complaint_department[i].complaint_id == sub.item_id &&
                $scope.complaint_department[i].dept_id == sub.dept_id )
            {
                max_time = $scope.complaint_department[i].max_time;
                break;
            }
        }

        sub.max_time = max_time;
    }

    function getCommentList() {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/getsubcomments',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.subcomment_list = response.data;
            for(var i = 0; i < $scope.subcomment_list.length; i++)
            {
                if( $scope.subcomment_list[i].comment )
                    $scope.subcomment_list[i].comment = $scope.subcomment_list[i].comment.replace(/\r?\n/g,'<br/>');
                else
                    $scope.subcomment_list[i].comment = '';
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    function getMainCommentList() {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.property_id = profile.property_id;
        request.id = $scope.complaint.parent_id;
        
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

    function getComplaintCategoryList() {
        $scope.category_list = [];
        $scope.subcategory_list = [];

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
            $scope.category_list = response.data;            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    function selectAssignee(sub) {
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
            sub.assignee_name = 'No Default Assignee';
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

    $scope.onSelectDepartment = function(sub) {
        selectDepartment(sub);
        selectAssignee(sub);        
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

    $scope.onComplete = function() {
        if( !($scope.complaint.category_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Category');
            return;
        }

        if( !($scope.complaint.subcategory_id > 0) )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select Sub Category');
            return;
        }

        checkCompensationForSubcomplaint($scope.complaint, function(sub1) {
            var modalInstance = $uibModal.open({
                templateUrl: 'modal_input.html',
                controller: 'ModalInputCtrl',
                scope: $scope,
                resolve: {
                    title: function () {
                        return 'Please input comment';
                    },
                    min_length: function () {
                        return 160;
                    }
                }
            });
    
            modalInstance.result
                .then(function (comment) {
                    if( comment.length )
                    completeComplaint(comment);
                }, function () {
    
                });
        });        

        

        
    }

    function completeComplaint(comment) {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/completesubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
            toaster.pop('success', MESSAGE_TITLE, 'Sub complaint is completed.');        
            // $scope.complaint.in_progress = 0;
            // $scope.complaint.status = 2;
            // $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onCancel = function() {
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
                cancelComplaint(comment);
            }, function () {

            });
    }

    function cancelComplaint(comment) {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/cancelsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('success', MESSAGE_TITLE, 'Sub complaint is canceled.');        
            // $scope.complaint.in_progress = 0;
            // $scope.complaint.status = 5;
            // $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onReassign = function() {
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;
        request.dept_id = $scope.complaint.dept_id;
        request.assignee_id = $scope.complaint.assignee_id;
        request.user_id = profile.id;
        request.comment = 'Reassign';

        if( request.dept_id == original_dept_id )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select different department');
            return;
        }
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/reassignsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            toaster.pop('success', MESSAGE_TITLE, 'Sub complaint is reassigned.');     
            // $scope.complaint.status = 4; // reassign   
            // $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.getAssigneeList = function(val) {
        if( val == undefined )
            val = "";
        
        var profile = AuthService.GetCredentials();
        var property_id = profile.property_id;

        var request = {};

        request.property_id = property_id;
        request.id = $scope.complaint.id;

        return $http({
                method: 'POST',
                url: '/frontend/complaint/assigneelist',
                data: request,
                headers: {'Content-Type': 'application/json; charset=utf-8'}
            }).then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.onAssigneeSelect = function ($item, $model, $label) {
        $scope.complaint.assignee_id = $item.user_id;

        var request = {};
        request.id = $scope.complaint.id;
        request.assignee_id = $item.user_id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/changeassignee',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            // $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    };

    $scope.commitComment = function(comment) {
        console.log(comment);

        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.sub_id = $scope.complaint.id;
        request.parent_id = 0;
        request.user_id = profile.id;        
        request.comment = comment;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/addsubcomment',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                

            $scope.subcomment_list = response.data;
            $scope.complaint.sub_comment = ''; 
            for(var i = 0; i < $scope.subcomment_list.length; i++)
            {
                $scope.subcomment_list[i].comment = $scope.subcomment_list[i].comment.replace(/\r?\n/g,'<br/>');
            }
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onAck = function() {
        var profile = AuthService.GetCredentials();
        
        var request = {};

        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/acksubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            // $scope.complaint.ack = 1;
            // $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }

    $scope.onInprogress = function() {
        var profile = AuthService.GetCredentials();
        
        var request = {};

        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/inprogresssubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            // $scope.complaint.in_progress = 1;
            // $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
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
            toaster.pop('success', MESSAGE_TITLE, 'Comment has been Updated Successfully.');
            $scope.$emit('onChangedSubComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Comment.');
        })
            .finally(function () {

            });
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
            toaster.pop('success', MESSAGE_TITLE, 'Resolution has been Updated Successfully.');
            $scope.$emit('onChangedComplaint', response.data);
        }).catch(function (response) {
            toaster.pop('error', MESSAGE_TITLE, 'Failed to update Resolution.');
        })
            .finally(function () {

            });
    }

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

    $scope.viewPrimary = function () {
        var modalInstance = $uibModal.open({
            templateUrl: 'complaint_detail.html',
            controller: 'ComplaintDetailCtrl',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
                },
                comment_list: function () {
                    return $scope.comment_list;
                }
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.selected = selectedItem;
        }, function () {

        });
    }

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

    $scope.setComplaintCategoryList = function(list) {
        $scope.category_list = list;
    }

    $scope.setComplaintSubcategoryList = function(list) {
        $scope.subcategory_list = list;
    }

    $scope.getCategoryList = function(query) {
        if( query == undefined )
            query = "";

        return $scope.category_list.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.onCategorySelect = function ($item, $model, $label) {
        $scope.complaint.category_id = $item.id;
        getComplaintSubcategoryList($item.id);

        var request = {};
        request.id = $scope.complaint.id;
        request.category_id = $item.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/savecategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    function getComplaintSubcategoryList(category_id) {
        $scope.subcategory_list = [];
        $scope.complaint.subcategory_name = '';
        $scope.complaint.subcategory_id = 0;

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
                $scope.complaint.subcategory_name = $scope.subcategory_list[0].name;
                $scope.complaint.subcategory_id = $scope.subcategory_list[0].id;
                return;
            }
            else
            {
                $scope.complaint.subcategory_name = '';
                $scope.complaint.subcategory_id = 0;
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.createSubcategory = function () {        
         if( !($scope.complaint.category_id > 0) )
            return;

        var modalInstance = $uibModal.open({
            templateUrl: 'complaint_subcategory.html',
            controller: 'ComplaintSubcategoryCtrl',
            scope: $scope,
            resolve: {
                complaint: function () {
                    return $scope.complaint;
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

    $scope.getSubcategoryList = function(query) {
        if( query == undefined )
            query = "";

        return $scope.subcategory_list.filter(function(type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.onSubcategorySelect = function ($item, $model, $label) {
        $scope.complaint.subcategory_id = $item.id; 

        var request = {};
        request.id = $scope.complaint.id;
        request.subcategory_id = $item.id;

        $http({
            method: 'POST',
            url: '/frontend/complaint/savesubcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.uploadFiles = function (files) {
        $scope.files = $scope.files.concat(files);
        var profile = AuthService.GetCredentials();       
        if ($scope.files && $scope.files.length > 0 ) {
            Upload.upload({
                url: '/frontend/complaint/uploadsubfiles',
                data: {
                    id: $scope.complaint.id,
                    user_id: profile.id,
                    files: $scope.files
                }
            }).then(function (response) {
                $scope.files = [];
                if( response.data.path )
                    $scope.complaint.sub_download_array = response.data.path.split("|");
                else
                    $scope.complaint.sub_download_array = [];
                
                $scope.complaint.sub_pdf_type_flag = $scope.complaint.sub_download_array.map(row => {
                    var extension = row.substr((row.lastIndexOf('.') +1));
                    return extension == 'pdf';                    
                });        
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
    };

    $scope.removeFile = function($index) {        
        var request = {};
        request.id = $scope.complaint.id;
        request.index = $index;

        $http({
            method: 'POST',
            url: '/frontend/complaint/removesubfiles',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                            
            // if( response.data.path )
            //     $scope.complaint.sub_download_array = response.data.path.split("|");
            // else
            //     $scope.complaint.sub_download_array = [];

            // $scope.complaint.sub_pdf_type_flag = $scope.complaint.sub_download_array.map(row => {
            //     var extension = row.substr((row.lastIndexOf('.') +1));
            //     return extension == 'pdf';                    
            // });    

        }).catch(function(response) {
        })
        .finally(function() {

        });
     
    }

    $scope.log_list = [];
    function getSubcomplaintLogs() {
        var request = {};
        request.id = $scope.complaint.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/logsforsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.log_list = response.data.datalist;
        }).catch(function(response) {
            
        })
        .finally(function() {

        });
    }

    $scope.comp = {};
    
    function getSubcomplaintCompensationList() {
        var request = {};        
        request.sub_id = $scope.complaint.id;    

        $http({
            method: 'POST',
            url: '/frontend/complaint/getcompensationlistforsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            $scope.complaint.compensation_list = response.data;     
            var sum = 0;
            var no = 0;
            $scope.complaint.compensation_list.map(item => {
                sum += item.cost;
                no++;

                item.no = no;
            });
            $scope.complaint.compensation_total = sum;
        }).catch(function(response) {
            
        })
        .finally(function() {

        });
    }

    $scope.onCompensationSelect = function($item, $model, $label) {
        $scope.comp.id = $item.id;
        $scope.comp.cost = $item.cost;        
    }

    $scope.onProviderSelect = function($item, $model, $label) {
        $scope.comp.sub_provider_id = $item.id;
    }

    $scope.onAddCompensation = function() {
        if( !($scope.comp.id > 0) )
            return;

        if( !($scope.comp.sub_provider_id > 0) )
            return;
        if( $scope.comp.cost < $scope.complaint_setting.minimum_compensation)
        {            
            toaster.pop('info', MESSAGE_TITLE, 'Please enter a valid amount. Minimum Compensation is AED' + $scope.complaint_setting.minimum_compensation);
            return;
        }   
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
                addCompensation();            
        }, function () {

        });    
    }

    function addCompensation()
    {
        var request = {};
        request = angular.copy($scope.comp);
        request.sub_id = $scope.complaint.id;    
        $http({
            method: 'POST',
            url: '/frontend/complaint/addcompensationforsubcomplaint',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            // getSubcomplaintCompensationList();
            $scope.comp = {};
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
    }

    $scope.downloadFile = function(url) {
        $window.location.href = '/' + url;
    }

    $scope.$on('subcomplaint_files_changed', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.sub.id )
            return;

        if( complaint.sub.path )
            $scope.complaint.sub_download_array = complaint.sub.path.split("|");
        else
            $scope.complaint.sub_download_array = [];

        $scope.complaint.sub_pdf_type_flag = $scope.complaint.sub_download_array.map(row => {
            var extension = row.substr((row.lastIndexOf('.') +1));
            return extension == 'pdf';                    
        });        
    });

    $scope.$on('subcomplaint_comment_added', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.sub.id )
            return;

        $scope.subcomment_list = complaint.sub.comment_list;
        $scope.subcomment_list.forEach(function(row1, index1){
            row1.comment = row1.comment.replace(/\r?\n/g,'<br/>');                
            row1.time = $scope.getTime(row1);    
        });        
    });

    $scope.$on('subcomplaint_compensation_create', function(event, args){
        var complaint = args.info;
        if( $scope.complaint.id != complaint.sub_id )
            return;

        $scope.complaint.compensation_list = complaint.sub_comp_list;         
        $scope.complaint.compensation_total = complaint.sub_item_total;

        getSubcomplaintLogs();
    });

});

app.controller('ComplaintDetailCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint, $window,comment_list) {
    $scope.body_height = $window.innerHeight - 300;
  
    $scope.complaint = complaint;
    $scope.comment_list = comment_list;
    $scope.ok = function () {
        $uibModalInstance.close();
    };

    $scope.getTicketNumber = function (ticket) {
        if(!ticket)
            return 'F00000';

        return sprintf('F%05d', ticket.parent_id);
    };

    $scope.getTime = function(row) {
        return moment(row.created_at).fromNow();
    }

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

    $scope.flagComplaint = function() {
        var profile = AuthService.GetCredentials();
        
        var request = {};
        request.complaint_id = $scope.complaint.parent_id;
        request.user_id = profile.id;
        
        $http({
            method: 'POST',
            url: '/frontend/complaint/flag',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);                
            $scope.complaint.flag = response.data.flag;  
            $scope.pageChanged();          
        }).catch(function(response) {
            // CASE 3: NO Asignee Found on shift : Default Asignee
        })
        .finally(function() {

        });
    }
});

app.controller('ComplaintCategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint, category_list) {
    $scope.complaint = complaint;
    $scope.cateory_list = category_list;

    $scope.createCategory = function () {
        var profile = AuthService.GetCredentials();

        var request = {};

        request.name = $scope.complaint.category_new_name;
        request.dept_id = profile.dept_id;
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
            $scope.setComplaintCategoryList($scope.category_list);    
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    
    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('ComplaintSubcategoryCtrl', function($scope, $uibModalInstance, $http, AuthService, complaint, subcategory_list) {
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