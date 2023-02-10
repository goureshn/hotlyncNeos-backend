app.controller('MyMobileApprovalController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService) {
    var MESSAGE_TITLE = 'My Task';

    $scope.table_container_height = 'height: ' + ($window.innerHeight - 100) + 'px; overflow-y: auto;';



    $scope.tableState = undefined;
    $scope.user_info = undefined;

    $scope.filter = {};
    $scope.filter.approval_temp = 'Waiting For Approval';
    var profile = AuthService.GetCredentials();
    var data = {};
    data.setting_group = 'currency';
    data.property_id = profile.property_id;
    $http({
        method: 'POST',
        url: '/backoffice/configuration/wizard/general',
        data: data,
        headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
        .success(function (data, status, headers, config) {
            $scope.currency = data.currency.currency;
        })
        .error(function (data, status, headers, config) {
            console.log(status);
        });

    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};

        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/approvalmobilelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datalist = response.data;
              //  window.alert(JSON.stringify($scope.datalist));
                $scope.filter.depart_selected = false;
                for (var i = 0; i < $scope.datalist.length; i++) {
                    $scope.datalist[i].depart_selected = false;
                }
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.getDetailCallList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isChildLoading = true;

        var request = {};

        var profile = AuthService.GetCredentials();

        if ($scope.user_info)
            request.agent_id = $scope.user_info.id;
        else {
            request.agent_id = 0;
            request.approver_id = profile.id;
            request.property_id = profile.property_id;
        }

        $http({
            method: 'POST',
            url: '/frontend/callaccount/detailmobilelist',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.detaillist = response.data;
                for (var i = 0; i < $scope.detaillist.length; i++) {
                    $scope.detaillist[i].approval_temp = $scope.detaillist[i].approval + '';
                    $scope.detaillist[i].selected = false;
                }

                //  $scope.filter.approval_temp = 'Waiting For Approval';
                $scope.filter.total_selected = false;
                $scope.selected_count = 0;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isChildLoading = false;
            });
    };

    $scope.approve_types = [
        'Waiting For Approval',
        'Approve',
        'Return',
        'Reject',
        'Closed'
    ];

    $scope.showUserCallList = function (user) {
        console.log(user);
        $scope.user_info = user;
        $scope.getDetailCallList();
    }

    $scope.$on('onSelectUser', function (event, args) {
        $scope.showUserCallList(args);
    });

    $scope.getDate = function (row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function (row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function (duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }



    $scope.onChangeApproval = function (row) {
        if (row.approval_temp == 'Return' || row.approval_temp == 'Reject') {
            var size = 'lg';
            var modalInstance = $uibModal.open({
                templateUrl: 'returnReasonModal.html',
                controller: 'ReturnReasonController',
                size: size,
                resolve: {
                    call: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (comment) {
                if (comment == undefined || comment.length < 1) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                    return;
                }

                row.comment = comment;

            }, function () {

            });
        }
    }

    $scope.onChangeTotalApproval = function () {
        if ($scope.filter.approval_temp == 'Return' || $scope.filter.approval_temp == 'Reject') {
            var row = {};
            var size = 'lg';
            var modalInstance = $uibModal.open({
                templateUrl: 'returnReasonModal.html',
                controller: 'ReturnReasonController',
                size: size,
                resolve: {
                    call: function () {
                        return row;
                    }
                }
            });

            modalInstance.result.then(function (comment) {
                if (comment == undefined || comment.length < 1) {
                    toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                    return;
                }

                for (var i = 0; i < $scope.detaillist.length; i++) {
                    if ($scope.detaillist[i].selected &&
                        $scope.detaillist[i].approval == 'Waiting For Approval') {
                        $scope.detaillist[i].approval_temp = $scope.filter.approval_temp;
                        $scope.detaillist[i].comment = comment + "";
                    }
                }

            }, function () {

            });
        }
        else {
            for (var i = 0; i < $scope.detaillist.length; i++) {
                if ($scope.detaillist[i].selected &&
                    $scope.detaillist[i].approval == 'Waiting For Approval') {
                    $scope.detaillist[i].approval_temp = $scope.filter.approval_temp;
                }
            }
        }
    }

    $scope.approveCalls = function () {
        var request = {};

        request.calls = [];

        var profile = AuthService.GetCredentials();

        for (var i = 0; i < $scope.detaillist.length; i++) {
            var call = $scope.detaillist[i];
            if (call.approval == call.approval_temp)
                continue;

            var data = {};
            data.id = call.id;
            data.approver = profile.id;

            if (call.approval_temp == 'Waiting For Approval')
                data.approval = 'Waiting For Approval';
            if (call.approval_temp == 'Approve')
                data.approval = 'Approved';
            if (call.approval_temp == 'Return')
                data.approval = 'Returned';
            if (call.approval_temp == 'Reject')
                data.approval = 'Rejected';
            if (call.approval_temp == 'Closed')
                data.approval = 'Close';

            if (data.approval == 'Return')
                data.classify = 'Unclassified';
            else
                data.classify = call.classify;

            data.comment = call.comment;

            request.calls.push(data);
        }

        $http({
            method: 'POST',
            url: '/frontend/callaccount/approvemobilecall',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.getDataList();
                $scope.getDetailCallList();
                $scope.$emit('refreshCall', '');
            }).catch(function (response) {

            })
            .finally(function () {

            });
    }
    $scope.onChangeSelected = function () {
        var selected_count = 0;
        for (var i = 0; i < $scope.detaillist.length; i++) {
            if ($scope.detaillist[i].selected)
                selected_count++;
        }

        $scope.selected_count = selected_count;
    }

    $scope.onChangeTotalSelected = function () {
        for (var i = 0; i < $scope.detaillist.length; i++) {
            $scope.detaillist[i].selected = $scope.filter.total_selected;
        }

        $scope.onChangeSelected();
    }

    $scope.onChangeDepartTotalSelected = function () {
        for (var i = 0; i < $scope.datalist.length; i++) {
            $scope.datalist[i].depart_selected = $scope.filter.depart_selected;
        }
    }

    $scope.onApproveReturn = function (part, type, row) {
        //var part = part;
        var part = 'date';
       
        var type = type;
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        var submitter_ids = [];
        var call_ids = [];
        if (part == 'date') {
            if (row.id > 0) {
                call_ids.push(row.id);
            } else {
                for (var i = 0; i < $scope.detaillist.length; i++) {
                    if ($scope.detaillist[i].selected == true) {
                        call_ids.push($scope.detaillist[i].id);
                    }
                }
            }
            if (call_ids.length < 1) return;
        }
        var size = '';
        var modalInstance = $uibModal.open({
            templateUrl: 'returnReplyfinanceModal.html',
            controller: 'ReturnReplyMobileFinanceController',
            size: size,
            resolve: {
                call: function () {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (comment) {

            if (comment.comment_content == undefined || comment.comment_content.length < 1) {
                toaster.pop('error', MESSAGE_TITLE, 'Please set reason');
                return;
            }
            var currentdate = new Date();
            var datetime = currentdate.getFullYear() + "-" +
                (currentdate.getMonth() + 1) + "_" +
                currentdate.getDate() + "_" +
                currentdate.getHours() + "_" +
                currentdate.getMinutes() + "_" +
                currentdate.getSeconds() + "_";
            var url = datetime + Math.floor((Math.random() * 100) + 1);
            var imagetype = comment.imagetype;
            var imagename = comment.imagename;
            if (imagetype != undefined) {
                var extension = imagetype.substr(imagetype.indexOf("/") + 1, imagetype.length);
                request.image_url = url + "." + extension;
                if (comment.src == '') request.image_url = '';
            }
            request.image_src = comment.src;
            request.comment = comment.comment_content;
            request.part = part;
            request.type = type;
            submitter_ids.push(comment.submitter);
            submitter_ids = JSON.stringify(submitter_ids);
            request.submitter_ids = submitter_ids;
            call_ids = JSON.stringify(call_ids);
            request.call_ids = call_ids;
            $http({
                method: 'POST',
                url: '/frontend/callaccount/updatemobileapproval',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    $scope.getDataList();
                    $scope.getDetailCallList();
                    toaster.pop('success', MESSAGE_TITLE, ' has been updated successfully');
                    // $scope.selected_count = 0;
                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to update');
                })
                .finally(function () {
                    $scope.isChildLoading = false;
                });
        }, function () {

        });
    }

    $scope.onMoreInfo = function (row) {
        var size = '';
        var modalInstance = $uibModal.open({
            templateUrl: 'MoreInfoModal.html',
            controller: 'MoreInfoController',
            size: size,
            resolve: {
                call: function () {
                    return row;
                }
            }
        });
    }

    $scope.onApproveReject = function (part, type, row) {
        var part = part;
        var type = type;
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        var submitter_ids = [];
        var call_ids = [];
        if (part == 'agent') {
            for (var i = 0; i < $scope.datalist.length; i++) {
                if ($scope.datalist[i].depart_selected == true) {
                    submitter_ids.push($scope.datalist[i].id);
                }
            }
            if (submitter_ids.length < 1) return;
        }
        if (part == 'date') {
            if (row != '' && row.id > 0) {
                call_ids.push(row.id);
                submitter_ids.push(profile.id);
            } else {
                for (var i = 0; i < $scope.detaillist.length; i++) {
                    if ($scope.detaillist[i].selected == true) {
                        call_ids.push($scope.detaillist[i].id);
                        submitter_ids.push(profile.id);
                    }
                }
            }
            if (call_ids.length < 1) return;
        }
        request.part = part;
        request.type = type;
        submitter_ids = JSON.stringify(submitter_ids);
        request.submitter_ids = submitter_ids;
        call_ids = JSON.stringify(call_ids);
        request.call_ids = call_ids;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/updatemobileapproval',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.getDataList();
                $scope.getDetailCallList();
                toaster.pop('success', MESSAGE_TITLE, ' has been updated successfully');
                // $scope.selected_count = 0;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to update');
            })
            .finally(function () {
                $scope.isChildLoading = false;
            });
    }

});

app.directive('fileDropzoneapproval', function () {
    return {
        restrict: 'A',
        scope: {
            file: '=',
            fileName: '='
        },
        link: function (scope, element, attrs) {
            var checkSize,
                isTypeValid,
                processDragOverOrEnter,
                validMimeTypes;

            processDragOverOrEnter = function (event) {
                if (event != null) {
                    event.preventDefault();
                }
                event.dataTransfer.effectAllowed = 'copy';
                return false;
            };

            validMimeTypes = attrs.fileDropzone;

            checkSize = function (size) {
                var _ref;
                if (((_ref = attrs.maxFileSize) === (void 0) || _ref === '') || (size / 1024) / 1024 < attrs.maxFileSize) {
                    return true;
                } else {
                    alert("File must be smaller than " + attrs.maxFileSize + " MB");
                    return false;
                }
            };

            isTypeValid = function (type) {
                if ((validMimeTypes === (void 0) || validMimeTypes === '') || validMimeTypes.indexOf(type) > -1) {
                    return true;
                } else {
                    alert("Invalid file type.  File must be one of following types " + validMimeTypes);
                    return false;
                }
            };

            element.bind('dragover', processDragOverOrEnter);
            element.bind('dragenter', processDragOverOrEnter);

            return element.bind('drop', function (event) {
                var file, name, reader, size, type;
                if (event != null) {
                    event.preventDefault();
                }
                reader = new FileReader();
                reader.onload = function (evt) {
                    if (checkSize(size) && isTypeValid(type)) {
                        return scope.$apply(function () {
                            scope.file = evt.target.result;
                            if (angular.isString(scope.fileName)) {
                                return scope.fileName = name;
                            }
                        });
                    }
                };
                file = event.dataTransfer.files[0];
                name = file.name;
                type = file.type;
                size = file.size;
                reader.readAsDataURL(file);
                return false;
            });
        }
    };
})


    .directive("filereadapproval", [function () {
        return {
            scope: {
                filereadapproval: "=",
                imagenameapproval: "=",
                imagetypeapproval: "="
            },
            link: function (scope, element, attributes) {
                element.bind("change", function (changeEvent) {
                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.filereadapproval = loadEvent.target.result;
                        });
                    }
                    scope.imagenameapproval = changeEvent.target.files[0].name;
                    scope.imagetypeapproval = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);



app.controller('ReturnReasonController', function ($scope, $uibModalInstance, $http, call) {
    $scope.call = call;
    $scope.call.comment_content = '';

    $scope.send = function () {
        $uibModalInstance.close($scope.call.comment_content);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.getDate = function (row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function (row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function (duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    var request = {};
    request.call_id = call.id;

    $scope.comment_list = [];
    $http({
        method: 'POST',
        url: '/frontend/callaccount/commentlistmobile',
        data: request,
        headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
        .then(function (response) {
            $scope.comment_list = response.data;
        }).catch(function (response) {

        })
        .finally(function () {

        });
});

app.controller('ReturnReplyMobileFinanceController', function ($scope, $uibModalInstance, $http, call) {
    // window.alert(JSON.stringify(call));
    $scope.call = call;
    $scope.call.src = '';
    $scope.call.imagename = '';
    $scope.call.imagetype = '';
    $scope.call.call_date=call.date;
    $scope.call.start_time=call.time;
    $scope.call.called_no=call.call_to;
    $scope.call.carrier_charges=call.charges;
    $scope.call.comment_content = '';

    $scope.send = function () {
        $uibModalInstance.close($scope.call);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.getDate = function (row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function (row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function (duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    var request = {};
    request.call_id = call.id;

    $scope.comment_list = [];
    $http({
        method: 'POST',
        url: '/frontend/callaccount/commentlistmobile',
        data: request,
        headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
        .then(function (response) {
            $scope.comment_list = response.data;
        }).catch(function (response) {

        })
        .finally(function () {

        });
});

app.controller('MoreInfoController', function ($scope, $uibModalInstance, $http, call) {
    $scope.call = call;

    $scope.send = function () {
        $uibModalInstance.close($scope.call.comment_content);
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };

    $scope.getDate = function (row) {
        return moment(row.created_at).format('YYYY-MM-DD');
    }

    $scope.getTime = function (row) {
        return moment(row.created_at).format('h:mm:ss a');
    }

    $scope.getDurationInMinute = function (duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    var request = {};
    request.call_id = call.id;

    $scope.comment_list = [];
    $http({
        method: 'POST',
        url: '/frontend/callaccount/commentlistmobile',
        data: request,
        headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
        .then(function (response) {
            $scope.comment_list = response.data;
        }).catch(function (response) {

        })
        .finally(function () {

        });

    request = {};
    request.destination_id = call.destination_id;

    $scope.destination = [];
    $http({
        method: 'POST',
        url: '/frontend/callaccount/getdestinationname',
        data: request,
        headers: { 'Content-Type': 'application/json; charset=utf-8' }
    })
        .then(function (response) {
            $scope.destination = response.data;
        }).catch(function (response) {

        })
        .finally(function () {

        });

});

