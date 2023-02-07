app.controller('ChatSettingController', function ($scope, $rootScope, $window, $http, $uibModal, $timeout, $interval, $compile, AuthService, toaster, TranslateService, socket, GuestService, DateService) {
	var profile = AuthService.GetCredentials();
	$scope.agent_id = profile.id;

	$scope.isLoading = false;

	$scope.setting = {
		warning_time: 0,
		critical_time: 0,
		job_roles: [],
		end_chat: '',
		no_answer: '',
		accept_chat: ''
	};

	$scope.oldSetting = angular.copy($scope.setting);

	$scope.bChanged = false;

	$scope.joblistFilter = function(query) {
		return $scope.jobRoleDataForAll.filter(function(item) {
			return item.job_role.toLowerCase().indexOf(query.toLowerCase()) != -1;
		});
	};

	$scope.checkChanged = function () {
		let curSetting = JSON.stringify($scope.setting);
		let oldSetting = JSON.stringify($scope.oldSetting);
		if (curSetting === oldSetting) {
			$scope.bChanged = false;
		} else {
			$scope.bChanged = true;
		}
	};

	$scope.job_role_list = [
		{id: 'Waiting', label: 'Waiting'},
		{id: 'Active', label: 'Active'},
		{id: 'Ended', label: 'Ended'}
	];
	$scope.job_roles_setting = {
		keyboardControls: true,
		scrollable: true,
		scrollableHeight: 400,
		enableSearch: true,
		checkBoxes: true,
		smartButtonTextConverter: function (itemText) {
			return itemText;
		}
	};

	$scope.job_roles_hint = { buttonDefaultText: 'Select Job Roles' };

	$scope.job_roles_events = {
		onDeselectAll: function () {
			$timeout(function () {
				$scope.checkChanged();
			}, 500);
		},
		onSelectAll: function () {
			$timeout(function () {
				$scope.checkChanged();
			}, 500);
		},
		onItemSelect: function () {
			$scope.checkChanged();
		},
		onItemDeselect: function () {
			$scope.checkChanged();
		}
	};

	$scope.onReset = function() {

		$scope.setting = angular.copy($scope.oldSetting);

		let oldJobRoleIds = $scope.oldSetting.job_roles.map(item => {
			return item.id;
		});


		$scope.setting.job_roles = $scope.job_role_list.filter(item => {
			if (oldJobRoleIds.includes(item.id)) {
				return true;
			}
			return false;
		});

		$scope.bChanged = false;
	};

	$scope.onSaveChatSetting = function () {

		if ($scope.setting.critical_time != 0 && $scope.setting.critical_time <= $scope.setting.warning_time) {
			toaster.pop('Warning', "Notification", "Critical Time should be bigger than warning time!");
			return;
		}

		let request = {};
		request.property_id = profile.property_id;
		request.warning_time = $scope.setting.warning_time;
		request.critical_time = $scope.setting.critical_time;
		request.job_role_ids = $scope.setting.job_roles.length > 0 ? $scope.setting.job_roles.map(item => {
			return item.id;
		}).join(',') : '';

		request.end_chat = $scope.setting.end_chat;
		request.no_answer = $scope.setting.no_answer;
		request.accept_chat = $scope.setting.accept_chat;

		$http({
			method: 'POST',
			url: '/frontend/guestservice/saveguestchatsettinginfo',
			data: request,
			headers: { 'Content-Type': 'application/json; charset=utf-8' }
		})
			.then(function (response) {
				if( response.data.success == true )
				{
					toaster.pop('success', 'Notification', 'Updated successfully!');

					let sending_data = {
						warning_time: $scope.setting.warning_time,
						critical_time: $scope.setting.critical_time,
						end_chat: $scope.setting.end_chat,
						no_answer: $scope.setting.no_answer,
						accept_chat: $scope.setting.accept_chat
					};
					sending_data.job_role_ids = $scope.setting.job_roles.map(item => {
						return item.id;
					}).join(',');

					$rootScope.$emit('guestchat_setting_updated', sending_data);

					$scope.oldSetting = angular.copy($scope.setting);
					$scope.bChanged = false;
				}
				else
					toaster.pop('info', MESSAGE_TITLE, response.data.message);

			}).catch(function (response) {
				console.error('Gists error', response.status, response.data);
			})
			.finally(function () {
				$scope.isLoading = false;
			});
	};

	function getGuestChatSettingInfo() {
		let property_id = profile.property_id;
		$http.get('/frontend/guestservice/getguestchatsettinginfo?property_id=' + property_id)
			.then(function (response) {
				 $scope.setting.warning_time = parseInt(response.data.warning_time);
				 $scope.setting.critical_time = parseInt(response.data.critical_time);
				 $scope.setting.end_chat = response.data.end_chat;
				 $scope.setting.no_answer = response.data.no_answer;
				 $scope.setting.accept_chat = response.data.accept_chat;

				 let tempJobRoleIds = response.data.job_role_ids.length > 0 ? response.data.job_role_ids.split(',') : [];

				 $scope.setting.job_roles = $scope.job_role_list.filter(jobRoleInfo => {
				 	 if (tempJobRoleIds.includes(jobRoleInfo.id.toString())) {
				 	 	return true;
					 }

				 	 return false;
				 });

				 $scope.oldSetting = angular.copy($scope.setting);
			});
	}

	function initial () {
		let property_id = profile.property_id;
		$http.get('/frontend/guestservice/getjobrolelist?property_id=' + property_id)
			.then(function (response) {
				$scope.job_role_list = response.data;

				getGuestChatSettingInfo();
			});
	}

	initial();
});
