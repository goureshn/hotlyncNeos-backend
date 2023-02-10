app.controller('TicketFilterCtrl', function($scope) {
  $scope.ticketfilters = [
    {name: 'Todays Tickets', filter:''},
    {name: 'Tickets created by me', filter:'starred'},
    {name: 'Open Tickets', filter:'sent'},
    {name: 'Escalated Tickets', filter:'important'},
    {name: 'By Department', filter:'draft'},
    {name: 'All Tickets', filter:'trash'},
    {name: 'Guest Tickets', filter:'trash'},
    {name: 'Urgency', filter:'trash'},
    {name: 'Schedule Tickets', filter:'trash'},
  ];

  $scope.selectFilter = $scope.ticketfilters[0];
  $scope.onSelectFilter = function(filter) {
    $scope.selectFilter = filter;
  }

});
