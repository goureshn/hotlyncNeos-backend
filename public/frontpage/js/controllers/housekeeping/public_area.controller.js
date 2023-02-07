app.controller('PublicAreaController', function($scope, $rootScope, $http, $window, $uibModal, AuthService, toaster) {
    
    $scope.calendar = [];
    $scope.publicAreas = [];

    $scope.publicAreasMain = [];

    $scope.location_tags = [];

    $scope.mainTaskId = "0";
    $scope.mainTaskName = "Task";

    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"];
    const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    const timeRange = ["0","1","2","3","4","5","6","7","8","9","10","11","12","13","14","15","16","17","18","19","20","21","22","23"];

    $scope.selectReportBy = ["User","Location","Date"];
    $scope.selectReportDuration = ["Day","Week","Month"];

    $scope.reportBy = "User";
    $scope.reportDuration = "Day";

    $scope.test = "#f6b73c";

    $scope.getTasks = function(){
        $http({
            method: 'POST',
            url: '/hskp/publicAreaGetTasks',
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            //console.log(response.data);
            response.data.forEach(element => {
                if(element.status == 1){
                    element.status = true;
                }
                else{
                    element.status = false;
                }
            });
            $scope.publicAreas = response.data;
            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }
    $scope.getTasksByMainId = function(data){
        request = {};
        request.id = data.id;
        $http({
            method: 'POST',
            url: '/hskp/publicAreaGetTasksByMainId',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            response.data.forEach(element => {
                if(element.status == 1){
                    element.status = true;
                }
                else{
                    element.status = false;
                }
            });
            $scope.publicAreas = response.data;
            
        }).catch(function(response) {
        })
        .finally(function() {

        });
    }

    $scope.getTasksMain = function(){
        $http({
            method: 'POST',
            url: '/hskp/publicAreaGetTasksMain',
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            $scope.publicAreasMain = response.data;
        }).catch(function(response) {
        })
        .finally(function() {
        });
    }
    $scope.getTasksMain();

    $scope.nextCalendarDay = function(){
        $scope.calendarFullDate.setDate( $scope.calendarFullDate.getDate() + 1);
        $scope.calendarDate = $scope.calendarFullDate.getDate();
        $scope.calendarMonth = $scope.calendarFullDate.getMonth();
        $scope.calendarMonthName = monthNames[$scope.calendarFullDate.getMonth()];
        $scope.calendarDay = $scope.calendarFullDate.getDay();
        $scope.calendarDayName = dayNames[$scope.calendarFullDate.getDay()];
        $scope.calendarYear = $scope.calendarFullDate.getFullYear();
        $scope.daysInMonth = new Date($scope.calendarYear, $scope.calendarMonth, 0).getDate();
    }
    $scope.previousCalendarDay = function(){
        $scope.calendarFullDate.setDate( $scope.calendarFullDate.getDate() - 1);
        $scope.calendarDate = $scope.calendarFullDate.getDate();
        $scope.calendarMonth = $scope.calendarFullDate.getMonth();
        $scope.calendarMonthName = monthNames[$scope.calendarFullDate.getMonth()];
        $scope.calendarDay = $scope.calendarFullDate.getDay();
        $scope.calendarDayName = dayNames[$scope.calendarFullDate.getDay()];
        $scope.calendarYear = $scope.calendarFullDate.getFullYear();
        $scope.daysInMonth = new Date($scope.calendarYear, $scope.calendarMonth, 0).getDate();
    }
    $scope.todayCalendarDay = function(){
        $scope.calendarFullDate = new Date();
        $scope.calendarDate = $scope.calendarFullDate.getDate();
        $scope.calendarMonth = $scope.calendarFullDate.getMonth();
        $scope.calendarMonthName = monthNames[$scope.calendarFullDate.getMonth()];
        $scope.calendarDay = $scope.calendarFullDate.getDay();
        $scope.calendarDayName = dayNames[$scope.calendarFullDate.getDay()];
        $scope.calendarYear = $scope.calendarFullDate.getFullYear();
        $scope.daysInMonth = new Date($scope.calendarYear, $scope.calendarMonth, 0).getDate();
    }
    $scope.todayCalendarDay();

    $scope.getLocationList = function(val) {
        if( val == undefined )
            val = "";

        return $http.get('/list/locationtotallist?location=' + val )
            .then(function(response){
                return response.data.map(function(item){
                    return item;
                });
            });
    };

    $scope.generateReport = function(){
        
        let jsonData = {
            "reportBy": $scope.reportBy,
            "reportDuration": $scope.reportDuration
        }
        $http({
            method: 'POST',
            url: '/hskp/generatePublicAreaReport',
            data: JSON.stringify(jsonData),
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            document.getElementById('public_area_report').innerHTML = response.data;
        }).catch(function(response) {
        })
        .finally(function() {
        });
    }

    $scope.downloadReport = function(){

        $http({
            method: 'POST',
            url: '/hskp/downloadPublicAreaReport'
        }).then(function(response) {
            console.log(response.data);
            var a = document.createElement("a");
				a.href = response.data;
				a.download = 'public_area_report.pdf';
				document.body.appendChild(a);
				a.click();
				a.remove();
            
        }).catch(function(response) {
        })
        .finally(function() {
        });
    }

    
    $scope.addPublicArea = function()
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/public_area_add.html',
            controller: 'PublicAreaCreateController',
            scope: $scope,
            resolve: {
            }
        });

        modalInstance.result.then(function (list) {
        }, function () {
        });

    }

    $scope.addPublicAreaMain = function()
    {
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/public_area_main_add.html',
            controller: 'PublicAreaMainCreateController',
            scope: $scope,
            resolve: {
                
            }
        });
        modalInstance.result.then(function (list) {
        }, function () {
        });
    }

    $scope.onViewPublicAreaMain = function(area){
        // console.log(area.id);
        // console.log(area.name);
        $scope.mainTaskId = area.id;
        $scope.mainTaskName = area.name;
        $scope.getTasksByMainId(area);
    }

    $scope.onEditPublicArea = function(area){
        $scope.areaCodeId = area.id;
        $scope.name = area.name;
        // $scope.start_time = area.start_time;
        // $scope.end_time = area.end_time;
        $scope.time_out = area.time_out;
        $scope.location_id = area.location_id;

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/public_area_edit.html',
            controller: 'PublicAreaEditController',
            scope: $scope,
            resolve: {
            }
        });

        modalInstance.result.then(function (list) {
        }, function () {
        });

    }

    $scope.onEditPublicAreaMain = function(area){
        $scope.areaCodeMainId = area.id;
        $scope.aCMainName = area.name;

        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/public_area_main_edit.html',
            controller: 'PublicAreaMainEditController',
            scope: $scope,
            resolve: {
            }
        });

        modalInstance.result.then(function (list) {
        }, function () {
        });

    }

    $scope.onChangePAActive = function(area)
    {
        request = {};
        request.id = area.id;
        request.status = area.status;
        $http({
            method: 'POST',
            url: '/hskp/publicAreaEditTaskActive',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
        }).catch(function(response) {
        })
        .finally(function() {
        });

    }

    $scope.onViewQRCode = function(area)
    {
        $scope.areaCodeId = area.id;
        var modalInstance = $uibModal.open({
            templateUrl: 'tpl/housekeeping/modal/public_area_qrcode_view.html',
            controller: 'PublicAreaViewQRCodeController',
            scope: $scope,
            resolve: {
            }
        });

        modalInstance.result.then(function (list) {
        }, function () {
        });
    }


});

