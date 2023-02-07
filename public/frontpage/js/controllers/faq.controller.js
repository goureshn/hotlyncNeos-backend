app.controller('FaqController', function($scope, $rootScope, $http, $window, $uibModal, $timeout, $interval, AuthService, toaster) {
    var MESSAGE_TITLE = 'FAQ';

    $scope.full_height = 'height: ' + ($window.innerHeight - 120) + 'px; overflow-y: auto';
   
    $scope.show = 'list';
    var profile = AuthService.GetCredentials();
    var permission_group_id = profile.permission_group_id;
    
    $http.get('/frontend/faq/getmodulelist?permission_group_id='+permission_group_id).success( function(response) {
        $scope.modules = response;
        var module = {};
        module.id = 0;
        module.name = 'All Module';
        $scope.modules.push(module);
        $scope.module_id = 0;
    });


    $scope.getFaqList = function () {
        var request = {};
        request.permission_group_id = permission_group_id;
        request.module_id = $scope.module_id;
        request.searchtext = $scope.searchtext;        

        $http({
            method: 'POST',
            url: '/frontend/faq/getfaqlist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.show = 'list';
                $scope.faqlist = response.data.faqlist;
                $scope.totalcount = response.data.totalcount;
                
                console.log(response);
            }).catch(function(response) {
                console.error('Gists error', response.status, response.data);
            })
            .finally(function() {
                $scope.isLoading = false;
            });
    }
    $scope.getFaqList();

    $scope.onSearch = function() {
        $scope.getFaqList();
    }

    $scope.viewTag = function(val) {
        var tags = val;
        var tag_val = "";
        for(var i = 0 ; i < tags.length ; i++) {
            tag_val += " <label class='tag_group'>"+tags[i].text+"</label>";   
        }
        return  tag_val;
    }

    $scope.viewDetail = function (faq) {
        $scope.faq = faq;
        $scope.show = 'detail';
    };

    $scope.backList = function() {
        $scope.show = "list";
    }

});


