<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

@include('emails.guestrequest_header')

<body>
    <center>
        <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="bodyTable">
            <tr>
                <td align="center" valign="top" id="bodyCell">                
                    <table border="0" cellpadding="0" cellspacing="0" width="600" id="emailBody">                    
                        <tr>
                            <td align="center" valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td valign="top" class="textContent">
                                            <br /> <p>Dear {{$info['first_name']}} {{$info['last_name']}},</p>                                            
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" width="600" class="flexibleContainerCell">
                                <table border="0" align="Left" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Notification Type</b>: {{$info['type'] == 'Escalated' ? 'Escalation' : $info['type']}}</p>
                                            <p><b>Ticket No</b>: {{$info['task']->ticketno1}}</p>
                                            @if(!empty($info['task']->guest_name))
                                            <p><b>Guest Name</b>: {{$info['task']->guest_name}}</p>
                                            <p><b>Room</b>: {{$info['task']->room}}</p>
                                            
                                            @else
                                            <p><b>Location</b>: {{$info['task']->location_name}} - {{$info['task']->location_type}}</p>
                                            @endif
                                            <p><b>Task</b>: {{$info['task']->task_name}}</p>
                                            <p><b>Duration</b>: {{$info['duration_time']}}</p>
                                            <p><b>Department</b>: {{$info['task']->department}}</p>                                            
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Status</b>: {{$info['status_name']}}</p>
                                            @if(!empty($info['task']->guest_name))
                                            <p><b>Arrival Date</b>: {{$info['arrival_time']}}</p>                                            
                                            <p><b>Departure Date</b>: {{$info['departure_time']}}</p>
                                            <p><b>VIP</b>: {{$info['task']->vip}}</p>
                                            @endif
                                            <p><b>Priority</b>: {{$info['task']->priority_name}}</p>
                                            <p><b>Staff</b>: {{$info['task']->staff_name}}</p>
                                            <p><b>Created By</b>: {{$info['task']->attendant_name}}</p>
                                        </td>
                                    </tr>                                    
                                </table>
                            </td>
                        </tr>
                    </table>    
                </td>
            </tr>
        </table>
        
        @include('emails.guestrequest_footer')

    </center>
</body>

</html>