app.controller('PublicAreaCreateController', function($scope, $uibModalInstance, $http, AuthService, toaster, liveserver) {
    $scope.model = {};

    $scope.model.time_out = "5";

    $scope.model.active = false;
    $scope.location_tags = [];

    $scope.create = function () {        
        var request = {};

        if($scope.location_tags.length > 1){
            toaster.pop('error',"Location Error","Please enter only 1 location");
            return;
        }

        request.main_task_id = $scope.mainTaskId;
        // request.start_time = moment($scope.model.start_time).format('YYYY-MM-DD HH:mm:ss');
        // request.end_time = moment($scope.model.end_time).format('YYYY-MM-DD HH:mm:ss');
        request.time_out = $scope.model.time_out;
        request.location_id = $scope.location_tags.map(item => item.id).join(',');
        
        // if( request.start_time == "undefined" )
        //     return;

        // if( request.end_time == "undefined" )
        //     return;

        if( request.time_out == "undefined" )
            return;

        $http({
            method: 'POST',
            url: '/hskp/publicAreaAddTask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            if( response.data.code == 200 )
            {
                let data = {};
                data.id = $scope.mainTaskId;
                $scope.getTasksByMainId(data);
                $uibModalInstance.close();
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});
app.controller('PublicAreaMainCreateController', function($scope, $uibModalInstance, $http, AuthService, toaster, liveserver) {
    $scope.model = {};
    
    $scope.create = function () {        
        var request = {};

        request.name = $scope.model.name;
        
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/hskp/publicAreaAddTaskMain',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            if( response.data.code == 200 )
            {
                $scope.getTasksMain();
                $uibModalInstance.close();
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});
app.controller('PublicAreaEditController', function($scope, $uibModalInstance, $http, AuthService, toaster, liveserver) {
    $scope.model = {};

    //alert($scope.time_out);

    $scope.model.active = false;
    $scope.model.id = $scope.areaCodeId;
    // $scope.model.stime = new Date("2020/01/01 "+$scope.start_time);
    // $scope.model.etime = new Date("2020/01/01 "+$scope.end_time);
    $scope.model.time_out = $scope.time_out;
    $scope.location_tags = [];

    var taskIdsRequest = {};
    taskIdsRequest.location_id = $scope.location_id;
    $http({
        method: 'POST',
        url: '/hskp/publicAreaGetLocationsWithIds',
        data: taskIdsRequest,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    }).then(function(response) {
            $scope.location_tags = response.data;
    }).catch(function(response) {
    })
    .finally(function() {

    });

    $scope.save = function () {        
        var request = {};

        request.id = $scope.model.id;
        // request.start_time = moment(new Date($scope.model.stime)).format('YYYY-MM-DD HH:mm:ss');
        // request.end_time = moment(new Date($scope.model.etime)).format('YYYY-MM-DD HH:mm:ss');
        request.time_out = $scope.model.time_out;
        request.location_id = $scope.location_tags.map(item => item.id).join(',');

        if($scope.location_tags.length > 1){
            toaster.pop('error',"Location Error","Please enter only 1 location");
            return;
        }
        
        // if( request.start_time == "undefined" )
        //     return;

        // if( request.end_time == "undefined" )
        //     return;

        if( request.time_out == "undefined" )
            return;

        $http({
            method: 'POST',
            url: '/hskp/publicAreaEditTask',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            if( response.data.code == 200 )
            {
                let data = {};
                data.id = $scope.mainTaskId;
                $scope.getTasksByMainId(data);
                $uibModalInstance.close();
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});

app.controller('PublicAreaMainEditController', function($scope, $uibModalInstance, $http, AuthService, toaster, liveserver) {
    $scope.model = {};
    $scope.model.id = $scope.areaCodeMainId;
    $scope.model.name = $scope.aCMainName;
    

    var taskIdsRequest = {};
    taskIdsRequest.location_id = $scope.location_id;

    $scope.save = function () {        
        var request = {};

        request.id = $scope.model.id;
        request.name = $scope.model.name;
        
        if( !request.name )
            return;

        $http({
            method: 'POST',
            url: '/hskp/publicAreaEditTaskMain',
            data: request,
            headers: {'Content-Type': 'application/json; charset=utf-8'}
        }).then(function(response) {
            console.log(response.data);
            if( response.data.code == 200 )
            {
                $scope.getTasksMain();
                $uibModalInstance.close();
            }
        }).catch(function(response) {
        })
        .finally(function() {

        });
    };

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };

});


app.controller('PublicAreaViewQRCodeController', function($scope, $uibModalInstance, $http, AuthService, toaster, liveserver) {
    $scope.model = {};

    $scope.model.active = false;

    var request = {};

    request.id = $scope.areaCodeId;     
    $scope.qrcodeimage = "";
    
    $http({
        method: 'POST',
        url: '/hskp/publicAreaQRCodeGenerator',
        data: request,
        headers: {'Content-Type': 'application/json; charset=utf-8'}
    }).then(function(response) {

        $scope.qrcodeimage = response.data;          
    }).catch(function(response) {
    })
        .finally(function() {

        });
        

    $scope.cancel = function () {
        $uibModalInstance.dismiss();
    };
    $scope.downloadQRCode = function () {

            var a = document.createElement("a");
				a.href = $scope.qrcodeimage;
				a.download = 'qrcode.png';
				document.body.appendChild(a);
				a.click();
				a.remove();
            
    };

});