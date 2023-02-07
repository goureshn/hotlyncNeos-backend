'use strict';

app.controller('GuestAnswerController', function($scope, $http, $state, $q, $timeout, $translate, toaster, mwFormResponseUtils) {
    var ctrl = this;
    var MESSAGE_TITLE = 'Survey answer Page';

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
    ctrl.templateData= {};

    ctrl.saveResponse = function(){
        var d = $q.defer();

        var request = {};
        request.id = $scope.survey_data.id;
        request.token = $scope.server_param.token;
        request.answer = JSON.stringify(ctrl.responseData);

        $http({
            method: 'POST',
            url: '/frontend/guestsurvey/postanswer',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                d.resolve(true);
                toaster.pop('success', MESSAGE_TITLE, 'Your answer is posted successfully');
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
                d.reject();
            })
            .finally(function() {

            });

        return d.promise;
    };

    ctrl.onImageSelection = function (){

        var d = $q.defer();
        var src = prompt("Please enter image src");
        if(src !=null){
            d.resolve(src);
        }else{
            d.reject();
        }

        return d.promise;
    };

    ctrl.resetViewer = function(){
        if(ctrl.formViewer.reset){
            ctrl.formViewer.reset();
        }

    };

    ctrl.resetBuilder= function(){
        if(ctrl.formBuilder.reset){
            ctrl.formBuilder.reset();
        }
    };

    ctrl.changeLanguage = function (languageKey) {
        $translate.use(languageKey);
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

    var request = {};

    console.log($scope.server_param);

    request.survey_id = $scope.server_param.survey_id;
    request.token = $scope.server_param.token;

    $scope.survey_data = {};
    $http({
        method: 'POST',
        url: '/frontend/guestsurvey/surveytemplate',
        data: request,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    })
        .then(function(response) {
            $scope.survey_data = response.data;
            ctrl.formData = JSON.parse(response.data.builder);
            if( !ctrl.formData )
                ctrl.formData = {};

            //ctrl.responseData = JSON.parse(response.data.answer);
            //if( !ctrl.responseData )
            //    ctrl.responseData = {};

            ctrl.resetViewer();

        }).catch(function(response) {
            console.error('Gists error', response.status, response.data);
        })
        .finally(function() {

        });

});