app.controller('CallTimingsController', function ($scope, $rootScope, $http, $interval, AuthService, toaster, $uibModal) {
    $scope.profile = AuthService.GetCredentials();

    function getLimitTime(exitTime, type = 'next') {
        let existTime = new Date('2000-01-01 ' +  exitTime + ':00');

        let limitTime = null;
        if (type === 'next') {
            limitTime = moment(existTime).add(1, 'm').toDate();
        } else {
            limitTime = moment(existTime).add(-1, 'm').toDate();
        }

        if (limitTime == null) {
            return '';
        }
        return moment(limitTime).format('HH:mm');
    }

    function setOldDate(id, data, type = 'start') {
        if (type === "start") {
            data.start_date = data.old_start_date;
            $('#' + id + '_start_date').val(data.start_date);
        } else {
            data.end_date = data.old_end_date;
            $('#' + id + '_end_date').val(data.end_date);
        }
    }

    function setOldTime(id, timeDetail, type = 'start') {

        if (type === 'start') {
            timeDetail.start_time = timeDetail.old_start_time;
            $('#' + id + '_start_time').val(timeDetail.old_start_time);
        } else {
            timeDetail.end_time = timeDetail.old_end_time;
            $('#' + id + '_end_time').val(timeDetail.old_end_time);
        }

    }
    
    function getCleanedTimingInfo(timingArr) {
        let tempArr = angular.copy(timingArr);
        
        for (let i = 0; i < tempArr.length; i++) {

            let tempInfo = tempArr[i];
            if (tempInfo['$$hashKey'] !== null || tempInfo['$$hashKey'] !== undefined) {
                delete tempInfo['$$hashKey'];
            }

            if (tempInfo['model_start_date'] !== "") {
                tempInfo['model_start_date'] = "";
            }

            if (tempInfo['model_end_date'] !== "") {
                tempInfo['model_end_date'] = "";
            }

            tempInfo['time_details'].map(function (item) {
                if (item['model_start_time'] !== "") {
                    item['model_start_time'] = "";
                }

                if (item['model_end_time'] !== "") {
                    item['model_end_time'] = "";
                }
            });
        }

        return tempArr;
    }

    
    function checkValidateDates(datesArr, curIndex, startDate, endDate) {
        let bResult = true;
        for(let i = 0; i < datesArr.length; i++) {
            if (i === curIndex) {
                continue;
            } 
            
            let tempDateItem = datesArr[i];
            
            if ((startDate >= tempDateItem.start_date && startDate <= tempDateItem.end_date)
                || (endDate >= tempDateItem.start_date && endDate <= tempDateItem.end_date)
                || (tempDateItem.start_date >= startDate && tempDateItem.end_date <= endDate)) {
                toaster.pop('warning', 'Notification', 'Please select correct date or date range');

                bResult = false;
                break;
            }
        }

        return bResult;
    }

    $scope.selectedSkillId = 0;
    $scope.oldSelectedSkillId = 0;
    $scope.skillList = [];

    // for days timing info
    $scope.daysTimingArr = [];
    $scope.oldDaysTimingArr = [];

    $scope.bChangedDays = false;
    $scope.isLoadingDays = false;

    // for special dates timing info
    $scope.bDatesTiming = false;
    $scope.bOldDatesTiming = false;

    $scope.datesTimingArr = [
        {
            start_date: '2021-05-12',
            end_date : '2021-05-13',
            old_start_date: '2021-05-12',
            old_end_date: '2021-05-13',
            model_start_date: "",
            model_end_date: "",

            type: 'all',
            all_info : {
                type: 'hotlync',
                number: ''
            },
            time_details : [
                {
                    'start_time': '00:00',
                    'end_time': '23:59',
                    'type': 'hotlync',
                    'number': '',
                    'old_start_time': '00:00',
                    'old_end_time': '23:59',
                    'model_start_time': "",
                    'model_end_time': ""
                }
            ]
        },
        {
            start_date: '2021-05-14',
            end_date : '2021-05-17',
            old_start_date: '2021-05-14',
            old_end_date: '2021-05-17',
            model_start_date: "",
            model_end_date: "",
            type: 'all',
            all_info : {
                type: 'hotlync',
                number: ''
            },
            time_details : [
                {
                    'start_time': '00:00',
                    'end_time': '23:59',
                    'type': 'hotlync',
                    'number': '',
                    'old_start_time': '00:00',
                    'old_end_time': '23:59',
                    'model_start_time': "",
                    'model_end_time': ""
                }
            ]
        },
        {
            start_date: '2021-05-21',
            end_date : '2021-05-23',
            old_start_date: '2021-05-21',
            old_end_date: '2021-05-23',
            model_start_date: "",
            model_end_date: "",

            type: 'all',
            all_info : {
                type: 'hotlync',
                number: ''
            },
            time_details : [
                {
                    'start_time': '00:00',
                    'end_time': '23:59',
                    'type': 'hotlync',
                    'number': '',
                    'old_start_time': '00:00',
                    'old_end_time': '23:59',
                    'model_start_time': "",
                    'model_end_time': ""
                }
            ]
        },
        {
            start_date: '2021-05-25',
            end_date : '2021-05-27',
            old_start_date: '2021-05-25',
            old_end_date: '2021-05-27',
            model_start_date: "",
            model_end_date: "",
            type: 'all',
            all_info : {
                type: 'hotlync',
                number: ''
            },
            time_details : [
                {
                    'start_time': '00:00',
                    'end_time': '23:59',
                    'type': 'hotlync',
                    'number': '',
                    'old_start_time': '00:00',
                    'old_end_time': '23:59',
                    'model_start_time': "",
                    'model_end_time': ""
                }
            ]
        }
    ];
    $scope.oldDatesTimingArr = angular.copy($scope.datesTimingArr);

    $scope.bChangedDates = false;
    $scope.isLoadingDates = false;

    $scope.testWhatsappKey = 'xFNfwpRkveF013zOrtbk';
    $scope.testWhatsappAuthorization = 'eEZOZndwUmt2ZUYwMTN6T3J0Yms6U211UzNRakJ3blhCbk9TNG5rb3lPT1c2ZVVhSkJ1MXFmSXltR1lOMA==';
    $scope.testChannelId = '94792798-e368-4e53-80c3-6a13d586bb01';
    $scope.testPhoneNumber = '8613321443788';
    $scope.testMessageContent = '<h3>Hello</h3><br/><span style="color: red">Very nice</span>';
    $scope.testLoading = false;

    $scope.onCheckWhatsapp = function () {
        let AuthKey = $scope.testWhatsappKey;
        var body = {
            "Text": $scope.testMessageContent,
            "Number": $scope.testPhoneNumber, // destination number
            "MediaId": "1", //default 1
            "MessageType": "Text", // used for prapproved contents
            "Tool": "API",
            "TemplateID": ""
        };

        let url = `https://restapi.smscountry.com/v0.1/Accounts/${AuthKey}/Whatsapp/${$scope.testChannelId}/Messages/`;

        $scope.testLoading = true;
        // first way
        $http({
            method: 'POST',
            url: url,
            // body : body,
            data: body,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Basic ${$scope.testWhatsappAuthorization}`
            }
        }).then(function(response) {
            console.log(response);

        }).catch(function(response) {
            console.log(response);
        })
            .finally(function() {
                $scope.testLoading = false;
            });

        // second way(result is same with first)
        // var request = new XMLHttpRequest();
        //
        // request.open('POST', url);
        //
        // request.setRequestHeader('Content-Type', 'application/json');
        // request.setRequestHeader('Authorization', 'Basic ' + $scope.testWhatsappAuthorization);
        //
        // request.onreadystatechange = function () {
        //     if (this.readyState === 4) {
        //         console.log('Status:', this.status);
        //         console.log('Headers:', this.getAllResponseHeaders());
        //         console.log('Body:', this.responseText);
        //     }
        //
        //     if (this.readyState === 2) {
        //         let i = 0;
        //     }
        // };
        //
        // request.send(JSON.stringify(body));
    };

    $scope.getTimingInfo = function() {

        $scope.isLoadingDays = true;

        var request = {};
        request.property_id = $scope.profile.property_id;
        request.skill_id = $scope.selectedSkillId;

        $http({
            method: 'POST',
            url: '/frontend/call/timinginfo',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if ($scope.selectedSkillId == 0) {
                $scope.skillList = response.data.skill_list;
            }

            $scope.daysTimingArr = response.data.days_timing_arr;
            $scope.oldDaysTimingArr = angular.copy($scope.daysTimingArr);

            $scope.selectedSkillId = response.data.selected_skill_id;
            $scope.oldSelectedSkillId = response.data.selected_skill_id;
            $scope.bChangedDays = false;

            $scope.bDatesTiming = response.data.dates_flag;
            $scope.bOldDatesTiming = $scope.bDatesTiming;

            $scope.datesTimingArr = response.data.dates_timing_arr;
            $scope.oldDatesTimingArr = angular.copy($scope.datesTimingArr);

            $scope.bChangedDates = false;

        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
            .finally(function() {
                $scope.isLoadingDays = false;
            });
    };
    
    $scope.changeDate = function(skill_id, dataKey, data, type = 'start') {
        let id = skill_id + "_date_" + dataKey;
        let strStartDate = $('#' + id + '_start_date').val();
        let strEndDate = $('#' + id + '_end_date').val();

        if (type === 'start') {
            if (strStartDate > strEndDate) {
                toaster.pop('error', 'Notification', 'Start Date shouldn\'t be bigger than end date!');

                setOldDate(id, data, type);
                return;
            }
        } else {
            if (strEndDate < strStartDate) {
                toaster.pop('error', 'Notification', 'End Date shouldn\'t be smaller than start date!');
                setOldDate(id, data, type);
                return;
            }
        }

        if (!checkValidateDates($scope.datesTimingArr, dataKey, strStartDate, strEndDate)) {
            setOldDate(id, data, type);
            return;
        }

        if (type === 'start') {
            data.start_date = strStartDate;
            data.old_start_date = strStartDate;
        } else {
            data.end_date = strEndDate;
            data.old_end_date = strEndDate;
        }

        $scope.onCompareChangeDatesTimingInfo();
    };

    $scope.changeStartTime = function(skill_id, dataKey, detailKey, timeDetail, timeDetails, format = 'day') {
        let id = skill_id + "_" + dataKey + "_" + detailKey;

        if (format !== 'day') {
            id = skill_id + "_date_" + dataKey + "_" + detailKey;
        }

        let strStartTime = $('#' + id + '_start_time').val();
        let strEndTime = $('#' + id + '_end_time').val();

        if (strStartTime > strEndTime) {
            toaster.pop('error', 'Notification', 'Start time shouldn\'t be bigger than end time!');

            setOldTime(id, timeDetail, 'start');
            return;
        }

        if (detailKey > 0) {
            let prevDetail = timeDetails[detailKey - 1];

            let limitStartTime = getLimitTime(prevDetail.start_time, 'next');

            if (strStartTime < limitStartTime) {
                toaster.pop('error', 'Notification', 'Please insert again!');

                setOldTime(id, timeDetail, 'start');

                return;
            }

            prevDetail.end_time = getLimitTime(strStartTime, 'prev');
            prevDetail.old_end_time = prevDetail.end_time;

            let prevId = skill_id + "_" + dataKey + "_" + (detailKey - 1);
            if (format !== "day") {
                prevId = skill_id + "_date_" + dataKey + "_" + (detailKey - 1);
            }

            $('#' + prevId + '_end_time').val(prevDetail.end_time);
        }

        timeDetail.start_time = strStartTime;
        timeDetail.old_start_time = strStartTime;

        if (format === "day") {
            $scope.onCompareChangeDaysTimingInfo();
        } else {
            $scope.onCompareChangeDatesTimingInfo();
        }

    };

    $scope.changeEndTime = function(skill_id, dataKey, detailKey, timeDetail, timeDetails, format = 'day') {
        let id = skill_id + "_" + dataKey + "_" + detailKey;
        if (format !== 'day') {
            id = skill_id + "_date_" + dataKey + "_" + detailKey;
        }

        let strStartTime = $('#' + id + '_start_time').val();
        let strEndTime = $('#' + id + '_end_time').val();

        if (strEndTime < strStartTime) {
            toaster.pop('error', 'Notification', 'Start time shouldn\'t be smaller than start time!');

            setOldTime(id, timeDetail, 'end');
            return;
        }


        if (detailKey < timeDetails.length - 1) {
            let nextDetail = timeDetails[detailKey + 1];

            let limitEndTime = getLimitTime(nextDetail.end_time, 'prev');

            if (strEndTime > limitEndTime) {
                toaster.pop('error', 'Notification', 'Please insert again!');

                setOldTime(id, timeDetail, 'end');
                return;
            }

            nextDetail.start_time = getLimitTime(strEndTime, 'next');
            nextDetail.old_start_time = nextDetail.start_time;
            let nextId = skill_id + "_" + dataKey + "_" + (detailKey + 1);
            if (format !== "day") {
                nextId = skill_id + "_date_" + dataKey + "_" + (detailKey + 1);
            }

            $('#' + nextId + "_start_time").val(nextDetail.start_time);
        }

        timeDetail.end_time = strEndTime;
        timeDetail.old_end_time = strEndTime;

        if (format === "day") {
            $scope.onCompareChangeDaysTimingInfo();
        } else {
            $scope.onCompareChangeDatesTimingInfo();
        }
    };

    $scope.onAddDetail = function(skill_id, dataKey, detailKey, timeDetail, timeDetails, type = 'day') {

        let id = skill_id + "_" + dataKey + "_" + detailKey;

        if (type !== "day") {
            id = skill_id + "_date_" + dataKey + "_" + detailKey;
        }

        let strStartTime = $('#' + id + '_start_time').val();
        let strEndTime = $('#' + id + '_end_time').val();
        
        if (timeDetail.end_time > '23:58') {
            toaster.pop('error', 'Notification', 'Cannot add new time!');
            return;
        }  
        
        if (detailKey < timeDetails.length - 1) {
            let nextDetail = timeDetails[detailKey + 1];
            
            if (nextDetail.start_time >= nextDetail.end_time) {
                toaster.pop('error', 'Notification', 'Cannot add new time!');
                return;
            }

            let insertTime = nextDetail.start_time;

            nextDetail.start_time = getLimitTime(nextDetail.start_time, 'next');
            nextDetail.old_start_time = nextDetail.start_time;
            let nextId = skill_id + "_" + dataKey + "_" + (detailKey + 1);
            if (type !== "day") {
                nextId = skill_id + "_date_" + dataKey + "_" + (detailKey + 1);
            }

            $('#' + nextId + '_start_time').val(nextDetail.start_time);

            let tempDetail = {
                'start_time' : insertTime,
                'end_time': insertTime,
                'type' : 'hotlync',
                'number' : '',
                'old_start_time' : insertTime,
                'old_end_time' : insertTime,
                'model_start_time': "",
                'model_end_time': "",
            };

            timeDetails.splice(detailKey + 1, 0, tempDetail);
        } else {
            let tempDetail = {
                'start_time' : getLimitTime(timeDetail.end_time, 'next'),
                'end_time': '23:59',
                'type' : 'hotlync',
                'number' : '',
                'old_start_time': getLimitTime(timeDetail.end_time, 'next'),
                'old_end_time': '23:59',
                'model_start_time': "",
                'model_end_time': "",
            };

            timeDetails.push(tempDetail);
        }

        if (type === "day") {
            $scope.onCompareChangeDaysTimingInfo();
        } else {
            $scope.onCompareChangeDatesTimingInfo();
        }
    };

    $scope.onAddDates = function() {
        let modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/add_dates.html',
            backdrop: 'static',
            size: 'md',
            resolve: {
                existArr: function () {
                    let result = [];

                    for (let i = 0; i < $scope.datesTimingArr.length; i++) {
                        let dateItem = $scope.datesTimingArr[i];
                        let temp = {};
                        temp['start_date'] = dateItem.start_date;
                        temp['end_date'] = dateItem.end_date;

                        result.push(temp);
                    }

                    return result;
                }
            },
            controller: function ($scope, $rootScope, $uibModalInstance, AuthService, toaster, existArr) {
                $scope.onCancel = function () {
                    $uibModalInstance.dismiss();
                };

                $scope.exist_arr = existArr;
                $scope.min_date = moment().format('YYYY-MM-DD');

                $scope.start_date = "";
                $scope.end_date = "";

                $scope.onAdd = function() {
                    if (!$scope.onValidate()) {
                        return;
                    }

                    let strStartDate = moment($scope.start_date).format('YYYY-MM-DD');
                    let strEndDate = moment($scope.end_date).format('YYYY-MM-DD');

                    let result = {
                        'start_date': strStartDate,
                        'end_date': strEndDate
                    };

                    $uibModalInstance.close(result);
                };

                $scope.onValidate = function () {

                    let bResult = true;

                    if ($scope.start_date === "" || $scope.end_date === "") {
                        toaster.pop('warning', 'notice', 'Please select dates');
                        return false;
                    }

                    let strStartDate = moment($scope.start_date).format('YYYY-MM-DD');
                    let strEndDate = moment($scope.end_date).format('YYYY-MM-DD');

                    if (strStartDate > strEndDate) {
                        toaster.pop('warning', 'notice', 'Start date shouldn\'t be bigger than end date!');
                        return false;
                    }


                    for (let i = 0; i < $scope.exist_arr.length; i++) {
                        let tempDateItem = $scope.exist_arr[i];

                        if ((strStartDate >= tempDateItem.start_date && strStartDate <= tempDateItem.end_date)
                            || (strEndDate >= tempDateItem.start_date && strEndDate <= tempDateItem.end_date)
                            || (tempDateItem.start_date >= strStartDate && tempDateItem.end_date <= strEndDate)) {
                            toaster.pop('warning', 'Notification', 'Please select correct date or date range');

                            bResult = false;
                            break;
                        }
                    }

                    return bResult;
                }

            },
        });

        modalInstance.result.then(function (ret) {
            let start_date = ret.start_date;
            let end_date = ret.end_date;

            let newDateItem = {
                start_date: start_date,
                end_date : end_date,
                old_start_date: start_date,
                old_end_date: end_date,
                model_start_date: "",
                model_end_date: "",

                type: 'all',
                all_info : {
                    type: 'hotlync',
                    number: ''
                },
                time_details : [
                    {
                        'start_time': '00:00',
                        'end_time': '23:59',
                        'type': 'hotlync',
                        'number': '',
                        'old_start_time': '00:00',
                        'old_end_time': '23:59',
                        'model_start_time': "",
                        'model_end_time': ""
                    }
                ]
            };

            $scope.datesTimingArr.push(newDateItem);
        }, function () {

        });
    };

    $scope.onRemoveDetail = function(skill_id, dataKey, detailKey, timeDetail, timeDetails, type = 'day') {
        let id = skill_id + "_" + dataKey + "_" + detailKey;

        if (type !== 'day') {
            id = skill_id + "_date_" + dataKey + "_" + detailKey;
        }
        
        let strStartTime = $('#' + id + '_start_time').val();

        if (timeDetails.length < 2) {
            return;
        }

        if (timeDetails.length > 2) {
            if (detailKey < timeDetails.length - 1) {
                let nextDetail = timeDetails[detailKey + 1];

                if (detailKey < 1) {
                    nextDetail.start_time = '00:00';
                    nextDetail.old_start_time = '00:00';
                } else {
                    nextDetail.start_time = strStartTime;
                    nextDetail.old_start_time = strStartTime;
                }
                let nextId = skill_id + "_" + dataKey + "_" + (detailKey + 1)
                $('#' + nextId + '_start_time').val(nextDetail.start_time);
            } else {
                let prevDetail = timeDetails[detailKey - 1];

                prevDetail.end_time = '23:59';
                prevDetail.old_end_time = '23:59';

                let prevId = skill_id + "_" + dataKey + "_" + (detailKey - 1);
                $('#' + prevId + '_end_time').val(prevDetail.end_time);
            }
        }

        timeDetails.splice(detailKey, 1);
        if (type === 'day') {
            $scope.onCompareChangeDaysTimingInfo();
        } else {
            $scope.onCompareChangeDatesTimingInfo();
        }
    };

    $scope.onSaveDaysTimingInfo = function() {
        var request = {};
        request['property_id'] = $scope.profile.property_id;

        request['skill_id'] = $scope.selectedSkillId;
        let daysTimingArr = getCleanedTimingInfo($scope.daysTimingArr);
        request['days_info'] = JSON.stringify(daysTimingArr);

        $scope.isLoadingDays = true;
        $http({
            method: 'POST',
            url: '/frontend/call/setdaystiminginfo',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if (response.data.status == 'success') {
                $scope.oldDaysTimingArr = angular.copy($scope.daysTimingArr);
                $scope.bChangedDays = false;
            } else {
                toaster.pop('error', 'Database Error', 'Error in saving!');
            }

        }).catch(function(response) {
            toaster.pop('error', 'Database Error', 'Error in saving!');
        })
            .finally(function() {
                $scope.isLoadingDays = false;
            });
    };

    $scope.onSaveDatesTimingInfo = function() {
        var request = {};
        request['property_id'] = $scope.profile.property_id;

        request['skill_id'] = $scope.selectedSkillId;
        let datesTimingArr = getCleanedTimingInfo($scope.datesTimingArr);
        request['dates_info'] = JSON.stringify(datesTimingArr);
        request['dates_flag'] = $scope.bDatesTiming === true ? 1 : 0;

        $scope.isLoadingDates = true;
        $http({
            method: 'POST',
            url: '/frontend/call/setdatestiminginfo',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            if (response.data.status == 'success') {
                $scope.oldDatesTimingArr = angular.copy($scope.datesTimingArr);
                $scope.bOldDatesTiming = $scope.bDatesTiming;
                $scope.bChangedDates = false;
            } else {
                toaster.pop('error', 'Database Error', 'Error in saving!');
            }

        }).catch(function(response) {
            toaster.pop('error', 'Database Error', 'Error in saving!');
        })
            .finally(function() {
                $scope.isLoadingDates = false;
            });
    };

    $scope.onResetDaysTimingInfo = function() {
        $scope.daysTimingArr = angular.copy($scope.oldDaysTimingArr);

        $scope.bChangedDays = false;
    };

    $scope.onResetDatesTimingInfo = function() {
        $scope.bDatesTiming = $scope.bOldDatesTiming;
        $scope.datesTimingArr = angular.copy($scope.oldDatesTimingArr);
        $scope.bChangedDates = false;
    };

    $scope.onCompareChangeDaysTimingInfo = function() {
        let strOldDaysTimingArr = JSON.stringify(getCleanedTimingInfo($scope.oldDaysTimingArr));
        let strCurTimingInfo = JSON.stringify(getCleanedTimingInfo($scope.daysTimingArr));

        if (strOldDaysTimingArr === strCurTimingInfo) {
            $scope.bChangedDays = false;
        } else {
            $scope.bChangedDays = true;
        }
    };

    $scope.onRemoveDate = function(deleteIndex) {
        $scope.datesTimingArr.splice(deleteIndex, 1);

        $scope.onCompareChangeDatesTimingInfo();
    };

    $scope.onCompareChangeDatesTimingInfo = function() {

        if ($scope.bDatesTiming !== $scope.bOldDatesTiming) {
            $scope.bChangedDates = true;
            return;
        }
        let strOldDatesTimingArr = JSON.stringify(getCleanedTimingInfo($scope.oldDatesTimingArr));
        let strCurDatesTimingArr = JSON.stringify(getCleanedTimingInfo($scope.datesTimingArr));

        if (strOldDatesTimingArr === strCurDatesTimingArr) {
            $scope.bChangedDates = false;
        } else {
            $scope.bChangedDates = true;
        }
    };
    
    $scope.onSelectSkill = function() {
        if ($scope.bChangedDays === true || $scope.bChangedDates === true) {
            toaster.pop('info', 'Notice', 'Please save or reset first!');
            $scope.selectedSkillId = $scope.oldSelectedSkillId;
        } else {
            $scope.getTimingInfo();
        }
    };

    $scope.getTimingInfo();
});
