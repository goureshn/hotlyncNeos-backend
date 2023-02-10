app.controller('ScheduleReportController', function ($scope, $window, AuthService,$httpParamSerializer, $uibModalInstance, toaster, $http, filter) {
    $scope.schedule = {};
    $scope.schedule.filter = filter;
    $scope.schedule.name = '';
    $scope.schedule.frequency='Daily';
    $scope.schedule.recipient = '';
    $scope.schedule.report_format = 'pdf';
    $scope.schedule.date = new Date();
    $scope.schedule.time =  new Date(2016, 11, 17, 0, 0);
    $scope.schedule.start_time =  new Date(2016, 11, 17, 0, 0);
    $scope.schedule.end_time =  new Date(2016, 11, 17, 0, 0);
    $scope.days = [
        "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
    ];

    $scope.schedule.sel_day = $scope.days[0];

    $scope.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
   
    /*
    $scope.recipient_list = [];
    $http.get('/frontend/guestservice/recipientlist')
        .then(function(response){
            $scope.recipient_list = response.data;
            $scope.recipient_list.forEach(function(item, index) {
                if ((item.email == null) && (item.last_name == null)){
                    item.displayname = item.first_name+' - No e-mail';
                }else if((item.last_name != null) && (item.email != null)){
                    item.displayname = item.first_name+' '+item.last_name+' - '+item.email;
                }else if ((item.last_name == null) && (item.email != null)){
                    item.displayname = item.first_name+' - '+item.email;
                }else {
                    item.displayname = item.first_name+' '+item.last_name+' - No email';
                }
            });
               
       });
    $scope.selected_recipient = {};
    $scope.onRecipientSelect = function ($item, $model, $label) {
        $scope.schedule.recipient = $item.email;
    }; 
     
   */
    $scope.open = function($event) {
        $scope.schedule.opened = true;
    };

    $scope.loadFilters = function(query, filter_name) {
        var filter = {};

        var profile = AuthService.GetCredentials();

        filter.property_id = profile.property_id;
        filter.filter_name = filter_name;
        filter.filter = query;

        var param = $httpParamSerializer(filter);

        return $http.get('/frontend/report/filterlist?' + param);
    }

    $scope.onSaveSchedule = function() {
        
        console.log($scope.schedule);
       
        $scope.schedule.date = moment($scope.schedule.date).format("YYYY-MM-DD");
        $scope.schedule.day = $scope.schedule.sel_day;
        if($scope.schedule.attach == true) $scope.schedule.attach_flag = 1;
        if($scope.schedule.attach == false) $scope.schedule.attach_flag = 0;
        if($scope.schedule.repeat == true) $scope.schedule.repeat_flag = 1;
        if($scope.schedule.repeat == false) $scope.schedule.repeat_flag = 0;
        $scope.schedule.time = moment($scope.schedule.time).format('HH:mm:ss');
        $scope.schedule.start_time = moment($scope.schedule.start_time).format('HH:mm:ss');
        $scope.schedule.end_time = moment($scope.schedule.end_time).format('HH:mm:ss');
        /*
        var email_tags = [];
        if($scope.email_tags != null) {
            for (var i = 0; i < $scope.email_tags.length; i++)
                email_tags.push($scope.email_tags[i].text);
        }
        $scope.schedule.recipient = JSON.stringify(email_tags);
        */
        $scope.schedule.recipient = generateFilters($scope.schedule.email_tags);
        
        $uibModalInstance.close($scope.schedule);
        
    }
    function generateFilters(tags) {
        var report_tags = [];
        if( tags )
        {
            for(var i = 0; i < tags.length; i++)
                report_tags.push(tags[i].text);
        }

        return JSON.stringify(report_tags);
    }

    

    $scope.dateOptions = {
        formatYear: 'yy',
        startingDay: 1,
        class: 'datepicker'
    };
    $scope.canselModalReport = function() {
        $uibModalInstance.close();
    }
});
