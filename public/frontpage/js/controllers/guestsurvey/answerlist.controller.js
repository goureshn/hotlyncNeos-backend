app.controller('AnswerlistController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, $q, AuthService, toaster, mwFormResponseUtils) {
    var MESSAGE_TITLE = 'Answer List Page';

    $scope.full_height = 'height: ' + ($window.innerHeight - 45) + 'px; overflow-y: auto;';
    $scope.box_height = 'height: ' + ($window.innerHeight - 130) + 'px; overflow-y: auto;';

    function initData() {

    }

    initData();

    $scope.isGroupLoading = true;
    $scope.selectgroup = {};

    $scope.getGroupList = function getDataList(tableState) {
        //here you could create a query string from tableState
        //fake ajax call
        $scope.isGroupLoading = true;

        if( tableState != undefined )
        {
            $scope.tableGroupState = tableState;
        }

        var request = {};
        var profile = AuthService.GetCredentials();
        request.dept_id = profile.dept_id;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestsurvey/surveylistnames',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.grouplist = response.data.datalist;

                $scope.selectgroup = $scope.grouplist[0];
                $scope.getDataList();

                $scope.room_type = response.data.room_type;
                $scope.room_status = response.data.room_status;
                $scope.guest_type = response.data.guest_type;
                $scope.check_list_items = response.data.check_list_items;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isGroupLoading = false;
            });
    };

    $scope.onClickGroup = function(row) {
        $scope.selectgroup = row;
        $scope.getDataList();
    }

    $scope.isLoading = false;

    //  pagination
    $scope.paginationOptions = {
        pageNumber: 0,
        pageSize: 20,
        sort: 'desc',
        field: 'id',
        totalItems: 0,
        numberOfPages : 1,
        countOfPages: 1
    };

    $scope.getDataList = function getDataList(tableState) {
        if( tableState != undefined ) {
            $scope.tableState = tableState;
        }
        if( !($scope.selectgroup.id > 0) )
            return;

        // fake ajax call
        $scope.isLoading = true;



        if( tableState != undefined )
        {
            var pagination = tableState.pagination;

            $scope.paginationOptions.pageNumber = pagination.start || 0;     // This is NOT the page number, but the index of item in the list that you want to use to display the table.
            $scope.paginationOptions.pageSize = pagination.number || $scope.paginationOptions.pageSize;  // Number of entries showed per page.
            $scope.paginationOptions.field = tableState.sort.predicate;
            $scope.paginationOptions.sort = tableState.sort.reverse ? 'desc' : 'asc';
        }

        var request = {};
        var profile = AuthService.GetCredentials();
        request.dept_id = profile.dept_id;
        request.property_id = profile.property_id;

        request.page = $scope.paginationOptions.pageNumber;
        request.pagesize = $scope.paginationOptions.pageSize;
        request.field = $scope.paginationOptions.field;
        request.sort = $scope.paginationOptions.sort;
        request.survey_id = $scope.selectgroup.id;

        $http({
            method: 'POST',
            url: '/frontend/guestsurvey/answerlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.datalist = response.data.datalist;
                $scope.paginationOptions.totalItems = response.data.totalcount;

                for(var i = 0; i < $scope.datalist.length; i++)
                {
                    $scope.datalist[i].formData = JSON.parse($scope.datalist[i].builder);
                    if( !$scope.datalist[i].formData )
                        $scope.datalist[i].formData = {};
                    //$scope.datalist[i].formData = {};
                    $scope.datalist[i].optionsBuilder={
                        /*elementButtons:   [{title: 'My title tooltip', icon: 'fa fa-database', text: '', callback: ctrl.callback, filter: ctrl.filter, showInOpen: true}],
                         customQuestionSelects:  [
                         {key:"category", label: 'Category', options: [{key:"1", label:"Uno"},{key:"2", label:"dos"},{key:"3", label:"tres"},{key:"4", label:"4"}], required: false},
                         {key:"category2", label: 'Category2', options: [{key:"1", label:"Uno"},{key:"2", label:"dos"},{key:"3", label:"tres"},{key:"4", label:"4"}]}
                         ],
                         elementTypes: ['question', 'image']*/
                    };
                    $scope.datalist[i].formBuilder={};
                    $scope.datalist[i].formViewer = {};
                    $scope.datalist[i].formOptions = {
                        autoStart: true
                    };
                    $scope.datalist[i].formStatus= {};

                    $scope.datalist[i].responseData = JSON.parse($scope.datalist[i].answer);
                    if( !$scope.datalist[i].responseData )
                        $scope.datalist[i].responseData = {};

                    $scope.datalist[i].templateData = {};
                }

                var numberOfPages = 0;

                if( $scope.paginationOptions.totalItems < 1 )
                    numberOfPages = 0;
                else
                    numberOfPages = parseInt(($scope.paginationOptions.totalItems - 1) / $scope.paginationOptions.pageSize + 1);

                if( tableState != undefined )
                    tableState.pagination.numberOfPages = numberOfPages;
                else
                    $scope.tableState.pagination.numberOfPages = numberOfPages;

                $scope.paginationOptions.countOfPages = numberOfPages;

                $scope.room_type = response.data.room_type;
                $scope.room_status = response.data.room_status;
                $scope.check_list_items = response.data.check_list_items;

                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    };

    $scope.onClickRow = function(row, index) {
        row.collapse = !row.collapse;
        //ctrl.resetViewer(row);
        ctrl.resetBuilder(row);
        //if(ctrl.formBuilder.reset){
        //    ctrl.formBuilder.reset(row);
        //}
        for(var i = 0; i < $scope.datalist.length; i++)
        {
            if( i == index )
                continue;

            $scope.datalist[i].collapse = false;
        }
    }

    $scope.getRoomTypes = function(row) {
        return getValuefromID(row.room_type, $scope.room_type, 'type');
    }

    $scope.getGuestType = function(row) {
        return getValuefromID(row.guest_type, $scope.guest_type, 'guest_type');
    }

    function getValuefromID(ids, values, key)
    {
        var ids = JSON.parse(ids);
        var result = '';
        var index = 0;
        for(var i = 0; i < ids.length; i++)
        {
            for( var j = 0; j < values.length; j++)
            {
                if( ids[i] == values[j].id )
                {
                    if( index > 0 )
                        result += ', ';
                    result +=  values[j][key];
                    index++;
                    break;
                }
            }
        }

        return result;
    }

    function getArrayfromID(ids, values)
    {
        var ids = JSON.parse(ids);
        var result = [];
        for(var i = 0; i < ids.length; i++)
        {
            for( var j = 0; j < values.length; j++)
            {
                if( ids[i] == values[j].id )
                {
                    result.push(values[j]);
                    break;
                }
            }
        }

        return result;
    }

    var ctrl = this;

    ctrl.formData = {};
    ctrl.formBuilder={};
    ctrl.formViewer = {};
    ctrl.formOptions = {
        autoStart: true
    };
    ctrl.optionsBuilder={
        /*elementButtons:   [{title: 'My title tooltip', icon: 'fa fa-database', text: '', callback: ctrl.callback, filter: ctrl.filter, showInOpen: true}],
         customQuestionSelects:  [
         {key:"category", label: 'Category', options: [{key:"1", label:"Uno"},{key:"2", label:"dos"},{key:"3", label:"tres"},{key:"4", label:"4"}], required: false},
         {key:"category2", label: 'Category2', options: [{key:"1", label:"Uno"},{key:"2", label:"dos"},{key:"3", label:"tres"},{key:"4", label:"4"}]}
         ],
         elementTypes: ['question', 'image']*/
    };
    ctrl.formStatus= {};
    ctrl.responseData={};
    ctrl.templateData = {};

    ctrl.saveResponse = function(row){
        var d = $q.defer();
        //var res = confirm("Response save success?");
        //if(res){
            d.resolve(true);
        //}else{
        //    d.reject();
        //}
        return d.promise;
    };

    ctrl.onImageSelection = function (row){

        var d = $q.defer();
        var src = prompt("Please enter image src");
        if(src !=null){
            d.resolve(src);
        }else{
            d.reject();
        }

        return d.promise;
    };

    ctrl.resetViewer = function(row){
        //if(row.formViewer.reset){
        //    row.formViewer.reset();
        //}

    };

    ctrl.resetBuilder= function(row){
        if(row.formBuilder.reset){
            row.formBuilder.reset();
        }
    };

    ctrl.getMerged=function(){
        return mwFormResponseUtils.mergeFormWithResponse(ctrl.formData, ctrl.responseData);
    };

    ctrl.getQuestionWithResponseList=function(){
        return mwFormResponseUtils.getQuestionWithResponseList(ctrl.formData, ctrl.responseData);
    };
    ctrl.getResponseSheetRow=function(){
        return mwFormResponseUtils.getResponseSheetRow(ctrl.formData, ctrl.responseData);
    };
    ctrl.getResponseSheetHeaders=function(){
        return mwFormResponseUtils.getResponseSheetHeaders(ctrl.formData, ctrl.headersWithQuestionNumber);
    };

    ctrl.getResponseSheet=function(){
        return mwFormResponseUtils.getResponseSheet(ctrl.formData, ctrl.responseData, ctrl.headersWithQuestionNumber);
    };

    $scope.saveSurvey = function(row) {
        var request = {};

        request.id = row.id;
        request.builder = JSON.stringify(row.formData);

        $http({
            method: 'POST',
            url: '/frontend/guestsurvey/postsurveybuilder',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'SurveyBuilder have been changed successfully');
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }

    $scope.cancelSurvey = function(row) {
        ctrl.resetViewer(row);
        ctrl.resetBuilder(row);
    }

});
