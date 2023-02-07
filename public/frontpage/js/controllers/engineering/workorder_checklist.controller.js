app.controller('WorkorderChecklistController', function ($scope, $rootScope, $http, $uibModal, $uibModalInstance, AuthService, toaster, workorder, Upload) {
    var MESSAGE_TITLE = 'Workorder Checklist';

    $scope.workorder = angular.copy(workorder);

    $scope.workorder_checklist = [];

    var selected_row = null;

    function getChecklist()
    {
        var request = {};
         
        request.workorder_id = $scope.workorder.id;
        
        var url = '/frontend/eng/workorderchecklist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            var prev = '';
            $scope.workorder_checklist = response.data.content.list.map(item => {
                if( item.category_name == prev )
                    item.category_name = '';
                else
                    prev = item.category_name;
                    if( !item.attached )
                    {
                        item.attached = '';
                        item.download_array = []; 
                    }
                    else
                    {
                        item.download_array = item.attached.split('&');
                    }
            //    $scope.onAttachCheckList(item);
                  updateIconType()
                return item;
            });

            console.log(response);
        }).catch(function(response) {
                console.error('Gists error', response.data);
            })
            .finally(function() {
                
            });
    }

    getChecklist();

    $scope.cancelEdit = function(row)
    {
        $scope.workorder_checklist.forEach(item => {
            if( row && item.id == row.id )
                return;

            item.reading_edit = false;    
            item.comment_edit = false;
        });
    }

    $scope.onClickYesNo = function(row, yes_no)
    {
        if( row.item_type != 'Yes/No')
            return;
        
        var request = {};
         
        request.workorder_id = $scope.workorder.id;
        request.item_id = row.item_id;
        request.item_type = row.item_type;
        request.yes_no = yes_no;
        
        var url = '/frontend/eng/updateworkorderchecklist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {            
            console.log(response);
            row.check_flag = 1;
            row.yes_no = yes_no;
        }).catch(function(response) {
                console.error('Gists error', response.data);                
            })
            .finally(function() {
                
            });
    }

    $scope.onClickReading = function(row)
    {
        if( row.item_type != 'Reading')
            return;

        if( row.reading_edit )    
            return;

        row.reading_edit = true;
        row.new_reading = angular.copy(row.reading);
    }

    $scope.onUpdateReading = function(row)
    {
        if( row.item_type != 'Reading')
            return;

        var request = {};
         
        request.workorder_id = $scope.workorder.id;
        request.item_id = row.item_id;
        request.item_type = row.item_type;
        request.reading = row.new_reading;
        
        var url = '/frontend/eng/updateworkorderchecklist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {            
            console.log(response);
            row.check_flag = 1;
            row.reading_edit = false;
            row.reading = angular.copy(row.new_reading);        
        }).catch(function(response) {
                console.error('Gists error', response.data);                
            })
            .finally(function() {
                
            });
    }
 
    $scope.onClickComment = function(row)
    {
        if( row.comment_edit )    
            return;

        row.comment_edit = true;
        row.new_comment = angular.copy(row.comment);
    }

    $scope.onUpdateComment = function(row)
    {      
        var request = {};
         
        request.workorder_id = $scope.workorder.id;
        request.item_id = row.item_id;        
        request.comment = row.new_comment;
        
        var url = '/frontend/eng/updateworkorderchecklistcomment';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {            
            console.log(response);
            row.check_flag = 1;
            row.comment_edit = false;
            row.comment = angular.copy(row.new_comment);        
        }).catch(function(response) {
                console.error('Gists error', response.data);                
            })
            .finally(function() {
                
            });
    }
    
    $scope.download_array = [];
    $scope.pdf_type_flag = [];
    $scope.icon_class = [];

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

    $scope.uploadFiles = function (files, row) {
        if( files.length < 1 )
            return;

        Upload.upload({
            url: '/frontend/workorder/uploadchecklistfiles',
            data: {              
                id: $scope.workorder.id,  
                files: files
            }
        }).then(function (response) {
            var list = response.data.content;            

            row.download_array = row.download_array.concat(list);            
            updateIconType();

            var attached = row.download_array.join('&');
            updateAttach(attached,row);
        }, function (response) {
            
        }, function (evt) {
            
        });
    };

    $scope.removeFile = function(index, row)
    {      
        console.log('Here');  
        row.download_array.splice(index, 1);
        updateIconType();

        var attached = row.download_array.join('&');

        updateAttach(attached, row);
    }

    function updateAttach(attached, row)
    {
        console.log('Here');  
        selected_row = row;
        console.log(selected_row); 
        if( !selected_row )
            return;
            
        var request = {};
         
        request.workorder_id = $scope.workorder.id;
        request.item_id = selected_row.item_id;        
        request.attached = attached;
        console.log(attached);
        
        var url = '/frontend/eng/updateworkorderchecklistattach';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) { 
            selected_row.attached = attached;           
            console.log(response);            
        }).catch(function(response) {
                console.error('Gists error', response.data);                
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
    };
    
    $scope.downloadFile = function(url) {
        $window.location.href = '/' + url;
    }

    $scope.onConfirm = function()
    {
        $scope.workorder.inspected = $scope.workorder_checklist.filter(item => {
            return item.check_flag == 0;
        }).length == 0;

        $scope.onUpdateInspected($scope.workorder);

        $uibModalInstance.close();        
    }


    $scope.cancel = function() {
        $uibModalInstance.dismiss();        
    }

});
