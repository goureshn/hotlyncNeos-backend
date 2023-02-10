app.controller('MycallFinanceMobileController', function ($scope, $rootScope, $http, $timeout, $uibModal, $window, $interval, toaster, AuthService) {
    var MESSAGE_TITLE = 'My Task Finance';

    $scope.full_height = 'height: ' + ($window.innerHeight - 90) + 'px; overflow-y: auto;';
    $scope.getDurationInMinute = function (duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }
    $scope.selectDepartNum = undefined;
    $scope.selectRowRow = undefined;
    $scope.selectRow = undefined;
    $scope.filter = {};
    $scope.selectedRow = undefined;
    $scope.classifystatus = "Business";
    $scope.copyclassifystatus = 'Business';
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
    $scope.approve_types = [
        'Waiting For Approval',
        'Approve',
        'Return',
        'Reject',
        'Closed'
    ];

    $scope.getDurationInMinute = function (duration) {
        return moment.utc(duration * 1000).format("mm:ss");
    }

    $scope.getClassifystatus = function () {
        $scope.copyclassifystatus = angular.copy($scope.classifystatus);
        $scope.getDataList();
        $scope.getDetailCallList($scope.selectRow);
    }


    $scope.getDataList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isLoading = true;

        var request = {};

        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        request.classifystatus = $scope.copyclassifystatus;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/departlistmobile',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datalist = response.data.data_list;
                $scope.disable_mgr = response.data.callaccounting_disable_approval;
                $scope.filter.depart_selected = false;
                for (var i = 0; i < $scope.datalist.length; i++) {
                    $scope.datalist[i].depart_selected = false;
                }
                if ($scope.copyclassifystatus == 'Business') $scope.classifystatus = 'Personal';
                if ($scope.copyclassifystatus == 'Personal') $scope.classifystatus = 'Business';

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isLoading = false;
            });
    };

    $scope.getSelectDetailCallList = function (row, index) {
        $scope.selectDepartNum = index;
        $scope.selectRow = row;
        $scope.getDetailCallList(row);
    }

    $scope.getDetailCallList = function getDataList(row) {


        $scope.isChildLoading = true;
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = 0;
        if (row != undefined) {
            request.dept_id = row.id;
        }
        request.classifystatus = $scope.copyclassifystatus;
        request.property_id = profile.property_id;
        $http({
            method: 'POST',
            url: '/frontend/callaccount/financedetailcalllistmob',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.detaillist = response.data;
                for (var i = 0; i < $scope.detaillist.length; i++) {
                    $scope.detaillist[i].selected = false;
                    $scope.detaillist[i].downimage = 'glyphicon glyphicon-arrow-down';
                    for (var j = 0; j < $scope.detaillist[i].inform.length; j++) {
                        $scope.detaillist[i].inform[j].selected = false;
                    }
                }
                $scope.filter.total_selected = false;

                // $scope.selected_count = 0;
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isChildLoading = false;
            });
    };

    $scope.onChangeSelected = function () {
        var selected_count = 0;

        for (var i = 0; i < $scope.detaillist.length; i++) {
            if ($scope.detaillist[i].selected)
                selected_count++;
            var inform = $scope.detaillist[i].inform;
            for (var j = 0; j < inform.length; j++) {
                inform[j].selected = $scope.detaillist[i].selected;
            }

        }
        $scope.selected_count = selected_count;
    }

    $scope.onChangeTotalSelected = function () {
        for (var i = 0; i < $scope.detaillist.length; i++) {
            $scope.detaillist[i].selected = $scope.filter.total_selected;
        }

        $scope.onChangeSelected();
    }

    $scope.onDepartChangeSelected = function () {
        var selected_count = 1;
        for (var i = 0; i < $scope.datalist.length; i++) {
            if ($scope.datalist[i].depart_selected)
                selected_count++;
        }
        $scope.selected_count = selected_count;
    }

    $scope.onChangeDepartTotalSelected = function () {
        for (var i = 0; i < $scope.datalist.length; i++) {
            $scope.datalist[i].depart_selected = $scope.filter.depart_selected;
        }
        $scope.onDepartChangeSelected();
    }


    $scope.selectedIndex = 0;
    $scope.onClickRow = function (row, index) {
        $scope.selectedRow = row;
        $scope.selectedIndex = index;
        row.collapse = !row.collapse;
        for (var i = 0; i < $scope.detaillist.length; i++) {
            if (i == index)
                continue;

            $scope.detaillist[i].collapse = false;
        }
        if ($scope.detaillist[index].collapse == true) row.downimage = 'glyphicon glyphicon-arrow-up';
        else row.downimage = 'glyphicon glyphicon-arrow-down';
    }

    $scope.onFinanceReturn = function (row, part) {
        //var part = 'child_return';
        var kind = 'agent';
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        var depart_ids = [];
        var agent_ids = [];
        var calls_ids = [];
        if (kind == 'agent') {
            if (row.id > 0) {
                agent_ids.push(row.submitter);
                calls_ids.push(row.id);
            } else {
                for (var i = 0; i < $scope.detaillist.length; i++) {
                    if ($scope.detaillist[i].selected == true)
                        agent_ids.push($scope.detaillist[i].submitter);

                    var inform = $scope.detaillist[i].inform;
                    for (var j = 0; j < inform.length; j++) {
                        if (inform[j].selected == true)
                            calls_ids.push(inform[j].id);
                    }
                }
            }
            if (calls_ids.length == 0) return;
        }
        var size = '';
        var modalInstance = $uibModal.open({
            templateUrl: 'returnfinanceModal.html',
            controller: 'ReturnReplyFinanceController',
            size: size,
            resolve: {
                call: function () {
                    return row;
                }
            }
        });

        modalInstance.result.then(function (comment) {

            if (comment == undefined || comment.comment_content.length < 1) {
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
            request.kind = kind;
            request.type = part;
            depart_ids = JSON.stringify(depart_ids);
            agent_ids = JSON.stringify(agent_ids);
            calls_ids = JSON.stringify(calls_ids);
            request.depart_ids = depart_ids;
            request.agent_ids = agent_ids;
            request.calls_ids = calls_ids;
            request.classifystatus = row.classify;

            $http({
                method: 'POST',
                url: '/frontend/callaccount/financemobdepartclose',
                data: request,
                headers: { 'Content-Type': 'application/json; charset=utf-8' }
            })
                .then(function (response) {
                    toaster.pop('success', MESSAGE_TITLE, ' has been updated successfully');
                    $scope.getDataList();
                    $scope.getDetailCallList($scope.selectRow);
                    $scope.selectedRow.collapse = !$scope.selectedRow.collapse;

                }).catch(function (response) {
                    console.error('Gists error', response.status, response.data);
                    toaster.pop('error', MESSAGE_TITLE, 'Failed to update');
                })
                .finally(function () {
                    $scope.isChildLoading = false;
                    if ($scope.selectedRow != undefined)
                        $scope.onClickRow($scope.selectedRow, $scope.selectedIndex);
                });
        }, function () {

        });
    }


    $scope.onFinanceClose = function (part, row) {
        var kind = part;
        var child = ''
        if (part == 'child' || part == 'child_return') kind = 'agent';
        var request = {};
        var profile = AuthService.GetCredentials();
        request.agent_id = profile.id;
        request.property_id = profile.property_id;
        var depart_ids = [];
        var agent_ids = [];
        var calls_ids = [];
        if (kind == 'department') {
            for (var i = 0; i < $scope.datalist.length; i++) {
                if ($scope.datalist[i].depart_selected == true) {
                    depart_ids.push($scope.datalist[i].id);
                }
            }
        }
        if (kind == 'agent' || kind == 'charged') {
            if (row != '' && row.id > 0) {
                agent_ids.push(row.submitter);
                calls_ids.push(row.id);
            } else {
                for (var i = 0; i < $scope.detaillist.length; i++) {
                    if ($scope.detaillist[i].selected == true)
                        agent_ids.push($scope.detaillist[i].submitter);

                    var inform = $scope.detaillist[i].inform;
                    for (var j = 0; j < inform.length; j++) {
                        if (inform[j].selected == true)
                            calls_ids.push(inform[j].id);
                    }
                }
            }
            kind = 'agent';
            if (calls_ids.length == 0) return;
        }
        request.kind = kind;
        request.type = part;
        depart_ids = JSON.stringify(depart_ids);
        agent_ids = JSON.stringify(agent_ids);
        calls_ids = JSON.stringify(calls_ids);
        request.depart_ids = depart_ids;
        request.agent_ids = agent_ids;
        request.calls_ids = calls_ids;

        request.classifystatus = $scope.copyclassifystatus;

        $http({
            method: 'POST',
            url: '/frontend/callaccount/financedepartclose',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                toaster.pop('success', MESSAGE_TITLE, ' has been updated successfully');
                $scope.getDataList();
                $scope.getDetailCallList($scope.selectRow);
                $scope.selectedRow.collapse = !$scope.selectedRow.collapse;

            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
                toaster.pop('error', MESSAGE_TITLE, 'Failed to update');
            })
            .finally(function () {
                $scope.isChildLoading = false;
                if ($scope.selectedRow != undefined)
                    $scope.onClickRow($scope.selectedRow, $scope.selectedIndex);
            });


    }

    $scope.viewcallinformation = false;
    $scope.call = {};
    $scope.getCallInformation = function (row, param) {
        $scope.call.dept_id = row.id;
        if (param == 'Unclassified' && row.unclasscount != '') {
            $scope.call.approval = "";
            $scope.call.classify = param;
            $scope.viewcallinformation = true;
            if ($scope.viewcallinformation == true)
                $scope.getDataCallList();
        } else if (param == 'Waiting For Approval' && row.waitingcount != '') {
            $scope.call.approval = "Waiting For Approval";
            $scope.call.classify = "";
            $scope.viewcallinformation = true;
            if ($scope.viewcallinformation == true)
                $scope.getDataCallList();
        } else {
            $scope.viewcallinformation = false;
        }
        $scope.call.department = row.department;
        $scope.call.title = param;

    }

    $scope.closeCallInformation = function () {
        $scope.viewcallinformation = false;
    }

    $scope.tableState = undefined;

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 1,
        pageSize: 30,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages: 1,
        countOfPages: 1
    };

    $scope.getDataCallList = function (tableState) {

        $scope.isCallLoading = true;

        if (tableState != undefined) {
            $scope.tableState = tableState;
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }
        else {
            $scope.paginationOptions.pageNumber = 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = 'id';
            $scope.paginationOptions.sort = 'asc';
        }

        var request = {};
        request.approval = $scope.call.approval;
        request.classify = $scope.call.classify;
        request.dept_id = $scope.call.dept_id;
        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;

        request.call_type = [];
        for (var key in $scope.call_filter) {
            if ($scope.call_filter[key] == true && key != 'All') {
                request.call_type.push(key);
            }
        }
        if (request.call_type.length < 6)
            $scope.calltypecolor = '#f2a30a';
        else
            $scope.calltypecolor = '#fff';

        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;

        $scope.datacalllist = [];

        // only get data
        $http({
            method: 'POST',
            url: '/frontend/callaccount/getmymobilecallsfromfinance',
            data: request,
            headers: { 'Content-Type': 'application/json; charset=utf-8' }
        })
            .then(function (response) {
                $scope.datacalllist = response.data.datalist;

                $scope.paginationOptions.totalItems = response.data.totalcount;
                if ($scope.paginationOptions.totalItems < 1)
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if (tableState != undefined)
                    tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;
                console.log(response);
            }).catch(function (response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function () {
                $scope.isCallLoading = false;
            });
    };
});

app.directive('fileDropzone', function () {
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


    .directive("fileread", [function () {
        return {
            scope: {
                fileread: "=",
                imagename: "=",
                imagetype: "="
            },
            link: function (scope, element, attributes) {
                element.bind("change", function (changeEvent) {
                    var reader = new FileReader();
                    reader.onload = function (loadEvent) {
                        scope.$apply(function () {
                            scope.fileread = loadEvent.target.result;
                        });
                    }
                    scope.imagename = changeEvent.target.files[0].name;
                    scope.imagetype = changeEvent.target.files[0].type;
                    reader.readAsDataURL(changeEvent.target.files[0]);
                });
            }
        }
    }]);

app.controller('ReturnReplyFinanceController', function ($scope, $uibModalInstance, $http, call) {
    $scope.call = call;
    $scope.call.src = '';
    $scope.call.imagename = '';
    $scope.call.imagetype = '';
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

