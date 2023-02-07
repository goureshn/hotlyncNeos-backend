app.controller('ModChecklistResultController', function ($scope, $rootScope, $timeout, $http,$uibModal, AuthService, toaster, Upload) {
    var MESSAGE_TITLE = 'task Checklist';

    $scope.task = {};

    $scope.isLoading = false;

    $scope.task_checklist = [];
    $scope.task_oldCheckList = [];
    $scope.bTaskNotChanged = true;
    $scope.check_index = -1;

    $scope.category_list = [];

    $scope.check_all_flag = {
        yes: 0,
        no: 0,
        pending: 0
    };

    $scope.yes_count = 0;
    $scope.no_count = 0;
    $scope.pending_count = 0;
    $scope.common_count = 0;

    var selected_row = null;

    $scope.location_list = [];

    var user_list = [];
    var name_list = [];
    var profile = null;
    function getInitDataList()
    {
        profile = AuthService.GetCredentials();

        $http.get('/list/userlist?property_id=' + profile.property_id)
            .then(function (response) {
                user_list = response.data;
            });

        $http.get('/list/checklist?property_id=' + profile.property_id)
            .then(function (response) {
                name_list = response.data;
            });

        $http.get('/list/locationlist?property_id=' + profile.property_id)
            .then(function (response) {
                $scope.location_list = response.data;
            });
    }

    function getChecklist()
    {
        var request = {};

        request.task_id = $scope.task.id;

        var url = '/frontend/mod/checklistresult';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            var prev = '';
            $scope.task.location_mode = response.data.content.location_mode;
            $scope.task.location = response.data.content.location;
            $scope.task_checklist = response.data.content.list;
            $scope.other_list = response.data.content.other_list;

            reorderResult();

            console.log(response);
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {

            });
    }


    $scope.$on('checklisttask_updated', function (event, message) {
       let taskId = message.content.task_id;

       if (taskId == $scope.task.id) {
           getChecklist();
       }
    });

    $scope.$on('checklisttask_updatestatus', function (event, message) {
        let taskId = message.content.task_id;

        if (taskId == $scope.task.id) {
            $rootScope.$emit("removeItemFromSelectedLogs", $scope.task.id);
        }
    });

    $scope.onReset = function() {
        $scope.task_checklist = angular.copy($scope.task_oldCheckList);

        $scope.check_all_flag.yes = 0;
        $scope.check_all_flag.no = 0;
    };

    $scope.setInit = function(row) {
        $scope.task = row;

        getChecklist();
        getInitDataList();
    };

    $scope.cancelEdit = function(row)
    {
        $scope.task_checklist.forEach(item => {
            if( row && item.id == row.id )
                return;

            item.comment_edit = false;
        });

        $scope.other_list.forEach(item => {
            if( row && item.id == row.id )
                return;

            item.comment_edit = false;
        });
    }

    $scope.onClickComment = function(row)
    {
        if( row.comment_edit )
            return;

        row.comment_edit = true;
    }

    $scope.onUpdateComment = function(row)
    {
        row.comment_edit = false;
        $scope.checkChanged();
    };

    $scope.download_array = [];
    $scope.pdf_type_flag = [];
    $scope.icon_class = [];

    function updateIconTypeForRow(item)
    {
        if( !item.attached )
            item.download_array = [];
        else
            item.download_array = item.attached.split("&");

        item.pdf_type_flag = item.download_array.map(row => {
            var extension = row.substr((row.lastIndexOf('.') +1));
            return extension == 'pdf' || extension == 'eml' || extension == 'msg';
        });

        item.icon_class = item.download_array.map(row1 => {
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

    function updateIconType()
    {
        $scope.pdf_type_flag = $scope.download_array.map(row => {
            var extension = row.substr((row.lastIndexOf('.') +1));
            return extension == 'pdf' || extension == 'eml' || extension == 'msg';
        });

        $scope.icon_class = $scope.download_array.map(row1 => {
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

    $scope.onAttachCheckList = function(row)
    {
        selected_row = row;
        if( !row.attached )
        {
            row.attached = '';
            $scope.download_array = [];
        }
        else
        {
            $scope.download_array = row.attached.split('&');
        }

        updateIconType();
    }

    $scope.onDeleteComment= function(row) {
        row.comment = "";
    };

    $scope.onEditComment = function(row, type) {
        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/mod/edit_comment_dialog.html',
            backdrop: 'static',
            size: 'md',
            scope: $scope,
            resolve: {
                item_name: function() {
                    return row.item_name;
                },
                comment: function () {
                    return row.comment;
                },
                type: function () {
                    return type;
                }
            },
            controller: function ($scope, $uibModalInstance, item_name, comment) {

                $scope.item_name = item_name;
                $scope.comment = comment;

                $scope.old_comment = comment;
                $scope.type = type;

                $scope.onSave = function () {
                    $uibModalInstance.close($scope.comment);
                };

                $scope.onReset = function() {
                    $scope.comment = comment;
                };

                $scope.onCancel = function () {
                    $uibModalInstance.dismiss();
                };
            },
        });

        modalInstance.result.then(function (result) {
            row.comment = result;
        }, function () {

        });
    };

    $scope.onChangeYesNo = function(row, setVal) {

        let pending_count = $scope.pending_count;
        let yes_count = $scope.yes_count;
        let no_count = $scope.no_count;

        let bPending = false;

        let curCategory = $scope.category_list[$scope.check_index];
        let yes_no_count = $scope.task_checklist[curCategory].yes_no_count;

        if (setVal === 1) {
            if (row.check_flag != 1) {
                row.check_flag = 1;
                pending_count --;
                yes_no_count ++;
                yes_count ++;
                bPending = true;
            }

            if (row.yes_no != 1) {
                row.yes_no = 1;
                no_count --;

                if (bPending == false) {
                    yes_count ++;
                }
            }
        } else if (setVal === 0) {

            if (row.check_flag != 1) {
                row.check_flag = 1;
                pending_count --;
                yes_no_count ++;
                no_count ++;
                bPending = true;
            }

            if (row.yes_no != 0) {
                row.yes_no = 0;
                yes_count --;

                if (bPending == false) {
                    no_count ++;
                }
            }
        }

        $scope.task_checklist[curCategory].yes_no_count = yes_no_count;
        $scope.yes_count = yes_count;
        $scope.no_count = no_count;
        $scope.pending_count = pending_count;

        $scope.check_all_flag.yes = 0;
        $scope.check_all_flag.no = 0;

        $scope.checkChanged();
    };

    $scope.uploadFiles = function (files, row) {
        // if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
        //     AuthService.isValidModule('mobile.dutymanager.manager') == false )
        //     return;

        $scope.onAttachCheckList(row);

        if( files.length < 1 )
            return;

        Upload.upload({
            url: '/frontend/mod/uploadchecklistfiles',
            data: {
                task_id: $scope.task.id,
                files: files
            }
        }).then(function (response) {
            var list = response.data.content;

            $scope.download_array = $scope.download_array.concat(list);

            row.download_array = row.download_array.concat(list);

            var attached = $scope.download_array.join('&');
            row.attached = attached;
            updateAttach(selected_row, attached);
        }, function (response) {

        }, function (evt) {

        });
    };

    $scope.removeFile = function(index)
    {
        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;

        $scope.download_array.splice(index, 1);
        var attached = $scope.download_array.join('&');

        updateAttach(selected_row, attached);
    }

    $scope.removeFileForRow = function(row, index)
    {
        // if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
        //     AuthService.isValidModule('mobile.dutymanager.manager') == false )
        //     return;

        row.download_array.splice(index, 1);
        var attached = row.download_array.join('&');
        row.attached = attached;
        updateAttach(row, attached);
    }

    function updateAttach(row, attached)
    {
        if( !row )
            return;

        var request = {};

        request.id = row.id;
        request.attached = attached;

        var url = '/frontend/mod/updatechecklistattach';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            row.attached = attached;
            updateIconTypeForRow(row);
            console.log(response);
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {

            });
    }

    $scope.setYesNoAllStatus = function(setVal) {

        if (setVal === 'yes') {
            $scope.check_all_flag.no = 0;
        } else{
            $scope.check_all_flag.yes = 0;
        }

        let yes_count = $scope.yes_count;
        let no_count = $scope.no_count;
        let pending_count = $scope.pending_count;

        if ($scope.category_list.length > $scope.check_index) {
            let curCategory = $scope.category_list[$scope.check_index];

            let itemList = $scope.task_checklist[curCategory].item_list;

            let totalCount = 0;
            let yes_no_count = 0;

            itemList.forEach(subItem => {
                if (subItem.item_type == 'Yes/No') {
                    let bPending = false;

                    if (setVal === 'yes') {
                        if (subItem.check_flag != 1) {
                            subItem.check_flag = 1;
                            pending_count --;
                            yes_count ++;
                            bPending = true;
                        }

                        if (subItem.yes_no != 1) {
                            subItem.yes_no = 1;
                            no_count --;

                            if (bPending == false) {
                                yes_count ++;
                            }
                        }
                    } else if (setVal === 'no') {

                        if (subItem.check_flag != 1) {
                            subItem.check_flag = 1;
                            pending_count --;
                            no_count ++;
                            bPending = true;
                        }

                        if (subItem.yes_no != 0) {
                            subItem.yes_no = 0;
                            yes_count --;

                            if (bPending == false) {
                                no_count ++;
                            }
                        }
                    }

                    yes_no_count ++;
                }

                totalCount ++;
            });

            $scope.task_checklist[curCategory].total_count = totalCount;
            $scope.task_checklist[curCategory].yes_no_count = yes_no_count;
        }

        $scope.yes_count = yes_count;
        $scope.no_count = no_count;
        $scope.pending_count = pending_count;

        $scope.bTaskNotChanged = false;
    };

    $scope.changeStatus = function(status)
    {
        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;

        var request = {};

        request.task_id = $scope.task.id;
        request.status = status;

        var url = '/frontend/mod/updatecheckliststatus';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                $scope.getDataList();
            }
            else
            {
                toaster.pop('info', MESSAGE_TITLE, response.data.message);
            }

        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {

            });

    }

    $scope.onLocationSelect = function ($item, $model, $label) {

        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;

        $scope.task.location_id = $item.id;

        var request = {};

        request.task_id = $scope.task.id;
        request.location_id = $scope.task.location_id;

        var url = '/frontend/mod/updatechecklistlocation';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response);
            if( response.data.code == 200 )
            {
                $rootScope.$emit('callGetDataList', {});

                //$scope.getDataList();
            }
            else
            {
                toaster.pop('info', MESSAGE_TITLE, response.data.message);
            }

        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {

            });

    };

    $scope.onAddMore = function() {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/mod/add_more_dialog.html',
            controller: 'ChecklistAddMoreCtrl',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                category_arr: function () {
                    return $scope.category_list.map(ct_id => {
                        let temp = {};
                        temp.category_id = ct_id;
                        temp.category_name = $scope.task_checklist[ct_id].category_name;

                        return temp;
                    })
                },
                pdf_type_flag: function () {
                    return $scope.pdf_type_flag;
                },
                icon_class: function () {
                    return $scope.icon_class;
                },
                task: function () {
                    return $scope.task;
                }
            }
        });

        modalInstance.result.then(function (data) {
            if(data) {
                getChecklist();

                $rootScope.emit('callGetDataList', {});
            }
        }, function () {

        });
    };

    $scope.next = function() {
        // if ($scope.location_name === "" || $scope.location_name == undefined) {
        //     return;
        // }

        $scope.check_index ++;

        $scope.check_all_flag.yes = 0;
        $scope.check_all_flag.no = 0;

    };

    $scope.previous = function() {
        $scope.check_index --;

        $scope.check_all_flag.yes = 0;
        $scope.check_all_flag.no = 0;
    };

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
    };

    $scope.onGotoCategory = function(sel_category) {
        for (let i = 0; i < $scope.category_list.length; i++) {
            if ($scope.category_list[i] == sel_category) {
                $scope.check_index = i;
                break;
            }
        }
    };

    $scope.onConfirm = function()
    {
        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;
        $scope.isLoading = true;
        var request = {};
        request.user_id = profile.id;
        request.task_id = $scope.task.id;

        let task_checklist = [];
        $scope.category_list.forEach(item => {
            let itemList = $scope.task_checklist[item].item_list;

            itemList.forEach(subItem => {
                task_checklist.push(subItem);
            })
        });

        request.task_checklist = task_checklist;

        if ($scope.pending_count == 0) {
            request.status = 'Done';
        } else {
            if ($scope.yes_count == 0 && $scope.no_count == 0) {
                request.status = 'Pending';
            } else {
                request.status = 'In Progress'
            }
        }

        var url = '/frontend/mod/updatechecklistresult';
        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {

            console.log(response);
            toaster.pop('success', MESSAGE_TITLE, 'Tasks have been updated successfully');

            if( response.data.code == 200 || response.data.code == 201)
            {
                $rootScope.$emit("callGetDataList", {});

                if ($scope.pending_count == 0) {
                    $timeout(function () {
                        $rootScope.$emit("removeItemFromSelectedLogs", $scope.task.id);
                    }, 100);
                } else {
                    $scope.task_oldCheckList = angular.copy($scope.task_checklist);
                    $scope.bTaskNotChanged = true;
                }
            }

        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });
    };


    $scope.cancel = function() {
        $rootScope.$emit("removeItemFromSelectedLogs", $scope.task.id);
    };

    $scope.other_list = [];
    $scope.item = {};

    $scope.type_list = [
        'Yes/No',
        'Comment',
    ];

    function init()
    {
        $scope.item.id = 0;
        $scope.item.catgory_id = 0;
        $scope.item.category_name = '';
        $scope.item.item_name = '';
        $scope.item.order_id = '';
        $scope.item.type = $scope.type_list[0];
    }

    init();

    function getCategoryList()
    {
        var request = {};
        request.checklist_id = $scope.task.checklist_id;

        $http({
            method: 'POST',
            url: '/frontend/mod/categorylist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.category_list = response.data.content;
            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {

            });
    }

    getCategoryList();

    $scope.onCategorySelect = function($item, $model, $label)
    {
        $scope.item.category_id = $item.id;
        $scope.item.order_id = $item.order_id;
    }


    $scope.onAddCategory = function(name)
    {
        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;

        var request = {};
        request.checklist_id = $scope.checklist.id;
        request.name = name;
        request.order_id = $scope.item.order_id;

        $http({
            method: 'POST',
            url: '/frontend/mod/createchecklistcategory',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.category_list = response.data.list;
                $scope.item.category_id = response.data.id;
                $scope.item.category_name = response.data.name;
                $scope.item.noCategoryResults = false;

            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {

            });
    }

    $scope.edit = function(row)
    {
        $scope.item.id = row.id;
        $scope.item.category_id = row.category_id;
        $scope.item.order_id = row.order_id;
        $scope.item.category_name = row.category_name;
        $scope.item.item_name = row.item_name;
        $scope.item.type = row.item_type;
    }

    $scope.delete = function(row)
    {
        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;

        var request = {};
        request.id = row.id;
        request.task_id = $scope.task.id;

        $http({
            method: 'POST',
            url: '/frontend/mod/deletechecklistresult',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.other_list = response.data.content.other_list;

                reorderResult();

            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {

            });
    }

    $scope.checkChanged = function() {
        let strTaskCheckList = JSON.stringify($scope.task_checklist);
        let strOldTaskCheckList = JSON.stringify($scope.task_oldCheckList);

        if (strTaskCheckList === strOldTaskCheckList) {
            $scope.bTaskNotChanged = true;
        } else {
            $scope.bTaskNotChanged = false;
        }
    }

    $scope.onCreateCheckListOther = function()
    {
        if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
            AuthService.isValidModule('mobile.dutymanager.manager') == false )
            return;

        var request = {};
        request.id = $scope.item.id;
        request.category_id = $scope.item.category_id;
        request.order_id = $scope.item.order_id;
        request.checklist_id = $scope.task.checklist_id;
        request.task_id = $scope.task.id;
        request.item_name = $scope.item.item_name;
        request.item_type = $scope.item.type;
        if( request.category_id < 1 )
        {
            toaster.pop('info', MESSAGE_TITLE, 'Please select category');
            return;
        }

        $http({
            method: 'POST',
            url: '/frontend/mod/createchecklistother',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                if( response.data.code != 200 )
                {
                    toaster.pop('info', MESSAGE_TITLE, response.data.message);
                    return;
                }

                $scope.other_list = response.data.content.other_list;
                reorderResult();

                init();
            }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
            .finally(function() {

            });
    }

    function reorderResult()
    {
        var prev = '';

        var tempList = {};
        let i = -1;

        let yes_count = 0;
        let no_count = 0;
        let pending_count = 0;
        let common_count = 0;

        $scope.task_checklist.forEach(item => {

            if (item.item_type == 'Comment') {
                common_count ++;
            } else {
                // set yes, no, pending count
                if (item.check_flag == 1 && item.item_type == 'Yes/No') {
                    if (item.yes_no == 1 )
                    {
                        yes_count ++;
                    } else if(item.yes_no == 0) {
                        no_count++;
                    }
                } else {
                    pending_count++;
                }
            }

            if (tempList[item.real_category_id] == undefined || tempList[item.real_category_id] == null) {
                tempList[item.real_category_id] = {
                    category_name: item.category_name,
                    item_list: [],
                    yes_no_count: 0,
                    total_count: 0
                };

            }

            tempList[item.real_category_id].item_list.push(item);

            if (item.check_flag == 1 && item.item_type == 'Yes/No') {
                if (item.yes_no == 1 || item.yes_no == 0 )
                {
                    tempList[item.real_category_id].yes_no_count ++;
                }
            }

            tempList[item.real_category_id].total_count ++;

            updateIconTypeForRow(item);

            return item;
        });

        $scope.common_count = common_count;
        $scope.yes_count = yes_count;
        $scope.no_count = no_count;
        $scope.pending_count = pending_count;

        $scope.task_checklist = tempList;
        $scope.task_oldCheckList = angular.copy($scope.task_checklist);

        // category list
        $scope.category_list = Object.keys($scope.task_checklist);

        prev = '';
        $scope.other_list = $scope.other_list.map(item => {
            if( item.category_name == prev )
                item.category_name = '';
            else
                prev = item.category_name;

            updateIconTypeForRow(item);

            return item;
        });

    }
});

app.controller('ChecklistAddMoreCtrl', function ($scope, $rootScope, $uibModal, $uibModalInstance, $timeout, $http, AuthService, toaster, Upload, category_arr, pdf_type_flag, icon_class, task) {
    var MESSAGE_TITLE = 'Add Checklist';

    $scope.category_arr = category_arr;
    $scope.model_data = {
        category_name: "",
        category_id: 0,
        name: "",
        comment : "",
        check_flag: 0,
        yes_no: 1,
        item_type: "Yes/No",
        download_array : []
    };

    $scope.task = task;

    $scope.yes_flag = 0;
    $scope.no_flag = 0;

    $scope.isLoading = false;

    $scope.pdf_type_flag = pdf_type_flag;
    $scope.icon_class = icon_class;

    $scope.category_name = "";

    $scope.onCancel = function() {
        $uibModalInstance.dismiss();
    };

    $scope.onSetCheckFlag = function(row, val) {

        if (row.check_flag == 0) {
            row.check_flag = 1;
        }
        $scope.model_data.yes_no = val;

        if (val == 1) {
            $scope.no_flag = !$scope.yes_flag;
        } else {
            $scope.yes_flag = !$scope.no_flag;
        }
    };

    $scope.onAddItem = function() {

        let request = angular.copy($scope.model_data);
        request.checklist_id = $scope.task.checklist_id;
        request.task_id = $scope.task.id;

        $scope.isLoading = true;

        $http({
            method: 'POST',
            url: '/frontend/mod/addchecklistitem',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            toaster.pop('success', "Notification", "Successfully added!");
            $rootScope.$emit('addedNewItem', {});
            $uibModalInstance.close('Yes');
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onOk = function() {

        let category_names = $scope.category_arr.map(item => {
            return item.category_name;
        });

        if (($scope.model_data.category_id == 0 && $scope.category_name != "") || !category_names.includes($scope.category_name)) {
            let modalInstance = $uibModal.open({
                templateUrl: 'tpl/mod/confirm_create.html',
                backdrop: 'static',
                size: 'sm',
                scope: $scope,
                resolve: {
                    category_name: function () {
                        return $scope.category_name;
                    }
                },
                controller: function ($scope, $uibModalInstance, category_name) {

                    $scope.category_name = category_name;

                    $scope.onYes = function () {
                        $uibModalInstance.close('Yes');
                    };
                    $scope.onNo = function () {
                        $uibModalInstance.close('No');
                    };
                },
            });

            modalInstance.result.then(function (result) {
                if (result === 'Yes') {
                    $scope.model_data.category_name = $scope.category_name;
                    $scope.model_data.category_id = 0;
                    $scope.onAddItem();
                } else {
                    $scope.model_data.category_name = '';
                    toaster.pop('warning', "Notice", 'Please select category name again.');
                    return;
                }
            }, function () {

            });
        } else {
            $scope.onAddItem();
        }
    };

    $scope.onChangeYesNo = function (row, val) {
        if (!$scope.model_data.check_flag) {
            $scope.model_data.check_flag = 1;
        }
        $scope.model_data.yes_no = val;
    };

    $scope.onSelectCategoryName = function (item) {
        $scope.model_data.category_name = item.category_name;
        $scope.model_data.category_id = item.category_id;
    };

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
    };

    $scope.removeFileForRow = function(row, index)
    {
        // if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
        //     AuthService.isValidModule('mobile.dutymanager.manager') == false )
        //     return;

        row.download_array.splice(index, 1);
        var attached = row.download_array.join('&');
        row.attached = attached;
    }

    $scope.uploadFiles = function (files, row) {
        // if( (AuthService.isValidModule('mobile.dutymanager.edit') == false || AuthService.isValidModule('mobile.dutymanager.edit') && $scope.task.status == 'Done') &&
        //     AuthService.isValidModule('mobile.dutymanager.manager') == false )
        //     return;


        if( files.length < 1 )
            return;

        Upload.upload({
            url: '/frontend/mod/uploadchecklistfiles',
            data: {
                task_id: $scope.task.id,
                files: files
            }
        }).then(function (response) {
            var list = response.data.content;

            row.download_array = row.download_array.concat(list);

            var attached = row.download_array.join('&');
            row.attached = attached;


        }, function (response) {

        }, function (evt) {

        });
    };

});



