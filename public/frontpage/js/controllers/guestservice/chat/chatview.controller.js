app.controller('ChatViewController', function ($scope, $window) {

    $scope.active = 1;

    $scope.imageSrc = "";
    $scope.isShowFullImage = false;

    function checkContains(mousePos, rect) {
        if ((mousePos.X >= rect.left && mousePos.X <= rect.left + rect.width) && mousePos.Y >= rect.top && mousePos.Y <= rect.top + rect.height) {
            return true;
        }

        return false;
    }

    $scope.onCheckOutside = function (event) {

        let filterBtnComponent = $('#btn-filter-drop-down');

        let mouseX = event.clientX;
        let mouseY = event.clientY;

        let offset = filterBtnComponent.offset();
        let width = filterBtnComponent.width();
        let height = filterBtnComponent.height();

        let check2 = checkContains({X:mouseX, Y:mouseY}, {left:offset.left, top: offset.top, width: width, height: height});

        if (check2 === true) {
            return;
        }

        let tagInputs = $('#filter-drop-down .tags.focused');

        if (tagInputs.length > 0) {
            return;
        }

        let filterComponent = $('#filter-drop-down');

        if (filterComponent.is(":visible")) {
            offset = filterComponent.offset();
            width = filterComponent.width();
            height = filterComponent.height();

            let checkFilter = checkContains({X:mouseX, Y:mouseY}, {left:offset.left, top: offset.top, width: width, height: height});

            if (checkFilter === true) {
                return;
            }

            // call to component to close filter
            $scope.$broadcast('close_filter_dropdown', {});
        }
    };

    $scope.$on('show_full_image', function (event, imageSrc) {
        $scope.imageSrc = imageSrc;
        $scope.isShowFullImage = true;
    });

    $scope.onCancelFullImage = function() {
        $scope.isShowFullImage = false;
    };

    $scope.onKeyDown = function (event) {

        let chatComponent = $('#guest-chat-page').parent().parent().parent().parent();
        if (chatComponent && chatComponent.hasClass('md-active')) {

            if (event === 5) { // end chat action shortcut (Ctrl + Shift + E)
                $scope.$broadcast('end_chat_action', {});
            } else if (event === 11) { // accept waiting chat (Ctrl + Shift + K)
                $scope.$broadcast('accept_waiting_chat', {});
            } else if (event === 12) {
                $scope.$broadcast('no_answer_action', {});
            }
        }
    };

});

