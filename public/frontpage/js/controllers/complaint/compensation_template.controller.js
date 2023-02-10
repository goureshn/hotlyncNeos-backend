app.controller('CompensationTemplateController', function($scope, $http, AuthService, toaster) {
	var MESSAGE_TITLE = 'Compensation Template';

    $scope.comp_temp = '';

    var profile = AuthService.GetCredentials();
    var select_pos = {};
    select_pos.index = 0;
	select_pos.length = 0;

    function loadTemplate() {
    	var request = {};
    	request.property_id = profile.property_id;

    	$http({
	        method: 'POST',
	        url: '/frontend/complaint/getcomptemplate',
	        data: request,
	        headers: {'Content-Type': 'application/json; charset=utf-8'}
	    })
	        .then(function(response) {
	            $scope.comp_temp = response.data.template;

	            $scope.temp_item_list = response.data.temp_item_list;
	            $scope.temp_item = response.data.temp_item_list[0].key;

	            select_pos = {};
	            select_pos.index = 0;
	            select_pos.length = 0;
	        }).catch(function(response) {
	            
	        })
	        .finally(function() {
	            
	        });	
    }
    
    loadTemplate();

    $scope.saveTemplate = function() {
    	var request = {};
    	request.property_id = profile.property_id;
    	request.template = $scope.comp_temp;

    	$http({
	        method: 'POST',
	        url: '/frontend/complaint/savecomptemplate',
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

    	$scope.$broadcast('insert-text', select_pos);    	
    }

	$scope.editorCreated = function (editor) {
	  console.log(editor)
	}
	$scope.contentChanged = function (editor, html, text, delta, oldDelta, source) {
	  console.log('delta: ', delta, 'oldDelta:', oldDelta);
	}
	$scope.selectionChanged = function (editor, range, oldRange, source) {
		select_pos = range;
	  	console.log('editor: ', editor, 'range: ', range, 'oldRange:', oldRange, 'source:', source)
	}
});