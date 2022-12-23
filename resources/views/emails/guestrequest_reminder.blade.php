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
                                            <br /> <p>Dear Ennovatech,</p>                                            
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
                                            <p><b>Ticket No</b>: {{$info['task']->ticketno1}}</p>
                                            <p><b>Notification Type</b>: {{$info['type']}}</p>
                                            <p><b>Room</b>: {{$info['task']->room}}</p>                                            
                                            <p><b>Guest</b>: {{$info['task']->guest_name}}</p>                                           
                                        </td>
                                    </tr>                                    
                                </table>
                                <table border="0" align="Right" cellpadding="0" cellspacing="0" width="260" class="flexibleContainer">
                                    <tr>
                                       <td valign="top" class="textContent">
                                            <p><b>Request</b>: {{$info['task']->quantity}} x {{$info['task']->task_name}}</p>
                                            <p><b>Request Time</b>: {{$info['request_time']}}</p>
                                            <p><b>Created by</b>: {{$info['task']->attendant_name}}</p>
                                            <p><b>Assigned To</b>: {{$info['task']->wholename}}</p>
                                            <p><b>Expected Delvery</b>: {{$info['expected_time']}}</p>
                                            <p><b>Time Elapsed:</b>: {{$info['elapsed_time']}}</p>
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