app.controller('CallSkillsController', function ($scope, $rootScope, $http, $interval, AuthService, toaster, $uibModal) {        
    // Skill List
    $scope.skill_list = [];
    $scope.getSkillList = function () 
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        var url = '/frontend/call/skilllist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.skill_list = response.data;
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }

    $scope.onCreateSkill = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/call_skill_edit.html',
            controller: 'CallSkillEditController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                skill: function () {
                    return row;
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.getSkillList();
        }, function () {

        }); 
    }     
    
    $scope.onDeleteSkill = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure want to delete this skill?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');                    
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                deleteSkill(row);            
        }, function () {

        });    
    }

    function deleteSkill(row)
    {
        var request = {};
        request.id = row.id;
        
        $http({
            method: 'POST',
            url: '/frontend/call/deleteskill',
            data: request,            
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getSkillList();
                $scope.getSkillGroupList();
                $scope.getAgentList();                
            }).catch(function(response) {
                
            })
            .finally(function() {
            });

    }

    // Skill Group List
    $scope.skill_group_list = [];
    $scope.getSkillGroupList = function () 
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        var url = '/frontend/call/skillgrouplist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.skill_group_list = response.data;
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }

    $scope.loadSkillFilters = function (query) {
        return $scope.skill_list.filter(function (type) {
            return type.name.toLowerCase().indexOf(query.toLowerCase()) != -1;
        });
    };

    $scope.onCreateSkillGroup = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/call_skill_group_edit.html',
            controller: 'CallSkillGroupEditController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                skill_group: function () {
                    return row;
                },
            }
        });

        modalInstance.result.then(function (selectedItem) {
            $scope.getSkillGroupList();
        }, function () {

        }); 
    }      

    $scope.onDeleteSkillGroup = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure want to delete this skill group?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');                    
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                deleteSkillGroup(row);            
        }, function () {

        });    
    }

    function deleteSkillGroup(row)
    {
        var request = {};
        request.id = row.id;
        
        $http({
            method: 'POST',
            url: '/frontend/call/deleteskillgroup',
            data: request,            
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getSkillGroupList();                
            }).catch(function(response) {
                
            })
            .finally(function() {
            });

    }
    
    // Agent Skill List
    $scope.agent_list = [];
    $scope.getAgentList = function () 
    {
        var request = {};
        
        var profile = AuthService.GetCredentials();
        request.property_id = profile.property_id;
        
        var url = '/frontend/call/agentskillist';

        $http({
            method: 'POST',
            url: url,
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.agent_list = response.data;
        }).catch(function(response) {
            console.error('Gists error', response.data);
        })
        .finally(function() {
        
        });
    }

    $scope.onEditAgentSkill = function(row) {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/calls/modal/call_agent_skill_edit.html',
            controller: 'CallAgentSkillEditController',
            size: 'md',
            scope: $scope,
            backdrop: 'static',
            resolve: {
                agent: function () {
                    return row;
                },
            }
        });

        modalInstance.result.then(function () {
            $scope.getAgentList();
        }, function () {

        }); 
    }      
});

app.controller('CallSkillEditController', function ($scope,$window, $http, $uibModal, $uibModalInstance, AuthService, toaster, skill) {
    var MESSAGE_TITLE = 'Call Skill';

    var profile = AuthService.GetCredentials();
    $scope.skill = angular.copy(skill);

    $scope.createSkill = function() {
        
        var request = $scope.skill;
        request.property_id = profile.property_id;

        $http({
            method: 'POST',
            url: '/frontend/call/createskill',
            data: request,            
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                if( response.data.code == 200 )
                    $uibModalInstance.close();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
            });

    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }
});

