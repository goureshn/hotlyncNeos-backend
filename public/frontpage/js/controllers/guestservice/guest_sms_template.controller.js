app.controller('GusetSmsTemplateController', function($scope, $rootScope , $http, AuthService, toaster) {
    var MESSAGE_TITLE = 'Compensation Template';

    $scope.guest_sms_temp = '';

    var profile = AuthService.GetCredentials();
    var select_pos = {};

    function loadTemplate() {
        var request = {};
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/getguestsmstemplate',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.guest_sms_temp = response.data.template;

                $scope.temp_item_list = response.data.temp_item_list;
                $scope.temp_item = response.data.temp_item_list[0].key;

            }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    loadTemplate();

    $scope.saveTemplate = function() {
        var request = {};
        request.property_id = profile.property_id;
        request.template = $scope.guest_sms_temp;

        $http({
            method: 'POST',
            url: '/frontend/guestservice/saveguestsmstemplate',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                toaster.pop('success', MESSAGE_TITLE, 'Template is updated successfully.')
            }).catch(function(response) {

            })
            .finally(function() {

            });
    }

    $scope.onChangedItem = function(key) {
        console.log(key);

        select_pos.text = '{{' + key + '}}';
        $rootScope.$broadcast('add', select_pos.text);
     }

});

app.directive('myText', ['$rootScope', function($rootScope) {
    return {
        link: function(scope, element, attrs) {
            $rootScope.$on('add', function(e, val) {
                var domElement = element[0];

                if (document.selection) {
                    domElement.focus();
                    var sel = document.selection.createRange();
                    sel.text = val;
                    domElement.focus();
                } else if (domElement.selectionStart || domElement.selectionStart === 0) {
                    var startPos = domElement.selectionStart;
                    var endPos = domElement.selectionEnd;
                    var scrollTop = domElement.scrollTop;
                    domElement.value = domElement.value.substring(0, startPos) + val + domElement.value.substring(endPos, domElement.value.length);
                    scope.guest_sms_temp = domElement.value;
                    domElement.focus();
                    domElement.selectionStart = startPos + val.length;
                    domElement.selectionEnd = startPos + val.length;
                    domElement.scrollTop = scrollTop;
                } else {
                    domElement.value += val;
                    domElement.focus();
                }

            });
        }
    }
}])