app.controller('CallSkillGroupEditController', function ($scope,$window, $http, $uibModal, $uibModalInstance, AuthService, toaster, skill_group) {
    var MESSAGE_TITLE = 'Call Skill Group';

    var profile = AuthService.GetCredentials();
    if( !skill_group )
        skill_group = {};

    $scope.skill_group = angular.copy(skill_group);
    if( !$scope.skill_group.skill_tags )
        $scope.skill_group.skill_tags = [];
    
    $scope.getdepartment = function(val) {
            if( val == undefined )
                val = "";
      //      return $http.get('/backoffice/user/wizard/departmentlist?building_ids=' + building_ids + '&department='+val)
            return $http.get('/frontend/call/departlist?property_id=' + profile.property_id  + '&department='+val)
                .then(function(response){
                    return response.data.map(function(item){
                        return item;
                    });
                });
        };
    
        $scope.ondepartment = function (department, $item, $model, $label) {
            var departments = {};
           
            $scope.skill_group.dept_id = $item.id;
            $scope.skill_group.department = $item.department;
          
        };
    $scope.createSkillGroup = function() {
        
        var request = $scope.skill_group;
        request.property_id = profile.property_id;
        request.skill_ids = $scope.skill_group.skill_tags.map(item => item.id).join(",");

        $http({
            method: 'POST',
            url: '/frontend/call/createskillgroup',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                console.log(response);
                if( response.data.code == 200 )
                    $uibModalInstance.close();
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to create Work Request!');
            })
            .finally(function() {
            });

    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }
});

app.controller('CallAgentSkillEditController', function ($scope,$window, $http, $uibModal, $uibModalInstance, AuthService, toaster, agent) {
    var MESSAGE_TITLE = 'Agent Skill';

    $scope.skill_candidate_list = angular.copy($scope.skill_list);

    function initData()
    {
        $scope.skill_level = {};

        $scope.skill_level.id = 0;
        $scope.skill_level.name = '';
        $scope.skill_level.level = 0;
    }

    initData();

    $scope.agent_skill_level_list = [];
    $scope.getSkillLevelList = function()
    {
        var request = {};
        request.agent_id = agent.id;        

        $http({
            method: 'POST',
            url: '/frontend/call/agentskillevellist',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.agent_skill_level_list = response.data;
                $scope.skill_candidate_list = $scope.skill_list.filter(row => {
                    return $scope.agent_skill_level_list.filter(row1 => row1.skill_id == row.id).length == 0;
                });
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to get Skill Level list!');
            })
            .finally(function() {
            });

    }

    $scope.onSkillSelect = function($item, $model, $label)
    {
        $scope.skill_level.name = $item.name;
        $scope.skill_level.skill_id = $item.id;
    }

    $scope.onEditRow = function(row)
    {
        $scope.skill_level = angular.copy(row);
    }

    $scope.addRow = function()
    {
        var request = {};
        request.agent_id = agent.id;   
        request.skill_id = $scope.skill_level.skill_id;      
        request.level = $scope.skill_level.level;      

        $http({
            method: 'POST',
            url: '/frontend/call/addagentskilllevel',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getSkillLevelList();
                initData();               
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to get Skill Level list!');
            })
            .finally(function() {
            });

    }

    $scope.editRow = function()
    {
        var request = {};
        request.id = $scope.skill_level.id;   
        request.agent_id = agent.id;   
        request.skill_id = $scope.skill_level.skill_id;      
        request.level = $scope.skill_level.level;      

        $http({
            method: 'POST',
            url: '/frontend/call/addagentskilllevel',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getSkillLevelList();
                initData();               
            }).catch(function(response) {
                toaster.pop('error', MESSAGE_TITLE, 'Failed to get Skill Level list!');
            })
            .finally(function() {
            });

    }

    $scope.onDeleteRow = function(row) {
        var message = {};

        message.title = 'Confirm Dialog';
        message.content = 'Are you sure want to delete this skill?';

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/modal/modal_confirm.html',            
            resolve: {
                message: function () {
                    return message;
                }
            },            
            controller: function ($scope, $uibModalInstance) {
                $scope.message = message;
                $scope.ok = function (e) {
                    $uibModalInstance.close('ok');                    
                };
                $scope.cancel = function (e) {
                    $uibModalInstance.dismiss();                    
                };                
            },
        });

        modalInstance.result.then(function (ret) {
            if( ret == 'ok' )
                deleteRow(row);            
        }, function () {

        });    
    }

    function deleteRow(row)
    {
        var request = {};
        request.id = row.id;
        
        $http({
            method: 'POST',
            url: '/frontend/call/deleteagentskilllevel',
            data: request,            
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        })
            .then(function(response) {
                $scope.getSkillLevelList();
            }).catch(function(response) {
                
            })
            .finally(function() {
            });

    }

    $scope.ok = function()
    {
        $uibModalInstance.close();
    }

    $scope.cancel = function()
    {
        $uibModalInstance.dismiss();
    }
});


